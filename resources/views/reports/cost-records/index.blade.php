@extends('layouts.app')

@section('title', 'Cost Records')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="{{ asset('css/reports/cost-records/index.css') }}">
@endpush

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Cost Records
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Reports</li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Cost Records</li>
        </ul>
    </div>
@endsection

@section('content')
    <!--begin::Main Tabs-->
    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x fs-5 mb-5" id="main_cost_tabs">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#cost_records_tab">
                <i class="ki-outline ki-notepad fs-4"></i>
                Cost Records
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#cost_summary_tab">
                <i class="ki-outline ki-chart-simple fs-4"></i>
                Cost Summary
            </a>
        </li>
    </ul>
    <!--end::Main Tabs-->

    <!--begin::Tab Content-->
    <div class="tab-content" id="main_cost_tabs_content">
        <!--begin::Cost Records Tab-->
        <div class="tab-pane fade show active" id="cost_records_tab" role="tabpanel">
            <!--begin::Card-->
            <div class="card">
                <!--begin::Card Header-->
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" data-cost-table-filter="search"
                                class="form-control form-control-solid w-250px ps-12"
                                placeholder="Search in cost records">
                        </div>
                        <!--end::Search-->
                    </div>

                    <div class="card-toolbar gap-3" id="records_toolbar">
                        <!--begin::Filter Button-->
                        <button type="button" class="btn btn-light-primary" id="filter_btn">
                            <i class="ki-outline ki-filter fs-4"></i> Filter
                        </button>
                        <!--end::Filter Button-->

                        <!--begin::Export Button-->
                        <button type="button" class="btn btn-light-primary" id="export_btn">
                            <i class="ki-outline ki-exit-up fs-4"></i> Export
                        </button>
                        <!--end::Export Button-->

                        <!--begin::Add Cost Button-->
                        <button type="button" class="btn btn-primary" id="add_cost_btn">
                            <i class="ki-outline ki-plus fs-4"></i> Add Cost
                        </button>
                        <!--end::Add Cost Button-->
                    </div>
                </div>
                <!--end::Card Header-->

                <!--begin::Filter Menu (Custom positioned)-->
                <div class="filter-menu" id="filter_menu">
                    <div class="menu-header">
                        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                    </div>
                    <div class="menu-body">
                        <!--begin::Date Range-->
                        <div class="mb-5">
                            <label class="form-label fw-semibold">Date Range:</label>
                            <input type="text" class="form-control form-control-solid" id="filter_date_range"
                                placeholder="Select date range" readonly>
                        </div>
                        <!--end::Date Range-->

                        <!--begin::Cost Types-->
                        <div class="mb-5">
                            <label class="form-label fw-semibold">Cost Types:</label>
                            <select class="form-select form-select-solid" id="filter_cost_types" multiple
                                data-placeholder="All Cost Types">
                                @foreach ($costTypes as $costType)
                                    <option value="{{ $costType->id }}">{{ $costType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <!--end::Cost Types-->
                    </div>
                    <div class="menu-footer">
                        <button type="button" class="btn btn-sm btn-light fw-semibold" id="filter_reset_btn">Reset</button>
                        <button type="button" class="btn btn-sm btn-primary fw-semibold" id="filter_apply_btn">Apply</button>
                    </div>
                </div>
                <!--end::Filter Menu-->

                <!--begin::Export Menu (Custom positioned)-->
                <div class="export-menu" id="export_menu">
                    <div class="py-3">
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-export-type="copy">Copy to clipboard</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-export-type="excel">Export as Excel</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-export-type="csv">Export as CSV</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-export-type="pdf">Export as PDF</a>
                        </div>
                    </div>
                </div>
                <!--end::Export Menu-->

                <!--begin::Card Body-->
                <div class="card-body pt-0">
                    <!--begin::Active Filters-->
                    <div id="active_filters_container" class="active-filters-container d-none"></div>
                    <!--end::Active Filters-->

                    @if ($isAdmin && $branches->count() > 1)
                        <!--begin::Branch Tabs for Admin-->
                        <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6 branch-tabs">
                            @foreach ($branches as $index => $branch)
                                <li class="nav-item">
                                    <a class="nav-link {{ $index === 0 ? 'active' : '' }}" data-bs-toggle="tab"
                                        href="#pane_branch_{{ $branch->id }}" data-branch-id="{{ $branch->id }}">
                                        <i class="ki-outline ki-bank fs-5 me-2"></i>
                                        {{ $branch->branch_name }}
                                        <span class="badge badge-light-primary ms-2 branch-count-badge"
                                            data-branch-id="{{ $branch->id }}">{{ $costCounts[$branch->id] ?? 0 }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content">
                            @foreach ($branches as $index => $branch)
                                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                                    id="pane_branch_{{ $branch->id }}" role="tabpanel">
                                    <div class="table-responsive">
                                        <table id="costs_datatable_{{ $branch->id }}"
                                            class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                                            data-branch-id="{{ $branch->id }}">
                                            <thead>
                                            <tr class="fw-bold fs-7 text-uppercase gs-0">
                                                <th class="w-50px">SL</th>
                                                <th class="w-150px">Date</th>
                                                <th>Cost Entries</th>
                                                <th class="w-120px text-end">Total (Tk)</th>
                                                <th class="w-120px">Created By</th>
                                                <th class="not-export text-end w-100px">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-gray-600 fw-semibold"></tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <!--end::Branch Tabs for Admin-->
                    @else
                        <!--begin::Single DataTable-->
                        <div class="table-responsive">
                            <table id="costs_datatable"
                                class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-50px">SL</th>
                                        <th class="w-100px">Date</th>
                                        <th>Cost Entries</th>
                                        <th class="w-120px text-end">Total (Tk)</th>
                                        <th class="w-120px">Created By</th>
                                        @if ($isAdmin)
                                            <th class="not-export text-end w-100px">Actions</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold"></tbody>
                            </table>
                        </div>
                        <!--end::Single DataTable-->
                    @endif
                </div>
                <!--end::Card Body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Cost Records Tab-->

        <!--begin::Cost Summary Tab-->
        <div class="tab-pane fade" id="cost_summary_tab" role="tabpanel">
            <!--begin::Filter Card-->
            <div class="card mb-5">
                <div class="card-body">
                    <div class="row g-4 align-items-end">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label fw-semibold required">Date Range</label>
                            <div class="input-group input-group-solid">
                                <span class="input-group-text">
                                    <i class="ki-outline ki-calendar fs-4"></i>
                                </span>
                                <input type="text" class="form-control form-control-solid" id="summary_date_range"
                                    placeholder="Select date range" readonly>
                            </div>
                        </div>

                        @if ($isAdmin)
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label fw-semibold">Branch</label>
                                <select class="form-select form-select-solid" id="summary_branch_select"
                                    data-control="select2" data-placeholder="All Branches" data-allow-clear="true">
                                    <option value="">All Branches</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-lg-2 col-md-6">
                            <button type="button" class="btn btn-primary w-100" id="generate_summary_btn">
                                <span class="indicator-label">
                                    <i class="ki-outline ki-chart-simple fs-4 me-1"></i> Generate
                                </span>
                                <span class="indicator-progress">
                                    Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Filter Card-->

            <!--begin::Summary Content-->
            <div id="summary_content" class="d-none">
                <!--begin::Stats Cards-->
                <div class="row g-4 mb-5">
                    <div class="col-xl-4 col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body py-4 px-5 d-flex align-items-center">
                                <div class="stat-icon-sm bg-primary rounded-circle me-3">
                                    <i class="ki-outline ki-wallet fs-3 text-white"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold text-gray-800" id="stat_total_cost">৳0</div>
                                    <div class="fs-7 text-muted">Total Cost</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body py-4 px-5 d-flex align-items-center">
                                <div class="stat-icon-sm bg-success rounded-circle me-3">
                                    <i class="ki-outline ki-notepad fs-3 text-white"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold text-gray-800" id="stat_total_entries">0</div>
                                    <div class="fs-7 text-muted">Total Entries</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body py-4 px-5 d-flex align-items-center">
                                <div class="stat-icon-sm bg-info rounded-circle me-3">
                                    <i class="ki-outline ki-chart-line fs-3 text-white"></i>
                                </div>
                                <div>
                                    <div class="fs-4 fw-bold text-gray-800" id="stat_daily_average">৳0</div>
                                    <div class="fs-7 text-muted">Daily Average</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end::Stats Cards-->

                <!--begin::Summary Row-->
                <div class="row g-5">
                    <!--begin::Chart Card-->
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0 pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold fs-4 mb-1">Cost Distribution</span>
                                    <span class="text-muted fw-semibold fs-7">Cost breakdown by type</span>
                                </h3>
                                <div class="card-toolbar">
                                    <button type="button" class="btn btn-sm btn-icon btn-light-primary" id="export_chart_png"
                                        title="Export as PNG">
                                        <i class="ki-outline ki-picture fs-4"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="summary_chart"></div>
                            </div>
                        </div>
                    </div>
                    <!--end::Chart Card-->

                    <!--begin::Table Card-->
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0 pt-5">
                                <h3 class="card-title align-items-start flex-column">
                                    <span class="card-label fw-bold fs-4 mb-1">Cost Type Breakdown</span>
                                    <span class="text-muted fw-semibold fs-7">Detailed summary by cost type</span>
                                </h3>
                                <div class="card-toolbar gap-2">
                                    <button type="button" class="btn btn-sm btn-icon btn-light-success" id="export_summary_excel"
                                        title="Export Excel">
                                        <i class="ki-outline ki-file-down fs-4"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-icon btn-light-danger" id="export_summary_pdf"
                                        title="Export PDF">
                                        <i class="ki-outline ki-document fs-4"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-dashed summary-table">
                                        <thead>
                                            <tr>
                                                <th>Cost Type</th>
                                                <th class="text-center">Count</th>
                                                <th class="text-end">Amount</th>
                                                <th class="text-center">%</th>
                                            </tr>
                                        </thead>
                                        <tbody id="summary_table_body"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end::Table Card-->
                </div>
                <!--end::Summary Row-->
            </div>
            <!--end::Summary Content-->
        </div>
        <!--end::Cost Summary Tab-->
    </div>
    <!--end::Tab Content-->

    <!--begin::Add Cost Modal-->
    <div class="modal fade" id="cost_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-700px">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="cost_modal_title" class="modal-title fw-bold">Add Today's Cost</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>
                <form id="cost_form">
                    <div class="modal-body py-10 px-lg-12" style="max-height: 70vh; overflow-y: auto;">
                        <!--begin::Branch Selection-->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Branch</label>
                            @if ($isAdmin)
                                <select id="cost_branch_id" name="branch_id" class="form-select form-select-solid"
                                    data-control="select2" data-placeholder="Select branch"
                                    data-dropdown-parent="#cost_modal" data-hide-search="true">
                                    <option value="">-- Select Branch --</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">
                                            {{ $branch->branch_name }} ({{ $branch->branch_prefix }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control form-control-solid bg-secondary"
                                    value="{{ $branches->first()->branch_name ?? '' }} ({{ $branches->first()->branch_prefix ?? '' }})"
                                    readonly disabled>
                                <input type="hidden" id="cost_branch_id" name="branch_id"
                                    value="{{ $branches->first()->id ?? '' }}">
                            @endif
                        </div>
                        <!--end::Branch Selection-->

                        <!--begin::Date-->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Date</label>
                            <input type="text" id="cost_date" name="cost_date"
                                class="form-control form-control-solid bg-secondary"
                                value="{{ now()->format('d-m-Y') }}" readonly>
                            <div class="form-text text-muted">
                                <i class="ki-outline ki-information-3 fs-7 me-1"></i>
                                Cost can only be added for today's date
                            </div>
                        </div>
                        <!--end::Date-->

                        <!--begin::Cost Types Tagify-->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Cost Types</label>
                            <input type="text" id="cost_types_tagify" class="form-control form-control-solid"
                                placeholder="Select cost types...">
                            <div class="form-text text-muted">Select one or more cost types</div>
                        </div>
                        <!--end::Cost Types Tagify-->

                        <!--begin::Cost Entries Container-->
                        <div id="cost_entries_container" class="mb-5">
                            <label class="fw-semibold fs-6 mb-3">Cost Entries</label>
                            <div id="cost_entries_list">
                                <div class="text-center text-muted py-5">
                                    <i class="ki-outline ki-information fs-3x text-gray-400 mb-3"></i>
                                    <p class="mb-0">Select cost types above to add entries</p>
                                </div>
                            </div>
                        </div>
                        <!--end::Cost Entries Container-->

                        <!--begin::Other Costs Section-->
                        <div class="fv-row mb-5">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="fw-semibold fs-6">Other Costs</label>
                                <button type="button" class="btn btn-sm btn-light-primary" id="add_other_cost_btn">
                                    <i class="ki-outline ki-plus fs-6"></i> Add Other
                                </button>
                            </div>
                            <div id="other_costs_container"></div>
                            <div class="form-text text-muted">
                                <i class="ki-outline ki-information-3 fs-7 me-1"></i>
                                Add custom cost types not in the predefined list
                            </div>
                        </div>
                        <!--end::Other Costs Section-->

                        <!--begin::Total-->
                        <div id="cost_total_section" class="cost-total-section d-none">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="total-label">Total Cost</span>
                                <span id="cost_total_amount" class="total-amount">৳0</span>
                            </div>
                        </div>
                        <!--end::Total-->
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="save_cost_btn">
                            <span class="indicator-label">
                                <i class="ki-outline ki-check fs-4 me-1"></i> Save Cost
                            </span>
                            <span class="indicator-progress">
                                Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--end::Add Cost Modal-->

    @if ($isAdmin)
        <!--begin::Edit Cost Modal-->
        <div class="modal fade" id="edit_cost_modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-600px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title fw-bold">Edit Cost Amounts</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <form id="edit_cost_form">
                        <div class="modal-body py-10 px-lg-12" style="max-height: 70vh; overflow-y: auto;">
                            <input type="hidden" id="edit_cost_id">

                            <div class="d-flex justify-content-between align-items-center mb-5 p-4 bg-light rounded">
                                <div>
                                    <span class="text-muted fs-7">Date:</span>
                                    <span id="edit_cost_date" class="fw-bold text-gray-800 ms-2"></span>
                                </div>
                                <div>
                                    <span class="text-muted fs-7">Branch:</span>
                                    <span id="edit_cost_branch" class="fw-bold text-gray-800 ms-2"></span>
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="fw-semibold fs-6 mb-3">Cost Entries</label>
                                <div id="edit_entries_list"></div>
                            </div>

                            <div class="cost-total-section">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="total-label">Total Cost</span>
                                    <span id="edit_cost_total" class="total-amount">৳0</span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer flex-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="update_cost_btn">
                                <span class="indicator-label">
                                    <i class="ki-outline ki-check fs-4 me-1"></i> Update Cost
                                </span>
                                <span class="indicator-progress">
                                    Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!--end::Edit Cost Modal-->

        <!--begin::Delete Cost Modal-->
        <div class="modal fade" id="delete_cost_modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title fw-bold">Delete Cost Record</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-10 text-center">
                        <div class="mb-5">
                            <i class="ki-outline ki-trash text-danger fs-5x"></i>
                        </div>
                        <p class="fs-5 fw-semibold text-gray-700 mb-2">
                            Are you sure you want to delete this cost record?
                        </p>
                        <p class="fs-7 text-muted">
                            This will permanently remove all entries. This action cannot be undone.
                        </p>
                        <input type="hidden" id="delete_cost_id">
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirm_delete_cost_btn">
                            <span class="indicator-label">
                                <i class="ki-outline ki-trash fs-4 me-1"></i> Delete
                            </span>
                            <span class="indicator-progress">
                                Deleting...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Delete Cost Modal-->
    @endif
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
        window.CostRecordsConfig = {
            isAdmin: @json($isAdmin),
            userBranchId: @json(auth()->user()->branch_id),
            todayDate: "{{ now()->format('d-m-Y') }}",
            hasBranchTabs: @json($isAdmin && $branches->count() > 1),
            branches: @json($branches->map(fn($b) => ['id' => $b->id, 'name' => $b->branch_name, 'prefix' => $b->branch_prefix])),
            costTypes: @json($costTypes->map(fn($ct) => ['id' => $ct->id, 'name' => $ct->name, 'description' => $ct->description])),
            routes: {
                costsData: "{{ route('reports.cost-records.data') }}",
                exportCosts: "{{ route('reports.cost-records.export') }}",
                costTypes: "{{ route('costs.types') }}",
                storeCost: "{{ route('costs.store') }}",
                checkTodayCost: "{{ route('costs.check-today') }}",
                costSummary: "{{ route('reports.cost-summary') }}",
                @if ($isAdmin)
                    showCost: "{{ route('costs.show', ':id') }}",
                    updateCost: "{{ route('costs.update', ':id') }}",
                    deleteCost: "{{ route('costs.destroy', ':id') }}"
                @endif
            },
            csrfToken: "{{ csrf_token() }}"
        };
    </script>
    <script src="{{ asset('js/reports/cost-records/index.js') }}"></script>
    <script>
        document.getElementById("reports_menu")?.classList.add("here", "show");
        document.getElementById("cost_records_link")?.classList.add("active");
    </script>
@endpush
