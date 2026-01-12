@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/secondary-classes/show.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', $secondaryClass->name . ' - Special Class')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            {{ $secondaryClass->name }}
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">Academic</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('classnames.index') }}" class="text-muted text-hover-primary">Classes</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('classnames.show', $classname->id) }}"
                    class="text-muted text-hover-primary">{{ $classname->name }}</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Special Class</li>
        </ul>
    </div>
@endsection

@section('content')
    @php
        $badgeColors = [
            'badge-light-primary',
            'badge-light-success',
            'badge-light-warning',
            'badge-light-danger',
            'badge-light-info',
        ];
        $branchColors = [];
        if (isset($branches)) {
            foreach ($branches as $index => $branch) {
                $branchColors[$branch->id] = $badgeColors[$index % count($badgeColors)];
            }
        }
    @endphp

    <!--begin::Layout-->
    <div class="d-flex flex-column flex-xl-row">
        <!--begin::Sidebar-->
        <div class="flex-column flex-lg-row-auto w-100 w-xl-350px mb-10">
            <!--begin::Card-->
            <div class="card card-flush mb-0 @if (!$secondaryClass->is_active) border border-dashed border-danger @endif"
                data-kt-sticky="true" data-kt-sticky-name="secondary-class-summary"
                data-kt-sticky-offset="{default: false, lg: 0}" data-kt-sticky-width="{lg: '250px', xl: '350px'}"
                data-kt-sticky-left="auto" data-kt-sticky-top="100px" data-kt-sticky-animation="false"
                data-kt-sticky-zindex="95">
                <!--begin::Card header-->
                <div class="card-header">
                    <div class="card-title">
                        <h3 class="text-gray-600">Special Class Info</h3>
                    </div>
                    @if (($isAdmin || $isManager) && $secondaryClass->is_active)
                        <div class="card-toolbar">
                            <a href="#" class="btn btn-sm btn-light btn-icon" data-kt-menu-trigger="click"
                                data-kt-menu-placement="bottom-end">
                                <i class="ki-outline ki-dots-horizontal fs-3"></i>
                            </a>
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-6 w-175px py-4"
                                data-kt-menu="true">
                                <div class="menu-item px-3">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_secondary_class"
                                        class="menu-link text-hover-primary px-3">
                                        <i class="las la-pen fs-3 me-2"></i>Edit Class
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body pt-0 fs-6">
                    <!--begin::Section-->
                    <div class="mb-7">
                        <div class="d-flex flex-column">
                            <div class="d-flex flex-column mb-3">
                                <span class="fs-1 fw-bold text-gray-900 me-2">{{ $secondaryClass->name }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span
                                    class="badge @if ($secondaryClass->is_active) badge-light-success @else badge-light-danger @endif">
                                    {{ $secondaryClass->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="badge badge-light-info">
                                    {{ ucwords(str_replace('_', ' ', $secondaryClass->payment_type)) }}
                                </span>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fs-6 text-gray-600 me-2">
                                    Parent Class: <strong>{{ $classname->name }}</strong>
                                </span>
                            </div>
                        </div>
                    </div>
                    <!--end::Section-->

                    <div class="separator separator-dashed mb-7"></div>

                    <!--begin::Section - Fee Info-->
                    <div class="mb-7">
                        <h5 class="mb-4">Fee Information</h5>
                        <div class="fee-info-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-gray-600">Default Fee Amount</span>
                                <span
                                    class="fs-2 fw-bold text-primary">৳{{ number_format($secondaryClass->fee_amount, 0) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-gray-600">Payment Type</span>
                                <span
                                    class="badge badge-light-info">{{ ucwords(str_replace('_', ' ', $secondaryClass->payment_type)) }}</span>
                            </div>
                        </div>
                    </div>
                    <!--end::Section-->

                    <div class="separator separator-dashed mb-7"></div>

                    <!--begin::Section - Statistics-->
                    <div class="mb-7">
                        <h5 class="mb-4">Statistics</h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-primary">{{ $stats['total_students'] }}</div>
                                    <div class="stats-label">Total Students</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-success">{{ $stats['active_students'] }}</div>
                                    <div class="stats-label">Active Students</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-danger">{{ $stats['inactive_students'] }}</div>
                                    <div class="stats-label">Inactive Students</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-info">৳{{ number_format($stats['total_revenue'], 0) }}
                                    </div>
                                    <div class="stats-label">Total Revenue</div>
                                </div>
                            </div>
                            @if ($secondaryClass->payment_type === 'monthly')
                                <div class="col-12">
                                    <div class="stats-mini-card bg-light-success">
                                        <div class="stats-value text-success">
                                            ৳{{ number_format($stats['expected_monthly_revenue'], 0) }}</div>
                                        <div class="stats-label">Expected Monthly Revenue</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <!--end::Section-->

                    @if ($isAdmin && count($stats['branch_stats']) > 0)
                        <div class="separator separator-dashed mb-7"></div>

                        <!--begin::Section - Branch Stats-->
                        <div class="mb-0">
                            <h5 class="mb-4">Branch-wise Distribution</h5>
                            <div class="branch-stats-list">
                                @foreach ($stats['branch_stats'] as $branchId => $branchStat)
                                    <div class="branch-stat-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-semibold text-gray-700">{{ $branchStat['name'] }}</span>
                                            <span class="badge {{ $branchColors[$branchId] ?? 'badge-light-primary' }}">
                                                {{ $branchStat['total'] }} students
                                            </span>
                                        </div>
                                        <div class="d-flex gap-3 mt-2 text-muted fs-7">
                                            <span><i
                                                    class="ki-outline ki-check-circle text-success fs-7 me-1"></i>{{ $branchStat['active'] }}
                                                active</span>
                                            <span><i
                                                    class="ki-outline ki-cross-circle text-danger fs-7 me-1"></i>{{ $branchStat['inactive'] }}
                                                inactive</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <!--end::Section-->
                    @endif

                    <div class="separator separator-dashed mb-7"></div>

                    <!--begin::Section - Timestamps-->
                    <div class="mb-0">
                        <h5 class="mb-4">Activity</h5>
                        <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                            <tr>
                                <td class="text-gray-500">Created:</td>
                                <td class="text-gray-800">
                                    {{ $secondaryClass->created_at->diffForHumans() }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $secondaryClass->created_at->format('d-M-Y h:i:s A') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-gray-500">Updated:</td>
                                <td class="text-gray-800">
                                    {{ $secondaryClass->updated_at->diffForHumans() }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $secondaryClass->updated_at->format('d-M-Y h:i:s A') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>
                                </td>
                            </tr>
                        </table>
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
            <!--begin::Card-->
            <div class="card mb-6 mb-xl-9">
                <!--begin::Header-->
                <div class="card-header">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                            <input type="text" data-enrolled-students-table-filter="search"
                                class="form-control form-control-solid w-350px ps-12" placeholder="Search students...">
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <div class="d-flex justify-content-end gap-3">
                            <!--begin::Filter-->
                            <button type="button" class="btn btn-light-primary" data-kt-menu-trigger="click"
                                data-kt-menu-placement="bottom-end">
                                <i class="ki-outline ki-filter fs-2"></i>Filter
                            </button>
                            <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                                <div class="px-7 py-5">
                                    <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                                </div>
                                <div class="separator border-gray-200"></div>
                                <div class="px-7 py-5" data-enrolled-students-table-filter="form">
                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">Status:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="Select status" data-allow-clear="true"
                                            data-hide-search="true" id="filter_status">
                                            <option></option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                    @if ($isAdmin)
                                        <div class="mb-10">
                                            <label class="form-label fs-6 fw-semibold">Branch:</label>
                                            <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                                data-placeholder="Select branch" data-allow-clear="true"
                                                data-hide-search="true" id="filter_branch">
                                                <option></option>
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}">{{ $branch->branch_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                    <div class="d-flex justify-content-end">
                                        <button type="reset"
                                            class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                            data-kt-menu-dismiss="true"
                                            data-enrolled-students-table-filter="reset">Reset</button>
                                        <button type="submit" class="btn btn-primary fw-semibold px-6"
                                            data-kt-menu-dismiss="true"
                                            data-enrolled-students-table-filter="filter">Apply</button>
                                    </div>
                                </div>
                            </div>
                            <!--end::Filter-->

                            @if (($isAdmin || $isManager) && $secondaryClass->is_active)
                                <!--begin::Add Student-->
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#kt_modal_enroll_student">
                                    <i class="ki-outline ki-plus fs-2"></i>Enroll Student
                                </button>
                                <!--end::Add Student-->
                            @endif
                        </div>
                    </div>
                </div>
                <!--end::Header-->

                <!--begin::Card body-->
                <div class="card-body pb-5">
                    @if ($isAdmin && $branches->count() > 0)
                        <!--begin::Branch Tabs for Admin-->
                        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="branchTabs" role="tablist">
                            @foreach ($branches as $branch)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                        id="branch-{{ $branch->id }}-tab" data-bs-toggle="tab"
                                        data-bs-target="#branch_{{ $branch->id }}_content" type="button"
                                        role="tab" data-branch-filter="{{ $branch->id }}">
                                        <i class="ki-outline ki-home fs-4 me-2"></i>{{ $branch->branch_name }}
                                        <span
                                            class="badge {{ $branchColors[$branch->id] ?? 'badge-light-primary' }} ms-2">
                                            {{ $studentsByBranch->get($branch->id, collect())->count() }}
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <!--end::Branch Tabs-->
                    @endif

                    <!--begin::Table-->
                    <table class="table table-hover align-middle table-row-dashed fs-6 fw-semibold gy-4"
                        id="kt_enrolled_students_table">
                        <thead>
                            <tr class="fw-bold fs-7 text-uppercase gs-0">
                                <th class="w-30px">#</th>
                                <th>Student</th>
                                <th>Group</th>
                                <th>Batch</th>
                                <th class="w-150px">Branch</th>
                                <th class="w-120px">Amount</th>
                                <th class="w-120px">Enrolled At</th>
                                <th class="w-100px">Status</th>
                                @if ($isAdmin || $isManager)
                                    <th class="w-100px text-end">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            @forelse ($enrolledStudents as $enrollment)
                                @php $student = $enrollment->student; @endphp
                                @if ($student)
                                    <tr data-branch-id="{{ $student->branch_id }}" data-student-id="{{ $student->id }}"
                                        data-enrollment-id="{{ $enrollment->id }}"
                                        data-status="{{ $student->studentActivation?->active_status ?? 'inactive' }}">
                                        <td class="pe-2">{{ $loop->index + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="d-flex flex-column text-start">
                                                    <a href="{{ route('students.show', $student->id) }}"
                                                        class="@if ($student->studentActivation?->active_status !== 'active') text-danger @else text-gray-800 text-hover-primary @endif mb-1"
                                                        @if ($student->studentActivation?->active_status !== 'active') title="Inactive Student" data-bs-toggle="tooltip" @endif>
                                                        {{ $student->name }}
                                                    </a>
                                                    <span class="fw-bold fs-base">{{ $student->student_unique_id }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($student->academic_group == 'Science')
                                                <span
                                                    class="badge badge-pill badge-info">{{ $student->academic_group }}</span>
                                            @elseif ($student->academic_group == 'Commerce')
                                                <span
                                                    class="badge badge-pill badge-success">{{ $student->academic_group }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $student->batch->name ?? '-' }}</td>
                                        <td>{{ $student->branch->branch_name ?? '-' }}</td>
                                        <td>
                                            <span
                                                class="amount-display fw-bold text-primary">৳{{ number_format($enrollment->amount, 0) }}</span>
                                        </td>
                                        <td>{{ $enrollment->enrolled_at ? $enrollment->enrolled_at->format('d-M-Y') : '-' }}
                                        </td>
                                        <td>
                                            @if ($student->studentActivation?->active_status === 'active')
                                                <span class="badge badge-light-success">Active</span>
                                            @else
                                                <span class="badge badge-light-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if (($isAdmin || $isManager) && $secondaryClass->is_active === true)
                                                <div class="btn-group">
                                                    @if ($secondaryClass->payment_type === 'monthly')
                                                        <button type="button"
                                                            class="btn btn-sm btn-icon btn-light-primary edit-enrollment"
                                                            data-student-id="{{ $student->id }}"
                                                            data-student-name="{{ $student->name }}"
                                                            data-amount="{{ $enrollment->amount }}"
                                                            data-bs-toggle="tooltip" title="Edit Amount">
                                                            <i class="ki-outline ki-pencil fs-5"></i>
                                                        </button>
                                                    @endif
                                                    <button type="button"
                                                        class="btn btn-sm btn-icon btn-light-danger withdraw-student"
                                                        data-student-id="{{ $student->id }}"
                                                        data-student-name="{{ $student->name }}" data-bs-toggle="tooltip"
                                                        title="Withdraw Student">
                                                        <i class="ki-outline ki-cross-circle fs-5"></i>
                                                    </button>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Layout-->

    @if ($isAdmin || $isManager)
        <!--begin::Modal - Enroll Student-->
        <div class="modal fade" id="kt_modal_enroll_student" tabindex="-1" aria-hidden="true"
            data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered mw-650px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="fw-bold">Enroll Student</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </div>
                    </div>
                    <div class="modal-body px-5 my-5">
                        <form id="kt_modal_enroll_student_form" class="form" novalidate="novalidate">
                            <div class="d-flex flex-column scroll-y px-5 px-lg-10">
                                <!--begin::Student Search-->
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Search Student</label>
                                    <select name="student_id" id="enroll_student_select"
                                        class="form-select form-select-solid"
                                        data-placeholder="Type to search students..."
                                        data-dropdown-parent="#kt_modal_enroll_student">
                                        <option></option>
                                    </select>
                                    <div class="text-muted fs-7 mt-2">
                                        Only students from class <strong>{{ $classname->name }}</strong> who are not
                                        already enrolled will appear.
                                    </div>
                                </div>
                                <!--end::Student Search-->

                                <!--begin::Amount-->
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Fee Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">৳</span>
                                        <input type="number" name="amount" id="enroll_amount"
                                            class="form-control form-control-solid"
                                            value="{{ $secondaryClass->fee_amount }}" min="0" required />
                                    </div>
                                    <div class="text-muted fs-7 mt-2">
                                        Default fee: ৳{{ number_format($secondaryClass->fee_amount, 0) }}
                                        ({{ ucwords(str_replace('_', ' ', $secondaryClass->payment_type)) }})
                                    </div>
                                </div>
                                <!--end::Amount-->
                            </div>
                            <div class="text-center pt-10">
                                <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="kt_modal_enroll_student_submit">
                                    <span class="indicator-label">Enroll Student</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Modal - Enroll Student-->

        <!--begin::Modal - Edit Enrollment-->
        <div class="modal fade" id="kt_modal_edit_enrollment" tabindex="-1" aria-hidden="true"
            data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered mw-500px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="fw-bold">Edit Enrollment</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </div>
                    </div>
                    <div class="modal-body px-5 my-5">
                        <form id="kt_modal_edit_enrollment_form" class="form" novalidate="novalidate">
                            <input type="hidden" name="student_id" id="edit_student_id" />
                            <div class="d-flex flex-column scroll-y px-5 px-lg-10">
                                <!--begin::Student Info-->
                                <div class="mb-7">
                                    <label class="fw-semibold fs-6 mb-2">Student</label>
                                    <div class="form-control form-control-solid bg-light-secondary"
                                        id="edit_student_name_display"></div>
                                </div>
                                <!--end::Student Info-->

                                <!--begin::Amount-->
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Fee Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">৳</span>
                                        <input type="number" name="amount" id="edit_amount"
                                            class="form-control form-control-solid" min="0" required />
                                    </div>
                                </div>
                                <!--end::Amount-->
                            </div>
                            <div class="text-center pt-10">
                                <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="kt_modal_edit_enrollment_submit">
                                    <span class="indicator-label">Update</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Modal - Edit Enrollment-->

        <!--begin::Modal - Withdraw Student-->
        <div class="modal fade" id="kt_modal_withdraw_student" tabindex="-1" aria-hidden="true"
            data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered mw-500px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="fw-bold text-danger">Withdraw Student</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </div>
                    </div>
                    <div class="modal-body px-5 my-5">
                        <form id="kt_modal_withdraw_student_form" class="form" novalidate="novalidate">
                            <input type="hidden" name="student_id" id="withdraw_student_id" />
                            <input type="hidden" name="force_withdraw" id="force_withdraw" value="false" />
                            <div class="d-flex flex-column scroll-y px-5 px-lg-10">
                                <!--begin::Warning-->
                                <div
                                    class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6 mb-7">
                                    <i class="ki-outline ki-information-5 fs-2tx text-warning me-4"></i>
                                    <div class="d-flex flex-stack flex-grow-1">
                                        <div class="fw-semibold">
                                            <h4 class="text-gray-900 fw-bold">Attention Required</h4>
                                            <div class="fs-6 text-gray-700">
                                                You are about to withdraw <strong
                                                    id="withdraw_student_name_display"></strong> from this special class.
                                                This action cannot be undone.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Warning-->

                                <!--begin::Unpaid Invoices Warning (hidden by default)-->
                                <div class="notice d-flex bg-light-danger rounded border-danger border border-dashed p-6 mb-7 d-none"
                                    id="unpaid_invoices_warning">
                                    <i class="ki-outline ki-shield-cross fs-2tx text-danger me-4"></i>
                                    <div class="d-flex flex-stack flex-grow-1">
                                        <div class="fw-semibold">
                                            <h4 class="text-danger fw-bold">Unpaid Invoices Found!</h4>
                                            <div class="fs-6 text-gray-700" id="unpaid_invoices_message">
                                                This student has unpaid Special Class Fee invoices.
                                            </div>
                                            <div class="form-check form-check-custom form-check-danger mt-3">
                                                <input class="form-check-input" type="checkbox"
                                                    id="confirm_force_withdraw" />
                                                <label class="form-check-label text-danger" for="confirm_force_withdraw">
                                                    I understand and want to proceed with withdrawal anyway
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Unpaid Invoices Warning-->
                            </div>
                            <div class="text-center pt-10">
                                <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger" id="kt_modal_withdraw_student_submit">
                                    <span class="indicator-label">Withdraw Student</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Modal - Withdraw Student-->

        <!--begin::Modal - Edit Secondary Class-->
        <div class="modal fade" id="kt_modal_edit_secondary_class" tabindex="-1" aria-hidden="true"
            data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered mw-500px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="fw-bold">Edit Special Class</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </div>
                    </div>
                    <div class="modal-body px-5 my-5">
                        <form id="kt_modal_edit_secondary_class_form" class="form" novalidate="novalidate">
                            <div class="d-flex flex-column scroll-y px-5 px-lg-10">
                                <!--begin::Name-->
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Special Class Name</label>
                                    <input type="text" name="name" id="edit_secondary_class_name"
                                        class="form-control form-control-solid" value="{{ $secondaryClass->name }}"
                                        required />
                                </div>
                                <!--end::Name-->

                                <!--begin::Payment Type (Read-only)-->
                                <div class="fv-row mb-7">
                                    <label class="fw-semibold fs-6 mb-2">Payment Type <span class="text-muted">(Cannot
                                            change)</span></label>
                                    <input type="text" class="form-control form-control-solid bg-light-secondary"
                                        value="{{ ucwords(str_replace('_', ' ', $secondaryClass->payment_type)) }}"
                                        readonly disabled />
                                    <input type="hidden" name="payment_type"
                                        value="{{ $secondaryClass->payment_type }}" />
                                </div>
                                <!--end::Payment Type-->

                                <!--begin::Fee Amount-->
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Fee Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">৳</span>
                                        <input type="number" name="fee_amount" id="edit_secondary_class_fee"
                                            class="form-control form-control-solid"
                                            value="{{ $secondaryClass->fee_amount }}" min="0" required />
                                    </div>
                                </div>
                                <!--end::Fee Amount-->
                            </div>
                            <div class="text-center pt-10">
                                <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary" id="kt_modal_edit_secondary_class_submit">
                                    <span class="indicator-label">Update</span>
                                    <span class="indicator-progress">Please wait...
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Modal - Edit Secondary Class-->
    @endif
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        // Route configurations
        const routeEnrollStudent =
            "{{ route('classnames.secondary-classes.enroll', [$classname->id, $secondaryClass->id]) }}";
        const routeUpdateStudent =
            "{{ route('classnames.secondary-classes.update-student', [$classname->id, $secondaryClass->id, ':studentId']) }}";
        const routeWithdrawStudent =
            "{{ route('classnames.secondary-classes.withdraw', [$classname->id, $secondaryClass->id, ':studentId']) }}";
        const routeCheckUnpaid =
            "{{ route('classnames.secondary-classes.check-unpaid', [$classname->id, $secondaryClass->id, ':studentId']) }}";
        const routeAvailableStudents =
            "{{ route('classnames.secondary-classes.available-students', [$classname->id, $secondaryClass->id]) }}";
        const routeUpdateSecondaryClass = "{{ route('secondary-classes.update', $secondaryClass->id) }}";
        const isAdminUser = {{ $isAdmin || $isManager ? 'true' : 'false' }};
        const defaultFeeAmount = {{ $secondaryClass->fee_amount }};
        const secondaryClassIsActive = {{ $secondaryClass->is_active ? 'true' : 'false' }};
    </script>
    <script src="{{ asset('js/secondary-classes/show.js') }}"></script>
    <script>
        document.getElementById("academic_menu").classList.add("here", "show");
        document.getElementById("class_link").classList.add("active");
    </script>
@endpush
