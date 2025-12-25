{{-- Stats Cards --}}
<div class="row g-5 mb-5" id="statsCards">
    {{-- Total Students --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-gray-500 fw-semibold d-block fs-7">Total Students</span>
                    <span class="text-gray-900 fw-bolder d-block fs-2x mt-1" id="statTotalStudents">
                        {{ number_format($data['stats']['total_students'] ?? 0) }}
                    </span>
                    <div class="mt-2">
                        <span class="badge badge-light-success fs-8">
                            <i class="ki-outline ki-arrow-up fs-9 text-success me-1"></i>
                            12%
                        </span>
                        <span class="text-gray-500 fs-8 ms-1">vs last month</span>
                    </div>
                </div>
                <div class="symbol symbol-50px bg-light-primary rounded">
                    <span class="symbol-label">
                        <i class="ki-outline ki-profile-user fs-2x text-primary"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Active Students --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-gray-500 fw-semibold d-block fs-7">Active Students</span>
                    <span class="text-gray-900 fw-bolder d-block fs-2x mt-1" id="statActiveStudents">
                        {{ number_format($data['stats']['active_students'] ?? 0) }}
                    </span>
                    <div class="mt-2">
                        <span class="badge badge-light-success fs-8" id="statActivePercentage">
                            {{ $data['stats']['active_percentage'] ?? 0 }}% Active
                        </span>
                    </div>
                </div>
                <div class="symbol symbol-50px bg-light-success rounded">
                    <span class="symbol-label">
                        <i class="ki-outline ki-check-circle fs-2x text-success"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Due Invoices --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-gray-500 fw-semibold d-block fs-7">Due Invoices</span>
                    <span class="text-gray-900 fw-bolder d-block fs-2x mt-1" id="statDueInvoices">
                        {{ number_format($data['stats']['due_invoices'] ?? 0) }}
                    </span>
                    <div class="mt-2">
                        <span class="badge badge-light-danger fs-8">
                            <i class="ki-outline ki-arrow-down fs-9 text-danger me-1"></i>
                            8%
                        </span>
                        <span class="text-gray-500 fs-8 ms-1">vs last month</span>
                    </div>
                </div>
                <div class="symbol symbol-50px bg-light-danger rounded">
                    <span class="symbol-label">
                        <i class="ki-outline ki-notification-bing fs-2x text-danger"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Total Collection --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-gray-500 fw-semibold d-block fs-7">Total Collection</span>
                    <span class="text-gray-900 fw-bolder d-block fs-2x mt-1" id="statTotalCollection">
                        {{ formatTakaCurrency($data['stats']['current_month_collection'] ?? 0, true) }}
                    </span>
                    <div class="mt-2">
                        @php
                            $trend = $data['stats']['collection_trend'] ?? 0;
                            $trendClass = $trend >= 0 ? 'success' : 'danger';
                            $trendIcon = $trend >= 0 ? 'arrow-up' : 'arrow-down';
                        @endphp
                        <span class="badge badge-light-{{ $trendClass }} fs-8" id="statCollectionTrend">
                            <i class="ki-outline ki-{{ $trendIcon }} fs-9 text-{{ $trendClass }} me-1"></i>
                            {{ abs($trend) }}%
                        </span>
                        <span class="text-gray-500 fs-8 ms-1">this month</span>
                    </div>
                </div>
                <div class="symbol symbol-50px bg-light-info rounded">
                    <span class="symbol-label">
                        <i class="ki-outline ki-dollar fs-2x text-info"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Additional Stats Row --}}
<div class="row g-5 mb-5">
    {{-- Pending Students --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card bg-light-warning">
            <div class="card-body py-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px me-4">
                        <span class="symbol-label bg-warning">
                            <i class="ki-outline ki-time fs-3 text-white"></i>
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-700 fw-semibold d-block fs-7">Pending Approval</span>
                        <span class="text-gray-900 fw-bolder fs-4" id="statPendingStudents">
                            {{ number_format($data['stats']['pending_students'] ?? 0) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Inactive Students --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card bg-light-secondary">
            <div class="card-body py-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px me-4">
                        <span class="symbol-label bg-secondary">
                            <i class="ki-outline ki-cross-circle fs-3 text-white"></i>
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-700 fw-semibold d-block fs-7">Inactive Students</span>
                        <span class="text-gray-900 fw-bolder fs-4" id="statInactiveStudents">
                            {{ number_format($data['stats']['inactive_students'] ?? 0) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Paid Invoices --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card bg-light-success">
            <div class="card-body py-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px me-4">
                        <span class="symbol-label bg-success">
                            <i class="ki-outline ki-check fs-3 text-white"></i>
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-700 fw-semibold d-block fs-7">Paid Invoices</span>
                        <span class="text-gray-900 fw-bolder fs-4" id="statPaidInvoices">
                            {{ number_format($data['stats']['paid_invoices'] ?? 0) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Partially Paid --}}
    <div class="col-sm-6 col-xl-3">
        <div class="card bg-light-primary">
            <div class="card-body py-4">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-40px me-4">
                        <span class="symbol-label bg-primary">
                            <i class="ki-outline ki-loading fs-3 text-white"></i>
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-700 fw-semibold d-block fs-7">Partially Paid</span>
                        <span class="text-gray-900 fw-bolder fs-4" id="statPartialInvoices">
                            {{ number_format($data['stats']['partially_paid_invoices'] ?? 0) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
