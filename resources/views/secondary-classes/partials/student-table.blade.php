@props([
    'tableId' => 'kt_students_table',
    'branchId' => null,
    'statusType' => 'active',
    'secondaryClass',
    'classname',
    'batches' => collect(),
    'isAdmin' => false,
    'isManager' => false,
])

<!--begin::Card-->
<div class="card card-flush">
    <!--begin::Card header-->
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <!--begin::Card title-->
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                <input type="text" 
                    data-table-filter="search" 
                    data-table-id="{{ $tableId }}"
                    class="form-control form-control-solid w-250px ps-12" 
                    placeholder="Search students...">
            </div>
        </div>
        <!--end::Card title-->

        <!--begin::Card toolbar-->
        <div class="card-toolbar flex-row-fluid justify-content-end gap-3">
            <!--begin::Filter-->
            <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                data-kt-menu-placement="bottom-end">
                <i class="ki-outline ki-filter fs-2"></i>Filter
            </button>
            <!--begin::Menu-->
            <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                <!--begin::Header-->
                <div class="px-7 py-5">
                    <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                </div>
                <!--end::Header-->
                
                <!--begin::Separator-->
                <div class="separator border-gray-200"></div>
                <!--end::Separator-->
                
                <!--begin::Content-->
                <div class="px-7 py-5">
                    <!--begin::Group Filter-->
                    <div class="mb-5">
                        <label class="form-label fs-6 fw-semibold">Academic Group:</label>
                        <select class="form-select form-select-solid fw-bold filter-group-select" 
                            data-table-id="{{ $tableId }}"
                            data-kt-select2="true"
                            data-placeholder="All Groups"
                            data-allow-clear="true"
                            data-hide-search="true">
                            <option value="">All Groups</option>
                            <option value="Science">Science</option>
                            <option value="Commerce">Commerce</option>
                        </select>
                    </div>
                    <!--end::Group Filter-->

                    <!--begin::Batch Filter-->
                    <div class="mb-10">
                        <label class="form-label fs-6 fw-semibold">Batch:</label>
                        <select class="form-select form-select-solid fw-bold filter-batch-select" 
                            data-table-id="{{ $tableId }}"
                            data-kt-select2="true"
                            data-placeholder="All Batches"
                            data-allow-clear="true"
                            data-hide-search="true">
                            <option value="">All Batches</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!--end::Batch Filter-->
                    
                    <!--begin::Actions-->
                    <div class="d-flex justify-content-end">
                        <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                            data-kt-menu-dismiss="true" 
                            data-table-filter="reset" 
                            data-table-id="{{ $tableId }}">Reset</button>
                        <button type="submit" class="btn btn-primary fw-semibold px-6"
                            data-kt-menu-dismiss="true" 
                            data-table-filter="apply" 
                            data-table-id="{{ $tableId }}">Apply</button>
                    </div>
                    <!--end::Actions-->
                </div>
                <!--end::Content-->
            </div>
            <!--end::Menu-->
            <!--end::Filter-->

            <!--begin::Refresh Button-->
            <button type="button" class="btn btn-icon btn-light-info refresh-table-btn" 
                data-table-id="{{ $tableId }}"
                data-bs-toggle="tooltip" 
                title="Refresh Data">
                <i class="ki-outline ki-arrows-circle fs-2"></i>
            </button>
            <!--end::Refresh Button-->
        </div>
        <!--end::Card toolbar-->
    </div>
    <!--end::Card header-->

    <!--begin::Card body-->
    <div class="card-body pt-0">
        <!--begin::Table-->
        <table class="table table-hover align-middle table-row-dashed fs-6 gy-4 ucms-table dataTable students-datatable"
            id="{{ $tableId }}"
            data-branch-id="{{ $branchId ?? '' }}"
            data-status-type="{{ $statusType }}">
            <thead>
                <tr class="fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-30px">#</th>
                    <th class="w-250px">Student</th>
                    <th>Group</th>
                    <th>Batch</th>
                    <th class="w-150px">Fee</th>
                    <th class="w-150px">Total Paid</th>
                    <th class="w-150px">Enrolled At</th>
                    <th class="w-150px">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                {{-- Data will be loaded via AJAX --}}
            </tbody>
        </table>
        <!--end::Table-->
    </div>
    <!--end::Card body-->
</div>
<!--end::Card-->