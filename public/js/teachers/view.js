"use strict";

var KTEditTeacherModal = function () {
      // Shared variables
      const element = document.getElementById('kt_modal_edit_teacher');
      const form = element ? element.querySelector('#kt_modal_edit_teacher_form') : null;
      const modal = element ? new bootstrap.Modal(element) : null;

      let teacherId = null;
      let validator = null; // Declare validator globally

      // Init Edit User Modal (only if modal exists)
      const initEditTeacher = () => {
            if (!element) return;

            document.addEventListener('click', function (e) {
                  const editBtn = e.target.closest("[data-bs-target='#kt_modal_edit_teacher']");
                  if (!editBtn) return;

                  e.preventDefault();

                  teacherId = editBtn.getAttribute("data-teacher-id");
                  console.log('Teacher ID:', teacherId);

                  if (!teacherId) return;

                  if (form) form.reset();

                  // AJAX data fetch
                  fetch(`/teachers/${teacherId}/ajax-data`)
                        .then(response => {
                              if (!response.ok) throw new Error('Network response was not ok');
                              return response.json();
                        })
                        .then(data => {
                              if (data.success && data.data) {
                                    const teacher = data.data;

                                    const titleEl = document.getElementById("kt_modal_edit_teacher_title");
                                    if (titleEl) {
                                          titleEl.textContent = `Update teacher ${teacher.name}`;
                                    }

                                    if (form) {
                                          form.querySelector("input[name='teacher_name_edit']").value = teacher.name || '';
                                          form.querySelector("input[name='teacher_salary_edit']").value = teacher.base_salary ?? '';
                                          form.querySelector("input[name='teacher_phone_edit']").value = teacher.phone || '';
                                          form.querySelector("input[name='teacher_email_edit']").value = teacher.email || '';

                                          // Gender (simple)
                                          const gender = teacher.gender; // 'male' or 'female'
                                          if (gender) {
                                                const genderRadio = form.querySelector(
                                                      `input[name='teacher_gender_edit'][value="${gender}"]`
                                                );

                                                if (genderRadio) {
                                                      genderRadio.checked = true;
                                                      genderRadio.dispatchEvent(new Event('change'));
                                                }
                                          }

                                          // Select2 blood group handler (keep jQuery part)
                                          const setSelect2Value = (name, value) => {
                                                const el = $(`select[name="${name}"]`);
                                                if (el.length) {
                                                      el.val(value).trigger('change');
                                                }
                                          };
                                          setSelect2Value("teacher_blood_group_edit", teacher.blood_group);

                                          form.querySelector("input[name='teacher_qualification_edit']").value = teacher.qualification || '';
                                          form.querySelector("input[name='teacher_experience_edit']").value = teacher.experience || '';
                                    }

                                    if (modal) modal.show();
                              } else {
                                    throw new Error(data.message || 'Invalid response data');
                              }
                        })
                        .catch(error => {
                              console.error("Error:", error);
                              toastr.error(error.message || "Failed to load user details");
                        });
            });

            // Cancel and close buttons
            const cancelButton = element.querySelector('[data-edit-teachers-modal-action="cancel"]');
            const closeButton = element.querySelector('[data-edit-teachers-modal-action="close"]');
            [cancelButton, closeButton].forEach(btn => {
                  if (btn) {
                        btn.addEventListener('click', e => {
                              e.preventDefault();
                              if (form) form.reset();
                              if (modal) modal.hide();
                        });
                  }
            });
      };

      // Form validation (unchanged logic, only run if form exists)
      var initEditFormValidation = function () {
            if (!form) return;

            validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'teacher_name_edit': { validators: { notEmpty: { message: 'Teacher name is required' } } },
                              'teacher_gender_edit': { validators: { notEmpty: { message: 'Gender is required' } } },
                              'teacher_email_edit': {
                                    validators: {
                                          notEmpty: { message: 'Email is required' },
                                          emailAddress: { message: 'Enter a valid email address' },
                                    }
                              },
                              'teacher_phone_edit': {
                                    validators: {
                                          notEmpty: { message: 'Mobile no. is required' },
                                          regexp: {
                                                regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                                message: 'Please enter a valid Bangladeshi mobile number'
                                          },
                                          stringLength: { min: 11, max: 11, message: 'The mobile number must be exactly 11 digits' }
                                    }
                              },
                              'teacher_salary_edit': {
                                    validators: {
                                          notEmpty: { message: 'Salary is required' },
                                          greaterThan: { min: 100, message: 'Salary must be at least 100' }
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

            const submitButton = element.querySelector('[data-edit-teachers-modal-action="submit"]');

            if (submitButton && validator) {
                  submitButton.addEventListener('click', function (e) {
                        e.preventDefault();

                        validator.validate().then(function (status) {
                              if (status === 'Valid') {
                                    submitButton.setAttribute('data-kt-indicator', 'on');
                                    submitButton.disabled = true;

                                    const formData = new FormData(form);
                                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                                    formData.append('_method', 'PUT');

                                    fetch(`/teachers/${teacherId}`, {
                                          method: 'POST',
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(response => {
                                                if (!response.ok) {
                                                      return response.json().then(errorData => { throw new Error(errorData.message || 'Network response was not ok'); });
                                                }
                                                return response.json();
                                          })
                                          .then(data => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      toastr.success(data.message || 'Teacher updated successfully');
                                                      if (modal) modal.hide();
                                                      setTimeout(() => { window.location.reload(); }, 1500);
                                                } else {
                                                      throw new Error(data.message || 'Teacher Update failed');
                                                }
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;
                                                toastr.error(error.message || 'Failed to update user');
                                                console.error('Error:', error);
                                          });
                              } else {
                                    toastr.warning('Please fill all required fields');
                              }
                        });
                  });
            }
      };

      // Teacher Toggle activation (registered regardless of modal)
      const handleToggleActivation = function () {
            document.addEventListener('click', function (e) {
                  // match anchor with both data attributes (your blade uses these)
                  const btn = e.target.closest('a[data-teacher-id][data-active-status]');
                  if (!btn) return;

                  // Prevent accidental matches on other anchors
                  if (!btn.classList.contains('toggle-teacher-status') && !btn.classList.contains('toggle-teacher-status-auto')) {
                        // If you want to rely only on data attributes, remove this guard.
                        // For now, allow both: use class 'toggle-teacher-status' in blade to be explicit.
                  }

                  e.preventDefault();

                  const teacherId = btn.getAttribute('data-teacher-id');
                  const teacherName = btn.getAttribute('data-teacher-name') || 'Teacher';
                  const isActive = btn.getAttribute('data-active-status') == "1" ? true : false;

                  const actionText = isActive ? "Deactivate" : "Activate";
                  const newStatus = isActive ? 0 : 1;

                  Swal.fire({
                        title: `${actionText} ${teacherName}?`,
                        text: `Are you sure you want to ${actionText.toLowerCase()} this teacher?`,
                        icon: isActive ? "warning" : "info",
                        showCancelButton: true,
                        confirmButtonText: actionText,
                        cancelButtonText: "Cancel",
                        reverseButtons: true,
                        buttonsStyling: false,
                        customClass: {
                              confirmButton: isActive ? "btn btn-danger" : "btn btn-success",
                              cancelButton: "btn btn-light"
                        }
                  }).then((result) => {
                        if (result.isConfirmed) {
                              let url = routeToggleActive.replace(':id', teacherId);

                              fetch(url, {
                                    method: 'POST',
                                    headers: {
                                          "Content-Type": "application/json",
                                          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                    },
                                    body: JSON.stringify({
                                          teacher_id: teacherId,
                                          is_active: newStatus,
                                    })
                              })
                                    .then(response => response.json())
                                    .then(data => {
                                          if (data.success) {
                                                Swal.fire({
                                                      title: "Success!",
                                                      text: data.message,
                                                      icon: "success",
                                                      buttonsStyling: false,
                                                      confirmButtonText: "OK",
                                                      customClass: { confirmButton: "btn btn-primary" }
                                                }).then(() => {
                                                      window.location.reload();
                                                });
                                          } else {
                                                Swal.fire({
                                                      title: "Error",
                                                      text: data.message,
                                                      icon: "error",
                                                      buttonsStyling: false,
                                                      confirmButtonText: "OK",
                                                      customClass: { confirmButton: "btn btn-danger" }
                                                });
                                          }
                                    })
                                    .catch(error => {
                                          console.error(error);
                                          Swal.fire({
                                                title: "Error",
                                                text: "Something went wrong!",
                                                icon: "error",
                                                buttonsStyling: false,
                                                confirmButtonText: "OK",
                                                customClass: { confirmButton: "btn btn-danger" }
                                          });
                                    });
                        }
                  });
            });
      }

      // Delete Transaction
      const handleDeletion = function () {
            document.addEventListener('click', function (e) {
                  const deleteBtn = e.target.closest('.delete-teacher');
                  if (!deleteBtn) return;

                  e.preventDefault();

                  const teacherId = deleteBtn.getAttribute('data-teacher-id');
                  if (!teacherId) return;

                  let url = routeDeleteTeacher.replace(':id', teacherId);

                  Swal.fire({
                        title: 'Are you sure to delete?',
                        text: "Once deleted, this teacher will be removed.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it',
                        cancelButtonText: 'Cancel',
                        buttonsStyling: false,
                        customClass: {
                              confirmButton: 'btn btn-danger',
                              cancelButton: 'btn btn-light'
                        }
                  }).then((result) => {
                        if (result.isConfirmed) {
                              fetch(url, {
                                    method: "DELETE",
                                    headers: {
                                          "Content-Type": "application/json",
                                          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                                    },
                              })
                                    .then(async response => {
                                          // Try parse JSON if possible
                                          let data = null;
                                          try {
                                                data = await response.json();
                                          } catch (err) {
                                                // non-json response
                                                throw new Error('Invalid server response');
                                          }

                                          if (!response.ok) {
                                                throw new Error(data.message || 'Failed to delete teacher');
                                          }

                                          return data;
                                    })
                                    .then(data => {
                                          if (data.success) {
                                                Swal.fire({
                                                      title: 'Deleted!',
                                                      text: data.message || 'Teacher deleted successfully.',
                                                      icon: 'success',
                                                      buttonsStyling: false,
                                                      confirmButtonText: 'OK',
                                                      customClass: { confirmButton: 'btn btn-primary' }
                                                }).then(() => {
                                                      location.reload();
                                                });
                                          } else {
                                                Swal.fire({
                                                      title: 'Failed!',
                                                      text: data.message || 'Teacher could not be deleted.',
                                                      icon: 'error',
                                                      confirmButtonText: 'OK',
                                                      buttonsStyling: false,
                                                      customClass: { confirmButton: 'btn btn-danger' }
                                                });
                                          }
                                    })
                                    .catch(error => {
                                          console.error("Fetch Error:", error);
                                          Swal.fire({
                                                title: 'Error',
                                                text: error.message || 'An error occurred. Please contact support.',
                                                icon: 'error',
                                                confirmButtonText: 'OK',
                                                buttonsStyling: false,
                                                customClass: { confirmButton: 'btn btn-danger' }
                                          });
                                    });
                        }
                  });
            });
      };

      return {
            init: function () {
                  initEditTeacher();
                  initEditFormValidation();
                  handleToggleActivation();
                  handleDeletion(); // register deletion handler
            }
      };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTEditTeacherModal.init();
});
