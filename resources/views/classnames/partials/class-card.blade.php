@php
    $canEditClass = auth()->user()->can('classes.edit');
    $canDeleteClass = auth()->user()->can('classes.delete');
    $statusColorClass = $isActive ? 'primary' : 'danger';
    $statusBadgeClass = $isActive ? 'badge-light-success' : 'badge-light-danger';
    $statusText = $isActive ? 'Active' : 'Inactive';
@endphp

<div class="col-md-6 col-xl-4 class-item" data-name="{{ strtolower($classname->name) }}"
    data-numeral="{{ $classname->class_numeral }}" data-students="{{ $classname->students_count }}"
    data-updated="{{ $classname->updated_at->timestamp }}" data-class-id="{{ $classname->id }}">

    <div class="card card-flush h-100 hover-elevate-up shadow-sm">
        <!--begin::Card Header-->
        <div class="card-header border-0 pt-6 pb-0">
            <div class="d-flex align-items-center flex-grow-1">
                <!--begin::Symbol-->
                <div class="symbol symbol-50px symbol-circle me-4">
                    <span
                        class="symbol-label bg-light-{{ $statusColorClass }} text-{{ $statusColorClass }} fs-2 fw-bold">
                        {{ $classname->class_numeral }}
                    </span>
                </div>
                <!--end::Symbol-->

                <!--begin::Title-->
                <div class="d-flex flex-column flex-grow-1">
                    <a href="{{ route('classnames.show', $classname->id) }}"
                        class="fs-4 fw-bold text-gray-900 text-hover-primary mb-1 text-truncate">
                        {{ $classname->name }}
                    </a>
                    <span class="badge {{ $statusBadgeClass }} fw-semibold w-auto align-self-start">
                        {{ $statusText }}
                    </span>
                </div>
                <!--end::Title-->
            </div>

            <!--begin::Card Toolbar-->
            <div class="card-toolbar m-0">
                <button type="button" class="btn btn-icon btn-sm btn-light btn-active-light-primary"
                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                    <i class="ki-outline ki-dots-vertical fs-3"></i>
                </button>

                <!--begin::Menu-->
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold w-200px py-3"
                    data-kt-menu="true">
                    @if ($canEditClass)
                        <!--begin::Menu Item - Edit-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-bs-toggle="modal"
                                data-bs-target="#kt_modal_edit_class" data-class-id="{{ $classname->id }}">
                                <i class="ki-outline ki-pencil fs-5 me-2"></i> Edit Class
                            </a>
                        </div>
                        <!--end::Menu Item-->

                        <!--begin::Menu Item - Toggle Status-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3 toggle-status-btn"
                                data-class-id="{{ $classname->id }}"
                                data-current-status="{{ $isActive ? 'active' : 'inactive' }}">
                                @if ($isActive)
                                    <i class="ki-outline ki-toggle-off fs-5 me-2"></i> Deactivate
                                @else
                                    <i class="ki-outline ki-toggle-on fs-5 me-2"></i> Activate
                                @endif
                            </a>
                        </div>
                        <!--end::Menu Item-->
                    @endif

                    @if ($canDeleteClass && $classname->students_count == 0)
                        <!--begin::Menu Separator-->
                        <div class="separator my-2"></div>
                        <!--end::Menu Separator-->

                        <!--begin::Menu Item - Delete-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3 text-danger class-delete-button"
                                data-class-id="{{ $classname->id }}">
                                <i class="ki-outline ki-trash fs-5 me-2"></i> Delete
                            </a>
                        </div>
                        <!--end::Menu Item-->
                    @endif
                </div>
                <!--end::Menu-->
            </div>
            <!--end::Card Toolbar-->
        </div>
        <!--end::Card Header-->

        <!--begin::Card Body-->
        <div class="card-body pt-4 pb-0">
            <!--begin::Description-->
            <p class="mb-4 text-gray-600 py-3">
                {{ $classname->description ? $classname->description : 'No description set' }}
            </p>
            <!--end::Description-->

            @if ($is_admin && $branches->count() > 0)
                <!--begin::Branch Tabs (Admin Only)-->
                <div class="batch-tabs" role="tablist">
                    <button type="button" class="batch-tab active fs-5" data-batch="all"
                        data-class-id="{{ $classname->id }}" role="tab">
                        All
                    </button>
                    @foreach ($branches as $branch)
                        <button type="button" class="batch-tab fs-5" data-batch="{{ $branch->id }}"
                            data-class-id="{{ $classname->id }}" role="tab">
                            {{ $branch->branch_name }}
                        </button>
                    @endforeach
                </div>
                <!--end::Branch Tabs-->

                <!--begin::Stats Content-->
                <div class="batch-content" id="batch-content-{{ $classname->id }}">
                    <div class="stats-grid">
                        <div class="stat-item active">
                            <div class="stat-value text-success active-count">{{ $classname->active_students_count }}
                            </div>
                            <div class="stat-label">Active</div>
                        </div>
                        <div class="stat-item inactive">
                            <div class="stat-value text-danger inactive-count">
                                {{ $classname->inactive_students_count }}</div>
                            <div class="stat-label">Inactive</div>
                        </div>
                        <div class="stat-item total">
                            <div class="stat-value text-gray-800 total-count">{{ $classname->students_count }}</div>
                            <div class="stat-label">Total</div>
                        </div>
                    </div>
                </div>
                <!--end::Stats Content-->

                <!--begin::Hidden Branch Data for JS-->
                <script type="application/json" id="branch-data-{{ $classname->id }}">@php
                    $jsonData = [
                        'all' => [
                            'active' => $classname->active_students_count,
                            'inactive' => $classname->inactive_students_count,
                            'total' => $classname->students_count
                        ]
                    ];
                    foreach ($branches as $branch) {
                        $branchData = $classname->branch_counts[$branch->id] ?? ['active' => 0, 'inactive' => 0, 'total' => 0];
                        $jsonData[$branch->id] = [
                            'active' => $branchData['active'],
                            'inactive' => $branchData['inactive'],
                            'total' => $branchData['total']
                        ];
                    }
                    echo json_encode($jsonData);
                @endphp</script>
                <!--end::Hidden Branch Data-->
            @else
                <!--begin::Stats (Non-Admin)-->
                <div class="stats-grid">
                    <div class="stat-item active">
                        <div class="stat-value text-success">{{ $classname->active_students_count }}</div>
                        <div class="stat-label">Active</div>
                    </div>
                    <div class="stat-item inactive">
                        <div class="stat-value text-danger">{{ $classname->inactive_students_count }}</div>
                        <div class="stat-label">Inactive</div>
                    </div>
                    <div class="stat-item total">
                        <div class="stat-value text-gray-800">{{ $classname->students_count }}</div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>
                <!--end::Stats-->
            @endif
        </div>
        <!--end::Card Body-->

        <!--begin::Card Footer-->
        <div class="card-footer border-top pt-4 pb-4">
            <a href="{{ route('classnames.show', $classname->id) }}"
                class="btn btn-sm btn-light-primary w-100 fw-semibold">
                <i class="ki-outline ki-eye fs-5 me-1"></i> View Details
            </a>
        </div>
        <!--end::Card Footer-->
    </div>
</div>
