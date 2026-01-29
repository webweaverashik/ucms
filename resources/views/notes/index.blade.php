@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .dataTables_processing {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1px solid #e4e6ef !important;
            border-radius: 0.475rem !important;
            padding: 1rem !important;
            z-index: 999 !important;
        }

        .table-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .export-loading {
            pointer-events: none;
            opacity: 0.6;
        }
    </style>
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
    @php
        // Define badge colors for different branches
        $badgeColors = [
            'badge-light-primary',
            'badge-light-success',
            'badge-light-warning',
            'badge-light-danger',
            'badge-light-info',
        ];

        // Map branches to badge colors dynamically
        $branchColors = [];
        foreach ($branches as $index => $branch) {
            $branchColors[$branch->id] = $badgeColors[$index % count($badgeColors)];
            $branchColors[$branch->branch_name] = $badgeColors[$index % count($badgeColors)];
        }
    @endphp

    <!--begin::Distributed Notes List-->
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
                        <i class="ki-outline ki-user fs-2"></i> Single Distribution
                    </a>
                    <a href="{{ route('notes.bulk.create') }}" class="btn btn-light-success">
                        <i class="ki-outline ki-people fs-2"></i> Bulk Distribution
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
                                        <option value="{{ $sheet->id }}">
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
                                <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-kt-menu-dismiss="true"
                                    data-kt-notes-distribution-table-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                    data-kt-notes-distribution-table-filter="filter">Apply</button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Menu 1-->
                    <!--end::Filter-->

                    <!--begin::Export dropdown-->
                    <div class="dropdown">
                        <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                            data-kt-menu-placement="bottom-end" id="export_dropdown_btn">
                            <i class="ki-outline ki-exit-up fs-2"></i>Export
                        </button>
                        <!--begin::Menu-->
                        <div id="kt_notes_export_dropdown_menu"
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
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body pt-0">
            @if ($isAdmin)
                <!--begin::Tabs for Admin-->
                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="distributionBranchTabs"
                    role="tablist">
                    @foreach ($branches as $index => $branch)
                        @php
                            $branchDistCount = $distributionCounts[$branch->id] ?? 0;
                            $tabBadgeColor = $badgeColors[$index % count($badgeColors)];
                        @endphp
                        <li class="nav-item" role="presentation">
                            <a class="nav-link fw-bold {{ $index === 0 ? 'active' : '' }}"
                                id="tab-dist-branch-{{ $branch->id }}" data-bs-toggle="tab"
                                href="#kt_tab_dist_branch_{{ $branch->id }}" role="tab"
                                aria-controls="kt_tab_dist_branch_{{ $branch->id }}"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                data-branch-id="{{ $branch->id }}">
                                <i class="ki-outline ki-bank fs-4 me-1"></i>
                                {{ ucfirst($branch->branch_name) }}
                                <span class="badge {{ $tabBadgeColor }} ms-2 branch-count-badge"
                                    data-branch-id="{{ $branch->id }}">{{ $branchDistCount }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <!--end::Tabs for Admin-->

                <!--begin::Tab Content-->
                <div class="tab-content" id="distributionBranchTabsContent">
                    @foreach ($branches as $index => $branch)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                            id="kt_tab_dist_branch_{{ $branch->id }}" role="tabpanel"
                            aria-labelledby="tab-dist-branch-{{ $branch->id }}">
                            @include('notes.partials.notes-table-ajax', [
                                'tableId' => 'kt_notes_distribution_table_branch_' . $branch->id,
                                'branchId' => $branch->id,
                            ])
                        </div>
                    @endforeach
                </div>
                <!--end::Tab Content-->
            @else
                <!--begin::Single Table for Non-Admin-->
                @include('notes.partials.notes-table-ajax', [
                    'tableId' => 'kt_notes_distribution_table',
                    'branchId' => $branchId,
                ])
                <!--end::Single Table for Non-Admin-->
            @endif
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->
    <!--end::Distributed Notes List-->
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <!-- SheetJS for Excel export -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <!-- jsPDF for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.1/jspdf.plugin.autotable.min.js"></script>
@endpush

@push('page-js')
    <script>
        // Routes for AJAX
        const routeAjaxData = "{{ route('notes.distribution.ajax-data') }}";
        const routeExportData = "{{ route('notes.distribution.export-data') }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
        const isAdmin = @json($isAdmin);
        const branchIds = @json($branches->pluck('id')->toArray());
        const branchColors = @json($branchColors);
    </script>
    <script src="{{ asset('js/notes/index.js') }}"></script>
    <script>
        document.getElementById("notes_menu").classList.add("here", "show");
        document.getElementById("all_distributions_link")?.classList.add("active");
    </script>
@endpush
