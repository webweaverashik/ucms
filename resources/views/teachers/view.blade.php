@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', $teacher->name)

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            {{ $teacher->name }}
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">
                    Teacher Info </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                View </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection

@section('content')

@endsection



@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush


@push('page-js')
    <script>
        const routeDeleteStudent = "{{ route('teachers.destroy', ':id') }}";
        const routeToggleActive = "{{ route('teachers.toggleActive', ':id') }}";
        const routeDeleteInvoice = "{{ route('invoices.destroy', ':id') }}";
        const routeDeleteTxn = "{{ route('transactions.destroy', ':id') }}";
        const routeApproveTxn = "{{ route('transactions.approve', ':id') }}";
    </script>

    <script src="{{ asset('js/teachers/view.js') }}"></script>

    <script>
        document.getElementById("teachers_menu").classList.add("here", "show");
        document.getElementById("teachers_link").classList.add("active");
    </script>
@endpush
