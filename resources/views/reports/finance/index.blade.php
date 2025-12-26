@extends('layouts.app')

@section('title', 'Finance Reports - Revenue vs Cost')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
        }

        .summary-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
        }

        .daterangepicker td.disabled-date {
            background: #fff5f5 !important;
            color: #dc3545 !important;
            text-decoration: line-through;
            pointer-events: none;
            opacity: 0.6;
        }

        .amount-edit-btn {
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        tr:hover .amount-edit-btn {
            opacity: 1;
        }

        .export-section {
            display: none;
        }

        .export-section.show {
            display: flex;
        }

        /* Collector column styling */
        .collector-col {
            background-color: rgba(80, 205, 137, 0.05) !important;
        }

        /* Table header styling for multi-row headers */
        .table thead th[rowspan] {
            vertical-align: middle;
        }

        /* Responsive table improvements */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Inactive class column styling */
        .table thead th .badge-light-danger {
            padding: 2px 4px;
            vertical-align: middle;
        }

        .table thead th .text-gray-500 {
            font-style: italic;
        }

        /* Collector summary cards */
        .collector-summary-card {
            transition: all 0.2s ease;
        }

        .collector-summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
    </style>
@endpush

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Finance Reports
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
            <li class="breadcrumb-item text-muted">Revenue vs Cost</li>
        </ul>
    </div>
@endsection

@section('content')
    <!--begin::Card with Tabs-->
    <div class="card">
        <!--begin::Card Header with Tabs-->
        <div class="card-header card-header-stretch">
            <div class="card-title">
                <h3 class="fw-bold m-0">Finance Reports</h3>
            </div>
            <div class="card-toolbar">
                <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" role="tab" href="#tab_revenue_cost">
                            <i class="ki-outline ki-chart-simple fs-4 me-2"></i>
                            Revenue vs Cost
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" role="tab" href="#tab_cost_records"
                            id="cost_records_tab">
                            <i class="ki-outline ki-wallet fs-4 me-2"></i>
                            Cost Records
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <!--end::Card Header-->

        <!--begin::Card Body-->
        <div class="card-body">
            <div class="tab-content" id="financeTabContent">
                <!--begin::Tab - Revenue vs Cost-->
                <div class="tab-pane fade show active" id="tab_revenue_cost" role="tabpanel">
                    <!--begin::Filter Form-->
                    <form id="finance_report_form" class="row g-3 align-items-end mb-8">
                        <!-- Date Range -->
                        <div class="col-lg-4 col-md-6">
                            <label for="finance_daterangepicker" class="form-label fw-semibold required">Select Date
                                Range</label>
                            <div class="input-group input-group-solid flex-nowrap">
                                <span class="input-group-text">
                                    <i class="ki-outline ki-calendar fs-3"></i>
                                </span>
                                <input type="text" class="form-control form-control-solid rounded-start-0 border-start"
                                    placeholder="Pick date range" id="finance_daterangepicker" name="date_range" readonly>
                            </div>
                        </div>

                        <!-- Branch Selection -->
                        <div class="col-lg-3 col-md-6">
                            <label for="branch_id" class="form-label fw-semibold required">Branch</label>
                            <div class="input-group input-group-solid flex-nowrap">
                                <span class="input-group-text">
                                    <i class="ki-outline ki-bank fs-3"></i>
                                </span>
                                <select id="branch_id" class="form-select form-select-solid rounded-start-0 border-start"
                                    name="branch_id" data-control="select2" data-placeholder="Select branch"
                                    data-hide-search="true" @if (!$isAdmin) disabled @endif>
                                    @if ($isAdmin)
                                        <option value="">-- Select Branch --</option>
                                    @endif
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}"
                                            @if (!$isAdmin) selected @endif>
                                            {{ $branch->branch_name }} ({{ $branch->branch_prefix }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if (!$isAdmin)
                                <input type="hidden" name="branch_id" value="{{ $branches->first()->id ?? '' }}">
                            @endif
                        </div>

                        <!-- Buttons -->
                        <div class="col-lg-5 col-md-12">
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-primary" id="generate_report_btn">
                                    <span class="indicator-label">
                                        <i class="ki-outline ki-document fs-4 me-1"></i>
                                        Generate Report
                                    </span>
                                    <span class="indicator-progress">
                                        Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                                {{-- Add Cost button visible to all users --}}
                                <button type="button" class="btn btn-success" id="add_cost_btn">
                                    <i class="ki-outline ki-plus fs-4 me-1"></i>
                                    Add Cost
                                </button>
                            </div>
                        </div>
                    </form>
                    <!--end::Filter Form-->

                    <div class="separator separator-dashed mb-8"></div>

                    <!-- Loader -->
                    <div id="finance_report_loader" class="text-center py-10 d-none">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted fw-semibold">Generating report...</p>
                    </div>

                    <!-- Summary Cards -->
                    <div id="summary_cards" class="row g-4 mb-8 d-none">
                        <div class="col-md-3 col-sm-6">
                            <div class="summary-card card bg-primary text-white h-100">
                                <div class="card-body d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-white opacity-75 fs-7 fw-semibold mb-1">Total Revenue</p>
                                        <h3 id="total_revenue" class="text-white fw-bold mb-0">৳0</h3>
                                    </div>
                                    <div class="bg-white bg-opacity-20 rounded p-3">
                                        <i class="ki-outline ki-dollar fs-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="summary-card card bg-danger text-white h-100">
                                <div class="card-body d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-white opacity-75 fs-7 fw-semibold mb-1">Total Cost</p>
                                        <h3 id="total_cost" class="text-white fw-bold mb-0">৳0</h3>
                                    </div>
                                    <div class="bg-white bg-opacity-20 rounded p-3">
                                        <i class="ki-outline ki-wallet fs-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="summary-card card bg-success text-white h-100">
                                <div class="card-body d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-white opacity-75 fs-7 fw-semibold mb-1">Net Profit</p>
                                        <h3 id="net_profit" class="text-white fw-bold mb-0">৳0</h3>
                                    </div>
                                    <div class="bg-white bg-opacity-20 rounded p-3">
                                        <i class="ki-outline ki-chart-line-up fs-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="summary-card card bg-info text-white h-100">
                                <div class="card-body d-flex align-items-center justify-content-between">
                                    <div>
                                        <p class="text-white opacity-75 fs-7 fw-semibold mb-1">Profit Margin</p>
                                        <h3 id="profit_margin" class="text-white fw-bold mb-0">0%</h3>
                                    </div>
                                    <div class="bg-white bg-opacity-20 rounded p-3">
                                        <i class="ki-outline ki-graph-up fs-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart Section (Full Width) -->
                    <div id="chart_section" class="mb-8 d-none">
                        <div class="bg-light rounded p-5">
                            <h4 class="fw-bold text-gray-800 mb-4">Revenue vs Cost Chart</h4>
                            <div class="chart-container">
                                <canvas id="finance_report_graph"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Export Buttons (Before Table) -->
                    <div id="export_buttons" class="export-section gap-3 mb-5">
                        <button type="button" class="btn btn-light-success" id="export_excel_btn">
                            <i class="ki-outline ki-file-down fs-4 me-2"></i>
                            Export Excel
                        </button>
                        <button type="button" class="btn btn-light-warning" id="export_chart_btn">
                            <i class="ki-outline ki-picture fs-4 me-2"></i>
                            Download Chart
                        </button>
                    </div>

                    <!-- Report Table (includes Collector-wise breakdown) -->
                    <div id="finance_report_result"></div>
                </div>
                <!--end::Tab - Revenue vs Cost-->

                <!--begin::Tab - Cost Records-->
                <div class="tab-pane fade" id="tab_cost_records" role="tabpanel">
                    <!--begin::Toolbar-->
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4 class="fw-bold text-gray-800 mb-0">Daily Cost Records</h4>
                        <div class="d-flex gap-2">
                            {{-- Add Cost button visible to all users --}}
                            <button type="button" class="btn btn-sm btn-success" id="add_cost_btn_tab">
                                <i class="ki-outline ki-plus fs-4 me-1"></i>
                                Add Cost
                            </button>
                            <button type="button" class="btn btn-sm btn-light-primary" id="refresh_costs_btn">
                                <i class="ki-outline ki-arrows-circle fs-4 me-1"></i>
                                Refresh
                            </button>
                        </div>
                    </div>
                    <!--end::Toolbar-->

                    <div class="separator separator-dashed mb-5"></div>

                    <!--begin::DataTable-->
                    <div class="table-responsive">
                        <table id="costs_datatable"
                            class="table table-row-bordered table-row-gray-200 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-muted bg-light">
                                    <th class="ps-4 rounded-start min-w-100px">Date</th>
                                    <th class="min-w-125px">Branch</th>
                                    <th class="min-w-100px text-end">Amount</th>
                                    <th class="min-w-200px">Description</th>
                                    <th class="min-w-100px">Created By</th>
                                    @if ($isAdmin)
                                        <th class="pe-4 rounded-end text-center min-w-100px">Actions</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <!--end::DataTable-->
                </div>
                <!--end::Tab - Cost Records-->
            </div>
        </div>
        <!--end::Card Body-->
    </div>
    <!--end::Card with Tabs-->

    <!--begin::Add/Edit Cost Modal (Available to all users for Add, Admin only for Edit) -->
    <div class="modal fade" id="cost_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="cost_modal_title" class="modal-title fw-bold">Add Daily Cost</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>
                <form id="cost_form">
                    <div class="modal-body py-10 px-lg-12">
                        <input type="hidden" id="cost_id" value="">

                        <!-- Branch Selection -->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Branch</label>
                            @if ($isAdmin)
                                {{-- Admin can select any branch --}}
                                <select id="cost_branch_id" name="branch_id" class="form-select form-select-solid"
                                    data-control="select2" data-placeholder="Select branch first"
                                    data-dropdown-parent="#cost_modal" data-hide-search="true">
                                    <option value="">-- Select Branch --</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">
                                            {{ $branch->branch_name }} ({{ $branch->branch_prefix }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text text-muted">Select branch first to enable date selection</div>
                            @else
                                {{-- Non-admin users: branch is pre-selected and readonly --}}
                                <input type="text" class="form-control form-control-solid bg-secondary"
                                    value="{{ $branches->first()->branch_name ?? '' }} ({{ $branches->first()->branch_prefix ?? '' }})"
                                    readonly disabled>
                                <input type="hidden" id="cost_branch_id" name="branch_id"
                                    value="{{ $branches->first()->id ?? '' }}">
                                <div class="form-text text-muted">Your branch is automatically selected</div>
                            @endif
                        </div>

                        <!-- Date & Amount Row -->
                        <div class="row mb-7">
                            <!-- Date -->
                            <div class="col-md-6">
                                <div class="fv-row">
                                    <label class="required fw-semibold fs-6 mb-2">Date</label>
                                    <input type="text" id="cost_date" name="cost_date"
                                        class="form-control form-control-solid {{ $isAdmin ? 'bg-secondary' : '' }}"
                                        placeholder="{{ $isAdmin ? 'Select branch first' : 'Select date' }}" readonly
                                        {{ $isAdmin ? 'disabled' : '' }}>
                                    <div id="date_help_text" class="form-text text-muted">
                                        {{ $isAdmin ? 'Select branch first' : 'Select an available date' }}
                                    </div>
                                </div>
                            </div>

                            <!-- Amount -->
                            <div class="col-md-6">
                                <div class="fv-row">
                                    <label class="required fw-semibold fs-6 mb-2">Amount</label>
                                    <div class="input-group input-group-solid">
                                        <span class="input-group-text">৳</span>
                                        <input type="number" id="cost_amount" name="amount"
                                            class="form-control form-control-solid rounded-start-0 border-start" min="1" step="1"
                                            placeholder="0">
                                    </div>
                                    <div class="form-text text-muted">Whole number only</div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Description</label>
                            <textarea id="cost_description" name="description" class="form-control form-control-solid" rows="3"
                                placeholder="Enter cost description..." maxlength="500"></textarea>
                            <div class="form-text text-muted">Maximum 500 characters</div>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="save_cost_btn">
                            <span class="indicator-label">
                                <i class="ki-outline ki-check fs-4 me-1"></i>
                                Save Cost
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
    <!--end::Add/Edit Cost Modal-->

    @if ($isAdmin)
        <!--begin::Delete Confirmation Modal-->
        <div class="modal fade" id="delete_cost_modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title fw-bold">Delete Cost Record</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary"
                            data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-10 text-center">
                        <div class="mb-5">
                            <i class="ki-outline ki-trash text-danger fs-5x"></i>
                        </div>
                        <p class="fs-5 fw-semibold text-gray-700 mb-2">Are you sure you want to delete this cost record?
                        </p>
                        <p class="fs-7 text-muted">This action cannot be undone.</p>
                        <input type="hidden" id="delete_cost_id">
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirm_delete_cost_btn">
                            <span class="indicator-label">
                                <i class="ki-outline ki-trash fs-4 me-1"></i>
                                Delete
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
        <!--end::Delete Confirmation Modal-->

        <!--begin::Inline Edit Amount Modal-->
        <div class="modal fade" id="inline_edit_modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal">
                <div class="modal-content">
                    <div class="modal-header py-4">
                        <h4 class="modal-title fw-bold">Edit Amount</h4>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary"
                            data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-6">
                        <input type="hidden" id="inline_edit_cost_id">
                        <div class="input-group input-group-solid">
                            <span class="input-group-text">৳</span>
                            <input type="number" id="inline_edit_amount" class="form-control form-control-solid rounded-start-0 border-start"
                                min="1" step="1" placeholder="0">
                        </div>
                        <div class="form-text text-muted">Enter whole number only</div>
                    </div>
                    <div class="modal-footer py-4 flex-center">
                        <button type="button" class="btn btn-sm btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-primary" id="save_inline_edit_btn">
                            <span class="indicator-label">
                                <i class="ki-outline ki-check fs-4 me-1"></i>
                                Save
                            </span>
                            <span class="indicator-progress">
                                <span class="spinner-border spinner-border-sm align-middle"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Inline Edit Amount Modal-->
    @endif
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
@endpush

@push('page-js')
    <script>
        // Configuration passed to JavaScript
        window.FinanceReportConfig = {
            isAdmin: @json($isAdmin),
            userBranchId: @json(auth()->user()->branch_id),
            routes: {
                generate: "{{ route('reports.finance.generate') }}",
                costs: "{{ route('reports.finance.costs') }}",
                // Store cost available to all users
                storeCost: "{{ route('costs.store') }}",
                getCostByDate: "{{ route('costs.by-date') }}",
                @if ($isAdmin)
                    // Edit/Delete only for admin
                    showCost: "{{ route('costs.show', ':id') }}",
                    updateCost: "{{ route('costs.update', ':id') }}",
                    deleteCost: "{{ route('costs.destroy', ':id') }}"
                @endif
            },
            csrfToken: "{{ csrf_token() }}"
        };
    </script>
    <script src="{{ asset('js/reports/finance/index.js') }}"></script>
    <script>
        // Activate menu items
        document.getElementById("reports_menu")?.classList.add("here", "show");
        document.getElementById("finance_report_link")?.classList.add("active");
    </script>
@endpush
