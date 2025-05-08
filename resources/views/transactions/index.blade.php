@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
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
                Transactions </li>
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
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text"
                        data-transaction-table-filter="search" class="form-control form-control-solid w-350px ps-12"
                        placeholder="Search in transactions">
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
                                <label class="form-label fs-6 fw-semibold">Due Date:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-transaction-table-filter="product" data-hide-search="true">
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
                                    data-transaction-table-filter="status" data-hide-search="true">
                                    <option></option>
                                    <option value="I_due">Due</option>
                                    <option value="I_overdue">Overdue</option>
                                    <option value="I_partial">Partial Paid</option>
                                </select>
                            </div>
                            <!--end::Input group-->

                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-kt-menu-dismiss="true" data-transaction-table-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                    data-transaction-table-filter="filter">Apply</button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Menu 1-->

                    <!--begin::Add subscription-->
                    <a href="{{ route('transactions.create') }}" class="btn btn-primary">
                        <i class="ki-outline ki-plus fs-2"></i>New Transaction</a>
                    <!--end::Add subscription-->

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
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table" id="kt_transactions_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">SL</th>
                        <th class="w-150px">Invoice No.</th>
                        <th>Voucher No.</th>
                        <th>Amount (৳)</th>
                        <th>Payment Type</th>
                        <th class="w-350px">Student</th>
                        <th>Payment Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @foreach ($transactions as $transaction)
                        <tr>
                            <td>{{ $loop->index + 1 }}</td>
                            <td>
                                <a href="{{ route('invoices.show', $transaction->paymentInvoice->id) }}">
                                    {{ $transaction->paymentInvoice->invoice_number }}
                                </a>
                            </td>

                            <td>{{ $transaction->voucher_no }}</td>
                            <td>{{ intval($transaction->amount_paid) }}</td>
                            <td>
                                @if ($transaction->payment_type === 'partial')
                                    <span class="badge badge-warning">Partial</span>
                                @elseif ($transaction->payment_type === 'full')
                                    <span class="badge badge-success">Full Paid</span>
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('students.show', $transaction->student->id) }}">
                                    {{ $transaction->student->name }},
                                    {{ $transaction->student->student_unique_id }}
                                </a>
                            </td>

                            <td>
                                {{ $transaction->created_at->format('d-M-Y') }}
                                <span class="ms-1" data-bs-toggle="tooltip"
                                    title="{{ $transaction->created_at->format('d-M-Y h:i:s A') }}">
                                    <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                </span>
                            </td>

                            <td>
                                <a href="{{ route('transactions.edit', $transaction->id) }}" title="Edit Transaction"
                                    data-bs-toggle="tooltip" title="Edit Invoice"
                                    class="btn btn-icon btn-active-light-warning w-30px h-30px me-3">
                                    <i class="bi bi-download fs-2"></i>
                                </a>
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
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script src="{{ asset('js/transactions/index.js') }}"></script>

    <script>
        document.getElementById("transactions_link").classList.add("active");
    </script>
@endpush
