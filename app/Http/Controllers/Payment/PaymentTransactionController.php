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
        $transactions = PaymentTransaction::whereHas('student', function ($query) {
            $query->where('branch_id', auth()->user()->branch_id);
        })->latest('id')->get();

        if (auth()->user()->branch_id != 0) {
            $students = Student::where('branch_id', auth()->user()->branch_id)
                ->where(function ($query) {
                    $query->whereNull('student_activation_id')
                        ->orWhereHas('studentActivation', function ($q) {
                            $q->where('active_status', 'active');
                        });
                })
                ->orderby('student_unique_id')
                ->get();
        } else {
            $students = Student::where(function ($query) {
                $query->whereNull('student_activation_id')->orWhereHas('studentActivation', function ($q) {
                    $q->where('active_status', 'active');
                });
            })
                ->orderby('student_unique_id')
                ->get();
        }

        return view('transactions.index', compact('transactions', 'students'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->back()->with('warning', 'Activity Not Allowed');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return $request;

        $validated = $request->validate([
            'transaction_student' => 'required|exists:students,id',
            'transaction_invoice' => 'required|exists:payment_invoices,id',
            'transaction_type'    => 'required|in:full,partial,discounted',
            'transaction_amount'  => 'required|numeric|min:1',
            'transaction_remarks' => 'nullable|string|max:1000',
        ]);

        $invoice = PaymentInvoice::where('id', $validated['transaction_invoice'])->where('student_id', $validated['transaction_student'])->firstOrFail();

        // Use amount_due instead of total_amount for validation
        $maxAmount = $invoice->amount_due;

        // return ['request' => $validated, 'invoice' => $invoice, 'maxAmount' => $maxAmount];

        // Combined validation for all payment types
        $paymentType = $validated['transaction_type'];
        $amount      = $validated['transaction_amount'];

        if (($paymentType === 'full' && $amount != $maxAmount) ||
            (in_array($paymentType, ['partial', 'discounted']) && $amount >= $maxAmount)) {

            $errorMessage = match ($paymentType) {
                'full' => "For full payments, the amount must equal the due amount (৳{$maxAmount}).",
                'partial' => "Partial payment must be less than the due amount (৳{$maxAmount}).",
                'discounted' => "Discounted payment must be less than the due amount (৳{$maxAmount}).",
            };

            return redirect()
                ->back()
                ->with('warning', $errorMessage);
        }

        // Count existing transactions for this invoice to get next sequence number
        $transactionCount = PaymentTransaction::where('payment_invoice_id', $invoice->id)->count();
        $sequence         = str_pad($transactionCount + 1, 2, '0', STR_PAD_LEFT);
        $voucherNo        = 'TXN_' . $invoice->invoice_number . '_' . $sequence;

        // Create transaction
        $transaction = PaymentTransaction::create([
            'student_id'         => $validated['transaction_student'],
            'payment_invoice_id' => $invoice->id,
            'amount_paid'        => $validated['transaction_amount'],
            'payment_type'       => $validated['transaction_type'],
            'voucher_no'         => $voucherNo,
            'created_by'         => auth()->user()->id,
            'remarks'            => $validated['transaction_remarks'],
        ]);

        // Update invoice status and amount_due
        $newAmountDue = $invoice->amount_due - $validated['transaction_amount'];

        if ($validated['transaction_type'] === 'discounted') {
            // For discounted payments, mark as fully paid regardless of amount
            $invoice->update([
                'amount_due' => 0,
                'status'     => 'paid',
            ]);
        } elseif ($newAmountDue <= 0) {
            // Full payment (regular case)
            $invoice->update([
                'amount_due' => 0,
                'status'     => 'paid',
            ]);
        } else {
            // Partial payment
            $invoice->update([
                'amount_due' => $newAmountDue,
                'status'     => $invoice->amount_due == $invoice->total_amount
                ? 'partially_paid'  // First partial payment
                : $invoice->status, // Keep existing status if already partially paid
            ]);
        }

        return redirect()->back()->with('success', 'Transaction recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->back()->with('warning', 'Activity Not Allowed');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return redirect()->back()->with('warning', 'Activity Not Allowed');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return redirect()->back()->with('warning', 'Activity Not Allowed');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return redirect()->back()->with('warning', 'Activity Not Allowed');
    }
}
