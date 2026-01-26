<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Academic\Institution;
use App\Models\Academic\SecondaryClass;
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
use App\Models\Student\StudentSecondaryClass;
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

        // Get all branches for admin - minimal query
        $branches = Branch::select('id', 'branch_name', 'branch_prefix')->get();

        // DON'T load student counts here - they will be loaded via AJAX when DataTable initializes
        // This makes the page load instantly

        // Use simple queries without eager loading for filter dropdowns
        $classnames = ClassName::where('is_active', true)->select('id', 'name', 'class_numeral')->get();

        $batches = Batch::select('batches.id', 'batches.name', 'batches.branch_id', 'branches.branch_name')
            ->join('branches', 'batches.branch_id', '=', 'branches.id')
            ->when($branchId != 0, function ($query) use ($branchId) {
                $query->where('batches.branch_id', $branchId);
            })
            ->get();

        $institutions = Institution::select('id', 'name')->get();

        return view('students.index', compact('classnames', 'batches', 'institutions', 'branches', 'isAdmin'));
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
            $tuitionFee   = optional($student->payments)->tuition_fee ?? '';
            $paymentStyle = optional($student->payments)->payment_style ?? '';
            $dueDate      = optional($student->payments)->due_date ?? '';
            $paymentInfo  = $paymentStyle ? ucfirst($paymentStyle) . '-1/' . $dueDate : '';

            // Academic group badge
            $groupBadge = '';
            if ($student->academic_group && $student->academic_group !== 'General') {
                $badgeClass = [
                    'Science'  => 'info',
                    'Commerce' => 'primary',
                    'Arts'     => 'warning',
                ][$student->academic_group] ?? 'secondary';
                $groupBadge = '<span class="badge badge-pill badge-' . $badgeClass . '">' . $student->academic_group . '</span>';
            } else {
                $groupBadge = '<span class="text-muted">-</span>';
            }

            // Build actions dropdown
            $actions = $this->buildActionsDropdown($student, $isActive);

            $data[] = [
                'DT_RowId'         => 'row_' . $student->id,
                'checkbox'         => $student->id, // For checkbox column
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
        $canDelete       = $user->can('students.delete');

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

    /**
     * Display pending students
     */
    public function pending()
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
    public function getPendingBranchCounts(): JsonResponse
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
    public function getPendingStudentsData(Request $request): JsonResponse
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
        $students = $query->with([
            'class:id,name,class_numeral',
            'branch:id,branch_name,branch_prefix',
            'batch:id,name',
            'institution:id,name,eiin_number',
            'mobileNumbers:id,mobile_number,number_type,student_id',
            'payments:id,payment_style,due_date,tuition_fee,student_id',
        ])->get();

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
            $paymentInfo  = $paymentStyle ? ucfirst($paymentStyle) . '-1/' . $dueDate : '';

            // Academic group badge
            $groupBadge = '';
            if ($student->academic_group && $student->academic_group !== 'General') {
                $badgeClass = [
                    'Science'  => 'info',
                    'Commerce' => 'primary',
                    'Arts'     => 'warning',
                ][$student->academic_group] ?? 'secondary';
                $groupBadge = '<span class="badge badge-pill badge-' . $badgeClass . '">' . $student->academic_group . '</span>';
            } else {
                $groupBadge = '<span class="text-muted">-</span>';
            }

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
            $actions = $this->buildPendingActionsDropdown($student, $canApprove, $canEdit, $canDelete);

            // Student info with photo
            $photoUrl = $student->photo_url
                ? asset($student->photo_url)
                : asset($student->gender == 'male' ? 'img/boy.png' : 'img/girl.png');

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
    private function buildPendingActionsDropdown(Student $student, bool $canApprove, bool $canEdit, bool $canDelete): string
    {
        if (! $canApprove && ! $canEdit && ! $canDelete) {
            return '<span class="text-muted">-</span>';
        }

        $html  = '<a href="#" class="btn btn-light btn-active-light-primary btn-sm" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions <i class="ki-outline ki-down fs-5 m-0"></i></a>';
        $html .= '<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-175px py-4" data-kt-menu="true">';

        if ($canApprove) {
            $html .= '<div class="menu-item px-3">';
            $html .= '<a href="#" class="menu-link px-3 text-hover-success approve-student" data-student-id="' . $student->id . '" data-student-name="' . htmlspecialchars($student->name) . '" data-student-unique-id="' . $student->student_unique_id . '">';
            $html .= '<i class="bi bi-person-check fs-3 me-2"></i> Approve</a>';
            $html .= '</div>';
        }

        if ($canEdit) {
            $html .= '<div class="menu-item px-3">';
            $html .= '<a href="' . route('students.edit', $student->id) . '" class="menu-link text-hover-primary px-3"><i class="las la-pen fs-3 me-2"></i> Edit</a>';
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

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $classnames   = ClassName::active()->latest('class_numeral')->get();
        $institutions = Institution::select('id', 'name', 'eiin_number')->get();
        $batches      = Batch::when(auth()->user()->branch_id != 0, function ($query) {
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
            'student_academic_group'  => 'string|in:General,Science,Commerce,Arts',
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

            // Secondary Classes Validation
            'secondary_classes'       => 'nullable|array',
            'secondary_classes.*'     => 'exists:secondary_classes,id',
            'secondary_class_fees'    => 'nullable|array',
            'secondary_class_fees.*'  => 'nullable|numeric|min:0',
            'only_secondary_class'    => 'nullable',
        ]);

        // ========================================
        // FIX: Validate at least one subject is selected (any type)
        // ========================================
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

            // Store subjects with flexible logic - FIXED VERSION
            if (empty($validated['only_secondary_class'])) {
                $this->storeStudentSubjects($student, $validated);
            }

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

            if ($validated['payment_style'] == 'current' && $validated['student_tuition_fee'] > 0) {
                $this->createInvoice($student, $validated['student_tuition_fee'], 'Tuition Fee', now()->format('m_Y'));
            }

            // Create Admission Fee Invoice
            if ($validated['student_admission_fee'] > 0) {
                $this->createInvoice($student, $validated['student_admission_fee'], 'Admission Fee');
            }

            // Create Sheet Fee Invoice
            if (empty($validated['only_secondary_class'])) {
                $sheet = Sheet::where('class_id', $validated['student_class'])->first();
                if ($sheet) {
                    $this->createInvoice($student, $sheet->price, 'Sheet Fee');
                }
            }

            // ---------------------------------------
            // Handle Secondary Classes Enrollment
            // ---------------------------------------
            if (! empty($validated['secondary_classes'])) {
                foreach ($validated['secondary_classes'] as $secondaryClassId) {
                    $secondaryClass = SecondaryClass::find($secondaryClassId);
                    if (! $secondaryClass) {
                        continue;
                    }

                    /**
                     * Determine fee:
                     * 1. Form-provided fee
                     * 2. SecondaryClass default fee
                     */
                    $feeAmount = $validated['secondary_class_fees'][$secondaryClassId] ?? $secondaryClass->fee_amount;

                    /**
                     * Create ACTIVE enrollment
                     * Observer will automatically log:
                     * → action = enrolled
                     */
                    StudentSecondaryClass::create([
                        'student_id'         => $student->id,
                        'secondary_class_id' => $secondaryClassId,
                        'amount'             => $feeAmount,
                        'enrolled_at'        => now(),
                    ]);

                    /**
                     * Create invoice (controller responsibility)
                     */
                    if ($feeAmount > 0) {
                        $monthYear = null;
                        if ($secondaryClass->payment_type === 'monthly') {
                            $monthYear = now()->format('m_Y');
                        }
                        $this->createInvoice($student, $feeAmount, 'Special Class Fee', $monthYear, 'secondary_class', $secondaryClassId);
                    }
                }
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
            clearServerCache();

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
     * @param  array  $validated
     * @param  int  $classNumeral
     * @param  string  $group
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
     * @param  Student  $student
     * @param  array  $validated
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
     * @param  mixed  $value
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
     * @param  Branch  $branch
     * @param  ClassName  $class
     * @return string
     */
    private function generateStudentUniqueId(Branch $branch, ClassName $class): string
    {
        $classNumeral = $class->class_numeral;
        $currentYear  = Carbon::now()->format('y');

        // ========================================
        // Updated Logic:
        // For class 10-12: Use class year_prefix
        // For class 04-09 (and others): Use current year
        // ========================================
        if ($classNumeral >= 10 && $classNumeral <= 12) {
            $year = $class->year_prefix ?? $currentYear;
        } else {
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

    /**
     * Create a payment invoice for a student
     *
     * @param  Student  $student
     * @param  float  $amount
     * @param  string  $typeName  - e.g., 'Admission Fee', 'Sheet Fee', 'Special Class Fee'
     * @param  string|null  $monthYear
     * @return void
     */
    private function createInvoice(Student $student, float $amount, string $typeName, ?string $monthYear = null): void
    {
        $yearSuffix = now()->format('y');
        $month      = now()->format('m');
        $prefix     = $student->branch->branch_prefix;

        $lastInvoice = PaymentInvoice::where('invoice_number', 'like', "{$prefix}{$yearSuffix}{$month}_%")
            ->orderBy('invoice_number', 'desc')
            ->withTrashed()
            ->first();

        $nextSequence = $lastInvoice
            ? (int) substr($lastInvoice->invoice_number, strrpos($lastInvoice->invoice_number, '_') + 1) + 1
            : 1001;

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
            'month_year'      => $monthYear,
            'invoice_type_id' => $invoiceType->id,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $student = Student::with([
            // Attendances for calendar
            'attendances',
            // Class
            'class:id,name,class_numeral,is_active',
            // Branch
            'branch:id,branch_name,branch_prefix',
            // Batch
            'batch:id,name',
            // Institution
            'institution:id,name,eiin_number',
            // Activation
            'studentActivation:id,active_status,created_at',
            // Activation history
            'activations.updatedBy:id,name',
            // Guardians
            'guardians:id,name,mobile_number,gender,relationship,student_id',
            // Siblings
            'siblings:id,name,year,class,institution_name,relationship,student_id',
            // Mobile numbers
            'mobileNumbers:id,mobile_number,number_type,student_id',
            // Payment profile
            'payments:id,payment_style,due_date,tuition_fee,student_id',
            // Payment invoices
            'paymentInvoices'      => function ($q) {
                $q->with(['invoiceType:id,type_name', 'student.payments:id,payment_style,due_date,student_id']);
            },
            // Payment transactions
            'paymentTransactions'  => function ($q) {
                $q->with(['paymentInvoice:id,invoice_number,created_at']);
            },
            // Subjects taken
            'subjectsTaken.subject:id,name,academic_group,subject_type',
            // ✅ FIXED: Sheet topics taken
            'sheetsTopicTaken'     => function ($q) {
                $q->with(['sheetTopic:id,topic_name,subject_id', 'sheetTopic.subject:id,name,class_id', 'sheetTopic.subject.class:id,name,class_numeral', 'distributedBy:id,name']);
            },
            // Sheet payments
            'sheetPayments.sheet'  => function ($q) {
                $q->with(['class:id,name,class_numeral', 'class.subjects:id,name,class_id']);
            },
            // Reference
            'reference.referer',
            // Secondary classes
            'secondaryClasses.secondaryClass.class:id,name,class_numeral',
            // Class change history
            'classChangeHistories' => function ($q) {
                $q->with([
                    'fromClass' => fn($cq) => $cq->withTrashed()->select('id', 'name', 'class_numeral'),
                    'toClass'   => fn($cq)   => $cq->withTrashed()->select('id', 'name', 'class_numeral'),
                    'createdBy:id,name',
                ]);
            },
            // Secondary class history
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

        // Load class names safely: bypass global scopes so both statuses are available
        // If student has a class, prefer loading same-status list; else load active by default
        $studentClassIsActive = optional($student->class)->is_active;

        if ($studentClassIsActive === false) {
            $classnames = ClassName::inactive()->get();
        } else {
            // true OR null → active by default
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

            // MobileNumbers Table Fields
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
            clearServerCache();

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

        // Create new subject records for each subject
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
        $deletedBy = auth()->id();

        DB::transaction(function () use ($student, $deletedBy) {
            /**
             * ==================================================
             * CASE 1: Pending student → PERMANENT DELETE
             * ==================================================
             */
            if (is_null($student->student_activation_id)) {
                // Force delete payment transactions
                $student
                    ->paymentInvoices()
                    ->withTrashed()
                    ->each(function ($invoice) {
                        $invoice->paymentTransactions()->withTrashed()->forceDelete();
                    });

                // Force delete invoices
                $student->paymentInvoices()->withTrashed()->forceDelete();

                // Force delete sheet payments
                $student->sheetPayments()->withTrashed()->forceDelete();

                // ✅ REMOVE payment info (NO Soft Deletes)
                $student->payments()->delete();

                // Force delete guardians & siblings
                $student->guardians()->withTrashed()->forceDelete();
                $student->siblings()->withTrashed()->forceDelete();

                // Optional cleanup (recommended)
                $student->subjectsTaken()->delete();
                // $student->sheetsTopicTaken()->delete();
                // $student->attendances()->delete();

                // Finally force delete student
                $student->forceDelete();

                return;
            }

            /**
             * ==================================================
             * CASE 2: Active student → SOFT DELETE
             * ==================================================
             */
            // Audit
            $student->update(['deleted_by' => $deletedBy]);

            // Soft delete only the student
            $student->delete();
        });

        clearServerCache();

        return response()->json(['success' => true]);
    }

    public function getReferredData(Request $request)
    {
        $refererType = $request->get('referer_type');

        if ($refererType == 'teacher') {
                                                    // Fetch teacher data (no unique_id)
            $teachers = \App\Models\Teacher::all(); // Adjust according to your data model

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

        $lastInvoice   = (clone $tuitionInvoices)->orderByRaw("CAST(SUBSTRING_INDEX(month_year, '_', -1) AS UNSIGNED) DESC, CAST(SUBSTRING_INDEX(month_year, '_', 1) AS UNSIGNED) DESC")->first();
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
                            $q->inactive()->select('id', 'name', 'class_numeral');
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
                            $q->inactive();
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

        return view('students.alumni.index', compact('students', 'studentsByBranch', 'classnames', 'batches', 'institutions', 'branches', 'isAdmin'));
    }
}
