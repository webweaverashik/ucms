@extends('layouts.app')

@push('page-css')
    <link rel="stylesheet" href="{{ asset('css/settings/cost-types.css') }}">
@endpush

@section('title', 'Cost Types')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Cost Types
        </h1>
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">Systems</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Cost Types</li>
        </ul>
    </div>
@endsection

@section('content')
    @include('settings.partials.hero')

    <!--begin::Toolbar-->
    <div class="d-flex flex-wrap flex-stack mb-6">
        <h3 class="fw-bold my-2">
            Cost Types
            <span class="fs-6 text-gray-500 fw-semibold ms-1" id="totalCount">({{ count($costTypes) }})</span>
        </h3>
        <div class="d-flex my-2">
            <button type="button" class="btn btn-primary" id="btnAddCostType">
                <i class="ki-outline ki-plus fs-2"></i> Add Cost Type
            </button>
        </div>
    </div>
    <!--end::Toolbar-->

    <!--begin::Row-->
    <div class="row g-5 g-xl-9" id="costTypesContainer">
        @foreach ($costTypes as $type)
            <div class="col-md-3" data-id="{{ $type->id }}">
                <div class="card card-flush h-md-100 py-6 border-hover-primary {{ !$type->is_active ? 'inactive' : '' }}">
                    <div class="card-header">
                        <div class="card-title d-flex justify-content-between w-100">
                            <h3 class="cost-type-name">{{ $type->name }}</h3>
                            @if ($type->name === 'Others')
                                <span class="badge badge-light-warning">Default</span>
                            @else
                                <div class="d-flex align-items-center">
                                    <div class="form-check form-switch form-check-custom form-check-solid me-3">
                                        <input class="form-check-input toggle-active" type="checkbox"
                                            data-bs-toggle="tooltip" title="Toggle Active/Inactive"
                                            data-id="{{ $type->id }}" {{ $type->is_active ? 'checked' : '' }}>
                                    </div>
                                    <button type="button" class="btn btn-icon btn-sm btn-light-primary btn-edit"
                                        data-bs-toggle="tooltip" title="Edit Type" data-id="{{ $type->id }}"
                                        data-name="{{ $type->name }}" data-description="{{ $type->description }}">
                                        <i class="ki-outline ki-pencil fs-4"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-body pt-1">
                        <h5 class="text-gray-500 cost-type-description">
                            <i class="ki-outline ki-price-tag fs-4 me-2"></i>
                            <span class="fs-5">{{ $type->description ?? 'No description available' }}</span>
                        </h5>
                        <div class="fw-semibold text-gray-600 mt-10 fs-5">
                            No. of Cost Entry: <span
                                class="cost-entries-count fw-bold">{{ $type->cost_entries_count }}</span>
                        </div>
                        <div class="fw-semibold text-gray-600 mt-2 fs-5">
                            Total Cost Amount: <span
                                class="cost-entries-count fw-bold">{{ $type->cost_entries_sum_amount ?? 0 }} ৳</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <!--end::Row-->

    <!--begin::Modal - Add/Edit Cost Type-->
    <div class="modal fade" id="kt_modal_cost_type" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <div class="modal-content">
                <div class="modal-header" id="kt_modal_cost_type_header">
                    <h2 class="fw-bold" id="modalTitle">Add Cost Type</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                    <form id="kt_modal_cost_type_form" class="form" action="#">
                        <input type="hidden" id="cost_type_id" name="id">
                        <!--begin::Input group-->
                        <div class="fv-row mb-7">
                            <label class="required fw-semibold fs-6 mb-2">Cost Type Name</label>
                            <input type="text" name="name" id="cost_type_name" class="form-control form-control-solid"
                                placeholder="Enter cost type name" />
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="fv-row mb-7">
                            <label class="fw-semibold fs-6 mb-2">Description</label>
                            <textarea name="description" id="cost_type_description" class="form-control form-control-solid" rows="3"
                                placeholder="Enter description (optional)"></textarea>
                        </div>
                        <!--end::Input group-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="kt_modal_cost_type_submit">
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
        // Configuration object for KTCostTypes
        var KTCostTypesConfig = {
            csrfToken: '{{ csrf_token() }}',
            routes: {
                store: '{{ route('cost-types.store') }}',
                update: '{{ url('settings/cost-types') }}/:id',
                toggleActive: '{{ route('cost-types.toggleActive') }}'
            }
        };
    </script>
    <script src="{{ asset('js/settings/cost-types.js') }}"></script>
@endpush
