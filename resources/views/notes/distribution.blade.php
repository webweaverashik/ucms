@push('page-css')
    <link href="{{ asset('css/notes/distribution.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Assign Notes')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">

        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Notes Distribution
        </h1>

        <span class="h-20px border-gray-300 border-start mx-4"></span>

        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">Notes & Sheets</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Assign Notes</li>
        </ul>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-body py-7">

            <div class="row align-items-end g-5">

                <!-- Student -->
                <div class="col-lg-5">
                    <label class="required fw-semibold fs-6 mb-2">Select Student</label>
                    <div class="input-group input-group-solid flex-nowrap">
                        <span class="input-group-text">
                            <i class="ki-outline ki-faceid fs-3"></i>
                        </span>
                        <div class="overflow-hidden flex-grow-1">
                            <select id="student_select_id"
                                class="form-select form-select-solid rounded-start-0 border-start" data-control="select2"
                                data-placeholder="Select a student">
                                <option></option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}">
                                        {{ $student->name }} ({{ $student->student_unique_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Sheet -->
                <div class="col-lg-5">
                    <label class="required fw-semibold fs-6 mb-2">Sheet Group</label>
                    <div class="input-group input-group-solid flex-nowrap">
                        <span class="input-group-text">
                            <i class="ki-outline ki-note-2 fs-3"></i>
                        </span>
                        <div class="overflow-hidden flex-grow-1">
                            <select id="student_paid_sheet_group"
                                class="form-select form-select-solid rounded-start-0 border-start" data-control="select2"
                                data-placeholder="Select a sheet" disabled>
                                <option></option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Button -->
                <div class="col-lg-2">
                    <label class="fw-semibold fs-6 mb-2 d-block">&nbsp;</label>
                    <button type="button" id="load_topics_btn" class="btn btn-primary w-100" disabled>
                        <i class="ki-outline ki-eye fs-3 me-2"></i>
                        Load Topics
                    </button>
                </div>

            </div>

            <!-- Topics -->
            <div id="student_notes_distribution" class="mt-6"></div>

        </div>
    </div>
@endsection

@push('page-js')
    <script src="{{ asset('js/notes/distribution.js') }}"></script>

    <script>
        document.getElementById("notes_sheets_menu").classList.add("here", "show");
        document.getElementById("notes_distribution_link").classList.add("active");
    </script>
@endpush
