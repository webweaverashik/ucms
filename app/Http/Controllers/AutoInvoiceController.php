<?php
namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentInvoiceType;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoInvoiceController extends Controller
{
    /**
     * Display the auto invoice generation page.
     */
    public function index()
    {
        if (! auth()->user()->isAdmin()) {
            return redirect()->back()->with('warning', 'Unauthorized Access');
        }

        $branches = Branch::get();

        return view('settings.auto-invoice.index', compact('branches'));
    }

    /**
     * Generate invoices for students with 'current' payment style.
     * Uses current month for billing period.
     */
    public function generateCurrent(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            return redirect()->back()->with('warning', 'Unauthorized Access');
        }

        try {
            DB::beginTransaction();

            // Get branch_id from request
            $branchId = $request->query('branch_id');

            // Get current month/year
            $currentMonth = Carbon::now()->format('m');
            $currentYear  = Carbon::now()->format('Y');

            // Month year for current payment style
            $monthYear = "{$currentMonth}_{$currentYear}";

            // Get all active students with 'current' payment style
            $query = Student::with(['studentActivation', 'payments', 'paymentInvoices', 'branch'])
                ->whereHas('studentActivation', fn($q) => $q->where('active_status', 'active'))
                ->whereHas('payments', fn($q) => $q->where('payment_style', 'current'))
                ->whereHas('class', fn($q) => $q->active());

            // Filter by branch if specified
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            $students = $query->get();

            // Tuition Fee type
            $invoice_type = PaymentInvoiceType::where('type_name', 'Tuition Fee')->select('id')->first();

            if (! $invoice_type) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Tuition Fee invoice type not found. Please create it first.');
            }

            $generatedInvoices = 0;
            $skippedReasons    = [
                'existing_invoice' => 0,
                'free_student'     => 0,
            ];

            foreach ($students as $student) {
                // Check if student has zero tuition fee (FREE student)
                if ($student->payments->tuition_fee <= 0) {
                    $skippedReasons['free_student']++;
                    continue;
                }

                // Check if student already has an invoice for this month_year
                $existingInvoice = $student
                    ->paymentInvoices()
                    ->where('month_year', $monthYear)
                    ->whereHas('invoiceType', function ($q) {
                        $q->where('type_name', 'Tuition Fee');
                    })
                    ->exists();

                if ($existingInvoice) {
                    $skippedReasons['existing_invoice']++;
                    continue;
                }

                // Get the branch prefix from student's branch
                $branchPrefix = $student->branch->branch_prefix ?? 'DEF';

                // Format for invoice number (G2506_1001)
                $invoicePrefix = strtoupper($branchPrefix) . substr($currentYear, -2) . $currentMonth . '_';

                // Find the last invoice number with this prefix
                $lastInvoice = PaymentInvoice::where('invoice_number', 'like', $invoicePrefix . '%')
                    ->orderBy('invoice_number', 'desc')
                    ->first();

                $sequence = $lastInvoice ? (int) substr($lastInvoice->invoice_number, strlen($invoicePrefix)) + 1 : 1001;

                // Create the new invoice
                PaymentInvoice::create([
                    'invoice_number'  => $invoicePrefix . $sequence,
                    'student_id'      => $student->id,
                    'total_amount'    => $student->payments->tuition_fee,
                    'amount_due'      => $student->payments->tuition_fee,
                    'month_year'      => $monthYear,
                    'invoice_type_id' => $invoice_type->id,
                    'created_by'      => Auth::id(),
                ]);

                $generatedInvoices++;
            }

            DB::commit();

            // Clearing cache after invoice generation
            $this->clearInvoiceCache();

            // Build detailed message
            $message        = "Generated {$generatedInvoices} current invoice(s).";
            $skippedDetails = [];

            if ($skippedReasons['existing_invoice'] > 0) {
                $skippedDetails[] = "{$skippedReasons['existing_invoice']} with existing invoices";
            }
            if ($skippedReasons['free_student'] > 0) {
                $skippedDetails[] = "{$skippedReasons['free_student']} FREE student(s) (tuition fee = 0)";
            }

            if (! empty($skippedDetails)) {
                $message .= ' Skipped: ' . implode(', ', $skippedDetails) . '.';
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto Invoice Generation Error (Current): ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while generating invoices. Please try again.');
        }
    }

    /**
     * Generate invoices for students with 'due' payment style.
     * Uses last month for billing period.
     */
    public function generateDue(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            return redirect()->back()->with('warning', 'Unauthorized Access');
        }

        try {
            DB::beginTransaction();

            // Get branch_id from request
            $branchId = $request->query('branch_id');

            // Get current and last month/year
            $currentMonth  = Carbon::now()->format('m');
            $currentYear   = Carbon::now()->format('Y');
            $lastMonth     = Carbon::now()->subMonth()->format('m');
            $lastMonthYear = Carbon::now()->subMonth()->format('Y');

            // Month year for due payment style (last month)
            $monthYear = "{$lastMonth}_{$lastMonthYear}";

            // Get all active students with 'due' payment style (not 'current')
            $query = Student::with(['studentActivation', 'payments', 'paymentInvoices', 'branch'])
                ->whereHas('studentActivation', fn($q) => $q->where('active_status', 'active'))
                ->whereHas('payments', fn($q) => $q->where('payment_style', '!=', 'current'))
                ->whereHas('class', fn($q) => $q->active());

            // Filter by branch if specified
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            $students = $query->get();

            // Tuition Fee type (same as current invoices)
            $invoice_type = PaymentInvoiceType::where('type_name', 'Tuition Fee')->select('id')->first();

            if (! $invoice_type) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Tuition Fee invoice type not found. Please create it first.');
            }

            $generatedInvoices = 0;
            $skippedReasons    = [
                'existing_invoice' => 0,
                'free_student'     => 0,
            ];

            foreach ($students as $student) {
                // Check if student has zero tuition fee (FREE student)
                if ($student->payments->tuition_fee <= 0) {
                    $skippedReasons['free_student']++;
                    continue;
                }

                // Check if student already has an invoice for this month_year
                $existingInvoice = $student
                    ->paymentInvoices()
                    ->where('month_year', $monthYear)
                    ->whereHas('invoiceType', function ($q) {
                        $q->where('type_name', 'Tuition Fee');
                    })
                    ->exists();

                if ($existingInvoice) {
                    $skippedReasons['existing_invoice']++;
                    continue;
                }

                // Get the branch prefix from student's branch
                $branchPrefix = $student->branch->branch_prefix ?? 'DEF';

                // Format for invoice number (G2505_1001 - using last month)
                $invoicePrefix = strtoupper($branchPrefix) . substr($lastMonthYear, -2) . $lastMonth . '_';

                // Find the last invoice number with this prefix
                $lastInvoice = PaymentInvoice::where('invoice_number', 'like', $invoicePrefix . '%')
                    ->orderBy('invoice_number', 'desc')
                    ->first();

                $sequence = $lastInvoice ? (int) substr($lastInvoice->invoice_number, strlen($invoicePrefix)) + 1 : 1001;

                // Create the new invoice
                PaymentInvoice::create([
                    'invoice_number'  => $invoicePrefix . $sequence,
                    'student_id'      => $student->id,
                    'total_amount'    => $student->payments->tuition_fee,
                    'amount_due'      => $student->payments->tuition_fee,
                    'month_year'      => $monthYear,
                    'invoice_type_id' => $invoice_type->id,
                    'created_by'      => Auth::id(),
                ]);

                $generatedInvoices++;
            }

            DB::commit();

            // Clearing cache after invoice generation
            $this->clearInvoiceCache();

            // Build detailed message
            $message        = "Generated {$generatedInvoices} due invoice(s).";
            $skippedDetails = [];

            if ($skippedReasons['existing_invoice'] > 0) {
                $skippedDetails[] = "{$skippedReasons['existing_invoice']} with existing invoices";
            }
            if ($skippedReasons['free_student'] > 0) {
                $skippedDetails[] = "{$skippedReasons['free_student']} FREE student(s) (tuition fee = 0)";
            }

            if (! empty($skippedDetails)) {
                $message .= ' Skipped: ' . implode(', ', $skippedDetails) . '.';
            }

            $totalSkipped = array_sum($skippedReasons);
            if ($generatedInvoices === 0 && $totalSkipped === 0) {
                return redirect()->back()->with('warning', 'No students found with due payment style.');
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto Invoice Generation Error (Due): ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while generating due invoices. Please try again.');
        }
    }

    /**
     * Clear invoice-related cache.
     */
    private function clearInvoiceCache(): void
    {
        clearServerCache();
    }
}
