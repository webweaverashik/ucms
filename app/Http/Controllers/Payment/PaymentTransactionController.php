<?php
namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;
use App\Models\Sheet\SheetPayment;
use App\Models\Student\Student;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $user     = auth()->user();
        $branchId = $user->branch_id;
        $isAdmin  = $user->hasRole('admin');

        $branches          = Branch::all();
        $transactionCounts = [];

        if ($isAdmin) {
            foreach ($branches as $branch) {
                $transactionCounts[$branch->id] = PaymentTransaction::whereHas('student', function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id);
                })->count();
            }

            $students = Student::active()
                ->select('id', 'name', 'student_unique_id', 'branch_id')
                ->orderBy('student_unique_id')
                ->get();

            $studentsByBranch = $students->groupBy('branch_id');
        } else {
            $students = Student::active()
                ->when($branchId != 0, function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->select('id', 'name', 'student_unique_id', 'branch_id')
                ->orderBy('student_unique_id')
                ->get();

            $studentsByBranch = [];
        }

        return view('transactions.index', compact(
            'transactionCounts', 'students', 'studentsByBranch', 'branches', 'isAdmin', 'branchId'
        ));
    }

    /**
     * Get transactions data for AJAX DataTable.
     */
    public function getData(Request $request)
    {
        if (! auth()->user()->can('transactions.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user         = auth()->user();
        $userBranchId = $user->branch_id;
        $isAdmin      = $user->hasRole('admin');
        $branchId     = $request->get('branch_id');
        $showDeleted  = $request->get('show_deleted') === 'true' || $request->get('show_deleted') === '1';

        $query = PaymentTransaction::with([
            'paymentInvoice:id,invoice_number,created_at',
            'createdBy:id,name',
            'student:id,name,student_unique_id,branch_id',
            'student.branch:id,branch_name',
        ]);

        if ($showDeleted) {
            $query->onlyTrashed();
        }

        // Branch filter
        if ($isAdmin && $branchId) {
            $query->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        } elseif (! $isAdmin && $userBranchId != 0) {
            $query->whereHas('student', function ($q) use ($userBranchId) {
                $q->where('branch_id', $userBranchId);
            });
        }

        // Date range filter
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [
                $dateFrom . ' 00:00:00',
                $dateTo . ' 23:59:59',
            ]);
        } elseif ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $totalRecords = $query->count();

        // Search filter
        $searchValue = $request->get('search')['value'] ?? '';
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('voucher_no', 'like', "%{$searchValue}%")
                    ->orWhere('amount_paid', 'like', "%{$searchValue}%")
                    ->orWhere('payment_type', 'like', "%{$searchValue}%")
                    ->orWhereHas('paymentInvoice', function ($q) use ($searchValue) {
                        $q->where('invoice_number', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('student', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%")
                            ->orWhere('student_unique_id', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('createdBy', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        // Payment type filter
        $paymentTypeFilter = $request->get('payment_type_filter');
        if (! empty($paymentTypeFilter)) {
            $typeMap = [
                'T_partial'    => 'partial',
                'T_full_paid'  => 'full',
                'T_discounted' => 'discounted',
            ];
            if (isset($typeMap[$paymentTypeFilter])) {
                $query->where('payment_type', $typeMap[$paymentTypeFilter]);
            }
        }

        $filteredRecords = $query->count();

        // Sorting
        $orderColumnIndex = $request->get('order')[0]['column'] ?? 0;
        $orderDirection   = $request->get('order')[0]['dir'] ?? 'desc';
        $columns          = ['id', 'payment_invoice_id', 'voucher_no', 'amount_paid', 'payment_type', 'payment_type', 'student_id', 'created_at', 'created_by'];
        $orderColumn      = $columns[$orderColumnIndex] ?? 'id';

        if ($orderColumn === 'id') {
            $query->orderBy('id', 'desc');
        } else {
            $query->orderBy($orderColumn, $orderDirection);
        }

        // Pagination
        $start        = $request->get('start', 0);
        $length       = $request->get('length', 10);
        $transactions = $query->skip($start)->take($length)->get();

        $data    = [];
        $counter = $start + 1;

        foreach ($transactions as $transaction) {
            // Null-safe guards — a deleted transaction may have a null-loaded relation
            $invoice = $transaction->paymentInvoice;
            $student = $transaction->student;

            if (! $invoice || ! $student) {
                // Skip malformed/orphaned rows silently rather than crashing
                $counter++;
                continue;
            }

            $paymentTypeBadge = match ($transaction->payment_type) {
                'partial'    => '<span class="badge badge-warning rounded-pill">Partial</span>',
                'full'       => '<span class="badge badge-success rounded-pill">Full Paid</span>',
                'discounted' => '<span class="badge badge-info rounded-pill">Discounted</span>',
                default      => '',
            };

            $paymentTypeFilterVal = match ($transaction->payment_type) {
                'partial'    => 'T_partial',
                'full'       => 'T_full_paid',
                'discounted' => 'T_discounted',
                default      => '',
            };

            $isDeleted       = $transaction->trashed();
            $isWithin24Hours = $transaction->created_at->gt(now()->subHours(24));
            $isDeletable     = ! $isDeleted && (! $transaction->is_approved || $isWithin24Hours);

            $actions = $this->buildActionsHtml($transaction, $request, $isDeletable, $isDeleted);

            $data[] = [
                'DT_RowId'            => 'row_' . $transaction->id,
                'DT_RowClass'         => $isDeleted ? 'bg-light-danger' : '',
                'sl'                  => $counter++,
                'invoice_no'          => '<a href="' . route('invoices.show', $invoice->id) . '" class="text-gray-800 text-hover-primary">' . e($invoice->invoice_number) . '</a>',
                'invoice_no_raw'      => $invoice->invoice_number,
                'voucher_no'          => e($transaction->voucher_no) . ($isDeleted ? ' <span class="badge badge-danger rounded-pill ms-1">Deleted</span>' : ''),
                'voucher_no_raw'      => $transaction->voucher_no,
                'amount_paid'         => $transaction->amount_paid,
                'payment_type_filter' => $paymentTypeFilterVal,
                'payment_type'        => $paymentTypeBadge,
                'payment_type_raw'    => ucfirst($transaction->payment_type),
                'student'             => '<a href="' . route('students.show', $student->id) . '" class="text-gray-800 text-hover-primary">' . e($student->name) . ', ' . e($student->student_unique_id) . '</a>',
                'student_raw'         => $student->name . ', ' . $student->student_unique_id,
                'branch'              => $student->branch->branch_name ?? '',
                'payment_date'        => $transaction->created_at->format('h:i:s A, d-M-Y'),
                'payment_date_raw'    => $transaction->created_at->format('Y-m-d H:i:s'),
                'deleted_at'          => $isDeleted ? $transaction->deleted_at->format('h:i:s A, d-M-Y') : null,
                'received_by'         => $transaction->createdBy->name ?? 'System',
                'actions'             => $actions,
                'is_approved'         => $transaction->is_approved,
                'is_deletable'        => $isDeletable,
                'is_deleted'          => $isDeleted,
                'student_id'          => $transaction->student_id,
                'invoice_year'        => $invoice->created_at?->format('Y') ?? '',
                'transaction_id'      => $transaction->id,
            ];
        }

        return response()->json([
            'draw'            => intval($request->get('draw')),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }

    /**
     * Build actions HTML for a transaction.
     */
    private function buildActionsHtml($transaction, $request, $isDeletable = false, $isDeleted = false)
    {
        $canApproveTxn      = auth()->user()->can('transactions.approve');
        $canDeleteTxn       = auth()->user()->can('transactions.delete');
        $canDownloadPayslip = auth()->user()->can('transactions.payslip.download');

        $actions = '';

        if ($isDeleted) {
            $deletedAt  = $transaction->deleted_at ? $transaction->deleted_at->format('d M Y, h:i A') : 'Unknown';
            $actions   .= '<span class="text-muted small" data-bs-toggle="tooltip" title="Deleted at: ' . $deletedAt . '"><i class="bi bi-info-circle me-1"></i>Deleted</span>';
            return $actions;
        }

        // Discounted transactions with amount_paid == 0 must never show a delete button.
        $isDiscountedZeroAmount = $transaction->payment_type === 'discounted'
        && (float) $transaction->amount_paid == 0;

        // Null-safe invoice reference
        $invoice = $transaction->paymentInvoice;

        if ($transaction->is_approved === false) {
            if ($canApproveTxn) {
                $actions .= '<a href="#" data-bs-toggle="tooltip" title="Approve Transaction" class="btn btn-icon text-hover-success w-30px h-30px approve-txn me-2" data-txn-id="' . $transaction->id . '"><i class="bi bi-check-circle fs-2"></i></a>';
            }

            if ($canDeleteTxn && $isDeletable && ! $isDiscountedZeroAmount) {
                $actions .= '<a href="#" data-bs-toggle="tooltip" title="Delete Transaction" class="btn btn-icon text-hover-danger w-30px h-30px delete-txn" data-txn-id="' . $transaction->id . '" data-is-approved="0"><i class="ki-outline ki-trash fs-2"></i></a>';
            }

            if (! $canApproveTxn && ! ($canDeleteTxn && $isDeletable && ! $isDiscountedZeroAmount)) {
                $actions .= '<span class="badge rounded-pill text-bg-secondary">Pending Approval</span>';
            }
        } else {
            if ($canDownloadPayslip && $invoice) {
                $actions .= '<a href="#" data-bs-toggle="tooltip" title="Download Statement" class="btn btn-icon text-hover-primary w-30px h-30px download-statement me-2" data-student-id="' . $transaction->student_id . '" data-year="' . ($invoice->created_at?->format('Y') ?? '') . '" data-invoice-id="' . $invoice->id . '"><i class="bi bi-download fs-2"></i></a>';
            }

            if ($canDeleteTxn && $isDeletable && ! $isDiscountedZeroAmount) {
                $actions .= '<a href="#" data-bs-toggle="tooltip" title="Delete Transaction (Reverse Collection)" class="btn btn-icon text-hover-danger w-30px h-30px delete-txn" data-txn-id="' . $transaction->id . '" data-is-approved="1"><i class="ki-outline ki-trash fs-2"></i></a>';
            }
        }

        return $actions;
    }

    /**
     * Get all transactions for export (without pagination).
     */
    public function getExportData(Request $request)
    {
        if (! auth()->user()->can('transactions.view')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user         = auth()->user();
        $userBranchId = $user->branch_id;
        $isAdmin      = $user->hasRole('admin');
        $branchId     = $request->get('branch_id');
        $showDeleted  = $request->get('show_deleted') === 'true' || $request->get('show_deleted') === '1';

        $query = PaymentTransaction::with([
            'paymentInvoice:id,invoice_number,created_at',
            'createdBy:id,name',
            'student:id,name,student_unique_id,branch_id',
            'student.branch:id,branch_name',
        ]);

        if ($showDeleted) {
            $query->onlyTrashed();
        }

        if ($isAdmin && $branchId) {
            $query->whereHas('student', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        } elseif (! $isAdmin && $userBranchId != 0) {
            $query->whereHas('student', function ($q) use ($userBranchId) {
                $q->where('branch_id', $userBranchId);
            });
        }

        // Date range filter
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [
                $dateFrom . ' 00:00:00',
                $dateTo . ' 23:59:59',
            ]);
        } elseif ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        } elseif ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Search filter
        $searchValue = $request->get('search');
        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('voucher_no', 'like', "%{$searchValue}%")
                    ->orWhere('amount_paid', 'like', "%{$searchValue}%")
                    ->orWhere('payment_type', 'like', "%{$searchValue}%")
                    ->orWhereHas('paymentInvoice', function ($q) use ($searchValue) {
                        $q->where('invoice_number', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('student', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%")
                            ->orWhere('student_unique_id', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('createdBy', function ($q) use ($searchValue) {
                        $q->where('name', 'like', "%{$searchValue}%");
                    });
            });
        }

        // Payment type filter
        $paymentTypeFilter = $request->get('payment_type_filter');
        if (! empty($paymentTypeFilter)) {
            $typeMap = [
                'T_partial'    => 'partial',
                'T_full_paid'  => 'full',
                'T_discounted' => 'discounted',
            ];
            if (isset($typeMap[$paymentTypeFilter])) {
                $query->where('payment_type', $typeMap[$paymentTypeFilter]);
            }
        }

        $transactions = $query->orderBy('id', 'desc')->get();

        $data    = [];
        $counter = 1;

        foreach ($transactions as $transaction) {
            $invoice = $transaction->paymentInvoice;
            $student = $transaction->student;

            if (! $invoice || ! $student) {
                continue;
            }

            $row = [
                'sl'           => $counter++,
                'invoice_no'   => $invoice->invoice_number,
                'voucher_no'   => $transaction->voucher_no,
                'amount_paid'  => $transaction->amount_paid,
                'payment_type' => ucfirst($transaction->payment_type),
                'student'      => $student->name . ', ' . $student->student_unique_id,
                'payment_date' => $transaction->created_at->format('h:i:s A, d-M-Y'),
                'received_by'  => $transaction->createdBy->name ?? 'System',
            ];

            if ($showDeleted) {
                $row['deleted_at'] = $transaction->deleted_at ? $transaction->deleted_at->format('h:i:s A, d-M-Y') : '';
            }

            $data[] = $row;
        }

        return response()->json([
            'data'         => $data,
            'show_deleted' => $showDeleted,
        ]);
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
            'transaction_amount'  => 'required|numeric|min:0',
            'transaction_remarks' => [
                $request->input('transaction_type') === 'discounted' ? 'required' : 'nullable',
                'string',
                'max:1000',
            ],
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
            if ($paymentType === 'discounted') {
                if ($amount < 0) {
                    throw new \Exception("Amount cannot be negative.");
                }
                if ($amount >= $maxAmount) {
                    throw new \Exception("Discounted payment must be less than due (৳{$maxAmount}).");
                }
            } elseif ($invoice->status === 'partially_paid') {
                if ($amount < 1) {
                    throw new \Exception("Amount must be at least ৳1.");
                }
                if ($amount > $maxAmount) {
                    throw new \Exception("Amount must be ≤ due amount (৳{$maxAmount}).");
                }
            } else {
                if ($amount < 1) {
                    throw new \Exception("Amount must be at least ৳1.");
                }
                if ($paymentType === 'full' && $amount != $maxAmount) {
                    throw new \Exception("For full payments, amount must equal due (৳{$maxAmount}).");
                }
                if ($paymentType === 'partial' && $amount >= $maxAmount) {
                    throw new \Exception("Partial payment must be less than due (৳{$maxAmount}).");
                }
            }

            /* ---------------- Voucher number ---------------- */
            $transactionCount = PaymentTransaction::where('payment_invoice_id', $invoice->id)->withTrashed()->count();
            $voucherNo        = 'TXN_' . $invoice->invoice_number . '_' . str_pad($transactionCount + 1, 2, '0', STR_PAD_LEFT);

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
             * SHEET PAYMENT AUTO INSERT
             * ===================================================== */
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

            $transactionData = [
                'id'          => $transaction->id,
                'student_id'  => $transaction->student_id,
                'invoice_id'  => $transaction->payment_invoice_id,
                'voucher_no'  => $transaction->voucher_no,
                'amount_paid' => $transaction->amount_paid,
                'year'        => $invoice->created_at->format('Y'),
                'is_approved' => $transaction->is_approved,
            ];

            /* ---------------- Update wallet (skip for discounted – added on approval) ---------------- */
            if ($paymentType !== 'discounted') {
                $walletService = new WalletService();
                $walletService->recordCollection(
                    user: auth()->user(),
                    amount: $transaction->amount_paid,
                    payment: $transaction,
                    description: "Collection from Student #{$transaction->student->student_unique_id} for Invoice #{$invoice->invoice_number} (Voucher #{$transaction->voucher_no})"
                );
            }
        });

        clearServerCache();

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
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentTransaction $transaction)
    {
        if (! auth()->user()->can('transactions.delete')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete transactions.',
            ], 403);
        }

        if ($transaction->is_approved && $transaction->created_at->lt(now()->subHours(24))) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete approved transactions older than 24 hours.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($transaction) {
                $invoice = $transaction->paymentInvoice;
                $student = $transaction->student;

                if ($transaction->is_approved) {
                    $originalWalletLog   = $transaction->walletLog;
                    $originalDescription = $originalWalletLog
                        ? $originalWalletLog->description
                        : "Collection from Student #{$student->student_unique_id} for Invoice #{$invoice->invoice_number} (Voucher #{$transaction->voucher_no})";

                    $deletionDescription = "Deleted: {$originalDescription}";
                    $transactionCreator  = $transaction->createdBy;

                    if ($transactionCreator) {
                        $walletService = new WalletService();
                        $walletService->recordAdjustment(
                            user: $transactionCreator,
                            amount: -$transaction->amount_paid,
                            reason: $deletionDescription
                        );

                        $transactionCreator->decrement('total_collected', $transaction->amount_paid);
                    }

                /*
                 * Recalculate amount_due from the ground up:
                 * sum only the approved transactions that will still exist after this one is soft-deleted.
                 *
                 * This correctly handles discounted transactions, where approval sets amount_due = 0 regardless of the actual amount_paid, so simply adding amount_paid back would give the wrong result.
                 */
                    $sumOfRemainingApproved = $invoice->paymentTransactions()
                        ->where('id', '!=', $transaction->id)
                        ->where('is_approved', true)
                        ->sum('amount_paid');

                    $newAmountDue = max($invoice->total_amount - $sumOfRemainingApproved, 0);

                    $newStatus = 'due';
                    if ($newAmountDue <= 0) {
                        $newStatus = 'paid';
                    } elseif ($newAmountDue < $invoice->total_amount) {
                        $newStatus = 'partially_paid';
                    }

                    $invoice->update([
                        'amount_due' => $newAmountDue,
                        'status'     => $newStatus,
                    ]);
                }

                $transaction->delete();
            });

            clearServerCache();

            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully and wallet adjusted.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve a discounted transaction.
     */
    public function approve(string $id)
    {
        $transaction = PaymentTransaction::with(['student', 'paymentInvoice', 'createdBy'])->findOrFail($id);

        $transaction->update(['is_approved' => true]);
        $transaction->paymentInvoice->update(['amount_due' => 0, 'status' => 'paid']);

        $walletService = new WalletService();
        $walletService->recordCollection(
            user: $transaction->createdBy,
            amount: $transaction->amount_paid,
            payment: $transaction,
            description: "Collection from Student #{$transaction->student->student_unique_id} for Invoice #{$transaction->paymentInvoice->invoice_number} (Voucher #{$transaction->voucher_no})"
        );

        return response()->json(['success' => true]);
    }
}
