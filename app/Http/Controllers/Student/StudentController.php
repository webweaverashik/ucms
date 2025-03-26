<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\Request;
use App\Models\Academic\Shift;
use App\Models\Student\Student;
use App\Models\Academic\Subject;
use App\Models\Student\Guardian;
use App\Models\Academic\ClassName;
use App\Http\Controllers\Controller;
use App\Models\Academic\Institution;

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
        $guardians = Guardian::all();
        $classnames = ClassName::all();
        $subjects = Subject::all();
        $shifts = Shift::where('branch_id', auth()->user()->branch_id)->get();
        $institutions = Institution::all();

        return view('students.create', compact('guardians', 'classnames', 'subjects', 'shifts', 'institutions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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

    public function ucms(Request $request)
    {
        $search = $request->input('search');
        $filter = $request->input('filter');

        // Query the students table
        $query = Student::query();

        // Apply search filter
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        // Apply filter if set (e.g., class)
        if ($filter) {
            $query->where('class', $filter);
        }

        // Paginate the results
        $students = $query->paginate(10);

        // Return the view with the necessary data
        return view('students.student', compact('students', 'search', 'filter'));
    }
}
