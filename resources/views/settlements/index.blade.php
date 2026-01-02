@extends('layouts.app')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('title', 'User Settlements')

@section('header-title')
    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
        User Settlements
    </h1>
    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
        <li class="breadcrumb-item text-muted">
            <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Dashboard</a>
        </li>
        <li class="breadcrumb-item">
            <span class="bullet bg-gray-500 w-5px h-2px"></span>
        </li>
        <li class="breadcrumb-item text-muted">Settlements</li>
    </ul>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
        <div class="col-md-6 col-lg-6 col-xl-4">
            <div class="card card-flush h-md-100">
                <div class="card-header pt-5">
                    <div class="card-title d-flex flex-column">
                        <span class="fs-2hx fw-bold text-warning me-2 lh-1 ls-n2">{{ $usersWithBalance }}</span>
                        <span class="text-gray-500 pt-1 fw-semibold fs-6">Users with Balance</span>
                    </div>
                </div>
                <div class="card-body d-flex align-items-end pt-0">
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="badge badge-light-warning fs-base">
                            <i class="ki-outline ki-wallet fs-5 text-warning me-1"></i>
                            Pending Collection
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-6 col-xl-4">
            <div class="card card-flush h-md-100">
                <div class="card-header pt-5">
                    <div class="card-title d-flex flex-column">
                        <span
                            class="fs-2hx fw-bold text-danger me-2 lh-1 ls-n2">৳{{ number_format($totalPending, 2) }}</span>
                        <span class="text-gray-500 pt-1 fw-semibold fs-6">Total Pending</span>
                    </div>
                </div>
                <div class="card-body d-flex align-items-end pt-0">
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="badge badge-light-danger fs-base">
                            <i class="ki-outline ki-dollar fs-5 text-danger me-1"></i>
                            To be Collected
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-6 col-xl-4">
            <div class="card card-flush h-md-100">
                <div class="card-header pt-5">
                    <div class="card-title d-flex flex-column">
                        <span class="fs-2hx fw-bold text-primary me-2 lh-1 ls-n2">{{ $branches->count() }}</span>
                        <span class="text-gray-500 pt-1 fw-semibold fs-6">Total Branches</span>
                    </div>
                </div>
                <div class="card-body d-flex align-items-end pt-0">
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="badge badge-light-primary fs-base">
                            <i class="ki-outline ki-bank fs-5 text-primary me-1"></i>
                            Active Branches
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Branch Tabs Card --}}
    <div class="card card-flush">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                {{-- Branch Tabs --}}
                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
                    @foreach ($branches as $index => $branch)
                        <li class="nav-item">
                            <a class="nav-link text-active-primary pb-4 {{ $index === 0 ? 'active' : '' }}"
                                data-bs-toggle="tab" href="#kt_tab_branch_{{ $branch->id }}">
                                <i class="ki-outline ki-bank fs-4 me-2"></i>
                                {{ $branch->branch_name }}
                                @php
                                    $branchPending = $usersByBranch[$branch->id]->sum('current_balance');
                                @endphp
                                @if ($branchPending > 0)
                                    <span
                                        class="badge badge-light-danger ms-2">৳{{ number_format($branchPending, 0) }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-toolbar">
                <div class="d-flex justify-content-end align-items-center gap-3">
                    {{-- Search --}}
                    <div class="d-flex align-items-center position-relative">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                        <input type="text" data-kt-filter="search" class="form-control form-control-solid w-200px ps-12"
                            placeholder="Search user..." />
                    </div>

                    {{-- Balance Filter --}}
                    <select class="form-select form-select-solid w-150px" data-kt-filter="balance" data-control="select2"
                        data-placeholder="All Balances" data-allow-clear="true" data-hide-search="true">
                        <option></option>
                        <option value="with_balance">With Balance</option>
                        <option value="zero_balance">Zero Balance</option>
                    </select>

                    {{-- View Logs Button --}}
                    <a href="{{ route('settlements.logs') }}" class="btn btn-sm btn-flex btn-light-primary">
                        <i class="ki-outline ki-document fs-4 me-1"></i>
                        View All Logs
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            {{-- Tab Contents --}}
            <div class="tab-content">
                @foreach ($branches as $index => $branch)
                    <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                        id="kt_tab_branch_{{ $branch->id }}">
                        <table class="table align-middle table-row-dashed fs-6 gy-5 kt-settlements-table ucms-table"
                            data-branch-id="{{ $branch->id }}">
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-150px">User</th>
                                    <th class="min-w-80px">Role</th>
                                    <th class="min-w-100px text-end">Total Collected</th>
                                    <th class="min-w-100px text-end">Total Settled</th>
                                    <th class="min-w-100px text-end">Current Balance</th>
                                    <th class="text-end min-w-100px">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-600">
                                @forelse($usersByBranch[$branch->id] as $user)
                                    <tr>
                                        <td class="d-flex align-items-center">
                                            <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                                @if ($user->photo_url)
                                                    <div class="symbol-label">
                                                        <img src="{{ asset($user->photo_url) }}" alt="{{ $user->name }}"
                                                            class="w-100" />
                                                    </div>
                                                @else
                                                    <div class="symbol-label fs-3 bg-light-primary text-primary">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="d-flex flex-column">
                                                <a href="{{ route('settlements.show', $user) }}"
                                                    class="text-gray-800 text-hover-primary mb-1">{{ $user->name }}</a>
                                                <span>{{ $user->mobile_number }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($user->isAdmin())
                                                <span class="badge badge-light-danger">Admin</span>
                                            @elseif($user->isManager())
                                                <span class="badge badge-light-warning">Manager</span>
                                            @elseif($user->isAccountant())
                                                <span class="badge badge-light-info">Accountant</span>
                                            @else
                                                <span
                                                    class="badge badge-light-secondary">{{ $user->roles->first()?->name ?? 'User' }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span
                                                class="text-success fw-bold">৳{{ number_format($user->total_collected, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span
                                                class="text-info fw-bold">৳{{ number_format($user->total_settled, 2) }}</span>
                                        </td>
                                        <td class="text-end"
                                            data-filter="{{ $user->current_balance > 0 ? 'with_balance' : 'zero_balance' }}">
                                            @if ($user->current_balance > 0)
                                                <span
                                                    class="text-danger fw-bolder fs-5">৳{{ number_format($user->current_balance, 2) }}</span>
                                            @else
                                                <span class="text-muted">৳0.00</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="#"
                                                class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary"
                                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                                Actions
                                                <i class="ki-outline ki-down fs-5 ms-1"></i>
                                            </a>
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-175px py-4"
                                                data-kt-menu="true">
                                                <div class="menu-item px-3">
                                                    <a href="{{ route('settlements.show', $user) }}"
                                                        class="menu-link px-3">
                                                        <i class="ki-outline ki-eye fs-4 me-2"></i>
                                                        View History
                                                    </a>
                                                </div>
                                                @if ($user->current_balance > 0)
                                                    <div class="menu-item px-3">
                                                        <a href="javascript:void(0);" class="menu-link px-3 btn-settle"
                                                            data-user-id="{{ $user->id }}"
                                                            data-user-name="{{ $user->name }}"
                                                            data-balance="{{ $user->current_balance }}">
                                                            <i class="ki-outline ki-dollar fs-4 me-2"></i>
                                                            Settle Now
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-10">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="ki-outline ki-wallet fs-3x text-gray-400 mb-3"></i>
                                                <span class="text-gray-500 fs-5">No users found in this branch</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
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
                            Collect money from <span id="modal_user_name" class="text-primary fw-bold"></span>
                        </div>
                    </div>

                    <form id="kt_modal_settlement_form" class="form" action="{{ route('settlements.store') }}"
                        method="POST">
                        @csrf
                        <input type="hidden" name="user_id" id="settlement_user_id">

                        <div class="d-flex flex-column mb-8">
                            <div class="d-flex flex-stack bg-light-warning rounded p-4 mb-5">
                                <div class="d-flex align-items-center me-2">
                                    <i class="ki-outline ki-wallet fs-2x text-warning me-3"></i>
                                    <div class="flex-grow-1">
                                        <span class="text-gray-700 fw-semibold d-block fs-6">Current Balance</span>
                                        <span class="text-warning fw-bolder fs-2" id="modal_current_balance">৳0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column mb-8 fv-row">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">Settlement Amount</span>
                                <span class="ms-1" data-bs-toggle="tooltip" title="Enter amount received from user">
                                    <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                </span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" min="1"
                                    class="form-control form-control-solid" placeholder="Enter amount" name="amount"
                                    id="settlement_amount" required />
                                <button type="button" class="btn btn-light-primary" id="btn_full_amount">Full</button>
                            </div>
                            <div class="fv-plugins-message-container invalid-feedback" id="amount_error"></div>
                        </div>

                        <div class="d-flex flex-column mb-8 fv-row">
                            <label class="fs-6 fw-semibold mb-2">Notes (Optional)</label>
                            <textarea class="form-control form-control-solid" rows="3" name="notes"
                                placeholder="Enter any notes about this settlement..."></textarea>
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
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script src="{{ asset('js/settlements/index.js') }}"></script>

    <script>
        document.getElementById("reports_menu")?.classList.add("here", "show");
        document.getElementById("settlements_link")?.classList.add("active");
    </script>
@endpush
