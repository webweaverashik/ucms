@extends('layouts.app')

@section('title', 'Dashboard')

@push('page-css')
    <style>
        .stat-card {
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .chart-container-sm {
            position: relative;
            height: 250px;
        }

        .branch-tab {
            cursor: pointer;
            transition: all 0.2s;
        }

        .branch-tab:hover {
            background-color: var(--bs-gray-100);
        }

        .branch-tab.active {
            background-color: var(--bs-primary) !important;
            color: white !important;
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
        }

        @keyframes shimmer {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .activity-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .activity-dot.online {
            background-color: #1BC5BD;
        }
    </style>
@endpush

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Dashboard
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
    <div id="dashboard-app">
        {{-- Branch Info Banner (instead of tabs) --}}
        <div class="card bg-light-primary mb-5">
            <div class="card-body py-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-50px me-4">
                        <span class="symbol-label bg-primary">
                            <i class="ki-outline ki-bank fs-2x text-white"></i>
                        </span>
                    </div>
                    <div>
                        <h4 class="text-primary mb-0">{{ $data['current_branch']['branch_name'] ?? 'Your Branch' }}</h4>
                        <span class="text-muted">You are viewing data for your assigned branch</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        @include('dashboard.partials._stats_cards')

        {{-- Charts Row 1 --}}
        <div class="row g-5 mb-5">
            {{-- Payment Overview Chart --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Payment Overview</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Monthly collection trends</span>
                        </h3>
                        <div class="card-toolbar">
                            <select class="form-select form-select-sm w-auto" id="paymentChartPeriod">
                                <option value="6">Last 6 months</option>
                                <option value="12">Last 12 months</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="chart-container">
                            <canvas id="paymentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Student Distribution Chart --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Student Distribution</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">By class and status</span>
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="chart-container">
                            <canvas id="studentDistChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Row 2 --}}
        <div class="row g-5 mb-5">
            {{-- Attendance Analytics --}}
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Attendance Analytics</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Class-wise attendance this week</span>
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="chart-container-sm">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Invoice Status Breakdown --}}
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header border-0 pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Invoice Status</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Current breakdown</span>
                        </h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="chart-container-sm">
                            <canvas id="invoiceStatusChart"></canvas>
                        </div>
                        <div class="mt-4" id="invoiceStatusLegend">
                            {{-- Populated by JS --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tables Row --}}
        @include('dashboard.partials._tables')

        {{-- Batch Stats --}}
        @include('dashboard.partials._batch_stats')
    </div>
@endsection

@push('vendor-js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@push('page-js')
<script>
    const DashboardConfig = {
        apiBaseUrl: '{{ url("/dashboard/api") }}',
        csrfToken: '{{ csrf_token() }}',
        currentBranch: '{{ auth()->user()->branch_id }}',
        isAdmin: false,
        initialData: @json($data),
    };
</script>
    <script src="{{ asset('js/dashboard/utils.js') }}"></script>
    <script src="{{ asset('js/dashboard/cache.js') }}"></script>
    <script src="{{ asset('js/dashboard/charts.js') }}"></script>
    <script src="{{ asset('js/dashboard/tables.js') }}"></script>
    <script src="{{ asset('js/dashboard/main.js') }}"></script>
    <script>
        document.getElementById("dashboard_link").classList.add("active");
    </script>
@endpush
