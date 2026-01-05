<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Academic\Institution;
use App\Models\Academic\Subject;
use App\Models\Branch;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentInvoiceType;
use App\Models\Sheet\Sheet;
use App\Models\Student\Guardian;
use App\Models\Student\MobileNumber;
use App\Models\Student\Reference;
use App\Models\Student\Sibling;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user     = auth()->user();
        $branchId = $user->branch_id;
        $isAdmin  = $user->hasRole('admin');

        // Get all branches for admin
        $branches = Branch::all();

        if ($isAdmin) {
            // For admin: Get students grouped by branch
            $studentsByBranch = [];

            foreach ($branches as $branch) {
                $cacheKey = 'students_list_branch_' . $branch->id;

                $studentsByBranch[$branch->id] = Cache::remember($cacheKey, now()->addHours(1), function () use ($branch) {
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
                        ->where('branch_id', $branch->id)
                        ->whereHas('class', function ($query) {
                            $query->where('is_active', true);
                        })
                        ->latest('updated_at')
                        ->get();
                });
            }

            $students    = collect(); // Empty collection for admin (uses tabs)
            $allStudents = collect(); // No longer needed
        } else {
            // For non-admin: Get only their branch students
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

            $studentsByBranch = [];
            $allStudents      = collect();
        }

        $classnames = ClassName::active()->get();

        $batches = Batch::with('branch:id,branch_name')
            ->when($branchId != 0, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->select('id', 'name', 'branch_id')
            ->get();

        $institutions = Institution::all();

        return view('students.index', compact(
            'students',
            'studentsByBranch',
            'allStudents',
            'classnames',
            'batches',
            'institutions',
            'branches',
            'isAdmin'
        ));
    }

    public function pending()
    {
        $branchId = auth()->user()->branch_id;

        $students = Student::with(['class:id,name,class_numeral', 'branch:id,branch_name,branch_prefix', 'batch:id,name', 'institution:id,name,eiin_number', 'guardians:id,name,relationship,student_id', 'mobileNumbers:id,mobile_number,number_type,student_id', 'payments:id,payment_style,due_date,tuition_fee,student_id'])
            ->whereNull('student_activation_id')
            ->when($branchId != 0, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->latest()
            ->get();

        $classnames = ClassName::where('is_active', true)->get();

        $batches = Batch::with('branch:id,branch_name')
            ->when(auth()->user()->branch_id != 0, function ($query) {
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
        $classnames = ClassName::active()->latest('class_numeral')->get();

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
    public function store(Request $request): JsonResponse
    {
        // ========================================
        // FIX: Filter out incomplete subject entries before validation
        // This handles cases where hidden is_4th field is sent without checkbox id
        // ========================================
        if ($request->has('subjects')) {
            $subjects         = $request->input('subjects', []);
            $filteredSubjects = array_filter($subjects, function ($subject) {
                return ! empty($subject['id']);
            });
            // Re-index array to avoid gaps in keys
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
            'student_academic_group'  => 'nullable|string|in:General,Science,Commerce,Arts',
            'student_branch'          => 'required|integer|exists:branches,id',
            'student_batch'           => 'required|integer|exists:batches,id',
            'student_institution'     => 'required|integer|exists:institutions,id',
            'student_remarks'         => 'nullable|string|max:1000',
            'avatar'                  => 'nullable|image|mimes:jpg,jpeg,png|max:100',

            // UPDATED: Made subjects nullable to handle flexible selection
            'subjects'                => 'nullable|array',
            'subjects.*.id'           => 'required|integer|exists:subjects,id',
            'subjects.*.is_4th'       => 'required|in:0,1',

            // Keep these for backward compatibility (optional now)
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
        ]);

        // ========================================
        // FIX: Validate at least one subject is selected (any type)
        // ========================================
        $subjectsCount = count($validated['subjects'] ?? []);

        if ($subjectsCount === 0) {
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

        // Validate optional subjects (only checks Main ≠ 4th)
        $optionalValidation = $this->validateOptionalSubjects($validated, $classNumeral, $group);
        if ($optionalValidation !== true) {
            return response()->json(
                [
                    'success' => false,
                    'errors'  => [$optionalValidation],
                ],
                422,
            );
        }

        return DB::transaction(function () use ($validated, $class, $classNumeral, $group) {
            $branch = Branch::findOrFail($validated['student_branch']);

            // Generate student_unique_id with proper logic
            $studentUniqueId = $this->generateStudentUniqueId($branch, $class);

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

            // Store subjects with flexible logic - FIXED VERSION
            $this->storeStudentSubjects($student, $validated);

            // Guardians
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

            // Siblings
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

            // Reference
            if (! empty($validated['referer_type']) && ! empty($validated['referred_by'])) {
                $reference = Reference::create([
                    'referer_id'   => $validated['referred_by'],
                    'referer_type' => $validated['referer_type'],
                ]);
                $student->update(['reference_id' => $reference->id]);
            }

            // Payment
            Payment::create([
                'student_id'    => $student->id,
                'payment_style' => $validated['payment_style'],
                'due_date'      => $validated['payment_due_date'],
                'tuition_fee'   => $validated['student_tuition_fee'],
            ]);

            if ($validated['payment_style'] == 'current') {
                $this->createPaymentInvoice($student, $validated['student_tuition_fee']);
            }

            // Create Admission Fee Invoice
            if ($validated['student_admission_fee'] > 0) {
                $this->createInvoice($student, $validated['student_admission_fee'], 'Admission Fee');
            }

            // Create Sheet Fee Invoice
            $sheet = Sheet::where('class_id', $validated['student_class'])->first();
            if ($sheet && $sheet->price > 0) {
                $this->createInvoice($student, $sheet->price, 'Sheet Fee');
            }

            // Mobile numbers
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

            // Clear relevant caches
            clearUCMSCaches();

            return response()->json([
                'success' => true,
                'student' => $student,
                'message' => 'Student created successfully',
            ]);
        });
    }

    /**
     * Validate optional subjects based on class numeral and group
     *
     * NEW FLEXIBLE VALIDATION:
     * - Optional subjects are truly optional (not required)
     * - Only validates that Main ≠ 4th when BOTH are selected
     *
     * @param array $validated
     * @param int $classNumeral
     * @param string $group
     * @return bool|string
     */
    private function validateOptionalSubjects(array $validated, int $classNumeral, string $group): bool | string
    {
        // Only validate for classes 9-12
        if ($classNumeral < 9 || $classNumeral > 12) {
            return true;
        }

        // Check if this class/group has optional subjects
        $hasOptionalSubjects = Subject::where('class_id', $validated['student_class'])->where('academic_group', $group)->where('subject_type', 'optional')->exists();

        if (! $hasOptionalSubjects) {
            return true;
        }

        // FIXED: Check for duplicate main and 4th subjects from the subjects array
        $subjects         = $validated['subjects'] ?? [];
        $fourthSubjectIds = [];
        $mainSubjectIds   = [];

        foreach ($subjects as $subject) {
            $is4th = $this->isFourthSubjectValue($subject['is_4th'] ?? '0');
            if ($is4th) {
                $fourthSubjectIds[] = $subject['id'];
            } else {
                $mainSubjectIds[] = $subject['id'];
            }
        }

        // Check if any 4th subject is also selected as main
        foreach ($fourthSubjectIds as $fourthId) {
            if (in_array($fourthId, $mainSubjectIds)) {
                return 'A subject cannot be both main and 4th subject. Please select different subjects.';
            }
        }

        return true;
    }

    /**
     * Store student subjects - FIXED VERSION
     * Now properly reads the is_4th flag from each subject in the array
     *
     * @param Student $student
     * @param array $validated
     */
    private function storeStudentSubjects(Student $student, array $validated): void
    {
        $subjects = $validated['subjects'] ?? [];

        if (empty($subjects)) {
            return;
        }

        // Track 4th subject count for validation
        $fourthSubjectCount = 0;

        foreach ($subjects as $subjectData) {
            $subjectId = $subjectData['id'];
            $is4th     = $this->isFourthSubjectValue($subjectData['is_4th'] ?? '0');

            if ($is4th) {
                $fourthSubjectCount++;
            }

            // Validate: Only one 4th subject allowed
            if ($fourthSubjectCount > 1) {
                throw ValidationException::withMessages([
                    'subjects' => 'Only one subject can be marked as 4th subject.',
                ]);
            }

            $student->subjectsTaken()->create([
                'subject_id'     => $subjectId,
                'is_4th_subject' => $is4th,
            ]);
        }
    }

    /**
     * Helper to determine if a value represents a 4th subject
     * Handles various input formats: '1', '0', 1, 0, true, false, 'true', 'false'
     *
     * @param mixed $value
     * @return bool
     */
    private function isFourthSubjectValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return $value === '1' || strtolower($value) === 'true';
        }

        return false;
    }

    /**
     * Generate a unique student ID
     *
     * Format: <branch_prefix>-<year><class_numeral><sequential_number>
     *
     * Rules:
     * 1. For class 11-12 (HSC): Academic year runs July-June
     *    - Admitted Jan-June: Use current year (e.g., 26 for 2026)
     *    - Admitted July-Dec: Use next year (e.g., 27 for admissions in July 2026)
     * 2. For class 1-10: Use current calendar year
     * 3. Sequential number is shared across ALL classes with same class_numeral in same branch
     *    (e.g., 'HSC Sci' and 'HSC Com' both with class_numeral=11 share the sequence)
     *
     * @param Branch $branch
     * @param ClassName $class
     * @return string
     */
    private function generateStudentUniqueId(Branch $branch, ClassName $class): string
    {
        $classNumeral = $class->class_numeral;
        $currentMonth = Carbon::now()->month;
        $currentYear  = Carbon::now()->format('y');

        // ========================================
        // FIX 1: HSC Year Logic (July-June academic year)
        // For class 11-12, if admitted from July onwards, use next year
        // ========================================
        if ($classNumeral >= 11 && $classNumeral <= 12) {
            // HSC academic year: July to June
            // July (7) onwards = next academic year
            $year = $currentMonth >= 7 ? Carbon::now()->addYear()->format('y') : $currentYear;
        } else {
            // For class 1-10, use current calendar year
            $year = $currentYear;
        }

        // Build the ID pattern prefix
        $pattern = "{$branch->branch_prefix}-{$year}{$classNumeral}";

        // ========================================
        // FIX 2: Search across ALL classes with same class_numeral
        // This prevents duplicate IDs when multiple classes share the same numeral
        // (e.g., 'HSC Sci' and 'HSC Com' both with class_numeral=11)
        // ========================================
        $maxStudent = Student::where('student_unique_id', 'like', "{$pattern}%")
            ->orderByRaw('CAST(SUBSTRING(student_unique_id, -2) AS UNSIGNED) DESC')
            ->first();

        // Calculate next sequential number
        $nextSequence = 1;
        if ($maxStudent) {
            $lastTwoDigits = substr($maxStudent->student_unique_id, -2);
            $nextSequence  = (int) $lastTwoDigits + 1;
        }

        // Cap at 99 (maximum 2-digit sequence)
        $nextSequence = min($nextSequence, 99);

        // Generate the unique ID
        $studentUniqueId = $pattern . str_pad($nextSequence, 2, '0', STR_PAD_LEFT);

        // ========================================
        // Safety check: Ensure uniqueness (in case of edge cases)
        // ========================================
        while (Student::where('student_unique_id', $studentUniqueId)->exists()) {
            $nextSequence++;
            if ($nextSequence > 99) {
                throw new \Exception("Maximum student limit (99) reached for pattern: {$pattern}");
            }
            $studentUniqueId = $pattern . str_pad($nextSequence, 2, '0', STR_PAD_LEFT);
        }

        return $studentUniqueId;
    }

    private function createPaymentInvoice(Student $student, float $tuitionFee): void
    {
        $yearSuffix = now()->format('y');
        $month      = now()->format('m');
        $prefix     = $student->branch->branch_prefix;
        $monthYear  = now()->format('m_Y');

        $lastInvoice = PaymentInvoice::where('invoice_number', 'like', "{$prefix}{$yearSuffix}{$month}_%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        $nextSequence = $lastInvoice ? (int) substr($lastInvoice->invoice_number, strrpos($lastInvoice->invoice_number, '_') + 1) + 1 : 1001;

        $invoiceNumber = "{$prefix}{$yearSuffix}{$month}_{$nextSequence}";

        $invoice_type = PaymentInvoiceType::where('type_name', 'Tuition Fee')->first();

        PaymentInvoice::create([
            'invoice_number'  => $invoiceNumber,
            'student_id'      => $student->id,
            'total_amount'    => $tuitionFee,
            'amount_due'      => $tuitionFee,
            'month_year'      => $monthYear,
            'invoice_type_id' => $invoice_type->id,
        ]);
    }

    /**
     * Create a payment invoice for a student
     *
     * @param Student $student
     * @param float $amount
     * @param string $typeName - e.g., 'Admission Fee', 'Sheet Fee'
     * @return void
     */
    private function createInvoice(Student $student, float $amount, string $typeName): void
    {
        $yearSuffix = now()->format('y');
        $month      = now()->format('m');
        $prefix     = $student->branch->branch_prefix;

        $lastInvoice = PaymentInvoice::where('invoice_number', 'like', "{$prefix}{$yearSuffix}{$month}_%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        $nextSequence = $lastInvoice ? (int) substr($lastInvoice->invoice_number, strrpos($lastInvoice->invoice_number, '_') + 1) + 1 : 1001;

        $invoiceNumber = "{$prefix}{$yearSuffix}{$month}_{$nextSequence}";

        $invoiceType = PaymentInvoiceType::where('type_name', $typeName)->first();

        if (! $invoiceType) {
            \Log::warning("Invoice type '{$typeName}' not found for student {$student->id}");
            return;
        }

        PaymentInvoice::create([
            'invoice_number'  => $invoiceNumber,
            'student_id'      => $student->id,
            'total_amount'    => $amount,
            'amount_due'      => $amount,
            'month_year'      => null,
            'invoice_type_id' => $invoiceType->id,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Try to find the student including trashed ones
        $student = Student::withTrashed()
            ->with([
                // 'attendances' added to eager loading
                'attendances',
                'class' => function ($q) {
                    $q->withoutGlobalScope('active')->select('id', 'name', 'class_numeral', 'is_active');
                },
            ])
            ->find($id);

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
        $sheet_subjectNames = $sheetPayments->pluck('sheet.class.subjects')->flatten()->unique('name')->pluck('name')->sort()->values();

        $invoice_types = PaymentInvoiceType::select('id', 'type_name')->oldest('type_name')->get();

        if ($student->class->isActive() === false) {
            return view('students.alumni.view', compact('student', 'sheet_class_names', 'sheet_subjectNames'));
        } else {
            return view('students.view', compact('student', 'sheet_class_names', 'sheet_subjectNames', 'attendance_events', 'invoice_types'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Eager-load class WITHOUT global scopes
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

        // Access guard for branch
        if (auth()->user()->branch_id != 0 && auth()->user()->branch_id != $student->branch_id) {
            return redirect()->route('students.index')->with('error', 'This student is not available on this branch.');
        }

        // Fetch students for sidebar/list
        $studentsQuery = Student::whereNotNull('student_activation_id')->latest('id');
        if (auth()->user()->branch_id != 0) {
            $studentsQuery->where('branch_id', auth()->user()->branch_id);
        }
        $students = $studentsQuery->get();

        // Load class names safely: bypass global scopes o both statuses are available
        // If student has a class, prefer loading same-status list; else load active by default
        $studentClassIsActive = optional($student->class)->is_active;

        if ($studentClassIsActive === true) {
            $classnames = ClassName::withoutGlobalScopes()->where('is_active', true)->get();
        } elseif ($studentClassIsActive === false) {
            $classnames = ClassName::withoutGlobalScopes()->where('is_active', false)->get();
        } else {
            // student has no class assigned, return active classes by default
            $classnames = ClassName::withoutGlobalScopes()->where('is_active', true)->get();
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

        // Validate request data
        $validated = $request->validate([
            // Student Table Fields
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

            // Subjects - New format: array of {id, is_4th}
            'subjects'                => 'required|array|min:1',
            'subjects.*.id'           => 'required|integer|exists:subjects,id',
            'subjects.*.is_4th'       => 'required|in:0,1,true,false',

            // Mobile Numbers Table Fields
            'student_phone_home'      => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'student_phone_sms'       => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'student_phone_whatsapp'  => ['nullable', 'regex:/^01[3-9]\d{8}$/'],

            // Payment Table Fields
            'student_tuition_fee'     => $isAccountant ? 'nullable' : 'required|numeric|min:0',
            'payment_style'           => $isAccountant ? 'nullable' : 'required|in:current,due',
            'payment_due_date'        => $isAccountant ? 'nullable' : 'required|integer|in:7,10,15,30',

            // Guardians Table Fields
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

            // Siblings Table Fields
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

            // Update subjects using the new format
            $this->updateStudentSubjects($student, $validated['subjects']);

            // Update guardians
            $this->updateGuardians($student, $validated);

            // Update siblings
            $this->updateSiblings($student, $validated);

            // Update mobile numbers
            $this->updateMobileNumbers($student, $validated);

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

            return response()->json([
                'success' => true,
                'student' => $student->fresh(),
                'message' => 'Student updated successfully',
            ]);
        });
    }

    /**
     * Update student subjects with proper is_4th_subject handling
     */
    private function updateStudentSubjects(Student $student, array $subjects): void
    {
        // Validate that there's at most one 4th subject
        $fourthSubjects = collect($subjects)->filter(function ($subject) {
            return $this->isFourthSubjectValue($subject['is_4th']);
        });

        if ($fourthSubjects->count() > 1) {
            throw ValidationException::withMessages([
                'subjects' => 'Only one subject can be marked as 4th subject.',
            ]);
        }

        // Get the 4th subject ID if exists
        $fourthSubjectId = $fourthSubjects->first()['id'] ?? null;

        // Check for duplicate main optional and 4th subject
        if ($fourthSubjectId) {
            $mainSubjects = collect($subjects)
                ->filter(function ($subject) {
                    return ! $this->isFourthSubjectValue($subject['is_4th']);
                })
                ->pluck('id')
                ->toArray();

            if (in_array($fourthSubjectId, $mainSubjects)) {
                throw ValidationException::withMessages([
                    'subjects' => 'A subject cannot be both main and 4th subject.',
                ]);
            }
        }

        // Delete existing subjects
        $student->subjectsTaken()->delete();

        // Create new subject records
        foreach ($subjects as $subject) {
            $student->subjectsTaken()->create([
                'subject_id'     => $subject['id'],
                'is_4th_subject' => $this->isFourthSubjectValue($subject['is_4th']),
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
                // Check if the same relationship is already assigned to another guardian
                $exists = $student->guardians()->where('relationship', $relation)->when($guardianId, fn($q) => $q->where('id', '!=', $guardianId))->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        "guardian_{$i}_relationship" => 'Cannot add another ' . $relation . ' type guardian.',
                    ]);
                }
            }

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
            // Remove WhatsApp number if cleared
            $student->mobileNumbers()->where('number_type', 'whatsapp')->delete();
        }
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
        // Eager load the payments relationship
        $student->load('payments');

        $tuitionInvoices = $student->paymentInvoices()->whereHas('invoiceType', function ($q) {
            $q->where('type_name', 'Tuition Fee');
        });

        $lastInvoice = (clone $tuitionInvoices)->orderByRaw("CAST(SUBSTRING_INDEX(month_year, '_', -1) AS UNSIGNED) DESC, CAST(SUBSTRING_INDEX(month_year, '_', 1) AS UNSIGNED) DESC")->first();

        $oldestInvoice = (clone $tuitionInvoices)->orderByRaw("CAST(SUBSTRING_INDEX(month_year, '_', -1) AS UNSIGNED) ASC, CAST(SUBSTRING_INDEX(month_year, '_', 1) AS UNSIGNED) ASC")->first();

        return response()->json([
            'last_invoice_month'   => optional($lastInvoice)->month_year,
            'oldest_invoice_month' => optional($oldestInvoice)->month_year,
            'tuition_fee'          => optional($student->payments)->tuition_fee,   // Changed from payment to payments
            'payment_style'        => optional($student->payments)->payment_style, // Changed from payment to payments
        ]);
    }

    /* Get the sheet fee for a student */
    public function getSheetFee($id)
    {
        $student  = Student::with('class.sheet')->findOrFail($id);
        $sheetFee = optional($student->class->sheet)->price;

        return response()->json(['sheet_fee' => $sheetFee]);
    }

    /* Old Student - Alumni */
/**
 * Display alumni students (old students from inactive classes)
 */
    public function alumniStudent()
    {
        $user     = auth()->user();
        $branchId = $user->branch_id;
        $isAdmin  = $user->hasRole('admin');

        // Get all branches for admin
        $branches = Branch::all();

        if ($isAdmin) {
            // For admin: Get alumni students grouped by branch
            $studentsByBranch = [];

            foreach ($branches as $branch) {
                $cacheKey = 'alumni_students_list_branch_' . $branch->id;

                $studentsByBranch[$branch->id] = Cache::remember($cacheKey, now()->addHours(1), function () use ($branch) {
                    return Student::with([
                        'class' => function ($q) {
                            $q->withoutGlobalScope('active')->select('id', 'name', 'class_numeral');
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
                        ->where('branch_id', $branch->id)
                        ->whereHas('class', function ($q) {
                            $q->withoutGlobalScope('active')->where('is_active', false);
                        })
                        ->latest('updated_at')
                        ->get();
                });
            }

            $students = collect(); // Empty collection for admin (uses tabs)
        } else {
            // For non-admin: Get only their branch alumni students
            $cacheKey = 'alumni_students_list_branch_' . $branchId;

            $students = Cache::remember($cacheKey, now()->addHours(1), function () use ($branchId) {
                return Student::with([
                    'class' => function ($q) {
                        $q->withoutGlobalScope('active')->select('id', 'name', 'class_numeral');
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
                        $q->withoutGlobalScope('active')->where('is_active', false);
                    })
                    ->latest('updated_at')
                    ->get();
            });

            $studentsByBranch = [];
        }

        $classnames = ClassName::withoutGlobalScope('active')->where('is_active', false)->get();

        $batches = Batch::with('branch:id,branch_name')
            ->when($branchId != 0, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->select('id', 'name', 'branch_id')
            ->get();

        $institutions = Institution::all();

        return view('students.alumni.index', compact(
            'students',
            'studentsByBranch',
            'classnames',
            'batches',
            'institutions',
            'branches',
            'isAdmin'
        ));
    }
}
