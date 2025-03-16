<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="assets/images/logo.png" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="assets/images/logo.png" alt="" height="17">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-light">
            <span class="logo-sm">
                <img src="assets/images/logo.png" alt="" height="25">
            </span>
            <span class="logo-lg">
                <img src="assets/images/logo.png" alt="" height="50">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('dashboard') }}" id="dashboard_menu">
                        <i class="mdi mdi-view-dashboard-outline"></i> <span data-key="t-dashboard">Dashboard</span>
                    </a>
                </li> <!-- end Dashboard Menu -->


                <!-- Student & Admission Module -->
                <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-student-n-admission">Student &amp;
                        Admission</span>
                </li>

                <!-- students menu start -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarStudentInfo" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarStudentInfo" id="student_info_menu">
                        <i class="mdi mdi-account-school-outline"></i> <span data-key="t-student-info">Student
                            Info</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarStudentInfo">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('students.index') }}" class="nav-link" data-key="All Students">All
                                    Students</a>
                            </li>
                            <li class="nav-item">
                                <a href="landing.html" class="nav-link" data-key="t-subject-assign">Subject Assign</a>
                            </li>
                            <li class="nav-item">
                                <a href="nft-landing.html" class="nav-link" data-key="t-promote-students">Promote
                                    Students</a>
                            </li>
                            <li class="nav-item">
                                <a href="job-landing.html" class="nav-link" data-key="t-transfer-students">Transfer
                                    Student</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <!-- students menu end -->

                <!-- admission menu start -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarAdmission" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarAdmission" id="admission_menu">
                        <i class="ri-bank-line"></i> <span data-key="t-admission">Admission</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarAdmission">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('students.create') }}" class="nav-link" data-key="New Admission">New
                                    Admission</a>
                            </li>
                            <li class="nav-item">
                                <a href="landing.html" class="nav-link" data-key="t-pending-approval">Pending
                                    Approval</a>
                            </li>
                            <li class="nav-item">
                                <a href="nft-landing.html" class="nav-link" data-key="t-tuition-fee">Tuition Fee</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <!-- admission menu end -->

                <!-- academic menu start -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarAcademic" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarAcademic" id="admission_menu">
                        <i class="las la-school"></i> <span data-key="t-academic">Academic</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarAcademic">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('students.create') }}" class="nav-link"
                                    data-key="t-new-admission">New
                                    Admission</a>
                            </li>
                            <li class="nav-item">
                                <a href="landing.html" class="nav-link" data-key="t-pending-approval">Pending
                                    Approval</a>
                            </li>
                            <li class="nav-item">
                                <a href="nft-landing.html" class="nav-link" data-key="t-tuition-fee">Tuition Fee</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <!-- academic menu end -->

                <!-- sheet menu start -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarNotesSheets" data-bs-toggle="collapse"
                        role="button" aria-expanded="false" aria-controls="sidebarNotesSheets"
                        id="note_sheet_menu">
                        <i class="ri-git-repository-line"></i> <span data-key="Notes & Sheets">Notes &amp;
                            Sheets</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarNotesSheets">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('students.create') }}" class="nav-link" data-key="All Sheets">All Sheets</a>
                            </li>
                            <li class="nav-item">
                                <a href="landing.html" class="nav-link" data-key="Sheet Distribution">Sheet
                                    Distribution</a>
                            </li>
                            <li class="nav-item">
                                <a href="nft-landing.html" class="nav-link" data-key="Sheet Fee">Sheet Fee</a>
                            </li>
                        </ul>
                    </div>
                </li>
                <!-- sheet menu end -->

                <!-- guardians menu start -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('guardians.index') }}" id="guardians_menu">
                        <i class="mdi mdi-human-male-female-child"></i> <span data-key="Guardians">Guardians</span>
                    </a>
                </li>
                <!-- guardians menu end -->

                <!-- siblings menu start -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#" id="siblings_menu">
                        <i class='bx bx-child'></i> <span data-key="Siblings">Siblings</span>
                    </a>
                </li>
                <!-- siblings menu end -->


                <!-- Payment & Invoice Module -->
                <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="Payment & Invoice">Payment
                        &amp;
                        Invoice</span>
                </li>

                <!-- invoice menu start -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#" id="invoices_menu">
                        <i class="las la-file-invoice-dollar"></i> <span data-key="Invoices">Invoices</span>
                    </a>
                </li>
                <!-- invoice menu end -->

                <!-- invoice menu start -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#" id="invoices_menu">
                        <i class="las la-coins"></i> <span data-key="Payments">Payments</span>
                    </a>
                </li>
                <!-- invoice menu end -->


                <!-- Teacher & Salary Module -->
                <li class="menu-title">
                    <i class="ri-more-fill"></i> <span data-key="Teachers Info">Teachers Info</span>
                </li>

                <!-- invoice menu start -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('teachers.index') }}" id="teachers_menu">
                        <i class="las la-chalkboard-teacher"></i> <span data-key="Teachers">Teachers</span>
                    </a>
                </li>
                <!-- invoice menu end -->

                <!-- invoice menu start -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#" id="salary_menu">
                        <i class="las la-money-bill-wave"></i> <span data-key="Salary Tracking">Salary Tracking</span>
                    </a>
                </li>
                <!-- invoice menu end -->

                <!-- Settings Module -->
                <li class="menu-title">
                    <i class="ri-more-fill"></i> <span data-key="Others">Others</span>
                </li>

                <!-- users menu start -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('users.index') }}" id="users_menu">
                        <i class="las la-users-cog"></i> <span data-key="Users">Users</span>
                    </a>
                </li>
                <!-- users menu end -->

                <!-- settings menu start -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#" id="settings_menu">
                        <i class="las la-cog"></i> <span data-key="Settings">Settings</span>
                    </a>
                </li>
                <!-- settings menu end -->

                <!-- sms menu start -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#" id="sms_menu">
                       <i class="las la-sms"></i> <span data-key="SMS Log">SMS Log</span>
                    </a>
                </li>
                <!-- sms menu end -->
            </ul>
        </div>
        <!-- Sidebar -->
    </div>

    <div class="sidebar-background"></div>
</div>
