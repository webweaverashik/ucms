<?php
namespace App\Http\Controllers\Sheet;

use App\Http\Controllers\Controller;
use App\Models\Sheet\SheetTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SheetTopicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sheet_subject_id' => 'required|integer|exists:subjects,id',
            'notes_name'       => 'required|string|max:255',
            'pdf_file'         => 'nullable|file|mimes:pdf|max:10240', // 10MB max
        ]);

        $pdfPath = null;

        // Handle PDF upload
        if ($request->hasFile('pdf_file')) {
            $pdfPath = $this->uploadPdf($request->file('pdf_file'), $validated['notes_name']);
        }

        SheetTopic::create([
            'subject_id' => $validated['sheet_subject_id'],
            'topic_name' => $validated['notes_name'],
            'pdf_path'   => $pdfPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Note created successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SheetTopic $note)
    {
        $validated = $request->validate([
            'topic_name' => 'required|string|max:255',
            'pdf_file'   => 'nullable|file|mimes:pdf|max:10240', // 10MB max
            'remove_pdf' => 'nullable|string|in:0,1',
        ]);

        $updateData = [
            'topic_name' => $validated['topic_name'],
        ];

        // Check if we need to remove the current PDF
        if ($request->input('remove_pdf') === '1') {
            // Delete existing PDF file
            if ($note->pdf_path) {
                $this->deletePdf($note->pdf_path);
            }
            $updateData['pdf_path'] = null;
        }

        // Handle new PDF upload
        if ($request->hasFile('pdf_file')) {
            // Delete existing PDF if present
            if ($note->pdf_path) {
                $this->deletePdf($note->pdf_path);
            }

            // Upload new PDF with topic name as slug base
            $updateData['pdf_path'] = $this->uploadPdf($request->file('pdf_file'), $validated['topic_name']);
        }

        $note->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Note updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $note = SheetTopic::findOrFail($id);

        // Delete PDF file if exists
        if ($note->pdf_path) {
            $this->deletePdf($note->pdf_path);
        }

        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'Note deleted successfully',
        ]);
    }

    /**
     * Update the notes status
     */
    public function updateStatus(SheetTopic $sheetTopic, Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $sheetTopic->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
        ]);
    }

    /**
     * Upload PDF to public/uploads/notes directory with unique slug name
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $topicName
     * @return string
     */
    private function uploadPdf($file, string $topicName): string
    {
        // Create directory if it doesn't exist
        $uploadPath = public_path('uploads/notes');

        if (! File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0755, true);
        }

        // Generate unique slug-based filename
        // Format: topic-name-slug_timestamp_random.pdf
        $slug      = Str::slug($topicName, '-');
        $timestamp = now()->format('Ymd_His');
        $random    = Str::lower(Str::random(8));
        $filename  = "{$slug}_{$timestamp}_{$random}.pdf";

        // Ensure filename is not too long (max 200 chars for safety)
        if (strlen($filename) > 200) {
            $slug     = Str::limit($slug, 150, '');
            $filename = "{$slug}_{$timestamp}_{$random}.pdf";
        }

        // Move file to uploads directory
        $file->move($uploadPath, $filename);

        // Return relative path
        return 'uploads/notes/' . $filename;
    }

    /**
     * Delete PDF from public/uploads/notes directory
     *
     * @param string $path
     * @return bool
     */
    private function deletePdf(string $path): bool
    {
        $fullPath = public_path($path);

        if (File::exists($fullPath)) {
            return File::delete($fullPath);
        }

        return false;
    }
}
