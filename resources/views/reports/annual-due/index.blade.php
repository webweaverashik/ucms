@extends('layouts.app')

@section('title', 'Annual Due Reports')

@push('page-css')
    <link rel="stylesheet" href="{{ asset('css/reports/annual-due/index.css') }}">
@endpush

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Annual Due Reports
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
            <li class="breadcrumb-item text-muted">Annual Due</li>
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
                    Annual Due Report
                </h3>
            </div>
        </div>
        <!--end::Card Header-->

        <!--begin::Card Body-->
        <div class="card-body pt-0">
            <!--begin::Filter Form-->
            <form id="annual_due_form" class="row g-4 align-items-end mb-8">
                {{-- Branch Selection --}}
                <div class="col-lg-3 col-md-6">
                    <label for="branch_id" class="form-label fw-semibold required">Branch</label>
                    <div class="input-group input-group-solid flex-nowrap">
                        <span class="input-group-text">
                            <i class="ki-outline ki-bank fs-3"></i>
                        </span>
                        <select id="branch_id"
                            class="form-select form-select-solid rounded-start-0 border-start"
                            name="branch_id" data-control="select2"
                            data-placeholder="Select branch" data-hide-search="true">
                            <option value="">Select Branch</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">
                                    {{ $branch->branch_name }} ({{ $branch->branch_prefix }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Year Selection --}}
                <div class="col-lg-3 col-md-6">
                    <label for="year_select" class="form-label fw-semibold required">Select Year</label>
                    <div class="input-group input-group-solid flex-nowrap">
                        <span class="input-group-text">
                            <i class="ki-outline ki-calendar fs-3"></i>
                        </span>
                        <select id="year_select"
                            class="form-select form-select-solid rounded-start-0 border-start"
                            name="year" data-control="select2"
                            data-placeholder="Select year" data-hide-search="true">
                            <option value="">Select Year</option>
                            @for ($year = date('Y'); $year >= 2025; $year--)
                                <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>

                {{-- Generate Button --}}
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

            <!--begin::Loader-->
            <div id="report_loader" class="text-center py-10 d-none">
                <div class="spinner-border text-primary" role="status"
                    style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted fw-semibold">Generating report...</p>
            </div>
            <!--end::Loader-->

            <!--begin::Report Container (hidden until data loads)-->
            <div id="report_container" class="d-none">

                <!--begin::Stats Cards-->
                <div id="stats_container" class="row g-5 mb-8">
                    {{-- Rendered by JS --}}
                </div>
                <!--end::Stats Cards-->

                <!--begin::Tabs-->
                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="report_tabs"
                    role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active fw-bold" data-bs-toggle="tab"
                            href="#tuition_summary_tab" role="tab">
                            <i class="ki-outline ki-chart fs-4 me-1"></i> Tuition Summary
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link fw-bold" data-bs-toggle="tab"
                            href="#tuition_detailed_tab" role="tab">
                            <i class="ki-outline ki-notepad fs-4 me-1"></i> Tuition Detailed
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link fw-bold" data-bs-toggle="tab"
                            href="#other_fees_tab" role="tab">
                            <i class="ki-outline ki-bill fs-4 me-1"></i> Invoice Type-wise Due
                            <span class="badge badge-sm badge-light-warning ms-1" id="other_fees_badge"></span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="report_tab_content">

                    <!--begin::Tuition Summary Tab-->
                    <div class="tab-pane fade show active" id="tuition_summary_tab" role="tabpanel">
                        {{-- Table heading row with export button --}}
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h5 class="fw-bold m-0">
                                <i class="ki-outline ki-chart-simple fs-4 text-primary me-2"></i>
                                Tuition Fee Summary — Class × Month
                            </h5>
                            <button type="button" class="btn btn-light-success btn-sm"
                                onclick="KTAnnualDueReport.exportExcel('tuition_summary');">
                                <i class="ki-outline ki-file-down fs-4 me-1"></i> Export to Excel
                            </button>
                        </div>
                        <div id="tuition_summary_container">
                            {{-- Rendered by JS --}}
                        </div>
                    </div>
                    <!--end::Tuition Summary Tab-->

                    <!--begin::Tuition Detailed Tab-->
                    <div class="tab-pane fade" id="tuition_detailed_tab" role="tabpanel">
                        {{-- Table heading row with export button --}}
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h5 class="fw-bold m-0">
                                <i class="ki-outline ki-notepad fs-4 text-primary me-2"></i>
                                Tuition Fee Detailed Breakdown
                            </h5>
                            <button type="button" class="btn btn-light-success btn-sm"
                                onclick="KTAnnualDueReport.exportExcel('tuition_detailed');">
                                <i class="ki-outline ki-file-down fs-4 me-1"></i> Export to Excel
                            </button>
                        </div>

                        {{-- Tuition Detailed Filters --}}
                        <div class="row g-3 mb-5" id="tuition_filters_row">
                            <div class="col-lg-4 col-md-6">
                                <div class="position-relative">
                                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"
                                        style="top:50%;transform:translateY(-50%);"></i>
                                    <input type="text" id="tuition_search"
                                        class="form-control form-control-solid ps-12"
                                        placeholder="Search class or batch...">
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <select id="tuition_month_filter"
                                    class="form-select form-select-solid"
                                    data-placeholder="All Months" data-allow-clear="true"
                                    data-hide-search="true">
                                    <option value="">All Months</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <select id="tuition_class_filter"
                                    class="form-select form-select-solid"
                                    data-placeholder="All Classes" data-allow-clear="true"
                                    data-hide-search="true">
                                    <option value="">All Classes</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <button type="button" id="tuition_clear_btn"
                                    class="btn btn-light-danger w-100">
                                    <i class="ki-outline ki-cross fs-4 me-1"></i> Clear
                                </button>
                            </div>
                        </div>
                        <div id="tuition_detailed_container">
                            {{-- Rendered by JS --}}
                        </div>
                    </div>
                    <!--end::Tuition Detailed Tab-->

                    <!--begin::Other Fees Tab-->
                    <div class="tab-pane fade" id="other_fees_tab" role="tabpanel">

                        {{-- Other Fees Summary with export --}}
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h5 class="fw-bold m-0">
                                <i class="ki-outline ki-chart-simple fs-4 text-warning me-2"></i>
                                Invoice Type Summary
                            </h5>
                            <button type="button" class="btn btn-light-success btn-sm"
                                onclick="KTAnnualDueReport.exportExcel('other_summary');">
                                <i class="ki-outline ki-file-down fs-4 me-1"></i> Export to Excel
                            </button>
                        </div>
                        <div id="other_summary_container" class="mb-8">
                            {{-- Rendered by JS --}}
                        </div>

                        <div class="separator separator-dashed mb-8"></div>

                        {{-- Other Fees Detailed with export --}}
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h5 class="fw-bold m-0">
                                <i class="ki-outline ki-notepad fs-4 text-warning me-2"></i>
                                Invoice Type Detailed Breakdown
                            </h5>
                            <button type="button" class="btn btn-light-success btn-sm"
                                onclick="KTAnnualDueReport.exportExcel('other_detailed');">
                                <i class="ki-outline ki-file-down fs-4 me-1"></i> Export to Excel
                            </button>
                        </div>

                        {{-- Other Detailed Filters --}}
                        <div class="row g-3 mb-5" id="other_filters_row">
                            <div class="col-lg-3 col-md-6">
                                <div class="position-relative">
                                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"
                                        style="top:50%;transform:translateY(-50%);"></i>
                                    <input type="text" id="other_search"
                                        class="form-control form-control-solid ps-12"
                                        placeholder="Search...">
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <select id="other_month_filter"
                                    class="form-select form-select-solid"
                                    data-placeholder="All Months" data-allow-clear="true"
                                    data-hide-search="true">
                                    <option value="">All Months</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <select id="other_type_filter"
                                    class="form-select form-select-solid"
                                    data-placeholder="All Types" data-allow-clear="true"
                                    data-hide-search="true">
                                    <option value="">All Types</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <select id="other_class_filter"
                                    class="form-select form-select-solid"
                                    data-placeholder="All Classes" data-allow-clear="true"
                                    data-hide-search="true">
                                    <option value="">All Classes</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <button type="button" id="other_clear_btn"
                                    class="btn btn-light-danger w-100">
                                    <i class="ki-outline ki-cross fs-4 me-1"></i> Clear
                                </button>
                            </div>
                        </div>

                        <div id="other_detailed_container">
                            {{-- Rendered by JS --}}
                        </div>
                    </div>
                    <!--end::Other Fees Tab-->

                </div>
                <!--end::Tabs-->

            </div>
            <!--end::Report Container-->

            <!--begin::Empty State-->
            <div id="empty_state" class="text-center py-15 d-none">
                <i class="ki-outline ki-information-3 fs-3x text-gray-400"></i>
                <p class="text-gray-500 fs-5 fw-semibold mt-4">
                    No due records found for the selected criteria.
                </p>
            </div>
            <!--end::Empty State-->

        </div>
        <!--end::Card Body-->
    </div>
    <!--end::Card-->

    <!--begin::Invoices Modal-->
    <div class="modal fade" id="kt_modal_invoices" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <!--begin::Modal Header-->
                <div class="modal-header py-4">
                    <h3 class="modal-title fw-bold fs-5" id="invoices_modal_title">
                        <i class="ki-outline ki-document fs-3 text-primary me-2"></i>
                        Due Invoices
                    </h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <!--end::Modal Header-->

                <!--begin::Modal Body-->
                <div class="modal-body py-4">
                    <!--begin::Modal Loader-->
                    <div id="invoices_modal_loader" class="text-center py-10 d-none">
                        <div class="spinner-border text-primary" role="status" style="width: 2.5rem; height: 2.5rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted fw-semibold fs-7">Loading invoices...</p>
                    </div>
                    <!--end::Modal Loader-->

                    <!--begin::Modal Content-->
                    <div id="invoices_modal_body">
                        {{-- Rendered by JS --}}
                    </div>
                    <!--end::Modal Content-->
                </div>
                <!--end::Modal Body-->

                <!--begin::Modal Footer-->
                <div class="modal-footer py-3">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
                <!--end::Modal Footer-->
            </div>
        </div>
    </div>
    <!--end::Invoices Modal-->
@endsection

@push('vendor-js')
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
@endpush

@push('page-js')
    <script>
        var reportDataUrl     = "{{ route('reports.annual-due.data') }}";
        var invoicesDataUrl   = "{{ route('reports.annual-due.invoices') }}";
        var invoiceShowBaseUrl = "{{ url('invoices') }}";
        var studentShowBaseUrl = "{{ url('students') }}";
    </script>
    <script src="{{ asset('js/reports/annual-due/index.js') }}"></script>
    <script>
        document.getElementById("reports_menu")?.classList.add("here", "show");
        document.getElementById("annual_due_link")?.classList.add("active");
    </script>
@endpush
