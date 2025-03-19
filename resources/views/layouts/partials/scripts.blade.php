    <script>
        var hostUrl = "assets/";
    </script>

    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <!--end::Global Javascript Bundle-->

    <!--begin::Vendors Javascript(used for this page only)-->
    @stack('vendor-js')
    <!--end::Vendors Javascript-->

    <!--begin::Custom Javascript(used for this page only)-->
    @stack('page-ks')
    <!--end::Custom Javascript-->
