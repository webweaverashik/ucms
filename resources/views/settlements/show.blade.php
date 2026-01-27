@extends('layouts.app')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('title', 'Wallet History - ' . $user->name)

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            {{ $user->name }} - Wallet History
        </h1>
        <!--end::Title-->

        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->

        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('settlements.index') }}" class="text-muted text-hover-primary"> Settlements</a>
            </li>
            <!--end::Item-->

            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->

            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Personal Wallet
            </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection

@section('content')
    {{-- User Profile Card --}}
    <div class="card mb-5 mb-xl-10">
        <div class="card-body pt-9 pb-0">
            <div class="d-flex flex-wrap flex-sm-nowrap">
                <div class="me-7 mb-4">
                    <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                        @if ($user->photo_url)
                            <img src="{{ asset($user->photo_url) }}" alt="{{ $user->name }}" />
                        @else
                            <div class="symbol-label fs-1 bg-light-primary text-primary">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                        <div class="d-flex flex-column">
                            <div class="d-flex align-items-center mb-2">
                                <span class="text-gray-900 fs-2 fw-bold me-1">{{ $user->name }}</span>
                                @if ($user->is_active)
                                    <span class="badge badge-light-success">Active</span>
                                @else
                                    <span class="badge badge-light-danger">Inactive</span>
                                @endif
                            </div>

                            <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                                <span class="d-flex align-items-center text-gray-500 me-5 mb-2">
                                    <i class="ki-outline ki-profile-circle fs-4 me-1"></i>
                                    {{ ucfirst($user->roles->first()?->name) ?? 'User' }}
                                </span>
                                <span class="d-flex align-items-center text-gray-500 me-5 mb-2">
                                    <i class="ki-outline ki-geolocation fs-4 me-1"></i>
                                    {{ $user->branch->branch_name ?? 'N/A' }}
                                </span>
                                <span class="d-flex align-items-center text-gray-500 mb-2">
                                    <i class="ki-outline ki-phone fs-4 me-1"></i>
                                    {{ $user->mobile_number }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap flex-stack">
                        <div class="d-flex flex-column flex-grow-1 pe-8">
                            <div class="d-flex flex-wrap">
                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-outline ki-arrow-up fs-3 text-success me-2"></i>
                                        <div class="fs-2 fw-bold text-success">
                                            ৳{{ number_format($summary['total_collected'], 0) }}
                                        </div>
                                    </div>
                                    <div class="fw-semibold fs-6 text-gray-500">Total Collected</div>
                                </div>

                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-outline ki-arrow-down fs-3 text-info me-2"></i>
                                        <div class="fs-2 fw-bold text-info">
                                            ৳{{ number_format($summary['total_settled'], 0) }}
                                        </div>
                                    </div>
                                    <div class="fw-semibold fs-6 text-gray-500">Total Settled</div>
                                </div>

                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-outline ki-wallet fs-3 text-warning me-2"></i>
                                        <div class="fs-2 fw-bold text-warning" id="current_balance_display"
                                            data-wallet-balance="{{ $summary['current_balance'] }}">
                                            ৳{{ number_format($summary['current_balance'], 0) }}
                                        </div>
                                    </div>
                                    <div class="fw-semibold fs-6 text-gray-500">Current Balance</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
                <li class="nav-item mt-2">
                    <a class="nav-link text-active-primary ms-0 me-10 py-5 active" href="#">
                        Transaction History
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- Today's Summary --}}
    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <div class="col-md-6 col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <div class="card-title d-flex flex-column">
                        <span
                            class="fs-2hx fw-bold text-success me-2 lh-1 ls-n2">৳{{ number_format($summary['today_collected'], 0) }}</span>
                        <span class="text-gray-500 pt-1 fw-semibold fs-6">Today's Collection</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card card-flush h-100">
                <div class="card-header pt-5">
                    <div class="card-title d-flex flex-column">
                        <span
                            class="fs-2hx fw-bold text-info me-2 lh-1 ls-n2">৳{{ number_format($summary['today_settled'], 0) }}</span>
                        <span class="text-gray-500 pt-1 fw-semibold fs-6">Today's Settlement</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Transaction History Table --}}
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                    <input type="text" id="kt_filter_search" class="form-control form-control-solid w-250px ps-13"
                        placeholder="Search..." />
                </div>
            </div>

            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end gap-3">
                    <!--begin::Filter-->
                    <button type="button" class="btn btn-light-primary" id="kt_filter_btn">
                        <i class="ki-outline ki-filter fs-2"></i>Filter
                    </button>
                    <!--begin::Filter Menu-->
                    <div class="menu menu-sub menu-sub-dropdown w-300px w-md-350px" id="kt_filter_menu">
                        <!--begin::Header-->
                        <div class="px-7 py-5">
                            <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                        </div>
                        <!--end::Header-->

                        <!--begin::Separator-->
                        <div class="separator border-gray-200"></div>
                        <!--end::Separator-->

                        <!--begin::Content-->
                        <div class="px-7 py-5">
                            <div class="row">
                                <!--begin::Input group-->
                                <div class="col-12 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Type:</label>
                                    <select class="form-select form-select-solid fw-bold" id="filter_type">
                                        <option value="">All Types</option>
                                        <option value="collection">Collection</option>
                                        <option value="settlement">Settlement</option>
                                        <option value="adjustment">Adjustment</option>
                                    </select>
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="col-6 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Date From:</label>
                                    <input type="text" class="form-control form-control-solid fw-bold flatpickr-input"
                                        id="filter_date_from" placeholder="Select date" readonly />
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="col-6 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Date To:</label>
                                    <input type="text" class="form-control form-control-solid fw-bold flatpickr-input"
                                        id="filter_date_to" placeholder="Select date" readonly />
                                </div>
                                <!--end::Input group-->
                            </div>

                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="button"
                                    class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    id="kt_filter_reset">Reset</button>
                                <button type="button" class="btn btn-primary fw-semibold px-6"
                                    id="kt_filter_apply">Apply</button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Filter Menu-->
                    <!--end::Filter-->

                    <!--begin::Export-->
                    <button type="button" class="btn btn-light-primary" id="kt_export_btn">
                        <i class="ki-outline ki-exit-up fs-2"></i>Export
                    </button>
                    <!--begin::Export Menu-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                        id="kt_export_menu">
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-kt-export="copy">Copy to clipboard</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-kt-export="excel">Export as Excel</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-kt-export="csv">Export as CSV</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-kt-export="pdf">Export as PDF</a>
                        </div>
                        <!--end::Menu item-->
                    </div>
                    <!--end::Export Menu-->
                    <!--end::Export-->

                    <!--begin::Action Buttons-->
                    <button type="button" class="btn btn-light-warning btn-adjustment text-nowrap"
                        data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}"
                        data-balance="{{ $user->current_balance }}">
                        <i class="ki-outline ki-wrench fs-4 me-1"></i> Adjustment
                    </button>

                    @if ($user->current_balance > 0)
                        <button type="button" class="btn btn-primary btn-settle text-nowrap"
                            data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}"
                            data-balance="{{ $user->current_balance }}">
                            <i class="ki-outline ki-dollar fs-4 me-1"></i> Settle Now
                        </button>
                    @endif
                    <!--end::Action Buttons-->
                </div>
                <!--end::Toolbar-->
            </div>
        </div>

        <div class="card-body pt-0">
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table" id="kt_wallet_logs_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-20px">#</th>
                        <th class="w-100px">Date</th>
                        <th class="w-100px">Type</th>
                        <th class="w-250px">Description</th>
                        <th class="w-100px">Amount</th>
                        <th class="w-100px">Old Balance</th>
                        <th class="w-100px">New Balance</th>
                        <th class="w-100px">Created By</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                </tbody>
            </table>
        </div>
    </div>

    {{-- Settlement Modal --}}
    <div class="modal fade" id="kt_modal_settlement" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header pb-0 border-0 justify-content-end">
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>

                <div class="modal-body scroll-y mx-5 mx-xl-10 pt-0 pb-15">
                    <div class="text-center mb-13">
                        <h1 class="mb-3">Record Settlement</h1>
                        <div class="text-muted fw-semibold fs-5">
                            Collect money from <span id="modal_user_name"
                                class="text-primary fw-bold">{{ $user->name }}</span>
                        </div>
                    </div>

                    <form id="kt_modal_settlement_form" class="form" action="{{ route('settlements.store') }}"
                        method="POST">
                        @csrf
                        <input type="hidden" name="user_id" id="settlement_user_id" value="{{ $user->id }}">

                        <div class="d-flex flex-column mb-8">
                            <div class="d-flex flex-stack bg-light-warning rounded p-4 mb-5">
                                <div class="d-flex align-items-center me-2">
                                    <i class="ki-outline ki-wallet fs-2x text-warning me-3"></i>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-700 fw-semibold d-block fs-6">Current Balance</span>
                                        <span class="text-warning fw-bolder fs-2"
                                            id="modal_current_balance">৳{{ number_format($user->current_balance, 0) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column mb-8 fv-row">
                            <label class="fs-6 fw-semibold mb-2 required">
                                Settlement Amount
                            </label>
                            <div class="input-group input-group-solid flex-nowrap">
                                <span class="input-group-text">
                                    <i class="ki-outline ki-dollar fs-3"></i>
                                </span>
                                <input type="number" step="1" min="1" max="{{ $user->current_balance }}"
                                    class="form-control form-control-solid rounded-start-0 border-start"
                                    placeholder="Enter amount" name="amount" id="settlement_amount" required />
                                <button type="button" class="btn btn-light-primary" id="btn_full_amount">Full</button>
                            </div>
                            <div class="fv-plugins-message-container invalid-feedback" id="amount_error"></div>
                        </div>

                        <div class="d-flex flex-column mb-8 fv-row">
                            <label class="fs-6 fw-semibold mb-2">Notes (Optional)</label>
                            <textarea class="form-control form-control-solid" rows="3" name="notes" placeholder="Enter any notes..."></textarea>
                        </div>

                        <div class="text-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="btn_submit_settlement">
                                <span class="indicator-label">Record Settlement</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Adjustment Modal --}}
    <div class="modal fade" id="kt_modal_adjustment" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header pb-0 border-0 justify-content-end">
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>

                <div class="modal-body scroll-y mx-5 mx-xl-10 pt-0 pb-15">
                    <div class="text-center mb-13">
                        <h1 class="mb-3">Balance Adjustment</h1>
                        <div class="text-muted fw-semibold fs-5">
                            Adjust balance for <span id="adj_modal_user_name"
                                class="text-primary fw-bold">{{ $user->name }}</span>
                        </div>
                    </div>

                    <form id="kt_modal_adjustment_form" class="form" action="{{ route('settlements.adjustment') }}"
                        method="POST">
                        @csrf
                        <input type="hidden" name="user_id" id="adjustment_user_id" value="{{ $user->id }}">

                        <div class="d-flex flex-column mb-8">
                            <div class="d-flex flex-stack bg-light-info rounded p-4 mb-5">
                                <div class="d-flex align-items-center me-2">
                                    <i class="ki-outline ki-wallet fs-2x text-info me-3"></i>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-700 fw-semibold d-block fs-6">Current Balance</span>
                                        <span class="text-info fw-bolder fs-2"
                                            id="adj_modal_current_balance">৳{{ number_format($user->current_balance, 0) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column mb-8 fv-row">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">Adjustment Type</span>
                            </label>
                            <div class="d-flex gap-5">
                                <label class="form-check form-check-custom form-check-solid form-check-sm">
                                    <input class="form-check-input" type="radio" name="adjustment_type"
                                        value="increase" checked />
                                    <span class="form-check-label text-success fw-semibold">
                                        <i class="ki-outline ki-arrow-up fs-4 me-1"></i> Increase Balance
                                    </span>
                                </label>
                                <label class="form-check form-check-custom form-check-solid form-check-sm">
                                    <input class="form-check-input" type="radio" name="adjustment_type"
                                        value="decrease" />
                                    <span class="form-check-label text-danger fw-semibold">
                                        <i class="ki-outline ki-arrow-down fs-4 me-1"></i> Decrease Balance
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="d-flex flex-column mb-8 fv-row">
                            <label class="fs-6 fw-semibold mb-2 required">
                                Amount
                            </label>
                            <div class="input-group input-group-solid flex-nowrap">
                                <span class="input-group-text">
                                    <i class="ki-outline ki-dollar fs-3"></i>
                                </span>
                                <input type="number" step="1" min="1"
                                    class="form-control form-control-solid rounded-start-0 border-start"
                                    placeholder="Enter amount" name="amount" id="adjustment_amount" required />
                            </div>
                            <div class="fv-plugins-message-container invalid-feedback" id="adj_amount_error"></div>
                        </div>

                        <div class="d-flex flex-column mb-8 fv-row">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">Reason</span>
                            </label>
                            <textarea class="form-control form-control-solid" rows="3" name="reason" id="adjustment_reason"
                                placeholder="Enter reason for adjustment..." required></textarea>
                            <div class="form-text text-muted">This will be recorded in the audit log.</div>
                        </div>

                        <div class="text-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning" id="btn_submit_adjustment">
                                <span class="indicator-label">Record Adjustment</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
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
        var KT_USER_ID = {{ $user->id }};
        var KT_USER_NAME = "{{ $user->name }}";
        var KT_SHOW_DATA_URL = "{{ route('settlements.show.data', $user) }}";
        var KT_SHOW_EXPORT_URL = "{{ route('settlements.show.export', $user) }}";
        var KT_SETTLEMENT_URL = "{{ route('settlements.store') }}";
        var KT_ADJUSTMENT_URL = "{{ route('settlements.adjustment') }}";
    </script>
    <script src="{{ asset('js/settlements/show.js') }}"></script>
    <script>
        document.getElementById("settlements_menu")?.classList.add("here", "show");
        document.getElementById("settlements_link")?.classList.add("active");
    </script>
@endpush
