@push('page-css')
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
    <!--begin::Row-->
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-5 g-xl-9">
        <!--begin::Add new card-->
        <div class="col-md-4">
            <!--begin::Card-->
            <div class="card h-md-100">
                <!--begin::Card body-->
                <div class="card-body d-flex flex-center">
                    <!--begin::Button-->
                    <button type="button" class="btn btn-clear d-flex flex-column flex-center" data-bs-toggle="modal"
                        data-bs-target="#kt_modal_add_class">
                        <!--begin::Illustration-->
                        <img src="{{ asset('assets/media/illustrations/sketchy-1/4.png') }}" alt=""
                            class="mw-100 mh-150px mb-7" />
                        <!--end::Illustration-->
                        <!--begin::Label-->
                        <div class="btn btn-primary fw-bold fs-5">Add New Class</div>
                        <!--end::Label-->
                    </button>
                    <!--begin::Button-->
                </div>
                <!--begin::Card body-->
            </div>
            <!--begin::Card-->
        </div>
        <!--begin::Add new card-->

        @foreach ($classnames as $classname)
            <!--begin::Col-->
            <div class="col-md-4">
                <!--begin::Card-->
                <div class="card card-flush h-md-100 border-hover-primary">
                    <!--begin::Card header-->
                    <div class="card-header">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <h2>{{ $classname->name }} ({{ $classname->class_numeral }})</h2>
                        </div>
                        <!--end::Card title-->
                    </div>
                    <!--end::Card header-->
                    <!--begin::Card body-->
                    <div class="card-body pt-1">
                        <div class="fw-bold text-gray-700 mb-5">{{ $classname->description ?? 'This is a sample description. Update the class description to change this.' }}</div>
                        
                        <div class="fw-bold text-gray-600 mb-5"><i class="fas fa-users me-2"></i>Total active students:
                            {{ $classname->activeStudents->count() }}</div>
                    </div>
                    <!--end::Card body-->
                    <!--begin::Card footer-->
                    <div class="card-footer flex-wrap pt-0">
                        <a href="{{ route('classnames.show', $classname->id) }}"
                            class="btn btn-light btn-active-primary my-1 me-2">View Class</a>
                        <button type="button" class="btn btn-light btn-active-light-primary my-1" data-bs-toggle="modal"
                            data-bs-target="#kt_modal_edit_class" data-class-id="{{ $classname->id }}">Edit Class</button>
                    </div>
                    <!--end::Card footer-->
                </div>
                <!--end::Card-->
            </div>
            <!--end::Col-->
        @endforeach

    </div>
    <!--end::Row-->

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
                                <select name="class_numeral_add" class="form-select form-select-solid" data-control="select2"
                                    data-hide-search="true" data-dropdown-parent="#kt_modal_add_class"
                                    data-placeholder="Select numeral" required>
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
                                <label class="fw-semibold fs-6 mb-2">Class Numeral <span class="text-muted">(Cannot change)</span></label>
                                <select name="class_numeral_edit" class="form-select form-select-solid" data-control="select2"
                                    data-hide-search="true" data-dropdown-parent="#kt_modal_edit_class"
                                    data-placeholder="Select numeral" disabled>
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
    <script src="{{ asset('js/classnames/index.js') }}"></script>

    <script>
        document.getElementById("academic_menu").classList.add("here", "show");
        document.getElementById("class_link").classList.add("active");
    </script>
@endpush
