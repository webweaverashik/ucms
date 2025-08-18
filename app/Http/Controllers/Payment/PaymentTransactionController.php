<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (! auth()->user()->can('transactions.view')) {
            return redirect()->back()->with('warning', 'No permission to view transactions.');
        }

        $branchId = auth()->user()->branch_id;

        // Simplified transactions query
        $transactions = PaymentTransaction::with([
            'paymentInvoice' => function ($query) {
                $query->select('id', 'invoice_number');
            },
            'createdBy'      => function ($query) {
                $query->select('id', 'name');
            },
            'student'        => function ($query) {
                $query->select('id', 'name', 'student_unique_id');
            },
        ])
            ->whereHas('student', function ($query) use ($branchId) {
                if ($branchId != 0) {
                    $query->where('branch_id', $branchId);
                }
            })
            ->latest('id')
            ->get();

        // Simplified students query
        $students = Student::when($branchId != 0, function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
            ->where(function ($query) {
                $query->whereNull('student_activation_id')->orWhereHas('studentActivation', function ($q) {
                    $q->where('active_status', 'active');
                });
            })
            ->select('id', 'name', 'student_unique_id')
            ->orderBy('student_unique_id')
            ->get();

        return view('transactions.index', compact('transactions', 'students'));
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
        $validated = $request->validate([
            'transaction_student' => 'required|exists:students,id',
            'transaction_invoice' => 'required|exists:payment_invoices,id',
            'transaction_type'    => 'required|in:full,partial,discounted',
            'transaction_amount'  => 'required|numeric|min:1',
            'transaction_remarks' => 'nullable|string|max:1000',
        ]);

        $invoice = PaymentInvoice::where('id', $validated['transaction_invoice'])
            ->where('student_id', $validated['transaction_student'])
            ->firstOrFail();

        $maxAmount   = $invoice->amount_due;
        $paymentType = $validated['transaction_type'];
        $amount      = $validated['transaction_amount'];

        // Special case for partially paid invoices - allow equal or less than amount_due
        if ($invoice->status === 'partially_paid') {
            if ($amount > $maxAmount) {
                return redirect()->back()->with('warning', "Amount must be less than or equal to the due amount (৳{$maxAmount}).");
            }
        }
        // Normal validation for other statuses
        else {
            if (($paymentType === 'full' && $amount != $maxAmount) ||
                (in_array($paymentType, ['partial', 'discounted']) && $amount >= $maxAmount)) {
                $errorMessage = match ($paymentType) {
                    'full' => "For full payments, the amount must equal the due amount (৳{$maxAmount}).",
                    'partial' => "Partial payment must be less than the due amount (৳{$maxAmount}).",
                    'discounted' => "Discounted payment must be less than the due amount (৳{$maxAmount}).",
                };
                return redirect()->back()->with('warning', $errorMessage);
            }
        }

        // Count existing transactions for this invoice to get next sequence number
        $transactionCount = PaymentTransaction::where('payment_invoice_id', $invoice->id)->withTrashed()->count();
        $sequence         = str_pad($transactionCount + 1, 2, '0', STR_PAD_LEFT);
        $voucherNo        = 'TXN_' . $invoice->invoice_number . '_' . $sequence;

        // Update invoice status and amount_due
        $newAmountDue = $invoice->amount_due - $validated['transaction_amount'];

        // Create transaction
        $transaction = PaymentTransaction::create([
            'student_id'         => $validated['transaction_student'],
            'student_classname'  => $invoice->student->class->name . ' (' . $invoice->student->class->class_numeral . ')',
            'payment_invoice_id' => $invoice->id,
            'amount_paid'        => $validated['transaction_amount'],
            'remaining_amount'   => $newAmountDue,
            'payment_type'       => $validated['transaction_type'],
            'voucher_no'         => $voucherNo,
            'created_by'         => auth()->user()->id,
            'remarks'            => $validated['transaction_remarks'],
            'is_approved'        => $validated['transaction_type'] !== 'discounted', // true for full/partial, false for discounted
        ]);

        if ($validated['transaction_type'] === 'discounted') {
            // For discounted payments, mark the payment as pending and is_approved false
            // $invoice->update([
            //     'amount_due' => 0,
            //     'status'     => 'paid',
            // ]);
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
                'status'     => 'partially_paid',
            ]);
        }

        // AutoSMS for transaction creation
        $mobile = $transaction->student->mobileNumbers
            ->where('number_type', 'sms')
            ->first()
            ->mobile_number;

        send_auto_sms(
            'student_payment_success',
            $mobile,
            [
                'student_name'     => $transaction->student->name,
                'invoice_no'       => $invoice->invoice_number,
                'voucher_no'       => $transaction->voucher_no,
                'paid_amount'      => $transaction->amount_paid,
                'remaining_amount' => $transaction->remaining_amount,
                'payment_time'     => $transaction->created_at->format('d M Y, h:i A'),
            ]
        );

        // Clear the cache
        clearUCMSCaches();

        return redirect()->back()->with('success', 'Transaction recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->back();
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
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentTransaction $transaction)
    {
        $transaction->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Approve a discounted transaction
     */
    public function approve(string $id)
    {
        $transaction = PaymentTransaction::findOrFail($id);

        $transaction->update(['is_approved' => true]);
        $transaction->paymentInvoice->update(['amount_due' => 0, 'status' => 'paid']);

        return response()->json(['success' => true]);
    }
}
