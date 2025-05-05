<?php
namespace App\Http\Controllers;

use App\Models\Student\Student;
use Mpdf\Mpdf;

class PdfController extends Controller
{
    public function downloadAdmissionForm(string $id)
    {
        // Try to find the student including trashed ones (if soft deletes are enabled)
        $student = Student::withTrashed()->find($id);

        // If not found or trashed, redirect with warning
        if (!$student || $student->trashed()) {
            return redirect()->route('students.index')->with('warning', 'Student not found or deleted.');
        }

        // Restrict access: Only allow editing if the user belongs to the same branch
        if (auth()->user()->branch_id != 0 && auth()->user()->branch_id != $student->branch_id) {
            return redirect()->route('students.index')->with('error', 'Student not available on this branch.');
        }

        // Inactive student should not be able to download admission form
        if ($student->student_activation_id && $student->studentActivation?->active_status !== 'active') {
            return redirect()->route('students.index')->with('error', 'This student is inactive.');
        }

        /* ------- PDF Generating Below ------- */
        // Create a custom temp directory in your storage folder
        $tempDir = storage_path('app/mpdf');

        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        try {
            $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'tempDir' => $tempDir,
                'default_font' => 'timesnewroman',
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_header' => 0,
                'margin_footer' => 0,
            ]);

            // Only if you actually want a watermark
            // $pdf->SetWatermarkImage(public_path('watermark.png'), 0.1);
            // $pdf->showWatermarkImage = true;

            // Consider keeping auto page break or setting a large value
            $pdf->SetAutoPageBreak(false); // Or false if you're sure about content length

            $html = view('pdf.admission-form-layout', compact('student'))->render();

            // Add this to handle UTF-8 content properly
            $pdf->WriteHTML(mb_convert_encoding($html, 'UTF-8', 'HTML-ENTITIES'));

            return $pdf->Output('form_' . $student->student_unique_id . '.pdf', 'I');
        } catch (\Mpdf\MpdfException $e) {
            // Handle the error appropriately
            return back()->with('error', 'PDF generation failed: ' . $e->getMessage());
        }
    }
}
