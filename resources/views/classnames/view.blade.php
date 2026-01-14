@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('css/classnames/view.css') }}" rel="stylesheet" type="text/css" />
@endpush

@extends('layouts.app')

@section('title', 'Class - ' . $classname->name)

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            {{ $classname->name }} &nbsp;<i class="text-muted"> ({{ $classname->class_numeral }})</i>
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary"> Academic </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Class
            </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection

@section('content')
    @php
        $manageSubjects = auth()->user()->can('subjects.manage');
        $createClass = auth()->user()->can('classes.create');
        $groupedSubjects = $classname->subjects->groupBy('academic_group');
        $totalSubjects = $classname->subjects->count();

        // Define badge colors for different branches
        $badgeColors = [
            'badge-light-primary',
            'badge-light-success',
            'badge-light-warning',
            'badge-light-danger',
            'badge-light-info',
        ];

        // Map branches to badge colors dynamically
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
            <div class="card card-flush mb-0 @if (!$classname->isActive()) border border-dashed border-danger @endif"
                data-kt-sticky="true" data-kt-sticky-name="student-summary" data-kt-sticky-offset="{default: false, lg: 0}"
                data-kt-sticky-width="{lg: '250px', xl: '350px'}" data-kt-sticky-left="auto" data-kt-sticky-top="100px"
                data-kt-sticky-animation="false" data-kt-sticky-zindex="95">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <h3 class="text-gray-600">Class Info</h3>
                    </div>
                    <!--end::Card title-->
                    @can('classes.edit')
                        @if ($classname->isActive())
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <!--begin::More options-->
                                <a href="#" class="btn btn-sm btn-light btn-icon" data-kt-menu-trigger="click"
                                    data-kt-menu-placement="bottom-end">
                                    <i class="ki-outline ki-dots-horizontal fs-3"> </i>
                                </a>
                                <!--begin::Menu-->
                                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-6 w-175px py-4"
                                    data-kt-menu="true">
                                    <!--begin::Menu item-->
                                    <div class="menu-item px-3">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_class"
                                            data-class-id="{{ $classname->id }}" class="menu-link text-hover-primary px-3 "><i
                                                class="las la-pen fs-3 me-2"></i>
                                            Edit Class</a>
                                    </div>
                                    <!--end::Menu item-->
                                </div>
                                <!--end::Menu-->
                                <!--end::More options-->
                            </div>
                            <!--end::Card toolbar-->
                        @endif
                    @endcan
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body pt-0 fs-6">
                    <!--begin::Section-->
                    <div class="mb-7">
                        <!--begin::Details-->
                        <div class="d-flex flex-column">
                            <!--begin::Info-->
                            <div class="d-flex flex-column mb-3">
                                <!--begin::Name-->
                                <span class="fs-1 fw-bold text-gray-900 me-2">{{ $classname->name }}
                                    <i class="text-muted">({{ $classname->class_numeral }})</i></span>
                                <!--end::Name-->
                            </div>
                            <!--end::Info-->
                            <!--begin::Info-->
                            <div class="d-flex flex-column">
                                <!--begin::Name-->
                                <span
                                    class="fs-6 text-gray-600 me-2">{{ $classname->description ?? 'This is a sample description. Update the class description to change this.' }}</span>
                                <!--end::Name-->
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
                        <h5 class="mb-4">Statistics</h5>
                        <!--end::Title-->
                        <!--begin::Stats Grid-->
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-primary">
                                        {{ $classname->active_students_count + $classname->inactive_students_count }}</div>
                                    <div class="stats-label">Total Students</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-success">{{ $classname->active_students_count }}</div>
                                    <div class="stats-label">Active Students</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-danger">{{ $classname->inactive_students_count }}</div>
                                    <div class="stats-label">Inactive Students</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stats-mini-card">
                                    <div class="stats-value text-info">{{ $totalSubjects }}</div>
                                    <div class="stats-label">Total Subjects</div>
                                </div>
                            </div>
                        </div>
                        <!--end::Stats Grid-->
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
                            <tr class="">
                                <td class="text-gray-500">Status:</td>
                                <td class="text-gray-800">
                                    @if ($classname->isActive())
                                        <span class="badge badge-success rounded-pill">Active</span>
                                    @else
                                        <span class="badge badge-danger rounded-pill">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            <tr class="">
                                <td class="text-gray-500">Created Since:</td>
                                <td class="text-gray-800">
                                    {{ $classname->created_at->diffForHumans() }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $classname->created_at->format('d-M-Y h:m:s A') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>
                                </td>
                            </tr>
                            <tr class="">
                                <td class="text-gray-500">Updated Since:</td>
                                <td class="text-gray-800">
                                    {{ $classname->updated_at->diffForHumans() }}
                                    <span class="ms-1" data-bs-toggle="tooltip"
                                        title="{{ $classname->updated_at->format('d-M-Y h:m:s A') }}">
                                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                                    </span>
                                </td>
                            </tr>
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
        <div class="flex-lg-row-fluid ms-lg-10" data-kt-swapper="false">
            <!--begin:::Tabs-->
            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_subjects_tab"><i
                            class="ki-outline ki-book-open fs-3 me-2"></i>Subjects</a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_enrolled_students_tab"><i
                            class="ki-outline ki-people fs-3 me-2"></i>Regular Students</a>
                </li>
                <!--end:::Tab item-->
                <!--begin:::Tab item-->
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab"
                        href="#kt_secondary_classnames_tab"><i class="ki-outline ki-teacher fs-3 me-2"></i>Special
                        Class</a>
                </li>
                <!--end:::Tab item-->

                <!--begin:::Tab item-->
                @if ($createClass || $manageSubjects)
                    <li class="nav-item ms-auto">
                        <!--begin::Action menu-->
                        <a href="#" class="btn btn-primary ps-7" data-kt-menu-trigger="click"
                            data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">Actions
                            <i class="ki-outline ki-down fs-2 me-0"></i></a>
                        <!--begin::Menu-->
                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold py-4 w-250px fs-6"
                            data-kt-menu="true">
                            @if ($manageSubjects && $classname->isActive())
                                <!--begin::Menu item-->
                                <div class="menu-item px-5 my-1">
                                    <a href="#" class="menu-link px-5 text-hover-primary" data-bs-toggle="modal"
                                        data-bs-target="#kt_modal_add_subject"><i
                                            class="ki-outline ki-book-open fs-2 me-2"></i>Add Subject
                                    </a>
                                </div>
                                <!--end::Menu item-->
                            @endif
                            @if ($createClass && $classname->isActive())
                                <!--begin::Menu item-->
                                <div class="menu-item px-5 my-1">
                                    <a href="#" class="menu-link px-5 text-hover-primary" data-bs-toggle="modal"
                                        data-bs-target="#kt_modal_add_special_class"><i
                                            class="ki-outline ki-teacher fs-2 me-2"></i>Add Special Class
                                    </a>
                                </div>
                                <!--end::Menu item-->
                            @endif
                        </div>
                        <!--end::Menu-->
                        <!--end::Action Menu-->
                    </li>
                @endif
                <!--end:::Tab item-->
            </ul>
            <!--end:::Tabs-->

            <!--begin:::Tab content-->
            <div class="tab-content" id="myTabContent">
                <!--begin:::Tab pane-->
                <div class="tab-pane fade show active" id="kt_subjects_tab" role="tabpanel">
                    <!--begin::Card-->
                    <div class="card mb-5 mb-xl-10">
                        <!--begin::Card header-->
                        <div class="card-header m-0">
                            <!--begin::Card title-->
                            <div class="card-title m-0">
                                <h3>Subjects</h3>
                            </div>
                            <!--end::Card title-->
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge badge-light-primary fs-7">
                                        <i class="ki-outline ki-book fs-6 me-1"></i>
                                        {{ $totalSubjects }} Subjects
                                    </span>
                                </div>
                            </div>
                            <!--end::Card toolbar-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body py-4">
                            @forelse ($groupedSubjects as $group => $subjects)
                                <!--begin::Academic Group Section-->
                                <div class="academic-group-section">
                                    <!--begin::Group Header-->
                                    <div class="group-header d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            @php
                                                $groupIcon = match ($group) {
                                                    'Science' => 'ki-flask',
                                                    'Commerce' => 'ki-chart-line-up',
                                                    'Arts' => 'ki-paintbucket',
                                                    default => 'ki-abstract-26',
                                                };
                                            @endphp
                                            <i class="ki-outline {{ $groupIcon }} fs-3 me-2 text-white"></i>
                                            <h5 class="mb-0 fw-bold">{{ $group ?? 'General' }} Group</h5>
                                        </div>
                                        <span class="subjects-count fs-7 fw-semibold">
                                            <i class="ki-outline ki-book-open fs-6 me-1"></i>
                                            {{ $subjects->count() }} subjects
                                        </span>
                                    </div>
                                    <!--end::Group Header-->

                                    <!--begin::Subjects Grid-->
                                    <div class="p-4">
                                        <div class="row g-4">
                                            @foreach ($subjects as $subject)
                                                <div class="col-md-6 col-xl-4">
                                                    <!--begin::Subject Card-->
                                                    <div class="subject-card subject-editable"
                                                        data-id="{{ $subject->id }}">
                                                        <!--begin::Subject Content-->
                                                        <div class="d-flex align-items-start justify-content-between">
                                                            <div class="d-flex align-items-center flex-grow-1 me-2">
                                                                @php
                                                                    $iconClass = strtolower($group ?? 'general');
                                                                    $subjectIcon = match ($group) {
                                                                        'Science' => 'ki-flask',
                                                                        'Commerce' => 'ki-chart-pie-simple',
                                                                        'Arts' => 'ki-brush',
                                                                        default => 'ki-book',
                                                                    };
                                                                @endphp
                                                                <div class="subject-icon {{ $iconClass }} me-3">
                                                                    <i class="ki-outline {{ $subjectIcon }}"></i>
                                                                </div>
                                                                <div class="flex-grow-1 min-w-0">
                                                                    <span
                                                                        class="subject-title subject-text fs-6 d-block text-truncate">
                                                                        {{ $subject->name }}
                                                                    </span>
                                                                    <input type="text"
                                                                        class="subject-input form-control form-control-sm d-none fs-6"
                                                                        value="{{ $subject->name }}" />
                                                                    <span class="text-muted fs-8">
                                                                        <i class="ki-outline ki-people fs-8 me-1"></i>
                                                                        {{ $subject->students_count }} students enrolled
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            @if ($manageSubjects && $classname->isActive())
                                                                <!--begin::Actions-->
                                                                <div
                                                                    class="subject-actions d-flex align-items-center gap-1">
                                                                    <!--begin::Edit Mode Actions (Hidden by default)-->
                                                                    <button type="button"
                                                                        class="btn btn-icon btn-sm action-save check-icon d-none"
                                                                        data-bs-toggle="tooltip" title="Save">
                                                                        <i class="ki-outline ki-check fs-4"></i>
                                                                    </button>
                                                                    <button type="button"
                                                                        class="btn btn-icon btn-sm action-cancel cancel-icon d-none"
                                                                        data-bs-toggle="tooltip" title="Cancel">
                                                                        <i class="ki-outline ki-cross fs-4"></i>
                                                                    </button>
                                                                    <!--end::Edit Mode Actions-->

                                                                    <!--begin::View Mode Actions-->
                                                                    <button type="button"
                                                                        class="btn btn-icon btn-sm action-edit edit-icon"
                                                                        data-bs-toggle="tooltip" title="Edit Subject">
                                                                        <i class="ki-outline ki-pencil fs-5"></i>
                                                                    </button>
                                                                    @if ($subject->students_count == 0)
                                                                        <button type="button"
                                                                            class="btn btn-icon btn-sm action-delete delete-subject"
                                                                            data-subject-id="{{ $subject->id }}"
                                                                            data-bs-toggle="tooltip"
                                                                            title="Delete Subject">
                                                                            <i class="ki-outline ki-trash fs-5"></i>
                                                                        </button>
                                                                    @endif
                                                                    <!--end::View Mode Actions-->
                                                                </div>
                                                                <!--end::Actions-->
                                                            @endif
                                                        </div>
                                                        <!--end::Subject Content-->
                                                    </div>
                                                    <!--end::Subject Card-->
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <!--end::Subjects Grid-->
                                </div>
                                <!--end::Academic Group Section-->
                            @empty
                                <!--begin::Empty State-->
                                <div class="text-center py-15">
                                    <div class="empty-state-icon">
                                        <i class="ki-outline ki-book-open"></i>
                                    </div>
                                    <h4 class="text-gray-800 fw-bold mb-3">No Subjects Added Yet</h4>
                                    <p class="text-muted fs-6 mb-6 mw-400px mx-auto">
                                        Start by adding your first subject for this class. Subjects help organize the
                                        curriculum for students.
                                    </p>
                                    @if ($manageSubjects && $classname->isActive())
                                        <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#kt_modal_add_subject">
                                            <i class="ki-outline ki-plus fs-3 me-1"></i> Add First Subject
                                        </a>
                                    @endif
                                </div>
                                <!--end::Empty State-->
                            @endforelse
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end:::Tab pane-->

                <!--begin:::Students Tab pane-->
                <div class="tab-pane fade" id="kt_enrolled_students_tab" role="tabpanel">
                    <!--begin::Statements-->
                    <div class="card mb-6 mb-xl-9">
                        <!--begin::Header-->
                        <div class="card-header">
                            <!--begin::Title-->
                            <div class="card-title">
                                <!--begin::Search-->
                                <div class="d-flex align-items-center position-relative my-1">
                                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                                    <input type="text" data-enrolled-regular-students-table-filter="search"
                                        class="form-control form-control-solid w-350px ps-12"
                                        placeholder="Search in students">
                                </div>
                                <!--end::Search-->
                            </div>
                            <!--end::Title-->
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <!--begin::Toolbar-->
                                <div class="d-flex justify-content-end"
                                    data-enrolled-regular-students-table-filter="base">
                                    <!--begin::Filter-->
                                    <button type="button" class="btn btn-light-primary me-3"
                                        data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
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
                                        <div class="px-7 py-5" data-enrolled-regular-students-table-filter="form">
                                            <!--begin::Input group-->
                                            <div class="mb-10">
                                                <label class="form-label fs-6 fw-semibold">Group:</label>
                                                <select class="form-select form-select-solid fw-bold"
                                                    data-kt-select2="true" data-placeholder="Select group"
                                                    data-allow-clear="true" data-hide-search="true">
                                                    <option></option>
                                                    <option value="ucms_Science">Science</option>
                                                    <option value="ucms_Commerce">Commerce</option>
                                                </select>
                                            </div>
                                            <!--end::Input group-->

                                            <div class="mb-10">
                                                <label class="form-label fs-6 fw-semibold">Student Status:</label>
                                                <select class="form-select form-select-solid fw-bold"
                                                    data-kt-select2="true" data-placeholder="Select option"
                                                    data-allow-clear="true" data-kt-subscription-table-filter="status"
                                                    data-hide-search="true">
                                                    <option></option>
                                                    <option value="active">Active</option>
                                                    <option value="suspended">Inactive</option>
                                                </select>
                                            </div>
                                            <!--begin::Actions-->
                                            <div class="d-flex justify-content-end">
                                                <button type="reset"
                                                    class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                                    data-kt-menu-dismiss="true"
                                                    data-enrolled-regular-students-table-filter="reset">Reset</button>
                                                <button type="submit" class="btn btn-primary fw-semibold px-6"
                                                    data-kt-menu-dismiss="true"
                                                    data-enrolled-regular-students-table-filter="filter">Apply</button>
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
                        <!--end::Header-->

                        <!--begin::Card body-->
                        <div class="card-body pb-5">
                            @if ($isAdmin && $branches->count() > 0)
                                <!--begin::Branch Tabs for Admin-->
                                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="branchTabs"
                                    role="tablist">
                                    @foreach ($branches as $index => $branch)
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link @if ($index === 0) active @endif"
                                                id="branch-{{ $branch->id }}-tab" data-bs-toggle="tab"
                                                data-bs-target="#branch_{{ $branch->id }}_content" type="button"
                                                role="tab" data-branch-filter="{{ $branch->id }}">
                                                <i class="ki-outline ki-home fs-4 me-2"></i>
                                                {{ $branch->branch_name }}
                                                <span
                                                    class="badge {{ $branchColors[$branch->id] ?? 'badge-light-primary' }} ms-2">{{ $studentsByBranch->get($branch->id, collect())->count() }}</span>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                                <!--end::Branch Tabs for Admin-->
                            @endif

                            <!--begin::Table-->
                            <table class="table table-hover align-middle table-row-dashed fs-6 fw-semibold gy-4 ucms-table"
                                id="kt_enrolled_regular_students_table">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-30px">#</th>
                                        <th>Student Name</th>
                                        <th>Group</th>
                                        <th class="d-none">Group (Filter)</th>
                                        <th class="d-none">Status (Filter)</th>
                                        <th>Batch</th>
                                        <th class="w-200px">Branch</th>
                                        <th class="w-150px">Admitted By</th>
                                        <th class="w-150px">Admission Date</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach ($classname->students as $student)
                                        <tr data-branch-id="{{ $student->branch_id }}">
                                            <td class="pe-2">{{ $loop->index + 1 }}</td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <!--begin::user details-->
                                                    <div class="d-flex flex-column text-start">
                                                        <a href="{{ route('students.show', $student->id) }}"
                                                            class="@if ($student->studentActivation && $student->studentActivation->active_status == 'inactive') text-danger @else text-gray-800 text-hover-primary @endif mb-1"
                                                            @if ($student->studentActivation && $student->studentActivation->active_status == 'inactive') title="Inactive Student" data-bs-toggle="tooltip"
                                                            data-bs-placement="top" @endif>{{ $student->name }}
                                                        </a>
                                                        <span
                                                            class="fw-bold fs-base">{{ $student->student_unique_id }}</span>
                                                    </div>
                                                    <!--begin::user details-->
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $badge =
                                                        [
                                                            'Science' => 'info',
                                                            'Commerce' => 'primary',
                                                            'Arts' => 'warning',
                                                        ][$student->academic_group] ?? null;
                                                @endphp

                                                @if ($badge)
                                                    <span
                                                        class="badge badge-pill badge-{{ $badge }}">{{ $student->academic_group }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="d-none">ucms_{{ $student->academic_group }}</td>
                                            <td class="d-none">
                                                @if ($student->studentActivation->active_status == 'active')
                                                    active
                                                @else
                                                    suspended
                                                @endif
                                            </td>
                                            <td>{{ $student->batch->name ?? '-' }}</td>
                                            <td>{{ $student->branch->branch_name ?? '-' }}</td>
                                            <td>{{ $student->createdBy->name ?? '-' }}</td>
                                            <td>{{ $student->created_at->format('d-M-Y') }}</td>
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
                <!--end:::Students Tab pane-->

                <!--begin:::Secondary Classes Tab pane-->
                <div class="tab-pane fade" id="kt_secondary_classnames_tab" role="tabpanel">
                    <!--begin::Card-->
                    <div class="card mb-6 mb-xl-9">
                        <!--begin::Header-->
                        <div class="card-header">
                            <!--begin::Title-->
                            <div class="card-title">
                                <h3>Special Classes</h3>
                            </div>
                            <!--end::Title-->
                            <!--begin::Card toolbar-->
                            <div class="card-toolbar">
                                <span class="badge badge-light-info fs-7">
                                    <i class="ki-outline ki-abstract-26 fs-6 me-1"></i>
                                    {{ $classname->secondaryClasses->count() }} Classes
                                </span>
                            </div>
                            <!--end::Card toolbar-->
                        </div>
                        <!--end::Header-->

                        <!--begin::Card body-->
                        <div class="card-body pb-5">
                            <!--begin::Secondary Classes Grid-->
                            <div class="row g-4" id="secondary-classes-container">
                                @forelse ($classname->secondaryClasses as $secondaryClass)
                                    <!--begin::Secondary Class Card-->
                                    <div class="col-md-6" data-secondary-class-id="{{ $secondaryClass->id }}">
                                        <div
                                            class="secondary-class-card @if (!$secondaryClass->is_active) inactive @endif">
                                            <!--begin::Card Header-->
                                            <div class="secondary-class-header">
                                                <div class="d-flex align-items-center">
                                                    <div class="secondary-class-icon">
                                                        <i class="ki-outline ki-abstract-26"></i>
                                                    </div>
                                                    <div class="ms-3">
                                                        <a href="{{ route('classnames.secondary-classes.show', [$classname->id, $secondaryClass->id]) }}"
                                                            class="secondary-class-title mb-0 text-gray-900 text-hover-primary fw-bold fs-5 text-decoration-none">
                                                            {{ $secondaryClass->name }}
                                                        </a>
                                                        <span class="text-muted fs-7 d-block">
                                                            {{ ucwords(str_replace('_', ' ', $secondaryClass->payment_type)) }}
                                                        </span>
                                                    </div>
                                                </div>
                                                @if (auth()->user()->isAdmin())
                                                    <div class="secondary-class-actions">
                                                        <div
                                                            class="form-check form-switch form-check-solid form-check-success">
                                                            <input class="form-check-input toggle-secondary-activation"
                                                                type="checkbox" value="{{ $secondaryClass->id }}"
                                                                data-secondary-class-id="{{ $secondaryClass->id }}"
                                                                data-bs-toggle="tooltip" title="Change status"
                                                                @if ($secondaryClass->is_active) checked @endif>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            <!--end::Card Header-->

                                            <!--begin::Card Body-->
                                            <div class="secondary-class-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="secondary-class-stat">
                                                        <span class="stat-label">Fee Amount</span>
                                                        <span
                                                            class="stat-value text-primary">৳{{ number_format($secondaryClass->fee_amount, 0) }}</span>
                                                    </div>
                                                    <div class="secondary-class-stat text-end">
                                                        <span class="stat-label">Students</span>
                                                        <a href="{{ route('classnames.secondary-classes.show', [$classname->id, $secondaryClass->id]) }}"
                                                            class="stat-value text-info text-hover-primary text-decoration-none">
                                                            {{ $secondaryClass->students_count }}
                                                        </a>
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span
                                                        class="badge @if ($secondaryClass->is_active) badge-light-success @else badge-light-danger @endif">
                                                        <i
                                                            class="ki-outline @if ($secondaryClass->is_active) ki-check-circle @else ki-cross-circle @endif fs-6 me-1"></i>
                                                        {{ $secondaryClass->is_active ? 'Active' : 'Inactive' }}
                                                    </span>

                                                    <div class="d-flex align-items-center gap-2">
                                                        <a href="{{ route('classnames.secondary-classes.show', [$classname->id, $secondaryClass->id]) }}"
                                                            class="btn btn-sm btn-light-info btn-icon"
                                                            data-bs-toggle="tooltip" title="View Details">
                                                            <i class="ki-outline ki-eye fs-5"></i>
                                                        </a>

                                                        @if (auth()->user()->isAdmin())
                                                            <div class="btn-group">
                                                                <button type="button"
                                                                    class="btn btn-sm btn-light-primary edit-secondary-class @if (!$secondaryClass->is_active) disabled @endif"
                                                                    data-secondary-class-id="{{ $secondaryClass->id }}"
                                                                    data-is-active="{{ $secondaryClass->is_active ? '1' : '0' }}"
                                                                    data-bs-toggle="tooltip"
                                                                    title="{{ $secondaryClass->is_active ? 'Edit' : 'Activate first to edit' }}">
                                                                    <i class="ki-outline ki-pencil fs-5"></i>
                                                                </button>
                                                                @if ($secondaryClass->students_count == 0)
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-light-danger delete-secondary-class @if (!$secondaryClass->is_active) disabled @endif"
                                                                        data-secondary-class-id="{{ $secondaryClass->id }}"
                                                                        data-is-active="{{ $secondaryClass->is_active ? '1' : '0' }}"
                                                                        data-bs-toggle="tooltip"
                                                                        title="{{ $secondaryClass->is_active ? 'Delete' : 'Activate first to delete' }}">
                                                                        <i class="ki-outline ki-trash fs-5"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <!--end::Card Body-->
                                        </div>
                                    </div>
                                    <!--end::Secondary Class Card-->
                                @empty
                                    <!--begin::Empty State-->
                                    <div class="col-12">
                                        <div class="text-center py-15" id="secondary-classes-empty">
                                            <div class="empty-state-icon">
                                                <i class="ki-outline ki-abstract-26"></i>
                                            </div>
                                            <h4 class="text-gray-800 fw-bold mb-3">No Special Classes Yet</h4>
                                            <p class="text-muted fs-6 mb-6 mw-400px mx-auto">
                                                Create special classes for additional courses or programs under this class.
                                            </p>
                                            @if (auth()->user()->isAdmin())
                                                <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                                                    data-bs-target="#kt_modal_add_special_class">
                                                    <i class="ki-outline ki-plus fs-3 me-1"></i> Add First Special Class
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                    <!--end::Empty State-->
                                @endforelse
                            </div>
                            <!--end::Secondary Classes Grid-->
                        </div>
                        <!--end::Card body-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end:::Secondary Classes Tab pane-->
            </div>
            <!--end:::Tab content-->
        </div>
        <!--end::Content-->
    </div>
    <!--end::Layout-->

    <!--begin::Modal - Add Subject-->
    <div class="modal fade" id="kt_modal_add_subject" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_add_subject_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Create a new subject</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-add-subject-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"> </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-5">
                    <!--begin::Form-->
                    <form id="kt_modal_add_subject_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_subject_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_add_subject_header"
                            data-kt-scroll-wrappers="#kt_modal_add_subject_scroll" data-kt-scroll-offset="300px">
                            {{-- Hidden Input --}}
                            <input type="hidden" name="subject_class" value="{{ $classname->id }}" />
                            <!--begin::Subject name Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Subject Name</label>
                                <!-- end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="subject_name"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. Physics"
                                    required />
                                <!--end::Input-->
                            </div>
                            <!--end::Subject name Input group-->
                            <!--begin::Group Input-->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Academic Group</label>
                                <select name="subject_group" class="form-select form-select-solid"
                                    data-dropdown-parent="#kt_modal_add_subject" data-control="select2"
                                    data-hide-search="true" data-placeholder="Select group" required>
                                    <option></option>
                                    <option value="General" selected>General</option>
                                    @if ((int) $classname->class_numeral >= 9)
                                        <option value="Science">Science</option>
                                        <option value="Commerce">Commerce</option>
                                    @endif
                                </select>
                            </div>
                            <!--end::Group Input-->
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-add-subject-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-add-subject-modal-action="submit">
                                <span class="indicator-label">Submit</span>
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
    <!--end::Modal - Add Subject-->

    <!--begin::Modal - Edit class-->
    <div class="modal fade" id="kt_modal_edit_class" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_edit_class_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold" id="kt_modal_edit_class_title">Edit Class</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-edit-class-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"> </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_class_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_class_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_class_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_class_scroll" data-kt-scroll-offset="300px">
                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Class Name</label>
                                <input type="text" name="class_name_edit"
                                    class="form-control form-control-solid mb-3 mb-lg-0"
                                    placeholder="Write name of the class" required />
                            </div>
                            <!--end::Name Input group-->
                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Class Numeral <span class="text-muted">(Cannot
                                        change)</span></label>
                                <select name="class_numeral_edit" class="form-select form-select-solid"
                                    data-control="select2" data-hide-search="true"
                                    data-dropdown-parent="#kt_modal_edit_class" data-placeholder="Select numeral"
                                    disabled>
                                    <option></option>
                                    @for ($i = 12; $i >= 4; $i--)
                                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">
                                            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <!--end::Name Input group-->
                            <input type="hidden" name="activation_status"
                                value="{{ $classname->isActive() ? 'active' : 'inactive' }}" />
                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Description <span
                                        class="text-muted">(Optional)</span></label>
                                <input type="text" name="description_edit"
                                    class="form-control form-control-solid mb-3 mb-lg-0"
                                    placeholder="Write something about the class" />
                            </div>
                            <!--end::Name Input group-->
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-edit-class-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-edit-class-modal-action="submit">
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
    <!--end::Modal - Edit class-->

    <!--begin::Modal - Add Special Class-->
    <div class="modal fade" id="kt_modal_add_special_class" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_add_special_class_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Add Special Class</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary"
                        data-kt-add-special-class-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"> </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-5">
                    <!--begin::Form-->
                    <form id="kt_modal_add_special_class_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_special_class_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_add_special_class_header"
                            data-kt-scroll-wrappers="#kt_modal_add_special_class_scroll" data-kt-scroll-offset="300px">
                            {{-- Hidden Input --}}
                            <input type="hidden" name="class_id" value="{{ $classname->id }}" />
                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Special Class Name</label>
                                <input type="text" name="name" class="form-control form-control-solid mb-3 mb-lg-0"
                                    placeholder="e.g. ICT Lab" required />
                            </div>
                            <!--end::Name Input group-->
                            <!--begin::Payment Type Input-->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Payment Type</label>
                                <select name="payment_type" class="form-select form-select-solid"
                                    data-dropdown-parent="#kt_modal_add_special_class" data-control="select2"
                                    data-hide-search="true" data-placeholder="Select payment type" required>
                                    <option></option>
                                    <option value="one_time">One Time</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                            <!--end::Payment Type Input-->
                            <!--begin::Fee Amount Input group-->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Fee Amount</label>
                                <input type="number" name="fee_amount"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. 500"
                                    min="0" required />
                            </div>
                            <!--end::Fee Amount Input group-->
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-add-special-class-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary"
                                data-kt-add-special-class-modal-action="submit">
                                <span class="indicator-label">Submit</span>
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
    <!--end::Modal - Add Special Class-->

    <!--begin::Modal - Edit Special Class-->
    <div class="modal fade" id="kt_modal_edit_special_class" tabindex="-1" aria-hidden="true"
        data-bs-backdrop="static" data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_edit_special_class_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold" id="kt_modal_edit_special_class_title">Edit Special Class</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary"
                        data-kt-edit-special-class-modal-action="close">
                        <i class="ki-outline ki-cross fs-1"> </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-5">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_special_class_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_edit_special_class_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_special_class_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_special_class_scroll" data-kt-scroll-offset="300px">
                            {{-- Hidden Input --}}
                            <input type="hidden" name="secondary_class_id" id="edit_secondary_class_id" />
                            <!--begin::Name Input group-->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Special Class Name</label>
                                <input type="text" name="name" id="edit_special_class_name"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. ICT Lab"
                                    required />
                            </div>
                            <!--end::Name Input group-->
                            <!--begin::Payment Type Input (Read-only)-->
                            <div class="fv-row mb-7">
                                <label class="fw-semibold fs-6 mb-2">Payment Type <span class="text-muted">(Cannot
                                        change)</span></label>
                                <input type="text" id="edit_payment_type_display"
                                    class="form-control form-control-solid bg-light-secondary" readonly disabled />
                                <input type="hidden" name="payment_type" id="edit_payment_type" />
                            </div>
                            <!--end::Payment Type Input-->
                            <!--begin::Fee Amount Input group-->
                            <div class="fv-row mb-7">
                                <label class="required fw-semibold fs-6 mb-2">Fee Amount</label>
                                <input type="number" name="fee_amount" id="edit_fee_amount"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="e.g. 500"
                                    min="0" required />
                            </div>
                            <!--end::Fee Amount Input group-->
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-edit-special-class-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary"
                                data-kt-edit-special-class-modal-action="submit">
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
    <!--end::Modal - Edit Special Class-->
@endsection

@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        const routeDeleteSubject = "{{ route('subjects.destroy', ':id') }}";
        const routeSecondaryClasses = "{{ route('secondary-classes.index') }}";
        const routeSecondaryClassShow = "{{ route('secondary-classes.show', ':id') }}";
        const routeSecondaryClassUpdate = "{{ route('secondary-classes.update', ':id') }}";
        const routeSecondaryClassDestroy = "{{ route('secondary-classes.destroy', ':id') }}";
        const isAdminUser = {{ $isAdmin ? 'true' : 'false' }};
    </script>
    <script src="{{ asset('js/classnames/view.js') }}"></script>
    <script>
        $('select[data-control="select2"]').select2({
            width: 'resolve'
        });
    </script>
    <script>
        document.getElementById("academic_menu").classList.add("here", "show");
        document.getElementById("class_link").classList.add("active");
    </script>
@endpush
