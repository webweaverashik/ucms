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
        <li class="menu-item active open">
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
                {{-- <i class="menu-icon fa-solid fa-graduation-cap"></i> --}}
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
                        <div data-i18n="Guardians">Guardians</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Promote Students">Promote Students</div>
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
                        <div data-i18n="Fee Allocation">Fee Allocation</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- admission menu end -->

        <!-- academic menu start -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-building-bank"></i>
                <div data-i18n="Academic Info">Academic Info</div>
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
                <i class="menu-icon icon-base ti tabler-books"></i>
                <div data-i18n="Academic Info">Notes &amp; Sheet</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Class">All Sheets</div>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Shifts">Sheet Distribution</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- sheet menu end -->


        <!-- Payment & Invoice Module -->
        <li class="menu-header small">
            <span class="menu-header-text" data-i18n="Payment & Invoice">Payment &amp; Invoice</span>
        </li>

        <!-- Invoice menu start -->
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon icon-base ti tabler-file-invoice"></i>
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
                <i class="menu-icon icon-base ti tabler-receipt-dollar"></i>
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
    </ul>
</aside>

<div class="menu-mobile-toggler d-xl-none rounded-1">
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
        <i class="ti tabler-menu icon-base"></i>
        <i class="ti tabler-chevron-right icon-base"></i>
    </a>
</div>
