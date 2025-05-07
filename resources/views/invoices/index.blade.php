@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'All Invoices')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Invoices
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
                    Payment Info </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Invoices </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin:::Tabs-->
    <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
        <!--begin:::Tab item-->
        <li class="nav-item">
            <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_due_invoices_tab"><i
                    class="ki-outline ki-home fs-3 me-2"></i>Personal
                Info</a>
        </li>
        <!--end:::Tab item-->

        <!--begin:::Tab item-->
        <li class="nav-item">
            <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_paid_invoices_tab"><i
                    class="ki-outline ki-book-open fs-3 me-2"></i>Enrolled
                Subjects</a>
        </li>
        <!--end:::Tab item-->

        <!--begin:::Tab item-->
        <li class="nav-item ms-auto">
            <!--begin::Action menu-->
            <a href="#" class="btn btn-primary ps-7" data-kt-menu-trigger="click" data-kt-menu-attach="parent"
                data-kt-menu-placement="bottom-end">Actions
                <i class="ki-outline ki-down fs-2 me-0"></i></a>
            <!--begin::Menu-->
            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold py-4 w-250px fs-6"
                data-kt-menu="true">
                <!--begin::Menu item-->
                <div class="menu-item px-5">
                    <div class="menu-content text-muted pb-2 px-5 fs-7 text-uppercase">Payments</div>
                </div>
                <!--end::Menu item-->
                <!--begin::Menu item-->
                <div class="menu-item px-5">
                    <a href="#" class="menu-link px-5">Create invoice</a>
                </div>
                <!--end::Menu item-->
                <!--begin::Menu item-->
                <div class="menu-item px-5">
                    <a href="#" class="menu-link flex-stack px-5">Create payments
                        <span class="ms-2" data-bs-toggle="tooltip"
                            title="Specify a target name for future usage and reference">
                            <i class="ki-outline ki-information fs-7">
                            </i>
                        </span></a>
                </div>
                <!--end::Menu item-->

                <!--begin::Menu separator-->
                <div class="separator my-3"></div>
                <!--end::Menu separator-->
                <!--begin::Menu item-->
                <div class="menu-item px-5">
                    <div class="menu-content text-muted pb-2 px-5 fs-7 text-uppercase">Account</div>
                </div>
                <!--end::Menu item-->
                <!--begin::Menu item-->
                <div class="menu-item px-5">
                    @if (optional($student->studentActivation)->active_status == 'active')
                        <a href="#" class="menu-link px-5 text-hover-warning" data-bs-toggle="modal"
                            data-bs-target="#kt_toggle_activation_student_modal"
                            data-student-unique-id="{{ $student->student_unique_id }}"
                            data-student-name="{{ $student->name }}" data-student-id="{{ $student->id }}"
                            data-active-status="{{ optional($student->studentActivation)->active_status }}"><i
                                class="bi bi-person-slash fs-2 me-2"></i> Deactivate Student</a>
                    @else
                        <a href="#" class="menu-link px-5 text-hover-success" data-bs-toggle="modal"
                            data-bs-target="#kt_toggle_activation_student_modal"
                            data-student-unique-id="{{ $student->student_unique_id }}"
                            data-student-name="{{ $student->name }}" data-student-id="{{ $student->id }}"
                            data-active-status="{{ optional($student->studentActivation)->active_status }}"><i
                                class="bi bi-person-check fs-2 me-2"></i> Activate Student</a>
                    @endif
                </div>
                <!--end::Menu item-->

                @if (optional($student->studentActivation)->active_status == 'active')
                    <div class="menu-item px-5">
                        <a href="{{ route('students.download', $student->id) }}" target="_blank"
                            class="menu-link text-hover-primary px-5"><i class="bi bi-download fs-2 me-2"></i>
                            Download</a>
                    </div>
                @endif

                <!--begin::Menu item-->
                <div class="menu-item px-5 my-1">
                    <a href="{{ route('students.edit', $student->id) }}" class="menu-link px-5 text-hover-primary"><i
                            class="las la-pen fs-3 me-2"></i> Edit
                        Students</a>
                </div>
                <!--end::Menu item-->
                <!--begin::Menu item-->
                <div class="menu-item px-5">
                    <a href="#" class="menu-link text-hover-danger px-5 delete-student"
                        data-student-id="{{ $student->id }}"><i class="bi bi-trash fs-3 me-2"></i>
                        Delete Student</a>
                </div>
                <!--end::Menu item-->
            </div>
            <!--end::Menu-->
            <!--end::Menu-->
        </li>
        <!--end:::Tab item-->
    </ul>
    <!--end:::Tabs-->

    <!--begin:::Tab content-->
    <div class="tab-content" id="myTabContent">
        <!--begin:::Tab pane-->
        <div class="tab-pane fade show active" id="kt_due_invoices_tab" role="tabpanel">
            <!--begin::Card-->
            <div class="card">
                <!--begin::Card header-->
                <div class="card-header border-0 pt-6">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <!--begin::Search-->
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text"
                                data-kt-subscription-table-filter="search"
                                class="form-control form-control-solid w-350px ps-12" placeholder="Search In Guardians">
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
                                        <button type="reset"
                                            class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                            data-kt-menu-dismiss="true"
                                            data-kt-subscription-table-filter="reset">Reset</button>
                                        <button type="submit" class="btn btn-primary fw-semibold px-6"
                                            data-kt-menu-dismiss="true"
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
                    <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                        id="kt_guardians_table">
                        <thead>
                            <tr class="fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-25px">SL</th>
                                <th class="min-w-200px">Name</th>
                                <th class="d-none">Gender (filter)</th>
                                <th>Gender</th>
                                <th>Students</th>
                                <th>Mobile</th>
                                <th>Relationship</th>
                                <th>Monthly<br>Payment (৳)</th>
                                <th>Branch</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            @foreach ($invoices as $guardian)
                                <tr>
                                    <td class="pe-2">{{ $loop->index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
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
                                                <span class="text-gray-800 mb-1">{{ $guardian->name }}
                                                </span>
                                            </div>
                                            <!--begin::user details-->
                                        </div>
                                    </td>
                                    <td class="d-none">gd_{{ $guardian->gender }}</td>
                                    <td>
                                        @if ($guardian->gender == 'male')
                                            <i class="las la-mars"></i>
                                            {{ ucfirst($guardian->gender) }}
                                        @else
                                            <i class="las la-venus"></i>
                                            {{ ucfirst($guardian->gender) }}
                                        @endif
                                    </td>
                                    <td>
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

                                    <td>
                                        {{ $guardian->mobile_number }}
                                    </td>
                                    <td>
                                        {{ ucfirst($guardian->relationship) }}
                                    </td>
                                    <td>
                                        {{ intval(optional(optional($guardian->student)->payments)->tuition_fee) }}
                                    </td>
                                    <td>
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
                                    <td>
                                        <a href="#" title="Edit Guardian" data-bs-toggle="modal"
                                            data-bs-target="#kt_modal_edit_guardian"
                                            data-guardian-id="{{ $guardian->id }}"
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
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end:::Tab pane-->

        <!--begin:::Tab pane-->
        <div class="tab-pane fade" id="kt_paid_invoices_tab" role="tabpanel">
            <!--begin::Card-->
            <div class="card pt-4 mb-6 mb-xl-9">
                <!--begin::Card header-->
                <div class="card-header border-0">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>{{ $student->class->name }} ({{ $student->class->class_numeral }}) @if ($student->academic_group != 'General')
                                - {{ $student->academic_group }}
                            @endif
                        </h2>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body py-0">
                    <!--begin::Table wrapper-->
                    @php
                        $groupedSubjects = $student->subjectsTaken->groupBy(
                            fn($item) => $item->subject->academic_group ?? 'Unknown',
                        );
                    @endphp

                    <div class="row">
                        {{-- Render priority groups first --}}
                        @foreach (['General', 'Science', 'Commerce'] as $priorityGroup)
                            @if ($groupedSubjects->has($priorityGroup))
                                <div class="col-12 mb-2">
                                    <h5 class="fw-bold">{{ $priorityGroup }}</h5>
                                    <div class="row">
                                        @foreach ($groupedSubjects[$priorityGroup] as $subjectTaken)
                                            <div class="col-md-3 mb-3">
                                                <h6 class="text-gray-600">
                                                    <i class="bi bi-check2-circle fs-3 text-success"></i>
                                                    {{ $subjectTaken->subject->name ?? 'N/A' }}
                                                </h6>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        {{-- Render remaining non-priority groups --}}
                        @foreach ($groupedSubjects as $group => $subjects)
                            @if (!in_array($group, ['General', 'Science', 'Commerce']))
                                <div class="col-12 mb-2">
                                    <h4 class="fw-bold">{{ $group }}</h4>
                                    <div class="row">
                                        @foreach ($subjects as $subjectTaken)
                                            <div class="col-md-3 mb-3">
                                                <h5 class="text-gray-700">
                                                    <i class="bi bi-check2-circle fs-3 text-success"></i>
                                                    {{ $subjectTaken->subject->name ?? 'N/A' }}
                                                </h5>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <!--end::Table wrapper-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end:::Tab pane-->
    </div>
    <!--end:::Tab content-->
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        document.getElementById("invoices_link").classList.add("active");
    </script>
@endpush
