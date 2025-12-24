"use strict";

// ========================================
// Profile Update Form
// ========================================
var KEditUserForm = function () {
      var form;
      var submitButton;
      var indicatorLabel;
      var indicatorProgress;
      var originalValues = {};

      var storeOriginalValues = function () {
            form.querySelectorAll('input[name]').forEach(function (input) {
                  originalValues[input.name] = input.value.trim();
            });
      };

      var hasChanges = function () {
            for (var name in originalValues) {
                  var input = form.querySelector(`[name="${name}"]`);
                  if (input && input.value.trim() !== originalValues[name]) {
                        return true;
                  }
            }
            return false;
      };

      // Client-side validation
      var validateForm = function () {
            var isValid = true;

            // Clear previous errors
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

            // Name
            var nameInput = form.querySelector('[name="name"]');
            var name = nameInput.value.trim();
            if (!name) {
                  showError('name', 'Your name is required.');
                  isValid = false;
            }

            // Email
            var emailInput = form.querySelector('[name="email"]');
            var email = emailInput.value.trim();
            if (!email) {
                  showError('email', 'Email is required.');
                  isValid = false;
            } else if (!/^\S+@\S+\.\S+$/.test(email)) {
                  showError('email', 'Please enter a valid email address.');
                  isValid = false;
            }

            // Mobile - Must be 11 digits, Bangladeshi format (01xxxxxxxxx)
            var mobileInput = form.querySelector('[name="mobile_number"]');
            var mobile = mobileInput.value.trim();
            if (!mobile) {
                  showError('mobile_number', 'Mobile number is required.');
                  isValid = false;
            } else if (!/^01[3-9]\d{8}$/.test(mobile)) {
                  showError('mobile_number', 'Please enter a valid 11-digit Bangladeshi mobile number (e.g., 017XXXXXXX).');
                  isValid = false;
            }

            return isValid;
      };

      var showError = function (fieldName, message) {
            var input = form.querySelector(`[name="${fieldName}"]`);
            if (input) {
                  input.classList.add('is-invalid');
                  var feedback = input.parentElement.querySelector('.invalid-feedback');
                  if (feedback) feedback.textContent = message;
            }
            toastr.error(message);
      };

      var handleSubmit = function () {
            submitButton.addEventListener('click', function (e) {
                  e.preventDefault();

                  if (!hasChanges()) {
                        toastr.info('কোনো পরিবর্তন করা হয়নি।');
                        return;
                  }

                  if (!validateForm()) {
                        return;
                  }

                  // Show loading state
                  indicatorLabel.style.display = 'none';
                  indicatorProgress.style.display = 'inline-block';
                  submitButton.disabled = true;

                  var formData = new FormData(form);

                  // Crucial: Spoof PUT method via POST for Laravel to parse FormData correctly
                  formData.append('_method', 'PUT');

                  var updateUrl = form.dataset.updateUrl;

                  // Debug: Check what's being sent (remove in production)
                  // console.log('Sending FormData:');
                  // for (let [key, value] of formData.entries()) {
                  //     console.log(key + ': ' + value);
                  // }

                  fetch(updateUrl, {
                        method: 'POST', // Use POST + _method=PUT
                        headers: {
                              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                              'Accept': 'application/json'
                              // Do NOT set Content-Type — browser sets it with boundary
                        },
                        body: formData
                  })
                        .then(response => {
                              if (!response.ok) {
                                    return response.json().then(err => { throw err; });
                              }
                              return response.json();
                        })
                        .then(data => {
                              if (data.success) {
                                    toastr.success(data.message || 'প্রোফাইল সফলভাবে আপডেট করা হয়েছে।');
                                    storeOriginalValues(); // Update originals after save
                              } else {
                                    if (data.errors) {
                                          Object.keys(data.errors).forEach(field => {
                                                showError(field, data.errors[field][0]);
                                          });
                                    } else {
                                          toastr.error(data.message || 'আপডেট করতে সমস্যা হয়েছে।');
                                    }
                              }
                        })
                        .catch(error => {
                              console.error('Error:', error);
                              if (error.errors) {
                                    Object.keys(error.errors).forEach(field => {
                                          showError(field, error.errors[field][0]);
                                    });
                              } else {
                                    toastr.error(error.message || 'সার্ভারের সাথে যোগাযোগ করা যায়নি।');
                              }
                        })
                        .finally(() => {
                              indicatorLabel.style.display = '';
                              indicatorProgress.style.display = 'none';
                              submitButton.disabled = false;
                        });
            });
      };

      return {
            init: function () {
                  form = document.getElementById('kt_create_user_form');
                  if (!form) {
                        console.warn('Form #kt_create_user_form not found!');
                        return;
                  }

                  submitButton = form.querySelector('button[type="submit"]');
                  indicatorLabel = submitButton.querySelector('.indicator-label');
                  indicatorProgress = submitButton.querySelector('.indicator-progress');

                  indicatorProgress.style.display = 'none';
                  storeOriginalValues();
                  handleSubmit();
            }
      };
}();


// ========================================
// Password Reset Inline
// ========================================
var KTUserInlinePasswordReset = function () {

      // Private variables
      var newPassword;
      var confirmPassword;
      var strengthText;
      var strengthBar;
      var updateBtn;
      var indicatorLabel;
      var indicatorProgress;

      // Password strength calculation (returns score 0-6)
      var calculateStrength = function (password) {
            if (!password) {
                  return { score: 0, text: '', color: '', width: '0%' };
            }

            var score = 0;

            if (password.length >= 8) score++;
            if (password.length >= 12) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^a-zA-Z0-9]/.test(password)) score++;

            var feedback = '';
            var color = '';
            var width = '0%';

            if (score <= 2) {
                  feedback = 'Weak';
                  color = 'bg-danger';
                  width = '25%';
            } else if (score <= 4) {
                  feedback = 'Fair';
                  color = 'bg-warning';
                  width = '50%';
            } else if (score === 5) {
                  feedback = 'Good';
                  color = 'bg-info';
                  width = '75%';
            } else if (score === 6) {
                  feedback = 'Very Strong';
                  color = 'bg-success';
                  width = '100%';
            }

            return { score: score, text: feedback, color: color, width: width };
      };

      // Update password strength meter
      var handleNewPasswordInput = function () {
            var strength = calculateStrength(newPassword.value);

            strengthText.textContent = strength.text;

            if (strength.text) {
                  strengthText.className = 'fw-bold fs-5 mb-2 text-' + strength.color.split('-')[1];
            } else {
                  strengthText.className = 'fw-bold fs-5 mb-2';
            }

            strengthBar.className = 'progress-bar ' + strength.color;
            strengthBar.style.width = strength.width;

            // Enable/disable submit button based on strong password
            if (strength.score === 6) {
                  updateBtn.disabled = false;
            } else {
                  updateBtn.disabled = true;
            }
      };

      // Confirm password match check
      var handleConfirmPasswordInput = function () {
            if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
                  confirmPassword.classList.add('is-invalid');
            } else {
                  confirmPassword.classList.remove('is-invalid');
            }
      };

      // Password show/hide toggle
      var handlePasswordToggle = function () {
            document.querySelectorAll('.toggle-password').forEach(function (toggle) {
                  toggle.addEventListener('click', function () {
                        var targetId = this.getAttribute('data-target');
                        var input = document.getElementById(targetId);
                        var icon = this.querySelector('i');

                        if (!input || !icon) return;

                        if (input.type === 'password') {
                              input.type = 'text';
                              icon.classList.replace('ki-eye', 'ki-eye-slash');
                        } else {
                              input.type = 'password';
                              icon.classList.replace('ki-eye-slash', 'ki-eye');
                        }
                  });
            });
      };

      // Handle form submission
      var handleSubmit = function () {
            // Extra safety: re-check strength before submit
            var strength = calculateStrength(newPassword.value);
            if (strength.score < 6) {
                  toastr.warning('Password is not strong enough.');
                  return;
            }

            // Reset invalid states
            newPassword.classList.remove('is-invalid');
            confirmPassword.classList.remove('is-invalid');

            var newPass = newPassword.value.trim();
            var confirmPass = confirmPassword.value;

            if (newPass !== confirmPass) {
                  confirmPassword.classList.add('is-invalid');
                  toastr.error('New password and confirmation do not match.');
                  return;
            }

            // Show loading
            indicatorLabel.style.display = 'none';
            indicatorProgress.style.display = 'inline-block';
            updateBtn.disabled = true;

            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            var url = updateBtn.dataset.url;

            fetch(url, {
                  method: 'PUT',
                  headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                  },
                  body: JSON.stringify({
                        new_password: newPass,
                        new_password_confirmation: confirmPass
                  })
            })
                  .then(function (response) {
                        return response.json();
                  })
                  .then(function (data) {
                        if (data.success) {
                              toastr.success(data.message || 'Password updated successfully.');

                              newPassword.value = '';
                              confirmPassword.value = '';
                              strengthText.textContent = '';
                              strengthBar.style.width = '0%';
                              strengthBar.className = 'progress-bar';

                              // Button will auto-disable on empty input
                              updateBtn.disabled = true;
                        } else {
                              toastr.error(data.message || 'Password update failed.');
                        }
                  })
                  .catch(function () {
                        toastr.error('Something went wrong.');
                  })
                  .finally(function () {
                        indicatorLabel.style.display = '';
                        indicatorProgress.style.display = 'none';
                        // Button remains disabled until strong password is entered again
                  });
      };

      /* ---------------------------------------
       * Init
       * --------------------------------------- */
      return {
            init: function () {
                  newPassword = document.getElementById('userPasswordNew');
                  confirmPassword = document.getElementById('userConfirmPassword');
                  strengthText = document.getElementById('password-strength-text');
                  strengthBar = document.getElementById('password-strength-bar');
                  updateBtn = document.getElementById('password_update_btn');
                  indicatorLabel = updateBtn.querySelector('.indicator-label');
                  indicatorProgress = updateBtn.querySelector('.indicator-progress');

                  // Initially disable the button
                  updateBtn.disabled = true;

                  // Event listeners
                  newPassword.addEventListener('input', handleNewPasswordInput);
                  confirmPassword.addEventListener('input', handleConfirmPasswordInput);
                  updateBtn.addEventListener('click', handleSubmit);

                  // Initialize toggle
                  handlePasswordToggle();
            }
      };
}();

// ========================================
// Login Activity Table
// ========================================
var KTLoginActivity = function () {
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
                  'columnDefs': [{
                        // orderable: false, targets: [9, 10]
                  }]
            });

            // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
            datatable.on('draw', function () {

            });
      }

      // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
      var handleSearch = function () {
            const filterSearch = document.querySelector('[data-login-activities-table-filter="search"]');
            filterSearch.addEventListener('keyup', function (e) {
                  datatable.search(e.target.value).draw();
            });
      }

      return {
            // Public functions  
            init: function () {
                  table = document.getElementById('kt_login_activities_table');

                  if (!table) {
                        return;
                  }

                  initDatatable();
                  handleSearch();
            }
      }
}();

// Metronic standard init
KTUtil.onDOMContentLoaded(function () {
      KEditUserForm.init();
      KTUserInlinePasswordReset.init();
      KTLoginActivity.init();
});