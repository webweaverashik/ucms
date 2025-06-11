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

      // Delete pending students
      const handleDeletion = function () {
            document.querySelectorAll('.delete-sheet').forEach(item => {
                  item.addEventListener('click', function (e) {
                        e.preventDefault();

                        let sheetId = this.getAttribute('data-sheet-id');
                        let url = routeDeleteSheet.replace(':id', sheetId);  // Replace ':id' with actual student ID

                        Swal.fire({
                              title: "Are you sure to delete this institution?",
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
                                                            text: "The institution has been removed successfully.",
                                                            icon: "success",
                                                      }).then(() => {
                                                            location.reload(); // Reload to reflect changes
                                                      });
                                                } else {
                                                      Swal.fire({
                                                            title: "Error!",
                                                            text: data.message,
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
                  table = document.getElementById('kt_all_sheets_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  handleSearch();
                  handleDeletion();
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
                                                      window.location.reload();
                                                } else {
                                                      throw new Error(data.message || 'Update failed');
                                                }
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;
                                                toastr.error(error.message || 'Failed to update institution');
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
      var initEditSheet = () => {
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

            // AJAX form data load
            const editButtons = document.querySelectorAll("[data-bs-target='#kt_modal_edit_sheet']");
            if (editButtons.length) {
                  editButtons.forEach((button) => {
                        button.addEventListener("click", function () {
                              sheetId = this.getAttribute("data-sheet-id"); // Assign value globally
                              console.log("sheet ID:", sheetId);
                              if (!sheetId) return;

                              // Clear form
                              if (form) form.reset();

                              fetch(`/sheets/${sheetId}`)
                                    .then(response => {
                                          if (!response.ok) throw new Error('Network response was not ok');
                                          return response.json();
                                    })
                                    .then(data => {
                                          if (data.success && data.data) {
                                                const institution = data.data;

                                                // Helper function to safely set values
                                                const setValue = (selector, value) => {
                                                      const el = document.querySelector(selector);
                                                      if (el) el.value = value;
                                                };

                                                // Helper function to safely check radio buttons
                                                const checkRadio = (name, value) => {
                                                      const radio = document.querySelector(`input[name='${name}'][value='${value}']`);
                                                      if (radio) radio.checked = true;
                                                };

                                                // Populate form fields
                                                setValue("input[name='institution_name_edit']", institution.name);
                                                setValue("input[name='eiin_number_edit']", institution.eiin_number);
                                                checkRadio('institution_type_edit', institution.type);

                                                // Show modal
                                                modal.show();
                                          } else {
                                                throw new Error(data.message || 'Invalid response data');
                                          }
                                    })
                                    .catch(error => {
                                          console.error("Error:", error);
                                          toastr.error(error.message || "Failed to load institution details");
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
                              'sheet_class_id_edit': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Name is required'
                                          }
                                    }
                              },
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

            const submitButton = element.querySelector('[data-kt-institutions-modal-action="submit"]');
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
                                    fetch(`/institutions/${institutionId}`, {
                                          method: 'POST', // Laravel expects POST for PUT routes
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
                                                      toastr.success(data.message || 'institution updated successfully');
                                                      modal.hide();

                                                      // Reload the page
                                                      window.location.reload();
                                                } else {
                                                      throw new Error(data.message || 'Update failed');
                                                }
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;
                                                toastr.error(error.message || 'Failed to update institution');
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