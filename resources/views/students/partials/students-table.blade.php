{{-- Partial view for students table - AJAX version --}}
{{-- 
    Total columns: 17
    Index mapping:
    0  = # (counter)
    1  = Student (combined: name + student_unique_id - in export these are separate columns)
    2  = Class
    3  = Group
    4  = Batch
    5  = Institution
    6  = Mobile (Home)
    7  = Mobile (SMS)
    8  = Mobile (WhatsApp)
    9  = Guardian 1
    10 = Guardian 2
    11 = Sibling 1
    12 = Sibling 2
    13 = Tuition Fee
    14 = Payment Type
    15 = Status
    16 = Actions
--}}
<table class="table table-hover table-row-dashed align-middle fs-6 gy-5 ucms-table students-datatable"
    id="{{ $tableId }}" data-branch-id="{{ $branchId ?? '' }}">
    <thead>
        <tr class="fw-bold fs-7 text-uppercase gs-0">
            <th class="w-25px">#</th>
            <th class="min-w-200px">Student</th>
            <th class="min-w-100px">Class</th>
            <th>Group</th>
            <th>Batch</th>
            <th class="min-w-200px">Institution</th>
            <th>Mobile (Home)</th>
            <th>Mobile (SMS)</th>
            <th>Mobile (WhatsApp)</th>
            <th class="min-w-150px">Guardian 1</th>
            <th class="min-w-150px">Guardian 2</th>
            <th class="min-w-150px">Sibling 1</th>
            <th class="min-w-150px">Sibling 2</th>
            <th>Tuition Fee</th>
            <th>Payment Type</th>
            <th>Status</th>
            <th class="min-w-70px text-end not-export">Actions</th>
        </tr>
    </thead>
    <tbody class="text-gray-600 fw-semibold">
        {{-- Data will be loaded via AJAX --}}
    </tbody>
</table>
