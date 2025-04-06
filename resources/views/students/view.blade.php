@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'View Student')

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
            <div class="card card-flush mb-0 @if ($student->studentActivation->active_status == 'inactive') border border-dashed border-danger @endif"
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
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-6 w-200px py-4"
                            data-kt-menu="true">
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                @if ($student->studentActivation->active_status == 'active')
                                    <a href="#" class="menu-link px-3">Inactivate Student</a>
                                @else
                                    <a href="#" class="menu-link px-3">Activate Student</a>
                                @endif
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="{{ route('students.edit', $student->id) }}" class="menu-link px-3">Edit Student</a>
                            </div>
                            <!--end::Menu item-->
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link text-danger px-3"
                                    data-kt-student-view-action="delete">Delete Student</a>
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

                                {{-- <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Institution:</td>
                                    <td>
                                        @if ($student->institution)
                                            <a href="{{ url('students/?institution=') . $student->institution_id }}"
                                                class="text-gray-800">
                                                {{ $student->institution->name }} (EIIN:
                                                {{ $student->institution->eiin_number }})
                                            </a>
                                        @else
                                            <span class="text-gray-600">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                                <!--end::Row--> --}}
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
                                    <td class="text-gray-800">{{ ucfirst($student->payments->payment_style) }}</td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Monthly Fee:</td>
                                    <td>{{ intval($student->payments->tuition_fee) }} Tk</td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                <tr class="">
                                    <td class="text-gray-500">Due Date:</td>
                                    <td class="text-gray-800">1 to {{ $student->payments->due_date }}</td>
                                </tr>
                                <!--end::Row-->

                                <!--begin::Row-->
                                @if ($student->reference && $student->reference->referer)
                                    <tr>
                                        <td class="text-gray-500">Reference:</td>
                                        <td class="text-gray-800">
                                            @php
                                                $referer = $student->reference->referer;
                                                $type = strtolower($student->reference->referer_type);
                                                $route =
                                                    $type === 'student'
                                                        ? route('students.show', $referer->id)
                                                        : route('teachers.show', $referer->id);
                                            @endphp

                                            <a href="{{ $route }}" class="text-primary fw-bold">
                                                {{ $referer->name ?? 'N/A' }}
                                                @if ($type === 'student')
                                                    ({{ $referer->student_unique_id }})
                                                @endif
                                            </a>
                                        </td>
                                    </tr>
                                @endif
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
                                    @if ($student->studentActivation->active_status == 'inactive')
                                        <span
                                            class="badge badge-light-danger">{{ ucfirst($student->studentActivation->active_status) }}</span>
                                    @else
                                        <span
                                            class="badge badge-light-success">{{ ucfirst($student->studentActivation->active_status) }}</span>
                                    @endif
                                </td>
                            </tr>
                            <!--end::Row-->

                            <!--begin::Row-->
                            <tr class="">
                                @if ($student->studentActivation->active_status == 'active')
                                    <td class="text-gray-500">Active Since:</td>
                                @elseif ($student->studentActivation->active_status == 'inactive')
                                    <td class="text-gray-500">Inactive Since:</td>
                                @endif
                                <td class="text-gray-800">
                                    {{ $student->studentActivation->created_at->diffForHumans() }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $student->studentActivation->created_at->format('d-M-Y h:m:s A') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>
                                </td>
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
                        href="#kt_student_view_personal_info_tab"><i class="ki-outline ki-home fs-3 me-2"></i>Personal Info</a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_student_view_enrolled_subjects_tab"><i class="ki-outline ki-book-open fs-3 me-2"></i>Enrolled Subjects</a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-kt-countup-tabs="true" data-bs-toggle="tab"
                        href="#kt_student_view_transactions_tab"><i class="ki-outline ki-credit-cart fs-3 me-2"></i>Transactions</a>
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
                            @if ($student->studentActivation->active_status == 'active')
                                <a href="#" class="menu-link px-5">Inactivate Student</a>
                            @else
                                <a href="#" class="menu-link px-5">Activate Student</a>
                            @endif
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-5 my-1">
                            <a href="{{ route('students.edit', $student->id) }}" class="menu-link px-5">Edit Students</a>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-5">
                            <a href="#" class="menu-link text-danger px-5">Delete Student</a>
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
                    <!--begin::Card-->
                    <div class="card pt-4 mb-6 mb-xl-9">
                        <!--begin::Card header-->
                        <div class="card-header border-0">
                            <!--begin::Card title-->
                            <div class="card-title">
                                <h2>Payment Records</h2>
                            </div>
                            <!--end::Card title-->
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Filter-->
                                <button type="button" class="btn btn-sm btn-flex btn-light-primary"
                                    data-bs-toggle="modal" data-bs-target="#kt_modal_add_payment">
                                    <i class="ki-outline ki-plus-square fs-3">
                                    </i>Add payment</button>
                                <!--end::Filter-->
                            </div>
                            <!--end::Card toolbar-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body pt-0 pb-5">
                            <!--begin::Table-->
                            <table class="table align-middle table-row-dashed gy-5" id="kt_table_customers_payment">
                                <thead class="border-bottom border-gray-200 fs-7 fw-bold">
                                    <tr class="text-start text-muted text-uppercase gs-0">
                                        <th class="min-w-100px">Invoice No.</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th class="min-w-100px">Date</th>
                                        <th class="text-end min-w-100px pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="fs-6 fw-semibold text-gray-600">
                                    <tr>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary mb-1">9555-4582</a>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-success">Successful</span>
                                        </td>
                                        <td>$1,200.00</td>
                                        <td>14 Dec 2020, 8:43 pm</td>
                                        <td class="pe-0 text-end">
                                            <a href="#"
                                                class="btn btn-sm btn-light image.png btn-active-light-primary"
                                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                                <i class="ki-outline ki-down fs-5 ms-1"></i></a>
                                            <!--begin::Menu-->
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
                                                data-kt-menu="true">
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="apps/customers/view.html" class="menu-link px-3">View</a>
                                                </div>
                                                <!--end::Menu item-->
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="#" class="menu-link px-3"
                                                        data-kt-customer-table-filter="delete_row">Delete</a>
                                                </div>
                                                <!--end::Menu item-->
                                            </div>
                                            <!--end::Menu-->
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary mb-1">2825-8675</a>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-success">Successful</span>
                                        </td>
                                        <td>$79.00</td>
                                        <td>01 Dec 2020, 10:12 am</td>
                                        <td class="pe-0 text-end">
                                            <a href="#"
                                                class="btn btn-sm btn-light image.png btn-active-light-primary"
                                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                                <i class="ki-outline ki-down fs-5 ms-1"></i></a>
                                            <!--begin::Menu-->
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
                                                data-kt-menu="true">
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="apps/customers/view.html" class="menu-link px-3">View</a>
                                                </div>
                                                <!--end::Menu item-->
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="#" class="menu-link px-3"
                                                        data-kt-customer-table-filter="delete_row">Delete</a>
                                                </div>
                                                <!--end::Menu item-->
                                            </div>
                                            <!--end::Menu-->
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary mb-1">2885-1960</a>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-success">Successful</span>
                                        </td>
                                        <td>$5,500.00</td>
                                        <td>12 Nov 2020, 2:01 pm</td>
                                        <td class="pe-0 text-end">
                                            <a href="#"
                                                class="btn btn-sm btn-light image.png btn-active-light-primary"
                                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                                <i class="ki-outline ki-down fs-5 ms-1"></i></a>
                                            <!--begin::Menu-->
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
                                                data-kt-menu="true">
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="apps/customers/view.html" class="menu-link px-3">View</a>
                                                </div>
                                                <!--end::Menu item-->
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="#" class="menu-link px-3"
                                                        data-kt-customer-table-filter="delete_row">Delete</a>
                                                </div>
                                                <!--end::Menu item-->
                                            </div>
                                            <!--end::Menu-->
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary mb-1">1824-7470</a>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-warning">Pending</span>
                                        </td>
                                        <td>$880.00</td>
                                        <td>21 Oct 2020, 5:54 pm</td>
                                        <td class="pe-0 text-end">
                                            <a href="#"
                                                class="btn btn-sm btn-light image.png btn-active-light-primary"
                                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                                <i class="ki-outline ki-down fs-5 ms-1"></i></a>
                                            <!--begin::Menu-->
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
                                                data-kt-menu="true">
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="apps/customers/view.html" class="menu-link px-3">View</a>
                                                </div>
                                                <!--end::Menu item-->
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="#" class="menu-link px-3"
                                                        data-kt-customer-table-filter="delete_row">Delete</a>
                                                </div>
                                                <!--end::Menu item-->
                                            </div>
                                            <!--end::Menu-->
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary mb-1">3705-7211</a>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-success">Successful</span>
                                        </td>
                                        <td>$7,650.00</td>
                                        <td>19 Oct 2020, 7:32 am</td>
                                        <td class="pe-0 text-end">
                                            <a href="#"
                                                class="btn btn-sm btn-light image.png btn-active-light-primary"
                                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                                <i class="ki-outline ki-down fs-5 ms-1"></i></a>
                                            <!--begin::Menu-->
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
                                                data-kt-menu="true">
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="apps/customers/view.html" class="menu-link px-3">View</a>
                                                </div>
                                                <!--end::Menu item-->
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="#" class="menu-link px-3"
                                                        data-kt-customer-table-filter="delete_row">Delete</a>
                                                </div>
                                                <!--end::Menu item-->
                                            </div>
                                            <!--end::Menu-->
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary mb-1">1025-7539</a>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-success">Successful</span>
                                        </td>
                                        <td>$375.00</td>
                                        <td>23 Sep 2020, 12:38 am</td>
                                        <td class="pe-0 text-end">
                                            <a href="#"
                                                class="btn btn-sm btn-light image.png btn-active-light-primary"
                                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                                <i class="ki-outline ki-down fs-5 ms-1"></i></a>
                                            <!--begin::Menu-->
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
                                                data-kt-menu="true">
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="apps/customers/view.html" class="menu-link px-3">View</a>
                                                </div>
                                                <!--end::Menu item-->
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="#" class="menu-link px-3"
                                                        data-kt-customer-table-filter="delete_row">Delete</a>
                                                </div>
                                                <!--end::Menu item-->
                                            </div>
                                            <!--end::Menu-->
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary mb-1">9297-5685</a>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-success">Successful</span>
                                        </td>
                                        <td>$129.00</td>
                                        <td>11 Sep 2020, 3:18 pm</td>
                                        <td class="pe-0 text-end">
                                            <a href="#"
                                                class="btn btn-sm btn-light image.png btn-active-light-primary"
                                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                                <i class="ki-outline ki-down fs-5 ms-1"></i></a>
                                            <!--begin::Menu-->
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
                                                data-kt-menu="true">
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="apps/customers/view.html" class="menu-link px-3">View</a>
                                                </div>
                                                <!--end::Menu item-->
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="#" class="menu-link px-3"
                                                        data-kt-customer-table-filter="delete_row">Delete</a>
                                                </div>
                                                <!--end::Menu item-->
                                            </div>
                                            <!--end::Menu-->
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary mb-1">2332-5361</a>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-danger">Rejected</span>
                                        </td>
                                        <td>$450.00</td>
                                        <td>03 Sep 2020, 1:08 am</td>
                                        <td class="pe-0 text-end">
                                            <a href="#"
                                                class="btn btn-sm btn-light image.png btn-active-light-primary"
                                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                                <i class="ki-outline ki-down fs-5 ms-1"></i></a>
                                            <!--begin::Menu-->
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
                                                data-kt-menu="true">
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="apps/customers/view.html" class="menu-link px-3">View</a>
                                                </div>
                                                <!--end::Menu item-->
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="#" class="menu-link px-3"
                                                        data-kt-customer-table-filter="delete_row">Delete</a>
                                                </div>
                                                <!--end::Menu item-->
                                            </div>
                                            <!--end::Menu-->
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <a href="#" class="text-gray-600 text-hover-primary mb-1">5809-6407</a>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-warning">Pending</span>
                                        </td>
                                        <td>$8,700.00</td>
                                        <td>01 Sep 2020, 4:58 pm</td>
                                        <td class="pe-0 text-end">
                                            <a href="#"
                                                class="btn btn-sm btn-light image.png btn-active-light-primary"
                                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                                <i class="ki-outline ki-down fs-5 ms-1"></i></a>
                                            <!--begin::Menu-->
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
                                                data-kt-menu="true">
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="apps/customers/view.html" class="menu-link px-3">View</a>
                                                </div>
                                                <!--end::Menu item-->
                                                <!--begin::Menu item-->
                                                <div class="menu-item px-3">
                                                    <a href="#" class="menu-link px-3"
                                                        data-kt-customer-table-filter="delete_row">Delete</a>
                                                </div>
                                                <!--end::Menu item-->
                                            </div>
                                            <!--end::Menu-->
                                        </td>
                                    </tr>
                                </tbody>
                                <!--end::Table body-->
                            </table>
                            <!--end::Table-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
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
                                <h2>{{ $student->class->name }} ({{ $student->class->class_numeral }})</h2>
                            </div>
                            <!--end::Card title-->
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Button-->
                                <button type="button" class="btn btn-sm btn-light-primary">
                                    <i class="ki-outline ki-cloud-download fs-3">
                                    </i>Download Report</button>
                                <!--end::Button-->
                            </div>
                            <!--end::Card toolbar-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body py-0">
                            <!--begin::Table wrapper-->
                            <div class="table-responsive">
                                <!--begin::Table-->
                                <table class="table align-middle table-row-dashed fw-semibold text-gray-600 fs-6 gy-5"
                                    id="kt_table_customers_logs">
                                    <tbody>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-success">200 OK</div>
                                            </td>
                                            <td>POST /v1/invoices/in_5876_5396/payment</td>
                                            <td class="pe-0 text-end min-w-200px">25 Jul 2024, 11:05 am</td>
                                        </tr>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-success">200 OK</div>
                                            </td>
                                            <td>POST /v1/invoices/in_8423_6638/payment</td>
                                            <td class="pe-0 text-end min-w-200px">25 Jul 2024, 10:10 pm</td>
                                        </tr>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-warning">404 WRN</div>
                                            </td>
                                            <td>POST /v1/customer/c_673c0c955ddec/not_found</td>
                                            <td class="pe-0 text-end min-w-200px">05 May 2024, 10:10 pm</td>
                                        </tr>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-success">200 OK</div>
                                            </td>
                                            <td>POST /v1/invoices/in_4021_1047/payment</td>
                                            <td class="pe-0 text-end min-w-200px">25 Oct 2024, 8:43 pm</td>
                                        </tr>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-warning">404 WRN</div>
                                            </td>
                                            <td>POST /v1/customer/c_673c0c955ddeb/not_found</td>
                                            <td class="pe-0 text-end min-w-200px">22 Sep 2024, 11:30 am</td>
                                        </tr>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-danger">500 ERR</div>
                                            </td>
                                            <td>POST /v1/invoice/in_1016_4332/invalid</td>
                                            <td class="pe-0 text-end min-w-200px">21 Feb 2024, 5:30 pm</td>
                                        </tr>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-danger">500 ERR</div>
                                            </td>
                                            <td>POST /v1/invoice/in_8374_6938/invalid</td>
                                            <td class="pe-0 text-end min-w-200px">25 Oct 2024, 10:10 pm</td>
                                        </tr>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-success">200 OK</div>
                                            </td>
                                            <td>POST /v1/invoices/in_9042_8351/payment</td>
                                            <td class="pe-0 text-end min-w-200px">20 Dec 2024, 5:30 pm</td>
                                        </tr>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-success">200 OK</div>
                                            </td>
                                            <td>POST /v1/invoices/in_4099_3061/payment</td>
                                            <td class="pe-0 text-end min-w-200px">25 Oct 2024, 10:30 am</td>
                                        </tr>
                                        <tr>
                                            <td class="min-w-70px">
                                                <div class="badge badge-light-danger">500 ERR</div>
                                            </td>
                                            <td>POST /v1/invoice/in_8374_6938/invalid</td>
                                            <td class="pe-0 text-end min-w-200px">25 Oct 2024, 9:23 pm</td>
                                        </tr>
                                    </tbody>
                                </table>
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
                                <h2>Earnings</h2>
                            </div>
                        </div>
                        <!--end::Header-->
                        <!--begin::Body-->
                        <div class="card-body py-0">
                            <div class="fs-5 fw-semibold text-gray-500 mb-4">Last 30 day earnings calculated. Apart from
                                arranging the order of topics.</div>
                            <!--begin::Left Section-->
                            <div class="d-flex flex-wrap flex-stack mb-5">
                                <!--begin::Row-->
                                <div class="d-flex flex-wrap">
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-gray-300 w-150px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span data-kt-countup="true" data-kt-countup-value="6,840"
                                                data-kt-countup-prefix="$">0</span>
                                            <i class="ki-outline ki-arrow-up fs-1 text-success">


                                            </i>
                                        </span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Net Earnings</span>
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-gray-300 w-125px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span class="" data-kt-countup="true"
                                                data-kt-countup-value="16">0</span>%
                                            <i class="ki-outline ki-arrow-down fs-1 text-danger">


                                            </i></span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Change</span>
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-gray-300 w-150px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span data-kt-countup="true" data-kt-countup-value="1,240"
                                                data-kt-countup-prefix="$">0</span>
                                            <span class="text-primary">--</span>
                                        </span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Fees</span>
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Row-->
                                <a href="#" class="btn btn-sm btn-light-primary flex-shrink-0">Withdraw
                                    Earnings</a>
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
                            <!--begin::Toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Tab nav-->
                                <ul class="nav nav-stretch fs-5 fw-semibold nav-line-tabs nav-line-tabs-2x border-transparent"
                                    role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link text-active-primary active" data-bs-toggle="tab"
                                            role="tab" href="#kt_customer_view_statement_1">This Year</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link text-active-primary ms-3" data-bs-toggle="tab" role="tab"
                                            href="#kt_customer_view_statement_2">2020</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link text-active-primary ms-3" data-bs-toggle="tab" role="tab"
                                            href="#kt_customer_view_statement_3">2019</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a class="nav-link text-active-primary ms-3" data-bs-toggle="tab" role="tab"
                                            href="#kt_customer_view_statement_4">2018</a>
                                    </li>
                                </ul>
                                <!--end::Tab nav-->
                            </div>
                            <!--end::Toolbar-->
                        </div>
                        <!--end::Header-->
                        <!--begin::Card body-->
                        <div class="card-body pb-5">
                            <!--begin::Tab Content-->
                            <div id="kt_customer_view_statement_tab_content" class="tab-content">
                                <!--begin::Tab panel-->
                                <div id="kt_customer_view_statement_1" class="py-0 tab-pane fade show active"
                                    role="tabpanel">
                                    <!--begin::Table-->
                                    <table id="kt_customer_view_statement_table_1"
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
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">102445788</a>
                                                </td>
                                                <td>Darknight transparency 36 Icons Pack</td>
                                                <td class="text-success">$38.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 24, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">423445721</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-2.60</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 08, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Cartoon Mobile Emoji Phone Pack</td>
                                                <td class="text-success">$76.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Sep 15, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Iphone 12 Pro Mockup Mega Bundle</td>
                                                <td class="text-success">$5.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>May 30, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">523445943</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-1.30</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Apr 22, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">231445943</a>
                                                </td>
                                                <td>Parcel Shipping / Delivery Service App</td>
                                                <td class="text-success">$204.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Feb 09, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">426445943</a>
                                                </td>
                                                <td>Visual Design Illustration</td>
                                                <td class="text-success">$31.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">984445943</a>
                                                </td>
                                                <td>Abstract Vusial Pack</td>
                                                <td class="text-success">$52.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Jan 04, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">324442313</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-0.80</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">102445788</a>
                                                </td>
                                                <td>Darknight transparency 36 Icons Pack</td>
                                                <td class="text-success">$38.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 24, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">423445721</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-2.60</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 08, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Cartoon Mobile Emoji Phone Pack</td>
                                                <td class="text-success">$76.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Sep 15, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Iphone 12 Pro Mockup Mega Bundle</td>
                                                <td class="text-success">$5.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>May 30, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">523445943</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-1.30</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Apr 22, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">231445943</a>
                                                </td>
                                                <td>Parcel Shipping / Delivery Service App</td>
                                                <td class="text-success">$204.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Feb 09, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">426445943</a>
                                                </td>
                                                <td>Visual Design Illustration</td>
                                                <td class="text-success">$31.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">984445943</a>
                                                </td>
                                                <td>Abstract Vusial Pack</td>
                                                <td class="text-success">$52.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Jan 04, 2021</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">324442313</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-0.80</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <!--end::Table-->
                                </div>
                                <!--end::Tab panel-->
                                <!--begin::Tab panel-->
                                <div id="kt_customer_view_statement_2" class="py-0 tab-pane fade" role="tabpanel">
                                    <!--begin::Table-->
                                    <table id="kt_customer_view_statement_table_2"
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
                                                <td>May 30, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">523445943</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-1.30</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Apr 22, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">231445943</a>
                                                </td>
                                                <td>Parcel Shipping / Delivery Service App</td>
                                                <td class="text-success">$204.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Feb 09, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">426445943</a>
                                                </td>
                                                <td>Visual Design Illustration</td>
                                                <td class="text-success">$31.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">984445943</a>
                                                </td>
                                                <td>Abstract Vusial Pack</td>
                                                <td class="text-success">$52.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Jan 04, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">324442313</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-0.80</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">102445788</a>
                                                </td>
                                                <td>Darknight transparency 36 Icons Pack</td>
                                                <td class="text-success">$38.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 24, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">423445721</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-2.60</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 08, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Cartoon Mobile Emoji Phone Pack</td>
                                                <td class="text-success">$76.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Sep 15, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Iphone 12 Pro Mockup Mega Bundle</td>
                                                <td class="text-success">$5.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>May 30, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">523445943</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-1.30</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Apr 22, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">231445943</a>
                                                </td>
                                                <td>Parcel Shipping / Delivery Service App</td>
                                                <td class="text-success">$204.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Feb 09, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">426445943</a>
                                                </td>
                                                <td>Visual Design Illustration</td>
                                                <td class="text-success">$31.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">984445943</a>
                                                </td>
                                                <td>Abstract Vusial Pack</td>
                                                <td class="text-success">$52.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Jan 04, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">324442313</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-0.80</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">102445788</a>
                                                </td>
                                                <td>Darknight transparency 36 Icons Pack</td>
                                                <td class="text-success">$38.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 24, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">423445721</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-2.60</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 08, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Cartoon Mobile Emoji Phone Pack</td>
                                                <td class="text-success">$76.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Sep 15, 2020</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Iphone 12 Pro Mockup Mega Bundle</td>
                                                <td class="text-success">$5.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <!--end::Table-->
                                </div>
                                <!--end::Tab panel-->
                                <!--begin::Tab panel-->
                                <div id="kt_customer_view_statement_3" class="py-0 tab-pane fade" role="tabpanel">
                                    <!--begin::Table-->
                                    <table id="kt_customer_view_statement_table_3"
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
                                                <td>Feb 09, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">426445943</a>
                                                </td>
                                                <td>Visual Design Illustration</td>
                                                <td class="text-success">$31.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">984445943</a>
                                                </td>
                                                <td>Abstract Vusial Pack</td>
                                                <td class="text-success">$52.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Jan 04, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">324442313</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-0.80</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Sep 15, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Iphone 12 Pro Mockup Mega Bundle</td>
                                                <td class="text-success">$5.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">102445788</a>
                                                </td>
                                                <td>Darknight transparency 36 Icons Pack</td>
                                                <td class="text-success">$38.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 24, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">423445721</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-2.60</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 08, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Cartoon Mobile Emoji Phone Pack</td>
                                                <td class="text-success">$76.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>May 30, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">523445943</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-1.30</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Apr 22, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">231445943</a>
                                                </td>
                                                <td>Parcel Shipping / Delivery Service App</td>
                                                <td class="text-success">$204.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Feb 09, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">426445943</a>
                                                </td>
                                                <td>Visual Design Illustration</td>
                                                <td class="text-success">$31.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">984445943</a>
                                                </td>
                                                <td>Abstract Vusial Pack</td>
                                                <td class="text-success">$52.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Jan 04, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">324442313</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-0.80</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Sep 15, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Iphone 12 Pro Mockup Mega Bundle</td>
                                                <td class="text-success">$5.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">102445788</a>
                                                </td>
                                                <td>Darknight transparency 36 Icons Pack</td>
                                                <td class="text-success">$38.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 24, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">423445721</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-2.60</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 08, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Cartoon Mobile Emoji Phone Pack</td>
                                                <td class="text-success">$76.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>May 30, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">523445943</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-1.30</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Apr 22, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">231445943</a>
                                                </td>
                                                <td>Parcel Shipping / Delivery Service App</td>
                                                <td class="text-success">$204.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <!--end::Table-->
                                </div>
                                <!--end::Tab panel-->
                                <!--begin::Tab panel-->
                                <div id="kt_customer_view_statement_4" class="py-0 tab-pane fade" role="tabpanel">
                                    <!--begin::Table-->
                                    <table id="kt_customer_view_statement_table_4"
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
                                                <td>Nov 01, 2018</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">102445788</a>
                                                </td>
                                                <td>Darknight transparency 36 Icons Pack</td>
                                                <td class="text-success">$38.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 24, 2018</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">423445721</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-2.60</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2018</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">102445788</a>
                                                </td>
                                                <td>Darknight transparency 36 Icons Pack</td>
                                                <td class="text-success">$38.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 24, 2018</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">423445721</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-2.60</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Feb 09, 2018</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">426445943</a>
                                                </td>
                                                <td>Visual Design Illustration</td>
                                                <td class="text-success">$31.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2018</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">984445943</a>
                                                </td>
                                                <td>Abstract Vusial Pack</td>
                                                <td class="text-success">$52.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Jan 04, 2018</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">324442313</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-0.80</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 08, 2018</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Cartoon Mobile Emoji Phone Pack</td>
                                                <td class="text-success">$76.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 08, 2018</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Cartoon Mobile Emoji Phone Pack</td>
                                                <td class="text-success">$76.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Feb 09, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">426445943</a>
                                                </td>
                                                <td>Visual Design Illustration</td>
                                                <td class="text-success">$31.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">984445943</a>
                                                </td>
                                                <td>Abstract Vusial Pack</td>
                                                <td class="text-success">$52.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Jan 04, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">324442313</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-0.80</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Sep 15, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Iphone 12 Pro Mockup Mega Bundle</td>
                                                <td class="text-success">$5.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Nov 01, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">102445788</a>
                                                </td>
                                                <td>Darknight transparency 36 Icons Pack</td>
                                                <td class="text-success">$38.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 24, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">423445721</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-2.60</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Oct 08, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">312445984</a>
                                                </td>
                                                <td>Cartoon Mobile Emoji Phone Pack</td>
                                                <td class="text-success">$76.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>May 30, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">523445943</a>
                                                </td>
                                                <td>Seller Fee</td>
                                                <td class="text-danger">$-1.30</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Apr 22, 2019</td>
                                                <td>
                                                    <a href="#"
                                                        class="text-gray-600 text-hover-primary">231445943</a>
                                                </td>
                                                <td>Parcel Shipping / Delivery Service App</td>
                                                <td class="text-success">$204.00</td>
                                                <td class="text-end">
                                                    <button
                                                        class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <!--end::Table-->
                                </div>
                                <!--end::Tab panel-->
                            </div>
                            <!--end::Tab Content-->
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
                                <h2>Earnings</h2>
                            </div>
                        </div>
                        <!--end::Header-->
                        <!--begin::Body-->
                        <div class="card-body py-0">
                            <div class="fs-5 fw-semibold text-gray-500 mb-4">Last 30 day earnings calculated. Apart from
                                arranging the order of topics.</div>
                            <!--begin::Left Section-->
                            <div class="d-flex flex-wrap flex-stack mb-5">
                                <!--begin::Row-->
                                <div class="d-flex flex-wrap">
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-gray-300 w-150px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span data-kt-countup="true" data-kt-countup-value="6,840"
                                                data-kt-countup-prefix="$">0</span>
                                            <i class="ki-outline ki-arrow-up fs-1 text-success">


                                            </i>
                                        </span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Net Earnings</span>
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-gray-300 w-125px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span class="" data-kt-countup="true"
                                                data-kt-countup-value="16">0</span>%
                                            <i class="ki-outline ki-arrow-down fs-1 text-danger">


                                            </i></span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Change</span>
                                    </div>
                                    <!--end::Col-->
                                    <!--begin::Col-->
                                    <div class="border border-dashed border-gray-300 w-150px rounded my-3 p-4 me-6">
                                        <span class="fs-1 fw-bold text-gray-800 lh-1">
                                            <span data-kt-countup="true" data-kt-countup-value="1,240"
                                                data-kt-countup-prefix="$">0</span>
                                            <span class="text-primary">--</span>
                                        </span>
                                        <span class="fs-6 fw-semibold text-muted d-block lh-1 pt-2">Fees</span>
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Row-->
                                <a href="#" class="btn btn-sm btn-light-primary flex-shrink-0">Withdraw
                                    Earnings</a>
                            </div>
                            <!--end::Left Section-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Earnings-->
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
                                <table class="table align-middle table-row-dashed gy-5"
                                    id="kt_table_users_login_session">
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
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        document.getElementById("student_info_menu").classList.add("here", "show");
        document.getElementById("all_students_link").classList.add("active");
    </script>
@endpush
