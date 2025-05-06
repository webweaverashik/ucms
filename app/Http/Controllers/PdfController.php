<?php
namespace App\Http\Controllers;

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

        return Pdf::view('pdf.admission-form-layout', ['student' => $student]) // Pass the student data to the view
            ->format('a4')
            ->inline($student->student_unique_id . '_admission_form.pdf'); // Use inline() to display and download() to download

        // return view('pdf.admission-form-layout', ['student' => $student]);

    }
}
