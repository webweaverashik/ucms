<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentInvoice;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $unpaid_invoices = PaymentInvoice::where('status', '!=', 'paid')
            ->whereHas('student', function ($query) {
                $query
                    ->whereNull('deleted_at')
                    ->where('branch_id', auth()->user()->branch_id)
                    ->where(function ($q) {
                        $q->whereHas('studentActivation', function ($q2) {
                            $q2->where('active_status', 'active');
                        })->orWhereNull('student_activation_id');
                    });
            })
            ->withoutTrashed()
            ->orderBy('id', 'desc')
            ->get();

        $paid_invoices = PaymentInvoice::where('status', 'paid')
            ->whereHas('student', function ($query) {
                $query->whereNull('deleted_at')->where(
                    'branch_id',
                    auth()->user()->branch_id,
                    /*->where(function ($q) {
                        $q->whereHas('studentActivation', function ($q2) {
                            $q2->where('active_status', 'active');
                        })->orWhereNull('student_activation_id');
                    })*/
                );
            })
            ->withoutTrashed()
            ->orderBy('id', 'desc')
            ->get();

        $dueMonths = PaymentInvoice::where('status', '!=', 'paid')
            ->whereNotNull('month_year') // avoid nulls
            ->pluck('month_year')
            ->filter(function ($value) {
                // ensure format matches 'm_Y' and is parseable
                return preg_match('/^\d{2}_\d{4}$/', $value) && Carbon::hasFormat($value, 'm_Y');
            })
            ->unique()
            ->sortBy(function ($value) {
                return Carbon::createFromFormat('m_Y', $value);
            })
            ->values();

        $paidMonths = PaymentInvoice::where('status', 'paid')
            ->whereNotNull('month_year') // Filter out nulls
            ->pluck('month_year')
            ->filter(function ($value) {
                // Ensure the value is in correct format like "01_2025"
                return preg_match('/^\d{2}_\d{4}$/', $value) && Carbon::hasFormat($value, 'm_Y');
            })
            ->unique()
            ->sortBy(function ($value) {
                return Carbon::createFromFormat('m_Y', $value);
            })
            ->values();

        if (auth()->user()->branch_id != 0) {
            $students = Student::where('branch_id', auth()->user()->branch_id)
                ->where(function ($query) {
                    $query->whereNull('student_activation_id')->orWhereHas('studentActivation', function ($q) {
                        $q->where('active_status', 'active');
                    });
                })
                ->withoutTrashed()
                ->orderby('student_unique_id', 'asc')
                ->get();
        } else {
            $students = Student::where(function ($query) {
                $query->whereNull('student_activation_id')->orWhereHas('studentActivation', function ($q) {
                    $q->where('active_status', 'active');
                });
            })
                ->withoutTrashed()
                ->orderby('student_unique_id', 'asc')
                ->get();
        }

        return view('invoices.index', compact('unpaid_invoices', 'paid_invoices', 'dueMonths', 'paidMonths', 'students'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return $request;

        $rules = [
            'invoice_student' => 'required|exists:students,id',
            'invoice_type'    => 'required|in:tuition_fee,exam_fee,model_test_fee,others_fee',
            'invoice_amount'  => 'required|numeric|min:0',
        ];

        // Conditionally apply rule for invoice_month_year
        if ($request->invoice_type === 'tuition_fee') {
            $rules['invoice_month_year']  = 'required|string';
            $validated_invoice_month_year = $request->invoice_month_year;
        } else {
            $rules['invoice_month_year']  = 'nullable'; // or leave it out completely
            $validated_invoice_month_year = null;
        }

        // Validate the request
        $request->validate($rules);

                                          // --- Code for generating invoice number starts
        $yearSuffix = now()->format('y'); // '25'
        $month      = now()->format('m'); // '05'

        $student = Student::with('branch')->findOrFail($request->invoice_student);
        $prefix  = $student->branch->branch_prefix;

        $monthYear = now()->format('m_Y');

        // Fetch the last invoice for the same prefix and month
        $lastInvoice = PaymentInvoice::where('invoice_number', 'like', "{$prefix}{$yearSuffix}{$month}_%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extract the numeric sequence after the last underscore
            $lastSequence = (int) substr($lastInvoice->invoice_number, strrpos($lastInvoice->invoice_number, '_') + 1);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1001; // Start from 1001 if no previous invoice
        }

        $invoiceNumber = "{$prefix}{$yearSuffix}{$month}_{$nextSequence}";
        // --- Code for generating invoice number ends

        // Invoice Generation
        $invoice = PaymentInvoice::create([
            'invoice_number' => $invoiceNumber,
            'student_id'     => $request->invoice_student,
            'invoice_type'   => $request->invoice_type,
            'total_amount'   => $request->invoice_amount,
            'amount_due'     => $request->invoice_amount,
            'month_year'     => $validated_invoice_month_year, // Can be null for non-tuition
            'created_by'     => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Invoice created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $invoice = PaymentInvoice::find($id);

        if (! $invoice || optional($invoice->student)->deleted_at !== null) {
            return redirect()->route('invoices.index')->with('warning', 'Invoice not found.');
        }

        if (auth()->user()->branch_id != 0) {
            $students = Student::where('branch_id', auth()->user()->branch_id)
                ->where(function ($query) {
                    $query->whereNull('student_activation_id')->orWhereHas('studentActivation', function ($q) {
                        $q->where('active_status', 'active');
                    });
                })
                ->withoutTrashed()
                ->orderby('student_unique_id', 'asc')
                ->get();
        } else {
            $students = Student::where(function ($query) {
                $query->whereNull('student_activation_id')->orWhereHas('studentActivation', function ($q) {
                    $q->where('active_status', 'active');
                });
            })
                ->withoutTrashed()
                ->orderby('student_unique_id', 'asc')
                ->get();
        }

        return view('invoices.view', compact('invoice', 'students'));
    }

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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'invoice_amount_edit' => 'required|numeric|min:0',
        ]);

        $invoice = PaymentInvoice::findOrFail($id);

        // Update the guardian record
        $invoice->update([
            'total_amount' => $request->invoice_amount_edit,
            'amount_due'   => $request->invoice_amount_edit,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invoice updated successfully',
            'data'    => $invoice,
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

        if ($invoice->student->branch_id !== auth()->user()->branch_id) {
            return response()->json(['error' => 'Unauthorized Access'], 403);
        }

        $invoice->update([
            'deleted_by' => auth()->id(),
        ]);

        $invoice->delete();

        return response()->json(['success' => true]);
    }

    public function getDueInvoices($studentId)
    {
        $dueInvoices = PaymentInvoice::where('student_id', $studentId)
            ->where('status', '!=', 'paid')
            ->withoutTrashed()
            ->get(['id', 'invoice_number', 'total_amount', 'amount_due'])
            ->map(function ($invoice) {
                $invoice->total_amount = (int) $invoice->total_amount;
                $invoice->amount_due   = (int) $invoice->amount_due;
                return $invoice;
            });

        return response()->json($dueInvoices);
    }
}
