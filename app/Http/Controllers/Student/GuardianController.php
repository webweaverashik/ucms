<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Student\Guardian;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class GuardianController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userBranchId = auth()->user()->branch_id;

        if (auth()->user()->branch_id != 0) {
            $guardians = Guardian::whereHas('student', function ($query) use ($userBranchId) {
                $query->where('branch_id', $userBranchId);
            })
                ->withoutTrashed()
                ->get();

            $students = Student::where('branch_id', $userBranchId)->withoutTrashed()->orderby('student_unique_id', 'asc')->get();
        } else {
            $guardians = Guardian::withoutTrashed()->get(); // SuperAdmin can view everything

            $students = Student::withoutTrashed()->orderby('student_unique_id', 'asc')->get();
        }

        $branches = Branch::all();

        return view('guardians.index', compact('guardians', 'branches', 'students'));
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
    public function show(Guardian $guardian)
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => $guardian->id,
                'student_id'    => $guardian->student_id,
                'name'          => $guardian->name,
                'mobile_number' => $guardian->mobile_number,
                'gender'        => $guardian->gender,
                'relationship'  => $guardian->relationship,
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
        // return $request;

        $validated = $request->validate([
            'guardian_student'       => 'required|exists:students,id', // Must be a valid student ID
            'guardian_name'          => 'required|string|max:255',     // Required, must be a string, max length 255
            'guardian_mobile_number' => 'required|string|max:11',
            'guardian_gender'        => 'required|in:male,female',                                    // Must be male or female
            'guardian_relationship'  => 'required|string|in:father,mother,brother,sister,uncle,aunt', // Required, string, max 50 chars
            'guardian_password'      => 'nullable|string|min:8|confirmed',                            // Password is optional but must be at least 6 characters if provided
        ]);

        $guardian = Guardian::findOrFail($id);

        // Prepare data for update
        $updateData = [
            'student_id'    => $validated['guardian_student'],
            'name'          => $validated['guardian_name'],
            'mobile_number' => $validated['guardian_mobile_number'],
            'gender'        => $validated['guardian_gender'],
            'relationship'  => $validated['guardian_relationship'],
        ];

        // If the request contains a password, hash and update it
        if ($request->filled('guardian_password')) {
            $updateData['password'] = Hash::make($request->input('guardian_password'));
        }

        // Update the guardian record
        $guardian->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Guardian updated successfully',
            'data'    => $guardian,
        ]);

        // return redirect()->route('guardians.index')->with('success', 'Guardian updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Guardian $guardian)
    {
        // Update 'deleted_by' column with the currently authenticated user's ID
        $guardian->update(['deleted_by' => Auth::id()]);

        // Delete the guardian
        $guardian->delete();

        // Return JSON response
        return response()->json(['success' => true]);
    }
}
