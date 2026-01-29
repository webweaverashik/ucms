@props([
    'title' => 'Stat',
    'id' => 'statValue',
    'icon' => 'bi-graph-up',
    'color' => 'primary',
    'subtitle' => null,
    'subtitleId' => null,
])

<div class="card dashboard-card h-100">
    <div class="card-body d-flex align-items-center">
        <div class="stat-icon bg-light-{{ $color }} me-4">
            <i class="bi {{ $icon }} fs-1 text-{{ $color }}"></i>
        </div>
        <div>
            <div class="text-gray-500 fs-7 fw-semibold">{{ $title }}</div>
            <div class="fs-2 fw-bold text-gray-800" id="{{ $id }}">
                <span class="loading-skeleton d-inline-block" style="width: 60px; height: 28px;"></span>
            </div>
            @if ($subtitle || $subtitleId)
                <div class="text-muted fs-8" @if ($subtitleId) id="{{ $subtitleId }}" @endif>
                    @if ($subtitle)
                        {{ $subtitle }}@else<span class="loading-skeleton d-inline-block"
                            style="width: 80px; height: 14px;"></span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
