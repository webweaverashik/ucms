@extends('layouts.app')

@section('title', 'All Classes')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Classes
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Academic</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Class</li>
        </ul>
    </div>
@endsection

@push('page-css')
    <style>
        /* Batch Tab Styles */
        .batch-tabs {
            display: flex;
            gap: 2px;
            border-bottom: 1px solid var(--bs-gray-200);
            margin-bottom: 1rem;
            overflow-x: auto;
        }

        .batch-tab {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--bs-gray-600);
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .batch-tab:hover {
            color: var(--bs-gray-800);
            background: var(--bs-gray-100);
        }

        .batch-tab.active {
            color: var(--bs-white);
            background: linear-gradient(135deg, var(--bs-primary) 0%, #1d4ed8 100%);
            border-radius: 0.375rem 0.375rem 0 0;
            border-bottom-color: var(--bs-primary);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            text-align: center;
        }

        .stat-item {
            padding: 0.75rem 0.5rem;
            border-radius: 0.5rem;
        }

        .stat-item.active {
            background: var(--bs-success-light);
        }

        .stat-item.inactive {
            background: var(--bs-danger-light);
        }

        .stat-item.total {
            background: var(--bs-gray-100);
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--bs-gray-500);
        }

        /* Card Description
        .card-description {
            min-height: 40px;
            color: var(--bs-gray-600);
            font-size: 1rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        } */

        /* Alumni Banner Gradient */
        .alumni-banner {
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%);
            border: 1px dashed var(--bs-primary);
        }

        /* Active Filters Bar */
        .active-filters-bar {
            display: none;
            align-items: center;
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--bs-gray-200);
        }

        .active-filters-bar.show {
            display: flex;
        }

        /* Empty State */
        .empty-state-icon {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bs-gray-100);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
        }
    </style>
@endpush

@section('content')
    @php
        $canEditClass = auth()->user()->can('classes.edit');
        $canDeleteClass = auth()->user()->can('classes.delete');
    @endphp

    <!--begin::Stats Overview-->
    <div class="row g-5 g-xl-8 mb-8">
        <!--begin::Col - Total Classes-->
        <div class="col-6 col-lg-3">
            <div class="card card-flush h-100 hover-elevate-up shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="symbol symbol-50px me-4">
                        <div class="symbol-label bg-light-primary">
                            <i class="ki-outline ki-abstract-26 fs-2x text-primary"></i>
                        </div>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="text-gray-500 fs-7 fw-semibold">Total Classes</span>
                        <span class="fs-2x fw-bold text-gray-900">{{ $stats['total_classes'] }}</span>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Col-->

        <!--begin::Col - Active Classes-->
        <div class="col-6 col-lg-3">
            <div class="card card-flush h-100 hover-elevate-up shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="symbol symbol-50px me-4">
                        <div class="symbol-label bg-light-success">
                            <i class="ki-outline ki-verify fs-2x text-success"></i>
                        </div>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="text-gray-500 fs-7 fw-semibold">Active Classes</span>
                        <span class="fs-2x fw-bold text-gray-900">{{ $stats['active_classes'] }}</span>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Col-->

        <!--begin::Col - Regular Students-->
        <div class="col-6 col-lg-3">
            <div class="card card-flush h-100 hover-elevate-up shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="symbol symbol-50px me-4">
                        <div class="symbol-label bg-light-info">
                            <i class="ki-outline ki-people fs-2x text-info"></i>
                        </div>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="text-gray-500 fs-7 fw-semibold">Regular Students</span>
                        <span class="fs-2x fw-bold text-gray-900">{{ number_format($stats['regular_students']) }}</span>
                        <span class="text-gray-400 fs-8">From active classes only</span>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Col-->

        <!--begin::Col - Active Students-->
        <div class="col-6 col-lg-3">
            <div class="card card-flush h-100 hover-elevate-up shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="symbol symbol-50px me-4">
                        <div class="symbol-label bg-light-warning">
                            <i class="ki-outline ki-user-tick fs-2x text-warning"></i>
                        </div>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="text-gray-500 fs-7 fw-semibold">Active Students</span>
                        <span class="fs-2x fw-bold text-gray-900">{{ number_format($stats['active_students']) }}</span>
                        <span class="text-gray-400 fs-8">From active classes only</span>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Col-->
    </div>
    <!--end::Stats Overview-->

    <!--begin::Alumni Overview Banner-->
    @if ($stats['inactive_classes'] > 0)
        <div class="card card-flush alumni-banner mb-8">
            <div class="card-body py-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="symbol symbol-45px">
                            <div class="symbol-label bg-primary">
                                <i class="ki-outline ki-teacher fs-2 text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-primary fw-bold mb-0">Alumni Overview</h4>
                            <span class="text-primary fs-7 opacity-75">Students from inactive classes</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-6">
                        <div class="text-center">
                            <div class="fs-2x fw-bold text-primary">{{ number_format($stats['alumni_students']) }}</div>
                            <div class="text-primary fs-8 fw-semibold opacity-75">Total Alumni</div>
                        </div>
                        <div class="vr bg-primary opacity-25" style="width: 1px; height: 40px;"></div>
                        <div class="text-center">
                            <div class="fs-2x fw-bold text-primary">{{ $stats['inactive_classes'] }}</div>
                            <div class="text-primary fs-8 fw-semibold opacity-75">Inactive Classes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!--end::Alumni Overview Banner-->

    <!--begin::Toolbar Card-->
    <div class="card card-flush shadow-sm mb-6">
        <div class="card-body py-5">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <!--begin::Search-->
                <div class="position-relative w-100 w-lg-350px">
                    <i
                        class="ki-outline ki-magnifier fs-3 position-absolute top-50 translate-middle-y ms-4 text-gray-500"></i>
                    <input type="text" id="class-search-input" class="form-control form-control-solid ps-12"
                        placeholder="Search by class name...">
                </div>
                <!--end::Search-->

                <!--begin::Right Controls-->
                <div class="d-flex align-items-center gap-3 flex-wrap flex-lg-nowrap">
                    <!--begin::Numeral Filter-->
                    <div class="w-100 w-lg-175px">
                        <select id="numeral-filter" class="form-select form-select-solid" data-control="select2"
                            data-placeholder="All Numerals" data-allow-clear="true" data-hide-search="true">
                            <option></option>
                            @foreach (range(4, 12) as $i)
                                <option value="{{ sprintf('%02d', $i) }}">Class {{ sprintf('%02d', $i) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!--end::Numeral Filter-->

                    @can('classes.create')
                        <!--begin::Add Button-->
                        <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_class">
                            <i class="ki-outline ki-plus fs-4 me-1"></i> Add Class
                        </a>
                        <!--end::Add Button-->
                    @endcan
                </div>
                <!--end::Right Controls-->
            </div>

            <!--begin::Active Filters-->
            <div id="active-filters" class="active-filters-bar">
                <span class="text-gray-500 fs-7 me-2">Showing:</span>
                <span id="results-count" class="badge badge-light-primary fs-7 fw-semibold"></span>
                <button id="clear-filters" class="btn btn-sm btn-link text-danger ms-auto p-0">
                    <i class="ki-outline ki-cross fs-5 me-1"></i>Clear filters
                </button>
            </div>
            <!--end::Active Filters-->
        </div>
    </div>
    <!--end::Toolbar Card-->

    <!--begin::Tabs Navigation-->
    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-5 fw-semibold mb-8">
        <li class="nav-item">
            <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_active_classnames_tab">
                <i class="ki-outline ki-verify fs-4 me-2"></i>Active Classes
                <span class="badge badge-light-primary ms-2" id="active-tab-count">{{ $active_classes->count() }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_inactive_classnames_tab">
                <i class="ki-outline ki-minus-circle fs-4 me-2"></i>Inactive Classes (Alumni)
                <span class="badge badge-light-danger ms-2"
                    id="inactive-tab-count">{{ $inactive_classes->count() }}</span>
            </a>
        </li>
    </ul>
    <!--end::Tabs Navigation-->

    <!--begin::Tab Content-->
    <div class="tab-content" id="classTabContent">
        <!--begin::Active Classes Tab-->
        <div class="tab-pane fade show active" id="kt_active_classnames_tab" role="tabpanel">
            <div id="active_classes_container" class="row g-6 g-xl-9">
                @forelse ($active_classes as $classname)
                    @include('classnames.partials.class-card', [
                        'classname' => $classname,
                        'isActive' => true,
                    ])
                @empty
                    <div class="col-12 empty-state-original">
                        @include('classnames.partials.empty-state', ['type' => 'active'])
                    </div>
                @endforelse
            </div>
        </div>
        <!--end::Active Classes Tab-->

        <!--begin::Inactive Classes Tab-->
        <div class="tab-pane fade" id="kt_inactive_classnames_tab" role="tabpanel">
            <div id="inactive_classes_container" class="row g-6 g-xl-9">
                @forelse ($inactive_classes as $classname)
                    @include('classnames.partials.class-card', [
                        'classname' => $classname,
                        'isActive' => false,
                    ])
                @empty
                    <div class="col-12 empty-state-original">
                        @include('classnames.partials.empty-state', ['type' => 'inactive'])
                    </div>
                @endforelse
            </div>
        </div>
        <!--end::Inactive Classes Tab-->
    </div>
    <!--end::Tab Content-->

    <!--begin::Modal - Add Class-->
    @include('classnames.partials.modal-add')
    <!--end::Modal - Add Class-->

    <!--begin::Modal - Edit Class-->
    @include('classnames.partials.modal-edit')
    <!--end::Modal - Edit Class-->
@endsection

@push('page-js')
    <script>
        // Route definitions
        const routeStoreClass = "{{ route('classnames.store') }}";
        const routeDeleteClass = "{{ route('classnames.destroy', ':id') }}";
        const routeToggleStatus = "{{ route('classnames.update', ':id') }}";
        const routeGetClassData = "{{ route('classnames.ajax', ':class') }}";
    </script>
    <script src="{{ asset('js/classnames/index.js') }}"></script>
    <script>
        // Sidebar active state
        document.getElementById("academic_menu")?.classList.add("here", "show");
        document.getElementById("class_link")?.classList.add("active");
    </script>
@endpush
