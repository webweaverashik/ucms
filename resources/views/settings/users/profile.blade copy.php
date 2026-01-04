@extends('layouts.app')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        /* ================= READONLY SECTION (LIGHT MODE) ================= */
        .readonly-section {
            border: 1px dashed #cfd3e1;
            border-radius: 8px;
            padding: 16px;
            background-color: #f9fafb;
            transition: all .2s ease;
            cursor: help;
        }

        .readonly-section:hover {
            background-color: #f1f4f8;
            border-color: #b5b9cc;
        }

        .readonly-value {
            font-size: 1rem;
            color: #3f4254;
            font-weight: 500;
        }

        /* ================= DARK MODE SUPPORT ================= */
        [data-bs-theme="dark"] .readonly-section {
            background-color: #1e1e2d;
            border-color: #323248;
        }

        [data-bs-theme="dark"] .readonly-section:hover {
            background-color: #2b2b40;
            border-color: #474761;
        }

        [data-bs-theme="dark"] .readonly-value {
            color: #e1e1ef;
        }

        [data-bs-theme="dark"] .readonly-section .form-label {
            color: #a1a5b7 !important;
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

        <span class="h-20px border-gray-300 border-start mx-4"></span>

        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">User Management</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Profile</li>
        </ul>
    </div>
@endsection

@section('content')
    <div class="row g-7">
        <!-- ================= LEFT: PROFILE INFO ================= -->
        <div class="col-lg-8">
            @if (auth()->user()->isAdmin())
                <form id="kt_create_user_form" class="form" novalidate
                    data-update-url="{{ route('users.profile.update') }}">
                    @csrf

                    <div class="card card-flush py-4">
                        <div class="card-header">
                            <div class="card-title">
                                <h3>Personal Information</h3>
                            </div>
                        </div>

                        <div class="card-body pt-0">
                            <div class="row g-6">

                                <!-- Inside the personal info card-body -->

                                <!-- Name -->
                                <div class="col-md-6 fv-row">
                                    <label class="form-label required">User Name</label>
                                    <input type="text" name="name" value="{{ auth()->user()->name }}"
                                        class="form-control form-control-solid" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Email -->
                                <div class="col-md-6 fv-row">
                                    <label class="form-label required">Email</label>
                                    <input type="email" name="email" value="{{ auth()->user()->email }}"
                                        class="form-control form-control-solid" required>
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- Mobile -->
                                <div class="col-md-6 fv-row">
                                    <label class="form-label required">Mobile</label>
                                    <input type="text" name="mobile_number" value="{{ auth()->user()->mobile_number }}"
                                        class="form-control form-control-solid" required maxlength="11">
                                    <div class="invalid-feedback"></div>
                                </div>

                                <!-- ================= READ ONLY SECTION ================= -->
                                <div class="col-12">
                                    <div class="readonly-section" data-bs-toggle="tooltip" data-bs-placement="top"
                                        title="These fields are read-only and cannot be changed.">
                                        <div class="row g-6">
                                            <div class="col-md-4">
                                                <label class="form-label text-muted">Role</label>
                                                <div class="readonly-value">
                                                    {{ ucfirst(auth()->user()->getRoleNames()->first()) }}
                                                </div>
                                            </div>



                                            <div class="col-md-4">
                                                <label class="form-label text-muted">Branch</label>
                                                <div class="readonly-value">
                                                    {{ auth()->user()->branch ? auth()->user()->branch->branch_name : '-' }}
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <label class="form-label text-muted">Created At</label>
                                                <div class="readonly-value">
                                                    {{ auth()->user()->created_at->format('d-M-Y') }}
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <!-- ================= END READ ONLY SECTION ================= -->

                            </div>
                        </div>

                        <div class="card-footer d-flex justify-content-end gap-3">
                            <button type="submit" class="btn btn-primary w-150px">
                                <span class="indicator-label">Update</span>
                                <span class="indicator-progress">
                                    Please wait...
                                    <span class="spinner-border spinner-border-sm ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            @else
                <div class="card card-flush py-4">
                    <div class="card-header">
                        <div class="card-title">
                            <h3>Personal Information</h3>
                        </div>
                    </div>

                    <div class="card-body pt-0">
                        <div class="row g-6">
                            <!-- ================= READ ONLY USER INFORMATION ================= -->
                            <div class="col-12">
                                <div class="readonly-section" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="These fields are read-only and cannot be changed.">

                                    <div class="row g-6">

                                        <!-- User Name -->
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">User Name</label>
                                            <div class="readonly-value">
                                                {{ auth()->user()->name }}
                                            </div>
                                        </div>

                                        <!-- Email -->
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">Email</label>
                                            <div class="readonly-value">
                                                {{ auth()->user()->email }}
                                            </div>
                                        </div>

                                        <!-- Mobile -->
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">Mobile</label>
                                            <div class="readonly-value">
                                                {{ auth()->user()->mobile_number ?? '-' }}
                                            </div>
                                        </div>

                                        <!-- Role -->
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">Role</label>
                                            <div class="readonly-value">
                                                <span
                                                    class="badge badge-info">{{ ucfirst(auth()->user()->getRoleNames()->first()) }}</span>
                                            </div>
                                        </div>

                                        <!-- Branch -->
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">Branch</label>
                                            <div class="readonly-value">
                                                <span
                                                    class="badge badge-light-success">{{ auth()->user()->branch?->branch_name ?? '-' }}</span>

                                            </div>
                                        </div>

                                        <!-- Created At -->
                                        <div class="col-md-6">
                                            <label class="form-label text-muted">Created At</label>
                                            <div class="readonly-value">
                                                {{ auth()->user()->created_at?->format('d-M-Y') }}
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <!-- ================= END READ ONLY USER INFORMATION ================= -->

                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- ================= RIGHT: PASSWORD RESET ================= -->
        <div class="col-lg-4">
            <div class="card card-flush py-4">
                <div class="card-header">
                    <div class="card-title">
                        <h3>Reset Password</h3>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <!-- New Password -->
                    <div class="fv-row mb-6">
                        <label class="required fw-semibold fs-6 mb-2">New Password</label>
                        <div class="input-group">
                            <input type="password" name="password_new" class="form-control mb-3 mb-lg-0"
                                placeholder="Write New Password" required id="userPasswordNew" autocomplete="off" />
                            <span class="input-group-text toggle-password" data-target="userPasswordNew"
                                style="cursor: pointer;">
                                <i class="ki-outline ki-eye
                                fs-3"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="fv-row mb-6">
                        <label class="required fw-semibold fs-6 mb-2">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" name="password_confirm" class="form-control mb-3 mb-lg-0"
                                placeholder="Write the password again" required id="userConfirmPassword"
                                autocomplete="off" />
                            <span class="input-group-text toggle-password" data-target="userConfirmPassword"
                                style="cursor: pointer;">
                                <i class="ki-outline ki-eye
                                fs-3"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Password Strength Meter -->
                    <div class="mb-6">
                        <div class="fs-6 fw-semibold text-muted mb-2">Password Strength</div>
                        <div id="password-strength-text" class="fw-bold fs-5 mb-2"></div>
                        <div class="progress h-8px">
                            <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%;">
                            </div>
                        </div>
                        <div class="text-muted fs-7 mt-2">
                            At least 8 characters, one uppercase letter, one lowercase letter, one number and one special
                            character
                            are required.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-warning w-150px" id="password_update_btn"
                            data-url="{{ route('users.password.reset', $user->id) }}">
                            <span class="indicator-label">Update</span>
                            <span class="indicator-progress" style="display:none;">
                                Please wait...
                                <span class="spinner-border spinner-border-sm ms-2"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= BOTTOM: LOGIN HISTORY ================= -->
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <h3 class="mb-0">My Wallet Logs</h3>

                        <div class="d-flex align-items-center position-relative">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" data-wallet-logs-table-filter="search"
                                class="form-control form-control-solid w-200px w-lg-350px ps-13"
                                placeholder="Search in logs" />
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
                                <th class="w-150px">User</th>
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
                                <tr data-type="{{ $log->type }}" data-user="{{ $log->user->name ?? '' }}">
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td data-order="{{ $log->created_at->timestamp }}">
                                        <span class="text-gray-800">{{ $log->created_at->format('d M, Y') }}</span>
                                        <span
                                            class="text-gray-500 d-block fs-7">{{ $log->created_at->format('h:i A') }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-circle symbol-40px overflow-hidden me-3">
                                                @if ($log->user->photo_url ?? false)
                                                    <div class="symbol-label">
                                                        <img src="{{ asset($log->user->photo_url) }}"
                                                            alt="{{ $log->user->name }}" class="w-100" />
                                                    </div>
                                                @else
                                                    <div class="symbol-label fs-6 bg-light-primary text-primary">
                                                        {{ strtoupper(substr($log->user->name ?? 'U', 0, 1)) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-column">
                                                <a href="{{ route('settlements.show', $log->user_id) }}"
                                                    class="text-gray-800 text-hover-primary mb-1">{{ $log->user->name ?? 'N/A' }}</a>
                                            </div>
                                        </div>
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
                                            <span
                                                class="text-success fw-bold">+৳{{ number_format($log->amount, 2) }}</span>
                                        @else
                                            <span class="text-danger fw-bold">৳{{ number_format($log->amount, 2) }}</span>
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

        <!-- ================= BOTTOM: LOGIN HISTORY ================= -->
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <h3 class="mb-0">My Recent Login Activities</h3>

                        <div class="d-flex align-items-center position-relative">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" data-login-activities-table-filter="search"
                                class="form-control form-control-solid w-200px w-lg-350px ps-13"
                                placeholder="Search login activities" />
                        </div>
                    </div>
                </div>


                <div class="card-body py-4">
                    <!--begin::Table-->
                    <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                        id="kt_login_activities_table">
                        <thead>
                            <tr class="fw-bold fs-5 gs-0">
                                <th class="w-25px">#</th>
                                <th>IP Address</th>
                                <th>User Agent</th>
                                <th>Device</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold fs-5">
                            @foreach ($loginActivities as $activity)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $activity->ip_address }}</td>
                                    <td>{{ $activity->user_agent }}</td>
                                    <td>{{ $activity->device }}</td>
                                    <td>{{ $activity->created_at->diffForHumans() }} <span class="ms-1"
                                            data-bs-toggle="tooltip"
                                            title="{{ $activity->created_at->format('d-m-Y, h:i A') }}">
                                            <i class="ki-outline ki-information fs-4"></i>
                                        </span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <!--end::Table-->
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script src="{{ asset('js/users/profile.js') }}"></script>
@endpush
