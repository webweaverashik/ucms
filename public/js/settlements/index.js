"use strict";

// Class definition
var KTSettlements = function () {
      // Shared variables
      var datatables = {};
      var modal;
      var modalElement;
      var form;
      var submitButton;
      var currentBalance = 0;
      var currentBalanceFilter = '';

      // Init DataTables for each branch tab
      var initDatatables = function () {
            document.querySelectorAll('.kt-settlements-table').forEach(function (table) {
                  var branchId = table.getAttribute('data-branch-id');

                  datatables[branchId] = $(table).DataTable({
                        "info": false,
                        "order": [],
                        "pageLength": 10,
                        "lengthChange": true,
                        "columnDefs": [
                              { orderable: false, targets: 6 }
                        ]
                  });
            });

            // Add global custom filter for balance
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                  // If no filter is set, show all
                  if (!currentBalanceFilter) {
                        return true;
                  }

                  // Get the table element from settings
                  var tableId = settings.nTable.id;
                  var $table = $('#' + tableId);

                  if (!$table.hasClass('kt-settlements-table')) {
                        return true; // Not our table, skip
                  }

                  var api = $table.DataTable();
                  var row = api.row(dataIndex).node();

                  if (!row) return true;

                  var balanceCell = $(row).find('td:eq(5)');
                  var filterVal = balanceCell.data('filter');

                  if (currentBalanceFilter === 'with_balance') {
                        return filterVal === 'with_balance';
                  } else if (currentBalanceFilter === 'zero_balance') {
                        return filterVal === 'zero_balance';
                  }

                  return true;
            });
      }

      // Get active datatable
      var getActiveDatatable = function () {
            var activePane = document.querySelector('.tab-pane.active');
            if (activePane) {
                  var table = activePane.querySelector('.kt-settlements-table');
                  if (table) {
                        var branchId = table.getAttribute('data-branch-id');
                        return datatables[branchId];
                  }
            }
            return null;
      }

      // Handle search - applies to active tab
      var handleSearch = function () {
            const filterSearch = document.querySelector('[data-kt-filter="search"]');
            if (filterSearch) {
                  filterSearch.addEventListener('keyup', function (e) {
                        // Search in all datatables
                        Object.values(datatables).forEach(function (dt) {
                              dt.search(e.target.value).draw();
                        });
                  });
            }
      }

      // Handle balance filter
      var handleBalanceFilter = function () {
            const filterBalance = $('[data-kt-filter="balance"]');
            if (filterBalance.length) {
                  filterBalance.on('change', function () {
                        currentBalanceFilter = $(this).val() || '';

                        // Redraw all datatables
                        Object.values(datatables).forEach(function (dt) {
                              dt.draw();
                        });
                  });
            }
      }

      // Handle tab change - reinitialize datatable display
      var handleTabChange = function () {
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                  var targetId = $(e.target).attr('href');
                  var table = $(targetId).find('.kt-settlements-table');

                  if (table.length) {
                        var branchId = table.data('branch-id');
                        if (datatables[branchId]) {
                              datatables[branchId].columns.adjust().draw();
                        }
                  }
            });
      }

      // Handle settlement button click
      var handleSettleButton = function () {
            $(document).on('click', '.btn-settle', function (e) {
                  e.preventDefault();

                  var userId = $(this).data('user-id');
                  var userName = $(this).data('user-name');
                  var balance = parseFloat($(this).data('balance'));

                  currentBalance = balance;

                  $('#settlement_user_id').val(userId);
                  $('#modal_user_name').text(userName);
                  $('#modal_current_balance').text('৳' + balance.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                  $('#settlement_amount').val('').attr('max', balance);
                  $('#amount_error').text('');
                  $('textarea[name="notes"]').val('');

                  modal.show();
            });
      }

      // Handle full amount button
      var handleFullAmount = function () {
            $('#btn_full_amount').on('click', function () {
                  $('#settlement_amount').val(currentBalance.toFixed(2));
            });
      }

      // Handle form submit
      var handleFormSubmit = function () {
            form.addEventListener('submit', function (e) {
                  e.preventDefault();

                  var amount = parseFloat($('#settlement_amount').val());

                  // Validate amount
                  if (isNaN(amount) || amount <= 0) {
                        $('#amount_error').text('Please enter a valid amount');
                        return;
                  }

                  if (amount > currentBalance) {
                        $('#amount_error').text('Amount cannot exceed current balance');
                        return;
                  }

                  $('#amount_error').text('');

                  // Show loading
                  submitButton.setAttribute('data-kt-indicator', 'on');
                  submitButton.disabled = true;

                  // Submit via AJAX
                  $.ajax({
                        url: form.action,
                        method: 'POST',
                        data: $(form).serialize(),
                        headers: {
                              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {
                              submitButton.removeAttribute('data-kt-indicator');
                              submitButton.disabled = false;

                              modal.hide();

                              Swal.fire({
                                    text: response.message || "Settlement recorded successfully!",
                                    icon: "success",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: {
                                          confirmButton: "btn btn-primary"
                                    }
                              }).then(function () {
                                    location.reload();
                              });
                        },
                        error: function (xhr) {
                              submitButton.removeAttribute('data-kt-indicator');
                              submitButton.disabled = false;

                              var message = 'Something went wrong!';
                              if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                              } else if (xhr.responseJSON && xhr.responseJSON.error) {
                                    message = xhr.responseJSON.error;
                              }

                              Swal.fire({
                                    text: message,
                                    icon: "error",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: {
                                          confirmButton: "btn btn-primary"
                                    }
                              });
                        }
                  });
            });
      }

      // Handle modal hidden event - reset form
      var handleModalReset = function () {
            modalElement.addEventListener('hidden.bs.modal', function () {
                  form.reset();
                  $('#amount_error').text('');
            });
      }

      // Initialize Select2
      var initSelect2 = function () {
            $('[data-control="select2"]').select2({
                  minimumResultsForSearch: Infinity
            });
      }

      // Public methods
      return {
            init: function () {
                  modalElement = document.getElementById('kt_modal_settlement');
                  form = document.getElementById('kt_modal_settlement_form');
                  submitButton = document.getElementById('btn_submit_settlement');

                  if (modalElement) {
                        modal = new bootstrap.Modal(modalElement);
                  }

                  // Init datatables for all branch tabs
                  initDatatables();
                  initSelect2();
                  handleSearch();
                  handleBalanceFilter();
                  handleTabChange();

                  if (form) {
                        handleSettleButton();
                        handleFullAmount();
                        handleFormSubmit();
                        handleModalReset();
                  }
            }
      }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTSettlements.init();
});