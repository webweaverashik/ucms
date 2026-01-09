<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Models\Student\StudentActivation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentActivationController extends Controller
{
    public function approve(Request $request, string $id)
    {
        $user    = auth()->user();
        $student = Student::findOrFail($id);

        // Authorization check: Manager can only approve students from their own branch
        if ($user->isManager() && $student->branch_id !== $user->branch_id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You are not authorized to approve students from other branches.',
                ],
                403,
            );
        }

        // Check if the student has any unpaid (due or partially_paid) invoice
        $hasDueInvoice = $student
            ->paymentInvoices()
            ->whereIn('status', ['due', 'partially_paid'])
            ->whereHas('invoiceType', function ($query) {
                $query->where('type_name', 'Tuition Fee');
            })
            ->exists();

        // For Manager: Cannot approve if tuition fee is pending
        if ($user->isManager() && $hasDueInvoice) {
            return response()->json([
                'success' => false,
                'message' => 'First tuition fee is still due. Cannot approve.',
            ]);
        }

        // For Admin: If due invoice exists and not confirmed, ask for confirmation
        if ($user->isAdmin() && $hasDueInvoice && ! $request->boolean('confirm_due')) {
            return response()->json([
                'success'               => false,
                'requires_confirmation' => true,
                'message'               => 'This student tuition fee is still due.',
            ]);
        }

        $request->validate([
            'active_status' => 'required|in:active,inactive',
        ]);

        return DB::transaction(function () use ($request, $student, $hasDueInvoice, $user) {
            // Prepare reason
            $reason = $hasDueInvoice && $user->isAdmin() ? 'Approved with pending tuition fee' : 'Admission Approved';

            // Create Activation Entry
            $activation = StudentActivation::create([
                'student_id'    => $student->id,
                'active_status' => $request->active_status,
                'reason'        => $reason,
                'updated_by'    => Auth::id(),
            ]);

            // Update Student's Activation ID
            $student->update(['student_activation_id' => $activation->id]);

            // AutoSMS for invoice created
            $mobileNumber = $student->mobileNumbers->where('number_type', 'sms')->first();
            if ($mobileNumber) {
                send_auto_sms('student_registration_success', $mobileNumber->mobile_number, [
                    'student_name'       => $student->name,
                    'student_unique_id'  => $student->student_unique_id,
                    'student_class_name' => $student->class->name,
                    'student_batch_name' => $student->batch->name,
                    'tuition_fee'        => $student->payments->tuition_fee ?? 0,
                    'due_date'           => $student->payments->due_date ?? '',
                ]);
            }

            // Clear the cache
            clearServerCache();

            return response()->json([
                'success' => true,
                'message' => 'Student approved successfully.',
            ]);
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

            clearServerCache();

            return redirect()->back()->with('success', 'Student status updated successfully.');
        });
    }
}
