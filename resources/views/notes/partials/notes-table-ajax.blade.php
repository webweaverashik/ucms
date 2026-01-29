{{-- Partial view for AJAX-based notes distribution table --}}
<div class="position-relative">
    <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table notes-distribution-datatable"
        id="{{ $tableId }}" data-branch-id="{{ $branchId }}">
        <thead>
            <tr class="fw-bold fs-7 text-uppercase gs-0">
                <th class="w-50px">SL</th>
                <th class="min-w-150px">Topic Name</th>
                <th class="min-w-120px">Subject</th>
                <th class="min-w-150px">Sheet Group</th>
                <th class="min-w-200px">Student</th>
                <th class="min-w-100px">Distributed By</th>
                <th class="min-w-120px">Distributed At</th>
            </tr>
        </thead>
        <tbody class="text-gray-600 fw-semibold">
            {{-- Data will be loaded via AJAX --}}
        </tbody>
    </table>
</div>
