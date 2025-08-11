@push('page-css')
@endpush


@extends('layouts.app')

@section('title', 'Send SMS')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Send SMS
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
                Send Single SMS </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <div class="row">
        <div class="col-xl-8 col-xxl-6">
            <!--begin::Card-->
            <div class="card">
                <!--begin::Card body-->
                <div class="card-body py-20">
                    <!--begin::Form-->
                    <form id="kt_send_single_sms_form" class="form" action="{{ route('sms.single.send') }}"
                        method="POST">
                        @csrf
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_send_single_sms_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="false" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_transaction_header"
                            data-kt-scroll-wrappers="#kt_send_single_sms_scroll" data-kt-scroll-offset="300px">

                            <div class="row">
                                <!--begin::Name Input group-->
                                <div class="fv-row mb-7 col-12">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Mobile Number</label>
                                    <!--end::Label-->

                                    <!--begin::Solid input group style-->
                                    <div class="input-group input-group-solid flex-nowrap">
                                        <span class="input-group-text">
                                            <i class="ki-outline ki-phone fs-3"></i>
                                        </span>
                                        <div class="overflow-hidden flex-grow-1">
                                            <input type="text" name="mobile" maxlength="11"
                                                class="form-control form-control-solid mb-3 mb-lg-0 rounded-start-0 border-start"
                                                placeholder="Write the 11 digit mobile number e.g. 017XXXXXXXX" required />
                                        </div>
                                    </div>
                                    <!--end::Solid input group style-->
                                </div>
                                <!--end::Name Input group-->

                                <!--begin::Month_Year Input group-->
                                <div class="fv-row mb-7 col-12">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">SMS Language</label>
                                    <!--end::Label-->

                                    <!--begin::Solid input group style-->
                                    <div class="row">
                                        <!--begin::New Month Year-->
                                        <div class="col-6">
                                            <!--begin::Option-->
                                            <input type="radio" class="btn-check" name="message_type" checked="checked"
                                                value="TEXT" id="text_message_type_input" />
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                for="text_message_type_input">
                                                <i class="ki-outline ki-abstract-44 fs-2x me-5"></i>
                                                <!--begin::Info-->
                                                <span class="d-block fw-semibold text-start">
                                                    <span class="text-gray-900 fw-bold d-block fs-6">English</span>
                                                </span>
                                                <!--end::Info-->
                                            </label>
                                            <!--end::Option-->
                                        </div>
                                        <!--end::New Month Year-->

                                        <!--begin::Old Month Year-->
                                        <div class="col-6">
                                            <!--begin::Option-->
                                            <input type="radio" class="btn-check" name="message_type" value="UNICODE"
                                                id="unicode_message_type_input" />
                                            <label
                                                class="btn btn-outline btn-outline-dashed btn-active-light-primary p-3 d-flex align-items-center"
                                                for="unicode_message_type_input">
                                                <i class="ki-outline ki-abstract-24 fs-2x me-5"></i>
                                                <!--begin::Info-->
                                                <span class="d-block fw-semibold text-start">
                                                    <span class="text-gray-900 fw-bold d-block fs-6">বাংলা</span>
                                                </span>
                                                <!--end::Info-->
                                            </label>
                                            <!--end::Option-->
                                        </div>
                                        <!--end::Old Month Year-->
                                    </div>
                                    <!--end::Solid input group style-->
                                </div>
                                <!--end::Month_Year Input group-->

                                <!--begin::Amount Input group-->
                                <div class="fv-row mb-7 col-12">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Message</label>
                                    <!--end::Label-->
                                    <textarea class="form-control form-control-solid" rows="5" name="message_body" placeholder="Write the message"></textarea>
                                    <!--end::Input-->
                                </div>
                                <!--end::Amount Input group-->
                            </div>
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3">Reset</button>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                        <!--end::Actions-->
                    </form>
                    <!--end::Form-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Card-->
        </div>
    </div>
@endsection


@push('vendor-js')
@endpush

@push('page-js')
    <script src="{{ asset('js/sms/single.js') }}"></script>

    <script>
        document.getElementById("sms_menu").classList.add("here", "show");
        document.getElementById("single_sms_link").classList.add("active");
    </script>
@endpush
