@extends('layouts.app')
@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('title', 'All Wallet Logs')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Wallet Transactions
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('settlements.index') }}" class="text-muted text-hover-primary">
                    Settlements</a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                All Logs </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection

@section('content')
    <div class="card card-flush">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                    <input type="text" data-kt-filter="search" class="form-control form-control-solid w-350px ps-13"
                        placeholder="Search..." />
                </div>
            </div>
            <div class="card-toolbar">
                <div class="d-flex justify-content-end align-items-center gap-3">
                    {{-- User Filter --}}
                    <select class="form-select form-select-solid w-200px w-md-250px" data-kt-filter="user" data-control="select2"
                        data-placeholder="All Users" data-allow-clear="true">
                        <option></option>
                        @foreach ($users as $user)
                            <option value="{{ $user->name }}">{{ $user->name }}</option>
                        @endforeach
                    </select>

                    {{-- Type Filter --}}
                    <select class="form-select form-select-solid w-150px w-md-250px" data-kt-filter="type" data-control="select2"
                        data-placeholder="All Types" data-allow-clear="true" data-hide-search="true">
                        <option></option>
                        <option value="collection">Collection</option>
                        <option value="settlement">Settlement</option>
                        <option value="adjustment">Adjustment</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body pt-0">
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table" id="kt_logs_table">
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
                    @forelse($logs as $log)
                        <tr data-type="{{ $log->type }}" data-user="{{ $log->user->name ?? '' }}">
                            <td>{{ $loop->index + 1 }}</td>
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
                                    <span class="badge badge-success">Collection</span>
                                @elseif($log->type === 'settlement')
                                    <span class="badge badge-info">Settlement</span>
                                @else
                                    <span class="badge badge-warning">Adjustment</span>
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
                                    <span class="text-success fw-bold">+৳{{ number_format($log->amount, 0) }}</span>
                                @else
                                    <span class="text-danger fw-bold">৳{{ number_format($log->amount, 0) }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <span class="text-gray-600">৳{{ number_format($log->old_balance, 0) }}</span>
                            </td>
                            <td class="text-end">
                                <span class="text-gray-800 fw-bold">৳{{ number_format($log->new_balance, 0) }}</span>
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
        document.getElementById("settlements_menu")?.classList.add("here", "show");
        document.getElementById("settlements_logs_link")?.classList.add("active");
    </script>
@endpush
