<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Academic\ClassName;
use App\Models\Academic\SecondaryClass;
use App\Models\Branch;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentInvoiceType;
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
            return response()->json([
                'success' => false,
                'message' => 'Only admin can view special classes.',
            ], 403);
        }

        $secondaryClasses = SecondaryClass::with('class:id,name')
            ->withCount('students')
            ->latest()
            ->get();

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
            return response()->json([
                'success' => false,
                'message' => 'Only admin can create special classes.',
            ], 403);
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
            return response()->json([
                'success' => false,
                'message' => 'Only admin can view special class details.',
            ], 403);
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
            return redirect()->route('classnames.show', $classname->id)
                ->with('warning', 'Special class does not belong to this class.');
        }

        // Get branches for admin
        $branches = $isAdmin
            ? Branch::select('id', 'branch_name', 'branch_prefix')->orderBy('branch_name')->get()
            : collect();

        // Get enrolled students with relationships
        $enrolledStudentsQuery = StudentSecondaryClass::where('secondary_class_id', $secondaryClass->id)
            ->with([
                'student' => function ($q) {
                    $q->select(['id', 'student_unique_id', 'name', 'academic_group', 'branch_id', 'batch_id', 'class_id', 'student_activation_id'])
                        ->with([
                            'branch:id,branch_name,branch_prefix',
                            'batch:id,name',
                            'studentActivation:id,active_status',
                        ]);
                },
            ]);

        // Filter by branch for non-admin
        if (! $isAdmin) {
            $enrolledStudentsQuery->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $enrolledStudents = $enrolledStudentsQuery->get();

        // Calculate stats
        $stats = $this->calculateStats($secondaryClass, $isAdmin, $branchId, $branches);

        // Group students by branch for admin
        $studentsByBranch = [];
        if ($isAdmin) {
            $studentsByBranch = $enrolledStudents->groupBy(function ($enrollment) {
                return $enrollment->student->branch_id ?? 0;
            });
        }

        $isManager = false;
        try {
            $isManager = $user->isManager();
        } catch (\Throwable $e) {
            // Method might not exist on user model
        }

        return view('secondary-classes.show', compact(
            'isManager',
            'classname',
            'secondaryClass',
            'enrolledStudents',
            'stats',
            'branches',
            'isAdmin',
            'studentsByBranch'
        ));
    }

    /**
     * Calculate stats for secondary class
     */
    private function calculateStats(SecondaryClass $secondaryClass, bool $isAdmin, ?int $branchId, $branches): array
    {
        $enrollments = StudentSecondaryClass::where('secondary_class_id', $secondaryClass->id)
            ->with(['student.studentActivation', 'student.branch']);

        if (! $isAdmin) {
            $enrollments->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $enrollments = $enrollments->get();

        $totalStudents    = $enrollments->count();
        $activeStudents   = $enrollments->filter(fn($e) => $e->student?->studentActivation?->active_status === 'active')->count();
        $inactiveStudents = $totalStudents - $activeStudents;

        $totalRevenue = $enrollments->sum('amount');
        $expectedMonthlyRevenue = $secondaryClass->payment_type === 'monthly'
            ? $activeStudents * $secondaryClass->fee_amount
            : 0;

        // Branch-wise stats for admin
        $branchStats = [];
        if ($isAdmin && $branches->count() > 0) {
            foreach ($branches as $branch) {
                $branchEnrollments = $enrollments->filter(fn($e) => $e->student?->branch_id === $branch->id);
                $branchStats[$branch->id] = [
                    'name'     => $branch->branch_name,
                    'prefix'   => $branch->branch_prefix,
                    'total'    => $branchEnrollments->count(),
                    'active'   => $branchEnrollments->filter(fn($e) => $e->student?->studentActivation?->active_status === 'active')->count(),
                    'inactive' => $branchEnrollments->filter(fn($e) => $e->student?->studentActivation?->active_status !== 'active')->count(),
                    'revenue'  => $branchEnrollments->sum('amount'),
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
            return response()->json([
                'success' => false,
                'message' => 'Only admin can update special classes.',
            ], 403);
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
            return response()->json([
                'success' => false,
                'message' => 'Only admin can delete special classes.',
            ], 403);
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
        $secondaryClasses = SecondaryClass::where('class_id', $classId)
            ->withCount('students')
            ->orderBy('name')
            ->get();

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
        $user = auth()->user();
        $isManager = false;
        try {
            $isManager = $user->isManager();
        } catch (\Throwable $e) {}

        $canManage = $user->isAdmin() || $isManager;

        if (! $canManage && ! $user->can('classes.view')) {
            return response()->json([
                'success' => false,
                'message' => 'No permission.',
            ], 403);
        }

        $user     = auth()->user();
        $isAdmin  = $user->isAdmin();
        $branchId = $user->branch_id;

        // Get already enrolled student IDs
        $enrolledStudentIds = StudentSecondaryClass::where('secondary_class_id', $secondaryClass->id)
            ->pluck('student_id')
            ->toArray();

        // Get available students from the same class
        $studentsQuery = Student::where('class_id', $classname->id)
            ->whereNotIn('id', $enrolledStudentIds)
            ->where(function ($q) {
                $q->active()->orWhere(function($sub) {
                    $sub->pending();
                });
            })
            ->with(['branch:id,branch_name', 'batch:id,name', 'studentActivation:id,active_status']);

        // Filter by branch for non-admin
        if (! $isAdmin) {
            $studentsQuery->where('branch_id', $branchId);
        }

        // Search filter
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('student_unique_id', 'like', "%{$search}%");
            });
        }

        // Branch filter for admin
        if ($isAdmin && $request->has('branch_id') && ! empty($request->branch_id)) {
            $studentsQuery->where('branch_id', $request->branch_id);
        }

        $students = $studentsQuery->select(['id', 'student_unique_id', 'name', 'academic_group', 'branch_id', 'batch_id', 'student_activation_id'])
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $students->map(function ($student) {
                return [
                    'id'                => $student->id,
                    'student_unique_id' => $student->student_unique_id,
                    'name'              => $student->name,
                    'academic_group'    => $student->academic_group,
                    'branch_name'       => $student->branch->branch_name ?? '-',
                    'batch_name'        => $student->batch->name ?? '-',
                    'is_active'         => $student->studentActivation?->active_status === 'active',
                ];
            }),
        ]);
    }

    /**
     * Enroll a student in secondary class
     */
    public function enrollStudent(Request $request, ClassName $classname, SecondaryClass $secondaryClass)
    {
        $user = auth()->user();
        $isManager = false;
        try {
            $isManager = $user->isManager();
        } catch (\Throwable $e) {}

        if (! ($user->isAdmin() || $isManager)) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied.',
            ], 403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'amount'     => 'required|numeric|min:0',
        ]);

        // Check if student belongs to this class
        $student = Student::findOrFail($validated['student_id']);
        if ($student->class_id !== $classname->id) {
            return response()->json([
                'success' => false,
                'message' => 'Student does not belong to this class.',
            ], 422);
        }

        // Check if already enrolled
        $existingEnrollment = StudentSecondaryClass::where('student_id', $validated['student_id'])
            ->where('secondary_class_id', $secondaryClass->id)
            ->first();

        if ($existingEnrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Student is already enrolled in this special class.',
            ], 422);
        }

        DB::transaction(function () use ($validated, $secondaryClass, $student) {
            // Create enrollment
            StudentSecondaryClass::create([
                'student_id'         => $validated['student_id'],
                'secondary_class_id' => $secondaryClass->id,
                'amount'             => $validated['amount'],
                'enrolled_at'        => now(),
            ]);

            // Create Invoice
            $feeAmount = (int) $validated['amount'];
            if ($feeAmount > 0) {
                $monthYear = null;
                if ($secondaryClass->payment_type === 'monthly') {
                    $monthYear = now()->format('m_Y');
                }
                $this->createInvoice($student, $feeAmount, 'Special Class Fee', $monthYear);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Student enrolled successfully.',
        ]);
    }

    /**
     * Create a payment invoice for a student
     *
     * @param Student $student
     * @param int $amount
     * @param string $typeName - e.g., 'Admission Fee', 'Sheet Fee', 'Special Class Fee'
     * @param string|null $monthYear
     * @return void
     */
    private function createInvoice(Student $student, int $amount, string $typeName, ?string $monthYear = null): void
    {
        $yearSuffix = now()->format('y');
        $month      = now()->format('m');
        // Ensure branch is loaded
        if (!$student->relationLoaded('branch')) {
            $student->load('branch');
        }
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
        $user = auth()->user();
        $isManager = false;
        try {
            $isManager = $user->isManager();
        } catch (\Throwable $e) {}

        if (! ($user->isAdmin() || $isManager)) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied.',
            ], 403);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $enrollment = StudentSecondaryClass::where('student_id', $student->id)
            ->where('secondary_class_id', $secondaryClass->id)
            ->first();

        if (! $enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Enrollment not found.',
            ], 404);
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
     * Check for unpaid special class fee invoices
     */
    public function checkUnpaidInvoices(ClassName $classname, SecondaryClass $secondaryClass, Student $student)
    {
        $user = auth()->user();
        $isManager = false;
        try {
            $isManager = $user->isManager();
        } catch (\Throwable $e) {}

        if (! ($user->isAdmin() || $isManager)) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied.',
            ], 403);
        }

        // Get the Special Class Fee invoice type
        $specialClassFeeType = PaymentInvoiceType::where('type_name', 'Special Class Fee')->first();

        if (! $specialClassFeeType) {
            return response()->json([
                'success'         => true,
                'has_unpaid'      => false,
                'unpaid_count'    => 0,
                'unpaid_amount'   => 0,
                'unpaid_invoices' => [],
            ]);
        }

        // Check for unpaid invoices
        $unpaidInvoices = PaymentInvoice::where('student_id', $student->id)
            ->where('invoice_type_id', $specialClassFeeType->id)
            ->whereIn('status', ['due', 'partially_paid'])
            ->get();

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
     */
    public function withdrawStudent(Request $request, ClassName $classname, SecondaryClass $secondaryClass, Student $student)
    {
        $user = auth()->user();
        $isManager = false;
        try {
            $isManager = $user->isManager();
        } catch (\Throwable $e) {}

        if (! ($user->isAdmin() || $isManager)) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied.',
            ], 403);
        }

        // Check for unpaid invoices first
        $specialClassFeeType = PaymentInvoiceType::where('type_name', 'Special Class Fee')->first();

        if ($specialClassFeeType) {
            $unpaidInvoices = PaymentInvoice::where('student_id', $student->id)
                ->where('invoice_type_id', $specialClassFeeType->id)
                ->whereIn('status', ['due', 'partially_paid'])
                ->count();

            // Check if force withdraw is requested
            $forceWithdraw = $request->input('force_withdraw', false);

            if ($unpaidInvoices > 0 && ! $forceWithdraw) {
                return response()->json([
                    'success'      => false,
                    'has_unpaid'   => true,
                    'unpaid_count' => $unpaidInvoices,
                    'message'      => "Student has {$unpaidInvoices} unpaid Special Class Fee invoice(s). Please clear the dues first or confirm force withdrawal.",
                ], 422);
            }
        }

        $enrollment = StudentSecondaryClass::where('student_id', $student->id)
            ->where('secondary_class_id', $secondaryClass->id)
            ->first();

        if (! $enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Enrollment not found.',
            ], 404);
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