@push('page-css')
@endpush


@extends('layouts.app')

@section('title', 'New Admission')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Admission Form
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
                Admission </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <div id="error-container"></div>

    <!--begin::Stepper-->
    <div class="stepper stepper-pills stepper-column d-flex flex-column flex-xl-row flex-row-fluid gap-10"
        id="kt_create_student_stepper">

        <!--begin::Aside-->
        <div class="card d-flex justify-content-center justify-content-xl-start flex-row-auto w-100 w-xl-300px w-xxl-400px">
            <!--begin::Wrapper-->
            <div class="card-body px-6 px-lg-10 px-xxl-15 py-20">
                <!--begin::Nav-->
                <div class="stepper-nav">
                    <!--begin::Step 1-->
                    <div class="stepper-item current" data-kt-stepper-element="nav">
                        <!--begin::Wrapper-->
                        <div class="stepper-wrapper">
                            <!--begin::Icon-->
                            <div class="stepper-icon w-40px h-40px">
                                <i class="ki-outline ki-check fs-2 stepper-check"></i>
                                <span class="stepper-number">1</span>
                            </div>
                            <!--end::Icon-->
                            <!--begin::Label-->
                            <div class="stepper-label">
                                <h3 class="stepper-title">Student Information</h3>
                                <div class="stepper-desc fw-semibold">Fill up the personal information</div>
                            </div>
                            <!--end::Label-->
                        </div>
                        <!--end::Wrapper-->
                        <!--begin::Line-->
                        <div class="stepper-line h-40px"></div>
                        <!--end::Line-->
                    </div>
                    <!--end::Step 1-->

                    <!--begin::Step 2-->
                    <div class="stepper-item" data-kt-stepper-element="nav">
                        <!--begin::Wrapper-->
                        <div class="stepper-wrapper">
                            <!--begin::Icon-->
                            <div class="stepper-icon w-40px h-40px">
                                <i class="ki-outline ki-check fs-2 stepper-check"></i>
                                <span class="stepper-number">2</span>
                            </div>
                            <!--end::Icon-->
                            <!--begin::Label-->
                            <div class="stepper-label">
                                <h3 class="stepper-title">Guardians & Siblings</h3>
                                <div class="stepper-desc fw-semibold">Fill up guardians & siblings information</div>
                            </div>
                            <!--end::Label-->
                        </div>
                        <!--end::Wrapper-->
                        <!--begin::Line-->
                        <div class="stepper-line h-40px"></div>
                        <!--end::Line-->
                    </div>
                    <!--end::Step 2-->

                    <!--begin::Step 3-->
                    <div class="stepper-item" data-kt-stepper-element="nav">
                        <!--begin::Wrapper-->
                        <div class="stepper-wrapper">
                            <!--begin::Icon-->
                            <div class="stepper-icon w-40px h-40px">
                                <i class="ki-outline ki-check fs-2 stepper-check"></i>
                                <span class="stepper-number">3</span>
                            </div>
                            <!--end::Icon-->
                            <!--begin::Label-->
                            <div class="stepper-label">
                                <h3 class="stepper-title">Enrolled Subjects</h3>
                                <div class="stepper-desc fw-semibold">Select the enrolled subjects</div>
                            </div>
                            <!--end::Label-->
                        </div>
                        <!--end::Wrapper-->
                        <!--begin::Line-->
                        <div class="stepper-line h-40px"></div>
                        <!--end::Line-->
                    </div>
                    <!--end::Step 3-->

                    <!--begin::Step 4-->
                    <div class="stepper-item" data-kt-stepper-element="nav">
                        <!--begin::Wrapper-->
                        <div class="stepper-wrapper">
                            <!--begin::Icon-->
                            <div class="stepper-icon w-40px h-40px">
                                <i class="ki-outline ki-check fs-2 stepper-check"></i>
                                <span class="stepper-number">4</span>
                            </div>
                            <!--end::Icon-->
                            <!--begin::Label-->
                            <div class="stepper-label">
                                <h3 class="stepper-title">Administrative Info</h3>
                                <div class="stepper-desc fw-semibold">Monthly fee, deadline, reference</div>
                            </div>
                            <!--end::Label-->
                        </div>
                        <!--end::Wrapper-->
                        <!--begin::Line-->
                        <div class="stepper-line h-40px"></div>
                        <!--end::Line-->
                    </div>
                    <!--end::Step 4-->

                    <!--begin::Step 5-->
                    <div class="stepper-item mark-completed" data-kt-stepper-element="nav">
                        <!--begin::Wrapper-->
                        <div class="stepper-wrapper">
                            <!--begin::Icon-->
                            <div class="stepper-icon w-40px h-40px">
                                <i class="ki-outline ki-check fs-2 stepper-check"></i>
                                <span class="stepper-number">5</span>
                            </div>
                            <!--end::Icon-->
                            <!--begin::Label-->
                            <div class="stepper-label">
                                <h3 class="stepper-title">Admission Done</h3>
                                <div class="stepper-desc fw-semibold">Branch Manager approval</div>
                            </div>
                            <!--end::Label-->
                        </div>
                        <!--end::Wrapper-->
                    </div>
                    <!--end::Step 5-->
                </div>
                <!--end::Nav-->
            </div>
            <!--end::Wrapper-->
        </div>
        <!--begin::Aside-->


        <!--begin::Form Content-->
        <div class="card d-flex flex-row-fluid flex-center">
            <!--begin::Form-->
            <form class="card-body py-20 w-100 px-9" novalidate="novalidate" enctype="multipart/form-data"
                id="kt_create_student_form">
                <!--begin::Step 1-->
                <div data-kt-stepper-element="content">
                    <!--begin::Wrapper-->
                    <div class="w-100">
                        <!--begin::Heading-->
                        <div class="pb-10 pb-lg-15">
                            <!--begin::Title-->
                            <h2 class="fw-bold d-flex align-items-center text-gray-900">Student Personal Information
                                <span class="ms-1" data-bs-toggle="tooltip"
                                    title="Student ID will be generated automatically.">
                                    <i class="ki-outline ki-information-5 text-gray-500 fs-6">
                                    </i>
                                </span>
                            </h2>
                            <!--end::Title-->
                            <!--begin::Notice-->
                            <div class="text-muted fw-semibold fs-6">If you need more info, please check out
                            </div>
                            <!--end::Notice-->
                        </div>
                        <!--end::Heading-->


                        <div class="row">
                            {{-- Personal Information --}}
                            <div class="col-lg-8">
                                <!--begin::Name Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Full Name</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="student_name"
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Write full name"
                                        required />
                                    <!--end::Input-->
                                </div>
                                <!--end::Name Input group-->

                                <!--begin::Address Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Home Address</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="student_home_address"
                                        class="form-control form-control-solid mb-3 mb-lg-0"
                                        placeholder="Write student home address" required />
                                    <!--end::Input-->
                                </div>
                                <!--end::Address Input group-->

                                {{-- Mobile Number Row --}}
                                <div class="row">
                                    <div class="col-md-12">
                                        <!--begin::Input group-->
                                        <div class="fv-row mb-7">
                                            <!--begin::Label-->
                                            <label class="required fw-semibold fs-6 mb-2">Mobile No. (Home)</label>
                                            <!--end::Label-->
                                            <!--begin::Input-->
                                            <input type="text" name="student_phone_home"
                                                class="form-control form-control-solid mb-3 mb-lg-0" maxlength="11"
                                                placeholder="e.g. 01771-334334" required />
                                            <!--end::Input-->
                                        </div>
                                        <!--end::Input group-->
                                    </div>

                                    <div class="col-md-6">
                                        <!--begin::Input group-->
                                        <div class="fv-row mb-7">
                                            <!--begin::Label-->
                                            <label class="required fw-semibold fs-6 mb-2">SMS No.</label>
                                            <!--end::Label-->
                                            <!--begin::Input-->
                                            <input type="text" name="student_phone_sms"
                                                class="form-control form-control-solid mb-3 mb-lg-0" maxlength="11"
                                                placeholder="For result and notice" required />
                                            <!--end::Input-->
                                        </div>
                                        <!--end::Input group-->
                                    </div>

                                    <div class="col-md-6">
                                        <!--begin::Input group-->
                                        <div class="fv-row mb-7">
                                            <!--begin::Label-->
                                            <label class="fw-semibold fs-6 mb-2">WhatsApp No. <span
                                                    class="text-muted">(optional)</span></label>
                                            <!--end::Label-->
                                            <!--begin::Input-->
                                            <input type="text" name="student_phone_whatsapp" maxlength="11"
                                                class="form-control form-control-solid mb-3 mb-lg-0"
                                                placeholder="Write WhatsApp number (if any)" />
                                            <!--end::Input-->
                                        </div>
                                        <!--end::Input group-->
                                    </div>
                                </div>

                                {{-- Email and Birthday Row --}}
                                <div class="row">
                                    <div class="col-md-6">
                                        <!--begin::Email Input group-->
                                        <div class="fv-row mb-7">
                                            <!--begin::Label-->
                                            <label class="fw-semibold fs-6 mb-2">Email <span
                                                    class="text-muted">(optional)</span></label>
                                            <!--end::Label-->
                                            <!--begin::Input-->
                                            <input type="email" name="student_email"
                                                class="form-control form-control-solid mb-3 mb-lg-0"
                                                placeholder="Write student email (if any)" />
                                            <!--end::Input-->
                                        </div>
                                        <!--end::Email Input group-->
                                    </div>

                                    <div class="col-md-6">
                                        <!--begin::Birthday group-->
                                        <div class="fv-row">
                                            <!--begin::Label-->
                                            <label class="form-label">Date of Birth <span
                                                    class="text-muted">(optional)</span></label>
                                            <!--end::Label-->
                                            <!--begin::Editor-->
                                            <div class="input-group input-group-solid flex-nowrap">
                                                <span class="input-group-text">
                                                    <i class="las la-calendar fs-3"></i>
                                                </span>
                                                <div class="overflow-hidden flex-grow-1">
                                                    <input name="birth_date" id="student_birth_date"
                                                        placeholder="Select a date"
                                                        class="form-control form-control-solid" />
                                                </div>
                                            </div>
                                            <!--end::Editor-->
                                        </div>
                                        <!--end::Birthday group-->
                                    </div>
                                </div>
                            </div>

                            {{-- Photo, Gender, Religion, Blood Group --}}
                            <div class="col-lg-4">
                                <!--begin::Photo Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="d-block fw-semibold fs-6 mb-5">Profile Photo <span
                                            class="text-muted">(optional)</span></label>
                                    <!--end::Label-->
                                    <!--begin::Image placeholder-->
                                    <style>
                                        .image-input-placeholder {
                                            background-image: url('{{ asset('assets/media/svg/files/blank-image.svg') }}');
                                        }

                                        [data-bs-theme="dark"] .image-input-placeholder {
                                            background-image: url('{{ asset('assets/media/svg/files/blank-image-dark.svg') }}');
                                        }
                                    </style>
                                    <!--end::Image placeholder-->
                                    <!--begin::Image input-->
                                    <div class="image-input image-input-circle image-input-empty image-input-outline image-input-placeholder"
                                        data-kt-image-input="true">
                                        <!--begin::Preview existing avatar-->
                                        <div class="image-input-wrapper w-125px h-125px"></div>
                                        <!--end::Preview existing avatar-->
                                        <!--begin::Label-->
                                        <label
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                            title="Change avatar">
                                            <i class="ki-outline ki-pencil fs-7">
                                            </i>
                                            <!--begin::Inputs-->
                                            <input type="file" name="avatar" accept=".png, .jpg, .jpeg" />
                                            <input type="hidden" name="avatar_remove" />
                                            <!--end::Inputs-->
                                        </label>
                                        <!--end::Label-->
                                        <!--begin::Cancel-->
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                            title="Cancel avatar">
                                            <i class="ki-outline ki-cross fs-2">
                                            </i>
                                        </span>
                                        <!--end::Cancel-->
                                        <!--begin::Remove-->
                                        <span
                                            class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                            data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                            title="Remove avatar">
                                            <i class="ki-outline ki-cross fs-2">
                                            </i>
                                        </span>
                                        <!--end::Remove-->
                                    </div>
                                    <!--end::Image input-->
                                    <!--begin::Hint-->
                                    <div class="form-text">Allowed file types: png, jpg, jpeg. Max 50kB</div>
                                    <!--end::Hint-->
                                </div>
                                <!--end::Photo Input group-->

                                <!--begin::Gender Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="d-flex align-items-center form-label mb-3 required">Gender</label>
                                    <!--end::Label-->
                                    <!--begin::Row-->
                                    <div class="row">
                                        <!--begin::Col-->
                                        <div class="col-lg-6">
                                            <!--begin::Option-->
                                            <input type="radio" class="btn-check" name="student_gender" value="male"
                                                checked="checked" id="gender_male_input" />
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
                                            <input type="radio" class="btn-check" name="student_gender" value="female"
                                                id="gender_female_input" />
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
                                    <!--end::Row-->
                                </div>
                                <!--end::Gender Input group-->

                                <!--begin::Religion Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="form-label">Religion <span class="text-muted">(optional)</span></label>
                                    <!--end::Label-->

                                    <!--begin::Solid input group style-->
                                    <div class="input-group input-group-solid flex-nowrap">
                                        <span class="input-group-text">
                                            <i class="las la-star-and-crescent fs-3"></i>
                                        </span>
                                        <div class="overflow-hidden flex-grow-1">
                                            <select name="student_religion" data-hide-search="true"
                                                class="form-select form-select-solid rounded-start-0 border-start"
                                                data-control="select2" data-placeholder="Select an option">
                                                <option></option>
                                                <option value="Islam">Islam</option>
                                                <option value="Hinduism">Hinduism</option>
                                                <option value="Others">Others</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!--end::Solid input group style-->
                                </div>
                                <!--end::Religion Input group-->

                                <!--begin::Blood Input group-->
                                <div class="fv-row">
                                    <!--begin::Label-->
                                    <label class="form-label">Blood Group <span
                                            class="text-muted">(optional)</span></label>
                                    <!--end::Label-->

                                    <!--begin::Solid input group style-->
                                    <div class="input-group input-group-solid flex-nowrap">
                                        <span class="input-group-text">
                                            <i class="las la-tint fs-3"></i>
                                        </span>
                                        <div class="overflow-hidden flex-grow-1">
                                            <select name="student_blood_group"
                                                class="form-select form-select-solid rounded-start-0 border-start"
                                                data-control="select2" data-placeholder="Select an option">
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
                                    <!--end::Solid input group style-->
                                </div>
                                <!--end::Blood Input group-->
                            </div>
                        </div>
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Step 1-->

                <!--begin::Step 2-->
                <div data-kt-stepper-element="content">
                    <!--begin::Wrapper-->
                    <div class="w-100">
                        <!--begin::Heading-->
                        <div class="pb-10 pb-lg-15">
                            <!--begin::Title-->
                            <h2 class="fw-bold text-gray-900">Guardian & Sibling Info</h2>
                            <!--end::Title-->
                            <!--begin::Notice-->
                            <div class="text-muted fw-semibold fs-6">If you need more info, please check out
                            </div>
                            <!--end::Notice-->
                        </div>
                        <!--end::Heading-->

                        <!--begin::Parents Input group-->
                        <div class="mb-15">
                            <!--begin::Label-->
                            <label class="form-label fs-3">Guardians <span class="text-muted">(at least one
                                    guardian)</span></label>
                            <!--end::Label-->

                            <!--begin::Guardian 1-->
                            <div class="form-group row mb-3 border border-dashed rounded px-2 py-3">
                                <div class="col-md fv-row">
                                    <label class="form-label required">Guardian-1 Name</label>
                                    <input type="text" class="form-control form-control-solid mb-2 mb-md-0"
                                        placeholder="Enter full name" name="guardian_1_name" required />
                                </div>
                                <div class="col-md fv-row">
                                    <label class="form-label required">Guardian-1 Mobile No.</label>
                                    <input type="text" class="form-control form-control-solid mb-2 mb-md-0"
                                        maxlength="11" placeholder="Enter contact number" name="guardian_1_mobile"
                                        required />
                                </div>
                                <div class="col-md-2 fv-row">
                                    <!--begin::Label-->
                                    <label class="form-label required">Gender</label>
                                    <!--end::Label-->

                                    <!--begin::Solid input group style-->
                                    <select name="guardian_1_gender" data-hide-search="true"
                                        class="form-select form-select-solid" data-control="select2"
                                        data-placeholder="Select" required>
                                        <option></option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                    <!--end::Solid input group style-->
                                </div>
                                <div class="col-md-2 fv-row">
                                    <!--begin::Label-->
                                    <label class="form-label required">Relationship</label>
                                    <!--end::Label-->

                                    <!--begin::Solid input group style-->
                                    <select name="guardian_1_relationship" data-hide-search="true"
                                        class="form-select form-select-solid" data-control="select2"
                                        data-placeholder="Select" required>
                                        <option></option>
                                        <option value="father">Father</option>
                                        <option value="mother">Mother</option>
                                        <option value="brother">Brother</option>
                                        <option value="sister">Sister</option>
                                        <option value="uncle">Uncle</option>
                                        <option value="aunt">Aunt</option>
                                    </select>
                                    <!--end::Solid input group style-->
                                </div>
                            </div>
                            <!--end::Guardian 1-->

                            <!--begin::Guardian 2-->
                            <div class="form-group row mb-3 border border-dashed rounded px-2 py-3">
                                <div class="col-md fv-row">
                                    <label class="form-label">Guardian-2 Name</label>
                                    <input type="text" class="form-control form-control-solid mb-2 mb-md-0"
                                        placeholder="Enter full name" name="guardian_2_name" />
                                </div>
                                <div class="col-md fv-row">
                                    <label class="form-label">Guardian-2 Mobile No.</label>
                                    <input type="text" class="form-control form-control-solid mb-2 mb-md-0"
                                        maxlength="11" placeholder="Enter contact number" name="guardian_2_mobile" />
                                </div>
                                <div class="col-md-2 fv-row">
                                    <!--begin::Label-->
                                    <label class="form-label">Gender</label>
                                    <!--end::Label-->

                                    <!--begin::Solid input group style-->
                                    <select name="guardian_2_gender" data-hide-search="true"
                                        class="form-select form-select-solid" data-control="select2"
                                        data-allow-clear="true" data-placeholder="Select">
                                        <option></option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                    <!--end::Solid input group style-->
                                </div>
                                <div class="col-md-2 fv-row">
                                    <!--begin::Label-->
                                    <label class="form-label">Relationship</label>
                                    <!--end::Label-->

                                    <!--begin::Solid input group style-->
                                    <select name="guardian_2_relationship" data-hide-search="true"
                                        class="form-select form-select-solid" data-control="select2"
                                        data-allow-clear="true" data-placeholder="Select">
                                        <option></option>
                                        <option value="father">Father</option>
                                        <option value="mother">Mother</option>
                                        <option value="brother">Brother</option>
                                        <option value="sister">Sister</option>
                                        <option value="uncle">Uncle</option>
                                        <option value="aunt">Aunt</option>
                                    </select>
                                    <!--end::Solid input group style-->
                                </div>
                            </div>
                            <!--end::Guardian 2-->
                        </div>
                        <!--end::Parents group-->

                        <!--begin::Siblings Input group-->
                        <div class="mb-10">
                            <!--begin::Label-->
                            <label class="form-label fs-3">Siblings (if any)</label>
                            <!--end::Label-->
                            {{-- Sibling - 1 --}}
                            <div class="form-group row mb-3 border border-dashed px-2 py-3 rounded">
                                <div class="col-md-3 fv-row">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control form-control-solid mb-2 mb-md-0"
                                        placeholder="Enter full name" name="sibling_1_name" />
                                </div>
                                <div class="col-md-2 fv-row">
                                    <label class="form-label">Class/Age</label>
                                    <input type="text" class="form-control form-control-solid mb-2 mb-md-0"
                                        maxlength="15" name="sibling_1_class" />
                                </div>
                                <div class="col-md-1 fv-row">
                                    <label class="form-label">Year</label>
                                    <input type="text" class="form-control form-control-solid mb-2 mb-md-0"
                                        name="sibling_1_year" />
                                </div>
                                <div class="col-md-4 fv-row">
                                    <label class="form-label">Instituition</label>
                                    <input type="text" class="form-control form-control-solid mb-2 mb-md-0"
                                        placeholder="Enter instituition name" name="sibling_1_institution" />
                                </div>
                                <div class="col-md-2 fv-row">
                                    <!--begin::Label-->
                                    <label class="form-label">Relationship</label>
                                    <!--end::Label-->

                                    <!--begin::Solid input group style-->
                                    <select name="sibling_1_relationship" data-hide-search="true"
                                        class="form-select form-select-solid" data-control="select2"
                                        data-allow-clear="true" data-placeholder="Select">
                                        <option></option>
                                        <option value="brother">Brother</option>
                                        <option value="sister">Sister</option>
                                    </select>
                                    <!--end::Solid input group style-->
                                </div>
                                <!--end::Religion Input group-->
                            </div>

                            {{-- Sibling - 2 --}}
                            <div class="form-group row mb-3 border border-dashed px-2 py-3 rounded">
                                <div class="col-md-3 fv-row">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control form-control-solid mb-2 mb-md-0"
                                        placeholder="Enter full name" name="sibling_2_name" />
                                </div>
                                <div class="col-md-2 fv-row">
                                    <label class="form-label">Class/Age</label>
                                    <input type="text" class="form-control form-control-solid mb-2 mb-md-0"
                                        maxlength="15" name="sibling_2_class" />
                                </div>
                                <div class="col-md-1 fv-row">
                                    <label class="form-label">Year</label>
                                    <input type="text" class="form-control form-control-solid mb-2 mb-md-0"
                                        name="sibling_2_year" />
                                </div>
                                <div class="col-md-4 fv-row">
                                    <label class="form-label">Instituition</label>
                                    <input type="text" class="form-control form-control-solid mb-2 mb-md-0"
                                        placeholder="Enter instituition name" name="sibling_2_institution" />
                                </div>
                                <div class="col-md-2 fv-row">
                                    <!--begin::Label-->
                                    <label class="form-label">Relationship</label>
                                    <!--end::Label-->

                                    <!--begin::Solid input group style-->
                                    <select name="sibling_2_relationship" data-hide-search="true"
                                        class="form-select form-select-solid" data-control="select2"
                                        data-allow-clear="true" data-placeholder="Select">
                                        <option></option>
                                        <option value="brother">Brother</option>
                                        <option value="sister">Sister</option>
                                    </select>
                                    <!--end::Solid input group style-->
                                </div>
                            </div>
                        </div>
                        <!--end::Siblings group-->

                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Step 2-->

                <!--begin::Step 3-->
                <div data-kt-stepper-element="content">
                    <div class="w-100">
                        <div class="pb-10 pb-lg-12">
                            <h2 class="fw-bold text-gray-900">Enrolled Subjects</h2>
                            <div class="text-muted fw-semibold fs-6">
                                Assign to class and select enrolled subjects.
                            </div>
                        </div>

                        <div class="row mb-6">
                            <div class="col-lg-7 fv-row">
                                <div class="fv-row mb-7">
                                    <label class="form-label required fs-6 fw-semibold">Class</label>
                                    <select name="student_class" id="student_class_input"
                                        class="form-select form-select-solid" data-control="select2"
                                        data-placeholder="Select a class" required>
                                        <option></option>
                                        @foreach ($classnames as $classname)
                                            <option value="{{ $classname->id }}"
                                                data-class-numeral="{{ $classname->class_numeral }}">
                                                {{ $classname->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-5 fv-row" id="student-group-selection">
                                <div class="mb-7">
                                    <label class="form-label required fs-6 fw-semibold">Academic Group</label>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <input type="radio" class="btn-check" name="student_academic_group"
                                                value="Science" checked id="group_science" required />
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                for="group_science">
                                                <i class="ki-outline ki-flask fs-2x me-3"></i>
                                                <span class="fw-bold">Science</span>
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="radio" class="btn-check" name="student_academic_group"
                                                value="Commerce" id="group_commerce" required />
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                for="group_commerce">
                                                <i class="ki-outline ki-chart-line-up fs-2x me-3"></i>
                                                <span class="fw-bold">Commerce</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="fv-row mb-7">
                            <label class="form-label required fs-6 fw-semibold">School/College</label>
                            <div class="input-group input-group-solid flex-nowrap">
                                <span class="input-group-text">
                                    <i class="ki-outline ki-bank fs-3"></i>
                                </span>
                                <div class="overflow-hidden flex-grow-1">
                                    <select name="student_institution" id="institution_select"
                                        class="form-select form-select-solid rounded-start-0 border-start"
                                        data-control="select2" data-placeholder="Select institution" required>
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="fv-row">
                            <label class="form-label required fs-6 fw-semibold mb-3">Enrolled Subjects</label>

                            <div
                                class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4 mb-6">
                                <i class="ki-outline ki-information-5 fs-2tx text-primary me-4"></i>
                                <div class="fw-semibold">
                                    <div class="fs-6 text-gray-700">
                                        <strong>Compulsory subjects</strong> are auto-selected.
                                        For <strong>optional subjects</strong>, select one as main and one as 4th subject.
                                    </div>
                                </div>
                            </div>

                            <div id="subject_list">
                                <div class="text-center py-10">
                                    <span class="text-muted fs-6">Select a class to view subjects</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end::Step 3-->

                <!--begin::Step 4-->
                <div data-kt-stepper-element="content" class="current">
                    <!--begin::Wrapper-->
                    <div class="w-100">
                        <!--begin::Heading-->
                        <div class="pb-10 pb-lg-15">
                            <!--begin::Title-->
                            <h2 class="fw-bold text-gray-900">Administrative</h2>
                            <!--end::Title-->
                            <!--begin::Notice-->
                            <div class="text-muted fw-semibold fs-6">Set batch, tuition fee, type, due date etc.</div>
                            <!--end::Notice-->
                        </div>
                        <!--end::Heading-->

                        <!--begin::Branch radio-->
                        <div class="fv-row mb-7 @if (!auth()->user()->isAdmin()) d-none @endif">
                            <label class="fs-6 fw-semibold mb-2 required">Branch</label>
                            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-1 row-cols-xl-3 g-9"
                                data-kt-buttons="true" data-kt-buttons-target="[data-kt-button='true']">
                                @foreach ($branches as $branch)
                                    <div class="col">
                                        <label
                                            class="btn btn-outline btn-outline-dashed btn-active-light-primary @if ($loop->first) active @endif d-flex text-start p-6"
                                            data-kt-button="true">
                                            <span
                                                class="form-check form-check-custom form-check-solid form-check-sm align-items-start mt-1">
                                                <input class="form-check-input branch-radio" type="radio"
                                                    name="student_branch" value="{{ $branch->id }}" required
                                                    @if ($loop->first) checked="checked" @endif />
                                            </span>
                                            <span class="ms-5">
                                                <span
                                                    class="fs-4 fw-bold text-gray-800 d-block">{{ $branch->branch_name }}</span>
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <!--end::Branch radio-->

                        <!--begin::Batch radio-->
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2 required">Batch</label>
                            <div id="batch-container" class="row row-cols-1 row-cols-md-3 row-cols-lg-2 row-cols-xl-4 g-9"
                                data-kt-buttons="true" data-kt-buttons-target="[data-kt-button='true']">

                                @php
                                    $defaultBranchId = $branches->first()->id;
                                @endphp

                                @foreach ($branches as $branch)
                                    @foreach ($batches->where('branch_id', $branch->id) as $batch)
                                        <div class="col batch-option" data-branch="{{ $branch->id }}"
                                            style="{{ $branch->id == $defaultBranchId ? '' : 'display: none;' }}">
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary d-flex text-start p-6"
                                                data-kt-button="true">
                                                <span
                                                    class="form-check form-check-custom form-check-solid form-check-sm mt-1">
                                                    <input class="form-check-input" type="radio" name="student_batch"
                                                        value="{{ $batch->id }}" required />
                                                </span>
                                                <span class="ms-5">
                                                    <span class="fs-4 fw-bold text-gray-800 d-block">
                                                        {{ $batch->name }} ({{ $batch->branch->branch_prefix }})
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                        <!--end::Batch radio-->


                        {{-- Tuition fee, type, due date Row --}}
                        <div class="row">
                            <div class="col-md-3">
                                <!--begin::Input group-->
                                <div class="mb-7 fv-row">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Admission Fee
                                        <span class="ms-1" data-bs-toggle="tooltip"
                                            title="Set any amount for admission fee. Set 0 for no admission fee.">
                                            <i class="ki-outline ki-information-5 text-gray-500 fs-6">
                                            </i>
                                        </span>
                                    </label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="number" class="form-control form-control-solid"
                                        name="student_admission_fee" min="0" placeholder="Admission fee"
                                        required />
                                    <!--end::Input-->
                                </div>
                                <!--end::Input group-->
                            </div>

                            <div class="col-md-3">
                                <!--begin::Input group-->
                                <div class="mb-7 fv-row">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Tuition Fee
                                        <span class="ms-1" data-bs-toggle="tooltip"
                                            title="Set any amount. Set 0 for FREE students.">
                                            <i class="ki-outline ki-information-5 text-gray-500 fs-6">
                                            </i>
                                        </span>
                                    </label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="number" class="form-control form-control-solid"
                                        name="student_tuition_fee" min="0" placeholder="Monthly tuition fee"
                                        required />
                                    <!--end::Input-->
                                </div>
                                <!--end::Input group-->
                            </div>

                            <div class="col-md-3">
                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Payment Style:</label>
                                    <!--end::Label-->

                                    <!--begin::Input-->
                                    <div class="d-flex mt-3">
                                        <!--begin::Radio-->
                                        <div class="form-check form-check-custom form-check-solid me-5">
                                            <input class="form-check-input" type="radio" value="current"
                                                name="payment_style" id="payment_style_current" checked="checked"
                                                required />
                                            <label class="form-check-label fs-6 fw-medium"
                                                for="payment_style_current">Current</label>
                                        </div>
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" value="due"
                                                name="payment_style" id="payment_style_due" required />
                                            <label class="form-check-label fs-6 fw-medium"
                                                for="payment_style_due">Due</label>
                                        </div>
                                        <!--end::Radio-->
                                    </div>
                                    <!--end::Input-->
                                </div>
                                <!--end::Input group-->
                            </div>

                            <div class="col-md-3">
                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="fw-semibold fs-6 mb-2 required">Due Date:</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <select name="payment_due_date" class="form-select form-select-solid"
                                        data-control="select2" data-hide-search="true" data-placeholder="Select due date"
                                        required>
                                        <option></option>
                                        <option value="7">1 to 7</option>
                                        <option value="10">1 to 10</option>
                                        <option value="15">1 to 15</option>
                                        <option value="30">1 to 30</option>
                                    </select>
                                    <!--end::Input-->
                                </div>
                                <!--end::Input group-->
                            </div>
                        </div>

                        {{-- Referrer Row --}}
                        <div class="row">
                            <div class="col-md-3">
                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="fw-semibold fs-6 mb-2">Referer Type <span
                                            class="text-muted">(optional)</span></label>
                                    <!--end::Label-->

                                    <!--begin::Input-->
                                    <div class="d-flex mt-3">
                                        <!--begin::Radio-->
                                        <div class="form-check form-check-custom form-check-solid me-5">
                                            <input class="form-check-input" type="radio" value="teacher"
                                                name="referer_type" id="referer_type_teacher" />
                                            <label class="form-check-label fs-6 fw-medium"
                                                for="referer_type_teacher">Teacher</label>
                                        </div>

                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" value="student"
                                                name="referer_type" id="referer_type_student" />
                                            <label class="form-check-label fs-6 fw-medium"
                                                for="referer_type_student">Student</label>
                                        </div>
                                        <!--end::Radio-->
                                    </div>
                                    <!--end::Input-->
                                </div>
                                <!--end::Input group-->
                            </div>

                            <div class="col-md-9">
                                <!--begin::Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="fw-semibold fs-6 mb-2">Referred By <span
                                            class="text-muted">(optional)</span></label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <select name="referred_by" id="referred_by" class="form-select form-select-solid"
                                        data-control="select2" data-placeholder="Select the person">
                                        <option></option>
                                    </select>
                                    <!--end::Input-->
                                </div>
                                <!--end::Input group-->
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Remarks <span class="text-muted">(optional)</span></label>
                                <input type="text" class="form-control form-control-solid mb-2 mb-md-0"
                                    placeholder="Write remarks (if any)" name="student_remarks" />
                            </div>
                        </div>

                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Step 4-->

                <!--begin::Step 5-->
                <div data-kt-stepper-element="content">
                    <!--begin::Wrapper-->
                    <div class="w-100">
                        <!--begin::Heading-->
                        <div class="pb-8 pb-lg-10">
                            <!--begin::Title-->
                            <h2 class="fw-bold text-gray-900">Admission Done!</h2>
                            <!--end::Title-->

                            <!--begin::Notice-->
                            <div class="text-muted fw-semibold fs-6">Now, it requires Branch Manager approval to activate
                                student account.
                            </div>
                            <!--end::Notice-->
                        </div>
                        <!--end::Heading-->

                        <!--begin::Body-->
                        <div class="mb-0">
                            {{-- <!--begin::Text-->
                            <div class="fs-6 text-gray-600 mb-5">Writing headlines for blog posts is as much an art
                                as it is a science and probably warrants its own post, but for all advise is with
                                what works for your great & amazing audience.</div>
                            <!--end::Text--> --}}

                            <!--begin::Alert-->
                            <!--begin::Notice-->
                            <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6">
                                <!--begin::Icon-->
                                <i class="ki-outline ki-information fs-2tx text-warning me-4">
                                </i>
                                <!--end::Icon-->
                                <!--begin::Wrapper-->
                                <div class="d-flex flex-stack flex-grow-1">
                                    <!--begin::Content-->
                                    <div class="fw-semibold">
                                        <h4 class="text-gray-900 fw-bold"><span id="admitted_name">Ashikur Rahman</span>,
                                            ID: <span id="admitted_id">G-250905</span></h4>
                                        <div class="fs-6 text-gray-700">Now, receive the admission fee, current month
                                            payment & sheet fee from the student and then inform your manager to approve the
                                            student. Then you can download the admission copy.
                                        </div>
                                    </div>
                                    <!--end::Content-->
                                </div>
                                <!--end::Wrapper-->
                            </div>
                            <!--end::Notice-->
                            <!--end::Alert-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Step 5-->

                <!--begin::Actions-->
                <div class="d-flex flex-stack pt-10">
                    <!--begin::Wrapper-->
                    <div class="mr-2">
                        <button type="button" class="btn btn-lg btn-light-primary me-3"
                            data-kt-stepper-action="previous">
                            <i class="ki-outline ki-arrow-left fs-4 me-1">
                            </i>Back</button>
                    </div>
                    <!--end::Wrapper-->
                    <!--begin::Wrapper-->
                    <div>
                        <button type="button" class="btn btn-lg btn-primary me-3" data-kt-stepper-action="submit">
                            <span class="indicator-label">Submit
                                <i class="ki-outline ki-arrow-right fs-3 ms-2 me-0">
                                </i></span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                        <button type="button" class="btn btn-lg btn-primary" data-kt-stepper-action="next">Continue
                            <i class="ki-outline ki-arrow-right fs-4 ms-1 me-0">
                            </i></button>
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Actions-->
            </form>
            <!--end::Form-->
        </div>
        <!--end::Form Content-->

    </div>
    <!--end::Stepper-->
@endsection


@push('vendor-js')
@endpush

@push('page-js')
    <script>
        document.getElementById("admission_menu").classList.add("here", "show");
        document.getElementById("new_admission_link").classList.add("active");
    </script>

    <script>
        var storeStudentRoute = "{{ route('students.store') }}";
        var csrfToken = "{{ csrf_token() }}";

        var ajaxTeacherRoute = "{{ route('admin.referrers.teachers') }}";
        var ajaxStudentRoute = "{{ route('admin.referrers.students') }}";
    </script>

    <script src="{{ asset('js/students/create.js') }}"></script>
@endpush
