@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush
@extends('layouts.app')
@section('title', 'All Invoices')
@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Invoices
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
                Invoices
            </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection
@section('content')
    @php
        // Preloading permissions checking
        $canEditInvoice = auth()->user()->can('invoices.edit');
        $canDeleteInvoice = auth()->user()->can('invoices.delete');
    @endphp
    <!--begin:::Tabs-->
    <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
        <!--begin:::Tab item-->
        <li class="nav-item">
            <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_due_invoices_tab"><i
                    class="ki-outline ki-home fs-3 me-2"></i>Due Invoices
            </a>
        </li>
        <!--end:::Tab item-->
        <!--begin:::Tab item-->
        <li class="nav-item">
            <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_paid_invoices_tab"><i
                    class="ki-outline ki-book-open fs-3 me-2"></i>Fully Paid Invoices
            </a>
        </li>
        <!--end:::Tab item-->
        @can('invoices.create')
            <!--begin:::Tab item-->
            <li class="nav-item ms-auto">
                <!--begin::Action menu-->
                <a href="#" class="btn btn-primary ps-7" data-bs-toggle="modal"
                    data-bs-target="#kt_modal_create_invoice"><i class="ki-outline ki-plus fs-2 me-0"></i> Create Invoice</a>
                <!--end::Menu-->
            </li>
            <!--end:::Tab item-->
        @endcan
    </ul>
    <!--end:::Tabs-->
    <!--begin:::Tab content-->
    <div class="tab-content" id="myTabContent">
        <!--begin:::Tab pane-->
        <div class="tab-pane fade show active" id="kt_due_invoices_tab" role="tabpanel">
            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" data-kt-due-invoice-table-filter="search"
                                class="form-control form-control-solid w-md-350px ps-12"
                                placeholder="Search in due invoices">
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
                        <div class="d-flex justify-content-end" data-kt-subscription-table-toolbar="base">
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
                                <div class="px-7 py-5" data-kt-due-invoice-table-filter="form">
                                    <!--begin::Input group-->
                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">Invoice Type:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true"
                                            data-kt-subscription-table-filter="product" data-hide-search="true">
                                            <option></option>
                                            @foreach ($invoice_types as $type)
                                                <option value="ucms_{{ $type->type_name }}">{{ $type->type_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Input group-->
                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">Due Date:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true"
                                            data-kt-subscription-table-filter="product" data-hide-search="true">
                                            <option></option>
                                            <option value="1/7">1-7</option>
                                            <option value="1/10">1-10</option>
                                            <option value="1/15">1-15</option>
                                            <option value="1/30">1-30</option>
                                        </select>
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Input group-->
                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">Invoice Status:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true"
                                            data-kt-subscription-table-filter="status" data-hide-search="true">
                                            <option></option>
                                            <option value="I_due">Due</option>
                                            <option value="I_overdue">Overdue</option>
                                            <option value="I_partial">Partial Paid</option>
                                        </select>
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Input group-->
                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">Billing Month:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true"
                                            data-kt-subscription-table-filter="status">
                                            <option></option>
                                            @foreach ($dueMonths as $dueMonth)
                                                @php
                                                    $parts = explode('_', $dueMonth);
                                                    $date = new DateTime();
                                                    $date->setDate($parts[1], $parts[0], 1);
                                                @endphp
                                                <option value="D_{{ $dueMonth }}">
                                                    {{ $date->format('F Y') }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Actions-->
                                    <div class="d-flex justify-content-end">
                                        <button type="reset"
                                            class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                            data-kt-menu-dismiss="true"
                                            data-kt-due-invoice-table-filter="reset">Reset</button>
                                        <button type="submit" class="btn btn-primary fw-semibold px-6"
                                            data-kt-menu-dismiss="true"
                                            data-kt-due-invoice-table-filter="filter">Apply</button>
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
                    <!--begin::Table-->
                    <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                        id="kt_due_invoices_table">
                        <thead>
                            <tr class="fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-25px">SL</th>
                                <th class="w-100px">Invoice No.</th>
                                <th class="w-300px">Student</th>
                                <th>Mobile</th>
                                <th class="filter-only">Invoice Type (filter)</th>
                                <th>Invoice Type</th>
                                <th class="filter-only">Billing Month (filter)</th>
                                <th>Billing Month</th>
                                <th>Total Amount (Tk)</th>
                                <th>Remaining (Tk)</th>
                                <th class="filter-only">Due Date (filter)</th>
                                <th>Due Date</th>
                                <th class="filter-only">Status (filter)</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th class="w-150px not-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            @foreach ($unpaid_invoices as $invoice)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('invoices.show', $invoice->id) }}">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                        @if ($invoice->comments_count > 0)
                                            <span class="badge badge-circle badge-sm badge-primary ms-1">{{ $invoice->comments_count }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('students.show', $invoice->student->id) }}" target="_blank">
                                            {{ $invoice->student->name }}, {{ $invoice->student->student_unique_id }}
                                        </a>
                                    </td>
                                    <td>
                                        {{ $invoice->student->mobileNumbers->where('number_type', 'home')->pluck('mobile_number')->implode('<br>') }}
                                    </td>
                                    <td class="filter-only">ucms_{{ $invoice->invoiceType?->type_name }}</td>
                                    <td>{{ $invoice->invoiceType?->type_name }}</td>
                                    <td class="filter-only">D_{{ $invoice->month_year }}</td>
                                    <td>
                                        @if (!empty($invoice->month_year) && preg_match('/^(\d{2})_(\d{4})$/', $invoice->month_year, $matches))
                                            {{ \Carbon\Carbon::create($matches[2], $matches[1], 1)->format('F Y') }}
                                        @elseif (empty($invoice->month_year) && $invoice->invoiceType?->type_name == 'Special Class Fee')
                                            <span class="badge badge-primary rounded-pill">One Time</span></span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $invoice->total_amount }}</td>
                                    <td>{{ $invoice->amount_due }}</td>
                                    <td class="filter-only">
                                        @if ($invoice->invoiceType?->type_name == 'Tuition Fee')
                                            1/{{ $invoice->student->payments->due_date }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($invoice->invoiceType?->type_name == 'Tuition Fee')
                                            {{ ucfirst($invoice->student->payments->payment_style) }}-1/{{ $invoice->student->payments->due_date }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    @php
                                        $status = $invoice->status;
                                        $payment = optional($invoice->student)->payments;
                                        $dueDate = null;
                                        $isOverdue = false;

                                        if ($payment && $payment->due_date && $invoice->month_year) {
                                            try {
                                                $monthYearRaw = trim($invoice->month_year);
                                                if (preg_match('/^\d{2}_\d{4}$/', $monthYearRaw)) {
                                                    $monthYear = \Carbon\Carbon::createFromFormat('m_Y', $monthYearRaw);
                                                    $dueDate = $monthYear->copy()->day((int) $payment->due_date);

                                                    if (
                                                        in_array($status, ['due', 'partially_paid']) &&
                                                        now()->toDateString() > $dueDate->toDateString()
                                                    ) {
                                                        $isOverdue = true;
                                                    }
                                                }
                                            } catch (\Exception $e) {
                                                // Silently ignore
                                            }
                                        }
                                    @endphp
                                    <td class="filter-only">
                                        @if ($status === 'due')
                                            I_due
                                        @elseif ($status === 'partially_paid')
                                            I_partial
                                        @endif
                                        @if ($isOverdue)
                                            I_overdue
                                        @endif
                                    </td>
                                    <td>
                                        @if ($status === 'due')
                                            @if ($isOverdue)
                                                <span class="badge badge-danger rounded-pill">Overdue</span>
                                            @else
                                                <span class="badge badge-warning rounded-pill">Due</span>
                                            @endif
                                        @elseif ($status === 'partially_paid')
                                            <span class="badge badge-info rounded-pill">Partial</span>
                                            @if ($isOverdue)
                                                <span class="badge badge-danger rounded-pill ms-1">Overdue</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        {{ $invoice->created_at->format('d-m-Y') }}<br>
                                        <small class="text-muted">
                                            {{ $invoice->created_at->format('h:i:s A') }}
                                        </small>
                                    </td>
                                    <td>
                                        <a href="#" class="btn btn-light btn-active-light-primary btn-sm"
                                            data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                            <i class="ki-outline ki-down fs-5 m-0"></i></a>
                                        <!--begin::Menu-->
                                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-175px py-4"
                                            data-kt-menu="true">
                                            @if ($invoice->status === 'due')
                                                @if ($canEditInvoice)
                                                    <div class="menu-item px-3">
                                                        <a href="#" data-invoice-id="{{ $invoice->id }}"
                                                            data-bs-toggle="modal" data-bs-target="#kt_modal_edit_invoice"
                                                            class="menu-link text-hover-primary px-3"><i
                                                                class="ki-outline ki-pencil fs-3 me-2"></i> Edit
                                                            Invoice</a>
                                                    </div>
                                                    <div class="menu-item px-3">
                                                        <a href="#" data-invoice-id="{{ $invoice->id }}"
                                                            data-invoice-number="{{ $invoice->invoice_number }}"
                                                            data-bs-toggle="modal" data-bs-target="#kt_modal_add_comment"
                                                            class="menu-link text-hover-primary px-3 add-comment-btn"><i
                                                                class="ki-outline ki-messages fs-3 me-2"></i> Add Comment
                                                        </a>
                                                    </div>
                                                @endif
                                                @if ($canDeleteInvoice)
                                                    <div class="menu-item px-3">
                                                        <a href="#" data-invoice-id="{{ $invoice->id }}"
                                                            class="menu-link text-hover-danger px-3 delete-invoice"><i
                                                                class="ki-outline ki-trash fs-3 me-2"></i> Delete
                                                            Invoice</a>
                                                    </div>
                                                @endif
                                            @elseif ($invoice->status === 'partially_paid')
                                                @if ($canEditInvoice)
                                                    <div class="menu-item px-3">
                                                        <a href="#" data-invoice-id="{{ $invoice->id }}"
                                                            data-invoice-number="{{ $invoice->invoice_number }}"
                                                            data-bs-toggle="modal" data-bs-target="#kt_modal_add_comment"
                                                            class="menu-link text-hover-primary px-3 add-comment-btn"><i
                                                                class="ki-outline ki-messages fs-3 me-2"></i> Add Comment
                                                        </a>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                        <!--end::Menu-->
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end:::Tab pane-->
        <!--begin:::Tab pane-->
        <div class="tab-pane fade" id="kt_paid_invoices_tab" role="tabpanel">
            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" data-kt-paid-invoice-table-filter="search"
                                class="form-control form-control-solid w-md-350px ps-12"
                                placeholder="Search in paid invoices">
                        </div>
                        <!--end::Search-->
                        <!--begin::Export hidden buttons-->
                        <div id="kt_hidden_export_buttons_2" class="d-none"></div>
                        <!--end::Export buttons-->
                    </div>
                    <!--begin::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Toolbar-->
                        <div class="d-flex justify-content-end" data-kt-paid-invoice-table-toolbar="base">
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
                                <div class="px-7 py-5" data-kt-paid-invoice-table-filter="form">
                                    <!--begin::Input group-->
                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">Invoice Type:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true"
                                            data-kt-subscription-table-filter="product" data-hide-search="true">
                                            <option></option>
                                            @foreach ($invoice_types as $type)
                                                <option value="ucms_{{ $type->type_name }}">{{ $type->type_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Input group-->
                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">Due Date:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true"
                                            data-kt-subscription-table-filter="product" data-hide-search="true">
                                            <option></option>
                                            <option value="1/7">1-7</option>
                                            <option value="1/10">1-10</option>
                                            <option value="1/15">1-15</option>
                                            <option value="1/30">1-30</option>
                                        </select>
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Input group-->
                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">Billing Month:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true"
                                            data-kt-subscription-table-filter="status">
                                            <option></option>
                                            @foreach ($paidMonths as $paidMonth)
                                                @php
                                                    $parts = explode('_', $paidMonth);
                                                    $date = new DateTime();
                                                    $date->setDate($parts[1], $parts[0], 1);
                                                @endphp
                                                <option value="P_{{ $paidMonth }}">
                                                    {{ $date->format('F Y') }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Actions-->
                                    <div class="d-flex justify-content-end">
                                        <button type="reset"
                                            class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                            data-kt-menu-dismiss="true"
                                            data-kt-paid-invoice-table-filter="reset">Reset</button>
                                        <button type="submit" class="btn btn-primary fw-semibold px-6"
                                            data-kt-menu-dismiss="true"
                                            data-kt-paid-invoice-table-filter="filter">Apply</button>
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
                                <div id="kt_table_report_dropdown_menu_2"
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
                    <!--begin::Table-->
                    <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                        id="kt_paid_invoices_table">
                        <thead>
                            <tr class="fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-25px">SL</th>
                                <th class="w-150px">Invoice No.</th>
                                <th class="w-350px">Student</th>
                                <th class="d-none">Invoice Type (filter)</th>
                                <th>Invoice Type</th>
                                <th>Amount (Tk)</th>
                                <th class="d-none">Billing Month (filter)</th>
                                <th>Billing Month</th>
                                <th class="d-none">Due Date (filter)</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            @foreach ($paid_invoices as $invoice)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('invoices.show', $invoice->id) }}">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('students.show', $invoice->student->id) }}">
                                            {{ $invoice->student->name }}, {{ $invoice->student->student_unique_id }}
                                        </a>
                                    </td>
                                    <td class="d-none">ucms_{{ $invoice->invoiceType?->type_name }}</td>
                                    <td>{{ $invoice->invoiceType?->type_name }}</td>
                                    <td>{{ $invoice->total_amount }}</td>
                                    <td class="d-none">P_{{ $invoice->month_year }}</td>
                                    <td>
                                        @if (!empty($invoice->month_year) && preg_match('/^(\d{2})_(\d{4})$/', $invoice->month_year, $matches))
                                            {{ \Carbon\Carbon::create($matches[2], $matches[1], 1)->format('F Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="d-none">
                                        @if ($invoice->invoiceType?->type_name == 'Tuition Fee')
                                            1/{{ $invoice->student->payments->due_date }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($invoice->invoiceType?->type_name == 'Tuition Fee')
                                            {{ ucfirst($invoice->student->payments->payment_style) }}-1/{{ $invoice->student->payments->due_date }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-success rounded-pill">{{ ucfirst($invoice->status) }}</span>
                                    </td>
                                    <td>
                                        {{ $invoice->created_at->format('d-m-Y') }}<br>
                                        <small class="text-muted">
                                            {{ $invoice->created_at->format('h:i:s A') }}
                                        </small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end:::Tab pane-->
    </div>
    <!--end:::Tab content-->

    <!--begin::Modal - Create Invoice-->
    <div class="modal fade" id="kt_modal_create_invoice" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-750px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_add_invoice_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Create Invoice</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-add-invoice-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"> </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_add_invoice_form" class="form" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_invoice_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_transaction_header"
                            data-kt-scroll-wrappers="#kt_modal_add_invoice_scroll" data-kt-scroll-offset="300px">
                            <div class="row">
                                <!--begin::Name Input group-->
                                <div class="fv-row mb-7 col-12">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Select Student</label>
                                    <!--end::Label-->
                                    <!--begin::Solid input group style-->
                                    <div class="input-group input-group-solid flex-nowrap">
                                        <span class="input-group-text">
                                            <i class="ki-outline ki-faceid fs-3"></i>
                                        </span>
                                        <div class="overflow-hidden flex-grow-1">
                                            <select name="invoice_student"
                                                class="form-select form-select-solid rounded-start-0 border-start"
                                                data-control="select2" data-dropdown-parent="#kt_modal_create_invoice"
                                                data-placeholder="Select a student at first" required>
                                                <option></option>
                                                @foreach ($students as $student)
                                                    <option value="{{ $student->id }}">{{ $student->name }}
                                                        ({{ $student->student_unique_id }}) -
                                                        {{ ucfirst($student->payments->payment_style) }} -
                                                        1/{{ $student->payments->due_date }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <!--end::Solid input group style-->
                                </div>
                                <!--end::Name Input group-->
                                <!--begin::Invoice Type Input group-->
                                <div class="fv-row mb-7 col-12">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Invoice Type</label>
                                    <!--end::Label-->
                                    <!--begin::Solid input group style-->
                                    <div class="input-group input-group-solid flex-nowrap">
                                        <span class="input-group-text">
                                            <i class="ki-outline ki-save-2 fs-3"></i>
                                        </span>
                                        <div class="overflow-hidden flex-grow-1">
                                            <select name="invoice_type"
                                                class="form-select form-select-solid rounded-start-0 border-start"
                                                data-control="select2" data-dropdown-parent="#kt_modal_create_invoice"
                                                data-placeholder="Select a invoice type" data-hide-search="false" required
                                                disabled>
                                                <option></option>
                                                @foreach ($invoice_types as $type)
                                                    <option value="{{ $type->id }}"
                                                        data-type-name="{{ $type->type_name }}">{{ $type->type_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <!--end::Solid input group style-->
                                </div>
                                <!--end::Invoice Type Input group-->
                                <!--begin::Month_Year Input group-->
                                <div class="fv-row mb-7 col-6" id="month_year_type_id">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Billing Type</label>
                                    <!--end::Label-->
                                    <!--begin::Solid input group style-->
                                    <div class="row">
                                        <!--begin::New Month Year-->
                                        <div class="col-lg-6">
                                            <!--begin::Option-->
                                            <input type="radio" class="btn-check" name="month_year_type" checked="checked"
                                                value="new_invoice" id="new_invoice_input" />
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                for="new_invoice_input">
                                                <i class="ki-outline ki-abstract fs-2x me-5"></i>
                                                <!--begin::Info-->
                                                <span class="d-block fw-semibold text-start">
                                                    <span class="text-gray-900 fw-bold d-block fs-6">New</span>
                                                </span>
                                                <!--end::Info-->
                                            </label>
                                            <!--end::Option-->
                                        </div>
                                        <!--end::New Month Year-->
                                        <!--begin::Old Month Year-->
                                        <div class="col-lg-6">
                                            <!--begin::Option-->
                                            <input type="radio" class="btn-check" name="month_year_type" value="old_invoice"
                                                id="old_invoice_input" />
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                for="old_invoice_input">
                                                <i class="ki-outline ki-abstract-20 fs-2x me-5"></i>
                                                <!--begin::Info-->
                                                <span class="d-block fw-semibold text-start">
                                                    <span class="text-gray-900 fw-bold d-block fs-6">Old</span>
                                                </span>
                                                <!--end::Info-->
                                            </label>
                                            <!--end::Option-->
                                        </div>
                                        <!--end::Old Month Year-->
                                    </div>
                                    <!--end::Solid input group style-->
                                </div>
                                <!--end::Month_Year Input group-->
                                <!--begin::Month_Year Input group-->
                                <div class="fv-row mb-7 col-6" id="month_year_id">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Billing Month</label>
                                    <!--end::Label-->
                                    <!--begin::Solid input group style-->
                                    <div class="input-group input-group-solid flex-nowrap">
                                        <span class="input-group-text">
                                            <i class="ki-outline ki-calendar fs-3"></i>
                                        </span>
                                        <div class="overflow-hidden flex-grow-1">
                                            <select name="invoice_month_year"
                                                class="form-select form-select-solid rounded-start-0 border-start"
                                                data-control="select2" data-dropdown-parent="#kt_modal_create_invoice"
                                                data-placeholder="Select billing month" data-hide-search="true" disabled
                                                required>
                                                <option></option>
                                            </select>
                                        </div>
                                    </div>
                                    <!--end::Solid input group style-->
                                </div>
                                <!--end::Month_Year Input group-->
                                <!--begin::Amount Input group-->
                                <div class="fv-row mb-7 col-12">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Amount</label>
                                    <!--end::Label-->
                                    <div class="input-group input-group-solid flex-nowrap">
                                        <span class="input-group-text">
                                            <i class="ki-outline ki-dollar fs-3"></i>
                                        </span>
                                        <div class="overflow-hidden flex-grow-1">
                                            <!--begin::Input-->
                                            <input type="number" name="invoice_amount" min="50"
                                                class="form-control form-control-solid mb-3 mb-lg-0 rounded-start-0 border-start"
                                                placeholder="Enter the amount" disabled required />
                                            <!--end::Input-->
                                        </div>
                                    </div>
                                    <!--end::Input-->
                                </div>
                                <!--end::Amount Input group-->
                            </div>
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-add-invoice-modal-action="cancel">Discard</button>
                            <button type="button" class="btn btn-primary" data-kt-add-invoice-modal-action="submit">
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
    <!--end::Modal - Create Invoice-->

    <!--begin::Modal - Edit Invoice-->
    <div class="modal fade" id="kt_modal_edit_invoice" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold" id="kt_modal_edit_invoice_title">Update Invoice</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-edit-invoice-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"> </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_invoice_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_invoice_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_transaction_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_invoice_scroll" data-kt-scroll-offset="300px">
                            <!--begin::Student Display-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="fw-semibold fs-6 mb-2">Corresponding Student</label>
                                <!--end::Label-->
                                <!--begin::Display field-->
                                <div class="form-control form-control-solid bg-light-secondary" id="edit_student_display">
                                    <span class="text-muted">Loading...</span>
                                </div>
                                <!--end::Display field-->
                            </div>
                            <!--end::Student Display-->
                            <!--begin::Invoice Type Display-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="fw-semibold fs-6 mb-2">Invoice Type</label>
                                <!--end::Label-->
                                <!--begin::Display field-->
                                <div class="form-control form-control-solid bg-light-secondary" id="edit_invoice_type_display">
                                    <span class="text-muted">Loading...</span>
                                </div>
                                <!--end::Display field-->
                            </div>
                            <!--end::Invoice Type Display-->
                            <!--begin::Month_Year Display-->
                            <div class="fv-row mb-7" id="month_year_id_edit">
                                <!--begin::Label-->
                                <label class="fw-semibold fs-6 mb-2">Billing Month</label>
                                <!--end::Label-->
                                <!--begin::Display field-->
                                <div class="form-control form-control-solid bg-light-secondary" id="edit_month_year_display">
                                    <span class="text-muted">Loading...</span>
                                </div>
                                <!--end::Display field-->
                            </div>
                            <!--end::Month_Year Display-->
                            <!--begin::Amount Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Amount</label>
                                <!--end::Label-->
                                <div class="input-group input-group-solid flex-nowrap">
                                    <span class="input-group-text">
                                        <i class="ki-outline ki-dollar fs-3"></i>
                                    </span>
                                    <div class="overflow-hidden flex-grow-1">
                                        <!--begin::Input-->
                                        <input type="number" name="invoice_amount_edit" min="50"
                                            class="form-control form-control-solid mb-3 mb-lg-0 rounded-start-0 border-start"
                                            placeholder="Enter the amount" required />
                                        <!--end::Input-->
                                    </div>
                                </div>
                                <!--end::Input-->
                            </div>
                            <!--end::Amount Input group-->
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-edit-invoice-modal-action="cancel">Discard</button>
                            <button type="button" class="btn btn-primary" data-kt-edit-invoice-modal-action="submit">
                                <span class="indicator-label">Update</span>
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
    <!--end::Modal - Edit Invoice-->

    <!--begin::Modal - Add Comment-->
    <div class="modal fade" id="kt_modal_add_comment" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold" id="kt_modal_add_comment_title">Add Comment</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-add-comment-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_add_comment_form" class="form" novalidate="novalidate">
                        <!--begin::Hidden invoice ID-->
                        <input type="hidden" name="payment_invoice_id" id="comment_invoice_id" value="" />
                        <!--end::Hidden invoice ID-->
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_comment_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_add_comment_header"
                            data-kt-scroll-wrappers="#kt_modal_add_comment_scroll" data-kt-scroll-offset="300px">
                            
                            <!--begin::Previous Comments Section-->
                            <div class="mb-7" id="previous_comments_section">
                                <label class="fw-semibold fs-6 mb-3">Previous Comments</label>
                                <div id="previous_comments_container" class="border rounded p-4 bg-light-secondary" style="max-height: 200px; overflow-y: auto;">
                                    <div class="text-center text-muted py-3" id="comments_loading">
                                        <span class="spinner-border spinner-border-sm me-2"></span>Loading comments...
                                    </div>
                                    <div id="comments_list"></div>
                                    <div class="text-center text-muted py-3 d-none" id="no_comments">
                                        <i class="ki-outline ki-message-notif fs-2x text-muted mb-2"></i>
                                        <p class="mb-0">No comments yet</p>
                                    </div>
                                </div>
                            </div>
                            <!--end::Previous Comments Section-->

                            <!--begin::Comment Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">New Comment</label>
                                <!--end::Label-->
                                <!--begin::Textarea-->
                                <textarea name="comment" class="form-control form-control-solid" rows="4"
                                    placeholder="Enter your comment here..." required></textarea>
                                <!--end::Textarea-->
                                <div class="form-text text-muted">Minimum 3 characters, maximum 1000 characters.</div>
                            </div>
                            <!--end::Comment Input group-->
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-add-comment-modal-action="cancel">Discard</button>
                            <button type="button" class="btn btn-primary" data-kt-add-comment-modal-action="submit">
                                <span class="indicator-label">Add Comment</span>
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
    <!--end::Modal - Add Comment-->
@endsection
@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush
@push('page-js')
    <script>
        const routeDeleteInvoice = "{{ route('invoices.destroy', ':id') }}";
        const routeStoreInvoice = "{{ route('invoices.store') }}";
        const routeStoreComment = "{{ route('invoice.comments.store') }}";
        const routeGetComments = "{{ route('invoice.comments.index', ':id') }}";
    </script>
    <script src="{{ asset('js/invoices/index.js') }}"></script>
    <script>
        document.getElementById("payments_menu").classList.add("here", "show");
        document.getElementById("invoices_link").classList.add("active");
    </script>
@endpush