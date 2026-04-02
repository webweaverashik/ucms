<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Academic\Subject;
use App\Models\Academic\SubjectTaken;
use App\Models\Branch;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentPromoteController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('students.promote')) {
            return back()->with('warning', 'No permission to promote students.');
        }

        $user     = auth()->user();
        $branches = $user->isAdmin() ? Branch::all() : Branch::where('id', $user->branch_id)->get();
        $classes  = ClassName::active()->get();

        $managerBatches = [];
        if (! $user->isAdmin()) {
            $managerBatches = Batch::where('branch_id', $user->branch_id)->get();
        }

        return view('students.promote', compact('branches', 'classes', 'managerBatches'));
    }

    public function getStudents(Request $request)
    {
        if (! $request->branch_id || ! $request->class_id) {
            return response()->json(['error' => 'Branch and Class are required'], 422);
        }

        $query = Student::query()
            ->with(['class', 'batch', 'studentActivation'])
            ->where('branch_id', $request->branch_id)
            ->where('class_id', $request->class_id);

        if ($request->batch_id) {
            $query->where('batch_id', $request->batch_id);
        }

        if ($request->academic_group) {
            $query->where('academic_group', $request->academic_group);
        }

        $students = $query->get()->map(function ($student) {
            return [
                'id'        => $student->id,
                'unique_id' => $student->student_unique_id,
                'name'      => $student->name,
                'class'     => $student->class->name ?? 'N/A',
                'class_id'  => $student->class_id,
                'batch'     => $student->batch->name ?? 'N/A',
                'group'     => $student->academic_group,
                'status'    => $student->studentActivation->active_status ?? 'pending',
            ];
        });

        return response()->json($students);
    }

    public function promote(Request $request)
    {
        $request->validate([
            'student_ids'     => 'required|array',
            'target_class_id' => 'required',
            'target_batch_id' => 'required',
        ]);

        $targetClass = ClassName::findOrFail($request->target_class_id);

        DB::beginTransaction();
        try {
            foreach ($request->student_ids as $studentId) {
                $student      = Student::with('class')->findOrFail($studentId);
                $currentClass = $student->class;

                if ($currentClass && $targetClass->class_numeral < $currentClass->class_numeral) {
                    throw new \Exception("Cannot demote student " . $student->name . " to a lower class.");
                }

                $student->update([
                    'class_id' => $request->target_class_id,
                    'batch_id' => $request->target_batch_id,
                ]);

                if ($currentClass && $targetClass->class_numeral > $currentClass->class_numeral) {
                    SubjectTaken::where('student_id', $student->id)->delete();

                    $newSubjects = Subject::where('class_id', $targetClass->id)
                        ->where(function ($q) use ($student) {
                            $q->where('academic_group', 'General')
                                ->orWhere('academic_group', $student->academic_group);
                        })->get();

                    foreach ($newSubjects as $subject) {
                        SubjectTaken::create([
                            'student_id'     => $student->id,
                            'subject_id'     => $subject->id,
                            'is_4th_subject' => ($subject->subject_type === 'optional'),
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Students promoted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
