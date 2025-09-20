"use strict";

// Class definition
var KTFinanceReport = function () {
      // Init daterangepicker
      var initDaterangepicker = () => {
            var start = moment().subtract(6, "days");
            var end = moment();
            var input = $("#finance_daterangepicker");

            function cb(start, end) {
                  input.html(start.format("DD-MM-YYYY") + " - " + end.format("DD-MM-YYYY"));
            }

            input.daterangepicker({
                  startDate: start,
                  endDate: end,
                  locale: {
                        format: "DD-MM-YYYY"   // 👈 force this format in the picker
                  },
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
                  initDaterangepicker();
            }
      };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTFinanceReport.init();
});
