@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'Finance Reports')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Finance Reports
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
                    Reports </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Finance </li>
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
            <div class="card-title w-100">
                <form class="row g-3 align-items-end w-100">
                    <!-- Date Selection -->
                    <div class="col-md-5">
                        <label for="finance_daterangepicker" class="form-label fw-semibold required">Select Date</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-calendar fs-3"></i>
                            </span>
                            <input type="text" class="form-control form-control-solid rounded-start-0 border-start"
                                placeholder="Pick date range" id="finance_daterangepicker">
                        </div>
                    </div>

                    <!-- Branch Selection -->
                    <div class="col-md-5">
                        <label for="student_paid_sheet_group" class="form-label fw-semibold required">Branch</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-note-2 fs-3"></i>
                            </span>
                            <select id="student_paid_sheet_group"
                                class="form-select form-select-solid rounded-start-0 border-start" data-control="select2"
                                data-placeholder="Select branch">
                                <option></option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->branch_name }}
                                        ({{ $branch->branch_prefix }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary" id="generate_finance_report">
                            Submit
                        </button>
                    </div>
                </form>
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
    <script src="{{ asset('js/reports/finance/index.js') }}"></script>

    <script>
        document.getElementById("reports_menu").classList.add("here", "show");
        document.getElementById("finance_report_link").classList.add("active");
    </script>
@endpush
