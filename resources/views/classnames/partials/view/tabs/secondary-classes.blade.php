<div class="tab-pane fade" id="kt_secondary_classnames_tab" role="tabpanel">
    <!--begin::Card-->
    <div class="card mb-6 mb-xl-9">
        <!--begin::Header-->
        <div class="card-header">
            <!--begin::Title-->
            <div class="card-title">
                <h3>Special Classes</h3>
            </div>
            <!--end::Title-->
            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <span class="badge badge-light-info fs-7">
                    <i class="ki-outline ki-abstract-26 fs-6 me-1"></i>
                    {{ $classname->secondaryClasses->count() }} Classes
                </span>
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Header-->
        <!--begin::Card body-->
        <div class="card-body pb-5">
            <!--begin::Secondary Classes Grid-->
            <div class="row g-4" id="secondary-classes-container">
                @forelse ($classname->secondaryClasses as $secondaryClass)
                    <!--begin::Secondary Class Card-->
                    <div class="col-md-6" data-secondary-class-id="{{ $secondaryClass->id }}">
                        <div
                            class="secondary-class-card @if (!$secondaryClass->is_active) inactive @endif">
                            <!--begin::Card Header-->
                            <div class="secondary-class-header">
                                <div class="d-flex align-items-center">
                                    <div class="secondary-class-icon">
                                        <i class="ki-outline ki-abstract-26"></i>
                                    </div>
                                    <div class="ms-3">
                                        <a href="{{ route('classnames.secondary-classes.show', [$classname->id, $secondaryClass->id]) }}"
                                            class="secondary-class-title mb-0 text-gray-900 text-hover-primary fw-bold fs-5 text-decoration-none">
                                            {{ $secondaryClass->name }}
                                        </a>
                                        <span class="text-muted fs-7 d-block">
                                            {{ ucwords(str_replace('_', ' ', $secondaryClass->payment_type)) }}
                                        </span>
                                    </div>
                                </div>
                                @if (auth()->user()->isAdmin())
                                    <div class="secondary-class-actions">
                                        <div
                                            class="form-check form-switch form-check-solid form-check-success">
                                            <input class="form-check-input toggle-secondary-activation"
                                                type="checkbox" value="{{ $secondaryClass->id }}"
                                                data-secondary-class-id="{{ $secondaryClass->id }}"
                                                data-bs-toggle="tooltip" title="Change status"
                                                @if ($secondaryClass->is_active) checked @endif>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <!--end::Card Header-->
                            <!--begin::Card Body-->
                            <div class="secondary-class-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="secondary-class-stat">
                                        <span class="stat-label">Fee Amount</span>
                                        <span
                                            class="stat-value text-primary">৳{{ number_format($secondaryClass->fee_amount, 0) }}</span>
                                    </div>
                                    <div class="secondary-class-stat text-end">
                                        <span class="stat-label">Students</span>
                                        <a href="{{ route('classnames.secondary-classes.show', [$classname->id, $secondaryClass->id]) }}"
                                            class="stat-value text-info text-hover-primary text-decoration-none">
                                            {{ $secondaryClass->students_count }}
                                        </a>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <span
                                        class="badge @if ($secondaryClass->is_active) badge-light-success @else badge-light-danger @endif">
                                        <i
                                            class="ki-outline @if ($secondaryClass->is_active) ki-check-circle @else ki-cross-circle @endif fs-6 me-1"></i>
                                        {{ $secondaryClass->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="{{ route('classnames.secondary-classes.show', [$classname->id, $secondaryClass->id]) }}"
                                            class="btn btn-sm btn-light-info btn-icon"
                                            data-bs-toggle="tooltip" title="View Details">
                                            <i class="ki-outline ki-eye fs-5"></i>
                                        </a>
                                        @if (auth()->user()->isAdmin())
                                            <div class="btn-group">
                                                <button type="button"
                                                    class="btn btn-sm btn-light-primary edit-secondary-class @if (!$secondaryClass->is_active) disabled @endif"
                                                    data-secondary-class-id="{{ $secondaryClass->id }}"
                                                    data-is-active="{{ $secondaryClass->is_active ? '1' : '0' }}"
                                                    data-bs-toggle="tooltip"
                                                    title="{{ $secondaryClass->is_active ? 'Edit' : 'Activate first to edit' }}">
                                                    <i class="ki-outline ki-pencil fs-5"></i>
                                                </button>
                                                @if ($secondaryClass->students_count == 0)
                                                    <button type="button"
                                                        class="btn btn-sm btn-light-danger delete-secondary-class @if (!$secondaryClass->is_active) disabled @endif"
                                                        data-secondary-class-id="{{ $secondaryClass->id }}"
                                                        data-is-active="{{ $secondaryClass->is_active ? '1' : '0' }}"
                                                        data-bs-toggle="tooltip"
                                                        title="{{ $secondaryClass->is_active ? 'Delete' : 'Activate first to delete' }}">
                                                        <i class="ki-outline ki-trash fs-5"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <!--end::Card Body-->
                        </div>
                    </div>
                    <!--end::Secondary Class Card-->
                @empty
                    <!--begin::Empty State-->
                    <div class="col-12">
                        <div class="text-center py-15" id="secondary-classes-empty">
                            <div class="empty-state-icon">
                                <i class="ki-outline ki-abstract-26"></i>
                            </div>
                            <h4 class="text-gray-800 fw-bold mb-3">No Special Classes Yet</h4>
                            <p class="text-muted fs-6 mb-6 mw-400px mx-auto">
                                Create special classes for additional courses or programs under this
                                class.
                            </p>
                            @if (auth()->user()->isAdmin())
                                <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#kt_modal_add_special_class">
                                    <i class="ki-outline ki-plus fs-3 me-1"></i> Add First Special Class
                                </a>
                            @endif
                        </div>
                    </div>
                    <!--end::Empty State-->
                @endforelse
            </div>
            <!--end::Secondary Classes Grid-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->
</div>
