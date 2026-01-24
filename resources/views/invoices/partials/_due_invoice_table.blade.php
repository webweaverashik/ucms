@php
    $canEditInvoice = auth()->user()->can('invoices.edit');
    $canDeleteInvoice = auth()->user()->can('invoices.delete');
    $canViewInvoice = auth()->user()->can('invoices.view');
@endphp

<!--begin::Card-->
<div class="card">
    <!--begin::Card header-->
    <div class="card-header border-0 pt-6">
        <!--begin::Card title-->
        <div class="card-title">
            <!--begin::Search-->
            <div class="d-flex align-items-center position-relative my-1">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                <input type="text" class="form-control form-control-solid w-md-350px ps-12 due-invoice-search"
                    placeholder="Search in due invoices" data-table-id="{{ $tableId }}">
            </div>
            <!--end::Search-->
        </div>
        <!--begin::Card title-->
        <!--begin::Card toolbar-->
        <div class="card-toolbar">
            <!--begin::Toolbar-->
            <div class="d-flex justify-content-end" data-kt-subscription-table-toolbar="base">
                <!--begin::Filter-->
                <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                    data-kt-menu-placement="bottom-end">
                    <i class="ki-outline ki-filter fs-2"></i>Filter</button>
                <!--begin::Menu 1-->
                <div class="menu menu-sub menu-sub-dropdown w-300px w-sm-450px" data-kt-menu="true">
                    <!--begin::Header-->
                    <div class="px-7 py-5">
                        <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                    </div>
                    <!--end::Header-->
                    <!--begin::Separator-->
                    <div class="separator border-gray-200"></div>
                    <!--end::Separator-->
                    <!--begin::Content-->
                    <div class="px-7 py-5 row">
                        <!--begin::Input group-->
                        <div class="col-sm-6 mb-10">
                            <label class="form-label fs-6 fw-semibold">Invoice Type:</label>
                            <select class="form-select form-select-solid fw-bold filter-invoice-type"
                                data-kt-select2="true" data-placeholder="Select option" data-allow-clear="true" data-hide-search="true" data-table-id="{{ $tableId }}">
                                <option></option>
                            </select>
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="col-sm-6 mb-10">
                            <label class="form-label fs-6 fw-semibold">Due Date:</label>
                            <select class="form-select form-select-solid fw-bold filter-due-date" data-kt-select2="true"
                                data-placeholder="Select option" data-allow-clear="true" data-hide-search="true"
                                data-table-id="{{ $tableId }}">
                                <option></option>
                                <option value="1/7">1-7</option>
                                <option value="1/10">1-10</option>
                                <option value="1/15">1-15</option>
                                <option value="1/30">1-30</option>
                            </select>
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="col-sm-6 mb-10">
                            <label class="form-label fs-6 fw-semibold">Invoice Status:</label>
                            <select class="form-select form-select-solid fw-bold filter-status" data-kt-select2="true"
                                data-placeholder="Select option" data-allow-clear="true" data-hide-search="true"
                                data-table-id="{{ $tableId }}">
                                <option></option>
                                <option value="I_due">Due</option>
                                <option value="I_overdue">Overdue</option>
                                <option value="I_partial">Partial Paid</option>
                            </select>
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="col-sm-6 mb-10">
                            <label class="form-label fs-6 fw-semibold">Billing Month:</label>
                            <select class="form-select form-select-solid fw-bold filter-billing-month"
                                data-kt-select2="true" data-placeholder="Select option" data-allow-clear="true" data-hide-search="true"
                                data-table-id="{{ $tableId }}">
                                <option></option>
                            </select>
                        </div>
                        <!--end::Input group-->
                        <!--begin::Actions-->
                        <div class="d-flex justify-content-end">
                            <button type="reset"
                                class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6 filter-reset-btn"
                                data-kt-menu-dismiss="true" data-table-id="{{ $tableId }}">Reset</button>
                            <button type="submit" class="btn btn-primary fw-semibold px-6 filter-apply-btn"
                                data-kt-menu-dismiss="true" data-table-id="{{ $tableId }}">Apply</button>
                        </div>
                        <!--end::Actions-->
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Menu 1-->
                <!--end::Filter-->
                <!--begin::Export dropdown-->
                <div class="dropdown">
                    <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-exit-up fs-2"></i>Export
                    </button>
                    <!--begin::Menu-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                        data-kt-menu="true">
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3 export-btn" data-table-id="{{ $tableId }}"
                                data-type="due" data-export="copy">Copy to clipboard</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3 export-btn" data-table-id="{{ $tableId }}"
                                data-type="due" data-export="excel">Export as Excel</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3 export-btn" data-table-id="{{ $tableId }}"
                                data-type="due" data-export="csv">Export as CSV</a>
                        </div>
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3 export-btn" data-table-id="{{ $tableId }}"
                                data-type="due" data-export="pdf">Export as PDF</a>
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
    <div class="card-body py-4">
        <!--begin::Table-->
        <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table due-invoices-table"
            id="{{ $tableId }}" data-branch-id="{{ $branchId }}">
            <thead>
                <tr class="fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-25px">SL</th>
                    <th class="w-150px">Invoice No.</th>
                    <th class="w-250px">Student</th>
                    <th>Invoice Type</th>
                    <th>Billing Month</th>
                    <th>Total Amount (Tk)</th>
                    <th>Remaining (Tk)</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th class="min-w-150px">Last Comment</th>
                    <th>Created At</th>
                    <th class="w-100px">Actions</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                <!-- Data will be loaded via AJAX -->
            </tbody>
        </table>
        <!--end::Table-->
    </div>
    <!--end::Card body-->
</div>
<!--end::Card-->