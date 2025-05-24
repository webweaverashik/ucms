@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'View Invoice')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            View Invoice - {{ $invoice->invoice_number }}
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
    <!--begin::Navbar-->
    <div
        class="card mb-6 mb-xl-9 @if (optional($invoice->student->studentActivation)->active_status === 'inactive') border border-dashed border-danger @elseif ($invoice->student->studentActivation == null) border border-dashed border-info @endif">
        <div class="card-body pt-9 pb-0">
            <!--begin::Details-->
            <div class="row">
                <div class="col-md-4">
                    <!--begin::Details-->
                    <div class="d-flex flex-wrap flex-sm-nowrap mb-6">
                        <!--begin::Image-->
                        <div class="d-flex flex-center flex-shrink-0 bg-light rounded-circle w-125px h-125px me-7 mb-4">
                            <img class="w-100 p-3"
                                src="{{ $invoice->student->photo_url ? asset($invoice->student->photo_url) : asset('assets/img/dummy.png') }}"
                                alt="{{ $invoice->student->name }}" />
                        </div>

                        <!--begin::Details-->
                        <div class="d-flex flex-column">
                            <!--begin::Farm Name-->
                            <div class="d-flex align-items-center mb-1">
                                <span class="text-gray-800 fs-2 fw-bold me-3">{{ $invoice->student->name }},
                                    {{ $invoice->student->student_unique_id }}</span>
                            </div>
                            <!--end::Farm Name-->

                            <div class="d-flex flex-wrap fw-semibold mb-2 fs-5 text-gray-500">
                                Class: &nbsp;<span class="text-gray-800">{{ $invoice->student->class->name }}</span>
                            </div>

                            <div class="d-flex flex-wrap fw-semibold mb-2 fs-5 text-gray-500">
                                Shift: &nbsp;<span class="text-gray-800">{{ $invoice->student->shift->name }}</span>
                            </div>

                            <div class="d-flex flex-wrap fw-semibold mb-2 fs-5 text-gray-500">
                                Tuition Fee: &nbsp;<span
                                    class="text-gray-800">{{ $invoice->student->payments->tuition_fee }} ৳</span>
                            </div>
                        </div>
                    </div>
                    <!--end::Details-->
                </div>

                <div class="col-md-1"></div>

                <div class="col-md-2">
                    <!--begin::Title-->
                    <h4 class="mb-4">Invoice Info</h4>
                    <!--end::Title-->
                    <!--begin::Details-->
                    <table class="table fs-6 fw-semibold gs-0 gy-1 gx-0">
                        <!--begin::Row-->
                        <tr class="">
                            <td class="text-gray-500">Invoice No.:</td>
                            <td class="text-gray-800">
                                {{ $invoice->invoice_number }}
                            </td>
                        </tr>
                        <!--end::Row-->

                        <!--begin::Row-->
                        <tr class="">
                            <td class="text-gray-500">Billing Month:</td>
                            <td class="text-gray-800">
                                {{ \Carbon\Carbon::createFromFormat('m_Y', $invoice->month_year)->format('F Y') }}
                            </td>
                        </tr>
                        <!--end::Row-->

                        <!--begin::Row-->
                        <tr class="">
                            <td class="text-gray-500">Total Payable:</td>
                            <td class="text-gray-800">
                                {{ $invoice->total_amount }} ৳
                            </td>
                        </tr>
                        <!--end::Row-->

                        <!--begin::Row-->
                        <tr class="">
                            <td class="text-gray-500">Remaining Amount:</td>
                            <td
                                class="@if ($invoice->amount_due > 0) text-danger animation-blink @else text-gray-800 @endif">
                                {{ $invoice->amount_due }} ৳
                            </td>
                        </tr>
                        <!--end::Row-->
                    </table>
                    <!--end::Details-->
                </div>

                <div class="col-md-1"></div>

                <div class="col-md-2">
                    <!--begin::Details-->
                    <table class="table fs-6 fw-semibold gs-0 gy-1 gx-0 mt-10">
                        <!--begin::Row-->
                        <tr class="">
                            <td class="text-gray-500">Due Date:</td>
                            <td class="text-gray-800">
                                1-{{ $invoice->student->payments->due_date }}
                            </td>
                        </tr>
                        <!--end::Row-->



                        {{-- @php
                            $status = $invoice->status;
                            $payment = optional($invoice->student)->payments;
                            $dueDate = null;
                            $isOverdue = false;

                            if ($payment && $payment->due_date && $invoice->month_year) {
                                try {
                                    $monthYear = \Carbon\Carbon::createFromFormat('m_Y', $invoice->month_year);
                                    $dueDate = $monthYear->copy()->day($payment->due_date);

                                    if (
                                        in_array($status, ['due', 'partially_paid']) &&
                                        now()->toDateString() > $dueDate->toDateString()
                                    ) {
                                        $isOverdue = true;
                                    }
                                } catch (\Exception $e) {
                                    // Silently ignore parse errors
                                }
                            }
                        @endphp --}}

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




                        <!--begin::Row-->
                        <tr class="">
                            <td class="text-gray-500">Status:</td>

                            <td>
                                @if ($status === 'due')
                                    @if ($isOverdue)
                                        <span class="badge badge-danger ms-1">Overdue</span>
                                    @else
                                        <span class="badge badge-warning">Due</span>
                                    @endif
                                @elseif ($status === 'partially_paid')
                                    <span class="badge badge-info">Partial</span>
                                    @if ($isOverdue)
                                        <span class="badge badge-danger ms-1">Overdue</span>
                                    @endif
                                @elseif ($status === 'paid')
                                    <span class="badge badge-success">Paid</span>
                                @endif
                            </td>
                        </tr>
                        <!--end::Row-->
                    </table>
                    <!--end::Details-->
                </div>

                <div class="col-md-1"></div>

                <div class="col-md-1">
                    <!--begin::Actions-->
                    <div class="d-flex justify-content-end mb-4">
                        @if ($invoice->paymentTransactions->count() == 0)
                            <!--begin::Three Dots-->
                            <div class="me-0">
                                <button class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                    <i class="ki-solid ki-dots-horizontal fs-2x"></i>
                                </button>
                                <!--begin::Three Dots-->

                                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-150px py-3"
                                    data-kt-menu="true">
                                    <div class="menu-item px-3">
                                        <a href="{{ route('invoices.edit', $invoice->id) }}"
                                            class="menu-link px-3 text-hover-primary"><i class="las la-pen fs-2 me-2"></i>
                                            Edit</a>
                                    </div>

                                    @if (optional($invoice->student->studentActivation)->active_status == 'active' && $invoice->status == 'due')

                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link text-hover-danger px-3 delete-invoice"
                                                data-invoice-id={{ $invoice->id }}><i class="las la-trash fs-2 me-2"></i>
                                                Delete</a>
                                        </div>
                                        <!--end::Menu item-->
                                    @endif
                                </div>
                                <!--end::Menu 3-->
                            </div>
                        @endif
                        <!--end::Menu-->
                    </div>
                    <!--end::Actions-->
                </div>
            </div>
            <!--end::Details-->
        </div>
    </div>
    <!--end::Navbar-->

    <!--begin::Table-->
    <div class="card mt-6 mt-xl-9">
        <!--begin::Header-->
        <div class="card-header">
            <!--begin::Title-->
            <div class="card-title">
                <h2>Transactions of this invoice</h2>
            </div>

            <!--end::Title-->
            <!--begin::Toolbar-->
            <div class="card-toolbar">
                <!--begin::Add user-->
                @if ($invoice->amount_due != 0)
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#kt_modal_add_transaction">
                        <i class="ki-outline ki-plus fs-2"></i>Transaction</a>
                @endif
                <!--end::Add user-->
            </div>
            <!--end::Toolbar-->
        </div>
        <!--end::Header-->

        <!--begin::Card body-->
        <div class="card-body pb-5">
            <!--begin::Tab panel-->
            <div class="py-0">
                <!--begin::Table-->
                <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                    id="kt_invoice_transactions_table">
                    <thead>
                        <tr class="fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-25px">SL</th>
                            <th class="w-150px">Invoice No.</th>
                            <th>Voucher No.</th>
                            <th>Amount (৳)</th>
                            <th>Payment Type</th>
                            <th class="w-350px">Student</th>
                            <th>Payment Date</th>
                            <th>Remarks</th>
                            <th>Download</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                        @foreach ($invoice->paymentTransactions as $transaction)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>
                                    {{ $transaction->paymentInvoice->invoice_number }}
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
                                    <a href="{{ route('students.show', $transaction->student->id) }}" target="_blank">
                                        {{ $transaction->student->name }},
                                        {{ $transaction->student->student_unique_id }}
                                    </a>
                                </td>

                                <td>
                                    {{ $transaction->created_at->format('h:i A, d-M-Y') }}
                                </td>

                                <td>{{ $transaction->remarks }}</td>

                                <td>
                                    <a href="{{ route('transactions.download', $transaction->id) }}" target="_blank"
                                        data-bs-toggle="tooltip" title="Download Payslip"
                                        class="btn btn-icon btn-active-light-primary w-30px h-30px me-3">
                                        <i class="bi bi-download fs-2"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Tab panel-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->


    <!--begin::Modal - Add Transaction-->
    <div class="modal fade" id="kt_modal_add_transaction" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_add_transaction_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Add Transaction for {{ $invoice->invoice_number }}</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_add_transaction_form" class="form" action="{{ route('transactions.store') }}"
                        method="POST" data-kt-redirect="false">
                        @csrf
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_transaction_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_transaction_header"
                            data-kt-scroll-wrappers="#kt_modal_add_transaction_scroll" data-kt-scroll-offset="300px">

                            {{-- hidden inputs --}}
                            <input type="hidden" name="transaction_student" value="{{ $invoice->student_id }}">
                            <input type="hidden" name="transaction_invoice" value="{{ $invoice->id }}">

                            <div id="invoice_status_indicator" data-status="{{ $invoice->status }}"
                                style="display:none;"></div>

                            <!--begin::Type Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="d-flex align-items-center form-label mb-3 required">Payment Type</label>
                                <!--end::Label-->
                                <!--begin::Row-->
                                <div class="row">
                                    <!--begin::Col-->
                                    <div class="col-lg-6">
                                        <!--begin::Option-->
                                        <input type="radio" class="btn-check" name="transaction_type" value="full"
                                            id="full_payment_type_input" checked />
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
                                    <div class="col-lg-6">
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
                                </div>
                                <!--end::Row-->
                            </div>
                            <!--end::Type Input group-->

                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Amount</label>
                                <!--end::Label-->
                                <!-- Amount input with total amount data attribute -->
                                <input type="number" name="transaction_amount" min="0"
                                    id="transaction_amount_input" class="form-control form-control-solid mb-3 mb-lg-0"
                                    placeholder="Enter the paid amount" value="{{ $invoice->amount_due }}"
                                    data-total-amount="{{ $invoice->total_amount }}" required />
                            </div>
                            <!--end::Name Input group-->

                            <!--begin::Name Input group-->
                            <div class="fv-row">
                                <!--begin::Label-->
                                <label class="fw-semibold fs-6 mb-2">Remarks <span
                                        class="text-muted">(optional)</span></label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="transaction_remarks" min="0"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Add some remarks" />
                                <!--end::Input-->
                            </div>
                            <!--end::Name Input group-->

                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Discard</button>
                            <button type="submit" class="btn btn-primary">
                                Submit
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
@endpush

@push('page-js')
    <script>
        const routeDeleteInvoice = "{{ route('invoices.destroy', ':id') }}";
    </script>

    <script src="{{ asset('js/invoices/view.js') }}"></script>
    <script src="{{ asset('js/invoices/view-ajax.js') }}"></script>

    <script>
        document.getElementById("invoices_link").classList.add("active");
    </script>
@endpush
