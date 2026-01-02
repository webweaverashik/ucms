"use strict";

// Class definition
var KTWalletHistory = function () {
      // Shared variables
      var table;
      var datatable;
      var modal;
      var modalElement;
      var form;
      var submitButton;
      var currentBalance = 0;

      // Init DataTable
      var initDatatable = function () {
            datatable = $(table).DataTable({
                  info: true,
                  order: [[0, 'desc']],
                  pageLength: 25,
                  lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                  columnDefs: [
                        { orderable: false, targets: [2] }
                  ],
                  language: {
                        emptyTable: `<div class="d-flex flex-column align-items-center py-10">
                    <i class="ki-outline ki-document fs-3x text-gray-400 mb-3"></i>
                    <span class="text-gray-500 fs-5">No transactions found</span>
                </div>`
                  }
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

      // Handle type filter
      var handleTypeFilter = function () {
            const filterType = $('[data-kt-filter="type"]');
            if (filterType.length) {
                  filterType.on('change', function () {
                        var val = $(this).val();

                        // Custom filter function
                        $.fn.dataTable.ext.search.pop(); // Remove previous filter

                        if (val) {
                              $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                                    var row = datatable.row(dataIndex).node();
                                    var rowType = $(row).data('type');
                                    return rowType === val;
                              });
                        }

                        datatable.draw();
                  });
            }
      }

      // Get current balance from data attribute
      var initBalance = function () {
            var balanceElement = document.querySelector('[data-wallet-balance]');
            if (balanceElement) {
                  currentBalance = parseFloat(balanceElement.dataset.walletBalance) || 0;
            }
      }

      // Handle settlement button click
      var handleSettleButton = function () {
            $(document).on('click', '.btn-settle', function (e) {
                  e.preventDefault();

                  var balance = parseFloat($(this).data('balance'));
                  currentBalance = balance;

                  $('#modal_current_balance').text('৳' + balance.toLocaleString('en-US', { minimumFractionDigits: 2 }));
                  $('#settlement_amount').val('').attr('max', balance);
                  $('#amount_error').text('');
                  $('textarea[name="notes"]').val('');

                  if (modal) {
                        modal.show();
                  }
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
            if (!form) return;

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
                                    confirmButtonText: "Ok!",
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
                                    confirmButtonText: "Ok",
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
            if (!modalElement) return;

            modalElement.addEventListener('hidden.bs.modal', function () {
                  form.reset();
                  $('#amount_error').text('');

                  // Remove custom filter when modal closes
                  $.fn.dataTable.ext.search.pop();
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
                  table = document.getElementById('kt_wallet_logs_table');
                  modalElement = document.getElementById('kt_modal_settlement');
                  form = document.getElementById('kt_modal_settlement_form');
                  submitButton = document.getElementById('btn_submit_settlement');

                  if (modalElement) {
                        modal = new bootstrap.Modal(modalElement);
                  }

                  if (table) {
                        initDatatable();
                        initSelect2();
                        handleSearch();
                        handleTypeFilter();
                  }

                  initBalance();
                  handleSettleButton();
                  handleFullAmount();

                  if (form) {
                        handleFormSubmit();
                        handleModalReset();
                  }
            }
      }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTWalletHistory.init();
});