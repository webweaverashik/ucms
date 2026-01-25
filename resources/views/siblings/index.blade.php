@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'All Siblings')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Siblings
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">
                    Student Info
                </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Siblings
            </li>
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
            $branchColors[$branch->branch_name] = $badgeColors[$index % count($badgeColors)];
        }
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
                    <input type="text" data-kt-siblings-table-filter="search"
                        class="form-control form-control-solid w-250px w-sm-400px ps-12" placeholder="Search in sibling">
                </div>
                <!--end::Search-->
            </div>
            <!--begin::Card title-->
            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-siblings-table-toolbar="base">
                    <!--begin::Filter-->
                    <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-filter fs-2"></i>Filter</button>
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
                        <div class="px-7 py-5" data-kt-siblings-table-filter="form">
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Relationship Type:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-kt-siblings-table-filter="relationship" data-hide-search="true">
                                    <option></option>
                                    <option value="Brother">Brother</option>
                                    <option value="Sister">Sister</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-kt-menu-dismiss="true" data-kt-siblings-table-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                    data-kt-siblings-table-filter="filter">Apply</button>
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
        <div class="card-body py-4">
            @if ($isAdmin)
                <!--begin::Tabs for Admin-->
                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="siblingBranchTabs" role="tablist">
                    @foreach ($branches as $index => $branch)
                        @php
                            $tabBadgeColor = $badgeColors[$index % count($badgeColors)];
                        @endphp
                        <li class="nav-item" role="presentation">
                            <a class="nav-link fw-bold {{ $index === 0 ? 'active' : '' }}"
                                id="tab-sibling-branch-{{ $branch->id }}" data-bs-toggle="tab"
                                href="#kt_tab_sibling_branch_{{ $branch->id }}" role="tab"
                                aria-controls="kt_tab_sibling_branch_{{ $branch->id }}"
                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}" data-branch-id="{{ $branch->id }}">
                                <i class="ki-outline ki-bank fs-4 me-1"></i>
                                {{ ucfirst($branch->branch_name) }}
                                <span class="badge {{ $tabBadgeColor }} ms-2 sibling-count-badge badge-loading"
                                    data-branch-id="{{ $branch->id }}">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <!--end::Tabs for Admin-->

                <!--begin::Tab Content-->
                <div class="tab-content" id="siblingBranchTabsContent">
                    @foreach ($branches as $index => $branch)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                            id="kt_tab_sibling_branch_{{ $branch->id }}" role="tabpanel"
                            aria-labelledby="tab-sibling-branch-{{ $branch->id }}">
                            @include('siblings.partials.siblings-table', [
                                'tableId' => 'kt_siblings_table_branch_' . $branch->id,
                                'branchId' => $branch->id,
                            ])
                        </div>
                    @endforeach
                </div>
                <!--end::Tab Content-->
            @else
                <!--begin::Single Table for Non-Admin-->
                @include('siblings.partials.siblings-table', [
                    'tableId' => 'kt_siblings_table',
                    'branchId' => null,
                ])
                <!--end::Single Table for Non-Admin-->
            @endif
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->

    <!--begin::Modal - Edit sibling-->
    <div class="modal fade" id="kt_modal_edit_sibling" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_edit_sibling_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Update Sibling</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-siblings-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"> </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_sibling_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_sibling_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_sibling_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_sibling_scroll" data-kt-scroll-offset="300px">
                            <div class="row">
                                <!--begin::Student Input group-->
                                <div class="col-12 fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="form-label">
                                        <span>Corrosponding Student</span>
                                        <span class="ms-1" data-bs-toggle="tooltip" title="Student cannot be changed.">
                                            <i class="ki-outline ki-information-5 text-gray-500 fs-6"> </i>
                                        </span>
                                    </label>
                                    <!--end::Label-->
                                    <!--begin::Solid input group style-->
                                    <div class="input-group input-group-solid flex-nowrap">
                                        <span class="input-group-text">
                                            <i class="las la-graduation-cap fs-3"></i>
                                        </span>
                                        <div class="overflow-hidden flex-grow-1">
                                            <select name="sibling_student"
                                                class="form-select form-select-solid rounded-start-0 border-start"
                                                data-control="select2" data-dropdown-parent="#kt_modal_edit_sibling"
                                                data-placeholder="Select an option" disabled>
                                                <option></option>
                                                @foreach ($students as $student)
                                                    <option value="{{ $student->id }}">{{ $student->name }} (ID:
                                                        {{ $student->student_unique_id }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <!--end::Solid input group style-->
                                </div>
                                <!--end::Student Input group-->

                                <!--begin::Name Input group-->
                                <div class="col-12 fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Sibling Name</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="sibling_name"
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Full Name"
                                        required />
                                    <!--end::Input-->
                                </div>
                                <!--end::Name Input group-->

                                <!--begin::Class/Age Input group-->
                                <div class="col-6 fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="d-flex align-items-center form-label mb-3 required">Class/Age</label>
                                    <!--end::Label-->
                                    <!--begin::Row-->
                                    <input type="text" name="sibling_class"
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. Class 2"
                                        required />
                                    <!--end::Row-->
                                </div>
                                <!--end::Class/Age Input group-->

                                <!--begin::year Input group-->
                                <div class="col-6 fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Year</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="sibling_year"
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. 8"
                                        required />
                                    <!--end::Input-->
                                </div>
                                <!--end::year Input group-->

                                <!--begin::Institution Input group-->
                                <div class="col-12 fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="d-flex align-items-center form-label mb-3 required">Institution</label>
                                    <!--end::Label-->
                                    <!--begin::Row-->
                                    <div class="input-group input-group-solid flex-nowrap">
                                        <span class="input-group-text">
                                            <i class="las la-building fs-3"></i>
                                        </span>
                                        <div class="overflow-hidden flex-grow-1">
                                            <input type="text" name="sibling_institution"
                                                class="form-control form-control-solid rounded-start-0 border-start"
                                                placeholder="Sibling institution name" required />
                                        </div>
                                    </div>
                                    <!--end::Row-->
                                </div>
                                <!--end::Institution Input group-->

                                <!--begin::Input group-->
                                <div class="col-12 fv-row">
                                    <!--begin::Label-->
                                    <label class="form-label required">Relationship with student</label>
                                    <!--end::Label-->
                                    <!--begin::Solid input group style-->
                                    <select name="sibling_relationship" class="form-select form-select-solid"
                                        data-control="select2" data-hide-search="true"
                                        data-placeholder="Select relationship" required>
                                        <option></option>
                                        <option value="brother">Brother</option>
                                        <option value="sister">Sister</option>
                                    </select>
                                    <!--end::Solid input group style-->
                                </div>
                                <!--end::Input group-->
                            </div>
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-siblings-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-siblings-modal-action="submit">
                                <span class="indicator-label">Update</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                        <!--end::Actions-->
                    </form>
                    <!--end::Form-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - Edit sibling-->
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        const routeDeleteSibling = "{{ route('siblings.destroy', ':id') }}";
        const routeSiblingsData = "{{ route('siblings.data') }}";
        const routeSiblingsCount = "{{ route('siblings.count') }}";
        const isAdmin = {{ $isAdmin ? 'true' : 'false' }};
        const branchIds = @json($branches->pluck('id'));
    </script>
    <script src="{{ asset('js/siblings/index.js') }}"></script>
    <script>
        document.getElementById("student_info_menu").classList.add("here", "show");
        document.getElementById("siblings_link").classList.add("active");
    </script>
@endpush
