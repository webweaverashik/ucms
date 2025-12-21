@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'Transfer History')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Student Transfer History
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
                    Student Info </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Transfer </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    <!--begin::Card-->
    <div class="card mt-6">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i> <input type="text"
                        data-student-transfer-history-table-filter="search"
                        class="form-control form-control-solid w-350px ps-12" placeholder="Search in transfer history">
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
                <div class="d-flex justify-content-end" data-student-transfer-history-table-toolbar="base">
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

                    <!--begin::Add Teacher-->
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#kt_modal_new_transfer">
                        <i class="ki-outline ki-plus fs-2"></i>New Transfer</a>
                    <!--end::Add Teacher-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->

        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
            <table class="table table-hover align-middle table-row-dashed fs-6 gy-5 ucms-table"
                id="student_transfer_history_table">
                <thead>
                    <tr class="fw-bold fs-7 text-uppercase gs-0">
                        <th>#</th>
                        <th class="w-250px">Student</th>
                        <th class="d-none">From Branch</th>
                        <th>From Branch</th>
                        <th>From Batch</th>
                        <th class="d-none">To Branch</th>
                        <th>To Branch</th>
                        <th>To Batch</th>
                        <th>Transfer Date</th>
                        <th>Transferred By</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @foreach ($transfer_logs as $log)
                        <tr>
                            <td>{{ $loop->index + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <!--begin:: Avatar -->
                                    <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                        <a href="{{ route('students.show', $log->student_id) }}">
                                            <div class="symbol-label">
                                                <img src="{{ $log->student->photo_url ? asset($log->student->photo_url) : asset($log->student->gender == 'male' ? 'img/boy.png' : 'img/girl.png') }}"
                                                    alt="{{ $log->student->name }}" class="w-100" />
                                            </div>
                                        </a>
                                    </div>
                                    <!--end::Avatar-->

                                    <!--begin::user details-->
                                    <div class="d-flex flex-column text-start">
                                        <a href="{{ route('students.show', $log->student_id) }}" target="_blank"
                                            class="text-gray-800 text-hover-primary mb-1">{{ $log->student->name }}
                                        </a>
                                        <span class="fw-bold fs-base">{{ $log->student->student_unique_id }}</span>
                                    </div>
                                    <!--begin::user details-->
                                </div>
                            </td>

                            <!-- From Branch Badge -->
                            <td class="d-none">
                                {{ $log->from_branch_id }}_{{ $log->fromBranch->branch_name }}_{{ $log->fromBranch->branch_prefix }}
                            </td>
                            <td>
                                @php
                                    $fromColors = [
                                        'light-primary',
                                        'light-danger',
                                        'light-success',
                                        'light-warning',
                                        'light-info',
                                        'light-dark',
                                        'light-secondary',
                                    ];
                                    $fromColorIndex = ($log->from_branch_id ?? 0) % count($fromColors);
                                    $fromBadgeClass = 'badge badge-' . $fromColors[$fromColorIndex] . ' rounded-pill';
                                @endphp
                                <span class="{{ $fromBadgeClass }}">{{ $log->fromBranch->branch_name }}</span>
                            </td>
                            <td>{{ $log->fromBatch->name }}</td>

                            <!-- To Branch Badge -->
                            <td class="d-none">
                                {{ $log->to_branch_id }}_{{ $log->toBranch->branch_name }}_{{ $log->toBranch->branch_prefix }}
                            </td>
                            <td>
                                @php
                                    $toColors = [
                                        'light-success',
                                        'light-info',
                                        'light-primary',
                                        'light-warning',
                                        'light-danger',
                                        'light-dark',
                                        'light-secondary',
                                    ];
                                    $toColorIndex = ($log->to_branch_id ?? 0) % count($toColors);
                                    $toBadgeClass = 'badge badge-' . $toColors[$toColorIndex] . ' rounded-pill';
                                @endphp
                                <span class="{{ $toBadgeClass }}">{{ $log->toBranch->branch_name }}</span>
                            </td>
                            <td>{{ $log->toBatch->name }}</td>
                            <td>{{ $log->created_at->format('h:i:s A, d-M-Y') }}</td>
                            <td>
                                {{ $log->transferredBy->name }}
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


    <!--begin::Modal - Edit Teacher-->
    <div class="modal fade" id="kt_modal_new_transfer" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
        data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-750px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold" id="kt_modal_new_transfer_title">Transfer a student from one branch to another</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-new-transfer-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_new_transfer_form" class="form" action="#" novalidate="novalidate">
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_new_transfer_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_new_transfer_header"
                            data-kt-scroll-wrappers="#kt_modal_new_transfer_scroll" data-kt-scroll-offset="300px">
                            <div class="row">
                                <!--begin::Name Input group-->
                                <div class="col-lg-12">
                                    <div class="fv-row mb-7">
                                        <label for="student_select_input" class="form-label fw-semibold required">
                                            Select Student
                                        </label>
                                        <div class="input-group input-group-solid flex-nowrap">
                                            <span class="input-group-text">
                                                <i class="ki-outline ki-faceid fs-3"></i>
                                            </span>
                                            <div class="overflow-hidden flex-grow-1">
                                                <select id="student_select_input"
                                                    class="form-select form-select-solid rounded-start-0 border-start"
                                                    name="student_id" data-control="select2"
                                                    data-dropdown-parent="#kt_modal_new_transfer"
                                                    data-placeholder="Select a student first">
                                                    <option></option>
                                                    @foreach ($students as $student)
                                                        <option value="{{ $student->id }}">
                                                            {{ $student->name }} ({{ $student->student_unique_id }}) |
                                                            {{ $student->branch->branch_name }}
                                                            ({{ $student->branch->branch_prefix }})
                                                            |
                                                            {{ $student->class->name }} | {{ $student->batch->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Name Input group-->

                                <!--begin::Branch Input group-->
                                <div class="col-lg-6">
                                    <div class="fv-row mb-7">
                                        <label for="student_branch_input" class="form-label fw-semibold required">
                                            Transfer to Branch
                                        </label>
                                        <div class="input-group input-group-solid flex-nowrap">
                                            <span class="input-group-text">
                                                <i class="ki-outline ki-parcel fs-3"></i>
                                            </span>
                                            <div class="overflow-hidden flex-grow-1">
                                                <select id="student_branch_input"
                                                    class="form-select form-select-solid rounded-start-0 border-start"
                                                    name="branch_id" data-control="select2"
                                                    data-placeholder="Select branch" data-hide-search="true">
                                                    <option></option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Branch Input group-->

                                <!--begin::Batch Input group-->
                                <div class="col-lg-6">
                                    <div class="fv-row mb-7">
                                        <label for="student_batch_input" class="form-label fw-semibold required">
                                            Batch
                                        </label>
                                        <div class="input-group input-group-solid flex-nowrap">
                                            <span class="input-group-text">
                                                <i class="ki-outline ki-people fs-3"></i>
                                            </span>
                                            <div class="overflow-hidden flex-grow-1">
                                                <select id="student_batch_input"
                                                    class="form-select form-select-solid rounded-start-0 border-start"
                                                    name="batch_id" data-control="select2"
                                                    data-placeholder="Select batch" data-hide-search="true">
                                                    <option></option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--end::Batch Input group-->
                            </div>
                        </div>
                        <!--end::Scroll-->

                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-new-transfer-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-new-transfer-modal-action="submit">
                                <span class="indicator-label">Submit</span>
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
    <!--end::Modal - Edit Teacher-->
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script>
        window.availableBranchesRoute = "{{ route('students.transfer.availableBranches', ':student') }}";
        window.batchesByBranchRoute = "{{ route('students.transfer.batchesByBranch', ':branch') }}";
        window.storeNewTransferRoute = "{{ route('students.transfer.store') }}";
    </script>

    <script src="{{ asset('js/students/transfer/index.js') }}"></script>

    <script>
        document.getElementById("admission_menu").classList.add("here", "show");
        document.getElementById("transfer_students_link").classList.add("active");
    </script>
@endpush
