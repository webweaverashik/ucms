<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;
use App\Models\Sheet\SheetPayment;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\WalletService;

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

        // Unique cache key per branch (useful when branch_id != 0)
        $cacheKey = "transactions_branch_{$branchId}";

        $transactions = Cache::remember($cacheKey, now()->addHours(1), function () use ($branchId) {
            return PaymentTransaction::with(['paymentInvoice:id,invoice_number', 'createdBy:id,name', 'student:id,name,student_unique_id,branch_id', 'student.branch:id,branch_name'])
                ->whereHas('student', function ($query) use ($branchId) {
                    if ($branchId != 0) {
                        $query->where('branch_id', $branchId);
                    }
                })
                ->latest('id')
                ->get();
        });

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

        $branches = Branch::all();

        return view('transactions.index', compact('transactions', 'students', 'branches'));
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

        $transactionData = null;

        DB::transaction(function () use ($validated, &$transactionData) {
            $invoice = PaymentInvoice::with(['invoiceType', 'student.class.sheet'])
                ->where('id', $validated['transaction_invoice'])
                ->where('student_id', $validated['transaction_student'])
                ->lockForUpdate()
                ->firstOrFail();

            $maxAmount   = $invoice->amount_due;
            $paymentType = $validated['transaction_type'];
            $amount      = $validated['transaction_amount'];

            /* ---------------- Amount validation ---------------- */
            if ($invoice->status === 'partially_paid') {
                if ($amount > $maxAmount) {
                    throw new \Exception("Amount must be ≤ due amount (৳{$maxAmount}).");
                }
            } else {
                if (($paymentType === 'full' && $amount != $maxAmount) || (in_array($paymentType, ['partial', 'discounted']) && $amount >= $maxAmount)) {
                    throw new \Exception(
                        match ($paymentType) {
                            'full' => "For full payments, amount must equal due (৳{$maxAmount}).",
                            'partial' => "Partial payment must be less than due (৳{$maxAmount}).",
                            'discounted' => "Discounted payment must be less than due (৳{$maxAmount}).",
                        },
                    );
                }
            }

            /* ---------------- Voucher number ---------------- */
            $transactionCount = PaymentTransaction::where('payment_invoice_id', $invoice->id)->withTrashed()->count();

            $voucherNo = 'TXN_' . $invoice->invoice_number . '_' . str_pad($transactionCount + 1, 2, '0', STR_PAD_LEFT);

            $newAmountDue = $invoice->amount_due - $amount;

            /* ---------------- Create transaction ---------------- */
            $transaction = PaymentTransaction::create([
                'student_id'         => $invoice->student_id,
                'student_classname'  => $invoice->student->class->name . ' (' . $invoice->student->class->class_numeral . ')',
                'payment_invoice_id' => $invoice->id,
                'amount_paid'        => $amount,
                'remaining_amount'   => $newAmountDue,
                'payment_type'       => $paymentType,
                'voucher_no'         => $voucherNo,
                'created_by'         => auth()->id(),
                'remarks'            => $validated['transaction_remarks'],
                'is_approved'        => $paymentType !== 'discounted',
            ]);

            /* ---------------- Update invoice ---------------- */
            if ($paymentType !== 'discounted') {
                $invoice->update([
                    'amount_due' => max($newAmountDue, 0),
                    'status'     => $newAmountDue <= 0 ? 'paid' : 'partially_paid',
                ]);
            }

            /* =====================================================
             * ✅ SHEET PAYMENT AUTO INSERT (CORRECTED)
             * Sheet resolved via: student → class → sheet
             * =====================================================
             */
            if ($invoice->invoiceType?->type_name === 'Sheet Fee') {
                $sheet = $invoice->student->class?->sheet;

                if ($sheet && ! SheetPayment::where('invoice_id', $invoice->id)->exists()) {
                    SheetPayment::create([
                        'sheet_id'   => $sheet->id,
                        'invoice_id' => $invoice->id,
                        'student_id' => $invoice->student_id,
                    ]);
                }
            }

            /* ---------------- Auto SMS ---------------- */
            $mobile = $transaction->student->mobileNumbers->where('number_type', 'sms')->first()?->mobile_number;

            if ($mobile) {
                send_auto_sms('student_payment_success', $mobile, [
                    'student_name'     => $transaction->student->name,
                    'invoice_no'       => $invoice->invoice_number,
                    'voucher_no'       => $transaction->voucher_no,
                    'paid_amount'      => $transaction->amount_paid,
                    'remaining_amount' => $transaction->remaining_amount,
                    'payment_time'     => $transaction->created_at->format('d M Y, h:i A'),
                ]);
            }

            // Store transaction data for response
            $transactionData = [
                'id'          => $transaction->id,
                'student_id'  => $transaction->student_id,
                'invoice_id'  => $transaction->payment_invoice_id,
                'voucher_no'  => $transaction->voucher_no,
                'amount_paid' => $transaction->amount_paid,
                'year'        => $invoice->created_at->format('Y'),
                'is_approved' => $transaction->is_approved,
            ];

            $walletService->recordCollection(user: auth()->user(), amount: $payment->amount_paid, payment: $payment, description: "Collection from Student #{$payment->student_id}");
        });

        clearUCMSCaches();

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'     => true,
                'message'     => 'Transaction recorded successfully.',
                'transaction' => $transactionData,
            ]);
        }

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
