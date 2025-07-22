"use strict";

var KTUsersList = function () {
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
               'columnDefs': [{ orderable: false, targets: [5, 6] }]
          });

          // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
          datatable.on('draw', function () {

          });
     }

     // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
     var handleSearch = function () {
          const filterSearch = document.querySelector('[data-kt-user-table-filter="search"]');
          filterSearch.addEventListener('keyup', function (e) {
               datatable.search(e.target.value).draw();
          });
     }

     // Delete users
     const handleDeletion = function () {
          document.addEventListener('click', function (e) {
               const deleteBtn = e.target.closest('.delete-user');
               if (!deleteBtn) return;

               e.preventDefault();

               let userId = deleteBtn.getAttribute('data-user-id');
               console.log('User ID:', userId);

               let url = routeDeleteUser.replace(':id', userId);

               Swal.fire({
                    title: 'Are you sure you want to delete?',
                    text: "Once deleted, this user's information will be removed.",
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
                                             text: 'The user has been deleted successfully.',
                                             icon: 'success',
                                             confirmButtonText: 'Okay',
                                        }).then(() => {
                                             location.reload();
                                        });
                                   } else {
                                        Swal.fire('Failed!', 'The user could not be deleted.', 'error');
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


     // Filter Datatable
     var handleFilter = function () {
          // Select filter options
          const filterForm = document.querySelector('[data-users-table-filter="form"]');
          const filterButton = filterForm.querySelector('[data-users-table-filter="filter"]');
          const resetButton = filterForm.querySelector('[data-users-table-filter="reset"]');
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

     // Toggle activation
     const handleToggleActivation = function () {
          document.addEventListener('change', function (e) {
               const toggle = e.target.closest('.toggle-active');
               if (!toggle) return;

               const userId = toggle.value;
               const isActive = toggle.checked ? 1 : 0;

               console.log('User ID:', userId);

               let url = routeToggleActive.replace(':id', userId);

               fetch(url, {
                    method: 'POST',
                    headers: {
                         'Content-Type': 'application/json',
                         "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    },
                    body: JSON.stringify({
                         user_id: userId,
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
               table = document.getElementById('kt_users_table');

               if (!table) {
                    return;
               }

               initDatatable();
               handleSearch();
               handleFilter();
               handleDeletion();
               handleToggleActivation();
          }
     }
}();

var KTUsersAddUser = function () {
     // Shared variables
     const element = document.getElementById('kt_modal_add_user');
     // Early return if element doesn't exist
     if (!element) {
          console.error('Modal element not found');
          return {
               init: function () { }
          };
     }

     const form = element.querySelector('#kt_modal_add_user_form');
     const modal = new bootstrap.Modal(element);

     // Init add schedule modal
     var initAddUser = () => {

          // Cancel button handler
          const cancelButton = element.querySelector('[data-add-users-modal-action="cancel"]');
          cancelButton.addEventListener('click', e => {
               e.preventDefault();

               form.reset(); // Reset form			
               modal.hide();
          });

          // Close button handler
          const closeButton = element.querySelector('[data-add-users-modal-action="close"]');
          closeButton.addEventListener('click', e => {
               e.preventDefault();

               form.reset(); // Reset form			
               modal.hide();
          });
     }

     // Form validation
     var initValidation = function () {
          if (!form) return;

          var validator = FormValidation.formValidation(
               form,
               {
                    fields: {
                         'user_name': {
                              validators: {
                                   notEmpty: {
                                        message: 'Username is required'
                                   }
                              }
                         },
                         'user_email': {
                              validators: {
                                   notEmpty: {
                                        message: 'Email is required'
                                   },
                                   emailAddress: {
                                        message: 'Enter a valid email address',
                                   },
                              }
                         },
                         'user_mobile': {
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
                         'user_branch': {
                              validators: {
                                   notEmpty: {
                                        message: 'Branch is required'
                                   }
                              }
                         },
                         'user_role': {
                              validators: {
                                   notEmpty: {
                                        message: 'Role is required'
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

          const submitButton = element.querySelector('[data-add-users-modal-action="submit"]');

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

                              fetch(storeUserRoute, {
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
                                             toastr.success(data.message || 'User created successfully');
                                             modal.hide();
                                             setTimeout(() => {
                                                  window.location.reload();
                                             }, 1500);
                                        } else {
                                             toastr.error(data.message || 'User creation failed');
                                        }
                                   })
                                   .catch(error => {
                                        submitButton.removeAttribute('data-kt-indicator');
                                        submitButton.disabled = false;
                                        toastr.error(error.message || 'Failed to create user');
                                        console.error('Error:', error);
                                   });

                         } else {
                              toastr.warning('Please fill all fields correctly');
                         }
                    });
               });
          }


          // Role-based layout and validation logic
          const roleInputs = document.querySelectorAll('input[name="user_role"]');
          const branchDiv = document.getElementById('branch_input_div');
          const userNameDiv = document.getElementById('user_name_input_div');

          roleInputs.forEach(roleInput => {
               roleInput.addEventListener('change', function () {
                    if (this.id === 'role_admin_input') {
                         // Admin selected
                         if (branchDiv) branchDiv.style.display = 'none';
                         if (userNameDiv) {
                              userNameDiv.classList.remove('col-lg-6');
                              userNameDiv.classList.add('col-lg-12');
                         }
                         validator.disableValidator('user_branch', 'notEmpty');
                    } else {
                         // Manager or Accountant selected
                         if (branchDiv) branchDiv.style.display = '';
                         if (userNameDiv) {
                              userNameDiv.classList.remove('col-lg-12');
                              userNameDiv.classList.add('col-lg-6');
                         }
                         validator.enableValidator('user_branch', 'notEmpty');
                    }
               });
          });
     }

     return {
          // Public functions
          init: function () {
               initAddUser();
               initValidation();
          }
     };
}();

var KTUsersEditUser = function () {
     // Shared variables
     const element = document.getElementById('kt_modal_edit_user');
     const form = element.querySelector('#kt_modal_edit_user_form');
     const modal = new bootstrap.Modal(element);

     let userId = null;
     let validator = null; // Declare validator globally

     // Function to toggle branch field visibility and validation
     const toggleBranchValidation = (role) => {
          const branchDiv = document.getElementById('branch_edit_div');
          const userNameDiv = document.getElementById('user_name_edit_div');

          if (role === 'admin') {
               if (branchDiv) branchDiv.style.display = 'none';
               if (userNameDiv) {
                    userNameDiv.classList.remove('col-lg-6');
                    userNameDiv.classList.add('col-lg-12');
               }
               if (validator) {
                    validator.disableValidator('user_branch_edit', 'notEmpty');
               }
          } else {
               if (branchDiv) branchDiv.style.display = '';
               if (userNameDiv) {
                    userNameDiv.classList.remove('col-lg-12');
                    userNameDiv.classList.add('col-lg-6');
               }
               if (validator) {
                    validator.enableValidator('user_branch_edit', 'notEmpty');
               }
          }
     };

     // Init Edit User Modal
     const initEditUser = () => {
          document.addEventListener('click', function (e) {
               const editBtn = e.target.closest("[data-bs-target='#kt_modal_edit_user']");
               if (!editBtn) return;

               e.preventDefault();

               userId = editBtn.getAttribute("data-user-id");
               console.log('User ID:', userId);

               if (!userId) return;

               if (form) form.reset();

               // AJAX data fetch
               fetch(`/settings/users/${userId}`)
                    .then(response => {
                         if (!response.ok) throw new Error('Network response was not ok');
                         return response.json();
                    })
                    .then(data => {
                         if (data.success && data.data) {
                              const user = data.data;

                              const titleEl = document.getElementById("kt_modal_edit_user_title");
                              if (titleEl) {
                                   titleEl.textContent = `Update user ${user.name}`;
                              }

                              document.querySelector("input[name='user_name_edit']").value = user.name;
                              document.querySelector("input[name='user_email_edit']").value = user.email;
                              document.querySelector("input[name='user_mobile_edit']").value = user.mobile_number;

                              const setSelect2Value = (name, value) => {
                                   const el = $(`select[name="${name}"]`);
                                   if (el.length) {
                                        el.val(value).trigger('change');
                                   }
                              };
                              setSelect2Value("user_branch_edit", user.branch_id);

                              const roleRadio = document.querySelector(`input[name='user_role_edit'][value="${user.role}"]`);
                              if (roleRadio) roleRadio.checked = true;

                              toggleBranchValidation(user.role);

                              modal.show();

                              const roleRadios = form.querySelectorAll('input[name="user_role_edit"]');
                              roleRadios.forEach((radio) => {
                                   radio.addEventListener('change', function () {
                                        toggleBranchValidation(this.value);
                                   });
                              });

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
          const cancelButton = element.querySelector('[data-edit-users-modal-action="cancel"]');
          const closeButton = element.querySelector('[data-edit-users-modal-action="close"]');
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
                         'user_name_edit': {
                              validators: {
                                   notEmpty: {
                                        message: 'Username is required'
                                   }
                              }
                         },
                         'user_mobile_edit': {
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
                         'user_branch_edit': {
                              validators: {
                                   notEmpty: {
                                        message: 'Branch is required'
                                   }
                              }
                         },
                         'user_role_edit': {
                              validators: {
                                   notEmpty: {
                                        message: 'Role is required'
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

          const submitButton = element.querySelector('[data-edit-users-modal-action="submit"]');

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

                              console.log(userId);
                              fetch(`/settings/users/${userId}`, {
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
                                             toastr.success(data.message || 'User updated successfully');
                                             modal.hide();
                                             setTimeout(() => {
                                                  window.location.reload();
                                             }, 1500); // 1000ms = 1 second delay
                                        } else {
                                             throw new Error(data.message || 'User Update failed');
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
               initEditUser();
               initEditFormValidation();
          }
     };
}();

var KTUsersResetPassword = function () {
     // Shared variables
     const element = document.getElementById('kt_modal_edit_password');
     const form = element.querySelector('#kt_modal_edit_password_form');
     const modal = new bootstrap.Modal(element);

     let userId = null;
     let validator = null; // Declare validator globally

     // Init add schedule modal
     var initEditPassword = () => {
          const passwordInput = document.getElementById('userPasswordNew');
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
                    userId = changePasswordBtn.getAttribute('data-user-id');
                    console.log('User ID:', userId);

                    const userName = changePasswordBtn.getAttribute('data-user-name');

                    const userIdInput = document.getElementById('user_id_input');
                    const modalTitle = document.getElementById('kt_modal_edit_password_title');

                    if (userIdInput) userIdInput.value = userId;
                    if (modalTitle) modalTitle.textContent = `Password Reset of ${userName}`;
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

                              console.log('Updating password for user ID:', userId);
                              fetch(`/settings/users/${userId}/password`, {
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
     KTUsersList.init();
     KTUsersAddUser.init();
     KTUsersEditUser.init();
     KTUsersResetPassword.init();
});