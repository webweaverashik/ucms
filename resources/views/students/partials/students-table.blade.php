{{-- Partial view for students table - AJAX version --}}
{{-- 
    Column order (19 columns):
    0: # (Counter)
    1: Student (Name + ID combined in display, separate in export)
    2: Class
    3: Group
    4: Batch
    5: Institution
    6: Mobile (Home)
    7: Mobile (SMS)
    8: Mobile (WhatsApp)
    9: Guardian 1
    10: Guardian 2
    11: Sibling 1
    12: Sibling 2
    13: Tuition Fee (Tk)
    14: Payment Type
    15: Status
    16: Admission Date
    17: Admitted By
    18: Actions
--}}
<table class="table table-hover table-row-dashed align-middle fs-6 gy-5 ucms-table students-datatable" 
       id="{{ $tableId }}" 
       data-branch-id="{{ $branchId ?? '' }}">
    <thead>
        <tr class="fw-bold fs-7 text-uppercase gs-0">
            <th class="w-25px">#</th>
            <th class="min-w-200px">Student</th>
            <th class="min-w-100px">Class</th>
            <th>Group</th>
            <th>Batch</th>
            <th class="min-w-200px">Institution</th>
            <th>Mobile<br>(Home)</th>
            <th>Mobile<br>(SMS)</th>
            <th>Mobile<br>(WhatsApp)</th>
            <th class="min-w-150px">Guardian 1</th>
            <th class="min-w-150px">Guardian 2</th>
            <th class="min-w-150px">Sibling 1</th>
            <th class="min-w-150px">Sibling 2</th>
            <th class="w-100px">Tuition Fee (Tk)</th>
            <th>Payment<br>Type</th>
            <th>Status</th>
            <th class="min-w-100px">Admission<br>Date</th>
            <th class="min-w-100px">Admitted<br>By</th>
            <th class="min-w-70px not-export">Actions</th>
        </tr>
    </thead>
    <tbody class="text-gray-600 fw-semibold">
        {{-- Data will be loaded via AJAX --}}
    </tbody>
</table>
