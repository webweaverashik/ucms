@push('page-css')
    <link href="{{ asset('css/notes/bulk-distribution.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Bulk Notes Distribution')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">

        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Bulk Distribution
        </h1>

        <span class="h-20px border-gray-300 border-start mx-4"></span>

        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('notes.distribution.index') }}" class="text-muted text-hover-primary">Notes & Sheets</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Bulk Distribution</li>
        </ul>
    </div>
@endsection

@section('content')
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h3 class="card-label fw-bold text-gray-800">
                    <i class="ki-outline ki-people fs-2 me-2 text-primary"></i>
                    Distribute Topic to Multiple Students
                </h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('notes.distribution.index') }}" class="btn btn-light-primary btn-sm">
                    <i class="ki-outline ki-arrow-left fs-4"></i>
                    Back to Distributions
                </a>
            </div>
        </div>
        <!--end::Card header-->

        <div class="card-body pt-0">
            {{-- Selection Form --}}
            <div class="row align-items-end g-5 mb-6">
                {{-- Sheet Group --}}
                <div class="col-lg-4">
                    <label class="required fw-semibold fs-6 mb-2">Sheet Group</label>
                    <div class="input-group input-group-solid flex-nowrap">
                        <span class="input-group-text">
                            <i class="ki-outline ki-folder fs-3"></i>
                        </span>
                        <div class="overflow-hidden flex-grow-1">
                            <select id="bulk_sheet_group_select"
                                class="form-select form-select-solid rounded-start-0 border-start" data-control="select2"
                                data-placeholder="Select Sheet Group">
                                <option></option>
                                @foreach ($sheetGroups as $sheet)
                                    <option value="{{ $sheet->id }}">
                                        {{ $sheet->class->name ?? 'Unknown' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Sheet Topic --}}
                <div class="col-lg-5">
                    <label class="required fw-semibold fs-6 mb-2">Sheet Topic</label>
                    <div class="input-group input-group-solid flex-nowrap">
                        <span class="input-group-text">
                            <i class="ki-outline ki-note-2 fs-3"></i>
                        </span>
                        <div class="overflow-hidden flex-grow-1">
                            <select id="bulk_sheet_topic_select"
                                class="form-select form-select-solid rounded-start-0 border-start" data-control="select2"
                                data-placeholder="Select Sheet Topic" disabled>
                                <option></option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Load Button --}}
                <div class="col-lg-3">
                    <button type="button" id="bulk_load_students_btn" class="btn btn-primary w-100" disabled>
                        <i class="ki-outline ki-people fs-3 me-2"></i>
                        Load Students
                    </button>
                </div>
            </div>

            {{-- Info Cards Container (Hidden initially) --}}
            <div id="bulk_info_cards" class="row g-4 mb-6 d-none">
                <div class="col-6 col-md-3">
                    <div class="card topic-count total h-100">
                        <div class="card-body text-center py-4">
                            <div id="stat_total_paid" class="fs-2hx fw-bold text-gray-800">0</div>
                            <div class="fs-7 text-gray-600">Total Paid</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card topic-count distributed h-100">
                        <div class="card-body text-center py-4">
                            <div id="stat_already_distributed" class="fs-2hx fw-bold text-primary">0</div>
                            <div class="fs-7 text-gray-600">Already Distributed</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card topic-count pending h-100">
                        <div class="card-body text-center py-4">
                            <div id="stat_pending" class="fs-2hx fw-bold text-warning">0</div>
                            <div class="fs-7 text-gray-600">Pending</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card topic-count selected h-100">
                        <div class="card-body text-center py-4">
                            <div id="stat_selected" class="fs-2hx fw-bold text-success">0</div>
                            <div class="fs-7 text-gray-600">Selected</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Topic Info Banner (Hidden initially) --}}
            <div id="bulk_topic_banner" class="alert alert-primary d-none mb-6">
                <div class="d-flex align-items-center">
                    <i class="ki-outline ki-information-4 fs-2 text-primary me-4"></i>
                    <div class="flex-grow-1">
                        <span class="fw-bold">Distributing:</span>
                        <span id="banner_topic_name" class="text-primary fw-semibold"></span>
                        <span class="text-muted mx-2">|</span>
                        <span>Subject: <span id="banner_subject_name" class="fw-semibold"></span></span>
                        <span class="text-muted mx-2">|</span>
                        <span>Class: <span id="banner_class_name" class="fw-semibold"></span></span>
                    </div>
                    <span id="banner_group_badge" class="badge ms-3"></span>
                </div>
            </div>

            {{-- Students Container --}}
            <div id="bulk_students_container">
                {{-- Empty State --}}
                <div id="bulk_empty_state" class="text-center py-15">
                    <div class="mb-5">
                        <i class="ki-outline ki-people fs-5tx text-gray-300"></i>
                    </div>
                    <h3 class="fs-4 fw-bold text-gray-700 mb-3">No Students Loaded</h3>
                    <p class="text-gray-500 fs-6 mb-0">
                        Select a Sheet Group and Topic, then click "Load Students" to view<br>
                        students who have paid but not yet received this topic.
                    </p>
                </div>

                {{-- Students List (Hidden initially) --}}
                <div id="bulk_students_list" class="d-none">
                    {{-- Search & Actions Bar --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-5">
                        <div class="position-relative w-250px">
                            <i class="ki-outline ki-magnifier fs-4 position-absolute top-50 translate-middle-y ms-4"></i>
                            <input type="text" id="bulk_student_search" class="form-control form-control-solid ps-12"
                                placeholder="Search students...">
                        </div>
                        <div class="d-flex gap-3">
                            <button type="button" id="bulk_select_all_btn" class="btn btn-light-primary btn-sm">
                                <i class="ki-outline ki-check-square fs-5 me-1"></i>
                                Select All
                            </button>
                            <button type="button" id="bulk_clear_selection_btn" class="btn btn-light-danger btn-sm">
                                <i class="ki-outline ki-cross-square fs-5 me-1"></i>
                                Clear Selection
                            </button>
                        </div>
                    </div>

                    {{-- Students Grid --}}
                    <div id="bulk_students_grid" class="row g-4 students-grid-container">
                        {{-- Student cards will be inserted here --}}
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex align-items-center justify-content-between mt-6 pt-6 border-top">
                        <p class="text-gray-600 fs-6 mb-0">
                            <span id="bulk_selection_summary">0 students selected</span>
                        </p>
                        <div class="d-flex gap-3">
                            <button type="button" id="bulk_reset_btn" class="btn btn-light">
                                <i class="ki-outline ki-arrows-circle fs-4 me-2"></i>
                                Reset
                            </button>
                            <button type="button" id="bulk_distribute_btn" class="btn btn-success" disabled>
                                <i class="ki-outline ki-send fs-4 me-2"></i>
                                Distribute to Selected
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('page-js')
    <script src="{{ asset('js/notes/bulk-distribution.js') }}"></script>

    <script>
        document.getElementById("notes_menu").classList.add("here", "show");
        document.getElementById("bulk_distribution_link")?.classList.add("active");
    </script>
@endpush
