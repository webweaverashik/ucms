@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'All Sheets')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Sheets
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
                Sheet Groups </li>
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
                            data-kt-sheet-table-filter="search" class="form-control form-control-solid w-350px ps-12"
                            placeholder="Search Sheet">
                    </div>
                    <!--end::Search-->
                </div>
                <!--begin::Card title-->

                <!--begin::Card toolbar-->
                <div class="card-toolbar">
                    <!--begin::Toolbar-->
                    <div class="d-flex justify-content-end" data-kt-subscription-table-toolbar="base">
                        @can('sheets.create')
                            <!--begin::Add New Guardian-->
                            <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#kt_modal_add_sheet">
                                <i class="ki-outline ki-plus fs-2"></i>Sheet Group
                            </a>
                            <!--end::Add New Guardian-->
                        @endcan
                    </div>
                    <!--end::Toolbar-->

                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->

            <!--begin::Card body-->
            <div class="card-body pt-0">
                <!--begin::Table-->
                <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                    id="kt_all_sheets_table">
                    <thead>
                        <tr class="fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-30px">SL</th>
                            <th class="w-500px">Class Name</th>
                            <th>Price (৳)</th>
                            <th>No. of sale</th>
                            <th class="w-100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                        @foreach ($sheets as $sheet)
                            <tr>
                                <td class="pe-2">{{ $loop->index + 1 }}</td>
                                <td class="mb-1">
                                    <a href="{{ route('sheets.show', $sheet->id) }}">{{ $sheet->class->name }}
                                        ({{ $sheet->class->class_numeral }})
                                    </a>
                                </td>
                                <td>{{ $sheet->price }}</td>
                                <td>{{ $sheet->sheetPayments->count() }}</td>
                                <td>
                                    @can('sheets.edit')
                                        <a href="#" title="Edit Sheet Group" data-bs-toggle="modal"
                                            data-bs-target="#kt_modal_edit_sheet" data-sheet-id="{{ $sheet->id }}" data-sheet-price="{{ $sheet->price }}" data-sheet-class="{{ $sheet->class->name }} ({{ $sheet->class->class_numeral }})"
                                            class="btn btn-icon text-hover-primary w-30px h-30px">
                                            <i class="ki-outline ki-pencil fs-2"></i>
                                        </a>
                                    @endcan

                                    @can('sheets.delete')
                                        {{-- <a href="#" title="Delete Sheet Group" data-bs-toggle="tooltip"
                                            class="btn btn-icon text-hover-danger w-30px h-30px delete-sheet"
                                            data-sheet-id="{{ $sheet->id }}">
                                            <i class="ki-outline ki-trash fs-2"></i>
                                        </a> --}}
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
    <!--end::Institutions List-->


    <!--begin::Modal - Add Sheet-->
    <div class="modal fade" id="kt_modal_add_sheet" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_add_sheet_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Add New Sheet Group</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-sheet-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-5">
                    <!--begin::Form-->
                    <form id="kt_modal_add_sheet_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_sheet_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_sheet_header"
                            data-kt-scroll-wrappers="#kt_modal_add_sheet_scroll" data-kt-scroll-offset="300px">
                            <p class="text-gray-600 fs-5">Make sure the same sheet group should not be added twice.</p>

                            <!--begin::Class Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Select Class</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <select name="sheet_class_id" class="form-select form-select-solid" data-control="select2"
                                    data-placeholder="Select class" required>
                                    <option></option>
                                    @foreach ($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}
                                            ({{ $class->class_numeral }})
                                        </option>
                                    @endforeach
                                </select>
                                <!--end::Input-->
                            </div>
                            <!--end::Class Input group-->

                            <!--begin::Price Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Set Price</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="number" name="sheet_price" min="100"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. 2000" required />
                                <!--end::Input-->
                            </div>
                            <!--end::Price Input group-->
                        </div>
                        <!--end::Scroll-->

                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-sheet-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-sheet-modal-action="submit">
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
    <!--end::Modal - Add Sheet-->


    <!--begin::Modal - Edit Sheet-->
    <div class="modal fade" id="kt_modal_edit_sheet" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_edit_sheet_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold" id="kt_modal_edit_sheet_title">Update Sheet Price</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-sheet-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_sheet_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_sheet_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_sheet_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_sheet_scroll" data-kt-scroll-offset="300px">

                            <!--begin::Price Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Update Price</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="number" name="sheet_price_edit" min="100"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. 2000"
                                    required />
                                <!--end::Input-->
                            </div>
                            <!--end::Price Input group-->

                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-sheet-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-sheet-modal-action="submit">
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
    <!--end::Modal - Edit Sheet-->
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        const routeDeleteSheet = "{{ route('sheets.destroy', ':id') }}";
    </script>

    <script src="{{ asset('js/sheets/index.js') }}"></script>

    <script>
        document.getElementById("notes_sheets_menu").classList.add("here", "show");
        document.getElementById("all_sheets_link").classList.add("active");
    </script>
@endpush
