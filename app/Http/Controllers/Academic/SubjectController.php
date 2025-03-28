<?php

namespace App\Http\Controllers\Academic;

use Illuminate\Http\Request;
use App\Models\Academic\Subject;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subjects = Subject::withoutTrashed()->get();

        return view('subjects.index', compact('subjects'));
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

    /**
     * Get subjects by class ID using AJAX request
     */
    // public function getSubjects($classId)
    // {
    //     $subjects = Subject::where('class_id', $classId)
    //         ->select('id', 'subject_name', 'is_mandatory', 'academic_group') // ✅ Include academic_group
    //         ->orderByDesc('is_mandatory') // ✅ Sort in Laravel
    //         ->get();

    //     return response()->json($subjects);
    // }

    public function getSubjects($class_id, $academic_group): JsonResponse
    {
        $subjects = Subject::where('class_id', $class_id)->where('academic_group', $academic_group)->select('id', 'name')->withoutTrashed()->get();

        return response()->json($subjects);
    }
}
