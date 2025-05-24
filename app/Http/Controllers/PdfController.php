<?php
namespace App\Http\Controllers;

use App\Models\Payment\PaymentTransaction;
use App\Models\Student\Student;
use Spatie\LaravelPdf\Facades\Pdf;

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

        return Pdf::view('pdf.admission-form-layout', ['student' => $student])
    ->pdf(function (\Spatie\Browsershot\Browsershot $browsershot) {
        $browsershot
            ->nodeBinary('/home/uniqueco/.nvm/versions/node/v22.16.0/bin/node')
            ->npmBinary('/home/uniqueco/.nvm/versions/node/v22.16.0/bin/npm')
            ->setOption('args', ['--no-sandbox']);
    })
    ->paper('a4')
    ->download($student->student_unique_id . '_admission_form.pdf');

        // return view('pdf.admission-form-layout', ['student' => $student]);

    }

    public function downloadPaySlip(string $id)
    {

        $transaction = PaymentTransaction::find($id);

        if (! $transaction) {
            return redirect()->route('transactions.index')->with('warning', 'Transaction not found.');
        }

        return Pdf::view('pdf.payslip', ['transaction' => $transaction]) // Pass the student data to the view
            ->paperSize(80, 150, 'mm')                                       // 80mm width, 297mm height (A4 length)
            ->inline($transaction->vocher_no . '_payslip.pdf');              // Use inline() to display and download() to download
    }
}
