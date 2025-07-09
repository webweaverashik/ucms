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
                  "autoWidth": false,  // Disable auto width
                  'columnDefs': [
                        { orderable: false, targets: 8 }, // Disable ordering on column Actions                
                  ]
            });

            // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
            datatable.on('draw', function () {

            });
      }

      // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
      var handleSearch = function () {
            const filterSearch = document.querySelector('[data-transaction-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                  datatable.search(e.target.value).draw();
            });
      }

      // Filter Datatable
      var handleFilter = function () {
            // Select filter options
            const filterForm = document.querySelector('[data-transaction-table-filter="form"]');
            const filterButton = filterForm.querySelector('[data-transaction-table-filter="filter"]');
            const resetButton = filterForm.querySelector('[data-transaction-table-filter="reset"]');
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
                                                            location.reload(); // Reload to reflect changes
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
            // Public functions  
            init: function () {
                  table = document.getElementById('kt_transactions_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
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
            console.error('Modal element not found');
            return {
                  init: function () { }
            };
      }

      const form = element.querySelector('#kt_modal_add_transaction_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);
      const studentSelect = document.getElementById('transaction_student_select');
      const invoiceSelect = document.getElementById('student_due_invoice_select');


      // Init add transaction form
      var initAddTransaction = () => {

      }

      var initCloseModal = () => {

            // Reset Select2 inputs

            // Cancel button handler
            const cancelButton = element.querySelector('[data-kt-add-transaction-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();

                        if (studentSelect && $(studentSelect).data('select2')) {
                              $(studentSelect).val(null).trigger('change');
                        }

                        if (invoiceSelect && $(invoiceSelect).data('select2')) {
                              $(invoiceSelect).val(null).trigger('change');
                        }

                        // Reset amount input
                        const amountInput = document.getElementById('transaction_amount_input');
                        if (amountInput) {
                              amountInput.value = '';
                              amountInput.disabled = true;
                        }

                        // Remove previous error state
                        $('#transaction_amount_input').removeClass('is-invalid');
                        $('#transaction_amount_error').remove();

                        modal.hide();
                  });
            }

            // Close button handler
            const closeButton = element.querySelector('[data-kt-add-transaction-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();

                        if (studentSelect && $(studentSelect).data('select2')) {
                              $(studentSelect).val(null).trigger('change');
                        }

                        if (invoiceSelect && $(invoiceSelect).data('select2')) {
                              $(invoiceSelect).val(null).trigger('change');
                        }

                        // Reset amount input
                        const amountInput = document.getElementById('transaction_amount_input');
                        if (amountInput) {
                              amountInput.value = '';
                              amountInput.disabled = true;
                        }

                        // Remove previous error state
                        $('#transaction_amount_input').removeClass('is-invalid');
                        $('#transaction_amount_error').remove();

                        modal.hide();
                  });
            }
      }

      return {
            init: function () {
                  // initAddTransaction();
                  initCloseModal();
            }
      };
}();


// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTAllTransactionsList.init();
      KTAddTransaction.init();
});