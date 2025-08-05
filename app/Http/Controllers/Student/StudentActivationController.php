<?php
namespace App\Http\Controllers\Student;

use Illuminate\Http\Request;
use App\Models\Student\Student;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Student\StudentActivation;

class StudentActivationController extends Controller
{
    public function approve(Request $request, string $id)
    {
        $student = Student::findOrFail($id);

        // Check if the student has any unpaid (due or partially_paid) invoice
        $hasDueInvoice = $student->paymentInvoices()
            ->whereIn('status', ['due', 'partially_paid'])
            ->exists();

        if ($hasDueInvoice) {
            // return redirect()->back()->with('error', 'Admission fee is still due. Cannot approve.');
            return response()->json(['success' => false, 'message' => 'Admission fee is still due. Cannot approve.']);
        }

        $request->validate([
            'active_status' => 'required|in:active,inactive',
            'reason'        => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($request, $student) {
            // Create Activation Entry
            $activation = StudentActivation::create([
                'student_id'    => $student->id,
                'active_status' => $request->active_status,
                'reason'        => $request->reason,
                'updated_by'    => Auth::id(),
            ]);

            // Update Student's Activation ID
            $student->update(['student_activation_id' => $activation->id]);

            Cache::forget('students_list_branch_' . auth()->user()->branch_id);
            return response()->json(['success' => true, 'message' => 'Student activation updated successfully']);
        });
    }

    public function toggleActive(Request $request)
    {
        // return $request;

        $request->validate([
            'active_status' => 'required|in:active,inactive',
            'reason'        => 'nullable|string|max:255',
        ]);

        $student = Student::findOrFail($request->student_id);

        return DB::transaction(function () use ($request, $student) {
            // Create Activation Entry
            $activation = StudentActivation::create([
                'student_id'    => $student->id,
                'active_status' => $request->active_status,
                'reason'        => $request->reason,
                'updated_by'    => Auth::id(),
            ]);

            // Update Student's Activation ID
            $student->update(['student_activation_id' => $activation->id]);

            Cache::forget('students_list_branch_' . auth()->user()->branch_id);

            return redirect()->back()->with('success', 'Student status updated successfully.');
        });

    }
}
