@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'Reports')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Reports
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
                    System </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Reports </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative">
                    <!--begin::Student selection-->
                    <div class="fv-row mb-7 w-400px">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Select Student</label>
                        <!--end::Label-->

                        <!--begin::Solid input group style-->
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-faceid fs-3"></i>
                            </span>
                            <div class="overflow-hidden flex-grow-1">
                                <!-- Student Select -->
                                <select class="form-select form-select-solid rounded-start-0 border-start"
                                    data-control="select2" data-placeholder="Select a student" id="student_select_id">
                                    <option></option>
                                    @foreach ($students as $student)
                                        <option value="{{ $student->id }}">
                                            {{ $student->name }} ({{ $student->student_unique_id }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <!--end::Solid input group style-->
                    </div>
                    <!--end::Student selection-->

                    <!--begin::Sheet Group Input-->
                    <div class="fv-row mb-7 w-350px ps-4">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Sheet Group</label>
                        <!--end::Label-->

                        <!--begin::Solid input group style-->
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-note-2 fs-3"></i>
                            </span>
                            <div class="overflow-hidden flex-grow-1">
                                <!-- Sheet Group Select (Initially Disabled) -->
                                <select id="student_paid_sheet_group"
                                    class="form-select form-select-solid rounded-start-0 border-start"
                                    data-control="select2" data-placeholder="Select a sheet" disabled>
                                    <option></option>
                                </select>
                            </div>
                        </div>
                        <!--end::Solid input group style-->
                    </div>
                    <!--end::Sheet Group Input-->
                </div>
                <!--end::Search-->
            </div>
            <!--begin::Card title-->
        </div>
        <!--end::Card header-->

        <!--begin::Notes Distribution Panel-->
        <div class="card-body py-4" id="student_notes_distribution">
        </div>
        <!--end::Notes Distribution Panel-->
    </div>
    <!--end::Card-->
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        document.getElementById("reports_menu").classList.add("here", "show");
        document.getElementById("finance_report_link").classList.add("active");
    </script>
@endpush
