@push('page-css')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'User Settings')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Users of UCMS
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
                    Systems </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                All Users </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    @if ($errors->any())
        <div class="alert alert-danger fs-4">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <!--begin::Card-->
    <div class="card">
        <!--begin::Card header-->
        <div class="card-header border-0 pt-6">
            <!--begin::Card title-->
            <div class="card-title">
                <!--begin::Search-->
                <div class="d-flex align-items-center position-relative my-1">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5">
                    </i>
                    <input type="text" data-kt-user-table-filter="search"
                        class="form-control form-control-solid w-250px ps-13" placeholder="Search user" />
                </div>
                <!--end::Search-->
            </div>
            <!--begin::Card title-->
            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <!--begin::Toolbar-->
                <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                    <!--begin::Add user-->
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_user">
                        <i class="ki-outline ki-plus fs-2"></i>New User</a>
                    <!--end::Add user-->
                </div>
                <!--end::Toolbar-->
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
            <table class="table table-hover align-middle fs-6 gy-5" id="kt_table_users">
                <thead>
                    <tr class="fw-bold fs-5 text-uppercase gs-0">
                        <th class="w-50px text-center">SL</th>
                        <th class="min-w-125px">User Info</th>
                        <th class="min-w-125px text-center">Branch</th>
                        <th class="min-w-125px text-center">Role</th>
                        <th class="min-w-125px text-center">Last Login</th>
                        <th class="min-w-125px text-center">Active/Inactive</th>
                        <th class="min-w-100px text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold fs-5">
                    @foreach ($users as $user)
                        <tr>
                            <td class="text-center">{{ $loop->index + 1 }}</td>
                            <td class="d-flex align-items-center">
                                <!--begin:: Avatar -->
                                <div class="symbol symbol-circle symbol-50px overflow-hidden me-3">
                                    <a href="#">
                                        <div class="symbol-label">
                                            <img src="{{ $user->photo_url ? asset($user->photo_url) : asset('assets/img/dummy.png') }}"
                                                alt="{{ $user->name }}" class="w-100" />
                                        </div>
                                    </a>
                                </div>
                                <!--end::Avatar-->
                                <!--begin::user details-->
                                <div class="d-flex flex-column">
                                    <a href="#" class="text-gray-800 text-hover-primary mb-1">{{ $user->name }}</a>
                                    <span class="fw-bold fs-base">{{ $user->email }}</span>
                                </div>
                                <!--begin::user details-->
                            </td>
                            <td class="text-center">
                                @if ($user->branch)
                                    @php
                                        $branchColors = [
                                            1 => 'primary', // Blue for Branch ID 1
                                            2 => 'success', // Green for Branch ID 2
                                            3 => 'danger', // Red for Branch ID 3
                                        ];
                                        $badgeColor = $branchColors[$user->branch->id] ?? 'secondary'; // Default color
                                    @endphp

                                    <span class="badge badge-{{ $badgeColor }}">{{ $user->branch->branch_name }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($user->role == 'staff')
                                    <div class="badge badge-light-warning fw-bold">Staff</div>
                                @elseif ($user->role == 'admin')
                                    <div class="badge badge-light-success fw-bold">Admin</div>
                                @endif
                            </td>
                            <td class="text-center">
                                {{ $user->loginActivities()->latest()->first()->created_at ?? '-' }}</td>
                            <td class="text-center">
                                @if ($user->id != auth()->user()->id)
                                    <div
                                        class="form-check form-switch form-check-solid form-check-success d-flex justify-content-center">
                                        <input class="form-check-input toggle-active" type="checkbox"
                                            value="{{ $user->id }}" @if ($user->is_active == 1) checked @endif>
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="#" title="Edit User" data-bs-toggle="modal"
                                    data-bs-target="#kt_modal_edit_user" data-user-id="{{ $user->id }}"
                                    class="btn btn-icon btn-active-light-warning w-30px h-30px me-3">
                                    <i class="ki-outline ki-pencil fs-2"></i>
                                </a>
                                @if ($user->id != auth()->user()->id)
                                    <a href="#" title="Delete User" data-bs-toggle="tooltip"
                                        class="btn btn-icon btn-active-light-danger w-30px h-30px me-3 delete-user"
                                        data-user-id="{{ $user->id }}">
                                        <i class="ki-outline ki-trash fs-2"></i>
                                    </a>
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


    <!--begin::Modal - Add User-->
    <div class="modal fade" id="kt_modal_add_user" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_add_user_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Add New User</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-users-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_add_user_form" class="form" action="{{ route('users.store') }}"
                        enctype="multipart/form-data" method="POST">
                        @csrf
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_user_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_add_user_header"
                            data-kt-scroll-wrappers="#kt_modal_add_user_scroll" data-kt-scroll-offset="300px">
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="d-block fw-semibold fs-6 mb-5">Profile Photo</label>
                                <!--end::Label-->
                                <!--begin::Image placeholder-->
                                <style>
                                    .image-input-placeholder {
                                        background-image: url('{{ asset('assets/media/svg/files/blank-image.svg') }}');
                                    }

                                    [data-bs-theme="dark"] .image-input-placeholder {
                                        background-image: url('{{ asset('assets/media/svg/files/blank-image-dark.svg') }}');
                                    }
                                </style>
                                <!--end::Image placeholder-->
                                <!--begin::Image input-->
                                <div class="image-input image-input-empty image-input-outline image-input-placeholder"
                                    data-kt-image-input="true">
                                    <!--begin::Preview existing avatar-->
                                    <div class="image-input-wrapper w-125px h-125px"></div>
                                    <!--end::Preview existing avatar-->
                                    <!--begin::Label-->
                                    <label
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                        title="Change avatar">
                                        <i class="ki-outline ki-pencil fs-7">
                                        </i>
                                        <!--begin::Inputs-->
                                        <input type="file" name="avatar" accept=".png, .jpg, .jpeg" />
                                        <input type="hidden" name="avatar_remove" />
                                        <!--end::Inputs-->
                                    </label>
                                    <!--end::Label-->
                                    <!--begin::Cancel-->
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                        title="Cancel avatar">
                                        <i class="ki-outline ki-cross fs-2">
                                        </i>
                                    </span>
                                    <!--end::Cancel-->
                                    <!--begin::Remove-->
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                        title="Remove avatar">
                                        <i class="ki-outline ki-cross fs-2">
                                        </i>
                                    </span>
                                    <!--end::Remove-->
                                </div>
                                <!--end::Image input-->
                                <!--begin::Hint-->
                                <div class="form-text">Allowed file types: png, jpg, jpeg. Max 200kB</div>
                                <!--end::Hint-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Name</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="user_name"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Write full name"
                                    value="{{ old('user_name') }}" required />
                                <!--end::Input-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">Email</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="email" name="user_email"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="test@mail.com"
                                    value="{{ old('user_email') }}" required />
                                <!--end::Input-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="mb-5">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-5">Role</label>
                                <!--end::Label-->
                                <!--begin::Roles-->
                                <!--begin::Input row-->
                                <div class="d-flex fv-row">
                                    <!--begin::Radio-->
                                    <div class="form-check form-check-custom form-check-solid">
                                        <!--begin::Input-->
                                        <input class="form-check-input me-3" name="user_role" type="radio"
                                            value="admin" id="kt_modal_add_role_admin" />
                                        <!--end::Input-->
                                        <!--begin::Label-->
                                        <label class="form-check-label" for="kt_modal_add_role_admin">
                                            <div class="fw-bold text-gray-800">এডমিন</div>
                                            <div class="text-gray-600">খামার অনুমোদন ও সংশোধন, প্রেস্ক্রিপশন অনুমোদন ও
                                                সংশোধন, সিস্টেম সেটিংস
                                            </div>
                                        </label>
                                        <!--end::Label-->
                                    </div>
                                    <!--end::Radio-->
                                </div>
                                <!--end::Input row-->
                                <div class='separator separator-dashed my-5'></div>
                                <!--begin::Input row-->
                                <div class="d-flex fv-row">
                                    <!--begin::Radio-->
                                    <div class="form-check form-check-custom form-check-solid">
                                        <!--begin::Input-->
                                        <input class="form-check-input me-3" name="user_role" type="radio"
                                            value="staff" id="kt_modal_add_role_staff" checked />
                                        <!--end::Input-->
                                        <!--begin::Label-->
                                        <label class="form-check-label" for="kt_modal_add_role_staff">
                                            <div class="fw-bold text-gray-800">স্টাফ</div>
                                            <div class="text-gray-600">খামার নিবন্ধন, রেজিস্টার এন্ট্রি, প্রেস্ক্রিপশন
                                                এন্ট্রি</div>
                                        </label>
                                        <!--end::Label-->
                                    </div>
                                    <!--end::Radio-->
                                </div>
                                <!--end::Input row-->
                                <!--end::Roles-->
                            </div>
                            <!--end::Input group-->

                            <!--end::Input group-->
                            <div class="mb-5">
                                <!--begin::Col-->
                                <div class="fv-row">
                                    <!--begin::Label-->
                                    <label class="required fs-6 fw-semibold form-label mb-2">Assign Branch</label>
                                    <!--end::Label-->
                                    <!--begin::Row-->
                                    <div class="fv-row">
                                        <!--begin::Col-->
                                        <select name="user_branch" class="form-select form-select-solid"
                                            data-control="select2" data-hide-search="true"
                                            data-placeholder="Select branch" required>
                                            <option></option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                            @endforeach
                                        </select>
                                        <!--end::Col-->
                                    </div>
                                    <!--end::Row-->
                                </div>
                                <!--end::Col-->
                            </div>

                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-users-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-users-modal-action="submit">
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
    <!--end::Modal - Add User-->

    <!--begin::Modal - Edit User-->
    <div class="modal fade" id="kt_modal_edit_user" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw-650px">
            <!--begin::Modal content-->
            <div class="modal-content">
                <!--begin::Modal header-->
                <div class="modal-header" id="kt_modal_edit_user_header">
                    <!--begin::Modal title-->
                    <h2 class="fw-bold">Update User</h2>
                    <!--end::Modal title-->
                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-kt-users-modal-action="close">
                        <i class="ki-outline ki-cross fs-1">
                        </i>
                    </div>
                    <!--end::Close-->
                </div>
                <!--end::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body px-5 my-7">
                    <!--begin::Form-->
                    <form id="kt_modal_edit_user_form" class="form" action="#" enctype="multipart/form-data"
                        method="POST">
                        @csrf
                        @method('PUT')
                        <!--begin::Scroll-->
                        <div class="d-flex flex-column scroll-y px-5 px-lg-10" id="kt_modal_add_user_scroll"
                            data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-max-height="auto"
                            data-kt-scroll-dependencies="#kt_modal_edit_user_header"
                            data-kt-scroll-wrappers="#kt_modal_edit_user_scroll" data-kt-scroll-offset="300px">
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="d-block fw-semibold fs-6 mb-5">প্রোফাইল ছবি</label>
                                <!--end::Label-->
                                <!--begin::Image placeholder-->
                                <style>
                                    .image-input-placeholder {
                                        background-image: url('{{ asset('assets/media/svg/files/blank-image.svg') }}');
                                    }

                                    [data-bs-theme="dark"] .image-input-placeholder {
                                        background-image: url('{{ asset('assets/media/svg/files/blank-image-dark.svg') }}');
                                    }
                                </style>
                                <!--end::Image placeholder-->
                                <!--begin::Image input-->
                                <div class="image-input image-input-empty image-input-outline image-input-placeholder"
                                    data-kt-image-input="true">
                                    <!--begin::Preview existing avatar-->
                                    <div class="image-input-wrapper w-125px h-125px"></div>
                                    <!--end::Preview existing avatar-->
                                    <!--begin::Label-->
                                    <label
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="change" data-bs-toggle="tooltip"
                                        title="Change avatar">
                                        <i class="ki-outline ki-pencil fs-7">
                                        </i>
                                        <!--begin::Inputs-->
                                        <input type="file" name="avatar" accept=".png, .jpg, .jpeg" />
                                        <input type="hidden" name="avatar_remove" />
                                        <!--end::Inputs-->
                                    </label>
                                    <!--end::Label-->
                                    <!--begin::Cancel-->
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="cancel" data-bs-toggle="tooltip"
                                        title="Cancel avatar">
                                        <i class="ki-outline ki-cross fs-2">
                                        </i>
                                    </span>
                                    <!--end::Cancel-->
                                    <!--begin::Remove-->
                                    <span
                                        class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow"
                                        data-kt-image-input-action="remove" data-bs-toggle="tooltip"
                                        title="Remove avatar">
                                        <i class="ki-outline ki-cross fs-2">
                                        </i>
                                    </span>
                                    <!--end::Remove-->
                                </div>
                                <!--end::Image input-->
                                <!--begin::Hint-->
                                <div class="form-text">Allowed file types: png, jpg, jpeg. Max 200kB</div>
                                <!--end::Hint-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">নাম</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="text" name="user_name"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="সম্পূর্ণ নাম লিখুন"
                                    value="{{ old('user_name') }}" required />
                                <!--end::Input-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="fv-row mb-7">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-2">ইমেইল</label>
                                <!--end::Label-->
                                <!--begin::Input-->
                                <input type="email" name="user_email"
                                    class="form-control form-control-solid mb-3 mb-lg-0" placeholder="test@gmail.com"
                                    value="{{ old('user_email') }}" required />
                                <!--end::Input-->
                            </div>
                            <!--end::Input group-->
                            <!--begin::Input group-->
                            <div class="mb-5">
                                <!--begin::Label-->
                                <label class="required fw-semibold fs-6 mb-5">Role</label>
                                <!--end::Label-->
                                <!--begin::Roles-->
                                <!--begin::Input row-->
                                <div class="d-flex fv-row">
                                    <!--begin::Radio-->
                                    <div class="form-check form-check-custom form-check-solid">
                                        <!--begin::Input-->
                                        <input class="form-check-input me-3" name="user_role" type="radio"
                                            value="admin" id="kt_modal_update_role_admin" />
                                        <!--end::Input-->
                                        <!--begin::Label-->
                                        <label class="form-check-label" for="kt_modal_update_role_admin">
                                            <div class="fw-bold text-gray-800">এডমিন</div>
                                            <div class="text-gray-600">খামার অনুমোদন ও সংশোধন, প্রেস্ক্রিপশন অনুমোদন ও
                                                সংশোধন, সিস্টেম সেটিংস
                                            </div>
                                        </label>
                                        <!--end::Label-->
                                    </div>
                                    <!--end::Radio-->
                                </div>
                                <!--end::Input row-->
                                <div class='separator separator-dashed my-5'></div>
                                <!--begin::Input row-->
                                <div class="d-flex fv-row">
                                    <!--begin::Radio-->
                                    <div class="form-check form-check-custom form-check-solid">
                                        <!--begin::Input-->
                                        <input class="form-check-input me-3" name="user_role" type="radio"
                                            value="staff" id="kt_modal_update_role_staff" />
                                        <!--end::Input-->
                                        <!--begin::Label-->
                                        <label class="form-check-label" for="kt_modal_update_role_staff">
                                            <div class="fw-bold text-gray-800">স্টাফ</div>
                                            <div class="text-gray-600">খামার নিবন্ধন, রেজিস্টার এন্ট্রি, প্রেস্ক্রিপশন
                                                এন্ট্রি</div>
                                        </label>
                                        <!--end::Label-->
                                    </div>
                                    <!--end::Radio-->
                                </div>
                                <!--end::Input row-->
                                <!--end::Roles-->
                            </div>
                            <!--end::Input group-->
                        </div>
                        <!--end::Scroll-->
                        <!--begin::Actions-->
                        <div class="text-center pt-10">
                            <button type="reset" class="btn btn-light me-3"
                                data-kt-users-modal-action="cancel">Discard</button>
                            <button type="submit" class="btn btn-primary" data-kt-users-modal-action="submit">
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
    <!--end::Modal - Edit User-->
@endsection


@push('vendor-js')
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
@endpush

@push('page-js')
    <script src="{{ asset('js/users.index.js') }}"></script><!--  Used for modal close only -->

    <script>
        document.getElementById("users_link").classList.add("active");
    </script>

    {{-- Toggle active/inactive button --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleInputs = document.querySelectorAll('.toggle-active');

            toggleInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const farmId = this.value;
                    const isActive = this.checked ? 1 : 0;
                    const row = this.closest('tr'); // Get the parent <tr> element

                    fetch("{{ route('users.toggleActive') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({
                                farm_id: farmId,
                                is_active: isActive
                            })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                toastr.success(data.message);
                            } else {
                                toastr.error(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            toastr.error('Error occurred while toggling farm status');
                        });
                });
            });
        });
    </script>

    {{-- Delete button alert modal dialog --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-user');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();

                    const userId = this.dataset.userId;

                    Swal.fire({
                        title: 'Are you sure to delete this user?',
                        text: "User data will be unavailable after deletion.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete.',
                        cancelButtonText: 'Cancel',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch("{{ route('users.destroy', '') }}/" + userId, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        _method: 'DELETE'
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            title: 'Success!',
                                            text: 'User has been deleted successfully.',
                                            icon: 'success',
                                            confirmButtonText: 'OK'
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        Swal.fire('Failed!',
                                            'User could not be deleted.',
                                            'error');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire('Error!',
                                        'Please, contact your administrator.',
                                        'error');
                                });
                        }
                    });
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $(document).on("click", "[data-bs-target='#kt_modal_edit_user']", function() {
                let userId = $(this).data("user-id");
                let url = "/users/" + userId + "/edit"; // Adjust as per your route

                $.ajax({
                    url: url,
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            let user = response.user;

                            // Set form action dynamically
                            $("#kt_modal_edit_user_form").attr("action", "/users/" + userId);

                            // Populate form fields
                            $("input[name='user_name']").val(user.name);
                            $("input[name='user_email']").val(user.email);
                            $("input[name='user_role'][value='" + user.role + "']").prop(
                                "checked", true);

                            // Profile image preview
                            if (user.photo_url) {
                                $(".image-input-wrapper").css("background-image", "url(" + user
                                    .photo_url + ")");
                            }
                        } else {
                            alert("User data not found!");
                        }
                    },
                    error: function() {
                        alert("Error fetching user data!");
                    },
                });
            });
        });
    </script>
@endpush
