@push('page-css')
@endpush


@extends('layouts.app')

@section('title', 'New Admission')


@section('page-title')
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
