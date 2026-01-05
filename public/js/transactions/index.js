"use strict";

var KTAllTransactionsList = function () {
      // Define shared variables
      var datatables = {};
      var activeDatatable = null;
      var initializedTabs = {};

      // Get DataTable config based on whether branch column is shown
      var getDataTableConfig = function (showBranchColumn) {
            var config = {
                  "info": true,
                  'order': [],
                  "lengthMenu": [10, 25, 50, 100],
                  "pageLength": 10,
                  "lengthChange": true,
                  "autoWidth": false,
                  'columnDefs': []
            };

            // Adjust action column index based on branch column visibility
            if (showBranchColumn) {
                  config.columnDefs.push({ orderable: false, targets: 10 }); // Actions column with branch
            } else {
                  config.columnDefs.push({ orderable: false, targets: 9 }); // Actions column without branch
            }

            return config;
      };

      // Initialize a single datatable
      var initSingleDatatable = function (tableId, showBranchColumn) {
            var table = document.getElementById(tableId);
            if (!table) {
                  return null;
            }

            var config = getDataTableConfig(showBranchColumn);
            var datatable = $(table).DataTable(config);

            // Re-init functions on every table re-draw
            datatable.on('draw', function () {
                  KTMenu.init();
            });

            return datatable;
      };

      // Initialize datatables for admin (multiple tabs)
      var initAdminDatatables = function () {
            // Initialize the first branch tab (it's active by default)
            if (branchIds && branchIds.length > 0) {
                  var firstBranchId = branchIds[0];
                  var firstTableId = 'kt_transactions_table_branch_' + firstBranchId;
                  var firstBranchTable = document.getElementById(firstTableId);

                  if (firstBranchTable) {
                        datatables[firstBranchId] = initSingleDatatable(firstTableId, false);
                        activeDatatable = datatables[firstBranchId];
                        initializedTabs[firstBranchId] = true;
                  }
            }

            // Setup tab change event listener for lazy loading
            var tabLinks = document.querySelectorAll('#transactionBranchTabs a[data-bs-toggle="tab"]');
            tabLinks.forEach(function (tabLink) {
                  tabLink.addEventListener('shown.bs.tab', function (event) {
                        var branchId = event.target.getAttribute('data-branch-id');
                        var tableId = 'kt_transactions_table_branch_' + branchId;

                        // Initialize datatable for this tab if not already done
                        if (!initializedTabs[branchId]) {
                              datatables[branchId] = initSingleDatatable(tableId, false);
                              initializedTabs[branchId] = true;
                        }

                        // Set active datatable
                        activeDatatable = datatables[branchId];

                        // Adjust columns for responsive display
                        if (activeDatatable) {
                              activeDatatable.columns.adjust().draw(false);
                        }

                        // Reinitialize export buttons for the active table
                        updateExportButtons();
                  });
            });
      };

      // Initialize datatable for non-admin (single table)
      var initNonAdminDatatable = function () {
            var table = document.getElementById('kt_transactions_table');
            if (!table) {
                  return;
            }

            datatables['single'] = initSingleDatatable('kt_transactions_table', false);
            activeDatatable = datatables['single'];
      };

      // Hook export buttons
      var exportButtons = function () {
            updateExportButtons();

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

      // Update export buttons for the active datatable
      var updateExportButtons = function () {
            if (!activeDatatable) return;

            const documentTitle = 'Transactions Report';

            // Clear existing buttons
            var hiddenContainer = document.getElementById('kt_hidden_export_buttons');
            if (hiddenContainer) {
                  hiddenContainer.innerHTML = '';
            }

            // Create new buttons for the active datatable
            new $.fn.dataTable.Buttons(activeDatatable, {
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
                                    if (typeof getPdfFooterWithPrintTime === 'function') {
                                          doc.footer = getPdfFooterWithPrintTime();
                                    }
                              }
                        }
                  ]
            }).container().appendTo('#kt_hidden_export_buttons');
      };

      // Search Datatable
      var handleSearch = function () {
            const filterSearch = document.querySelector('[data-transaction-table-filter="search"]');
            if (!filterSearch) return;

            filterSearch.addEventListener('keyup', function (e) {
                  if (activeDatatable) {
                        activeDatatable.search(e.target.value).draw();
                  }
            });
      };

      // Filter Datatable
      var handleFilter = function () {
            const filterForm = document.querySelector('[data-transaction-table-filter="form"]');
            if (!filterForm) return;

            const filterButton = filterForm.querySelector('[data-transaction-table-filter="filter"]');
            const resetButton = filterForm.querySelector('[data-transaction-table-filter="reset"]');
            const selectOptions = filterForm.querySelectorAll('select');

            // Filter datatable on submit
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

                        if (activeDatatable) {
                              activeDatatable.search(filterString).draw();
                        }
                  });
            }

            // Reset datatable
            if (resetButton) {
                  resetButton.addEventListener('click', function () {
                        selectOptions.forEach((item) => {
                              $(item).val(null).trigger('change');
                        });

                        if (activeDatatable) {
                              activeDatatable.search('').draw();
                        }
                  });
            }
      };

      // Delete Transaction
      const handleDeletion = function () {
            document.addEventListener('click', function (e) {
                  const deleteBtn = e.target.closest('.delete-txn');
                  if (!deleteBtn) return;

                  e.preventDefault();

                  let txnId = deleteBtn.getAttribute('data-txn-id');
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
                                          "X-CSRF-TOKEN": csrfToken,
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
            document.addEventListener('click', function (e) {
                  const approveBtn = e.target.closest('.approve-txn');
                  if (!approveBtn) return;

                  e.preventDefault();

                  let txnId = approveBtn.getAttribute('data-txn-id');

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
                                          "X-CSRF-TOKEN": csrfToken,
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
      };

      // Statement Download Handler
      const handleStatementDownload = function () {
            document.addEventListener('click', function (e) {
                  const downloadBtn = e.target.closest('.download-statement');
                  if (!downloadBtn) return;

                  e.preventDefault();

                  const studentId = downloadBtn.getAttribute('data-student-id');
                  const year = downloadBtn.getAttribute('data-year');

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

                  fetch(routeDownloadStatement, {
                        method: "POST",
                        headers: {
                              "X-CSRF-TOKEN": csrfToken,
                        },
                        body: formData
                  })
                        .then(response => {
                              if (!response.ok) {
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
                                    printWindow.focus();
                              } else {
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
            init: function () {
                  // Check if admin or non-admin based on the presence of tabs
                  if (typeof isAdmin !== 'undefined' && isAdmin) {
                        initAdminDatatables();
                  } else {
                        initNonAdminDatatable();
                  }

                  exportButtons();
                  handleSearch();
                  handleFilter();
                  handleDeletion();
                  handleApproval();
                  handleStatementDownload();
            },
            getActiveDatatable: function () {
                  return activeDatatable;
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
      const branchSelect = document.getElementById('transaction_branch_select');
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

      // Handle branch select (for admin)
      var handleBranchSelect = function () {
            if (!branchSelect) return;

            $(branchSelect).on('change', function () {
                  const branchId = $(this).val();
                  const $studentSelect = $(studentSelect);

                  // Clear student select
                  $studentSelect.empty().append('<option value="">Select a student</option>');

                  // Clear invoice select
                  $(invoiceSelect).empty().append('<option value="">Select Due Invoice</option>');

                  // Reset amount input
                  $(amountInput).val('').prop('disabled', true);

                  if (!branchId) return;

                  // Populate students for selected branch
                  if (typeof studentsByBranch !== 'undefined' && studentsByBranch[branchId]) {
                        studentsByBranch[branchId].forEach(student => {
                              $studentSelect.append(
                                    `<option value="${student.id}">${student.name} (${student.student_unique_id})</option>`
                              );
                        });
                  }
            });
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
                        isValid = false;
                        errorMessage = `For ${paymentType} payment, amount must be less than the due amount of ৳${maxAmount}`;
                  } else if (
                        (paymentType === 'partial' || paymentType === 'discounted') &&
                        isPartiallyPaidInvoice &&
                        amount > maxAmount
                  ) {
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

      // Form submission via AJAX
      var handleFormSubmit = function () {
            $(form).on('submit', function (e) {
                  e.preventDefault();

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
                        isValid = false;
                  } else if (
                        (paymentType === 'partial' || paymentType === 'discounted') &&
                        isPartiallyPaidInvoice &&
                        amount > maxAmount
                  ) {
                        isValid = false;
                  } else if (paymentType === 'full' && amount != maxAmount) {
                        isValid = false;
                  }

                  if (!isValid || $(amountInput).hasClass('is-invalid')) {
                        toastr.warning('Please enter a valid amount.');
                        return false;
                  }

                  // Get submit button and show loading state
                  const submitBtn = form.querySelector('[data-kt-add-transaction-modal-action="submit"]');
                  submitBtn.setAttribute('data-kt-indicator', 'on');
                  submitBtn.disabled = true;

                  // Prepare form data
                  const formData = new FormData(form);

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
                        .then(response => {
                              if (!response.ok) {
                                    return response.json().then(err => { throw err; });
                              }
                              return response.json();
                        })
                        .then(data => {
                              if (data.success) {
                                    toastr.success(data.message || 'Transaction recorded successfully.');

                                    const transactionData = data.transaction;

                                    resetForm();
                                    modal.hide();

                                    if (transactionData && transactionData.is_approved) {
                                          Swal.fire({
                                                title: 'Transaction Successful!',
                                                text: 'Do you want to download the payment statement?',
                                                icon: 'success',
                                                showCancelButton: true,
                                                confirmButtonColor: '#3085d6',
                                                cancelButtonColor: '#6c757d',
                                                confirmButtonText: 'Yes, download',
                                                cancelButtonText: 'No, just reload'
                                          }).then((result) => {
                                                if (result.isConfirmed) {
                                                      downloadStatementAndReload(transactionData.student_id, transactionData.year);
                                                } else {
                                                      location.reload();
                                                }
                                          });
                                    } else {
                                          Swal.fire({
                                                title: 'Transaction Recorded!',
                                                text: 'This transaction requires approval before the statement can be downloaded.',
                                                icon: 'info',
                                                confirmButtonText: 'OK'
                                          }).then(() => {
                                                location.reload();
                                          });
                                    }
                              } else {
                                    toastr.error(data.message || 'Failed to record transaction.');
                                    submitBtn.removeAttribute('data-kt-indicator');
                                    submitBtn.disabled = false;
                              }
                        })
                        .catch(error => {
                              console.error('Transaction Error:', error);

                              let errorMessage = 'An error occurred. Please try again.';

                              if (error.message) {
                                    errorMessage = error.message;
                              } else if (error.errors) {
                                    errorMessage = Object.values(error.errors).flat().join('\n');
                              }

                              toastr.error(errorMessage);

                              submitBtn.removeAttribute('data-kt-indicator');
                              submitBtn.disabled = false;
                        });

                  return false;
            });
      }

      // Download statement and then reload page
      var downloadStatementAndReload = function (studentId, year) {
            const formData = new FormData();
            formData.append('student_id', studentId);
            formData.append('statement_year', year);

            fetch(routeDownloadStatement, {
                  method: 'POST',
                  headers: {
                        'X-CSRF-TOKEN': csrfToken,
                  },
                  body: formData
            })
                  .then(response => {
                        if (!response.ok) {
                              throw new Error('Failed to load statement');
                        }
                        return response.text();
                  })
                  .then(html => {
                        const printWindow = window.open('', '_blank', 'width=900,height=700,scrollbars=yes,resizable=yes');
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

                        location.reload();
                  })
                  .catch(error => {
                        console.error('Statement Download Error:', error);
                        toastr.error('Failed to download statement. Page will reload.');
                        location.reload();
                  });
      }

      // Reset form and close modal
      var resetForm = function () {
            if (form) form.reset();

            if (branchSelect && $(branchSelect).data('select2')) {
                  $(branchSelect).val(null).trigger('change');
            }

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
            const cancelButton = element.querySelector('[data-kt-add-transaction-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        resetForm();
                        modal.hide();
                  });
            }

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
                  handleBranchSelect();
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