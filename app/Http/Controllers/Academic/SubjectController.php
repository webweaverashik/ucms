<?php

namespace App\Http\Controllers\Academic;

use Illuminate\Http\Request;
use App\Models\Academic\Subject;
use Illuminate\Http\JsonResponse;
use App\Models\Academic\ClassName;
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
    //     // Get subjects where class_id matches the selected class
    //     $subjects = Subject::where('class_id', $classId)->withoutTrashed()->orderByDesc('is_mandatory')->get();

    //     // Return the subjects as JSON
    //     return response()->json(['subjects' => $subjects]);
    // }

    public function getSubjects(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:class_names,id',
            'group' => 'required|in:General,Science,Commerce,Arts', // Changed to 'group'
            'include_general' => 'required|boolean',
        ]);

        $query = Subject::where('class_id', $request->class_id)
            ->when(
                $request->include_general,
                function ($q) use ($request) {
                    return $q->where(function ($q) use ($request) {
                        $q->where('academic_group', 'General')->orWhere('academic_group', $request->group);
                    });
                },
                function ($q) {
                    return $q->where('academic_group', 'General');
                },
            )
            ->orderBy('academic_group')
            ->orderBy('name');

        return response()->json([
            'success' => true,
            'subjects' => $query->get(),
        ]);
    }
}
