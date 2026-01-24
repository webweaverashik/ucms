<div class="tab-pane fade" id="kt_enrolled_students_tab" role="tabpanel">
    <!--begin::Statements-->
    <div class="card mb-6 mb-xl-9">
        <!--begin::Header-->
        <div class="card-header">
            <!--begin::Title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                    <input type="text" data-enrolled-regular-students-table-filter="search"
                        class="form-control form-control-solid w-350px ps-12"
                        placeholder="Search in students">
                </div>
                <!--end::Search-->
            </div>
            <!--end::Title-->
            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-enrolled-regular-students-table-filter="base">
                    <!--begin::Filter-->
                    <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-filter fs-2"></i>Filter</button>
                    <!--begin::Menu 1-->
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
                        <div class="px-7 py-5" data-enrolled-regular-students-table-filter="form">
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Group:</label>
                                <select class="form-select form-select-solid fw-bold"
                                    data-kt-select2="true" data-placeholder="Select group"
                                    data-allow-clear="true" data-hide-search="true"
                                    id="filter_academic_group">
                                    <option></option>
                                    <option value="Science">Science</option>
                                    <option value="Commerce">Commerce</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Student Status:</label>
                                <select class="form-select form-select-solid fw-bold"
                                    data-kt-select2="true" data-placeholder="Select option"
                                    data-allow-clear="true" data-hide-search="true" id="filter_status">
                                    <option></option>
                                    <option value="active">Active</option>
                                    <option value="suspended">Inactive</option>
                                </select>
                            </div>
                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="reset"
                                    class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-kt-menu-dismiss="true"
                                    data-enrolled-regular-students-table-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6"
                                    data-kt-menu-dismiss="true"
                                    data-enrolled-regular-students-table-filter="filter">Apply</button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Menu 1-->
                    <!--end::Filter-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Header-->
        <!--begin::Card body-->
        <div class="card-body pb-5">
            @if ($isAdmin && $branches->count() > 0)
                <!--begin::Branch Tabs for Admin-->
                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="branchTabs"
                    role="tablist">
                    @foreach ($branches as $index => $branch)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link @if ($index === 0) active @endif"
                                id="branch-{{ $branch->id }}-tab" data-bs-toggle="tab"
                                data-bs-target="#branch_{{ $branch->id }}_content" type="button"
                                role="tab" data-branch-filter="{{ $branch->id }}">
                                <i class="ki-outline ki-home fs-4 me-2"></i>
                                {{ $branch->branch_name }}
                                <span
                                    class="badge {{ $branchColors[$branch->id] ?? 'badge-light-primary' }} ms-2 branch-count-badge"
                                    data-branch-id="{{ $branch->id }}">
                                    {{ $branchStudentCounts[$branch->id] ?? 0 }}
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <!--end::Branch Tabs for Admin-->
            @endif

            @if ($canDeactivate && $classname->isActive())
                <!--begin::Bulk Actions Toolbar-->
                <div class="d-flex justify-content-between align-items-center mb-4" id="bulk_actions_toolbar" style="display: none !important;">
                    <div class="d-flex align-items-center">
                        <span class="fw-bold text-gray-700 me-3">
                            <span id="selected_count">0</span> student(s) selected
                            <span class="text-muted fs-7" id="multipage_indicator" style="display: none;">
                                (across multiple pages)
                            </span>
                        </span>
                        <button type="button" class="btn btn-sm btn-light-danger me-2" id="btn_clear_selection">
                            <i class="ki-outline ki-cross fs-5 me-1"></i> Clear Selection
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="btn_bulk_activate">
                            <i class="bi bi-person-check fs-5 me-1"></i> Activate Selected
                        </button>
                        <button type="button" class="btn btn-sm btn-warning" id="btn_bulk_deactivate">
                            <i class="bi bi-person-slash fs-5 me-1"></i> Deactivate Selected
                        </button>
                    </div>
                </div>
                <!--end::Bulk Actions Toolbar-->
            @endif

            <!--begin::Table-->
            <table class="table table-hover align-middle table-row-dashed fs-6 fw-semibold gy-4 ucms-table"
                id="kt_enrolled_regular_students_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        @if ($canDeactivate && $classname->isActive())
                            <th class="w-10px pe-2 checkbox-column">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" id="select_all_checkbox" data-kt-check="true" data-kt-check-target="#kt_enrolled_regular_students_table .student-checkbox" />
                                </div>
                            </th>
                        @endif
                        <th class="w-20px">#</th>
                        <th class="w-150px">Student Name</th>
                        <th>Group</th>
                        <th>Batch</th>
                        <th class="w-100px">Admitted By</th>
                        <th class="w-100px">Admission Date</th>
                        <th class="w-100px">Actions</th>
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
    <!--end::Statements-->
</div>
