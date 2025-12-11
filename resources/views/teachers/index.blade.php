@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'All Teachers')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Teachers
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
                Teachers </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <div class="container-xxl">
        <!--begin::Card-->
        <div class="card">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">
                <!--begin::Card title-->
                <div class="card-title">
                    <!--begin::Search-->
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text"
                            data-teachers-table-filter="search" class="form-control form-control-solid w-350px ps-12"
                            placeholder="Search in teachers">
                    </div>
                    <!--end::Search-->

                    <!--begin::Export hidden buttons-->
                    <div id="kt_hidden_export_buttons" class="d-none"></div>
                    <!--end::Export buttons-->

                </div>
                <!--begin::Card title-->

                <!--begin::Card toolbar-->
                <div class="card-toolbar">
                    <!--begin::Toolbar-->
                    <div class="d-flex justify-content-end" data-teachers-table-filter="base">
                        <!--begin::Export dropdown-->
                        <div class="dropdown">
                            <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                                data-kt-menu-placement="bottom-end">
                                <i class="ki-outline ki-exit-up fs-2"></i>Export
                            </button>

                            <!--begin::Menu-->
                            <div id="kt_table_report_dropdown_menu"
                                class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                                data-kt-menu="true">
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3" data-row-export="copy">Copy to
                                        clipboard</a>
                                </div>
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3" data-row-export="excel">Export as Excel</a>
                                </div>
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3" data-row-export="csv">Export as CSV</a>
                                </div>
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link px-3" data-row-export="pdf">Export as PDF</a>
                                </div>
                                <!--end::Menu item-->
                            </div>
                            <!--end::Menu-->
                        </div>
                        <!--end::Export dropdown-->

                        @can('teachers.create')
                            <!--begin::Add Teacher-->
                            <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#kt_modal_add_teacher">
                                <i class="ki-outline ki-plus fs-2"></i>New Teacher</a>
                            <!--end::Add Teacher-->
                        @endcan
                        <!--end::Filter-->
                    </div>
                    <!--end::Toolbar-->
                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->

            <!--begin::Card body-->
            <div class="card-body py-4">
                <!--begin::Table-->
                <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table" id="kt_teachers_table">
                    <thead>
                        <tr class="fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-25px">SL</th>
                            <th class="">Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th class="w-100px">Base Salary (Tk)</th>
                            <th class="w-200px">Active/Inactive</th>
                            <th class="not-export">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                        @foreach ($teachers as $teacher)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>
                                    <a href="{{ route('teachers.show', $teacher->id) }}">
                                        {{ $teacher->name }}
                                    </a>
                                </td>

                                <td>{{ $teacher->email }}</td>
                                <td>{{ $teacher->phone }}</td>
                                <td>{{ $teacher->base_salary }}</td>
                                <td>
                                    <div
                                        class="form-check form-switch form-check-solid form-check-success d-flex justify-content-center">
                                        <input class="form-check-input toggle-active" type="checkbox"
                                            value="{{ $teacher->id }}" @if ($teacher->is_active == 1) checked @endif
                                            @cannot('teachers.edit') disabled @endcan>
                                    </div>
                                </td>
                                <td>
                                    @can('teachers.edit')
                                        <a href="#" title="Edit Teacher" data-bs-toggle="modal"
                                            data-bs-target="#kt_modal_edit_teacher"
                                            class="btn btn-icon text-hover-primary w-30px h-30px edit-teacher me-2"
                                            data-teacher-id={{ $teacher->id }}>
                                            <i class="ki-outline ki-pencil fs-2"></i>
                                        </a>
                                    @endcan

                                    <a href="#" title="Reset Passsword" data-bs-toggle="modal"
                                        data-bs-target="#kt_modal_edit_password" data-teacher-id="{{ $teacher->id }}"
                                        data-teacher-name="{{ $teacher->name }}"
                                        class="btn btn-icon text-hover-primary w-30px h-30px change-password-btn">
                                        <i class="ki-outline ki-key fs-2"></i>
                                    </a>

                                    @can('teachers.delete')
                                        <a href="#" title="Delete Teacher"
                                            class="btn btn-icon text-hover-danger w-30px h-30px delete-teacher"
                                            data-teacher-id={{ $teacher->id }}>
                                            <i class="ki-outline ki-trash fs-2"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>

    <!--begin::Modal - Add Teacher-->
    <div class="modal fade" id="kt_modal_add_teacher" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-750px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_add_teacher_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Add Teacher</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-add-teacher-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_add_teacher_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_teacher_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_teacher_header"
                            data-kt-scroll-wrappers="#kt_modal_add_teacher_scroll" data-kt-scroll-offset="300px">
                            <div class="row">
                                <!--begin::Name Input group-->
                                <div class="col-lg-6">
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="required fw-semibold fs-6 mb-2">Name</label>
                                        <!--end::Label-->
                                        <!--begin::Input-->
                                        <input type="text" name="teacher_name"
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
                                                <input type="radio" class="btn-check" name="teacher_gender"
                                                    value="male" checked="checked" id="gender_male_input" />
                                                <label
                                                    class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                    for="gender_male_input">
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
                                                <input type="radio" class="btn-check" name="teacher_gender"
                                                    value="female" id="gender_female_input" />
                                                <label
                                                    class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                    for="gender_female_input">
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
                                        <input type="number" name="teacher_salary" min="100"
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
                                        <input type="text" name="teacher_phone"
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
                                        <input type="email" name="teacher_email"
                                            class="form-control form-control-solid mb-3 mb-lg-0"
                                            placeholder="Enter email address" required />
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
                                                <select name="teacher_blood_group"
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
                                        <input type="text" name="teacher_qualification"
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
                                        <input type="text" name="teacher_experience"
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
                                data-kt-add-teacher-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-add-teacher-modal-action="submit">
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
    <!--end::Modal - Add Teacher-->


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


    <!--begin::Modal - Edit Teacher Password-->
    <div class="modal fade" id="kt_modal_edit_password" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-450px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_edit_password_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold" id="kt_modal_edit_password_title">Password Reset</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-edit-password-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_password_form" class="form" action="#" novalidate="novalidate"
                        autocomplete="off">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5" id="kt_modal_edit_password_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_password_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_password_scroll" data-kt-scroll-offset="300px">
                            <div class="row">
                                <div class="col-lg-12">
                                    <!--begin::Input group-->
                                    <div class="fv-row mb-7">
                                        <!--begin::Label-->
                                        <label class="required fw-semibold fs-6 mb-2">Write New Password</label>
                                        <!--end::Label-->

                                        <div class="input-group">
                                            <input type="password" name="new_password" id="teacherPasswordNew"
                                                class="form-control mb-3 mb-lg-0" placeholder="Enter New Password"
                                                required autocomplete="off" />
                                            <span class="input-group-text toggle-password"
                                                data-target="teacherPasswordNew" style="cursor: pointer;"
                                                title="See Password" data-bs-toggle="tooltip">
                                                <i class="ki-outline ki-eye fs-3"></i>
                                            </span>
                                        </div>

                                        <!-- Password strength meter -->
                                        <div id="password-strength-text" class="mt-1 fw-bold small text-muted"></div>
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div id="password-strength-bar" class="progress-bar" role="progressbar"
                                                style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <!--end::Input group-->
                                </div>
                            </div>
                        </div>
                        <!--end::Scroll-->

                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-edit-password-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-success" data-kt-edit-password-modal-action="submit">
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
    <!--end::Modal - Edit Teacher Password-->
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        const storeTeacherRoute = "{{ route('teachers.store') }}";

        const routeDeleteTeacher = "{{ route('teachers.destroy', ':id') }}";
        const routeToggleActive = "{{ route('teachers.toggleActive', ':id') }}";
    </script>

    <script src="{{ asset('js/teachers/index.js') }}"></script>

    <script>
        document.getElementById("teachers_menu").classList.add("here", "show");
        document.getElementById("teachers_link").classList.add("active");
    </script>
@endpush
