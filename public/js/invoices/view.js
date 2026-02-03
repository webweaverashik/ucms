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
                  "autoWidth": false, // Disable auto width
                  'columnDefs': [
                        { orderable: false, targets: 8 }, // Disable ordering on column Actions
                  ]
            });

            // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
            datatable.on('draw', function () {
            });
      }

      // Delete pending Invoice
      var handleInvoiceDeletion = function () {
            document.querySelectorAll('.delete-invoice').forEach(item => {
                  item.addEventListener('click', function (e) {
                        e.preventDefault();
                        let invoiceId = this.getAttribute('data-invoice-id');
                        let url = routeDeleteInvoice.replace(':id', invoiceId); // Replace ':id' with actual invoice ID

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
      var handleTransactionDeletion = function () {
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
      var handleTransactionApproval = function () {
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

      // Statement Download Handler
      const handleStatementDownload = function () {
            document.addEventListener('click', function (e) {
                  const downloadBtn = e.target.closest('.download-statement');
                  if (!downloadBtn) return;

                  e.preventDefault();

                  const studentId = downloadBtn.getAttribute('data-student-id');
                  const year = downloadBtn.getAttribute('data-year');
                  const invoiceId = downloadBtn.getAttribute('data-invoice-id');

                  if (!studentId || !year) {
                        Swal.fire({
                              title: 'Error!',
                              text: 'Missing student or year information.',
                              icon: 'error',
                        });
                        return;
                  }

                  // Show loading state on button
                  const originalIcon = downloadBtn.innerHTML;
                  downloadBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                  downloadBtn.style.pointerEvents = 'none';

                  // Create FormData for POST request
                  const formData = new FormData();
                  formData.append('student_id', studentId);
                  formData.append('statement_year', year);
                  formData.append('invoice_id', invoiceId);

                  fetch(routeDownloadStatement, {
                        method: "POST",
                        headers: {
                              "X-CSRF-TOKEN": csrfToken,
                        },
                        body: formData
                  })
                        .then(response => {
                              if (!response.ok) {
                                    // Try to parse error message from response
                                    return response.text().then(text => {
                                          throw new Error(text || 'Server error occurred');
                                    });
                              }
                              return response.text();
                        })
                        .then(html => {
                              // Create a new window with the HTML content
                              const printWindow = window.open("", "_blank", "width=900,height=700,scrollbars=yes,resizable=yes");
                              if (printWindow) {
                                    printWindow.document.open();
                                    printWindow.document.write(html);
                                    printWindow.document.close();
                                    // Focus on the new window
                                    printWindow.focus();
                              } else {
                                    // Popup blocked
                                    Swal.fire({
                                          title: 'Popup Blocked!',
                                          text: 'Please allow popups for this website to view the statement.',
                                          icon: 'warning',
                                    });
                              }

                              // Restore button state
                              downloadBtn.innerHTML = originalIcon;
                              downloadBtn.style.pointerEvents = 'auto';
                        })
                        .catch(error => {
                              console.error("Statement Download Error:", error);

                              // Check if the error message indicates no transactions
                              const errorMessage = error.message.toLowerCase();
                              if (errorMessage.includes('no transactions')) {
                                    Swal.fire({
                                          title: 'No Data Found',
                                          text: 'No transactions found for the selected year.',
                                          icon: 'info',
                                    });
                              } else {
                                    Swal.fire({
                                          title: 'Error!',
                                          text: 'Failed to load statement. Please try again.',
                                          icon: 'error',
                                    });
                              }

                              // Restore button state
                              downloadBtn.innerHTML = originalIcon;
                              downloadBtn.style.pointerEvents = 'auto';
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
                  handleStatementDownload();
            }
      }
}();

var KTEditInvoiceModal = function () {
      // Shared variables
      var element;
      var form;
      var modal;
      var submitButton;
      var invoiceId = null;
      var maxTuitionFee = 0;

      // Format month_year (e.g., "01_2025" to "January 2025")
      var formatMonthYear = function (monthYear) {
            if (!monthYear || monthYear === 'null' || monthYear === '') return 'N/A';

            var parts = monthYear.split('_');
            if (parts.length !== 2) return monthYear;

            var month = parseInt(parts[0], 10);
            var year = parts[1];

            var monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                  'July', 'August', 'September', 'October', 'November', 'December'];

            if (month >= 1 && month <= 12) {
                  return monthNames[month - 1] + ' ' + year;
            }

            return monthYear;
      };

      // Clear validation error
      var clearValidationError = function () {
            var amountInput = document.getElementById('invoice_amount_edit');
            var errorDiv = document.getElementById('invoice_amount_error');

            if (amountInput) {
                  amountInput.classList.remove('is-invalid');
            }

            if (errorDiv) {
                  errorDiv.style.display = 'none';
                  errorDiv.textContent = '';
            }
      };

      // Show validation error
      var showValidationError = function (message) {
            var amountInput = document.getElementById('invoice_amount_edit');
            var errorDiv = document.getElementById('invoice_amount_error');

            if (amountInput) {
                  amountInput.classList.add('is-invalid');
            }

            if (errorDiv) {
                  errorDiv.style.display = 'block';
                  errorDiv.textContent = message;
            }
      };

      // Validate amount
      var validateAmount = function (amount, showToast) {
            // Clear previous error
            clearValidationError();

            // Check if empty
            if (amount === '' || amount === null || amount === undefined) {
                  showValidationError('Amount is required');
                  if (showToast && typeof toastr !== 'undefined') {
                        toastr.warning('Amount is required');
                  }
                  return false;
            }

            var numAmount = parseFloat(amount);

            // Check if valid number
            if (isNaN(numAmount)) {
                  showValidationError('Please enter a valid number');
                  if (showToast && typeof toastr !== 'undefined') {
                        toastr.warning('Please enter a valid number');
                  }
                  return false;
            }

            // Check minimum
            if (numAmount < 1) {
                  showValidationError('Amount must be at least ৳1');
                  if (showToast && typeof toastr !== 'undefined') {
                        toastr.warning('Amount must be at least ৳1');
                  }
                  return false;
            }

            // Check maximum (tuition fee)
            if (maxTuitionFee > 0 && numAmount > maxTuitionFee) {
                  showValidationError('Amount cannot exceed the tuition fee of ৳' + maxTuitionFee);
                  if (showToast && typeof toastr !== 'undefined') {
                        toastr.warning('Amount cannot exceed the tuition fee of ৳' + maxTuitionFee);
                  }
                  return false;
            }

            return true;
      };

      // Reset form and modal state
      var resetForm = function () {
            if (form) {
                  form.reset();
            }

            // Reset hidden field
            var hiddenInput = document.getElementById('edit_invoice_id');
            if (hiddenInput) hiddenInput.value = '';

            // Reset display fields
            var studentDisplay = document.getElementById('edit_student_display');
            if (studentDisplay) studentDisplay.innerHTML = '<span class="text-muted">-</span>';

            var typeDisplay = document.getElementById('edit_invoice_type_display');
            if (typeDisplay) typeDisplay.innerHTML = '<span class="text-muted">-</span>';

            var monthYearDisplay = document.getElementById('edit_month_year_display');
            if (monthYearDisplay) monthYearDisplay.innerHTML = '<span class="text-muted">-</span>';

            // Reset amount input
            var amountInput = document.getElementById('invoice_amount_edit');
            if (amountInput) {
                  amountInput.value = '';
            }

            // Show month year wrapper by default
            var monthYearWrapper = document.getElementById('month_year_edit_wrapper');
            if (monthYearWrapper) monthYearWrapper.style.display = '';

            // Reset title
            var titleEl = document.getElementById('kt_modal_edit_invoice_title');
            if (titleEl) titleEl.textContent = 'Update Invoice';

            // Reset hint
            var hintDiv = document.getElementById('invoice_amount_hint');
            if (hintDiv) hintDiv.textContent = '';

            // Clear validation error
            clearValidationError();

            invoiceId = null;
            maxTuitionFee = 0;
      };

      // Populate modal with invoice data from data attributes
      var populateModal = function (button) {
            // Get data from button attributes
            invoiceId = button.getAttribute('data-invoice-id');
            var invoiceNumber = button.getAttribute('data-invoice-number');
            var studentId = button.getAttribute('data-student-id');
            var studentName = button.getAttribute('data-student-name');
            var studentUniqueId = button.getAttribute('data-student-unique-id');
            var invoiceTypeId = button.getAttribute('data-invoice-type-id');
            var invoiceTypeName = button.getAttribute('data-invoice-type-name');
            var monthYear = button.getAttribute('data-month-year');
            var totalAmount = button.getAttribute('data-total-amount');
            var tuitionFee = button.getAttribute('data-tuition-fee');

            // Store tuition fee for validation
            maxTuitionFee = tuitionFee ? parseFloat(tuitionFee) : 0;

            console.log('Edit Invoice:', {
                  id: invoiceId,
                  invoiceNumber: invoiceNumber,
                  studentName: studentName,
                  studentUniqueId: studentUniqueId,
                  invoiceTypeName: invoiceTypeName,
                  monthYear: monthYear,
                  totalAmount: totalAmount,
                  tuitionFee: tuitionFee
            });

            if (!invoiceId) {
                  console.error('No invoice ID found');
                  return;
            }

            // Store invoice ID in hidden field
            document.getElementById('edit_invoice_id').value = invoiceId;

            // Set modal title
            var titleEl = document.getElementById('kt_modal_edit_invoice_title');
            if (titleEl) {
                  titleEl.textContent = 'Update Invoice ' + invoiceNumber;
            }

            // Set student display
            var studentDisplay = document.getElementById('edit_student_display');
            if (studentDisplay) {
                  var displayText = studentName || 'Unknown';
                  if (studentUniqueId) {
                        displayText += ' (' + studentUniqueId + ')';
                  }
                  studentDisplay.innerHTML = '<span class="fw-semibold">' + displayText + '</span>';
            }

            // Set invoice type display
            var typeDisplay = document.getElementById('edit_invoice_type_display');
            if (typeDisplay) {
                  typeDisplay.innerHTML = '<span class="fw-semibold">' + (invoiceTypeName || '-') + '</span>';
            }

            // Show/hide month_year wrapper based on invoice type
            var monthYearWrapper = document.getElementById('month_year_edit_wrapper');
            var typeNameLower = invoiceTypeName ? invoiceTypeName.toLowerCase().replace(/ /g, '_') : '';

            if (monthYearWrapper) {
                  if (typeNameLower !== 'tuition_fee') {
                        monthYearWrapper.style.display = 'none';
                  } else {
                        monthYearWrapper.style.display = '';
                        // Set month year display
                        var monthYearDisplay = document.getElementById('edit_month_year_display');
                        if (monthYearDisplay) {
                              monthYearDisplay.innerHTML = '<span class="fw-semibold">' + formatMonthYear(monthYear) + '</span>';
                        }
                  }
            }

            // Set amount
            var amountInput = document.getElementById('invoice_amount_edit');
            if (amountInput) {
                  amountInput.value = totalAmount || '';
            }

            // Set hint text
            var hintDiv = document.getElementById('invoice_amount_hint');
            if (hintDiv && maxTuitionFee > 0) {
                  hintDiv.textContent = 'Maximum allowed: ৳' + maxTuitionFee + ' (Tuition Fee)';
            }

            // Show modal
            modal.show();
      };

      // Handle real-time validation on amount input
      var handleAmountInputValidation = function () {
            var amountInput = document.getElementById('invoice_amount_edit');
            if (!amountInput) return;

            amountInput.addEventListener('input', function () {
                  validateAmount(this.value, false); // No toastr on input
            });

            amountInput.addEventListener('blur', function () {
                  validateAmount(this.value, false); // No toastr on blur
            });
      };

      // Handle form submission via AJAX
      var handleFormSubmit = function () {
            form.addEventListener('submit', function (e) {
                  e.preventDefault();

                  // Get the amount value
                  var amountInput = document.getElementById('invoice_amount_edit');
                  var amountValue = amountInput ? amountInput.value : '';

                  // Validate amount (with toastr warning)
                  if (!validateAmount(amountValue, true)) {
                        return;
                  }

                  if (!invoiceId) {
                        if (typeof toastr !== 'undefined') {
                              toastr.error('Invoice ID not found');
                        } else {
                              Swal.fire({
                                    title: 'Error!',
                                    text: 'Invoice ID not found',
                                    icon: 'error'
                              });
                        }
                        return;
                  }

                  // Show loading indicator
                  submitButton.setAttribute('data-kt-indicator', 'on');
                  submitButton.disabled = true;

                  // Prepare form data
                  var formData = new FormData();
                  formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                  formData.append('_method', 'PUT');
                  formData.append('invoice_amount_edit', amountValue);

                  // Build URL
                  var url = routeUpdateInvoice.replace(':id', invoiceId);

                  fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                              'Accept': 'application/json',
                              'X-Requested-With': 'XMLHttpRequest'
                        }
                  })
                        .then(function (response) {
                              return response.json().then(function (data) {
                                    if (!response.ok) {
                                          throw new Error(data.message || 'Network response was not ok');
                                    }
                                    return data;
                              });
                        })
                        .then(function (data) {
                              // Hide loading indicator
                              submitButton.removeAttribute('data-kt-indicator');
                              submitButton.disabled = false;

                              if (data.success) {
                                    // Hide modal
                                    modal.hide();

                                    Swal.fire({
                                          title: 'Success!',
                                          text: data.message || 'Invoice updated successfully',
                                          icon: 'success',
                                          confirmButtonText: 'OK'
                                    }).then(function () {
                                          window.location.reload();
                                    });
                              } else {
                                    throw new Error(data.message || 'Invoice update failed');
                              }
                        })
                        .catch(function (error) {
                              // Hide loading indicator
                              submitButton.removeAttribute('data-kt-indicator');
                              submitButton.disabled = false;

                              console.error('Error:', error);

                              if (typeof toastr !== 'undefined') {
                                    toastr.error(error.message || 'Failed to update invoice');
                              } else {
                                    Swal.fire({
                                          title: 'Error!',
                                          text: error.message || 'Failed to update invoice',
                                          icon: 'error'
                                    });
                              }
                        });
            });
      };

      // Init edit invoice modal
      var initEditInvoice = function () {
            // Cancel button handler
            var cancelButton = element.querySelector('[data-kt-edit-invoice-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        resetForm();
                        modal.hide();
                  });
            }

            // Close button handler
            var closeButton = element.querySelector('[data-kt-edit-invoice-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        resetForm();
                        modal.hide();
                  });
            }

            // Modal hidden event - reset form when modal is closed
            element.addEventListener('hidden.bs.modal', function () {
                  resetForm();
            });

            // Edit button click handlers
            var editButtons = document.querySelectorAll('.edit-invoice-btn');
            if (editButtons.length) {
                  editButtons.forEach(function (button) {
                        button.addEventListener('click', function (e) {
                              e.preventDefault();
                              // Reset form before loading new data
                              resetForm();
                              // Populate modal with data from button attributes
                              populateModal(this);
                        });
                  });
            }

            // Initialize amount input validation
            handleAmountInputValidation();
      };

      return {
            init: function () {
                  element = document.getElementById('kt_modal_edit_invoice');

                  // Early return if element doesn't exist
                  if (!element) {
                        return;
                  }

                  form = element.querySelector('#kt_modal_edit_invoice_form');
                  submitButton = element.querySelector('#kt_modal_edit_invoice_submit');
                  modal = bootstrap.Modal.getOrCreateInstance(element);

                  if (!form || !submitButton) {
                        console.error('Form or submit button not found');
                        return;
                  }

                  initEditInvoice();
                  handleFormSubmit();
            }
      };
}();

var KTTransactionForm = function () {
      // Shared variables
      var invoice = {};
      var amountInput;
      var fullPaymentOption;
      var partialPaymentOption;
      var discountedPaymentOption;
      var form;
      var modal;
      var element;

      // Private functions
      var initInvoiceData = function () {
            var invoiceInput = document.querySelector('input[name="transaction_invoice"]');
            var studentInput = document.querySelector('input[name="transaction_student"]');
            var amountInputEl = document.getElementById('transaction_amount_input');
            var statusIndicator = document.getElementById('invoice_status_indicator');

            invoice = {
                  id: invoiceInput ? invoiceInput.value : null,
                  student_id: studentInput ? studentInput.value : null,
                  amount_due: amountInputEl ? parseFloat(amountInputEl.value) : 0,
                  total_amount: amountInputEl ? parseFloat(amountInputEl.getAttribute('data-total-amount')) : 0,
                  status: statusIndicator ? (statusIndicator.getAttribute('data-status') || 'unpaid') : 'unpaid'
            };
      };

      var initializeForm = function () {
            if (!amountInput) return;

            // Set up amount input
            amountInput.value = invoice.amount_due;
            amountInput.disabled = false;
            amountInput.setAttribute('data-max', invoice.amount_due);
            amountInput.setAttribute('min', 1);

            // Check invoice status to determine payment options
            if (invoice.status === 'partially_paid' || invoice.amount_due < invoice.total_amount) {
                  // Disable full payment for partially paid invoices
                  if (fullPaymentOption) {
                        fullPaymentOption.disabled = true;
                        fullPaymentOption.checked = false;
                  }

                  if (partialPaymentOption) {
                        partialPaymentOption.checked = true;
                  }

                  if (discountedPaymentOption) {
                        discountedPaymentOption.disabled = false;
                  }

                  amountInput.value = ''; // Clear value for partial payment
            } else {
                  // Enable all options for unpaid invoices
                  if (fullPaymentOption) {
                        fullPaymentOption.disabled = false;
                        fullPaymentOption.checked = true;
                  }

                  if (partialPaymentOption) {
                        partialPaymentOption.disabled = false;
                  }

                  if (discountedPaymentOption) {
                        discountedPaymentOption.disabled = false;
                  }

                  amountInput.value = invoice.amount_due;
            }
      };

      var handlePaymentTypeChange = function () {
            var paymentTypeInputs = document.querySelectorAll('input[name="transaction_type"]');

            paymentTypeInputs.forEach(function (input) {
                  input.addEventListener('change', function () {
                        var paymentType = this.value;

                        if (!amountInput) return;

                        if (paymentType === 'partial') {
                              amountInput.value = ''; // Clear value for partial payment
                        } else if (paymentType === 'discounted') {
                              amountInput.value = ''; // Clear value for discounted payment
                        } else {
                              amountInput.value = invoice.amount_due; // Set to full amount
                        }
                  });
            });
      };

      var validateAmount = function () {
            if (!amountInput) return;

            amountInput.addEventListener('input', function () {
                  var amount = parseFloat(this.value);
                  var maxAmount = parseFloat(this.getAttribute('data-max'));
                  var checkedPaymentType = document.querySelector('input[name="transaction_type"]:checked');
                  var paymentType = checkedPaymentType ? checkedPaymentType.value : 'full';

                  // Remove previous error state
                  this.classList.remove('is-invalid');
                  var existingError = document.getElementById('transaction_amount_error');
                  if (existingError) {
                        existingError.remove();
                  }

                  // Validate the amount
                  var isValid = true;
                  var errorMessage = '';

                  if (isNaN(amount)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid number';
                  } else if (amount < 1) {
                        isValid = false;
                        errorMessage = 'Amount must be at least ৳1';
                  } else if (invoice.status === 'partially_paid') {
                        // For partially paid invoices, allow amount equal to or less than due amount
                        if (amount > maxAmount) {
                              isValid = false;
                              errorMessage = 'Amount must be less than or equal to the due amount of ৳' + maxAmount;
                        }
                  } else if (paymentType === 'partial' && amount >= maxAmount) {
                        isValid = false;
                        errorMessage = 'For partial payment, amount must be less than the due amount of ৳' + maxAmount;
                  } else if (paymentType === 'discounted' && amount >= maxAmount) {
                        isValid = false;
                        errorMessage = 'For discounted payment, amount must be less than the due amount of ৳' + maxAmount;
                  } else if (paymentType === 'full' && amount != maxAmount) {
                        isValid = false;
                        errorMessage = 'For full payment, amount must be exactly ৳' + maxAmount;
                  }

                  if (!isValid) {
                        this.classList.add('is-invalid');
                        var errorDiv = document.createElement('div');
                        errorDiv.className = 'invalid-feedback';
                        errorDiv.id = 'transaction_amount_error';
                        errorDiv.textContent = errorMessage;
                        this.parentNode.insertBefore(errorDiv, this.nextSibling);
                  }
            });
      };

      // Reset form
      var resetForm = function () {
            if (form) form.reset();

            if (amountInput) {
                  amountInput.classList.remove('is-invalid');
                  amountInput.value = invoice.amount_due;
            }

            var existingError = document.getElementById('transaction_amount_error');
            if (existingError) {
                  existingError.remove();
            }

            // Reset payment type selection
            if (invoice.status === 'partially_paid' || invoice.amount_due < invoice.total_amount) {
                  if (partialPaymentOption) {
                        partialPaymentOption.checked = true;
                  }
                  if (amountInput) {
                        amountInput.value = '';
                  }
            } else {
                  if (fullPaymentOption) {
                        fullPaymentOption.checked = true;
                  }
                  if (amountInput) {
                        amountInput.value = invoice.amount_due;
                  }
            }
      };

      // Download statement and then reload page
      var downloadStatementAndReload = function (studentId, year, invoiceId) {  // ✅ ADD invoiceId
            var formData = new FormData();
            formData.append('student_id', studentId);
            formData.append('statement_year', year);
            formData.append('invoice_id', invoiceId);

            fetch(routeDownloadStatement, {
                  method: 'POST',
                  headers: {
                        'X-CSRF-TOKEN': csrfToken,
                  },
                  body: formData
            })
                  .then(function (response) {
                        if (!response.ok) {
                              throw new Error('Failed to load statement');
                        }
                        return response.text();
                  })
                  .then(function (html) {
                        // Open statement in new window
                        var printWindow = window.open('', '_blank', 'width=900,height=700,scrollbars=yes,resizable=yes');
                        if (printWindow) {
                              printWindow.document.open();
                              printWindow.document.write(html);
                              printWindow.document.close();
                              printWindow.focus();
                        } else {
                              Swal.fire({
                                    title: 'Popup Blocked!',
                                    text: 'Please allow popups for this website to view the statement.',
                                    icon: 'warning',
                              });
                        }

                        // Reload the page after opening statement
                        location.reload();
                  })
                  .catch(function (error) {
                        console.error('Statement Download Error:', error);
                        toastr.error('Failed to download statement. Page will reload.');
                        location.reload();
                  });
      };

      var handleFormSubmission = function () {
            if (!form) return;

            form.addEventListener('submit', function (e) {
                  e.preventDefault();

                  var amount = parseFloat(amountInput.value);
                  var maxAmount = parseFloat(amountInput.getAttribute('data-max'));
                  var checkedPaymentType = document.querySelector('input[name="transaction_type"]:checked');
                  var paymentType = checkedPaymentType ? checkedPaymentType.value : 'full';

                  // Check validation
                  var isValid = true;

                  if (isNaN(amount)) {
                        isValid = false;
                  } else if (amount < 1) {
                        isValid = false;
                  } else if (invoice.status === 'partially_paid') {
                        // For partially paid invoices, allow amount equal to or less than due amount
                        if (amount > maxAmount) {
                              isValid = false;
                        }
                  } else if (paymentType === 'partial' && amount >= maxAmount) {
                        isValid = false;
                  } else if (paymentType === 'discounted' && amount >= maxAmount) {
                        isValid = false;
                  } else if (paymentType === 'full' && amount != maxAmount) {
                        isValid = false;
                  }

                  if (!isValid || amountInput.classList.contains('is-invalid')) {
                        toastr.warning('Please enter a valid amount.');
                        return false;
                  }

                  // Get submit button and show loading state
                  var submitBtn = form.querySelector('button[type="submit"]');
                  var originalBtnText = submitBtn.innerHTML;
                  submitBtn.innerHTML = '<span class="indicator-label" style="display: none;">Submit</span><span class="indicator-progress" style="display: inline-block;">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>';
                  submitBtn.disabled = true;

                  // Prepare form data
                  var formData = new FormData(form);

                  // Submit via AJAX
                  fetch(form.action, {
                        method: 'POST',
                        headers: {
                              'X-CSRF-TOKEN': csrfToken,
                              'Accept': 'application/json',
                              'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                  })
                        .then(function (response) {
                              if (!response.ok) {
                                    return response.json().then(function (err) {
                                          throw err;
                                    });
                              }
                              return response.json();
                        })
                        .then(function (data) {
                              if (data.success) {
                                    // Show success message
                                    toastr.success(data.message || 'Transaction recorded successfully.');

                                    // Store transaction data for download
                                    var transactionData = data.transaction;

                                    // Reset form and close modal
                                    resetForm();
                                    if (modal) {
                                          modal.hide();
                                    }

                                    // Check if transaction is approved (non-discounted)
                                    if (transactionData && transactionData.is_approved) {
                                          // Show confirmation to download statement
                                          Swal.fire({
                                                title: 'Transaction Successful!',
                                                text: 'Do you want to download the payment statement?',
                                                icon: 'success',
                                                showCancelButton: true,
                                                confirmButtonColor: '#3085d6',
                                                cancelButtonColor: '#6c757d',
                                                confirmButtonText: 'Yes, download',
                                                cancelButtonText: 'No, just reload'
                                          }).then(function (result) {
                                                if (result.isConfirmed) {
                                                      // Download statement then reload
                                                      downloadStatementAndReload(transactionData.student_id, transactionData.year, transactionData.invoice_id);
                                                } else {
                                                      // Just reload the page
                                                      location.reload();
                                                }
                                          });
                                    } else {
                                          // For discounted transactions (pending approval), just reload
                                          Swal.fire({
                                                title: 'Transaction Recorded!',
                                                text: 'This transaction requires approval before the statement can be downloaded.',
                                                icon: 'info',
                                                confirmButtonText: 'OK'
                                          }).then(function () {
                                                location.reload();
                                          });
                                    }
                              } else {
                                    toastr.error(data.message || 'Failed to record transaction.');

                                    // Reset button state
                                    submitBtn.innerHTML = originalBtnText;
                                    submitBtn.disabled = false;
                              }
                        })
                        .catch(function (error) {
                              console.error('Transaction Error:', error);

                              var errorMessage = 'An error occurred. Please try again.';
                              if (error.message) {
                                    errorMessage = error.message;
                              } else if (error.errors) {
                                    // Laravel validation errors
                                    errorMessage = Object.values(error.errors).flat().join('\n');
                              }

                              toastr.error(errorMessage);

                              // Reset button state
                              submitBtn.innerHTML = originalBtnText;
                              submitBtn.disabled = false;
                        });

                  return false;
            });
      };

      return {
            // Public functions
            init: function () {
                  // Get DOM elements
                  element = document.getElementById('kt_modal_add_transaction');
                  amountInput = document.getElementById('transaction_amount_input');
                  fullPaymentOption = document.querySelector('input[name="transaction_type"][value="full"]');
                  partialPaymentOption = document.querySelector('input[name="transaction_type"][value="partial"]');
                  discountedPaymentOption = document.querySelector('input[name="transaction_type"][value="discounted"]');
                  form = document.getElementById('kt_modal_add_transaction_form');

                  // Early return if required elements don't exist
                  if (!amountInput || !element) {
                        return;
                  }

                  // Initialize modal
                  modal = bootstrap.Modal.getOrCreateInstance(element);

                  // Initialize
                  initInvoiceData();
                  initializeForm();
                  handlePaymentTypeChange();
                  validateAmount();
                  handleFormSubmission();
            }
      };
}();

// Class definition - Add Comment Modal
var KTAddCommentModal = function () {
      // Shared variables
      var element;
      var form;
      var modal;
      var submitButton;
      var invoiceId = null;
      var invoiceNumber = null;

      // Helper function to get CSRF token
      var getCsrfToken = function () {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      };

      // Escape HTML to prevent XSS
      var escapeHtml = function (text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
      };

      // Add comment to the comments container
      var addCommentToContainer = function (comment) {
            var commentsContainer = document.getElementById('comments_container');
            var noCommentsPlaceholder = document.getElementById('no_comments_placeholder');

            // Hide the "no comments" placeholder
            if (noCommentsPlaceholder) {
                  noCommentsPlaceholder.style.display = 'none';
            }

            // Create comment HTML
            var commentHtml = `
            <div class="d-flex mb-5 comment-card p-3 rounded" data-comment-id="${comment.id}">
                <div class="symbol symbol-40px me-4">
                    <span class="symbol-label bg-light-primary text-primary fw-bold">
                        ${comment.commented_by.charAt(0).toUpperCase()}
                    </span>
                </div>
                <div class="d-flex flex-column flex-grow-1">
                    <div class="d-flex align-items-center mb-1">
                        <span class="text-gray-800 fw-bold fs-6 me-3">${escapeHtml(comment.commented_by)}</span>
                        <span class="text-muted fs-7">${comment.created_at}</span>
                    </div>
                    <p class="text-gray-600 fs-6 mb-0">${escapeHtml(comment.comment)}</p>
                </div>
            </div>
            <div class="separator separator-dashed mb-5"></div>
        `;

            // Insert at the beginning of the container
            commentsContainer.insertAdjacentHTML('afterbegin', commentHtml);

            // Update the comment count badge
            var badge = document.getElementById('comments_count_badge');
            if (badge) {
                  var currentCount = parseInt(badge.textContent) || 0;
                  badge.textContent = currentCount + 1;
            }
      };

      // Handle add comment button click
      var handleAddCommentClick = function () {
            document.addEventListener('click', function (e) {
                  var button = e.target.closest('.add-comment-btn');
                  if (!button) return;

                  invoiceId = button.getAttribute('data-invoice-id');
                  invoiceNumber = button.getAttribute('data-invoice-number');

                  if (!invoiceId) return;

                  // Set the invoice ID in the hidden field
                  document.getElementById('comment_invoice_id').value = invoiceId;

                  // Update modal title
                  var titleEl = document.getElementById('kt_modal_add_comment_title');
                  if (titleEl) {
                        titleEl.textContent = 'Add Comment - Invoice ' + (invoiceNumber || invoiceId);
                  }

                  // Clear the comment textarea
                  var textarea = document.getElementById('comment_textarea');
                  if (textarea) {
                        textarea.value = '';
                  }
            });
      };

      // Handle modal close/cancel
      var handleModalClose = function () {
            var cancelButton = element.querySelector('[data-kt-add-comment-modal-action="cancel"]');
            var closeButton = element.querySelector('[data-kt-add-comment-modal-action="close"]');

            if (cancelButton) {
                  cancelButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        resetForm();
                        modal.hide();
                  });
            }

            if (closeButton) {
                  closeButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        resetForm();
                        modal.hide();
                  });
            }

            // Reset form when modal is hidden
            element.addEventListener('hidden.bs.modal', function () {
                  resetForm();
            });
      };

      // Reset form
      var resetForm = function () {
            if (form) {
                  form.reset();
            }
            var textarea = document.getElementById('comment_textarea');
            if (textarea) {
                  textarea.classList.remove('is-invalid');
            }
      };

      // Validate comment
      var validateComment = function (comment) {
            var textarea = document.getElementById('comment_textarea');

            if (!comment || comment.trim().length < 3) {
                  textarea.classList.add('is-invalid');
                  return false;
            }

            if (comment.length > 1000) {
                  textarea.classList.add('is-invalid');
                  return false;
            }

            textarea.classList.remove('is-invalid');
            return true;
      };

      // Handle form submission via AJAX
      var handleFormSubmit = function () {
            submitButton.addEventListener('click', function (e) {
                  e.preventDefault();

                  var textarea = document.getElementById('comment_textarea');
                  var commentValue = textarea ? textarea.value : '';

                  // Validate comment
                  if (!validateComment(commentValue)) {
                        toastr.warning('Please enter a valid comment (3-1000 characters).');
                        return;
                  }

                  // Show loading indicator
                  submitButton.setAttribute('data-kt-indicator', 'on');
                  submitButton.disabled = true;

                  // Prepare form data
                  var formData = new FormData(form);
                  formData.append('_token', getCsrfToken());

                  // Submit via AJAX
                  fetch(routeStoreComment, {
                        method: 'POST',
                        body: formData,
                        headers: {
                              'Accept': 'application/json',
                              'X-Requested-With': 'XMLHttpRequest'
                        }
                  })
                        .then(function (response) {
                              return response.json().then(function (data) {
                                    if (!response.ok) {
                                          // Handle validation errors
                                          if (response.status === 422 && data.errors) {
                                                var errorMessages = [];
                                                Object.keys(data.errors).forEach(function (key) {
                                                      errorMessages.push(data.errors[key][0]);
                                                });
                                                throw new Error(errorMessages.join('<br>'));
                                          }
                                          throw new Error(data.message || 'Something went wrong');
                                    }
                                    return data;
                              });
                        })
                        .then(function (data) {
                              // Hide loading indicator
                              submitButton.removeAttribute('data-kt-indicator');
                              submitButton.disabled = false;

                              if (data.success) {
                                    // Add comment to the container
                                    addCommentToContainer(data.comment);

                                    // Reset form
                                    resetForm();

                                    // Close modal
                                    modal.hide();

                                    // Show success message
                                    toastr.success(data.message || 'Comment added successfully!');
                              } else {
                                    throw new Error(data.message || 'Failed to add comment');
                              }
                        })
                        .catch(function (error) {
                              // Hide loading indicator
                              submitButton.removeAttribute('data-kt-indicator');
                              submitButton.disabled = false;

                              // Show error message
                              Swal.fire({
                                    html: error.message || 'Something went wrong. Please try again.',
                                    icon: 'error',
                                    buttonsStyling: false,
                                    confirmButtonText: 'Ok, got it!',
                                    customClass: {
                                          confirmButton: 'btn btn-primary'
                                    }
                              });
                        });
            });
      };

      return {
            init: function () {
                  element = document.getElementById('kt_modal_add_comment');
                  if (!element) {
                        return;
                  }

                  form = element.querySelector('#kt_modal_add_comment_form');
                  submitButton = document.getElementById('kt_modal_add_comment_submit');
                  modal = bootstrap.Modal.getOrCreateInstance(element);

                  if (!form || !submitButton) {
                        return;
                  }

                  handleAddCommentClick();
                  handleModalClose();
                  handleFormSubmit();
            }
      };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTInvoiceWithTransactionsList.init();
      KTEditInvoiceModal.init();
      KTTransactionForm.init();
      KTAddCommentModal.init();
});