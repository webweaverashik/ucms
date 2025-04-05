@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'Pending Approval')


@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Waiting for approval
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
                Pending Students </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text"
                        data-kt-subscription-table-filter="search" class="form-control form-control-solid w-250px ps-12"
                        placeholder="Search Students">
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
                                <label class="form-label fs-6 fw-semibold">Payment Type:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-kt-subscription-table-filter="billing" data-hide-search="true">
                                    <option></option>
                                    <option value="due">Due</option>
                                    <option value="current">Current</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Due Date:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-kt-subscription-table-filter="product" data-hide-search="true">
                                    <option></option>
                                    <option value="1/7">1-7</option>
                                    <option value="1/10">1-10</option>
                                    <option value="1/15">1-15</option>
                                    <option value="1/30">1-30</option>
                                </select>
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Class</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-kt-subscription-table-filter="status">
                                    <option></option>
                                    @foreach ($classnames as $classname)
                                        <option value="{{ $classname->class_numeral }}_ucms">{{ $classname->name }}
                                        </option>
                                    @endforeach
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
                    {{-- <!--begin::Export-->
                            <button type="button" class="btn btn-light-primary me-3" data-bs-toggle="modal"
                                data-bs-target="#kt_subscriptions_export_modal">
                                <i class="ki-outline ki-exit-up fs-2">
                                </i>Export</button>
                            <!--end::Export--> --}}
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
                <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_students_table">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            {{-- <th class="w-10px pe-2">
                                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                            <input class="form-check-input" type="checkbox" data-kt-check="true"
                                                data-kt-check-target="#kt_students_table .form-check-input"
                                                value="1" />
                                        </div>
                                    </th> --}}
                            <th class="w-10px pe-2">SL</th>
                            <th class="min-w-250px">Student</th>
                            <th class="d-none">Class (filter)</th>
                            <th class="text-center">Class</th>
                            <th class="text-center">Shift</th>
                            <th class="text-center">School</th>
                            <th class="">Guardians</th>
                            <th class="text-center">Mobile<br>(Home)</th>
                            <th class="text-center">Fee (৳)</th>
                            <th class="text-center">Payment Type</th>
                            <th class="text-center">Admission Date</th>
                            <th class="text-center">Remarks</th>
                            <th class="text-end min-w-70px">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                        @foreach ($students as $student)
                            <tr>
                                <td class="pe-2">{{ $loop->index + 1 }}</td>
                                <td class="d-flex align-items-center">
                                    <!--begin:: Avatar -->
                                    <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                        <a href="{{ route('students.show', $student->id) }}">
                                            <div class="symbol-label">
                                                <img src="{{ $student->photo_url ? asset($student->photo_url) : asset('assets/img/dummy.png') }}"
                                                    alt="{{ $student->name }}" class="w-100" />
                                            </div>
                                        </a>
                                    </div>
                                    <!--end::Avatar-->
                                    <!--begin::user details-->
                                    <div class="d-flex flex-column">
                                        <a href="{{ route('students.show', $student->id) }}"
                                            class="text-gray-800 text-hover-primary mb-1">{{ $student->name }}
                                            @if ($student->student_activation_id == null)
                                                <span
                                                    class="bullet bullet-dot bg-success h-6px w-6px position-absolute animation-blink"
                                                    title="Pending Student" data-bs-toggle="tooltip"
                                                    data-bs-placement="top">
                                                </span>
                                            @endif
                                        </a>
                                        <span class="fw-bold fs-base">{{ $student->student_unique_id }}</span>
                                    </div>
                                    <!--begin::user details-->
                                </td>
                                <td class="d-none">{{ $student->class->class_numeral }}_ucms</td>
                                <td class="text-center">{{ $student->class->name }}</td>
                                <td class="text-center">{{ $student->shift->name }}</td>
                                <td class="text-center">{{ $student->institution->name }}
                                    (EIIN: {{ $student->institution->eiin_number }})</td>
                                <td>
                                    @foreach ($student->guardians as $guardian)
                                        <a href="{{ route('guardians.show', $guardian->id) }}"><span
                                                class="badge badge-light-primary text-hover-success">{{ $guardian->name }},
                                                {{ ucfirst($guardian->relationship) }}</span></a><br>
                                    @endforeach
                                </td>
                                <td class="text-center">
                                    {!! $student->mobileNumbers->where('number_type', 'home')->pluck('mobile_number')->implode('<br>') ?: '-' !!}
                                </td>
                                <td class="text-center">
                                    @if ($student->payments)
                                        {{ intval($student->payments->tuition_fee) }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($student->payments)
                                        {{ ucfirst($student->payments->payment_style) }}-1/{{ $student->payments->due_date }}
                                    @endif
                                </td>
                                <td class="text-center">{{ $student->created_at->format('d-M-Y') }}</td>
                                <td class="text-center">{{ $student->remarks }}</td>
                                <td class="text-end">
                                    <a href="#" class="btn btn-light btn-active-light-primary btn-sm"
                                        data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                        <i class="ki-outline ki-down fs-5 m-0"></i></a>
                                    <!--begin::Menu-->
                                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4"
                                        data-kt-menu="true">
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3 activate-student"
                                                data-student-id="{{ $student->id }}">Approve</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="{{ route('students.edit', $student->id) }}"
                                                class="menu-link px-3">Edit</a>
                                        </div>
                                        <!--end::Menu item-->
                                        <!--begin::Menu item-->
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3 delete-student"
                                                data-student-id="{{ $student->id }}">Delete</a>
                                        </div>
                                        <!--end::Menu item-->
                                    </div>
                                    <!--end::Menu-->
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
@endsection



@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush


@push('page-js')
    <script src="{{ asset('assets/js/custom/apps/customers/view/add-payment.js') }}"></script>
    <script src="{{ asset('assets/js/custom/apps/customers/view/adjust-balance.js') }}"></script>
    <script src="{{ asset('assets/js/custom/apps/customers/view/invoices.js') }}"></script>
    <script src="{{ asset('assets/js/custom/apps/customers/view/payment-method.js') }}"></script>
    <script src="{{ asset('assets/js/custom/apps/customers/view/payment-table.js') }}"></script>
    <script src="{{ asset('assets/js/custom/apps/customers/view/statement.js') }}"></script>

    <script>
        const routeDeleteStudent = "{{ route('students.destroy', ':id') }}";
    </script>

    <script src="{{ asset('js/students/students.pending.js') }}"></script>

    <script>
        document.getElementById("admission_menu").classList.add("here", "show");
        document.getElementById("pending_approval_link").classList.add("active");
    </script>
@endpush
