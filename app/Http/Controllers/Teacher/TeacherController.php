<?php
namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Teacher\Teacher;
use Illuminate\Http\Request;
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
        $commonRules = [
            'teacher_name'          => 'required|string|max:255',
            'teacher_gender'        => 'required|in:male,female',
            'teacher_salary'        => 'required|integer|min:100',
            'teacher_phone'         => 'required|string|size:11',
            'teacher_email'         => 'required|string|email|max:255|unique:teachers,email',
            'teacher_blood_group'   => 'nullable|string',
            'teacher_qualification' => 'nullable|string',
            'teacher_experience'    => 'nullable|string',
        ];

        $request->validate($commonRules);

        $teacher = Teacher::create([
            'name'                   => $request->teacher_name,
            'gender'                 => $request->teacher_gender,
            'email'                  => $request->teacher_email,
            'phone'                  => $request->teacher_phone,
            'password'               => Hash::make('ucms@123'),
            'base_salary'            => $request->teacher_salary,
            'blood_group'            => $request->teacher_blood_group,
            'academic_qualification' => $request->teacher_qualification,
            'experience'             => $request->teacher_experience,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Teacher created successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $teacher = Teacher::find($id);

        if (! $teacher) {
            return redirect()->route('teachers.index')->with('warning', 'Teacher not found.');
        }

        return view('teachers.view', compact('teacher'));
    }

    /**
     * Show the ajax data for editing the teacher
     */
    public function getTeacherData(string $id)
    {
        $teacher = Teacher::find($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'            => $teacher->id,
                'name'          => $teacher->name,
                'email'         => $teacher->email,
                'phone'         => $teacher->phone,
                'base_salary'   => $teacher->base_salary,
                'gender'        => $teacher->gender,
                'blood_group'   => $teacher->blood_group,
                'qualification' => $teacher->academic_qualification,
                'experience'    => $teacher->experience,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $teacher = Teacher::findOrFail($id);

        $commonRules = [
            'teacher_name_edit'          => 'required|string|max:255',
            'teacher_gender_edit'        => 'required|in:male,female',
            'teacher_salary_edit'        => 'required|integer|min:100',
            'teacher_phone_edit'         => 'required|string|size:11',
            'teacher_email_edit'         => 'required|string|email|max:255|unique:teachers,email,' . $teacher->id,
            'teacher_blood_group_edit'   => 'nullable|string',
            'teacher_qualification_edit' => 'nullable|string',
            'teacher_experience_edit'    => 'nullable|string',
        ];

        $request->validate($commonRules);

        // Update the teacher record
        $teacher->update([
            'name'                   => $request->teacher_name_edit,
            'gender'                 => $request->teacher_gender_edit,
            'email'                  => $request->teacher_email_edit,
            'phone'                  => $request->teacher_phone_edit,
            'base_salary'            => $request->teacher_salary_edit,
            'blood_group'            => $request->teacher_blood_group_edit,
            'academic_qualification' => $request->teacher_qualification_edit,
            'experience'             => $request->teacher_experience_edit,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Teacher updated successfully',
        ]);
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
        if (auth()->user()->cannot('teachers.edit')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to perform this action.']);
        }

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
