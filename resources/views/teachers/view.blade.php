@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', $teacher->name)

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            {{ $teacher->name }}
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
                    Teacher Info </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                View </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection

@section('content')
    <!--begin::Layout-->
    <div class="d-flex flex-column flex-xl-row">
        <!--begin::Sidebar-->
        <div class="flex-column flex-lg-row-auto w-100 w-xl-350px mb-10">
            <!--begin::Card-->
            <div class="card card-flush mb-0
            @if ($teacher->is_active === false) border border-dashed border-danger @endif"
                data-kt-sticky="true" data-kt-sticky-name="student-summary" data-kt-sticky-offset="{default: false, lg: 0}"
                data-kt-sticky-width="{lg: '250px', xl: '350px'}" data-kt-sticky-left="auto" data-kt-sticky-top="100px"
                data-kt-sticky-animation="false" data-kt-sticky-zindex="95">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>Teacher Info</h2>
                    </div>
                    <!--end::Card title-->
                    <!--begin::Card toolbar-->
                    <div class="card-toolbar">
                        <!--begin::More options-->
                        <a href="#" class="btn btn-sm btn-light btn-icon" data-kt-menu-trigger="click"
                            data-kt-menu-placement="bottom-end">
                            <i class="ki-outline ki-dots-horizontal fs-3">
                            </i>
                        </a>
                        <!--begin::Menu-->
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-6 w-175px py-4"
                            data-kt-menu="true">
                            @can('teachers.edit')
                                <div class="menu-item px-3">
                                    @if ($teacher->is_active === true)
                                        <a href="#" class="menu-link text-hover-warning px-3" data-bs-toggle="modal"
                                            data-bs-target="#kt_toggle_activation_student_modal"
                                            data-teacher-name="{{ $teacher->name }}" data-teacher-id="{{ $teacher->id }}"
                                            data-active-status="{{ $teacher->is_active }}"><i
                                                class="bi bi-person-slash fs-2 me-2"></i> Deactivate</a>
                                    @else
                                        <a href="#" class="menu-link text-hover-success px" data-bs-toggle="modal"
                                            data-bs-target="#kt_toggle_activation_student_modal"
                                            data-teacher-name="{{ $teacher->name }}" data-teacher-id="{{ $teacher->id }}"
                                            data-active-status="{{ $teacher->is_active }}"><i
                                                class="bi bi-person-check fs-3 me-2"></i> Activate</a>
                                    @endif
                                </div>

                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="{{ route('teachers.edit', $teacher->id) }}"
                                        class="menu-link text-hover-primary px-3"><i class="las la-pen fs-3 me-2"></i> Edit</a>
                                </div>
                                <!--end::Menu item-->
                            @endcan

                            @can('teachers.delete')
                                <!--begin::Menu item-->
                                <div class="menu-item px-3">
                                    <a href="#" class="menu-link text-hover-danger px-3 delete-teacher"
                                        data-teacher-id="{{ $teacher->id }}"><i class="bi bi-trash fs-3 me-2"></i>
                                        Delete</a>
                                </div>
                                <!--end::Menu item-->
                            @endcan
                        </div>
                        <!--end::Menu-->
                        <!--end::More options-->
                    </div>
                    <!--end::Card toolbar-->
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body pt-0 fs-6">
                    <!--begin::Section-->
                    <div class="mb-7">
                        <!--begin::Details-->
                        <div class="d-flex align-items-center">
                            <!--begin::Avatar-->
                            <div class="symbol symbol-60px symbol-circle me-3">
                                <img src="{{ $teacher->photo_url ? asset($teacher->photo_url) : asset($teacher->gender == 'male' ? 'img/male-placeholder.png' : 'img/female-placeholder.png')  }}" />

                            </div>
                            <!--end::Avatar-->
                            <!--begin::Info-->
                            <div class="d-flex flex-column">
                                <!--begin::Name-->
                                <span class="fs-4 fw-bold text-gray-900 me-2">{{ $teacher->name }}</span>
                                <!--end::Name-->
                                <!--begin::Student ID-->
                                <span class="fw-bold text-gray-600">{{ $teacher->phone }}</span>
                                <!--end::Student ID-->
                            </div>
                            <!--end::Info-->
                        </div>
                        <!--end::Details-->
                    </div>
                    <!--end::Section-->

                    <!--begin::Seperator-->
                    <div class="separator separator-dashed mb-7"></div>
                    <!--end::Seperator-->

                    <!--begin::Section-->
                    <div class="mb-7">
                        <!--begin::Title-->
                        <h5 class="mb-4">Bio
                        </h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <div class="mb-0">
                            <!--begin::Details-->
                            <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Gender:</td>
                                    <td>
                                        @if ($teacher->gender == 'male')
                                            <i class="las la-mars fs-4"></i>
                                        @elseif ($teacher->gender == 'female')
                                            <i class="las la-venus fs-4"></i>
                                        @endif

                                        {{ ucfirst($teacher->gender) }}
                                    </td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Qualification:</td>
                                    <td class="text-gray-800">{{ $teacher->academic_qualification }}</td>
                                </tr>
                                <!--end::Row-->
                                
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Experience:</td>
                                    <td class="text-gray-800">{{ $teacher->experience }}</td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Email:</td>
                                    <td>
                                        {{ $teacher->email }}
                                    </td>
                                </tr>
                                <!--end::Row-->
                                
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Blood Group:</td>
                                    <td>
                                        {{ $teacher->blood_group }}
                                    </td>
                                </tr>
                                <!--end::Row-->

                            </table>
                            <!--end::Details-->
                        </div>
                        <!--end::Details-->
                    </div>
                    <!--end::Section-->

                    <!--begin::Seperator-->
                    <div class="separator separator-dashed mb-7"></div>
                    <!--end::Seperator-->

                    <!--begin::Section-->
                    <div class="mb-7">
                        <!--begin::Title-->
                        <h5 class="mb-4">Salary Info
                        </h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <div class="mb-0">
                            <!--begin::Details-->
                            <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Base Salary:</td>
                                    <td>
                                        ৳ {{ $teacher->base_salary }}
                                    </td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Total Class taken:</td>
                                    <td class="text-gray-800">
                                        100
                                    </td>
                                </tr>
                                <!--end::Row-->

                            </table>
                            <!--end::Details-->
                        </div>
                        <!--end::Details-->
                    </div>
                    <!--end::Section-->

                    <!--begin::Seperator-->
                    <div class="separator separator-dashed mb-7"></div>
                    <!--end::Seperator-->

                    <!--begin::Section-->
                    <div class="mb-0">
                        <!--begin::Title-->
                        <h5 class="mb-4">Activation Details</h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Status:</td>
                                <td>
                                    @if ($teacher->is_active === false)
                                        <span class="badge badge-danger rounded-pill">Inactive</span>
                                    @else
                                        <span class="badge badge-success rounded-pill">Active</span>
                                    @endif

                                </td>
                            </tr>
                            <!--end::Row-->

                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Registered Since:</td>
                                <td class="text-gray-800">
                                    {{ $teacher->created_at->diffForHumans() }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $teacher->created_at->format('h:i:s A, d-M-Y') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>

                                </td>
                            </tr>
                            <!--end::Row-->
                        </table>
                        <!--end::Details-->
                    </div>
                    <!--end::Section-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Sidebar-->

        <!--begin::Content-->
        <div class="flex-lg-row-fluid ms-lg-10">
            <!--begin:::Tabs-->
            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                        href="#kt_student_view_personal_info_tab"><i class="ki-outline ki-home fs-3 me-2"></i>Personal
                        Info</a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_student_view_enrolled_subjects_tab"><i
                            class="ki-outline ki-book-open fs-3 me-2"></i>Enrolled Subjects</a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                        href="#kt_student_view_transactions_tab"><i
                            class="ki-outline ki-credit-cart fs-3 me-2"></i>Transactions</a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                        href="#kt_student_view_sheets_tab"><i class="ki-outline ki-some-files fs-3 me-2"></i>Sheets</a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                        href="#kt_student_view_activity_tab"><i class="ki-outline ki-save-2 fs-3 me-2"></i>Activity</a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item-->
                <li class="nav-item ms-auto">
                    <!--begin::Action menu-->
                    <a href="#" class="btn btn-primary ps-7" data-kt-menu-trigger="click"
                        data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">Actions
                        <i class="ki-outline ki-down fs-2 me-0"></i></a>
                    <!--begin::Menu-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold py-4 w-250px fs-6"
                        data-kt-menu="true">
                        <!--begin::Menu item-->
                        <div class="menu-item px-5">
                            <div class="menu-content text-muted pb-2 px-5 fs-7 text-uppercase">Sheet</div>
                        </div>
                        <!--end::Menu item-->
                        @can('notes.distribute')
                            <div class="menu-item px-5">
                                <a href="{{ route('notes.distribution.create') }}"
                                    class="menu-link text-hover-primary px-5"><i class="ki-outline ki-note-2 fs-2 me-2"></i>
                                    Note Distribution</a>
                            </div>
                        @endcan

                        <!--begin::Menu separator-->
                        <div class="separator my-3"></div>
                        <!--end::Menu separator-->

                        <!--begin::Menu item-->
                        <div class="menu-item px-5">
                            <div class="menu-content text-muted pb-2 px-5 fs-7 text-uppercase">Account</div>
                        </div>
                        <!--end::Menu item-->

                        @can('nothing')
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
                        @endcan

                        @can('nothing')
                            @if (optional($student->studentActivation)->active_status == 'active')
                                <div class="menu-item px-5">
                                    <a href="{{ route('students.download', $student->id) }}" target="_blank"
                                        class="menu-link text-hover-primary px-5"><i class="bi bi-download fs-2 me-2"></i>
                                        Download Form</a>
                                </div>
                            @endif
                        @endcan

                        @can('nothing')
                            <!--begin::Menu item-->
                            <div class="menu-item px-5 my-1">
                                <a href="{{ route('students.edit', $student->id) }}"
                                    class="menu-link px-5 text-hover-primary"><i class="las la-pen fs-3 me-2"></i> Edit
                                    Student</a>
                            </div>
                            <!--end::Menu item-->
                        @endcan

                        @can('nothing')
                            <!--begin::Menu item-->
                            <div class="menu-item px-5">
                                <a href="#" class="menu-link text-hover-danger px-5 delete-student"
                                    data-student-id="{{ $student->id }}"><i class="bi bi-trash fs-3 me-2"></i>
                                    Delete Student</a>
                            </div>
                            <!--end::Menu item-->
                        @endcan
                    </div>
                    <!--end::Menu-->
                    <!--end::Action Menu-->
                </li>
                <!--end:::Tab item-->
            </ul>
            <!--end:::Tabs-->

            <!--begin:::Tab content-->
            <div class="tab-content" id="myTabContent">

            </div>
            <!--end:::Tab content-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Layout-->
@endsection



@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush


@push('page-js')
    <script>
        const routeDeleteStudent = "{{ route('teachers.destroy', ':id') }}";
        const routeToggleActive = "{{ route('teachers.toggleActive', ':id') }}";
        const routeDeleteInvoice = "{{ route('invoices.destroy', ':id') }}";
        const routeDeleteTxn = "{{ route('transactions.destroy', ':id') }}";
        const routeApproveTxn = "{{ route('transactions.approve', ':id') }}";
    </script>

    <script src="{{ asset('js/teachers/view.js') }}"></script>

    <script>
        document.getElementById("teachers_menu").classList.add("here", "show");
        document.getElementById("teachers_link").classList.add("active");
    </script>
@endpush
