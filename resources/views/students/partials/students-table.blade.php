{{-- Partial view for students table - AJAX version --}}
<table class="table table-hover table-row-dashed align-middle fs-6 gy-5 ucms-table students-datatable"
    id="{{ $tableId }}" data-branch-id="{{ $branchId ?? '' }}">
    <thead>
        <tr class="fw-bold fs-7 text-uppercase gs-0">
            <th class="w-25px">#</th>
            <th class="min-w-200px">Student</th>
            <th class="d-none">Gender (filter)</th>
            <th class="d-none">Active/Inactive (filter)</th>
            <th class="d-none">Class (filter)</th>
            <th>Class</th>
            <th class="d-none">Group (filter)</th>
            <th>Group</th>
            <th class="d-none">Batch (Filter)</th>
            <th>Batch</th>
            <th class="w-300px">Institution</th>
            <th>Mobile<br>(Home)</th>
            <th class="w-100px">Tuition Fee (Tk)</th>
            <th>Payment<br>Type</th>
            <th class="d-none">Branch (filter)</th>
            <th class="min-w-70px not-export">Actions</th>
        </tr>
    </thead>
    <tbody class="text-gray-600 fw-semibold">
        {{-- Data will be loaded via AJAX --}}
    </tbody>
</table>