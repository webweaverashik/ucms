@push('page-css')
    <link href="{{ asset('css/classnames/view.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')
@section('title', 'Class - ' . $classname->name)

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            {{ $classname->name }} &nbsp;<i class="text-muted"> ({{ $classname->class_numeral }})</i>
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">
                    Academic
                </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Class
            </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection

@section('content')
    @php
        $manageSubjects = auth()->user()->can('subjects.manage');
        $groupedSubjects = $classname->subjects->groupBy('academic_group');
        $totalSubjects = $classname->subjects->count();
    @endphp

    <!--begin::Layout-->
    <div class="d-flex flex-column flex-xl-row">
        <!--begin::Sidebar-->
        <div class="flex-column flex-lg-row-auto w-100 w-xl-350px mb-10">
            <!--begin::Card-->
            <div class="card card-flush mb-0 @if (! $classname->isActive()) border border-dashed border-danger @endif"
                data-kt-sticky="true" data-kt-sticky-name="student-summary" data-kt-sticky-offset="{default: false, lg: 0}"
                data-kt-sticky-width="{lg: '250px', xl: '350px'}" data-kt-sticky-left="auto" data-kt-sticky-top="100px"
                data-kt-sticky-animation="false" data-kt-sticky-zindex="95">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h3 class="text-gray-600">Class Info</h3>
                    </div>
                    <!--end::Card title-->
                    @can('classes.edit')
                        @if ($classname->isActive())
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <!--begin::More options-->
                                <a href="#" class="btn btn-sm btn-light btn-icon" data-kt-menu-trigger="click"
                                    data-kt-menu-placement="bottom-end">
                                    <i class="ki-outline ki-dots-horizontal fs-3"> </i>
                                </a>
                                <!--begin::Menu-->
                                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-6 w-175px py-4"
                                    data-kt-menu="true">
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_class"
                                            data-class-id="{{ $classname->id }}" class="menu-link text-hover-primary px-3 "><i
                                                class="las la-pen fs-3 me-2"></i>
                                            Edit Class</a>
                                    </div>
                                    <!--end::Menu item-->
                                </div>
                                <!--end::Menu-->
                                <!--end::More options-->
                            </div>
                            <!--end::Card toolbar-->
                        @endif
                    @endcan
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-0 fs-6">
                    <!--begin::Section-->
                    <div class="mb-7">
                        <!--begin::Details-->
                        <div class="d-flex flex-column">
                            <!--begin::Info-->
                            <div class="d-flex flex-column mb-3">
                                <!--begin::Name-->
                                <span class="fs-1 fw-bold text-gray-900 me-2">{{ $classname->name }} <i
                                        class="text-muted">({{ $classname->class_numeral }})</i></span>
                                <!--end::Name-->
                            </div>
                            <!--end::Info-->
                            <!--begin::Info-->
                            <div class="d-flex flex-column">
                                <!--begin::Name-->
                                <span
                                    class="fs-6 text-gray-600 me-2">{{ $classname->description ?? 'This is a sample description. Update the class description to change this.' }}</span>
                                <!--end::Name-->
                            </div>
                            <!--end::Info-->
                        </div>
                        <!--end::Details-->
                    </div>
                    <!--end::Section-->

                    <!--begin::Seperator-->
                    <div class="separator separator-dashed mb-7"></div>
                    <!--end::Seperator-->

                    <!--begin::Section-->
                    <div class="mb-7">
                        <!--begin::Title-->
                        <h5 class="mb-4">Statistics</h5>
                        <!--end::Title-->
                        <!--begin::Stats Grid-->
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-success">{{ $classname->active_students_count }}</div>
                                    <div class="stats-label">Active Students</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-danger">{{ $classname->inactive_students_count }}</div>
                                    <div class="stats-label">Inactive Students</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-primary">{{ $totalSubjects }}</div>
                                    <div class="stats-label">Total Subjects</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-info">{{ $groupedSubjects->count() }}</div>
                                    <div class="stats-label">Groups</div>
                                </div>
                            </div>
                        </div>
                        <!--end::Stats Grid-->
                    </div>
                    <!--end::Section-->

                    <!--begin::Seperator-->
                    <div class="separator separator-dashed mb-7"></div>
                    <!--end::Seperator-->

                    <!--begin::Section-->
                    <div class="mb-0">
                        <!--begin::Title-->
                        <h5 class="mb-4">Activation Details</h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                            <tr class="">
                                <td class="text-gray-500">Status:</td>
                                <td class="text-gray-800">
                                    @if ($classname->isActive())
                                        <span class="badge badge-success rounded-pill">Active</span>
                                    @else
                                        <span class="badge badge-danger rounded-pill">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            <tr class="">
                                <td class="text-gray-500">Created Since:</td>
                                <td class="text-gray-800">
                                    {{ $classname->created_at->diffForHumans() }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $classname->created_at->format('d-M-Y h:m:s A') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>
                                </td>
                            </tr>
                            <tr class="">
                                <td class="text-gray-500">Updated Since:</td>
                                <td class="text-gray-800">
                                    {{ $classname->updated_at->diffForHumans() }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $classname->updated_at->format('d-M-Y h:m:s A') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>
                                </td>
                            </tr>
                        </table>
                        <!--end::Details-->
                    </div>
                    <!--end::Section-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Sidebar-->

        <!--begin::Content-->
        <div class="flex-lg-row-fluid ms-lg-10">
            <!--begin:::Tabs-->
            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_subjects_tab"><i
                            class="ki-outline ki-book-open fs-3 me-2"></i>Subjects</a>
                </li>
                <!--end:::Tab item-->
                @if ($manageSubjects)
                    @if ($classname->isActive())
                        <!--begin:::Tab item-->
                        <li class="nav-item ms-auto">
                            <!--begin::Action menu-->
                            <a href="#" class="btn btn-primary ps-7" data-bs-toggle="modal"
                                data-bs-target="#kt_modal_add_subject"><i class="ki-outline ki-plus fs-2 me-0"></i>New
                                Subject </a>
                            <!--end::Action Menu-->
                        </li>
                        <!--end:::Tab item-->
                    @endif
                @endif
            </ul>
            <!--end:::Tabs-->

            <!--begin:::Tab content-->
            <div class="tab-content" id="myTabContent">
                <!--begin:::Tab pane-->
                <div class="tab-pane fade show active" id="kt_subjects_tab" role="tabpanel">
                    <!--begin::Card-->
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <!--begin::Card header-->
                        <div class="card-header border-0">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <h2>Subjects</h2>
                            </div>
                            <!--end::Card title-->
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge badge-light-primary fs-7">
                                        <i class="ki-outline ki-book fs-6 me-1"></i>
                                        {{ $totalSubjects }} Subjects
                                    </span>
                                </div>
                            </div>
                            <!--end::Card toolbar-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body py-4">
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
                                                    <div class="subject-card subject-editable"
                                                        data-id="{{ $subject->id }}">
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
                                                                    <span
                                                                        class="subject-title subject-text fs-6 d-block text-truncate">
                                                                        {{ $subject->name }}
                                                                    </span>
                                                                    <input type="text"
                                                                        class="subject-input form-control form-control-sm d-none fs-6"
                                                                        value="{{ $subject->name }}" />
                                                                    <span class="text-muted fs-8">
                                                                        <i class="ki-outline ki-people fs-8 me-1"></i>
                                                                        {{ $subject->students->count() }} students enrolled
                                                                    </span>
                                                                </div>
                                                            </div>

                                                            @if ($manageSubjects && $classname->isActive())
                                                                <!--begin::Actions-->
                                                                <div
                                                                    class="subject-actions d-flex align-items-center gap-1">
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
                                                                    @if ($subject->students->count() == 0)
                                                                        <button type="button"
                                                                            class="btn btn-icon btn-sm action-delete delete-subject"
                                                                            data-subject-id="{{ $subject->id }}"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Delete Subject">
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
                <!--end:::Tab pane-->
            </div>
            <!--end:::Tab content-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Layout-->

    <!--begin::Modal - Add Subject-->
    <div class="modal fade" id="kt_modal_add_subject" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_add_subject_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Create a new subject</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-add-subject-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"> </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-5">
                    <!--begin::Form-->
                    <form id="kt_modal_add_subject_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_subject_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_add_subject_header"
                            data-kt-scroll-wrappers="#kt_modal_add_subject_scroll" data-kt-scroll-offset="300px">
                            {{-- Hidden Input --}}
                            <input type="hidden" name="subject_class" value="{{ $classname->id }}" />
                            <!--begin::Subject name Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Subject Name</label>
                                <!-- end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="subject_name"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. Physics"
                                    required />
                                <!--end::Input-->
                            </div>
                            <!--end::Subject name Input group-->
                            <!--begin::Group Input-->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Academic Group</label>
                                <select name="subject_group" class="form-select form-select-solid"
                                    data-dropdown-parent="#kt_modal_add_subject" data-control="select2"
                                    data-hide-search="true" data-placeholder="Select group" required>
                                    <option></option>
                                    <option value="General" selected>General</option>
                                    @if ((int) $classname->class_numeral >= 9)
                                        <option value="Science">Science</option>
                                        <option value="Commerce">Commerce</option>
                                        {{-- <option value="Arts">Arts</option> --}}
                                    @endif
                                </select>
                            </div>
                            <!--end::Group Input-->
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-add-subject-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-add-subject-modal-action="submit">
                                <span class="indicator-label">Submit</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                        <!--end::Actions-->
                    </form>
                    <!--end::Form-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - Add Subject-->

    <!--begin::Modal - Edit class-->
    <div class="modal fade" id="kt_modal_edit_class" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_edit_class_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold" id="kt_modal_edit_class_title">Edit Class</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-edit-class-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"> </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_class_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_class_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_class_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_class_scroll" data-kt-scroll-offset="300px">
                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Class Name</label>
                                <input type="text" name="class_name_edit"
                                    class="form-control form-control-solid mb-3 mb-lg-0"
                                    placeholder="Write name of the class" required />
                            </div>
                            <!--end::Name Input group-->
                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Class Numeral <span class="text-muted">(Cannot
                                        change)</span></label>
                                <select name="class_numeral_edit" class="form-select form-select-solid"
                                    data-control="select2" data-hide-search="true"
                                    data-dropdown-parent="#kt_modal_edit_class" data-placeholder="Select numeral"
                                    disabled>
                                    <option></option>
                                    @for ($i = 12; $i >= 4; $i--)
                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">
                                            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <!--end::Name Input group-->

                            <input type="hidden" name="activation_status" value="{{ $classname->isActive() ? 'active' : 'inactive' }}" />

                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Description <span
                                        class="text-muted">(Optional)</span></label>
                                <input type="text" name="description_edit"
                                    class="form-control form-control-solid mb-3 mb-lg-0"
                                    placeholder="Write something about the class" />
                            </div>
                            <!--end::Name Input group-->
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-edit-class-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-edit-class-modal-action="submit">
                                <span class="indicator-label">Update</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                        <!--end::Actions-->
                    </form>
                    <!--end::Form-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - Edit class-->
@endsection

@push('vendor-js')
@endpush

@push('page-js')
    <script>
        const routeDeleteSubject = "{{ route('subjects.destroy', ':id') }}";
    </script>
    <script src="{{ asset('js/classnames/view.js') }}"></script>
    <script>
        $('select[data-control="select2"]').select2({
            width: 'resolve'
        });
    </script>
    <script>
        document.getElementById("academic_menu").classList.add("here", "show");
        document.getElementById("class_link").classList.add("active");
    </script>
@endpush