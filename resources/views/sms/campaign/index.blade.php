@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'SMS Campaigns')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            SMS Campaigns
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
                SMS Campaigns List</li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text"
                        data-sms-campaigns-table-filter="search" class="form-control form-control-solid w-350px ps-12"
                        placeholder="Search in campaigns">
                </div>
                <!--end::Search-->

                <!--begin::Export hidden buttons-->
                <div id="kt_hidden_export_buttons" class="d-none"></div>
                <!--end::Export buttons-->

            </div>
            <!--begin::Card title-->

            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-sms-campaigns-table-toolbar="base">
                    <!--begin::Filter-->
                    <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-filter fs-2"></i>Filter</button>
                    <!--begin::Menu 1-->
                    <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                        <!--begin::Header-->
                        <div class="px-7 py-5">
                            <div class="fs-5 text-gray-900 fw-bold">Filter Options</div>
                        </div>
                        <!--end::Header-->
                        <!--begin::Separator-->
                        <div class="separator border-gray-200"></div>
                        <!--end::Separator-->
                        <!--begin::Content-->
                        <div class="px-7 py-5" data-sms-campaigns-table-filter="form">
                            <!--begin::Input group-->
                            <div class="mb-10">
                                <label class="form-label fs-6 fw-semibold">Campaign Status:</label>
                                <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                    data-placeholder="Select option" data-allow-clear="true"
                                    data-sms-campaigns-table-filter="status" data-hide-search="true">
                                    <option></option>
                                    <option value="T_partial">Sent</option>
                                    <option value="T_discounted">Not Sent</option>
                                </select>
                            </div>
                            <!--end::Input group-->

                            <!--begin::Actions-->
                            <div class="d-flex justify-content-end">
                                <button type="reset" class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                    data-kt-menu-dismiss="true" data-sms-campaigns-table-filter="reset">Reset</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-6" data-kt-menu-dismiss="true"
                                    data-sms-campaigns-table-filter="filter">Apply</button>
                            </div>
                            <!--end::Actions-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Menu 1-->

                    <!--begin::Export dropdown-->
                    <div class="dropdown">
                        <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                            data-kt-menu-placement="bottom-end">
                            <i class="ki-outline ki-exit-up fs-2"></i>Export
                        </button>

                        <!--begin::Menu-->
                        <div id="kt_table_report_dropdown_menu"
                            class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4"
                            data-kt-menu="true">
                            <!--begin::Menu item-->
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="copy">Copy to
                                    clipboard</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="excel">Export as Excel</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="csv">Export as CSV</a>
                            </div>
                            <div class="menu-item px-3">
                                <a href="#" class="menu-link px-3" data-row-export="pdf">Export as PDF</a>
                            </div>
                            <!--end::Menu item-->
                        </div>
                        <!--end::Menu-->
                    </div>
                    <!--end::Export dropdown-->

                    <!--begin::Add Campaign-->
                    @can('sms.campaign.create')
                        <a href="{{ route('send-campaign.create') }}" class="btn btn-primary">
                            <i class="ki-outline ki-plus fs-2"></i>New Campaign</a>
                    @endcan
                    <!--end::Add Campaign-->

                    <!--end::Filter-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table" id="kt_sms_campaigns_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-25px">SL</th>
                        <th>Title</th>
                        <th class="w-500px">Message</th>
                        <th>Recipients</th>
                        <th>Created At</th>
                        <th>Branch</th>
                        <th>Created By</th>
                        <th>Status</th>
                        <th class="not-export">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @foreach ($campaigns as $campaign)
                        <tr>
                            <td>{{ $loop->index + 1 }}</td>
                            <td>{{ $campaign->campaign_title }}</td>
                            <td>{{ $campaign->message_body }}</td>
                            {{-- <td>{{ $campaign->recipients }}</td> --}}
                            <td><button type="button"
                                    class="btn btn-icon text-hover-success w-30px h-30px view-receipients"
                                    data-campaign-title="{{ $campaign->campaign_title }}"
                                    data-recipients='@json(is_array($campaign->recipients) ? $campaign->recipients : json_decode($campaign->recipients, true) ?? [])'>
                                    <i class="ki-outline ki-eye fs-2"></i>
                                </button>

                            </td>
                            <td>
                                {{ $campaign->created_at->format('h:i:s A, d-M-Y') }}
                            </td>
                            <td>{{ $campaign->branch->branch_name }}</td>
                            <td>
                                {{ $campaign->createdBy->name ?? 'System' }}
                            </td>

                            <td>
                                @if ($campaign->is_approved === false)
                                    <span class="badge badge-warning rounded-pill">Pending</span>
                                @else
                                    <span class="badge badge-success rounded-pill">Approved</span>
                                @endif
                            </td>

                            <td>
                                @if ($campaign->is_approved === false)
                                    @can('sms.campaign.approve')
                                        <a href="#" title="Approve Campaign"
                                            class="btn btn-icon text-hover-success w-30px h-30px approve-campaign me-2"
                                            data-campaign-id={{ $campaign->id }}>
                                            <i class="bi bi-check-circle fs-2"></i>
                                        </a>
                                    @endcan

                                    @can('sms.campaign.edit')
                                        <a href="#" title="Edit Message Body" data-campaign-id={{ $campaign->id }}
                                            class="btn btn-icon text-hover-success w-30px h-30px me-2 edit-campaign">
                                            <i class="ki-outline ki-pencil fs-2"></i>
                                        </a>
                                    @endcan

                                    @can('sms.campaign.delete')
                                        <a href="#" title="Delete Campaign"
                                            class="btn btn-icon text-hover-danger w-30px h-30px delete-campaign"
                                            data-campaign-id={{ $campaign->id }}>
                                            <i class="bi bi-trash fs-2"></i>
                                        </a>
                                    @endcan
                                @else
                                    <span class="badge badge-info rounded-pill">Sent</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>
    <!--end::Card-->

    <!--begin::Modal - Edit Campaign-->
    <div class="modal fade" id="kt_modal_edit_campaign" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_edit_campaign_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold" id="kt_modal_edit_campaign_title">Update Campaign</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-campaign-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_campaign_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_campaign_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_campaign_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_campaign_scroll" data-kt-scroll-offset="300px">
                            <!--begin::TEXT & UNICODE-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">SMS Language</label>
                                <!--end::Label-->

                                <!--begin::Solid input group style-->
                                <div class="row">
                                    <!--begin::English text-->
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
                                    <!--end::English text-->

                                    <!--begin::Bangla text-->
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
                                    <!--end::Bangla text-->
                                </div>
                                <!--end::Solid input group style-->
                            </div>
                            <!--end::TEXT & UNICODE-->

                            <!--begin::Message input-->
                            <div class="fv-row mb-7 col-12">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Message</label>
                                <!--end::Label-->
                                <textarea class="form-control form-control-solid" rows="8" name="message_body"
                                    placeholder="Write the message"></textarea>
                                <!--end::Input-->
                            </div>
                            <!--end::Message input-->
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-campaign-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-campaign-modal-action="submit">
                                <span class="indicator-label">Update</span>
                                <span class="indicator-progress">Please wait...
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            </button>
                        </div>
                        <!--end::Actions-->
                    </form>
                    <!--end::Form-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
    <!--end::Modal - Edit Campaign-->

    <div class="modal fade" id="viewRecipientsModal" tabindex="-1" aria-labelledby="viewRecipientsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewRecipientsModalLabel">Recipients</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="recipientsContent">
                    <!-- recipients will load here -->
                </div>
            </div>
        </div>
    </div>

@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        const routeDeleteCampaign = "{{ route('send-campaign.destroy', ':id') }}";
    </script>

    <script src="{{ asset('js/sms/campaign/index.js') }}"></script>

    <script>
        document.getElementById("sms_menu").classList.add("here", "show");
        document.getElementById("sms_campaign_link").classList.add("active");
    </script>
@endpush
