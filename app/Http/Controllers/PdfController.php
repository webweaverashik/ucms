<?php
namespace App\Http\Controllers;

use App\Models\Payment\PaymentTransaction;
use App\Models\Student\Student;
use Spatie\LaravelPdf\Facades\Pdf;

class PdfController extends Controller
{
    public function downloadAdmissionForm(string $id)
    {
        $student = Student::withTrashed()->find($id);

        if (! $student || $student->trashed()) {
            return redirect()->route('students.index')->with('error', 'Student not found or deleted.');
        }

        $userBranchId = auth()->user()->branch_id ?? 0;
        if ($userBranchId != 0 && $userBranchId != $student->branch_id) {
            return redirect()->route('students.index')->with('warning', 'Student not in this branch.');
        }

        if ($student->student_activation_id && ($student->studentActivation?->active_status ?? '') !== 'active') {
            return redirect()->route('students.index')->with('warning', 'This student is inactive.');
        }

        return Pdf::view('pdf.admission-form-layout', ['student' => $student])
    // ->setOption('args', ['--no-sandbox'])
    ->nodeBinary('/home/uniqueco/.nvm/versions/node/v22.16.0/bin/node')
    ->npmBinary('/home/uniqueco/.nvm/versions/node/v22.16.0/bin/npm')
    ->paper('a4')
    ->download($student->student_unique_id . '_admission_form.pdf');
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
