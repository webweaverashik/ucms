<?php
namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Academic\SecondaryClass;
use App\Models\Branch;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentInvoiceType;
use App\Models\Payment\SecondaryClassPayment;
use App\Models\Student\Student;
use App\Models\Student\StudentSecondaryClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecondaryClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Only admin can view special classes.',
                ],
                403,
            );
        }

        $secondaryClasses = SecondaryClass::with('class:id,name')->withCount('students')->latest()->get();

        return response()->json([
            'success' => true,
            'data'    => $secondaryClasses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Only admin can create special classes.',
                ],
                403,
            );
        }

        $validated = $request->validate([
            'class_id'     => 'required|exists:class_names,id',
            'name'         => 'required|string|max:255',
            'payment_type' => 'required|in:one_time,monthly',
            'fee_amount'   => 'required|numeric|min:0',
        ]);

        $secondaryClass = SecondaryClass::create([
            'class_id'     => $validated['class_id'],
            'name'         => $validated['name'],
            'payment_type' => $validated['payment_type'],
            'fee_amount'   => $validated['fee_amount'],
            'is_active'    => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Special class created successfully.',
            'data'    => $secondaryClass->load('class:id,name')->loadCount('students'),
        ]);
    }

    /**
     * Display the specified resource (API).
     */
    public function show(SecondaryClass $secondaryClass)
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Only admin can view special class details.',
                ],
                403,
            );
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $secondaryClass->id,
                'class_id'       => $secondaryClass->class_id,
                'name'           => $secondaryClass->name,
                'payment_type'   => $secondaryClass->payment_type,
                'fee_amount'     => $secondaryClass->fee_amount,
                'is_active'      => $secondaryClass->is_active,
                'students_count' => $secondaryClass->students()->count(),
            ],
        ]);
    }

    /**
     * Display the specified resource with class context (Blade View).
     */
    public function showWithClass(ClassName $classname, SecondaryClass $secondaryClass)
    {
        if (! auth()->user()->can('classes.view')) {
            return redirect()->back()->with('warning', 'No permission to view classes.');
        }

        $user     = auth()->user();
        $isAdmin  = $user->isAdmin();
        $branchId = $user->branch_id;

        // Verify the secondary class belongs to this class
        if ((int) $secondaryClass->class_id !== (int) $classname->id) {
            return redirect()->route('classnames.show', $classname->id)->with('warning', 'Special class does not belong to this class.');
        }

        // Get branches for admin
        $branches = $isAdmin ? Branch::select('id', 'branch_name', 'branch_prefix')->orderBy('branch_name')->get() : collect();

        // Get batches for filter (include branch_id for dynamic filtering)
        $batches = Batch::select('id', 'name', 'branch_id')->orderBy('name')->get();

        // Calculate stats
        $stats = $this->calculateStats($secondaryClass, $isAdmin, $branchId, $branches);

        $isManager = false;
        try {
            $isManager = $user->isManager();
        } catch (\Throwable $e) {
            // Method might not exist on user model
        }

        // Preload available students for enrollment
        $availableStudents = $this->getAvailableStudentsData($classname, $secondaryClass, $isAdmin, $branchId);

        return view('secondary-classes.show', compact(
            'isManager',
            'classname',
            'secondaryClass',
            'stats',
            'branches',
            'batches',
            'isAdmin',
            'availableStudents'
        ));
    }

    /**
     * Get enrolled students via AJAX for DataTables (server-side processing)
     */
    public function getEnrolledStudentsAjax(Request $request, ClassName $classname, SecondaryClass $secondaryClass)
    {
        $user     = auth()->user();
        $isAdmin  = $user->isAdmin();
        $branchId = $user->branch_id;

        $isManager = false;
        try {
            $isManager = $user->isManager();
        } catch (\Throwable $e) {
        }

                                                                // Get filter parameters
        $statusType   = $request->get('status_type', 'active'); // 'active' or 'inactive'
        $branchFilter = $request->get('branch_id');
        $groupFilter  = $request->get('academic_group');
        $batchFilter  = $request->get('batch_id');
        $search       = $request->get('search')['value'] ?? '';

        // DataTables parameters
        $start       = $request->get('start', 0);
        $length      = $request->get('length', 10);
        $orderColumn = $request->get('order')[0]['column'] ?? 0;
        $orderDir    = $request->get('order')[0]['dir'] ?? 'asc';

        // Column mapping for ordering
        $columns = ['id', 'name', 'academic_group', 'batch_name', 'amount', 'total_paid', 'enrolled_at', 'actions'];

        // Base query
        $query = StudentSecondaryClass::where('secondary_class_id', $secondaryClass->id)
            ->where('is_active', $statusType === 'active')
            ->with([
                'student' => function ($q) {
                    $q->select(['id', 'student_unique_id', 'name', 'academic_group', 'branch_id', 'batch_id', 'class_id', 'student_activation_id'])
                        ->with(['branch:id,branch_name,branch_prefix', 'batch:id,name', 'studentActivation:id,active_status']);
                },
            ]);

        // Filter by branch for non-admin
        if (! $isAdmin) {
            $query->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        } elseif ($branchFilter) {
            $query->whereHas('student', function ($q) use ($branchFilter) {
                $q->where('branch_id', $branchFilter);
            });
        }

        // Filter by academic group
        if ($groupFilter) {
            $query->whereHas('student', function ($q) use ($groupFilter) {
                $q->where('academic_group', $groupFilter);
            });
        }

        // Filter by batch
        if ($batchFilter) {
            $query->whereHas('student', function ($q) use ($batchFilter) {
                $q->where('batch_id', $batchFilter);
            });
        }

        // Search
        if ($search) {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('student_unique_id', 'like', "%{$search}%");
            });
        }

        // Get total count before pagination (with branch filter for proper count)
        $totalRecordsQuery = StudentSecondaryClass::where('secondary_class_id', $secondaryClass->id)
            ->where('is_active', $statusType === 'active');

        // Apply branch filter for total records count
        if (! $isAdmin) {
            $totalRecordsQuery->whereHas('student', function ($sq) use ($branchId) {
                $sq->where('branch_id', $branchId);
            });
        } elseif ($branchFilter) {
            $totalRecordsQuery->whereHas('student', function ($sq) use ($branchFilter) {
                $sq->where('branch_id', $branchFilter);
            });
        }

        $totalRecords = $totalRecordsQuery->count();

        $filteredRecords = $query->count();

        // Apply ordering
        $hasCustomOrder = false;
        if (isset($columns[$orderColumn])) {
            $column = $columns[$orderColumn];
            if ($column === 'name') {
                $query->join('students', 'student_secondary_classes.student_id', '=', 'students.id')
                    ->orderBy('students.name', $orderDir)
                    ->select('student_secondary_classes.*');
                $hasCustomOrder = true;
            } elseif ($column === 'enrolled_at') {
                $query->orderBy('enrolled_at', $orderDir);
                $hasCustomOrder = true;
            } elseif ($column === 'amount') {
                $query->orderBy('amount', $orderDir);
                $hasCustomOrder = true;
            }
        }

        // Default ordering: enrolled_at descending (newest first)
        if (! $hasCustomOrder) {
            $query->orderBy('enrolled_at', 'desc');
        }

        // Pagination
        $enrollments = $query->skip($start)->take($length)->get();

        // Calculate total paid for all enrollments
        foreach ($enrollments as $enrollment) {
            $totalPaid = SecondaryClassPayment::where('student_id', $enrollment->student_id)
                ->where('secondary_class_id', $secondaryClass->id)
                ->whereHas('invoice')
                ->with(['invoice.paymentTransactions'])
                ->get()
                ->sum(function ($payment) {
                    return $payment->invoice?->paymentTransactions->sum('amount_paid') ?? 0;
                });
            $enrollment->total_paid = $totalPaid;
        }

        // Format data for DataTables
        $data = [];
        foreach ($enrollments as $index => $enrollment) {
            $student = $enrollment->student;
            if (! $student) {
                continue;
            }

            $row = [
                'DT_RowId'          => 'row_' . $enrollment->id,
                'DT_RowAttr'        => [
                    'data-branch-id'         => $student->branch_id,
                    'data-student-id'        => $student->id,
                    'data-enrollment-id'     => $enrollment->id,
                    'data-enrollment-status' => $enrollment->is_active ? 'active' : 'inactive',
                    'data-academic-group'    => $student->academic_group ?? '',
                ],
                'index'             => $start + $index + 1,
                'student_id'        => $student->id,
                'student_unique_id' => $student->student_unique_id,
                'name'              => $student->name,
                'academic_group'    => $student->academic_group,
                'batch_name'        => $student->batch->name ?? '-',
                'branch_id'         => $student->branch_id,
                'branch_name'       => $student->branch->branch_name ?? '-',
                'amount'            => $enrollment->amount,
                'total_paid'        => $enrollment->total_paid ?? 0,
                'enrolled_at'       => $enrollment->enrolled_at ? $enrollment->enrolled_at->format('d-M-Y') : '-',
                'is_active'         => $enrollment->is_active,
                'can_manage'        => ($isAdmin || $isManager) && $secondaryClass->is_active,
                'payment_type'      => $secondaryClass->payment_type,
            ];

            $data[] = $row;
        }

        return response()->json([
            'draw'            => intval($request->get('draw')),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /**
     * Get stats via AJAX
     */
    public function getStatsAjax(ClassName $classname, SecondaryClass $secondaryClass)
    {
        $user     = auth()->user();
        $isAdmin  = $user->isAdmin();
        $branchId = $user->branch_id;
        $branches = $isAdmin ? Branch::select('id', 'branch_name', 'branch_prefix')->orderBy('branch_name')->get() : collect();

        $stats = $this->calculateStats($secondaryClass, $isAdmin, $branchId, $branches);

        return response()->json([
            'success' => true,
            'stats'   => $stats,
        ]);
    }

    /**
     * Get branch counts for tabs
     */
    public function getBranchCountsAjax(Request $request, ClassName $classname, SecondaryClass $secondaryClass)
    {
        $user       = auth()->user();
        $isAdmin    = $user->isAdmin();
        $branchId   = $user->branch_id;
        $statusType = $request->get('status_type', 'active');

        $query = StudentSecondaryClass::where('secondary_class_id', $secondaryClass->id)
            ->where('is_active', $statusType === 'active')
            ->with('student:id,branch_id');

        if (! $isAdmin) {
            $query->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $enrollments = $query->get();

        // Group by branch_id and count, ensuring integer keys for consistent JS comparison
        $branchCounts = [];
        foreach ($enrollments as $enrollment) {
            $studentBranchId = (int) ($enrollment->student->branch_id ?? 0);
            if (! isset($branchCounts[$studentBranchId])) {
                $branchCounts[$studentBranchId] = 0;
            }
            $branchCounts[$studentBranchId]++;
        }

        return response()->json([
            'success' => true,
            'counts'  => $branchCounts,
        ]);
    }

    /**
     * Get available students data for preloading
     */
    private function getAvailableStudentsData(ClassName $classname, SecondaryClass $secondaryClass, bool $isAdmin, ?int $branchId)
    {
        // Get already enrolled student IDs
        $enrolledStudentIds = StudentSecondaryClass::where('secondary_class_id', $secondaryClass->id)->pluck('student_id')->toArray();

        // Get available students from the same class
        $studentsQuery = Student::where('class_id', $classname->id)
            ->whereNotIn('id', $enrolledStudentIds)
            ->with(['branch:id,branch_name', 'batch:id,name', 'studentActivation:id,active_status']);

        // Filter by branch for non-admin
        if (! $isAdmin) {
            $studentsQuery->where('branch_id', $branchId);
        }

        return $studentsQuery
            ->select(['id', 'student_unique_id', 'name', 'academic_group', 'branch_id', 'batch_id', 'student_activation_id'])
            ->orderBy('name')
            ->get()
            ->map(function ($student) {
                // Determine status: pending if student_activation_id is null
                $status = 'pending';
                if ($student->student_activation_id !== null) {
                    $status = $student->studentActivation?->active_status === 'active' ? 'active' : 'inactive';
                }

                return [
                    'id'                => $student->id,
                    'student_unique_id' => $student->student_unique_id,
                    'name'              => $student->name,
                    'academic_group'    => $student->academic_group,
                    'branch_id'         => $student->branch_id,
                    'branch_name'       => $student->branch->branch_name ?? '-',
                    'batch_name'        => $student->batch->name ?? '-',
                    'status'            => $status,
                    'is_pending'        => $student->student_activation_id === null,
                ];
            });
    }

    /**
     * Calculate stats for secondary class
     * Active/inactive based on StudentSecondaryClass->is_active
     */
    private function calculateStats(SecondaryClass $secondaryClass, bool $isAdmin, ?int $branchId, $branches): array
    {
        $enrollmentsQuery = StudentSecondaryClass::where('secondary_class_id', $secondaryClass->id)->with(['student.branch']);

        if (! $isAdmin) {
            $enrollmentsQuery->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $enrollments = $enrollmentsQuery->get();

        $totalStudents = $enrollments->count();

        // Use StudentSecondaryClass->is_active instead of student activation status
        $activeStudents   = $enrollments->where('is_active', true)->count();
        $inactiveStudents = $enrollments->where('is_active', false)->count();

        // Calculate total revenue from actual payments
        $totalRevenue = 0;
        foreach ($enrollments as $enrollment) {
            $paid = SecondaryClassPayment::where('student_id', $enrollment->student_id)
                ->where('secondary_class_id', $secondaryClass->id)
                ->whereHas('invoice')
                ->with(['invoice.paymentTransactions'])
                ->get()
                ->sum(function ($payment) {
                    return $payment->invoice?->paymentTransactions->sum('amount_paid') ?? 0;
                });
            $totalRevenue += $paid;
        }

        $expectedMonthlyRevenue = $secondaryClass->payment_type === 'monthly'
            ? $activeStudents * $secondaryClass->fee_amount
            : 0;

        // Branch-wise stats for admin
        $branchStats = [];
        if ($isAdmin && $branches->count() > 0) {
            foreach ($branches as $branch) {
                // Use loose comparison (==) to handle string/int type differences between servers
                $branchEnrollments = $enrollments->filter(fn($e) => (int) $e->student?->branch_id === (int) $branch->id);

                // Calculate branch revenue
                $branchRevenue = 0;
                foreach ($branchEnrollments as $enrollment) {
                    $paid = SecondaryClassPayment::where('student_id', $enrollment->student_id)
                        ->where('secondary_class_id', $secondaryClass->id)
                        ->whereHas('invoice')
                        ->with(['invoice.paymentTransactions'])
                        ->get()
                        ->sum(function ($payment) {
                            return $payment->invoice?->paymentTransactions->sum('amount_paid') ?? 0;
                        });
                    $branchRevenue += $paid;
                }

                $branchStats[$branch->id] = [
                    'name'     => $branch->branch_name,
                    'prefix'   => $branch->branch_prefix,
                    'total'    => $branchEnrollments->count(),
                    'active'   => $branchEnrollments->where('is_active', true)->count(),
                    'inactive' => $branchEnrollments->where('is_active', false)->count(),
                    'revenue'  => $branchRevenue,
                ];
            }
        }

        return [
            'total_students'           => $totalStudents,
            'active_students'          => $activeStudents,
            'inactive_students'        => $inactiveStudents,
            'total_revenue'            => $totalRevenue,
            'expected_monthly_revenue' => $expectedMonthlyRevenue,
            'default_fee'              => $secondaryClass->fee_amount,
            'branch_stats'             => $branchStats,
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SecondaryClass $secondaryClass)
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Only admin can update special classes.',
                ],
                403,
            );
        }

        // Toggle activation only
        if ($request->has('toggle_only') && $request->toggle_only === 'true') {
            $secondaryClass->update([
                'is_active' => ! $secondaryClass->is_active,
            ]);

            return response()->json([
                'success'   => true,
                'message'   => $secondaryClass->is_active ? 'Special class activated.' : 'Special class deactivated.',
                'is_active' => $secondaryClass->is_active,
            ]);
        }

        // Full update
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'payment_type' => 'required|in:one_time,monthly',
            'fee_amount'   => 'required|numeric|min:0',
        ]);

        $secondaryClass->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Special class updated successfully.',
            'data'    => $secondaryClass->fresh()->loadCount('students'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SecondaryClass $secondaryClass)
    {
        if (! auth()->user()->isAdmin()) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Only admin can delete special classes.',
                ],
                403,
            );
        }

        if ($secondaryClass->students()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete special class with enrolled students.',
            ]);
        }

        $secondaryClass->update(['deleted_by' => auth()->id()]);
        $secondaryClass->delete();

        return response()->json([
            'success' => true,
            'message' => 'Special class deleted successfully.',
        ]);
    }

    /**
     * Get secondary classes by parent class ID
     */
    public function getByClass($classId)
    {
        $secondaryClasses = SecondaryClass::where('class_id', $classId)->withCount('students')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data'    => $secondaryClasses,
        ]);
    }

    /**
     * Get available students for enrollment (not already enrolled in this secondary class)
     */
    public function getAvailableStudents(ClassName $classname, SecondaryClass $secondaryClass, Request $request)
    {
        $user      = auth()->user();
        $isManager = false;
        try {
            $isManager = $user->isManager();
        } catch (\Throwable $e) {
        }

        $canManage = $user->isAdmin() || $isManager;

        if (! $canManage && ! $user->can('classes.view')) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'No permission.',
                ],
                403,
            );
        }

        $isAdmin  = $user->isAdmin();
        $branchId = $user->branch_id;

        $students = $this->getAvailableStudentsData($classname, $secondaryClass, $isAdmin, $branchId);

        return response()->json([
            'success' => true,
            'data'    => $students,
        ]);
    }

    /**
     * Enroll a student in secondary class
     */
    public function enrollStudent(Request $request, ClassName $classname, SecondaryClass $secondaryClass)
    {
        $user      = auth()->user();
        $isManager = false;
        try {
            $isManager = $user->isManager();
        } catch (\Throwable $e) {
        }

        if (! ($user->isAdmin() || $isManager)) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Permission denied.',
                ],
                403,
            );
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'amount'     => 'required|numeric|min:0',
        ]);

        // Check if student belongs to this class
        $student = Student::findOrFail($validated['student_id']);
        if ((int) $student->class_id !== (int) $classname->id) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Student does not belong to this class.',
                ],
                422,
            );
        }

        // Check if already enrolled
        $existingEnrollment = StudentSecondaryClass::where('student_id', $validated['student_id'])->where('secondary_class_id', $secondaryClass->id)->first();

        if ($existingEnrollment) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Student is already enrolled in this special class.',
                ],
                422,
            );
        }

        DB::transaction(function () use ($validated, $secondaryClass, $student) {
            // Create enrollment
            StudentSecondaryClass::create([
                'student_id'         => $validated['student_id'],
                'secondary_class_id' => $secondaryClass->id,
                'amount'             => $validated['amount'],
                'enrolled_at'        => now(),
                'is_active'          => true,
            ]);

            // Create Invoice and SecondaryClassPayment
            $feeAmount = (int) $validated['amount'];
            if ($feeAmount > 0) {
                $monthYear = null;
                if ($secondaryClass->payment_type === 'monthly') {
                    $monthYear = now()->format('m_Y');
                }

                $invoice = $this->createInvoice($student, $feeAmount, 'Special Class Fee', $monthYear);

                // Create SecondaryClassPayment entry
                if ($invoice) {
                    SecondaryClassPayment::create([
                        'student_id'         => $student->id,
                        'secondary_class_id' => $secondaryClass->id,
                        'invoice_id'         => $invoice->id,
                    ]);
                }
            }

            clearServerCache();
        });

        return response()->json([
            'success' => true,
            'message' => 'Student enrolled successfully.',
        ]);
    }

    /**
     * Create a payment invoice for a student
     *
     * @param  Student  $student
     * @param  int  $amount
     * @param  string  $typeName  - e.g., 'Admission Fee', 'Sheet Fee', 'Special Class Fee'
     * @param  string|null  $monthYear
     * @return PaymentInvoice|null
     */
    private function createInvoice(Student $student, int $amount, string $typeName, ?string $monthYear = null): ?PaymentInvoice
    {
        $yearSuffix = now()->format('y');
        $month      = now()->format('m');

        // Ensure branch is loaded
        if (! $student->relationLoaded('branch')) {
            $student->load('branch');
        }

        $prefix = $student->branch->branch_prefix;

        $lastInvoice = PaymentInvoice::where('invoice_number', 'like', "{$prefix}{$yearSuffix}{$month}_%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        $nextSequence = $lastInvoice
            ? (int) substr($lastInvoice->invoice_number, strrpos($lastInvoice->invoice_number, '_') + 1) + 1
            : 1001;

        $invoiceNumber = "{$prefix}{$yearSuffix}{$month}_{$nextSequence}";

        $invoiceType = PaymentInvoiceType::where('type_name', $typeName)->first();

        if (! $invoiceType) {
            \Log::warning("Invoice type '{$typeName}' not found for student {$student->id}");

            return null;
        }

        return PaymentInvoice::create([
            'invoice_number'  => $invoiceNumber,
            'student_id'      => $student->id,
            'total_amount'    => $amount,
            'amount_due'      => $amount,
            'month_year'      => $monthYear,
            'invoice_type_id' => $invoiceType->id,
            'created_by'      => auth()->id(),
        ]);
    }

    /**
     * Update student enrollment (amount)
     */
    public function updateStudentEnrollment(Request $request, ClassName $classname, SecondaryClass $secondaryClass, Student $student)
    {
        $user      = auth()->user();
        $isManager = false;
        try {
            $isManager = $user->isManager();
        } catch (\Throwable $e) {
        }

        if (! ($user->isAdmin() || $isManager)) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Permission denied.',
                ],
                403,
            );
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $enrollment = StudentSecondaryClass::where('student_id', $student->id)->where('secondary_class_id', $secondaryClass->id)->first();

        if (! $enrollment) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Enrollment not found.',
                ],
                404,
            );
        }

        $enrollment->update([
            'amount' => $validated['amount'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Enrollment updated successfully.',
        ]);
    }

    /**
     * Toggle student enrollment activation status
     */
    public function toggleStudentActivation(Request $request, ClassName $classname, SecondaryClass $secondaryClass, Student $student)
    {
        $user      = auth()->user();
        $isManager = false;
        try {
            $isManager = $user->isManager();
        } catch (\Throwable $e) {
        }

        if (! ($user->isAdmin() || $isManager)) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Permission denied.',
                ],
                403,
            );
        }

        $enrollment = StudentSecondaryClass::where('student_id', $student->id)->where('secondary_class_id', $secondaryClass->id)->first();

        if (! $enrollment) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Enrollment not found.',
                ],
                404,
            );
        }

        // If trying to deactivate, check for unpaid invoices
        if ($enrollment->is_active) {
            $unpaidPayments = SecondaryClassPayment::where('student_id', $student->id)
                ->where('secondary_class_id', $secondaryClass->id)
                ->whereHas('invoice', function ($q) {
                    $q->where('status', '!=', 'paid');
                })
                ->count();

            if ($unpaidPayments > 0) {
                return response()->json(
                    [
                        'success'      => false,
                        'has_unpaid'   => true,
                        'unpaid_count' => $unpaidPayments,
                        'message'      => "Cannot deactivate. Student has {$unpaidPayments} unpaid Special Class Fee invoice(s). Please clear all dues first.",
                    ],
                    422,
                );
            }
        }

        $enrollment->update([
            'is_active' => ! $enrollment->is_active,
        ]);

        $statusText = $enrollment->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'success' => true,
            'message' => "Student enrollment {$statusText} successfully.",
            'is_active' => $enrollment->is_active,
        ]);
    }

    /**
     * Check for unpaid special class fee invoices
     * Uses SecondaryClassPayment->invoice->status
     */
    public function checkUnpaidInvoices(ClassName $classname, SecondaryClass $secondaryClass, Student $student)
    {
        $user      = auth()->user();
        $isManager = false;
        try {
            $isManager = $user->isManager();
        } catch (\Throwable $e) {
        }

        if (! ($user->isAdmin() || $isManager)) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Permission denied.',
                ],
                403,
            );
        }

        // Check for unpaid invoices via SecondaryClassPayment
        $unpaidPayments = SecondaryClassPayment::where('student_id', $student->id)
            ->where('secondary_class_id', $secondaryClass->id)
            ->whereHas('invoice', function ($q) {
                $q->where('status', '!=', 'paid');
            })
            ->with('invoice')
            ->get();

        $unpaidInvoices = $unpaidPayments->map(fn($p) => $p->invoice)->filter();

        return response()->json([
            'success'         => true,
            'has_unpaid'      => $unpaidInvoices->count() > 0,
            'unpaid_count'    => $unpaidInvoices->count(),
            'unpaid_amount'   => $unpaidInvoices->sum('amount_due'),
            'unpaid_invoices' => $unpaidInvoices->map(function ($invoice) {
                return [
                    'id'             => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'month_year'     => $invoice->month_year,
                    'total_amount'   => $invoice->total_amount,
                    'amount_due'     => $invoice->amount_due,
                    'status'         => $invoice->status,
                ];
            }),
        ]);
    }

    /**
     * Withdraw (drop) a student from secondary class
     * No force withdraw - must clear dues first
     */
    public function withdrawStudent(Request $request, ClassName $classname, SecondaryClass $secondaryClass, Student $student)
    {
        $user      = auth()->user();
        $isManager = false;
        try {
            $isManager = $user->isManager();
        } catch (\Throwable $e) {
        }

        if (! ($user->isAdmin() || $isManager)) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Permission denied.',
                ],
                403,
            );
        }

        // Check for unpaid invoices via SecondaryClassPayment
        $unpaidPayments = SecondaryClassPayment::where('student_id', $student->id)
            ->where('secondary_class_id', $secondaryClass->id)
            ->whereHas('invoice', function ($q) {
                $q->where('status', '!=', 'paid');
            })
            ->count();

        if ($unpaidPayments > 0) {
            return response()->json(
                [
                    'success'      => false,
                    'has_unpaid'   => true,
                    'unpaid_count' => $unpaidPayments,
                    'message'      => "Student has {$unpaidPayments} unpaid Special Class Fee invoice(s). Please clear all dues before withdrawal.",
                ],
                422,
            );
        }

        $enrollment = StudentSecondaryClass::where('student_id', $student->id)->where('secondary_class_id', $secondaryClass->id)->first();

        if (! $enrollment) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Enrollment not found.',
                ],
                404,
            );
        }

        DB::transaction(function () use ($enrollment) {
            // Delete enrollment
            $enrollment->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Student withdrawn successfully.',
        ]);
    }
}