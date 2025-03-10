<aside id="layout-menu" class="layout-menu menu-vertical menu">
    <div class="app-brand demo">
        <a href="{{ route('dashboard') }}" class="app-brand-link">
            <img src="{{ asset('assets/img/branding/logo.png') }}" alt="" class="app-brand-logo w-px-40">
            <span class="app-brand-text demo menu-text fw-bold ms-3">UCMS</span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
            <i class="icon-base ti tabler-x d-block d-xl-none"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboards -->
        <li class="menu-item active">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-layout-dashboard"></i>
                <div data-i18n="Dashboards">Dashboards</div>
                {{-- <div class="badge text-bg-danger rounded-pill ms-auto">5</div> --}}
            </a>
        </li>

        <!-- Student & Admission Module -->
        <li class="menu-header small">
            <span class="menu-header-text" data-i18n="Student & Admission">Student &amp; Admission</span>
        </li>

        <!-- students menu start -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-users-group"></i>
                <div data-i18n="Student Info">Student Info</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="All Students">All Students</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Subject Assign">Subject Assign</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Promote Students">Promote Students</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Transfer Student">Transfer Student</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- students menu end -->

        <!-- admission menu start -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-school"></i>

                <div data-i18n="Admission">Admission</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="New Admission">New Admission</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Pending Approval">Pending Approval</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Tuition Fee">Tuition Fee</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- admission menu end -->

        <!-- academic menu start -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-vocabulary"></i>

                <div data-i18n="Academic">Academic</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Class">Class</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Shifts">Shifts</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Subjects">Subjects</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- academic menu end -->

        <!-- sheet menu start -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-school"></i>
                <div data-i18n="Sheets">Notes &amp; Sheet</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="All Sheets">All Sheets</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Sheet Distribution">Sheet Distribution</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Sheet Fee">Sheet Fee</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- sheet menu end -->

        <!-- Guardians & Siblings Module -->
        <li class="menu-header small">
            <span class="menu-header-text" data-i18n="Guardians & Siblings">Guardians &amp; Siblings</span>
        </li>

        <!-- guardins menu start -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-friends"></i>
                <div data-i18n="Guardians">Guardians</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="All Guardians">All Guardians</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Add New">Add New</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- guardins menu end -->

        <!-- siblings menu start -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-heart-handshake"></i>
                <div data-i18n="Siblings">Siblings</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="All Siblings">All Siblings</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Add New">Add New</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- siblings menu end -->

        <!-- Payment & Invoice Module -->
        <li class="menu-header small">
            <span class="menu-header-text" data-i18n="Payment & Invoice">Payment &amp; Invoice</span>
        </li>

        <!-- Invoice menu start -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-notes"></i>
                <div data-i18n="Invoice">Invoice</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="All Invoices">All Invoices</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="All Vochers">All Vochers</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- Invoice menu end -->

        <!-- Payment menu start -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-cash-register"></i>
                <div data-i18n="Payment">Payment</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="All Payments">All Payments</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Add Payment">Add Payment</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- Payment menu end -->


        <!-- Teacher Salary Module -->
        <li class="menu-header small">
            <span class="menu-header-text" data-i18n="Teacher Info">Teacher &amp; Info</span>
        </li>

        <!-- Teachers menu start -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-user-scan"></i>
                <div data-i18n="Teachers">Teachers</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="All Teachers">All Teachers</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Add New">Add New</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- Teachers menu end -->

        <!-- salary menu start -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-calendar-dollar"></i>
                <div data-i18n="Salary">Salary</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Salary Satements">Salary Satements</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Prepare New">Prepare New</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- salary menu end -->


        <!-- Others Module -->
        <li class="menu-header small">
            <span class="menu-header-text" data-i18n="Settings & Reporting">Settings &amp; Reporting</span>
        </li>

        <!-- report menu start -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-report"></i>
                <div data-i18n="Reports">Reports</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Salary Satements">Salary Satements</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Prepare New">Prepare New</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- report menu end -->

        <!-- users menu start -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-users"></i>
                <div data-i18n="Users">Users</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="All Users">All Users</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Roles & Permissions">Roles &amp; Permissions</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- users menu end -->

        <!-- settings menu start -->
        <!-- Dashboards -->
        <li class="menu-item">
            <a href="#" class="menu-link">
                <i class="menu-icon icon-base ti tabler-settings"></i>
                <div data-i18n="Settings">Settings</div>
                {{-- <div class="badge text-bg-danger rounded-pill ms-auto">5</div> --}}
            </a>
        </li>
        <!-- settings menu end -->


    </ul>
</aside>

<div class="menu-mobile-toggler d-xl-none rounded-1">
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
        <i class="ti tabler-menu icon-base"></i>
        <i class="ti tabler-chevron-right icon-base"></i>
    </a>
</div>
