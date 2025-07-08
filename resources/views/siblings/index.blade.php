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
                    Student Info </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Siblings </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')

    @php
        // Define badge colors for different branches
        $badgeColors = ['badge-light-primary', 'badge-light-success', 'badge-light-warning'];

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
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text"
                        data-kt-siblings-table-filter="search" class="form-control form-control-solid w-250px ps-12"
                        placeholder="Search in sibling">
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
                                    data-kt-siblings-table-filter="billing" data-hide-search="true">
                                    <option></option>
                                    <option value="Brother">Brother</option>
                                    <option value="Sister">Sister</option>
                                </select>
                            </div>
                            <!--end::Input group-->

                            @if (auth()->user()->hasRole('admin'))
                                <!--begin::Input group-->
                                <div class="mb-10">
                                    <label class="form-label fs-6 fw-semibold">Branch:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true"
                                        data-kt-subscription-table-filter="product" data-hide-search="true">
                                        <option></option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ ucfirst($branch->branch_name) }}">
                                                {{ ucfirst($branch->branch_name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <!--end::Input group-->
                            @endif

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
        <div class="card-body pt-0">
            <!--begin::Table-->
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table" id="kt_siblings_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-10px pe-2">SL</th>
                        <th class="min-w-200px">Name</th>
                        <th class="d-none">Gender (filter)</th>
                        <th>Gender</th>
                        <th>Students</th>
                        <th>Age</th>
                        <th>Class</th>
                        <th>Institution</th>
                        <th>Relationship</th>
                        <th class="@if (!auth()->user()->hasRole('admin')) d-none @endif">Branch</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @foreach ($siblings as $sibling)
                        <tr>
                            <td class="pe-2">{{ $loop->index + 1 }}</td>
                            <td class="text-gray-800">
                                <!--begin::user details-->
                                {{ $sibling->name }}
                                <!--begin::user details-->
                            </td>
                            <td class="d-none">gd_{{ $sibling->relationship }}</td>
                            <td>
                                @if ($sibling->relationship == 'brother')
                                    <i class="las la-mars"></i>
                                    Male
                                @else
                                    <i class="las la-venus"></i>
                                    Female
                                @endif
                            </td>
                            <td>
                                @if ($sibling->student)
                                    <a href="{{ route('students.show', $sibling->student->id) }}">
                                        <span class="text-hover-success fs-6">
                                            {{ $sibling->student->name }},
                                            {{ $sibling->student->student_unique_id }}
                                        </span>
                                    </a>
                                @else
                                    <span class="badge badge-light-danger">-</span>
                                @endif
                            </td>
                            <td>
                                {{ $sibling->age }}
                            </td>
                            <td>
                                {{ $sibling->class }}
                            </td>
                            <td>
                                {{ $sibling->institution->name }} (EIIN: {{ $sibling->institution->eiin_number }})
                            </td>
                            <td>
                                {{ ucfirst($sibling->relationship) }}
                            </td>

                            <td class="@if (!auth()->user()->hasRole('admin')) d-none @endif">
                                @if ($sibling->student && $sibling->student->branch)
                                    @php
                                        $branchName = $sibling->student->branch->branch_name;
                                        $badgeColor = $branchColors[$branchName] ?? 'badge-light-secondary'; // Default color
                                    @endphp
                                    <span class="badge {{ $badgeColor }}">{{ $branchName }}</span>
                                @else
                                    <span class="badge badge-light-danger">-</span>
                                @endif
                            </td>
                            <td>
                                @can('siblings.edit')
                                    <a href="#" title="Edit Sibling" data-bs-toggle="modal"
                                        data-bs-target="#kt_modal_edit_sibling" data-sibling-id="{{ $sibling->id }}"
                                        class="btn btn-icon text-hover-primary w-30px h-30px">
                                        <i class="ki-outline ki-pencil fs-2"></i>
                                    </a>
                                @endcan

                                @can('siblings.delete')
                                    <a href="#" title="Delete Sibling" data-bs-toggle="tooltip"
                                        class="btn btn-icon text-hover-danger w-30px h-30px delete-sibling"
                                        data-sibling-id="{{ $sibling->id }}">
                                        <i class="ki-outline ki-trash fs-2"></i>
                                    </a>
                                @endcan
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
                        <i class="ki-outline ki-cross fs-1">
                        </i>
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

                            <!--begin::Student Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="form-label">
                                    <span>Corrosponding Student</span>
                                    <span class="ms-1" data-bs-toggle="tooltip" title="Student cannot be changed.">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6">
                                        </i>
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
                                                <option value="{{ $student->id }}">{{ $student->name }}
                                                    (ID: {{ $student->student_unique_id }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <!--end::Solid input group style-->
                            </div>
                            <!--end::Student Input group-->

                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
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

                            <!--begin::Phone Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Sibling Age (Y)</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="number" name="sibling_age" min="6" max="20"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. 8" required />
                                <!--end::Input-->
                            </div>
                            <!--end::Phone Input group-->

                            <!--begin::Gender Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="d-flex align-items-center form-label mb-3 required">Class</label>
                                <!--end::Label-->
                                <!--begin::Row-->
                                <input type="text" name="sibling_class"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. Class 2"
                                    required />
                                <!--end::Row-->
                            </div>
                            <!--end::Gender Input group-->

                            <!--begin::Gender Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="d-flex align-items-center form-label mb-3 required">Institution</label>
                                <!--end::Label-->

                                <!--begin::Row-->
                                <div class="input-group input-group-solid flex-nowrap">
                                    <span class="input-group-text">
                                        <i class="las la-building fs-3"></i>
                                    </span>
                                    <div class="overflow-hidden flex-grow-1">
                                        <select name="sibling_institution"
                                            class="form-select form-select-solid rounded-start-0 border-start"
                                            data-control="select2" data-dropdown-parent="#kt_modal_edit_sibling"
                                            data-placeholder="Select an institution" data-allow-clear="true" required>
                                            <option></option>
                                            @foreach ($institutions as $institution)
                                                <option value="{{ $institution->id }}">{{ $institution->name }}
                                                    (EIIN: {{ $institution->eiin_number }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <!--end::Row-->
                            </div>
                            <!--end::Gender Input group-->

                            <!--begin::Input group-->
                            <div class="fv-row">
                                <!--begin::Label-->
                                <label class="form-label required">Relationship with student</label>
                                <!--end::Label-->

                                <!--begin::Solid input group style-->
                                <select name="sibling_relationship" class="form-select form-select-solid"
                                    data-control="select2" data-hide-search="true" data-placeholder="Select relationship"
                                    required>
                                    <option></option>
                                    <option value="brother">Brother</option>
                                    <option value="sister">Sister</option>
                                </select>
                                <!--end::Solid input group style-->
                            </div>
                            <!--end::Input group-->

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
    </script>

    <script src="{{ asset('js/siblings/index.js') }}"></script>


    <script>
        document.getElementById("student_info_menu").classList.add("here", "show");
        document.getElementById("siblings_link").classList.add("active");
    </script>
@endpush
