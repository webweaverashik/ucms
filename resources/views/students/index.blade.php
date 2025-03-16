@push('page-css')
@endpush


@extends('layouts.app')

@section('title', 'All Student')


@section('page-title')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">All Students</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item">
                            <a href="#">Student Info</a>
                        </li>
                        <li class="breadcrumb-item active">
                            All Students
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
        document.getElementById("student_info_menu").classList.add("collapsed", "active");
        document.getElementById("student_info_menu").setAttribute("aria-expanded", "true");
        document.getElementById("sidebarStudentInfo").classList.add("show");
        document.querySelector('[data-key="t-all-students"]').classList.add("active");
    </script>
@endpush
