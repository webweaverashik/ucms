@php
    $isActiveType = $type === 'active';
    $title = $isActiveType ? 'No Active Classes Found' : 'No Inactive Classes';
    $subtitle = $isActiveType ? 'Start by creating a new class.' : 'All classes are currently active.';
    $iconClass = $isActiveType ? 'ki-folder' : 'ki-archive';
@endphp

<div class="card shadow-sm">
    <div class="card-body d-flex flex-column align-items-center justify-content-center py-20">
        <div class="empty-state-icon">
            <i class="ki-outline {{ $iconClass }} fs-3x text-gray-400"></i>
        </div>
        <h3 class="text-gray-700 fw-semibold mb-2">{{ $title }}</h3>
        <p class="text-gray-500 mb-0">{{ $subtitle }}</p>

        @if ($isActiveType)
            @can('classes.create')
                <a href="#" class="btn btn-primary mt-5" data-bs-toggle="modal" data-bs-target="#kt_modal_add_class">
                    <i class="ki-outline ki-plus fs-4 me-1"></i> Add Class
                </a>
            @endcan
        @endif
    </div>
</div>
