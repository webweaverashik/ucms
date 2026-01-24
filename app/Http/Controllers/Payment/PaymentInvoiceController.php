<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentInvoiceType;
use App\Models\Sheet\SheetPayment;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentInvoiceController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('invoices.view')) {
            return redirect()->back()->with('warning', 'No permission to view invoices.');
        }

        $branchId = auth()->user()->branch_id;
        $isAdmin  = $branchId == 0;

        // Get branches for admin tabs
        $branches = $isAdmin ? Branch::orderBy('branch_name')->get() : collect();

        // Get students for modal
        $students = Student::active()
            ->with(['studentActivation:id,active_status', 'payments:id,student_id,payment_style,due_date,tuition_fee'])
            ->when($branchId != 0, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('class', fn($q) => $q->active())
            ->orderBy('student_unique_id')
            ->select('id', 'name', 'student_unique_id', 'branch_id')
            ->get();

        // Get invoice types
        $invoice_types = PaymentInvoiceType::select('id', 'type_name')
            ->where('type_name', '!=', 'Special Class Fee')
            ->orderBy('type_name')
            ->get();

        return view('invoices.index', compact('branches', 'students', 'invoice_types', 'isAdmin'));
    }

    /**
     * Get filter options (months) for dropdowns - AJAX
     */
    public function getFilterOptions(Request $request)
    {
        $branchId       = auth()->user()->branch_id;
        $filterBranchId = $request->get('branch_id', $branchId);

        if ($branchId != 0) {
            $filterBranchId = $branchId;
        }

        $studentQuery = function ($query) use ($filterBranchId, $branchId) {
            $query->withoutTrashed();
            if ($branchId != 0) {
                $query->where('branch_id', $branchId);
            } elseif ($filterBranchId) {
                $query->where('branch_id', $filterBranchId);
            }
        };

        $dueMonths = PaymentInvoice::where('status', '!=', 'paid')
            ->whereHas('student', $studentQuery)
            ->whereNotNull('month_year')
            ->pluck('month_year')
            ->filter(fn($month) => preg_match('/^\d{2}_\d{4}$/', $month))
            ->unique()
            ->sortBy(fn($month) => Carbon::createFromFormat('m_Y', $month))
            ->values();

        $paidMonths = PaymentInvoice::where('status', 'paid')
            ->whereHas('student', $studentQuery)
            ->whereNotNull('month_year')
            ->pluck('month_year')
            ->filter(fn($month) => preg_match('/^\d{2}_\d{4}$/', $month))
            ->unique()
            ->sortBy(fn($month) => Carbon::createFromFormat('m_Y', $month))
            ->values();

        return response()->json([
            'dueMonths'  => $dueMonths->map(function ($month) {
                $parts = explode('_', $month);
                $date  = Carbon::create($parts[1], $parts[0], 1);
                return ['value' => 'D_' . $month, 'label' => $date->format('F Y')];
            }),
            'paidMonths' => $paidMonths->map(function ($month) {
                $parts = explode('_', $month);
                $date  = Carbon::create($parts[1], $parts[0], 1);
                return ['value' => 'P_' . $month, 'label' => $date->format('F Y')];
            }),
        ]);
    }

    /**
     * Server-side DataTable for unpaid invoices
     */
    public function getUnpaidInvoicesAjax(Request $request)
    {
        if (! auth()->user()->can('invoices.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $branchId       = auth()->user()->branch_id;
        $filterBranchId = $request->get('branch_id', $branchId);

        if ($branchId != 0) {
            $filterBranchId = $branchId;
        }

        // DataTables parameters
        $draw        = intval($request->get('draw', 1));
        $start       = intval($request->get('start', 0));
        $length      = intval($request->get('length', 10));
        $search      = $request->get('search')['value'] ?? '';
        $orderColumn = $request->get('order')[0]['column'] ?? 0;
        $orderDir    = $request->get('order')[0]['dir'] ?? 'desc';

        // Column mapping for ordering
        $columns = ['id', 'invoice_number', 'student_name', 'invoice_type', 'billing_month', 'total_amount', 'amount_due', 'due_date', 'status', 'last_comment', 'created_at', 'actions'];
        $orderBy = $columns[$orderColumn] ?? 'id';

        $studentQuery = function ($query) use ($filterBranchId, $branchId) {
            $query->withoutTrashed();
            if ($branchId != 0) {
                $query->where('branch_id', $branchId);
            } elseif ($filterBranchId) {
                $query->where('branch_id', $filterBranchId);
            }
        };

        // Base query
        $query = PaymentInvoice::with([
            'student:id,name,student_unique_id,branch_id',
            'student.payments:id,student_id,payment_style,due_date,tuition_fee',
            'student.mobileNumbers:id,student_id,mobile_number,number_type',
            'invoiceType:id,type_name',
            'comments' => fn($q) => $q->with('commentedBy:id,name')->latest()->limit(1),
        ])
            ->withCount('comments')
            ->where('status', '!=', 'paid')
            ->whereHas('student', $studentQuery);

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('student', fn($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('student_unique_id', 'like', "%{$search}%"))
                    ->orWhereHas('student.mobileNumbers', fn($mq) => $mq->where('mobile_number', 'like', "%{$search}%"));
            });
        }

        // Apply custom filters
        if ($invoiceType = $request->get('invoice_type')) {
            $typeName = str_replace('ucms_', '', $invoiceType);
            $query->whereHas('invoiceType', fn($q) => $q->where('type_name', $typeName));
        }

        if ($dueDate = $request->get('due_date')) {
            $parts = explode('/', $dueDate);
            if (count($parts) === 2) {
                $query->whereHas('student.payments', fn($q) => $q->where('due_date', $parts[1]));
            }
        }

        if ($billingMonth = $request->get('billing_month')) {
            $monthYear = str_replace('D_', '', $billingMonth);
            $query->where('month_year', $monthYear);
        }

        // Get total before status filter
        $recordsTotal = (clone $query)->count();

        // Apply status filter (requires post-processing for overdue)
        $status = $request->get('status');
        if ($status === 'I_due') {
            $query->where('status', 'due');
        } elseif ($status === 'I_partial') {
            $query->where('status', 'partially_paid');
        }

        $recordsFiltered = (clone $query)->count();

        // Order and paginate
        if (in_array($orderBy, ['id', 'invoice_number', 'total_amount', 'amount_due', 'created_at'])) {
            $query->orderBy($orderBy, $orderDir);
        } else {
            $query->orderBy('id', 'desc');
        }

        $invoices = $query->skip($start)->take($length)->get();

        // Filter overdue if needed (after pagination for better performance)
        if ($status === 'I_overdue') {
            // For overdue, we need to re-query without pagination
            $overdueQuery = PaymentInvoice::with([
                'student:id,name,student_unique_id,branch_id',
                'student.payments:id,student_id,payment_style,due_date,tuition_fee',
                'student.mobileNumbers:id,student_id,mobile_number,number_type',
                'invoiceType:id,type_name',
                'comments' => fn($q) => $q->with('commentedBy:id,name')->latest()->limit(1),
            ])
                ->withCount('comments')
                ->where('status', '!=', 'paid')
                ->whereHas('student', $studentQuery);

            if ($search) {
                $overdueQuery->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('student', fn($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('student_unique_id', 'like', "%{$search}%"));
                });
            }

            $allInvoices = $overdueQuery->get()->filter(function ($invoice) {
                $payment = optional($invoice->student)->payments;
                if ($payment && $payment->due_date && $invoice->month_year && preg_match('/^\d{2}_\d{4}$/', $invoice->month_year)) {
                    $monthYear = Carbon::createFromFormat('m_Y', $invoice->month_year);
                    $dueDate   = $monthYear->copy()->day((int) $payment->due_date);
                    return in_array($invoice->status, ['due', 'partially_paid']) && now()->toDateString() > $dueDate->toDateString();
                }
                return false;
            });

            $recordsFiltered = $allInvoices->count();
            $invoices        = $allInvoices->slice($start, $length)->values();
        }

        $data = $invoices->map(function ($invoice, $index) use ($start) {
            $status    = $invoice->status;
            $payment   = optional($invoice->student)->payments;
            $isOverdue = false;

            if ($payment && $payment->due_date && $invoice->month_year && preg_match('/^\d{2}_\d{4}$/', $invoice->month_year)) {
                $monthYear = Carbon::createFromFormat('m_Y', $invoice->month_year);
                $dueDate   = $monthYear->copy()->day((int) $payment->due_date);
                $isOverdue = in_array($status, ['due', 'partially_paid']) && now()->toDateString() > $dueDate->toDateString();
            }

            $billingMonth = '-';
            if (! empty($invoice->month_year) && preg_match('/^(\d{2})_(\d{4})$/', $invoice->month_year, $matches)) {
                $billingMonth = Carbon::create($matches[2], $matches[1], 1)->format('F Y');
            } elseif (empty($invoice->month_year) && $invoice->invoiceType?->type_name == 'Special Class Fee') {
                $billingMonth = 'One Time';
            }

            $dueDateStr = '-';
            if ($invoice->invoiceType?->type_name == 'Tuition Fee' && $payment) {
                $dueDateStr = ucfirst($payment->payment_style) . '-1/' . $payment->due_date;
            }

            $homeMobile = $invoice->student->mobileNumbers->where('number_type', 'home')->pluck('mobile_number')->implode(', ');

            $statusHtml = '';
            if ($status === 'due') {
                $statusHtml = $isOverdue ? '<span class="badge badge-danger rounded-pill">Overdue</span>' : '<span class="badge badge-warning rounded-pill">Due</span>';
            } elseif ($status === 'partially_paid') {
                $statusHtml = '<span class="badge badge-info rounded-pill">Partial</span>';
                if ($isOverdue) {
                    $statusHtml .= ' <span class="badge badge-danger rounded-pill ms-1">Overdue</span>';
                }

            }

            $lastComment  = $invoice->comments->first();

            return [
                'id'                => $invoice->id,
                'sl'                => $start + $index + 1,
                'invoice_number'    => $invoice->invoice_number,
                'comments_count'    => $invoice->comments_count ?? 0,
                'student_id'        => $invoice->student->id,
                'student_name'      => $invoice->student->name,
                'student_unique_id' => $invoice->student->student_unique_id,
                'mobile'            => $homeMobile,
                'invoice_type'      => $invoice->invoiceType?->type_name ?? '-',
                'billing_month'     => $billingMonth,
                'billing_month_raw' => $invoice->month_year,
                'total_amount'      => $invoice->total_amount,
                'amount_due'        => $invoice->amount_due,
                'due_date'          => $dueDateStr,
                'due_date_raw'      => $payment ? '1/' . $payment->due_date : '-',
                'status'            => $status,
                'status_html'       => $statusHtml,
                'status_text'       => ucfirst($status) . ($isOverdue ? ' (Overdue)' : ''),
                'is_overdue'        => $isOverdue,
                'created_at'        => $invoice->created_at->format('d-m-Y'),
                'created_at_time'   => $invoice->created_at->format('h:i:s A'),
                'last_comment'      => $lastComment ? $lastComment->comment : '',
                'last_comment_by'   => $lastComment && $lastComment->commentedBy ? $lastComment->commentedBy->name : '',
                'last_comment_at'   => $lastComment ? $lastComment->created_at->format('d M Y, h:i A') : '',
            ];
        });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    /**
     * Server-side DataTable for paid invoices
     */
    public function getPaidInvoicesAjax(Request $request)
    {
        if (! auth()->user()->can('invoices.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $branchId       = auth()->user()->branch_id;
        $filterBranchId = $request->get('branch_id', $branchId);

        if ($branchId != 0) {
            $filterBranchId = $branchId;
        }

        // DataTables parameters
        $draw        = intval($request->get('draw', 1));
        $start       = intval($request->get('start', 0));
        $length      = intval($request->get('length', 10));
        $search      = $request->get('search')['value'] ?? '';
        $orderColumn = $request->get('order')[0]['column'] ?? 0;
        $orderDir    = $request->get('order')[0]['dir'] ?? 'desc';

        $columns = ['id', 'invoice_number', 'student_name', 'invoice_type', 'total_amount', 'billing_month', 'due_date', 'status', 'last_comment', 'created_at'];
        $orderBy = $columns[$orderColumn] ?? 'id';

        $studentQuery = function ($query) use ($filterBranchId, $branchId) {
            $query->withoutTrashed();
            if ($branchId != 0) {
                $query->where('branch_id', $branchId);
            } elseif ($filterBranchId) {
                $query->where('branch_id', $filterBranchId);
            }
        };

        $query = PaymentInvoice::with([
            'student:id,name,student_unique_id,branch_id',
            'student.payments:id,student_id,payment_style,due_date,tuition_fee',
            'student.mobileNumbers:id,student_id,mobile_number,number_type',
            'invoiceType:id,type_name',
            'comments' => fn($q) => $q->with('commentedBy:id,name')->latest()->limit(1),
        ])
            ->withCount('comments')
            ->where('status', 'paid')
            ->whereHas('student', $studentQuery);

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('student', fn($sq) => $sq->where('name', 'like', "%{$search}%")->orWhere('student_unique_id', 'like', "%{$search}%"))
                    ->orWhereHas('student.mobileNumbers', fn($mq) => $mq->where('mobile_number', 'like', "%{$search}%"));
            });
        }

        // Apply custom filters
        if ($invoiceType = $request->get('invoice_type')) {
            $typeName = str_replace('ucms_', '', $invoiceType);
            $query->whereHas('invoiceType', fn($q) => $q->where('type_name', $typeName));
        }

        if ($dueDate = $request->get('due_date')) {
            $parts = explode('/', $dueDate);
            if (count($parts) === 2) {
                $query->whereHas('student.payments', fn($q) => $q->where('due_date', $parts[1]));
            }
        }

        if ($billingMonth = $request->get('billing_month')) {
            $monthYear = str_replace('P_', '', $billingMonth);
            $query->where('month_year', $monthYear);
        }

        $recordsTotal    = (clone $query)->count();
        $recordsFiltered = $recordsTotal;

        // Order and paginate
        if (in_array($orderBy, ['id', 'invoice_number', 'total_amount', 'created_at'])) {
            $query->orderBy($orderBy, $orderDir);
        } else {
            $query->orderBy('id', 'desc');
        }

        $invoices = $query->skip($start)->take($length)->get();

        $data = $invoices->map(function ($invoice, $index) use ($start) {
            $payment = optional($invoice->student)->payments;

            $billingMonth = '-';
            if (! empty($invoice->month_year) && preg_match('/^(\d{2})_(\d{4})$/', $invoice->month_year, $matches)) {
                $billingMonth = Carbon::create($matches[2], $matches[1], 1)->format('F Y');
            }

            $dueDateStr = '-';
            if ($invoice->invoiceType?->type_name == 'Tuition Fee' && $payment) {
                $dueDateStr = ucfirst($payment->payment_style) . '-1/' . $payment->due_date;
            }

            $homeMobile = $invoice->student->mobileNumbers->where('number_type', 'home')->pluck('mobile_number')->implode(', ');

            $lastComment = $invoice->comments->first();

            return [
                'id'                => $invoice->id,
                'sl'                => $start + $index + 1,
                'invoice_number'    => $invoice->invoice_number,
                'comments_count'    => $invoice->comments_count ?? 0,
                'student_id'        => $invoice->student->id,
                'student_name'      => $invoice->student->name,
                'student_unique_id' => $invoice->student->student_unique_id,
                'mobile'            => $homeMobile,
                'invoice_type'      => $invoice->invoiceType?->type_name ?? '-',
                'billing_month'     => $billingMonth,
                'billing_month_raw' => $invoice->month_year,
                'total_amount'      => $invoice->total_amount,
                'due_date'          => $dueDateStr,
                'due_date_raw'      => $payment ? '1/' . $payment->due_date : '-',
                'status'            => 'Paid',
                'created_at'        => $invoice->created_at->format('d-m-Y'),
                'created_at_time'   => $invoice->created_at->format('h:i:s A'),
                'last_comment'      => $lastComment ? $lastComment->comment : '',
                'last_comment_by'   => $lastComment && $lastComment->commentedBy ? $lastComment->commentedBy->name : '',
                'last_comment_at'   => $lastComment ? $lastComment->created_at->format('d M Y, h:i A') : '',
            ];
        });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    /**
     * Export all data for a table (bypasses pagination)
     */
    public function exportInvoicesAjax(Request $request)
    {
        if (! auth()->user()->can('invoices.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $type           = $request->get('type', 'due'); // 'due' or 'paid'
        $branchId       = auth()->user()->branch_id;
        $filterBranchId = $request->get('branch_id', $branchId);

        if ($branchId != 0) {
            $filterBranchId = $branchId;
        }

        $studentQuery = function ($query) use ($filterBranchId, $branchId) {
            $query->withoutTrashed();
            if ($branchId != 0) {
                $query->where('branch_id', $branchId);
            } elseif ($filterBranchId) {
                $query->where('branch_id', $filterBranchId);
            }
        };

        $query = PaymentInvoice::with([
            'student:id,name,student_unique_id,branch_id',
            'student.payments:id,student_id,payment_style,due_date,tuition_fee',
            'student.mobileNumbers:id,student_id,mobile_number,number_type',
            'invoiceType:id,type_name',
            'comments' => fn($q) => $q->with('commentedBy:id,name')->latest()->limit(1),
        ])
            ->whereHas('student', $studentQuery);

        if ($type === 'paid') {
            $query->where('status', 'paid');
        } else {
            $query->where('status', '!=', 'paid');
        }

        // Apply filters
        if ($invoiceType = $request->get('invoice_type')) {
            $typeName = str_replace('ucms_', '', $invoiceType);
            $query->whereHas('invoiceType', fn($q) => $q->where('type_name', $typeName));
        }

        if ($dueDate = $request->get('due_date')) {
            $parts = explode('/', $dueDate);
            if (count($parts) === 2) {
                $query->whereHas('student.payments', fn($q) => $q->where('due_date', $parts[1]));
            }
        }

        if ($billingMonth = $request->get('billing_month')) {
            $prefix    = $type === 'paid' ? 'P_' : 'D_';
            $monthYear = str_replace($prefix, '', $billingMonth);
            $query->where('month_year', $monthYear);
        }

        if ($status = $request->get('status')) {
            if ($status === 'I_due') {
                $query->where('status', 'due');
            } elseif ($status === 'I_partial') {
                $query->where('status', 'partially_paid');
            }
        }

        $invoices = $query->orderBy('id', 'desc')->get();

        // Filter overdue if needed
        if ($request->get('status') === 'I_overdue') {
            $invoices = $invoices->filter(function ($invoice) {
                $payment = optional($invoice->student)->payments;
                if ($payment && $payment->due_date && $invoice->month_year && preg_match('/^\d{2}_\d{4}$/', $invoice->month_year)) {
                    $monthYear = Carbon::createFromFormat('m_Y', $invoice->month_year);
                    $dueDate   = $monthYear->copy()->day((int) $payment->due_date);
                    return in_array($invoice->status, ['due', 'partially_paid']) && now()->toDateString() > $dueDate->toDateString();
                }
                return false;
            })->values();
        }

        $data = $invoices->map(function ($invoice, $index) use ($type) {
            $status    = $invoice->status;
            $payment   = optional($invoice->student)->payments;
            $isOverdue = false;

            if ($type === 'due' && $payment && $payment->due_date && $invoice->month_year && preg_match('/^\d{2}_\d{4}$/', $invoice->month_year)) {
                $monthYear = Carbon::createFromFormat('m_Y', $invoice->month_year);
                $dueDate   = $monthYear->copy()->day((int) $payment->due_date);
                $isOverdue = in_array($status, ['due', 'partially_paid']) && now()->toDateString() > $dueDate->toDateString();
            }

            $billingMonth = '-';
            if (! empty($invoice->month_year) && preg_match('/^(\d{2})_(\d{4})$/', $invoice->month_year, $matches)) {
                $billingMonth = Carbon::create($matches[2], $matches[1], 1)->format('F Y');
            } elseif (empty($invoice->month_year) && $invoice->invoiceType?->type_name == 'Special Class Fee') {
                $billingMonth = 'One Time';
            }

            $dueDateStr = '-';
            if ($invoice->invoiceType?->type_name == 'Tuition Fee' && $payment) {
                $dueDateStr = ucfirst($payment->payment_style) . '-1/' . $payment->due_date;
            }

            $homeMobile  = $invoice->student->mobileNumbers->where('number_type', 'home')->pluck('mobile_number')->implode(', ');
            $lastComment = $invoice->comments->first();

            return [
                'sl'                => $index + 1,
                'invoice_number'    => $invoice->invoice_number,
                'student_name'      => $invoice->student->name,
                'student_unique_id' => $invoice->student->student_unique_id,
                'mobile'            => $homeMobile,
                'invoice_type'      => $invoice->invoiceType?->type_name ?? '-',
                'billing_month'     => $billingMonth,
                'total_amount'      => $invoice->total_amount,
                'amount_due'        => $invoice->amount_due ?? 0,
                'due_date'          => $dueDateStr,
                'status_text'       => $type === 'paid' ? 'Paid' : (ucfirst($status) . ($isOverdue ? ' (Overdue)' : '')),
                'last_comment'      => $lastComment ? $lastComment->comment : '',
                'last_comment_by'   => $lastComment && $lastComment->commentedBy ? $lastComment->commentedBy->name : '',
                'last_comment_at'   => $lastComment ? $lastComment->created_at->format('d M Y, h:i A') : '',
                'created_at'        => $invoice->created_at->format('d-m-Y'),
                'created_at_time'   => $invoice->created_at->format('h:i:s A'),
            ];
        });

        return response()->json(['data' => $data]);
    }

    private function getFilteredMonths(string $operator, string $value)
    {
        return PaymentInvoice::where('status', $operator, $value)
            ->whereNotNull('month_year')
            ->pluck('month_year')
            ->filter(fn($month) => preg_match('/^\d{2}_\d{4}$/', $month) && Carbon::hasFormat($month, 'm_Y'))
            ->unique()
            ->sortBy(fn($month) => Carbon::createFromFormat('m_Y', $month))
            ->values();
    }

    public function create()
    {
        return redirect()->back();
    }

    public function store(Request $request)
    {
        $rules = [
            'invoice_student' => 'required|exists:students,id',
            'invoice_type'    => 'required|exists:payment_invoice_types,id',
            'invoice_amount'  => 'required|numeric|min:50',
        ];

        $invoiceType     = PaymentInvoiceType::findOrFail($request->invoice_type);
        $invoiceTypeName = strtolower(str_replace(' ', '_', $invoiceType->type_name));

        if ($invoiceTypeName === 'tuition_fee') {
            $rules['invoice_month_year'] = 'required|string';
            $validatedMonthYear          = $request->invoice_month_year;
        } else {
            $validatedMonthYear = null;
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $student = Student::with(['class', 'branch'])->findOrFail($request->invoice_student);
        $classId = optional($student->class)->id;

        if ($invoiceTypeName === 'tuition_fee' && PaymentInvoice::where('student_id', $student->id)->where('invoice_type_id', $invoiceType->id)->where('month_year', $validatedMonthYear)->exists()) {
            $message = 'A tuition fee invoice for ' . $student->name . ' of this month already exists';
            return $request->expectsJson() ? response()->json(['success' => false, 'message' => $message], 422) : back()->with('warning', $message);
        }

        if ($invoiceTypeName === 'sheet_fee') {
            $alreadyPaid = SheetPayment::where('student_id', $student->id)->whereHas('sheet', fn($q) => $q->where('class_id', $classId))->exists();
            if ($alreadyPaid) {
                $message = 'Sheet invoice already exists for ' . $student->name;
                return $request->expectsJson() ? response()->json(['success' => false, 'message' => $message], 422) : back()->with('warning', $message);
            }
        }

        if ($invoiceTypeName === 'admission_fee' && PaymentInvoice::where('student_id', $student->id)->where('invoice_type_id', $invoiceType->id)->where('month_year', $validatedMonthYear)->exists()) {
            $message = 'Admission fee invoice already exists for ' . $student->name;
            return $request->expectsJson() ? response()->json(['success' => false, 'message' => $message], 422) : back()->with('warning', $message);
        }

        $yearSuffix = now()->format('y');
        $month      = now()->format('m');
        $prefix     = $student->branch->branch_prefix;

        $lastInvoice = PaymentInvoice::withTrashed()
            ->where('invoice_number', 'like', "{$prefix}{$yearSuffix}{$month}_%")
            ->latest('invoice_number')
            ->first();

        $nextSequence  = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, strrpos($lastInvoice->invoice_number, '_') + 1)) + 1 : 1001;
        $invoiceNumber = "{$prefix}{$yearSuffix}{$month}_{$nextSequence}";

        $invoice = PaymentInvoice::create([
            'invoice_number'  => $invoiceNumber,
            'student_id'      => $student->id,
            'invoice_type_id' => $invoiceType->id,
            'total_amount'    => $request->invoice_amount,
            'amount_due'      => $request->invoice_amount,
            'month_year'      => $validatedMonthYear,
            'created_by'      => auth()->id(),
        ]);

        if ($invoiceTypeName === 'sheet_fee') {
            $sheet = optional($student->class)->sheet;
            if ($sheet) {
                SheetPayment::create(['sheet_id' => $sheet->id, 'invoice_id' => $invoice->id, 'student_id' => $student->id]);
            }
        }

        $mobile   = $invoice->student->mobileNumbers->where('number_type', 'sms')->first()->mobile_number ?? null;
        $smsTypes = ['tuition_fee', 'model_test_fee', 'exam_fee', 'sheet_fee', 'book_fee', 'diary_fee', 'others_fee', 'admission_fee'];

        if ($mobile && in_array($invoiceTypeName, $smsTypes)) {
            send_auto_sms("{$invoiceTypeName}_invoice_created", $mobile, [
                'student_name' => $invoice->student->name,
                'month_year'   => $invoice->month_year ? Carbon::createFromDate(explode('_', $invoice->month_year)[1], explode('_', $invoice->month_year)[0])->format('F') : now()->format('F'),
                'amount'       => $invoice->total_amount,
                'invoice_no'   => $invoice->invoice_number,
                'due_date'     => $this->ordinal($invoice->student->payments->due_date) . ' ' . now()->format('F'),
            ]);
        }

        if ($invoiceTypeName === 'tuition_fee') {
            $father = $invoice->student->guardians->where('relationship', 'father')->first();
            $mobile = $father->mobile_number ?? null;
            if ($mobile) {
                send_auto_sms('guardian_tuition_fee_invoice_created', $mobile, [
                    'student_name' => $invoice->student->name,
                    'month_year'   => $invoice->month_year ? Carbon::createFromDate(explode('_', $invoice->month_year)[1], explode('_', $invoice->month_year)[0])->format('F') : now()->format('F'),
                    'amount'       => $invoice->total_amount,
                    'invoice_no'   => $invoice->invoice_number,
                    'due_date'     => $this->ordinal($invoice->student->payments->due_date) . ' ' . now()->format('F'),
                ]);
            }
        }

        clearUCMSCaches();

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Invoice created successfully.', 'invoice' => $invoice]);
        }

        return redirect()->back()->with('success', 'Invoice created successfully.');
    }

    private function ordinal(int $number): string
    {
        if (! in_array($number % 100, [11, 12, 13])) {
            switch ($number % 10) {
                case 1:return $number . 'st';
                case 2:return $number . 'nd';
                case 3:return $number . 'rd';
            }
        }
        return $number . 'th';
    }

    public function show(string $id)
    {
        if (! auth()->user()->can('invoices.view')) {
            return redirect()->back()->with('warning', 'No permission to view invoices.');
        }

        $invoice = PaymentInvoice::with(['student', 'invoiceType'])->find($id);

        if (! $invoice || $invoice->student === null || $invoice->student->trashed()) {
            return redirect()->route('invoices.index')->with('warning', 'Invoice not found');
        }

        if (auth()->user()->branch_id != 0 && $invoice->student->branch_id != auth()->user()->branch_id) {
            return redirect()->route('invoices.index')->with('warning', 'Invoice not found.');
        }

        $students = Student::active()
            ->when(auth()->user()->branch_id != 0, fn($query) => $query->where('branch_id', auth()->user()->branch_id))
            ->orderBy('student_unique_id')
            ->get();

        return view('invoices.view', compact('invoice', 'students'));
    }

    public function edit(string $id)
    {
        return redirect()->back();
    }

    public function update(Request $request, string $id)
    {
        $request->validate(['invoice_amount_edit' => 'required|numeric|min:50']);

        $invoice = PaymentInvoice::findOrFail($id);
        $invoice->update(['total_amount' => $request->invoice_amount_edit, 'amount_due' => $request->invoice_amount_edit]);

        clearUCMSCaches();

        return response()->json(['success' => true, 'message' => 'Invoice updated successfully']);
    }

    public function destroy(string $id)
    {
        $invoice = PaymentInvoice::find($id);

        if (! $invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        if ($invoice->status === 'paid' || $invoice->status === 'partially_paid') {
            return response()->json(['error' => 'Cannot delete paid invoice'], 422);
        }

        $invoice->update(['deleted_by' => auth()->id()]);

        if ($invoice->sheetPayment) {
            $invoice->sheetPayment->delete();
        }

        $invoice->delete();
        clearUCMSCaches();

        return response()->json(['success' => true]);
    }

    public function viewAjax(PaymentInvoice $invoice)
    {
        $invoice->load(['invoiceType:id,type_name', 'student']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                => $invoice->id,
                'student_id'        => $invoice->student_id,
                'student_name'      => $invoice->student?->name ?? 'Unknown',
                'student_unique_id' => $invoice->student?->student_unique_id ?? '',
                'invoice_number'    => $invoice->invoice_number,
                'total_amount'      => $invoice->total_amount,
                'month_year'        => $invoice->month_year,
                'invoice_type_id'   => $invoice->invoice_type_id,
                'invoice_type_name' => $invoice->invoiceType?->type_name ?? '',
            ],
        ]);
    }

    public function getDueInvoices($studentId)
    {
        $dueInvoices = PaymentInvoice::where('student_id', $studentId)
            ->where('status', '!=', 'paid')
            ->with('invoiceType:id,type_name')
            ->latest('id')
            ->get(['id', 'invoice_number', 'total_amount', 'amount_due', 'month_year', 'invoice_type_id'])
            ->map(fn($invoice) => [
                'id'             => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total_amount'   => $invoice->total_amount,
                'amount_due'     => $invoice->amount_due,
                'month_year'     => $invoice->month_year,
                'invoice_type'   => $invoice->invoiceType?->type_name,
            ]);

        return response()->json($dueInvoices);
    }
}
