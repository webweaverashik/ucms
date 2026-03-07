@push('page-css')
    <link href="{{ asset('css/attendances/index.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Student Attendance')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Today ({{ date('d-m-Y') }}) Attendance
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">Attendance</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Students</li>
        </ul>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-0 pt-6">
            <div class="card-title w-100">
                <form id="student_list_filter_form" class="row g-3 g-md-4 align-items-end w-100">
                    <input type="hidden" value="{{ date('d-m-Y') }}" name="attendance_date" id="attendance_date">

                    {{-- Branch Selection - Hidden for non-admin users --}}
                    <div class="col-12 col-md-6 col-lg-3 {{ !auth()->user()->hasRole('admin') ? 'd-none' : '' }}"
                        id="branch_selection_wrapper">
                        <label for="branch_id" class="form-label fw-semibold required mb-2">Branch</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-parcel fs-3"></i>
                            </span>
                            <select id="branch_id" class="form-select form-select-solid rounded-start-0 border-start"
                                name="branch_id" data-control="select2" data-placeholder="Select branch"
                                data-hide-search="true">
                                <option value="">Select branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}"
                                        {{ !auth()->user()->hasRole('admin') && auth()->user()->branch_id == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->branch_name }} ({{ $branch->branch_prefix }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Class Selection --}}
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label for="class_id" class="form-label fw-semibold required mb-2">Class</label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-book fs-3"></i>
                            </span>
                            <select id="class_id" class="form-select form-select-solid rounded-start-0 border-start"
                                name="class_id" data-control="select2" data-placeholder="Select class"
                                data-hide-search="false">
                                <option value="">Select class</option>
                                @foreach ($classnames as $classname)
                                    <option value="{{ $classname->id }}"
                                        data-class-numeral="{{ $classname->class_numeral }}">
                                        {{ $classname->name }} ({{ $classname->class_numeral }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Academic Group Selection - Shown only for Class 09-12 (Optional) --}}
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2 d-none" id="academic_group_wrapper">
                        <label for="academic_group" class="form-label fw-semibold mb-2">Group <span
                                class="text-muted fw-normal fs-8">(Optional)</span></label>
                        <div class="input-group input-group-solid flex-nowrap">
                            <span class="input-group-text">
                                <i class="ki-outline ki-abstract-26 fs-3"></i>
                            </span>
                            <select id="academic_group" class="form-select form-select-solid rounded-start-0 border-start"
                                name="academic_group" data-control="select2" data-placeholder="All Groups"
                                data-allow-clear="true" data-hide-search="true">
                                <option value="">All Groups</option>
                                @foreach ($academicGroups as $group)
                                    <option value="{{ $group }}">{{ $group }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Batch Selection (Loaded via AJAX) --}}
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <label for="batch_id" class="form-label fw-semibold required mb-2">Batch</label>
                        <div class="input-group input-group-solid flex-nowrap position-relative">
                            <span class="input-group-text">
                                <i class="ki-outline ki-people fs-3"></i>
                            </span>
                            <select id="batch_id" class="form-select form-select-solid rounded-start-0 border-start"
                                name="batch_id" data-control="select2" data-placeholder="Select batch"
                                data-hide-search="true">
                                <option value="">Select branch first</option>
                            </select>
                            <div id="batch_loader" class="position-absolute end-0 top-50 translate-middle-y me-10 d-none">
                                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1 flex-lg-grow-0" id="submit_button">
                                <i class="ki-outline ki-magnifier fs-3 me-1"></i>
                                <span class="d-none d-sm-inline">Load Students</span>
                                <span class="d-inline d-sm-none">Load</span>
                            </button>
                            <button type="button" class="btn btn-light-secondary flex-grow-1 flex-lg-grow-0"
                                id="reset_button">
                                <i class="ki-outline ki-arrows-circle fs-3"></i>
                                <span class="d-none d-sm-inline ms-1">Reset</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-body py-6 py-md-10">
            {{-- Loader --}}
            <div id="student_list_loader" class="text-center my-5 d-none">
                <span class="spinner-border text-primary" role="status"></span>
                <p class="mt-3 fw-semibold text-gray-600">Loading students...</p>
            </div>

            {{-- Off Day Warning Container --}}
            <div id="off_day_warning"></div>

            {{-- Bulk Action Buttons --}}
            <div class="d-flex align-items-center flex-wrap gap-2 gap-md-3 mb-4 mb-md-5 p-3 p-md-4 bg-light rounded border border-dashed border-gray-300 d-none"
                id="bulk_buttons">
                <span class="fw-bold text-gray-700 me-2 w-100 w-sm-auto mb-2 mb-sm-0">
                    <i class="ki-outline ki-abstract-26 fs-4 me-1"></i>
                    Quick Actions:
                </span>
                <div class="d-flex gap-2 flex-wrap">
                    <label class="quick-action-btn present-btn">
                        <input type="radio" name="mark_all" id="mark_all_present" value="present">
                        <i class="ki-outline ki-check-circle fs-5"></i>
                        <span>All Present</span>
                    </label>
                    <label class="quick-action-btn late-btn">
                        <input type="radio" name="mark_all" id="mark_all_late" value="late">
                        <i class="ki-outline ki-time fs-5"></i>
                        <span>All Late</span>
                    </label>
                    <label class="quick-action-btn absent-btn">
                        <input type="radio" name="mark_all" id="mark_all_absent" value="absent">
                        <i class="ki-outline ki-cross-circle fs-5"></i>
                        <span>All Absent</span>
                    </label>
                </div>
            </div>

            {{-- Student List Container --}}
            <div id="student_list_container"></div>

            {{-- Save Attendance Section --}}
            <div class="mt-4 mt-md-5 d-none d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 p-3 p-md-4 bg-light-info rounded"
                id="save_attendance_section">
                <div class="d-flex align-items-center">
                    <i class="ki-outline ki-information-5 fs-2 text-info me-3"></i>
                    <span class="text-gray-700 fs-7 fs-md-6">
                        <strong id="student_count">0</strong> students loaded. Mark attendance for all before saving.
                    </span>
                </div>
                <button class="btn btn-primary w-100 w-md-auto" id="save_attendance_button">
                    <i class="ki-outline ki-check-circle fs-3 me-1"></i>
                    Save Attendance
                </button>
            </div>
        </div>
    </div>
@endsection

@push('page-js')
    {{-- Pass PHP variables to JavaScript --}}
    <script>
        window.AttendanceConfig = {
            routes: {
                getBatches: @json(route('attendances.get_batches', ':branchId')),
                getStudents: @json(route('attendances.get_students')),
                storeBulk: @json(route('attendances.store_bulk')),
                studentShow: @json(route('students.show', ['student' => '__STUDENT_ID__']))
            },
            csrfToken: "{{ csrf_token() }}",
            isAdmin: {{ auth()->user()->hasRole('admin') ? 'true' : 'false' }},
            userBranchId: {{ auth()->user()->branch_id ?? 'null' }},
            groupRequiredClasses: @json($groupRequiredClasses)
        };
    </script>
    <script src="{{ asset('js/attendances/index.js') }}"></script>
    <script>
        // Sidebar menu active state
        document.getElementById("academic_menu")?.classList.add("here", "show");
        document.getElementById("attendance_link")?.classList.add("active");
    </script>
@endpush
