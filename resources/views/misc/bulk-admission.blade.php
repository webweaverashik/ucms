@push('page-css')
@endpush


@extends('layouts.app')

@section('title', 'Bulk Admission')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Bulk Admission
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
                    Systems </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Upload </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    @if (session('success'))
        <div
            class="alert alert-dismissible bg-light-success border border-success border-dashed d-flex flex-column flex-sm-row w-100 p-5 mb-10">
            <!--begin::Icon-->
            <i class="ki-duotone ki-message-text-2 fs-2hx text-success me-4 mb-5 mb-sm-0">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
            <!--end::Icon-->

            <!--begin::Content-->
            <div class="d-flex flex-column pe-0 pe-sm-10">
                <h5 class="mb-1 text-success">Excel file uploaded successfully</h5>
                <ul>
                    <li class="text-success fs-6">{{ session('success') }}</li>
                </ul>
            </div>
            <!--end::Content-->

            <!--begin::Close-->
            <button type="button"
                class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto"
                data-bs-dismiss="alert">
                <i class="ki-outline ki-cross fs-1 text-success"></i>
            </button>
            <!--end::Close-->
        </div>
    @endif


    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <h2>Upload excel file of student data</h2>
            </div>
            <!--begin::Card title-->
        </div>
        <!--end::Card header-->

        <!--begin::Notes Distribution Panel-->
        <div class="card-body py-4">
            <form id="bulk_admission_form" class="form" action="{{ route('bulk.admission.upload') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <!--begin::Excel file upload-->
                    <div class="fv-row col-lg-6 col-xl-5 mb-4">
                        <label for="formFile" class="required fw-semibold fs-6 mb-2">Upload Excel file</label>
                        <div class="input-group flex-nowrap">
                            <input class="form-control" type="file" id="formFile" accept=".xlsx, .xls" name="excel_file"
                                required>
                        </div>
                    </div>
                    <!--end::Excel file upload-->

                    <!--beging::Submit button-->
                    <div class="col-xl-1 col-lg-2 mt-lg-4 mb-8">
                        <button type="submit" class="btn btn-primary mt-4">
                            <span class="indicator-label">Submit</span>
                            <span class="indicator-progress">Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                    <!--end::Submit button-->

                </div>
            </form>
        </div>
        <!--end::Notes Distribution Panel-->
    </div>
    <!--end::Card-->
@endsection


@push('vendor-js')
@endpush

@push('page-js')
    <script>
        document.getElementById("settings_menu").classList.add("here", "show");
        document.getElementById("bulk_admission_link").classList.add("active");
    </script>
@endpush
