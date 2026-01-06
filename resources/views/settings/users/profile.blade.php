@extends('layouts.app')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        /* ================= PROFILE CARD MOBILE RESPONSIVENESS ================= */

        /* Stats cards - ensure consistent sizing */
        @media (max-width: 575.98px) {
            .fs-md-2 {
                font-size: 1.1rem !important;
            }

            .fs-md-6 {
                font-size: 0.85rem !important;
            }
        }

        @media (min-width: 576px) {
            .fs-md-2 {
                font-size: 1.5rem !important;
            }

            .fs-md-6 {
                font-size: 1rem !important;
            }
        }

        /* Email text break on mobile */
        @media (max-width: 575.98px) {
            .text-break {
                word-break: break-all;
            }
        }

        /* Card header flex layout */
        @media (max-width: 767.98px) {
            .card-header.flex-column {
                align-items: stretch !important;
            }

            .w-md-auto {
                width: 100% !important;
            }

            .w-md-300px {
                width: 100% !important;
            }

            .w-md-200px {
                width: 100% !important;
            }
        }

        @media (min-width: 768px) {
            .w-md-auto {
                width: auto !important;
            }

            .w-md-300px {
                width: 300px !important;
            }

            .w-md-200px {
                width: 200px !important;
            }

            .card-header.flex-md-row {
                flex-direction: row !important;
            }
        }

        /* DataTables responsive improvements */
        @media (max-width: 767.98px) {

            #kt_wallet_logs_table,
            #kt_login_activities_table {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
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
                                <span
                                    class="text-gray-900 fs-2 fw-bold me-0 me-sm-2 mb-1 mb-sm-0">{{ $user->name }}</span>
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
                                                ৳{{ number_format($user->total_collected ?? 0, 2) }}
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
                                                ৳{{ number_format($user->total_settled ?? 0, 2) }}
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
                                                ৳{{ number_format($user->current_balance ?? 0, 2) }}
                                            </div>
                                        </div>
                                        <div class="fw-semibold fs-7 fs-md-6 text-gray-500">Current Balance</div>
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
                <div class="card-header border-0 pt-6 flex-column flex-md-row">
                    <div class="card-title w-100 w-md-auto mb-4 mb-md-0">
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" data-kt-filter="wallet-search"
                                class="form-control form-control-solid w-100 w-md-300px ps-13"
                                placeholder="Search wallet logs..." />
                        </div>
                    </div>
                    <div class="card-toolbar w-100 w-md-auto">
                        <div class="d-flex align-items-center gap-3 w-100 w-md-auto">
                            {{-- Type Filter --}}
                            <select class="form-select form-select-solid w-100 w-md-200px" data-kt-filter="wallet-type"
                                data-control="select2" data-placeholder="All Types" data-allow-clear="true"
                                data-hide-search="true">
                                <option></option>
                                <option value="collection">Collection</option>
                                <option value="settlement">Settlement</option>
                                <option value="adjustment">Adjustment</option>
                            </select>
                        </div>
                    </div>
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
                                <th class="w-100px">Amount</th>
                                <th class="w-100px">Old Balance</th>
                                <th class="w-100px">New Balance</th>
                                <th class="w-100px">Created By</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600">
                            @forelse($walletLogs as $log)
                                <tr data-type="{{ $log->type }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td data-order="{{ $log->created_at->timestamp }}">
                                        <span class="text-gray-800">{{ $log->created_at->format('d M, Y') }}</span>
                                        <span
                                            class="text-gray-500 d-block fs-7">{{ $log->created_at->format('h:i A') }}</span>
                                    </td>
                                    <td>
                                        @if ($log->type === 'collection')
                                            <span class="badge badge-light-success">Collection</span>
                                        @elseif($log->type === 'settlement')
                                            <span class="badge badge-light-info">Settlement</span>
                                        @else
                                            <span class="badge badge-light-warning">Adjustment</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($log->paymentTransaction)
                                            <a href="{{ route('invoices.show', $log->paymentTransaction->payment_invoice_id) }}"
                                                class="text-gray-800 text-hover-primary text-wrap" target="_blank">
                                                {{ $log->description }}
                                            </a>
                                        @else
                                            <span class="text-gray-800">{{ $log->description ?? '-' }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end" data-order="{{ $log->amount }}">
                                        @if ($log->amount >= 0)
                                            <span class="text-success fw-bold">৳
                                                +{{ number_format($log->amount, 2) }}</span>
                                        @else
                                            <span class="text-danger fw-bold">৳
                                                {{ number_format($log->amount, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="text-gray-600">৳{{ number_format($log->old_balance, 2) }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span
                                            class="text-gray-800 fw-bold">৳{{ number_format($log->new_balance, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-gray-700">{{ $log->creator->name ?? 'System' }}</span>
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Login Activity Tab --}}
        <div class="tab-pane fade" id="kt_tab_login_activity" role="tabpanel">
            <div class="card">
                <div class="card-header border-0 pt-6">
                    <div class="card-title w-100 w-md-auto">
                        <div class="d-flex align-items-center position-relative my-1 w-100">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" data-kt-filter="login-search"
                                class="form-control form-control-solid w-100 w-md-300px ps-13"
                                placeholder="Search login activities..." />
                        </div>
                    </div>
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
                            @foreach ($loginActivities as $activity)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $activity->ip_address }}
                                    </td>
                                    <td>
                                        <span class="text-gray-800">
                                            {{ $activity->user_agent }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-info">{{ $activity->device ?? 'Unknown' }}</span>
                                    </td>
                                    <td data-order="{{ $activity->created_at->timestamp }}">
                                        <span class="text-gray-800">{{ $activity->created_at->diffForHumans() }}</span>
                                        <span
                                            class="text-gray-500 d-block fs-7">{{ $activity->created_at->format('d M, Y h:i A') }}</span>
                                    </td>
                                </tr>
                            @endforeach
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
                                At least 8 characters, one uppercase, one lowercase, one number, and one special character.
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
                            method="POST">
                            @csrf
                            @method('PUT')

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
            passwordResetUrl: "{{ route('users.password.reset', $user->id) }}",
            profileUpdateUrl: "{{ route('users.profile.update') }}",
            isAdmin: {{ auth()->user()->isAdmin() ? 'true' : 'false' }}
        };
    </script>
    <script src="{{ asset('js/settings/users/profile.js') }}"></script>
@endpush
