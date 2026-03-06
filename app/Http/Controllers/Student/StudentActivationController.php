<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Academic\ClassName;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentInvoiceType;
use App\Models\Student\Student;
use App\Models\Student\StudentActivation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StudentActivationController extends Controller
{
    public function approve(Request $request, string $id): JsonResponse
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
            $reason = $hasDueInvoice && $user->isAdmin()
                ? 'Approved with pending tuition fee'
                : 'Admission Approved';

            // Create Activation Entry
            $activation = StudentActivation::create([
                'student_id'    => $student->id,
                'active_status' => $request->active_status,
                'reason'        => $reason,
                'updated_by'    => Auth::id(),
            ]);

            // Update Student's Activation ID
            $student->update(['student_activation_id' => $activation->id]);

            // AutoSMS for student registration success
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

    public function toggleActive(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'student_id'    => 'required|integer|exists:students,id',
                'active_status' => 'required|in:active,inactive',
                'reason'        => 'required|string|max:255',
                'class_id'      => 'nullable|integer|exists:class_names,id',
            ]);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ],
                422,
            );
        }

        $student = Student::with(['studentActivation', 'payments', 'branch', 'mobileNumbers', 'guardians'])->findOrFail($request->student_id);

        // Authorization check: Non-admin users can only toggle students from their own branch
        $user = auth()->user();
        if ($user->branch_id != 0 && $student->branch_id !== $user->branch_id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'You are not authorized to modify students from other branches.',
                ],
                403,
            );
        }

        // Check if student is being activated from inactive status
        $isBeingActivated = $request->active_status === 'active' 
            && $student->studentActivation 
            && $student->studentActivation->active_status === 'inactive';

        try {
            return DB::transaction(function () use ($request, $student, $user, $isBeingActivated) {
                // Create Activation Entry
                $activation = StudentActivation::create([
                    'student_id'    => $student->id,
                    'active_status' => $request->active_status,
                    'reason'        => $request->reason,
                    'updated_by'    => Auth::id(),
                ]);

                // Update Student's Activation ID
                $student->update(['student_activation_id' => $activation->id]);

                // Create current month tuition fee invoice if student is being activated from inactive
                $invoiceCreated = false;
                $invoiceMessage = null;

                if ($isBeingActivated) {
                    $invoiceResult  = $this->createCurrentMonthTuitionFeeInvoice($student);
                    $invoiceCreated = $invoiceResult['created'];
                    $invoiceMessage = $invoiceResult['message'];
                }

                // Clear the cache
                clearServerCache();

                $statusText = $request->active_status === 'active' ? 'activated' : 'deactivated';

                // Calculate stats only if class_id is provided (for classnames view page)
                $stats = $this->calculateClassStats($request->class_id, $user);

                $responseMessage = "Student has been {$statusText} successfully.";
                if ($invoiceMessage) {
                    $responseMessage .= ' ' . $invoiceMessage;
                }

                return response()->json([
                    'success'         => true,
                    'message'         => $responseMessage,
                    'new_status'      => $request->active_status,
                    'stats'           => $stats,
                    'invoice_created' => $invoiceCreated,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error toggling student status: ' . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'An error occurred while updating student status.',
                ],
                500,
            );
        }
    }

    /**
     * Bulk toggle activation for multiple students
     * Works for both:
     * - Students index page (no class_id, no stats needed)
     * - Classnames view page (class_id provided, stats returned)
     */
    public function bulkToggleActive(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'student_ids'   => 'required|array|min:1',
                'student_ids.*' => 'integer|exists:students,id',
                'active_status' => 'required|in:active,inactive',
                'reason'        => 'required|string|max:255',
                'class_id'      => 'nullable|integer|exists:class_names,id', // Optional - only for classnames view
            ]);
        } catch (ValidationException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors'  => $e->errors(),
                ],
                422,
            );
        }

        $user         = auth()->user();
        $studentIds   = $request->student_ids;
        $activeStatus = $request->active_status;
        $reason       = $request->reason;
        $classId      = $request->class_id; // May be null for students index page

        // Get all students with necessary relationships
        $students = Student::with(['studentActivation', 'payments', 'branch', 'mobileNumbers', 'guardians'])
            ->whereIn('id', $studentIds)
            ->get();

        // Authorization check for non-admin users
        if ($user->branch_id != 0) {
            $unauthorizedStudents = $students->filter(fn($s) => $s->branch_id !== $user->branch_id);
            if ($unauthorizedStudents->isNotEmpty()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'You are not authorized to modify some students from other branches.',
                    ],
                    403,
                );
            }
        }

        try {
            return DB::transaction(function () use ($students, $activeStatus, $reason, $classId, $user) {
                $successCount        = 0;
                $failedCount         = 0;
                $invoicesCreated     = 0;
                $invoicesSkipped     = 0;
                $freeStudentsSkipped = 0;

                foreach ($students as $student) {
                    try {
                        // Check if student is being activated from inactive status
                        $isBeingActivated = $activeStatus === 'active'
                            && $student->studentActivation
                            && $student->studentActivation->active_status === 'inactive';

                        // Create Activation Entry
                        $activation = StudentActivation::create([
                            'student_id'    => $student->id,
                            'active_status' => $activeStatus,
                            'reason'        => $reason,
                            'updated_by'    => Auth::id(),
                        ]);

                        // Update Student's Activation ID
                        $student->update(['student_activation_id' => $activation->id]);

                        // Create current month tuition fee invoice if student is being activated from inactive
                        if ($isBeingActivated) {
                            $invoiceResult = $this->createCurrentMonthTuitionFeeInvoice($student);
                            if ($invoiceResult['created']) {
                                $invoicesCreated++;
                            } elseif ($invoiceResult['reason'] === 'free_student') {
                                $freeStudentsSkipped++;
                            } else {
                                $invoicesSkipped++;
                            }
                        }

                        $successCount++;
                    } catch (\Exception $e) {
                        Log::error('Error activating student ID ' . $student->id . ': ' . $e->getMessage());
                        $failedCount++;
                    }
                }

                $statusText = $activeStatus === 'active' ? 'activated' : 'deactivated';

                // Calculate stats only if class_id is provided (for classnames view page)
                $stats = $this->calculateClassStats($classId, $user);

                // Clear server cache after bulk update
                clearServerCache();

                // Build response message
                $message = "{$successCount} student(s) have been {$statusText} successfully.";

                if ($failedCount > 0) {
                    $message = "{$successCount} student(s) {$statusText} successfully. {$failedCount} failed.";
                }

                if ($invoicesCreated > 0) {
                    $message .= " {$invoicesCreated} tuition fee invoice(s) created.";
                }

                if ($freeStudentsSkipped > 0) {
                    $message .= " {$freeStudentsSkipped} free student(s) skipped (no invoice).";
                }

                if ($invoicesSkipped > 0) {
                    $message .= " {$invoicesSkipped} invoice(s) skipped (already exists).";
                }

                return response()->json([
                    'success'              => true,
                    'message'              => $message,
                    'new_status'           => $activeStatus,
                    'success_count'        => $successCount,
                    'failed_count'         => $failedCount,
                    'invoices_created'     => $invoicesCreated,
                    'invoices_skipped'     => $invoicesSkipped,
                    'free_students_skipped' => $freeStudentsSkipped,
                    'stats'                => $stats,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error in bulk toggle activation: ' . $e->getMessage());

            return response()->json(
                [
                    'success' => false,
                    'message' => 'An error occurred while updating student statuses.',
                ],
                500,
            );
        }
    }

    /**
     * Create current month tuition fee invoice for a student
     *
     * @param Student $student
     * @return array ['created' => bool, 'message' => string|null, 'reason' => string|null]
     */
    private function createCurrentMonthTuitionFeeInvoice(Student $student): array
    {
        try {
            // Get tuition fee invoice type
            $tuitionFeeType = PaymentInvoiceType::where('type_name', 'Tuition Fee')->first();

            if (! $tuitionFeeType) {
                Log::warning('Tuition Fee invoice type not found');
                return ['created' => false, 'message' => null, 'reason' => 'type_not_found'];
            }

            // Get current month year in format MM_YYYY
            $currentMonthYear = now()->format('m_Y');

            // Check if invoice already exists for current month
            $existingInvoice = PaymentInvoice::where('student_id', $student->id)
                ->where('invoice_type_id', $tuitionFeeType->id)
                ->where('month_year', $currentMonthYear)
                ->exists();

            if ($existingInvoice) {
                return ['created' => false, 'message' => 'Current month invoice already exists.', 'reason' => 'already_exists'];
            }

            // Get tuition fee amount from student's payment profile
            $tuitionFee = $student->payments->tuition_fee ?? 0;

            // Skip invoice creation for free students (tuition fee is 0)
            if ($tuitionFee <= 0) {
                return ['created' => false, 'message' => null, 'reason' => 'free_student'];
            }

            // Generate invoice number
            $yearSuffix = now()->format('y');
            $month      = now()->format('m');
            $prefix     = $student->branch->branch_prefix ?? 'INV';

            $lastInvoice = PaymentInvoice::withTrashed()
                ->where('invoice_number', 'like', "{$prefix}{$yearSuffix}{$month}_%")
                ->latest('invoice_number')
                ->first();

            $nextSequence = $lastInvoice
                ? ((int) substr($lastInvoice->invoice_number, strrpos($lastInvoice->invoice_number, '_') + 1)) + 1
                : 1001;

            $invoiceNumber = "{$prefix}{$yearSuffix}{$month}_{$nextSequence}";

            // Create the invoice
            $invoice = PaymentInvoice::create([
                'invoice_number'  => $invoiceNumber,
                'student_id'      => $student->id,
                'invoice_type_id' => $tuitionFeeType->id,
                'total_amount'    => $tuitionFee,
                'amount_due'      => $tuitionFee,
                'month_year'      => $currentMonthYear,
                'created_by'      => auth()->id(),
            ]);

            // Send SMS notifications
            $this->sendTuitionFeeInvoiceSms($student, $invoice);

            return ['created' => true, 'message' => 'Tuition fee invoice created.', 'reason' => null];

        } catch (\Exception $e) {
            Log::error('Error creating tuition fee invoice for student ID ' . $student->id . ': ' . $e->getMessage());
            return ['created' => false, 'message' => 'Failed to create invoice.', 'reason' => 'error'];
        }
    }

    /**
     * Send SMS notifications for tuition fee invoice
     *
     * @param Student $student
     * @param PaymentInvoice $invoice
     * @return void
     */
    private function sendTuitionFeeInvoiceSms(Student $student, PaymentInvoice $invoice): void
    {
        try {
            $monthYear = $invoice->month_year;
            $monthName = $monthYear
                ? Carbon::createFromDate(explode('_', $monthYear)[1], explode('_', $monthYear)[0])->format('F')
                : now()->format('F');

            $dueDate = $this->ordinal($student->payments->due_date ?? 1) . ' ' . now()->format('F');

            // Send SMS to student
            $mobileNumber = $student->mobileNumbers->where('number_type', 'sms')->first();
            if ($mobileNumber) {
                send_auto_sms('tuition_fee_invoice_created', $mobileNumber->mobile_number, [
                    'student_name' => $student->name,
                    'month_year'   => $monthName,
                    'amount'       => $invoice->total_amount,
                    'invoice_no'   => $invoice->invoice_number,
                    'due_date'     => $dueDate,
                ]);
            }

            // Send SMS to father/guardian
            $father = $student->guardians->where('relationship', 'father')->first();
            if ($father && $father->mobile_number) {
                send_auto_sms('guardian_tuition_fee_invoice_created', $father->mobile_number, [
                    'student_name' => $student->name,
                    'month_year'   => $monthName,
                    'amount'       => $invoice->total_amount,
                    'invoice_no'   => $invoice->invoice_number,
                    'due_date'     => $dueDate,
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't stop the activation process
            Log::warning('Failed to send tuition fee invoice SMS for student ID ' . $student->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Convert number to ordinal (1st, 2nd, 3rd, etc.)
     *
     * @param int $number
     * @return string
     */
    private function ordinal(int $number): string
    {
        $suffixes = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];

        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        }

        return $number . $suffixes[$number % 10];
    }

    /**
     * Calculate class statistics for active/inactive students
     * Used by classnames view page to update the UI stats
     *
     * @param int|null $classId
     * @param mixed $user
     * @return array|null
     */
    private function calculateClassStats(?int $classId, $user): ?array
    {
        if (! $classId) {
            return null;
        }

        $class = ClassName::find($classId);
        if (! $class) {
            return null;
        }

        $branchId = $user->branch_id;

        $activeCount = $class->activeStudents()
            ->when(! $user->isAdmin(), fn($q) => $q->where('branch_id', $branchId))
            ->count();

        $inactiveCount = $class->inactiveStudents()
            ->when(! $user->isAdmin(), fn($q) => $q->where('branch_id', $branchId))
            ->count();

        return [
            'total'    => $activeCount + $inactiveCount,
            'active'   => $activeCount,
            'inactive' => $inactiveCount,
        ];
    }
}
