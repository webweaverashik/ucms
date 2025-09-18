"use strict";

// Class definition
var KTAppEcommerceReportSales = function () {
      // Shared variables
      var table;
      var datatable;

      // Private functions
      var initDatatable = function () {
            // Set date data order
            const tableRows = table.querySelectorAll('tbody tr');

            tableRows.forEach(row => {
                  const dateRow = row.querySelectorAll('td');
                  const realDate = moment(dateRow[0].innerHTML, "MMM DD, YYYY").format(); // select date from 4th column in table
                  dateRow[0].setAttribute('data-order', realDate);
            });


            // Init datatable --- more info on datatables: https://datatables.net/manual/
            datatable = $(table).DataTable({
                  "info": false,
                  'order': [],
                  'pageLength': 10,
            });
      }

      // Init daterangepicker
      var initDaterangepicker = () => {
            var start = moment().subtract(6, "days");
            var end = moment();
            var input = $("#finance_daterangepicker");

            function cb(start, end) {
                  input.html(start.format("MMMM D, YYYY") + " - " + end.format("MMMM D, YYYY"));
            }

            input.daterangepicker({
                  startDate: start,
                  endDate: end,
                  ranges: {
                        "Today": [moment(), moment()],
                        "Yesterday": [moment().subtract(1, "days"), moment().subtract(1, "days")],
                        "Last 7 Days": [moment().subtract(6, "days"), moment()],
                        "Last 30 Days": [moment().subtract(29, "days"), moment()],
                        "This Month": [moment().startOf("month"), moment().endOf("month")],
                        "Last Month": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")]
                  }
            }, cb);

            cb(start, end);
      }


      // Public methods
      return {
            init: function () {
                  table = document.querySelector('#kt_ecommerce_report_sales_table');

                  // if (!table) {
                  //       return;
                  // }

                  // initDatatable();
                  initDaterangepicker();
                  // exportButtons();
                  // handleSearchDatatable();
            }
      };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTAppEcommerceReportSales.init();
});
