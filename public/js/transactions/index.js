"use strict";

var KTAllTransactionsList = function () {
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
                  "autoWidth": false,
                  'columnDefs': [
                        { orderable: false, targets: 9 },
                  ]
            });

            // Re-init functions on every table re-draw
            datatable.on('draw', function () {

            });
      }

      // Hook export buttons
      var exportButtons = () => {
            const documentTitle = 'Transactions Report';

            var buttons = new $.fn.dataTable.Buttons(datatable, {
                  buttons: [
                        {
                              extend: 'copyHtml5',
                              className: 'buttons-copy',
                              title: documentTitle,
                              exportOptions: {
                                    columns: ':visible:not(.not-export)'
                              }
                        },
                        {
                              extend: 'excelHtml5',
                              className: 'buttons-excel',
                              title: documentTitle,
                              exportOptions: {
                                    columns: ':visible:not(.not-export)'
                              }
                        },
                        {
                              extend: 'csvHtml5',
                              className: 'buttons-csv',
                              title: documentTitle,
                              exportOptions: {
                                    columns: ':visible:not(.not-export)'
                              }
                        },
                        {
                              extend: 'pdfHtml5',
                              className: 'buttons-pdf',
                              title: documentTitle,
                              exportOptions: {
                                    columns: ':visible:not(.not-export)',
                                    modifier: {
                                          page: 'all',
                                          search: 'applied'
                                    }
                              },
                              customize: function (doc) {
                                    doc.pageMargins = [20, 20, 20, 40];
                                    doc.defaultStyle.fontSize = 10;
                                    doc.footer = getPdfFooterWithPrintTime();
                              }
                        }
                  ]
            }).container().appendTo('#kt_hidden_export_buttons');

            // Hook dropdown export actions
            const exportItems = document.querySelectorAll('#kt_table_report_dropdown_menu [data-row-export]');
            exportItems.forEach(exportItem => {
                  exportItem.addEventListener('click', function (e) {
                        e.preventDefault();
                        const exportValue = this.getAttribute('data-row-export');
                        const target = document.querySelector('.buttons-' + exportValue);
                        if (target) {
                              target.click();
                        } else {
                              console.warn('Export button not found:', exportValue);
                        }
                  });
            });
      };

      // Search Datatable
      var handleSearch = function () {
            const filterSearch = document.querySelector('[data-transaction-table-filter="search"]');
            if (filterSearch) {
                  filterSearch.addEventListener('keyup', function (e) {
                        datatable.search(e.target.value).draw();
                  });
            }
      }

      // Filter Datatable
      var handleFilter = function () {
            const filterForm = document.querySelector('[data-transaction-table-filter="form"]');
            if (!filterForm) return;

            const filterButton = filterForm.querySelector('[data-transaction-table-filter="filter"]');
            const resetButton = filterForm.querySelector('[data-transaction-table-filter="reset"]');
            const selectOptions = filterForm.querySelectorAll('select');

            if (filterButton) {
                  filterButton.addEventListener('click', function () {
                        var filterString = '';

                        selectOptions.forEach((item, index) => {
                              if (item.value && item.value !== '') {
                                    if (index !== 0) {
                                          filterString += ' ';
                                    }
                                    filterString += item.value;
                              }
                        });

                        datatable.search(filterString).draw();
                  });
            }

            if (resetButton) {
                  resetButton.addEventListener('click', function () {
                        selectOptions.forEach((item, index) => {
                              $(item).val(null).trigger('change');
                        });
                        datatable.search('').draw();
                  });
            }
      }

      // Delete Transaction
      const handleDeletion = function () {
            document.addEventListener('click', function (e) {
                  const deleteBtn = e.target.closest('.delete-txn');
                  if (!deleteBtn) return;

                  e.preventDefault();

                  let txnId = deleteBtn.getAttribute('data-txn-id');
                  console.log('TXN ID:', txnId);

                  let url = routeDeleteTxn.replace(':id', txnId);

                  Swal.fire({
                        title: 'Are you sure you want to delete?',
                        text: "Once deleted, this transaction will be removed.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it',
                        cancelButtonText: 'Cancel',
                  }).then((result) => {
                        if (result.isConfirmed) {
                              fetch(url, {
                                    method: "DELETE",
                                    headers: {
                                          "Content-Type": "application/json",
                                          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                                    },
                              })
                                    .then(response => response.json())
                                    .then(data => {
                                          if (data.success) {
                                                Swal.fire({
                                                      title: 'Success!',
                                                      text: 'Transaction deleted successfully.',
                                                      icon: 'success',
                                                      confirmButtonText: 'Okay',
                                                }).then(() => {
                                                      location.reload();
                                                });
                                          } else {
                                                Swal.fire('Failed!', 'Transaction could not be deleted.', 'error');
                                          }
                                    })
                                    .catch(error => {
                                          console.error("Fetch Error:", error);
                                          Swal.fire('Failed!', 'An error occurred. Please contact support.', 'error');
                                    });
                        }
                  });
            });
      };

      // Transaction approval AJAX
      const handleApproval = function () {
            document.querySelectorAll('.approve-txn').forEach(item => {
                  item.addEventListener('click', function (e) {
                        e.preventDefault();

                        let txnId = this.getAttribute('data-txn-id');
                        console.log("TXN ID: ", txnId);

                        Swal.fire({
                              title: 'Are you sure?',
                              text: "Do you want to approve this transaction?",
                              icon: 'warning',
                              showCancelButton: true,
                              confirmButtonColor: '#3085d6',
                              cancelButtonColor: '#d33',
                              confirmButtonText: 'Yes, approve!'
                        }).then((result) => {
                              if (result.isConfirmed) {
                                    fetch(`/transactions/${txnId}/approve`, {
                                          method: "POST",
                                          headers: {
                                                "Content-Type": "application/json",
                                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                                          }
                                    })
                                          .then(response => response.json())
                                          .then(data => {
                                                if (data.success) {
                                                      Swal.fire({
                                                            title: "Approved!",
                                                            text: "Transaction approved successfully.",
                                                            icon: "success",
                                                      }).then(() => {
                                                            location.reload();
                                                      });
                                                } else {
                                                      Swal.fire({
                                                            title: "Error!",
                                                            text: data.message,
                                                            icon: "warning",
                                                      });
                                                }
                                          })
                                          .catch(error => {
                                                console.error("Fetch Error:", error);
                                                Swal.fire({
                                                      title: "Error!",
                                                      text: "Something went wrong. Please try again.",
                                                      icon: "error",
                                                });
                                          });
                              }
                        });
                  });
            });
      };

      return {
            init: function () {
                  table = document.getElementById('kt_transactions_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  exportButtons();
                  handleSearch();
                  handleFilter();
                  handleDeletion();
                  handleApproval();
            }
      }
}();


var KTAddTransaction = function () {
      // Shared variables
      const element = document.getElementById('kt_modal_add_transaction');

      // Early return if element doesn't exist
      if (!element) {
            return {
                  init: function () { }
            };
      }

      const form = element.querySelector('#kt_modal_add_transaction_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);
      const studentSelect = document.getElementById('transaction_student_select');
      const invoiceSelect = document.getElementById('student_due_invoice_select');
      const amountInput = document.getElementById('transaction_amount_input');

      // Store invoices data
      let invoices = [];

      // Track if selected invoice is partially paid
      let isPartiallyPaidInvoice = false;

      // Format "07_2025" to "July 2025"
      var formatMonthYear = function (raw) {
            if (!raw) return '';

            const [monthStr, year] = raw.split('_');
            const month = parseInt(monthStr, 10);

            const monthNames = [
                  'January', 'February', 'March', 'April', 'May', 'June',
                  'July', 'August', 'September', 'October', 'November', 'December'
            ];

            if (month >= 1 && month <= 12 && year) {
                  return `${monthNames[month - 1]} ${year}`;
            }

            return raw;
      }

      // Fetch invoices on student select
      var handleStudentSelect = function () {
            $(studentSelect).on('change', function () {
                  const studentId = $(this).val();
                  if (!studentId) return;

                  $.ajax({
                        url: `/students/${studentId}/due-invoices`,
                        method: 'GET',
                        success: function (response) {
                              invoices = response;
                              const $invoiceSelect = $(invoiceSelect);
                              $invoiceSelect.empty().append(`<option value="">Select Due Invoice</option>`);

                              if (response.length === 0) {
                                    $invoiceSelect.append(`<option disabled>No due invoices found</option>`);
                              } else {
                                    response.forEach(invoice => {
                                          const total = Number(invoice.total_amount).toLocaleString('en-BD');
                                          const due = Number(invoice.amount_due).toLocaleString('en-BD');

                                          const label = invoice.month_year
                                                ? formatMonthYear(invoice.month_year)
                                                : (invoice.invoice_type || 'Unknown');

                                          $invoiceSelect.append(
                                                `<option value="${invoice.id}">
                                    ${invoice.invoice_number} (${label}) - Total: ৳${total}, Due: ৳${due}
                                </option>`
                                          );
                                    });
                              }

                              $(amountInput)
                                    .val('')
                                    .prop('disabled', true)
                                    .removeClass('is-invalid');
                              $('#transaction_amount_error').remove();
                              $('input[name="transaction_type"]').prop('disabled', false);
                        },
                        error: function () {
                              alert('Failed to load due invoices. Please try again.');
                        }
                  });
            });
      }

      // Populate amount and adjust payment options when invoice selected
      var handleInvoiceSelect = function () {
            $(invoiceSelect).on('change', function () {
                  const selectedId = $(this).val();
                  const invoice = invoices.find(inv => inv.id == selectedId);

                  if (invoice) {
                        const $amountInput = $(amountInput);
                        $amountInput
                              .val(invoice.amount_due)
                              .prop('disabled', false)
                              .data('max', invoice.amount_due)
                              .attr('min', 1);

                        const $fullPaymentOption = $('input[name="transaction_type"][value="full"]');
                        const $partialPaymentOption = $('input[name="transaction_type"][value="partial"]');

                        if (invoice.amount_due < invoice.total_amount) {
                              // Invoice is partially paid
                              isPartiallyPaidInvoice = true;
                              $fullPaymentOption.prop('disabled', true).prop('checked', false);
                              $partialPaymentOption.prop('checked', true);
                              $amountInput.val('');
                        } else {
                              // Fresh invoice
                              isPartiallyPaidInvoice = false;
                              $fullPaymentOption.prop('disabled', false);
                              $partialPaymentOption.prop('disabled', false);
                              $fullPaymentOption.prop('checked', true);
                              $amountInput.val(invoice.amount_due);
                        }
                  }
            });
      }

      // Toggle input behavior for payment type
      var handlePaymentTypeChange = function () {
            $('input[name="transaction_type"]').on('change', function () {
                  const paymentType = $(this).val();
                  const $amountInput = $(amountInput);
                  const selectedId = $(invoiceSelect).val();
                  const invoice = invoices.find(inv => inv.id == selectedId);

                  if (invoice) {
                        if (paymentType === 'partial') {
                              $amountInput.val('');
                        } else if (paymentType === 'discounted') {
                              $amountInput.val('');
                        } else {
                              $amountInput.val(invoice.amount_due);
                        }
                  }
            });
      }

      // Validate amount input
      var handleAmountValidation = function () {
            $(amountInput).on('input', function () {
                  const amount = parseFloat($(this).val());
                  const maxAmount = parseFloat($(this).data('max'));
                  const paymentType = $('input[name="transaction_type"]:checked').val();

                  // Remove previous error state
                  $(this).removeClass('is-invalid');
                  $('#transaction_amount_error').remove();

                  // Validate the amount
                  let isValid = true;
                  let errorMessage = '';

                  if (isNaN(amount)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid number';
                  } else if (amount < 1) {
                        isValid = false;
                        errorMessage = 'Amount must be at least ৳1';
                  } else if (
                        (paymentType === 'partial' || paymentType === 'discounted') &&
                        !isPartiallyPaidInvoice &&
                        amount >= maxAmount
                  ) {
                        // For fresh invoices, partial/discounted must be less than max
                        isValid = false;
                        errorMessage = `For ${paymentType} payment, amount must be less than the due amount of ৳${maxAmount}`;
                  } else if (
                        (paymentType === 'partial' || paymentType === 'discounted') &&
                        isPartiallyPaidInvoice &&
                        amount > maxAmount
                  ) {
                        // For partially paid invoices, allow equal to or less than remaining amount
                        isValid = false;
                        errorMessage = `Amount must be less than or equal to the due amount of ৳${maxAmount}`;
                  } else if (paymentType === 'full' && amount != maxAmount) {
                        isValid = false;
                        errorMessage = `For full payment, amount must be exactly ৳${maxAmount}`;
                  }

                  if (!isValid) {
                        $(this).addClass('is-invalid');
                        $(this).after(
                              `<div class="invalid-feedback" id="transaction_amount_error">
                        ${errorMessage}
                    </div>`
                        );
                  }
            });
      }

      // Form submission validation
      var handleFormSubmit = function () {
            $(form).on('submit', function (e) {
                  const amount = parseFloat($(amountInput).val());
                  const maxAmount = parseFloat($(amountInput).data('max'));
                  const paymentType = $('input[name="transaction_type"]:checked').val();

                  let isValid = true;

                  if (isNaN(amount)) {
                        isValid = false;
                  } else if (amount < 1) {
                        isValid = false;
                  } else if (
                        (paymentType === 'partial' || paymentType === 'discounted') &&
                        !isPartiallyPaidInvoice &&
                        amount >= maxAmount
                  ) {
                        // For fresh invoices, partial/discounted must be less than max
                        isValid = false;
                  } else if (
                        (paymentType === 'partial' || paymentType === 'discounted') &&
                        isPartiallyPaidInvoice &&
                        amount > maxAmount
                  ) {
                        // For partially paid invoices, allow equal to or less than remaining amount
                        isValid = false;
                  } else if (paymentType === 'full' && amount != maxAmount) {
                        isValid = false;
                  }

                  if (!isValid || $(amountInput).hasClass('is-invalid')) {
                        e.preventDefault();
                        toastr.warning('Please enter a valid amount.');
                        return false;
                  }

                  return true;
            });
      }

      // Reset form and close modal
      var resetForm = function () {
            if (form) form.reset();

            if (studentSelect && $(studentSelect).data('select2')) {
                  $(studentSelect).val(null).trigger('change');
            }

            if (invoiceSelect && $(invoiceSelect).data('select2')) {
                  $(invoiceSelect).val(null).trigger('change');
            }

            if (amountInput) {
                  amountInput.value = '';
                  amountInput.disabled = true;
            }

            $(amountInput).removeClass('is-invalid');
            $('#transaction_amount_error').remove();

            // Reset invoices array and flags
            invoices = [];
            isPartiallyPaidInvoice = false;
      }

      // Handle modal close actions
      var handleCloseModal = function () {
            // Cancel button handler
            const cancelButton = element.querySelector('[data-kt-add-transaction-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        resetForm();
                        modal.hide();
                  });
            }

            // Close button handler
            const closeButton = element.querySelector('[data-kt-add-transaction-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        resetForm();
                        modal.hide();
                  });
            }
      }

      return {
            init: function () {
                  handleStudentSelect();
                  handleInvoiceSelect();
                  handlePaymentTypeChange();
                  handleAmountValidation();
                  handleFormSubmit();
                  handleCloseModal();
            }
      };
}();


// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTAllTransactionsList.init();
      KTAddTransaction.init();
});