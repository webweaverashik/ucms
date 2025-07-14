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
    @stack('page-js')

    <script>
        // function to get pdf footer for datatables export
        function getPdfFooterWithPrintTime() {
            const now = new Date();

            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0'); // Month is zero-based
            const year = now.getFullYear();

            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;

            const formattedTime = `${hours}:${minutes}:${seconds} ${ampm}`;
            const formattedDate = `${day}-${month}-${year} ${formattedTime}`;
            const printTime = `Printed on: ${formattedDate}`;

            return function(currentPage, pageCount) {
                return {
                    columns: [{
                            text: printTime,
                            alignment: 'left',
                            margin: [20, 0]
                        },
                        {
                            text: `Page ${currentPage} of ${pageCount}`,
                            alignment: 'right',
                            margin: [0, 0, 20, 0]
                        }
                    ],
                    fontSize: 8,
                    margin: [0, 10]
                };
            };
        }

        // Toaster configuration
        document.addEventListener("DOMContentLoaded", function() {
            toastr.options = {
                "closeButton": false,
                "debug": false,
                "newestOnTop": true,
                "progressBar": true,
                "positionClass": "toastr-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "2000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut",
            };

            @if (session('success'))
                toastr.success("{{ session('success') }}");
            @endif

            @if (session('warning'))
                toastr.warning("{{ session('warning') }}");
            @endif

            @if (session('error'))
                toastr.error("{{ session('error') }}");
            @endif
        });

        // Tooltip Trigger for modal button also -- Globally
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
    <!--end::Custom Javascript-->
