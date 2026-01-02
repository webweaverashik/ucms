"use strict";

// Class definition
var KTWalletLogs = function () {
      // Shared variables
      var table;
      var datatable;
      var filterType = '';
      var filterUser = '';

      // Init DataTable
      var initDatatable = function () {
            datatable = $(table).DataTable({
                  info: true,
                  order: [[0, 'desc']],
                  pageLength: 25,
                  lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                  columnDefs: [
                        { orderable: false, targets: [3] }
                  ],
                  language: {
                        emptyTable: `<div class="d-flex flex-column align-items-center py-10">
                    <i class="ki-outline ki-document fs-3x text-gray-400 mb-3"></i>
                    <span class="text-gray-500 fs-5">No transactions found</span>
                </div>`
                  }
            });

            // Add custom filter function
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                  // Only apply to this specific table
                  if (settings.nTable.id !== 'kt_logs_table') {
                        return true;
                  }

                  var row = datatable.row(dataIndex).node();
                  var rowType = $(row).data('type') || '';
                  var rowUser = $(row).data('user') || '';

                  // Filter by type
                  if (filterType && rowType !== filterType) {
                        return false;
                  }

                  // Filter by user
                  if (filterUser && rowUser !== filterUser) {
                        return false;
                  }

                  return true;
            });
      }

      // Handle search
      var handleSearch = function () {
            const filterSearch = document.querySelector('[data-kt-filter="search"]');
            if (filterSearch) {
                  filterSearch.addEventListener('keyup', function (e) {
                        datatable.search(e.target.value).draw();
                  });
            }
      }

      // Handle user filter
      var handleUserFilter = function () {
            const selectUser = $('[data-kt-filter="user"]');
            if (selectUser.length) {
                  selectUser.on('change', function () {
                        filterUser = $(this).val() || '';
                        datatable.draw();
                  });
            }
      }

      // Handle type filter
      var handleTypeFilter = function () {
            const selectType = $('[data-kt-filter="type"]');
            if (selectType.length) {
                  selectType.on('change', function () {
                        filterType = $(this).val() || '';
                        datatable.draw();
                  });
            }
      }

      // Initialize Select2
      var initSelect2 = function () {
            $('[data-control="select2"]').each(function () {
                  var hideSearch = $(this).data('hide-search');
                  $(this).select2({
                        minimumResultsForSearch: hideSearch ? Infinity : 10
                  });
            });
      }

      // Public methods
      return {
            init: function () {
                  table = document.getElementById('kt_logs_table');

                  if (table) {
                        initDatatable();
                        initSelect2();
                        handleSearch();
                        handleUserFilter();
                        handleTypeFilter();
                  }
            }
      }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTWalletLogs.init();
});