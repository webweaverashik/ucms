@push('page-css')
@endpush


@extends('layouts.app')

@section('title', 'All Branches')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Branches
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
                Branches </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin::Row-->
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-5 g-xl-9">
        @foreach ($branches as $branch)
            <!--begin::Col-->
            <div class="col-md-6">
                <!--begin::Card-->
                <div class="card card-flush h-md-100 py-6 border-hover-primary">
                    <!--begin::Card header-->
                    <div class="card-header">
                        <!--begin::Card title-->
                        <div class="card-title">
                            <h2>{{ $branch->branch_name }} Branch ({{ $branch->branch_prefix }})</h2><br>
                        </div>
                        <!--end::Card title-->
                    </div>
                    <!--end::Card header-->
                    <!--begin::Card body-->
                    <div class="card-body pt-1">
                        <h4 class="text-gray-600"><i class="ki-outline ki-geolocation fs-4 me-2"></i>{{ $branch->address }}</h4>
                        <h4 class="text-gray-600"><i class="bi bi-telephone fs-4 me-2"></i>{{ $branch->phone_number }}</h4>
                        <div class="fw-bold text-gray-600 mt-10 fs-5">Total students on this branch:
                            {{ count($branch->students) }}</div>
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
            </div>
            <!--end::Col-->
        @endforeach
    </div>
    <!--end::Row-->

@endsection


@push('vendor-js')
@endpush

@push('page-js')
    <script>
        document.getElementById("settings_menu").classList.add("here", "show");
        document.getElementById("branch_link").classList.add("active");
    </script>
@endpush
