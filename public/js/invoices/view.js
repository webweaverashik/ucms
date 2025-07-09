"use strict";

var KTInvoiceWithTransactionsList = function () {
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

      // Delete pending Invoice
      const handleInvoiceDeletion = function () {
            document.querySelectorAll('.delete-invoice').forEach(item => {
                  item.addEventListener('click', function (e) {
                        e.preventDefault();

                        let invoiceId = this.getAttribute('data-invoice-id');
                        let url = routeDeleteInvoice.replace(':id', invoiceId);  // Replace ':id' with actual invoice ID

                        Swal.fire({
                              title: "Are you sure to delete this invoice?",
                              text: "This action cannot be undone!",
                              icon: "warning",
                              showCancelButton: true,
                              confirmButtonColor: "#d33",
                              cancelButtonColor: "#3085d6",
                              confirmButtonText: "Yes, delete!",
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
                                                            title: "Deleted!",
                                                            text: "The invoice has been deleted successfully.",
                                                            icon: "success",
                                                      }).then(() => {
                                                            window.location.href = '/invoices';
                                                      });
                                                } else {
                                                      Swal.fire({
                                                            title: "Error!",
                                                            text: data.error,
                                                            icon: "error",
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


      // Delete Transaction
      const handleTransactionDeletion = function () {
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
      const handleTransactionApproval = function () {
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
                  table = document.getElementById('kt_invoice_transactions_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  handleInvoiceDeletion();
                  handleTransactionDeletion();
                  handleTransactionApproval();
            }
      }
}();


var KTEditInvoiceModal = function () {
      // Shared variables
      const element = document.getElementById('kt_modal_edit_invoice');

      // Early return if element doesn't exist
      if (!element) {
            console.error('Modal element not found');
            return {
                  init: function () { }
            };
      }

      const form = element.querySelector('#kt_modal_edit_invoice_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);

      let invoiceId = null; // Declare globally

      // Init edit invoice modal
      var initEditInvoice = () => {
            // Cancel button handler
            const cancelButton = element.querySelector('[data-kt-edit-invoice-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        modal.hide();
                  });
            }

            // Close button handler
            const closeButton = element.querySelector('[data-kt-edit-invoice-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        modal.hide();
                  });
            }

            // AJAX form data load
            const editButtons = document.querySelectorAll("[data-bs-target='#kt_modal_edit_invoice']");
            if (editButtons.length) {
                  editButtons.forEach((button) => {
                        button.addEventListener("click", function () {
                              invoiceId = this.getAttribute("data-invoice-id"); // Assign value globally
                              console.log("Invoice ID:", invoiceId);
                              if (!invoiceId) return;

                              // Clear form
                              if (form) form.reset();

                              fetch(`/invoices/${invoiceId}/view-ajax`)
                                    .then(response => {
                                          if (!response.ok) throw new Error('Network response was not ok');
                                          return response.json();
                                    })
                                    .then(data => {
                                          if (data.success && data.data) {
                                                if (!data.success || !data.data) {
                                                      throw new Error("Invalid response data");
                                                }

                                                const invoice = data.data;

                                                // Set modal title
                                                const titleEl = document.getElementById("kt_modal_edit_invoice_title");
                                                if (titleEl) {
                                                      titleEl.textContent = `Update Invoice ${invoice.invoice_number}`;
                                                }

                                                // Show/hide #invoice_type_id_edit based on invoice_type
                                                const monthYearWrapper = document.querySelector("#month_year_id_edit");
                                                if (monthYearWrapper) {
                                                      if (invoice.invoice_type !== 'tuition_fee') {
                                                            monthYearWrapper.style.display = 'none';
                                                      } else {
                                                            monthYearWrapper.style.display = '';
                                                      }
                                                }

                                                // Populate regular input fields
                                                document.querySelector("input[name='invoice_amount_edit']").value = invoice.total_amount;

                                                // Set Select2 values and trigger change
                                                const setSelect2Value = (name, value) => {
                                                      const el = $(`select[name="${name}"]`);
                                                      if (el.length) {
                                                            el.val(value).trigger('change');
                                                      }
                                                };

                                                // Populate form fields
                                                setSelect2Value("invoice_student_edit", invoice.student_id);
                                                setSelect2Value("invoice_type_edit", invoice.invoice_type);
                                                setSelect2Value("invoice_month_year_edit", invoice.month_year);

                                                // Show modal (assumes Bootstrap modal)
                                                modal.show();
                                          } else {
                                                throw new Error(data.message || 'Invalid response data');
                                          }
                                    })
                                    .catch(error => {
                                          console.error("Error:", error);
                                          toastr.error(error.message || "Failed to load invoice details");
                                    });
                        });
                  });
            }

      }

      // Form validation
      var initValidation = function () {
            if (!form) return;

            var validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'invoice_amount_edit': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Amount is required'
                                          }
                                    }
                              }
                        },
                        plugins: {
                              trigger: new FormValidation.plugins.Trigger(),
                              bootstrap: new FormValidation.plugins.Bootstrap5({
                                    rowSelector: '.fv-row',
                                    eleInvalidClass: '',
                                    eleValidClass: ''
                              })
                        }
                  }
            );

            const submitButton = element.querySelector('[data-kt-edit-invoice-modal-action="submit"]');

            if (submitButton && validator) {
                  submitButton.addEventListener('click', function (e) {
                        e.preventDefault(); // Prevent default button behavior

                        validator.validate().then(function (status) {
                              if (status === 'Valid') {
                                    // Show loading indicator
                                    submitButton.setAttribute('data-kt-indicator', 'on');
                                    submitButton.disabled = true;

                                    const formData = new FormData(form);
                                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                                    formData.append('_method', 'PUT');

                                    fetch(`/invoices/${invoiceId}`, {
                                          method: 'POST',
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(response => {
                                                if (!response.ok) throw new Error('Network response was not ok');
                                                return response.json();
                                          })
                                          .then(data => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      toastr.success(data.message || 'Invoice updated successfully');
                                                      modal.hide();
                                                      window.location.reload();
                                                } else {
                                                      throw new Error(data.message || 'Invoice Update failed');
                                                }
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;
                                                toastr.error(error.message || 'Failed to update invoice');
                                                console.error('Error:', error);
                                          });
                              } else {
                                    toastr.warning('Please fill all required fields correctly');
                              }
                        });
                  });
            }

      }

      return {
            init: function () {
                  initEditInvoice();
                  initValidation();
            }
      };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTInvoiceWithTransactionsList.init();
      KTEditInvoiceModal.init();
});