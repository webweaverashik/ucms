"use strict";

var KTGuardiansList = function () {
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
            "pageLength": 25,
            "lengthChange": true,
            "autoWidth": false,  // Disable auto width
            'columnDefs': [
                { orderable: false, targets: 4 }, // Disable ordering on column Guardian                
                { orderable: false, targets: 9 }, // Disable ordering on column Actions                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }

    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-subscription-table-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            datatable.search(e.target.value).draw();
        });
    }

    // Filter Datatable
    var handleFilter = function () {
        // Select filter options
        const filterForm = document.querySelector('[data-kt-subscription-table-filter="form"]');
        const filterButton = filterForm.querySelector('[data-kt-subscription-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-kt-subscription-table-filter="reset"]');
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

    // Delete pending students
    const handleDeletion = function () {
        document.querySelectorAll('.delete-guardian').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();

                let guardianId = this.getAttribute('data-guardian-id');
                let url = routeDeleteGuardian.replace(':id', guardianId);  // Replace ':id' with actual student ID

                Swal.fire({
                    title: "Are you sure to delete this guardian?",
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
                                    Swal.fire({
                                        title: "Deleted!",
                                        text: "The guardian has been removed successfully.",
                                        icon: "success",
                                    }).then(() => {
                                        location.reload(); // Reload to reflect changes
                                    });
                                } else {
                                    Swal.fire({
                                        title: "Error!",
                                        text: data.message,
                                        icon: "error",
                                    });
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
        // Public functions  
        init: function () {
            table = document.getElementById('kt_guardians_table');

            if (!table) {
                return;
            }

            initDatatable();
            handleSearch();
            handleDeletion();
            handleFilter();
        }
    }
}();


var KTGuardiansEditGuardian = function () {
    // Shared variables
    const element = document.getElementById('kt_modal_edit_guardian');

    // Early return if element doesn't exist
    if (!element) {
        console.error('Modal element not found');
        return {
            init: function () { }
        };
    }

    const form = element.querySelector('#kt_modal_edit_guardian_form');
    const modal = bootstrap.Modal.getOrCreateInstance(element);

    let guardianId = null; // Declare globally

    // Init edit guardian modal
    var initEditGuardian = () => {
        // Cancel button handler
        const cancelButton = element.querySelector('[data-kt-guardians-modal-action="cancel"]');
        if (cancelButton) {
            cancelButton.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                modal.hide();
            });
        }

        // Close button handler
        const closeButton = element.querySelector('[data-kt-guardians-modal-action="close"]');
        if (closeButton) {
            closeButton.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                modal.hide();
            });
        }

        // AJAX form data load
        const editButtons = document.querySelectorAll("[data-bs-target='#kt_modal_edit_guardian']");
        if (editButtons.length) {
            editButtons.forEach((button) => {
                button.addEventListener("click", function () {
                    guardianId = this.getAttribute("data-guardian-id"); // Assign value globally
                    console.log("Guardian ID:", guardianId);
                    if (!guardianId) return;

                    // Clear form
                    if (form) form.reset();

                    fetch(`/guardians/${guardianId}`)
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            if (data.success && data.data) {
                                const guardian = data.data;

                                // Helper function to safely set values
                                const setValue = (selector, value) => {
                                    const el = document.querySelector(selector);
                                    if (el) el.value = value;
                                };

                                // Helper function to safely check radio buttons
                                const checkRadio = (name, value) => {
                                    const radio = document.querySelector(`input[name='${name}'][value='${value}']`);
                                    if (radio) radio.checked = true;
                                };

                                // Populate form fields
                                setValue("select[name='guardian_student']", guardian.student_id);
                                setValue("input[name='guardian_name']", guardian.name);
                                setValue("input[name='guardian_mobile_number']", guardian.mobile_number);
                                checkRadio('guardian_gender', guardian.gender);
                                setValue("select[name='guardian_relationship']", guardian.relationship);

                                // Trigger change events
                                const studentSelect = document.querySelector("select[name='guardian_student']");
                                const relationshipSelect = document.querySelector("select[name='guardian_relationship']");
                                if (studentSelect) studentSelect.dispatchEvent(new Event("change"));
                                if (relationshipSelect) relationshipSelect.dispatchEvent(new Event("change"));

                                // Show modal
                                modal.show();
                            } else {
                                throw new Error(data.message || 'Invalid response data');
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            toastr.error(error.message || "Failed to load guardian details");
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
                    'guardian_student': {
                        validators: {
                            notEmpty: {
                                message: 'Please, select a student'
                            }
                        }
                    },
                    'guardian_name': {
                        validators: {
                            notEmpty: {
                                message: 'Name is required'
                            }
                        }
                    },
                    'guardian_mobile_number': {
                        validators: {
                            notEmpty: {
                                message: 'Mobile number is required'
                            },
                            regexp: {
                                regexp: /^01[4-9][0-9](?!\b(\d)\1{7}\b)\d{7}$/,
                                message: 'Please enter a valid Bangladeshi mobile number'
                            },
                            stringLength: {
                                min: 11,
                                max: 11,
                                message: 'The mobile number must be exactly 11 digits'
                            }
                        }
                    },
                    'guardian_gender': {
                        validators: {
                            notEmpty: {
                                message: 'Please, select a gender'
                            }
                        }
                    },
                    'guardian_relationship': {
                        validators: {
                            notEmpty: {
                                message: 'Select a relationship'
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

        const submitButton = element.querySelector('[data-kt-guardians-modal-action="submit"]');
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
                        fetch(`/guardians/${guardianId}`, {
                            method: 'POST', // Laravel expects POST for PUT routes
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
                                    toastr.success(data.message || 'Guardian updated successfully');
                                    modal.hide();

                                    // Reload the page
                                    window.location.reload();
                                } else {
                                    throw new Error(data.message || 'Update failed');
                                }
                            })
                            .catch(error => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                toastr.error(error.message || 'Failed to update guardian');
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
            initEditGuardian();
            initValidation();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTGuardiansList.init();
    KTGuardiansEditGuardian.init();
});