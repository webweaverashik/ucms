@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .dataTables_processing {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid #e4e6ef !important;
            border-radius: 0.475rem !important;
            padding: 1rem !important;
            z-index: 999 !important;
        }

        .table-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .export-loading {
            pointer-events: none;
            opacity: 0.6;
        }

        /* Custom SweetAlert width for delete confirmation */
        .swal-wide {
            width: 500px !important;
        }

        .swal-wide .swal2-html-container {
            white-space: pre-line;
            text-align: left;
        }
    </style>
@endpush

@extends('layouts.app')

@section('title', 'All Transactions')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Transactions
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
                    Payments Info
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
                Transactions
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
            $branchColors[$branch->id] = $badgeColors[$index % count($badgeColors)];
            $branchColors[$branch->branch_name] = $badgeColors[$index % count($badgeColors)];
        }

        // Preloading permissions checking
        $canApproveTxn = auth()->user()->can('transactions.approve');
        $canDeleteTxn = auth()->user()->can('transactions.delete');
        $canDownloadPayslip = auth()->user()->can('transactions.payslip.download');
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
                    <input type="text" data-transaction-table-filter="search"
                        class="form-control form-control-solid w-md-350px ps-12" placeholder="Search in transactions">
                </div>
                <!--end::Search-->
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
                        <div class="px-7 py-5" data-transaction-table-filter="form">
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Payment Type:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true" data-hide-search="true"
                                    id="payment_type_filter_select">
                                    <option></option>
                                    <option value="T_partial">Partial</option>
                                    <option value="T_full_paid">Full Paid</option>
                                    <option value="T_discounted">Discounted</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            @can('transactions.delete')
                            <!--begin::Show Deleted Toggle-->
                            <div class="mb-10">
                                <label class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" id="show_deleted_filter" value="1" />
                                    <span class="form-check-label fw-semibold text-gray-700">
                                        Show Deleted Only
                                    </span>
                                </label>
                                <div class="form-text text-muted mt-2">
                                    View only deleted transactions
                                </div>
                            </div>
                            <!--end::Show Deleted Toggle-->
                            @endcan
                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="reset"
                                    class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-kt-menu-dismiss="true" data-transaction-table-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6"
                                    data-kt-menu-dismiss="true" data-transaction-table-filter="filter">Apply</button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Menu 1-->
                    <!--begin::Export dropdown-->
                    <div class="dropdown">
                        <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                            data-kt-menu-placement="bottom-end" id="export_dropdown_btn">
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
                    @can('transactions.create')
                        <!--begin::Add subscription-->
                        <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#kt_modal_add_transaction">
                            <i class="ki-outline ki-plus fs-2"></i>New Transaction</a>
                        <!--end::Add subscription-->
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
                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="transactionBranchTabs"
                    role="tablist">
                    @foreach ($branches as $index => $branch)
                        @php
                            $branchTxnCount = $transactionCounts[$branch->id] ?? 0;
                            $tabBadgeColor = $badgeColors[$index % count($badgeColors)];
                        @endphp
                        <li class="nav-item" role="presentation">
                            <a class="nav-link fw-bold {{ $index === 0 ? 'active' : '' }}"
                                id="tab-txn-branch-{{ $branch->id }}" data-bs-toggle="tab"
                                href="#kt_tab_txn_branch_{{ $branch->id }}" role="tab"
                                aria-controls="kt_tab_txn_branch_{{ $branch->id }}"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                data-branch-id="{{ $branch->id }}">
                                <i class="ki-outline ki-bank fs-4 me-1"></i>
                                {{ ucfirst($branch->branch_name) }}
                                <span class="badge {{ $tabBadgeColor }} ms-2 branch-count-badge"
                                    data-branch-id="{{ $branch->id }}">{{ $branchTxnCount }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <!--end::Tabs for Admin-->
                <!--begin::Tab Content-->
                <div class="tab-content" id="transactionBranchTabsContent">
                    @foreach ($branches as $index => $branch)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                            id="kt_tab_txn_branch_{{ $branch->id }}" role="tabpanel"
                            aria-labelledby="tab-txn-branch-{{ $branch->id }}">
                            @include('transactions.partials.transactions-table-ajax', [
                                'tableId' => 'kt_transactions_table_branch_' . $branch->id,
                                'branchId' => $branch->id,
                                'showBranchColumn' => false,
                            ])
                        </div>
                    @endforeach
                </div>
                <!--end::Tab Content-->
            @else
                <!--begin::Single Table for Non-Admin-->
                @include('transactions.partials.transactions-table-ajax', [
                    'tableId' => 'kt_transactions_table',
                    'branchId' => $branchId,
                    'showBranchColumn' => false,
                ])
                <!--end::Single Table for Non-Admin-->
            @endif
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->

    <!--begin::Modal - Add Transaction-->
    <div class="modal fade" id="kt_modal_add_transaction" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-750px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_add_transaction_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Add Transaction</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-add-transaction-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"> </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_add_transaction_form" class="form" action="{{ route('transactions.store') }}"
                        method="POST">
                        @csrf
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_transaction_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_transaction_header"
                            data-kt-scroll-wrappers="#kt_modal_add_transaction_scroll" data-kt-scroll-offset="300px">
                            @if ($isAdmin)
                                <!--begin::Branch Select for Admin-->
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Select Branch</label>
                                    <div class="input-group input-group-solid flex-nowrap">
                                        <span class="input-group-text">
                                            <i class="ki-outline ki-bank fs-3"></i>
                                        </span>
                                        <div class="overflow-hidden flex-grow-1">
                                            <select name="transaction_branch"
                                                class="form-select form-select-solid rounded-start-0 border-start"
                                                data-control="select2"
                                                data-dropdown-parent="#kt_modal_add_transaction"
                                                data-placeholder="Select a branch" id="transaction_branch_select">
                                                <option></option>
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}">
                                                        {{ ucfirst($branch->branch_name) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Branch Select for Admin-->
                            @endif
                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Select Student</label>
                                <!--end::Label-->
                                <!--begin::Solid input group style-->
                                <div class="input-group input-group-solid flex-nowrap">
                                    <span class="input-group-text">
                                        <i class="ki-outline ki-faceid fs-3"></i>
                                    </span>
                                    <div class="overflow-hidden flex-grow-1">
                                        <select name="transaction_student"
                                            class="form-select form-select-solid rounded-start-0 border-start"
                                            data-control="select2" data-dropdown-parent="#kt_modal_add_transaction"
                                            data-placeholder="Select a student" id="transaction_student_select">
                                            <option></option>
                                            @if (!$isAdmin)
                                                @foreach ($students as $student)
                                                    <option value="{{ $student->id }}">{{ $student->name }}
                                                        ({{ $student->student_unique_id }})
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                <!--end::Solid input group style-->
                            </div>
                            <!--end::Name Input group-->
                            <!--begin::Phone Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Invoice Number</label>
                                <!--end::Label-->
                                <!--begin::Solid input group style-->
                                <div class="input-group input-group-solid flex-nowrap">
                                    <span class="input-group-text">
                                        <i class="ki-outline ki-save-2 fs-3"></i>
                                    </span>
                                    <div class="overflow-hidden flex-grow-1">
                                        <select name="transaction_invoice"
                                            class="form-select form-select-solid rounded-start-0 border-start"
                                            data-control="select2" data-dropdown-parent="#kt_modal_add_transaction"
                                            data-placeholder="Select a due invoice" id="student_due_invoice_select">
                                            <!-- show the due invoice of selected student -->
                                        </select>
                                    </div>
                                </div>
                                <!--end::Solid input group style-->
                            </div>
                            <!--end::Phone Input group-->
                            <!--begin::Type Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="d-flex align-items-center form-label mb-3 required">Payment Type</label>
                                <!--end::Label-->
                                <!--begin::Row-->
                                <div class="row">
                                    <!--begin::Col-->
                                    <div class="col-lg-4">
                                        <!--begin::Option-->
                                        <input type="radio" class="btn-check" name="transaction_type" value="full"
                                            checked="checked" id="full_payment_type_input" />
                                        <label
                                            class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                            for="full_payment_type_input">
                                            <i class="ki-outline ki-dollar fs-2x me-5"></i>
                                            <!--begin::Info-->
                                            <span class="d-block fw-semibold text-start">
                                                <span class="text-gray-900 fw-bold d-block fs-6">Full Payment</span>
                                            </span>
                                            <!--end::Info-->
                                        </label>
                                        <!--end::Option-->
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="col-lg-4">
                                        <!--begin::Option-->
                                        <input type="radio" class="btn-check" name="transaction_type" value="partial"
                                            id="partial_payment_type_input" />
                                        <label
                                            class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                            for="partial_payment_type_input">
                                            <i class="ki-outline ki-finance-calculator fs-2x me-5"></i>
                                            <!--begin::Info-->
                                            <span class="d-block fw-semibold text-start">
                                                <span class="text-gray -mt-2-900 fw-bold d-block fs-6">Partial
                                                    Payment</span>
                                            </span>
                                            <!--end::Info-->
                                        </label>
                                        <!--end::Option-->
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="col-lg-4">
                                        <!--begin::Option-->
                                        <input type="radio" class="btn-check" name="transaction_type"
                                            value="discounted" id="discounted_payment_type_input" />
                                        <label
                                            class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                            for="discounted_payment_type_input">
                                            <i class="ki-outline ki-discount fs-2x me-5"></i>
                                            <!--begin::Info-->
                                            <span class="d-block fw-semibold text-start">
                                                <span class="text-gray -mt-2-900 fw-bold d-block fs-6">Discounted</span>
                                            </span>
                                            <!--end::Info-->
                                        </label>
                                        <!--end::Option-->
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Row-->
                            </div>
                            <!--end::Type Input group-->
                            <div class="row">
                                <div class="col-lg-6">
                                    <!--begin::Name Input group-->
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="required fw-semibold fs-6 mb-2">Amount</label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="number" name="transaction_amount" min="0"
                                            id="transaction_amount_input"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="Enter the paid amount" required disabled />
                                        <!--end::Input-->
                                    </div>
                                    <!--end::Name Input group-->
                                </div>
                                <div class="col-lg-6">
                                    <!--begin::Name Input group-->
                                    <div class="fv-row">
                                        <!--begin::Label-->
                                        <label class="fw-semibold fs-6 mb-2">Remarks <span
                                                class="text-muted">(optional)</span></label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="text" name="transaction_remarks" min="0"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="Add some remarks" />
                                        <!--end::Input-->
                                    </div>
                                    <!--end::Name Input group-->
                                </div>
                            </div>
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-add-transaction-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary"
                                data-kt-add-transaction-modal-action="submit">
                                <span class="indicator-label">Submit</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                        <!--end::Actions-->
                    </form>
                    <!--end::Form-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - Add Transaction-->
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
        const routeAjaxData = "{{ route('transactions.ajax-data') }}";
        const routeExportData = "{{ route('transactions.export-data') }}";
        const routeDeleteTxn = "{{ route('transactions.destroy', ':id') }}";
        const routeApproveTxn = "{{ route('transactions.approve', ':id') }}";
        const routeDownloadStatement = "{{ route('student.statement.download') }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
        const isAdmin = @json($isAdmin);
        const branchIds = @json($branches->pluck('id')->toArray());
        const studentsByBranch = @json($isAdmin ? $studentsByBranch : []);
        const branchColors = @json($branchColors);
    </script>
    <script src="{{ asset('js/transactions/index.js') }}"></script>
    <script>
        document.getElementById("payments_menu").classList.add("here", "show");
        document.getElementById("transactions_link").classList.add("active");
    </script>
@endpush
