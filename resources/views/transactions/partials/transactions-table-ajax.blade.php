{{-- Partial view for AJAX-based transactions table --}}
<div class="position-relative">
    <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table transactions-datatable"
        id="{{ $tableId }}" data-branch-id="{{ $branchId }}">
        <thead>
            <tr class="fw-bold fs-7 text-uppercase gs-0">
                <th class="w-50px">SL</th>
                <th class="w-150px">Invoice No.</th>
                <th>Voucher No.</th>
                <th>Amount (Tk)</th>
                <th class="d-none">Payment Type (Filter)</th>
                <th>Payment Type</th>
                <th class="w-350px">Student</th>
                @if ($showBranchColumn ?? false)
                    <th>Branch</th>
                @endif
                <th>Payment Date</th>
                <th>Received By</th>
                <th class="not-export text-end">Actions</th>
            </tr>
        </thead>
        <tbody class="text-gray-600 fw-semibold">
            {{-- Data will be loaded via AJAX --}}
        </tbody>
    </table>
</div>
