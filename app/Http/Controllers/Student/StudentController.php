<?php

namespace App\Http\Controllers\Student;

use Illuminate\Http\Request;
use App\Models\Academic\Shift;
use App\Models\Student\Student;
use App\Models\Academic\ClassName;
use App\Http\Controllers\Controller;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $students = Student::with('activations')->get();

        $active_students = Student::whereHas('studentActivation', function ($query) {
            $query->where('active_status', 'active');
        })->get();

        $inactive_students = Student::whereHas('studentActivation', function ($query) {
            $query->where('active_status', 'inactive');
        })->get();

        $classnames = ClassName::all();
        $shifts = Shift::all();
        // return response()->json($classnames);

        return view('students.index', compact('active_students', 'inactive_students', 'classnames', 'shifts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('students.create');
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
}
