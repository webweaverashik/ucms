<?php
namespace App\Http\Controllers;

use Mpdf\Mpdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Student\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Models\Payment\PaymentInvoice;
use App\Models\Academic\SecondaryClass;
use App\Models\Payment\PaymentTransaction;
use App\Models\Payment\SecondaryClassPayment;

class PdfController extends Controller
{
    public function downloadAdmissionForm(string $id)
    {
        $student = Student::find($id); // Use findOrFail to handle cases where the student doesn't exist

        // If not found or trashed, redirect with warning
        if (! $student || $student->trashed()) {
            return redirect()->route('students.index')->with('error', 'Student not found or deleted.');
        }

        // Restrict access: Only allow editing if the user belongs to the same branch
        if (auth()->user()->branch_id != 0 && auth()->user()->branch_id != $student->branch_id) {
            return redirect()->route('students.index')->with('warning', 'Student not in this branch.');
        }

        // Inactive student should not be able to download admission form
        if ($student->student_activation_id && $student->studentActivation?->active_status !== 'active') {
            return redirect()->route('students.index')->with('warning', 'This student is inactive.');
        }

        return view('pdf.admission_form_layout', ['student' => $student]);
    }

    public function downloadPaySlip(string $id)
    {
        $transaction = PaymentTransaction::find($id);

        if (! $transaction || ! $transaction->is_approved || (auth()->user()->branch_id != 0 && $transaction->student->branch_id != auth()->user()->branch_id)) {
            return redirect()->route('transactions.index')->with('warning', 'TXN not found or not approved.');
        }

        // -- mPDF Configuration --
        $tempDir = storage_path('app/mpdf');

        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $pdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => [80, 100], // width: 80mm, height: auto or fixed like 297mm
            'tempDir'          => $tempDir,
            'default_font'     => 'arial',
            'autoScriptToLang' => true,
            'autoLangToFont'   => true,
            'margin_top'       => 3,
            'margin_bottom'    => 0,
            'margin_left'      => 3,
            'margin_right'     => 3,
        ]);

        $html = view('pdf.payslip', compact('transaction'))->render();

        $pdf->WriteHTML($html);

        return $pdf->Output($transaction->voucher_no . '.pdf', 'I'); // I = Inline view, D = Download
    }

/**
     * Download statement - auto-detects statement type from invoice
     * 
     * If invoice_id is provided and it's a 'Special Class Fee' invoice,
     * the controller auto-finds the secondary_class_id and generates special class statement.
     * Otherwise, generates regular statement.
     * 
     * @param Request $request
     * - student_id (required)
     * - statement_year (required)
     * - invoice_id (optional) - used to auto-detect Special Class Fee
     */
    public function downloadStatement(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'statement_year' => 'required|integer',
            'invoice_id' => 'nullable|exists:payment_invoices,id',
        ]);

        $student = Student::with(['class', 'batch'])->findOrFail($request->student_id);
        $year = $request->statement_year;

        // Debug: Log all request data (check storage/logs/laravel.log)
        Log::info('=== Statement Download Request ===', [
            'all_input' => $request->all(),
            'invoice_id_raw' => $request->input('invoice_id'),
            'invoice_id_filled' => $request->filled('invoice_id'),
        ]);

        // If invoice_id is provided, check if it's a Special Class Fee
        if ($request->filled('invoice_id')) {
            $invoice = PaymentInvoice::with('invoiceType')->find($request->invoice_id);

            Log::info('Invoice Lookup Result', [
                'searched_id' => $request->invoice_id,
                'invoice_found' => $invoice ? 'YES' : 'NO',
                'invoice_type_id' => $invoice?->invoice_type_id,
                'invoice_type_name' => $invoice?->invoiceType?->type_name,
                'is_special_class' => $invoice?->invoiceType?->type_name === 'Special Class Fee',
            ]);

            if ($invoice && $invoice->invoiceType?->type_name === 'Special Class Fee') {
                // Find the secondary class from SecondaryClassPayment
                $secondaryClassPayment = SecondaryClassPayment::where('invoice_id', $invoice->id)->first();

                Log::info('SecondaryClassPayment Lookup', [
                    'invoice_id' => $invoice->id,
                    'found' => $secondaryClassPayment ? 'YES' : 'NO',
                    'secondary_class_id' => $secondaryClassPayment?->secondary_class_id,
                    'record' => $secondaryClassPayment?->toArray(),
                ]);

                if ($secondaryClassPayment) {
                    Log::info('>>> Generating SPECIAL CLASS Statement');
                    return $this->generateSpecialClassStatement(
                        $student,
                        $year,
                        $secondaryClassPayment->secondary_class_id
                    );
                } else {
                    Log::warning('SecondaryClassPayment NOT FOUND - falling back to regular statement');
                }
            }
        } else {
            Log::info('invoice_id not provided or empty');
        }

        Log::info('>>> Generating REGULAR Statement');
        // Otherwise, generate regular statement
        return $this->generateRegularStatement($student, $year);
    }

    /**
     * Generate regular student statement
     */
    private function generateRegularStatement(Student $student, int $year)
    {
        // Get tuition fee transactions where month_year belongs to the requested year
        $tuitionFeeTransactions = PaymentTransaction::with([
            'paymentInvoice.invoiceType',
            'createdBy:id,name',
        ])
            ->where([
                ['student_id', '=', $student->id],
                ['is_approved', '=', true],
            ])
            ->whereHas('paymentInvoice', function ($query) use ($year) {
                $query->whereHas('invoiceType', function ($q) {
                    $q->where('type_name', 'Tuition Fee');
                })
                    ->where('month_year', 'LIKE', '%_' . $year);
            })
            ->get();

        // Get other fee transactions where invoice was created in the requested year
        // EXCLUDING both 'Tuition Fee' and 'Special Class Fee'
        $otherFeeTransactions = PaymentTransaction::with([
            'paymentInvoice.invoiceType',
            'createdBy:id,name',
        ])
            ->where([
                ['student_id', '=', $student->id],
                ['is_approved', '=', true],
            ])
            ->whereHas('paymentInvoice', function ($query) use ($year) {
                $query->whereHas('invoiceType', function ($q) {
                    $q->whereNotIn('type_name', ['Tuition Fee', 'Special Class Fee']);
                })
                    ->whereYear('created_at', $year);
            })
            ->get();

        // Merge both collections
        $transactions = $tuitionFeeTransactions->merge($otherFeeTransactions);

        if ($transactions->isEmpty()) {
            return response()->json(['error' => "No transactions found for {$year}."], 404);
        }

        $totalPaid = $transactions->sum('amount_paid');

        // Group tuition fee transactions by month number
        $monthlyPayments = $tuitionFeeTransactions
            ->groupBy(function ($t) {
                $monthYear = $t->paymentInvoice->month_year;
                if ($monthYear && preg_match('/^(\d{1,2})_(\d{4})$/', $monthYear, $matches)) {
                    return (int) $matches[1];
                }
                return (int) Carbon::parse($t->paymentInvoice->created_at)->format('n');
            })
            ->map(function (Collection $monthGroup) {
                return $monthGroup
                    ->groupBy('payment_invoice_id')
                    ->map(function (Collection $invoiceGroup) {
                        $first = $invoiceGroup->first();
                        $sumPaid = $invoiceGroup->sum('amount_paid');
                        $first->amount_paid = $sumPaid;
                        return $first;
                    });
            });

        return view('pdf.student_statement', compact(
            'student',
            'monthlyPayments',
            'transactions',
            'totalPaid',
            'year'
        ));
    }

    /**
     * Generate special class statement
     * 
     * Simply sums all 'Special Class Fee' payments by month for the selected secondary class.
     * Works for both 'monthly' and 'one_time' payment types - just sums amounts per month.
     */
    private function generateSpecialClassStatement(Student $student, int $year, int $secondaryClassId)
    {
        $secondaryClass = SecondaryClass::findOrFail($secondaryClassId);

        // Get all SecondaryClassPayments for this student and secondary class
        // Filter by 'Special Class Fee' invoice type and the given year
        $payments = SecondaryClassPayment::with([
            'invoice.invoiceType',
            'invoice.paymentTransactions' => function ($query) {
                $query->where('is_approved', true);
            },
            'invoice.paymentTransactions.createdBy:id,name',
        ])
            ->where('student_id', $student->id)
            ->where('secondary_class_id', $secondaryClassId)
            ->whereHas('invoice', function ($query) use ($year) {
                // Filter by 'Special Class Fee' invoice type
                $query->whereHas('invoiceType', function ($q) {
                    $q->where('type_name', 'Special Class Fee');
                })
                // Match year from month_year field OR created_at
                ->where(function ($q) use ($year) {
                    $q->where('month_year', 'LIKE', '%_' . $year)
                      ->orWhereYear('created_at', $year);
                });
            })
            ->get();

        if ($payments->isEmpty()) {
            return response()->json([
                'error' => "No special class payments found for {$year}."
            ], 404);
        }

        // Group payments by month and sum amounts
        // This works for BOTH 'monthly' and 'one_time' payment types
        $monthlyPayments = $payments
            ->groupBy(function ($payment) {
                $monthYear = $payment->invoice->month_year;
                // Extract month from month_year format (e.g., "01_2025")
                if ($monthYear && preg_match('/^(\d{1,2})_(\d{4})$/', $monthYear, $matches)) {
                    return (int) $matches[1];
                }
                // Fallback to invoice created_at month
                return (int) Carbon::parse($payment->invoice->created_at)->format('n');
            })
            ->map(function (Collection $monthGroup) {
                // Get all approved transactions for this month
                $allTransactions = $monthGroup->flatMap(
                    fn($p) => $p->invoice->paymentTransactions
                );

                // Sum all amounts for the month (regardless of payment_type)
                $totalPaid = $allTransactions->sum('amount_paid');
                
                // Sum all dues for the month
                $totalDue = $monthGroup->sum(fn($p) => $p->invoice->amount_due ?? 0);
                
                // Count number of payments (transactions)
                $paymentCount = $allTransactions->count();

                // Get last transaction for receiver name and date
                $lastTransaction = $allTransactions->sortByDesc('created_at')->first();

                return (object) [
                    'total_paid' => $totalPaid,
                    'total_due' => $totalDue,
                    'payment_count' => $paymentCount,
                    'receiver_name' => $lastTransaction?->createdBy?->name,
                    'last_payment_date' => $lastTransaction?->created_at,
                ];
            });

        // Calculate overall totals
        $totalPaid = $monthlyPayments->sum('total_paid');
        $totalDue = $monthlyPayments->sum('total_due');

        return view('pdf.special_class_statement', compact(
            'student',
            'secondaryClass',
            'monthlyPayments',
            'totalPaid',
            'totalDue',
            'year'
        ));
    }
}
