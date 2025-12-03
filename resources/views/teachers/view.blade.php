@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', $teacher->name)

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            {{ $teacher->name }}
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
                    Teacher Info </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                View </li>
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
            <div class="card card-flush mb-0
            @if ($teacher->is_active === false) border border-dashed border-danger @endif"
                data-kt-sticky="true" data-kt-sticky-name="student-summary" data-kt-sticky-offset="{default: false, lg: 0}"
                data-kt-sticky-width="{lg: '250px', xl: '350px'}" data-kt-sticky-left="auto" data-kt-sticky-top="100px"
                data-kt-sticky-animation="false" data-kt-sticky-zindex="95">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>Teacher Info</h2>
                    </div>
                    <!--end::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body pt-0 fs-6">
                    <!--begin::Section-->
                    <div class="mb-7">
                        <!--begin::Details-->
                        <div class="d-flex align-items-center">
                            <!--begin::Avatar-->
                            <div class="symbol symbol-60px symbol-circle me-3">
                                <img
                                    src="{{ $teacher->photo_url ? asset($teacher->photo_url) : asset($teacher->gender == 'male' ? 'img/male-placeholder.png' : 'img/female-placeholder.png') }}" />

                            </div>
                            <!--end::Avatar-->
                            <!--begin::Info-->
                            <div class="d-flex flex-column">
                                <!--begin::Name-->
                                <span class="fs-4 fw-bold text-gray-900 me-2">{{ $teacher->name }}</span>
                                <!--end::Name-->
                                <!--begin::Student ID-->
                                <span class="fw-bold text-gray-600">{{ $teacher->phone }}</span>
                                <!--end::Student ID-->
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
                        <h5 class="mb-4">Bio
                        </h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <div class="mb-0">
                            <!--begin::Details-->
                            <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Gender:</td>
                                    <td>
                                        @if ($teacher->gender == 'male')
                                            <i class="las la-mars fs-4"></i>
                                        @elseif ($teacher->gender == 'female')
                                            <i class="las la-venus fs-4"></i>
                                        @endif

                                        {{ ucfirst($teacher->gender) }}
                                    </td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Qualification:</td>
                                    <td class="text-gray-800">{{ $teacher->academic_qualification }}</td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Experience:</td>
                                    <td class="text-gray-800">{{ $teacher->experience }}</td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Email:</td>
                                    <td>
                                        {{ $teacher->email }}
                                    </td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Blood Group:</td>
                                    <td>
                                        {{ $teacher->blood_group }}
                                    </td>
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
                    <div class="mb-7">
                        <!--begin::Title-->
                        <h5 class="mb-4">Salary Info
                        </h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <div class="mb-0">
                            <!--begin::Details-->
                            <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                                <!--begin::Row-->
                                <tr>
                                    <td class="text-gray-500">Base Salary:</td>
                                    <td>
                                        ৳ {{ $teacher->base_salary }}
                                    </td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr>
                                    <td class="text-gray-500">Total Class Taken:</td>
                                    <td>
                                        100
                                    </td>
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
                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Status:</td>
                                <td>
                                    @if ($teacher->is_active === false)
                                        <span class="badge badge-danger rounded-pill">Inactive</span>
                                    @else
                                        <span class="badge badge-success rounded-pill">Active</span>
                                    @endif

                                </td>
                            </tr>
                            <!--end::Row-->

                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Last Updated:</td>
                                <td class="text-gray-800">
                                    {{ $teacher->updated_at->diffForHumans() }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $teacher->updated_at->format('h:i:s A, d-M-Y') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>

                                </td>
                            </tr>
                            <!--end::Row-->

                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Registered Since:</td>
                                <td class="text-gray-800">
                                    {{ $teacher->created_at->diffForHumans() }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $teacher->created_at->format('h:i:s A, d-M-Y') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>

                                </td>
                            </tr>
                            <!--end::Row-->
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
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                        href="#kt_student_view_personal_info_tab"><i class="ki-outline ki-book-open fs-3 me-2"></i>Class
                        Assignments</a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                        href="#kt_student_view_transactions_tab"><i class="ki-outline ki-credit-cart fs-3 me-2"></i>Salary
                        Tracking</a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                        href="#kt_student_view_sheets_tab"><i class="ki-outline ki-some-files fs-3 me-2"></i>Exam &
                        Result</a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                        href="#kt_student_view_activity_tab"><i class="ki-outline ki-save-2 fs-3 me-2"></i>Activity</a>
                </li>
                <!--end:::Tab item-->

                @canany(['teachers.edit', 'teachers.delete', 'teachers.deactivate'])
                <!--begin:::Tab item-->
                <li class="nav-item ms-auto">
                    <!--begin::Action menu-->
                    <a href="#" class="btn btn-primary ps-7" data-kt-menu-trigger="click"
                        data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">Actions
                        <i class="ki-outline ki-down fs-2 me-0"></i></a>
                    <!--begin::Menu-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold py-4 w-250px fs-6"
                        data-kt-menu="true">
                        <!--begin::Menu item-->
                        <div class="menu-item px-5">
                            <div class="menu-content text-muted pb-2 px-5 fs-7 text-uppercase">Account</div>
                        </div>
                        <!--end::Menu item-->

                        @can('teachers.deactivate')
                            <!--begin::Menu item-->
                            <div class="menu-item px-5">
                                @if ($teacher->is_active === true)
                                    <a href="#" class="menu-link text-hover-warning px-3" data-bs-toggle="modal"
                                        data-bs-target="#kt_toggle_activation_student_modal"
                                        data-teacher-name="{{ $teacher->name }}" data-teacher-id="{{ $teacher->id }}"
                                        data-active-status="{{ $teacher->is_active }}"><i
                                            class="bi bi-person-slash fs-2 me-2"></i> Deactivate</a>
                                @else
                                    <a href="#" class="menu-link text-hover-success px" data-bs-toggle="modal"
                                        data-bs-target="#kt_toggle_activation_student_modal"
                                        data-teacher-name="{{ $teacher->name }}" data-teacher-id="{{ $teacher->id }}"
                                        data-active-status="{{ $teacher->is_active }}"><i
                                            class="bi bi-person-check fs-3 me-2"></i> Activate</a>
                                @endif
                            </div>
                            <!--end::Menu item-->
                        @endcan

                        @can('teachers.edit')
                            <!--begin::Menu item-->
                            <div class="menu-item px-5 my-1">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_teacher"
                                    data-teacher-id="{{ $teacher->id }}" class="menu-link text-hover-primary px-3"><i
                                        class="las la-pen fs-3 me-2"></i> Edit</a>
                            </div>
                            <!--end::Menu item-->
                        @endcan

                        @can('teachers.delete')
                            <!--begin::Menu item-->
                            <div class="menu-item px-5">
                                <a href="#" class="menu-link text-hover-danger px-3 delete-teacher"
                                    data-teacher-id="{{ $teacher->id }}"><i class="bi bi-trash fs-3 me-2"></i>
                                    Delete</a>
                            </div>
                            <!--end::Menu item-->
                        @endcan
                    </div>
                    <!--end::Menu-->
                    <!--end::Action Menu-->
                </li>
                <!--end:::Tab item-->
                @endcan
            </ul>
            <!--end:::Tabs-->

            <!--begin:::Tab content-->
            <div class="tab-content" id="myTabContent">

            </div>
            <!--end:::Tab content-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Layout-->


    <!--begin::Modal - Edit Teacher-->
    <div class="modal fade" id="kt_modal_edit_teacher" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-750px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold" id="kt_modal_edit_teacher_title">Update Teacher</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-edit-teachers-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_teacher_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_teacher_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_teacher_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_teacher_scroll" data-kt-scroll-offset="300px">
                            <div class="row">
                                <!--begin::Name Input group-->
                                <div class="col-lg-6">
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="required fw-semibold fs-6 mb-2">Name</label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="text" name="teacher_name_edit"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="Enter teacher name" required />
                                        <!--end::Input-->
                                    </div>
                                </div>
                                <!--end::Name Input group-->

                                <!--begin::Gender Input group-->
                                <div class="col-lg-6">
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="required fw-semibold fs-6 mb-2">Gender</label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <div class="row">
                                            <!--begin::Col-->
                                            <div class="col-lg-6">
                                                <!--begin::Option-->
                                                <input type="radio" class="btn-check" name="teacher_gender_edit"
                                                    value="male" id="gender_male_input_edit" />
                                                <label
                                                    class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                    for="gender_male_input_edit">
                                                    <i class="las la-mars fs-2x me-5"></i>
                                                    <!--begin::Info-->
                                                    <span class="d-block fw-semibold text-start">
                                                        <span class="text-gray-900 fw-bold d-block fs-6">Male</span>
                                                    </span>
                                                    <!--end::Info-->
                                                </label>
                                                <!--end::Option-->
                                            </div>
                                            <!--end::Col-->
                                            <!--begin::Col-->
                                            <div class="col-lg-6">
                                                <!--begin::Option-->
                                                <input type="radio" class="btn-check" name="teacher_gender_edit"
                                                    value="female" id="gender_female_input_edit" />
                                                <label
                                                    class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                    for="gender_female_input_edit">
                                                    <i class="las la-venus fs-2x me-5"></i>
                                                    <!--begin::Info-->
                                                    <span class="d-block fw-semibold text-start">
                                                        <span class="text-gray-900 fw-bold d-block fs-6">Female</span>
                                                    </span>
                                                    <!--end::Info-->
                                                </label>
                                                <!--end::Option-->
                                            </div>
                                            <!--end::Col-->
                                        </div>
                                        <!--end::Input-->
                                    </div>
                                </div>
                                <!--end::Gender Input group-->

                                <!--begin::Salary Input group-->
                                <div class="col-lg-6">
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="fw-semibold fs-6 mb-2 required">Base Salary (tk)</label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="number" name="teacher_salary_edit" min="100"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="Enter base salary" required />
                                        <!--end::Input-->
                                    </div>
                                </div>
                                <!--end::Salary Input group-->

                                <!--begin::Phone Input group-->
                                <div class="col-lg-6">
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="fw-semibold fs-6 mb-2 required">Phone</label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="text" name="teacher_phone_edit"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="Enter phone number" required />
                                        <!--end::Input-->
                                    </div>
                                </div>
                                <!--end::Phone Input group-->

                                <!--begin::Email Input group-->
                                <div class="col-lg-6">
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="fw-semibold fs-6 mb-2 required">Email</label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="email" name="teacher_email_edit"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="Enter email number" required />
                                        <!--end::Input-->
                                    </div>
                                </div>
                                <!--end::Email Input group-->

                                <!--begin::Blood Group Input-->
                                <div class="col-lg-6">
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="fw-semibold fs-6 mb-2">Blood Group <span
                                                class="text-muted">(optional)</span></label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <div class="input-group input-group-solid flex-nowrap">
                                            <span class="input-group-text">
                                                <i class="las la-tint fs-3"></i>
                                            </span>
                                            <div class="overflow-hidden flex-grow-1">
                                                <select name="teacher_blood_group_edit"
                                                    class="form-select form-select-solid rounded-start-0 border-start"
                                                    data-control="select2" data-placeholder="Select an option"
                                                    data-hide-search="true">
                                                    <option></option>
                                                    <option value="A+">A+</option>
                                                    <option value="B+">B+</option>
                                                    <option value="AB+">AB+</option>
                                                    <option value="O+">O+</option>
                                                    <option value="A-">A-</option>
                                                    <option value="B-">B-</option>
                                                    <option value="AB-">AB-</option>
                                                    <option value="O-">O-</option>
                                                </select>
                                            </div>
                                        </div>
                                        <!--end::Input-->
                                    </div>
                                </div>
                                <!--end::Blood Group Input-->

                                <!--begin::Qualification Input group-->
                                <div class="col-lg-12">
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="fw-semibold fs-6 mb-2">Academic Qualification <span
                                                class="text-muted">(optional)</span></label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="text" name="teacher_qualification_edit"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="Enter academic qualification information" />
                                        <!--end::Input-->
                                    </div>
                                </div>
                                <!--end::Qualification Input group-->

                                <!--begin::Experience Input group-->
                                <div class="col-lg-12">
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="fw-semibold fs-6 mb-2">Teaching Experience <span
                                                class="text-muted">(optional)</span></label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="text" name="teacher_experience_edit"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="Enter teaching experience" />
                                        <!--end::Input-->
                                    </div>
                                </div>
                                <!--end::Experience Input group-->
                            </div>
                        </div>
                        <!--end::Scroll-->

                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-edit-teachers-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-edit-teachers-modal-action="submit">
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
    <!--end::Modal - Edit Teacher-->
@endsection



@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush


@push('page-js')
    <script>
        const routeDeleteTeacher = "{{ route('teachers.destroy', ':id') }}";
        const routeToggleActive = "{{ route('teachers.toggleActive', ':id') }}";
    </script>

    <script src="{{ asset('js/teachers/view.js') }}"></script>

    <script>
        document.getElementById("teachers_menu").classList.add("here", "show");
        document.getElementById("teachers_link").classList.add("active");
    </script>
@endpush
