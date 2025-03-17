<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Dashboard') | UCMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Unique Coaching Management System (UCMS)" name="description" />
    <meta content="Ashikur Rahman" name="author" />
    {{-- <meta name="asset-url" content="{{ asset('') }}"> --}}
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}">

    <!-- plugin css -->
    @stack('page-css')
    <!-- Layout config Js -->
    <script src="{{ asset('assets/js/layout.js') }}"></script>
    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="{{ asset('assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />
</head>