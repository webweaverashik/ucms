<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Academic\ClassName;
use App\Models\Academic\Institution;
use App\Models\Academic\Shift;
use App\Models\Academic\Subject;
use App\Models\Payment\Payment;
use App\Models\Student\Guardian;
use App\Models\Student\MobileNumber;
use App\Models\Student\Reference;
use App\Models\Student\Sibling;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (auth()->user()->branch_id != 0) {
            $students = Student::where('student_activation_id', '!=', null)->where('branch_id', auth()->user()->branch_id)->withoutTrashed()->orderby('id', 'desc')->get();
        } else {
            $students = Student::where('student_activation_id', '!=', null)->withoutTrashed()->orderby('id', 'desc')->get();
        }

        $classnames   = ClassName::all();
        $shifts       = Shift::all();
        $institutions = Institution::all();
        // return response()->json($students);

        return view('students.index', compact('students', 'classnames', 'shifts', 'institutions'));
    }

    public function pending()
    {
        if (auth()->user()->branch_id != 0) {
            $students = Student::where('student_activation_id', null)->where('branch_id', auth()->user()->branch_id)->withoutTrashed()->orderby('id', 'desc')->get();
        } else {
            $students = Student::where('student_activation_id', null)->withoutTrashed()->orderby('id', 'desc')->get();
        }

        $classnames   = ClassName::all();
        $shifts       = Shift::all();
        $institutions = Institution::all();
        // return response()->json($students);

        return view('students.pending', compact('students', 'classnames', 'shifts', 'institutions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $students     = Student::withoutTrashed()->get();
        $guardians    = Guardian::withoutTrashed()->get();
        $classnames   = ClassName::withoutTrashed()->get();
        $subjects     = Subject::withoutTrashed()->get();
        $shifts       = Shift::where('branch_id', auth()->user()->branch_id)->get();
        $institutions = Institution::withoutTrashed()->get();

        return view('students.create', compact('students', 'guardians', 'classnames', 'subjects', 'shifts', 'institutions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate request data
        $validated = $request->validate([
            // Student Table Fields
            'student_name'            => 'required|string|max:255',
            'student_home_address'    => 'nullable|string|max:500',
            'student_email'           => 'nullable|email|max:255|unique:students,email',
            'birth_date'              => 'required',
            'student_gender'          => 'required|in:male,female',
            'student_religion'        => 'nullable|string|in:Islam,Hinduism,Christianity,Buddhism,Others',
            // 'student_blood_group'     => 'nullable|string|in:A+,B+,AB+,O+,A-,B-,AB-,O-',
            'student_blood_group'     => 'nullable|string',
            'student_class'           => 'required|integer|exists:class_names,id',
            'student_academic_group'  => 'nullable|string|in:General,Science,Commerce,Arts',
            'student_shift'           => 'required|integer|exists:shifts,id',
            'student_institution'     => 'required|integer|exists:institutions,id',
            'subjects'                => 'required|array',
            'subjects.*'              => 'integer|exists:subjects,id',
            'student_remarks'         => 'nullable|string|max:1000',
            'avatar'                  => 'nullable|image|mimes:jpg,jpeg,png|max:200',

            // Mobile Numbers Table Fields (Up to 3)
            'student_phone_home'      => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'student_phone_sms'       => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'student_phone_whatsapp'  => ['nullable', 'regex:/^01[3-9]\d{8}$/'], // Made this field optional

            // Payment Table Fields
            'student_tuition_fee'     => 'required|numeric|min:0',
            'payment_style'           => 'required|in:current,due',
            'payment_due_date'        => 'required|integer|in:7,10,15,30',

            // Guardians Table Fields (Up to 3)
            'guardian_1_name'         => 'required|string|max:255',
            'guardian_1_mobile'       => 'required|string|max:11',
            'guardian_1_gender'       => 'required|in:male,female',
            'guardian_1_relationship' => 'required|string|in:father,mother,brother,sister,uncle,aunt',
            'guardian_2_name'         => 'nullable|string|max:255',
            'guardian_2_mobile'       => 'nullable|string|max:11',
            'guardian_2_gender'       => 'nullable|in:male,female',
            'guardian_2_relationship' => 'nullable|string|in:father,mother,brother,sister,uncle,aunt',
            'guardian_3_name'         => 'nullable|string|max:255',
            'guardian_3_mobile'       => 'nullable|string|max:11',
            'guardian_3_gender'       => 'nullable|in:male,female',
            'guardian_3_relationship' => 'nullable|string|in:father,mother,brother,sister,uncle,aunt',

            // Siblings Table Fields (Up to 2)
            'sibling_1_name'          => 'nullable|string|max:255',
            'sibling_1_age'           => 'nullable|integer|min:1|max:20',
            'sibling_1_class'         => 'nullable|string',
            'sibling_1_institution'   => 'nullable|integer|exists:institutions,id',
            'sibling_1_relationship'  => 'nullable|string|in:brother,sister',
            'sibling_2_name'          => 'nullable|string|max:255',
            'sibling_2_age'           => 'nullable|integer|min:1|max:20',
            'sibling_2_class'         => 'nullable|string',
            'sibling_2_institution'   => 'nullable|integer|exists:institutions,id',
            'sibling_2_relationship'  => 'nullable|string|in:brother,sister',

            // Reference
            'referer_type'            => 'nullable|string|in:student,teacher',
            'referred_by'             => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->referer_type === 'student') {
                        $exists = DB::table('students')->where('id', $value)->exists();
                    } elseif ($request->referer_type === 'teacher') {
                        $exists = DB::table('teachers')->where('id', $value)->exists();
                    } else {
                        $exists = false;
                    }

                    if (! $exists) {
                        $fail('The referred person must be a valid ' . $request->referer_type . '.');
                    }
                },
            ],
        ]);

        return DB::transaction(function () use ($validated) {
            // Fetch branch and class details
            $branch = auth()->user()->branch;
            $class  = ClassName::findOrFail($validated['student_class']);
            $year   = Carbon::now()->format('y');

            // Generate student unique ID
            $maxStudent = Student::where('class_id', $class->id)
                ->where('student_unique_id', 'like', "{$branch->branch_prefix}-{$year}{$class->class_numeral}%")
                ->orderByDesc('student_unique_id')
                ->first();

            $nextStudentId = $maxStudent ? (int) substr($maxStudent->student_unique_id, -2) + 1 : 1;
            $nextStudentId = min($nextStudentId, 99); // Ensure it doesn't exceed 99

            $studentUniqueId = "{$branch->branch_prefix}-{$year}{$class->class_numeral}" . str_pad($nextStudentId, 2, '0', STR_PAD_LEFT);

            // Insert student record
            $student = Student::create([
                'student_unique_id' => $studentUniqueId,
                'branch_id'         => $branch->id,
                'name'              => $validated['student_name'],
                'date_of_birth'     => Carbon::createFromFormat('d-m-Y', $validated['birth_date'])->format('Y-m-d'),
                'gender'            => $validated['student_gender'],
                'class_id'          => $validated['student_class'],
                'academic_group'    => $validated['student_academic_group'] ?? 'General',
                'shift_id'          => $validated['student_shift'],
                'institution_id'    => $validated['student_institution'],
                'religion'          => $validated['student_religion'] ?? null,
                'blood_group'       => $validated['student_blood_group'] ?? null,
                'home_address'      => $validated['student_home_address'] ?? null,
                'email'             => $validated['student_email'] ?? null,
                'password'          => Hash::make('password'),
                'reference_id'      => null,
                'remarks'           => $validated['student_remarks'] ?? null,
            ]);

            if (! $student) {
                return response()->json(['error' => 'Student creation failed!'], 500);
            }

            // ✅ Handle file upload with unique_id prefix (only if a file is provided)
            if (isset($validated['avatar'])) {
                $file      = $validated['avatar']; // ✅ Directly access the file
                $extension = $file->getClientOriginalExtension();
                $filename  = $studentUniqueId . '_photo' . '.' . $extension;
                $photoPath = public_path('uploads/students/'); // Full path

                // ✅ Check if folder exists, if not, create it with proper permissions
                if (! file_exists($photoPath)) {
                    mkdir($photoPath, 0777, true); // 0777 allows full read/write access
                }

                // ✅ Move the file
                $file->move($photoPath, $filename);

                $imageURL = 'uploads/students/' . $filename;

                // ✅ Update student photo in DB
                $student->update(['photo_url' => $imageURL]);
            }

            // Attach subjects using subjectsTaken() relationship
            foreach ($validated['subjects'] as $subjectId) {
                $student->subjectsTaken()->create(['subject_id' => $subjectId]);
            }

            // Insert guardians
            for ($i = 1; $i <= 3; $i++) {
                if (! empty($validated["guardian_{$i}_name"])) {
                    Guardian::create([
                        'student_id'    => $student->id,
                        'name'          => $validated["guardian_{$i}_name"],
                        'mobile_number' => $validated["guardian_{$i}_mobile"],
                        'gender'        => $validated["guardian_{$i}_gender"],
                        'relationship'  => $validated["guardian_{$i}_relationship"],
                        'password'      => Hash::make('password'),
                    ]);
                }
            }

            // Insert siblings
            for ($i = 1; $i <= 2; $i++) {
                if (! empty($validated["sibling_{$i}_name"])) {
                    Sibling::create([
                        'student_id'     => $student->id,
                        'name'           => $validated["sibling_{$i}_name"],
                        'age'            => $validated["sibling_{$i}_age"],
                        'class'          => $validated["sibling_{$i}_class"],
                        'institution_id' => $validated["sibling_{$i}_institution"],
                        'relationship'   => $validated["sibling_{$i}_relationship"],
                    ]);
                }
            }

            // Handle reference (if applicable)
            if (! empty($validated['referer_type']) && ! empty($validated['referred_by'])) {
                $reference = Reference::create([
                    'referer_id'   => $validated['referred_by'],
                    'referer_type' => $validated['referer_type'],
                ]);

                // Update student reference_id
                $student->update(['reference_id' => $reference->id]);
            }

            // Insert data into the Payment model
            Payment::create([
                'student_id'    => $student->id,
                'payment_style' => $validated['payment_style'],
                'due_date'      => $validated['payment_due_date'], // Now always required
                'tuition_fee'   => $validated['student_tuition_fee'],
            ]);

            // Insert mobile numbers using `create()`
            MobileNumber::create([
                'student_id'    => $student->id,
                'mobile_number' => $validated['student_phone_home'],
                'number_type'   => 'home',
            ]);

            MobileNumber::create([
                'student_id'    => $student->id,
                'mobile_number' => $validated['student_phone_sms'],
                'number_type'   => 'sms',
            ]);

            if (isset($validated['student_phone_whatsapp'])) {
                MobileNumber::create([
                    'student_id'    => $student->id,
                    'mobile_number' => $validated['student_phone_whatsapp'],
                    'number_type'   => 'whatsapp',
                ]);
            }

            return response()->json([
                'success' => true,
                'student' => $student,
                'message' => 'Student created successfully',
            ]);
        });
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
        $student = Student::findOrFail($id);

        // Restrict access: Only allow editing if the user belongs to the same branch
        if (auth()->user()->branch_id != 0 && auth()->user()->branch_id != $student->branch_id) {
            return redirect()->route('students.index')->with('error', 'This student is not available on this branch.');
        }

        // Fetch students based on branch access
        $studentsQuery = Student::where('student_activation_id', '!=', null)->withoutTrashed()->orderby('id', 'desc');

        if (auth()->user()->branch_id != 0) {
            $studentsQuery->where('branch_id', auth()->user()->branch_id);
        }

        $students = $studentsQuery->get();

        $classnames   = ClassName::all();
        $shifts       = Shift::all();
        $institutions = Institution::all();

        return view('students.edit', compact('student', 'students', 'classnames', 'shifts', 'institutions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        // Validate request data
        $validated = $request->validate([
            // Student Table Fields
            'student_name'            => 'required|string|max:255',
            'student_home_address'    => 'nullable|string|max:500',
            'student_email'           => 'nullable|email|max:255|unique:students,email,' . $student->id,
            'birth_date'              => 'required',
            'student_gender'          => 'required|in:male,female',
            'student_religion'        => 'nullable|string|in:Islam,Hinduism,Christianity,Buddhism,Others',
            'student_blood_group'     => 'nullable|string',
            'student_class'           => 'required|integer|exists:class_names,id',
            'student_academic_group'  => 'nullable|string|in:General,Science,Commerce,Arts',
            'student_shift'           => 'required|integer|exists:shifts,id',
            'student_institution'     => 'required|integer|exists:institutions,id',
            'subjects'                => 'required|array',
            'subjects.*'              => 'integer|exists:subjects,id',
            'student_remarks'         => 'nullable|string|max:1000',
            'avatar'                  => 'nullable|image|mimes:jpg,jpeg,png|max:200',

            // Mobile Numbers Table Fields (Up to 3)
            'student_phone_home'      => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'student_phone_sms'       => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'student_phone_whatsapp'  => ['nullable', 'regex:/^01[3-9]\d{8}$/'],

            // Payment Table Fields
            'student_tuition_fee'     => 'required|numeric|min:0',
            'payment_style'           => 'required|in:current,due',
            'payment_due_date'        => 'required|integer|in:7,10,15,30',

            // Guardians Table Fields (Up to 3)
            'guardian_1_name'         => 'required|string|max:255',
            'guardian_1_mobile'       => 'required|string|max:11',
            'guardian_1_gender'       => 'required|in:male,female',
            'guardian_1_relationship' => 'required|string|in:father,mother,brother,sister,uncle,aunt',
            'guardian_2_name'         => 'nullable|string|max:255',
            'guardian_2_mobile'       => 'nullable|string|max:11',
            'guardian_2_gender'       => 'nullable|in:male,female',
            'guardian_2_relationship' => 'nullable|string|in:father,mother,brother,sister,uncle,aunt',
            'guardian_3_name'         => 'nullable|string|max:255',
            'guardian_3_mobile'       => 'nullable|string|max:11',
            'guardian_3_gender'       => 'nullable|in:male,female',
            'guardian_3_relationship' => 'nullable|string|in:father,mother,brother,sister,uncle,aunt',

            // Siblings Table Fields (Up to 2)
            'sibling_1_name'          => 'nullable|string|max:255',
            'sibling_1_age'           => 'nullable|integer|min:1|max:20',
            'sibling_1_class'         => 'nullable|string',
            'sibling_1_institution'   => 'nullable|integer|exists:institutions,id',
            'sibling_1_relationship'  => 'nullable|string|in:brother,sister',
            'sibling_2_name'          => 'nullable|string|max:255',
            'sibling_2_age'           => 'nullable|integer|min:1|max:20',
            'sibling_2_class'         => 'nullable|string',
            'sibling_2_institution'   => 'nullable|integer|exists:institutions,id',
            'sibling_2_relationship'  => 'nullable|string|in:brother,sister',
        ]);

        return DB::transaction(function () use ($validated, $student) {
            // Update student record
            $student->update([
                'name'           => $validated['student_name'],
                'date_of_birth'  => Carbon::createFromFormat('d-m-Y', $validated['birth_date'])->format('Y-m-d'),
                'gender'         => $validated['student_gender'],
                'class_id'       => $validated['student_class'],
                'academic_group' => $validated['student_academic_group'] ?? 'General',
                'shift_id'       => $validated['student_shift'],
                'institution_id' => $validated['student_institution'],
                'religion'       => $validated['student_religion'] ?? null,
                'blood_group'    => $validated['student_blood_group'] ?? null,
                'home_address'   => $validated['student_home_address'] ?? null,
                'email'          => $validated['student_email'] ?? null,
                'remarks'        => $validated['student_remarks'] ?? null,
            ]);

            // Handle avatar update
            if (isset($validated['avatar'])) {
                $file      = $validated['avatar'];
                $extension = $file->getClientOriginalExtension();
                $filename  = $student->student_unique_id . '_photo.' . $extension;
                $photoPath = public_path('uploads/students/');
                if (! file_exists($photoPath)) {
                    mkdir($photoPath, 0777, true);
                }
                $file->move($photoPath, $filename);
                $student->update(['photo_url' => 'uploads/students/' . $filename]);
            }

            // Update subjects
            $student->subjectsTaken()->delete();
            foreach ($validated['subjects'] as $subjectId) {
                $student->subjectsTaken()->create(['subject_id' => $subjectId]);
            }

            // Update guardians
            foreach ([1, 2, 3] as $i) {
                if (! empty($validated["guardian_{$i}_name"])) {
                    $student->guardians()->updateOrCreate(
                        ['relationship' => $validated["guardian_{$i}_relationship"]],
                        [
                            'name'          => $validated["guardian_{$i}_name"],
                            'mobile_number' => $validated["guardian_{$i}_mobile"],
                            'gender'        => $validated["guardian_{$i}_gender"],
                        ]
                    );
                }
            }

            // Update siblings
            foreach ([1, 2] as $i) {
                if (! empty($validated["sibling_{$i}_name"])) {
                    $student->siblings()->updateOrCreate(
                        ['name' => $validated["sibling_{$i}_name"]],
                        [
                            'age'            => $validated["sibling_{$i}_age"],
                            'class'          => $validated["sibling_{$i}_class"],
                            'institution_id' => $validated["sibling_{$i}_institution"],
                            'relationship'   => $validated["sibling_{$i}_relationship"],
                        ]
                    );
                }
            }

            // Update payment details
            $student->payments()->update([
                'payment_style' => $validated['payment_style'],
                'due_date'      => $validated['payment_due_date'],
                'tuition_fee'   => $validated['student_tuition_fee'],
            ]);

            // Update mobile numbers
            $student->mobileNumbers()->delete();
            MobileNumber::create(['student_id' => $student->id, 'mobile_number' => $validated['student_phone_home'], 'number_type' => 'home']);

            MobileNumber::create(['student_id' => $student->id, 'mobile_number' => $validated['student_phone_sms'], 'number_type' => 'sms']);

            if (isset($validated['student_phone_whatsapp'])) {
                MobileNumber::create(['student_id' => $student->id, 'mobile_number' => $validated['student_phone_whatsapp'], 'number_type' => 'whatsapp']);
            }

            return response()->json(['success' => true, 'student' => $student, 'message' => 'Student updated successfully']);
        });
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
                        'id'   => $teacher->id,
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
                        'id'                => $student->id,
                        'name'              => $student->name,
                        'student_unique_id' => $student->student_unique_id, // Keep the unique_id for students
                    ];
                }),
            );
        }

        return response()->json([]);
    }
}
