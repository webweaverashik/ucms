<?php
namespace App\Http\Controllers;

use App\Models\Student\Student;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Browsershot\Browsershot;
use App\Models\Payment\PaymentTransaction;

class PdfController extends Controller
{
    public function downloadAdmissionForm(string $id)
    {
        $student = Student::find($id);

        // If not found or trashed, redirect with warning
        if (!$student || $student->trashed()) {
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

        // --- BROWSERSHOT CONFIGURATION FOR CPNEL ---
        // IMPORTANT: Replace these paths with the ones you found via SSH on your cPanel server.
        $nodePath = '~/.nvm/versions/node/v20.19.2/bin/node'; // Example path
        $npmPath = '~/.nvm/versions/node/v20.19.2/bin/npm';   // Example path
        // --- END BROWSERSHOT CONFIGURATION ---

        try {
            return Pdf::view('pdf.admission-form-layout', ['student' => $student])
                ->withBrowsershot(function (Browsershot $browsershot) use ($nodePath, $npmPath) {
                    $browsershot->setNodeBinary($nodePath)
                                ->setNpmBinary($npmPath)
                                ->addChromiumArguments([
                                    '--no-sandbox',           // Essential for shared hosting environments like cPanel
                                    '--disable-gpu',          // Can help prevent issues on some systems
                                    '--disable-dev-shm-usage' // Can help with memory management for Chromium
                                ])
                                ->setTimeout(60000); // Optional: Increase timeout (in milliseconds) for complex PDFs, e.g., 60 seconds
                })
                ->format('a4')
                ->inline($student->student_unique_id . '_admission_form.pdf'); // Use inline() to display in browser
                // If you want to force a download instead of displaying inline:
                // ->download($student->student_unique_id . '_admission_form.pdf');

        } catch (\Exception $e) {
            // Log the error for debugging. Check your Laravel logs (`storage/logs/laravel.log`) for details.
            \Log::error('PDF generation failed for student ' . $student->id . ': ' . $e->getMessage());

            // Return a user-friendly error message
            return redirect()->route('students.index')->with('error', 'Failed to generate the admission form. Please try again or contact support.');
        }
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
