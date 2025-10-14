<?php
namespace App\Http\Controllers\Misc;

use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use App\Models\Payment\PaymentTransaction;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MiscController extends Controller
{
    public function index()
    {
        if (! auth()->user()->hasRole('admin')) {
            return redirect()->back()->with('warning', 'Activity Not Allowed.');
        }

        return view('settings.misc.bulk_admission');
    }

    public function bulkAdmission(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:100',
        ]);

        if (! $request->hasFile('excel_file')) {
            return back()->with('error', 'No file uploaded.');
        }

        $file    = $request->file('excel_file');
        $results = ['inserted' => [], 'skipped' => []];

        try {
            Excel::import(new StudentsImport($results), $file);

            $insertedCount = count($results['inserted']);
            $skippedCount  = count($results['skipped']);

            return back()->with('success', "Import finished: {$insertedCount} inserted, {$skippedCount} skipped.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
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

        $transactions_tution_fee = PaymentTransaction::with([
            'paymentInvoice',
            'createdBy:id,name'
            ])
            ->where('student_id', $student->id)
            ->where('is_approved', true)
            ->wherehas('paymentInvoice', function ($query) {
                $query->where('invoice_type', 'tuition_fee');
            })
            ->whereYear('created_at', $year)
            ->latest()
            ->get();

        if ($transactions_tution_fee->isEmpty()) {
            return back()->with('error', "No transactions found for {$year}.");
        }

        // Example: render a Blade view (could also generate a PDF)
        return view('pdf.student_statement', compact('student', 'year', 'transactions_tution_fee'));
    }
}
