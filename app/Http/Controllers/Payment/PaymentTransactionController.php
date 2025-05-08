<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;
use App\Models\Student\Student;
use Illuminate\Http\Request;

class PaymentTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = PaymentTransaction::orderBy('id', 'desc')->get();

        if (auth()->user()->branch_id != 0) {
            $students = Student::where('branch_id', auth()->user()->branch_id)
                ->withoutTrashed()
                ->orderby('student_unique_id', 'asc')
                ->get();
        } else {
            $students = Student::withoutTrashed()->orderby('student_unique_id', 'asc')->get();
        }

        return view('transactions.index', compact('transactions', 'students'));
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

        return $request;
        
        $validated = $request->validate([
            'transaction_student' => 'required|exists:students,id',
            'transaction_invoice' => 'required|exists:payment_invoices,id',
            'transaction_type'    => 'required|in:full,partial',
            'transaction_amount'  => 'required|numeric|min:500',
        ]);

        $invoice = PaymentInvoice::where('id', $validated['transaction_invoice'])->where('student_id', $validated['transaction_student'])->firstOrFail();

        $maxAmount = $invoice->amount;

        // Extra check: partial payments must be < invoice amount
        if ($validated['transaction_type'] === 'partial' && $validated['transaction_amount'] >= $maxAmount) {
            return redirect()->back()->with('warning', 'Partial payment must be less than the invoice amount.');
        }

        // Extra check: full payments must match invoice amount
        if ($validated['transaction_type'] === 'full' && $validated['transaction_amount'] != $maxAmount) {
            return redirect()->back()->with('warning', 'For full payments, the amount must equal the invoice total.');
        }

        // Count existing transactions for this invoice to get next sequence number
        $transactionCount = PaymentTransaction::where('payment_invoice_id', $invoice->id)->count();
        $sequence         = str_pad($transactionCount + 1, 2, '0', STR_PAD_LEFT); // 2-digit sequence
        $voucherNo        = 'TXN_' . $invoice->invoice_number . '_' . $sequence;

        // Create transaction
        $transaction = PaymentTransaction::create([
            'student_id'         => $validated['transaction_student'],
            'payment_invoice_id' => $invoice->id,
            'amount_paid'        => $validated['transaction_amount'],
            'payment_type'       => $validated['transaction_type'],
            'voucher_no'         => $voucherNo,
        ]);

        // Update invoice if fully paid
        if ($validated['transaction_type'] === 'full') {
            $invoice->status = 'paid';
            $invoice->save();
        } else {
            $invoice->status = 'partially_paid';
            $invoice->save();
        }

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
