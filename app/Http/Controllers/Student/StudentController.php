<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Academic\Institution;
use App\Models\Academic\SecondaryClass;
use App\Models\Branch;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentInvoiceType;
use App\Models\Sheet\Sheet;
use App\Models\Student\Guardian;
use App\Models\Student\MobileNumber;
use App\Models\Student\Reference;
use App\Models\Student\Sibling;
use App\Models\Student\Student;
use App\Models\Student\StudentSecondaryClass;
use App\Services\StudentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    protected StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /*
    app/
    ├── Services/
    │   └── StudentService.php              # Shared business logic
    ├── Http/Controllers/Student/
    │   ├── StudentController.php           # Basic CRUD operations (slimmed down)
    │   ├── StudentDataController.php       # Active students AJAX data endpoints
    │   ├── PendingStudentController.php    # Pending students endpoints
    │   └── AlumniStudentController.php     # Alumni students endpoints
    */

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user     = auth()->user();
        $branchId = $user->branch_id;
        $isAdmin  = $user->hasRole('admin');

        // Get all branches for admin - minimal query
        $branches = Branch::select('id', 'branch_name', 'branch_prefix')->get();

        // Use simple queries without eager loading for filter dropdowns
        $classnames = ClassName::where('is_active', true)->select('id', 'name', 'class_numeral')->get();

        $batches = Batch::select('batches.id', 'batches.name', 'batches.branch_id', 'branches.branch_name')
            ->join('branches', 'batches.branch_id', '=', 'branches.id')
            ->when($branchId != 0, function ($query) use ($branchId) {
                $query->where('batches.branch_id', $branchId);
            })
            ->get();

        $institutions = Institution::select('id', 'name')->oldest('name')->get();

        return view('students.index', compact('classnames', 'batches', 'institutions', 'branches', 'isAdmin'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $classnames   = ClassName::active()->latest('class_numeral')->get();
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

        // Fetch Secondary Classes
        $secondaryClasses = SecondaryClass::where('is_active', true)->get();

        return view('students.create', compact('classnames', 'batches', 'institutions', 'branches', 'secondaryClasses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Filter out incomplete subject entries before validation
        if ($request->has('subjects')) {
            $subjects         = $request->input('subjects', []);
            $filteredSubjects = array_filter($subjects, function ($subject) {
                return ! empty($subject['id']);
            });
            $request->merge(['subjects' => array_values($filteredSubjects)]);
        }

        $validated = $request->validate([
            'student_name'            => 'required|string|max:255',
            'student_home_address'    => 'nullable|string|max:500',
            'student_email'           => 'nullable|email|max:255|unique:students,email',
            'birth_date'              => 'nullable|string',
            'student_gender'          => 'required|in:male,female',
            'student_religion'        => 'nullable|string|in:Islam,Hinduism,Christianity,Buddhism,Others',
            'student_blood_group'     => 'nullable|string',
            'student_class'           => 'required|integer|exists:class_names,id',
            'student_academic_group'  => 'string|in:General,Science,Commerce,Arts',
            'student_branch'          => 'required|integer|exists:branches,id',
            'student_batch'           => 'required|integer|exists:batches,id',
            'student_institution'     => 'required|integer|exists:institutions,id',
            'student_remarks'         => 'nullable|string|max:1000',
            'avatar'                  => 'nullable|image|mimes:jpg,jpeg,png|max:100',

            'subjects'                => 'nullable|array',
            'subjects.*.id'           => 'required|integer|exists:subjects,id',
            'subjects.*.is_4th'       => 'required|in:0,1',

            'optional_main_subject'   => 'nullable|integer|exists:subjects,id',
            'optional_4th_subject'    => 'nullable|integer|exists:subjects,id',

            'student_phone_home'      => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'student_phone_sms'       => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'student_phone_whatsapp'  => ['nullable', 'regex:/^01[3-9]\d{8}$/'],

            'student_tuition_fee'     => 'required|numeric|min:0',
            'student_admission_fee'   => 'required|numeric|min:0',
            'payment_style'           => 'required|in:current,due',
            'payment_due_date'        => 'required|integer|in:7,10,15,30',

            'guardian_1_name'         => 'required|string|max:255',
            'guardian_1_mobile'       => 'required|string|max:11',
            'guardian_1_gender'       => 'required|in:male,female',
            'guardian_1_relationship' => 'required|string|in:father,mother,brother,sister,uncle,aunt',
            'guardian_2_name'         => 'nullable|string|max:255',
            'guardian_2_mobile'       => 'nullable|string|max:11',
            'guardian_2_gender'       => 'nullable|in:male,female',
            'guardian_2_relationship' => 'nullable|string|in:father,mother,brother,sister,uncle,aunt',

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

            'referer_type'            => 'nullable|string|in:student,teacher',
            'referred_by'             => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($request) {
                    if (! $request->referer_type || ! $value) {
                        return;
                    }
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

            'secondary_classes'       => 'nullable|array',
            'secondary_classes.*'     => 'exists:secondary_classes,id',
            'secondary_class_fees'    => 'nullable|array',
            'secondary_class_fees.*'  => 'nullable|numeric|min:0',
            'only_secondary_class'    => 'nullable',
        ]);

        // Validate at least one subject is selected
        $subjectsCount = count($validated['subjects'] ?? []);
        $onlySecondary = $request->has('only_secondary_class') && $request->only_secondary_class;

        if ($subjectsCount === 0 && ! $onlySecondary) {
            return response()->json(
                [
                    'success' => false,
                    'errors'  => ['Please select at least one subject'],
                ],
                422,
            );
        }

        $class        = ClassName::findOrFail($validated['student_class']);
        $classNumeral = $class->class_numeral;
        $group        = $validated['student_academic_group'] ?? 'General';

        // Validate optional subjects
        $optionalValidation = $this->studentService->validateOptionalSubjects($validated, $classNumeral, $group);
        if ($optionalValidation !== true) {
            return response()->json(
                [
                    'success' => false,
                    'errors'  => [$optionalValidation],
                ],
                422,
            );
        }

        return DB::transaction(function () use ($validated, $class, $group) {
            $branch = Branch::findOrFail($validated['student_branch']);

            // Generate student_unique_id
            $studentUniqueId = $this->studentService->generateStudentUniqueId($branch, $class);

            $dateOfBirth = null;
            if (! empty($validated['birth_date'])) {
                try {
                    $dateOfBirth = Carbon::createFromFormat('d-m-Y', $validated['birth_date']);
                } catch (\Exception $e) {
                    $dateOfBirth = null;
                }
            }

            $student = Student::create([
                'student_unique_id' => $studentUniqueId,
                'branch_id'         => $branch->id,
                'name'              => $validated['student_name'],
                'date_of_birth'     => $dateOfBirth,
                'gender'            => $validated['student_gender'],
                'class_id'          => $validated['student_class'],
                'academic_group'    => $group,
                'batch_id'          => $validated['student_batch'],
                'institution_id'    => $validated['student_institution'],
                'religion'          => $validated['student_religion'] ?? null,
                'blood_group'       => $validated['student_blood_group'] ?? null,
                'home_address'      => $validated['student_home_address'] ?? null,
                'email'             => $validated['student_email'] ?? null,
                'password'          => Hash::make('password'),
                'reference_id'      => null,
                'remarks'           => $validated['student_remarks'] ?? null,
                'created_by'        => auth()->user()->id,
            ]);

            if (! $student) {
                return response()->json(['error' => 'Student creation failed!'], 500);
            }

            // Handle avatar upload
            if (isset($validated['avatar']) && $validated['avatar']) {
                $file      = $validated['avatar'];
                $extension = $file->getClientOriginalExtension();
                $filename  = $studentUniqueId . '_photo.' . $extension;
                $photoPath = public_path('uploads/students/');
                if (! file_exists($photoPath)) {
                    mkdir($photoPath, 0777, true);
                }
                $file->move($photoPath, $filename);
                $student->update(['photo_url' => 'uploads/students/' . $filename]);
            }

            // Store subjects
            if (empty($validated['only_secondary_class'])) {
                $this->studentService->storeStudentSubjects($student, $validated);
            }

            // Create Guardians
            $this->createGuardians($student, $validated);

            // Create Siblings
            $this->createSiblings($student, $validated);

            // Create Reference
            $this->createReference($student, $validated);

            // Create Payment
            $this->createPayment($student, $validated);

            // Create Secondary Classes Enrollment
            $this->createSecondaryClassEnrollments($student, $validated);

            // Create Mobile Numbers
            $this->createMobileNumbers($student, $validated);

            // Clear relevant caches
            clearServerCache();

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
        $student = Student::with([
            'attendances',
            'class:id,name,class_numeral,is_active',
            'branch:id,branch_name,branch_prefix',
            'batch:id,name',
            'institution:id,name,eiin_number',
            'studentActivation:id,active_status,created_at',
            'activations.updatedBy:id,name',
            'guardians:id,name,mobile_number,gender,relationship,student_id',
            'siblings:id,name,year,class,institution_name,relationship,student_id',
            'mobileNumbers:id,mobile_number,number_type,student_id',
            'payments:id,payment_style,due_date,tuition_fee,student_id',
            'paymentInvoices'      => function ($q) {
                $q->with(['invoiceType:id,type_name', 'student.payments:id,payment_style,due_date,student_id']);
            },
            'paymentTransactions'  => function ($q) {
                $q->with(['paymentInvoice:id,invoice_number,created_at']);
            },
            'subjectsTaken.subject:id,name,academic_group,subject_type',
            'sheetsTopicTaken'     => function ($q) {
                $q->with(['sheetTopic:id,topic_name,subject_id', 'sheetTopic.subject:id,name,class_id', 'sheetTopic.subject.class:id,name,class_numeral', 'distributedBy:id,name']);
            },
            'sheetPayments.sheet'  => function ($q) {
                $q->with(['class:id,name,class_numeral', 'class.subjects:id,name,class_id']);
            },
            'reference.referer',
            'secondaryClasses.secondaryClass.class:id,name,class_numeral',
            'classChangeHistories' => function ($q) {
                $q->with([
                    'fromClass' => fn($cq) => $cq->withTrashed()->select('id', 'name', 'class_numeral'),
                    'toClass'   => fn($cq)   => $cq->withTrashed()->select('id', 'name', 'class_numeral'),
                    'createdBy:id,name',
                ]);
            },
            'secondaryClassHistories.secondaryClass.class:id,name,class_numeral',
            'secondaryClassHistories.createdBy:id,name',
        ])->find($id);

        if (! $student) {
            return redirect()->route('students.index')->with('warning', 'Student not found or deleted.');
        }

        if (auth()->user()->branch_id != 0 && auth()->user()->branch_id != $student->branch_id) {
            return redirect()->route('students.index')->with('error', 'Student not found in this branch.');
        }

        // Attendance calendar events
        $attendance_events = $student->attendances->map(function ($attendance) {
            $color = match (strtolower($attendance->status)) {
                'absent' => '#f1416c',
                'late'   => '#ffc700',
                'excused', 'leave' => '#7239ea',
                default  => '#50cd89',
            };

            return [
                'title'       => ucfirst($attendance->status),
                'start'       => $attendance->attendance_date->format('Y-m-d'),
                'description' => $attendance->remarks ?? '',
                'color'       => $color,
            ];
        });

        // Sheet sidebar info
        $sheetPayments     = $student->sheetPayments;
        $sheet_class_names = $sheetPayments->pluck('sheet.class')->filter()->unique('id')->map(
            fn($class) => [
                'name'          => $class->name,
                'class_numeral' => $class->class_numeral,
            ],
        );

        $sheet_subjectNames = $sheetPayments->pluck('sheet.class.subjects')->flatten()->unique('name')->pluck('name')->sort()->values();

        $invoice_types = PaymentInvoiceType::select('id', 'type_name')->oldest('type_name')->get();

        if ($student->class->isActive() === false) {
            return view('students.alumni.view', compact('student', 'sheet_class_names', 'sheet_subjectNames'));
        }

        return view('students.view', compact('student', 'sheet_class_names', 'sheet_subjectNames', 'attendance_events', 'invoice_types'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $student = Student::with([
            'reference.referer',
            'class' => function ($q) {
                $q->withoutGlobalScopes()->select('id', 'name', 'class_numeral', 'is_active');
            },
        ])
            ->withTrashed()
            ->find($id);

        if (! $student || $student->trashed()) {
            return redirect()->route('students.index')->with('warning', 'Student not found or deleted.');
        }

        if (auth()->user()->branch_id != 0 && auth()->user()->branch_id != $student->branch_id) {
            return redirect()->route('students.index')->with('error', 'This student is not available on this branch.');
        }

        $studentsQuery = Student::whereNotNull('student_activation_id')->latest('id');
        if (auth()->user()->branch_id != 0) {
            $studentsQuery->where('branch_id', auth()->user()->branch_id);
        }
        $students = $studentsQuery->get();

        $studentClassIsActive = optional($student->class)->is_active;

        if ($studentClassIsActive === false) {
            $classnames = ClassName::inactive()->get();
        } else {
            $classnames = ClassName::active()->get();
        }

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

        $validated = $request->validate([
            'student_name'            => 'required|string|max:255',
            'student_home_address'    => 'nullable|string|max:500',
            'student_email'           => 'nullable|email|max:255|unique:students,email,' . $student->id,
            'birth_date'              => 'nullable|date_format:d-m-Y',
            'student_gender'          => 'required|in:male,female',
            'student_religion'        => 'nullable|string|in:Islam,Hinduism,Christianity,Buddhism,Others',
            'student_blood_group'     => 'nullable|string',
            'student_class'           => $isAccountant ? 'nullable' : 'required|integer|exists:class_names,id',
            'student_academic_group'  => 'nullable|string|in:General,Science,Commerce,Arts',
            'student_batch'           => $isAccountant ? 'nullable' : 'required|integer|exists:batches,id',
            'student_institution'     => $isAccountant ? 'nullable' : 'required|integer|exists:institutions,id',
            'student_remarks'         => 'nullable|string|max:1000',
            'avatar'                  => 'nullable|image|mimes:jpg,jpeg,png|max:100',

            'subjects'                => 'required|array|min:1',
            'subjects.*.id'           => 'required|integer|exists:subjects,id',
            'subjects.*.is_4th'       => 'required|in:0,1,true,false',

            'student_phone_home'      => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'student_phone_sms'       => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'student_phone_whatsapp'  => ['nullable', 'regex:/^01[3-9]\d{8}$/'],

            'student_tuition_fee'     => $isAccountant ? 'nullable' : 'required|numeric|min:0',
            'payment_style'           => $isAccountant ? 'nullable' : 'required|in:current,due',
            'payment_due_date'        => $isAccountant ? 'nullable' : 'required|integer|in:7,10,15,30',

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
            $this->studentService->updateStudentSubjects($student, $validated['subjects']);

            // Update guardians
            $this->updateGuardians($student, $validated);

            // Update siblings
            $this->updateSiblings($student, $validated);

            // Update mobile numbers
            $this->updateMobileNumbers($student, $validated);

            // Accountant cannot update academic and payment info
            if (! $isAccountant) {
                $student->update([
                    'class_id'       => $validated['student_class'],
                    'academic_group' => $validated['student_academic_group'] ?? 'General',
                    'batch_id'       => $validated['student_batch'],
                    'institution_id' => $validated['student_institution'],
                ]);

                $student->payments()->update([
                    'payment_style' => $validated['payment_style'],
                    'due_date'      => $validated['payment_due_date'],
                    'tuition_fee'   => $validated['student_tuition_fee'],
                ]);
            }

            clearServerCache();

            return response()->json([
                'success' => true,
                'student' => $student->fresh(),
                'message' => 'Student updated successfully',
            ]);
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        $deletedBy = auth()->id();

        DB::transaction(function () use ($student, $deletedBy) {
            if (is_null($student->student_activation_id)) {
                // Pending student - PERMANENT DELETE
                $student
                    ->paymentInvoices()
                    ->withTrashed()
                    ->each(function ($invoice) {
                        $invoice->paymentTransactions()->withTrashed()->forceDelete();
                    });

                $student->paymentInvoices()->withTrashed()->forceDelete();
                $student->sheetPayments()->withTrashed()->forceDelete();
                $student->payments()->delete();
                $student->guardians()->withTrashed()->forceDelete();
                $student->siblings()->withTrashed()->forceDelete();
                $student->subjectsTaken()->delete();
                $student->forceDelete();

                return;
            }

            // Active student - SOFT DELETE
            $student->update(['deleted_by' => $deletedBy]);
            $student->delete();
        });

        clearServerCache();

        return response()->json(['success' => true]);
    }

    /**
     * Get the invoice month year for a student.
     */
    public function getInvoiceMonthsData(Student $student)
    {
        $student->load('payments');

        $tuitionInvoices = $student->paymentInvoices()->whereHas('invoiceType', function ($q) {
            $q->where('type_name', 'Tuition Fee');
        });

        $lastInvoice = (clone $tuitionInvoices)->orderByRaw("CAST(SUBSTRING_INDEX(month_year, '_', -1) AS UNSIGNED) DESC, CAST(SUBSTRING_INDEX(month_year, '_', 1) AS UNSIGNED) DESC")->first();

        $oldestInvoice = (clone $tuitionInvoices)->orderByRaw("CAST(SUBSTRING_INDEX(month_year, '_', -1) AS UNSIGNED) ASC, CAST(SUBSTRING_INDEX(month_year, '_', 1) AS UNSIGNED) ASC")->first();

        return response()->json([
            'last_invoice_month'   => optional($lastInvoice)->month_year,
            'oldest_invoice_month' => optional($oldestInvoice)->month_year,
            'tuition_fee'          => optional($student->payments)->tuition_fee,
            'payment_style'        => optional($student->payments)->payment_style,
        ]);
    }

    /**
     * Get the sheet fee for a student
     */
    public function getSheetFee($id)
    {
        $student  = Student::with('class.sheet')->findOrFail($id);
        $sheetFee = optional($student->class->sheet)->price;

        return response()->json(['sheet_fee' => $sheetFee]);
    }

    /**
     * Get secondary classes for a specific regular class
     */
    public function getSecondaryClasses($id)
    {
        $secondaryClasses = SecondaryClass::where('class_id', $id)->where('is_active', true)->select('id', 'name', 'fee_amount', 'payment_type')->get();

        return response()->json([
            'success' => true,
            'data'    => $secondaryClasses,
        ]);
    }

    /**
     * Get referred data (teachers or students)
     */
    public function getReferredData(Request $request)
    {
        $refererType = $request->get('referer_type');

        if ($refererType == 'teacher') {
            $teachers = \App\Models\Teacher::all();
            return response()->json(
                $teachers->map(
                    fn($teacher) => [
                        'id'   => $teacher->id,
                        'name' => $teacher->name,
                    ],
                ),
            );
        } elseif ($refererType == 'student') {
            $students = Student::all();
            return response()->json(
                $students->map(
                    fn($student) => [
                        'id'                => $student->id,
                        'name'              => $student->name,
                        'student_unique_id' => $student->student_unique_id,
                    ],
                ),
            );
        }

        return response()->json([]);
    }

    // =========================================================================
    // Private Helper Methods
    // =========================================================================

    /**
     * Create guardians for a student
     */
    private function createGuardians(Student $student, array $validated): void
    {
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
    }

    /**
     * Create siblings for a student
     */
    private function createSiblings(Student $student, array $validated): void
    {
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
    }

    /**
     * Create reference for a student
     */
    private function createReference(Student $student, array $validated): void
    {
        if (! empty($validated['referer_type']) && ! empty($validated['referred_by'])) {
            $reference = Reference::create([
                'referer_id'   => $validated['referred_by'],
                'referer_type' => $validated['referer_type'],
            ]);
            $student->update(['reference_id' => $reference->id]);
        }
    }

    /**
     * Create payment and invoices for a student
     */
    private function createPayment(Student $student, array $validated): void
    {
        Payment::create([
            'student_id'    => $student->id,
            'payment_style' => $validated['payment_style'],
            'due_date'      => $validated['payment_due_date'],
            'tuition_fee'   => $validated['student_tuition_fee'],
        ]);

        if ($validated['payment_style'] == 'current' && $validated['student_tuition_fee'] > 0) {
            $this->studentService->createInvoice($student, $validated['student_tuition_fee'], 'Tuition Fee', now()->format('m_Y'));
        }

        if ($validated['student_admission_fee'] > 0) {
            $this->studentService->createInvoice($student, $validated['student_admission_fee'], 'Admission Fee');
        }

        if (empty($validated['only_secondary_class'])) {
            $sheet = Sheet::where('class_id', $validated['student_class'])->first();
            if ($sheet) {
                $this->studentService->createInvoice($student, $sheet->price, 'Sheet Fee');
            }
        }
    }

    /**
     * Create secondary class enrollments for a student
     */
    private function createSecondaryClassEnrollments(Student $student, array $validated): void
    {
        if (empty($validated['secondary_classes'])) {
            return;
        }

        foreach ($validated['secondary_classes'] as $secondaryClassId) {
            $secondaryClass = SecondaryClass::find($secondaryClassId);
            if (! $secondaryClass) {
                continue;
            }

            $feeAmount = $validated['secondary_class_fees'][$secondaryClassId] ?? $secondaryClass->fee_amount;

            StudentSecondaryClass::create([
                'student_id'         => $student->id,
                'secondary_class_id' => $secondaryClassId,
                'amount'             => $feeAmount,
                'enrolled_at'        => now(),
            ]);

            if ($feeAmount > 0) {
                $monthYear = null;
                if ($secondaryClass->payment_type === 'monthly') {
                    $monthYear = now()->format('m_Y');
                }
                $this->studentService->createInvoice($student, $feeAmount, 'Special Class Fee', $monthYear, $secondaryClassId);
            }
        }
    }

    /**
     * Create mobile numbers for a student
     */
    private function createMobileNumbers(Student $student, array $validated): void
    {
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

        if (! empty($validated['student_phone_whatsapp'])) {
            MobileNumber::create([
                'student_id'    => $student->id,
                'mobile_number' => $validated['student_phone_whatsapp'],
                'number_type'   => 'whatsapp',
            ]);
        }
    }

    /**
     * Update guardians
     */
    private function updateGuardians(Student $student, array $validated): void
    {
        foreach ([1, 2] as $i) {
            $guardianId = $validated["guardian_{$i}_id"] ?? null;
            $name       = $validated["guardian_{$i}_name"] ?? null;
            $mobile     = $validated["guardian_{$i}_mobile"] ?? null;
            $gender     = $validated["guardian_{$i}_gender"] ?? null;
            $relation   = $validated["guardian_{$i}_relationship"] ?? null;

            $allFieldsEmpty = ! $name && ! $mobile && ! $gender && ! $relation;

            if (! $allFieldsEmpty && $relation) {
                $exists = $student->guardians()->where('relationship', $relation)->when($guardianId, fn($q) => $q->where('id', '!=', $guardianId))->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        "guardian_{$i}_relationship" => 'Cannot add another ' . $relation . ' type guardian.',
                    ]);
                }
            }

            if ($guardianId && ! $allFieldsEmpty) {
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
                Guardian::find($guardianId)?->delete();
            } elseif (! $guardianId && ! $allFieldsEmpty) {
                $student->guardians()->create([
                    'name'          => $name,
                    'mobile_number' => $mobile,
                    'gender'        => $gender,
                    'relationship'  => $relation,
                ]);
            }
        }
    }

    /**
     * Update siblings
     */
    private function updateSiblings(Student $student, array $validated): void
    {
        foreach ([1, 2] as $i) {
            $siblingId   = $validated["sibling_{$i}_id"] ?? null;
            $name        = $validated["sibling_{$i}_name"] ?? null;
            $year        = $validated["sibling_{$i}_year"] ?? null;
            $class       = $validated["sibling_{$i}_class"] ?? null;
            $institution = $validated["sibling_{$i}_institution"] ?? null;
            $relation    = $validated["sibling_{$i}_relationship"] ?? null;

            $allFieldsEmpty = ! $name && ! $year && ! $class && ! $institution && ! $relation;

            if ($siblingId && ! $allFieldsEmpty) {
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
                Sibling::find($siblingId)?->delete();
            } elseif (! $siblingId && ! $allFieldsEmpty) {
                $student->siblings()->create([
                    'name'             => $name,
                    'year'             => $year,
                    'class'            => $class,
                    'institution_name' => $institution,
                    'relationship'     => $relation,
                ]);
            }
        }
    }

    /**
     * Update mobile numbers
     */
    private function updateMobileNumbers(Student $student, array $validated): void
    {
        $student->mobileNumbers()->updateOrCreate(['number_type' => 'home'], ['mobile_number' => $validated['student_phone_home']]);

        $student->mobileNumbers()->updateOrCreate(['number_type' => 'sms'], ['mobile_number' => $validated['student_phone_sms']]);

        if (! empty($validated['student_phone_whatsapp'])) {
            $student->mobileNumbers()->updateOrCreate(['number_type' => 'whatsapp'], ['mobile_number' => $validated['student_phone_whatsapp']]);
        } else {
            $student->mobileNumbers()->where('number_type', 'whatsapp')->delete();
        }
    }
}
