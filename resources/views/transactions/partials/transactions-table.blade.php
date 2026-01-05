{{-- Partial view for transactions table --}}
<table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table transactions-datatable"
    id="{{ $tableId }}">
    <thead>
        <tr class="fw-bold fs-7 text-uppercase gs-0">
            <th class="w-25px">SL</th>
            <th class="w-150px">Invoice No.</th>
            <th>Voucher No.</th>
            <th>Amount (Tk)</th>
            <th class="d-none">Payment Type (Filter)</th>
            <th>Payment Type</th>
            <th class="w-350px">Student</th>
            @if ($showBranchColumn)
                <th>Branch</th>
            @endif
            <th>Payment Date</th>
            <th>Received By</th>
            <th class="not-export">Actions</th>
        </tr>
    </thead>
    <tbody class="text-gray-600 fw-semibold">
        @foreach ($transactions as $transaction)
            <tr>
                <td>{{ $loop->index + 1 }}</td>
                <td>
                    <a href="{{ route('invoices.show', $transaction->paymentInvoice->id) }}">
                        {{ $transaction->paymentInvoice->invoice_number }}
                    </a>
                </td>
                <td>{{ $transaction->voucher_no }}</td>
                <td>{{ $transaction->amount_paid }}</td>
                <td class="d-none">
                    @if ($transaction->payment_type === 'partial')
                        T_partial
                    @elseif ($transaction->payment_type === 'full')
                        T_full_paid
                    @elseif ($transaction->payment_type === 'discounted')
                        T_discounted
                    @endif
                </td>
                <td>
                    @if ($transaction->payment_type === 'partial')
                        <span class="badge badge-warning rounded-pill">Partial</span>
                    @elseif ($transaction->payment_type === 'full')
                        <span class="badge badge-success rounded-pill">Full Paid</span>
                    @elseif ($transaction->payment_type === 'discounted')
                        <span class="badge badge-info rounded-pill">Discounted</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('students.show', $transaction->student->id) }}">
                        {{ $transaction->student->name }}, {{ $transaction->student->student_unique_id }}
                    </a>
                </td>
                @if ($showBranchColumn)
                    <td>
                        @php
                            $branchName = $transaction->student->branch->branch_name;
                            $badgeColor = $branchColors[$branchName] ?? 'badge-light-secondary';
                        @endphp
                        <span class="badge {{ $badgeColor }}">{{ $branchName }}</span>
                    </td>
                @endif
                <td>
                    {{ $transaction->created_at->format('h:i:s A, d-M-Y') }}
                </td>
                <td>
                    {{ $transaction->createdBy->name ?? 'System' }}
                </td>
                <td>
                    @if ($transaction->is_approved === false)
                        @if ($canApproveTxn)
                            <a href="#" title="Approve Transaction"
                                class="btn btn-icon text-hover-success w-30px h-30px approve-txn me-2"
                                data-txn-id={{ $transaction->id }}>
                                <i class="bi bi-check-circle fs-2"></i>
                            </a>
                        @endif

                        @if ($canDeleteTxn)
                            <a href="#" title="Delete Transaction"
                                class="btn btn-icon text-hover-danger w-30px h-30px delete-txn"
                                data-txn-id={{ $transaction->id }}>
                                <i class="bi bi-trash fs-2"></i>
                            </a>
                        @endif

                        {{-- Showing a placeholder text for other users --}}
                        @if (!$canApproveTxn)
                            <span class="badge rounded-pill text-bg-secondary">Pending Approval</span>
                        @endif
                    @else
                        @if ($canDownloadPayslip)
                            <a href="#" data-bs-toggle="tooltip" title="Download Statement"
                                class="btn btn-icon text-hover-primary w-30px h-30px download-statement"
                                data-student-id="{{ $transaction->student_id }}"
                                data-year="{{ $transaction->paymentInvoice->created_at->format('Y') }}">
                                <i class="bi bi-download fs-2"></i>
                            </a>
                        @endif
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
