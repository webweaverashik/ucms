@push('page-css')
@endpush


@extends('layouts.app')

@section('title', 'All Batches')

@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            All Batches
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
                    Academic </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Batches </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection


@section('content')
    {{-- Setting different badge color to different branches --}}
    @php
        $badgeColors = ['badge-light-primary', 'badge-light-success', 'badge-light-warning'];
        $branchColors = [];

        foreach ($branches as $index => $branch) {
            $branchColors[$branch->branch_name] = $badgeColors[$index % count($badgeColors)];
        }
    @endphp

    <div class="row">
        <div class="col-md-12">
            <!--begin::Row-->
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-5 g-xl-9">

                @foreach ($batches as $batch)
                    @php
                        $branchName = $batch->branch->branch_name;
                        $badgeColor = $branchColors[$branchName] ?? 'badge-light-info'; // Default color if not found
                    @endphp
                    <!--begin::Col-->
                    <div class="col">
                        <!--begin::Card-->
                        <div class="card card-flush h-md-100 border-hover-primary">
                            <!--begin::Card header-->
                            <div class="card-header">
                                <!--begin::Card title-->
                                <div class="card-title">
                                    <h2>{{ $batch->name }} &nbsp;</h2>
                                    <span class="badge {{ $badgeColor }}">{{ $branchName }}</span>
                                </div>
                                <!--end::Card title-->
                            </div>
                            <!--end::Card header-->
                            <!--begin::Card body-->
                            <div class="card-body pt-1">
                                <table class="table fs-6 fw-semibold gs-0 gy-1 gx-0">
                                    <!--begin::Row-->
                                    <tr class="">
                                        <td class="text-gray-500">Day Off:</td>
                                        <td class="text-gray-800 text-center">
                                            {{ ucfirst($batch->day_off) }}
                                        </td>
                                    </tr>
                                    <!--end::Row-->

                                    <!--begin::Row-->
                                    <tr class="">
                                        <td class="text-gray-500">Total active students:</td>
                                        <td class="text-gray-800 text-center">
                                            {{ count($batch->activeStudents) }}
                                        </td>
                                    </tr>
                                    <!--end::Row-->
                                </table>
                            </div>
                            <!--end::Card body-->
                        </div>
                        <!--end::Card-->
                    </div>
                    <!--end::Col-->
                @endforeach

            </div>
            <!--end::Row-->
        </div>

        {{-- begin:Right Sidebar --}}
        {{-- <div class="col-md-3">
            <div class="row">
                <!--begin::Add new card-->
                <div class="col-md-12">
                    <!--begin::Card-->
                    <div class="card border-dashed border-primary">
                        <!--begin::Card body-->
                        <div class="card-body">
                            <form action="{{ route('batches.store') }}" method="post">
                                @csrf
                                <!--begin::Heading-->
                                <div class="pb-5">
                                    <!--begin::Title-->
                                    <h3 class="fw-bold d-flex align-items-center text-gray-900">Add New Batch</h3>
                                    <!--end::Title-->
                                </div>
                                <!--end::Heading-->

                                <!--begin::Name Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="required fw-semibold fs-6 mb-2">Name</label>
                                    <!--end::Label-->
                                    <!--begin::Input-->
                                    <input type="text" name="batch_name"
                                        class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Batch name"
                                        maxlength="15" required value="{{ old('batch_name') }}" />
                                    <!--end::Input-->

                                    @error('batch_name')
                                        <p style="color: red;">{{ $message }}</p>
                                    @enderror
                                </div>
                                <!--end::Name Input group-->

                                <!--begin::Branch Input group-->
                                <div class="fv-row mb-7">
                                    <!--begin::Label-->
                                    <label class="form-label required">Branch</label>
                                    <!--end::Label-->

                                    <!--begin::Solid input group style-->
                                    <div class="flex-nowrap">
                                            <select name="batch_branch" data-hide-search="true"
                                                class="form-select form-select-solid" data-control="select2"
                                                data-placeholder="Select branch" required>
                                                <option></option>
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                                @endforeach
                                            </select>
                                        @error('batch_branch')
                                            <p style="color: red;">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <!--end::Solid input group style-->
                                </div>
                                <!--end::Branch Input group-->

                                <button type="submit" class="btn btn-primary">Submit</button>
                            </form>
                        </div>
                        <!--begin::Card body-->
                    </div>
                    <!--begin::Card-->
                </div>
            </div>
        </div> --}}
        {{-- end:Right Sidebar --}}
    </div>
@endsection


@push('vendor-js')
@endpush

@push('page-js')
    <script>
        document.getElementById("academic_menu").classList.add("here", "show");
        document.getElementById("batches_link").classList.add("active");
    </script>
@endpush
