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
                            <i class="ki-outline ki-dots-horizontal fs-3">
                            </i>
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
            <!--begin::Section-->
            <div class="mb-7">
                <!--begin::Title-->
                <h5 class="mb-4">Statistics</h5>
                <!--end::Title-->
                <!--begin::Stats Grid-->
                <div class="row g-3">
                    <div class="col-6">
                        <div class="stats-mini-card">
                            <div class="stats-value text-primary" id="stats-total-students">
                                {{ $classname->active_students_count + $classname->inactive_students_count }}</div>
                            <div class="stats-label">Total Students</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stats-mini-card">
                            <div class="stats-value text-success" id="stats-active-students">
                                {{ $classname->active_students_count }}</div>
                            <div class="stats-label">Active Students</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stats-mini-card">
                            <div class="stats-value text-danger" id="stats-inactive-students">
                                {{ $classname->inactive_students_count }}</div>
                            <div class="stats-label">Inactive Students</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stats-mini-card">
                            <div class="stats-value text-info" id="stats-total-subjects">{{ $totalSubjects }}</div>
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