<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ColumnSetting;
use App\Models\Student\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentDataController extends Controller
{
    protected StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Get student counts for all branches (for admin tabs)
     */
    public function getBranchCounts(): JsonResponse
    {
        $user = auth()->user();

        if (! $user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $counts = Student::query()
            ->select('students.branch_id', DB::raw('COUNT(students.id) as count'))
            ->join('class_names', function ($join) {
                $join->on('students.class_id', '=', 'class_names.id')
                    ->where('class_names.is_active', '=', true);
            })
            ->whereNotNull('students.student_activation_id')
            ->groupBy('students.branch_id')
            ->pluck('count', 'students.branch_id');

        return response()->json([
            'success' => true,
            'counts' => $counts,
        ]);
    }

    /**
     * Get students data for DataTable AJAX
     */
    public function getStudentsData(Request $request): JsonResponse
    {
        $user = auth()->user();
        $branchId = $user->branch_id;
        $isAdmin = $user->hasRole('admin');

        $draw = $request->input('draw', 1);
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $search = $request->input('search.value', '');
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');

        // Custom filters
        $filterBranchId = $request->input('branch_id');
        $filterGender = $request->input('gender');
        $filterStatus = $request->input('status');
        $filterPaymentType = $request->input('payment_type');
        $filterDueDate = $request->input('due_date');
        $filterBatchId = $request->input('batch_id');
        $filterGroup = $request->input('academic_group');
        $filterClassId = $request->input('class_id');
        $filterInstitution = $request->input('institution');

        // Column mapping
        $columns = [
            0 => 'students.id',
            1 => 'students.name',
            2 => 'students.class_id',
            4 => 'students.batch_id',
            5 => 'students.institution_id',
            13 => 'tuition_fee',
        ];
        $orderColumn = $columns[$orderColumnIndex] ?? 'students.updated_at';

        // Effective branch filter
        $effectiveBranchId = null;
        if ($isAdmin && $filterBranchId) {
            $effectiveBranchId = $filterBranchId;
        } elseif (! $isAdmin && $branchId != 0) {
            $effectiveBranchId = $branchId;
        }

        // Build query
        $query = Student::query()
            ->select('students.*')
            ->join('class_names', function ($join) {
                $join->on('students.class_id', '=', 'class_names.id')
                    ->where('class_names.is_active', '=', true);
            })
            ->whereNotNull('students.student_activation_id');

        if ($effectiveBranchId) {
            $query->where('students.branch_id', $effectiveBranchId);
        }

        // Apply filters
        if ($filterGender) {
            $query->where('students.gender', $filterGender);
        }

        if ($filterStatus) {
            $query->join('student_activations', function ($join) use ($filterStatus) {
                $join->on('students.student_activation_id', '=', 'student_activations.id')
                    ->where('student_activations.active_status', '=', $filterStatus);
            });
        }

        if ($filterPaymentType || $filterDueDate) {
            $query->join('payments_info', 'students.id', '=', 'payments_info.student_id');
            if ($filterPaymentType) {
                $query->where('payments_info.payment_style', $filterPaymentType);
            }
            if ($filterDueDate) {
                $query->where('payments_info.due_date', '<=', $filterDueDate);
            }
        }

        if ($filterBatchId) {
            $query->where('students.batch_id', $filterBatchId);
        }

        if ($filterGroup) {
            $query->where('students.academic_group', $filterGroup);
        }

        if ($filterClassId) {
            $query->where('students.class_id', $filterClassId);
        }

        if ($filterInstitution) {
            $query->join('institutions', 'students.institution_id', '=', 'institutions.id')
                ->where('institutions.name', 'like', "%{$filterInstitution}%");
        }

        // Global search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('students.name', 'like', "%{$search}%")
                    ->orWhere('students.student_unique_id', 'like', "%{$search}%")
                    ->orWhere('class_names.name', 'like', "%{$search}%")
                    ->orWhereExists(function ($subquery) use ($search) {
                        $subquery->selectRaw('1')
                            ->from('batches')
                            ->whereColumn('batches.id', 'students.batch_id')
                            ->where('batches.name', 'like', "%{$search}%");
                    })
                    ->orWhereExists(function ($subquery) use ($search) {
                        $subquery->selectRaw('1')
                            ->from('institutions')
                            ->whereColumn('institutions.id', 'students.institution_id')
                            ->where('institutions.name', 'like', "%{$search}%");
                    })
                    ->orWhereExists(function ($subquery) use ($search) {
                        $subquery->selectRaw('1')
                            ->from('mobile_numbers')
                            ->whereColumn('mobile_numbers.student_id', 'students.id')
                            ->where('mobile_numbers.mobile_number', 'like', "%{$search}%");
                    })
                    ->orWhereExists(function ($subquery) use ($search) {
                        $subquery->selectRaw('1')
                            ->from('guardians')
                            ->whereColumn('guardians.student_id', 'students.id')
                            ->where(function ($gq) use ($search) {
                                $gq->where('guardians.name', 'like', "%{$search}%")
                                    ->orWhere('guardians.mobile_number', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // Total count
        $totalQuery = Student::query()
            ->join('class_names', function ($join) {
                $join->on('students.class_id', '=', 'class_names.id')
                    ->where('class_names.is_active', '=', true);
            })
            ->whereNotNull('students.student_activation_id');

        if ($effectiveBranchId) {
            $totalQuery->where('students.branch_id', $effectiveBranchId);
        }

        $totalRecords = $totalQuery->count();
        $filteredRecords = $query->count('students.id');

        // Ordering
        if ($orderColumn === 'students.updated_at') {
            $query->latest('students.updated_at');
        } else {
            $query->orderBy($orderColumn, $orderDir);
        }

        // Pagination
        $query->skip($start)->take($length);

        // Eager load
        $students = $query->with([
            'class:id,name,class_numeral',
            'branch:id,branch_name,branch_prefix',
            'batch:id,name',
            'institution:id,name',
            'studentActivation:id,active_status',
            'mobileNumbers:id,mobile_number,number_type,student_id',
            'payments:id,payment_style,due_date,tuition_fee,student_id',
            'guardians:id,name,mobile_number,relationship,student_id',
            'siblings:id,name,relationship,class,institution_name,student_id',
            'createdBy:id,name',
        ])->get();

        // Format data
        $data = [];
        $counter = $start + 1;

        foreach ($students as $student) {
            $isActive = optional($student->studentActivation)->active_status === 'active';
            $mobileNumbers = $this->extractMobileNumbers($student);
            $guardianData = $this->extractGuardianData($student);
            $siblingData = $this->extractSiblingData($student);

            // Payment info
            $payment = $student->payments;
            $paymentStyle = optional($payment)->payment_style;
            $dueDate = optional($payment)->due_date;
            $tuitionFee = optional($payment)->tuition_fee ?? '';

            $paymentType = '';
            if ($paymentStyle && $dueDate) {
                $paymentType = ucfirst($paymentStyle) . ' - 1/' . $dueDate;
            }

            // Group badge
            $groupBadge = $this->studentService->buildGroupBadge($student->academic_group);

            // Actions
            $actions = $this->buildActionsDropdown($student, $isActive);

            $data[] = [
                'DT_RowId' => 'row_' . $student->id,
                'counter' => $counter++,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_unique_id' => $student->student_unique_id,
                'is_active' => $isActive,
                'class_id' => $student->class_id,
                'class_name' => optional($student->class)->name ?? '-',
                'academic_group' => $student->academic_group ?? '-',
                'group_badge' => $groupBadge,
                'batch_name' => optional($student->batch)->name ?? '-',
                'institution_name' => optional($student->institution)->name ?? '-',
                'mobile_home' => $mobileNumbers['home'],
                'mobile_sms' => $mobileNumbers['sms'],
                'mobile_whatsapp' => $mobileNumbers['whatsapp'],
                'guardian_1_name' => $guardianData['guardian_1_name'],
                'guardian_1_mobile' => $guardianData['guardian_1_mobile'],
                'guardian_1_relationship' => $guardianData['guardian_1_relationship'],
                'guardian_2_name' => $guardianData['guardian_2_name'],
                'guardian_2_mobile' => $guardianData['guardian_2_mobile'],
                'guardian_2_relationship' => $guardianData['guardian_2_relationship'],
                'sibling_1_name' => $siblingData['sibling_1_name'],
                'sibling_1_relationship' => $siblingData['sibling_1_relationship'],
                'sibling_1_class' => $siblingData['sibling_1_class'],
                'sibling_1_institution' => $siblingData['sibling_1_institution'],
                'sibling_2_name' => $siblingData['sibling_2_name'],
                'sibling_2_relationship' => $siblingData['sibling_2_relationship'],
                'sibling_2_class' => $siblingData['sibling_2_class'],
                'sibling_2_institution' => $siblingData['sibling_2_institution'],
                'tuition_fee' => $tuitionFee,
                'payment_type' => $paymentType,
                'activation_status' => $isActive ? 'active' : 'inactive',
                'admission_date' => $student->created_at ? $student->created_at->format('d-m-Y') : '-',
                'admitted_by' => optional($student->createdBy)->name ?? '-',
                'actions' => $actions,
            ];
        }

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Export students data (bypasses pagination)
     */
    public function exportStudentsData(Request $request): JsonResponse
    {
        $user = auth()->user();
        $branchId = $user->branch_id;
        $isAdmin = $user->hasRole('admin');

        $filterBranchId = $request->input('branch_id');

        $effectiveBranchId = null;
        if ($isAdmin && $filterBranchId) {
            $effectiveBranchId = $filterBranchId;
        } elseif (! $isAdmin && $branchId != 0) {
            $effectiveBranchId = $branchId;
        }

        $query = Student::query()
            ->select('students.*')
            ->join('class_names', function ($join) {
                $join->on('students.class_id', '=', 'class_names.id')
                    ->where('class_names.is_active', '=', true);
            })
            ->whereNotNull('students.student_activation_id');

        if ($effectiveBranchId) {
            $query->where('students.branch_id', $effectiveBranchId);
        }

        // Apply filters
        if ($request->filled('gender')) {
            $query->where('students.gender', $request->input('gender'));
        }

        if ($request->filled('status')) {
            $query->join('student_activations', function ($join) use ($request) {
                $join->on('students.student_activation_id', '=', 'student_activations.id')
                    ->where('student_activations.active_status', '=', $request->input('status'));
            });
        }

        if ($request->filled('batch_id')) {
            $query->where('students.batch_id', $request->input('batch_id'));
        }

        if ($request->filled('academic_group')) {
            $query->where('students.academic_group', $request->input('academic_group'));
        }

        if ($request->filled('class_id')) {
            $query->where('students.class_id', $request->input('class_id'));
        }

        $query->latest('students.updated_at');

        $students = $query->with([
            'class:id,name,class_numeral',
            'batch:id,name',
            'institution:id,name',
            'studentActivation:id,active_status',
            'mobileNumbers:id,mobile_number,number_type,student_id',
            'payments:id,payment_style,due_date,tuition_fee,student_id',
            'guardians:id,name,mobile_number,relationship,student_id',
            'siblings:id,name,relationship,class,institution_name,student_id',
            'createdBy:id,name',
        ])->get();

        $data = [];

        foreach ($students as $student) {
            $isActive = optional($student->studentActivation)->active_status === 'active';
            $mobileNumbers = $this->extractMobileNumbers($student);
            $guardianData = $this->extractGuardianData($student);
            $siblingData = $this->extractSiblingData($student);

            $payment = $student->payments;
            $paymentStyle = optional($payment)->payment_style;
            $dueDate = optional($payment)->due_date;
            $tuitionFee = optional($payment)->tuition_fee ?? '';

            $paymentType = '';
            if ($paymentStyle && $dueDate) {
                $paymentType = ucfirst($paymentStyle) . ' - 1/' . $dueDate;
            }

            // Build guardian export strings
            $guardian1Export = '';
            if ($guardianData['guardian_1_name']) {
                $guardian1Export = $guardianData['guardian_1_name'];
                if ($guardianData['guardian_1_relationship']) {
                    $guardian1Export .= ' (' . ucfirst($guardianData['guardian_1_relationship']) . ')';
                }
                if ($guardianData['guardian_1_mobile']) {
                    $guardian1Export .= ' - ' . $guardianData['guardian_1_mobile'];
                }
            }

            $guardian2Export = '';
            if ($guardianData['guardian_2_name']) {
                $guardian2Export = $guardianData['guardian_2_name'];
                if ($guardianData['guardian_2_relationship']) {
                    $guardian2Export .= ' (' . ucfirst($guardianData['guardian_2_relationship']) . ')';
                }
                if ($guardianData['guardian_2_mobile']) {
                    $guardian2Export .= ' - ' . $guardianData['guardian_2_mobile'];
                }
            }

            // Build sibling export strings
            $sibling1Export = '';
            if ($siblingData['sibling_1_name']) {
                $sibling1Export = $siblingData['sibling_1_name'];
                if ($siblingData['sibling_1_relationship']) {
                    $sibling1Export .= ' (' . ucfirst($siblingData['sibling_1_relationship']) . ')';
                }
                if ($siblingData['sibling_1_class']) {
                    $sibling1Export .= ' - Class: ' . $siblingData['sibling_1_class'];
                }
            }

            $sibling2Export = '';
            if ($siblingData['sibling_2_name']) {
                $sibling2Export = $siblingData['sibling_2_name'];
                if ($siblingData['sibling_2_relationship']) {
                    $sibling2Export .= ' (' . ucfirst($siblingData['sibling_2_relationship']) . ')';
                }
                if ($siblingData['sibling_2_class']) {
                    $sibling2Export .= ' - Class: ' . $siblingData['sibling_2_class'];
                }
            }

            $data[] = [
                'student_name' => $student->name,
                'student_unique_id' => $student->student_unique_id,
                'class_name' => optional($student->class)->name ?? '-',
                'academic_group' => $student->academic_group ?? '-',
                'batch_name' => optional($student->batch)->name ?? '-',
                'institution_name' => optional($student->institution)->name ?? '-',
                'mobile_home' => $mobileNumbers['home'],
                'mobile_sms' => $mobileNumbers['sms'],
                'mobile_whatsapp' => $mobileNumbers['whatsapp'],
                'guardian_1' => $guardian1Export ?: '-',
                'guardian_2' => $guardian2Export ?: '-',
                'sibling_1' => $sibling1Export ?: '-',
                'sibling_2' => $sibling2Export ?: '-',
                'tuition_fee' => $tuitionFee ?: '-',
                'payment_type' => $paymentType ?: '-',
                'activation_status' => $isActive ? 'Active' : 'Inactive',
                'admission_date' => $student->created_at ? $student->created_at->format('d-m-Y') : '-',
                'admitted_by' => optional($student->createdBy)->name ?? '-',
            ];
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Get column settings
     */
    public function getColumnSettings(): JsonResponse
    {
        $settings = ColumnSetting::getForPage('students_index');

        if (! $settings) {
            // Return default settings
            $settings = $this->getDefaultColumnSettings();
        }

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    /**
     * Save column settings
     */
    public function saveColumnSettings(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (! $user->hasRole('admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $visibility = $request->input('visibility');

        // Handle JSON string
        if (is_string($visibility)) {
            $visibility = json_decode($visibility, true);
        }

        if (! is_array($visibility)) {
            Log::error('Invalid visibility data', ['data' => $request->all()]);
            return response()->json(['success' => false, 'message' => 'Invalid data format'], 422);
        }

        // Clean and validate
        $cleanSettings = [];
        for ($i = 0; $i < 19; $i++) {
            $cleanSettings[$i] = isset($visibility[$i]) ? (bool) $visibility[$i] : true;
        }

        // Ensure required columns are visible
        $cleanSettings[0] = true;  // counter
        $cleanSettings[1] = true;  // student
        $cleanSettings[18] = true; // actions

        ColumnSetting::saveForPage('students_index', $cleanSettings, $user->id);

        Log::info('Column settings saved', ['settings' => $cleanSettings, 'user' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'Column settings saved for all users',
        ]);
    }

    /**
     * Get default column settings
     */
    private function getDefaultColumnSettings(): array
    {
        return [
            0 => true,   // counter
            1 => true,   // student
            2 => true,   // class
            3 => true,   // group
            4 => true,   // batch
            5 => true,   // institution
            6 => true,   // mobile_home
            7 => false,  // mobile_sms
            8 => false,  // mobile_whatsapp
            9 => false,  // guardian_1
            10 => false, // guardian_2
            11 => false, // sibling_1
            12 => false, // sibling_2
            13 => true,  // tuition_fee
            14 => true,  // payment_type
            15 => false, // status
            16 => false, // admission_date
            17 => false, // admitted_by
            18 => true,  // actions
        ];
    }

    /**
     * Extract mobile numbers from student
     */
    private function extractMobileNumbers($student): array
    {
        $mobileNumbers = $student->mobileNumbers ?? collect();

        return [
            'home' => $mobileNumbers->where('number_type', 'home')->first()?->mobile_number ?? '-',
            'sms' => $mobileNumbers->where('number_type', 'sms')->first()?->mobile_number ?? '-',
            'whatsapp' => $mobileNumbers->where('number_type', 'whatsapp')->first()?->mobile_number ?? '-',
        ];
    }

    /**
     * Extract guardian data from student
     */
    private function extractGuardianData($student): array
    {
        $guardians = $student->guardians ?? collect();
        $guardian1 = $guardians->first();
        $guardian2 = $guardians->skip(1)->first();

        return [
            'guardian_1_name' => $guardian1?->name ?? '',
            'guardian_1_mobile' => $guardian1?->mobile_number ?? '',
            'guardian_1_relationship' => $guardian1?->relationship ?? '',
            'guardian_2_name' => $guardian2?->name ?? '',
            'guardian_2_mobile' => $guardian2?->mobile_number ?? '',
            'guardian_2_relationship' => $guardian2?->relationship ?? '',
        ];
    }

    /**
     * Extract sibling data from student
     */
    private function extractSiblingData($student): array
    {
        $siblings = $student->siblings ?? collect();
        $sibling1 = $siblings->first();
        $sibling2 = $siblings->skip(1)->first();

        return [
            'sibling_1_name' => $sibling1?->name ?? '',
            'sibling_1_relationship' => $sibling1?->relationship ?? '',
            'sibling_1_class' => $sibling1?->class ?? '',
            'sibling_1_institution' => $sibling1?->institution_name ?? '',
            'sibling_2_name' => $sibling2?->name ?? '',
            'sibling_2_relationship' => $sibling2?->relationship ?? '',
            'sibling_2_class' => $sibling2?->class ?? '',
            'sibling_2_institution' => $sibling2?->institution_name ?? '',
        ];
    }

    /**
     * Build actions dropdown HTML
     */
    private function buildActionsDropdown(Student $student, bool $isActive): string
    {
        $user = auth()->user();
        $canDeactivate = $user->can('students.deactivate');
        $canDownloadForm = $user->can('students.form.download');
        $canEdit = $user->can('students.edit');

        $html = '<a href="#" class="btn btn-light btn-active-light-primary btn-sm" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions <i class="ki-outline ki-down fs-5 m-0"></i></a>';
        $html .= '<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-175px py-4" data-kt-menu="true">';

        if ($canDeactivate) {
            $html .= '<div class="menu-item px-3">';
            if ($isActive) {
                $html .= '<a href="#" class="menu-link text-hover-warning px-3" data-bs-toggle="modal" data-bs-target="#kt_toggle_activation_student_modal" data-student-unique-id="' . $student->student_unique_id . '" data-student-name="' . htmlspecialchars($student->name) . '" data-student-id="' . $student->id . '" data-active-status="active"><i class="bi bi-person-slash fs-2 me-2"></i> Deactivate</a>';
            } else {
                $html .= '<a href="#" class="menu-link text-hover-success px-3" data-bs-toggle="modal" data-bs-target="#kt_toggle_activation_student_modal" data-student-unique-id="' . $student->student_unique_id . '" data-student-name="' . htmlspecialchars($student->name) . '" data-student-id="' . $student->id . '" data-active-status="inactive"><i class="bi bi-person-check fs-3 me-2"></i> Activate</a>';
            }
            $html .= '</div>';
        }

        if ($canDownloadForm && $isActive) {
            $html .= '<div class="menu-item px-3">';
            $html .= '<a href="' . route('students.download', $student->id) . '" class="menu-link text-hover-primary px-3" target="_blank"><i class="bi bi-download fs-3 me-2"></i> Download</a>';
            $html .= '</div>';
        }

        if ($canEdit) {
            $html .= '<div class="menu-item px-3">';
            $html .= '<a href="' . route('students.edit', $student->id) . '" class="menu-link text-hover-primary px-3"><i class="ki-outline ki-pencil fs-3 me-2"></i> Edit Student</a>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}
