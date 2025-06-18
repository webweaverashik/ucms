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
                    @hasanyrole('admin|manager|accountant')
                        <!--begin:Student Menu Heading-->
                        <div class="menu-item pt-5">
                            <!--begin:Menu content-->
                            <div class="menu-content"><span class="menu-heading fw-bold text-uppercase fs-7">Student &
                                    Admission</span>
                            </div>
                            <!--end:Menu content-->
                        </div>
                        <!--end:Student Menu Heading-->
                    @endhasanyrole


                    @canany(['students.view', 'students.promote', 'students.transfer'])
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

                                @can('students.promote')
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link--><a class="menu-link" id="promote_students_link"
                                            href="#"><span class="menu-bullet"><span
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
                                            href="#"><span class="menu-bullet"><span
                                                    class="bullet bullet-dot"></span></span><span class="menu-title">Transfer
                                                Students</span></a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                @endcan
                            </div>
                            <!--end:Menu sub-->
                        </div>
                        <!--end: Student Info Menu item-->
                    @endcanany


                    @canany(['students.create', 'students.approve'])
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
                            </div>
                            <!--end:Menu sub-->
                        </div>
                        <!--end: Admission Menu item-->
                    @endcanany


                    @canany(['institutions.manage', 'classes.manage', 'shifts.manage'])
                        <!--begin:Academic Menu item-->
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
                                @can('institutions.manage')
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

                                @can('classes.manage')
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
                        <!--end: Academic Menu item-->
                    @endcanany


                    @canany(['sheets.view', 'sheets.distribute'])
                        <!--begin:Notes & Sheets Menu item-->
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
                        <!--end: Notes & Sheets Menu item-->
                    @endcanany


                    @can('guardians.view')
                        <!--begin:Guardians Menu item-->
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link" href="{{ route('guardians.index') }}" id="guardians_link">
                                <span class="menu-icon">
                                    {{-- <i class="ki-outline ki-calendar-8 fs-2"></i> --}}
                                    <i class="fa-solid fa-hands-holding-child fs-2"></i>
                                </span>
                                <span class="menu-title">Guardians</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                        <!--end:Guardians Menu item-->
                    @endcan


                    @can('siblings.view')
                        <!--begin:Siblings Menu item-->
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link" href="{{ route('siblings.index') }}" id="siblings_link">
                                <span class="menu-icon">
                                    {{-- <i class="ki-outline ki-calendar-8 fs-2"></i> --}}
                                    <i class="fa-solid fa-children fs-2"></i>
                                </span>
                                <span class="menu-title">Siblings</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                        <!--end:Siblings Menu item-->
                    @endcan


                    @canany(['invoices.view', 'transactions.view'])
                        {{-- ----------------- Payment & Invoice Modules ----------------- --}}
                        <!--begin:Payment & Invoice Menu Heading-->
                        <div class="menu-item pt-5">
                            <!--begin:Menu content-->
                            <div class="menu-content"><span class="menu-heading fw-bold text-uppercase fs-7">Payment &
                                    Invoice</span>
                            </div>
                            <!--end:Menu content-->
                        </div>
                        <!--end:Payment & Invoice Menu Heading-->
                    @endcanany

                    @can('invoices.view')
                        <!--begin:Invoices Menu item-->
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link" href="{{ route('invoices.index') }}" id="invoices_link">
                                <span class="menu-icon">
                                    <i class="las la-file-invoice-dollar fs-2"></i>
                                    {{-- <i class="fa-solid fa-file-invoice-dollar fs-2"></i> --}}
                                </span>
                                <span class="menu-title">Invoices</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                        <!--end:Invoices Menu item-->
                    @endcan


                    @can('transactions.view')
                        <!--begin:Payments Menu item-->
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link" href="{{ route('transactions.index') }}" id="transactions_link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-dollar fs-2"></i>
                                    {{-- <i class="fa-solid fa-comments-dollar fs-2"></i> --}}
                                </span>
                                <span class="menu-title">Transactions</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                        <!--end:Payments Menu item-->
                    @endcan


                    {{-- ----------------- Teachers Modules ----------------- --}}
                    <!--begin:Teachers Info Menu Heading-->
                    <div class="menu-item pt-5">
                        <!--begin:Menu content-->
                        <div class="menu-content"><span class="menu-heading fw-bold text-uppercase fs-7">Teachers
                                Info</span>
                        </div>
                        <!--end:Menu content-->
                    </div>
                    <!--end:Teachers Info Menu Heading-->

                    <!--begin:Teachers Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link" href="{{ route('teachers.index') }}" id="teachers_link">
                            <span class="menu-icon">
                                {{-- <i class="ki-outline ki-calendar-8 fs-2"></i> --}}
                                <i class="fa-solid fa-person-chalkboard fs-2"></i>
                            </span>
                            <span class="menu-title">Teachers</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Teachers Menu item-->


                    <!--begin:Salary Tracking Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link" href="{{ route('teachers.index') }}" id="salary_tracking_link">
                            <span class="menu-icon">
                                <i class="las la-money-check-alt fs-1"></i>
                                {{-- <i class="fa-solid fa-money-check-dollar fs-2"></i> --}}
                            </span>
                            <span class="menu-title">Salary Tracking</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Salary Tracking Menu item-->

                    @canany(['users.manage', 'settings.manage'])
                        {{-- ----------------- Settings Modules ----------------- --}}
                        <!--begin:Systems Info Menu Heading-->
                        <div class="menu-item pt-5">
                            <!--begin:Menu content-->
                            <div class="menu-content"><span
                                    class="menu-heading fw-bold text-uppercase fs-7">Systems</span>
                            </div>
                            <!--end:Menu content-->
                        </div>
                        <!--end:Systems Info Menu Heading-->

                        @can('users.manage')
                            <!--begin:Users Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="{{ route('users.index') }}" id="users_link">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-profile-user fs-2"></i>
                                    </span>
                                    <span class="menu-title">Users</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Users Menu item-->
                        @endcan

                        @can('settings.manage')
                            <!--begin:Settings Tracking Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="#" id="settings_link">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-setting-2 fs-2"></i>
                                    </span>
                                    <span class="menu-title">Settings</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Settings Tracking Menu item-->
                        @endcan
                    @endcan
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
