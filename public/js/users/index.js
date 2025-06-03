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
     var handleDeletion = function () {
          document.querySelectorAll('.delete-user').forEach(item => {
               item.addEventListener('click', function (e) {
                    e.preventDefault();

                    let userId = this.getAttribute('data-user-id');
                    console.log('User ID:', userId);

                    let url = routeDeleteUser.replace(':id', userId);  // Replace ':id' with actual user ID

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
                                                  location.reload(); // Reload to reflect changes
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
     var handleToggleActivation = function () {
          const toggleInputs = document.querySelectorAll('.toggle-active');

          toggleInputs.forEach(input => {
               input.addEventListener('change', function () {
                    const userId = this.value;
                    const isActive = this.checked ? 1 : 0;
                    const row = this.closest('tr'); // Get the parent <tr> element

                    console.log('User ID:', userId);

                    let url = routeToggleActive.replace(':id', userId);  // Replace ':id' with actual student ID


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
                              if (!response.ok) {
                                   throw new Error('Network response was not ok');
                              }
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
                              toastr.error('Error occurred while toggling farm status');
                         });
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
                                   .then(response => {
                                        if (!response.ok) throw new Error('Network response was not ok');
                                        return response.json();
                                   })
                                   .then(data => {
                                        submitButton.removeAttribute('data-kt-indicator');
                                        submitButton.disabled = false;

                                        if (data.success) {
                                             toastr.success(data.message || 'User created successfully');
                                             modal.hide();
                                             setTimeout(() => {
                                                  window.location.reload();
                                             }, 1500); // 1000ms = 1 second delay

                                        } else {
                                             throw new Error(data.message || 'User creation failed');
                                        }
                                   })
                                   .catch(error => {
                                        submitButton.removeAttribute('data-kt-indicator');
                                        submitButton.disabled = false;
                                        toastr.error(error.message || 'Failed to update invoice');
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

     let userId = null; // Declare globally

     // Init add schedule modal
     var initEditUser = () => {

          // Cancel button handler
          const cancelButton = element.querySelector('[data-edit-users-modal-action="cancel"]');
          cancelButton.addEventListener('click', e => {
               e.preventDefault();

               form.reset(); // Reset form			
               modal.hide();
          });

          // Close button handler
          const closeButton = element.querySelector('[data-edit-users-modal-action="close"]');
          closeButton.addEventListener('click', e => {
               e.preventDefault();

               form.reset(); // Reset form			
               modal.hide();
          });


          // AJAX form data load
          const editButtons = document.querySelectorAll("[data-bs-target='#kt_modal_edit_user']");
          if (editButtons.length) {
               editButtons.forEach((button) => {
                    button.addEventListener("click", function () {
                         userId = this.getAttribute("data-user-id"); // Assign value globally
                         console.log("User ID:", userId);
                         if (!userId) return;

                         // Clear form
                         if (form) form.reset();

                         fetch(`/users/${userId}`)
                              .then(response => {
                                   if (!response.ok) throw new Error('Network response was not ok');
                                   return response.json();
                              })
                              .then(data => {
                                   if (data.success && data.data) {
                                        if (!data.success || !data.data) {
                                             throw new Error("Invalid response data");
                                        }

                                        const user = data.data;

                                        // Set modal title
                                        const titleEl = document.getElementById("kt_modal_edit_user_title");
                                        if (titleEl) {
                                             titleEl.textContent = `Update user ${user.name}`;
                                        }


                                        // Populate regular input fields
                                        document.querySelector("input[name='user_name_edit']").value = user.name;
                                        document.querySelector("input[name='user_email_edit']").value = user.email;
                                        document.querySelector("input[name='user_mobile_edit']").value = user.mobile_number;

                                        // Set Select2 values and trigger change
                                        const setSelect2Value = (name, value) => {
                                             const el = $(`select[name="${name}"]`);
                                             if (el.length) {
                                                  el.val(value).trigger('change');
                                             }
                                        };

                                        // Populate form fields
                                        setSelect2Value("user_branch_edit", user.branch_id);

                                        // Role radio input fields
                                        if (user.role === 'admin') {
                                             document.getElementById("role_admin_edit").checked = true;
                                        } else if (user.role === 'manager') {
                                             document.getElementById("role_manager_edit").checked = true;
                                        } else if (user.role === 'accountant') {
                                             document.getElementById("role_accountant_edit").checked = true;
                                        }

                                        // Show modal (assumes Bootstrap modal)
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
               });
          }
     }


     // Form validation
     var initEditFormValidation = function () {
          if (!form) return;

          var validator = FormValidation.formValidation(
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

                              fetch(`/users/${userId}`, {
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
                                             toastr.success(data.message || 'User updated successfully');
                                             modal.hide();
                                             window.location.reload();
                                        } else {
                                             throw new Error(data.message || 'User Update failed');
                                        }
                                   })
                                   .catch(error => {
                                        submitButton.removeAttribute('data-kt-indicator');
                                        submitButton.disabled = false;
                                        toastr.error(error.message || 'Failed to update invoice');
                                        console.error('Error:', error);
                                   });
                         } else {
                              toastr.warning('Fill all required fields correctly');
                         }
                    });
               });
          }

     }

     return {
          // Public functions
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

     // Init add schedule modal
     var initEditPassword = () => {

          // Cancel button handler
          const cancelButton = element.querySelector('[data-kt-edit-password-modal-action="cancel"]');
          cancelButton.addEventListener('click', e => {
               e.preventDefault();

               form.reset(); // Reset form			
               modal.hide();
          });

          // Close button handler
          const closeButton = element.querySelector('[data-kt-edit-password-modal-action="close"]');
          closeButton.addEventListener('click', e => {
               e.preventDefault();

               form.reset(); // Reset form			
               modal.hide();
          });
     }

     return {
          // Public functions
          init: function () {
               initEditPassword();
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