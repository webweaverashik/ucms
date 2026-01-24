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
        <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_secondary_classnames_tab"><i
                class="ki-outline ki-teacher fs-3 me-2"></i>Special Class</a>
    </li>
    <!--end:::Tab item-->
    <!--begin:::Tab item-->
    @if (($createClass || $manageSubjects) && $classname->isActive())
        <li class="nav-item ms-auto">
            <!--begin::Action menu-->
            <a href="#" class="btn btn-primary ps-7" data-kt-menu-trigger="click" data-kt-menu-attach="parent"
                data-kt-menu-placement="bottom-end">Actions
                <i class="ki-outline ki-down fs-2 me-0"></i></a>
            <!--begin::Menu-->
            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg-light-primary fw-semibold py-4 w-250px fs-6"
                data-kt-menu="true">
                @if ($manageSubjects && $classname->isActive())
                    <!--begin::Menu item-->
                    <div class="menu-item px-5 my-1">
                        <a href="#" class="menu-link px-5 text-hover-primary" data-bs-toggle="modal"
                            data-bs-target="#kt_modal_add_subject"><i class="ki-outline ki-book-open fs-2 me-2"></i>Add
                            Subject
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