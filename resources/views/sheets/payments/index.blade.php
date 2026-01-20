@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        /* Smooth tab transitions */
        #branchTabs .nav-link {
            transition: all 0.3s ease;
            position: relative;
        }

        #branchTabs .nav-link.active {
            transition: all 0.3s ease;
        }

        #branchTabs .nav-link:hover:not(.active) {
            background-color: rgba(0, 0, 0, 0.03);
        }

        /* Smooth table loading transition */
        .card-body {
            transition: opacity 0.2s ease-in-out;
        }

        .card-body.loading {
            opacity: 0.5;
            pointer-events: none;
        }

        /* DataTable processing indicator */
        .dataTables_processing {
            background: rgba(255, 255, 255, 0.9) !important;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 20px !important;
            z-index: 999;
        }

        /* Table fade effect */
        #kt_sheet_payments_table {
            transition: opacity 0.15s ease-in-out;
        }

        #kt_sheet_payments_table.table-loading {
            opacity: 0.3;
        }
    </style>
@endpush


@extends('layouts.app')

@section('title', 'All Sheet Payments')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Payments
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
                    Payment Info </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Sheet Payments </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                    <input type="text" data-sheet-payments-table-filter="search"
                        class="form-control form-control-solid w-md-350px ps-12" placeholder="Search in payments">
                </div>
                <!--end::Search-->

                <!--begin::Export hidden buttons-->
                <div id="kt_hidden_export_buttons" class="d-none"></div>
                <!--end::Export buttons-->
            </div>
            <!--begin::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-transaction-table-toolbar="base">
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
                        <div class="px-7 py-5" data-sheet-payments-table-filter="form">
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Sheet Group:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-sheet-payments-table-filter="sheet_group" data-hide-search="true">
                                    <option></option>
                                    @foreach ($sheet_groups as $sheet)
                                        <option value="{{ $sheet->class->name }} ({{ $sheet->class->class_numeral }})">
                                            {{ $sheet->class->name }} ({{ $sheet->class->class_numeral }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Payment Type:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-sheet-payments-table-filter="payment_status" data-hide-search="true">
                                    <option></option>
                                    <option value="T_due">Due</option>
                                    <option value="T_partially_paid">Partial Paid</option>
                                    <option value="T_paid">Full Paid</option>
                                </select>
                            </div>
                            <!--end::Input group-->

                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-kt-menu-dismiss="true" data-sheet-payments-table-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                    data-sheet-payments-table-filter="filter">Apply</button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Menu 1-->
                    <!--end::Filter-->

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
                </div>
                <!--end::Toolbar-->

            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">

            @if ($isAdmin && $branches->count() > 0)
                <!--begin::Branch Tabs for Admin-->
                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x fs-6 mb-5" id="branchTabs" role="tablist">
                    @foreach ($branches as $index => $branch)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link fw-bold {{ $index === 0 ? 'active' : '' }}"
                                id="tab-branch-{{ $branch->id }}" data-bs-toggle="tab"
                                href="#kt_tab_branch_{{ $branch->id }}" role="tab"
                                aria-controls="kt_tab_branch_{{ $branch->id }}"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                data-branch-id="{{ $branch->id }}">
                                <i class="ki-outline ki-bank fs-4 me-1"></i>
                                {{ ucfirst($branch->branch_name) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                <!--end::Branch Tabs for Admin-->
            @endif
            
            <!--begin::Table-->
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                id="kt_sheet_payments_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">SL</th>
                        <th class="w-200px">Sheet Group</th>
                        <th class="w-200px">Invoice No.</th>
                        <th>Amount (Tk)</th>
                        <th class="d-none">Status (Filter)</th>
                        <th>Status</th>
                        <th>Paid (Tk)</th>
                        <th class="w-350px">Student</th>
                        <th>Payment Date</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                </tbody>
            </table>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->
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
        // Pass routes and config to JavaScript
        var sheetPaymentsConfig = {
            dataUrl: "{{ route('sheet-payments.data') }}",
            exportUrl: "{{ route('sheet-payments.export') }}",
            isAdmin: {{ $isAdmin ? 'true' : 'false' }}
        };
    </script>
    <script src="{{ asset('js/sheets/payments/index.js') }}"></script>

    <script>
        document.getElementById("sheets_menu").classList.add("here", "show");
        document.getElementById("sheet_payments_link").classList.add("active");
    </script>
@endpush
