/**
 * Cost Types - Settings Module JavaScript
 * Handles CRUD operations with AJAX using Metronic FormValidation
 */

'use strict';

var KTCostTypes = function () {
      // Elements
      var modal;
      var modalEl;
      var form;
      var submitBtn;
      var validator;
      var isEditMode = false;

      // Private functions
      var initModal = function () {
            modalEl = document.getElementById('kt_modal_cost_type');
            if (!modalEl) return;

            modal = new bootstrap.Modal(modalEl);
            form = document.getElementById('kt_modal_cost_type_form');
            submitBtn = document.getElementById('kt_modal_cost_type_submit');
      };

      var initValidation = function () {
            if (!form) return;

            validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'name': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Cost type name is required'
                                          },
                                          stringLength: {
                                                max: 255,
                                                message: 'Name must be less than 255 characters'
                                          }
                                    }
                              },
                              'description': {
                                    validators: {
                                          stringLength: {
                                                max: 500,
                                                message: 'Description must be less than 500 characters'
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

      var resetForm = function () {
            if (!form) return;

            form.reset();
            document.getElementById('cost_type_id').value = '';

            // Reset validation
            if (validator) {
                  validator.resetForm(true);
            }

            isEditMode = false;
            document.getElementById('modalTitle').textContent = 'Add Cost Type';
      };

      var handleAddButton = function () {
            var addBtn = document.getElementById('btnAddCostType');
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

                        document.getElementById('modalTitle').textContent = 'Edit Cost Type';
                        document.getElementById('cost_type_id').value = this.dataset.id;
                        document.getElementById('cost_type_name').value = this.dataset.name;
                        document.getElementById('cost_type_description').value = this.dataset.description || '';

                        modal.show();
                  });
            });
      };

      var handleToggleActive = function () {
            document.querySelectorAll('.toggle-active').forEach(function (toggle) {
                  toggle.addEventListener('change', function () {
                        var id = this.dataset.id;
                        var checkbox = this;
                        var card = checkbox.closest('.col-md-3').querySelector('.card');

                        fetch(KTCostTypesConfig.routes.toggleActive, {
                              method: 'POST',
                              headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': KTCostTypesConfig.csrfToken,
                                    'Accept': 'application/json'
                              },
                              body: JSON.stringify({ id: id })
                        })
                              .then(function (response) {
                                    return response.json();
                              })
                              .then(function (data) {
                                    if (data.success) {
                                          if (data.data.is_active) {
                                                card.classList.remove('inactive');
                                          } else {
                                                card.classList.add('inactive');
                                          }

                                          toastr.success(data.message);
                                    } else {
                                          checkbox.checked = !checkbox.checked;
                                          toastr.error(data.message || 'Something went wrong!');
                                    }
                              })
                              .catch(function (error) {
                                    checkbox.checked = !checkbox.checked;
                                    toastr.error('Something went wrong!');
                              });
                  });
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

            var id = document.getElementById('cost_type_id').value;
            var url = isEditMode
                  ? KTCostTypesConfig.routes.update.replace(':id', id)
                  : KTCostTypesConfig.routes.store;
            var method = isEditMode ? 'PUT' : 'POST';

            var formData = {
                  name: document.getElementById('cost_type_name').value.trim(),
                  description: document.getElementById('cost_type_description').value.trim()
            };

            fetch(url, {
                  method: method,
                  headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': KTCostTypesConfig.csrfToken,
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
            var costTypeLink = document.getElementById('settings_cost_type_link');

            if (settingsLink) settingsLink.classList.add('active');
            if (costTypeLink) costTypeLink.classList.add('active');
      };

      // Public methods
      return {
            init: function () {
                  initNavigation();
                  initModal();
                  initValidation();
                  handleAddButton();
                  handleEditButtons();
                  handleToggleActive();
                  handleFormSubmit();
                  handleModalHidden();
            }
      };
}();

// Initialize on DOM ready
KTUtil.onDOMContentLoaded(function () {
      KTCostTypes.init();
});