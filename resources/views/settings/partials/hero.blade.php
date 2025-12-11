<!--begin::Hero card-->
<div class="card mb-8">
    <!--begin::Hero body-->
    <div class="card-body flex-column p-5">
        <!--begin::Hero nav-->
        <div class="card-rounded d-flex flex-stack flex-wrap">
            <!--begin::Nav-->
            <ul class="nav flex-wrap border-transparent fw-bold">
                <!--begin::Nav item-->
                <li class="nav-item my-1">
                    <a class="btn btn-light btn-active-light-primary fw-semibold fs-6 nav-link px-3 px-lg-8 mx-1" id="settings_users_link"
                        href="{{ route('users.index') }}">Users</a>
                </li>
                <!--end::Nav item-->
                <!--begin::Nav item-->
                <li class="nav-item my-1">
                    <a class="btn btn-light btn-active-light-primary fw-semibold fs-6 nav-link px-3 px-lg-8 mx-1" id="settings_branch_link"
                        href="{{ route('branch.index') }}">Branch</a>
                </li>
                <!--end::Nav item-->
                <!--begin::Nav item-->
                <li class="nav-item my-1">
                    <a class="btn btn-light btn-active-light-primary fw-semibold fs-6 nav-link px-3 px-lg-8 mx-1" id="settings_bulk_admission_link"
                        href="{{ route('bulk.admission.index') }}">Bulk Admission</a>
                </li>
                <!--end::Nav item-->
                <!--begin::Nav item-->
                <li class="nav-item my-1">
                    <a class="btn btn-light btn-active-light-primary fw-semibold fs-6 nav-link px-3 px-lg-8 mx-1" id="auto_invoice_button"
                        href="{{ route('auto.invoice') }}">Auto Invoice</a> 
                </li>
                <!--end::Nav item-->
            </ul>
            <!--end::Nav-->
        </div>
        <!--end::Hero nav-->
    </div>
    <!--end::Hero body-->
</div>
<!--end::Hero card-->
