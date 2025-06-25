"use strict";

var KTSheetsList = function () {
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
                        { orderable: false, targets: 4 }, // Disable ordering on column institution                
                  ]
            });

            // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
            datatable.on('draw', function () {

            });
      }

      // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
      var handleSearch = function () {
            const filterSearch = document.querySelector('[data-kt-sheet-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                  datatable.search(e.target.value).draw();
            });
      }

      return {
            // Public functions  
            init: function () {
                  table = document.getElementById('kt_all_sheets_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  handleSearch();
            }
      }
}();

var KTAddSheet = function () {
      // Shared variables
      const element = document.getElementById('kt_modal_add_sheet');

      // Early return if element doesn't exist
      if (!element) {
            console.error('Modal element not found');
            return {
                  init: function () { }
            };
      }

      const form = element.querySelector('#kt_modal_add_sheet_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);

      // Init add sheet modal
      var initAddSheet = () => {
            // Cancel button handler
            const cancelButton = element.querySelector('[data-kt-sheet-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        modal.hide();
                  });
            }

            // Close button handler
            const closeButton = element.querySelector('[data-kt-sheet-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        modal.hide();
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
                              'sheet_class_id': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Class is required'
                                          }
                                    }
                              },
                              'sheet_price': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Price is required'
                                          },
                                          numeric: {
                                                message: 'The value must be a number'
                                          },
                                          greaterThan: {
                                                min: 100,
                                                message: 'The price must be at least 100'
                                          }
                                    }
                              },
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

            const submitButton = element.querySelector('[data-kt-sheet-modal-action="submit"]');
            if (submitButton && validator) {
                  submitButton.addEventListener('click', function (e) {
                        e.preventDefault();

                        validator.validate().then(function (status) {
                              if (status == 'Valid') {
                                    // Show loading indication
                                    submitButton.setAttribute('data-kt-indicator', 'on');
                                    submitButton.disabled = true;

                                    // Prepare form data
                                    const formData = new FormData(form);

                                    // Add CSRF token for Laravel
                                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                                    // Submit via AJAX
                                    fetch(`/sheets`, {
                                          method: 'POST', // Laravel expects POST for PUT routes
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(response => {
                                                if (!response.ok) {
                                                      return response.json().then(errorData => {
                                                            // Show error from Laravel if available
                                                            throw new Error(errorData.message || 'Network response was not ok');
                                                      });
                                                }
                                                return response.json();
                                          })
                                          .then(data => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      toastr.success(data.message || 'Sheet added successfully');
                                                      modal.hide();

                                                      setTimeout(() => {
                                                            window.location.reload();
                                                      }, 1500);
                                                } else {
                                                      throw new Error(data.message || 'Creation failed');
                                                }
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;
                                                toastr.error(error.message || 'Failed to add sheet');
                                                console.error('Error:', error);
                                          });

                              } else {
                                    toastr.warning('Please fill all required fields');
                              }
                        });
                  });
            }
      }

      return {
            init: function () {
                  initAddSheet();
                  initValidation();
            }
      };
}();

var KTEditSheet = function () {
      // Shared variables
      const element = document.getElementById('kt_modal_edit_sheet');

      // Early return if element doesn't exist
      if (!element) {
            console.error('Modal element not found');
            return {
                  init: function () { }
            };
      }

      const form = element.querySelector('#kt_modal_edit_sheet_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);

      let sheetId = null; // Declare globally

      // Init edit institution modal
      const initEditSheet = () => {
            // Cancel button
            const cancelButton = element.querySelector('[data-kt-sheet-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        modal.hide();
                  });
            }

            // Close button
            const closeButton = element.querySelector('[data-kt-sheet-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        modal.hide();
                  });
            }

            // Delegated edit button click handler
            document.addEventListener("click", function (e) {
                  const button = e.target.closest("[data-bs-target='#kt_modal_edit_sheet']");
                  if (!button) return;

                  const sheetId = button.getAttribute("data-sheet-id");
                  const sheetClass = button.getAttribute("data-sheet-class");
                  const sheetPrice = button.getAttribute("data-sheet-price");

                  // Clear form if needed
                  if (form) form.reset();

                  // Set modal title
                  const modalTitle = document.getElementById("kt_modal_edit_sheet_title");
                  if (modalTitle) {
                        modalTitle.textContent = `Update - ${sheetClass} - sheet group`;
                  }

                  // Set sheet price input value
                  const priceInput = document.querySelector("input[name='sheet_price_edit']");
                  if (priceInput) {
                        priceInput.value = sheetPrice;
                  }

                  // Show modal if not auto-triggered
                  modal.show();
            });
      };


      // Form validation
      var initValidation = function () {
            if (!form) return;

            var validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'sheet_price_edit': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Price is required'
                                          },
                                          numeric: {
                                                message: 'The value must be a number'
                                          },
                                          greaterThan: {
                                                min: 100,
                                                message: 'The price must be at least 100'
                                          }
                                    }
                              },
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

            const submitButton = element.querySelector('[data-kt-sheet-modal-action="submit"]');
            if (submitButton && validator) {
                  submitButton.addEventListener('click', function (e) {
                        e.preventDefault();

                        validator.validate().then(function (status) {
                              if (status == 'Valid') {
                                    // Show loading indication
                                    submitButton.setAttribute('data-kt-indicator', 'on');
                                    submitButton.disabled = true;

                                    // Prepare form data
                                    const formData = new FormData(form);

                                    // Add CSRF token for Laravel
                                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                                    formData.append('_method', 'PUT'); // For Laravel resource route

                                    // Submit via AJAX
                                    fetch(`/sheets/${sheetId}`, {
                                          method: 'POST', // Laravel expects POST for PUT routes
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(response => {
                                                if (!response.ok) {
                                                      return response.json().then(errorData => {
                                                            // Show error from Laravel if available
                                                            throw new Error(errorData.message || 'Network response was not ok');
                                                      });
                                                }
                                                return response.json();
                                          })
                                          .then(data => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      toastr.success(data.message || 'Sheet group updated successfully');
                                                      modal.hide();

                                                      setTimeout(() => {
                                                            window.location.reload();
                                                      }, 1500);

                                                } else {
                                                      throw new Error(data.message || 'Update failed');
                                                }
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;
                                                toastr.error(error.message || 'Failed to update sheet');
                                                console.error('Error:', error);
                                          });
                              } else {
                                    toastr.warning('Please fill all required fields');
                              }
                        });
                  });
            }
      }

      return {
            init: function () {
                  initEditSheet();
                  initValidation();
            }
      };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTSheetsList.init();
      KTAddSheet.init();
      KTEditSheet.init();
});