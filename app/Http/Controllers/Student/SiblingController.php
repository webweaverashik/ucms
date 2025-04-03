<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Academic\Institution;
use App\Models\Branch;
use App\Models\Student\Sibling;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class SiblingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userBranchId = auth()->user()->branch_id;

        if (auth()->user()->branch_id != 0) {
            $siblings = Sibling::whereHas('student', function ($query) use ($userBranchId) {
                $query->where('branch_id', $userBranchId);
            })
                ->withoutTrashed()
                ->get();

            $students = Student::where('branch_id', $userBranchId)->withoutTrashed()->orderby('student_unique_id', 'asc')->get();
        } else {
            $siblings = Sibling::withoutTrashed()->get(); // SuperAdmin can view everything

            $students = Student::withoutTrashed()->orderby('student_unique_id', 'asc')->get();
        }

        $branches     = Branch::all();
        $institutions = Institution::withoutTrashed()->get();

        return view('siblings.index', compact('siblings', 'branches', 'students', 'institutions'));
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
    public function show(Sibling $sibling)
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $sibling->id,
                'student_id'     => $sibling->student_id,
                'name'           => $sibling->name,
                'age'            => $sibling->age,
                'class'          => $sibling->class,
                'institution_id' => $sibling->institution_id,
                'relationship'   => $sibling->relationship,
            ],
        ]);
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
        $validated = $request->validate([
                                                                 // 'sibling_student'       => 'required|exists:students,id', // Must be a valid student ID
            'sibling_name'         => 'required|string|max:255', // Required, must be a string, max length 255
            'sibling_age'          => 'required|integer|min:1|max:20',
            'sibling_class'        => 'required|string',
            'sibling_institution'  => 'required|integer|exists:institutions,id',
            'sibling_relationship' => 'required|string|in:brother,sister',
        ]);

        $sibling = Sibling::findOrFail($id);

        // Prepare data for update
        $updateData = [
            // 'student_id'    => $validated['guardian_student'],
            'name'           => $validated['sibling_name'],
            'age'            => $validated['sibling_age'],
            'class'          => $validated['sibling_class'],
            'institution_id' => $validated['sibling_institution'],
            'relationship'   => $validated['sibling_relationship'],
        ];

        // Update the guardian record
        $sibling->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Sibling updated successfully',
            'data'    => $sibling,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sibling $sibling)
    {
        // Delete the guardian
        $sibling->delete();

        // Return JSON response
        return response()->json(['success' => true]);
    }
}
