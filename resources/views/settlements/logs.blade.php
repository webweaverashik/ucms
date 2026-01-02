@extends('layouts.app')
@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('title', 'All Wallet Logs')

@section('header-title')
    <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
        All Wallet Transactions
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
        <li class="breadcrumb-item text-muted">All Logs</li>
    </ul>
@endsection

@section('content')
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
                <div class="d-flex justify-content-end align-items-center gap-3">
                    {{-- User Filter --}}
                    <select class="form-select form-select-solid w-200px" data-kt-filter="user" data-control="select2"
                        data-placeholder="All Users" data-allow-clear="true">
                        <option></option>
                        @foreach ($users as $user)
                            <option value="{{ $user->name }}">{{ $user->name }}</option>
                        @endforeach
                    </select>

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
                        Back to Settlements
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_logs_table">
                <thead>
                    <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-100px">Date</th>
                        <th class="min-w-125px">User</th>
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
                        <tr data-type="{{ $log->type }}" data-user="{{ $log->user->name ?? '' }}">
                            <td data-order="{{ $log->created_at->timestamp }}">
                                <span class="text-gray-800">{{ $log->created_at->format('d M, Y') }}</span>
                                <span class="text-gray-500 d-block fs-7">{{ $log->created_at->format('h:i A') }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-circle symbol-40px overflow-hidden me-3">
                                        @if ($log->user->photo_url ?? false)
                                            <div class="symbol-label">
                                                <img src="{{ asset($log->user->photo_url) }}" alt="{{ $log->user->name }}"
                                                    class="w-100" />
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
                                <span class="text-gray-800">{{ Str::limit($log->description, 40) ?? '-' }}</span>
                                @if ($log->paymentTransaction)
                                    <a href="{{ route('transactions.show', $log->payment_transaction_id) }}"
                                        class="text-primary d-block fs-7">
                                        Payment #{{ $log->payment_transaction_id }}
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
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script src="{{ asset('js/settlements/logs.js') }}"></script>
    <script>
        document.getElementById("reports_menu")?.classList.add("here", "show");
        document.getElementById("settlements_link")?.classList.add("active");
    </script>
@endpush
