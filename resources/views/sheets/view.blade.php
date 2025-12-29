@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/sheets/view.css') }}" rel="stylesheet" type="text/css" />
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
    @php
        $canNotesManage = auth()->user()->can('notes.manage');
        $isAdmin = auth()->user()->isAdmin();
    @endphp
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
                        <h3 class="text-gray-600">Sheet Group Info</h3>
                    </div>
                    <!--end::Card title-->

                    @canany(['sheets.edit', 'notes.manage'])
                        <!--begin::Card toolbar-->
                        <div class="card-toolbar">
                            <!--begin::More options-->
                            <a href="#" class="btn btn-sm btn-light btn-icon" data-kt-menu-trigger="click"
                                data-kt-menu-placement="bottom-end">
                                <i class="ki-outline ki-dots-horizontal fs-3">
                                </i>
                            </a>
                            <!--begin::Menu-->
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-6 w-175px py-4"
                                data-kt-menu="true">
                                @can('sheets.edit')
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_sheet"
                                            data-sheet-id="{{ $sheet->id }}" data-sheet-price="{{ $sheet->price }}"
                                            data-sheet-class="{{ $sheet->class->name }} ({{ $sheet->class->class_numeral }})"
                                            class="menu-link text-hover-primary px-3"><i class="las la-pen fs-3 me-2"></i> Edit
                                            Price</a>
                                    </div>
                                    <!--end::Menu item-->
                                @endcan

                                @can('notes.manage')
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" class="menu-link text-hover-primary px-3" data-bs-toggle="modal"
                                            data-bs-target="#kt_modal_add_notes"><i class="ki-outline ki-plus fs-3 me-2"></i>
                                            New Notes</a>
                                    </div>
                                    <!--end::Menu item-->
                                @endcan
                            </div>
                            <!--end::Menu-->
                            <!--end::More options-->
                        </div>
                        <!--end::Card toolbar-->
                    @endcanany
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
                                <span class="fs-1 fw-bold text-gray-900 me-2">{{ $sheet->class->name }}
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
                    <div class="mb-0">
                        <!--begin::Title-->
                        <h5 class="mb-4">Activation Details</h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                            <tr class="">
                                <td class="text-gray-500">Created Since:</td>

                                <td class="text-gray-800">
                                    {{ $sheet->created_at->diffForHumans() }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $sheet->created_at->format('d-M-Y h:m:s A') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>
                                </td>
                            </tr>

                            <tr class="">
                                <td class="text-gray-500">Updated Since:</td>

                                <td class="text-gray-800">
                                    {{ $sheet->updated_at->diffForHumans() }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $sheet->updated_at->format('d-M-Y h:m:s A') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>
                                </td>
                            </tr>
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
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_sheet_notes_tab"><i
                            class="ki-outline ki-book-open fs-3 me-2"></i>Notes</a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                        href="#kt_sheet_payments_tab"><i class="ki-outline ki-credit-cart fs-3 me-2"></i>Payments</a>
                </li>
                <!--end:::Tab item-->

                @can('notes.manage')
                    <!--begin:::Tab item-->
                    <li class="nav-item ms-auto">
                        <!--begin::Action menu-->
                        <a href="#" class="btn btn-primary ps-7" data-bs-toggle="modal"
                            data-bs-target="#kt_modal_add_notes"><i class="ki-outline ki-plus fs-2 me-0"></i>New Notes
                        </a>
                        <!--end::Action Menu-->
                    </li>
                    <!--end:::Tab item-->
                @endcan
            </ul>
            <!--end:::Tabs-->

            <!--begin:::Tab content-->
            <div class="tab-content" id="myTabContent">
                <!--begin:::Tab pane-->
                <div class="tab-pane fade show active" id="kt_sheet_notes_tab" role="tabpanel">
                    <!--begin::Card-->
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <!--begin::Card header-->
                        <div class="card-header border-0">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <h2>Notes</h2>
                            </div>
                            <!--end::Card title-->
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge badge-light-primary fs-7">
                                        <i class="ki-outline ki-book fs-6 me-1"></i>
                                        {{ $sheet->class->subjects->sum(fn($s) => $s->sheetTopics->count()) }} Notes
                                    </span>
                                </div>
                            </div>
                            <!--end::Card toolbar-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body py-4">
                            @forelse ($sheet->class->subjects as $subject)
                                @if ($subject->sheetTopics->isNotEmpty())
                                    <!--begin::Subject Section-->
                                    <div class="subject-section">
                                        <!--begin::Subject Header-->
                                        <div class="subject-header d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <i class="ki-outline ki-book-square fs-3 me-2"></i>
                                                <h6 class="mb-0 fw-bold">{{ $subject->name }}</h6>
                                                @if ($subject->academic_group !== 'General')
                                                    <span class="badge ms-2 fs-8">{{ $subject->academic_group }}</span>
                                                @endif
                                            </div>
                                            <span class="notes-count fs-7 fw-semibold">
                                                <i class="ki-outline ki-notepad-bookmark fs-6 me-1"></i>
                                                {{ $subject->sheetTopics->count() }} notes
                                            </span>
                                        </div>
                                        <!--end::Subject Header-->

                                        <!--begin::Notes Grid-->
                                        <div class="p-4">
                                            <div class="row g-4">
                                                @foreach ($subject->sheetTopics as $topic)
                                                    <div class="col-md-6 col-xl-4">
                                                        <!--begin::Note Card-->
                                                        <div class="note-card p-3 h-100 {{ $topic->status == 'inactive' ? 'note-inactive' : '' }}"
                                                            data-id="{{ $topic->id }}"
                                                            data-topic-name="{{ $topic->topic_name }}"
                                                            data-pdf-path="{{ $topic->pdf_path }}">

                                                            <!--begin::Note Header-->
                                                            <div
                                                                class="d-flex align-items-start justify-content-between mb-3">
                                                                <div class="d-flex align-items-center flex-grow-1 me-2">
                                                                    <div
                                                                        class="note-icon {{ $topic->pdf_path ? 'has-pdf' : '' }} me-3">
                                                                        <i
                                                                            class="ki-outline ki-{{ $topic->pdf_path ? 'document' : 'notepad' }}"></i>
                                                                    </div>
                                                                    <div class="flex-grow-1 min-w-0">
                                                                        <span
                                                                            class="note-title fw-semibold fs-6 d-block text-truncate"
                                                                            data-bs-toggle="tooltip"
                                                                            title="{{ $topic->topic_name }}">
                                                                            {{ $topic->topic_name }}
                                                                        </span>
                                                                        <span class="text-muted fs-8">
                                                                            <i class="ki-outline ki-people fs-8 me-1"></i>
                                                                            {{ $topic->sheetsTaken->count() }} students
                                                                        </span>
                                                                    </div>
                                                                </div>

                                                                @if ($canNotesManage)
                                                                    <!--begin::Actions-->
                                                                    <div
                                                                        class="note-actions d-flex align-items-center gap-1">
                                                                        <div
                                                                            class="form-check form-switch form-check-custom form-check-solid form-check-sm">
                                                                            <input class="form-check-input status-toggle"
                                                                                type="checkbox"
                                                                                data-id="{{ $topic->id }}"
                                                                                data-bs-toggle="tooltip"
                                                                                title="Toggle Active/Inactive"
                                                                                @if ($topic->status == 'active') checked @endif>
                                                                        </div>
                                                                        <button type="button"
                                                                            class="btn btn-icon btn-sm btn-light-primary edit-note-btn"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#kt_modal_edit_notes"
                                                                            data-topic-id="{{ $topic->id }}"
                                                                            data-topic-name="{{ $topic->topic_name }}"
                                                                            data-topic-pdf="{{ $topic->pdf_path }}"
                                                                            title="Edit Note">
                                                                            <i class="ki-outline ki-pencil fs-5"></i>
                                                                        </button>
                                                                        @if ($topic->sheetsTaken->count() == 0)
                                                                            <button type="button"
                                                                                class="btn btn-icon btn-sm btn-light-danger delete-note"
                                                                                data-topic-id="{{ $topic->id }}"
                                                                                title="Delete Note">
                                                                                <i class="ki-outline ki-trash fs-5"></i>
                                                                            </button>
                                                                        @endif
                                                                    </div>
                                                                    <!--end::Actions-->
                                                                @endif
                                                            </div>
                                                            <!--end::Note Header-->

                                                            <!--begin::Note Footer-->
                                                            <div
                                                                class="d-flex align-items-center justify-content-between pt-3 mt-2 border-top">
                                                                @if ($topic->pdf_path)
                                                                    @if ($isAdmin || $topic->status == 'active')
                                                                        <a href="{{ asset($topic->pdf_path) }}"
                                                                            download="{{ basename($topic->pdf_path) }}"
                                                                            class="pdf-preview-link">
                                                                            <i class="ki-outline ki-file-down fs-5"></i>
                                                                            <span>Download PDF</span>
                                                                        </a>
                                                                    @else
                                                                        <span class="pdf-preview-link disabled"
                                                                            data-bs-toggle="tooltip"
                                                                            title="PDF is not available for inactive notes">
                                                                            <i class="ki-outline ki-file-down fs-5"></i>
                                                                            <span>Download PDF</span>
                                                                        </span>
                                                                    @endif
                                                                @else
                                                                    <span class="text-muted fs-8">
                                                                        <i
                                                                            class="ki-outline ki-information-2 fs-7 me-1"></i>
                                                                        No PDF attached
                                                                    </span>
                                                                @endif

                                                                <span
                                                                    class="status-badge {{ $topic->status == 'active' ? 'active' : 'inactive' }}">
                                                                    <i
                                                                        class="ki-outline ki-{{ $topic->status == 'active' ? 'check-circle' : 'cross-circle' }} fs-7 me-1"></i>
                                                                    {{ ucfirst($topic->status) }}
                                                                </span>
                                                            </div>
                                                            <!--end::Note Footer-->
                                                        </div>
                                                        <!--end::Note Card-->
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <!--end::Notes Grid-->
                                    </div>
                                    <!--end::Subject Section-->
                                @endif
                            @empty
                                <!--begin::Empty State-->
                                <div class="text-center py-15">
                                    <div class="empty-state-icon">
                                        <i class="ki-outline ki-notepad-bookmark"></i>
                                    </div>
                                    <h4 class="text-gray-800 fw-bold mb-3">No Notes Added Yet</h4>
                                    <p class="text-muted fs-6 mb-6 mw-400px mx-auto">
                                        Start by adding your first note for this sheet group. Notes help students access
                                        study materials easily.
                                    </p>
                                    @can('notes.manage')
                                        <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#kt_modal_add_notes">
                                            <i class="ki-outline ki-plus fs-3 me-1"></i>
                                            Add First Note
                                        </a>
                                    @endcan
                                </div>
                                <!--end::Empty State-->
                            @endforelse
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end:::Tab pane-->

                <!--begin:::Tab pane-->
                <div class="tab-pane fade" id="kt_sheet_payments_tab" role="tabpanel">
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

    <!--begin::Modal - Add Notes-->
    <div class="modal fade" id="kt_modal_add_notes" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_add_notes_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Create a new note</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-add-note-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-5">
                    <!--begin::Form-->
                    <form id="kt_modal_add_notes_form" class="form" action="#" enctype="multipart/form-data"
                        novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_notes_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_sheet_header"
                            data-kt-scroll-wrappers="#kt_modal_add_notes_scroll" data-kt-scroll-offset="300px">

                            <!--begin::Subject Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Select Subject</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <select name="sheet_subject_id" class="form-select form-select-solid"
                                    data-dropdown-parent="#kt_modal_add_notes" data-control="select2"
                                    data-placeholder="Select class" required>
                                    <option></option>
                                    @foreach ($sheet->class->subjects as $subject)
                                        <option value="{{ $subject->id }}">
                                            {{ $subject->name }}{{ $subject->academic_group !== 'General' ? " ({$subject->academic_group})" : '' }}
                                        </option>
                                    @endforeach
                                </select>

                                <!--end::Input-->
                            </div>
                            <!--end::Subject Input group-->

                            <!--begin::Note name Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Note name</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="notes_name"
                                    class="form-control form-control-solid mb-3 mb-lg-0"
                                    placeholder="e.g. Chapter 1 Notes" required />
                                <!--end::Input-->
                            </div>
                            <!--end::Note name Input group-->

                            <!--begin::PDF Upload Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="fw-semibold fs-6 mb-2">Upload PDF (Optional)</label>
                                <!--end::Label-->
                                <!--begin::File Upload-->
                                <div class="file-upload-wrapper" id="add_notes_file_wrapper">
                                    <input type="file" name="pdf_file" id="add_notes_pdf_file" accept=".pdf" />
                                    <div class="file-upload-content">
                                        <i class="ki-outline ki-file-up fs-3x text-primary mb-3"></i>
                                        <div class="fs-6 fw-semibold text-gray-700 mb-1">Drop PDF here or click to upload
                                        </div>
                                        <div class="fs-7 text-muted">Max file size: 10MB</div>
                                    </div>
                                    <div class="file-selected-info d-none">
                                        <i class="ki-outline ki-document fs-2x text-success mb-2"></i>
                                        <div class="fs-6 fw-semibold text-success file-name"></div>
                                        <button type="button" class="btn btn-sm btn-light-danger mt-2 remove-file-btn">
                                            <i class="ki-outline ki-trash fs-5"></i> Remove
                                        </button>
                                    </div>
                                </div>
                                <!--end::File Upload-->
                            </div>
                            <!--end::PDF Upload Input group-->
                        </div>
                        <!--end::Scroll-->

                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-add-note-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-add-note-modal-action="submit">
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
    <!--end::Modal - Add Notes-->

    <!--begin::Modal - Edit Notes-->
    <div class="modal fade" id="kt_modal_edit_notes" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_edit_notes_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Edit Note</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-edit-note-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-5">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_notes_form" class="form" action="#" enctype="multipart/form-data"
                        novalidate="novalidate">
                        <input type="hidden" name="topic_id" id="edit_topic_id" />
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_notes_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_notes_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_notes_scroll" data-kt-scroll-offset="300px">

                            <!--begin::Note name Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Note name</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="topic_name" id="edit_topic_name"
                                    class="form-control form-control-solid mb-3 mb-lg-0"
                                    placeholder="e.g. Chapter 1 Notes" required />
                                <!--end::Input-->
                            </div>
                            <!--end::Note name Input group-->

                            <!--begin::Current PDF Info-->
                            <div class="fv-row mb-7" id="current_pdf_section">
                                <label class="fw-semibold fs-6 mb-2">Current PDF</label>
                                <div class="current-pdf-info d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-outline ki-document fs-2x text-primary me-3"></i>
                                        <div>
                                            <span class="fs-6 fw-semibold text-gray-800" id="current_pdf_name">No PDF
                                                uploaded</span>
                                            <span class="d-block fs-7 text-muted">Click below to replace</span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        @if ($isAdmin)
                                            <a href="#" id="download_current_pdf"
                                                class="btn btn-sm btn-light-primary" download>
                                                <i class="ki-outline ki-file-down fs-5"></i> Download
                                            </a>
                                        @endif
                                        <button type="button" id="remove_current_pdf"
                                            class="btn btn-sm btn-light-danger">
                                            <i class="ki-outline ki-trash fs-5"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!--end::Current PDF Info-->

                            <!--begin::PDF Upload Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="fw-semibold fs-6 mb-2" id="edit_pdf_label">Upload New PDF</label>
                                <!--end::Label-->
                                <!--begin::File Upload-->
                                <div class="file-upload-wrapper" id="edit_notes_file_wrapper">
                                    <input type="file" name="pdf_file" id="edit_notes_pdf_file" accept=".pdf" />
                                    <input type="hidden" name="remove_pdf" id="remove_pdf_flag" value="0" />
                                    <div class="file-upload-content">
                                        <i class="ki-outline ki-file-up fs-3x text-primary mb-3"></i>
                                        <div class="fs-6 fw-semibold text-gray-700 mb-1">Drop PDF here or click to upload
                                        </div>
                                        <div class="fs-7 text-muted">Max file size: 10MB</div>
                                    </div>
                                    <div class="file-selected-info d-none">
                                        <i class="ki-outline ki-document fs-2x text-success mb-2"></i>
                                        <div class="fs-6 fw-semibold text-success file-name"></div>
                                        <button type="button" class="btn btn-sm btn-light-danger mt-2 remove-file-btn">
                                            <i class="ki-outline ki-trash fs-5"></i> Remove
                                        </button>
                                    </div>
                                </div>
                                <!--end::File Upload-->
                            </div>
                            <!--end::PDF Upload Input group-->
                        </div>
                        <!--end::Scroll-->

                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-edit-note-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-edit-note-modal-action="submit">
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
    <!--end::Modal - Edit Notes-->


    <!--begin::Modal - Edit Sheet-->
    <div class="modal fade" id="kt_modal_edit_sheet" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_edit_sheet_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold" id="kt_modal_edit_sheet_title">Update Sheet Price</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-sheet-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_sheet_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_sheet_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_sheet_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_sheet_scroll" data-kt-scroll-offset="300px">

                            <!--begin::Price Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Update Price</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="number" name="sheet_price_edit" min="100"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. 2000"
                                    required />
                                <!--end::Input-->
                            </div>
                            <!--end::Price Input group-->

                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-sheet-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-sheet-modal-action="submit">
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
    <!--end::Modal - Edit Sheet-->
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        const routeDeleteNote = "{{ route('notes.destroy', ':id') }}";
        const routeUpdateNote = "{{ route('notes.update', ':id') }}";
    </script>

    <script src="{{ asset('js/sheets/view.js') }}"></script>

    <script>
        $('select[data-control="select2"]').select2({
            width: 'resolve'
        });
    </script>

    <script>
        document.getElementById("notes_sheets_menu").classList.add("here", "show");
        document.getElementById("all_sheets_link").classList.add("active");
    </script>
@endpush
