<?php
namespace App\Http\Controllers\Student;

use App\Models\Student\Student;
use App\Models\Academic\ClassName;
use App\Http\Controllers\Controller;

class StudentPromoteController extends Controller
{
    /* Transfer a student from one class to another */
    public function index()
    {
        if (! auth()->user()->can('students.promote')) {
            return redirect()->back()->with('warning', 'No permission to promote students.');
        }

        $students = Student::active()
            ->select('id', 'name', 'student_unique_id', 'branch_id', 'class_id', 'batch_id')
            ->get();

        $classes = ClassName::where('is_active', true)->get();

        return view('students.promote', compact('students', 'classes'));
    }
}
