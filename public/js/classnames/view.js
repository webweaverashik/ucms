"use strict";

var KTAddSubject = function () {
      // Shared variables
      const element = document.getElementById('kt_modal_add_subject');

      // Early return if element doesn't exist
      if (!element) {
            console.error('Modal element not found');
            return {
                  init: function () { }
            };
      }

      const form = element.querySelector('#kt_modal_add_subject_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);

      // Init add sheet modal
      var initAddSubjectModal = () => {
            // Cancel button handler
            const cancelButton = element.querySelector('[data-kt-add-subject-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        // Reset select2
                        $(form).find('select[data-control="select2"]').val(null).trigger('change');
                        modal.hide();
                  });
            }

            // Close button handler
            const closeButton = element.querySelector('[data-kt-add-subject-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        $(form).find('select[data-control="select2"]').val(null).trigger('change');
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
                              'subject_name': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Subject is required'
                                          }
                                    }
                              },
                              'subject_group': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Group is required'
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

            const submitButton = element.querySelector('[data-kt-add-subject-modal-action="submit"]');

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
                                    fetch(`/subjects`, {
                                          method: 'POST',
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(response => {
                                                if (!response.ok) {
                                                      return response.json().then(errorData => {
                                                            throw new Error(errorData.message || 'Network response was not ok');
                                                      });
                                                }
                                                return response.json();
                                          })
                                          .then(data => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      toastr.success(data.message || 'Subject added successfully');
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
                                                toastr.error(error.message || 'Failed to add subject');
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
                  initAddSubjectModal();
                  initValidation();
            }
      };
}();

var KTEditSubject = function () {
      // Main subject editing functionality
      const setupSubjectEditing = () => {
            document.querySelectorAll('.subject-editable').forEach(card => {
                  const subjectText = card.querySelector('.subject-text');
                  const subjectInput = card.querySelector('.subject-input');
                  const editIcon = card.querySelector('.edit-icon');
                  const deleteIcon = card.querySelector('.delete-subject');
                  const checkIcon = card.querySelector('.check-icon');
                  const cancelIcon = card.querySelector('.cancel-icon');

                  if (!subjectText || !subjectInput) return;

                  const originalValue = subjectInput.value;

                  // Edit handler
                  if (editIcon) {
                        editIcon.addEventListener('click', (e) => {
                              e.stopPropagation();
                              enterEditMode();
                        });
                  }

                  // Cancel handler
                  if (cancelIcon) {
                        cancelIcon.addEventListener('click', (e) => {
                              e.stopPropagation();
                              exitEditMode();
                              subjectInput.value = originalValue;
                        });
                  }

                  // Save handler
                  if (checkIcon) {
                        checkIcon.addEventListener('click', (e) => {
                              e.stopPropagation();
                              saveChanges();
                        });
                  }

                  // Handle Enter/Escape keys
                  subjectInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                              e.preventDefault();
                              saveChanges();
                        } else if (e.key === 'Escape') {
                              exitEditMode();
                              subjectInput.value = originalValue;
                        }
                  });

                  const enterEditMode = () => {
                        // Hide view mode elements
                        subjectText.classList.add('d-none');
                        if (editIcon) editIcon.classList.add('d-none');
                        if (deleteIcon) deleteIcon.classList.add('d-none');

                        // Show edit mode elements
                        subjectInput.classList.remove('d-none');
                        if (checkIcon) checkIcon.classList.remove('d-none');
                        if (cancelIcon) cancelIcon.classList.remove('d-none');

                        // Add editing class for styling
                        card.classList.add('is-editing');

                        // Focus and select input
                        subjectInput.focus();
                        subjectInput.select();
                  };

                  const exitEditMode = () => {
                        // Show view mode elements
                        subjectText.classList.remove('d-none');
                        if (editIcon) editIcon.classList.remove('d-none');
                        if (deleteIcon) deleteIcon.classList.remove('d-none');

                        // Hide edit mode elements
                        subjectInput.classList.add('d-none');
                        if (checkIcon) checkIcon.classList.add('d-none');
                        if (cancelIcon) cancelIcon.classList.add('d-none');

                        // Remove editing class
                        card.classList.remove('is-editing');
                  };

                  const saveChanges = async () => {
                        const updatedValue = subjectInput.value.trim();
                        const subjectId = card.dataset.id;

                        if (!updatedValue) {
                              toastr.error("Subject name cannot be empty");
                              subjectInput.focus();
                              return;
                        }

                        if (updatedValue === originalValue) {
                              exitEditMode();
                              return;
                        }

                        // Show loading state
                        const saveIcon = checkIcon.querySelector('i');
                        if (saveIcon) {
                              saveIcon.classList.remove('ki-check');
                              saveIcon.classList.add('ki-arrows-circle', 'spinning');
                        }
                        checkIcon.disabled = true;

                        try {
                              const response = await fetch(`/subjects/${subjectId}`, {
                                    method: 'PUT',
                                    headers: {
                                          'Content-Type': 'application/json',
                                          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    },
                                    body: JSON.stringify({ subject_name: updatedValue })
                              });

                              if (!response.ok) {
                                    const error = await response.json().catch(() => ({}));
                                    throw new Error(error.message || 'Update failed');
                              }

                              const data = await response.json();
                              if (data.success) {
                                    subjectText.textContent = updatedValue;
                                    exitEditMode();
                                    toastr.success("Subject updated successfully");
                              }
                        } catch (error) {
                              console.error('Error:', error);
                              toastr.error(error.message || 'Failed to update subject');
                              subjectInput.value = originalValue;
                        } finally {
                              if (saveIcon) {
                                    saveIcon.classList.remove('ki-arrows-circle', 'spinning');
                                    saveIcon.classList.add('ki-check');
                              }
                              checkIcon.disabled = false;
                        }
                  };
            });
      };

      // Delete subject handler
      const handleSubjectDeletion = function () {
            document.querySelectorAll('.delete-subject').forEach(item => {
                  item.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        let subjectId = this.getAttribute('data-subject-id');
                        if (!subjectId) return;

                        let url = routeDeleteSubject.replace(':id', subjectId);

                        Swal.fire({
                              title: "Are you sure to delete this subject?",
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
                                                      toastr.success('Subject deleted successfully');
                                                      setTimeout(() => {
                                                            window.location.reload();
                                                      }, 1500);
                                                } else {
                                                      toastr.error(data.message);
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
            init: function () {
                  setupSubjectEditing();
                  handleSubjectDeletion();
            }
      };
}();

var KTEditClassName = function () {
      // Shared variables
      const element = document.getElementById('kt_modal_edit_class');

      // Early return if element doesn't exist
      if (!element) {
            console.error('Modal element not found');
            return {
                  init: function () { }
            };
      }

      const form = element.querySelector('#kt_modal_edit_class_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);
      let classId = null;

      // Init edit institution modal
      var initEditClass = () => {
            // Cancel button handler
            const cancelButton = element.querySelector('[data-kt-edit-class-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        modal.hide();
                  });
            }

            // Close button handler
            const closeButton = element.querySelector('[data-kt-edit-class-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        modal.hide();
                  });
            }

            // AJAX form data load
            const editButtons = document.querySelectorAll("[data-bs-target='#kt_modal_edit_class']");
            if (editButtons.length) {
                  editButtons.forEach((button) => {
                        button.addEventListener("click", function () {
                              classId = this.getAttribute("data-class-id");

                              if (!classId) return;

                              // Clear form
                              if (form) form.reset();

                              fetch(`/classnames/ajax-data/${classId}`)
                                    .then(response => {
                                          if (!response.ok) {
                                                return response.json().then(errorData => {
                                                      throw new Error(errorData.message || 'Network response was not ok');
                                                });
                                          }
                                          return response.json();
                                    })
                                    .then(data => {
                                          if (data.success && data.data) {
                                                const classname = data.data;

                                                // Helper function to safely set values
                                                const setValue = (selector, value) => {
                                                      const el = document.querySelector(selector);
                                                      if (el) el.value = value;
                                                };

                                                // Populate form fields
                                                setValue("input[name='class_name_edit']", classname.class_name);
                                                setValue("select[name='class_numeral_edit']", classname.class_numeral);
                                                setValue("input[name='description_edit']", classname.class_description);

                                                // Set modal title
                                                const modalTitle = document.getElementById("kt_modal_edit_class_title");
                                                if (modalTitle) {
                                                      modalTitle.textContent = `Update - ${classname.class_name} (${classname.class_numeral})`;
                                                }

                                                // Trigger change events
                                                const classNumeralSelect = document.querySelector("select[name='class_numeral_edit']");
                                                if (classNumeralSelect) classNumeralSelect.dispatchEvent(new Event("change"));

                                                // Show modal
                                                modal.show();
                                          } else {
                                                throw new Error(data.message || 'Invalid response data');
                                          }
                                    })
                                    .catch(error => {
                                          console.error("Error:", error);
                                          toastr.error(error.message || "Failed to load class details");
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
                              'class_name_edit': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Name is required'
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

            const submitButton = element.querySelector('[data-kt-edit-class-modal-action="submit"]');

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
                                    formData.append('_method', 'PUT');

                                    // Submit via AJAX
                                    fetch(`/classnames/${classId}`, {
                                          method: 'POST',
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(response => {
                                                if (!response.ok) {
                                                      return response.json().then(errorData => {
                                                            throw new Error(errorData.message || 'Network response was not ok');
                                                      });
                                                }
                                                return response.json();
                                          })
                                          .then(data => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      toastr.success(data.message || 'Class updated successfully');
                                                      modal.hide();
                                                      // Reload the page
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
                                                toastr.error(error.message || 'Failed to update class');
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
                  initEditClass();
                  initValidation();
            }
      };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTAddSubject.init();
      KTEditSubject.init();
      KTEditClassName.init();
});