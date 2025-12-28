<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Models\Student\StudentActivation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StudentActivationController extends Controller
{
    public function approve(Request $request, string $id)
    {
        $student = Student::findOrFail($id);

        // Check if the student has any unpaid (due or partially_paid) invoice
        $hasDueInvoice = $student->paymentInvoices()
            ->whereIn('status', ['due', 'partially_paid'])
            ->whereHas('invoiceType', function ($query) {
                $query->where('type_name', 'Tuition Fee');
            })
            ->exists();

        if ($hasDueInvoice) {
            return response()->json(['success' => false, 'message' => 'First tuition fee is still due. Cannot approve.']);
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

            // AutoSMS for invoice created
            $mobile = $student->mobileNumbers->where('number_type', 'sms')->first()->mobile_number;
            send_auto_sms("student_registration_success", $mobile, [
                'student_name'       => $student->name,
                'student_unique_id'  => $student->student_unique_id,
                'student_class_name' => $student->class->name,
                'student_batch_name' => $student->batch->name,
                'tuition_fee'        => $student->payments->tuition_fee,
                'due_date'           => $student->payments->due_date,
            ]);

            // Clear the cache
            clearUCMSCaches();

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
