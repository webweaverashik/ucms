"use strict";

var KTSheetPaymentsList = function () {
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
                        // { orderable: false, targets: 4 }, // Disable ordering on column institution                
                  ]
            });

            // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
            datatable.on('draw', function () {

            });
      }

      // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
      var handleSearch = function () {
            const filterSearch = document.querySelector('[data-sheet-payments-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                  datatable.search(e.target.value).draw();
            });
      }

      // Filter Datatable
      var handleFilter = function () {
            // Select filter options
            const filterForm = document.querySelector('[data-sheet-payments-table-filter="form"]');
            const filterButton = filterForm.querySelector('[data-sheet-payments-table-filter="filter"]');
            const resetButton = filterForm.querySelector('[data-sheet-payments-table-filter="reset"]');
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
                  table = document.getElementById('kt_sheet_payments_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  handleSearch();
                  handleFilter();
            }
      }
}();

var KTAddNotes = function () {
      // Shared variables
      const element = document.getElementById('kt_modal_add_notes');

      // Early return if element doesn't exist
      if (!element) {
            console.error('Modal element not found');
            return {
                  init: function () { }
            };
      }

      const form = element.querySelector('#kt_modal_add_notes_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);

      // Init add sheet modal
      var initAddNote = () => {
            // Cancel button handler
            const cancelButton = element.querySelector('[data-kt-add-note-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        modal.hide();
                  });
            }

            // Close button handler
            const closeButton = element.querySelector('[data-kt-add-note-modal-action="close"]');
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
                              'sheet_subject_id': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Subject is required'
                                          }
                                    }
                              },
                              'notes_name': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Note name is required'
                                          },
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

            const submitButton = element.querySelector('[data-kt-add-note-modal-action="submit"]');
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
                                    fetch(`/notes`, {
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
                                                      toastr.success(data.message || 'Note added successfully');
                                                      modal.hide();

                                                      setTimeout(() => {
                                                            window.location.reload();
                                                      }, 1500);
                                                } else {
                                                      throw new Error(data.message || 'Add failed');
                                                }
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;
                                                toastr.error(error.message || 'Failed to add note');
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
                  initAddNote();
                  initValidation();
            }
      };
}();


var KTEditNotes = function () {
    // Initialize tooltips
    const initTooltips = () => {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            let tooltip = null;
            const title = el.getAttribute('title') || el.dataset.bsOriginalTitle;
            
            if (!title) return;
            
            el.addEventListener('mouseenter', (e) => {
                tooltip = document.createElement('div');
                tooltip.className = 'custom-tooltip';
                tooltip.textContent = title;
                document.body.appendChild(tooltip);
                
                const rect = el.getBoundingClientRect();
                const tooltipWidth = tooltip.offsetWidth;
                
                tooltip.style.left = `${rect.left + rect.width/2 - tooltipWidth/2}px`;
                tooltip.style.top = `${rect.top - tooltip.offsetHeight - 8}px`;
                tooltip.style.opacity = '1';
            });
            
            el.addEventListener('mouseleave', () => {
                if (tooltip) {
                    tooltip.style.opacity = '0';
                    setTimeout(() => tooltip.remove(), 300);
                }
            });
        });
    };

    // Main topic editing functionality
    const setupTopicEditing = () => {
        document.querySelectorAll('.topic-editable').forEach(wrapper => {
            const card = wrapper;
            const topicText = wrapper.querySelector('.topic-text');
            const topicInput = wrapper.querySelector('.topic-input');
            const editIcon = wrapper.querySelector('.edit-icon');
            const checkIcon = wrapper.querySelector('.check-icon');
            const cancelIcon = wrapper.querySelector('.cancel-icon');
            const originalValue = topicInput.value;

            // Hover effects
            card.addEventListener('mouseenter', () => {
                card.classList.add('border-primary', 'shadow-sm');
            });
            
            card.addEventListener('mouseleave', () => {
                if (!topicInput.classList.contains('d-none')) return;
                card.classList.remove('border-primary', 'shadow-sm');
            });

            // Edit handler
            editIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                enterEditMode();
            });

            // Cancel handler
            cancelIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                exitEditMode();
                topicInput.value = originalValue;
            });

            // Save handler
            checkIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                saveChanges();
            });

            // Handle Enter/Escape keys
            topicInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    saveChanges();
                } else if (e.key === 'Escape') {
                    exitEditMode();
                    topicInput.value = originalValue;
                }
            });

            const enterEditMode = () => {
                topicText.classList.add('d-none');
                topicInput.classList.remove('d-none');
                editIcon.classList.add('d-none');
                checkIcon.classList.remove('d-none');
                cancelIcon.classList.remove('d-none');
                topicInput.focus();
                topicInput.select();
                card.classList.add('border-primary', 'shadow-sm');
            };

            const exitEditMode = () => {
                topicText.classList.remove('d-none');
                topicInput.classList.add('d-none');
                editIcon.classList.remove('d-none');
                checkIcon.classList.add('d-none');
                cancelIcon.classList.add('d-none');
                card.classList.remove('border-primary', 'shadow-sm');
            };

            const saveChanges = async () => {
                const updatedValue = topicInput.value.trim();
                const topicId = wrapper.dataset.id;

                if (!updatedValue) {
                    showError(wrapper, "Topic name cannot be empty");
                    topicInput.focus();
                    return;
                }

                if (updatedValue === originalValue) {
                    exitEditMode();
                    return;
                }

                // Show loading state
                checkIcon.classList.replace('bi-check-circle', 'bi-arrow-repeat');
                checkIcon.classList.add('spinning');

                try {
                    const response = await fetch(`/notes/${topicId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ topic_name: updatedValue })
                    });

                    if (!response.ok) {
                        const error = await response.json().catch(() => ({}));
                        throw new Error(error.message || 'Update failed');
                    }

                    const data = await response.json();
                    if (data.success) {
                        topicText.textContent = updatedValue;
                        exitEditMode();
                        showSuccess(wrapper, "Changes saved successfully");
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showError(wrapper, error.message || 'Failed to save changes');
                    topicInput.value = originalValue;
                } finally {
                    checkIcon.classList.replace('bi-arrow-repeat', 'bi-check-circle');
                    checkIcon.classList.remove('spinning');
                }
            };
        });
    };

    // Helper functions for showing messages
    const showSuccess = (element, message) => {
        showAlert(element, message, 'success');
    };

    const showError = (element, message) => {
        showAlert(element, message, 'danger');
    };

    const showAlert = (element, message, type) => {
        const existingAlert = element.querySelector(`.alert-${type}`);
        if (existingAlert) existingAlert.remove();

        const alert = document.createElement('div');
        alert.className = `alert alert-${type} py-1 px-2 mt-2 fade-in`;
        alert.innerHTML = `
            <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'} me-1"></i> 
            ${message}
        `;
        element.appendChild(alert);

        setTimeout(() => {
            alert.classList.add('fade-out');
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    };

    // Initialize everything
//     initTooltips();
    setupTopicEditing();
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

            const editButtons = document.querySelectorAll("[data-bs-target='#kt_modal_edit_sheet']");
            if (editButtons.length) {
                  editButtons.forEach((button) => {
                        button.addEventListener("click", function () {
                              sheetId = this.getAttribute("data-sheet-id");
                              const sheetClass = this.getAttribute("data-sheet-class");
                              const sheetPrice = this.getAttribute("data-sheet-price");

                              console.log("Sheet ID:", sheetId);

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

                              // Show modal (if not using Bootstrap's auto show via data-bs attributes)
                              // modal.show(); // Uncomment if showing programmatically
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
                                                if (!response.ok) throw new Error('Network response was not ok');
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
      KTSheetPaymentsList.init();
      KTAddNotes.init();
      KTEditNotes.init();
      KTEditSheet.init();
});