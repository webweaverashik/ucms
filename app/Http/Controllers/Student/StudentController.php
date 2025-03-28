<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Academic\ClassName;
use App\Models\Academic\Institution;
use App\Models\Academic\Shift;
use App\Models\Academic\Subject;
use App\Models\Student\Guardian;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $students = Student::withoutTrashed()->orderby('id', 'desc')->get();

        // $active_students = Student::whereHas('studentActivation', function ($query) {
        //     $query->where('active_status', 'active');
        // })->get();

        // $inactive_students = Student::whereHas('studentActivation', function ($query) {
        //     $query->where('active_status', 'inactive');
        // })->get();

        $classnames = ClassName::all();
        $shifts = Shift::all();
        $institutions = Institution::all();
        // return response()->json($students);

        return view('students.index', compact('students', 'classnames', 'shifts', 'institutions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $students = Student::withoutTrashed()->get();
        $guardians = Guardian::withoutTrashed()->get();
        $classnames = ClassName::withoutTrashed()->get();
        $subjects = Subject::withoutTrashed()->get();
        $shifts = Shift::where('branch_id', auth()->user()->branch_id)->get();
        $institutions = Institution::withoutTrashed()->get();

        return view('students.create', compact('students', 'guardians', 'classnames', 'subjects', 'shifts', 'institutions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return $request;
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function getReferredData(Request $request)
    {
        $refererType = $request->get('referer_type');

        if ($refererType == 'teacher') {
            // Fetch teacher data (no unique_id)
            $teachers = Teacher::withoutTrashed()->get(); // Adjust according to your data model
            return response()->json(
                $teachers->map(function ($teacher) {
                    return [
                        'id' => $teacher->id,
                        'name' => $teacher->name,
                    ];
                }),
            );
        } elseif ($refererType == 'student') {
            // Fetch student data
            $students = Student::withoutTrashed()->get(); // Adjust according to your data model
            return response()->json(
                $students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'student_unique_id' => $student->student_unique_id, // Keep the unique_id for students
                    ];
                }),
            );
        }

        return response()->json([]);
    }
}
