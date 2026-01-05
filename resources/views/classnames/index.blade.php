@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'All Class')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Class
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
                Class </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    @php
        // Preloading permissions checking
        $canEditClass = auth()->user()->can('classes.edit');
        $canDeleteClass = auth()->user()->can('classes.delete');
    @endphp

    <!--begin:::Tabs-->
    <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
        <!--begin:::Tab item-->
        <li class="nav-item">
            <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_active_classnames_tab"><i
                    class="ki-outline ki-home fs-3 me-2"></i>Active Class
            </a>
        </li>
        <!--end:::Tab item-->

        <!--begin:::Tab item-->
        <li class="nav-item">
            <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_inactive_classnames_tab"><i
                    class="ki-outline ki-book-open fs-3 me-2"></i>Inactive Class
            </a>
        </li>
        <!--end:::Tab item-->

        @can('classes.create')
            <!--begin:::Tab item-->
            <li class="nav-item ms-auto">
                <!--begin::Action menu-->
                <a href="#" class="btn btn-primary ps-7" data-bs-toggle="modal" data-bs-target="#kt_modal_add_class"><i
                        class="ki-outline ki-plus fs-2 me-0"></i> Add Class</a>
                <!--end::Menu-->
            </li>
            <!--end:::Tab item-->
        @endcan
    </ul>
    <!--end:::Tabs-->

    <!--begin:::Tab content-->
    <div class="tab-content" id="myTabContent">
        <!--begin:::Tab pane-->
        <div class="tab-pane fade show active" id="kt_active_classnames_tab" role="tabpanel">
            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text"
                                data-kt-active-class-table-filter="search"
                                class="form-control form-control-solid w-350px ps-12" placeholder="Search in active class">
                        </div>
                        <!--end::Search-->
                    </div>
                    <!--begin::Card title-->

                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Toolbar-->
                        <div class="d-flex justify-content-end" data-kt-active-class-table-toolbar="base">
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
                                <div class="px-7 py-5" data-kt-active-class-table-filter="form">
                                    <!--begin::Input group-->
                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">Class Numeral:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true"
                                            data-kt-active-class-table-filter="product" data-hide-search="true">
                                            <option></option>
                                            @foreach (range(4, 12) as $i)
                                                <option value="Active_{{ sprintf('%02d', $i) }}">{{ sprintf('%02d', $i) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!--end::Input group-->

                                    <!--begin::Input group-->
                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">Activation Status:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true"
                                            data-kt-active-class-table-filter="status" data-hide-search="true">
                                            <option></option>
                                            <option value="Active_class">Active</option>
                                            <option value="Inactive_class">Inactive</option>
                                        </select>
                                    </div>
                                    <!--end::Input group-->

                                    <!--begin::Actions-->
                                    <div class="d-flex justify-content-end">
                                        <button type="reset"
                                            class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                            data-kt-menu-dismiss="true"
                                            data-kt-active-class-table-filter="reset">Reset</button>
                                        <button type="submit" class="btn btn-primary fw-semibold px-6"
                                            data-kt-menu-dismiss="true"
                                            data-kt-active-class-table-filter="filter">Apply</button>
                                    </div>
                                    <!--end::Actions-->
                                </div>
                                <!--end::Content-->
                            </div>
                            <!--end::Menu 1-->
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
                    <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                        id="kt_active_classes_table">
                        <thead>
                            <tr class="fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-25px">SL</th>
                                <th class="">Class Name</th>
                                <th class="w-400px">Description</th>
                                <th>Class Numeral</th>
                                <th class="d-none">Class Numeral (filter)</th>
                                <th>Total Active Students</th>
                                <th>Inactive Students</th>
                                <th>Status</th>
                                <th class="d-none">Status (filter)</th>
                                <th class="w-100px not-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            @foreach ($active_classes as $classname)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('classnames.show', $classname->id) }}">
                                            {{ $classname->name }} <i
                                                class="text-muted">({{ $classname->class_numeral }})</i>
                                        </a>
                                    </td>
                                    <td>{{ $classname->description ?? 'This is a sample description. Update the class description to change this.' }}
                                    </td>

                                    <td>{{ $classname->class_numeral }}</td>
                                    <td class="d-none">Active_{{ $classname->class_numeral }}</td>

                                    <td>{{ $classname->active_students_count }}</td>
                                    <td>{{ $classname->inactive_students_count }}</td>

                                    <td>
                                        @if ($classname->is_active == true)
                                            <span class="badge badge-success rounded-pill">Active</span>
                                        @else
                                            <span class="badge badge-danger rounded-pill">Inactive</span>
                                        @endif
                                    </td>

                                    <td class="d-none">
                                        @if ($classname->is_active == true)
                                            Active_class
                                        @else
                                            Inactive_class
                                        @endif
                                    </td>

                                    <td>
                                        @if ($canEditClass)
                                            <a href="#" title="Edit Class" data-bs-toggle="modal"
                                                data-bs-target="#kt_modal_edit_class"
                                                data-class-id="{{ $classname->id }}"
                                                class="btn btn-icon text-hover-primary w-30px h-30px">
                                                <i class="ki-outline ki-pencil fs-2"></i>
                                            </a>
                                        @endif

                                        @if ($canDeleteClass && $classname->students_count === 0)
                                            <a href="#" title="Delete Class"
                                                data-active-class-id="{{ $classname->id }}"
                                                class="btn btn-icon text-hover-danger w-30px h-30px class-delete-button">
                                                <i class="ki-outline ki-trash fs-2"></i>
                                            </a>
                                        @endif
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
        <!--end:::Tab pane-->

        <!--begin:::Tab pane-->
        <div class="tab-pane fade show" id="kt_inactive_classnames_tab" role="tabpanel">
            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text"
                                data-kt-inactive-class-table-filter="search"
                                class="form-control form-control-solid w-350px ps-12"
                                placeholder="Search in inactive class">
                        </div>
                        <!--end::Search-->
                    </div>
                    <!--begin::Card title-->

                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::Toolbar-->
                        <div class="d-flex justify-content-end" data-kt-inactive-class-table-toolbar="base">
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
                                <div class="px-7 py-5" data-kt-inactive-class-table-filter="form">
                                    <!--begin::Input group-->
                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">Class Numeral:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true"
                                            data-kt-inactive-class-table-filter="product" data-hide-search="true">
                                            <option></option>
                                            @foreach (range(4, 12) as $i)
                                                <option value="Inactive_{{ sprintf('%02d', $i) }}">
                                                    {{ sprintf('%02d', $i) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!--end::Input group-->

                                    <!--begin::Input group-->
                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">Activation Status:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true"
                                            data-kt-inactive-class-table-filter="status" data-hide-search="true">
                                            <option></option>
                                            <option value="Active_class">Active</option>
                                            <option value="Inactive_class">Inactive</option>
                                        </select>
                                    </div>
                                    <!--end::Input group-->

                                    <!--begin::Actions-->
                                    <div class="d-flex justify-content-end">
                                        <button type="reset"
                                            class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                            data-kt-menu-dismiss="true"
                                            data-kt-inactive-class-table-filter="reset">Reset</button>
                                        <button type="submit" class="btn btn-primary fw-semibold px-6"
                                            data-kt-menu-dismiss="true"
                                            data-kt-inactive-class-table-filter="filter">Apply</button>
                                    </div>
                                    <!--end::Actions-->
                                </div>
                                <!--end::Content-->
                            </div>
                            <!--end::Menu 1-->
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
                    <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                        id="kt_inactive_classes_table">
                        <thead>
                            <tr class="fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-25px">SL</th>
                                <th class="">Class Name</th>
                                <th class="w-400px">Description</th>
                                <th>Class Numeral</th>
                                <th class="d-none">Class Numeral (filter)</th>
                                <th>Total Active Students</th>
                                <th>Status</th>
                                <th class="d-none">Status (filter)</th>
                                <th class="w-100px not-export">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            @foreach ($inactive_classes as $classname)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('classnames.show', $classname->id) }}">
                                            {{ $classname->name }} <i
                                                class="text-muted">({{ $classname->class_numeral }})</i>
                                        </a>
                                    </td>
                                    <td>{{ $classname->description ?? 'This is a sample description. Update the class description to change this.' }}
                                    </td>

                                    <td>{{ $classname->class_numeral }}</td>
                                    <td class="d-none">Inactive_{{ $classname->class_numeral }}</td>

                                    <td>{{ $classname->active_students_count }}</td>

                                    <td>
                                        @if ($classname->is_active == true)
                                            <span class="badge badge-success rounded-pill">Active</span>
                                        @else
                                            <span class="badge badge-danger rounded-pill">Inactive</span>
                                        @endif
                                    </td>

                                    <td class="d-none">
                                        @if ($classname->is_active == true)
                                            Active_class
                                        @else
                                            Inactive_class
                                        @endif
                                    </td>

                                    <td>
                                        @if ($canEditClass)
                                            <a href="#" title="Edit Class" data-bs-toggle="modal"
                                                data-bs-target="#kt_modal_edit_class"
                                                data-class-id="{{ $classname->id }}"
                                                class="btn btn-icon text-hover-primary w-30px h-30px">
                                                <i class="ki-outline ki-pencil fs-2"></i>
                                            </a>
                                        @endif
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
        <!--end:::Tab pane-->
    </div>
    <!--end:::Tab content-->



    <!--begin::Modal - Add class-->
    <div class="modal fade" id="kt_modal_add_class" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_add_class_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Add New Class</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-add-class-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_add_class_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_class_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_add_class_header"
                            data-kt-scroll-wrappers="#kt_modal_add_class_scroll" data-kt-scroll-offset="300px">

                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Class Name</label>
                                <input type="text" name="class_name_add"
                                    class="form-control form-control-solid mb-3 mb-lg-0"
                                    placeholder="Write name of the class" required />
                            </div>
                            <!--end::Name Input group-->

                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2 required">Class Numeral</label>
                                <select name="class_numeral_add" class="form-select form-select-solid"
                                    data-control="select2" data-hide-search="true"
                                    data-dropdown-parent="#kt_modal_add_class" data-placeholder="Select numeral" required>
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
                                <input type="text" name="description_add"
                                    class="form-control form-control-solid mb-3 mb-lg-0"
                                    placeholder="Write something about the class" />
                            </div>
                            <!--end::Name Input group-->
                        </div>
                        <!--end::Scroll-->

                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-add-class-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-add-class-modal-action="submit">
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
    <!--end::Modal - Add class-->


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

                            <!--begin::Class Name Input-->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Class Name</label>
                                <input type="text" name="class_name_edit"
                                    class="form-control form-control-solid mb-3 mb-lg-0"
                                    placeholder="Write name of the class" required />
                            </div>
                            <!--end::Class Name Input-->

                            <!--begin::Class Numeral Input-->
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
                            <!--end::Class Numeral Input-->

                            <!--begin::Description Input group-->
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Description <span
                                        class="text-muted">(Optional)</span></label>
                                <input type="text" name="description_edit"
                                    class="form-control form-control-solid mb-3 mb-lg-0"
                                    placeholder="Write something about the class" />
                            </div>
                            <!--end::Description Input group-->

                            <!--begin::Status Input-->
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2 required">Activation Status</label>
                                <!--begin::Solid input group style-->
                                <div class="row">
                                    <!--begin::New Month Year-->
                                    <div class="col-lg-6">
                                        <!--begin::Option-->
                                        <input type="radio" class="btn-check" name="activation_status"
                                            value="active" id="active_radio" />
                                        <label
                                            class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                            for="active_radio">
                                            <i class="ki-outline ki-abstract fs-2x me-5"></i>
                                            <!--begin::Info-->
                                            <span class="d-block fw-semibold text-start">
                                                <span class="text-gray-900 fw-bold d-block fs-6">Active</span>
                                            </span>
                                            <!--end::Info-->
                                        </label>
                                        <!--end::Option-->
                                    </div>
                                    <!--end::New Month Year-->

                                    <!--begin::Old Month Year-->
                                    <div class="col-lg-6">
                                        <!--begin::Option-->
                                        <input type="radio" class="btn-check" name="activation_status"
                                            value="inactive" id="inactive_radio" />
                                        <label
                                            class="btn btn-outline btn-outline-dashed btn-active-light-danger p-3 d-flex align-items-center"
                                            for="inactive_radio">
                                            <i class="ki-outline ki-abstract-20 fs-2x me-5"></i>
                                            <!--begin::Info-->
                                            <span class="d-block fw-semibold text-start">
                                                <span class="text-gray-900 fw-bold d-block fs-6">Inactive</span>
                                            </span>
                                            <!--end::Info-->
                                        </label>
                                        <!--end::Option-->
                                    </div>
                                    <!--end::Old Month Year-->
                                </div>
                                <!--end::Solid input group style-->
                            </div>
                            <!--end::Status Input-->
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
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        const routeDeleteActiveClass = "{{ route('classnames.destroy', ':id') }}";
    </script>

    <script src="{{ asset('js/classnames/index.js') }}"></script>

    <script>
        document.getElementById("academic_menu").classList.add("here", "show");
        document.getElementById("class_link").classList.add("active");
    </script>
@endpush
