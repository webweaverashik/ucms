"use strict";

var KTSiblingsList = function () {
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
                { orderable: false, targets: 4 }, // Disable ordering on column sibling                
                { orderable: false, targets: 10 }, // Disable ordering on column Actions                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }

    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-siblings-table-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            datatable.search(e.target.value).draw();
        });
    }

    // Filter Datatable
    var handleFilter = function () {
        // Select filter options
        const filterForm = document.querySelector('[data-kt-siblings-table-filter="form"]');
        const filterButton = filterForm.querySelector('[data-kt-siblings-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-kt-siblings-table-filter="reset"]');
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

    // Delete siblings
    const handleDeletion = function () {
        document.querySelectorAll('.delete-sibling').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();

                let siblingId = this.getAttribute('data-sibling-id');
                let url = routeDeleteSibling.replace(':id', siblingId);  // Replace ':id' with actual student ID

                Swal.fire({
                    title: "Are you sure to delete this sibling?",
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
                                        text: "The sibling has been removed successfully.",
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
            table = document.getElementById('kt_siblings_table');

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


var KTSiblingsEditSibling = function () {
    // Shared variables
    const element = document.getElementById('kt_modal_edit_sibling');

    // Early return if element doesn't exist
    if (!element) {
        console.error('Modal element not found');
        return {
            init: function () { }
        };
    }

    const form = element.querySelector('#kt_modal_edit_sibling_form');
    const modal = bootstrap.Modal.getOrCreateInstance(element);

    let siblingId = null; // Declare globally

    // Init edit sibling modal
    var initEditsibling = () => {
        // Cancel button handler
        const cancelButton = element.querySelector('[data-kt-siblings-modal-action="cancel"]');
        if (cancelButton) {
            cancelButton.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                modal.hide();
            });
        }

        // Close button handler
        const closeButton = element.querySelector('[data-kt-siblings-modal-action="close"]');
        if (closeButton) {
            closeButton.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                modal.hide();
            });
        }

        // AJAX form data load
        const editButtons = document.querySelectorAll("[data-bs-target='#kt_modal_edit_sibling']");
        if (editButtons.length) {
            editButtons.forEach((button) => {
                button.addEventListener("click", function () {
                    siblingId = this.getAttribute("data-sibling-id"); // Assign value globally
                    console.log("Sibling ID:", siblingId);

                    if (!siblingId) return;

                    // Clear form
                    if (form) form.reset();

                    fetch(`/siblings/${siblingId}`)
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
                                const sibling = data.data;

                                // Helper function to safely set values
                                const setValue = (selector, value) => {
                                    const el = document.querySelector(selector);
                                    if (el) el.value = value;
                                };

                                // Populate form fields
                                setValue("select[name='sibling_student']", sibling.student_id);
                                setValue("input[name='sibling_name']", sibling.name);
                                setValue("input[name='sibling_age']", sibling.age);
                                setValue("input[name='sibling_class']", sibling.class);
                                setValue("select[name='sibling_institution']", sibling.institution_id);
                                setValue("select[name='sibling_relationship']", sibling.relationship);

                                // Trigger change events
                                const studentSelect = document.querySelector("select[name='sibling_student']");
                                const institutionSelect = document.querySelector("select[name='sibling_institution']");
                                const relationshipSelect = document.querySelector("select[name='sibling_relationship']");
                                if (studentSelect) studentSelect.dispatchEvent(new Event("change"));
                                if (relationshipSelect) relationshipSelect.dispatchEvent(new Event("change"));
                                if (institutionSelect) institutionSelect.dispatchEvent(new Event("change"));

                                // Show modal
                                modal.show();
                            } else {
                                throw new Error(data.message || 'Invalid response data');
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            toastr.error(error.message || "Failed to load sibling details");
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
                    // 'sibling_student': {
                    //     validators: {
                    //         notEmpty: {
                    //             message: 'Please, select a student'
                    //         }
                    //     }
                    // },
                    'sibling_name': {
                        validators: {
                            notEmpty: {
                                message: 'Name is required'
                            }
                        }
                    },
                    'sibling_age': {
                        validators: {
                            notEmpty: {
                                message: 'Please, mention the age.'
                            }
                        }
                    },
                    'sibling_class': {
                        validators: {
                            notEmpty: {
                                message: 'Please, mention the class.'
                            }
                        }
                    },
                    'sibling_institution': {
                        validators: {
                            notEmpty: {
                                message: 'Institution is required.'
                            }
                        }
                    },
                    'sibling_relationship': {
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

        const submitButton = element.querySelector('[data-kt-siblings-modal-action="submit"]');
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
                        fetch(`/siblings/${siblingId}`, {
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
                                    toastr.success(data.message || 'sibling updated successfully');
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
                                toastr.error(error.message || 'Failed to update sibling');
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
            initEditsibling();
            initValidation();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTSiblingsList.init();
    KTSiblingsEditSibling.init();
});