@push('page-css')
    <link href="{{ asset('assets/libs/jsvectormap/jsvectormap.min.css') }}" rel="stylesheet" type="text/css" />
@endpush


@extends('layouts.app')

@section('title', 'Dashboard')


@section('page-title')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Dashboard</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active"><a href="javascript: void(0);">Dashboards</a>
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
    <!-- apexcharts -->
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- Vector map-->
    <script src="{{ asset('assets/libs/jsvectormap/jsvectormap.min.js') }}"></script>
    <script src="{{ asset('assets/libs/jsvectormap/maps/world-merc.js') }}"></script>

    <!-- Dashboard init -->
    <script src="{{ asset('assets/js/pages/dashboard-analytics.init.js') }}"></script>

    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
@endpush
