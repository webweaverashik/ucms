@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'Student Attendance')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Attendance Report
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
                Attendance </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin::Card-->
    <div class="card mb-6 mb-xl-9">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <div class="card-title w-100 mb-10">
                <form id="student_list_filter_form" class="row g-4 align-items-end w-100">
                    <!-- Date Selection -->
                    <div class="col-md-3">
                        <label for="attendance_daterangepicker" class="form-label fw-semibold required">Select Date</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-calendar fs-3"></i>
                            </span>
                            <input type="text" class="form-control form-control-solid rounded-start-0 border-start"
                                placeholder="Pick date range" id="attendance_daterangepicker" name="date_range">
                        </div>
                    </div>


                    <!-- Branch Selection -->
                    <div class="col-md-2 @if (!auth()->user()->hasRole('admin')) d-none @endif">
                        <label for="student_branch_group" class="form-label fw-semibold required">Branch</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-note-2 fs-3"></i>
                            </span>
                            <select id="student_branch_group"
                                class="form-select form-select-solid rounded-start-0 border-start" name="branch_id"
                                data-control="select2" data-placeholder="Select branch" data-hide-search="true">
                                <option></option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @if ($loop->first) selected @endif>
                                        {{ $branch->branch_name }}
                                        ({{ $branch->branch_prefix }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>



                    <!-- Class Selection -->
                    <div class="col-md-3">
                        <label for="student_class_group" class="form-label fw-semibold required">Class</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-note-2 fs-3"></i>
                            </span>
                            <select id="student_class_group"
                                class="form-select form-select-solid rounded-start-0 border-start" name="class_id"
                                data-control="select2" data-placeholder="Select class" data-hide-search="false">
                                <option></option>
                                @foreach ($classnames as $classname)
                                    <option value="{{ $classname->id }}" @if ($loop->first) selected @endif>
                                        {{ $classname->name }}
                                        ({{ $classname->class_numeral }})
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
                                <i class="ki-outline ki-note-2 fs-3"></i>
                            </span>
                            <select id="student_batch_group"
                                class="form-select form-select-solid rounded-start-0 border-start" name="batch_id"
                                data-control="select2" data-placeholder="Select batch" data-hide-search="true">
                                <option></option>
                                @foreach ($batches as $batch)
                                    <option value="{{ $batch->id }}" @if ($loop->first) selected @endif>
                                        {{ $batch->name }} ({{ $batch->branch->branch_name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary" id="submit_button">
                            Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!--end::Card header-->
    </div>
    <!--end::Card-->

    <!--begin::Card-->
    {{-- Hide the table card on initial load --}}
    <div class="card" id="attendance_report_panel">
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> 
                    <input type="text" data-attendance-table-filter="search" class="form-control form-control-solid w-350px ps-12"
                        placeholder="Search Students">

                </div>
                <!--end::Search-->

                <!--begin::Export hidden buttons-->
                <div id="kt_hidden_export_buttons" class="d-none"></div>
                <!--end::Export buttons-->
            </div>
            <!--begin::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-attendance-table-toolbar="base">
                    <!--begin::Export dropdown-->
                    <div class="dropdown">
                        <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                            data-kt-menu-placement="bottom-end">
                            <i class="ki-outline ki-exit-up fs-2"></i>Export
                        </button>

                        <!--begin::Menu-->
                        <div id="kt_table_report_dropdown_menu"
                            class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                            data-kt-menu="true">
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="copy">Copy to
                                    clipboard</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="excel">Export as
                                    Excel</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="csv">Export as CSV</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="pdf">Export as PDF</a>
                            </div>
                            <!--end::Menu item-->
                        </div>
                        <!--end::Menu-->
                    </div>
                    <!--end::Export dropdown-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <div class="card-body py-4">
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                id="kt_attendance_report_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">#</th>
                        <th class="">Student Name & Unique ID</th>
                        <th>ClassName</th>
                        <th>Batch</th>
                        <th class="">Total Present</th>
                        <th class="">Total Absent</th>
                        <th class="">Total Late</th>
                        <th class="not-export">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold" id="kt_attendance_report_table_body">
                    
                </tbody>
            </table>
        </div>
    </div>
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush


@push('page-js')
    <script>

    </script>

    <script src="{{ asset('js/reports/attendance/index.js') }}"></script>

    <script>
        document.getElementById("reports_menu").classList.add("here", "show");
        document.getElementById("attendance_report_link").classList.add("active");
    </script>
@endpush
