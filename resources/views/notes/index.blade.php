@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Notes Distribution')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Notes Distribution
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">Notes & Sheets</a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">Distribution</li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection

@section('content')
    <!--begin::Distributed Notes List-->
    <div class="container-xxl">
        <!--begin::Card-->
        <div class="card">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">
                <!--begin::Card title-->
                <div class="card-title">
                    <!--begin::Search-->
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                        <input type="text" data-kt-notes-distribution-table-filter="search"
                            class="form-control form-control-solid w-250px ps-12" placeholder="Search distributions...">
                    </div>
                    <!--end::Search-->
                </div>
                <!--begin::Card title-->

                <!--begin::Card toolbar-->
                <div class="card-toolbar">
                    <!--begin::Toolbar-->
                    <div class="d-flex justify-content-end gap-3" data-kt-subscription-table-toolbar="base">

                        <!--begin::Distribution Buttons-->
                        <a href="{{ route('notes.single.create') }}" class="btn btn-light-primary">
                            <i class="ki-outline ki-user fs-2"></i>
                            Single Distribution
                        </a>
                        <a href="{{ route('notes.bulk.create') }}" class="btn btn-light-success">
                            <i class="ki-outline ki-people fs-2"></i>
                            Bulk Distribution
                        </a>
                        <!--end::Distribution Buttons-->

                        <!--begin::Filter-->
                        <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                            data-kt-menu-placement="bottom-end">
                            <i class="ki-outline ki-filter fs-2"></i>Filter
                        </button>
                        <!--begin::Menu 1-->
                        <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                            <!--begin::Header-->
                            <div class="px-7 py-5">
                                <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                            </div>
                            <!--end::Header-->
                            <!--begin::Separator-->
                            <div class="separator border-gray-200"></div>
                            <!--end::Separator-->
                            <!--begin::Content-->
                            <div class="px-7 py-5" data-kt-notes-distribution-table-filter="form">
                                <!--begin::Input group - Sheet Group-->
                                <div class="mb-5">
                                    <label class="form-label fs-6 fw-semibold">Sheet Group:</label>
                                    <select id="filter_sheet_group" class="form-select form-select-solid fw-bold"
                                        data-kt-select2="true" data-placeholder="Select Sheet Group" data-allow-clear="true"
                                        data-kt-notes-distribution-table-filter="sheet_group">
                                        <option></option>
                                        @foreach ($sheetGroups as $sheet)
                                            <option value="{{ $sheet->id }}"
                                                data-filter-value="{{ $sheet->class->name }} ({{ $sheet->class->class_numeral }})">
                                                {{ $sheet->class->name }} ({{ $sheet->class->class_numeral }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group - Subject-->
                                <div class="mb-5">
                                    <label class="form-label fs-6 fw-semibold">Subject:</label>
                                    <select id="filter_subject" class="form-select form-select-solid fw-bold"
                                        data-kt-select2="true" data-placeholder="Select Subject" data-allow-clear="true"
                                        data-kt-notes-distribution-table-filter="subject" disabled>
                                        <option></option>
                                    </select>
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group - Topic-->
                                <div class="mb-10">
                                    <label class="form-label fs-6 fw-semibold">Topic:</label>
                                    <select id="filter_topic" class="form-select form-select-solid fw-bold"
                                        data-kt-select2="true" data-placeholder="Select Topic" data-allow-clear="true"
                                        data-kt-notes-distribution-table-filter="topic" disabled>
                                        <option></option>
                                    </select>
                                </div>
                                <!--end::Input group-->

                                <!--begin::Actions-->
                                <div class="d-flex justify-content-end">
                                    <button type="reset"
                                        class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                        data-kt-menu-dismiss="true"
                                        data-kt-notes-distribution-table-filter="reset">Reset</button>
                                    <button type="submit" class="btn btn-primary fw-semibold px-6"
                                        data-kt-menu-dismiss="true"
                                        data-kt-notes-distribution-table-filter="filter">Apply</button>
                                </div>
                                <!--end::Actions-->
                            </div>
                            <!--end::Content-->
                        </div>
                        <!--end::Menu 1-->
                        <!--end::Filter-->

                    </div>
                    <!--end::Toolbar-->
                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->

            <!--begin::Card body-->
            <div class="card-body pt-0">
                <!--begin::Table-->
                <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table" id="kt_notes_distribution_table">
                    <thead>
                        <tr class="fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-50px">SL</th>
                            <th class="min-w-150px">Topic Name</th>
                            <th class="min-w-120px">Subject</th>
                            <th class="min-w-150px">Sheet Group</th>
                            <th class="min-w-200px">Student</th>
                            <th class="min-w-100px">Distributed By</th>
                            <th class="min-w-120px">Distributed At</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                        @foreach ($notes_taken as $note)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="text-gray-800">{{ $note->sheetTopic->topic_name }}</td>
                                <td>{{ $note->sheetTopic->subject->name }}</td>
                                <td>
                                    <a href="{{ route('sheets.show', $note->class->sheet->id) }}"
                                        class="text-gray-800 text-hover-primary" target="_blank">
                                        {{ $note->class->name }} ({{ $note->class->class_numeral }})
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-circle symbol-35px me-3">
                                            <span class="symbol-label bg-light-primary text-primary fw-bold">
                                                {{ substr($note->student->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <a href="{{ route('students.show', $note->student->id) }}"
                                                class="text-gray-800 text-hover-primary fw-bold" target="_blank">
                                                {{ $note->student->name }}
                                            </a>
                                            <span class="text-muted fs-7">{{ $note->student->student_unique_id }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($note->distributedBy)
                                        <span class="badge badge-light-info">{{ $note->distributedBy->name }}</span>
                                    @else
                                        <span class="badge badge-light">System</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted">{{ $note->created_at->format('d M Y') }}</span>
                                    <span class="d-block text-muted fs-7">{{ $note->created_at->format('h:i A') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <!--end::Table-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>
    <!--end::Distributed Notes List-->
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script src="{{ asset('js/notes/index.js') }}"></script>

    <script>
        document.getElementById("notes_menu").classList.add("here", "show");
        document.getElementById("all_distributions_link")?.classList.add("active");
    </script>
@endpush
