"use strict";


var KTAddClassName = function () {
    // Shared variables
    const element = document.getElementById('kt_modal_add_class');

    // Early return if element doesn't exist
    if (!element) {
        console.error('Modal element not found');
        return {
            init: function () { }
        };
    }

    const form = element.querySelector('#kt_modal_add_class_form');
    const modal = bootstrap.Modal.getOrCreateInstance(element);

    // Init edit institution modal
    var initAddClass = () => {
        // Cancel button handler
        const cancelButton = element.querySelector('[data-kt-add-class-modal-action="cancel"]');
        if (cancelButton) {
            cancelButton.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                modal.hide();
            });
        }

        // Close button handler
        const closeButton = element.querySelector('[data-kt-add-class-modal-action="close"]');
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
                    'class_name_add': {
                        validators: {
                            notEmpty: {
                                message: 'Name is required'
                            }
                        }
                    },
                    'class_numeral_add': {
                        validators: {
                            notEmpty: {
                                message: 'Class numeral is required'
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

        const submitButton = element.querySelector('[data-kt-add-class-modal-action="submit"]');
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
                        fetch(`/classnames`, {
                            method: 'POST', // Laravel expects POST for PUT routes
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
                                    toastr.success(data.message || 'Class added successfully');
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
                        toastr.warning('Please fill all required fields');
                    }
                });
            });
        }
    }

    return {
        init: function () {
            initAddClass();
            initValidation();
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

    let classId = null; // Declare globally

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
                    classId = this.getAttribute("data-class-id"); // Assign value globally
                    console.log("Class ID:", classId);

                    if (!classId) return;

                    // Clear form
                    if (form) form.reset();

                    fetch(`/classnames/ajax-data/${classId}`)
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
                        formData.append('_method', 'PUT'); // For Laravel resource route

                        // Submit via AJAX
                        fetch(`/classnames/${classId}`, {
                            method: 'POST', // Laravel expects POST for PUT routes
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
                                toastr.error(error.message || 'Failed to update institution');
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
    KTAddClassName.init();
    KTEditClassName.init();
});