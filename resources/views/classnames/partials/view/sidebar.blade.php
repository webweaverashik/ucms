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
                                        class="las la-pen fs-3 me-2"></i> Edit
                                    Class</a>
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
                        <span class="fs-1 fw-bold text-gray-900 me-2">{{ $classname->name }} <i
                                class="text-muted">({{ $classname->class_numeral }})</i></span>
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

            <!--begin::Branch Tabs Section (Admin Only)-->
            @if ($isAdmin && $branches->count() > 0)
                <div class="mb-7">
                    <!--begin::Title-->
                    <h5 class="mb-4">
                        <i class="ki-outline ki-bank fs-4 me-2 text-primary"></i>
                        Branch Statistics
                    </h5>
                    <!--end::Title-->

                    <!--begin::Branch Tabs-->
                    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-4 fs-7" id="branchStatsTabs"
                        role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active py-2 px-3" id="all-branches-tab" data-bs-toggle="tab"
                                href="#all-branches-pane" role="tab" data-branch-id="all">
                                <span class="d-flex align-items-center fw-semibold">
                                    <i class="ki-outline ki-abstract-26 fs-6 me-1"></i>
                                    All
                                    <span class="badge badge-light-primary ms-2" id="badge-all">
                                        {{ $branchStats['all']['total'] ?? 0 }}
                                    </span>
                                </span>
                            </a>
                        </li>
                        @foreach ($branches as $branch)
                            <li class="nav-item" role="presentation">
                                <a class="nav-link py-2 px-3" id="branch-{{ $branch->id }}-tab" data-bs-toggle="tab"
                                    href="#branch-{{ $branch->id }}-pane" role="tab"
                                    data-branch-id="{{ $branch->id }}">
                                    <span class="d-flex align-items-center fw-semibold">
                                        {{ $branch->branch_prefix ?? $branch->branch_name }}
                                        <span class="badge badge-light-primary ms-2" id="badge-{{ $branch->id }}">
                                            {{ $branchStats[$branch->id]['total'] ?? 0 }}
                                        </span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <!--end::Branch Tabs-->

                    <!--begin::Tab Content-->
                    <div class="tab-content" id="branchStatsTabContent">
                        <!--begin::All Branches Pane-->
                        <div class="tab-pane fade show active" id="all-branches-pane" role="tabpanel">
                            @include('classnames.partials.view.branch-stats-card', [
                                'stats' => $branchStats['all'] ?? [
                                    'active' => 0,
                                    'inactive' => 0,
                                    'total' => 0,
                                    'receivable' => 0,
                                ],
                                'branchId' => 'all',
                            ])
                        </div>
                        <!--end::All Branches Pane-->

                        <!--begin::Individual Branch Panes-->
                        @foreach ($branches as $branch)
                            <div class="tab-pane fade" id="branch-{{ $branch->id }}-pane" role="tabpanel">
                                @include('classnames.partials.view.branch-stats-card', [
                                    'stats' => $branchStats[$branch->id] ?? [
                                        'active' => 0,
                                        'inactive' => 0,
                                        'total' => 0,
                                        'receivable' => 0,
                                    ],
                                    'branchId' => $branch->id,
                                    'branchName' => $branch->branch_name,
                                ])
                            </div>
                        @endforeach
                        <!--end::Individual Branch Panes-->
                    </div>
                    <!--end::Tab Content-->
                </div>
            @else
                <!--begin::Non-Admin Stats Section-->
                <div class="mb-7">
                    <!--begin::Title-->
                    <h5 class="mb-4">Statistics</h5>
                    <!--end::Title-->

                    @include('classnames.partials.view.branch-stats-card', [
                        'stats' => $branchStats['current'] ?? [
                            'active' => $classname->active_students_count,
                            'inactive' => $classname->inactive_students_count,
                            'total' => $classname->active_students_count + $classname->inactive_students_count,
                            'receivable' => 0,
                        ],
                        'branchId' => 'current',
                    ])
                </div>
                <!--end::Non-Admin Stats Section-->
            @endif
            <!--end::Branch Tabs Section-->

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
