<?php
namespace App\Http\Controllers;

use App\Models\Payment\PaymentTransaction;
use App\Models\Student\Student;
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

        return view('pdf.admission-form-layout', ['student' => $student]);

    }

    public function downloadPaySlip(string $id)
    {

        $transaction = PaymentTransaction::find($id);

        if (! $transaction) {
            return redirect()->route('transactions.index')->with('warning', 'Transaction not found.');
        }

        // -- JS Print --
        // return view('pdf.payslip', ['transaction' => $transaction]);

        // -- mPDF Configuration --
        $tempDir = storage_path('app/mpdf');

        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $pdf = new Mpdf([
            'mode'             => 'utf-8',
            'format'           => [80, 140], // width: 80mm, height: auto or fixed like 297mm
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
}
