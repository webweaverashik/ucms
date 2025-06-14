@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'Sheet Group - ' . $sheet->class->name)

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            {{ $sheet->class->name }} ({{ $sheet->class->class_numeral }})
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
                    Sheet Group </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                View </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin::Layout-->
    <div class="d-flex flex-column flex-xl-row">
        <!--begin::Sidebar-->
        <div class="flex-column flex-lg-row-auto w-100 w-xl-350px mb-10">
            <!--begin::Card-->
            <div class="card card-flush mb-0" data-kt-sticky="true" data-kt-sticky-name="student-summary"
                data-kt-sticky-offset="{default: false, lg: 0}" data-kt-sticky-width="{lg: '250px', xl: '350px'}"
                data-kt-sticky-left="auto" data-kt-sticky-top="100px" data-kt-sticky-animation="false"
                data-kt-sticky-zindex="95">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>Sheet Info</h2>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body pt-0 fs-6">
                    <!--begin::Section-->
                    <div class="mb-7">
                        <!--begin::Details-->
                        <div class="d-flex align-items-center">
                            <!--begin::Info-->
                            <div class="d-flex flex-column">
                                <!--begin::Name-->
                                <span class="fs-4 fw-bold text-gray-900 me-2">{{ $sheet->class->name }}
                                    ({{ $sheet->class->class_numeral }})</span>
                                <!--end::Name-->
                            </div>
                            <!--end::Info-->
                        </div>
                        <!--end::Details-->
                    </div>
                    <!--end::Section-->

                    <!--begin::Seperator-->
                    <div class="separator separator-dashed mb-7"></div>
                    <!--end::Seperator-->

                    <!--begin::Section-->
                    <div class="mb-7">
                        <!--begin::Title-->
                        <h5 class="mb-4">Pricing
                        </h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <div class="mb-0">
                            <!--begin::Details-->
                            <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Price:</td>
                                    <td class="text-gray-800">৳ {{ $sheet->price }}</td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">No. of Sales:</td>
                                    <td class="text-gray-800">{{ $sheet->sheetPayments->count() }}</td>
                                </tr>
                                <!--end::Row-->

                            </table>
                            <!--end::Details-->
                        </div>
                        <!--end::Details-->
                    </div>
                    <!--end::Section-->

                    <!--begin::Seperator-->
                    <div class="separator separator-dashed mb-7"></div>
                    <!--end::Seperator-->

                    <!--begin::Section-->
                    <div class="mb-7">
                        <!--begin::Title-->
                        <h5 class="mb-4">Payment Info
                        </h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <div class="mb-0">
                            <!--begin::Details-->
                            <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Payment Style:</td>
                                    <td class="text-gray-800">
                                        @if ($student->payments)
                                            {{ ucfirst($student->payments->payment_style) }}
                                        @else
                                            Current
                                        @endif
                                    </td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Monthly Fee:</td>
                                    <td>
                                        @if ($student->payments)
                                            ৳ {{ $student->payments->tuition_fee }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Due Date:</td>
                                    <td class="text-gray-800">1 to @if ($student->payments)
                                            {{ $student->payments->due_date }}
                                        @else
                                            7
                                        @endif
                                    </td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr>
                                    <td class="text-gray-500">Reference:</td>
                                    @if ($student->reference && $student->reference->referer)
                                        <td class="text-gray-800">
                                            @php
                                                $referer = $student->reference->referer;
                                                $type = strtolower($student->reference->referer_type);
                                                $route =
                                                    $type === 'student'
                                                        ? route('students.show', $referer->id)
                                                        : route('teachers.show', $referer->id);
                                            @endphp

                                            <a href="{{ $route }}" class="fw-bold text-gray-800 text-hover-primary">
                                                {{ $referer->name ?? 'N/A' }}
                                                @if ($type === 'student')
                                                    ({{ $referer->student_unique_id }})
                                                @endif
                                            </a>
                                    @endif
                                    </td>
                                </tr>
                                <!--end::Row-->

                            </table>
                            <!--end::Details-->
                        </div>
                        <!--end::Details-->
                    </div>
                    <!--end::Section-->

                    <!--begin::Seperator-->
                    <div class="separator separator-dashed mb-7"></div>
                    <!--end::Seperator-->

                    <!--begin::Section-->
                    <div class="mb-0">
                        <!--begin::Title-->
                        <h5 class="mb-4">Activation Details</h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Status:</td>
                                <td>
                                    @php
                                        $status = $student->studentActivation?->active_status;
                                    @endphp

                                    @if ($status === 'inactive')
                                        <span class="badge badge-light-danger">{{ ucfirst($status) }}</span>
                                    @elseif ($status === 'active')
                                        <span class="badge badge-light-success">{{ ucfirst($status) }}</span>
                                    @else
                                        <span class="badge badge-light-info">Pending Approval</span>
                                    @endif

                                </td>
                            </tr>
                            <!--end::Row-->

                            <!--begin::Row-->
                            <tr class="">
                                @if (optional($student->studentActivation)->active_status == 'active')
                                    <td class="text-gray-500">Active Since:</td>
                                @elseif (optional($student->studentActivation)->active_status == 'inactive')
                                    <td class="text-gray-500">Inactive Since:</td>
                                @endif

                                @if ($student->studentActivation)
                                    <td class="text-gray-800">
                                        {{ $student->studentActivation->created_at->diffForHumans() }}
                                        <span class="ms-1" data-bs-toggle="tooltip"
                                            title="{{ $student->studentActivation->created_at->format('d-M-Y h:m:s A') }}">
                                            <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                        </span>
                                    </td>
                                @endif
                            </tr>
                            <!--end::Row-->

                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Admission Date:</td>
                                <td class="text-gray-800">
                                    {{ $student->created_at->format('d-M-Y') }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $student->created_at->format('d-M-Y h:m:s A') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>

                                </td>
                            </tr>
                            <!--end::Row-->

                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Remarks:</td>
                                <td class="text-gray-800">
                                    @if ($student->remarks)
                                        {{ $student->remarks }}
                                    @else
                                        <span class="text-gray-600">-</span>
                                    @endif
                                </td>
                            </tr>
                            <!--end::Row-->
                        </table>
                        <!--end::Details-->
                    </div>
                    <!--end::Section-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Sidebar-->

        <!--begin::Content-->
        <div class="flex-lg-row-fluid ms-lg-10">
            <!--begin:::Tabs-->
            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                        href="#kt_student_view_enrolled_subjects_tab"><i
                            class="ki-outline ki-book-open fs-3 me-2"></i>Notes</a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                        href="#kt_student_view_transactions_tab"><i
                            class="ki-outline ki-credit-cart fs-3 me-2"></i>Payments</a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item-->
                <li class="nav-item ms-auto">
                    <!--begin::Action menu-->
                    <a href="#" class="btn btn-primary ps-7"><i class="ki-outline ki-plus fs-2 me-0"></i>New Notes
                    </a>
                    <!--end::Action Menu-->
                </li>
                <!--end:::Tab item-->
            </ul>
            <!--end:::Tabs-->

            <!--begin:::Tab content-->
            <div class="tab-content" id="myTabContent">
                <!--begin:::Tab pane-->
                <div class="tab-pane fade show active" id="kt_student_view_enrolled_subjects_tab" role="tabpanel">
                    <!--begin::Card-->
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <!--begin::Card header-->
                        <div class="card-header border-0">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <h2>{{ $student->class->name }} ({{ $student->class->class_numeral }}) @if ($student->academic_group != 'General')
                                        - {{ $student->academic_group }}
                                    @endif
                                </h2>
                            </div>
                            <!--end::Card title-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body py-0">
                            <!--begin::Table wrapper-->
                            @php
                                $groupedSubjects = $student->subjectsTaken->groupBy(
                                    fn($item) => $item->subject->academic_group ?? 'Unknown',
                                );
                            @endphp

                            <div class="row">
                                {{-- Render priority groups first --}}
                                @foreach (['General', 'Science', 'Commerce'] as $priorityGroup)
                                    @if ($groupedSubjects->has($priorityGroup))
                                        <div class="col-12 mb-2">
                                            <h5 class="fw-bold">{{ $priorityGroup }}</h5>
                                            <div class="row">
                                                @foreach ($groupedSubjects[$priorityGroup] as $subjectTaken)
                                                    <div class="col-md-3 mb-3">
                                                        <h6 class="text-gray-600">
                                                            <i class="bi bi-check2-circle fs-3 text-success"></i>
                                                            {{ $subjectTaken->subject->name ?? 'N/A' }}
                                                        </h6>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach

                                {{-- Render remaining non-priority groups --}}
                                @foreach ($groupedSubjects as $group => $subjects)
                                    @if (!in_array($group, ['General', 'Science', 'Commerce']))
                                        <div class="col-12 mb-2">
                                            <h4 class="fw-bold">{{ $group }}</h4>
                                            <div class="row">
                                                @foreach ($subjects as $subjectTaken)
                                                    <div class="col-md-3 mb-3">
                                                        <h5 class="text-gray-700">
                                                            <i class="bi bi-check2-circle fs-3 text-success"></i>
                                                            {{ $subjectTaken->subject->name ?? 'N/A' }}
                                                        </h5>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            <!--end::Table wrapper-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end:::Tab pane-->

                <!--begin:::Tab pane-->
                <div class="tab-pane fade" id="kt_student_view_transactions_tab" role="tabpanel">
                    <!--begin::Earnings-->
                    <div class="card mb-6 mb-xl-9">
                        <!--begin::Header-->
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2>Sheet Fee Payment Summary</h2>
                            </div>
                        </div>
                        <!--end::Header-->
                        <!--begin::Body-->
                        <div class="card-body py-0">
                            <div class="fs-5 fw-semibold text-gray-500 mb-4">Summary of payments of this sheet group.
                            </div>
                            <!--begin::Left Section-->
                            <div class="d-flex flex-wrap flex-stack mb-5">
                                <!--begin::Row-->
                                <div class="d-flex flex-wrap">
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-gray-300 w-150px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span data-kt-countup="true"
                                                data-kt-countup-value="{{ $sheet->sheetPayments->sum(fn($payment) => $payment->invoice?->paymentTransactions->sum('amount_paid') ?? 0) }}"
                                                data-kt-countup-prefix="৳">0</span>
                                        </span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Total Paid</span>
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-gray-300 w-125px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span class="" data-kt-countup="true"
                                                data-kt-countup-value="{{ $sheet->sheetPayments->count() }}">0</span></span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Invoices</span>
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-warning w-150px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span data-kt-countup="true"
                                                data-kt-countup-value="{{ $sheet->sheetPayments->sum(fn($payment) => $payment->invoice->amount_due ?? 0) }}"
                                                data-kt-countup-prefix="৳">0</span>
                                        </span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Due</span>
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Row-->
                            </div>
                            <!--end::Left Section-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Earnings-->

                    <!--begin::Statements-->
                    <div class="card mb-6 mb-xl-9">
                        <div class="card-header border-0 pt-6">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <!--begin::Search-->
                                <div class="d-flex align-items-center position-relative my-1">
                                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input
                                        type="text" data-sheet-payments-table-filter="search"
                                        class="form-control form-control-solid w-350px ps-12"
                                        placeholder="Search in payments">
                                </div>
                                <!--end::Search-->
                            </div>
                            <!--begin::Card title-->

                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Toolbar-->
                                <div class="d-flex justify-content-end" data-sheet-payments-table-toolbar="base">
                                    <!--begin::Filter-->
                                    <button type="button" class="btn btn-light-primary me-3"
                                        data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
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
                                                <label class="form-label fs-6 fw-semibold">Payment Type:</label>
                                                <select class="form-select form-select-solid fw-bold"
                                                    data-kt-select2="true" data-placeholder="Select option"
                                                    data-allow-clear="true" data-sheet-payments-table-filter="status"
                                                    data-hide-search="true">
                                                    <option></option>
                                                    <option value="T_due">Due</option>
                                                    <option value="T_partially_paid">Partial Paid</option>
                                                    <option value="T_paid">Full Paid</option>
                                                </select>
                                            </div>
                                            <!--end::Input group-->

                                            <!--begin::Actions-->
                                            <div class="d-flex justify-content-end">
                                                <button type="reset"
                                                    class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                                    data-kt-menu-dismiss="true"
                                                    data-sheet-payments-table-filter="reset">Reset</button>
                                                <button type="submit" class="btn btn-primary fw-semibold px-6"
                                                    data-kt-menu-dismiss="true"
                                                    data-sheet-payments-table-filter="filter">Apply</button>
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
                        <div class="card-body pb-5 tab-content">
                            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                                id="kt_sheet_payments_table">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-25px">SL</th>
                                        <th class="w-200px">Invoice No.</th>
                                        <th>Amount (৳)</th>
                                        <th class="d-none">Status (Filter)</th>
                                        <th>Status</th>
                                        <th>Paid (৳)</th>
                                        <th class="w-350px">Student</th>
                                        <th>Billing Date</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach ($sheet->sheetPayments as $payment)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>
                                                <a href="{{ route('invoices.show', $payment->invoice->id) }}">
                                                    {{ $payment->invoice->invoice_number }}
                                                </a>
                                            </td>

                                            <td>{{ $payment->invoice->total_amount }}</td>
                                            <td class="d-none">
                                                @if ($payment->invoice->status === 'due')
                                                    T_due
                                                @elseif ($payment->invoice->status === 'partially_paid')
                                                    T_partially_paid
                                                @elseif ($payment->invoice->status === 'paid')
                                                    T_paid
                                                @endif
                                            </td>


                                            <td>
                                                @if ($payment->invoice->status === 'due')
                                                    <span class="badge badge-warning">Due</span>
                                                @elseif ($payment->invoice->status === 'partially_paid')
                                                    <span class="badge badge-info">Partial</span>
                                                @elseif ($payment->invoice->status === 'paid')
                                                    <span class="badge badge-success">Paid</span>
                                                @endif
                                            </td>

                                            <td>{{ $payment->invoice->paymentTransactions->sum('amount_paid') }}</td>

                                            <td>
                                                <a href="{{ route('students.show', $payment->student->id) }}">
                                                    {{ $payment->student->name }},
                                                    {{ $payment->student->student_unique_id }}
                                                </a>
                                            </td>

                                            <td>
                                                {{ $payment->created_at->format('h:i A, d-M-Y') }}
                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Statements-->
                </div>
                <!--end:::Tab pane-->
            </div>
            <!--end:::Tab content-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Layout-->

    <!--begin::Modal - Toggle Activation Student-->
    <div class="modal fade" id="kt_toggle_activation_student_modal" tabindex="-1" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header">
                    <!--begin::Modal title-->
                    <h2 id="toggle-activation-modal-title">Activation/Deactivation Student</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->

                <!--begin::Modal body-->
                <div class="modal-body py-lg-5">
                    <!--begin::Content-->
                    <div class="flex-row-fluid p-lg-5">
                        <div>
                            <form action="{{ route('students.toggleActive') }}" class="form d-flex flex-column"
                                method="POST">
                                @csrf
                                <!--begin::Left column-->
                                <div class="d-flex flex-column">

                                    <input type="hidden" name="student_id" id="student_id" />
                                    <input type="hidden" name="active_status" id="activation_status" />

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <!--begin::Input group-->
                                            <div class="d-flex flex-column mb-5 fv-row">
                                                <!--begin::Label-->
                                                <label class="fs-5 fw-semibold mb-2 required"
                                                    id="reason_label">Activation/Deactivation Reason</label>
                                                <!--end::Label-->

                                                <!--begin::Input-->
                                                <textarea class="form-control" rows="3" name="reason" placeholder="Write the reason for this update"
                                                    required></textarea>
                                                <!--end::Input-->
                                            </div>
                                            <!--end::Input group-->
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <!--begin::Button-->
                                        <button type="reset" class="btn btn-secondary me-5"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <!--end::Button-->
                                        <!--begin::Button-->
                                        <button type="submit" class="btn btn-primary">
                                            Submit
                                        </button>
                                        <!--end::Button-->
                                    </div>
                                </div>
                                <!--end::Left column-->
                            </form>
                        </div>
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - Toggle Activation Student-->

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
                                            <option value="sheet_fee">Sheet Fee</option>
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
    <script src="{{ asset('js/sheets/view.js') }}"></script>

    <script>
        document.getElementById("notes_sheets_menu").classList.add("here", "show");
        document.getElementById("all_sheets_link").classList.add("active");
    </script>
@endpush
