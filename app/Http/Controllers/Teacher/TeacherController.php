<?php
namespace App\Http\Controllers\Teacher;

use Illuminate\Http\Request;
use App\Models\Teacher\Teacher;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $teachers = Teacher::latest('updated_at')->get();

        return view('teachers.index', compact('teachers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->back();
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
        $teacher = Teacher::find($id);

        if (! $teacher) {
            return redirect()->back()->with('warning', 'Teacher not found.');
        }

        return view('teachers.view', compact('teacher'));
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
    public function destroy(Teacher $teacher)
    {
        $teacher->delete();
        $teacher->update(['deleted_by' => auth()->user()->id]);

        return response()->json(['success' => true]);
    }

    /**
     * Toggle active and inactive teachers
     */
    public function toggleActive(Request $request)
    {
        $teacher = Teacher::find($request->teacher_id);

        if (! $teacher) {
            return response()->json(['success' => false, 'message' => 'Error. Please, contact support.']);
        }

        $teacher->is_active = $request->is_active;
        $teacher->save();

        return response()->json(['success' => true, 'message' => 'Teacher activation status updated.']);
    }

    /**
     * Reset teacher password
     */
    public function teacherPasswordReset(Request $request, Teacher $teacher)
    {
        $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        if (! $teacher) {
            return response()->json(['success' => false, 'message' => 'Teacher not found.']);
        }

        $teacher->password = Hash::make($request->new_password);
        $teacher->save();

        return response()->json(['success' => true]);
    }
}
