@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', $student->name)

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            {{ $student->name }}, {{ $student->student_unique_id }}
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
            @if (optional($student->studentActivation)->active_status === 'inactive') border border-dashed border-danger @elseif ($student->studentActivation == null) border border-dashed border-info @endif"
                data-kt-sticky="true" data-kt-sticky-name="student-summary" data-kt-sticky-offset="{default: false, lg: 0}"
                data-kt-sticky-width="{lg: '250px', xl: '350px'}" data-kt-sticky-left="auto" data-kt-sticky-top="100px"
                data-kt-sticky-animation="false" data-kt-sticky-zindex="95">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h2>Student Info</h2>
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
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                @if (optional($student->studentActivation)->active_status == 'active')
                                    <a href="#" class="menu-link text-hover-warning px-3" data-bs-toggle="modal"
                                        data-bs-target="#kt_toggle_activation_student_modal"
                                        data-student-unique-id="{{ $student->student_unique_id }}"
                                        data-student-name="{{ $student->name }}" data-student-id="{{ $student->id }}"
                                        data-active-status="{{ optional($student->studentActivation)->active_status }}"><i
                                            class="bi bi-person-slash fs-2 me-2"></i> Deactivate</a>
                                @else
                                    <a href="#" class="menu-link text-hover-success px" data-bs-toggle="modal"
                                        data-bs-target="#kt_toggle_activation_student_modal"
                                        data-student-unique-id="{{ $student->student_unique_id }}"
                                        data-student-name="{{ $student->name }}" data-student-id="{{ $student->id }}"
                                        data-active-status="{{ optional($student->studentActivation)->active_status }}"><i
                                            class="bi bi-person-check fs-3 me-2"></i> Activate</a>
                                @endif

                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="{{ route('students.edit', $student->id) }}"
                                    class="menu-link text-hover-primary px-3"><i class="las la-pen fs-3 me-2"></i> Edit</a>
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link text-hover-danger px-3 delete-student"
                                    data-student-id="{{ $student->id }}"><i class="bi bi-trash fs-3 me-2"></i>
                                    Delete</a>
                            </div>
                            <!--end::Menu item-->
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
                                {{-- <img alt="Pic" src="{{ asset('assets/media/avatars/300-5.jpg') }}" /> --}}
                                <img src="{{ $student->photo_url ? asset($student->photo_url) : asset('assets/img/dummy.png') }}"
                                    alt="{{ $student->name }}" />
                            </div>
                            <!--end::Avatar-->
                            <!--begin::Info-->
                            <div class="d-flex flex-column">
                                <!--begin::Name-->
                                <span class="fs-4 fw-bold text-gray-900 me-2">{{ $student->name }}</span>
                                <!--end::Name-->
                                <!--begin::Student ID-->
                                <span class="fw-bold text-gray-600">{{ $student->student_unique_id }}</span>
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
                        <h5 class="mb-4">Academic Info
                        </h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <div class="mb-0">
                            <!--begin::Details-->
                            <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Class:</td>
                                    <td class="text-gray-800">{{ $student->class->name }}
                                        ({{ $student->class->class_numeral }})</td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                @if ($student->academic_group != 'General')
                                    <tr>
                                        <td class="text-gray-500">Group:</td>
                                        <td>{{ $student->academic_group }}</td>
                                    </tr>
                                @endif
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Shift:</td>
                                    <td>{{ $student->shift->name }}</td>
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
                        <h5 class="mb-4">Payment Info
                        </h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <div class="mb-0">
                            <!--begin::Details-->
                            <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Payment Style:</td>
                                    <td class="text-gray-800">
                                        @if ($student->payments)
                                            {{ ucfirst($student->payments->payment_style) }}
                                        @else
                                            Current
                                        @endif
                                    </td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Monthly Fee:</td>
                                    <td>
                                        @if ($student->payments)
                                            ৳ {{ $student->payments->tuition_fee }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Due Date:</td>
                                    <td class="text-gray-800">1 to @if ($student->payments)
                                            {{ $student->payments->due_date }}
                                        @else
                                            7
                                        @endif
                                    </td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr>
                                    <td class="text-gray-500">Reference:</td>
                                    @if ($student->reference && $student->reference->referer)
                                        <td class="text-gray-800">
                                            @php
                                                $referer = $student->reference->referer;
                                                $type = strtolower($student->reference->referer_type);
                                                $route =
                                                    $type === 'student'
                                                        ? route('students.show', $referer->id)
                                                        : route('teachers.show', $referer->id);
                                            @endphp

                                            <a href="{{ $route }}"
                                                class="fw-bold text-gray-800 text-hover-primary">
                                                {{ $referer->name ?? 'N/A' }}
                                                @if ($type === 'student')
                                                    ({{ $referer->student_unique_id }})
                                                @endif
                                            </a>
                                    @endif
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
                    <div class="mb-10">
                        <!--begin::Title-->
                        <h5 class="mb-4">Activation Details</h5>
                        <!--end::Title-->
                        <!--begin::Details-->
                        <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Status:</td>
                                <td>
                                    @php
                                        $status = $student->studentActivation?->active_status;
                                    @endphp

                                    @if ($status === 'inactive')
                                        <span class="badge badge-light-danger">{{ ucfirst($status) }}</span>
                                    @elseif ($status === 'active')
                                        <span class="badge badge-light-success">{{ ucfirst($status) }}</span>
                                    @else
                                        <span class="badge badge-light-info">Pending Approval</span>
                                    @endif

                                </td>
                            </tr>
                            <!--end::Row-->

                            <!--begin::Row-->
                            <tr class="">
                                @if (optional($student->studentActivation)->active_status == 'active')
                                    <td class="text-gray-500">Active Since:</td>
                                @elseif (optional($student->studentActivation)->active_status == 'inactive')
                                    <td class="text-gray-500">Inactive Since:</td>
                                @endif

                                @if ($student->studentActivation)
                                    <td class="text-gray-800">
                                        {{ $student->studentActivation->created_at->diffForHumans() }}
                                        <span class="ms-1" data-bs-toggle="tooltip"
                                            title="{{ $student->studentActivation->created_at->format('d-M-Y h:m:s A') }}">
                                            <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                        </span>
                                    </td>
                                @endif
                            </tr>
                            <!--end::Row-->

                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Admission Date:</td>
                                <td class="text-gray-800">
                                    {{ $student->created_at->format('d-M-Y') }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $student->created_at->format('d-M-Y h:m:s A') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>

                                </td>
                            </tr>
                            <!--end::Row-->

                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Remarks:</td>
                                <td class="text-gray-800">
                                    @if ($student->remarks)
                                        {{ $student->remarks }}
                                    @else
                                        <span class="text-gray-600">-</span>
                                    @endif
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
                            <a href="{{ route('students.edit', $student->id) }}"
                                class="menu-link px-5 text-hover-primary"><i class="las la-pen fs-3 me-2"></i> Edit
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
                <div class="tab-pane fade show active" id="kt_student_view_personal_info_tab" role="tabpanel">
                    <!--begin::Personal Info-->
                    <div class="card mb-5 mb-xl-10" id="kt_profile_details_view">
                        <!--begin::Card header-->
                        <div class="card-header cursor-pointer">
                            <!--begin::Card title-->
                            <div class="card-title m-0">
                                <h3 class="fw-bold m-0">{{ $student->name }}'s Info</h3>
                            </div>
                            <!--end::Card title-->
                        </div>
                        <!--begin::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body p-9">
                            <!--begin::Row-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Full Name</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8">
                                    <span class="fw-bold fs-6 text-gray-800">{{ $student->name }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Row-->

                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Gender</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <span class="fw-bold text-gray-800 fs-6">
                                        @if ($student->gender == 'male')
                                            <i class="las la-mars fs-4"></i>
                                        @elseif ($student->gender == 'female')
                                            <i class="las la-venus fs-4"></i>
                                        @endif

                                        {{ ucfirst($student->gender) }}
                                    </span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Blood Group</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <span class="fw-bold text-gray-800 fs-6">{{ $student->blood_group }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Date of Birth</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <span
                                        class="fw-bold text-gray-800 fs-6">{{ $student->date_of_birth->format('d-M-Y') }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Home Address</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <span class="fw-bold text-gray-800 fs-6">{{ $student->home_address }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Religion</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 fv-row">
                                    <span class="fw-bold text-gray-800 fs-6">{{ $student->religion }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Email</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 d-flex align-items-center">
                                    <span class="fw-bold fs-6 text-gray-800 me-2">{{ $student->email }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Phone (Home)</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 d-flex align-items-center">
                                    <span
                                        class="fw-bold fs-6 text-gray-800 me-2">{{ $student->mobileNumbers->where('number_type', 'home')->pluck('mobile_number')->implode('') }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Phone (SMS)</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 d-flex align-items-center">
                                    <span
                                        class="fw-bold fs-6 text-gray-800 me-2">{{ $student->mobileNumbers->where('number_type', 'sms')->pluck('mobile_number')->implode('') }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Phone (WhatsApp)</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8 d-flex align-items-center">
                                    <span
                                        class="fw-bold fs-6 text-gray-800 me-2">{{ $student->mobileNumbers->where('number_type', 'whatsapp')->pluck('mobile_number')->implode('') }}</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-5">
                                <!--begin::Label-->
                                <label class="col-lg-4 fw-semibold text-muted fs-6">Institution
                                    <span class="ms-1" data-bs-toggle="tooltip" title="School or College Name">
                                        <i class="ki-outline ki-information fs-7">
                                        </i>
                                    </span></label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8">
                                    <span class="fw-bold fs-6 text-gray-800">{{ $student->institution->name }} (EIIN:
                                        {{ $student->institution->eiin_number }})</span>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Personal Info-->

                    <!--begin::Guardians-->
                    <div class="card mb-5 mb-xl-10">
                        <!--begin::Card header-->
                        <div class="card-header">
                            <!--begin::Title-->
                            <div class="card-title">
                                <h3>Guardian Info</h3>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body">
                            <!--begin::Guardians-->
                            <div class="row gx-9 gy-6">
                                @foreach ($student->guardians as $guardian)
                                    <!--begin::Col-->
                                    <div class="col-xl-6">
                                        <!--begin::Guardian-->
                                        <div class="card card-dashed h-xl-100 flex-row flex-wrap p-6 align-items-center">
                                            <!--begin::Photo-->
                                            <div class="symbol symbol-60px me-5">
                                                <img src="{{ $guardian->photo_url ?? asset($guardian->gender == 'male' ? 'img/male.png' : 'img/female.png') }}"
                                                    alt="{{ $guardian->name }}">
                                            </div>
                                            <!--end::Photo-->

                                            <!--begin::Details-->
                                            <div class="d-flex flex-column py-2">
                                                <div class="d-flex align-items-center fs-5 fw-bold mb-2">
                                                    {{ $guardian->name }}
                                                </div>
                                                <div class="fs-6 fw-semibold text-gray-600">
                                                    {{ $guardian->mobile_number }}<br>
                                                    {{ ucfirst($guardian->relationship) }}<br>
                                                </div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Guardian-->

                                    </div>
                                    <!--end::Col-->
                                @endforeach

                            </div>
                            <!--end::Guardians-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Guardians-->

                    <!--begin::Siblings-->
                    <div class="card mb-5 mb-xl-10">
                        <!--begin::Card header-->
                        <div class="card-header">
                            <!--begin::Title-->
                            <div class="card-title">
                                <h3>Sibling Info</h3>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body">
                            <!--begin::Guardians-->
                            <div class="row gx-9 gy-6">
                                @foreach ($student->siblings as $sibling)
                                    <!--begin::Col-->
                                    <div class="col-xl-6">
                                        <!--begin::Guardian-->
                                        <div class="card card-dashed h-xl-100 flex-row flex-wrap p-6 align-items-center">
                                            <!--begin::Photo-->
                                            <div class="symbol symbol-60px me-5">
                                                <img src="{{ $sibling->photo_url ?? asset($sibling->relationship == 'brother' ? 'img/male.png' : 'img/female.png') }}"
                                                    alt="{{ $sibling->name }}">
                                            </div>
                                            <!--end::Photo-->

                                            <!--begin::Details-->
                                            <div class="d-flex flex-column py-2">
                                                <div class="d-flex align-items-center fs-5 fw-bold mb-2">
                                                    {{ $sibling->name }} <span
                                                        class="ms-5 text-gray-600 fs-7    fw-semibold">{{ ucfirst($sibling->relationship) }}</span>
                                                </div>
                                                <div class="fs-6 fw-semibold text-gray-600">
                                                    Class: {{ $sibling->class }}<br>
                                                    School: {{ $sibling->institution->name }}
                                                </div>
                                            </div>
                                            <!--end::Details-->
                                        </div>
                                        <!--end::Guardian-->

                                    </div>
                                    <!--end::Col-->
                                @endforeach

                            </div>
                            <!--end::Guardians-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Siblings-->

                </div>
                <!--end:::Tab pane-->

                <!--begin:::Tab pane-->
                <div class="tab-pane fade" id="kt_student_view_enrolled_subjects_tab" role="tabpanel">
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

                <!--begin:::Tab pane-->
                <div class="tab-pane fade" id="kt_student_view_transactions_tab" role="tabpanel">
                    <!--begin::Earnings-->
                    <div class="card mb-6 mb-xl-9">
                        <!--begin::Header-->
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2>Tuition Fee Payment Summary</h2>
                            </div>
                        </div>
                        <!--end::Header-->
                        <!--begin::Body-->
                        <div class="card-body py-0">
                            <div class="fs-5 fw-semibold text-gray-500 mb-4">Summary of transacted amount of this student.
                            </div>
                            <!--begin::Left Section-->
                            <div class="d-flex flex-wrap flex-stack mb-5">
                                <!--begin::Row-->
                                <div class="d-flex flex-wrap">
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-gray-300 w-150px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span data-kt-countup="true"
                                                data-kt-countup-value="{{ $student->paymentTransactions->sum('amount_paid') }}"
                                                data-kt-countup-prefix="৳">0</span>
                                        </span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Total Paid</span>
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-gray-300 w-125px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span class="" data-kt-countup="true"
                                                data-kt-countup-value="{{ $student->paymentInvoices->whereNull('deleted_at')->count() }}">0</span></span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Months</span>
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-warning w-150px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span data-kt-countup="true"
                                                data-kt-countup-value="{{ $student->paymentInvoices->whereNull('deleted_at')->sum('amount_due') }}"
                                                data-kt-countup-prefix="৳">0</span>
                                        </span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Due</span>
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Row-->
                            </div>
                            <!--end::Left Section-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Earnings-->
                    <!--begin::Statements-->
                    <div class="card mb-6 mb-xl-9">
                        <!--begin::Header-->
                        <div class="card-header">
                            <!--begin::Title-->
                            <div class="card-title">
                                <h2>Statement</h2>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::Header-->
                        <!--begin::Card body-->
                        <div class="card-body pb-5">
                            <!--begin::Table-->
                            <table id="kt_student_view_transactions_table"
                                class="table table-hover align-middle table-row-dashed fs-6 fw-semibold gy-4 ucms-table">
                                <thead class="border-bottom border-gray-200">
                                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-25px">SL</th>
                                        <th class="w-125px">Date</th>
                                        <th class="w-100px">Invoice No.</th>
                                        <th class="w-300px">Voucher No.</th>
                                        <th class="w-100px">Amount</th>
                                        <th class="w-100px">Payment Type</th>
                                        <th class="w-100px text-end pe-7">Download</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($student->paymentTransactions->sortByDesc('created_at') as $transaction)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>{{ $transaction->created_at->format('d-M-Y') }}
                                                <span class="ms-1" data-bs-toggle="tooltip"
                                                    title="{{ $transaction->created_at->format('d-M-Y h:i:s A') }}">
                                                    <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('invoices.show', $transaction->paymentInvoice->id) }}">
                                                    {{ $transaction->paymentInvoice->invoice_number }}
                                                </a>
                                            </td>
                                            <td>{{ $transaction->voucher_no }}</td>
                                            <td class="text-success">৳ {{ $transaction->amount_paid }}</td>
                                            <td>
                                                @if ($transaction->payment_type === 'partial')
                                                    <span class="badge badge-warning">Partial</span>
                                                @elseif ($transaction->payment_type === 'full')
                                                    <span class="badge badge-success">Full Paid</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('transactions.download', $transaction->id) }}"
                                                    target="_blank" data-bs-toggle="tooltip" title="Download Payslip"
                                                    class="btn btn-icon btn-active-light-primary w-30px h-30px me-3">
                                                    <i class="bi bi-download fs-2"></i>
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
                    <!--end::Statements-->
                </div>
                <!--end:::Tab pane-->

                <!--begin:::Tab pane-->
                <div class="tab-pane fade" id="kt_student_view_sheets_tab" role="tabpanel">
                    <!--begin::Earnings-->
                    <div class="card mb-6 mb-xl-9">
                        <!--begin::Header-->
                        <div class="card-header border-0">
                            <div class="card-title">
                                <h2>Sheet Payment Summary</h2>
                            </div>
                        </div>
                        <!--end::Header-->
                        <!--begin::Body-->
                        <div class="card-body py-0">
                            <div class="fs-5 fw-semibold text-gray-500 mb-4">Summary of transacted amount of this student.
                            </div>
                            <!--begin::Left Section-->
                            <div class="d-flex flex-wrap flex-stack mb-5">
                                <!--begin::Row-->
                                <div class="d-flex flex-wrap">
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-gray-300 w-150px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span data-kt-countup="true" data-kt-countup-value="6,840"
                                                data-kt-countup-prefix="৳">0</span>
                                        </span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Total Paid</span>
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-gray-300 w-125px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span class="" data-kt-countup="true"
                                                data-kt-countup-value="16">0</span></span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Months</span>
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-warning w-150px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span data-kt-countup="true" data-kt-countup-value="1,240"
                                                data-kt-countup-prefix="৳">0</span>
                                        </span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Due</span>
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Row-->
                            </div>
                            <!--end::Left Section-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Earnings-->
                    <!--begin::Statements-->
                    <div class="card mb-6 mb-xl-9">
                        <!--begin::Header-->
                        <div class="card-header">
                            <!--begin::Title-->
                            <div class="card-title">
                                <h2>Statement</h2>
                            </div>
                            <!--end::Title-->
                        </div>
                        <!--end::Header-->
                        <!--begin::Card body-->
                        <div class="card-body pb-5">
                            <!--begin::Table-->
                            <table id="kt_student_view_sheets_table"
                                class="table align-middle table-row-dashed fs-6 text-gray-600 fw-semibold gy-4">
                                <thead class="border-bottom border-gray-200">
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-125px">Date</th>
                                        <th class="w-100px">Order ID</th>
                                        <th class="w-300px">Details</th>
                                        <th class="w-100px">Amount</th>
                                        <th class="w-100px text-end pe-7">Invoice</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Nov 01, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">102445788</a>
                                        </td>
                                        <td>Darknight transparency 36 Icons Pack</td>
                                        <td class="text-success">$38.00</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Oct 24, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">423445721</a>
                                        </td>
                                        <td>Seller Fee</td>
                                        <td class="text-danger">$-2.60</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Oct 08, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">312445984</a>
                                        </td>
                                        <td>Cartoon Mobile Emoji Phone Pack</td>
                                        <td class="text-success">$76.00</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Sep 15, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">312445984</a>
                                        </td>
                                        <td>Iphone 12 Pro Mockup Mega Bundle</td>
                                        <td class="text-success">$5.00</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>May 30, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">523445943</a>
                                        </td>
                                        <td>Seller Fee</td>
                                        <td class="text-danger">$-1.30</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Apr 22, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">231445943</a>
                                        </td>
                                        <td>Parcel Shipping / Delivery Service App</td>
                                        <td class="text-success">$204.00</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Feb 09, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">426445943</a>
                                        </td>
                                        <td>Visual Design Illustration</td>
                                        <td class="text-success">$31.00</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Nov 01, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">984445943</a>
                                        </td>
                                        <td>Abstract Vusial Pack</td>
                                        <td class="text-success">$52.00</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Jan 04, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">324442313</a>
                                        </td>
                                        <td>Seller Fee</td>
                                        <td class="text-danger">$-0.80</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Nov 01, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">102445788</a>
                                        </td>
                                        <td>Darknight transparency 36 Icons Pack</td>
                                        <td class="text-success">$38.00</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Oct 24, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">423445721</a>
                                        </td>
                                        <td>Seller Fee</td>
                                        <td class="text-danger">$-2.60</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Oct 08, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">312445984</a>
                                        </td>
                                        <td>Cartoon Mobile Emoji Phone Pack</td>
                                        <td class="text-success">$76.00</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Sep 15, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">312445984</a>
                                        </td>
                                        <td>Iphone 12 Pro Mockup Mega Bundle</td>
                                        <td class="text-success">$5.00</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>May 30, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">523445943</a>
                                        </td>
                                        <td>Seller Fee</td>
                                        <td class="text-danger">$-1.30</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Apr 22, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">231445943</a>
                                        </td>
                                        <td>Parcel Shipping / Delivery Service App</td>
                                        <td class="text-success">$204.00</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Feb 09, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">426445943</a>
                                        </td>
                                        <td>Visual Design Illustration</td>
                                        <td class="text-success">$31.00</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Nov 01, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">984445943</a>
                                        </td>
                                        <td>Abstract Vusial Pack</td>
                                        <td class="text-success">$52.00</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Jan 04, 2021</td>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary">324442313</a>
                                        </td>
                                        <td>Seller Fee</td>
                                        <td class="text-danger">$-0.80</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <!--end::Table-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Statements-->
                </div>
                <!--end:::Tab pane-->

                <!--begin:::Tab pane-->
                <div class="tab-pane fade" id="kt_student_view_activity_tab" role="tabpanel">
                    <!--begin::Card-->
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <!--begin::Card header-->
                        <div class="card-header border-0">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <h2>Activations</h2>
                            </div>
                            <!--end::Card title-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body pt-0 pb-5">
                            <!--begin::Table wrapper-->
                            <div class="table-responsive">
                                <!--begin::Table-->
                                <table class="table align-middle table-row-dashed gy-5" id="kt_table_users_login_session">
                                    <thead class="border-bottom border-gray-200 fs-7 fw-bold">
                                        <tr class="text-start text-muted text-uppercase gs-0">
                                            <th class="min-w-100px">Activity</th>
                                            <th>Reason</th>
                                            <th>Updated by</th>
                                            <th class="min-w-125px">Time</th>
                                        </tr>
                                    </thead>
                                    <tbody class="fs-6 fw-semibold text-gray-600">
                                        @foreach ($student->activations->sortByDesc('created_at') as $record)
                                            <tr>
                                                <td>
                                                    @if ($record->active_status == 'inactive')
                                                        <span
                                                            class="badge badge-light-danger">{{ ucfirst($record->active_status) }}</span>
                                                    @else
                                                        <span
                                                            class="badge badge-light-success">{{ ucfirst($record->active_status) }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $record->reason }}</td>
                                                <td>{{ $record->updatedBy->name }}</td>
                                                <td>{{ $record->created_at->diffForHumans() }} <span class="ms-1"
                                                        data-bs-toggle="tooltip"
                                                        title="{{ $record->created_at->format('d-M-Y h:m:s A') }}">
                                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                                    </span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <!--end::Table-->
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
        </div>
        <!--end::Content-->
    </div>
    <!--end::Layout-->

    <!--begin::Modal - Toggle Activation Student-->
    <div class="modal fade" id="kt_toggle_activation_student_modal" tabindex="-1" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header">
                    <!--begin::Modal title-->
                    <h2 id="toggle-activation-modal-title">Activation/Deactivation Student</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->

                <!--begin::Modal body-->
                <div class="modal-body py-lg-5">
                    <!--begin::Content-->
                    <div class="flex-row-fluid p-lg-5">
                        <div>
                            <form action="{{ route('students.toggleActive') }}" class="form d-flex flex-column"
                                method="POST">
                                @csrf
                                <!--begin::Left column-->
                                <div class="d-flex flex-column">

                                    <input type="hidden" name="student_id" id="student_id" />
                                    <input type="hidden" name="active_status" id="activation_status" />

                                    <div class="row">
                                        <div class="col-lg-12">
                                            <!--begin::Input group-->
                                            <div class="d-flex flex-column mb-5 fv-row">
                                                <!--begin::Label-->
                                                <label class="fs-5 fw-semibold mb-2 required"
                                                    id="reason_label">Activation/Deactivation Reason</label>
                                                <!--end::Label-->

                                                <!--begin::Input-->
                                                <textarea class="form-control" rows="3" name="reason" placeholder="Write the reason for this update"
                                                    required></textarea>
                                                <!--end::Input-->
                                            </div>
                                            <!--end::Input group-->
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <!--begin::Button-->
                                        <button type="reset" class="btn btn-secondary me-5"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <!--end::Button-->
                                        <!--begin::Button-->
                                        <button type="submit" class="btn btn-primary">
                                            Submit
                                        </button>
                                        <!--end::Button-->
                                    </div>
                                </div>
                                <!--end::Left column-->
                            </form>
                        </div>
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - Toggle Activation Student-->
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        const routeDeleteStudent = "{{ route('students.destroy', ':id') }}";
        const routeToggleActive = "{{ route('students.toggleActive', ':id') }}";
    </script>

    <script src="{{ asset('js/students/view.js') }}"></script>

    <script>
        document.getElementById("student_info_menu").classList.add("here", "show");
        document.getElementById("all_students_link").classList.add("active");
    </script>
@endpush
