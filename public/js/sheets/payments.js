"use strict";

var AllPaymentsList = function () {
      // Define shared variables
      var table;
      var datatable;

      // Private functions
      var initDatatable = function () {
            // Init datatable --- more info on datatables: https://datatables.net/manual/
            datatable = $(table).DataTable({
                  "info": true,
                  'order': [],
                  "lengthMenu": [10, 25, 50, 100],
                  "pageLength": 10,
                  "lengthChange": true,
                  "autoWidth": false,  // Disable auto width
                  'columnDefs': [
                        { orderable: false, targets: 7 }, // Disable ordering on column Actions                
                  ]
            });

            // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
            datatable.on('draw', function () {

            });
      }

      // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
      var handleSearch = function () {
            const filterSearch = document.querySelector('[data-sheet-payments-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                  datatable.search(e.target.value).draw();
            });
      }

      // Filter Datatable
      var handleFilter = function () {
            // Select filter options
            const filterForm = document.querySelector('[data-sheet-payments-table-filter="form"]');
            const filterButton = filterForm.querySelector('[data-sheet-payments-table-filter="filter"]');
            const resetButton = filterForm.querySelector('[data-sheet-payments-table-filter="reset"]');
            const selectOptions = filterForm.querySelectorAll('select');

            // Filter datatable on submit
            filterButton.addEventListener('click', function () {
                  var filterString = '';

                  // Get filter values
                  selectOptions.forEach((item, index) => {
                        if (item.value && item.value !== '') {
                              if (index !== 0) {
                                    filterString += ' ';
                              }

                              // Build filter value options
                              filterString += item.value;
                        }
                  });

                  // Filter datatable --- official docs reference: https://datatables.net/reference/api/search()
                  datatable.search(filterString).draw();
            });

            // Reset datatable
            resetButton.addEventListener('click', function () {
                  // Reset filter form
                  selectOptions.forEach((item, index) => {
                        // Reset Select2 dropdown --- official docs reference: https://select2.org/programmatic-control/add-select-clear-items
                        $(item).val(null).trigger('change');
                  });

                  // Filter datatable --- official docs reference: https://datatables.net/reference/api/search()
                  datatable.search('').draw();
            });
      }


      return {
            // Public functions  
            init: function () {
                  table = document.getElementById('kt_sheet_payments_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  handleSearch();
                  handleFilter();
            }
      }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      AllPaymentsList.init();
});