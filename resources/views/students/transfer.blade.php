@push('page-css')
@endpush


@extends('layouts.app')

@section('title', 'Transfer Student')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Transfer Student
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
                    Student Info </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Transfer </li>
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
                <form id="student_list_filter_form" class="row g-3 align-items-end w-100" novalidate="novalidate">
                    <!-- Student Selection -->
                    <div class="col-md-3">
                        <label for="student_class_group" class="form-label fw-semibold required">Select Student</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-faceid fs-3"></i>
                            </span>
                            <select class="form-select form-select-solid rounded-start-0 border-start" name="student_id"
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

                    <!-- Branch Selection -->
                    <div class="col-md-3">
                        <label for="student_branch_group" class="form-label fw-semibold required">Transfer to Branch</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-parcel fs-3"></i>
                            </span>
                            <select id="student_branch_group"
                                class="form-select form-select-solid rounded-start-0 border-start" name="branch_id"
                                data-control="select2" data-placeholder="Select branch" data-hide-search="true">
                                <option></option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">
                                        {{ $branch->branch_name }}
                                        ({{ $branch->branch_prefix }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Batch Selection -->
                    <div class="col-md-3">
                        <label for="student_batch_group" class="form-label fw-semibold required">Batch</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-people fs-3"></i>
                            </span>
                            <select id="student_batch_group"
                                class="form-select form-select-solid rounded-start-0 border-start" name="batch_id"
                                data-control="select2" data-placeholder="Select batch" data-hide-search="true">
                                <option></option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary" id="submit_button">
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
@endpush

@push('page-js')
    <script src="{{ asset('js/notes/distribution.js') }}"></script>


    <script>
        document.getElementById("admission_menu").classList.add("here", "show");
        document.getElementById("transfer_students_link").classList.add("active");
    </script>
@endpush
