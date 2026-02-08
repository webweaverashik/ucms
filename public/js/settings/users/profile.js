"use strict";

// ========================================
// Wallet Logs DataTable
// ========================================
var KTWalletLogsTable = (function () {
    var table;
    var datatable;
    var filterMenuEl;

    // Filter values
    var filters = {
        type: '',
        start_date: '',
        end_date: ''
    };

    // Flatpickr instances
    var dateFromPicker;
    var dateToPicker;

    var initDatatable = function () {
        datatable = $(table).DataTable({
            info: true,
            order: [[1, 'desc']],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            processing: true,
            serverSide: true,
            ajax: {
                url: ProfileConfig.walletLogsUrl,
                type: 'GET',
                data: function (d) {
                    d.type = filters.type;
                    d.start_date = filters.start_date;
                    d.end_date = filters.end_date;
                }
            },
            columns: [
                { data: 'counter', name: 'counter', orderable: false, searchable: false },
                { data: 'date', name: 'created_at' },
                { data: 'type', name: 'type' },
                { data: 'description', name: 'description' },
                { data: 'amount', name: 'amount', className: 'text-end' },
                { data: 'old_balance', name: 'old_balance', className: 'text-end' },
                { data: 'new_balance', name: 'new_balance', className: 'text-end' },
                { data: 'created_by', name: 'created_by' }
            ],
            columnDefs: [
                { orderable: false, targets: [0, 2, 3, 7] }
            ],
            language: {
                processing: '<span class="spinner-border spinner-border-sm align-middle me-2"></span> Loading...',
                emptyTable: '<div class="d-flex flex-column align-items-center py-10"><i class="ki-outline ki-wallet fs-3x text-gray-400 mb-3"></i><span class="text-gray-500 fs-5">No wallet logs found</span></div>',
                zeroRecords: '<div class="d-flex flex-column align-items-center py-10"><i class="ki-outline ki-wallet fs-3x text-gray-400 mb-3"></i><span class="text-gray-500 fs-5">No matching records found</span></div>'
            }
        });
    };

    var initFlatpickr = function () {
        var dateFromEl = document.getElementById('wallet_date_from');
        var dateToEl = document.getElementById('wallet_date_to');

        if (dateFromEl) {
            dateFromPicker = flatpickr(dateFromEl, {
                dateFormat: 'd-m-Y',
                allowInput: false,
                onChange: function (selectedDates, dateStr) {
                    filters.start_date = dateStr;
                    // Set minDate for dateTo picker
                    if (dateToPicker && selectedDates.length > 0) {
                        dateToPicker.set('minDate', selectedDates[0]);
                    }
                }
            });
        }

        if (dateToEl) {
            dateToPicker = flatpickr(dateToEl, {
                dateFormat: 'd-m-Y',
                allowInput: false,
                onChange: function (selectedDates, dateStr) {
                    filters.end_date = dateStr;
                    // Set maxDate for dateFrom picker
                    if (dateFromPicker && selectedDates.length > 0) {
                        dateFromPicker.set('maxDate', selectedDates[0]);
                    }
                }
            });
        }
    };

    var handleSearch = function () {
        var searchInput = document.querySelector('[data-kt-wallet-table-filter="search"]');
        var timeout;

        if (searchInput) {
            searchInput.addEventListener('input', function (e) {
                clearTimeout(timeout);
                timeout = setTimeout(function () {
                    datatable.search(e.target.value).draw();
                }, 300);
            });
        }
    };

    var handleFilter = function () {
        var typeSelect = $('[data-kt-wallet-table-filter="type"]');
        var filterButton = document.querySelector('[data-kt-wallet-table-filter="filter"]');
        var resetButton = document.querySelector('[data-kt-wallet-table-filter="reset"]');

        // Initialize Select2
        if (typeSelect.length) {
            typeSelect.select2({
                dropdownParent: filterMenuEl,
                minimumResultsForSearch: Infinity
            });
        }

        // Apply filter
        if (filterButton) {
            filterButton.addEventListener('click', function () {
                filters.type = typeSelect.val() || '';
                datatable.ajax.reload();

                // Close the filter menu
                if (filterMenuEl) {
                    var menuInstance = KTMenu.getInstance(filterMenuEl);
                    if (menuInstance) {
                        menuInstance.hide(filterMenuEl);
                    }
                }
            });
        }

        // Reset filter
        if (resetButton) {
            resetButton.addEventListener('click', function () {
                // Reset type
                typeSelect.val(null).trigger('change');
                filters.type = '';

                // Reset dates
                if (dateFromPicker) {
                    dateFromPicker.clear();
                    dateFromPicker.set('maxDate', null);
                }
                if (dateToPicker) {
                    dateToPicker.clear();
                    dateToPicker.set('minDate', null);
                }
                filters.start_date = '';
                filters.end_date = '';

                datatable.ajax.reload();

                // Close the filter menu
                if (filterMenuEl) {
                    var menuInstance = KTMenu.getInstance(filterMenuEl);
                    if (menuInstance) {
                        menuInstance.hide(filterMenuEl);
                    }
                }
            });
        }
    };

    return {
        init: function () {
            table = document.getElementById('kt_wallet_logs_table');
            filterMenuEl = document.getElementById('kt_wallet_filter_menu');

            if (!table) return;

            initDatatable();
            initFlatpickr();
            handleSearch();
            handleFilter();
        },
        reload: function () {
            if (datatable) {
                datatable.ajax.reload();
            }
        }
    };
})();

// ========================================
// Login Activity DataTable
// ========================================
var KTLoginActivityTable = (function () {
    var table;
    var datatable;
    var filterMenuEl;

    // Filter values
    var filters = {
        device: '',
        start_date: '',
        end_date: ''
    };

    // Flatpickr instances
    var dateFromPicker;
    var dateToPicker;

    var initDatatable = function () {
        datatable = $(table).DataTable({
            info: true,
            order: [[4, 'desc']],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            processing: true,
            serverSide: true,
            ajax: {
                url: ProfileConfig.loginActivitiesUrl,
                type: 'GET',
                data: function (d) {
                    d.device = filters.device;
                    d.start_date = filters.start_date;
                    d.end_date = filters.end_date;
                }
            },
            columns: [
                { data: 'counter', name: 'counter', orderable: false, searchable: false },
                { data: 'ip_address', name: 'ip_address' },
                { data: 'user_agent', name: 'user_agent' },
                { data: 'device', name: 'device' },
                { data: 'time', name: 'created_at' }
            ],
            columnDefs: [
                { orderable: false, targets: [0, 3] }
            ],
            language: {
                processing: '<span class="spinner-border spinner-border-sm align-middle me-2"></span> Loading...',
                emptyTable: '<div class="d-flex flex-column align-items-center py-10"><i class="ki-outline ki-shield-tick fs-3x text-gray-400 mb-3"></i><span class="text-gray-500 fs-5">No login activities found</span></div>',
                zeroRecords: '<div class="d-flex flex-column align-items-center py-10"><i class="ki-outline ki-shield-tick fs-3x text-gray-400 mb-3"></i><span class="text-gray-500 fs-5">No matching records found</span></div>'
            }
        });
    };

    var initFlatpickr = function () {
        var dateFromEl = document.getElementById('login_date_from');
        var dateToEl = document.getElementById('login_date_to');

        if (dateFromEl) {
            dateFromPicker = flatpickr(dateFromEl, {
                dateFormat: 'd-m-Y',
                allowInput: false,
                onChange: function (selectedDates, dateStr) {
                    filters.start_date = dateStr;
                    // Set minDate for dateTo picker
                    if (dateToPicker && selectedDates.length > 0) {
                        dateToPicker.set('minDate', selectedDates[0]);
                    }
                }
            });
        }

        if (dateToEl) {
            dateToPicker = flatpickr(dateToEl, {
                dateFormat: 'd-m-Y',
                allowInput: false,
                onChange: function (selectedDates, dateStr) {
                    filters.end_date = dateStr;
                    // Set maxDate for dateFrom picker
                    if (dateFromPicker && selectedDates.length > 0) {
                        dateFromPicker.set('maxDate', selectedDates[0]);
                    }
                }
            });
        }
    };

    var handleSearch = function () {
        var searchInput = document.querySelector('[data-kt-login-table-filter="search"]');
        var timeout;

        if (searchInput) {
            searchInput.addEventListener('input', function (e) {
                clearTimeout(timeout);
                timeout = setTimeout(function () {
                    datatable.search(e.target.value).draw();
                }, 300);
            });
        }
    };

    var handleFilter = function () {
        var deviceSelect = $('[data-kt-login-table-filter="device"]');
        var filterButton = document.querySelector('[data-kt-login-table-filter="filter"]');
        var resetButton = document.querySelector('[data-kt-login-table-filter="reset"]');

        // Initialize Select2
        if (deviceSelect.length) {
            deviceSelect.select2({
                dropdownParent: filterMenuEl,
                minimumResultsForSearch: Infinity
            });
        }

        // Apply filter
        if (filterButton) {
            filterButton.addEventListener('click', function () {
                filters.device = deviceSelect.val() || '';
                datatable.ajax.reload();

                // Close the filter menu
                if (filterMenuEl) {
                    var menuInstance = KTMenu.getInstance(filterMenuEl);
                    if (menuInstance) {
                        menuInstance.hide(filterMenuEl);
                    }
                }
            });
        }

        // Reset filter
        if (resetButton) {
            resetButton.addEventListener('click', function () {
                // Reset device
                deviceSelect.val(null).trigger('change');
                filters.device = '';

                // Reset dates
                if (dateFromPicker) {
                    dateFromPicker.clear();
                    dateFromPicker.set('maxDate', null);
                }
                if (dateToPicker) {
                    dateToPicker.clear();
                    dateToPicker.set('minDate', null);
                }
                filters.start_date = '';
                filters.end_date = '';

                datatable.ajax.reload();

                // Close the filter menu
                if (filterMenuEl) {
                    var menuInstance = KTMenu.getInstance(filterMenuEl);
                    if (menuInstance) {
                        menuInstance.hide(filterMenuEl);
                    }
                }
            });
        }
    };

    return {
        init: function () {
            table = document.getElementById('kt_login_activities_table');
            filterMenuEl = document.getElementById('kt_login_filter_menu');

            if (!table) return;

            initDatatable();
            initFlatpickr();
            handleSearch();
            handleFilter();
        },
        reload: function () {
            if (datatable) {
                datatable.ajax.reload();
            }
        }
    };
})();

// ========================================
// Password Update Modal
// ========================================
var KTPasswordModal = (function () {
    var modal;
    var modalElement;
    var form;
    var submitButton;
    var newPasswordInput;
    var confirmPasswordInput;
    var strengthText;
    var strengthBar;

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

    var updateStrengthMeter = function () {
        var strength = calculateStrength(newPasswordInput.value);

        strengthText.textContent = strength.text;
        if (strength.text) {
            strengthText.className = 'fw-bold fs-5 mb-2 text-' + strength.color.split('-')[1];
        } else {
            strengthText.className = 'fw-bold fs-5 mb-2';
        }

        strengthBar.className = 'progress-bar ' + strength.color;
        strengthBar.style.width = strength.width;

        // Enable/disable submit button
        submitButton.disabled = strength.score < 6;
    };

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

    var handleFormSubmit = function () {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var strength = calculateStrength(newPasswordInput.value);
            if (strength.score < 6) {
                toastr.warning('Password is not strong enough.');
                return;
            }

            var newPass = newPasswordInput.value.trim();
            var confirmPass = confirmPasswordInput.value.trim();

            if (newPass !== confirmPass) {
                confirmPasswordInput.classList.add('is-invalid');
                toastr.error('Passwords do not match.');
                return;
            }

            confirmPasswordInput.classList.remove('is-invalid');

            // Show loading
            submitButton.setAttribute('data-kt-indicator', 'on');
            submitButton.disabled = true;

            fetch(ProfileConfig.passwordResetUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
                    submitButton.removeAttribute('data-kt-indicator');
                    submitButton.disabled = true;

                    if (data.success) {
                        modal.hide();
                        Swal.fire({
                            text: data.message || 'Password updated successfully!',
                            icon: 'success',
                            buttonsStyling: false,
                            confirmButtonText: 'Ok',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
                    } else {
                        toastr.error(data.message || 'Password update failed.');
                    }
                })
                .catch(function (error) {
                    submitButton.removeAttribute('data-kt-indicator');
                    submitButton.disabled = false;
                    toastr.error('Something went wrong. Please try again.');
                });
        });
    };

    var handleModalReset = function () {
        modalElement.addEventListener('hidden.bs.modal', function () {
            form.reset();
            strengthText.textContent = '';
            strengthBar.style.width = '0%';
            strengthBar.className = 'progress-bar';
            submitButton.disabled = true;
            confirmPasswordInput.classList.remove('is-invalid');
        });
    };

    var handleOpenButton = function () {
        var btn = document.getElementById('btn_change_password');
        if (btn) {
            btn.addEventListener('click', function () {
                modal.show();
            });
        }
    };

    return {
        init: function () {
            modalElement = document.getElementById('kt_modal_password');
            if (!modalElement) return;

            modal = new bootstrap.Modal(modalElement);
            form = document.getElementById('kt_modal_password_form');
            submitButton = document.getElementById('btn_submit_password');
            newPasswordInput = document.getElementById('modal_password_new');
            confirmPasswordInput = document.getElementById('modal_password_confirm');
            strengthText = document.getElementById('modal_password_strength_text');
            strengthBar = document.getElementById('modal_password_strength_bar');

            newPasswordInput.addEventListener('input', updateStrengthMeter);
            confirmPasswordInput.addEventListener('input', function () {
                if (confirmPasswordInput.value && newPasswordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.classList.add('is-invalid');
                } else {
                    confirmPasswordInput.classList.remove('is-invalid');
                }
            });

            handlePasswordToggle();
            handleFormSubmit();
            handleModalReset();
            handleOpenButton();
        }
    };
})();

// ========================================
// Photo Upload Modal (Non-Admin)
// ========================================
var KTPhotoModal = (function () {
    var modal;
    var modalElement;
    var form;
    var submitButton;
    var photoInput;
    var photoRemove;
    var imageWrapper;
    var originalPhotoUrl;

    var initPhotoUpload = function () {
        var photoUploadEl = document.getElementById('kt_photo_upload');
        if (!photoUploadEl) return;

        imageWrapper = photoUploadEl.querySelector('.image-input-wrapper');
        photoInput = document.getElementById('photo_input');
        photoRemove = document.getElementById('photo_remove');
        originalPhotoUrl = ProfileConfig.userPhotoUrl;

        // Handle file selection
        if (photoInput) {
            photoInput.addEventListener('change', function (e) {
                var file = e.target.files[0];
                if (!file) return;

                // Validate file type
                var validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!validTypes.includes(file.type)) {
                    toastr.error('Please select a valid image file (JPG, PNG)');
                    photoInput.value = '';
                    return;
                }

                // Validate file size (100KB max)
                if (file.size > 100 * 1024) {
                    toastr.error('Image size must be less than 100KB');
                    photoInput.value = '';
                    return;
                }

                // Preview image
                var reader = new FileReader();
                reader.onload = function (e) {
                    imageWrapper.style.backgroundImage = 'url(' + e.target.result + ')';
                    photoUploadEl.classList.add('image-input-changed');
                    photoUploadEl.classList.remove('image-input-empty');
                    photoRemove.value = '0';
                };
                reader.readAsDataURL(file);
            });
        }

        // Handle cancel button
        var cancelBtn = photoUploadEl.querySelector('[data-kt-image-input-action="cancel"]');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                photoInput.value = '';
                imageWrapper.style.backgroundImage = 'url(' + originalPhotoUrl + ')';
                photoUploadEl.classList.remove('image-input-changed');
                photoRemove.value = '0';
            });
        }

        // Handle remove button
        var removeBtn = photoUploadEl.querySelector('[data-kt-image-input-action="remove"]');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                photoInput.value = '';
                imageWrapper.style.backgroundImage = 'url(' + ProfileConfig.placeholderUrl + ')';
                photoUploadEl.classList.remove('image-input-changed');
                photoUploadEl.classList.add('image-input-empty');
                photoRemove.value = '1';
            });
        }
    };

    var handleFormSubmit = function () {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            // Show loading
            submitButton.setAttribute('data-kt-indicator', 'on');
            submitButton.disabled = true;

            var formData = new FormData(form);

            fetch(ProfileConfig.profileUpdateUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    submitButton.removeAttribute('data-kt-indicator');
                    submitButton.disabled = false;

                    if (data.success) {
                        modal.hide();
                        Swal.fire({
                            text: data.message || 'Photo updated successfully!',
                            icon: 'success',
                            buttonsStyling: false,
                            confirmButtonText: 'Ok',
                            customClass: { confirmButton: 'btn btn-primary' }
                        }).then(function () {
                            location.reload();
                        });
                    } else {
                        toastr.error(data.message || 'Photo update failed.');
                    }
                })
                .catch(function (error) {
                    submitButton.removeAttribute('data-kt-indicator');
                    submitButton.disabled = false;
                    toastr.error('Something went wrong. Please try again.');
                });
        });
    };

    var handleModalReset = function () {
        modalElement.addEventListener('hidden.bs.modal', function () {
            var photoUploadEl = document.getElementById('kt_photo_upload');
            if (photoUploadEl) {
                photoInput.value = '';
                imageWrapper.style.backgroundImage = 'url(' + originalPhotoUrl + ')';
                photoUploadEl.classList.remove('image-input-changed', 'image-input-empty');
                photoRemove.value = '0';
            }
        });
    };

    var handleOpenButton = function () {
        var btn = document.getElementById('btn_change_photo');
        if (btn) {
            btn.addEventListener('click', function () {
                modal.show();
            });
        }
    };

    return {
        init: function () {
            modalElement = document.getElementById('kt_modal_photo');
            if (!modalElement) return;

            modal = new bootstrap.Modal(modalElement);
            form = document.getElementById('kt_modal_photo_form');
            submitButton = document.getElementById('btn_submit_photo');

            initPhotoUpload();
            handleFormSubmit();
            handleModalReset();
            handleOpenButton();
        }
    };
})();

// ========================================
// Profile Update Modal (Admin)
// ========================================
var KTProfileModal = (function () {
    var modal;
    var modalElement;
    var form;
    var submitButton;
    var originalValues = {};
    var photoInput;
    var photoRemove;
    var imageWrapper;
    var originalPhotoUrl;

    var storeOriginalValues = function () {
        originalValues = {
            name: document.getElementById('profile_name').value.trim(),
            email: document.getElementById('profile_email').value.trim(),
            mobile_number: document.getElementById('profile_mobile').value.trim()
        };
    };

    var initPhotoUpload = function () {
        var photoUploadEl = document.getElementById('kt_profile_photo_upload');
        if (!photoUploadEl) return;

        imageWrapper = photoUploadEl.querySelector('.image-input-wrapper');
        photoInput = document.getElementById('profile_photo_input');
        photoRemove = document.getElementById('profile_photo_remove');
        originalPhotoUrl = ProfileConfig.userPhotoUrl;

        // Handle file selection
        if (photoInput) {
            photoInput.addEventListener('change', function (e) {
                var file = e.target.files[0];
                if (!file) return;

                // Validate file type
                var validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                if (!validTypes.includes(file.type)) {
                    toastr.error('Please select a valid image file (JPG, PNG)');
                    photoInput.value = '';
                    return;
                }

                // Validate file size (100KB max)
                if (file.size > 100 * 1024) {
                    toastr.error('Image size must be less than 100KB');
                    photoInput.value = '';
                    return;
                }

                // Preview image
                var reader = new FileReader();
                reader.onload = function (e) {
                    imageWrapper.style.backgroundImage = 'url(' + e.target.result + ')';
                    photoUploadEl.classList.add('image-input-changed');
                    photoUploadEl.classList.remove('image-input-empty');
                    photoRemove.value = '0';
                };
                reader.readAsDataURL(file);
            });
        }

        // Handle cancel button
        var cancelBtn = photoUploadEl.querySelector('[data-kt-image-input-action="cancel"]');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                photoInput.value = '';
                imageWrapper.style.backgroundImage = 'url(' + originalPhotoUrl + ')';
                photoUploadEl.classList.remove('image-input-changed');
                photoRemove.value = '0';
            });
        }

        // Handle remove button
        var removeBtn = photoUploadEl.querySelector('[data-kt-image-input-action="remove"]');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                photoInput.value = '';
                imageWrapper.style.backgroundImage = 'url(' + ProfileConfig.placeholderUrl + ')';
                photoUploadEl.classList.remove('image-input-changed');
                photoUploadEl.classList.add('image-input-empty');
                photoRemove.value = '1';
            });
        }
    };

    var hasChanges = function () {
        var nameChanged = document.getElementById('profile_name').value.trim() !== originalValues.name;
        var emailChanged = document.getElementById('profile_email').value.trim() !== originalValues.email;
        var mobileChanged = document.getElementById('profile_mobile').value.trim() !== originalValues.mobile_number;
        var photoChanged = (photoInput && photoInput.files.length > 0) || (photoRemove && photoRemove.value === '1');

        return nameChanged || emailChanged || mobileChanged || photoChanged;
    };

    var validateForm = function () {
        var isValid = true;

        // Clear previous errors
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(function (el) {
            el.textContent = '';
        });

        // Name validation
        var name = document.getElementById('profile_name').value.trim();
        if (!name) {
            showError('name', 'Name is required.');
            isValid = false;
        }

        // Email validation
        var email = document.getElementById('profile_email').value.trim();
        if (!email) {
            showError('email', 'Email is required.');
            isValid = false;
        } else if (!/^\S+@\S+\.\S+$/.test(email)) {
            showError('email', 'Please enter a valid email address.');
            isValid = false;
        }

        // Mobile validation
        var mobile = document.getElementById('profile_mobile').value.trim();
        if (!mobile) {
            showError('mobile_number', 'Mobile number is required.');
            isValid = false;
        } else if (!/^01[3-9]\d{8}$/.test(mobile)) {
            showError('mobile_number', 'Please enter a valid 11-digit Bangladeshi mobile number.');
            isValid = false;
        }

        return isValid;
    };

    var showError = function (fieldName, message) {
        var input = form.querySelector('[name="' + fieldName + '"]');
        if (input) {
            input.classList.add('is-invalid');
            var feedback = input.parentElement.querySelector('.invalid-feedback');
            if (feedback) feedback.textContent = message;
        }
        toastr.error(message);
    };

    var handleFormSubmit = function () {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!hasChanges()) {
                toastr.info('No changes detected.');
                return;
            }

            if (!validateForm()) {
                return;
            }

            // Show loading
            submitButton.setAttribute('data-kt-indicator', 'on');
            submitButton.disabled = true;

            var formData = new FormData(form);

            fetch(ProfileConfig.profileUpdateUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
                .then(function (response) {
                    if (!response.ok) {
                        return response.json().then(function (err) {
                            throw err;
                        });
                    }
                    return response.json();
                })
                .then(function (data) {
                    submitButton.removeAttribute('data-kt-indicator');
                    submitButton.disabled = false;

                    if (data.success) {
                        modal.hide();
                        Swal.fire({
                            text: data.message || 'Profile updated successfully!',
                            icon: 'success',
                            buttonsStyling: false,
                            confirmButtonText: 'Ok',
                            customClass: { confirmButton: 'btn btn-primary' }
                        }).then(function () {
                            location.reload();
                        });
                    } else {
                        if (data.errors) {
                            Object.keys(data.errors).forEach(function (field) {
                                showError(field, data.errors[field][0]);
                            });
                        } else {
                            toastr.error(data.message || 'Profile update failed.');
                        }
                    }
                })
                .catch(function (error) {
                    submitButton.removeAttribute('data-kt-indicator');
                    submitButton.disabled = false;
                    if (error.errors) {
                        Object.keys(error.errors).forEach(function (field) {
                            showError(field, error.errors[field][0]);
                        });
                    } else {
                        toastr.error(error.message || 'Something went wrong.');
                    }
                });
        });
    };

    var handleModalReset = function () {
        modalElement.addEventListener('hidden.bs.modal', function () {
            // Reset to original values
            document.getElementById('profile_name').value = originalValues.name;
            document.getElementById('profile_email').value = originalValues.email;
            document.getElementById('profile_mobile').value = originalValues.mobile_number;

            // Reset photo
            var photoUploadEl = document.getElementById('kt_profile_photo_upload');
            if (photoUploadEl && photoInput && imageWrapper) {
                photoInput.value = '';
                imageWrapper.style.backgroundImage = 'url(' + originalPhotoUrl + ')';
                photoUploadEl.classList.remove('image-input-changed', 'image-input-empty');
                if (photoRemove) photoRemove.value = '0';
            }

            // Clear errors
            form.querySelectorAll('.is-invalid').forEach(function (el) {
                el.classList.remove('is-invalid');
            });
            form.querySelectorAll('.invalid-feedback').forEach(function (el) {
                el.textContent = '';
            });
        });
    };

    var handleOpenButton = function () {
        var btn = document.getElementById('btn_edit_profile');
        if (btn) {
            btn.addEventListener('click', function () {
                storeOriginalValues();
                modal.show();
            });
        }
    };

    return {
        init: function () {
            modalElement = document.getElementById('kt_modal_profile');
            if (!modalElement) return;

            modal = new bootstrap.Modal(modalElement);
            form = document.getElementById('kt_modal_profile_form');
            submitButton = document.getElementById('btn_submit_profile');

            storeOriginalValues();
            initPhotoUpload();
            handleFormSubmit();
            handleModalReset();
            handleOpenButton();
        }
    };
})();

// ========================================
// Tab Handling
// ========================================
var KTProfileTabs = (function () {
    var handleTabPersistence = function () {
        // Store active tab in localStorage
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            localStorage.setItem('profileActiveTab', $(e.target).attr('href'));

            // Reload datatable when switching tabs
            var tabId = $(e.target).attr('href');
            if (tabId === '#kt_tab_wallet_logs') {
                KTWalletLogsTable.reload();
            } else if (tabId === '#kt_tab_login_activity') {
                KTLoginActivityTable.reload();
            }
        });

        // Restore active tab on page load
        var activeTab = localStorage.getItem('profileActiveTab');
        if (activeTab) {
            var tabTrigger = document.querySelector('a[href="' + activeTab + '"]');
            if (tabTrigger) {
                var tab = new bootstrap.Tab(tabTrigger);
                tab.show();
            }
        }
    };

    return {
        init: function () {
            handleTabPersistence();
        }
    };
})();

// ========================================
// Initialize Tooltips
// ========================================
var KTTooltips = (function () {
    return {
        init: function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    };
})();

// ========================================
// Metronic Standard Init
// ========================================
KTUtil.onDOMContentLoaded(function () {
    KTWalletLogsTable.init();
    KTLoginActivityTable.init();
    KTPasswordModal.init();
    KTPhotoModal.init();
    KTProfileModal.init();
    KTProfileTabs.init();
    KTTooltips.init();
});
