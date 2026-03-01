@push('page-css')
<link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('css/students/index.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'All Students')

@section('header-title')
<div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
    data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
    class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
    <!--begin::Title-->
    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
        All running students in this branch
    </h1>
    <!--end::Title-->
    <!--begin::Separator-->
    <span class="h-20px border-gray-300 border-start mx-4"></span>
    <!--end::Separator-->
    <!--begin::Breadcrumb-->
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            <a href="#" class="text-muted text-hover-primary">
                Student Info
            </a>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <!--end::Item-->
        <!--begin::Item-->
        <li class="breadcrumb-item text-muted">
            All Students
        </li>
        <!--end::Item-->
    </ul>
    <!--end::Breadcrumb-->
</div>
@endsection

@section('content')
@php
    // Define badge colors for different branches
    $badgeColors = [
        'badge-light-primary',
        'badge-light-success',
        'badge-light-warning',
        'badge-light-danger',
        'badge-light-info',
    ];

    // Map branches to badge colors dynamically
    $branchColors = [];
    foreach ($branches as $index => $branch) {
        $branchColors[$branch->branch_name] = $badgeColors[$index % count($badgeColors)];
    }

    // Preloading permissions checking
    $canDeactivate = auth()->user()->can('students.deactivate');
    $canDownloadForm = auth()->user()->can('students.form.download');
    $canEdit = auth()->user()->can('students.edit');
    $canDelete = auth()->user()->can('students.delete');
@endphp

<!--begin::Card-->
<div class="card">
    <!--begin::Card header-->
    <div class="card-header border-0 pt-6">
        <!--begin::Card title-->
        <div class="card-title">
            <!--begin::Search-->
            <div class="d-flex align-items-center position-relative my-1">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                <input type="text" data-kt-students-list-table-filter="search"
                    class="form-control form-control-solid w-350px ps-12" placeholder="Search Students">
            </div>
            <!--end::Search-->
        </div>
        <!--begin::Card title-->
        <!--begin::Card toolbar-->
        <div class="card-toolbar">
            <!--begin::Toolbar-->
            <div class="d-flex justify-content-end" data-kt-subscription-table-toolbar="base">
                <!--begin::Filter-->
                <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                    data-kt-menu-placement="bottom-end">
                    <i class="ki-outline ki-filter fs-2"></i>Filter</button>
                <!--begin::Menu 1-->
                <div class="menu menu-sub menu-sub-dropdown w-350px w-md-500px" data-kt-menu="true">
                    <!--begin::Header-->
                    <div class="px-7 py-5">
                        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Separator-->
                    <div class="separator border-gray-200"></div>
                    <!--end::Separator-->
                    <!--begin::Content-->
                    <div class="px-7 py-5" data-kt-students-list-table-filter="form">
                        <div class="row">
                            <div class="col-6 mb-5">
                                <label class="form-label fs-6 fw-semibold">Student Gender:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-filter-field="gender" data-hide-search="true">
                                    <option></option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div class="col-6 mb-5">
                                <label class="form-label fs-6 fw-semibold">Student Status:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-filter-field="status" data-hide-search="true">
                                    <option></option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-6 mb-5">
                                <label class="form-label fs-6 fw-semibold">Payment Type:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-filter-field="payment_type" data-hide-search="true">
                                    <option></option>
                                    <option value="due">Due</option>
                                    <option value="current">Current</option>
                                </select>
                            </div>
                            <!--begin::Input group-->
                            <div class="col-6 mb-5">
                                <label class="form-label fs-6 fw-semibold">Due Date:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-filter-field="due_date" data-hide-search="true">
                                    <option></option>
                                    <option value="7">1-7</option>
                                    <option value="10">1-10</option>
                                    <option value="15">1-15</option>
                                    <option value="30">1-30</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="col-6 mb-5">
                                <label class="form-label fs-6 fw-semibold">Batches:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-filter-field="batch_id" data-hide-search="true">
                                    <option></option>
                                    @foreach ($batches as $batch)
                                        <option value="{{ $batch->id }}">
                                            {{ $batch->name }}
                                            @if ($isAdmin)
                                                ({{ $batch->branch_name }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="col-6 mb-5">
                                <label class="form-label fs-6 fw-semibold">Academic Group:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-filter-field="academic_group" data-hide-search="true">
                                    <option></option>
                                    <option value="Science">Science</option>
                                    <option value="Commerce">Commerce</option>
                                    <option value="Arts">Arts</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="col-12 mb-5">
                                <label class="form-label fs-6 fw-semibold">Class</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-filter-field="class_id">
                                    <option></option>
                                    @foreach ($classnames as $classname)
                                        <option value="{{ $classname->id }}">
                                            {{ $classname->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="col-12 mb-5">
                                <label class="form-label fs-6 fw-semibold">Institutions</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-filter-field="institution">
                                    <option></option>
                                    @foreach ($institutions as $institution)
                                        <option value="{{ $institution->name }}">
                                            {{ $institution->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--begin::Actions-->
                        <div class="d-flex justify-content-end">
                            <button type="reset"
                                class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                data-kt-menu-dismiss="true"
                                data-kt-students-list-table-filter="reset">Reset</button>
                            <button type="submit" class="btn btn-primary fw-semibold px-6"
                                data-kt-menu-dismiss="true"
                                data-kt-students-list-table-filter="filter">Apply</button>
                        </div>
                        <!--end::Actions-->
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Menu 1-->
                @if ($isAdmin)
                <!--begin::Column Selector-->
                <div>
                    <button type="button" class="btn btn-light-info me-3" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-setting-2 fs-2"></i>Columns</button>
                    <div class="menu menu-sub menu-sub-dropdown w-350px" data-kt-menu="true"
                        id="column_selector_menu">
                        <div class="px-7 py-5 d-flex justify-content-between align-items-center">
                            <div class="fs-5 text-gray-900 fw-bold">Select Columns</div>
                            <button type="button" class="btn btn-sm btn-icon btn-light-primary"
                                id="column_reset_btn" title="Reset to Default">
                                <i class="ki-outline ki-arrows-circle fs-4"></i>
                            </button>
                        </div>
                        <div class="separator border-gray-200"></div>
                        <div class="px-7 py-5" id="column_checkbox_list"
                            style="max-height: 350px; overflow-y: auto;">
                        </div>
                        <div class="separator border-gray-200"></div>
                        <div class="px-7 py-4">
                            <button type="button" class="btn btn-sm btn-primary w-100"
                                data-kt-menu-dismiss="true" id="column_apply_btn">
                                <i class="ki-outline ki-check fs-4 me-1"></i>Apply & Save for All Users
                            </button>
                        </div>
                    </div>
                </div>
                <!--end::Column Selector-->
                <!--begin::Export dropdown-->
                <div class="dropdown">
                    <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-exit-up fs-2"></i>Export
                    </button>
                    <!--begin::Menu-->
                    <div id="kt_table_report_dropdown_menu"
                        class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                        data-kt-menu="true">
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-row-export="copy">Copy to clipboard</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-row-export="excel">Export as Excel</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-row-export="csv">Export as CSV</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-row-export="pdf">Export as PDF</a>
                        </div>
                        <!--end::Menu item-->
                    </div>
                    <!--end::Menu-->
                </div>
                <!--end::Export dropdown-->
                @endif
                @can('students.create')
                <!--begin::Add Student-->
                <a href="{{ route('students.create') }}" class="btn btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i>New Admission</a>
                <!--end::Add Student-->
                @endcan
            </div>
            <!--end::Toolbar-->
        </div>
        <!--end::Card toolbar-->
    </div>
    <!--end::Card header-->
    <!--begin::Card body-->
    <div class="card-body py-4">
        @if ($isAdmin)
        <!--begin::Tabs for Admin-->
        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="branchTabs" role="tablist">
            @foreach ($branches as $index => $branch)
                @php
                    $tabBadgeColor = $badgeColors[$index % count($badgeColors)];
                @endphp
                <li class="nav-item" role="presentation">
                    <a class="nav-link fw-bold {{ $index === 0 ? 'active' : '' }}"
                        id="tab-branch-{{ $branch->id }}"
                        data-bs-toggle="tab"
                        href="#kt_tab_branch_{{ $branch->id }}"
                        role="tab"
                        aria-controls="kt_tab_branch_{{ $branch->id }}"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                        data-branch-id="{{ $branch->id }}">
                        <i class="ki-outline ki-bank fs-4 me-1"></i>
                        {{ ucfirst($branch->branch_name) }}
                        <span class="badge {{ $tabBadgeColor }} ms-2 branch-count-badge badge-loading"
                            data-branch-id="{{ $branch->id }}">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
        <!--end::Tabs for Admin-->
        <!--begin::Tab Content-->
        <div class="tab-content" id="branchTabsContent">
            @foreach ($branches as $index => $branch)
                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                    id="kt_tab_branch_{{ $branch->id }}" role="tabpanel"
                    aria-labelledby="tab-branch-{{ $branch->id }}">
                    @include('students.partials.students-table', [
                        'tableId' => 'kt_students_table_branch_' . $branch->id,
                        'branchId' => $branch->id,
                    ])
                </div>
            @endforeach
        </div>
        <!--end::Tab Content-->
        @else
        <!--begin::Single Table for Non-Admin-->
        @include('students.partials.students-table', [
            'tableId' => 'kt_students_table',
            'branchId' => null,
        ])
        <!--end::Single Table for Non-Admin-->
        @endif
    </div>
    <!--end::Card body-->
</div>
<!--end::Card-->

<!--begin::Modal - Toggle Activation Student-->
<div class="modal fade" id="kt_toggle_activation_student_modal" tabindex="-1" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header">
                <!--begin::Modal title-->
                <h2 id="toggle-activation-modal-title">Activation/Deactivation Student</h2>
                <!--end::Modal title-->
                <!--begin::Close-->
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
                <!--end::Close-->
            </div>
            <!--end::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body py-lg-5">
                <!--begin::Content-->
                <div class="flex-row-fluid p-lg-5">
                    <form action="{{ route('students.toggleActive') }}" class="form d-flex flex-column"
                        method="POST" id="kt_toggle_activation_form">
                        @csrf
                        <!--begin::Left column-->
                        <div class="d-flex flex-column">
                            <input type="hidden" name="student_id" id="student_id" />
                            <input type="hidden" name="active_status" id="activation_status" />
                            <div class="row">
                                <div class="col-lg-12">
                                    <!--begin::Input group-->
                                    <div class="d-flex flex-column mb-5 fv-row">
                                        <!--begin::Label-->
                                        <label class="fs-5 fw-semibold mb-2 required"
                                            id="reason_label">Activation/Deactivation Reason</label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <textarea class="form-control" rows="3" name="reason" id="activation_reason"
                                            placeholder="Write the reason for this update" required
                                            minlength="3"></textarea>
                                        <!--end::Input-->
                                        <div class="fv-plugins-message-container invalid-feedback"
                                            id="reason_error">
                                        </div>
                                    </div>
                                    <!--end::Input group-->
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <!--begin::Button-->
                                <button type="button" class="btn btn-secondary me-5"
                                    data-bs-dismiss="modal">Cancel</button>
                                <!--end::Button-->
                                <!--begin::Button-->
                                <button type="submit" class="btn btn-primary"
                                    id="kt_toggle_activation_submit">
                                    <span class="indicator-label">Submit</span>
                                    <span class="indicator-progress">Please wait...
                                        <span
                                            class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                                <!--end::Button-->
                            </div>
                        </div>
                        <!--end::Left column-->
                    </form>
                </div>
                <!--end::Content-->
            </div>
            <!--end::Modal body-->
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>
<!--end::Modal - Toggle Activation Student-->
@endsection

@push('vendor-js')
<script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<!-- SheetJS for Excel export -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<!-- jsPDF for PDF export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.1/jspdf.plugin.autotable.min.js"></script>
@endpush

@push('page-js')
<script>
    const routeDeleteStudent = "{{ route('students.destroy', ':id') }}";
    const routeToggleActive = "{{ route('students.toggleActive') }}";
    const routeStudentsData = "{{ route('students.data') }}";
    const routeStudentShow = "{{ route('students.show', ':id') }}";
    const routeBranchCounts = "{{ route('students.branch-counts') }}";
    const routeClassShow = "{{ route('classnames.show', ':id') }}";
    const routeStudentsExport = "{{ route('students.export') }}";
    const routeColumnSettingsGet = "{{ route('students.column-settings.get') }}";
    const routeColumnSettingsSave = "{{ route('students.column-settings.save') }}";
    const isAdmin = @json($isAdmin);
    const branchIds = @json($branches->pluck('id')->toArray());
</script>
<script src="{{ asset('js/students/index.js') }}"></script>
<script>
    document.getElementById("student_info_menu").classList.add("here", "show");
    document.getElementById("all_students_link").classList.add("active");
</script>
@endpush
