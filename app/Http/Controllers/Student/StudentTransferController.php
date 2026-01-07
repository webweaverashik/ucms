<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Academic\Batch;
use App\Models\Branch;
use App\Models\Student\Student;
use App\Models\Student\StudentTransferHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentTransferController extends Controller
{
    public function index()
    {
        // permission check
        if (!auth()->user()->can('students.transfer')) {
            return redirect()->back()->with('warning', 'No permission to transfer students.');
        }

        // Get transfer logs without eager loading branches yet
        $transfer_logs = StudentTransferHistory::with(['transferredBy:id,name', 'fromBatch:id,name', 'toBatch:id,name', 'student:id,name,student_unique_id,gender,photo_url'])
            ->latest()
            ->get();

        // Collect all unique branch IDs from both from_branch_id and to_branch_id
        $fromBranchIds = $transfer_logs->pluck('from_branch_id')->filter();
        $toBranchIds = $transfer_logs->pluck('to_branch_id')->filter();

        $allBranchIds = $fromBranchIds->merge($toBranchIds)->unique();

        // Load all branches in ONE query
        $branches = Branch::whereIn('id', $allBranchIds)
            ->select('id', 'branch_name', 'branch_prefix') // include prefix if needed
            ->get()
            ->keyBy('id');

        // Manually attach branch data to each log to avoid N+1 or duplicate queries
        foreach ($transfer_logs as $log) {
            $log->fromBranch = $branches->get($log->from_branch_id);
            $log->toBranch = $branches->get($log->to_branch_id);
        }

        // Load active students for modal
        $students = Student::active()
            ->with(['branch:id,branch_name,branch_prefix', 'class:id,name,class_numeral', 'batch:id,name'])
            ->whereHas('class', fn($q) => $q->active())
            ->select('id', 'name', 'student_unique_id', 'branch_id', 'class_id', 'batch_id')
            ->get();

        return view('students.transfer', compact('students', 'transfer_logs'));
    }

    /**
     * Return JSON student info (for UI panel)
     */
    public function studentInfo(Student $student)
    {
        if (!auth()->user()->can('students.transfer')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // load relations
        $student->load(['class', 'studentActivation']);

        $photoUrl = null;
        if ($student->photo_url) {
            // try Storage first, fallback to asset()
            $photoUrl = Storage::exists($student->photo_url) ? Storage::url($student->photo_url) : asset($student->photo_url);
        }

        return response()->json([
            'id' => $student->id,
            'name' => $student->name,
            'student_unique_id' => $student->student_unique_id,
            'class_name' => $student->class ? $student->class->name : null,
            'admission_date' => $student->created_at ? $student->created_at->toDateString() : null,
            'address' => $student->address,
            'branch_id' => $student->branch_id,
            'batch_id' => $student->batch_id,
            'photo' => $photoUrl,
        ]);
    }

    /**
     * Return branches excluding student's current branch
     */
    public function availableBranches(Student $student)
    {
        if (!auth()->user()->can('students.transfer')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $branches = Branch::where('id', '<>', $student->branch_id)->select('id', 'branch_name', 'branch_prefix')->get();

        return response()->json($branches);
    }

    /**
     * Return batches for a branch
     */
    public function batchesByBranch(Branch $branch)
    {
        if (!auth()->user()->can('students.transfer')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Assuming Batch model has branch_id column
        $batches = Batch::where('branch_id', $branch->id)->select('id', 'name')->get();

        return response()->json($batches);
    }

    /**
     * Store transfer (update student's branch and batch)
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('students.transfer')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'branch_id' => 'required|exists:branches,id',
            'batch_id' => 'required|exists:batches,id',
        ]);

        DB::beginTransaction();
        try {
            // Inside the store() method, after successful save:
            $student = Student::findOrFail($data['student_id']);

            $oldBranchId = $student->branch_id;
            $oldBatchId = $student->batch_id;

            // Update new values
            $student->branch_id = $data['branch_id'];
            $student->batch_id = $data['batch_id'];
            $student->save();

            // Log history
            StudentTransferHistory::create([
                'student_id' => $student->id,
                'from_branch_id' => $oldBranchId,
                'to_branch_id' => $data['branch_id'],
                'from_batch_id' => $oldBatchId,
                'to_batch_id' => $data['batch_id'],
                'transferred_by' => Auth::id(),
                'transferred_at' => now(),
            ]);

            DB::commit();

            return response()->json(['message' => 'Student transferred successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('Student Transfer Failed: ' . $e->getMessage(), [
                'student_id' => $data['student_id'] ?? null,
                'exception' => $e,
            ]);

            return response()->json(
                [
                    'message' => 'Transfer failed. Please try again or contact support.',
                ],
                500,
            );
        }
    }
}
