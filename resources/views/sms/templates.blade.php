@push('page-css')
@endpush


@extends('layouts.app')

@section('title', 'SMS Templates')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            SMS Templates
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
                    SMS </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                SMS Templates </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin:::Tabs-->
    <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
        <!--begin:::Tab item-->
        <li class="nav-item">
            <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_invoices_templates_tab"><i
                    class="ki-outline ki-bookmark fs-3 me-2"></i>Invoices SMS
            </a>
        </li>
        <!--end:::Tab item-->

        <!--begin:::Tab item-->
        <li class="nav-item">
            <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_transactions_templates_tab"><i
                    class="ki-outline ki-cheque fs-3 me-2"></i>Transactions SMS
            </a>
        </li>
        <!--end:::Tab item-->

        <!--begin:::Tab item-->
        <li class="nav-item">
            <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_academic_templates_tab"><i
                    class="ki-outline ki-teacher fs-3 me-2"></i>Academic SMS
            </a>
        </li>
        <!--end:::Tab item-->

        <!--begin:::Tab item-->
        <li class="nav-item">
            <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_others_templates_tab"><i
                    class="ki-outline ki-abstract-12 fs-3 me-2"></i>Others SMS
            </a>
        </li>
        <!--end:::Tab item-->
    </ul>
    <!--end:::Tabs-->

    <!--begin:::Tab content-->
    <div class="tab-content">
        <!--begin:::Tab pane-->
        <div class="tab-pane fade show active" id="kt_invoices_templates_tab" role="tabpanel">
            <!--begin::Card-->
            <div class="card pt-4">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <p class="text-gray-600 fs-5">Use Placeholder: {student_name}, {invoice_no}, {amount}, {month_year},
                            {due_date}
                        </p>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body py-10">
                    <!--begin::Table wrapper-->
                    <div class="row mb-n5 gx-10">
                        @foreach ($templates->where('type', 'invoices') as $template)
                            <div class="col-md-6 mb-10">
                                <div class="d-flex flex-row align-items-center gap-5 mb-2">
                                    <h5 class="fw-bold">{{ ucwords(str_replace('_', ' ', $template->name)) }}</h5>
                                    <div
                                        class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                        <input class="form-check-input h-20px w-40px toggle-status" type="checkbox"
                                            data-id="{{ $template->id }}"
                                            @if ($template->is_active) checked @endif />
                                    </div>
                                </div>

                                <div class="position-relative">
                                    <textarea class="form-control form-control-solid-bg template-body" rows="3" data-id="{{ $template->id }}"
                                        maxlength="500">{{ $template->body }}</textarea>

                                    <small class="text-muted d-block mt-1 char-counter">0/500</small>

                                    <button
                                        class="btn btn-sm btn-primary position-absolute end-0 bottom-0 m-2 save-body d-none"
                                        data-id="{{ $template->id }}">
                                        Save
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <!--end::Table wrapper-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end:::Tab pane-->

        <!--begin:::Tab pane-->
        <div class="tab-pane fade show" id="kt_transactions_templates_tab" role="tabpanel">
            <!--begin::Card-->
            <div class="card pt-4">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <p class="text-gray-600 fs-5">Use Placeholder: {student_name}, {voucher_no}, {invoice_no}, {paid_amount}, {remaining_amount}</p>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body py-10">
                    <!--begin::Table wrapper-->
                    <div class="row mb-n5 gx-10">
                        @foreach ($templates->where('type', 'transactions') as $template)
                            <div class="col-md-6 mb-10">
                                <div class="d-flex flex-row align-items-center gap-5 mb-2">
                                    <h5 class="fw-bold">{{ ucwords(str_replace('_', ' ', $template->name)) }}</h5>
                                    <div
                                        class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                        <input class="form-check-input h-20px w-40px toggle-status" type="checkbox"
                                            data-id="{{ $template->id }}"
                                            @if ($template->is_active) checked @endif />
                                    </div>
                                </div>

                                <div class="position-relative">
                                    <textarea class="form-control form-control-solid-bg template-body" rows="3" data-id="{{ $template->id }}"
                                        maxlength="500">{{ $template->body }}</textarea>

                                    <small class="text-muted d-block mt-1 char-counter">0/500</small>

                                    <button
                                        class="btn btn-sm btn-primary position-absolute end-0 bottom-0 m-2 save-body d-none"
                                        data-id="{{ $template->id }}">
                                        Save
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <!--end::Table wrapper-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end:::Tab pane-->

        <!--begin:::Tab pane-->
        <div class="tab-pane fade show" id="kt_academic_templates_tab" role="tabpanel">
            <!--begin::Card-->
            <div class="card pt-4">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <p class="text-gray-600 fs-5">Use Placeholder: {student_name}, {student_unique_id}, {student_class_name}, {student_shift_name}, {tuition_fee}, {due_date}</p>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body py-10">
                    <!--begin::Table wrapper-->
                    <div class="row mb-n5 gx-10">
                        @foreach ($templates->where('type', 'academic') as $template)
                            <div class="col-md-6 mb-10">
                                <div class="d-flex flex-row align-items-center gap-5 mb-2">
                                    <h5 class="fw-bold">{{ ucwords(str_replace('_', ' ', $template->name)) }}</h5>
                                    <div
                                        class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                        <input class="form-check-input h-20px w-40px toggle-status" type="checkbox"
                                            data-id="{{ $template->id }}"
                                            @if ($template->is_active) checked @endif />
                                    </div>
                                </div>

                                <div class="position-relative">
                                    <textarea class="form-control form-control-solid-bg template-body" rows="3" data-id="{{ $template->id }}"
                                        maxlength="500">{{ $template->body }}</textarea>

                                    <small class="text-muted d-block mt-1 char-counter">0/500</small>

                                    <button
                                        class="btn btn-sm btn-primary position-absolute end-0 bottom-0 m-2 save-body d-none"
                                        data-id="{{ $template->id }}">
                                        Save
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <!--end::Table wrapper-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end:::Tab pane-->

        <!--begin:::Tab pane-->
        <div class="tab-pane fade show" id="kt_others_templates_tab" role="tabpanel">
            <!--begin::Card-->
            <div class="card pt-4">
                <!--begin::Card header-->
                <div class="card-header">
                    <!--begin::Card title-->
                    <div class="card-title">
                        <p class="text-gray-600 fs-5">Use Placeholder: {student_name} </p>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--end::Card header-->
                <!--begin::Card body-->
                <div class="card-body py-10">
                    <!--begin::Table wrapper-->
                    <div class="row mb-n5 gx-10">
                        @foreach ($templates->where('type', 'others') as $template)
                            <div class="col-md-6 mb-10">
                                <div class="d-flex flex-row align-items-center gap-5 mb-2">
                                    <h5 class="fw-bold">{{ ucwords(str_replace('_', ' ', $template->name)) }}</h5>
                                    <div
                                        class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                        <input class="form-check-input h-20px w-40px toggle-status" type="checkbox"
                                            data-id="{{ $template->id }}"
                                            @if ($template->is_active) checked @endif />
                                    </div>
                                </div>

                                <div class="position-relative">
                                    <textarea class="form-control form-control-solid-bg template-body" rows="3" data-id="{{ $template->id }}"
                                        maxlength="500">{{ $template->body }}</textarea>

                                    <small class="text-muted d-block mt-1 char-counter">0/500</small>

                                    <button
                                        class="btn btn-sm btn-primary position-absolute end-0 bottom-0 m-2 save-body d-none"
                                        data-id="{{ $template->id }}">
                                        Save
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <!--end::Table wrapper-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
        <!--end:::Tab pane-->
    </div>
    <!--end:::Tab content-->
@endsection


@push('vendor-js')
@endpush

@push('page-js')
    <script src="{{ asset('js/sms/templates.js') }}"></script>

    <script>
        document.getElementById("sms_menu").classList.add("here", "show");
        document.getElementById("sms_template_link").classList.add("active");
    </script>
@endpush
