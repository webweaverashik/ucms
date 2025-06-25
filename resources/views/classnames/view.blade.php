@push('page-css')
@endpush


@extends('layouts.app')

@section('title', 'Class - ' . $classname->name)

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            {{ $classname->name }} ({{ $classname->class_numeral }})
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="?page=index" class="text-muted text-hover-primary">
                    Academic </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Class </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin::Layout-->
    <div class="d-flex flex-column flex-xl-row">
        <!--begin::Sidebar-->
        <div class="flex-column flex-lg-row-auto w-100 w-xl-350px mb-10">
            <!--begin::Card-->
            <div class="card card-flush mb-0" data-kt-sticky="true" data-kt-sticky-name="student-summary"
                data-kt-sticky-offset="{default: false, lg: 0}" data-kt-sticky-width="{lg: '250px', xl: '350px'}"
                data-kt-sticky-left="auto" data-kt-sticky-top="100px" data-kt-sticky-animation="false"
                data-kt-sticky-zindex="95">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h3 class="text-gray-600">Class Info</h3>
                    </div>
                    <!--end::Card title-->

                    @can('classes.manage')
                        <!--begin::Card toolbar-->
                        <div class="card-toolbar">
                            <!--begin::More options-->
                            <a href="#" class="btn btn-sm btn-light btn-icon" data-kt-menu-trigger="click"
                                data-kt-menu-placement="bottom-end">
                                <i class="ki-outline ki-dots-horizontal fs-3">
                                </i>
                            </a>
                            <!--begin::Menu-->
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-6 w-175px py-4"
                                data-kt-menu="true">
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_class"
                                        data-class-id="{{ $classname->id }}" class="menu-link text-hover-primary px-3 "><i
                                            class="las la-pen fs-3 me-2"></i> Edit
                                        Class</a>
                                </div>
                                <!--end::Menu item-->
                            </div>
                            <!--end::Menu-->
                            <!--end::More options-->
                        </div>
                        <!--end::Card toolbar-->
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
                                <span class="fs-1 fw-bold text-gray-900 me-2">{{ $classname->name }}
                                    ({{ $classname->class_numeral }})</span>
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
                        <h5 class="mb-4">Student Count
                        </h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <div class="mb-0">
                            <!--begin::Details-->
                            <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Active Student:</td>
                                    <td class="text-gray-800">{{ $classname->activeStudents->count() }}</td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Deactive Student:</td>
                                    <td class="text-gray-800">{{ $classname->inactiveStudents->count() }}</td>
                                </tr>
                                <!--end::Row-->

                            </table>
                            <!--end::Details-->
                        </div>
                        <!--end::Details-->
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

                @can('subjects.manage')
                    <!--begin:::Tab item-->
                    <li class="nav-item ms-auto">
                        <!--begin::Action menu-->
                        <a href="#" class="btn btn-primary ps-7" data-bs-toggle="modal"
                            data-bs-target="#kt_modal_add_subject"><i class="ki-outline ki-plus fs-2 me-0"></i>New Subject
                        </a>
                        <!--end::Action Menu-->
                    </li>
                    <!--end:::Tab item-->
                @endcan
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
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body py-0">
                            <!--begin::Table wrapper-->
                            <div class="row">
                                @php
                                    $groupedSubjects = $classname->subjects->groupBy('academic_group');
                                @endphp

                                @foreach ($groupedSubjects as $group => $subjects)
                                    <div class="col-12 mb-4">
                                        <h5 class="fw-bold">
                                            <i class="bi bi-check2-circle text-success me-1 fs-4"></i>
                                            {{ $group ?? 'General' }} Group
                                        </h5>
                                        <div class="row">
                                            @foreach ($subjects as $subject)
                                                <div class="col-md-6 col-xxl-4 mb-3">
                                                    <div class="subject-editable py-2 px-3" data-id="{{ $subject->id }}">
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-dot fs-2 text-info me-2"></i>
                                                            <div class="flex-grow-1">
                                                                <span class="subject-text text-gray-700 fs-6" title="{{ $subject->students->count() }} students enrolled this subject" data-bs-toggle="tooltip">
                                                                    {{ $subject->name }}
                                                                    ({{ $subject->students->count() }})
                                                                </span>
                                                                <input type="text"
                                                                    class="subject-input form-control form-control-sm d-none fs-6"
                                                                    value="{{ $subject->name }}" />
                                                            </div>
                                                            @can('subjects.manage')
                                                                <div class="action-icons ms-2 d-flex align-items-center">
                                                                    <i class="ki-outline ki-pencil fs-3 text-muted edit-icon text-hover-primary"
                                                                        role="button" data-bs-toggle="tooltip"
                                                                        title="Edit"></i>
                                                                    @if ($subject->students->count() == 0)
                                                                        <i class="ki-outline ki-trash fs-3 text-muted delete-subject text-hover-danger ms-3"
                                                                            role="button"
                                                                            data-subject-id="{{ $subject->id }}"
                                                                            data-bs-toggle="tooltip" title="Delete"></i>
                                                                    @else
                                                                        <span class="delete-subject"></span>
                                                                    @endif
                                                                    <i class="bi bi-check-circle fs-3 text-success check-icon d-none"
                                                                        role="button" data-bs-toggle="tooltip"
                                                                        title="Save"></i>
                                                                    <i class="bi bi-x-circle fs-3 text-danger cancel-icon d-none ms-2"
                                                                        role="button" data-bs-toggle="tooltip"
                                                                        title="Cancel"></i>
                                                                </div>
                                                            @endcan
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <!--end::Table wrapper-->
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
                        <i class="ki-outline ki-cross fs-1">
                        </i>
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
                        <i class="ki-outline ki-cross fs-1">
                        </i>
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
