@extends('layouts.app')

@push('page-css')
    <link rel="stylesheet" href="{{ asset('css/settings/branches.css') }}">
@endpush

@section('title', 'All Branches')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Branches
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">Systems</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Branches</li>
        </ul>
    </div>
@endsection

@section('content')
    @include('settings.partials.hero')

    <!--begin::Toolbar-->
    <div class="d-flex flex-wrap flex-stack mb-6">
        <h3 class="fw-bold my-2">
            Branches
            <span class="fs-6 text-gray-500 fw-semibold ms-1" id="totalCount">({{ count($branches) }})</span>
        </h3>
        <div class="d-flex my-2">
            <button type="button" class="btn btn-primary" id="btnAddBranch">
                <i class="ki-outline ki-plus fs-2"></i> Add Branch
            </button>
        </div>
    </div>
    <!--end::Toolbar-->

    <!--begin::Row-->
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-5 g-xl-9" id="branchesContainer">
        @foreach ($branches as $branch)
            <div class="col-md-6" data-id="{{ $branch->id }}">
                <div class="card card-flush h-md-100 py-6 border-hover-primary">
                    <div class="card-header">
                        <div class="card-title d-flex justify-content-between w-100">
                            <h2>
                                <span class="branch-name">{{ $branch->branch_name }}</span> Branch
                                (<span class="branch-prefix">{{ $branch->branch_prefix }}</span>)
                            </h2>
                            <button type="button" class="btn btn-icon btn-sm btn-light-primary btn-edit"
                                data-id="{{ $branch->id }}" data-name="{{ $branch->branch_name }}"
                                data-prefix="{{ $branch->branch_prefix }}" data-address="{{ $branch->address }}"
                                data-phone="{{ $branch->phone_number }}">
                                <i class="ki-outline ki-pencil fs-4"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body pt-1">
                        <h4 class="text-gray-600 branch-address">
                            <i class="ki-outline ki-geolocation fs-4 me-2"></i>
                            <span>{{ $branch->address ?? 'No address' }}</span>
                        </h4>
                        <h4 class="text-gray-600 branch-phone">
                            <i class="bi bi-telephone fs-4 me-2"></i>
                            <span>{{ $branch->phone_number ?? 'No phone' }}</span>
                        </h4>
                        <div class="fw-bold text-gray-600 mt-10 fs-5">
                            Total active students: <span
                                class="active-students-count">{{ count($branch->activeStudents) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <!--end::Row-->

    <!--begin::Modal - Add/Edit Branch-->
    <div class="modal fade" id="kt_modal_branch" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header" id="kt_modal_branch_header">
                    <h2 class="fw-bold" id="modalTitle">Add Branch</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <form id="kt_modal_branch_form" class="form" action="#">
                        <input type="hidden" id="branch_id" name="id">
                        <!--begin::Input group-->
                        <div class="row mb-7">
                            <!--begin::Col-->
                            <div class="col-md-8 fv-row">
                                <label class="required fw-semibold fs-6 mb-2">Branch Name</label>
                                <input type="text" name="branch_name" id="branch_name"
                                    class="form-control form-control-solid" placeholder="Enter branch name" />
                            </div>
                            <!--end::Col-->
                            <!--begin::Col-->
                            <div class="col-md-4 fv-row">
                                <label class="required fw-semibold fs-6 mb-2">Prefix</label>
                                <input type="text" name="branch_prefix" id="branch_prefix"
                                    class="form-control form-control-solid text-uppercase" placeholder="e.g. D" />
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Address</label>
                            <textarea name="address" id="branch_address" class="form-control form-control-solid" rows="3"
                                placeholder="Enter branch address (optional)"></textarea>
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Phone Number</label>
                            <input type="text" name="phone_number" id="branch_phone_number"
                                class="form-control form-control-solid" placeholder="Enter phone number (optional)" />
                        </div>
                        <!--end::Input group-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="kt_modal_branch_submit">
                                <span class="indicator-label">Submit</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                        <!--end::Actions-->
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--end::Modal-->
@endsection

@push('vendor-js')
@endpush

@push('page-js')
    <script>
        // Configuration object for KTBranches
        var KTBranchesConfig = {
            csrfToken: '{{ csrf_token() }}',
            routes: {
                store: '{{ route('branches.store') }}',
                update: '{{ url('settings/branches') }}/:id'
            }
        };
    </script>
    <script src="{{ asset('js/settings/branches.js') }}"></script>
@endpush
