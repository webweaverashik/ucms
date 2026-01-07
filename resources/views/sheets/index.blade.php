@push('page-css')
    <style>
        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Sort option active state */
        .sort-option.active {
            background-color: var(--bs-light-primary) !important;
            color: var(--bs-primary) !important;
        }
    </style>
@endpush

@extends('layouts.app')

@section('title', 'All Sheets')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Sheets
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">Academic</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Sheet Groups</li>
        </ul>
    </div>
@endsection

@section('content')
    <!--begin::Stats Overview-->
    <div class="row g-5 g-xl-8 mb-8">
        <!--begin::Col-->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-light-primary">
                                <i class="ki-outline ki-folder fs-2x text-primary"></i>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fs-7 fw-semibold">Total Sheet Groups</span>
                            <span class="fs-2x fw-bold text-gray-900" id="stat-total">{{ $sheets->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-light-success">
                                <i class="ki-outline ki-handcart fs-2x text-success"></i>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fs-7 fw-semibold">Total Sales</span>
                            <span class="fs-2x fw-bold text-gray-900"
                                id="stat-sales">{{ number_format($sheets->sum('sheetPayments_count')) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-light-info">
                                <i class="ki-outline ki-tag fs-2x text-info"></i>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fs-7 fw-semibold">Avg. Price</span>
                            <span class="fs-2x fw-bold text-gray-900"
                                id="stat-avg-price">৳{{ $sheets->count() > 0 ? number_format($sheets->avg('price'), 0) : 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Col-->
        <!--begin::Col-->
        <div class="col-sm-6 col-lg-3">
            <div class="card card-flush h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <div class="symbol-label bg-light-warning">
                                <i class="ki-outline ki-chart-simple fs-2x text-warning"></i>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fs-7 fw-semibold">Total Revenue</span>
                            <span class="fs-2x fw-bold text-gray-900"
                                id="stat-revenue">৳{{ number_format($totalRevenue, 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Col-->
    </div>
    <!--end::Stats Overview-->

    <!--begin::Toolbar Card-->
    <div class="card card-flush mb-6">
        <div class="card-body py-5">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <!--begin::Search-->
                <div class="position-relative w-100 w-lg-350px">
                    <i
                        class="ki-outline ki-magnifier fs-3 position-absolute top-50 translate-middle-y ms-4 text-gray-500"></i>
                    <input type="text" id="sheet-search-input" class="form-control form-control-solid ps-12"
                        placeholder="Search by class name...">
                </div>
                <!--end::Search-->

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <!--begin::Sort Dropdown-->
                    <div class="dropdown">
                        <button class="btn btn-light-primary btn-sm fw-semibold dropdown-toggle" type="button"
                            id="sort-dropdown-btn" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ki-outline ki-sort fs-4 me-1"></i>
                            <span id="sort-label">Sort by</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end w-200px py-3" id="sort-dropdown-menu">
                            <a class="dropdown-item sort-option d-flex align-items-center py-2 px-4" href="#"
                                data-sort="name-asc">
                                <i class="ki-outline ki-arrow-up fs-5 me-3 text-gray-500"></i>
                                <span>Name A-Z</span>
                            </a>
                            <a class="dropdown-item sort-option d-flex align-items-center py-2 px-4" href="#"
                                data-sort="name-desc">
                                <i class="ki-outline ki-arrow-down fs-5 me-3 text-gray-500"></i>
                                <span>Name Z-A</span>
                            </a>
                            <div class="separator my-2"></div>
                            <a class="dropdown-item sort-option d-flex align-items-center py-2 px-4" href="#"
                                data-sort="price-asc">
                                <i class="ki-outline ki-arrow-up fs-5 me-3 text-gray-500"></i>
                                <span>Price Low-High</span>
                            </a>
                            <a class="dropdown-item sort-option d-flex align-items-center py-2 px-4" href="#"
                                data-sort="price-desc">
                                <i class="ki-outline ki-arrow-down fs-5 me-3 text-gray-500"></i>
                                <span>Price High-Low</span>
                            </a>
                            <div class="separator my-2"></div>
                            <a class="dropdown-item sort-option d-flex align-items-center py-2 px-4" href="#"
                                data-sort="sales-desc">
                                <i class="ki-outline ki-chart fs-5 me-3 text-gray-500"></i>
                                <span>Most Sales</span>
                            </a>
                            <a class="dropdown-item sort-option d-flex align-items-center py-2 px-4" href="#"
                                data-sort="recent">
                                <i class="ki-outline ki-time fs-5 me-3 text-gray-500"></i>
                                <span>Most Recent</span>
                            </a>
                        </div>
                    </div>
                    <!--end::Sort Dropdown-->

                    <!--begin::View Toggle-->
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-icon btn-light-primary view-toggle-btn active"
                            data-view="grid" data-bs-toggle="tooltip" title="Grid View">
                            <i class="ki-outline ki-element-equal fs-4"></i>
                        </button>
                        <button type="button" class="btn btn-icon btn-light view-toggle-btn" data-view="list"
                            data-bs-toggle="tooltip" title="List View">
                            <i class="ki-outline ki-row-horizontal fs-4"></i>
                        </button>
                    </div>
                    <!--end::View Toggle-->
                </div>
            </div>

            <!--begin::Active Filters-->
            <div id="active-filters" class="d-none align-items-center mt-5 pt-5 border-top border-gray-200">
                <span class="text-gray-500 fs-7 me-2">Showing:</span>
                <span id="results-count" class="badge badge-light-primary fs-7 fw-semibold"></span>
                <button id="clear-filters" class="btn btn-sm btn-link text-danger ms-auto p-0">
                    <i class="ki-outline ki-cross fs-5 me-1"></i>Clear filters
                </button>
            </div>
            <!--end::Active Filters-->
        </div>
    </div>
    <!--end::Toolbar Card-->

    <!--begin::Sheets Container-->
    <div id="sheets-container" class="row g-6 mb-6">
        <!-- Cards will be injected here by JavaScript -->
    </div>
    <!--end::Sheets Container-->

    <!--begin::Empty State-->
    <div id="empty-state" class="card card-flush d-none">
        <div class="card-body py-20">
            <div class="d-flex flex-column align-items-center text-center">
                <div class="symbol symbol-100px symbol-circle mb-8">
                    <div class="symbol-label bg-light-primary">
                        <i class="ki-outline ki-folder fs-2x text-primary"></i>
                    </div>
                </div>
                <h3 class="fs-2 fw-bold text-gray-900 mb-3">No Sheet Groups Found</h3>
                <p class="text-gray-600 fs-6 mb-0 mw-400px">
                    We couldn't find any sheet groups. Sheet groups are created automatically when a class is created.
                </p>
            </div>
        </div>
    </div>
    <!--end::Empty State-->

    <!--begin::No Results-->
    <div id="no-results" class="card card-flush d-none">
        <div class="card-body py-20">
            <div class="d-flex flex-column align-items-center text-center">
                <div class="symbol symbol-100px symbol-circle mb-8">
                    <div class="symbol-label bg-light-warning">
                        <i class="ki-outline ki-magnifier fs-2x text-warning"></i>
                    </div>
                </div>
                <h3 class="fs-2 fw-bold text-gray-900 mb-3">No Results Found</h3>
                <p class="text-gray-600 fs-6 mb-5 mw-400px" id="no-results-text">
                    No sheet groups match your search criteria.
                </p>
                <button id="clear-search-btn" class="btn btn-light-primary btn-sm">
                    <i class="ki-outline ki-arrow-left fs-5 me-1"></i>
                    Clear Search
                </button>
            </div>
        </div>
    </div>
    <!--end::No Results-->

    <!--begin::Modal - Edit Sheet-->
    <div class="modal fade" id="kt_modal_edit_sheet" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header" id="kt_modal_edit_sheet_header">
                    <h2 class="fw-bold" id="kt_modal_edit_sheet_title">Update Sheet Price</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-sheet-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body px-5 my-7">
                    <form id="kt_modal_edit_sheet_form" class="form" novalidate="novalidate">
                        <input type="hidden" id="edit_sheet_id" name="sheet_id">
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_sheet_scroll">
                            <!--begin::Class Name Display-->
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2 text-gray-500">Class (Read-only)</label>
                                <input type="text" id="edit_class_name"
                                    class="form-control form-control-solid bg-gray-100" readonly>
                            </div>
                            <!--end::Class Name Display-->

                            <!--begin::Price Input-->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Update Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" name="sheet_price_edit" id="edit_sheet_price" min="100"
                                        class="form-control form-control-solid" placeholder="e.g. 2000" required>
                                </div>
                                <div class="form-text">Minimum price is ৳100</div>
                            </div>
                            <!--end::Price Input-->
                        </div>
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-sheet-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-sheet-modal-action="submit">
                                <span class="indicator-label">Update</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--end::Modal - Edit Sheet-->


@endsection

@push('page-js')
    <script>
        // Pass Laravel data to JavaScript
        const sheetsData = @json($sheetsJson);
        const routeSheetShow = "{{ route('sheets.show', ':id') }}";
        const routeSheetUpdate = "{{ route('sheets.update', ':id') }}";
        const canEditSheet = {{ auth()->user()->can('sheets.edit') ? 'true' : 'false' }};
    </script>
    <script src="{{ asset('js/sheets/index.js') }}"></script>
    <script>
        document.getElementById("sheets_menu")?.classList.add("here", "show");
        document.getElementById("all_sheets_link")?.classList.add("active");
    </script>
@endpush
