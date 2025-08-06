<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
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
        if (! auth()->user()->can('siblings.view')) {
            return redirect()->back()->with('warning', 'No permission to view siblings.');
        }

        $userBranchId = auth()->user()->branch_id;

        $siblings = Sibling::with([
            'student:id,name,student_unique_id,branch_id',
            'student.branch:id,branch_name',
        ])
            ->when($userBranchId != 0, function ($query) use ($userBranchId) {
                $query->whereHas('student', fn($q) => $q->where('branch_id', $userBranchId));
            })
            ->latest('id')
            ->get(['id', 'name', 'year', 'class', 'relationship', 'student_id', 'institution_name']);

        $students = Student::when($userBranchId != 0, fn($q) => $q->where('branch_id', $userBranchId))
            ->orderBy('student_unique_id')
            ->get();

        $branches = Branch::all();

        return view('siblings.index', compact('siblings', 'branches', 'students'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->back()->with('warning', 'Not Allowed');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return redirect()->back()->with('warning', 'Not Allowed');
    }

    /**
     * Display the specified resource.
     */
    public function show(Sibling $sibling)
    {
        if (! auth()->user()->can('siblings.view')) {
            return redirect()->back()->with('warning', 'No permission to view siblings.');
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'               => $sibling->id,
                'student_id'       => $sibling->student_id,
                'name'             => $sibling->name,
                'year'             => $sibling->year,
                'class'            => $sibling->class,
                'institution_name' => $sibling->institution_name,
                'relationship'     => $sibling->relationship,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return redirect()->back()->with('warning', 'Not Allowed');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'sibling_name'         => 'required|string|max:255',
            'sibling_year'         => 'required|string',
            'sibling_class'        => 'required|string',
            'sibling_institution'  => 'required|string',
            'sibling_relationship' => 'required|string|in:brother,sister',
        ]);

        $sibling = Sibling::findOrFail($id);

        // Prepare data for update
        $updateData = [
            // 'student_id'    => $validated['guardian_student'],
            'name'             => $validated['sibling_name'],
            'year'             => $validated['sibling_year'],
            'class'            => $validated['sibling_class'],
            'institution_name' => $validated['sibling_institution'],
            'relationship'     => $validated['sibling_relationship'],
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
