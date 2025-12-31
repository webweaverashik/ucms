@extends('layouts.app')

@section('title', 'Finance Reports - Revenue vs Cost')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="{{ asset('css/reports/finance/index.css') }}">
    <style>
        /* Tagify custom styles */
        .tagify {
            --tag-bg: var(--bs-primary);
            --tag-text-color: #fff;
            --tag-hover: var(--bs-primary);
            --tag-remove-btn-color: #fff;
            border: 1px solid var(--bs-gray-300);
            border-radius: 0.475rem;
            min-height: 44px;
        }

        .tagify--focus {
            border-color: var(--bs-primary);
        }

        .tagify__tag {
            border-radius: 0.375rem;
        }

        .tagify__dropdown {
            border-radius: 0.475rem;
            border: 1px solid var(--bs-gray-300);
            box-shadow: 0 0 50px 0 rgba(82, 63, 105, .15);
        }

        .tagify__dropdown__item--active {
            background: var(--bs-primary);
            color: #fff;
        }

        /* Cost entries styling */
        .cost-entry-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: var(--bs-gray-100);
            border-radius: 0.475rem;
            margin-bottom: 8px;
            animation: slideIn 0.2s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cost-entry-row .cost-type-name {
            flex: 1;
            font-weight: 500;
            color: var(--bs-gray-800);
        }

        .cost-entry-row .cost-type-badge {
            background: var(--bs-primary);
            color: #fff;
            padding: 4px 10px;
            border-radius: 0.375rem;
            font-size: 12px;
            font-weight: 600;
        }

        .cost-entry-row .amount-input {
            width: 150px;
        }

        /* Total section */
        .cost-total-section {
            background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-info) 100%);
            color: #fff;
            padding: 15px 20px;
            border-radius: 0.475rem;
            margin-top: 15px;
        }

        .cost-total-section .total-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .cost-total-section .total-amount {
            font-size: 24px;
            font-weight: 700;
        }

        /* Entry badges in table */
        .entry-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--bs-light);
            border: 1px solid var(--bs-gray-300);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin: 2px;
        }

        .entry-badge .type-name {
            color: var(--bs-gray-700);
            font-weight: 500;
        }

        .entry-badge .type-amount {
            color: var(--bs-primary);
            font-weight: 600;
        }

        /* Edit modal entry rows */
        .edit-entry-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: var(--bs-gray-100);
            border-radius: 0.475rem;
            margin-bottom: 8px;
        }

        .edit-entry-row .entry-type-name {
            flex: 1;
            font-weight: 500;
            color: var(--bs-gray-800);
        }

        .edit-entry-row .entry-amount-input {
            width: 150px;
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

                    <!-- Collector Summary Section -->
                    <div id="collector_summary_section" class="mb-8 d-none">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h4 class="fw-bold text-gray-800 mb-0">
                                <i class="ki-outline ki-people fs-3 text-success me-2"></i>
                                Collector-wise Collection Summary
                            </h4>
                            <span id="collector_count_badge" class="badge badge-light-success fs-7"></span>
                        </div>
                        <div id="collector_summary_cards" class="row g-4"></div>
                    </div>

                    <!-- Chart Section -->
                    <div id="chart_section" class="mb-8 d-none">
                        <div class="bg-light rounded p-5">
                            <h4 class="fw-bold text-gray-800 mb-4">Revenue vs Cost Chart</h4>
                            <div class="chart-container">
                                <canvas id="finance_report_graph"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Export Buttons -->
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

                    <!-- Report Table -->
                    <div id="finance_report_result"></div>
                </div>
                <!--end::Tab - Revenue vs Cost-->

                <!--begin::Tab - Cost Records-->
                <div class="tab-pane fade" id="tab_cost_records" role="tabpanel">
                    <!--begin::Toolbar-->
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <h4 class="fw-bold text-gray-800 mb-0">Daily Cost Records</h4>
                        <div class="d-flex gap-2">
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
                                    <th class="min-w-250px">Cost Entries</th>
                                    <th class="min-w-100px text-end">Total Amount</th>
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

    <!--begin::Add Cost Modal-->
    <div class="modal fade" id="cost_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-700px">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="cost_modal_title" class="modal-title fw-bold">Add Daily Cost</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>
                <form id="cost_form">
                    <div class="modal-body py-10 px-lg-12" style="max-height: 70vh; overflow-y: auto;">
                        <!-- Branch Selection -->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Branch</label>
                            @if ($isAdmin)
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
                                <input type="text" class="form-control form-control-solid bg-secondary"
                                    value="{{ $branches->first()->branch_name ?? '' }} ({{ $branches->first()->branch_prefix ?? '' }})"
                                    readonly disabled>
                                <input type="hidden" id="cost_branch_id" name="branch_id"
                                    value="{{ $branches->first()->id ?? '' }}">
                            @endif
                        </div>

                        <!-- Date -->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Date</label>
                            <input type="text" id="cost_date" name="cost_date"
                                class="form-control form-control-solid {{ $isAdmin ? 'bg-secondary' : '' }}"
                                placeholder="{{ $isAdmin ? 'Select branch first' : 'Select date' }}" readonly
                                {{ $isAdmin ? 'disabled' : '' }}>
                            <div id="date_help_text" class="form-text text-muted">
                                {{ $isAdmin ? 'Select branch first' : 'Select an available date' }}
                            </div>
                        </div>

                        <!-- Cost Types Selection with Tagify -->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Cost Types</label>
                            <input type="text" id="cost_types_tagify" class="form-control form-control-solid"
                                placeholder="Select cost types...">
                            <div class="form-text text-muted">Select one or more cost types to add entries</div>
                        </div>

                        <!-- Cost Entries Container -->
                        <div id="cost_entries_container" class="mb-5">
                            <label class="fw-semibold fs-6 mb-3">Cost Entries</label>
                            <div id="cost_entries_list">
                                <div class="text-center text-muted py-5">
                                    <i class="ki-outline ki-information fs-3x text-gray-400 mb-3"></i>
                                    <p class="mb-0">Select cost types above to add entries</p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Cost -->
                        <div id="cost_total_section" class="cost-total-section d-none">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="total-label">Total Cost</span>
                                <span id="cost_total_amount" class="total-amount">৳0</span>
                            </div>
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
    <!--end::Add Cost Modal-->

    @if ($isAdmin)
        <!--begin::Edit Cost Modal-->
        <div class="modal fade" id="edit_cost_modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-650px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title fw-bold">Edit Cost Entries</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary"
                            data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <form id="edit_cost_form">
                        <div class="modal-body py-10 px-lg-12" style="max-height: 70vh; overflow-y: auto;">
                            <input type="hidden" id="edit_cost_id">
                            <!-- Cost Info -->
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

                            <!-- Entries List -->
                            <div class="mb-5">
                                <label class="fw-semibold fs-6 mb-3">Cost Entries</label>
                                <div id="edit_entries_list"></div>
                            </div>

                            <!-- Total -->
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
                                    <i class="ki-outline ki-check fs-4 me-1"></i>
                                    Update Cost
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
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary"
                            data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-10 text-center">
                        <div class="mb-5">
                            <i class="ki-outline ki-trash text-danger fs-5x"></i>
                        </div>
                        <p class="fs-5 fw-semibold text-gray-700 mb-2">Are you sure you want to delete this cost
                            record?
                        </p>
                        <p class="fs-7 text-muted">This will permanently remove all entries. This action cannot be
                            undone.
                        </p>
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
        <!--end::Delete Cost Modal-->
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
                costTypes: "{{ route('costs.types') }}",
                storeCost: "{{ route('costs.store') }}",
                getCostByDate: "{{ route('costs.by-date') }}",
                @if ($isAdmin)
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
