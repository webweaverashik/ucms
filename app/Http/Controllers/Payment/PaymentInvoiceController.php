<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentInvoiceType;
use App\Models\Sheet\SheetPayment;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PaymentInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (! auth()->user()->can('invoices.view')) {
            return redirect()->back()->with('warning', 'No permission to view invoices.');
        }

        $branchId = auth()->user()->branch_id;
        $cacheKey = "invoices_index_branch_{$branchId}";

        $data = Cache::remember($cacheKey, now()->addHours(1), function () use ($branchId) {
            // Common student constraint
            $studentQuery = function ($query) use ($branchId) {
                if ($branchId != 0) {
                    $query->where('branch_id', $branchId);
                }
                $query->where(function ($q) {
                    $q->whereHas('studentActivation', function ($q2) {
                        $q2->where('active_status', 'active');
                    })->orWhereNull('student_activation_id');
                });
            };

            // Unpaid Invoices
            $unpaid_invoices = PaymentInvoice::with(['student:id,name,student_unique_id,student_activation_id', 'student.studentActivation:id,active_status', 'student.payments:id,student_id,payment_style,due_date,tuition_fee', 'invoiceType:id,type_name'])
                ->withCount('paymentTransactions')
                ->where('status', '!=', 'paid')
                ->whereHas('student', $studentQuery)
                ->latest('id')
                ->get();

            // Paid Invoices
            $paid_invoices = PaymentInvoice::with(['student:id,name,student_unique_id', 'student.payments:id,student_id,payment_style,due_date,tuition_fee', 'invoiceType:id,type_name'])
                ->where('status', 'paid')
                ->whereHas('student', function ($query) use ($branchId) {
                    if ($branchId != 0) {
                        $query->where('branch_id', $branchId);
                    }
                })
                ->latest('id')
                ->get();

            // Due & Paid Months
            $dueMonths  = $this->getFilteredMonths('!=', 'paid');
            $paidMonths = $this->getFilteredMonths('=', 'paid');

            // Students for modal
            $students = Student::with(['studentActivation:id,active_status', 'payments:id,student_id,payment_style,due_date,tuition_fee'])
                ->when($branchId != 0, function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->where(function ($query) {
                    $query->whereNotNull('student_activation_id')->orWhereHas('studentActivation', function ($q) {
                        $q->where('active_status', 'active');
                    });
                })
                ->whereHas('class', function ($query) {
                    $query->where('is_active', true);
                })
                ->latest('student_unique_id')
                ->select('id', 'name', 'student_unique_id', 'student_activation_id', 'branch_id')
                ->get();

            $invoice_types = PaymentInvoiceType::select('id', 'type_name')->get();

            return compact('unpaid_invoices', 'paid_invoices', 'dueMonths', 'paidMonths', 'students', 'invoice_types');
        });

        return view('invoices.index', $data);
    }

    private function getFilteredMonths(string $operator, string $value)
    {
        return PaymentInvoice::where('status', $operator, $value)
            ->whereNotNull('month_year')
            ->pluck('month_year')
            ->filter(function ($month) {
                return preg_match('/^\d{2}_\d{4}$/', $month) && Carbon::hasFormat($month, 'm_Y');
            })
            ->unique()
            ->sortBy(function ($month) {
                return Carbon::createFromFormat('m_Y', $month);
            })
            ->values();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->back();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'invoice_student' => 'required|exists:students,id',
            'invoice_type'    => 'required|exists:payment_invoice_types,id',
            'invoice_amount'  => 'required|numeric|min:50',
        ];

        // Get the invoice type
        $invoiceType     = PaymentInvoiceType::findOrFail($request->invoice_type);
        $invoiceTypeName = strtolower(str_replace(' ', '_', $invoiceType->type_name));

        // Tuition fees require month/year
        if ($invoiceTypeName === 'tuition_fee') {
            $rules['invoice_month_year'] = 'required|string';
            $validatedMonthYear          = $request->invoice_month_year;
        } else {
            $validatedMonthYear = null;
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Validation failed.',
                        'errors'  => $validator->errors(),
                    ],
                    422,
                );
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Fetch student with class and branch
        $student = Student::with(['class', 'branch'])->findOrFail($request->invoice_student);
        $classId = optional($student->class)->id;

        // ✅ Prevent duplicate tuition_fee invoice (same student + same month_year)
        if ($invoiceTypeName === 'tuition_fee' && PaymentInvoice::where('student_id', $student->id)->where('invoice_type_id', $invoiceType->id)->where('month_year', $validatedMonthYear)->exists()) {
            $message = 'Tuition fee invoice already exists for this student and month.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('warning', $message);
        }

        // ✅ Prevent duplicate sheet_fee for same student + class
        if ($invoiceTypeName === 'sheet_fee') {
            $alreadyPaid = SheetPayment::where('student_id', $student->id)->whereHas('sheet', fn($q) => $q->where('class_id', $classId))->exists();
            if ($alreadyPaid) {
                $message = 'Sheet invoice already exists for this student.';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                return back()->with('warning', $message);
            }
        }

        // ✅ Generate unique invoice number
        $yearSuffix = now()->format('y');
        $month      = now()->format('m');
        $prefix     = $student->branch->branch_prefix;

        $lastInvoice = PaymentInvoice::withTrashed()
            ->where('invoice_number', 'like', "{$prefix}{$yearSuffix}{$month}_%")
            ->latest('invoice_number')
            ->first();

        $nextSequence = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, strrpos($lastInvoice->invoice_number, '_') + 1)) + 1 : 1001;

        $invoiceNumber = "{$prefix}{$yearSuffix}{$month}_{$nextSequence}";

        // ✅ Create invoice
        $invoice = PaymentInvoice::create([
            'invoice_number'  => $invoiceNumber,
            'student_id'      => $student->id,
            'invoice_type_id' => $invoiceType->id,
            'total_amount'    => $request->invoice_amount,
            'amount_due'      => $request->invoice_amount,
            'month_year'      => $validatedMonthYear,
            'created_by'      => auth()->id(),
        ]);

        // ✅ Create SheetPayment after invoice is stored
        if ($invoiceTypeName === 'sheet_fee') {
            $sheet = optional($student->class)->sheet;
            if ($sheet) {
                SheetPayment::create([
                    'sheet_id'   => $sheet->id,
                    'invoice_id' => $invoice->id,
                    'student_id' => $student->id,
                ]);
            }
        }

        // AutoSMS for invoice created
        $mobile   = $invoice->student->mobileNumbers->where('number_type', 'sms')->first()->mobile_number;
        $smsTypes = ['tuition_fee', 'model_test_fee', 'exam_fee', 'sheet_fee', 'book_fee', 'diary_fee', 'others_fee', 'admission_fee'];

        if (in_array($invoiceTypeName, $smsTypes)) {
            send_auto_sms("{$invoiceTypeName}_invoice_created", $mobile, [
                'student_name' => $invoice->student->name,
                'month_year'   => $invoice->month_year
                    ? Carbon::createFromDate(
                    explode('_', $invoice->month_year)[1], // year
                    explode('_', $invoice->month_year)[0], // month
                )->format('F')
                    : now()->format('F'),
                'amount'       => $invoice->total_amount,
                'invoice_no'   => $invoice->invoice_number,
                'due_date'     => $this->ordinal($invoice->student->payments->due_date) . ' ' . now()->format('F'),
            ]);
        }

        // AutoSMS to guardian
        if ($invoiceTypeName === 'tuition_fee') {
            $father = $invoice->student->guardians->where('relationship', 'father')->first();
            $mobile = $father->mobile_number ?? null;
            if ($mobile) {
                send_auto_sms('guardian_tuition_fee_invoice_created', $mobile, [
                    'student_name' => $invoice->student->name,
                    'month_year'   => $invoice->month_year
                        ? Carbon::createFromDate(
                        explode('_', $invoice->month_year)[1], // year
                        explode('_', $invoice->month_year)[0], // month
                    )->format('F')
                        : now()->format('F'),
                    'amount'       => $invoice->total_amount,
                    'invoice_no'   => $invoice->invoice_number,
                    'due_date'     => $this->ordinal($invoice->student->payments->due_date) . ' ' . now()->format('F'),
                ]);
            }
        }

        // Clear the cache
        clearUCMSCaches();

        // Return JSON response for AJAX requests
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully.',
                'invoice' => $invoice,
            ]);
        }

        return redirect()->back()->with('success', 'Invoice created successfully.');
    }

    /**
     * Convert number to ordinal (1st, 2nd, 3rd, etc.)
     */
    private function ordinal(int $number): string
    {
        if (! in_array($number % 100, [11, 12, 13])) {
            switch ($number % 10) {
                case 1:
                    return $number . 'st';
                case 2:
                    return $number . 'nd';
                case 3:
                    return $number . 'rd';
            }
        }
        return $number . 'th';
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (! auth()->user()->can('invoices.view')) {
            return redirect()->back()->with('warning', 'No permission to view invoices.');
        }

        $invoice = PaymentInvoice::with(['student', 'invoiceType'])->find($id);

        if (! $invoice || $invoice->student === null || $invoice->student->trashed()) {
            return redirect()->route('invoices.index')->with('warning', 'Invoice not found');
        }

        // Restrict view based on branch unless admin
        if (auth()->user()->branch_id != 0 && $invoice->student->branch_id != auth()->user()->branch_id) {
            return redirect()->route('invoices.index')->with('warning', 'Invoice not found.');
        }

        $students = Student::when(auth()->user()->branch_id != 0, function ($query) {
            $query->where('branch_id', auth()->user()->branch_id);
        })
            ->where(function ($query) {
                $query->whereNull('student_activation_id')->orWhereHas('studentActivation', function ($q) {
                    $q->where('active_status', 'active');
                });
            })
            ->orderBy('student_unique_id')
            ->get();

        return view('invoices.view', compact('invoice', 'students'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'invoice_amount_edit' => 'required|numeric|min:50',
        ]);

        $invoice = PaymentInvoice::findOrFail($id);

        // Update the invoice record
        $invoice->update([
            'total_amount' => $request->invoice_amount_edit,
            'amount_due'   => $request->invoice_amount_edit,
        ]);

        // Clear the cache
        clearUCMSCaches();

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $invoice = PaymentInvoice::find($id);

        if (! $invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        if ($invoice->status === 'paid' || $invoice->status === 'partially_paid') {
            return response()->json(['error' => 'Cannot delete paid invoice'], 422);
        }

        // Mark who deleted it
        $invoice->update([
            'deleted_by' => auth()->id(),
        ]);

        // Delete related sheet payment, if exists
        if ($invoice->sheetPayment) {
            $invoice->sheetPayment->delete();
        }

        // Soft delete the invoice
        $invoice->delete();

        // Clear the cache
        clearUCMSCaches();

        return response()->json(['success' => true]);
    }

    /**
     * Invoice edit modal ajax
     */
    public function viewAjax(PaymentInvoice $invoice)
    {
        $invoice->load('invoiceType:id,type_name');

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                => $invoice->id,
                'student_id'        => $invoice->student_id,
                'invoice_number'    => $invoice->invoice_number,
                'total_amount'      => $invoice->total_amount,
                'month_year'        => $invoice->month_year,
                'invoice_type_id'   => $invoice->invoice_type_id,
                'invoice_type_name' => $invoice->invoiceType?->type_name,
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
            ->map(function ($invoice) {
                return [
                    'id'             => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'total_amount'   => $invoice->total_amount,
                    'amount_due'     => $invoice->amount_due,
                    'month_year'     => $invoice->month_year,
                    'invoice_type'   => $invoice->invoiceType?->type_name,
                ];
            });

        return response()->json($dueInvoices);
    }
}