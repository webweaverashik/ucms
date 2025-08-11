<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentInvoice;
use App\Models\Sheet\SheetPayment;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        $data = Cache::remember($cacheKey, now()->addHours(6), function () use ($branchId) {

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
            $unpaid_invoices = PaymentInvoice::with([
                'student:id,name,student_unique_id,student_activation_id',
                'student.studentActivation:id,active_status',
                'student.payments:id,student_id,payment_style,due_date,tuition_fee',
            ])
                ->withCount('paymentTransactions')
                ->where('status', '!=', 'paid')
                ->whereHas('student', $studentQuery)
                ->latest('id')
                ->get();

            // Paid Invoices
            $paid_invoices = PaymentInvoice::with([
                'student:id,name,student_unique_id',
                'student.payments:id,student_id,payment_style,due_date,tuition_fee',
            ])
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
            $students = Student::with([
                'studentActivation:id,active_status',
                'payments:id,student_id,payment_style,due_date,tuition_fee',
            ])
                ->when($branchId != 0, function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->where(function ($query) {
                    $query->whereNotNull('student_activation_id')
                        ->orWhereHas('studentActivation', function ($q) {
                            $q->where('active_status', 'active');
                        });
                })
                ->orderBy('student_unique_id')
                ->select('id', 'name', 'student_unique_id', 'student_activation_id', 'branch_id')
                ->get();

            return compact('unpaid_invoices', 'paid_invoices', 'dueMonths', 'paidMonths', 'students');
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
        return redirect()->back()->with('warning', 'Not Allowed');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'invoice_student' => 'required|exists:students,id',
            'invoice_type'    => 'required|in:tuition_fee,exam_fee,model_test_fee,others_fee,sheet_fee,diary_fee,book_fee',
            'invoice_amount'  => 'required|numeric|min:50',
        ];

        // Tuition fees require month/year
        if ($request->invoice_type === 'tuition_fee') {
            $rules['invoice_month_year'] = 'required|string';
            $validatedMonthYear          = $request->invoice_month_year;
        } else {
            $validatedMonthYear = null;
        }

        $request->validate($rules);

        // Fetch student with class and branch
        $student = Student::with(['class', 'branch'])->findOrFail($request->invoice_student);
        $classId = optional($student->class)->id;

        // ✅ Prevent duplicate tuition_fee invoice (same student + same month_year)
        if (
            $request->invoice_type === 'tuition_fee' &&
            PaymentInvoice::where('student_id', $student->id)
            ->where('invoice_type', 'tuition_fee')
            ->where('month_year', $validatedMonthYear)
            ->exists()
        ) {
            return back()->with('warning', 'Tuition fee invoice already exists for this student and month.');
        }

        // ✅ Prevent duplicate sheet_fee for same student + class
        if ($request->invoice_type === 'sheet_fee') {
            $alreadyPaid = SheetPayment::where('student_id', $student->id)
                ->whereHas('sheet', fn($q) => $q->where('class_id', $classId))
                ->exists();

            if ($alreadyPaid) {
                return back()->with('warning', 'Sheet invoice already exists for this student.');
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

        $nextSequence = $lastInvoice
        ? ((int) substr($lastInvoice->invoice_number, strrpos($lastInvoice->invoice_number, '_') + 1)) + 1
        : 1001;

        $invoiceNumber = "{$prefix}{$yearSuffix}{$month}_{$nextSequence}";

        // ✅ Create invoice
        $invoice = PaymentInvoice::create([
            'invoice_number' => $invoiceNumber,
            'student_id'     => $student->id,
            'invoice_type'   => $request->invoice_type,
            'total_amount'   => $request->invoice_amount,
            'amount_due'     => $request->invoice_amount,
            'month_year'     => $validatedMonthYear,
            'created_by'     => auth()->id(),
        ]);

        // ✅ Create SheetPayment after invoice is stored
        if ($request->invoice_type === 'sheet_fee') {
            $sheet = optional($student->class)->sheet;
            if ($sheet) {
                SheetPayment::create([
                    'sheet_id'   => $sheet->id,
                    'invoice_id' => $invoice->id,
                    'student_id' => $student->id,
                ]);
            }
        }

        // Clear the cache
        clearUCMSCaches();

        return redirect()->back()->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if (! auth()->user()->can('invoices.view')) {
            return redirect()->back()->with('warning', 'No permission to view invoices.');
        }

        $invoice = PaymentInvoice::with('student')->find($id);

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
                $query->whereNull('student_activation_id')
                    ->orWhereHas('studentActivation', function ($q) {
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
        return redirect()->back()->with('warning', 'Not Allowed');
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

        // Update the guardian record
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

        // Optional: check if current user belongs to the same branch
        // if ($invoice->student->branch_id !== auth()->user()->branch_id) {
        //     return response()->json(['error' => 'Unauthorized Access'], 403);
        // }

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
     * invoice edit modal ajax
     */
    public function viewAjax(PaymentInvoice $invoice)
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $invoice->id,
                'student_id'     => $invoice->student_id,
                'invoice_number' => $invoice->invoice_number,
                'total_amount'   => $invoice->total_amount,
                'month_year'     => $invoice->month_year,
                'invoice_type'   => $invoice->invoice_type,
            ],
        ]);
    }

    public function getDueInvoices($studentId)
    {
        $dueInvoices = PaymentInvoice::where('student_id', $studentId)
            ->where('status', '!=', 'paid')
            ->latest('id')
            ->get(['id', 'invoice_number', 'total_amount', 'amount_due', 'month_year', 'invoice_type'])
            ->map(function ($invoice) {
                $invoice->total_amount = $invoice->total_amount;
                $invoice->amount_due   = $invoice->amount_due;
                return $invoice;
            });

        return response()->json($dueInvoices);
    }
}
