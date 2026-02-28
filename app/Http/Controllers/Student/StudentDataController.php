<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentDataController extends Controller
{
    protected StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Get student counts for all branches (for admin tabs)
     * This endpoint is called on page load to show counts without loading full data
     */
    public function getBranchCounts(): JsonResponse
    {
        $user = auth()->user();

        // Only admin can access this endpoint
        if (! $user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get counts grouped by branch - uses same query logic as main listing
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
     * Helper method to extract guardian data from student
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
     * Helper method to extract mobile numbers from student
     */
    private function extractMobileNumbers($student): array
    {
        $mobileNumbers = $student->mobileNumbers ?? collect();

        return [
            'mobile_home' => $mobileNumbers->where('number_type', 'home')->first()?->mobile_number ?? '-',
            'mobile_sms' => $mobileNumbers->where('number_type', 'sms')->first()?->mobile_number ?? '-',
            'mobile_whatsapp' => $mobileNumbers->where('number_type', 'whatsapp')->first()?->mobile_number ?? '-',
        ];
    }

    /**
     * Helper method to extract sibling data from student
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
     * Build formatted guardian display HTML
     */
    private function buildGuardianHtml(string $name, string $relationship, string $mobile): string
    {
        if (empty($name)) {
            return '<span class="text-muted">-</span>';
        }

        $html = '<div class="d-flex flex-column">';
        $html .= '<span class="fw-bold">' . e($name) . '</span>';

        if ($relationship) {
            $html .= '<span class="text-muted fs-7">' . ucfirst($relationship) . '</span>';
        }

        if ($mobile) {
            $html .= '<span class="text-muted fs-7">' . e($mobile) . '</span>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Build formatted sibling display HTML
     */
    private function buildSiblingHtml(string $name, string $relationship, string $class, string $institution): string
    {
        if (empty($name)) {
            return '<span class="text-muted">-</span>';
        }

        $html = '<div class="d-flex flex-column">';
        $html .= '<span class="fw-bold">' . e($name) . '</span>';

        $details = [];
        if ($relationship) {
            $details[] = ucfirst($relationship);
        }
        if ($class) {
            $details[] = 'Class: ' . $class;
        }

        if (! empty($details)) {
            $html .= '<span class="text-muted fs-7">' . implode(' | ', $details) . '</span>';
        }

        if ($institution) {
            $html .= '<span class="text-gray-600 fs-8">' . e($institution) . '</span>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get students data for DataTable AJAX
     */
    public function getStudentsData(Request $request): JsonResponse
    {
        $user = auth()->user();
        $branchId = $user->branch_id;
        $isAdmin = $user->hasRole('admin');

        // Get DataTable parameters
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

        // Column mapping for ordering (19 columns)
        $columns = [
            0 => 'students.id',
            1 => 'students.name',           // Student (combined)
            2 => 'students.class_id',       // Class
            4 => 'students.batch_id',       // Batch
            5 => 'students.institution_id', // Institution
            13 => 'tuition_fee',            // Tuition Fee
            17 => 'students.created_at',    // Admission Date
        ];

        $orderColumn = $columns[$orderColumnIndex] ?? 'students.updated_at';

        // Determine effective branch filter
        $effectiveBranchId = null;
        if ($isAdmin && $filterBranchId) {
            $effectiveBranchId = $filterBranchId;
        } elseif (! $isAdmin && $branchId != 0) {
            $effectiveBranchId = $branchId;
        }

        // Build base query with JOIN instead of whereHas for better performance
        $query = Student::query()
            ->select('students.*')
            ->join('class_names', function ($join) {
                $join->on('students.class_id', '=', 'class_names.id')
                    ->where('class_names.is_active', '=', true);
            })
            ->whereNotNull('students.student_activation_id');

        // Apply branch filter early
        if ($effectiveBranchId) {
            $query->where('students.branch_id', $effectiveBranchId);
        }

        // Gender filter
        if ($filterGender) {
            $query->where('students.gender', $filterGender);
        }

        // Status filter - use JOIN instead of whereHas
        if ($filterStatus) {
            $query->join('student_activations', function ($join) use ($filterStatus) {
                $join->on('students.student_activation_id', '=', 'student_activations.id')
                    ->where('student_activations.active_status', '=', $filterStatus);
            });
        }

        // Payment filters - use JOIN instead of whereHas
        if ($filterPaymentType || $filterDueDate) {
            $query->join('payments_info', 'students.id', '=', 'payments_info.student_id');

            if ($filterPaymentType) {
                $query->where('payments_info.payment_style', $filterPaymentType);
            }

            if ($filterDueDate) {
                $query->where('payments_info.due_date', '<=', $filterDueDate);
            }
        }

        // Batch filter
        if ($filterBatchId) {
            $query->where('students.batch_id', $filterBatchId);
        }

        // Academic group filter
        if ($filterGroup) {
            $query->where('students.academic_group', $filterGroup);
        }

        // Class filter
        if ($filterClassId) {
            $query->where('students.class_id', $filterClassId);
        }

        // Institution filter - use JOIN instead of whereHas
        if ($filterInstitution) {
            $query->join('institutions', 'students.institution_id', '=', 'institutions.id')
                ->where('institutions.name', 'like', "%{$filterInstitution}%");
        }

        // Global search - optimized with direct column searches where possible
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('students.name', 'like', "%{$search}%")
                    ->orWhere('students.student_unique_id', 'like', "%{$search}%")
                    ->orWhere('class_names.name', 'like', "%{$search}%")
                    ->orWhereExists(function ($subquery) use ($search) {
                        $subquery
                            ->selectRaw('1')
                            ->from('batches')
                            ->whereColumn('batches.id', 'students.batch_id')
                            ->where('batches.name', 'like', "%{$search}%");
                    })
                    ->orWhereExists(function ($subquery) use ($search) {
                        $subquery
                            ->selectRaw('1')
                            ->from('institutions')
                            ->whereColumn('institutions.id', 'students.institution_id')
                            ->where('institutions.name', 'like', "%{$search}%");
                    })
                    ->orWhereExists(function ($subquery) use ($search) {
                        $subquery
                            ->selectRaw('1')
                            ->from('mobile_numbers')
                            ->whereColumn('mobile_numbers.student_id', 'students.id')
                            ->where('mobile_numbers.mobile_number', 'like', "%{$search}%");
                    })
                    ->orWhereExists(function ($subquery) use ($search) {
                        $subquery
                            ->selectRaw('1')
                            ->from('guardians')
                            ->whereColumn('guardians.student_id', 'students.id')
                            ->where(function ($gq) use ($search) {
                                $gq->where('guardians.name', 'like', "%{$search}%")
                                    ->orWhere('guardians.mobile_number', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // Get total count for this branch (cached calculation)
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

        // Get filtered count
        $filteredRecords = $query->count('students.id');

        // Apply ordering
        if ($orderColumn === 'students.updated_at') {
            $query->latest('students.updated_at');
        } else {
            $query->orderBy($orderColumn, $orderDir);
        }

        // Apply pagination
        if ($length > 0) {
            $query->skip($start)->take($length);
        }

        // Eager load relationships for the final result set only
        $students = $query->with([
            'class:id,name,class_numeral',
            'branch:id,branch_name,branch_prefix',
            'batch:id,name',
            'institution:id,name',
            'studentActivation:id,active_status',
            'mobileNumbers:id,mobile_number,number_type,student_id',
            'guardians:id,name,mobile_number,relationship,student_id',
            'siblings:id,name,relationship,class,institution_name,student_id',
            'payments:id,payment_style,due_date,tuition_fee,student_id',
            'createdBy:id,name',
        ])->get();

        // Format data for DataTable
        $data = [];
        $counter = $start + 1;

        foreach ($students as $student) {
            $isActive = optional($student->studentActivation)->active_status === 'active';

            // Extract mobile numbers
            $mobileData = $this->extractMobileNumbers($student);

            // Extract guardian data
            $guardianData = $this->extractGuardianData($student);

            // Extract sibling data
            $siblingData = $this->extractSiblingData($student);

            // Payment info
            $tuitionFee = optional($student->payments)->tuition_fee ?? '';
            $paymentInfo = $this->studentService->getPaymentInfo($student->payments);

            // Academic group badge
            $groupBadge = $this->studentService->buildGroupBadge($student->academic_group);

            // Activation status badge
            $statusBadge = $isActive
                ? '<span class="badge badge-light-success">Active</span>'
                : '<span class="badge badge-light-danger">Inactive</span>';

            // Build actions dropdown
            $actions = $this->buildActionsDropdown($student, $isActive);

            // Build guardian HTML
            $guardian1Html = $this->buildGuardianHtml(
                $guardianData['guardian_1_name'],
                $guardianData['guardian_1_relationship'],
                $guardianData['guardian_1_mobile']
            );

            $guardian2Html = $this->buildGuardianHtml(
                $guardianData['guardian_2_name'],
                $guardianData['guardian_2_relationship'],
                $guardianData['guardian_2_mobile']
            );

            // Build sibling HTML
            $sibling1Html = $this->buildSiblingHtml(
                $siblingData['sibling_1_name'],
                $siblingData['sibling_1_relationship'],
                $siblingData['sibling_1_class'],
                $siblingData['sibling_1_institution']
            );

            $sibling2Html = $this->buildSiblingHtml(
                $siblingData['sibling_2_name'],
                $siblingData['sibling_2_relationship'],
                $siblingData['sibling_2_class'],
                $siblingData['sibling_2_institution']
            );

            // Admission date and admitted by
            $admissionDate = $student->created_at ? $student->created_at->format('d-m-Y') : '-';
            $admissionDateTime = $student->created_at ? $student->created_at->format('h:i A') : '';
            $admittedBy = optional($student->createdBy)->name ?? '-';

            $data[] = [
                'DT_RowId' => 'row_' . $student->id,

                // Basic info
                'counter' => $counter++,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_unique_id' => $student->student_unique_id,

                // Academic info
                'class_id' => $student->class_id,
                'class_name' => optional($student->class)->name ?? '-',
                'academic_group' => $student->academic_group,
                'group_badge' => $groupBadge,
                'batch_name' => optional($student->batch)->name ?? '-',
                'institution_name' => optional($student->institution)->name ?? '-',

                // Mobile numbers
                'mobile_home' => $mobileData['mobile_home'],
                'mobile_sms' => $mobileData['mobile_sms'],
                'mobile_whatsapp' => $mobileData['mobile_whatsapp'],

                // Guardian info (for display)
                'guardian_1' => $guardian1Html,
                'guardian_2' => $guardian2Html,

                // Guardian info (raw data for export)
                'guardian_1_name' => $guardianData['guardian_1_name'],
                'guardian_1_mobile' => $guardianData['guardian_1_mobile'],
                'guardian_1_relationship' => $guardianData['guardian_1_relationship'],
                'guardian_2_name' => $guardianData['guardian_2_name'],
                'guardian_2_mobile' => $guardianData['guardian_2_mobile'],
                'guardian_2_relationship' => $guardianData['guardian_2_relationship'],

                // Sibling info (for display)
                'sibling_1' => $sibling1Html,
                'sibling_2' => $sibling2Html,

                // Sibling info (raw data for export)
                'sibling_1_name' => $siblingData['sibling_1_name'],
                'sibling_1_relationship' => $siblingData['sibling_1_relationship'],
                'sibling_1_class' => $siblingData['sibling_1_class'],
                'sibling_1_institution' => $siblingData['sibling_1_institution'],
                'sibling_2_name' => $siblingData['sibling_2_name'],
                'sibling_2_relationship' => $siblingData['sibling_2_relationship'],
                'sibling_2_class' => $siblingData['sibling_2_class'],
                'sibling_2_institution' => $siblingData['sibling_2_institution'],

                // Payment info
                'tuition_fee' => $tuitionFee,
                'payment_info' => $paymentInfo,

                // Status
                'is_active' => $isActive,
                'activation_status' => $isActive ? 'Active' : 'Inactive',
                'status_badge' => $statusBadge,

                // Admission info
                'admission_date' => $admissionDate,
                'admission_date_time' => $admissionDateTime,
                'admitted_by' => $admittedBy,

                // Actions
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
     * Export students data - bypasses pagination
     * Similar to PaymentInvoiceController@exportInvoicesAjax
     */
    public function exportStudentsData(Request $request): JsonResponse
    {
        $user = auth()->user();
        $branchId = $user->branch_id;
        $isAdmin = $user->hasRole('admin');

        // Custom filters
        $filterBranchId = $request->input('branch_id');
        $search = $request->input('search', '');

        // Determine effective branch filter
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

        // Apply branch filter
        if ($effectiveBranchId) {
            $query->where('students.branch_id', $effectiveBranchId);
        }

        // Apply filters from request
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

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('students.name', 'like', "%{$search}%")
                    ->orWhere('students.student_unique_id', 'like', "%{$search}%");
            });
        }

        // Order by student_unique_id DESC
        $query->orderBy('students.student_unique_id', 'desc');

        // Eager load relationships
        $students = $query->with([
            'class:id,name,class_numeral',
            'batch:id,name',
            'institution:id,name',
            'studentActivation:id,active_status',
            'mobileNumbers:id,mobile_number,number_type,student_id',
            'guardians:id,name,mobile_number,relationship,student_id',
            'siblings:id,name,relationship,class,institution_name,student_id',
            'payments:id,payment_style,due_date,tuition_fee,student_id',
            'createdBy:id,name',
        ])->get();

        // Format data for export
        $data = $students->map(function ($student, $index) {
            $isActive = optional($student->studentActivation)->active_status === 'active';

            // Extract data
            $mobileData = $this->extractMobileNumbers($student);
            $guardianData = $this->extractGuardianData($student);
            $siblingData = $this->extractSiblingData($student);

            $payment = $student->payments;
            $paymentType = '-';
            if ($payment) {
                $paymentType = ucfirst($payment->payment_style) . ' - 1/' . $payment->due_date;
            }

            // Build guardian export strings (following PaymentInvoiceController pattern)
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
                if ($siblingData['sibling_1_institution']) {
                    $sibling1Export .= ' - ' . $siblingData['sibling_1_institution'];
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
                if ($siblingData['sibling_2_institution']) {
                    $sibling2Export .= ' - ' . $siblingData['sibling_2_institution'];
                }
            }

            // Admission info
            $admissionDate = $student->created_at ? $student->created_at->format('d-m-Y h:i A') : '-';
            $admittedBy = optional($student->createdBy)->name ?? '-';

            return [
                'sl' => $index + 1,
                'student_name' => $student->name,
                'student_unique_id' => $student->student_unique_id,
                'class_name' => optional($student->class)->name ?? '-',
                'academic_group' => $student->academic_group ?? '-',
                'batch_name' => optional($student->batch)->name ?? '-',
                'institution_name' => optional($student->institution)->name ?? '-',
                'mobile_home' => $mobileData['mobile_home'],
                'mobile_sms' => $mobileData['mobile_sms'],
                'mobile_whatsapp' => $mobileData['mobile_whatsapp'],
                'guardian_1' => $guardian1Export ?: '-',
                'guardian_2' => $guardian2Export ?: '-',
                'sibling_1' => $sibling1Export ?: '-',
                'sibling_2' => $sibling2Export ?: '-',
                'tuition_fee' => optional($payment)->tuition_fee ?? '-',
                'payment_type' => $paymentType,
                'activation_status' => $isActive ? 'Active' : 'Inactive',
                'admission_date' => $admissionDate,
                'admitted_by' => $admittedBy,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Build actions dropdown HTML for a student
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
