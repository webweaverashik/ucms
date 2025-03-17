@push('page-css')
@endpush


@extends('layouts.app')

@section('title', 'New Admission')


@section('page-title')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">New admission to this branch</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('students.index') }}">Student Info</a>
                        </li>
                        <li class="breadcrumb-item active">
                            New Admission
                        </li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('content')
@endsection


@push('page-js')
    <script>
        document.getElementById("admission_menu").classList.add("collapsed", "active");
        document.getElementById("admission_menu").setAttribute("aria-expanded", "true");
        document.getElementById("sidebarAdmission").classList.add("show");
        document.querySelector('[data-key="New Admission"]').classList.add("active");
    </script>
@endpush
