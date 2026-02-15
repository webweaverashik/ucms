@extends('layouts.app')

@section('title', 'Finance Reports - Revenue vs Cost')

@push('page-css')
<link rel="stylesheet" href="{{ asset('css/reports/finance/index.css') }}">
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
<!--begin::Card-->
<div class="card">
    <!--begin::Card Header-->
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3 class="fw-bold m-0">
                <i class="ki-outline ki-chart-simple fs-2 text-primary me-2"></i>
                Revenue vs Cost Report
            </h3>
        </div>
        <div class="card-toolbar">
            {{-- <a href="{{ route('reports.cost-records.index') }}" class="btn btn-light-primary">
                <i class="ki-outline ki-wallet fs-4 me-1"></i> View Cost Records
            </a> --}}
        </div>
    </div>
    <!--end::Card Header-->

    <!--begin::Card Body-->
    <div class="card-body pt-0">
        <!--begin::Filter Form-->
        <form id="finance_report_form" class="row g-3 align-items-end mb-8">
            <!-- Date Range -->
            <div class="col-lg-4 col-md-6">
                <label for="finance_daterangepicker" class="form-label fw-semibold required">Select Date Range</label>
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
                        <option value="{{ $branch->id }}" @if (!$isAdmin) selected @endif>
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
                            <i class="ki-outline ki-document fs-4 me-1"></i> Generate Report
                        </span>
                        <span class="indicator-progress">
                            Please wait...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
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
                <i class="ki-outline ki-file-down fs-4 me-2"></i> Export Excel
            </button>
            <button type="button" class="btn btn-light-warning" id="export_chart_btn">
                <i class="ki-outline ki-picture fs-4 me-2"></i> Download Chart
            </button>
        </div>

        <!-- Report Table -->
        <div id="finance_report_result"></div>
    </div>
    <!--end::Card Body-->
</div>
<!--end::Card-->
@endsection

@push('vendor-js')
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
@endpush

@push('page-js')
<script>
    window.FinanceReportConfig = {
        isAdmin: @json($isAdmin),
        userBranchId: @json(auth()->user()->branch_id),
        todayDate: "{{ now()->format('d-m-Y') }}",
        routes: {
            generate: "{{ route('reports.finance.generate') }}",
        },
        csrfToken: "{{ csrf_token() }}"
    };
</script>
<script src="{{ asset('js/reports/finance/index.js') }}"></script>
<script>
    document.getElementById("reports_menu")?.classList.add("here", "show");
    document.getElementById("finance_report_link")?.classList.add("active");
</script>
@endpush
