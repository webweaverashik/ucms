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
                        href="{{ route('users.index') }}"><i class="ki-outline ki-user-edit fs-2 m-0"></i> Users</a>
                </li>
                <!--end::Nav item-->
                <!--begin::Nav item-->
                <li class="nav-item my-1">
                    <a class="btn btn-light btn-active-light-primary fw-semibold fs-6 nav-link px-3 px-lg-8 mx-1" id="settings_branch_link"
                        href="{{ route('branch.index') }}"><i class="ki-outline ki-parcel fs-2 m-0"></i> Branch</a>
                </li>
                <!--end::Nav item-->
                <!--begin::Nav item-->
                <li class="nav-item my-1">
                    <a class="btn btn-light btn-active-light-primary fw-semibold fs-6 nav-link px-3 px-lg-8 mx-1" id="settings_bulk_admission_link"
                        href="{{ route('bulk.admission.index') }}"><i class="ki-outline ki-file-up fs-2 m-0"></i> Bulk Admission</a>
                </li>
                <!--end::Nav item-->
                <!--begin::Nav item-->
                <li class="nav-item my-1">
                    <a class="btn btn-light btn-active-light-primary fw-semibold fs-6 nav-link px-3 px-lg-8 mx-1" id="auto_invoice_button"
                        href="{{ route('auto.invoice') }}"><i class="ki-outline ki-update-file fs-2 m-0"></i> Auto Invoice</a> 
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
