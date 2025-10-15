<?php
namespace App\Http\Controllers;

use App\Models\Payment\PaymentTransaction;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Mpdf\Mpdf;

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

    // Download statement of all transactions of student for a year
    public function downloadStatement(Request $request)
    {
        $request->validate([
            'student_id'     => 'required|exists:students,id',
            'statement_year' => 'required|integer',
        ]);

        $student = Student::findOrFail($request->student_id);
        $year    = $request->statement_year;

        $transactions = PaymentTransaction::with([
            'paymentInvoice',
            'createdBy:id,name',
        ])
            ->where([
                ['student_id', '=', $student->id],
                ['is_approved', '=', true],
            ])
            ->whereYear('created_at', $year)
            // ->whereHas('paymentInvoice', function ($q) {
            //     $q->where('invoice_type', 'tuition_fee');
            // })
            ->get();

        if ($transactions->isEmpty()) {
            return back()->with('error', "No transactions found for {$year}.");
        }

        // Group transactions by month number
        $monthlyPayments = $transactions
            ->where('paymentInvoice.invoice_type', 'tuition_fee') // optional filter
            ->groupBy(function ($t) {
                $monthYear = $t->paymentInvoice->month_year;

                // Handle formats like "10_2025"
                if ($monthYear && preg_match('/^(\d{1,2})_(\d{4})$/', $monthYear, $matches)) {
                    return (int) $matches[1]; // Extract month number
                }

                // Fallback to invoice created_at month
                return (int) Carbon::parse($t->paymentInvoice->created_at)->format('n');
            })
            ->map(function (Collection $monthGroup) {
                // Now group within month by invoice
                return $monthGroup
                    ->groupBy('payment_invoice_id')
                    ->map(function (Collection $invoiceGroup) {
                        // Sum all transactions of that invoice
                        $first   = $invoiceGroup->first();
                        $sumPaid = $invoiceGroup->sum('amount_paid');

                        // Return a single summarized record
                        $first->amount_paid = $sumPaid;
                        return $first;
                    });
            });

        return view('pdf.student_statement', compact('student', 'monthlyPayments', 'transactions'));
    }

}
