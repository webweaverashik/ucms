@extends('layouts.app')

@section('title', 'Cost Records')

@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="{{ asset('css/reports/cost-records/index.css') }}">
@endpush

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Cost Records
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Reports</li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Cost Records</li>
        </ul>
    </div>
@endsection

@section('content')
    <!--begin::Card-->
    <div class="card">
        <!--begin::Card Header-->
        <div class="card-header border-0 pt-6">
            <div class="card-title">
                <h3 class="fw-bold m-0">
                    <i class="ki-outline ki-wallet fs-2 text-primary me-2"></i>
                    Daily Cost Records
                </h3>
            </div>
            <div class="card-toolbar gap-2">
                <button type="button" class="btn btn-success" id="add_cost_btn">
                    <i class="ki-outline ki-plus fs-4 me-1"></i> Add Cost
                </button>
                <button type="button" class="btn btn-light-info" id="refresh_costs_btn">
                    <i class="ki-outline ki-arrows-circle fs-4 me-1"></i> Refresh
                </button>
            </div>
        </div>
        <!--end::Card Header-->

        <!--begin::Card Body-->
        <div class="card-body pt-0">
            <div class="separator separator-dashed mb-5"></div>

            @if ($isAdmin && $branches->count() > 1)
                <!--begin::Branch Tabs for Admin-->
                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="branch_tabs">
                    @foreach ($branches as $index => $branch)
                        <li class="nav-item">
                            <a class="nav-link {{ $index === 0 ? 'active' : '' }}" data-bs-toggle="tab"
                                href="#pane_branch_{{ $branch->id }}" data-branch-id="{{ $branch->id }}">
                                <i class="ki-outline ki-bank fs-4 me-2"></i>
                                {{ $branch->branch_name }}
                                <span class="badge badge-light-primary ms-2 branch-count"
                                    data-branch-id="{{ $branch->id }}">0</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="tab-content" id="branch_tabs_content">
                    @foreach ($branches as $index => $branch)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                            id="pane_branch_{{ $branch->id }}" role="tabpanel">
                            <div class="table-responsive">
                                <table id="costs_datatable_{{ $branch->id }}"
                                    class="table table-row-bordered table-row-gray-200 align-middle gs-0 gy-4 costs-datatable"
                                    data-branch-id="{{ $branch->id }}">
                                    <thead>
                                        <tr class="fw-bold text-muted bg-light">
                                            <th class="ps-4 rounded-start min-w-100px">Date</th>
                                            <th class="min-w-250px">Cost Entries</th>
                                            <th class="min-w-100px text-end">Total Amount</th>
                                            <th class="min-w-100px">Created By</th>
                                            <th class="pe-4 rounded-end text-center min-w-100px">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
                <!--end::Branch Tabs for Admin-->
            @else
                <!--begin::Single DataTable for Non-Admin or Single Branch-->
                <div class="table-responsive">
                    <table id="costs_datatable" class="table table-row-bordered table-row-gray-200 align-middle gs-0 gy-4">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-4 rounded-start min-w-100px">Date</th>
                                @if ($isAdmin)
                                    <th class="min-w-125px">Branch</th>
                                @endif
                                <th class="min-w-250px">Cost Entries</th>
                                <th class="min-w-100px text-end">Total Amount</th>
                                <th class="min-w-100px">Created By</th>
                                @if ($isAdmin)
                                    <th class="pe-4 rounded-end text-center min-w-100px">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <!--end::Single DataTable-->
            @endif
        </div>
        <!--end::Card Body-->
    </div>
    <!--end::Card-->

    <!--begin::Add Cost Modal-->
    <div class="modal fade" id="cost_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-700px">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="cost_modal_title" class="modal-title fw-bold">Add Today's Cost</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>
                <form id="cost_form">
                    <div class="modal-body py-10 px-lg-12" style="max-height: 70vh; overflow-y: auto;">
                        <!-- Branch Selection -->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Branch</label>
                            @if ($isAdmin)
                                <select id="cost_branch_id" name="branch_id" class="form-select form-select-solid"
                                    data-control="select2" data-placeholder="Select branch"
                                    data-dropdown-parent="#cost_modal" data-hide-search="true">
                                    <option value="">-- Select Branch --</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">
                                            {{ $branch->branch_name }} ({{ $branch->branch_prefix }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control form-control-solid bg-secondary"
                                    value="{{ $branches->first()->branch_name ?? '' }} ({{ $branches->first()->branch_prefix ?? '' }})"
                                    readonly disabled>
                                <input type="hidden" id="cost_branch_id" name="branch_id"
                                    value="{{ $branches->first()->id ?? '' }}">
                            @endif
                        </div>

                        <!-- Date (Today Only - Read Only) -->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Date</label>
                            <input type="text" id="cost_date" name="cost_date"
                                class="form-control form-control-solid bg-secondary" value="{{ now()->format('d-m-Y') }}"
                                readonly>
                            <div class="form-text text-muted">
                                <i class="ki-outline ki-information-3 fs-7 me-1"></i>
                                Cost can only be added for today's date
                            </div>
                        </div>

                        <!-- Cost Types Selection with Tagify -->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Cost Types</label>
                            <input type="text" id="cost_types_tagify" class="form-control form-control-solid"
                                placeholder="Select cost types...">
                            <div class="form-text text-muted">Select one or more cost types to add entries</div>
                        </div>

                        <!-- Cost Entries Container -->
                        <div id="cost_entries_container" class="mb-5">
                            <label class="fw-semibold fs-6 mb-3">Cost Entries</label>
                            <div id="cost_entries_list">
                                <div class="text-center text-muted py-5">
                                    <i class="ki-outline ki-information fs-3x text-gray-400 mb-3"></i>
                                    <p class="mb-0">Select cost types above to add entries</p>
                                </div>
                            </div>
                        </div>

                        <!-- Others Cost Section -->
                        <div class="fv-row mb-5">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="fw-semibold fs-6">Other Costs</label>
                                <button type="button" class="btn btn-sm btn-light-primary" id="add_other_cost_btn">
                                    <i class="ki-outline ki-plus fs-6"></i> Add Other
                                </button>
                            </div>
                            <div id="other_costs_container">
                                <!-- Other cost rows will be added here -->
                            </div>
                            <div class="form-text text-muted">
                                <i class="ki-outline ki-information-3 fs-7 me-1"></i>
                                Add custom cost types that are not in the predefined list
                            </div>
                        </div>

                        <!-- Total Cost -->
                        <div id="cost_total_section" class="cost-total-section d-none">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="total-label">Total Cost</span>
                                <span id="cost_total_amount" class="total-amount">৳0</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="save_cost_btn">
                            <span class="indicator-label">
                                <i class="ki-outline ki-check fs-4 me-1"></i> Save Cost
                            </span>
                            <span class="indicator-progress">
                                Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--end::Add Cost Modal-->

    @if ($isAdmin)
        <!--begin::Edit Cost Modal-->
        <div class="modal fade" id="edit_cost_modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-600px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title fw-bold">Edit Cost Amounts</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary"
                            data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <form id="edit_cost_form">
                        <div class="modal-body py-10 px-lg-12" style="max-height: 70vh; overflow-y: auto;">
                            <input type="hidden" id="edit_cost_id">
                            <!-- Cost Info -->
                            <div class="d-flex justify-content-between align-items-center mb-5 p-4 bg-light rounded">
                                <div>
                                    <span class="text-muted fs-7">Date:</span>
                                    <span id="edit_cost_date" class="fw-bold text-gray-800 ms-2"></span>
                                </div>
                                <div>
                                    <span class="text-muted fs-7">Branch:</span>
                                    <span id="edit_cost_branch" class="fw-bold text-gray-800 ms-2"></span>
                                </div>
                            </div>

                            <!-- Entries List -->
                            <div class="mb-5">
                                <label class="fw-semibold fs-6 mb-3">Cost Entries</label>
                                <div id="edit_entries_list"></div>
                            </div>

                            <!-- Total -->
                            <div class="cost-total-section">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="total-label">Total Cost</span>
                                    <span id="edit_cost_total" class="total-amount">৳0</span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer flex-center">
                            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="update_cost_btn">
                                <span class="indicator-label">
                                    <i class="ki-outline ki-check fs-4 me-1"></i> Update Cost
                                </span>
                                <span class="indicator-progress">
                                    Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!--end::Edit Cost Modal-->

        <!--begin::Delete Cost Modal-->
        <div class="modal fade" id="delete_cost_modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title fw-bold">Delete Cost Record</h3>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary"
                            data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-10 text-center">
                        <div class="mb-5">
                            <i class="ki-outline ki-trash text-danger fs-5x"></i>
                        </div>
                        <p class="fs-5 fw-semibold text-gray-700 mb-2">Are you sure you want to delete this cost record?
                        </p>
                        <p class="fs-7 text-muted">This will permanently remove all entries. This action cannot be undone.
                        </p>
                        <input type="hidden" id="delete_cost_id">
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirm_delete_cost_btn">
                            <span class="indicator-label">
                                <i class="ki-outline ki-trash fs-4 me-1"></i> Delete
                            </span>
                            <span class="indicator-progress">
                                Deleting...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Delete Cost Modal-->
    @endif
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        window.CostRecordsConfig = {
            isAdmin: @json($isAdmin),
            userBranchId: @json(auth()->user()->branch_id),
            todayDate: "{{ now()->format('d-m-Y') }}",
            hasBranchTabs: @json($isAdmin && $branches->count() > 1),
            branches: @json($branches->map(fn($b) => ['id' => $b->id, 'name' => $b->branch_name, 'prefix' => $b->branch_prefix])),
            routes: {
                costs: "{{ route('reports.finance.costs') }}",
                costTypes: "{{ route('costs.types') }}",
                storeCost: "{{ route('costs.store') }}",
                checkTodayCost: "{{ route('costs.check-today') }}",
                @if ($isAdmin)
                    showCost: "{{ route('costs.show', ':id') }}",
                    updateCost: "{{ route('costs.update', ':id') }}",
                    deleteCost: "{{ route('costs.destroy', ':id') }}"
                @endif
            },
            csrfToken: "{{ csrf_token() }}"
        };
    </script>
    <script src="{{ asset('js/reports/cost-records/index.js') }}"></script>
    <script>
        document.getElementById("reports_menu")?.classList.add("here", "show");
        document.getElementById("cost_records_link")?.classList.add("active");
    </script>
@endpush
