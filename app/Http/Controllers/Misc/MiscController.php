<?php
namespace App\Http\Controllers\Misc;

use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;

class MiscController extends Controller
{
    public function index()
    {
        if (! auth()->user()->isAdmin()) {
            return redirect()->back()->with('warning', 'Activity Not Allowed.');
        }

        return view('settings.misc.bulk_admission');
    }

    public function bulkAdmission(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:5120', // 5MB max
        ]);

        if (! $request->hasFile('excel_file') || ! $request->file('excel_file')->isValid()) {
            return back()->with('error', 'No valid file uploaded.');
        }

        $file         = $request->file('excel_file');
        $tempFilePath = null;

        try {
            // Determine the file type explicitly from original extension
            $originalExtension = strtolower($file->getClientOriginalExtension());
            $readerType        = $this->getReaderType($originalExtension);

            if (! $readerType) {
                return back()->with('error', 'Invalid file type. Please upload .xlsx or .xls file.');
            }

            // Method 1: Try getRealPath() first
            $filePath = $file->getRealPath();

            // Method 2: If getRealPath() fails, manually save the file
            if (empty($filePath) || $filePath === false || ! file_exists($filePath)) {
                // Create temp directory if it doesn't exist
                $tempDir = storage_path('app/temp');
                if (! is_dir($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }

                // Generate unique filename with original extension
                $tempFileName = 'import_' . Str::uuid() . '.' . $originalExtension;
                $tempFilePath = $tempDir . '/' . $tempFileName;

                // Move uploaded file to temp location
                $file->move($tempDir, $tempFileName);

                if (! file_exists($tempFilePath)) {
                    return back()->with('error', 'Failed to process uploaded file.');
                }

                $filePath = $tempFilePath;
            }

            $results = ['inserted' => [], 'skipped' => []];

            // Pass explicit reader type to avoid detection issues on cPanel
            Excel::import(new StudentsImport($results), $filePath, null, $readerType);

            $insertedCount = count($results['inserted']);
            $skippedCount  = count($results['skipped']);

            // Clean up temp file if created
            if ($tempFilePath && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }

            return back()->with('success', "Import finished: {$insertedCount} inserted, {$skippedCount} skipped.");
        } catch (\Throwable $e) {
            Log::error('Bulk admission import failed: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clean up temp file on error
            if ($tempFilePath && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
            }

            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Get the reader type based on file extension.
     *
     * @param string $extension
     * @return string|null
     */
    protected function getReaderType(string $extension): ?string
    {
        return match ($extension) {
            'xlsx'  => ExcelType::XLSX,
            'xls'   => ExcelType::XLS,
            'csv'   => ExcelType::CSV,
            default => null,
        };
    }
}
