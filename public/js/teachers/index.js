"use strict";

var KTAllTeachersList = function () {
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
                        { orderable: false, targets: 5 }, // Disable ordering on column Actions                
                  ]
            });

            // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
            datatable.on('draw', function () {

            });
      }

      // Hook export buttons
      var exportButtons = () => {
            const documentTitle = 'Teachers List';

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
            const filterSearch = document.querySelector('[data-teachers-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                  datatable.search(e.target.value).draw();
            });
      }


      // Delete Transaction
      const handleDeletion = function () {
            document.addEventListener('click', function (e) {
                  const deleteBtn = e.target.closest('.delete-teacher');
                  if (!deleteBtn) return;

                  e.preventDefault();

                  let teacherId = deleteBtn.getAttribute('data-teacher-id');
                  // console.log('Teacher ID:', teacherId);

                  let url = routeDeleteTeacher.replace(':id', teacherId);

                  Swal.fire({
                        title: 'Are you sure to delete?',
                        text: "Once deleted, this teacher will be removed.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it',
                        cancelButtonText: 'Cancel',
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
                                                      title: 'Success!',
                                                      text: 'Teacher deleted successfully.',
                                                      icon: 'success',
                                                      confirmButtonText: 'Okay',
                                                }).then(() => {
                                                      location.reload();
                                                });
                                          } else {
                                                Swal.fire('Failed!', 'Teacher could not be deleted.', 'error');
                                          }
                                    })
                                    .catch(error => {
                                          console.error("Fetch Error:", error);
                                          Swal.fire('Failed!', 'An error occurred. Please contact support.', 'error');
                                    });
                        }
                  });
            });
      };


      // Toggle activation
      const handleToggleActivation = function () {
            document.addEventListener('change', function (e) {
                  const toggle = e.target.closest('.toggle-active');
                  if (!toggle) return;

                  const teacherId = toggle.value;
                  const isActive = toggle.checked ? 1 : 0;

                  // console.log('Teacher ID:', teacherId);

                  let url = routeToggleActive.replace(':id', teacherId);

                  fetch(url, {
                        method: 'POST',
                        headers: {
                              'Content-Type': 'application/json',
                              "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        },
                        body: JSON.stringify({
                              teacher_id: teacherId,
                              is_active: isActive
                        })
                  })
                        .then(response => {
                              if (!response.ok) throw new Error('Network response was not ok');
                              return response.json();
                        })
                        .then(data => {
                              if (data.success) {
                                    toastr.success(data.message);
                              } else {
                                    toastr.error(data.message);
                              }
                        })
                        .catch(error => {
                              console.error('Error:', error);
                              toastr.error('Error occurred while toggling user status');
                        });
            });
      };

      return {
            // Public functions  
            init: function () {
                  table = document.getElementById('kt_teachers_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  exportButtons();
                  handleSearch();
                  handleDeletion();
                  handleToggleActivation();
            }
      }
}();


var KTAddTeacher = function () {
      // Shared variables
      const element = document.getElementById('kt_modal_add_teacher');

      // Early return if element doesn't exist
      if (!element) {
            console.error('Modal element not found');
            return {
                  init: function () { }
            };
      }

      const form = element.querySelector('#kt_modal_add_teacher_form');
      const modal = bootstrap.Modal.getOrCreateInstance(element);


      var initCloseModal = () => {

            // Reset Select2 inputs

            // Cancel button handler
            const cancelButton = element.querySelector('[data-kt-add-teacher-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener('click', e => {
                        e.preventDefault();
                        if (form) form.reset();
                        modal.hide();
                  });
            }

            // Close button handler
            const closeButton = element.querySelector('[data-kt-add-teacher-modal-action="close"]');
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
                              'teacher_name': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Teacher name is required'
                                          }
                                    }
                              },
                              'teacher_email': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Email is required'
                                          },
                                          emailAddress: {
                                                message: 'Enter a valid email address',
                                          },
                                    }
                              },
                              'teacher_phone': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Mobile no. is required'
                                          },
                                          regexp: {
                                                regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                                message: 'Please enter a valid Bangladeshi mobile number'
                                          },
                                          stringLength: {
                                                min: 11,
                                                max: 11,
                                                message: 'The mobile number must be exactly 11 digits'
                                          }
                                    }
                              },
                              'teacher_salary': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Salary is required'
                                          },
                                          greaterThan: {
                                                min: 100,
                                                message: 'Salary must be at least 100'
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

            const submitButton = element.querySelector('[data-kt-add-teacher-modal-action="submit"]');

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

                                    fetch(storeTeacherRoute, {
                                          method: "POST",
                                          body: formData,
                                          headers: {
                                                'Accept': 'application/json', // Explicitly ask for JSON
                                                'X-Requested-With': 'XMLHttpRequest'
                                          }
                                    })
                                          .then(async response => {
                                                const data = await response.json();

                                                if (!response.ok) {
                                                      const message = data.message || 'Something went wrong';
                                                      const errors = data.errors
                                                            ? [...new Set(Object.values(data.errors).flat())].join('<br>')
                                                            : '';
                                                      throw {
                                                            message: data.message || 'User creation failed',
                                                            response: new Response(JSON.stringify(data), {
                                                                  status: 422,
                                                                  headers: { 'Content-type': 'application/json' }
                                                            })
                                                      };

                                                }

                                                return data;
                                          })

                                          .then(data => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;

                                                if (data.success) {
                                                      toastr.success(data.message || 'Teacher created successfully');
                                                      modal.hide();
                                                      setTimeout(() => {
                                                            window.location.reload();
                                                      }, 1500);
                                                } else {
                                                      toastr.error(data.message || 'Teacher creation failed');
                                                }
                                          })
                                          .catch(error => {
                                                submitButton.removeAttribute('data-kt-indicator');
                                                submitButton.disabled = false;
                                                toastr.error(error.message || 'Failed to create teacher');
                                                console.error('Error:', error);
                                          });

                              } else {
                                    toastr.warning('Please fill all fields correctly');
                              }
                        });
                  });
            }
      }

      return {
            init: function () {
                  initCloseModal();
                  initValidation();
            }
      };
}();


var KTEditTeacher = function () {
      // Shared variables
      const element = document.getElementById('kt_modal_edit_teacher');
      const form = element.querySelector('#kt_modal_edit_teacher_form');
      const modal = new bootstrap.Modal(element);

      let teacherId = null;
      let validator = null; // Declare validator globally

      // Init Edit User Modal
      const initEditTeacher = () => {
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

                                    document.querySelector("input[name='teacher_name_edit']").value = teacher.name;
                                    document.querySelector("input[name='teacher_email_edit']").value = teacher.email;
                                    document.querySelector("input[name='teacher_phone_edit']").value = teacher.phone;
                                    document.querySelector("input[name='teacher_salary_edit']").value = teacher.base_salary;

                                    modal.show();

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
                              form.reset();
                              modal.hide();
                        });
                  }
            });
      };


      // Form validation
      var initEditFormValidation = function () {
            if (!form) return;

            validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'teacher_name_edit': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Teacher name is required'
                                          }
                                    }
                              },
                              'teacher_email_edit': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Email is required'
                                          },
                                          emailAddress: {
                                                message: 'Enter a valid email address',
                                          },
                                    }
                              },
                              'teacher_phone_edit': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Mobile no. is required'
                                          },
                                          regexp: {
                                                regexp: /^01[3-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                                message: 'Please enter a valid Bangladeshi mobile number'
                                          },
                                          stringLength: {
                                                min: 11,
                                                max: 11,
                                                message: 'The mobile number must be exactly 11 digits'
                                          }
                                    }
                              },
                              'teacher_salary_edit': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Salary is required'
                                          },
                                          greaterThan: {
                                                min: 100,
                                                message: 'Salary must be at least 100'
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

            const submitButton = element.querySelector('[data-edit-teachers-modal-action="submit"]');

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

                                    console.log(teacherId);
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
                                                      toastr.success(data.message || 'Teacher updated successfully');
                                                      modal.hide();
                                                      setTimeout(() => {
                                                            window.location.reload();
                                                      }, 1500); // 1000ms = 1 second delay
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

      return {
            init: function () {
                  initEditTeacher();
                  initEditFormValidation();
            }
      };
}();


var KTEditPassword = function () {
      // Shared variables
      const element = document.getElementById('kt_modal_edit_password');
      const form = element.querySelector('#kt_modal_edit_password_form');
      const modal = new bootstrap.Modal(element);

      let teacherId = null;
      let validator = null; // Declare validator globally

      // Init add schedule modal
      var initEditPassword = () => {
            const passwordInput = document.getElementById('teacherPasswordNew');
            const strengthText = document.getElementById('password-strength-text');
            const strengthBar = document.getElementById('password-strength-bar');

            // Cancel button handler
            const cancelButton = element.querySelector('[data-kt-edit-password-modal-action="cancel"]');
            cancelButton.addEventListener('click', e => {
                  e.preventDefault();

                  form.reset(); // Reset form			
                  modal.hide();

                  // Reset strength meter
                  if (strengthText) strengthText.textContent = '';
                  if (strengthBar) {
                        strengthBar.className = 'progress-bar';
                        strengthBar.style.width = '0%';
                  }
            });

            // Close button handler
            const closeButton = element.querySelector('[data-kt-edit-password-modal-action="close"]');
            closeButton.addEventListener('click', e => {
                  e.preventDefault();

                  form.reset(); // Reset form			
                  modal.hide();

                  // Reset strength meter
                  if (strengthText) strengthText.textContent = '';
                  if (strengthBar) {
                        strengthBar.className = 'progress-bar';
                        strengthBar.style.width = '0%';
                  }
            });


            // AJAX loading password modal data
            document.addEventListener('click', function (e) {
                  // Handle password toggle
                  const toggleBtn = e.target.closest('.toggle-password');
                  if (toggleBtn) {
                        const inputId = toggleBtn.getAttribute('data-target');
                        const input = document.getElementById(inputId);
                        const icon = toggleBtn.querySelector('i');

                        if (input) {
                              const isPassword = input.type === 'password';
                              input.type = isPassword ? 'text' : 'password';

                              if (icon) {
                                    icon.classList.toggle('ki-eye');
                                    icon.classList.toggle('ki-eye-slash');
                              }
                        }
                        return; // Prevent falling through to next case
                  }

                  // Handle edit password modal button
                  const changePasswordBtn = e.target.closest('.change-password-btn');
                  if (changePasswordBtn) {
                        teacherId = changePasswordBtn.getAttribute('data-teacher-id');
                        console.log('User ID:', teacherId);

                        const teacherName = changePasswordBtn.getAttribute('data-teacher-name');

                        const teacherIdInput = document.getElementById('user_id_input');
                        const modalTitle = document.getElementById('kt_modal_edit_password_title');

                        if (teacherIdInput) teacherIdInput.value = teacherId;
                        if (modalTitle) modalTitle.textContent = `Password Reset of ${teacherName}`;
                  }
            });

            // Live strength meter
            if (passwordInput) {
                  passwordInput.addEventListener('input', function () {
                        const value = passwordInput.value;
                        let score = 0;

                        if (value.length >= 8) score++;
                        if (/[A-Z]/.test(value)) score++;
                        if (/[a-z]/.test(value)) score++;
                        if (/\d/.test(value)) score++;
                        if (/[^A-Za-z0-9]/.test(value)) score++;

                        let strength = '';
                        let barColor = '';
                        let width = score * 20;

                        switch (score) {
                              case 0:
                              case 1:
                                    strength = 'Very Weak';
                                    barColor = 'bg-danger';
                                    break;
                              case 2:
                                    strength = 'Weak';
                                    barColor = 'bg-warning';
                                    break;
                              case 3:
                                    strength = 'Moderate';
                                    barColor = 'bg-info';
                                    break;
                              case 4:
                                    strength = 'Strong';
                                    barColor = 'bg-primary';
                                    break;
                              case 5:
                                    strength = 'Very Strong';
                                    barColor = 'bg-success';
                                    break;
                        }

                        strengthText.textContent = strength;
                        strengthBar.className = `progress-bar ${barColor}`;
                        strengthBar.style.width = `${width}%`;
                  });
            }
      }


      // Form validation
      var initFormValidation = function () {
            if (!form) return;

            validator = FormValidation.formValidation(
                  form,
                  {
                        fields: {
                              'new_password': {
                                    validators: {
                                          notEmpty: {
                                                message: 'Password is required'
                                          },
                                          stringLength: {
                                                min: 8,
                                                message: '* Must be at least 8 characters long'
                                          },
                                          regexp: {
                                                regexp: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/,
                                                message: '* Must contain uppercase, lowercase, number, and special character'
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

            const submitButton = element.querySelector('[data-kt-edit-password-modal-action="submit"]');

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

                                    console.log('Updating password for teacher ID:', teacherId);
                                    fetch(`/teachers/${teacherId}/password`, {
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
                                                      toastr.success(data.message || 'Password updated successfully');
                                                      modal.hide();
                                                      setTimeout(() => {
                                                            window.location.reload();
                                                      }, 1500); // 1000ms = 1 second delay
                                                } else {
                                                      throw new Error(data.message || 'Password Update failed');
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

      return {
            // Public functions
            init: function () {
                  initEditPassword();
                  initFormValidation();
            }
      };
}();


// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTAllTeachersList.init();
      KTAddTeacher.init();
      KTEditTeacher.init();
      KTEditPassword.init();
});