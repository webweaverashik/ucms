<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentInvoice;
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
                    })
                ;
            })
            ->withoutTrashed()
            ->orderBy('id', 'desc')
            ->get();

        $paid_invoices = PaymentInvoice::where('status', 'paid')
            ->whereHas('student', function ($query) {
                $query
                    ->whereNull('deleted_at')
                    ->where('branch_id', auth()->user()->branch_id)
                    /*->where(function ($q) {
                        $q->whereHas('studentActivation', function ($q2) {
                            $q2->where('active_status', 'active');
                        })->orWhereNull('student_activation_id');
                    })*/
                ;
            })
            ->withoutTrashed()
            ->orderBy('id', 'desc')
            ->get();

        $dueMonths = PaymentInvoice::where('status', '!=', 'paid')
            ->pluck('month_year')
            ->unique()
            ->sortBy(function ($value) {
                return \Carbon\Carbon::createFromFormat('m_Y', $value);
            })
            ->values();

        $paidMonths = PaymentInvoice::where('status', 'paid')
            ->pluck('month_year')
            ->unique()
            ->sortBy(function ($value) {
                return \Carbon\Carbon::createFromFormat('m_Y', $value);
            })
            ->values();

        return view('invoices.index', compact('unpaid_invoices', 'paid_invoices', 'dueMonths', 'paidMonths'));
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $invoice = PaymentInvoice::find($id);

        if (! $invoice) {
            return redirect()->route('invoices.index')->with('warning', 'Invoice not found.');
        }

        return view('invoices.view', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $invoice = PaymentInvoice::find($id);

        if (! $invoice) {
            return redirect()->route('invoices.index')->with('warning', 'Invoice not found.');
        }

        return view('invoices.edit', compact('invoice'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
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
