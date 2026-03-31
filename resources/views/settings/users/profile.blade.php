@extends('layouts.app')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/settings/users/profile.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('title', 'My Profile')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            My Profile
        </h1>
    </div>
@endsection

@section('content')
    {{-- User Profile Card --}}
    <div class="card mb-5 mb-xl-10">
        <div class="card-body pt-9 pb-0">
            {{-- Profile Header --}}
            <div class="d-flex flex-column flex-sm-row flex-wrap flex-sm-nowrap">
                {{-- Avatar Section --}}
                <div class="me-0 me-sm-7 mb-4 text-center text-sm-start">
                    <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative d-inline-block">
                        <img src="{{ $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png') }}"
                            alt="{{ $user->name }}" class="w-100" />
                        <div
                            class="position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border border-4 border-body h-20px w-20px">
                        </div>
                    </div>
                </div>

                {{-- User Info Section --}}
                <div class="flex-grow-1">
                    {{-- Name, Status & Actions --}}
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-center align-items-md-start mb-2">
                        {{-- Name & Details --}}
                        <div class="d-flex flex-column text-center text-md-start mb-4 mb-md-0">
                            {{-- Name & Status Badge --}}
                            <div class="d-flex flex-column flex-sm-row align-items-center mb-2">
                                <span class="text-gray-900 fs-2 fw-bold me-0 me-sm-2 mb-1 mb-sm-0">
                                    {{ $user->name }}
                                </span>
                            </div>

                            {{-- User Meta Info --}}
                            <div
                                class="d-flex flex-column flex-sm-row flex-wrap justify-content-center justify-content-md-start fw-semibold fs-6 mb-4 pe-2">
                                <span
                                    class="d-flex align-items-center text-gray-500 me-0 me-sm-5 mb-2 justify-content-center justify-content-md-start">
                                    <i class="ki-outline ki-profile-circle fs-4 me-1"></i>
                                    {{ ucfirst($user->roles->first()?->name) ?? 'User' }}
                                </span>
                                @if (!auth()->user()->isAdmin())
                                    <span
                                        class="d-flex align-items-center text-gray-500 me-0 me-sm-5 mb-2 justify-content-center justify-content-md-start">
                                        <i class="ki-outline ki-geolocation fs-4 me-1"></i>
                                        {{ $user->branch->branch_name }} Branch
                                    </span>
                                @endif
                                <span
                                    class="d-flex align-items-center text-gray-500 me-0 me-sm-5 mb-2 justify-content-center justify-content-md-start text-break">
                                    <i class="ki-outline ki-sms fs-4 me-1"></i>
                                    {{ $user->email }}
                                </span>
                                <span
                                    class="d-flex align-items-center text-gray-500 mb-2 justify-content-center justify-content-md-start">
                                    <i class="ki-outline ki-phone fs-4 me-1"></i>
                                    {{ $user->mobile_number ?? 'N/A' }}
                                </span>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="d-flex flex-column flex-sm-row gap-2 my-2 my-md-4">
                            @if (auth()->user()->isAdmin())
                                <button type="button" class="btn btn-sm btn-light-primary" id="btn_edit_profile">
                                    <i class="ki-outline ki-pencil fs-4 me-1"></i>Edit Profile
                                </button>
                            @else
                                <button type="button" class="btn btn-sm btn-light-primary" id="btn_change_photo">
                                    <i class="ki-outline ki-picture fs-4 me-1"></i>Change Photo
                                </button>
                            @endif
                            <button type="button" class="btn btn-sm btn-light-warning" id="btn_change_password">
                                <i class="ki-outline ki-lock fs-4 me-1"></i>Change Password
                            </button>
                        </div>
                    </div>

                    {{-- Stats Section --}}
                    <div class="d-flex flex-wrap flex-stack">
                        <div class="d-flex flex-column flex-grow-1 pe-0 pe-md-8">
                            <div class="row g-3">
                                {{-- Total Collected --}}
                                <div class="col-6 col-sm-6 col-md-3">
                                    <div class="border border-gray-300 border-dashed rounded py-3 px-4 h-100">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-outline ki-arrow-up fs-3 text-success me-2"></i>
                                            <div class="fs-4 fs-md-2 fw-bold text-success">
                                                ৳ {{ number_format($user->total_collected ?? 0) }}
                                            </div>
                                        </div>
                                        <div class="fw-semibold fs-7 fs-md-6 text-gray-500">Lifetime Collection</div>
                                    </div>
                                </div>

                                {{-- Total Settled --}}
                                <div class="col-6 col-sm-6 col-md-3">
                                    <div class="border border-gray-300 border-dashed rounded py-3 px-4 h-100">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-outline ki-arrow-down fs-3 text-info me-2"></i>
                                            <div class="fs-4 fs-md-2 fw-bold text-info">
                                                ৳ {{ number_format($user->total_settled ?? 0) }}
                                            </div>
                                        </div>
                                        <div class="fw-semibold fs-7 fs-md-6 text-gray-500">Lifetime Settled</div>
                                    </div>
                                </div>

                                {{-- Current Balance --}}
                                <div class="col-6 col-sm-6 col-md-3">
                                    <div class="border border-gray-300 border-dashed rounded py-3 px-4 h-100">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-outline ki-wallet fs-3 text-warning me-2"></i>
                                            <div class="fs-4 fs-md-2 fw-bold text-warning"
                                                data-wallet-balance="{{ $user->current_balance ?? 0 }}">
                                                ৳ {{ number_format($user->current_balance ?? 0) }}
                                            </div>
                                        </div>
                                        <div class="fw-semibold fs-7 fs-md-6 text-gray-500">Current Balance</div>
                                    </div>
                                </div>

                                {{-- Today's Collection --}}
                                <div class="col-6 col-sm-6 col-md-3">
                                    <div class="border border-gray-300 border-dashed rounded py-3 px-4 h-100">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-outline ki-calendar-tick fs-3 text-primary me-2"></i>
                                            <div class="fs-4 fs-md-2 fw-bold text-primary">
                                                ৳ {{ number_format($todayCollection ?? 0) }}
                                            </div>
                                        </div>
                                        <div class="fw-semibold fs-7 fs-md-6 text-gray-500">Today's Collection</div>
                                    </div>
                                </div>

                                {{-- Member Since --}}
                                <div class="col-6 col-sm-6 col-md-3">
                                    <div class="border border-gray-300 border-dashed rounded py-3 px-4 h-100">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-outline ki-calendar fs-3 text-primary me-2"></i>
                                            <div class="fs-4 fs-md-2 fw-bold text-primary">
                                                {{ $user->created_at?->format('d M, Y') }}
                                            </div>
                                        </div>
                                        <div class="fw-semibold fs-7 fs-md-6 text-gray-500">Member Since</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs Navigation --}}
            <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold mt-5">
                <li class="nav-item mt-2">
                    <a class="nav-link text-active-primary ms-0 me-5 me-md-10 py-5 active" data-bs-toggle="tab"
                        href="#kt_tab_wallet_logs">
                        <i class="ki-outline ki-wallet fs-4 me-2"></i><span class="d-none d-sm-inline">Wallet
                            Logs</span><span class="d-sm-none">Wallet</span>
                    </a>
                </li>
                <li class="nav-item mt-2">
                    <a class="nav-link text-active-primary ms-0 me-5 me-md-10 py-5" data-bs-toggle="tab"
                        href="#kt_tab_login_activity">
                        <i class="ki-outline ki-shield-tick fs-4 me-2"></i><span class="d-none d-sm-inline">Login
                            Activity</span><span class="d-sm-none">Logins</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- Tab Content --}}
    <div class="tab-content">
        {{-- Wallet Logs Tab --}}
        <div class="tab-pane fade show active" id="kt_tab_wallet_logs" role="tabpanel">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" data-kt-wallet-table-filter="search"
                                class="form-control form-control-solid w-250px ps-13"
                                placeholder="Search wallet logs..." />
                        </div>
                        <!--end::Search-->
                    </div>
                    <!--end::Card title-->

                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Toolbar-->
                        <div class="d-flex justify-content-end gap-3" data-kt-wallet-table-toolbar="base">
                            <!--begin::Filter-->
                            <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                                data-kt-menu-placement="bottom-end">
                                <i class="ki-outline ki-filter fs-2"></i>Filter
                            </button>
                            <!--begin::Menu-->
                            <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true"
                                id="kt_wallet_filter_menu">
                                <!--begin::Header-->
                                <div class="px-7 py-5">
                                    <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                                </div>
                                <!--end::Header-->
                                <!--begin::Separator-->
                                <div class="separator border-gray-200"></div>
                                <!--end::Separator-->
                                <!--begin::Content-->
                                <div class="px-7 py-5" data-kt-wallet-table-filter="form">
                                    <!--begin::Input group-->
                                    <div class="mb-5">
                                        <label class="form-label fs-6 fw-semibold">Type:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="All Types" data-allow-clear="true"
                                            data-kt-wallet-table-filter="type" data-hide-search="true">
                                            <option></option>
                                            <option value="collection">Collection</option>
                                            <option value="settlement">Settlement</option>
                                            <option value="adjustment">Adjustment</option>
                                        </select>
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Input group-->
                                    <div class="row mb-5">
                                        <div class="col-6">
                                            <label class="form-label fs-6 fw-semibold">Date From:</label>
                                            <input type="text"
                                                class="form-control form-control-solid fw-bold flatpickr-input"
                                                id="wallet_date_from" placeholder="Select date" readonly />
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fs-6 fw-semibold">Date To:</label>
                                            <input type="text"
                                                class="form-control form-control-solid fw-bold flatpickr-input"
                                                id="wallet_date_to" placeholder="Select date" readonly />
                                        </div>
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Actions-->
                                    <div class="d-flex justify-content-end">
                                        <button type="reset"
                                            class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                            data-kt-wallet-table-filter="reset">Reset</button>
                                        <button type="submit" class="btn btn-primary fw-semibold px-6"
                                            data-kt-wallet-table-filter="filter">Apply</button>
                                    </div>
                                    <!--end::Actions-->
                                </div>
                                <!--end::Content-->
                            </div>
                            <!--end::Menu-->
                            <!--end::Filter-->

                            @can('cost-records.create')
                                <!--begin::Add Student-->
                                <a href="#" class="btn btn-primary" id="add_cost_btn">
                                    <i class="ki-outline ki-plus fs-2"></i>Add Cost</a>
                                <!--end::Add Student-->
                            @endcan
                        </div>
                        <!--end::Toolbar-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <div class="card-body py-4">
                    <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                        id="kt_wallet_logs_table">
                        <thead>
                            <tr class="fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-20px">#</th>
                                <th class="w-100px">Date</th>
                                <th class="w-100px">Type</th>
                                <th class="w-250px">Description</th>
                                <th class="w-100px text-end">Amount</th>
                                <th class="w-100px text-end">Old Balance</th>
                                <th class="w-100px text-end">New Balance</th>
                                <th class="w-100px">Created By</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600">
                            {{-- Data loaded via AJAX --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Login Activity Tab --}}
        <div class="tab-pane fade" id="kt_tab_login_activity" role="tabpanel">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" data-kt-login-table-filter="search"
                                class="form-control form-control-solid w-250px ps-13"
                                placeholder="Search login activities..." />
                        </div>
                        <!--end::Search-->
                    </div>
                    <!--end::Card title-->

                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Toolbar-->
                        <div class="d-flex justify-content-end gap-3" data-kt-login-table-toolbar="base">
                            <!--begin::Filter-->
                            <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                                data-kt-menu-placement="bottom-end">
                                <i class="ki-outline ki-filter fs-2"></i>Filter
                            </button>
                            <!--begin::Menu-->
                            <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true"
                                id="kt_login_filter_menu">
                                <!--begin::Header-->
                                <div class="px-7 py-5">
                                    <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                                </div>
                                <!--end::Header-->
                                <!--begin::Separator-->
                                <div class="separator border-gray-200"></div>
                                <!--end::Separator-->
                                <!--begin::Content-->
                                <div class="px-7 py-5" data-kt-login-table-filter="form">
                                    <!--begin::Input group-->
                                    <div class="mb-5">
                                        <label class="form-label fs-6 fw-semibold">Device:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="All Devices" data-allow-clear="true"
                                            data-kt-login-table-filter="device" data-hide-search="true">
                                            <option></option>
                                            <option value="Desktop">Desktop</option>
                                            <option value="Mobile">Mobile</option>
                                        </select>
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Input group-->
                                    <div class="row mb-5">
                                        <div class="col-6">
                                            <label class="form-label fs-6 fw-semibold">Date From:</label>
                                            <input type="text"
                                                class="form-control form-control-solid fw-bold flatpickr-input"
                                                id="login_date_from" placeholder="Select date" readonly />
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fs-6 fw-semibold">Date To:</label>
                                            <input type="text"
                                                class="form-control form-control-solid fw-bold flatpickr-input"
                                                id="login_date_to" placeholder="Select date" readonly />
                                        </div>
                                    </div>
                                    <!--end::Input group-->
                                    <!--begin::Actions-->
                                    <div class="d-flex justify-content-end">
                                        <button type="reset"
                                            class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                            data-kt-login-table-filter="reset">Reset</button>
                                        <button type="submit" class="btn btn-primary fw-semibold px-6"
                                            data-kt-login-table-filter="filter">Apply</button>
                                    </div>
                                    <!--end::Actions-->
                                </div>
                                <!--end::Content-->
                            </div>
                            <!--end::Menu-->
                            <!--end::Filter-->
                        </div>
                        <!--end::Toolbar-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <div class="card-body py-4">
                    <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                        id="kt_login_activities_table">
                        <thead>
                            <tr class="fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-25px">#</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                                <th>Device</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            {{-- Data loaded via AJAX --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Password Update Modal --}}
    <div class="modal fade" id="kt_modal_password" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
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
                        <h1 class="mb-3">Change Password</h1>
                        <div class="text-muted fw-semibold fs-5">
                            Update your account password
                        </div>
                    </div>

                    <form id="kt_modal_password_form" class="form"
                        action="{{ route('users.password.reset', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- New Password --}}
                        <div class="fv-row mb-8">
                            <label class="required fw-semibold fs-6 mb-2">New Password</label>
                            <div class="position-relative">
                                <input type="password" name="new_password" id="modal_password_new"
                                    class="form-control form-control-solid" placeholder="Enter new password"
                                    autocomplete="new-password" />
                                <span
                                    class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2 toggle-password"
                                    data-target="modal_password_new">
                                    <i class="ki-outline ki-eye fs-3"></i>
                                </span>
                            </div>
                        </div>

                        {{-- Confirm Password --}}
                        <div class="fv-row mb-8">
                            <label class="required fw-semibold fs-6 mb-2">Confirm Password</label>
                            <div class="position-relative">
                                <input type="password" name="new_password_confirmation" id="modal_password_confirm"
                                    class="form-control form-control-solid" placeholder="Confirm new password"
                                    autocomplete="new-password" />
                                <span
                                    class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2 toggle-password"
                                    data-target="modal_password_confirm">
                                    <i class="ki-outline ki-eye fs-3"></i>
                                </span>
                            </div>
                        </div>

                        {{-- Password Strength Meter --}}
                        <div class="mb-8">
                            <div class="fs-6 fw-semibold text-muted mb-2">Password Strength</div>
                            <div id="modal_password_strength_text" class="fw-bold fs-5 mb-2"></div>
                            <div class="progress h-8px">
                                <div id="modal_password_strength_bar" class="progress-bar" role="progressbar"
                                    style="width: 0%;"></div>
                            </div>
                            <div class="text-muted fs-7 mt-2">
                                At least 8 characters, one uppercase, one lowercase, one number, and one special
                                character.
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning" id="btn_submit_password" disabled>
                                <span class="indicator-label">Update Password</span>
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

    {{-- Photo Upload Modal (Non-Admin Only) --}}
    @if (!auth()->user()->isAdmin())
        <div class="modal fade" id="kt_modal_photo" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
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
                            <h1 class="mb-3">Change Profile Photo</h1>
                            <div class="text-muted fw-semibold fs-5">
                                Upload a new profile picture
                            </div>
                        </div>

                        <form id="kt_modal_photo_form" class="form" action="{{ route('users.profile.update') }}"
                            method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            {{-- Photo Upload --}}
                            <div class="fv-row mb-8">
                                <div class="d-flex flex-center">
                                    <div class="image-input image-input-outline image-input-placeholder"
                                        data-kt-image-input="true" id="kt_photo_upload">
                                        <div class="image-input-wrapper w-150px h-150px"
                                            style="background-image: url('{{ $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png') }}')">
                                        </div>
                                        <label
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                            title="Change photo">
                                            <i class="ki-outline ki-pencil fs-7"></i>
                                            <input type="file" name="photo" accept=".png, .jpg, .jpeg"
                                                id="photo_input" />
                                            <input type="hidden" name="photo_remove" id="photo_remove"
                                                value="0" />
                                        </label>
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                            title="Cancel photo">
                                            <i class="ki-outline ki-cross fs-2"></i>
                                        </span>
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                            title="Remove photo">
                                            <i class="ki-outline ki-cross fs-2"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="form-text text-center mt-3">Allowed: png, jpg, jpeg. Max 100KB.</div>
                            </div>

                            <div class="text-center">
                                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="btn_submit_photo">
                                    <span class="indicator-label">Save Photo</span>
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
    @endif

    {{-- Profile Update Modal (Admin Only) --}}
    @if (auth()->user()->isAdmin())
        <div class="modal fade" id="kt_modal_profile" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
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
                            <h1 class="mb-3">Edit Profile</h1>
                            <div class="text-muted fw-semibold fs-5">
                                Update your personal information
                            </div>
                        </div>

                        <form id="kt_modal_profile_form" class="form" action="{{ route('users.profile.update') }}"
                            method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            {{-- Photo Upload --}}
                            <div class="fv-row mb-8">
                                <label class="fs-6 fw-semibold mb-4 d-block">Profile Photo</label>
                                <div class="image-input image-input-outline image-input-placeholder"
                                    data-kt-image-input="true" id="kt_profile_photo_upload">
                                    <div class="image-input-wrapper w-125px h-125px"
                                        style="background-image: url('{{ $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png') }}')">
                                    </div>
                                    <label
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                        title="Change photo">
                                        <i class="ki-outline ki-pencil fs-7"></i>
                                        <input type="file" name="photo" accept=".png, .jpg, .jpeg"
                                            id="profile_photo_input" />
                                        <input type="hidden" name="photo_remove" id="profile_photo_remove"
                                            value="0" />
                                    </label>
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                        title="Cancel photo">
                                        <i class="ki-outline ki-cross fs-2"></i>
                                    </span>
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                        title="Remove photo">
                                        <i class="ki-outline ki-cross fs-2"></i>
                                    </span>
                                </div>
                                <div class="form-text">Allowed: png, jpg, jpeg. Max 100KB.</div>
                            </div>

                            <div class="row g-9 mb-8">
                                {{-- Name --}}
                                <div class="col-md-12 fv-row">
                                    <label class="required fs-6 fw-semibold mb-2">Full Name</label>
                                    <input type="text" name="name" id="profile_name" value="{{ $user->name }}"
                                        class="form-control form-control-solid" placeholder="Enter full name" />
                                    <div class="fv-plugins-message-container invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row g-9 mb-8">
                                {{-- Email --}}
                                <div class="col-md-6 fv-row">
                                    <label class="required fs-6 fw-semibold mb-2">Email</label>
                                    <input type="email" name="email" id="profile_email" value="{{ $user->email }}"
                                        class="form-control form-control-solid" placeholder="Enter email" />
                                    <div class="fv-plugins-message-container invalid-feedback"></div>
                                </div>

                                {{-- Mobile Number --}}
                                <div class="col-md-6 fv-row">
                                    <label class="required fs-6 fw-semibold mb-2">Mobile Number</label>
                                    <input type="text" name="mobile_number" id="profile_mobile"
                                        value="{{ $user->mobile_number }}" class="form-control form-control-solid"
                                        placeholder="01XXXXXXXXX" maxlength="11" />
                                    <div class="fv-plugins-message-container invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="text-center">
                                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="btn_submit_profile">
                                    <span class="indicator-label">Save Changes</span>
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
    @endif

    <!--begin::Add Cost Modal-->
    <div class="modal fade" id="cost_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-700px">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="cost_modal_title" class="modal-title fw-bold">Add Today's Cost</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>
                <form id="cost_form">
                    <div class="modal-body py-10 px-lg-12" style="max-height: 70vh; overflow-y: auto;">
                        <!--begin::Branch Selection-->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Branch</label>
                            @if ($isAdmin)
                                <select id="cost_branch_id" name="branch_id" class="form-select form-select-solid"
                                    data-control="select2" data-placeholder="Select branch"
                                    data-dropdown-parent="#cost_modal" data-hide-search="true">
                                    <option value="">-- Select Branch --</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">
                                            {{ $branch->branch_name }} ({{ $branch->branch_prefix }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control form-control-solid bg-secondary"
                                    value="{{ $branches->first()->branch_name ?? '' }} ({{ $branches->first()->branch_prefix ?? '' }})"
                                    readonly disabled>
                                <input type="hidden" id="cost_branch_id" name="branch_id"
                                    value="{{ $branches->first()->id ?? '' }}">
                            @endif
                        </div>
                        <!--end::Branch Selection-->

                        <!--begin::Date-->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Date</label>
                            <input type="text" id="cost_date" name="cost_date"
                                class="form-control form-control-solid bg-secondary" value="{{ now()->format('d-m-Y') }}"
                                readonly>
                            <div class="form-text text-muted">
                                <i class="ki-outline ki-information-3 fs-7 me-1"></i>
                                Cost can only be added for today's date
                            </div>
                        </div>
                        <!--end::Date-->

                        <!--begin::Cost Types Tagify-->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Cost Types</label>
                            <input type="text" id="cost_types_tagify" class="form-control form-control-solid"
                                placeholder="Select cost types...">
                            <div class="form-text text-muted">Select one or more cost types</div>
                        </div>
                        <!--end::Cost Types Tagify-->

                        <!--begin::Cost Entries Container-->
                        <div id="cost_entries_container" class="mb-5">
                            <label class="fw-semibold fs-6 mb-3">Cost Entries</label>
                            <div id="cost_entries_list">
                                <div class="text-center text-muted py-5">
                                    <i class="ki-outline ki-information fs-3x text-gray-400 mb-3"></i>
                                    <p class="mb-0">Select cost types above to add entries</p>
                                </div>
                            </div>
                        </div>
                        <!--end::Cost Entries Container-->

                        <!--begin::Other Costs Section-->
                        <div class="fv-row mb-5">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="fw-semibold fs-6">Other Costs</label>
                                <button type="button" class="btn btn-sm btn-light-primary" id="add_other_cost_btn">
                                    <i class="ki-outline ki-plus fs-6"></i> Add Other
                                </button>
                            </div>
                            <div id="other_costs_container"></div>
                            <div class="form-text text-muted">
                                <i class="ki-outline ki-information-3 fs-7 me-1"></i>
                                Add custom cost types not in the predefined list
                            </div>
                        </div>
                        <!--end::Other Costs Section-->

                        <!--begin::Total-->
                        <div id="cost_total_section" class="cost-total-section d-none">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="total-label">Total Cost</span>
                                <span id="cost_total_amount" class="total-amount">৳0</span>
                            </div>
                        </div>
                        <!--end::Total-->
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="save_cost_btn">
                            <span class="indicator-label">
                                <i class="ki-outline ki-check fs-4 me-1"></i> Save Cost
                            </span>
                            <span class="indicator-progress">
                                Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--end::Add Cost Modal-->
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        // Pass data to JS
        var ProfileConfig = {
            userId: {{ $user->id }},
            userName: "{{ $user->name }}",
            userEmail: "{{ $user->email }}",
            userMobile: "{{ $user->mobile_number }}",
            userPhotoUrl: "{{ $user->photo_url ? asset($user->photo_url) : asset('img/male-placeholder.png') }}",
            placeholderUrl: "{{ asset('img/male-placeholder.png') }}",
            passwordResetUrl: "{{ route('users.password.reset', $user->id) }}",
            profileUpdateUrl: "{{ route('users.profile.update') }}",
            walletLogsUrl: "{{ route('users.profile.wallet-logs') }}",
            loginActivitiesUrl: "{{ route('users.profile.login-activities') }}",
            isAdmin: {{ auth()->user()->isAdmin() ? 'true' : 'false' }}
        };

        window.CostRecordsConfig = {
            isAdmin: @json($isAdmin),
            userBranchId: @json(auth()->user()->branch_id),
            todayDate: "{{ now()->format('d-m-Y') }}",
            hasBranchTabs: @json($isAdmin && $branches->count() > 1),
            branches: @json($branches->map(fn($b) => ['id' => $b->id, 'name' => $b->branch_name, 'prefix' => $b->branch_prefix])),
            costTypes: @json($costTypes->map(fn($ct) => ['id' => $ct->id, 'name' => $ct->name, 'description' => $ct->description])),
            routes: {
                costsData: "{{ route('reports.cost-records.data') }}",
                exportCosts: "{{ route('reports.cost-records.export') }}",
                costTypes: "{{ route('costs.types') }}",
                storeCost: "{{ route('costs.store') }}",
                checkTodayCost: "{{ route('costs.check-today') }}",
                costSummary: "{{ route('reports.cost-summary') }}",
                @if ($isAdmin)
                    showCost: "{{ route('costs.show', ':id') }}",
                    updateCost: "{{ route('costs.update', ':id') }}",
                    deleteCost: "{{ route('costs.destroy', ':id') }}"
                @endif
            },
            csrfToken: "{{ csrf_token() }}"
        };
    </script>
    <script src="{{ asset('js/settings/users/profile.js') }}"></script>
@endpush
