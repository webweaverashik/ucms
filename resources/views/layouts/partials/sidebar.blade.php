<div id="kt_app_sidebar" class="app-sidebar  flex-column " data-kt-drawer="true" data-kt-drawer-name="app-sidebar"
    data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px"
    data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
    <!--begin::Logo-->
    <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
        <!--begin::Logo image-->
        <a href="{{ route('dashboard') }}">
            <img alt="Logo" src="{{ asset('assets/img/logo-dark.png') }}" class="h-50px app-sidebar-logo-default" />
            <img alt="Logo" src="{{ asset('assets/img/icon.png') }}" class="h-20px app-sidebar-logo-minimize" />
        </a>
        <!--end::Logo image-->

        <!--begin::Sidebar toggle-->
        <!--begin::Minimized sidebar setup:
            if (isset($_COOKIE["sidebar_minimize_state"]) && $_COOKIE["sidebar_minimize_state"] === "on") {
                1. "src/js/layout/sidebar.js" adds "sidebar_minimize_state" cookie value to save the sidebar minimize state.
                2. Set data-kt-app-sidebar-minimize="on" attribute for body tag.
                3. Set data-kt-toggle-state="active" attribute to the toggle element with "kt_app_sidebar_toggle" id.
                4. Add "active" class to to sidebar toggle element with "kt_app_sidebar_toggle" id.
            }
        -->
        <div id="kt_app_sidebar_toggle"
            class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary h-30px w-30px position-absolute top-50 start-100 translate-middle rotate "
            data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body"
            data-kt-toggle-name="app-sidebar-minimize">
            <i class="ki-outline ki-black-left-line fs-3 rotate-180"></i>
        </div>
        <!--end::Sidebar toggle-->
    </div>
    <!--end::Logo-->

    <!--begin::sidebar menu-->
    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <!--begin::Menu wrapper-->
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper">
            <!--begin::Scroll wrapper-->
            <div id="kt_app_sidebar_menu_scroll" class="scroll-y my-5 mx-3" data-kt-scroll="true"
                data-kt-scroll-activate="true" data-kt-scroll-height="auto"
                data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer"
                data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px"
                data-kt-scroll-save-state="true">
                <!--begin::Menu-->
                <div class="menu menu-column menu-rounded menu-sub-indention fw-semibold fs-6" id="#kt_app_sidebar_menu"
                    data-kt-menu="true" data-kt-menu-expand="false">

                    <!--begin:Dashboard Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link" href="{{ route('dashboard') }}" id="dashboard_link">
                            <span class="menu-icon">
                                <i class="ki-outline ki-chart-pie-4 fs-2"></i>
                            </span>
                            <span class="menu-title">Dashboard</span>
                        </a>
                        <!--end:Dashboard Menu link-->
                    </div>
                    <!--end:Dashboard Menu item-->

                    {{-- ----------------- Student & Admission Modules ----------------- --}}
                    @canany(['students.view', 'guardians.view', 'siblings.view'])
                        <!--begin:Student Info Menu item-->
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="student_info_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    {{-- <i class="ki-outline ki-address-book fs-2"></i> --}}
                                    <i class="las la-graduation-cap fs-1"></i>
                                </span>
                                <span class="menu-title">Student Info</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('students.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="all_students_link"
                                            href="{{ route('students.index') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span class="menu-title">All
                                                Students</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan



                                <!--begin:Guardians Menu item-->
                                @can('guardians.view')
                                    <div class="menu-item">
                                        <a class="menu-link" id="guardians_link" href="{{ route('guardians.index') }}"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Guardians</span></a>
                                    </div>
                                @endcan
                                <!--end:Guardians Menu item-->

                                <!--begin:Siblings Menu item-->
                                @can('siblings.view')
                                    <div class="menu-item">
                                        <a class="menu-link" id="siblings_link" href="{{ route('siblings.index') }}"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Siblings</span></a>
                                    </div>
                                @endcan
                                <!--end:Siblings Menu item-->

                            </div>
                            <!--end:Menu sub-->
                        </div>
                        <!--end: Student Info Menu item-->
                    @endcanany


                    @canany(['students.create', 'students.approve', 'students.promote', 'students.transfer'])
                        <!--begin:Admission Menu item-->
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="admission_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    {{-- <i class="fa-solid fa-building-columns fs-2"></i> --}}
                                    <i class="ki-outline ki-bank fs-2"></i>
                                </span>
                                <span class="menu-title">Admission</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('students.create')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="new_admission_link"
                                            href="{{ route('students.create') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span class="menu-title">New
                                                Admission</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                @can('students.approve')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="pending_approval_link"
                                            href="{{ route('students.pending') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span class="menu-title">Pending
                                                Approval</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                @can('students.promote')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="promote_students_link"
                                            href="{{ route('students.promote') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span class="menu-title">Promote
                                                Students</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                @can('students.transfer')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="transfer_students_link"
                                            href="{{ route('students.transfer') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span class="menu-title">Transfer
                                                Students</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan
                            </div>
                            <!--end:Menu sub-->
                        </div>
                        <!--end: Admission Menu item-->
                    @endcanany


                    <!--begin:Academic Menu item-->
                    @canany(['institutions.view', 'classes.view', 'shifts.manage'])
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="academic_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-book fs-2"></i>
                                    {{-- <i class="fa-solid fa-school fs-2"></i> --}}
                                </span>
                                <span class="menu-title">Academic</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('institutions.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="institutions_link"
                                            href="{{ route('institutions.index') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Institutions</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                @can('classes.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="class_link"
                                            href="{{ route('classnames.index') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Class</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                @can('shifts.manage')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="shifts_link" href="{{ route('shifts.index') }}"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Shifts</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcanany
                    <!--end: Academic Menu item-->


                    <!--begin:Notes & Sheets Menu item-->
                    @canany(['sheets.view', 'sheets.distribute'])
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="notes_sheets_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-note-2 fs-2"></i>
                                    {{-- <i class="fa-solid fa-book fs-2"></i> --}}
                                </span>
                                <span class="menu-title">Notes & Sheets</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('sheets.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="all_sheets_link" href="{{ route('sheets.index') }}"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">All
                                                Sheets</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->


                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="sheet_payments_link" href="{{ route('sheet.payments') }}">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot">
                                                </span>
                                            </span>
                                            <span class="menu-title">Sheet Payments</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                @can('notes.distribute')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="notes_distribution_link"
                                            href="{{ route('notes.distribution') }}"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span class="menu-title">Notes
                                                Distribution</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcanany
                    <!--end: Notes & Sheets Menu item-->


                    <!--begin:Invoices & Transactions-->
                    @canany(['invoices.view', 'transactions.view'])
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="payments_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-dollar fs-2"></i>
                                </span>
                                <span class="menu-title">Payments</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('invoices.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" href="{{ route('invoices.index') }}" id="invoices_link">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Invoices</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                @can('transactions.view')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" href="{{ route('transactions.index') }}"
                                            id="transactions_link">
                                            <span class="menu-bullet">
                                                <span class="bullet bullet-dot">
                                                </span>
                                            </span>
                                            <span class="menu-title">Transactions</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan
                            </div>
                            <!--end:Menu sub-->
                        </div>
                    @endcanany
                    <!--end:Invoices & Transactions-->


                    {{-- ----------------- Teachers Modules ----------------- --}}
                    <!--begin:Teachers Modules-->
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="teachers_menu">
                        <!--begin:Menu link-->
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="fa-solid fa-person-chalkboard fs-3"></i>
                            </span>
                            <span class="menu-title">Teachers</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <!--end:Menu link-->

                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-accordion">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="{{ route('teachers.index') }}" id="teachers_link"><span
                                        class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                        class="menu-title">Teachers</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->

                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="{{ route('teachers.index') }}" id="salary_tracking_link">
                                    <span class="menu-bullet">
                                        <span class="bullet bullet-dot">
                                        </span>
                                    </span>
                                    <span class="menu-title">Salary Tracking</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end: Teachers Modules-->


                    <!--begin:Reports Menu-->
                    @can('reports.view')
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link" href="{{ route('reports.index') }}" id="reports_link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-filter-tablet fs-2"></i>
                                </span>
                                <span class="menu-title">Reports</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                    @endcan
                    <!--end:Reports Menu-->

                    <!--begin:Settings Info Menu Heading-->
                    @canany(['users.manage', 'settings.manage'])
                        <!--begin:Settings Modules-->
                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion" id="settings_menu">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-setting-2 fs-2"></i>
                                </span>
                                <span class="menu-title">Settings</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->

                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion">
                                @can('users.manage')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link" id="users_link" href="{{ route('users.index') }}"><span
                                                class="menu-bullet"><span class="bullet bullet-dot"></span></span><span
                                                class="menu-title">Users</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan

                                <!--begin:Menu item-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" id="branch_link" href="#">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot">
                                            </span>
                                        </span>
                                        <span class="menu-title">Branch</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->

                                <!--begin:Roles-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" id="roles_link" href="#">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot">
                                            </span>
                                        </span>
                                        <span class="menu-title">Roles</span>
                                    </a>
                                    <!--end:Roles-->
                                </div>
                                <!--end:Menu item-->
                            </div>
                            <!--end:Menu sub-->
                        </div>
                        <!--end: Settings Modules-->
                    @endcanany
                    <!--end:Settings Info Menu Heading-->

                </div>
                <!--end::Menu-->
            </div>
            <!--end::Scroll wrapper-->
        </div>
        <!--end::Menu wrapper-->
    </div>
    <!--end::sidebar menu-->

    <!--begin::Footer-->
    <div class="app-sidebar-footer flex-column-auto pt-2 pb-6 px-6" id="kt_app_sidebar_footer">
        <a href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            class="btn btn-flex flex-center btn-custom btn-danger overflow-hidden text-nowrap px-0 h-40px w-100"
            data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss-="click" title="Logout">
            <span class="btn-label">
                Logout
            </span>
            <i class="ki-outline ki-document btn-icon fs-2 m-0"></i>
        </a>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
        </form>
    </div>
    <!--end::Footer-->
</div>
