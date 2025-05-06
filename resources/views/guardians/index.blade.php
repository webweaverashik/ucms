@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'All Guardians')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Guardians
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="?page=index" class="text-muted text-hover-primary">
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
                Guardians </li>
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
                        data-kt-subscription-table-filter="search" class="form-control form-control-solid w-350px ps-12"
                        placeholder="Search In Guardians">
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
                        <div class="px-7 py-5" data-kt-subscription-table-filter="form">
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Relationship Type:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-kt-subscription-table-filter="billing" data-hide-search="true">
                                    <option></option>
                                    <option value="Father">Father</option>
                                    <option value="Mother">Mother</option>
                                    <option value="Brother">Brother</option>
                                    <option value="Sister">Sister</option>
                                    <option value="Uncle">Uncle</option>
                                    <option value="Aunt">Aunt</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Gender:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-kt-subscription-table-filter="product" data-hide-search="true">
                                    <option></option>
                                    <option value="gd_male">Male</option>
                                    <option value="gd_female">Female</option>
                                </select>
                            </div>
                            <!--end::Input group-->

                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-kt-menu-dismiss="true" data-kt-subscription-table-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                    data-kt-subscription-table-filter="filter">Apply</button>
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
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_guardians_table">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th class="w-10px pe-2">SL</th>
                            <th class="min-w-200px">Name</th>
                            <th class="d-none">Gender (filter)</th>
                            <th class="text-center">Gender</th>
                            <th class="text-center">Students</th>
                            <th class="text-center">Mobile</th>
                            <th class="text-center">Relationship</th>
                            <th class="text-center">Monthly<br>Payment (৳)</th>
                            <th class="text-center">Branch</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                        @foreach ($guardians as $guardian)
                            <tr>
                                <td class="pe-2">{{ $loop->index + 1 }}</td>
                                <td class="d-flex align-items-center">
                                    <!--begin:: Avatar -->
                                    <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                            <div class="symbol-label">
                                                <img src="{{ asset($guardian->gender == 'male' ? 'img/male.png' : 'img/female.png') }}"
                                                    alt="{{ $guardian->name }}" class="w-100" />

                                            </div>
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::user details-->
                                    <div class="d-flex flex-column">
                                        <span 
                                            class="text-gray-800 mb-1">{{ $guardian->name }}
                                        </span>
                                    </div>
                                    <!--begin::user details-->
                                </td>
                                <td class="d-none">gd_{{ $guardian->gender }}</td>
                                <td class="text-center">
                                    @if ($guardian->gender == 'male')
                                        <i class="las la-mars"></i>
                                        {{ ucfirst($guardian->gender) }}
                                    @else
                                        <i class="las la-venus"></i>
                                        {{ ucfirst($guardian->gender) }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($guardian->student)
                                        <a href="{{ route('students.show', $guardian->student->id) }}">
                                            <span class="text-hover-success fs-6">
                                                {{ $guardian->student->name }},
                                                {{ $guardian->student->student_unique_id }}
                                            </span>
                                        </a>
                                    @else
                                        <span class="badge badge-light-danger">No Student Assigned</span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    {{ $guardian->mobile_number }}
                                </td>
                                <td class="text-center">
                                    {{ ucfirst($guardian->relationship) }}
                                </td>
                                <td class="text-center">
                                    {{ intval(optional(optional($guardian->student)->payments)->tuition_fee) }}
                                </td>
                                <td class="text-center">
                                    @if ($guardian->student && $guardian->student->branch)
                                        @php
                                            $branchName = $guardian->student->branch->branch_name;
                                            $badgeColor = $branchColors[$branchName] ?? 'badge-light-secondary'; // Default color
                                        @endphp
                                        <span class="badge {{ $badgeColor }}">{{ $branchName }}</span>
                                    @else
                                        <span class="badge badge-light-danger">No Branch Assigned</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="#" title="Edit Guardian" data-bs-toggle="modal"
                                        data-bs-target="#kt_modal_edit_guardian" data-guardian-id="{{ $guardian->id }}"
                                        class="btn btn-icon btn-active-light-warning w-30px h-30px me-3">
                                        <i class="ki-outline ki-pencil fs-2"></i>
                                    </a>
                                    <a href="#" title="Delete Guardian" data-bs-toggle="tooltip"
                                        class="btn btn-icon btn-active-light-danger w-30px h-30px me-3 delete-guardian"
                                        data-guardian-id="{{ $guardian->id }}">
                                        <i class="ki-outline ki-trash fs-2"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->

    <!--begin::Modal - Edit Guardian-->
    <div class="modal fade" id="kt_modal_edit_guardian" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_edit_guardian_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Update Guardian</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-guardians-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_guardian_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_guardian_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_guardian_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_guardian_scroll" data-kt-scroll-offset="300px">

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
                                        <select name="guardian_student"
                                            class="form-select form-select-solid rounded-start-0 border-start"
                                            data-control="select2" data-dropdown-parent="#kt_modal_edit_guardian" data-placeholder="Select an option" disabled>
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
                                <label class="required fw-semibold fs-6 mb-2">Guardian Name</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="guardian_name"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Full Name"
                                    required />
                                <!--end::Input-->
                            </div>
                            <!--end::Name Input group-->

                            <!--begin::Phone Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Guardian Phone</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="guardian_mobile_number"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. Phone Number"
                                    required />
                                <!--end::Input-->
                            </div>
                            <!--end::Phone Input group-->

                            <!--begin::Gender Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="d-flex align-items-center form-label mb-3 required">Gender</label>
                                <!--end::Label-->
                                <!--begin::Row-->
                                <div class="row">
                                    <!--begin::Col-->
                                    <div class="col-lg-6">
                                        <!--begin::Option-->
                                        <input type="radio" class="btn-check" name="guardian_gender" value="male"
                                            checked="checked" id="gender_male_input" />
                                        <label
                                            class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                            for="gender_male_input">
                                            <i class="las la-mars fs-2x me-5"></i>
                                            <!--begin::Info-->
                                            <span class="d-block fw-semibold text-start">
                                                <span class="text-gray-900 fw-bold d-block fs-6">Male</span>
                                            </span>
                                            <!--end::Info-->
                                        </label>
                                        <!--end::Option-->
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="col-lg-6">
                                        <!--begin::Option-->
                                        <input type="radio" class="btn-check" name="guardian_gender" value="female"
                                            id="gender_female_input" />
                                        <label
                                            class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                            for="gender_female_input">
                                            <i class="las la-venus fs-2x me-5"></i>
                                            <!--begin::Info-->
                                            <span class="d-block fw-semibold text-start">
                                                <span class="text-gray-900 fw-bold d-block fs-6">Female</span>
                                            </span>
                                            <!--end::Info-->
                                        </label>
                                        <!--end::Option-->
                                    </div>
                                    <!--end::Col-->
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
                                <select name="guardian_relationship" class="form-select form-select-solid"
                                    data-control="select2" data-hide-search="true" data-placeholder="Select" required>
                                    <option></option>
                                    <option value="father">Father</option>
                                    <option value="mother">Mother</option>
                                    <option value="brother">Brother</option>
                                    <option value="sister">Sister</option>
                                    <option value="uncle">Uncle</option>
                                    <option value="aunt">Aunt</option>
                                </select>
                                <!--end::Solid input group style-->
                            </div>
                            <!--end::Input group-->

                            {{-- <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="fw-semibold fs-6 mb-2">Password <span class="text-muted">(optional)</span></label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="password" name="guardian_password"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="alphaneumeric and min 8 digit password"/>
                                <!--end::Input-->
                            </div>
                            <!--end::Name Input group--> --}}

                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-guardians-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-guardians-modal-action="submit">
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
    <!--end::Modal - Edit Guardian-->
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        const routeDeleteGuardian = "{{ route('guardians.destroy', ':id') }}";
    </script>

    <script src="{{ asset('js/guardians/index.js') }}"></script>


    <script>
        document.getElementById("guardians_link").classList.add("active");
    </script>
@endpush
