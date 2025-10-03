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
                    <a class="btn btn-color-gray-600 btn-active-secondary btn-active-color-primary fw-bolder fs-8 fs-lg-base nav-link px-3 px-lg-8 mx-1 text-uppercase" id="settings_users_link"
                        href="{{ route('users.index') }}">Users</a>
                </li>
                <!--end::Nav item-->
                <!--begin::Nav item-->
                <li class="nav-item my-1">
                    <a class="btn btn-color-gray-600 btn-active-secondary btn-active-color-primary fw-bolder fs-8 fs-lg-base nav-link px-3 px-lg-8 mx-1 text-uppercase" id="settings_branch_link"
                        href="{{ route('branch.index') }}">Branch</a>
                </li>
                <!--end::Nav item-->
                <!--begin::Nav item-->
                <li class="nav-item my-1">
                    <a class="btn btn-color-gray-600 btn-active-secondary btn-active-color-primary fw-bolder fs-8 fs-lg-base nav-link px-3 px-lg-8 mx-1 text-uppercase" id="settings_bulk_admission_link"
                        href="{{ route('bulk.admission.index') }}">Bulk Admission</a>
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
