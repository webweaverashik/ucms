{{-- Tables Row --}}
<div class="row g-5 mb-5">
    {{-- Recent Transactions --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900">Recent Transactions</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">Latest payment activities</span>
                </h3>
                <div class="card-toolbar">
                    <a href="{{ route('transactions.index') }}" class="btn btn-sm btn-light-primary">
                        View All
                    </a>
                </div>
            </div>
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th class="min-w-150px">Student</th>
                                <th class="min-w-100px">Amount</th>
                                <th class="min-w-80px">Type</th>
                                <th class="min-w-80px text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody id="recentTransactionsBody">
                            {{-- Loading skeleton --}}
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Employees --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900">Top Employees</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">By payment transactions handled</span>
                </h3>
            </div>
            <div class="card-body py-3" id="topEmployeesBody">
                {{-- Loading skeleton --}}
                <div class="text-center py-5">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Second Tables Row --}}
<div class="row g-5 mb-5">
    {{-- Top Subjects --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900">Top Enrolled Subjects</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">Most popular subjects</span>
                </h3>
            </div>
            <div class="card-body py-3" id="topSubjectsBody">
                {{-- Loading skeleton --}}
                <div class="text-center py-5">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Login Activities --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-900">Recent Login Activities</span>
                    <span class="text-muted mt-1 fw-semibold fs-7">Last 10 user logins</span>
                </h3>
                <div class="card-toolbar">
                    <span class="badge badge-light-primary">
                        <span class="activity-dot online me-1"></span>
                        Live
                    </span>
                </div>
            </div>
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th class="min-w-150px">User</th>
                                <th class="min-w-80px">Role</th>
                                <th class="min-w-100px">IP Address</th>
                                <th class="min-w-120px">Device</th>
                                <th class="min-w-100px text-end">Time</th>
                            </tr>
                        </thead>
                        <tbody id="loginActivitiesBody">
                            {{-- Loading skeleton --}}
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
