@props([
    'title' => 'Stat',
    'id' => 'statValue',
    'icon' => 'bi-graph-up',
    'color' => 'primary',
    'subtitle' => null,
    'subtitleId' => null,
])

<div class="card dashboard-card h-100 bg-{{ $color }}">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="flex-grow-1">
                <div class="text-white fs-7 fw-semibold opacity-75">{{ $title }}</div>
                <div class="fs-2x fw-bold text-white" id="{{ $id }}">
                    <span class="loading-skeleton d-inline-block" style="width: 100px; height: 36px;"></span>
                </div>
                @if ($subtitle || $subtitleId)
                    <div class="text-white opacity-75 fs-8"
                        @if ($subtitleId) id="{{ $subtitleId }}" @endif>
                        {{ $subtitle ?? '' }}
                    </div>
                @endif
            </div>
            <div class="ms-3">
                <i class="bi {{ $icon }} fs-3x text-white opacity-50"></i>
            </div>
        </div>
    </div>
</div>
