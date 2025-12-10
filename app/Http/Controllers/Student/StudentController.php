<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Academic\Institution;
use App\Models\Branch;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentInvoice;
use App\Models\Student\Guardian;
use App\Models\Student\MobileNumber;
use App\Models\Student\Reference;
use App\Models\Student\Sibling;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $branchId = auth()->user()->branch_id;

        // Unique cache key per branch (useful when branch_id != 0)
        $cacheKey = 'students_list_branch_' . $branchId;

        $students = Cache::remember($cacheKey, now()->addHours(1), function () use ($branchId) {
            return Student::with([
                'class:id,name,class_numeral',
                'branch:id,branch_name,branch_prefix',
                'batch:id,name',
                'institution:id,name,eiin_number',
                'studentActivation:id,active_status',
                'guardians:id,name,relationship,student_id',
                'mobileNumbers:id,mobile_number,number_type,student_id',
                'payments:id,payment_style,due_date,tuition_fee,student_id',
            ])
                ->whereNotNull('student_activation_id')
                ->when($branchId != 0, function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->whereHas('class', function ($query) {
                    $query->where('is_active', true);
                })
                ->latest('updated_at')
                ->get();
        });

        $classnames = ClassName::get();
        $batches    = Batch::with('branch:id,branch_name')->when(auth()->user()->branch_id != 0, function ($query) {
            $query->where('branch_id', auth()->user()->branch_id);
        })
            ->select('id', 'name', 'branch_id')
            ->get();

        $institutions = Institution::all();
        $branches     = Branch::all();

        return view('students.index', compact('students', 'classnames', 'batches', 'institutions', 'branches'));

    }

    public function pending()
    {
        $branchId = auth()->user()->branch_id;

        $students = Student::with([
            'class:id,name,class_numeral',
            'branch:id,branch_name,branch_prefix',
            'batch:id,name',
            'institution:id,name,eiin_number',
            'guardians:id,name,relationship,student_id',
            'mobileNumbers:id,mobile_number,number_type,student_id',
            'payments:id,payment_style,due_date,tuition_fee,student_id'])
            ->whereNull('student_activation_id')
            ->when($branchId != 0, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->latest()
            ->get();

        $classnames = ClassName::where('is_active', true)->get();
        $batches    = Batch::with('branch:id,branch_name')->when(auth()->user()->branch_id != 0, function ($query) {
            $query->where('branch_id', auth()->user()->branch_id);
        })
            ->select('id', 'name', 'branch_id')
            ->get();

        $institutions = Institution::all();
        $branches     = Branch::all();

        return view('students.pending', compact('students', 'classnames', 'batches', 'institutions', 'branches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // $students     = Student::all();
        // $guardians    = Guardian::all();
        // $subjects     = Subject::all();
        $classnames   = ClassName::where('is_active', true)->latest('class_numeral')->get();
        $institutions = Institution::select('id', 'name', 'eiin_number')->get();

        $batches = Batch::when(auth()->user()->branch_id != 0, function ($query) {
            $query->where('branch_id', auth()->user()->branch_id);
        })
            ->select('id', 'name', 'branch_id')
            ->get();

        $branches = Branch::when(auth()->user()->branch_id != 0, function ($query) {
            $query->where('id', auth()->user()->branch_id);
        })
            ->select('id', 'branch_name', 'branch_prefix')
            ->get();

        return view('students.create', compact('classnames', 'batches', 'institutions', 'branches'));
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
            'birth_date'              => 'nullable|date',
            'student_gender'          => 'required|in:male,female',
            'student_religion'        => 'nullable|string|in:Islam,Hinduism,Christianity,Buddhism,Others',
            'student_blood_group'     => 'nullable|string',
            'student_class'           => 'required|integer|exists:class_names,id',
            'student_academic_group'  => 'nullable|string|in:General,Science,Commerce,Arts',
            'student_branch'          => 'required|integer|exists:branches,id',
            'student_batch'           => 'required|integer|exists:batches,id',
            'student_institution'     => 'required|integer|exists:institutions,id',
            'subjects'                => 'required|array',
            'subjects.*'              => 'integer|exists:subjects,id',
            'student_remarks'         => 'nullable|string|max:1000',
            'avatar'                  => 'nullable|image|mimes:jpg,jpeg,png|max:100',

            // Mobile Numbers Table Fields (Up to 3)
            'student_phone_home'      => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'student_phone_sms'       => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'student_phone_whatsapp'  => ['nullable', 'regex:/^01[3-9]\d{8}$/'], // Made this field optional

            // Payment Table Fields
            'student_tuition_fee'     => 'required|numeric|min:0',
            'payment_style'           => 'required|in:current,due',
            'payment_due_date'        => 'required|integer|in:7,10,15,30',

            // Guardians Table Fields (Up to 2)
            'guardian_1_name'         => 'required|string|max:255',
            'guardian_1_mobile'       => 'required|string|max:11',
            'guardian_1_gender'       => 'required|in:male,female',
            'guardian_1_relationship' => 'required|string|in:father,mother,brother,sister,uncle,aunt',
            'guardian_2_name'         => 'nullable|string|max:255',
            'guardian_2_mobile'       => 'nullable|string|max:11',
            'guardian_2_gender'       => 'nullable|in:male,female',
            'guardian_2_relationship' => 'nullable|string|in:father,mother,brother,sister,uncle,aunt',

            // Siblings Table Fields (Up to 2)
            'sibling_1_name'          => 'nullable|string|max:255',
            'sibling_1_year'          => 'nullable|string',
            'sibling_1_class'         => 'nullable|string',
            'sibling_1_institution'   => 'nullable|string',
            'sibling_1_relationship'  => 'nullable|string|in:brother,sister',
            'sibling_2_name'          => 'nullable|string|max:255',
            'sibling_2_year'          => 'nullable|string',
            'sibling_2_class'         => 'nullable|string',
            'sibling_2_institution'   => 'nullable|string',
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
            $branch = Branch::findOrFail($validated['student_branch']);
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
                'date_of_birth'     => ! empty($validated['birth_date']) ? Carbon::createFromFormat('d-m-Y', $validated['birth_date']) : null,
                'gender'            => $validated['student_gender'],
                'class_id'          => $validated['student_class'],
                'academic_group'    => $validated['student_academic_group'] ?? 'General',
                'batch_id'          => $validated['student_batch'],
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
            for ($i = 1; $i <= 2; $i++) {
                if (! empty($validated["guardian_{$i}_name"])) {
                    Guardian::create([
                        'student_id' => $student->id,
                        'name'       => $validated["guardian_{$i}_name"],
                        'mobile_number' => $validated["guardian_{$i}_mobile"],
                        'gender' => $validated["guardian_{$i}_gender"],
                        'relationship' => $validated["guardian_{$i}_relationship"],
                        'password' => Hash::make('password'),
                    ]);
                }
            }

            // Insert siblings
            for ($i = 1; $i <= 2; $i++) {
                if (! empty($validated["sibling_{$i}_name"])) {
                    Sibling::create([
                        'student_id' => $student->id,
                        'name'       => $validated["sibling_{$i}_name"],
                        'year' => $validated["sibling_{$i}_year"],
                        'class' => $validated["sibling_{$i}_class"],
                        'institution_name' => $validated["sibling_{$i}_institution"],
                        'relationship' => $validated["sibling_{$i}_relationship"],
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

            // Create a invoice if the payment style is Current
            if ($validated['payment_style'] == 'current') {
                $yearSuffix = now()->format('y'); // '25'
                $month      = now()->format('m'); // '05'
                $prefix     = $student->branch->branch_prefix;
                $monthYear  = now()->format('m_Y');

                // Fetch the last invoice for the same prefix and month
                $lastInvoice = PaymentInvoice::where('invoice_number', 'like', "{$prefix}{$yearSuffix}{$month}_%")
                    ->orderBy('invoice_number', 'desc')
                    ->first();

                if ($lastInvoice) {
                    // Extract the numeric sequence after the last underscore
                    $lastSequence = (int) substr($lastInvoice->invoice_number, strrpos($lastInvoice->invoice_number, '_') + 1);
                    $nextSequence = $lastSequence + 1;
                } else {
                    $nextSequence = 1001; // Start from 1001 if no previous invoice
                }

                $invoiceNumber = "{$prefix}{$yearSuffix}{$month}_{$nextSequence}";

                PaymentInvoice::create([
                    'invoice_number' => $invoiceNumber,
                    'student_id'     => $student->id,
                    'total_amount'   => $validated['student_tuition_fee'],
                    'amount_due'     => $validated['student_tuition_fee'],
                    'month_year'     => $monthYear,
                ]);
            }

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

            // Clear the cache
            clearUCMSCaches();

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
        // Try to find the student including trashed ones
        // We add 'attendances' to eager loading here to avoid N+1 queries later
        $student = Student::withTrashed()->with('attendances')->find($id);

        // If not found or trashed, redirect with warning
        if (! $student || $student->trashed()) {
            return redirect()->route('students.index')->with('warning', 'Student not found or deleted.');
        }

        // Restrict access: Only allow editing if the user belongs to the same branch
        if (auth()->user()->branch_id != 0 && auth()->user()->branch_id != $student->branch_id) {
            return redirect()->route('students.index')->with('error', 'Student not found in this branch.');
        }

        // --- NEW CODE: Prepare Attendance Events for Calendar ---
        $attendance_events = $student->attendances->map(function ($attendance) {
                                 // Define Metronic Theme Colors
            $color  = '#50cd89'; // Green (Present)
            $status = strtolower($attendance->status);

            if ($status === 'absent') {
                $color = '#f1416c'; // Red
            } elseif ($status === 'late') {
                $color = '#ffc700'; // Yellow/Orange
            } elseif ($status === 'excused' || $status === 'leave') {
                $color = '#7239ea'; // Purple
            }

            return [
                'title'       => ucfirst($attendance->status),
                'start'       => $attendance->attendance_date->format('Y-m-d'),
                'description' => $attendance->remarks ?? '',
                'color'       => $color, // e.g. #50cd89
            ];
        });
        // --------------------------------------------------------

        // Get all sheet payments of the student
        $sheetPayments = $student
            ->sheetPayments()
            ->with(['sheet.class.subjects'])
            ->get();

        // Extract unique class names
        $sheet_class_names = $sheetPayments
            ->pluck('sheet.class')
            ->unique('id')
            ->map(function ($class) {
                return [
                    'name'          => $class->name,
                    'class_numeral' => $class->class_numeral,
                ];
            });

        // Extract unique subject names from those classes
        $sheet_subjectNames = $sheetPayments
            ->pluck('sheet.class.subjects')
            ->flatten()
            ->unique('name')
            ->pluck('name')
            ->sort()
            ->values();

        return view('students.view', compact('student', 'sheet_class_names', 'sheet_subjectNames', 'attendance_events'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Try to find the student including trashed ones (if soft deletes are enabled)
        $student = Student::with('reference.referer')->withTrashed()->find($id);

        // If not found or trashed, redirect with warning
        if (! $student || $student->trashed()) {
            return redirect()->route('students.index')->with('warning', 'Student not found or deleted.');
        }

        // Restrict access: Only allow editing if the user belongs to the same branch
        if (auth()->user()->branch_id != 0 && auth()->user()->branch_id != $student->branch_id) {
            return redirect()->route('students.index')->with('error', 'This student is not available on this branch.');
        }

        // Fetch students based on branch access
        $studentsQuery = Student::whereNotNull('student_activation_id')->latest('id');

        if (auth()->user()->branch_id != 0) {
            $studentsQuery->where('branch_id', auth()->user()->branch_id);
        }

        $students = $studentsQuery->get();

        $classnames   = ClassName::where('is_active', true)->get();
        $batches      = Batch::where('branch_id', $student->branch_id)->get();
        $institutions = Institution::all();

        return view('students.edit', compact('student', 'students', 'classnames', 'batches', 'institutions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        $isAccountant = auth()->user()->hasRole('accountant');

        // Validate request data
        $validated = $request->validate([
            // Student Table Fields
            'student_name'            => 'required|string|max:255',
            'student_home_address'    => 'nullable|string|max:500',
            'student_email'           => 'nullable|email|max:255|unique:students,email,' . $student->id,
            'birth_date'              => 'nullable|date',
            'student_gender'          => 'required|in:male,female',
            'student_religion'        => 'nullable|string|in:Islam,Hinduism,Christianity,Buddhism,Others',
            'student_blood_group'     => 'nullable|string',
            'student_class'           => $isAccountant ? 'nullable' : 'required|integer|exists:class_names,id',
            'student_academic_group'  => 'nullable|string|in:General,Science,Commerce,Arts',
            'student_batch'           => $isAccountant ? 'nullable' : 'required|integer|exists:batches,id',
            'student_institution'     => $isAccountant ? 'nullable' : 'required|integer|exists:institutions,id',
            'subjects'                => 'required|array',
            'subjects.*'              => 'integer|exists:subjects,id',
            'student_remarks'         => 'nullable|string|max:1000',
            'avatar'                  => 'nullable|image|mimes:jpg,jpeg,png|max:100',

            // Mobile Numbers Table Fields (Up to 3)
            'student_phone_home'      => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'student_phone_sms'       => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'student_phone_whatsapp'  => ['nullable', 'regex:/^01[3-9]\d{8}$/'],

            // Payment Table Fields
            'student_tuition_fee'     => $isAccountant ? 'nullable' : 'required|numeric|min:0',
            'payment_style'           => $isAccountant ? 'nullable' : 'required|in:current,due',
            'payment_due_date'        => $isAccountant ? 'nullable' : 'required|integer|in:7,10,15,30',

            // Guardians Table Fields (Up to 3)
            'guardian_1_id'           => 'nullable|integer|exists:guardians,id',
            'guardian_1_name'         => 'required|string|max:255',
            'guardian_1_mobile'       => 'required|string|max:11',
            'guardian_1_gender'       => 'required|in:male,female',
            'guardian_1_relationship' => 'required|string|in:father,mother,brother,sister,uncle,aunt',

            'guardian_2_id'           => 'nullable|integer|exists:guardians,id',
            'guardian_2_name'         => 'nullable|string|max:255',
            'guardian_2_mobile'       => 'nullable|string|max:11',
            'guardian_2_gender'       => 'nullable|in:male,female',
            'guardian_2_relationship' => 'nullable|string|in:father,mother,brother,sister,uncle,aunt',

            // Siblings Table Fields (Up to 2)
            'sibling_1_id'            => 'nullable|integer|exists:siblings,id',
            'sibling_1_name'          => 'nullable|string|max:255',
            'sibling_1_year'          => 'nullable|string',
            'sibling_1_class'         => 'nullable|string',
            'sibling_1_institution'   => 'nullable|string',
            'sibling_1_relationship'  => 'nullable|string|in:brother,sister',

            'sibling_2_id'            => 'nullable|integer|exists:siblings,id',
            'sibling_2_name'          => 'nullable|string|max:255',
            'sibling_2_year'          => 'nullable|string',
            'sibling_2_class'         => 'nullable|string',
            'sibling_2_institution'   => 'nullable|string',
            'sibling_2_relationship'  => 'nullable|string|in:brother,sister',
        ]);

        return DB::transaction(function () use ($validated, $student, $isAccountant) {
            // Update student record
            $student->update([
                'name'          => $validated['student_name'],
                'date_of_birth' => ! empty($validated['birth_date']) ? Carbon::createFromFormat('d-m-Y', $validated['birth_date']) : null,
                'gender'        => $validated['student_gender'],
                'religion'      => $validated['student_religion'] ?? null,
                'blood_group'   => $validated['student_blood_group'] ?? null,
                'home_address'  => $validated['student_home_address'] ?? null,
                'email'         => $validated['student_email'] ?? null,
                'remarks'       => $validated['student_remarks'] ?? null,
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
            foreach ([1, 2] as $i) {
                $guardianId = $validated["guardian_{$i}_id"] ?? null;
                $name       = $validated["guardian_{$i}_name"] ?? null;
                $mobile     = $validated["guardian_{$i}_mobile"] ?? null;
                $gender     = $validated["guardian_{$i}_gender"] ?? null;
                $relation   = $validated["guardian_{$i}_relationship"] ?? null;

                $allFieldsEmpty = ! $name && ! $mobile && ! $gender && ! $relation;

                if (! $allFieldsEmpty && $relation) {
                    // 🔍 Check if the same relationship is already assigned to another guardian
                    $exists = $student->guardians()->where('relationship', $relation)->when($guardianId, fn($q) => $q->where('id', '!=', $guardianId))->exists();

                    if ($exists) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "guardian_{$i}_relationship" => 'Cannot add another ' . $relation . ' type guardian.',
                        ]);
                    }
                }

                // Rest of your logic continues below
                if ($guardianId && ! $allFieldsEmpty) {
                    // Update existing guardian
                    $guardian = Guardian::find($guardianId);
                    if ($guardian) {
                        $guardian->update([
                            'name'          => $name,
                            'mobile_number' => $mobile,
                            'gender'        => $gender,
                            'relationship'  => $relation,
                        ]);
                    }
                } elseif ($guardianId && $allFieldsEmpty) {
                    // Delete if ID exists but all fields are empty
                    Guardian::find($guardianId)?->delete();
                } elseif (! $guardianId && ! $allFieldsEmpty) {
                    // Create new guardian if no ID but fields are filled
                    $student->guardians()->create([
                        'name'          => $name,
                        'mobile_number' => $mobile,
                        'gender'        => $gender,
                        'relationship'  => $relation,
                    ]);
                }
            }

            // Update siblings
            foreach ([1, 2] as $i) {
                $siblingId   = $validated["sibling_{$i}_id"] ?? null;
                $name        = $validated["sibling_{$i}_name"] ?? null;
                $year        = $validated["sibling_{$i}_year"] ?? null;
                $class       = $validated["sibling_{$i}_class"] ?? null;
                $institution = $validated["sibling_{$i}_institution"] ?? null;
                $relation    = $validated["sibling_{$i}_relationship"] ?? null;

                $allFieldsEmpty = ! $name && ! $year && ! $class && ! $institution && ! $relation;

                if ($siblingId && ! $allFieldsEmpty) {
                    // Update existing sibling
                    $sibling = Sibling::find($siblingId);
                    if ($sibling) {
                        $sibling->update([
                            'name'             => $name,
                            'year'             => $year,
                            'class'            => $class,
                            'institution_name' => $institution,
                            'relationship'     => $relation,
                        ]);
                    }
                } elseif ($siblingId && $allFieldsEmpty) {
                    // Delete sibling if ID exists but all fields are blank
                    Sibling::find($siblingId)?->delete();
                } elseif (! $siblingId && ! $allFieldsEmpty) {
                    // Create new sibling if no ID but fields are filled
                    $student->siblings()->create([
                        'name'             => $name,
                        'year'             => $year,
                        'class'            => $class,
                        'institution_name' => $institution,
                        'relationship'     => $relation,
                    ]);
                }
            }

            // Update mobile numbers
            $student->mobileNumbers()->updateOrCreate(['number_type' => 'home'], ['mobile_number' => $validated['student_phone_home']]);

            $student->mobileNumbers()->updateOrCreate(['number_type' => 'sms'], ['mobile_number' => $validated['student_phone_sms']]);

            if (isset($validated['student_phone_whatsapp'])) {
                $whatsappMobile = $student->mobileNumbers()->where('number_type', 'whatsapp')->first();
                if ($whatsappMobile) {
                    $whatsappMobile->update(['mobile_number' => $validated['student_phone_whatsapp']]);
                } else {
                    MobileNumber::create(['student_id' => $student->id, 'mobile_number' => $validated['student_phone_whatsapp'], 'number_type' => 'whatsapp']);
                }
            }

            // Accountant cannot update step 3 and step 4
            if (! $isAccountant) {
                $student->update([
                    'class_id'       => $validated['student_class'],
                    'academic_group' => $validated['student_academic_group'] ?? 'General',
                    'batch_id'       => $validated['student_batch'],
                    'institution_id' => $validated['student_institution'],
                ]);

                // Update payment details
                $student->payments()->update([
                    'payment_style' => $validated['payment_style'],
                    'due_date'      => $validated['payment_due_date'],
                    'tuition_fee'   => $validated['student_tuition_fee'],
                ]);
            }

            // Clear the cache
            clearUCMSCaches();

            return response()->json(['success' => true, 'student' => $student, 'message' => 'Student updated successfully']);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        $deletedBy = auth()->user()->id;

        // Update 'deleted_by' for guardians before deleting
        $student->guardians()->update(['deleted_by' => $deletedBy]);

        // Update 'deleted_by' for siblings before deleting
        $student->siblings()->update(['deleted_by' => $deletedBy]);

        // Update 'deleted_by' for student before deleting
        $student->update(['deleted_by' => $deletedBy]);

        // Now delete all records
        $student->guardians()->delete();
        $student->siblings()->delete();
        $student->delete();

        // Clear the cache
        clearUCMSCaches();

        return response()->json(['success' => true]);
    }

    public function getReferredData(Request $request)
    {
        $refererType = $request->get('referer_type');

        if ($refererType == 'teacher') {
                                        // Fetch teacher data (no unique_id)
            $teachers = Teacher::all(); // Adjust according to your data model
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
            $students = Student::all(); // Adjust according to your data model
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

    /**
     * Get the invoice month year for a student.
     */
    public function getInvoiceMonthsData(Student $student)
    {
        $lastInvoice = $student->paymentInvoices()
            ->where('invoice_type', 'tuition_fee')
            ->orderByRaw("SUBSTRING_INDEX(month_year, '_', -1) DESC, SUBSTRING_INDEX(month_year, '_', 1) DESC")
            ->first();

        $oldestInvoice = $student->paymentInvoices()
            ->where('invoice_type', 'tuition_fee')
            ->orderByRaw("SUBSTRING_INDEX(month_year, '_', -1) ASC, SUBSTRING_INDEX(month_year, '_', 1) ASC")
            ->first();

        return response()->json([
            'last_invoice_month'   => $lastInvoice ? $lastInvoice->month_year : null,
            'oldest_invoice_month' => $oldestInvoice ? $oldestInvoice->month_year : null,
            'tuition_fee'          => $student->payments->tuition_fee,
            'payment_style'        => $student->payments->payment_style,
        ]);
    }

    /* Get the sheet fee for a student */
    public function getSheetFee($id)
    {
        $student  = Student::with('class.sheet')->findOrFail($id);
        $sheetFee = optional($student->class->sheet)->price;

        return response()->json(['sheet_fee' => $sheetFee]);
    }

    /* Transfer a student from one branch to another */
    public function transferStudent()
    {
        if (! auth()->user()->can('students.transfer')) {
            return redirect()->back()->with('warning', 'No permission to transfer students.');
        }

        $students = Student::whereHas('studentActivation', function ($query) {
            $query->where('active_status', 'active');
        })
            ->select('id', 'name', 'student_unique_id', 'branch_id', 'class_id', 'batch_id')
            ->get();

        $branches = Branch::all();

        return view('students.transfer', compact('students', 'branches'));
    }

    /* Transfer a student from one branch to another */
    public function promoteStudents()
    {
        if (! auth()->user()->can('students.promote')) {
            return redirect()->back()->with('warning', 'No permission to promote students.');
        }

        $students = Student::whereHas('studentActivation', function ($query) {
            $query->where('active_status', 'active');
        })
            ->select('id', 'name', 'student_unique_id', 'branch_id', 'class_id', 'batch_id')
            ->get();

        $classes = ClassName::where('is_active', true)->get();

        return view('students.promote', compact('students', 'classes'));
    }

    /* Old Student - Alumni */
    public function alumniStudent()
    {
        $branchId = auth()->user()->branch_id;

        $students = Student::with([
            // remove the shorthand for class and use a closure to disable the global scope
            'class' => function ($q) {
                $q->withoutGlobalScope('active')
                    ->select('id', 'name', 'class_numeral');
            },
            'branch:id,branch_name,branch_prefix',
            'batch:id,name',
            'institution:id,name,eiin_number',
            'studentActivation:id,active_status',
            'guardians:id,name,relationship,student_id',
            'mobileNumbers:id,mobile_number,number_type,student_id',
            'payments:id,payment_style,due_date,tuition_fee,student_id',
        ])
            ->whereNotNull('student_activation_id')
            ->when($branchId != 0, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->whereHas('class', function ($q) {
                $q->withoutGlobalScope('active')
                    ->where('is_active', false);
            })
            ->latest('updated_at')
            ->get();

        $classnames = ClassName::withoutGlobalScope('active')->where('is_active', false)->get();
        $batches    = Batch::with('branch:id,branch_name')->when(auth()->user()->branch_id != 0, function ($query) {
            $query->where('branch_id', auth()->user()->branch_id);
        })
            ->select('id', 'name', 'branch_id')
            ->get();

        $institutions = Institution::all();
        $branches     = Branch::all();

        return view('students.alumni.index', compact('students', 'classnames', 'batches', 'institutions', 'branches'));
    }
}
