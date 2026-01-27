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

        // Prepare batches data for JavaScript
        $batchesForJs = $batches
            ->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'name' => $batch->name,
                    'branch_id' => $batch->branch_id ?? null,
                ];
            })
            ->values()
            ->toArray();
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
                    @if ($isAdmin && $secondaryClass->is_active)
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
                                    Parent Class:
                                    <a href="{{ route('classnames.show', $classname->id) }}"
                                        class="fw-bold text-gray-600 text-hover-primary">{{ $classname->name }}</a>
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
                                    class="fs-2 fw-bold text-primary">৳ {{ number_format($secondaryClass->fee_amount, 0) }}</span>
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
                    <div class="mb-7" id="stats_container">
                        <h5 class="mb-4">Statistics</h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-primary" id="stat_total_students">
                                        {{ $stats['total_students'] }}</div>
                                    <div class="stats-label">Total Students</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-success" id="stat_active_students">
                                        {{ $stats['active_students'] }}</div>
                                    <div class="stats-label">Active Enrollments</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-danger" id="stat_inactive_students">
                                        {{ $stats['inactive_students'] }}</div>
                                    <div class="stats-label">Inactive Enrollments</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-info" id="stat_total_revenue">
                                        ৳ {{ number_format($stats['total_revenue'], 0) }}
                                    </div>
                                    <div class="stats-label">Total Paid</div>
                                </div>
                            </div>
                            @if ($secondaryClass->payment_type === 'monthly')
                                <div class="col-12">
                                    <div class="stats-mini-card bg-light-success">
                                        <div class="stats-value text-success" id="stat_expected_monthly">
                                            ৳ {{ number_format($stats['expected_monthly_revenue'], 0) }}</div>
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
                        <div class="mb-0" id="branch_stats_container">
                            <h5 class="mb-4">Branch-wise Distribution</h5>
                            <div class="branch-stats-list">
                                @foreach ($stats['branch_stats'] as $branchId => $branchStat)
                                    <div class="branch-stat-item" data-branch-id="{{ $branchId }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-semibold text-gray-700">{{ $branchStat['name'] }}</span>
                                            <span
                                                class="badge {{ $branchColors[$branchId] ?? 'badge-light-primary' }} branch-total-badge">
                                                <span class="branch-total-count">{{ $branchStat['total'] }}&nbsp;</span>
                                                students
                                            </span>
                                        </div>
                                        <div class="d-flex gap-3 mt-2 text-muted fs-7">
                                            <span><i class="ki-outline ki-check-circle text-success fs-7 me-1"></i><span
                                                    class="branch-active-count">{{ $branchStat['active'] }}</span>
                                                active</span>
                                            <span><i class="ki-outline ki-cross-circle text-danger fs-7 me-1"></i><span
                                                    class="branch-inactive-count">{{ $branchStat['inactive'] }}</span>
                                                inactive</span>
                                            <span><i class="ki-outline ki-wallet text-info fs-7 me-1"></i>৳ <span
                                                    class="branch-revenue">{{ number_format($branchStat['revenue'], 0) }}</span></span>
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
            <!--begin:::Tabs-->
            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8"
                id="studentStatusTabs" role="tablist">
                <!--begin:::Tab item - Active Students-->
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab"
                        href="#kt_active_students_tab" role="tab" aria-selected="true" data-status-type="active">
                        <i class="ki-outline ki-people fs-3 me-2"></i>
                        Active Students
                    </a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item - Inactive Students-->
                <li class="nav-item" role="presentation">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_inactive_students_tab"
                        role="tab" aria-selected="false" data-status-type="inactive">
                        <i class="ki-outline ki-people fs-3 me-2"></i>
                        Inactive Students
                    </a>
                </li>
                <!--end:::Tab item-->

                @if (($isAdmin || $isManager) && $secondaryClass->is_active)
                    <li class="nav-item ms-auto">
                        <!--begin::Add Student-->
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#kt_modal_enroll_student">
                            <i class="ki-outline ki-plus fs-2"></i>Enroll Student
                        </button>
                        <!--end::Add Student-->
                    </li>
                @endif
            </ul>
            <!--end:::Tabs-->

            <!--begin:::Tab content-->
            <div class="tab-content" id="studentStatusTabContent">
                <!--begin:::Active Students Tab pane-->
                <div class="tab-pane fade show active" id="kt_active_students_tab" role="tabpanel">
                    <!--begin::Card-->
                    <div class="card card-flush">
                        <!--begin::Card body-->
                        <div class="card-body py-4">
                            @if ($isAdmin && $branches->count() > 0)
                                <!--begin::Branch Tabs for Admin-->
                                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="activeBranchTabs"
                                    role="tablist">
                                    @foreach ($branches as $index => $branch)
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link fw-bold {{ $index === 0 ? 'active' : '' }}"
                                                id="active-tab-branch-{{ $branch->id }}" data-bs-toggle="tab"
                                                href="#kt_active_tab_branch_{{ $branch->id }}" role="tab"
                                                aria-controls="kt_active_tab_branch_{{ $branch->id }}"
                                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                                data-branch-id="{{ $branch->id }}">
                                                <i class="ki-outline ki-bank fs-4 me-1"></i>
                                                {{ ucfirst($branch->branch_name) }}
                                                <span
                                                    class="badge {{ $branchColors[$branch->id] ?? 'badge-light-primary' }} ms-2 branch-count-badge"
                                                    data-branch-id="{{ $branch->id }}" data-status-type="active">
                                                    <span class="spinner-border spinner-border-sm"
                                                        style="width: 0.75rem; height: 0.75rem;" role="status"></span>
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <!--end::Branch Tabs-->

                                <!--begin::Tab Content-->
                                <div class="tab-content" id="activeBranchTabsContent">
                                    @foreach ($branches as $index => $branch)
                                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                                            id="kt_active_tab_branch_{{ $branch->id }}" role="tabpanel"
                                            aria-labelledby="active-tab-branch-{{ $branch->id }}">
                                            @include('secondary-classes.partials.student-table', [
                                                'tableId' => 'kt_active_students_table_branch_' . $branch->id,
                                                'branchId' => $branch->id,
                                                'statusType' => 'active',
                                                'secondaryClass' => $secondaryClass,
                                                'classname' => $classname,
                                                'batches' => $batches->where('branch_id', $branch->id),
                                                'isAdmin' => $isAdmin,
                                                'isManager' => $isManager,
                                            ])
                                        </div>
                                    @endforeach
                                </div>
                                <!--end::Tab Content-->
                            @else
                                <!--begin::Single Table for Non-Admin-->
                                @include('secondary-classes.partials.student-table', [
                                    'tableId' => 'kt_active_students_table',
                                    'branchId' => null,
                                    'statusType' => 'active',
                                    'secondaryClass' => $secondaryClass,
                                    'classname' => $classname,
                                    'batches' => $batches,
                                    'isAdmin' => $isAdmin,
                                    'isManager' => $isManager,
                                ])
                                <!--end::Single Table for Non-Admin-->
                            @endif
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end:::Active Students Tab pane-->

                <!--begin:::Inactive Students Tab pane-->
                <div class="tab-pane fade" id="kt_inactive_students_tab" role="tabpanel">
                    <!--begin::Card-->
                    <div class="card card-flush">
                        <!--begin::Card body-->
                        <div class="card-body py-4">
                            @if ($isAdmin && $branches->count() > 0)
                                <!--begin::Branch Tabs for Admin-->
                                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="inactiveBranchTabs"
                                    role="tablist">
                                    @foreach ($branches as $index => $branch)
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link fw-bold {{ $index === 0 ? 'active' : '' }}"
                                                id="inactive-tab-branch-{{ $branch->id }}" data-bs-toggle="tab"
                                                href="#kt_inactive_tab_branch_{{ $branch->id }}" role="tab"
                                                aria-controls="kt_inactive_tab_branch_{{ $branch->id }}"
                                                aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                                data-branch-id="{{ $branch->id }}">
                                                <i class="ki-outline ki-bank fs-4 me-1"></i>
                                                {{ ucfirst($branch->branch_name) }}
                                                <span
                                                    class="badge {{ $branchColors[$branch->id] ?? 'badge-light-primary' }} ms-2 branch-count-badge"
                                                    data-branch-id="{{ $branch->id }}" data-status-type="inactive">
                                                    <span class="spinner-border spinner-border-sm"
                                                        style="width: 0.75rem; height: 0.75rem;" role="status"></span>
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <!--end::Branch Tabs-->

                                <!--begin::Tab Content-->
                                <div class="tab-content" id="inactiveBranchTabsContent">
                                    @foreach ($branches as $index => $branch)
                                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                                            id="kt_inactive_tab_branch_{{ $branch->id }}" role="tabpanel"
                                            aria-labelledby="inactive-tab-branch-{{ $branch->id }}">
                                            @include('secondary-classes.partials.student-table', [
                                                'tableId' => 'kt_inactive_students_table_branch_' . $branch->id,
                                                'branchId' => $branch->id,
                                                'statusType' => 'inactive',
                                                'secondaryClass' => $secondaryClass,
                                                'classname' => $classname,
                                                'batches' => $batches->where('branch_id', $branch->id),
                                                'isAdmin' => $isAdmin,
                                                'isManager' => $isManager,
                                            ])
                                        </div>
                                    @endforeach
                                </div>
                                <!--end::Tab Content-->
                            @else
                                <!--begin::Single Table for Non-Admin-->
                                @include('secondary-classes.partials.student-table', [
                                    'tableId' => 'kt_inactive_students_table',
                                    'branchId' => null,
                                    'statusType' => 'inactive',
                                    'secondaryClass' => $secondaryClass,
                                    'classname' => $classname,
                                    'batches' => $batches,
                                    'isAdmin' => $isAdmin,
                                    'isManager' => $isManager,
                                ])
                                <!--end::Single Table for Non-Admin-->
                            @endif
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end:::Inactive Students Tab pane-->
            </div>
            <!--end:::Tab content-->
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
                                @if ($isAdmin)
                                    <!--begin::Branch Filter-->
                                    <div class="fv-row mb-5">
                                        <label class="fw-semibold fs-6 mb-2">Filter by Branch</label>
                                        <select id="enroll_branch_filter" class="form-select form-select-solid"
                                            data-kt-select2="true">
                                            <option value="">All Branches</option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <!--end::Branch Filter-->
                                @endif

                                <!--begin::Student Select-->
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Select Student</label>
                                    <select name="student_id" id="enroll_student_select"
                                        class="form-select form-select-solid" data-placeholder="Select a student..."
                                        data-dropdown-parent="#kt_modal_enroll_student">
                                        <option value="">Select a student...</option>
                                        @foreach ($availableStudents as $student)
                                            <option value="{{ $student['id'] }}"
                                                data-branch-id="{{ $student['branch_id'] }}"
                                                data-student-id="{{ $student['student_unique_id'] }}"
                                                data-branch-name="{{ $student['branch_name'] }}"
                                                data-batch-name="{{ $student['batch_name'] }}"
                                                data-status="{{ $student['status'] }}"
                                                data-is-pending="{{ $student['is_pending'] ? '1' : '0' }}">
                                                {{ $student['name'] }} ({{ $student['student_unique_id'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="text-muted fs-7 mt-2">
                                        Only students from class <strong>{{ $classname->name }}</strong> who are not
                                        already enrolled will appear.
                                    </div>
                                </div>
                                <!--end::Student Select-->

                                <!--begin::Selected Student Info-->
                                <div id="selected_student_info" class="d-none mb-7">
                                    <div
                                        class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-4">
                                        <i class="ki-outline ki-information-5 fs-2tx text-primary me-4"></i>
                                        <div class="d-flex flex-stack flex-grow-1">
                                            <div class="fw-semibold">
                                                <div class="fs-6 text-gray-700">
                                                    <strong id="selected_student_name"></strong>
                                                    <span id="selected_student_status"></span><br>
                                                    <span class="text-muted">Branch: <span
                                                            id="selected_student_branch"></span></span><br>
                                                    <span class="text-muted">Batch: <span
                                                            id="selected_student_batch"></span></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Selected Student Info-->

                                <!--begin::Amount-->
                                <div class="fv-row mb-7">
                                    <label class="required fw-semibold fs-6 mb-2">Fee Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">৳ </span>
                                        <input type="number" name="amount" id="enroll_amount"
                                            class="form-control form-control-solid"
                                            value="{{ $secondaryClass->fee_amount }}" min="0" required />
                                    </div>
                                    <div class="text-muted fs-7 mt-2">
                                        Default fee: ৳ {{ number_format($secondaryClass->fee_amount, 0) }}
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
                                        <span class="input-group-text">৳ </span>
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
                                        <span class="input-group-text">৳ </span>
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

        <!--begin::Modal - Toggle Enrollment Activation-->
        <div class="modal fade" id="kt_modal_toggle_activation" tabindex="-1" aria-hidden="true"
            data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered mw-500px">
                <div class="modal-content">
                    <div class="modal-header" id="toggle_modal_header">
                        <h2 class="fw-bold" id="toggle_activation_modal_title">Deactivate Enrollment</h2>
                        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                            <i class="ki-outline ki-cross fs-1"></i>
                        </div>
                    </div>
                    <div class="modal-body px-5 my-5">
                        <form id="kt_modal_toggle_activation_form" class="form" novalidate="novalidate">
                            <input type="hidden" name="student_id" id="toggle_student_id" />
                            <input type="hidden" name="is_active" id="toggle_is_active" />

                            <div class="d-flex flex-column scroll-y px-5 px-lg-10">
                                <!--begin::Deactivate Warning-->
                                <div class="notice d-flex bg-light-danger rounded border-danger border border-dashed p-6 mb-7"
                                    id="toggle_deactivate_warning">
                                    <i class="ki-outline ki-cross-circle fs-2tx text-danger me-4"></i>
                                    <div class="d-flex flex-stack flex-grow-1">
                                        <div class="fw-semibold">
                                            <h4 class="text-gray-900 fw-bold">Confirm Deactivation</h4>
                                            <div class="fs-6 text-gray-700">
                                                You are about to deactivate the enrollment for <strong
                                                    id="toggle_student_name_display"></strong>.
                                                The student will no longer be considered active in this special class.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Deactivate Warning-->

                                <!--begin::Activate Info-->
                                <div class="notice d-flex bg-light-success rounded border-success border border-dashed p-6 mb-7 d-none"
                                    id="toggle_activate_info">
                                    <i class="ki-outline ki-check-circle fs-2tx text-success me-4"></i>
                                    <div class="d-flex flex-stack flex-grow-1">
                                        <div class="fw-semibold">
                                            <h4 class="text-gray-900 fw-bold">Confirm Activation</h4>
                                            <div class="fs-6 text-gray-700">
                                                You are about to activate the enrollment for <strong
                                                    id="toggle_student_name_display_activate"></strong>.
                                                The student will be marked as active in this special class.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Activate Info-->

                                <!--begin::Unpaid Invoices Warning (hidden by default)-->
                                <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6 mb-7 d-none"
                                    id="toggle_unpaid_warning">
                                    <i class="ki-outline ki-shield-cross fs-2tx text-warning me-4"></i>
                                    <div class="d-flex flex-stack flex-grow-1">
                                        <div class="fw-semibold">
                                            <h4 class="text-warning fw-bold">Cannot Deactivate - Unpaid Invoices!</h4>
                                            <div class="fs-6 text-gray-700" id="toggle_unpaid_message">
                                                This student has unpaid Special Class Fee invoices. Please clear all
                                                dues before deactivation.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Unpaid Invoices Warning-->
                            </div>

                            <div class="text-center pt-10">
                                <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-danger" id="kt_modal_toggle_activation_submit">
                                    <span class="indicator-label" id="toggle_submit_label">Deactivate</span>
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
        <!--end::Modal - Toggle Enrollment Activation-->
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
        const routeCheckUnpaid =
            "{{ route('classnames.secondary-classes.check-unpaid', [$classname->id, $secondaryClass->id, ':studentId']) }}";
        const routeToggleActivation =
            "{{ route('classnames.secondary-classes.toggle-activation', [$classname->id, $secondaryClass->id, ':studentId']) }}";
        const routeUpdateSecondaryClass = "{{ route('secondary-classes.update', $secondaryClass->id) }}";
        const routeEnrolledStudentsAjax =
            "{{ route('classnames.secondary-classes.enrolled-students-ajax', [$classname->id, $secondaryClass->id]) }}";
        const routeStatsAjax =
            "{{ route('classnames.secondary-classes.stats-ajax', [$classname->id, $secondaryClass->id]) }}";
        const routeBranchCountsAjax =
            "{{ route('classnames.secondary-classes.branch-counts-ajax', [$classname->id, $secondaryClass->id]) }}";
        const routeAvailableStudents =
            "{{ route('classnames.secondary-classes.available-students', [$classname->id, $secondaryClass->id]) }}";
        const routeStudentShow = "{{ route('students.show', ':studentId') }}";

        const isAdminUser = {{ $isAdmin || $isManager ? 'true' : 'false' }};
        const defaultFeeAmount = {{ $secondaryClass->fee_amount }};
        const secondaryClassIsActive = {{ $secondaryClass->is_active ? 'true' : 'false' }};
        const paymentType = "{{ $secondaryClass->payment_type }}";

        // Branch colors for badges
        const branchColors = @json($branchColors);
        const branches = @json($branches);

        // Batches with branch association
        const allBatches = @json($batchesForJs);
    </script>
    <script src="{{ asset('js/secondary-classes/show.js') }}"></script>
    <script>
        document.getElementById("academic_menu").classList.add("here", "show");
        document.getElementById("class_link").classList.add("active");
    </script>
@endpush
