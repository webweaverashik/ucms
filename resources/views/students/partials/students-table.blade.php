{{-- 
    Students Table Partial - AJAX version
    
    IMPORTANT: Column order must match exactly with JavaScript DataTable columns array (19 columns)
    Index: 0=#, 1=Student, 2=Class, 3=Group, 4=Batch, 5=Institution,
           6=Mobile(Home), 7=Mobile(SMS), 8=Mobile(WhatsApp),
           9=Guardian1, 10=Guardian2, 11=Sibling1, 12=Sibling2,
           13=TuitionFee, 14=PaymentType, 15=Status,
           16=AdmissionDate, 17=AdmittedBy, 18=Actions
--}}

{{-- Skeleton preloader - shown while DataTable initializes --}}
<div class="students-skeleton" id="skeleton_{{ $tableId }}">
    <div class="skeleton-header">
        <div class="skeleton-bar" style="width:3%"></div>
        <div class="skeleton-bar" style="width:18%"></div>
        <div class="skeleton-bar" style="width:8%"></div>
        <div class="skeleton-bar" style="width:7%"></div>
        <div class="skeleton-bar" style="width:9%"></div>
        <div class="skeleton-bar" style="width:15%"></div>
        <div class="skeleton-bar" style="width:10%"></div>
        <div class="skeleton-bar" style="width:9%"></div>
        <div class="skeleton-bar" style="width:10%"></div>
        <div class="skeleton-bar" style="width:6%"></div>
    </div>
    @for ($i = 0; $i < 8; $i++)
    <div class="skeleton-row">
        <div class="skeleton-cell" style="width:3%"></div>
        <div class="skeleton-cell" style="width:18%"></div>
        <div class="skeleton-cell" style="width:8%"></div>
        <div class="skeleton-cell" style="width:7%"></div>
        <div class="skeleton-cell" style="width:9%"></div>
        <div class="skeleton-cell" style="width:15%"></div>
        <div class="skeleton-cell" style="width:10%"></div>
        <div class="skeleton-cell" style="width:9%"></div>
        <div class="skeleton-cell" style="width:10%"></div>
        <div class="skeleton-cell" style="width:6%"></div>
    </div>
    @endfor
</div>

{{-- Actual DataTable - hidden until ready --}}
<div class="students-table-wrapper" id="wrapper_{{ $tableId }}" style="opacity:0; height:0; overflow:hidden;">
    <table class="table table-hover table-row-dashed align-middle fs-6 gy-5 ucms-table students-datatable" 
           id="{{ $tableId }}" 
           data-branch-id="{{ $branchId ?? '' }}">
        <thead>
            <tr class="fw-bold fs-7 text-uppercase gs-0">
                {{-- 0: Counter --}}
                <th class="w-25px">#</th>
                {{-- 1: Student (Name + ID combined) --}}
                <th class="min-w-200px">Student</th>
                {{-- 2: Class --}}
                <th class="min-w-80px">Class</th>
                {{-- 3: Group --}}
                <th class="min-w-80px">Group</th>
                {{-- 4: Batch --}}
                <th class="min-w-100px">Batch</th>
                {{-- 5: Institution --}}
                <th class="min-w-150px">Institution</th>
                {{-- 6: Mobile (Home) --}}
                <th class="min-w-100px">Mobile<br>(Home)</th>
                {{-- 7: Mobile (SMS) --}}
                <th class="min-w-100px">Mobile<br>(SMS)</th>
                {{-- 8: Mobile (WhatsApp) --}}
                <th class="min-w-100px">Mobile<br>(WhatsApp)</th>
                {{-- 9: Guardian 1 --}}
                <th class="min-w-150px">Guardian 1</th>
                {{-- 10: Guardian 2 --}}
                <th class="min-w-150px">Guardian 2</th>
                {{-- 11: Sibling 1 --}}
                <th class="min-w-150px">Sibling 1</th>
                {{-- 12: Sibling 2 --}}
                <th class="min-w-150px">Sibling 2</th>
                {{-- 13: Tuition Fee --}}
                <th class="min-w-80px">Tuition<br>Fee (Tk)</th>
                {{-- 14: Payment Type --}}
                <th class="min-w-100px">Payment<br>Type</th>
                {{-- 15: Status --}}
                <th class="min-w-80px">Status</th>
                {{-- 16: Admission Date --}}
                <th class="min-w-100px">Admission<br>Date</th>
                {{-- 17: Admitted By --}}
                <th class="min-w-100px">Admitted<br>By</th>
                {{-- 18: Actions --}}
                <th class="min-w-70px text-end">Actions</th>
            </tr>
        </thead>
        <tbody class="text-gray-600 fw-semibold">
            {{-- Data loaded via AJAX --}}
        </tbody>
    </table>
</div>
