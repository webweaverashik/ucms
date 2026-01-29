@extends('layouts.app')

@section('title', 'Admin Dashboard')

@push('page-css')
    <link rel="stylesheet" href="{{ asset('css/dashboard/dashboard.css') }}">
@endpush

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Admin Dashboard
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Dashboard</li>
        </ul>
    </div>
@endsection

@section('content')
    <div class="d-flex flex-column flex-column-fluid">

        {{-- Branch Tabs (Admin Only) --}}
        @if ($isAdmin && $branches->count() > 0)
            <div class="card mb-5 shadow-sm">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center">
                        <span class="fw-semibold text-gray-600 me-4">Select Branch:</span>
                        <ul class="nav nav-pills nav-pills-sm" id="branchTabs">
                            @foreach ($branches as $index => $branch)
                                <li class="nav-item me-2">
                                    <a class="nav-link btn btn-sm btn-flex {{ $index === 0 ? 'btn-light-primary active' : 'btn-light' }}"
                                        data-branch-id="{{ $branch->id }}" href="javascript:void(0)">
                                        {{ $branch->branch_name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Student Stats Row --}}
        <div class="row g-5 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-light-primary me-4">
                            <i class="bi bi-people-fill fs-1 text-primary"></i>
                        </div>
                        <div>
                            <div class="stat-label">Total Students</div>
                            <div class="stat-value text-gray-900" id="statTotalStudents">
                                <span class="loading-skeleton d-inline-block" style="width: 60px; height: 28px;"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-light-success me-4">
                            <i class="bi bi-person-check-fill fs-1 text-success"></i>
                        </div>
                        <div>
                            <div class="stat-label">Active Students</div>
                            <div class="stat-value text-gray-900" id="statActiveStudents">
                                <span class="loading-skeleton d-inline-block" style="width: 60px; height: 28px;"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <a href="{{ route('students.pending.index') }}"
                    class="card dashboard-card h-100 shadow-sm text-decoration-none">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-light-warning me-4">
                            <i class="bi bi-person-exclamation fs-1 text-warning"></i>
                        </div>
                        <div>
                            <div class="stat-label">Pending Approval</div>
                            <div class="stat-value text-gray-900" id="statPendingStudents">
                                <span class="loading-skeleton d-inline-block" style="width: 60px; height: 28px;"></span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card h-100 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="stat-icon bg-light-danger me-4">
                            <i class="bi bi-receipt fs-1 text-danger"></i>
                        </div>
                        <div>
                            <div class="stat-label">Due Invoices</div>
                            <div class="stat-value text-gray-900" id="statDueInvoices">
                                <span class="loading-skeleton d-inline-block" style="width: 60px; height: 28px;"></span>
                            </div>
                            <div class="fs-8 text-danger fw-semibold" id="statDueAmount"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Collection Stats Row --}}
        <div class="row g-5 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card h-100 bg-success shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-white fs-7 fw-semibold opacity-75">Today's Collection</div>
                                <div class="fs-2x fw-bold text-white" id="statTodayCollection">
                                    <span class="loading-skeleton d-inline-block"
                                        style="width: 100px; height: 36px;"></span>
                                </div>
                            </div>
                            <i class="bi bi-cash-stack fs-3x text-white opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card h-100 bg-primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-white fs-7 fw-semibold opacity-75">This Month</div>
                                <div class="fs-2x fw-bold text-white" id="statMonthCollection">
                                    <span class="loading-skeleton d-inline-block"
                                        style="width: 100px; height: 36px;"></span>
                                </div>
                            </div>
                            <i class="bi bi-calendar-check fs-3x text-white opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card h-100 bg-danger shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-white fs-7 fw-semibold opacity-75">Today's Expense</div>
                                <div class="fs-2x fw-bold text-white" id="statTodayCost">
                                    <span class="loading-skeleton d-inline-block"
                                        style="width: 100px; height: 36px;"></span>
                                </div>
                            </div>
                            <i class="bi bi-wallet2 fs-3x text-white opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card h-100 bg-warning shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-white fs-7 fw-semibold opacity-75">Pending Approvals</div>
                                <div class="fs-2x fw-bold text-white" id="statPendingApprovals">
                                    <span class="loading-skeleton d-inline-block"
                                        style="width: 60px; height: 36px;"></span>
                                </div>
                            </div>
                            <i class="bi bi-clock-history fs-3x text-white opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="row g-5 mb-5">
            <div class="col-xl-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-4 mb-1" id="collectionChartTitle">Today's Collection</span>
                            <span class="text-muted fw-semibold fs-7" id="collectionChartSubtitle">Hourly breakdown</span>
                        </h3>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-icon btn-sm btn-light-primary me-2"
                                    id="prevDateBtn" title="Previous Day">
                                    <i class="bi bi-chevron-left fs-6"></i>
                                </button>
                                <div class="position-relative">
                                    <input type="text" class="form-control form-control-sm text-center fw-semibold"
                                        id="collectionDatePicker" style="width: 130px;" readonly>
                                </div>
                                <button type="button" class="btn btn-icon btn-sm btn-light-primary ms-2"
                                    id="nextDateBtn" title="Next Day">
                                    <i class="bi bi-chevron-right fs-6"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="collectionChart" class="chart-container"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-4 mb-1">Collection by User</span>
                            <span class="text-muted fw-semibold fs-7" id="userCollectionSubtitle">Today's
                                performance</span>
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <div id="userCollectionList" class="table-scrollable">
                            <div class="d-flex justify-content-center py-10">
                                <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                                <span class="text-muted">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Middle Row --}}
        <div class="row g-5 mb-5">
            <div class="col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-4 mb-1">Top Due Students</span>
                            <span class="text-muted fw-semibold fs-7">Students with highest dues</span>
                        </h3>
                    </div>
                    <div class="card-body py-3">
                        <div id="topDueStudentsList" class="table-scrollable">
                            <div class="d-flex justify-content-center py-10">
                                <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                                <span class="text-muted">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-4 mb-1">
                                Pending Discount Approvals
                                <span class="badge badge-danger ms-2 approval-badge" id="approvalBadge">0</span>
                            </span>
                            <span class="text-muted fw-semibold fs-7">Discounted transactions awaiting approval</span>
                        </h3>
                    </div>
                    <div class="card-body py-3">
                        <div id="pendingApprovalsList" class="table-scrollable">
                            <div class="d-flex justify-content-center py-10">
                                <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                                <span class="text-muted">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cost Analysis Row --}}
        <div class="row g-5 mb-5">
            <div class="col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-4 mb-1">Cost Breakdown</span>
                            <span class="text-muted fw-semibold fs-7" id="costPeriodLabel">This Month</span>
                        </h3>
                        <div class="card-toolbar">
                            <ul class="nav nav-pills nav-pills-sm">
                                <li class="nav-item">
                                    <a class="nav-link btn btn-sm btn-active-light-primary fw-semibold px-4 active"
                                        data-period="month" href="#">Month</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link btn btn-sm btn-active-light-primary fw-semibold px-4"
                                        data-period="week" href="#">Week</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link btn btn-sm btn-active-light-primary fw-semibold px-4"
                                        data-period="today" href="#">Today</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="costPieChart" class="chart-container"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-4 mb-1">Cost Details</span>
                            <span class="text-muted fw-semibold fs-7">Type-wise breakdown</span>
                        </h3>
                    </div>
                    <div class="card-body py-3">
                        <div id="costTypeTable" class="table-scrollable">
                            <div class="d-flex justify-content-center py-10">
                                <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                                <span class="text-muted">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Attendance & Recent Transactions Row --}}
        <div class="row g-5 mb-5">
            <div class="col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-4 mb-1">Attendance Overview</span>
                            <span class="text-muted fw-semibold fs-7" id="attendanceDateLabel">Today's summary</span>
                        </h3>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center">
                                <button type="button" class="btn btn-icon btn-sm btn-light-primary me-2"
                                    id="prevAttDateBtn" title="Previous Day">
                                    <i class="bi bi-chevron-left fs-6"></i>
                                </button>
                                <div class="position-relative">
                                    <input type="text" class="form-control form-control-sm text-center fw-semibold"
                                        id="attendanceDatePicker" style="width: 130px;" readonly>
                                </div>
                                <button type="button" class="btn btn-icon btn-sm btn-light-primary ms-2"
                                    id="nextAttDateBtn" title="Next Day">
                                    <i class="bi bi-chevron-right fs-6"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <div class="row g-3 mb-5">
                            <div class="col-4">
                                <div class="bg-light-success rounded p-3 text-center">
                                    <div class="fs-3 fw-bold text-success" id="attPresent">-</div>
                                    <div class="fs-8 text-muted fw-semibold">Present</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-light-danger rounded p-3 text-center">
                                    <div class="fs-3 fw-bold text-danger" id="attAbsent">-</div>
                                    <div class="fs-8 text-muted fw-semibold">Absent</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-light-warning rounded p-3 text-center">
                                    <div class="fs-3 fw-bold text-warning" id="attLate">-</div>
                                    <div class="fs-8 text-muted fw-semibold">Late</div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-7 border-0" id="attendanceBatchTabs">
                                <li class="nav-item">
                                    <a class="nav-link active fw-semibold" data-batch-id=""
                                        href="javascript:void(0)">All</a>
                                </li>
                            </ul>
                        </div>
                        <div id="attendanceByClassList" class="table-scrollable" style="max-height: 220px;">
                            <div class="d-flex justify-content-center py-5">
                                <span class="spinner-border spinner-border-sm text-primary"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold fs-4 mb-1">Recent Transactions</span>
                            <span class="text-muted fw-semibold fs-7">Latest payment activities</span>
                        </h3>
                        <div class="card-toolbar">
                            <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-light-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body py-3">
                        <div id="recentTransactionsList" class="table-scrollable">
                            <div class="d-flex justify-content-center py-10">
                                <span class="spinner-border spinner-border-sm text-primary me-2"></span>
                                <span class="text-muted">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('page-js')
    <script>
        // Pass initial branch ID to JavaScript
        window.DASHBOARD_CONFIG = {
            initialBranchId: '{{ $branchId ?? '' }}'
        };
    </script>
    <script src="{{ asset('js/dashboard/admin.js') }}"></script>
@endpush