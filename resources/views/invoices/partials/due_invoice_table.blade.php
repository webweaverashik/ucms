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
            <div class="d-flex justify-content-end flex-wrap gap-3" data-kt-subscription-table-toolbar="base">
                
                <!--begin::Column Selector Wrapper-->
                <div>
                    <button type="button" class="btn btn-light-info" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-setting-2 fs-2"></i>Columns
                    </button>
                    <!--begin::Column Selector Menu-->
                    <div class="menu menu-sub menu-sub-dropdown w-300px" data-kt-menu="true"
                        id="column_selector_due_{{ $tableId }}">
                        <!--begin::Header-->
                        <div class="px-7 py-5 d-flex justify-content-between align-items-center">
                            <div class="fs-5 text-gray-900 fw-bold">Select Columns</div>
                            <button type="button" class="btn btn-sm btn-icon btn-light-primary column-reset-btn"
                                data-table-id="{{ $tableId }}" data-type="due" title="Reset to Default">
                                <i class="ki-outline ki-arrows-circle fs-4"></i>
                            </button>
                        </div>
                        <!--end::Header-->
                        <!--begin::Separator-->
                        <div class="separator border-gray-200"></div>
                        <!--end::Separator-->
                        <!--begin::Content-->
                        <div class="px-7 py-5 column-checkbox-list" data-table-id="{{ $tableId }}" data-type="due"
                            style="max-height: 300px; overflow-y: auto;">
                            <!-- Checkboxes will be populated by JavaScript -->
                        </div>
                        <!--end::Content-->
                        <!--begin::Footer-->
                        <div class="separator border-gray-200"></div>
                        <div class="px-7 py-4">
                            <button type="button" class="btn btn-sm btn-primary w-100 column-apply-btn"
                                data-kt-menu-dismiss="true" data-table-id="{{ $tableId }}" data-type="due">
                                Apply Changes
                            </button>
                        </div>
                        <!--end::Footer-->
                    </div>
                    <!--end::Column Selector Menu-->
                </div>
                <!--end::Column Selector Wrapper-->

                <!--begin::Filter Wrapper-->
                <div>
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-filter fs-2"></i>Filter
                    </button>
                    <!--begin::Filter Menu-->
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
                                    data-kt-select2="true" data-placeholder="Select option" data-allow-clear="true"
                                    data-hide-search="true" data-table-id="{{ $tableId }}">
                                    <option></option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="col-sm-6 mb-10">
                                <label class="form-label fs-6 fw-semibold">Due Date:</label>
                                <select class="form-select form-select-solid fw-bold filter-due-date"
                                    data-kt-select2="true" data-placeholder="Select option" data-allow-clear="true"
                                    data-hide-search="true" data-table-id="{{ $tableId }}">
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
                                <select class="form-select form-select-solid fw-bold filter-status"
                                    data-kt-select2="true" data-placeholder="Select option" data-allow-clear="true"
                                    data-hide-search="true" data-table-id="{{ $tableId }}">
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
                                    data-kt-select2="true" data-placeholder="Select option" data-allow-clear="true"
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
                    <!--end::Filter Menu-->
                </div>
                <!--end::Filter Wrapper-->

                <!--begin::Export Wrapper-->
                <div>
                    <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-exit-up fs-2"></i>Export
                    </button>
                    <!--begin::Export Menu-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                        data-kt-menu="true">
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
                    </div>
                    <!--end::Export Menu-->
                </div>
                <!--end::Export Wrapper-->

            </div>
            <!--end::Toolbar-->
        </div>
        <!--end::Card toolbar-->
    </div>
    <!--end::Card header-->

    <!--begin::Card body-->
    <div class="card-body py-4">
        <!--begin::Table-->
        {{-- 
            IMPORTANT: Column order must match exactly with JavaScript DataTable columns array
            Index: 0=sl, 1=invoice_number, 2=student_name, 3=mobile, 4=class_name, 5=institution,
                   6=tuition_fee, 7=activation_status, 8=invoice_type, 9=billing_month, 10=total_amount,
                   11=amount_due, 12=due_date, 13=status, 14=last_comment, 15=created_at, 16=actions
        --}}
        <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table due-invoices-table"
            id="{{ $tableId }}" data-branch-id="{{ $branchId }}" data-table-type="due">
            <thead>
                <tr class="fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-25px">SL</th>
                    <th class="min-w-100px">Invoice No.</th>
                    <th class="min-w-150px">Student</th>
                    <th class="min-w-100px">Mobile</th>
                    <th class="min-w-80px">Class</th>
                    <th class="min-w-100px">Institution</th>
                    <th class="min-w-80px">Tuition Fee</th>
                    <th class="min-w-80px">Activation</th>
                    <th class="min-w-100px">Invoice Type</th>
                    <th class="min-w-100px">Billing Month</th>
                    <th class="min-w-80px">Total (Tk)</th>
                    <th class="min-w-80px">Remaining (Tk)</th>
                    <th class="min-w-80px">Due Date</th>
                    <th class="min-w-70px">Status</th>
                    <th class="min-w-120px">Last Comment</th>
                    <th class="min-w-100px">Created At</th>
                    <th class="min-w-80px text-end">Actions</th>
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