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
                        placeholder="Search Students">
                </div>
                <!--end::Search-->

                <!--begin::Export hidden buttons-->
                <div id="kt_hidden_export_buttons" class="d-none"></div>
                <!--end::Export buttons-->
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
                    <div class="menu menu-sub menu-sub-dropdown w-350px w-md-500px" data-kt-menu="true">
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
                            <div class="row">
                                @if (auth()->user()->hasRole('admin'))
                                    <div class="col-6 mb-5">
                                        <label class="form-label fs-6 fw-semibold">Branch:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true"
                                            data-kt-subscription-table-filter="product" data-hide-search="true">
                                            <option></option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}_{{ $branch->branch_name }}">
                                                    {{ ucfirst($branch->branch_name) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="col-6 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Student Gender:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true"
                                        data-kt-subscription-table-filter="status" data-hide-search="true">
                                        <option></option>
                                        <option value="student_male">Male</option>
                                        <option value="student_female">Female</option>
                                    </select>
                                </div>

                                <div class="col-6 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Payment Type:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true"
                                        data-kt-subscription-table-filter="billing" data-hide-search="true">
                                        <option></option>
                                        <option value="due">Due</option>
                                        <option value="current">Current</option>
                                    </select>
                                </div>

                                <!--begin::Input group-->
                                <div class="col-6 mb-5">
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
                                <div class="col-6 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Shifts:</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true"
                                        data-kt-subscription-table-filter="product" data-hide-search="true">
                                        <option></option>
                                        @foreach ($shifts as $shift)
                                            <option
                                                value="{{ $shift->id }}_{{ $shift->name }}_{{ $shift->branch->branch_name }}">
                                                {{ $shift->name }} @if (auth()->user()->hasRole('admin'))
                                                    ({{ $shift->branch->branch_name }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="@if (auth()->user()->hasRole('admin')) col-6 @else col-12 @endif mb-5">
                                    <label class="form-label fs-6 fw-semibold">Class</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true"
                                        data-kt-subscription-table-filter="status">
                                        <option></option>
                                        @foreach ($classnames as $classname)
                                            <option value="{{ $classname->id }}_{{ $classname->class_numeral }}_ucms">
                                                {{ $classname->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <!--end::Input group-->

                                <!--begin::Input group-->
                                <div class="col-12 mb-5">
                                    <label class="form-label fs-6 fw-semibold">Institutions</label>
                                    <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                        data-placeholder="Select option" data-allow-clear="true"
                                        data-kt-subscription-table-filter="status">
                                        <option></option>
                                        @foreach ($institutions as $institution)
                                            <option
                                                value="{{ $institution->name }} (EIIN: {{ $institution->eiin_number }})">
                                                {{ $institution->name }} ({{ $institution->eiin_number }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <!--end::Input group-->

                            </div>


                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="reset"
                                    class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-kt-menu-dismiss="true" data-kt-subscription-table-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6"
                                    data-kt-menu-dismiss="true" data-kt-subscription-table-filter="filter">Apply</button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Menu 1-->

                    <!--begin::Export dropdown-->
                    <div class="dropdown">
                        <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                            data-kt-menu-placement="bottom-end">
                            <i class="ki-outline ki-exit-up fs-2"></i>Export
                        </button>

                        <!--begin::Menu-->
                        <div id="kt_table_report_dropdown_menu"
                            class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                            data-kt-menu="true">
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="copy">Copy to
                                    clipboard</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="excel">Export as
                                    Excel</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="csv">Export as CSV</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="pdf">Export as PDF</a>
                            </div>
                            <!--end::Menu item-->
                        </div>
                        <!--end::Menu-->
                    </div>
                    <!--end::Export dropdown-->

                    @can('students.create')
                        <!--begin::Add Student-->
                        <a href="{{ route('students.create') }}" class="btn btn-primary">
                            <i class="ki-outline ki-plus fs-2"></i>New Admission</a>
                        <!--end::Add Student-->
                    @endcan
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table" id="kt_students_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">SL</th>
                        <th class="min-w-200px">Student</th>
                        <th class="d-none">Gender (filter)</th>
                        <th class="d-none">Class (filter)</th>
                        <th>Class</th>
                        <th class="d-none">Shift (Filter)</th>
                        <th>Shift</th>
                        <th class="w-300px">Institution</th>
                        <th>Guardians</th>
                        <th>Mobile<br>(Home)</th>
                        <th>Fee (Tk)</th>
                        <th>Payment<br>Type</th>
                        <th>Admission<br>Date</th>
                        <th class="d-none">Branch (filter)</th>
                        <th class="@if (!auth()->user()->hasRole('admin')) d-none @endif">Branch</th>
                        <th class="min-w-70px not-export">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @foreach ($students as $student)
                        <tr>
                            <td class="pe-2">{{ $loop->index + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <!--begin:: Avatar -->
                                    <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                        <a href="{{ route('students.show', $student->id) }}">
                                            <div class="symbol-label">
                                                <img src="{{ $student->photo_url ? asset($student->photo_url) : asset($student->gender == 'male' ? 'img/male.png' : 'img/female.png') }}"
                                                    alt="{{ $student->name }}" class="w-100" />

                                            </div>
                                        </a>
                                    </div>
                                    <!--end::Avatar-->

                                    <!--begin::user details-->
                                    <div class="d-flex flex-column text-start">
                                        <a href="{{ route('students.show', $student->id) }}"
                                            class="text-gray-800 text-hover-primary mb-1">{{ $student->name }}
                                        </a>
                                        <span class="fw-bold fs-base">{{ $student->student_unique_id }}</span>
                                    </div>
                                    <!--begin::user details-->
                                </div>
                            </td>
                            <td class="d-none">student_{{ $student->gender }}</td>
                            <td class="d-none">{{ $student->class_id }}_{{ $student->class->class_numeral }}_ucms</td>
                            <td>{{ $student->class->name }}</td>
                            <td class="d-none">
                                {{ $student->shift_id }}_{{ $student->shift->name }}_{{ $student->branch->branch_name }}
                            </td>
                            <td>{{ $student->shift->name }}</td>
                            <td>{{ $student->institution->name }}
                                (EIIN: {{ $student->institution->eiin_number }})</td>
                            <td>
                                @foreach ($student->guardians as $guardian)
                                    <a href="#"><span
                                            class="badge badge-light-primary text-hover-success fs-7">{{ $guardian->name }},
                                            {{ ucfirst($guardian->relationship) }}</span></a><br>
                                @endforeach
                            </td>
                            <td>
                                {!! $student->mobileNumbers->where('number_type', 'home')->pluck('mobile_number')->implode('<br>') ?: '-' !!}
                            </td>
                            <td>
                                @if ($student->payments)
                                    {{ $student->payments->tuition_fee }}
                                @endif
                            </td>
                            <td>
                                @if ($student->payments)
                                    {{ ucfirst($student->payments->payment_style) }}-1/{{ $student->payments->due_date }}
                                @endif
                            </td>
                            <td>{{ $student->created_at->format('d-M-Y') }}</td>
                            <td class="d-none">{{ $student->branch_id }}_{{ $student->branch->branch_name }}</td>
                            <td class="@if (!auth()->user()->hasRole('admin')) d-none @endif">
                                @if ($student->branch)
                                    @php
                                        $branchName = $student->branch->branch_name;
                                        $badgeColor = $branchColors[$branchName] ?? 'badge-light-secondary';
                                    @endphp
                                    <span class="badge {{ $badgeColor }}">{{ $branchName }}</span>
                                @else
                                    <span class="badge badge-light-secondary">N/A</span>
                                @endif
                            </td>
                            <td>
                                @canany(['students.approve', 'students.delete', 'students.edit'])
                                    <a href="#" class="btn btn-light btn-active-light-primary btn-sm"
                                        data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions
                                        <i class="ki-outline ki-down fs-5 m-0"></i></a>
                                    <!--begin::Menu-->
                                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-150px py-4"
                                        data-kt-menu="true">

                                        @hasanyrole('admin|manager')
                                            <!--begin::Menu item-->
                                            <div class="menu-item px-3">
                                                <a href="#" class="menu-link px-3 text-hover-success approve-student"
                                                    data-student-id="{{ $student->id }}"><i
                                                        class="bi bi-person-check fs-3 me-2"></i> Approve</a>
                                            </div>
                                            <!--end::Menu item-->
                                        @endhasanyrole

                                        @can('students.edit')
                                            <!--begin::Menu item-->
                                            <div class="menu-item px-3">
                                                <a href="{{ route('students.edit', $student->id) }}"
                                                    class="menu-link text-hover-primary px-3"><i class="las la-pen fs-3 me-2"></i>
                                                    Edit</a>
                                            </div>
                                            <!--end::Menu item-->
                                        @endcan

                                        @can('students.delete')
                                            <!--begin::Menu item-->
                                            <div class="menu-item px-3">
                                                <a href="#" class="menu-link text-hover-danger px-3 delete-student"
                                                    data-student-id="{{ $student->id }}"><i class="bi bi-trash fs-3 me-2"></i>
                                                    Delete</a>
                                            </div>
                                            <!--end::Menu item-->
                                        @endcan
                                    </div>
                                    <!--end::Menu-->
                                @endcanany
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

    <script src="{{ asset('js/students/pending.js') }}"></script>

    <script>
        document.getElementById("admission_menu").classList.add("here", "show");
        document.getElementById("pending_approval_link").classList.add("active");
    </script>
@endpush
