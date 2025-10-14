<?php
namespace App\Http\Controllers\Misc;

use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
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
}
