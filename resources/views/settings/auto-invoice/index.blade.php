@push('page-css')
    <style>
        .invoice-card {
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .invoice-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .invoice-card.current-invoice:hover {
            border-color: var(--bs-primary);
        }

        .invoice-card.due-invoice:hover {
            border-color: var(--bs-warning);
        }

        .icon-wrapper {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
    </style>
@endpush


@extends('layouts.app')

@section('title', 'Auto Invoice')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Auto-invoice Generation
        </h1>
        <!--end::Title-->
        <!--begin::Separator-->
        <span class="h-20px border-gray-300 border-start mx-4"></span>
        <!--end::Separator-->
        <!--begin::Breadcrumb-->
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 ">
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                <a href="#" class="text-muted text-hover-primary">Systems</a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">Invoice Generation</li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin::Success Alert-->
    @if (session('success'))
        <div
            class="alert alert-dismissible bg-light-success border border-success border-dashed d-flex flex-column flex-sm-row w-100 p-5 mb-10">
            <i class="ki-duotone ki-check-circle fs-2hx text-success me-4 mb-5 mb-sm-0">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            <div class="d-flex flex-column pe-0 pe-sm-10">
                <h5 class="mb-1 text-success">Invoice Generation Successful</h5>
                <span class="text-success fs-6">{{ session('success') }}</span>
            </div>
            <button type="button"
                class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto"
                data-bs-dismiss="alert">
                <i class="ki-outline ki-cross fs-1 text-success"></i>
            </button>
        </div>
    @endif
    <!--end::Success Alert-->

    <!--begin::Warning Alert-->
    @if (session('warning'))
        <div
            class="alert alert-dismissible bg-light-warning border border-warning border-dashed d-flex flex-column flex-sm-row w-100 p-5 mb-10">
            <i class="ki-duotone ki-information fs-2hx text-warning me-4 mb-5 mb-sm-0">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
            <div class="d-flex flex-column pe-0 pe-sm-10">
                <h5 class="mb-1 text-warning">Warning</h5>
                <span class="text-warning fs-6">{{ session('warning') }}</span>
            </div>
            <button type="button"
                class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto"
                data-bs-dismiss="alert">
                <i class="ki-outline ki-cross fs-1 text-warning"></i>
            </button>
        </div>
    @endif
    <!--end::Warning Alert-->

    <!--begin::Error Alert-->
    @if (session('error'))
        <div
            class="alert alert-dismissible bg-light-danger border border-danger border-dashed d-flex flex-column flex-sm-row w-100 p-5 mb-10">
            <i class="ki-duotone ki-cross-circle fs-2hx text-danger me-4 mb-5 mb-sm-0">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            <div class="d-flex flex-column pe-0 pe-sm-10">
                <h5 class="mb-1 text-danger">Error</h5>
                <span class="text-danger fs-6">{{ session('error') }}</span>
            </div>
            <button type="button"
                class="position-absolute position-sm-relative m-2 m-sm-0 top-0 end-0 btn btn-icon ms-sm-auto"
                data-bs-dismiss="alert">
                <i class="ki-outline ki-cross fs-1 text-danger"></i>
            </button>
        </div>
    @endif
    <!--end::Error Alert-->

    @include('settings.partials.hero')

    <!--begin::Information Notice-->
    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6 mb-6">
        <i class="ki-duotone ki-information-3 fs-2tx text-primary me-4">
            <span class="path1"></span>
            <span class="path2"></span>
            <span class="path3"></span>
        </i>
        <div class="d-flex flex-column flex-grow-1">
            <h4 class="text-gray-900 fw-bold mb-3">Important Information</h4>
            <div class="fs-6 text-gray-700">
                <ul class="mb-0 ps-4">
                    <li class="mb-2">
                        <span class="badge badge-primary me-2">Current</span> For students with "current" payment style -
                        bills for <strong>{{ Carbon\Carbon::now()->format('F Y') }}</strong>
                    </li>
                    <li class="mb-2">
                        <span class="badge badge-warning me-2">Due</span> For students with "due" payment style - bills for
                        <strong>{{ Carbon\Carbon::now()->subMonth()->format('F Y') }}</strong>
                    </li>
                    <li class="mb-2">
                        <span class="badge badge-light-dark me-2">Branch</span> Select a specific branch or leave empty for
                        all branches
                    </li>
                    <li class="mb-2">Only <strong>active students</strong> in <strong>active classes</strong> will receive
                        invoices</li>
                    <li class="mb-2">Students with <strong>0 tuition fees</strong> (FREE) will be skipped</li>
                    <li class="mb-0">Students with <strong>existing invoices</strong> for the billing period will be
                        skipped</li>
                </ul>
            </div>
        </div>
    </div>
    <!--end::Information Notice-->

    <!--begin::Main Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <div class="card-title flex-column">
                <h3 class="fw-bold mb-1">Invoice Generation</h3>
                <div class="fs-6 text-gray-500">
                    Generate invoices that have been missed by scheduled creation at
                    <span class="fw-semibold text-gray-700">01-{{ date('m') }}-{{ date('Y') }}, 12:00 AM</span>
                    or for reactivated students.
                </div>
            </div>
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-6">
            <div class="row g-5">
                <!--begin::Current Invoice-->
                <div class="col-md-6">
                    <div class="invoice-card current-invoice bg-light-primary rounded p-6 h-100">
                        <div class="d-flex align-items-center mb-4">
                            <div class="symbol symbol-50px me-4">
                                <span class="symbol-label bg-primary">
                                    <i class="ki-duotone ki-file-added fs-2x text-white">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </span>
                            </div>
                            <div>
                                <h4 class="fw-bold text-gray-900 mb-0">Current Month Invoices</h4>
                                <span class="text-gray-600 fs-7">{{ Carbon\Carbon::now()->format('F Y') }}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-gray-700">Select Branch</label>
                            <select class="form-select form-select-solid" id="current_branch_select" data-control="select2"
                                data-placeholder="All Branches" data-hide-search="false">
                                <option value="">All Branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <a href="{{ route('auto.invoice.current') }}" class="btn btn-primary w-100"
                            id="btn_generate_current" data-base-url="{{ route('auto.invoice.current') }}">
                            <i class="ki-outline ki-update-file fs-4 me-2"></i>
                            <span class="indicator-label">Generate Current Invoices</span>
                            <span class="indicator-progress">
                                Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </a>
                    </div>
                </div>
                <!--end::Current Invoice-->

                <!--begin::Due Invoice-->
                <div class="col-md-6">
                    <div class="invoice-card due-invoice bg-light-warning rounded p-6 h-100">
                        <div class="d-flex align-items-center mb-4">
                            <div class="symbol symbol-50px me-4">
                                <span class="symbol-label bg-warning">
                                    <i class="ki-duotone ki-timer fs-2x text-white">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </span>
                            </div>
                            <div>
                                <h4 class="fw-bold text-gray-900 mb-0">Due Month Invoices</h4>
                                <span
                                    class="text-gray-600 fs-7">{{ Carbon\Carbon::now()->subMonth()->format('F Y') }}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-gray-700">Select Branch</label>
                            <select class="form-select form-select-solid" id="due_branch_select" data-control="select2"
                                data-placeholder="All Branches" data-hide-search="false">
                                <option value="">All Branches</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <a href="{{ route('auto.invoice.due') }}" class="btn btn-warning w-100" id="btn_generate_due"
                            data-base-url="{{ route('auto.invoice.due') }}">
                            <i class="ki-outline ki-update-file fs-4 me-2"></i>
                            <span class="indicator-label">Generate Due Invoices</span>
                            <span class="indicator-progress">
                                Please wait...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </a>
                    </div>
                </div>
                <!--end::Due Invoice-->
            </div>
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Main Card-->
@endsection


@push('vendor-js')
@endpush

@push('page-js')
    <script src="{{ asset('js/settings/auto-invoice.js') }}"></script>

    <script>
        document.getElementById("settings_link").classList.add("active");
        document.getElementById("settings_auto_invoice_link").classList.add("active");
    </script>
@endpush
