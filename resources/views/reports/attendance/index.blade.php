@extends('layouts.app')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

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
                <a href="#" class="text-muted text-hover-primary">Reports</a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">Attendance</li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin::Card-->
    <div class="card mb-6 mb-xl-9">
        <!--begin::Card header-->
        <div class="card-header border-0 py-6">
            <div class="card-title w-100">
                <form id="student_list_filter_form" class="form w-100" novalidate="novalidate">
                    <div class="row g-4">
                        @php
                            $isAdmin = auth()->user()->hasRole('admin');
                            $userBranchId = auth()->user()->branch_id;
                        @endphp

                        <!-- Date Selection -->
                        <div class="col-lg-3">
                            <div class="fv-row">
                                <!--begin::Label-->
                                <label for="attendance_daterangepicker" class="required fw-semibold fs-6 mb-2">Select
                                    Date</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <div class="input-group input-group-solid">
                                    <span class="input-group-text">
                                        <i class="ki-outline ki-calendar fs-3"></i>
                                    </span>
                                    <input type="text"
                                        class="form-control form-control-solid rounded-start-0 border-start flex-grow-1 min-w-0"
                                        placeholder="Pick date range" id="attendance_daterangepicker" name="date_range">
                                </div>
                                <!--end::Input-->
                            </div>
                        </div>

                        <!-- Branch Selection (Admin Only) -->
                        @if ($isAdmin)
                            <div class="col-lg-2">
                                <div class="fv-row">
                                    <!--begin::Label-->
                                    <label for="student_branch_group" class="required fw-semibold fs-6 mb-2">Branch</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <div class="input-group input-group-solid">
                                        <span class="input-group-text">
                                            <i class="ki-outline ki-bank fs-3"></i>
                                        </span>
                                        <select id="student_branch_group"
                                            class="form-select form-select-solid rounded-start-0 border-start flex-grow-1 min-w-0"
                                            name="branch_id" data-control="select2" data-placeholder="Select branch"
                                            data-hide-search="true" data-dropdown-parent="#student_list_filter_form">
                                            <option value="">Select branch</option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}">
                                                    {{ $branch->branch_name }} ({{ $branch->branch_prefix }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!--end::Input-->
                                </div>
                            </div>
                        @else
                            <!-- Hidden branch input for non-admin users -->
                            <input type="hidden" id="student_branch_group" name="branch_id" value="{{ $userBranchId }}">
                        @endif

                        <!-- Class Selection -->
                        <div class="{{ $isAdmin ? 'col-lg-3' : 'col-lg-3' }}">
                            <div class="fv-row">
                                <!--begin::Label-->
                                <label for="student_class_group" class="required fw-semibold fs-6 mb-2">Class</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <div class="input-group input-group-solid">
                                    <span class="input-group-text">
                                        <i class="ki-outline ki-book fs-3"></i>
                                    </span>
                                    <select id="student_class_group"
                                        class="form-select form-select-solid rounded-start-0 border-start flex-grow-1 min-w-0"
                                        name="class_id" data-control="select2" data-placeholder="Select class"
                                        data-hide-search="false" data-dropdown-parent="#student_list_filter_form">
                                        <option value="">Select class</option>
                                        @foreach ($classnames as $classname)
                                            <option value="{{ $classname->id }}">
                                                {{ $classname->name }} ({{ $classname->class_numeral }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <!--end::Input-->
                            </div>
                        </div>

                        <!-- Batch Selection -->
                        <div class="{{ $isAdmin ? 'col-lg-2' : 'col-lg-3' }}">
                            <div class="fv-row">
                                <!--begin::Label-->
                                <label for="student_batch_group" class="required fw-semibold fs-6 mb-2">Batch</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <div class="input-group input-group-solid">
                                    <span class="input-group-text">
                                        <i class="ki-outline ki-people fs-3"></i>
                                    </span>
                                    <select id="student_batch_group"
                                        class="form-select form-select-solid rounded-start-0 border-start flex-grow-1 min-w-0"
                                        name="batch_id" data-control="select2" data-placeholder="Select batch"
                                        data-hide-search="true" data-dropdown-parent="#student_list_filter_form"
                                        @if ($isAdmin) disabled @endif>
                                        <option value="">Select batch</option>
                                        @if (!$isAdmin)
                                            @foreach ($batches as $batch)
                                                <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <!--end::Input-->
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="{{ $isAdmin ? 'col-lg-2' : 'col-lg-3' }}">
                            <div class="fv-row">
                                <!-- Invisible label to match height spacing -->
                                <label class="fw-semibold fs-6 mb-2 invisible">Actions</label>

                                <div class="d-flex gap-2">
                                    <!--begin::Submit Button-->
                                    <button type="submit" class="btn btn-primary flex-grow-1" id="submit_button"
                                        data-kt-indicator="off">
                                        <span class="indicator-label">
                                            <i class="ki-outline ki-magnifier fs-3 me-1"></i>Generate
                                        </span>
                                        <span class="indicator-progress">Please wait...
                                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                        </span>
                                    </button>
                                    <!--end::Submit Button-->
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>
        <!--end::Card header-->
    </div>
    <!--end::Card-->

    <!--begin::Card-->
    <div class="card" id="attendance_report_panel">
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                    <input type="text" data-attendance-table-filter="search"
                        class="form-control form-control-solid w-350px ps-12" placeholder="Search Students">
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
                                <a href="#" class="menu-link px-3" data-row-export="copy">Copy to clipboard</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="excel">Export as Excel</a>
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
    <!--end::Card-->
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush


@push('page-js')
    <script>
        // Pass configuration to JavaScript
        window.AttendanceReportConfig = {
            isAdmin: @json($isAdmin),
            userBranchId: @json($userBranchId),
            getBatchesUrl: "{{ route('attendances.get_batches', ':branchId') }}"
        };
    </script>

    <script src="{{ asset('js/reports/attendance/index.js') }}"></script>

    <script>
        document.getElementById("reports_menu").classList.add("here", "show");
        document.getElementById("attendance_report_link").classList.add("active");
    </script>
@endpush
