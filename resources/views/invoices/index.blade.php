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
                Invoices </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    @if ($errors->any())
        <div
            class="alert alert-dismissible bg-light-danger border border-danger border-dashed d-flex flex-column flex-sm-row w-100 p-5 mb-10">
            <!--begin::Icon-->
            <i class="ki-duotone ki-message-text-2 fs-2hx text-danger me-4 mb-5 mb-sm-0">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
            <!--end::Icon-->

            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

            <!--begin::Content-->
            <div class="d-flex flex-column pe-0 pe-sm-10">
                <h5 class="mb-1 text-danger">The following errors have been found.</h5>
                @foreach ($errors->all() as $error)
                    <span class="text-danger">{{ $error }}</span>
                @endforeach
            </div>
            <!--end::Content-->

            <!--begin::Close-->
            <button type="button"
                class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto"
                data-bs-dismiss="alert">
                <i class="ki-outline ki-cross fs-1 text-danger"></i>
            </button>
            <!--end::Close-->
        </div>
    @endif

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
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text"
                                data-kt-due-invoice-table-filter="search"
                                class="form-control form-control-solid w-350px ps-12" placeholder="Search in due invoices">
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
                                            <option value="tuition_fee">Tuition Fee</option>
                                            <option value="model_test_fee">Model Test Fee</option>
                                            <option value="exam_fee">Exam Fee</option>
                                            <option value="others_fee">Others Fee</option>
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
                                <th class="w-150px">Invoice No.</th>
                                <th class="w-300px">Student</th>
                                <th class="d-none">Invoice Type (filter)</th>
                                <th>Invoice Type</th>
                                <th class="d-none">Billing Month (filter)</th>
                                <th>Billing Month</th>
                                <th>Toal Amount (৳)</th>
                                <th>Remaining (৳)</th>
                                <th class="d-none">Due Date (filter)</th>
                                <th>Due Date</th>
                                <th class="d-none">Status (filter)</th>
                                <th>Status</th>
                                <th class="w-100px">Actions</th>
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
                                    </td>

                                    <td>
                                        <a href="{{ route('students.show', $invoice->student->id) }}" target="_blank">
                                            {{ $invoice->student->name }},
                                            {{ $invoice->student->student_unique_id }}
                                        </a>
                                    </td>

                                    <td class="d-none">{{ $invoice->invoice_type }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $invoice->invoice_type)) }}</td>

                                    <td class="d-none">D_{{ $invoice->month_year }}</td>

                                    <td>
                                        @if (preg_match('/^(\d{2})_(\d{4})$/', $invoice->month_year, $matches))
                                            {{ \Carbon\Carbon::create($matches[2], $matches[1], 1)->format('F Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>


                                    <td>{{ $invoice->total_amount }}</td>

                                    <td>{{ $invoice->amount_due }}</td>

                                    <td class="d-none">
                                        @if ($invoice->invoice_type == 'tuition_fee')
                                            1/{{ $invoice->student->payments->due_date }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if ($invoice->invoice_type == 'tuition_fee')
                                            {{ ucfirst($invoice->student->payments->payment_style) }}-1/{{ $invoice->student->payments->due_date }}
                                        @else
                                            N/A
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
                                                    $dueDate = $monthYear->copy()->day((int) $payment->due_date); // 👈 Cast to int

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


                                    <td class="d-none">
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
                                        @if ($invoice->invoice_type == 'tuition_fee')
                                            @if ($status === 'due')
                                                @if ($isOverdue)
                                                    <span class="badge badge-danger">Overdue</span>
                                                @else
                                                    <span class="badge badge-warning">Due</span>
                                                @endif
                                            @elseif ($status === 'partially_paid')
                                                <span class="badge badge-info">Partial</span>
                                                @if ($isOverdue)
                                                    <span class="badge badge-danger ms-1">Overdue</span>
                                                @endif
                                            @endif
                                        @else
                                            <span class="badge badge-warning">Due</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if (optional($invoice->student->studentActivation)->active_status == 'active' && $invoice->status == 'due')
                                            @can('invoices.edit')
                                                <a href="#" title="Edit invoice" data-invoice-id="{{ $invoice->id }}"
                                                    data-bs-toggle="modal" data-bs-target="#kt_modal_edit_invoice"
                                                    title="Edit Invoice"
                                                    class="btn btn-icon text-hover-primary w-30px h-30px">
                                                    <i class="ki-outline ki-pencil fs-2"></i>
                                                </a>
                                            @endcan
                                            @can('invoices.delete')
                                                <a href="#" title="Delete invoice" data-bs-toggle="tooltip"
                                                    class="btn btn-icon text-hover-danger w-30px h-30px delete-invoice"
                                                    data-invoice-id="{{ $invoice->id }}">
                                                    <i class="ki-outline ki-trash fs-2"></i>
                                                </a>
                                            @endcan
                                        @endif
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
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text"
                                data-kt-paid-invoice-table-filter="search"
                                class="form-control form-control-solid w-350px ps-12"
                                placeholder="Search in paid invoices">
                        </div>
                        <!--end::Search-->
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
                                            <option value="tuition_fee">Tuition Fee</option>
                                            <option value="model_test_fee">Model Test Fee</option>
                                            <option value="exam_fee">Exam Fee</option>
                                            <option value="others_fee">Others Fee</option>
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
                                <th>Amount (৳)</th>
                                <th class="d-none">Billing Month (filter)</th>
                                <th>Billing Month</th>
                                <th class="d-none">Due Date (filter)</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Payment Date</th>
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
                                            {{ $invoice->student->name }},
                                            {{ $invoice->student->student_unique_id }}
                                        </a>
                                    </td>

                                    <td class="d-none">{{ $invoice->invoice_type }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $invoice->invoice_type)) }}</td>

                                    <td>{{ $invoice->total_amount }}</td>

                                    <td class="d-none">P_{{ $invoice->month_year }}</td>

                                    <td>
                                        @if (preg_match('/^(\d{2})_(\d{4})$/', $invoice->month_year, $matches))
                                            {{ \Carbon\Carbon::create($matches[2], $matches[1], 1)->format('F Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>

                                    <td class="d-none">
                                        @if ($invoice->invoice_type == 'tuition_fee')
                                            1/{{ $invoice->student->payments->due_date }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if ($invoice->invoice_type == 'tuition_fee')
                                            {{ ucfirst($invoice->student->payments->payment_style) }}-1/{{ $invoice->student->payments->due_date }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-success">{{ ucfirst($invoice->status) }}</span>
                                    </td>
                                    <td>
                                        {{ $invoice->updated_at->format('d-M-Y') }}
                                        <span class="ms-1" data-bs-toggle="tooltip"
                                            title="{{ $invoice->updated_at->format('d-M-Y h:i:s A') }}">
                                            <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                        </span>
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
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_add_invoice_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Create Invoice</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-add-invoice-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_add_invoice_form" class="form" action="{{ route('invoices.store') }}"
                        method="POST">
                        @csrf
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_invoice_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_transaction_header"
                            data-kt-scroll-wrappers="#kt_modal_add_invoice_scroll" data-kt-scroll-offset="300px">

                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Select Student</label>
                                <!--end::Label-->

                                <!--begin::Solid input group style-->
                                <div class="input-group input-group-solid flex-nowrap">
                                    <span class="input-group-text">
                                        <i class="las la-graduation-cap fs-3"></i>
                                    </span>
                                    <div class="overflow-hidden flex-grow-1">
                                        <select name="invoice_student"
                                            class="form-select form-select-solid rounded-start-0 border-start"
                                            data-control="select2" data-dropdown-parent="#kt_modal_create_invoice"
                                            data-placeholder="Select a student at first" required>
                                            <option></option>
                                            @foreach ($students as $student)
                                                <option value="{{ $student->id }}">{{ $student->name }}
                                                    ({{ $student->student_unique_id }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <!--end::Solid input group style-->
                            </div>
                            <!--end::Name Input group-->

                            <!--begin::Invoice Type Input group-->
                            <div class="fv-row mb-7">
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
                                            data-placeholder="Select a invoice type" data-hide-search="true" required
                                            disabled>
                                            <option></option>
                                            <option value="tuition_fee" selected>Tuition Fee</option>
                                            <option value="exam_fee">Exam Fee</option>
                                            <option value="model_test_fee">Model Test Fee</option>
                                            <option value="others_fee">Others</option>
                                        </select>
                                    </div>
                                </div>
                                <!--end::Solid input group style-->
                            </div>
                            <!--end::Invoice Type Input group-->

                            <!--begin::Month_Year Input group-->
                            <div class="fv-row mb-7" id="month_year_id">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Month Year</label>
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
                                        <input type="number" name="invoice_amount" min="500"
                                            class="form-control form-control-solid mb-3 mb-lg-0 rounded-start-0 border-start"
                                            placeholder="Enter the amount" disabled required />
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
                                data-kt-add-invoice-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-add-invoice-modal-action="submit">
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
                        <i class="ki-outline ki-cross fs-1">
                        </i>
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

                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="fw-semibold fs-6 mb-2">Corrosponding Student</label>
                                <!--end::Label-->

                                <!--begin::Solid input group style-->
                                <div class="input-group input-group-solid flex-nowrap">
                                    <span class="input-group-text">
                                        <i class="las la-graduation-cap fs-3"></i>
                                    </span>
                                    <div class="overflow-hidden flex-grow-1">
                                        <select name="invoice_student_edit"
                                            class="form-select form-select-solid rounded-start-0 border-start"
                                            data-control="select2" data-dropdown-parent="#kt_modal_edit_invoice"
                                            data-placeholder="Select a student" disabled>
                                            <option></option>
                                            @foreach ($students as $student)
                                                <option value="{{ $student->id }}">{{ $student->name }}
                                                    ({{ $student->student_unique_id }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <!--end::Solid input group style-->
                            </div>
                            <!--end::Name Input group-->

                            <!--begin::Invoice Type Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="fw-semibold fs-6 mb-2">Invoice Type</label>
                                <!--end::Label-->

                                <!--begin::Solid input group style-->
                                <div class="input-group input-group-solid flex-nowrap">
                                    <span class="input-group-text">
                                        <i class="ki-outline ki-save-2 fs-3"></i>
                                    </span>
                                    <div class="overflow-hidden flex-grow-1">
                                        <select name="invoice_type_edit"
                                            class="form-select form-select-solid rounded-start-0 border-start"
                                            data-control="select2" data-dropdown-parent="#kt_modal_edit_invoice"
                                            data-placeholder="Select a invoice type" disabled>
                                            <option></option>
                                            <option value="tuition_fee">Tuition Fee</option>
                                            <option value="exam_fee">Exam Fee</option>
                                            <option value="model_test_fee">Model Test Fee</option>
                                            <option value="others_fee">Others</option>
                                        </select>
                                    </div>
                                </div>
                                <!--end::Solid input group style-->
                            </div>
                            <!--end::Invoice Type Input group-->

                            <!--begin::Month_Year Input group-->
                            <div class="fv-row mb-7" id="month_year_id_edit">
                                <!--begin::Label-->
                                <label class="fw-semibold fs-6 mb-2">Month Year</label>
                                <!--end::Label-->

                                <!--begin::Solid input group style-->
                                <div class="input-group input-group-solid flex-nowrap">
                                    <span class="input-group-text">
                                        <i class="ki-outline ki-calendar fs-3"></i>
                                    </span>
                                    <div class="overflow-hidden flex-grow-1">
                                        <select name="invoice_month_year_edit"
                                            class="form-select form-select-solid rounded-start-0 border-start"
                                            data-control="select2" data-dropdown-parent="#kt_modal_edit_invoice"
                                            data-placeholder="Select billing month" disabled>
                                            <option></option>
                                        </select>
                                    </div>
                                </div>
                                <!--end::Solid input group style-->
                            </div>
                            <!--end::Month_Year Input group-->

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
                                        <input type="number" name="invoice_amount_edit" min="500"
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
                                Update
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
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        const routeDeleteInvoice = "{{ route('invoices.destroy', ':id') }}";
    </script>

    <script src="{{ asset('js/invoices/index.js') }}"></script>
    <script src="{{ asset('js/invoices/index-ajax.js') }}"></script>

    <script>
        document.getElementById("invoices_link").classList.add("active");
    </script>
@endpush
