@push('page-css')
    <style>
        .construction-container {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .construction-card {
            text-align: center;
            padding: 3rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            color: white;
        }

        .construction-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-20px);
            }

            60% {
                transform: translateY(-10px);
            }
        }

        .construction-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            animation: fadeInDown 1s ease-in-out;
            color: rgba(255, 255, 255, 1);
        }

        .construction-text {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: fadeInUp 1s ease-in-out;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 0.9;
                transform: translateY(0);
            }
        }

        .dots {
            display: inline-flex;
            gap: 0.5rem;
        }

        .dot {
            width: 12px;
            height: 12px;
            background-color: white;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }

        .dot:nth-child(2) {
            animation-delay: 0.3s;
        }

        .dot:nth-child(3) {
            animation-delay: 0.6s;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.5);
                opacity: 0.5;
            }
        }

        .progress-bar-custom {
            height: 8px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.3);
            overflow: hidden;
            margin-top: 2rem;
        }

        .progress-fill {
            height: 100%;
            background: white;
            border-radius: 10px;
            animation: progressAnimation 3s ease-in-out infinite;
        }

        @keyframes progressAnimation {
            0% {
                width: 0%;
            }

            50% {
                width: 70%;
            }

            100% {
                width: 0%;
            }
        }

        .feature-list {
            text-align: left;
            display: inline-block;
            margin-top: 2rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            animation: fadeIn 1s ease-in-out backwards;
        }

        .feature-item:nth-child(1) {
            animation-delay: 0.2s;
        }

        .feature-item:nth-child(2) {
            animation-delay: 0.4s;
        }

        .feature-item:nth-child(3) {
            animation-delay: 0.6s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .feature-icon {
            font-size: 1.5rem;
        }
    </style>
@endpush

@extends('layouts.app')
@section('title', 'Dashboard')
@section('header-title')
    <div data-kt-swapper="true" data-kt-swapper-mode="{default: 'prepend', lg: 'prepend'}"
        data-kt-swapper-parent="{default: '#kt_app_content_container', lg: '#kt_app_header_wrapper'}"
        class="page-title d-flex align-items-center flex-wrap me-3 mb-5 mb-lg-0">
        <!--begin::Title-->
        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 align-items-center my-0">
            Dashboard
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
                    Home </a>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-500 w-5px h-2px"></span>
            </li>
            <!--end::Item-->
            <!--begin::Item-->
            <li class="breadcrumb-item text-muted">
                Dashboards </li>
            <!--end::Item-->
        </ul>
        <!--end::Breadcrumb-->
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="construction-container">
                    <div class="construction-card">
                        <!-- Animated Icon -->
                        <div class="construction-icon">
                            🚧
                        </div>

                        <!-- Title -->
                        <h1 class="construction-title">
                            Page Under Construction
                        </h1>

                        <!-- Description -->
                        <p class="construction-text">
                            We're working hard to bring you something amazing!<br>
                            This page is currently being developed.
                        </p>

                        <!-- Animated Dots -->
                        <div class="dots mb-3">
                            <div class="dot"></div>
                            <div class="dot"></div>
                            <div class="dot"></div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress-bar-custom">
                            <div class="progress-fill"></div>
                        </div>

                        <!-- Coming Features -->
                        <div class="feature-list">
                            <div class="feature-item">
                                <span class="feature-icon">📊</span>
                                <span>Analytics Dashboard</span>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon">📈</span>
                                <span>Real-time Reports</span>
                            </div>
                            <div class="feature-item">
                                <span class="feature-icon">⚡</span>
                                <span>Performance Metrics</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('vendor-js')
@endpush

@push('page-js')
    <script>
        document.getElementById("dashboard_link").classList.add("active");
    </script>
@endpush
