{{-- Branch Statistics Card Partial --}}
@php
    $activeCount = $stats['active'] ?? 0;
    $inactiveCount = $stats['inactive'] ?? 0;
    $totalCount = $stats['total'] ?? 0;
    $receivable = $stats['receivable'] ?? 0;
    $prefix = is_numeric($branchId) ? "branch-{$branchId}" : $branchId;
@endphp

<div class="branch-stats-card" data-branch-id="{{ $branchId }}">
    <!--begin::Branch Name Badge (for individual branches)-->
    @if (isset($branchName) && $branchId !== 'all' && $branchId !== 'current')
        <div class="text-center mb-3">
            <span class="badge badge-light-primary fs-7">
                {{ $branchName }}
            </span>
        </div>
    @endif
    <!--end::Branch Name Badge-->

    <!--begin::Stats Grid-->
    <div class="row g-3">
        <!--begin::Total Students-->
        <div class="col-6">
            <div class="stats-mini-card h-100">
                <div class="stats-value text-primary" id="stats-total-{{ $prefix }}">{{ $totalCount }}</div>
                <div class="stats-label">Total Students</div>
            </div>
        </div>
        <!--end::Total Students-->

        <!--begin::Active Students-->
        <div class="col-6">
            <div class="stats-mini-card h-100">
                <div class="stats-value text-success" id="stats-active-{{ $prefix }}">{{ $activeCount }}</div>
                <div class="stats-label">Active Students</div>
            </div>
        </div>
        <!--end::Active Students-->

        <!--begin::Inactive Students-->
        <div class="col-6">
            <div class="stats-mini-card h-100">
                <div class="stats-value text-danger" id="stats-inactive-{{ $prefix }}">{{ $inactiveCount }}</div>
                <div class="stats-label">Inactive Students</div>
            </div>
        </div>
        <!--end::Inactive Students-->

        <!--begin::Total Receivable (Admin Only) / Subjects (Non-Admin)-->
        @if ($isAdmin)
            <div class="col-6">
                <div class="stats-mini-card receivable-card h-100">
                    <div class="stats-value text-warning" id="stats-receivable-{{ $prefix }}">
                        <span class="fs-7 fw-semibold">৳</span>
                        {{ number_format($receivable, 0) }}
                    </div>
                    <div class="stats-label">Total Receivable</div>
                    @if ($activeCount > 0)
                        <div class="fs-9 text-muted mt-1">
                            Avg: ৳{{ number_format($receivable / max($activeCount, 1), 0) }}/student
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="col-6">
                <div class="stats-mini-card subjects-stat h-100">
                    <div class="stats-value text-info" id="stats-subjects-{{ $prefix }}">
                        {{ $classname->subjects->count() ?? 0 }}
                    </div>
                    <div class="stats-label">Total Subjects</div>
                </div>
            </div>
        @endif
        <!--end::Total Receivable (Admin Only) / Subjects (Non-Admin)-->
    </div>
    <!--end::Stats Grid-->

    <!--begin::Subjects Count (Admin only - shown in All tab)-->
    @if ($isAdmin && ($branchId === 'all' || $branchId === 'current'))
        <div class="row g-3 mt-1">
            <div class="col-12">
                <div class="stats-mini-card subjects-stat">
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <span class="stats-value text-info fs-4" id="stats-subjects-{{ $prefix }}">
                            {{ $classname->subjects->count() ?? 0 }}
                        </span>
                        <span class="stats-label">Total Subjects</span>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!--end::Subjects Count-->
</div>
