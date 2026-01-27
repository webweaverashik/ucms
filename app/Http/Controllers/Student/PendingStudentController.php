<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Academic\Institution;
use App\Models\Branch;
use App\Models\Student\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PendingStudentController extends Controller
{
    protected StudentService $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Display pending students
     */
    public function index()
    {
        $user     = auth()->user();
        $branchId = $user->branch_id;
        $isAdmin  = $user->isAdmin();

        $classnames = ClassName::active()->get();
        $batches    = Batch::with('branch:id,branch_name')
            ->when(! $isAdmin, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->select('id', 'name', 'branch_id')
            ->get();

        $institutions = Institution::all();
        $branches     = Branch::all();

        return view('students.pending.index', compact('classnames', 'batches', 'institutions', 'branches', 'isAdmin'));
    }

    /**
     * Get pending student counts for all branches (for admin tabs)
     */
    public function getBranchCounts(): JsonResponse
    {
        $user = auth()->user();

        // Only admin can access this endpoint
        if (! $user->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get counts grouped by branch for pending students
        $counts = Student::query()
            ->select('students.branch_id', DB::raw('COUNT(students.id) as count'))
            ->join('class_names', function ($join) {
                $join->on('students.class_id', '=', 'class_names.id')->where('class_names.is_active', '=', true);
            })
            ->whereNull('students.student_activation_id')
            ->groupBy('students.branch_id')
            ->pluck('count', 'students.branch_id');

        return response()->json([
            'success' => true,
            'counts'  => $counts,
        ]);
    }

    /**
     * Get pending students data for DataTable AJAX
     */
    public function getData(Request $request): JsonResponse
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
        $filterPaymentType = $request->input('payment_type');
        $filterDueDate     = $request->input('due_date');
        $filterBatchId     = $request->input('batch_id');
        $filterGroup       = $request->input('academic_group');
        $filterClassId     = $request->input('class_id');
        $filterInstitution = $request->input('institution');

        // Column mapping for ordering
        $columns = [
            0  => 'students.id',
            1  => 'students.name',
            4  => 'students.class_id',
            8  => 'students.batch_id',
            9  => 'students.institution_id',
            11 => 'payments_info.tuition_fee',
            13 => 'students.created_at',
        ];

        $orderColumn = $columns[$orderColumnIndex] ?? 'students.created_at';

        // Determine effective branch filter
        $effectiveBranchId = null;
        if ($isAdmin && $filterBranchId) {
            $effectiveBranchId = $filterBranchId;
        } elseif (! $isAdmin && $branchId != 0) {
            $effectiveBranchId = $branchId;
        }

        // Build base query - PENDING students (student_activation_id IS NULL)
        $query = Student::query()
            ->select('students.*')
            ->join('class_names', function ($join) {
                $join->on('students.class_id', '=', 'class_names.id')->where('class_names.is_active', '=', true);
            })
            ->whereNull('students.student_activation_id');

        // Apply branch filter early
        if ($effectiveBranchId) {
            $query->where('students.branch_id', $effectiveBranchId);
        }

        // Gender filter
        if ($filterGender) {
            $query->where('students.gender', $filterGender);
        }

        // Payment filters - use LEFT JOIN to handle students without payment records
        if ($filterPaymentType || $filterDueDate) {
            $query->leftJoin('payments_info', 'students.id', '=', 'payments_info.student_id');

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

        // Institution filter
        if ($filterInstitution) {
            $query->whereExists(function ($subquery) use ($filterInstitution) {
                $subquery
                    ->selectRaw('1')
                    ->from('institutions')
                    ->whereColumn('institutions.id', 'students.institution_id')
                    ->where('institutions.name', 'like', "%{$filterInstitution}%");
            });
        }

        // Global search
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

        // Get total count for this branch
        $totalQuery = Student::query()
            ->join('class_names', function ($join) {
                $join->on('students.class_id', '=', 'class_names.id')->where('class_names.is_active', '=', true);
            })
            ->whereNull('students.student_activation_id');

        if ($effectiveBranchId) {
            $totalQuery->where('students.branch_id', $effectiveBranchId);
        }

        $totalRecords = $totalQuery->count();

        // Get filtered count
        $filteredRecords = $query->count('students.id');

        // Apply ordering
        if ($orderColumn === 'students.created_at') {
            $query->latest('students.created_at');
        } else {
            $query->orderBy($orderColumn, $orderDir);
        }

        // Apply pagination
        if ($length > 0) {
            $query->skip($start)->take($length);
        }

        // Eager load relationships
        $students = $query->with(['class:id,name,class_numeral', 'branch:id,branch_name,branch_prefix', 'batch:id,name', 'institution:id,name,eiin_number', 'mobileNumbers:id,mobile_number,number_type,student_id', 'payments:id,payment_style,due_date,tuition_fee,student_id'])->get();

        // Format data for DataTable
        $data    = [];
        $counter = $start + 1;

        // Permission checks
        $canApprove = $user->can('students.approve');
        $canEdit    = $user->can('students.edit');
        $canDelete  = $user->can('students.delete');

        foreach ($students as $student) {
            // Get home mobile number
            $homeMobile       = $student->mobileNumbers->where('number_type', 'home')->first();
            $homeMobileNumber = $homeMobile ? $homeMobile->mobile_number : '-';

            // Payment info
            $tuitionFee   = optional($student->payments)->tuition_fee ?? '';
            $paymentStyle = optional($student->payments)->payment_style ?? '';
            $dueDate      = optional($student->payments)->due_date ?? '';
            $paymentInfo  = $this->studentService->getPaymentInfo($student->payments);

            // Academic group badge
            $groupBadge = $this->studentService->buildGroupBadge($student->academic_group);

            // Check if student is eligible for approval (no due tuition fee invoices)
            $hasDueInvoice = $student
                ->paymentInvoices()
                ->whereIn('status', ['due', 'partially_paid'])
                ->whereHas('invoiceType', function ($q) {
                    $q->where('type_name', 'Tuition Fee');
                })
                ->exists();
            $isEligibleForApproval = ! $hasDueInvoice;

            // Build actions dropdown
            $actions = $this->buildActionsDropdown($student, $canApprove, $canEdit, $canDelete);

            // Student info with photo
            $photoUrl = $student->photo_url ? asset($student->photo_url) : asset($student->gender == 'male' ? 'img/boy.png' : 'img/girl.png');

            $data[] = [
                'DT_RowId'         => 'row_' . $student->id,
                'counter'          => $counter++,
                'student'          => [
                    'id'                       => $student->id,
                    'name'                     => $student->name,
                    'student_unique_id'        => $student->student_unique_id,
                    'photo_url'                => $photoUrl,
                    'show_url'                 => route('students.show', $student->id),
                    'is_eligible_for_approval' => $isEligibleForApproval,
                ],
                'gender'           => $student->gender,
                'class_id'         => $student->class_id,
                'class_numeral'    => optional($student->class)->class_numeral,
                'class_name'       => optional($student->class)->name ?? '-',
                'academic_group'   => $student->academic_group,
                'group_badge'      => $groupBadge,
                'batch_id'         => $student->batch_id,
                'batch_name'       => optional($student->batch)->name ?? '-',
                'branch_id'        => $student->branch_id,
                'branch_name'      => optional($student->branch)->branch_name ?? '-',
                'institution_name' => optional($student->institution)->name ?? '-',
                'institution_eiin' => optional($student->institution)->eiin_number ?? '',
                'home_mobile'      => $homeMobileNumber,
                'tuition_fee'      => $tuitionFee,
                'payment_style'    => $paymentStyle,
                'due_date'         => $dueDate,
                'payment_info'     => $paymentInfo,
                'admission_date'   => $student->created_at->format('d-M-Y'),
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
     * Build actions dropdown HTML for a pending student
     */
    private function buildActionsDropdown(Student $student, bool $canApprove, bool $canEdit, bool $canDelete): string
    {
        if (! $canApprove && ! $canEdit && ! $canDelete) {
            return '<span class="text-muted">-</span>';
        }

        $html  = '<a href="#" class="btn btn-light btn-active-light-primary btn-sm" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions <i class="ki-outline ki-down fs-5 m-0"></i></a>';
        $html .= '<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-175px py-4" data-kt-menu="true">';

        if ($canApprove) {
            $html .= '<div class="menu-item px-3">';
            $html .= '<a href="#" class="menu-link px-3 text-hover-success approve-student" data-student-id="' . $student->id . '" data-student-name="' . htmlspecialchars($student->name) . '" data-student-unique-id="' . $student->student_unique_id . '">';
            $html .= '<i class="bi bi-person-check fs-3 me-2"></i> Approve Student</a>';
            $html .= '</div>';
        }

        if ($canEdit) {
            $html .= '<div class="menu-item px-3">';
            $html .= '<a href="' . route('students.edit', $student->id) . '" class="menu-link text-hover-primary px-3"><i class="ki-outline ki-pencil fs-3 me-2"></i> Edit Student</a>';
            $html .= '</div>';
        }

        if ($canDelete) {
            $html .= '<div class="menu-item px-3">';
            $html .= '<a href="#" class="menu-link text-hover-danger px-3 delete-student" data-student-id="' . $student->id . '"><i class="bi bi-trash fs-3 me-2"></i> Delete</a>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}