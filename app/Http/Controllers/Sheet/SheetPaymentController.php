<?php
namespace App\Http\Controllers\Sheet;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Sheet\Sheet;
use App\Models\Sheet\SheetPayment;
use Illuminate\Http\Request;

class SheetPaymentController extends Controller
{
    /**
     * Display a listing of sheet payments.
     */
    public function index()
    {
        $user         = auth()->user();
        $isAdmin      = $user->hasRole('admin');
        $sheet_groups = Sheet::whereHas('class', fn($q) => $q->active())->get();

        // For admin, get all branches
        $branches = collect();
        if ($isAdmin) {
            $branches = Branch::orderBy('branch_name')->get();
        }

        return view('sheets.payments.index', compact('sheet_groups', 'branches', 'isAdmin'));
    }

    /**
     * Get sheet payments data for AJAX DataTables.
     */
    public function getData(Request $request)
    {
        $user     = auth()->user();
        $isAdmin  = $user->hasRole('admin');
        $branchId = $isAdmin ? $request->input('branch_id') : $user->branch_id;

        // Build optimized base query using joins instead of multiple whereHas
        $baseQuery = SheetPayment::query()
            ->select([
                'sheet_payments.id',
                'sheet_payments.sheet_id',
                'sheet_payments.invoice_id',
                'sheet_payments.student_id',
                'sheet_payments.created_at',
            ])
            ->join('sheets', 'sheet_payments.sheet_id', '=', 'sheets.id')
            ->join('class_names', 'sheets.class_id', '=', 'class_names.id')
            ->join('payment_invoices', 'sheet_payments.invoice_id', '=', 'payment_invoices.id')
            ->join('students', 'sheet_payments.student_id', '=', 'students.id')
            ->whereNull('sheet_payments.deleted_at')
            ->whereNull('students.deleted_at')
            ->where('class_names.is_active', true);

        // Apply branch filter using join (much faster than whereHas)
        if ($branchId) {
            $baseQuery->where('students.branch_id', $branchId);
        }

        // Get total count before search/filter (use a simpler count query)
        $totalQuery   = clone $baseQuery;
        $totalRecords = $totalQuery->count();

        // Apply search filter
        $searchValue = $request->input('search.value');
        if (! empty($searchValue)) {
            $baseQuery->where(function ($q) use ($searchValue) {
                $q->where('class_names.name', 'like', "%{$searchValue}%")
                    ->orWhere('class_names.class_numeral', 'like', "%{$searchValue}%")
                    ->orWhere('payment_invoices.invoice_number', 'like', "%{$searchValue}%")
                    ->orWhere('students.name', 'like', "%{$searchValue}%")
                    ->orWhere('students.student_unique_id', 'like', "%{$searchValue}%");
            });
        }

        // Apply sheet group filter
        $sheetGroup = $request->input('sheet_group');
        if (! empty($sheetGroup)) {
            if (preg_match('/^(.+)\s*\((\d+)\)$/', $sheetGroup, $matches)) {
                $className    = trim($matches[1]);
                $classNumeral = trim($matches[2]);
                $baseQuery->where('class_names.name', $className)
                    ->where('class_names.class_numeral', $classNumeral);
            } else {
                $baseQuery->where('class_names.name', 'like', "%{$sheetGroup}%");
            }
        }

        // Apply payment status filter
        $paymentStatus = $request->input('payment_status');
        if (! empty($paymentStatus)) {
            $status = str_replace('T_', '', $paymentStatus);
            $baseQuery->where('payment_invoices.status', $status);
        }

        // Get filtered count
        $filteredQuery   = clone $baseQuery;
        $filteredRecords = $filteredQuery->count();

        // Apply ordering
        $orderColumnIndex = (int) $request->input('order.0.column', 8);
        $orderDirection   = $request->input('order.0.dir', 'desc');

        // Column mapping for ordering (using actual table columns)
        $orderColumns = [
            0 => 'sheet_payments.id',
            1 => 'class_names.name',
            2 => 'payment_invoices.invoice_number',
            3 => 'payment_invoices.total_amount',
            4 => 'payment_invoices.status',
            5 => 'payment_invoices.status',
            6 => 'payment_invoices.total_amount',
            7 => 'students.name',
            8 => 'sheet_payments.created_at',
        ];

        $orderColumn = $orderColumns[$orderColumnIndex] ?? 'sheet_payments.created_at';
        $baseQuery->orderBy($orderColumn, $orderDirection);

        // Apply pagination
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        // Get paginated results with eager loading for related data
        $paymentIds = $baseQuery->skip($start)->take($length)->pluck('sheet_payments.id');

        // Handle empty results
        if ($paymentIds->isEmpty()) {
            return response()->json([
                'draw'            => (int) $request->input('draw', 1),
                'recordsTotal'    => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data'            => [],
            ]);
        }

        // Single query to get all payment data with relationships
        // Use FIELD() for MySQL to maintain order from the paginated query
        $payments = SheetPayment::whereIn('id', $paymentIds)
            ->with([
                'sheet:id,class_id,price',
                'sheet.class:id,name,class_numeral',
                'invoice:id,invoice_number,total_amount,status',
                'invoice.paymentTransactions:id,payment_invoice_id,amount_paid',
                'student:id,name,student_unique_id',
            ])
            ->orderByRaw("FIELD(id, " . $paymentIds->implode(',') . ")")
            ->get();

        // Format data for DataTables
        $data    = [];
        $counter = $start + 1;

        foreach ($payments as $payment) {
            $sheet   = $payment->sheet;
            $invoice = $payment->invoice;
            $student = $payment->student;

            // Skip if any required relationship is missing
            if (! $sheet || ! $sheet->class || ! $invoice || ! $student) {
                continue;
            }

            $sheetGroup    = $sheet->class->name . ' (' . $sheet->class->class_numeral . ')';
            $invoiceStatus = $invoice->status ?? 'unknown';

            // Pre-calculate status badge
            $statusBadge = match ($invoiceStatus) {
                'due'            => '<span class="badge badge-warning">Due</span>',
                'partially_paid' => '<span class="badge badge-info">Partial</span>',
                'paid'           => '<span class="badge badge-success">Paid</span>',
                default          => '<span class="badge badge-secondary">Unknown</span>',
            };

            $statusFilter = match ($invoiceStatus) {
                'due'            => 'T_due',
                'partially_paid' => 'T_partially_paid',
                'paid'           => 'T_paid',
                default          => '',
            };

            $data[] = [
                'DT_RowIndex'      => $counter,
                'sl'               => $counter,
                'sheet_group'      => '<a href="' . route('sheets.show', $sheet->id) . '" class="text-gray-600 text-hover-primary">' . e($sheetGroup) . '</a>',
                'sheet_group_raw'  => $sheetGroup,
                'invoice_no'       => '<a href="' . route('invoices.show', $invoice->id) . '" class="text-gray-600 text-hover-primary">' . e($invoice->invoice_number) . '</a>',
                'invoice_no_raw'   => $invoice->invoice_number,
                'amount'           => $invoice->total_amount,
                'status_filter'    => $statusFilter,
                'status'           => $statusBadge,
                'status_raw'       => ucfirst(str_replace('_', ' ', $invoiceStatus)),
                'paid'             => $invoice->paymentTransactions->sum('amount_paid'),
                'student'          => '<a href="' . route('students.show', $student->id) . '" class="text-gray-600 text-hover-primary">' . e($student->name) . ', ' . e($student->student_unique_id) . '</a>',
                'student_raw'      => $student->name . ', ' . $student->student_unique_id,
                'payment_date'     => $payment->created_at->format('d-m-Y') . '<br><small class="text-muted">' . $payment->created_at->format('h:i:s A') . '</small>',
                'payment_date_raw' => $payment->created_at->format('d-m-Y h:i:s A'),
            ];

            $counter++;
        }

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /**
     * Export sheet payments data.
     */
    public function export(Request $request)
    {
        $user     = auth()->user();
        $isAdmin  = $user->hasRole('admin');
        $branchId = $isAdmin ? $request->input('branch_id') : $user->branch_id;

        // Build optimized query using joins
        $query = SheetPayment::query()
            ->select(['sheet_payments.id'])
            ->join('sheets', 'sheet_payments.sheet_id', '=', 'sheets.id')
            ->join('class_names', 'sheets.class_id', '=', 'class_names.id')
            ->join('payment_invoices', 'sheet_payments.invoice_id', '=', 'payment_invoices.id')
            ->join('students', 'sheet_payments.student_id', '=', 'students.id')
            ->whereNull('sheet_payments.deleted_at')
            ->whereNull('students.deleted_at')
            ->where('class_names.is_active', true);

        // Apply branch filter
        if ($branchId) {
            $query->where('students.branch_id', $branchId);
        }

        // Apply search filter
        $searchValue = $request->input('search');
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('class_names.name', 'like', "%{$searchValue}%")
                    ->orWhere('class_names.class_numeral', 'like', "%{$searchValue}%")
                    ->orWhere('payment_invoices.invoice_number', 'like', "%{$searchValue}%")
                    ->orWhere('students.name', 'like', "%{$searchValue}%")
                    ->orWhere('students.student_unique_id', 'like', "%{$searchValue}%");
            });
        }

        // Apply sheet group filter
        $sheetGroup = $request->input('sheet_group');
        if (! empty($sheetGroup)) {
            if (preg_match('/^(.+)\s*\((\d+)\)$/', $sheetGroup, $matches)) {
                $className    = trim($matches[1]);
                $classNumeral = trim($matches[2]);
                $query->where('class_names.name', $className)
                    ->where('class_names.class_numeral', $classNumeral);
            } else {
                $query->where('class_names.name', 'like', "%{$sheetGroup}%");
            }
        }

        // Apply payment status filter
        $paymentStatus = $request->input('payment_status');
        if (! empty($paymentStatus)) {
            $status = str_replace('T_', '', $paymentStatus);
            $query->where('payment_invoices.status', $status);
        }

        // Get all matching IDs
        $paymentIds = $query->orderBy('sheet_payments.created_at', 'desc')->pluck('sheet_payments.id');

        // Single query to get all data with relationships
        $payments = SheetPayment::whereIn('id', $paymentIds)
            ->with([
                'sheet:id,class_id,price',
                'sheet.class:id,name,class_numeral',
                'invoice:id,invoice_number,total_amount,status',
                'invoice.paymentTransactions:id,payment_invoice_id,amount_paid',
                'student:id,name,student_unique_id',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        // Format data for export
        $data    = [];
        $counter = 1;

        foreach ($payments as $payment) {
            $sheet   = $payment->sheet;
            $invoice = $payment->invoice;
            $student = $payment->student;

            // Skip if any required relationship is missing
            if (! $sheet || ! $sheet->class || ! $invoice || ! $student) {
                continue;
            }

            $sheetGroup = $sheet->class->name . ' (' . $sheet->class->class_numeral . ')';
            $statusText = ucfirst(str_replace('_', ' ', $invoice->status ?? 'unknown'));

            $data[] = [
                'sl'           => $counter++,
                'sheet_group'  => $sheetGroup,
                'invoice_no'   => $invoice->invoice_number,
                'amount'       => $invoice->total_amount,
                'status'       => $statusText,
                'paid'         => $invoice->paymentTransactions->sum('amount_paid'),
                'student'      => $student->name . ', ' . $student->student_unique_id,
                'payment_date' => $payment->created_at->format('d-m-Y h:i:s A'),
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
            'total'   => count($data),
        ]);
    }
}
