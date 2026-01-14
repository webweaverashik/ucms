"use strict";

// Class definition
var KTDueInvoicesList = function () {
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
                        { targets: [3], visible: false, searchable: true },          // Mobile
                        { targets: [4, 6, 10, 12], visible: false, searchable: true }, // Filter-only cols
                        { orderable: false, targets: 15 }                             // Actions
                  ]
            });

            datatable.on('draw', function () {
            });
      }

      // Hook export buttons
      var exportButtonsDue = function () {
            const documentTitle = 'Due Invoices Report';

            var buttons = new $.fn.dataTable.Buttons(datatable, {
                  buttons: [
                        {
                              extend: 'copyHtml5',
                              className: 'buttons-copy',
                              title: documentTitle,
                              exportOptions: {
                                    columns: ':not(.not-export):not(.filter-only)'
                              }
                        },
                        {
                              extend: 'excelHtml5',
                              className: 'buttons-excel',
                              title: documentTitle,
                              exportOptions: {
                                    columns: ':not(.not-export):not(.filter-only)'
                              }
                        },
                        {
                              extend: 'csvHtml5',
                              className: 'buttons-csv',
                              title: documentTitle,
                              exportOptions: {
                                    columns: ':not(.not-export):not(.filter-only)'
                              }
                        },
                        {
                              extend: 'pdfHtml5',
                              className: 'buttons-pdf',
                              title: documentTitle,
                              exportOptions: {
                                    columns: ':not(.not-export):not(.filter-only)',
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
            const filterSearch = document.querySelector('[data-kt-due-invoice-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                  datatable.search(e.target.value).draw();
            });
      }

      // Filter Datatable
      var handleFilter = function () {
            const filterForm = document.querySelector('[data-kt-due-invoice-table-filter="form"]');
            const filterButton = filterForm.querySelector('[data-kt-due-invoice-table-filter="filter"]');
            const resetButton = filterForm.querySelector('[data-kt-due-invoice-table-filter="reset"]');
            const selectOptions = filterForm.querySelectorAll('select');

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

            resetButton.addEventListener('click', function () {
                  selectOptions.forEach((item, index) => {
                        $(item).val(null).trigger('change');
                  });

                  datatable.search('').draw();
            });
      }

      // Delete invoices
      var handleDeletion = function () {
            document.addEventListener('click', function (e) {
                  const deleteBtn = e.target.closest('.delete-invoice');
                  if (!deleteBtn) return;

                  e.preventDefault();
                  const invoiceId = deleteBtn.getAttribute('data-invoice-id');
                  const url = routeDeleteInvoice.replace(':id', invoiceId);

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
                                                      location.reload();
                                                });
                                          } else {
                                                Swal.fire({
                                                      title: "Error!",
                                                      text: data.error || "Something went wrong.",
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
      };

      return {
            init: function () {
                  table = document.getElementById('kt_due_invoices_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  exportButtonsDue();
                  handleSearch();
                  handleFilter();
                  handleDeletion();
            }
      }
}();

// Class definition
var KTPaidInvoicesList = function () {
      var table;
      var datatable;

      var initDatatable = function () {
            datatable = $(table).DataTable({
                  "info": true,
                  'order': [],
                  "lengthMenu": [10, 25, 50, 100],
                  "pageLength": 10,
                  "lengthChange": true,
                  "autoWidth": false,
                  'columnDefs': [
                        { orderable: false, targets: 10 },
                  ]
            });

            datatable.on('draw', function () {
            });
      }

      var exportButtonsPaid = function () {
            const documentTitle = 'Paid Invoices Report';

            var buttons = new $.fn.dataTable.Buttons(datatable, {
                  buttons: [
                        {
                              extend: 'copyHtml5',
                              className: 'buttons-copy-paid',
                              title: documentTitle,
                              exportOptions: {
                                    columns: ':visible:not(.not-export)'
                              }
                        },
                        {
                              extend: 'excelHtml5',
                              className: 'buttons-excel-paid',
                              title: documentTitle,
                              exportOptions: {
                                    columns: ':visible:not(.not-export)'
                              }
                        },
                        {
                              extend: 'csvHtml5',
                              className: 'buttons-csv-paid',
                              title: documentTitle,
                              exportOptions: {
                                    columns: ':visible:not(.not-export)'
                              }
                        },
                        {
                              extend: 'pdfHtml5',
                              className: 'buttons-pdf-paid',
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
            }).container().appendTo('#kt_hidden_export_buttons_2');

            const exportItems = document.querySelectorAll('#kt_table_report_dropdown_menu_2 [data-row-export]');

            exportItems.forEach(exportItem => {
                  exportItem.addEventListener('click', function (e) {
                        e.preventDefault();
                        const exportValue = this.getAttribute('data-row-export');
                        const target = document.querySelector('.buttons-' + exportValue + '-paid');
                        if (target) {
                              target.click();
                        } else {
                              console.warn('Export button not found:', exportValue);
                        }
                  });
            });
      };

      var handleSearch = function () {
            const filterSearch = document.querySelector('[data-kt-paid-invoice-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                  datatable.search(e.target.value).draw();
            });
      }

      var handleFilter = function () {
            const filterForm = document.querySelector('[data-kt-paid-invoice-table-filter="form"]');
            const filterButton = filterForm.querySelector('[data-kt-paid-invoice-table-filter="filter"]');
            const resetButton = filterForm.querySelector('[data-kt-paid-invoice-table-filter="reset"]');
            const selectOptions = filterForm.querySelectorAll('select');

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

            resetButton.addEventListener('click', function () {
                  selectOptions.forEach((item, index) => {
                        $(item).val(null).trigger('change');
                  });

                  datatable.search('').draw();
            });
      }

      return {
            init: function () {
                  table = document.getElementById('kt_paid_invoices_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  exportButtonsPaid();
                  handleSearch();
                  handleFilter();
            }
      }
}();

// Class definition
var KTCreateInvoiceModal = function () {
      // Shared variables
      var element;
      var form;
      var modal;
      var submitButton;
      var validator;

      // Form elements
      var studentSelect;
      var invoiceTypeSelect;
      var monthYearSelect;
      var invoiceAmountInput;
      var monthYearTypeRadios;
      var monthYearTypeWrapper;
      var monthYearWrapper;

      // Data storage
      var invoiceData = {
            tuitionFee: null,
            paymentStyle: null,
            lastInvoiceMonth: null,
            oldestInvoiceMonth: null
      };

      // Helper function to get CSRF token
      var getCsrfToken = function () {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      };

      // Helper function to get selected type name from data attribute
      var getSelectedTypeName = function () {
            const selectedOption = invoiceTypeSelect.options[invoiceTypeSelect.selectedIndex];
            return selectedOption ? (selectedOption.getAttribute('data-type-name') || selectedOption.text.trim()) : '';
      };

      // Helper function to format month year for display
      var formatMonthYear = function (month, year) {
            const date = new Date(year, month - 1, 1);
            return date.toLocaleString('default', { month: 'long' }) + ' ' + year;
      };

      // Helper function to set Select2 value
      var setSelect2Value = function (selectElement, value) {
            $(selectElement).val(value).trigger('change');
      };

      // Helper function to clear and add option to select
      var setMonthYearOption = function (value, text) {
            // Clear existing options except first empty one
            monthYearSelect.innerHTML = '<option value=""></option>';

            if (value && text) {
                  const option = document.createElement('option');
                  option.value = value;
                  option.textContent = text;
                  monthYearSelect.appendChild(option);

                  // Trigger Select2 refresh
                  $(monthYearSelect).trigger('change');
            }
      };

      // Calculate new invoice month (next month after last invoice)
      var calculateNewInvoiceMonth = function () {
            let month, year;

            if (invoiceData.lastInvoiceMonth) {
                  const [lastMonth, lastYear] = invoiceData.lastInvoiceMonth.split('_').map(Number);
                  month = lastMonth + 1;
                  year = lastYear;

                  // Handle December → January transition
                  if (month > 12) {
                        month = 1;
                        year++;
                  }
            } else {
                  // For new students with no invoices
                  const currentDate = new Date();
                  if (invoiceData.paymentStyle === 'due') {
                        // For due payments, show previous month
                        month = currentDate.getMonth(); // 0-11
                        year = currentDate.getFullYear();
                        if (month === 0) {
                              month = 12;
                              year--;
                        }
                  } else {
                        // For current payments, show current month
                        month = currentDate.getMonth() + 1;
                        year = currentDate.getFullYear();
                  }
            }

            const monthStr = String(month).padStart(2, '0');
            const monthYear = `${monthStr}_${year}`;
            const monthName = formatMonthYear(month, year);

            return { value: monthYear, text: monthName };
      };

      // Calculate old invoice month (month before oldest invoice)
      var calculateOldInvoiceMonth = function () {
            let month, year;

            if (invoiceData.oldestInvoiceMonth) {
                  const [oldestMonth, oldestYear] = invoiceData.oldestInvoiceMonth.split('_').map(Number);
                  month = oldestMonth - 1;
                  year = oldestYear;

                  // Handle January → December transition
                  if (month < 1) {
                        month = 12;
                        year--;
                  }
            } else {
                  // For new students with no invoices
                  const currentDate = new Date();
                  if (invoiceData.paymentStyle === 'due') {
                        // For due payments, show month before previous
                        month = currentDate.getMonth() - 1;
                        year = currentDate.getFullYear();
                        if (month < 0) {
                              month = 11;
                              year--;
                        }
                  } else {
                        // For current payments, show previous month
                        month = currentDate.getMonth();
                        year = currentDate.getFullYear();
                        if (month === 0) {
                              month = 12;
                              year--;
                        }
                  }
            }

            const monthStr = String(month).padStart(2, '0');
            const monthYear = `${monthStr}_${year}`;
            const monthName = formatMonthYear(month, year);

            return { value: monthYear, text: monthName };
      };

      // Handle student selection change
      var handleStudentChange = function () {
            $(studentSelect).on('change', function () {
                  const studentId = this.value;

                  if (studentId) {
                        // Enable invoice type select
                        invoiceTypeSelect.disabled = false;
                        monthYearTypeRadios.forEach(radio => radio.disabled = false);

                        // Show loading state
                        monthYearSelect.innerHTML = '<option value="">Loading...</option>';
                        monthYearSelect.disabled = true;
                        invoiceAmountInput.value = '';
                        invoiceAmountInput.disabled = true;

                        // Fetch invoice months data
                        fetch(`/students/${studentId}/invoice-months-data`, {
                              method: 'GET',
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
                                    // Store data for later use
                                    invoiceData.tuitionFee = data.tuition_fee;
                                    invoiceData.paymentStyle = data.payment_style;
                                    invoiceData.lastInvoiceMonth = data.last_invoice_month;
                                    invoiceData.oldestInvoiceMonth = data.oldest_invoice_month;

                                    // Calculate and set new invoice month
                                    const newMonth = calculateNewInvoiceMonth();
                                    setMonthYearOption(newMonth.value, newMonth.text);

                                    // Enable month year select
                                    monthYearSelect.disabled = false;

                                    // Auto-fill amount if tuition fee is selected
                                    const selectedTypeName = getSelectedTypeName();
                                    if (selectedTypeName === 'Tuition Fee' && monthYearSelect.value) {
                                          invoiceAmountInput.value = invoiceData.tuitionFee || '';
                                          invoiceAmountInput.disabled = false;
                                    }
                              })
                              .catch(error => {
                                    console.error('Error:', error);
                                    monthYearSelect.innerHTML = '<option value="">Error loading months</option>';
                                    toastr.error('Failed to load invoice data');
                              });
                  } else {
                        // Reset form elements
                        invoiceTypeSelect.disabled = true;
                        monthYearSelect.disabled = true;
                        monthYearSelect.innerHTML = '<option value=""></option>';
                        invoiceAmountInput.value = '';
                        invoiceAmountInput.disabled = true;
                        monthYearTypeRadios.forEach(radio => radio.disabled = true);

                        // Reset Select2
                        setSelect2Value(invoiceTypeSelect, null);
                        $(monthYearSelect).trigger('change');
                  }
            });
      };

      // Handle month/year type radio button change
      var handleMonthYearTypeChange = function () {
            monthYearTypeRadios.forEach(radio => {
                  radio.addEventListener('change', function () {
                        if (!studentSelect.value) return;

                        const selectedType = this.value;
                        let monthData;

                        if (selectedType === 'new_invoice') {
                              monthData = calculateNewInvoiceMonth();
                        } else if (selectedType === 'old_invoice') {
                              monthData = calculateOldInvoiceMonth();
                        }

                        setMonthYearOption(monthData.value, monthData.text);

                        // Update amount field
                        const selectedTypeName = getSelectedTypeName();
                        if (monthYearSelect.value && selectedTypeName === 'Tuition Fee') {
                              invoiceAmountInput.value = invoiceData.tuitionFee || '';
                              invoiceAmountInput.disabled = false;
                        } else {
                              invoiceAmountInput.value = '';
                              invoiceAmountInput.disabled = true;
                        }
                  });
            });
      };

      // Handle month/year selection change
      var handleMonthYearChange = function () {
            $(monthYearSelect).on('change', function () {
                  const selectedTypeName = getSelectedTypeName();

                  if (this.value) {
                        invoiceAmountInput.disabled = false;
                        if (selectedTypeName === 'Tuition Fee') {
                              invoiceAmountInput.value = invoiceData.tuitionFee || '';
                        }
                  } else {
                        invoiceAmountInput.disabled = true;
                  }
            });
      };

      // Handle invoice type change
      var handleInvoiceTypeChange = function () {
            $(invoiceTypeSelect).on('change', function () {
                  const selectedTypeName = getSelectedTypeName();
                  const studentId = studentSelect.value;

                  if (selectedTypeName === 'Sheet Fee') {
                        // Hide month/year sections
                        monthYearTypeWrapper.style.display = 'none';
                        monthYearWrapper.style.display = 'none';
                        monthYearSelect.required = false;
                        monthYearTypeRadios.forEach(radio => radio.disabled = true);
                        invoiceAmountInput.disabled = true;
                        invoiceAmountInput.value = '';

                        if (studentId) {
                              // Fetch sheet fee amount
                              fetch(`/students/${studentId}/sheet-fee`, {
                                    method: 'GET',
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
                                          if (data.sheet_fee) {
                                                invoiceAmountInput.value = data.sheet_fee;
                                                invoiceAmountInput.disabled = false;
                                          } else {
                                                invoiceAmountInput.value = '0';
                                                invoiceAmountInput.disabled = false;
                                                toastr.warning('No sheet fee found for the student\'s class.');
                                          }
                                    })
                                    .catch(error => {
                                          console.error('Error:', error);
                                          invoiceAmountInput.value = '';
                                          invoiceAmountInput.disabled = false;
                                          toastr.error('Failed to fetch sheet fee.');
                                    });
                        }
                  } else if (selectedTypeName !== 'Tuition Fee') {
                        // Other invoice types (not tuition fee or sheet fee)
                        monthYearTypeWrapper.style.display = 'none';
                        monthYearWrapper.style.display = 'none';
                        monthYearSelect.required = false;
                        monthYearTypeRadios.forEach(radio => radio.disabled = true);
                        invoiceAmountInput.disabled = false;
                        invoiceAmountInput.value = '';
                  } else {
                        // Tuition fee is selected
                        monthYearTypeWrapper.style.display = '';
                        monthYearWrapper.style.display = '';
                        monthYearSelect.required = true;
                        monthYearTypeRadios.forEach(radio => radio.disabled = false);
                        invoiceAmountInput.disabled = !monthYearSelect.value;

                        if (monthYearSelect.value) {
                              invoiceAmountInput.value = invoiceData.tuitionFee || '';
                        }
                  }
            });
      };

      // Reset form to initial state
      var resetForm = function () {
            form.reset();

            // Reset Select2 dropdowns
            setSelect2Value(studentSelect, null);
            setSelect2Value(invoiceTypeSelect, null);
            $(monthYearSelect).empty().append('<option value=""></option>').trigger('change');

            // Disable form elements
            invoiceTypeSelect.disabled = true;
            monthYearSelect.disabled = true;
            invoiceAmountInput.value = '';
            invoiceAmountInput.disabled = true;

            // Reset month year sections visibility
            monthYearTypeWrapper.style.display = '';
            monthYearWrapper.style.display = '';
            monthYearSelect.required = true;

            // Disable and reset radio buttons
            monthYearTypeRadios.forEach(radio => radio.disabled = true);
            document.getElementById('new_invoice_input').checked = true;

            // Clear stored data
            invoiceData = {
                  tuitionFee: null,
                  paymentStyle: null,
                  lastInvoiceMonth: null,
                  oldestInvoiceMonth: null
            };

            // Reset validator if exists
            if (validator) {
                  validator.resetForm();
            }
      };

      // Handle modal close/cancel
      var handleModalClose = function () {
            const cancelButton = element.querySelector('[data-kt-add-invoice-modal-action="cancel"]');
            const closeButton = element.querySelector('[data-kt-add-invoice-modal-action="close"]');

            cancelButton.addEventListener('click', function (e) {
                  e.preventDefault();
                  resetForm();
                  modal.hide();
            });

            closeButton.addEventListener('click', function (e) {
                  e.preventDefault();
                  resetForm();
                  modal.hide();
            });

            // Reset form when modal is hidden
            element.addEventListener('hidden.bs.modal', function () {
                  resetForm();
            });
      };

      // Initialize form validation
      var initValidation = function () {
            validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'invoice_student': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Student is required'
                                          }
                                    }
                              },
                              'invoice_type': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Invoice type is required'
                                          }
                                    }
                              },
                              'invoice_amount': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Amount is required'
                                          },
                                          greaterThan: {
                                                min: 50,
                                                message: 'Amount must be at least 50'
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
      };

      // Handle form submission via AJAX
      var handleFormSubmit = function () {
            submitButton = element.querySelector('[data-kt-add-invoice-modal-action="submit"]');

            submitButton.addEventListener('click', function (e) {
                  e.preventDefault();

                  // Validate form
                  if (validator) {
                        validator.validate().then(function (status) {
                              if (status === 'Valid') {
                                    // Show loading indicator
                                    submitButton.setAttribute('data-kt-indicator', 'on');
                                    submitButton.disabled = true;

                                    // Prepare form data
                                    const formData = new FormData(form);
                                    formData.append('_token', getCsrfToken());

                                    // Submit via AJAX
                                    fetch(routeStoreInvoice, {
                                          method: 'POST',
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(response => {
                                                return response.json().then(data => {
                                                      if (!response.ok) {
                                                            // Handle validation errors
                                                            if (response.status === 422 && data.errors) {
                                                                  let errorMessages = [];
                                                                  Object.keys(data.errors).forEach(key => {
                                                                        errorMessages.push(data.errors[key][0]);
                                                                  });
                                                                  throw new Error(errorMessages.join('<br>'));
                                                            }
                                                            throw new Error(data.message || 'Something went wrong');
                                                      }
                                                      return data;
                                                });
                                          })
                                          .then(data => {
                                                // Hide loading indicator
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      // Show success message
                                                      Swal.fire({
                                                            text: data.message || 'Invoice created successfully!',
                                                            icon: 'success',
                                                            buttonsStyling: false,
                                                            confirmButtonText: 'Ok, got it!',
                                                            customClass: {
                                                                  confirmButton: 'btn btn-primary'
                                                            }
                                                      }).then(function (result) {
                                                            if (result.isConfirmed) {
                                                                  modal.hide();
                                                                  resetForm();
                                                                  // Reload the page
                                                                  window.location.reload();
                                                            }
                                                      });
                                                } else {
                                                      throw new Error(data.message || 'Failed to create invoice');
                                                }
                                          })
                                          .catch(error => {
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
                              } else {
                                    // Show validation error message
                                    Swal.fire({
                                          text: 'Please fill all required fields correctly.',
                                          icon: 'warning',
                                          buttonsStyling: false,
                                          confirmButtonText: 'Ok, got it!',
                                          customClass: {
                                                confirmButton: 'btn btn-primary'
                                          }
                                    });
                              }
                        });
                  }
            });
      };

      return {
            init: function () {
                  element = document.getElementById('kt_modal_create_invoice');

                  if (!element) {
                        return;
                  }

                  form = element.querySelector('#kt_modal_add_invoice_form');
                  modal = new bootstrap.Modal(element);

                  // Initialize form elements
                  studentSelect = element.querySelector('select[name="invoice_student"]');
                  invoiceTypeSelect = element.querySelector('select[name="invoice_type"]');
                  monthYearSelect = element.querySelector('select[name="invoice_month_year"]');
                  invoiceAmountInput = element.querySelector('input[name="invoice_amount"]');
                  monthYearTypeRadios = element.querySelectorAll('input[name="month_year_type"]');
                  monthYearTypeWrapper = document.getElementById('month_year_type_id');
                  monthYearWrapper = document.getElementById('month_year_id');

                  // Initialize with disabled state
                  monthYearTypeRadios.forEach(radio => radio.disabled = true);

                  // Initialize handlers
                  handleStudentChange();
                  handleMonthYearTypeChange();
                  handleMonthYearChange();
                  handleInvoiceTypeChange();
                  handleModalClose();
                  initValidation();
                  handleFormSubmit();
            }
      };
}();

// Class definition
var KTEditInvoiceModal = function () {
      // Shared variables
      var element;
      var form;
      var modal;
      var submitButton;
      var validator;
      var invoiceId = null;

      // Helper function to get CSRF token
      var getCsrfToken = function () {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      };

      // Helper function to format month year for display
      var formatMonthYear = function (monthYear) {
            if (!monthYear) return '';
            const [month, year] = monthYear.split('_');
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                  'July', 'August', 'September', 'October', 'November', 'December'];
            return `${monthNames[parseInt(month) - 1]} ${year}`;
      };

      // Handle edit button click
      var handleEditClick = function () {
            document.addEventListener('click', function (e) {
                  const button = e.target.closest("[data-bs-target='#kt_modal_edit_invoice']");
                  if (!button) return;

                  invoiceId = button.getAttribute('data-invoice-id');
                  if (!invoiceId) return;

                  // Clear form
                  if (form) form.reset();

                  // Fetch invoice data
                  fetch(`/invoices/${invoiceId}/view-ajax`, {
                        method: 'GET',
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
                              if (!data.success || !data.data) {
                                    throw new Error(data.message || 'Invalid response data');
                              }

                              const invoice = data.data;

                              // Set modal title
                              const titleEl = document.getElementById('kt_modal_edit_invoice_title');
                              if (titleEl) {
                                    titleEl.textContent = `Update Invoice ${invoice.invoice_number}`;
                              }

                              // Get the type name from the response
                              const invoiceTypeName = invoice.invoice_type_name || '';

                              // Show/hide month year wrapper based on type name
                              const monthYearWrapper = document.getElementById('month_year_id_edit');
                              if (monthYearWrapper) {
                                    monthYearWrapper.style.display = invoiceTypeName === 'Tuition Fee' ? '' : 'none';
                              }

                              // Set invoice amount
                              const amountInput = element.querySelector("input[name='invoice_amount_edit']");
                              if (amountInput) {
                                    amountInput.value = invoice.total_amount;
                              }

                              // Set student select (using Select2)
                              const studentSelect = $("select[name='invoice_student_edit']");
                              if (studentSelect.length) {
                                    studentSelect.val(invoice.student_id).trigger('change');
                              }

                              // Set invoice type select (using Select2)
                              const typeSelect = $("select[name='invoice_type_edit']");
                              if (typeSelect.length) {
                                    typeSelect.val(invoice.invoice_type_id).trigger('change');
                              }

                              // Set month_year field
                              const monthYearSelect = $("select[name='invoice_month_year_edit']");
                              if (monthYearSelect.length) {
                                    monthYearSelect.empty();
                                    const formattedMonthYear = formatMonthYear(invoice.month_year);
                                    const option = new Option(formattedMonthYear, invoice.month_year, true, true);
                                    monthYearSelect.append(option).trigger('change');
                              }

                              modal.show();
                        })
                        .catch(error => {
                              console.error('Error:', error);
                              toastr.error(error.message || 'Failed to load invoice details');
                        });
            });
      };

      // Handle modal close/cancel
      var handleModalClose = function () {
            const cancelButton = element.querySelector('[data-kt-edit-invoice-modal-action="cancel"]');
            const closeButton = element.querySelector('[data-kt-edit-invoice-modal-action="close"]');

            if (cancelButton) {
                  cancelButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (form) form.reset();
                        if (validator) validator.resetForm();
                        modal.hide();
                  });
            }

            if (closeButton) {
                  closeButton.addEventListener('click', function (e) {
                        e.preventDefault();
                        if (form) form.reset();
                        if (validator) validator.resetForm();
                        modal.hide();
                  });
            }

            // Reset form when modal is hidden
            element.addEventListener('hidden.bs.modal', function () {
                  if (form) form.reset();
                  if (validator) validator.resetForm();
            });
      };

      // Initialize form validation
      var initValidation = function () {
            if (!form) return;

            validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'invoice_amount_edit': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Amount is required'
                                          },
                                          greaterThan: {
                                                min: 50,
                                                message: 'Amount must be at least 50'
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
      };

      // Handle form submission via AJAX
      var handleFormSubmit = function () {
            submitButton = element.querySelector('[data-kt-edit-invoice-modal-action="submit"]');

            if (!submitButton) return;

            submitButton.addEventListener('click', function (e) {
                  e.preventDefault();

                  if (validator) {
                        validator.validate().then(function (status) {
                              if (status === 'Valid') {
                                    // Show loading indicator
                                    submitButton.setAttribute('data-kt-indicator', 'on');
                                    submitButton.disabled = true;

                                    // Prepare form data
                                    const formData = new FormData(form);
                                    formData.append('_token', getCsrfToken());
                                    formData.append('_method', 'PUT');

                                    // Submit via AJAX
                                    fetch(`/invoices/${invoiceId}`, {
                                          method: 'POST',
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(response => {
                                                return response.json().then(data => {
                                                      if (!response.ok) {
                                                            // Handle validation errors
                                                            if (response.status === 422 && data.errors) {
                                                                  let errorMessages = [];
                                                                  Object.keys(data.errors).forEach(key => {
                                                                        errorMessages.push(data.errors[key][0]);
                                                                  });
                                                                  throw new Error(errorMessages.join('<br>'));
                                                            }
                                                            throw new Error(data.message || 'Something went wrong');
                                                      }
                                                      return data;
                                                });
                                          })
                                          .then(data => {
                                                // Hide loading indicator
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      // Show success message
                                                      Swal.fire({
                                                            text: data.message || 'Invoice updated successfully!',
                                                            icon: 'success',
                                                            buttonsStyling: false,
                                                            confirmButtonText: 'Ok, got it!',
                                                            customClass: {
                                                                  confirmButton: 'btn btn-primary'
                                                            }
                                                      }).then(function (result) {
                                                            if (result.isConfirmed) {
                                                                  modal.hide();
                                                                  // Reload the page
                                                                  window.location.reload();
                                                            }
                                                      });
                                                } else {
                                                      throw new Error(data.message || 'Failed to update invoice');
                                                }
                                          })
                                          .catch(error => {
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
                              } else {
                                    // Show validation error message
                                    Swal.fire({
                                          text: 'Please fill all required fields correctly.',
                                          icon: 'warning',
                                          buttonsStyling: false,
                                          confirmButtonText: 'Ok, got it!',
                                          customClass: {
                                                confirmButton: 'btn btn-primary'
                                          }
                                    });
                              }
                        });
                  }
            });
      };

      return {
            init: function () {
                  element = document.getElementById('kt_modal_edit_invoice');

                  if (!element) {
                        return;
                  }

                  form = element.querySelector('#kt_modal_edit_invoice_form');
                  modal = bootstrap.Modal.getOrCreateInstance(element);

                  handleEditClick();
                  handleModalClose();
                  initValidation();
                  handleFormSubmit();
            }
      };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTDueInvoicesList.init();
      KTPaidInvoicesList.init();
      KTCreateInvoiceModal.init();
      KTEditInvoiceModal.init();
});