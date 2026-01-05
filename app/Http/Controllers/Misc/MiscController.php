<?php
namespace App\Http\Controllers\Misc;

use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class MiscController extends Controller
{
    public function index()
    {
        if (!auth()->user()->isAdmin()) {
            return redirect()->back()->with('warning', 'Activity Not Allowed.');
        }

        return view('settings.misc.bulk_admission');
    }

    public function bulkAdmission(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:5120', // 5MB max
        ]);

        if (!$request->hasFile('excel_file') || !$request->file('excel_file')->isValid()) {
            return back()->with('error', 'No valid file uploaded.');
        }

        $file = $request->file('excel_file');

        // Get the real path of the uploaded file
        $filePath = $file->getRealPath();

        if (empty($filePath) || !file_exists($filePath)) {
            // Fallback: Store temporarily and use that path
            $filePath = $file->store('temp', 'local');

            if (empty($filePath)) {
                return back()->with('error', 'Failed to process uploaded file.');
            }

            $filePath = storage_path('app/' . $filePath);
        }

        $results = ['inserted' => [], 'skipped' => []];

        try {
            Excel::import(new StudentsImport($results), $filePath);

            $insertedCount = count($results['inserted']);
            $skippedCount = count($results['skipped']);

            // Clean up temp file if it was stored
            if (file_exists($filePath) && str_contains($filePath, 'temp')) {
                @unlink($filePath);
            }

            return back()->with('success', "Import finished: {$insertedCount} inserted, {$skippedCount} skipped.");
        } catch (\Throwable $e) {
            Log::error('Bulk admission import failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clean up temp file on error
            if (isset($filePath) && file_exists($filePath) && str_contains($filePath, 'temp')) {
                @unlink($filePath);
            }

            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
