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
                $join->on('students.class_id', '=', 'class_names.id')->where('class_names.is_active', '=', true);
            })
            ->whereNotNull('students.student_activation_id')
            ->groupBy('students.branch_id')
            ->pluck('count', 'students.branch_id');

        return response()->json([
            'success' => true,
            'counts'  => $counts,
        ]);
    }

    /**
     * Get students data for DataTable AJAX
     */
    public function getStudentsData(Request $request): JsonResponse
    {
        $user     = auth()->user();
        $branchId = $user->branch_id;
        $isAdmin  = $user->hasRole('admin');

        // Check if this is an export request (fetch all data)
        $isExport = $request->boolean('export', false);

        // Get DataTable parameters
        $draw             = $request->input('draw', 1);
        $start            = $request->input('start', 0);
        $length           = $isExport ? -1 : $request->input('length', 10);
        $search           = $request->input('search.value', '');
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir         = $request->input('order.0.dir', 'desc');

        // Custom filters
        $filterBranchId    = $request->input('branch_id');
        $filterGender      = $request->input('gender');
        $filterStatus      = $request->input('status');
        $filterPaymentType = $request->input('payment_type');
        $filterDueDate     = $request->input('due_date');
        $filterBatchId     = $request->input('batch_id');
        $filterGroup       = $request->input('academic_group');
        $filterClassId     = $request->input('class_id');
        $filterInstitution = $request->input('institution');

        // Column mapping for ordering (updated indices after removing hidden columns)
        $columns = [
            0 => 'students.id',
            1 => 'students.name',
            2 => 'students.class_id',
            4 => 'students.batch_id',
            5 => 'students.institution_id',
            7 => 'students.tuition_fee',
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
                $join->on('students.class_id', '=', 'class_names.id')->where('class_names.is_active', '=', true);
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
                $join->on('students.student_activation_id', '=', 'student_activations.id')->where('student_activations.active_status', '=', $filterStatus);
            });
        }

        // Payment filters - use JOIN instead of whereHas
        if ($filterPaymentType || $filterDueDate) {
            $query->join('payments_info', 'students.id', '=', 'payments_info.student_id');

            if ($filterPaymentType) {
                $query->where('payments_info.payment_style', $filterPaymentType);
            }

            if ($filterDueDate) {
                $query->where('payments_info.due_date', $filterDueDate);
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
            $query->join('institutions', 'students.institution_id', '=', 'institutions.id')->where('institutions.name', 'like', "%{$filterInstitution}%");
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
                    });
            });
        }

        // Get total count for this branch (cached calculation)
        $totalQuery = Student::query()
            ->join('class_names', function ($join) {
                $join->on('students.class_id', '=', 'class_names.id')->where('class_names.is_active', '=', true);
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
        $students = $query->with(['class:id,name,class_numeral', 'branch:id,branch_name,branch_prefix', 'batch:id,name', 'institution:id,name', 'studentActivation:id,active_status', 'mobileNumbers:id,mobile_number,number_type,student_id', 'payments:id,payment_style,due_date,tuition_fee,student_id'])->get();

        // Format data for DataTable
        $data    = [];
        $counter = $start + 1;

        foreach ($students as $student) {
            $isActive = optional($student->studentActivation)->active_status === 'active';

            // Get home mobile number
            $homeMobile       = $student->mobileNumbers->where('number_type', 'home')->first();
            $homeMobileNumber = $homeMobile ? $homeMobile->mobile_number : '-';

            // Payment info
            $tuitionFee  = optional($student->payments)->tuition_fee ?? '';
            $paymentInfo = $this->studentService->getPaymentInfo($student->payments);

            // Academic group badge
            $groupBadge = $this->studentService->buildGroupBadge($student->academic_group);

            // Build actions dropdown
            $actions = $this->buildActionsDropdown($student, $isActive);

            $data[] = [
                'DT_RowId'         => 'row_' . $student->id,
                'checkbox'         => $student->id,
                'counter'          => $counter++,
                'student'          => [
                    'id'                => $student->id,
                    'name'              => $student->name,
                    'student_unique_id' => $student->student_unique_id,
                    'is_active'         => $isActive,
                ],
                'class_id'         => $student->class_id,
                'class_name'       => optional($student->class)->name ?? '-',
                'group_badge'      => $groupBadge,
                'batch_name'       => optional($student->batch)->name ?? '-',
                'institution_name' => optional($student->institution)->name ?? '-',
                'home_mobile'      => $homeMobileNumber,
                'tuition_fee'      => $tuitionFee,
                'payment_info'     => $paymentInfo,
                'actions'          => $actions,
            ];
        }

        return response()->json([
            'draw'            => intval($draw),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /**
     * Build actions dropdown HTML for a student
     */
    private function buildActionsDropdown(Student $student, bool $isActive): string
    {
        $user            = auth()->user();
        $canDeactivate   = $user->can('students.deactivate');
        $canDownloadForm = $user->can('students.form.download');
        $canEdit         = $user->can('students.edit');

        $html  = '<a href="#" class="btn btn-light btn-active-light-primary btn-sm" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions <i class="ki-outline ki-down fs-5 m-0"></i></a>';
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