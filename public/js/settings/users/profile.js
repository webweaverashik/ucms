"use strict";

// ========================================
// Wallet Logs DataTable
// ========================================
var KTWalletLogsTable = function () {
    var table;
    var datatable;

    var initDatatable = function () {
        datatable = $(table).DataTable({
            info: true,
            order: [],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            columnDefs: [
                // { orderable: false, targets: [0, 2] }
            ],
            language: {
                emptyTable: `<div class="d-flex flex-column align-items-center py-10">
                    <i class="ki-outline ki-wallet fs-3x text-gray-400 mb-3"></i>
                    <span class="text-gray-500 fs-5">No wallet logs found</span>
                </div>`
            }
        });
    };

    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-filter="wallet-search"]');
        if (filterSearch) {
            filterSearch.addEventListener('keyup', function (e) {
                datatable.search(e.target.value).draw();
            });
        }
    };

    var handleTypeFilter = function () {
        const filterType = $('[data-kt-filter="wallet-type"]');
        if (filterType.length) {
            filterType.on('change', function () {
                var val = $(this).val();

                // Remove previous custom filter
                $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(function (fn) {
                    return fn.name !== 'walletTypeFilter';
                });

                if (val) {
                    var walletTypeFilter = function (settings, data, dataIndex) {
                        if (settings.nTable.id !== 'kt_wallet_logs_table') return true;
                        var row = datatable.row(dataIndex).node();
                        var rowType = $(row).data('type');
                        return rowType === val;
                    };
                    Object.defineProperty(walletTypeFilter, 'name', { value: 'walletTypeFilter' });
                    $.fn.dataTable.ext.search.push(walletTypeFilter);
                }

                datatable.draw();
            });
        }
    };

    var initSelect2 = function () {
        $('[data-kt-filter="wallet-type"]').select2({
            minimumResultsForSearch: Infinity
        });
    };

    return {
        init: function () {
            table = document.getElementById('kt_wallet_logs_table');
            if (!table) return;

            initDatatable();
            initSelect2();
            handleSearch();
            handleTypeFilter();
        }
    };
}();

// ========================================
// Login Activity DataTable
// ========================================
var KTLoginActivityTable = function () {
    var table;
    var datatable;

    var initDatatable = function () {
        datatable = $(table).DataTable({
            info: true,
            order: [],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            columnDefs: [
                { orderable: false, targets: [0] }
            ],
            language: {
                emptyTable: `<div class="d-flex flex-column align-items-center py-10">
                    <i class="ki-outline ki-shield-tick fs-3x text-gray-400 mb-3"></i>
                    <span class="text-gray-500 fs-5">No login activities found</span>
                </div>`
            }
        });
    };

    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-filter="login-search"]');
        if (filterSearch) {
            filterSearch.addEventListener('keyup', function (e) {
                datatable.search(e.target.value).draw();
            });
        }
    };

    return {
        init: function () {
            table = document.getElementById('kt_login_activities_table');
            if (!table) return;

            initDatatable();
            handleSearch();
        }
    };
}();

// ========================================
// Password Update Modal
// ========================================
var KTPasswordModal = function () {
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
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            }
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
}();

// ========================================
// Profile Update Modal with Photo Upload
// ========================================
var KTProfileModal = function () {
    var modal;
    var modalElement;
    var form;
    var submitButton;
    var originalValues = {};
    var photoInput;
    var photoPreview;
    var photoError;
    var removePhotoInput;
    var selectedFile = null;
    var originalPhotoUrl;

    var storeOriginalValues = function () {
        originalValues = {
            name: document.getElementById('profile_name').value.trim(),
            email: document.getElementById('profile_email').value.trim(),
            mobile_number: document.getElementById('profile_mobile').value.trim()
        };
        originalPhotoUrl = ProfileConfig.userPhotoUrl;
    };

    var hasChanges = function () {
        return (
            document.getElementById('profile_name').value.trim() !== originalValues.name ||
            document.getElementById('profile_email').value.trim() !== originalValues.email ||
            document.getElementById('profile_mobile').value.trim() !== originalValues.mobile_number ||
            selectedFile !== null ||
            removePhotoInput.value === '1'
        );
    };

    var validateFile = function (file) {
        // Check file type
        if (!ProfileConfig.allowedTypes.includes(file.type)) {
            return 'Only JPG and PNG files are allowed.';
        }

        // Check file size (100KB = 102400 bytes)
        if (file.size > ProfileConfig.maxFileSize) {
            return 'File size must be less than 100KB. Current size: ' + (file.size / 1024).toFixed(2) + 'KB';
        }

        return null;
    };

    var handlePhotoUpload = function () {
        if (!photoInput) return;

        photoInput.addEventListener('change', function (e) {
            var file = e.target.files[0];

            if (!file) return;

            // Validate file
            var error = validateFile(file);
            if (error) {
                photoError.textContent = error;
                photoError.style.display = 'block';
                photoInput.value = '';
                selectedFile = null;
                return;
            }

            // Clear error
            photoError.style.display = 'none';
            selectedFile = file;
            removePhotoInput.value = '0';

            // Preview image
            var reader = new FileReader();
            reader.onload = function (e) {
                photoPreview.style.backgroundImage = 'url(' + e.target.result + ')';
            };
            reader.readAsDataURL(file);

            // Add changed class to image input
            photoPreview.closest('.image-input').classList.add('image-input-changed');
            photoPreview.closest('.image-input').classList.remove('image-input-empty');
        });
    };

    var handlePhotoRemove = function () {
        var removeBtn = document.querySelector('[data-kt-image-input-action="remove"]');
        if (removeBtn) {
            removeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                
                photoInput.value = '';
                selectedFile = null;
                removePhotoInput.value = '1';
                photoPreview.style.backgroundImage = 'url(' + ProfileConfig.defaultPhotoUrl + ')';
                photoError.style.display = 'none';
                
                photoPreview.closest('.image-input').classList.remove('image-input-changed');
                photoPreview.closest('.image-input').classList.add('image-input-empty');
            });
        }
    };

    var handlePhotoCancel = function () {
        var cancelBtn = document.querySelector('[data-kt-image-input-action="cancel"]');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function (e) {
                e.preventDefault();
                
                photoInput.value = '';
                selectedFile = null;
                removePhotoInput.value = '0';
                photoPreview.style.backgroundImage = 'url(' + originalPhotoUrl + ')';
                photoError.style.display = 'none';
                
                photoPreview.closest('.image-input').classList.remove('image-input-changed');
                photoPreview.closest('.image-input').classList.remove('image-input-empty');
            });
        }
    };

    var validateForm = function () {
        var isValid = true;

        // Clear previous errors
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

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
        var input = form.querySelector(`[name="${fieldName}"]`);
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
            
            // Add the photo file if selected
            if (selectedFile) {
                formData.set('photo', selectedFile);
            }

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
                        return response.json().then(err => {
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
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            }
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
                            if (field === 'photo') {
                                photoError.textContent = error.errors[field][0];
                                photoError.style.display = 'block';
                            } else {
                                showError(field, error.errors[field][0]);
                            }
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
            photoInput.value = '';
            selectedFile = null;
            removePhotoInput.value = '0';
            photoPreview.style.backgroundImage = 'url(' + originalPhotoUrl + ')';
            photoError.style.display = 'none';
            
            photoPreview.closest('.image-input').classList.remove('image-input-changed');
            photoPreview.closest('.image-input').classList.remove('image-input-empty');

            // Clear errors
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
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
            photoInput = document.getElementById('profile_photo_input');
            photoPreview = document.getElementById('profile_photo_preview');
            photoError = document.getElementById('profile_photo_error');
            removePhotoInput = document.getElementById('remove_photo_input');

            storeOriginalValues();
            handlePhotoUpload();
            handlePhotoRemove();
            handlePhotoCancel();
            handleFormSubmit();
            handleModalReset();
            handleOpenButton();
        }
    };
}();

// ========================================
// Tab Handling
// ========================================
var KTProfileTabs = function () {
    var handleTabPersistence = function () {
        // Store active tab in localStorage
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            localStorage.setItem('profileActiveTab', $(e.target).attr('href'));
        });

        // Restore active tab on page load
        var activeTab = localStorage.getItem('profileActiveTab');
        if (activeTab) {
            var tabTrigger = document.querySelector(`a[href="${activeTab}"]`);
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
}();

// ========================================
// Initialize Tooltips
// ========================================
var KTTooltips = function () {
    return {
        init: function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    };
}();

// ========================================
// Metronic Standard Init
// ========================================
KTUtil.onDOMContentLoaded(function () {
    KTWalletLogsTable.init();
    KTLoginActivityTable.init();
    KTPasswordModal.init();
    KTProfileModal.init();
    KTProfileTabs.init();
    KTTooltips.init();
});
