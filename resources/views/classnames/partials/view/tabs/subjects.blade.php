<div class="tab-pane fade show active" id="kt_subjects_tab" role="tabpanel">
    <!--begin::Card-->
    <div class="card mb-5 mb-xl-10">
        <!--begin::Card header-->
        <div class="card-header m-0">
            <!--begin::Card title-->
            <div class="card-title m-0">
                <h3>Subjects</h3>
            </div>
            <!--end::Card title-->
            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-light-primary fs-7" id="subjects-count-badge">
                        <i class="ki-outline ki-book fs-6 me-1"></i>
                        <span id="subjects-count-text">{{ $totalSubjects }}</span>&nbsp; Subjects
                    </span>
                </div>
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body py-4" id="subjects-container">
            @forelse ($groupedSubjects as $group => $subjects)
                <!--begin::Academic Group Section-->
                <div class="academic-group-section">
                    <!--begin::Group Header-->
                    <div class="group-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            @php
                                $groupIcon = match ($group) {
                                    'Science' => 'ki-flask',
                                    'Commerce' => 'ki-chart-line-up',
                                    'Arts' => 'ki-paintbucket',
                                    default => 'ki-abstract-26',
                                };
                            @endphp
                            <i class="ki-outline {{ $groupIcon }} fs-3 me-2 text-white"></i>
                            <h5 class="mb-0 fw-bold">{{ $group ?? 'General' }} Group</h5>
                        </div>
                        <span class="subjects-count fs-7 fw-semibold">
                            <i class="ki-outline ki-book-open fs-6 me-1"></i>
                            {{ $subjects->count() }} subjects
                        </span>
                    </div>
                    <!--end::Group Header-->
                    <!--begin::Subjects Grid-->
                    <div class="p-4">
                        <div class="row g-4">
                            @foreach ($subjects as $subject)
                                <div class="col-md-6 col-xl-4">
                                    <!--begin::Subject Card-->
                                    <div class="subject-card subject-editable" data-id="{{ $subject->id }}">
                                        <!--begin::Subject Content-->
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div class="d-flex align-items-center flex-grow-1 me-2">
                                                @php
                                                    $iconClass = strtolower($group ?? 'general');
                                                    $subjectIcon = match ($group) {
                                                        'Science' => 'ki-flask',
                                                        'Commerce' => 'ki-chart-pie-simple',
                                                        'Arts' => 'ki-brush',
                                                        default => 'ki-book',
                                                    };
                                                @endphp
                                                <div class="subject-icon {{ $iconClass }} me-3">
                                                    <i class="ki-outline {{ $subjectIcon }}"></i>
                                                </div>
                                                <div class="flex-grow-1 min-w-0">
                                                    <span class="subject-title subject-text fs-6 d-block text-truncate">
                                                        {{ $subject->name }}
                                                    </span>
                                                    <input type="text"
                                                        class="subject-input form-control form-control-sm d-none fs-6"
                                                        value="{{ $subject->name }}" />
                                                    <span class="text-muted fs-8">
                                                        <i class="ki-outline ki-people fs-8 me-1"></i>
                                                        {{ $subject->students_count }} students
                                                        enrolled
                                                    </span>
                                                </div>
                                            </div>
                                            @if ($manageSubjects && $classname->isActive())
                                                <!--begin::Actions-->
                                                <div class="subject-actions d-flex align-items-center gap-1">
                                                    <!--begin::Edit Mode Actions (Hidden by default)-->
                                                    <button type="button"
                                                        class="btn btn-icon btn-sm action-save check-icon d-none"
                                                        data-bs-toggle="tooltip" title="Save">
                                                        <i class="ki-outline ki-check fs-4"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-icon btn-sm action-cancel cancel-icon d-none"
                                                        data-bs-toggle="tooltip" title="Cancel">
                                                        <i class="ki-outline ki-cross fs-4"></i>
                                                    </button>
                                                    <!--end::Edit Mode Actions-->
                                                    <!--begin::View Mode Actions-->
                                                    <button type="button"
                                                        class="btn btn-icon btn-sm action-edit edit-icon"
                                                        data-bs-toggle="tooltip" title="Edit Subject">
                                                        <i class="ki-outline ki-pencil fs-5"></i>
                                                    </button>
                                                    @if ($subject->students_count == 0)
                                                        <button type="button"
                                                            class="btn btn-icon btn-sm action-delete delete-subject"
                                                            data-subject-id="{{ $subject->id }}"
                                                            data-bs-toggle="tooltip" title="Delete Subject">
                                                            <i class="ki-outline ki-trash fs-5"></i>
                                                        </button>
                                                    @endif
                                                    <!--end::View Mode Actions-->
                                                </div>
                                                <!--end::Actions-->
                                            @endif
                                        </div>
                                        <!--end::Subject Content-->
                                    </div>
                                    <!--end::Subject Card-->
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <!--end::Subjects Grid-->
                </div>
                <!--end::Academic Group Section-->
            @empty
                <!--begin::Empty State-->
                <div class="text-center py-15">
                    <div class="empty-state-icon">
                        <i class="ki-outline ki-book-open"></i>
                    </div>
                    <h4 class="text-gray-800 fw-bold mb-3">No Subjects Added Yet</h4>
                    <p class="text-muted fs-6 mb-6 mw-400px mx-auto">
                        Start by adding your first subject for this class. Subjects help organize the
                        curriculum for students.
                    </p>
                    @if ($manageSubjects && $classname->isActive())
                        <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#kt_modal_add_subject">
                            <i class="ki-outline ki-plus fs-3 me-1"></i> Add First Subject
                        </a>
                    @endif
                </div>
                <!--end::Empty State-->
            @endforelse
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->
</div>