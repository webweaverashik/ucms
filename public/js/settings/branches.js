/**
 * Branches - Settings Module JavaScript
 * Handles CRUD operations with AJAX using Metronic FormValidation
 */

'use strict';

var KTBranches = function () {
      // Elements
      var modal;
      var modalEl;
      var form;
      var submitBtn;
      var validator;
      var isEditMode = false;

      // Private functions
      var initModal = function () {
            modalEl = document.getElementById('kt_modal_branch');
            if (!modalEl) return;

            modal = new bootstrap.Modal(modalEl);
            form = document.getElementById('kt_modal_branch_form');
            submitBtn = document.getElementById('kt_modal_branch_submit');
      };

      var initValidation = function () {
            if (!form) return;

            validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'branch_name': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Branch name is required'
                                          },
                                          stringLength: {
                                                max: 20,
                                                message: 'Branch name must be less than 20 characters'
                                          }
                                    }
                              },
                              'branch_prefix': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Branch prefix is required'
                                          },
                                          stringLength: {
                                                min: 1,
                                                max: 1,
                                                message: 'Branch prefix must be exactly 1 letter'
                                          },
                                          regexp: {
                                                regexp: /^[A-Za-z]$/,
                                                message: 'Branch prefix must be a letter (A-Z)'
                                          }
                                    }
                              },
                              'address': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Address is required'
                                          },
                                          stringLength: {
                                                max: 500,
                                                message: 'Address must be less than 500 characters'
                                          }
                                    }
                              },
                              'phone_number': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Phone number is required'
                                          },
                                          regexp: {
                                                regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                                message: "Please enter a valid Bangladeshi mobile number",
                                          },
                                          stringLength: {
                                                min: 11,
                                                max: 11,
                                                message: "The mobile number must be exactly 11 digits",
                                          },
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

      var resetForm = function () {
            if (!form) return;

            form.reset();
            document.getElementById('branch_id').value = '';

            // Reset validation
            if (validator) {
                  validator.resetForm(true);
            }

            isEditMode = false;
            document.getElementById('modalTitle').textContent = 'Add Branch';
      };

      var handleAddButton = function () {
            var addBtn = document.getElementById('btnAddBranch');
            if (!addBtn) return;

            addBtn.addEventListener('click', function () {
                  resetForm();
                  modal.show();
            });
      };

      var handleEditButtons = function () {
            document.querySelectorAll('.btn-edit').forEach(function (btn) {
                  btn.addEventListener('click', function () {
                        resetForm();
                        isEditMode = true;

                        document.getElementById('modalTitle').textContent = 'Edit Branch';
                        document.getElementById('branch_id').value = this.dataset.id;
                        document.getElementById('branch_name').value = this.dataset.name;
                        document.getElementById('branch_prefix').value = this.dataset.prefix;
                        document.getElementById('branch_address').value = this.dataset.address || '';
                        document.getElementById('branch_phone_number').value = this.dataset.phone || '';

                        modal.show();
                  });
            });
      };

      var handlePrefixUppercase = function () {
            var prefixInput = document.getElementById('branch_prefix');
            if (!prefixInput) return;

            prefixInput.addEventListener('input', function () {
                  this.value = this.value.toUpperCase();
            });
      };

      var handleFormSubmit = function () {
            if (!form || !submitBtn) return;

            submitBtn.addEventListener('click', function (e) {
                  e.preventDefault();

                  if (validator) {
                        validator.validate().then(function (status) {
                              if (status === 'Valid') {
                                    submitForm();
                              } else {
                                    toastr.error('Please fill all required fields correctly.');
                              }
                        });
                  }
            });
      };

      var submitForm = function () {
            // Show loading
            submitBtn.setAttribute('data-kt-indicator', 'on');
            submitBtn.disabled = true;

            var id = document.getElementById('branch_id').value;
            var url = isEditMode
                  ? KTBranchesConfig.routes.update.replace(':id', id)
                  : KTBranchesConfig.routes.store;
            var method = isEditMode ? 'PUT' : 'POST';

            var formData = {
                  branch_name: document.getElementById('branch_name').value.trim(),
                  branch_prefix: document.getElementById('branch_prefix').value.trim().toUpperCase(),
                  address: document.getElementById('branch_address').value.trim(),
                  phone_number: document.getElementById('branch_phone_number').value.trim()
            };

            fetch(url, {
                  method: method,
                  headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': KTBranchesConfig.csrfToken,
                        'Accept': 'application/json'
                  },
                  body: JSON.stringify(formData)
            })
                  .then(function (response) {
                        return response.json();
                  })
                  .then(function (data) {
                        submitBtn.removeAttribute('data-kt-indicator');
                        submitBtn.disabled = false;

                        if (data.success) {
                              modal.hide();
                              toastr.success(data.message);

                              // Reload after a short delay
                              setTimeout(function () {
                                    location.reload();
                              }, 1000);
                        } else {
                              // Handle validation errors from server
                              if (data.errors) {
                                    Object.keys(data.errors).forEach(function (key) {
                                          // Update the field with error using FormValidation
                                          validator.updateFieldStatus(key, 'Invalid', 'notEmpty');

                                          // Show error message manually
                                          var field = form.querySelector('[name="' + key + '"]');
                                          if (field) {
                                                var parent = field.closest('.fv-row');
                                                if (parent) {
                                                      var errorContainer = parent.querySelector('.fv-plugins-message-container');
                                                      if (!errorContainer) {
                                                            errorContainer = document.createElement('div');
                                                            errorContainer.className = 'fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback';
                                                            parent.appendChild(errorContainer);
                                                      }
                                                      errorContainer.innerHTML = '<div class="fv-help-block"><span role="alert">' + data.errors[key][0] + '</span></div>';
                                                      errorContainer.style.display = 'block';
                                                }
                                          }
                                    });
                              }
                              if (data.message) {
                                    toastr.error(data.message);
                              }
                        }
                  })
                  .catch(function (error) {
                        submitBtn.removeAttribute('data-kt-indicator');
                        submitBtn.disabled = false;

                        toastr.error('Something went wrong!');
                  });
      };

      var handleModalHidden = function () {
            if (!modalEl) return;

            modalEl.addEventListener('hidden.bs.modal', function () {
                  resetForm();
            });
      };

      var initNavigation = function () {
            var settingsLink = document.getElementById('settings_link');
            var branchLink = document.getElementById('settings_branch_link');

            if (settingsLink) settingsLink.classList.add('active');
            if (branchLink) branchLink.classList.add('active');
      };

      // Public methods
      return {
            init: function () {
                  initNavigation();
                  initModal();
                  initValidation();
                  handleAddButton();
                  handleEditButtons();
                  handlePrefixUppercase();
                  handleFormSubmit();
                  handleModalHidden();
            }
      };
}();

// Initialize on DOM ready
KTUtil.onDOMContentLoaded(function () {
      KTBranches.init();
});