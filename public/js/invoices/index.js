"use strict";

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
                  "autoWidth": false,  // Disable auto width
                  'columnDefs': [
                        { orderable: false, targets: 13 }, // Disable ordering on column Actions                
                  ]
            });

            // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
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
                              title: documentTitle, exportOptions: {
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
                                    // Set page margins [left, top, right, bottom]
                                    doc.pageMargins = [20, 20, 20, 40]; // reduce from default 40

                                    // Optional: Set font size globally
                                    doc.defaultStyle.fontSize = 10;

                                    // Optional: Set header or footer
                                    doc.footer = getPdfFooterWithPrintTime(); // your custom footer function
                              }
                        }

                  ]
            }).container().appendTo('#kt_hidden_export_buttons'); // or a hidden container

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

      // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
      var handleSearch = function () {
            const filterSearch = document.querySelector('[data-kt-due-invoice-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                  datatable.search(e.target.value).draw();
            });
      }

      // Filter Datatable
      var handleFilter = function () {
            // Select filter options
            const filterForm = document.querySelector('[data-kt-due-invoice-table-filter="form"]');
            const filterButton = filterForm.querySelector('[data-kt-due-invoice-table-filter="filter"]');
            const resetButton = filterForm.querySelector('[data-kt-due-invoice-table-filter="reset"]');
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

      // Delete invoices
      const handleDeletion = function () {
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
            // Public functions  
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

var KTPaidInvoicesList = function () {
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
                        { orderable: false, targets: 10 }, // Disable ordering on status                
                  ]
            });

            // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
            datatable.on('draw', function () {

            });
      }

      // Hook export buttons
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
                              title: documentTitle, exportOptions: {
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
                                    // Set page margins [left, top, right, bottom]
                                    doc.pageMargins = [20, 20, 20, 40]; // reduce from default 40

                                    // Optional: Set font size globally
                                    doc.defaultStyle.fontSize = 10;

                                    // Optional: Set header or footer
                                    doc.footer = getPdfFooterWithPrintTime(); // your custom footer function
                              }
                        }

                  ]
            }).container().appendTo('#kt_hidden_export_buttons_2'); // or a hidden container

            // Hook dropdown export actions
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

      // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
      var handleSearch = function () {
            const filterSearch = document.querySelector('[data-kt-paid-invoice-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                  datatable.search(e.target.value).draw();
            });
      }

      // Filter Datatable
      var handleFilter = function () {
            // Select filter options
            const filterForm = document.querySelector('[data-kt-paid-invoice-table-filter="form"]');
            const filterButton = filterForm.querySelector('[data-kt-paid-invoice-table-filter="filter"]');
            const resetButton = filterForm.querySelector('[data-kt-paid-invoice-table-filter="reset"]');
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

var KTCreateInvoiceModal = function () {
      const element = document.getElementById('kt_modal_create_invoice');
      const form = element.querySelector('#kt_modal_add_invoice_form');
      const modal = new bootstrap.Modal(element);

      // Init add schedule modal
      var initAddInvoice = () => {

            // Cancel button handler
            const cancelButton = element.querySelector('[data-kt-add-invoice-modal-action="cancel"]');
            cancelButton.addEventListener('click', e => {
                  e.preventDefault();

                  form.reset(); // Reset form			
                  modal.hide();
            });

            // Close button handler
            const closeButton = element.querySelector('[data-kt-add-invoice-modal-action="close"]');
            closeButton.addEventListener('click', e => {
                  e.preventDefault();

                  form.reset(); // Reset form			
                  modal.hide();
            });
      }

      return {
            // Public functions
            init: function () {
                  initAddInvoice();
            }
      };
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

            // Delegated click event for edit buttons
            document.addEventListener("click", function (e) {
                  const button = e.target.closest("[data-bs-target='#kt_modal_edit_invoice']");
                  if (!button) return;

                  invoiceId = button.getAttribute("data-invoice-id");
                  if (!invoiceId) return;

                  // Clear form
                  if (form) form.reset();

                  fetch(`/invoices/${invoiceId}/view-ajax`)
                        .then(response => {
                              if (!response.ok) throw new Error('Network response was not ok');
                              return response.json();
                        })
                        .then(data => {
                              if (!data.success || !data.data) {
                                    throw new Error(data.message || "Invalid response data");
                              }

                              const invoice = data.data;

                              // Set modal title
                              const titleEl = document.getElementById("kt_modal_edit_invoice_title");
                              if (titleEl) {
                                    titleEl.textContent = `Update Invoice ${invoice.invoice_number}`;
                              }

                              // Show/hide month year wrapper
                              const monthYearWrapper = document.querySelector("#month_year_id_edit");
                              if (monthYearWrapper) {
                                    monthYearWrapper.style.display = invoice.invoice_type === 'tuition_fee' ? '' : 'none';
                              }

                              // Set invoice amount
                              const amountInput = document.querySelector("input[name='invoice_amount_edit']");
                              if (amountInput) {
                                    amountInput.value = invoice.total_amount;
                              }

                              // Helper to set Select2 fields
                              const setSelect2Value = (name, value) => {
                                    const el = $(`select[name="${name}"]`);
                                    if (el.length) el.val(value).trigger('change');
                              };

                              // Set student and invoice type
                              setSelect2Value("invoice_student_edit", invoice.student_id);
                              setSelect2Value("invoice_type_edit", invoice.invoice_type);

                              // Set month_year field
                              const monthYearSelect = $("select[name='invoice_month_year_edit']");
                              if (monthYearSelect.length) {
                                    monthYearSelect.empty();

                                    const formatMonthYear = (monthYear) => {
                                          if (!monthYear) return '';
                                          const [month, year] = monthYear.split('_');
                                          const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                                                'July', 'August', 'September', 'October', 'November', 'December'];
                                          return `${monthNames[parseInt(month) - 1]} ${year}`;
                                    };

                                    const formattedMonthYear = formatMonthYear(invoice.month_year);
                                    const option = new Option(formattedMonthYear, invoice.month_year, true, true);
                                    monthYearSelect.append(option).trigger('change');
                              }

                              modal.show();
                        })
                        .catch(error => {
                              console.error("Error:", error);
                              toastr.error(error.message || "Failed to load invoice details");
                        });
            });
      };


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
                                                      setTimeout(() => {
                                                            window.location.reload();
                                                      }, 1500); // 1000ms = 1 second delay
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
      KTDueInvoicesList.init();
      KTPaidInvoicesList.init();
      KTCreateInvoiceModal.init();
      KTEditInvoiceModal.init();
});