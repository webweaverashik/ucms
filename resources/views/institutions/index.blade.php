@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'All Institutions')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Institutions
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
                Institutions </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin::Institutions List-->
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
                            data-kt-institution-table-filter="search" class="form-control form-control-solid w-350px ps-12"
                            placeholder="Search School/College">
                    </div>
                    <!--end::Search-->
                </div>
                <!--begin::Card title-->

                <!--begin::Card toolbar-->
                <div class="card-toolbar">
                    <!--begin::Toolbar-->
                    <div class="d-flex justify-content-end" data-kt-subscription-table-toolbar="base">
                        <!--begin::Filter-->
                        <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                            data-kt-menu-placement="bottom-end">
                            <i class="ki-outline ki-filter fs-2"></i>Filter</button>
                        <!--begin::Menu 1-->
                        <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                            <!--begin::Header-->
                            <div class="px-7 py-5">
                                <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                            </div>
                            <!--end::Header-->
                            <!--begin::Separator-->
                            <div class="separator border-gray-200"></div>
                            <!--end::Separator-->
                            <!--begin::Content-->
                            <div class="px-7 py-5" data-kt-institution-table-filter="form">
                                <!--begin::Input group-->
                                <div class="mb-10">
                                    <label class="form-label fs-6 fw-semibold">Institution Type:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true"
                                        data-kt-institution-table-filter="product" data-hide-search="true">
                                        <option></option>
                                        <option value="ucms_School">School</option>
                                        <option value="ucms_College">College</option>
                                    </select>
                                </div>
                                <!--end::Input group-->

                                <!--begin::Actions-->
                                <div class="d-flex justify-content-end">
                                    <button type="reset"
                                        class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                        data-kt-menu-dismiss="true" data-kt-institution-table-filter="reset">Reset</button>
                                    <button type="submit" class="btn btn-primary fw-semibold px-6"
                                        data-kt-menu-dismiss="true" data-kt-institution-table-filter="filter">Apply</button>
                                </div>
                                <!--end::Actions-->
                            </div>
                            <!--end::Content-->
                        </div>
                        <!--end::Menu 1-->
                        <!--end::Filter-->

                        <!--begin::Add New Guardian-->
                        <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#kt_modal_add_institution">
                            <i class="ki-outline ki-plus fs-2"></i>New Institution
                        </a>
                        <!--end::Add New Guardian-->
                    </div>
                    <!--end::Toolbar-->

                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->

            <!--begin::Card body-->
            <div class="card-body pt-0">
                <!--begin::Table-->
                    <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table" id="kt_institutions_table">
                        <thead>
                            <tr class="fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-30px">SL</th>
                                <th class="w-500px">Institution Name</th>
                                <th>EIIN Number</th>
                                <th class="d-none">Type (Filter)</th>
                                <th>Type</th>
                                <th class="w-100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            @foreach ($institutions as $institution)
                                <tr>
                                    <td class="pe-2">{{ $loop->index + 1 }}</td>
                                    <td class="text-gray-800 text-hover-primary mb-1">{{ $institution->name }}</td>
                                    <td class="text-center">{{ $institution->eiin_number }}</td>
                                    <td class="d-none">ucms_{{ $institution->type }}</td>
                                    <td class="text-center">{{ ucfirst($institution->type) }}</td>
                                    <td class="text-center">
                                        <a href="#" title="Edit Institution" data-bs-toggle="modal"
                                            data-bs-target="#kt_modal_edit_institution"
                                            data-institution-id="{{ $institution->id }}"
                                            class="btn btn-icon text-hover-primary w-30px h-30px me-3">
                                            <i class="ki-outline ki-pencil fs-2"></i>
                                        </a>
                                        <a href="#" title="Delete Institution" data-bs-toggle="tooltip"
                                            class="btn btn-icon text-hover-danger w-30px h-30px me-3 delete-institution"
                                            data-institution-id="{{ $institution->id }}">
                                            <i class="ki-outline ki-trash fs-2"></i>
                                        </a>
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
    <!--end::Institutions List-->


    <!--begin::Modal - Add Institution-->
    <div class="modal fade" id="kt_modal_add_institution" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_add_institution_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Add Institution</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-institutions-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_add_institution_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_institution_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_institution_header"
                            data-kt-scroll-wrappers="#kt_modal_add_institution_scroll" data-kt-scroll-offset="300px">

                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Institution Name</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="institution_name_add"
                                    class="form-control form-control-solid mb-3 mb-lg-0"
                                    placeholder="Write the name of the institution." required />
                                <!--end::Input-->
                            </div>
                            <!--end::Name Input group-->

                            <!--begin::Phone Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">EIIN Number</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="eiin_number_add" maxlength="6"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. 100954"
                                    required />
                                <!--end::Input-->
                            </div>
                            <!--end::Phone Input group-->

                            <!--begin::Type Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="d-flex align-items-center form-label mb-3 required">Institution Type</label>
                                <!--end::Label-->
                                <!--begin::Row-->
                                <div class="row">
                                    <!--begin::Col-->
                                    <div class="col-lg-6">
                                        <!--begin::Option-->
                                        <input type="radio" class="btn-check" name="institution_type_add" value="school"
                                            checked="checked" id="school_input_add" />
                                        <label
                                            class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                            for="school_input_add">
                                            <i class="las la-school fs-2x me-5"></i>
                                            <!--begin::Info-->
                                            <span class="d-block fw-semibold text-start">
                                                <span class="text-gray-900 fw-bold d-block fs-6">School</span>
                                            </span>
                                            <!--end::Info-->
                                        </label>
                                        <!--end::Option-->
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="col-lg-6">
                                        <!--begin::Option-->
                                        <input type="radio" class="btn-check" name="institution_type_add" value="college"
                                            id="college_input" />
                                        <label
                                            class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                            for="college_input">
                                            <i class="las la-university fs-2x me-5"></i>
                                            <!--begin::Info-->
                                            <span class="d-block fw-semibold text-start">
                                                <span class="text-gray -mt-2-900 fw-bold d-block fs-6">College</span>
                                            </span>
                                            <!--end::Info-->
                                        </label>
                                        <!--end::Option-->
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Row-->
                            </div>
                            <!--end::Type Input group-->

                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-institutions-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-institutions-modal-action="submit">
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
    <!--end::Modal - Add Institution-->


    <!--begin::Modal - Edit Institution-->
    <div class="modal fade" id="kt_modal_edit_institution" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_edit_institution_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Update Institution</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-institutions-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_institution_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_sibling_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_sibling_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_sibling_scroll" data-kt-scroll-offset="300px">

                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Institution Name</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="institution_name_edit"
                                    class="form-control form-control-solid mb-3 mb-lg-0"
                                    placeholder="Write the name of the institution." required />
                                <!--end::Input-->
                            </div>
                            <!--end::Name Input group-->

                            <!--begin::Phone Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">EIIN Number</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="eiin_number_edit" maxlength="6"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. 100954"
                                    required />
                                <!--end::Input-->
                            </div>
                            <!--end::Phone Input group-->

                            <!--begin::Type Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="d-flex align-items-center form-label mb-3 required">Institution Type</label>
                                <!--end::Label-->
                                <!--begin::Row-->
                                <div class="row">
                                    <!--begin::Col-->
                                    <div class="col-lg-6">
                                        <!--begin::Option-->
                                        <input type="radio" class="btn-check" name="institution_type_edit" value="school"
                                            checked="checked" id="school_input_edit" />
                                        <label
                                            class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                            for="school_input_edit">
                                            <i class="las la-school fs-2x me-5"></i>
                                            <!--begin::Info-->
                                            <span class="d-block fw-semibold text-start">
                                                <span class="text-gray-900 fw-bold d-block fs-6">School</span>
                                            </span>
                                            <!--end::Info-->
                                        </label>
                                        <!--end::Option-->
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="col-lg-6">
                                        <!--begin::Option-->
                                        <input type="radio" class="btn-check" name="institution_type_edit" value="college"
                                            id="college_input_edit" />
                                        <label
                                            class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                            for="college_input_edit">
                                            <i class="las la-university fs-2x me-5"></i>
                                            <!--begin::Info-->
                                            <span class="d-block fw-semibold text-start">
                                                <span class="text-gray-900 fw-bold d-block fs-6">College</span>
                                            </span>
                                            <!--end::Info-->
                                        </label>
                                        <!--end::Option-->
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Row-->
                            </div>
                            <!--end::Type Input group-->

                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-institutions-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-institutions-modal-action="submit">
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
    <!--end::Modal - Edit Institution-->
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        const routeDeleteInstitution = "{{ route('institutions.destroy', ':id') }}";
    </script>

    <script src="{{ asset('js/institutions/index.js') }}"></script>

    <script>
        document.getElementById("academic_menu").classList.add("here", "show");
        document.getElementById("institutions_link").classList.add("active");
    </script>
@endpush
