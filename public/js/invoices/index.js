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

      // Delete pending students
      const handleDeletion = function () {
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
                                                            location.reload(); // Reload to reflect changes
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

      return {
            // Public functions  
            init: function () {
                  table = document.getElementById('kt_due_invoices_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
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
                        { orderable: false, targets: 10 }, // Disable ordering on status Guardian                
                  ]
            });

            // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
            datatable.on('draw', function () {

            });
      }

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

                                                // Handle month_year select field differently
                                                const monthYearSelect = $("select[name='invoice_month_year_edit']");
                                                if (monthYearSelect.length) {
                                                      // Clear existing options
                                                      monthYearSelect.empty();

                                                      // Convert "MM_YYYY" to "Month YYYY"
                                                      const formatMonthYear = (monthYear) => {
                                                            if (!monthYear) return '';

                                                            const [month, year] = monthYear.split('_');
                                                            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                                                                  'July', 'August', 'September', 'October', 'November', 'December'];
                                                            const monthName = monthNames[parseInt(month) - 1] || month;
                                                            return `${monthName} ${year}`;
                                                      };

                                                      const formattedMonthYear = formatMonthYear(invoice.month_year);

                                                      // Create and append new option with formatted display text
                                                      const option = new Option(
                                                            formattedMonthYear,    // Display text (April 2025)
                                                            invoice.month_year,    // Original value (04_2025)
                                                            true,                 // selected
                                                            true                  // selected
                                                      );

                                                      monthYearSelect.append(option).trigger('change');

                                                      // If you need to add more options, format them similarly
                                                      // Example:
                                                      // const option2 = new Option(formatMonthYear('05_2025'), '05_2025');
                                                      // monthYearSelect.append(option2);
                                                }

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
      KTDueInvoicesList.init();
      KTPaidInvoicesList.init();
      KTCreateInvoiceModal.init();
      KTEditInvoiceModal.init();
});