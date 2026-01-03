@extends('layouts.app')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('title', 'Wallet History - ' . $user->name)

@section('header-title')
    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
        Wallet History
    </h1>
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('settlements.index') }}" class="text-muted text-hover-primary">Settlements</a>
        </li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">{{ $user->name }}</li>
    </ul>
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
                                    {{ $user->roles->first()?->name ?? 'User' }}
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
                                            ৳{{ number_format($summary['total_collected'], 2) }}
                                        </div>
                                    </div>
                                    <div class="fw-semibold fs-6 text-gray-500">Total Collected</div>
                                </div>

                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-outline ki-arrow-down fs-3 text-info me-2"></i>
                                        <div class="fs-2 fw-bold text-info">
                                            ৳{{ number_format($summary['total_settled'], 2) }}
                                        </div>
                                    </div>
                                    <div class="fw-semibold fs-6 text-gray-500">Total Settled</div>
                                </div>

                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-outline ki-wallet fs-3 text-warning me-2"></i>
                                        <div class="fs-2 fw-bold text-warning"
                                            data-wallet-balance="{{ $summary['current_balance'] }}">
                                            ৳{{ number_format($summary['current_balance'], 2) }}
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
                            class="fs-2hx fw-bold text-success me-2 lh-1 ls-n2">৳{{ number_format($summary['today_collected'], 2) }}</span>
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
                            class="fs-2hx fw-bold text-info me-2 lh-1 ls-n2">৳{{ number_format($summary['today_settled'], 2) }}</span>
                        <span class="text-gray-500 pt-1 fw-semibold fs-6">Today's Settlement</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Transaction History Table --}}
    <div class="card card-flush">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                    <input type="text" data-kt-filter="search" class="form-control form-control-solid w-250px ps-13"
                        placeholder="Search..." />
                </div>
            </div>
            <div class="card-toolbar">
                <div class="d-flex align-items-center gap-3">
                    {{-- Type Filter --}}
                    <select class="form-select form-select-solid w-150px" data-kt-filter="type" data-control="select2"
                        data-placeholder="All Types" data-allow-clear="true" data-hide-search="true">
                        <option></option>
                        <option value="collection">Collection</option>
                        <option value="settlement">Settlement</option>
                        <option value="adjustment">Adjustment</option>
                    </select>

                    {{-- Back Button --}}
                    <a href="{{ route('settlements.index') }}" class="btn btn-sm btn-flex btn-light">
                        <i class="ki-outline ki-arrow-left fs-4 me-1"></i>
                        Back
                    </a>

                    <button type="button" class="btn btn-sm btn-flex btn-light-warning btn-adjustment"
                        data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}"
                        data-balance="{{ $user->current_balance }}">
                        <i class="ki-outline ki-wrench fs-4 me-1"></i>
                        Adjustment
                    </button>

                    @if ($user->current_balance > 0)
                        <button type="button" class="btn btn-sm btn-flex btn-primary btn-settle"
                            data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}"
                            data-balance="{{ $user->current_balance }}">
                            <i class="ki-outline ki-dollar fs-4 me-1"></i>
                            Settle Now
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_wallet_logs_table">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-100px">Date</th>
                        <th class="min-w-100px">Type</th>
                        <th class="min-w-150px">Description</th>
                        <th class="min-w-100px text-end">Amount</th>
                        <th class="min-w-100px text-end">Old Balance</th>
                        <th class="min-w-100px text-end">New Balance</th>
                        <th class="min-w-100px">Created By</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    @forelse($logs as $log)
                        <tr data-type="{{ $log->type }}">
                            <td data-order="{{ $log->created_at->timestamp }}">
                                <span class="text-gray-800">{{ $log->created_at->format('d M, Y') }}</span>
                                <span class="text-gray-500 d-block fs-7">{{ $log->created_at->format('h:i A') }}</span>
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
                                <span class="text-gray-800">{{ $log->description ?? '-' }}</span>
                                @if ($log->paymentTransaction)
                                    <a href="{{ route('transactions.show', $log->payment_transaction_id) }}"
                                        class="text-primary d-block fs-7">
                                        View Payment #{{ $log->payment_transaction_id }}
                                    </a>
                                @endif
                            </td>
                            <td class="text-end" data-order="{{ $log->amount }}">
                                @if ($log->amount >= 0)
                                    <span class="text-success fw-bold">+৳{{ number_format($log->amount, 2) }}</span>
                                @else
                                    <span class="text-danger fw-bold">৳{{ number_format($log->amount, 2) }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <span class="text-gray-600">৳{{ number_format($log->old_balance, 2) }}</span>
                            </td>
                            <td class="text-end">
                                <span class="text-gray-800 fw-bold">৳{{ number_format($log->new_balance, 2) }}</span>
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

    {{-- Settlement Modal --}}
    <div class="modal fade" id="kt_modal_settlement" tabindex="-1" aria-hidden="true">
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
                                            id="modal_current_balance">৳{{ number_format($user->current_balance, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column mb-8 fv-row">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">Settlement Amount</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" min="1" max="{{ $user->current_balance }}"
                                    class="form-control form-control-solid" placeholder="Enter amount" name="amount"
                                    id="settlement_amount" required />
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
    <div class="modal fade" id="kt_modal_adjustment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-500px">
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
                                            id="adj_modal_current_balance">৳{{ number_format($user->current_balance, 2) }}</span>
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
                                        <i class="ki-outline ki-arrow-up fs-4 me-1"></i>
                                        Increase Balance
                                    </span>
                                </label>
                                <label class="form-check form-check-custom form-check-solid form-check-sm">
                                    <input class="form-check-input" type="radio" name="adjustment_type"
                                        value="decrease" />
                                    <span class="form-check-label text-danger fw-semibold">
                                        <i class="ki-outline ki-arrow-down fs-4 me-1"></i>
                                        Decrease Balance
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="d-flex flex-column mb-8 fv-row">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">Amount</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" min="0.01"
                                    class="form-control form-control-solid" placeholder="Enter amount" name="amount"
                                    id="adjustment_amount" required />
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
@endpush

@push('page-js')
    <script src="{{ asset('js/settlements/show.js') }}"></script>

    <script>
        document.getElementById("reports_menu")?.classList.add("here", "show");
        document.getElementById("settlements_link")?.classList.add("active");
    </script>
@endpush
