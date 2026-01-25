@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'All Invoices')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Invoices
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">Payments Info</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Invoices</li>
        </ul>
    </div>
@endsection

@section('content')
    @php
        $canEditInvoice = auth()->user()->can('invoices.edit');
        $canDeleteInvoice = auth()->user()->can('invoices.delete');
        $canViewInvoice = auth()->user()->can('invoices.view');
    @endphp

    <!--begin:::Tabs-->
    <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
        <li class="nav-item">
            <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_due_invoices_tab">
                <i class="ki-outline ki-home fs-3 me-2"></i>Due Invoices
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_paid_invoices_tab">
                <i class="ki-outline ki-book-open fs-3 me-2"></i>Fully Paid Invoices
            </a>
        </li>
        @can('invoices.create')
            <li class="nav-item ms-auto">
                <a href="#" class="btn btn-primary ps-7" data-bs-toggle="modal" data-bs-target="#kt_modal_create_invoice">
                    <i class="ki-outline ki-plus fs-2 me-0"></i> Create Invoice
                </a>
            </li>
        @endcan
    </ul>
    <!--end:::Tabs-->

    @php
        $badgeColors = [
            'badge-light-primary',
            'badge-light-success',
            'badge-light-warning',
            'badge-light-danger',
            'badge-light-info',
        ];
    @endphp

    <!--begin:::Tab content-->
    <div class="tab-content" id="myTabContent">
        <!--begin:::Tab pane - Due Invoices-->
        <div class="tab-pane fade show active" id="kt_due_invoices_tab" role="tabpanel">
            @if ($isAdmin)
                <!--begin::Branch Tabs for Admin-->
                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="branchTabsDue">
                    @foreach ($branches as $index => $branch)
                        @php
                            $colorClass = $badgeColors[$index % count($badgeColors)];
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link fw-bold {{ $index === 0 ? 'active' : '' }}" data-bs-toggle="tab"
                                href="#due_branch_{{ $branch->id }}" data-branch-id="{{ $branch->id }}"
                                data-badge-color="{{ $colorClass }}">
                                <i class="ki-outline ki-bank fs-4 me-1"></i>{{ $branch->branch_name }}
                                @if (isset($branchDueCounts[$branch->id]) && $branchDueCounts[$branch->id] > 0)
                                    <span
                                        class="badge {{ $colorClass }} badge-sm ms-2">{{ $branchDueCounts[$branch->id] }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
                <!--end::Branch Tabs-->

                <!--begin::Branch Tab Content-->
                <div class="tab-content" id="branchTabsDueContent">
                    @foreach ($branches as $index => $branch)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                            id="due_branch_{{ $branch->id }}" role="tabpanel">
                            @include('invoices.partials.due_invoice_table', [
                                'branchId' => $branch->id,
                                'tableId' => 'kt_due_invoices_table_' . $branch->id,
                            ])
                        </div>
                    @endforeach
                </div>
                <!--end::Branch Tab Content-->
            @else
                @include('invoices.partials.due_invoice_table', [
                    'branchId' => auth()->user()->branch_id,
                    'tableId' => 'kt_due_invoices_table',
                ])
            @endif
        </div>
        <!--end:::Tab pane-->

        <!--begin:::Tab pane - Paid Invoices-->
        <div class="tab-pane fade" id="kt_paid_invoices_tab" role="tabpanel">
            @if ($isAdmin)
                <!--begin::Branch Tabs for Admin-->
                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="branchTabsPaid">
                    @foreach ($branches as $index => $branch)
                        <li class="nav-item">
                            <a class="nav-link fw-bold {{ $index === 0 ? 'active' : '' }}" data-bs-toggle="tab"
                                href="#paid_branch_{{ $branch->id }}" data-branch-id="{{ $branch->id }}">
                                <i class="ki-outline ki-bank fs-4 me-1"></i>{{ $branch->branch_name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
                <!--end::Branch Tabs-->

                <!--begin::Branch Tab Content-->
                <div class="tab-content" id="branchTabsPaidContent">
                    @foreach ($branches as $index => $branch)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                            id="paid_branch_{{ $branch->id }}" role="tabpanel">
                            @include('invoices.partials.paid_invoice_table', [
                                'branchId' => $branch->id,
                                'tableId' => 'kt_paid_invoices_table_' . $branch->id,
                            ])
                        </div>
                    @endforeach
                </div>
                <!--end::Branch Tab Content-->
            @else
                @include('invoices.partials.paid_invoice_table', [
                    'branchId' => auth()->user()->branch_id,
                    'tableId' => 'kt_paid_invoices_table',
                ])
            @endif
        </div>
        <!--end:::Tab pane-->
    </div>
    <!--end:::Tab content-->

    <!--begin::Modal - Create Invoice-->
    <div class="modal fade" id="kt_modal_create_invoice" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered mw-750px">
            <div class="modal-content">
                <div class="modal-header" id="kt_modal_add_invoice_header">
                    <h2 class="fw-bold">Create Invoice</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-add-invoice-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body px-5 my-7">
                    <form id="kt_modal_add_invoice_form" class="form" novalidate="novalidate">
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_invoice_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_transaction_header"
                            data-kt-scroll-wrappers="#kt_modal_add_invoice_scroll" data-kt-scroll-offset="300px">
                            <div class="row">
                                <div class="fv-row mb-7 col-12">
                                    <label class="required fw-semibold fs-6 mb-2">Select Student</label>
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
                                                        ({{ $student->student_unique_id }})
                                                        -
                                                        {{ ucfirst($student->payments->payment_style) }} -
                                                        1/{{ $student->payments->due_date }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="fv-row mb-7 col-12">
                                    <label class="required fw-semibold fs-6 mb-2">Invoice Type</label>
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
                                                @foreach ($invoice_types->where('type_name', '!=', 'Special Class Fee') as $type)
                                                    <option value="{{ $type->id }}"
                                                        data-type-name="{{ $type->type_name }}">{{ $type->type_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="fv-row mb-7 col-6" id="month_year_type_id">
                                    <label class="required fw-semibold fs-6 mb-2">Billing Type</label>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <input type="radio" class="btn-check" name="month_year_type"
                                                checked="checked" value="new_invoice" id="new_invoice_input" />
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                for="new_invoice_input">
                                                <i class="ki-outline ki-abstract fs-2x me-5"></i>
                                                <span class="d-block fw-semibold text-start">
                                                    <span class="text-gray-900 fw-bold d-block fs-6">New</span>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-lg-6">
                                            <input type="radio" class="btn-check" name="month_year_type"
                                                value="old_invoice" id="old_invoice_input" />
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                for="old_invoice_input">
                                                <i class="ki-outline ki-abstract-20 fs-2x me-5"></i>
                                                <span class="d-block fw-semibold text-start">
                                                    <span class="text-gray-900 fw-bold d-block fs-6">Old</span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="fv-row mb-7 col-6" id="month_year_id">
                                    <label class="required fw-semibold fs-6 mb-2">Billing Month</label>
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
                                </div>
                                <div class="fv-row mb-7 col-12">
                                    <label class="required fw-semibold fs-6 mb-2">Amount</label>
                                    <div class="input-group input-group-solid flex-nowrap">
                                        <span class="input-group-text">
                                            <i class="ki-outline ki-dollar fs-3"></i>
                                        </span>
                                        <div class="overflow-hidden flex-grow-1">
                                            <input type="number" name="invoice_amount" min="50"
                                                class="form-control form-control-solid mb-3 mb-lg-0 rounded-start-0 border-start"
                                                placeholder="Enter the amount" disabled required />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-add-invoice-modal-action="cancel">Discard</button>
                            <button type="button" class="btn btn-primary" data-kt-add-invoice-modal-action="submit">
                                <span class="indicator-label">Submit</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--end::Modal - Create Invoice-->

    <!--begin::Modal - Edit Invoice-->
    <div class="modal fade" id="kt_modal_edit_invoice" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold" id="kt_modal_edit_invoice_title">Update Invoice</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-edit-invoice-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body px-5 my-7">
                    <form id="kt_modal_edit_invoice_form" class="form" action="#" novalidate="novalidate">
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_invoice_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_transaction_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_invoice_scroll" data-kt-scroll-offset="300px">
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Corresponding Student</label>
                                <div class="form-control form-control-solid bg-light-secondary" id="edit_student_display">
                                    <span class="text-muted">Loading...</span>
                                </div>
                            </div>
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Invoice Type</label>
                                <div class="form-control form-control-solid bg-light-secondary"
                                    id="edit_invoice_type_display">
                                    <span class="text-muted">Loading...</span>
                                </div>
                            </div>
                            <div class="fv-row mb-7" id="month_year_id_edit">
                                <label class="fw-semibold fs-6 mb-2">Billing Month</label>
                                <div class="form-control form-control-solid bg-light-secondary"
                                    id="edit_month_year_display">
                                    <span class="text-muted">Loading...</span>
                                </div>
                            </div>
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Amount</label>
                                <div class="input-group input-group-solid flex-nowrap">
                                    <span class="input-group-text">
                                        <i class="ki-outline ki-dollar fs-3"></i>
                                    </span>
                                    <div class="overflow-hidden flex-grow-1">
                                        <input type="number" name="invoice_amount_edit" min="50"
                                            class="form-control form-control-solid mb-3 mb-lg-0 rounded-start-0 border-start"
                                            placeholder="Enter the amount" required />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-edit-invoice-modal-action="cancel">Discard</button>
                            <button type="button" class="btn btn-primary" data-kt-edit-invoice-modal-action="submit">
                                <span class="indicator-label">Update</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--end::Modal - Edit Invoice-->

    <!--begin::Modal - Add Comment-->
    <div class="modal fade" id="kt_modal_add_comment" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold" id="kt_modal_add_comment_title">Add Comment</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-add-comment-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body px-5 my-7">
                    <form id="kt_modal_add_comment_form" class="form" novalidate="novalidate">
                        <input type="hidden" name="payment_invoice_id" id="comment_invoice_id" value="" />
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_comment_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_add_comment_header"
                            data-kt-scroll-wrappers="#kt_modal_add_comment_scroll" data-kt-scroll-offset="300px">
                            <div class="mb-7" id="previous_comments_section">
                                <label class="fw-semibold fs-6 mb-3">Previous Comments</label>
                                <div id="previous_comments_container" class="border rounded p-4 bg-light-secondary"
                                    style="max-height: 200px; overflow-y: auto;">
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
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">New Comment</label>
                                <textarea name="comment" class="form-control form-control-solid" rows="4"
                                    placeholder="Enter your comment here..." required></textarea>
                                <div class="form-text text-muted">Minimum 3 characters, maximum 1000 characters.</div>
                            </div>
                        </div>
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-add-comment-modal-action="cancel">Discard</button>
                            <button type="button" class="btn btn-primary" data-kt-add-comment-modal-action="submit">
                                <span class="indicator-label">Add Comment</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--end::Modal - Add Comment-->
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
        // Routes
        const routeDeleteInvoice = "{{ route('invoices.destroy', ':id') }}";
        const routeStoreInvoice = "{{ route('invoices.store') }}";
        const routeStoreComment = "{{ route('invoice.comments.store') }}";
        const routeGetComments = "{{ route('invoice.comments.index', ':id') }}";
        const routeUnpaidAjax = "{{ route('invoices.unpaid.ajax') }}";
        const routePaidAjax = "{{ route('invoices.paid.ajax') }}";
        const routeExportAjax = "{{ route('invoices.export.ajax') }}";
        const routeFilterOptions = "{{ route('invoices.filter.options') }}";
        const routeInvoiceShow = "{{ route('invoices.show', ':id') }}";
        const routeStudentShow = "{{ route('students.show', ':id') }}";
        const routeBranchDueCounts = "{{ route('invoices.branch.due.counts') }}";

        // Permissions
        const canEditInvoice = {{ $canEditInvoice ? 'true' : 'false' }};
        const canDeleteInvoice = {{ $canDeleteInvoice ? 'true' : 'false' }};
        const canViewInvoice = {{ $canViewInvoice ? 'true' : 'false' }};

        // Invoice types for filters
        const invoiceTypes = @json($invoice_types);

        // Is admin
        const isAdmin = {{ $isAdmin ? 'true' : 'false' }};

        // First branch ID for admin
        const firstBranchId = {{ $isAdmin && $branches->count() > 0 ? $branches->first()->id : 'null' }};
    </script>
    <script src="{{ asset('js/invoices/index.js') }}"></script>
    <script>
        document.getElementById("payments_menu").classList.add("here", "show");
        document.getElementById("invoices_link").classList.add("active");
    </script>
@endpush