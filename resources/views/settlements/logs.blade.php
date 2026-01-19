@extends('layouts.app')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/custom/flatpickr/flatpickr.min.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('title', 'All Wallet Logs')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Wallet Transactions
        </h1>
        <!--end::Title-->

        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->

        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('settlements.index') }}" class="text-muted text-hover-primary"> Settlements</a>
            </li>
            <!--end::Item-->

            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->

            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                All Logs
            </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection

@section('content')
    <div class="card card-flush">
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                    <input type="text" id="kt_filter_search"
                        class="form-control form-control-solid w-250px ps-13" placeholder="Search..." />
                </div>
            </div>

            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end gap-3">
                    <!--begin::Filter-->
                    <button type="button" class="btn btn-light-primary" id="kt_filter_btn">
                        <i class="ki-outline ki-filter fs-2"></i>Filter
                    </button>
                    <!--begin::Filter Menu-->
                    <div class="menu menu-sub menu-sub-dropdown w-300px w-md-400px" id="kt_filter_menu">
                        <!--begin::Header-->
                        <div class="px-7 py-5">
                            <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                        </div>
                        <!--end::Header-->

                        <!--begin::Separator-->
                        <div class="separator border-gray-200"></div>
                        <!--end::Separator-->

                        <!--begin::Content-->
                        <div class="px-7 py-5">
                            <div class="row">
                                <!--begin::Input group-->
                                <div class="col-12 mb-5">
                                    <label class="form-label fs-6 fw-semibold">User:</label>
                                    <select class="form-select form-select-solid fw-bold" id="filter_user">
                                        <option value="">All Users</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="col-12 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Type:</label>
                                    <select class="form-select form-select-solid fw-bold" id="filter_type">
                                        <option value="">All Types</option>
                                        <option value="collection">Collection</option>
                                        <option value="settlement">Settlement</option>
                                        <option value="adjustment">Adjustment</option>
                                    </select>
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="col-6 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Date From:</label>
                                    <input type="text" class="form-control form-control-solid fw-bold flatpickr-input"
                                        id="filter_date_from" placeholder="Select date" readonly />
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="col-6 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Date To:</label>
                                    <input type="text" class="form-control form-control-solid fw-bold flatpickr-input"
                                        id="filter_date_to" placeholder="Select date" readonly />
                                </div>
                                <!--end::Input group-->
                            </div>

                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="button"
                                    class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    id="kt_filter_reset">Reset</button>
                                <button type="button" class="btn btn-primary fw-semibold px-6"
                                    id="kt_filter_apply">Apply</button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Filter Menu-->
                    <!--end::Filter-->

                    <!--begin::Export-->
                    <button type="button" class="btn btn-light-primary" id="kt_export_btn">
                        <i class="ki-outline ki-exit-up fs-2"></i>Export
                    </button>
                    <!--begin::Export Menu-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                        id="kt_export_menu">
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-kt-export="copy">Copy to clipboard</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-kt-export="excel">Export as Excel</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-kt-export="csv">Export as CSV</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-kt-export="pdf">Export as PDF</a>
                        </div>
                        <!--end::Menu item-->
                    </div>
                    <!--end::Export Menu-->
                    <!--end::Export-->
                </div>
                <!--end::Toolbar-->
            </div>
        </div>

        <div class="card-body pt-0">
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table" id="kt_logs_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-20px">#</th>
                        <th class="w-100px">Date</th>
                        <th class="w-150px">User</th>
                        <th class="w-100px">Type</th>
                        <th class="w-250px">Description</th>
                        <th class="w-100px text-end">Amount</th>
                        <th class="w-100px text-end">Old Balance</th>
                        <th class="w-100px text-end">New Balance</th>
                        <th class="w-100px">Created By</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/flatpickr/flatpickr.min.js') }}"></script>
    <!-- SheetJS for Excel export -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <!-- jsPDF for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.1/jspdf.plugin.autotable.min.js"></script>
@endpush

@push('page-js')
    <script>
        var KT_LOGS_DATA_URL = "{{ route('settlements.logs.data') }}";
        var KT_LOGS_EXPORT_URL = "{{ route('settlements.logs.export') }}";
    </script>
    <script src="{{ asset('js/settlements/logs.js') }}"></script>
    <script>
        document.getElementById("settlements_menu")?.classList.add("here", "show");
        document.getElementById("settlements_logs_link")?.classList.add("active");
    </script>
@endpush