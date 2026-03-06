<?php
namespace App\Http\Controllers;

use App\Models\Academic\SecondaryClass;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;
use App\Models\Payment\SecondaryClassPayment;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;

class PdfController extends Controller
{
    public function downloadAdmissionForm(string $id)
    {
        $student = Student::find($id);

        if (! $student || $student->trashed()) {
            return redirect()->route('students.index')->with('error', 'Student not found or deleted.');
        }

        if (auth()->user()->branch_id != 0 && auth()->user()->branch_id != $student->branch_id) {
            return redirect()->route('students.index')->with('warning', 'Student not in this branch.');
        }

        if ($student->student_activation_id && $student->studentActivation?->active_status !== 'active') {
            return redirect()->route('students.index')->with('warning', 'This student is inactive.');
        }

        return view('pdf.admission_form_layout', ['student' => $student]);
    }

    // This method is not using currently and made for thermal printer, so we are keeping it simple without using the PDF template and mPDF features like headers/footers, custom fonts, etc.
    public function downloadPaySlip(string $id)
    {
        $transaction = PaymentTransaction::find($id);

        if (! $transaction || ! $transaction->is_approved || (auth()->user()->branch_id != 0 && $transaction->student->branch_id != auth()->user()->branch_id)) {
            return redirect()->route('transactions.index')->with('warning', 'TXN not found or not approved.');
        }

        $tempDir = storage_path('app/mpdf');

        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $pdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => [80, 100],
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

        return $pdf->Output($transaction->voucher_no . '.pdf', 'I');
    }

    /**
     * Download statement - auto-detects statement type from invoice
     */
    public function downloadStatement(Request $request)
    {
        $request->validate([
            'student_id'     => 'required|exists:students,id',
            'statement_year' => 'required|integer',
            'invoice_id'     => 'nullable|exists:payment_invoices,id',
        ]);

        $student = Student::with(['class', 'batch'])->findOrFail($request->student_id);
        $year    = $request->statement_year;

        Log::info('=== Statement Download Request ===', [
            'all_input'         => $request->all(),
            'invoice_id_raw'    => $request->input('invoice_id'),
            'invoice_id_filled' => $request->filled('invoice_id'),
        ]);

        if ($request->filled('invoice_id')) {
            $invoice = PaymentInvoice::with('invoiceType')->find($request->invoice_id);

            Log::info('Invoice Lookup Result', [
                'searched_id'       => $request->invoice_id,
                'invoice_found'     => $invoice ? 'YES' : 'NO',
                'invoice_type_id'   => $invoice?->invoice_type_id,
                'invoice_type_name' => $invoice?->invoiceType?->type_name,
                'is_special_class'  => $invoice?->invoiceType?->type_name === 'Special Class Fee',
            ]);

            if ($invoice && $invoice->invoiceType?->type_name === 'Special Class Fee') {
                $secondaryClassPayment = SecondaryClassPayment::where('invoice_id', $invoice->id)->first();

                Log::info('SecondaryClassPayment Lookup', [
                    'invoice_id'         => $invoice->id,
                    'found'              => $secondaryClassPayment ? 'YES' : 'NO',
                    'secondary_class_id' => $secondaryClassPayment?->secondary_class_id,
                    'record'             => $secondaryClassPayment?->toArray(),
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
        return $this->generateRegularStatement($student, $year);
    }

    /**
     * Generate regular student statement
     *
     * FIXED: Now fetches INVOICES first (not just transactions) to show all months
     * including those with dues but no payments yet.
     */
    private function generateRegularStatement(Student $student, int $year)
    {
        // Get ALL tuition fee INVOICES for the year (regardless of payment status)
        // This ensures we show months with dues even if no payment has been made
        $tuitionFeeInvoices = PaymentInvoice::with([
            'invoiceType',
            'paymentTransactions' => function ($query) {
                $query->where('is_approved', true)->with('createdBy:id,name');
            },
        ])
            ->where('student_id', $student->id)
            ->whereHas('invoiceType', function ($q) {
                $q->where('type_name', 'Tuition Fee');
            })
            ->where('month_year', 'LIKE', '%_' . $year)
            ->get();

        // Get other fee invoices (excluding Tuition Fee and Special Class Fee)
        $otherFeeInvoices = PaymentInvoice::with([
            'invoiceType',
            'paymentTransactions' => function ($query) {
                $query->where('is_approved', true)->with('createdBy:id,name');
            },
        ])
            ->where('student_id', $student->id)
            ->whereHas('invoiceType', function ($q) {
                $q->whereNotIn('type_name', ['Tuition Fee', 'Special Class Fee']);
            })
            ->whereYear('created_at', $year)
            ->get();

        // Merge both collections for total calculation
        $allInvoices = $tuitionFeeInvoices->merge($otherFeeInvoices);

        if ($allInvoices->isEmpty()) {
            return response()->json(['error' => "No invoices found for {$year}."], 404);
        }

        // Calculate total paid from all approved transactions
        $totalPaid = $allInvoices->sum(function ($invoice) {
            return $invoice->paymentTransactions->sum('amount_paid');
        });

        // Group tuition fee invoices by month number
        $monthlyPayments = $tuitionFeeInvoices
            ->groupBy(function ($invoice) {
                $monthYear = $invoice->month_year;
                if ($monthYear && preg_match('/^(\d{1,2})_(\d{4})$/', $monthYear, $matches)) {
                    return (int) $matches[1];
                }
                return (int) Carbon::parse($invoice->created_at)->format('n');
            })
            ->map(function (Collection $invoices) {
                // Sum all paid amounts from all transactions across all invoices in this month
                $totalPaid = $invoices->sum(function ($invoice) {
                    return $invoice->paymentTransactions->sum('amount_paid');
                });

                // Sum all dues from all invoices in this month
                $totalDue = $invoices->sum('amount_due');

                // Get the first invoice for invoice_number
                $firstInvoice = $invoices->first();

                // Get last transaction for receiver/date (if any payments exist)
                $lastTransaction = $invoices->flatMap(fn($i) => $i->paymentTransactions)
                    ->sortByDesc('created_at')
                    ->first();

                return (object) [
                    'total_paid'        => $totalPaid,
                    'total_due'         => $totalDue,
                    'invoice_number'    => $firstInvoice->invoice_number,
                    'receiver_name'     => $lastTransaction?->createdBy?->name,
                    'last_payment_date' => $lastTransaction?->created_at,
                    'invoices'          => $invoices,
                ];
            });

        // For backward compatibility, also create a transactions collection
        $transactions = $allInvoices->flatMap(fn($invoice) => $invoice->paymentTransactions);

        return view('pdf.student_statement', compact(
            'student',
            'monthlyPayments',
            'otherFeeInvoices',
            'transactions',
            'totalPaid',
            'year'
        ));
    }

    /**
     * Generate special class statement
     */
    private function generateSpecialClassStatement(Student $student, int $year, int $secondaryClassId)
    {
        $secondaryClass = SecondaryClass::findOrFail($secondaryClassId);

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
                $query->whereHas('invoiceType', function ($q) {
                    $q->where('type_name', 'Special Class Fee');
                })
                    ->where(function ($q) use ($year) {
                        $q->where('month_year', 'LIKE', '%_' . $year)
                            ->orWhereYear('created_at', $year);
                    });
            })
            ->get();

        if ($payments->isEmpty()) {
            return response()->json([
                'error' => "No special class payments found for {$year}.",
            ], 404);
        }

        $monthlyPayments = $payments
            ->groupBy(function ($payment) {
                $monthYear = $payment->invoice->month_year;
                if ($monthYear && preg_match('/^(\d{1,2})_(\d{4})$/', $monthYear, $matches)) {
                    return (int) $matches[1];
                }
                return (int) Carbon::parse($payment->invoice->created_at)->format('n');
            })
            ->map(function (Collection $monthGroup) {
                $allTransactions = $monthGroup->flatMap(
                    fn($p) => $p->invoice->paymentTransactions
                );

                $totalPaid       = $allTransactions->sum('amount_paid');
                $totalDue        = $monthGroup->sum(fn($p) => $p->invoice->amount_due ?? 0);
                $paymentCount    = $allTransactions->count();
                $lastTransaction = $allTransactions->sortByDesc('created_at')->first();

                return (object) [
                    'total_paid'        => $totalPaid,
                    'total_due'         => $totalDue,
                    'payment_count'     => $paymentCount,
                    'receiver_name'     => $lastTransaction?->createdBy?->name,
                    'last_payment_date' => $lastTransaction?->created_at,
                ];
            });

        $totalPaid = $monthlyPayments->sum('total_paid');
        $totalDue  = $monthlyPayments->sum('total_due');

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
