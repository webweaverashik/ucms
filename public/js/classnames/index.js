"use strict";

var KTActiveClassList = function () {
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
                { orderable: false, targets: [2, 9] }
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }


    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-active-class-table-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            datatable.search(e.target.value).draw();
        });
    }

    // Filter Datatable
    var handleFilter = function () {
        // Select filter options
        const filterForm = document.querySelector('[data-kt-active-class-table-filter="form"]');
        const filterButton = filterForm.querySelector('[data-kt-active-class-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-kt-active-class-table-filter="reset"]');
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

    // Delete invoices
    const handleDeletion = function () {
        document.addEventListener('click', function (e) {
            const deleteBtn = e.target.closest('.class-delete-button');
            if (!deleteBtn) return;

            e.preventDefault();

            const activeClassId = deleteBtn.getAttribute('data-active-class-id');
            const url = routeDeleteActiveClass.replace(':id', activeClassId);

            Swal.fire({
                title: "Are you sure to delete this class?",
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
                                    text: data.message || "Class has been deleted successfully.",
                                    icon: "success",
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: "Error!",
                                    text: data.message || "Something went wrong.",
                                    icon: "error",
                                });
                            }
                        })
                        .catch(error => {
                            console.error("Fetch Error:", error);
                            Swal.fire({
                                title: "Error!",
                                text: "Server error. Please try again later.",
                                icon: "error",
                            });
                        });
                }
            });
        });
    };


    return {
        // Public functions  
        init: function () {
            table = document.getElementById('kt_active_classes_table');

            if (!table) {
                return;
            }

            initDatatable();
            handleSearch();
            handleFilter();
            handleDeletion();
        }
    }
}();

var KTInactiveClassList = function () {
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
                { orderable: false, targets: [2, 8] }
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }


    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-inactive-class-table-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            datatable.search(e.target.value).draw();
        });
    }

    // Filter Datatable
    var handleFilter = function () {
        // Select filter options
        const filterForm = document.querySelector('[data-kt-inactive-class-table-filter="form"]');
        const filterButton = filterForm.querySelector('[data-kt-inactive-class-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-kt-inactive-class-table-filter="reset"]');
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

    return {
        // Public functions  
        init: function () {
            table = document.getElementById('kt_inactive_classes_table');

            if (!table) {
                return;
            }

            initDatatable();
            handleSearch();
            handleFilter();
        }
    }
}();

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
    const element = document.getElementById('kt_modal_edit_class');

    if (!element) {
        console.error('Modal element not found');
        return { init: function () { } };
    }

    const form = element.querySelector('#kt_modal_edit_class_form');
    const modal = bootstrap.Modal.getOrCreateInstance(element);

    let classId = null;

    // ----------------------------
    // 🔹 Fetch and populate modal
    // ----------------------------
    const handleEditClick = (button) => {
        classId = button.getAttribute("data-class-id");
        if (!classId) return;

        if (form) form.reset();

        fetch(`/classnames/ajax-data/${classId}`)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.message || 'Network response was not ok');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data) {
                    const classname = data.data;
                    const setValue = (selector, value) => {
                        const el = document.querySelector(selector);
                        if (el) el.value = value;
                    };

                    // Populate form fields
                    setValue("input[name='class_name_edit']", classname.class_name);
                    setValue("select[name='class_numeral_edit']", classname.class_numeral);
                    setValue("input[name='description_edit']", classname.class_description);

                    // ✅ Handle activation status radio buttons
                    const isActive = Boolean(classname.is_active);
                    const activeRadio = document.getElementById("active_radio");
                    const inactiveRadio = document.getElementById("inactive_radio");

                    if (activeRadio && inactiveRadio) {
                        activeRadio.checked = isActive;
                        inactiveRadio.checked = !isActive;
                    }

                    // Refresh Bootstrap visual states
                    [activeRadio, inactiveRadio].forEach(radio => {
                        if (radio) radio.dispatchEvent(new Event("change"));
                    });

                    // Update modal title
                    const modalTitle = document.getElementById("kt_modal_edit_class_title");
                    if (modalTitle) modalTitle.textContent = `Update - ${classname.class_name} (${classname.class_numeral})`;

                    // Trigger numeral select UI update
                    const classNumeralSelect = document.querySelector("select[name='class_numeral_edit']");
                    if (classNumeralSelect) classNumeralSelect.dispatchEvent(new Event("change"));

                    modal.show();
                } else {
                    throw new Error(data.message || 'Invalid response data');
                }
            })
            .catch(error => {
                console.error("Error:", error);
                toastr.error(error.message || "Failed to load class details");
            });
    };

    // ----------------------------------------
    // 🔹 Event Delegation for both DataTables
    // ----------------------------------------
    document.addEventListener('click', function (e) {
        const button = e.target.closest("[data-bs-target='#kt_modal_edit_class']");
        if (button && (button.closest('#kt_active_classes_table') || button.closest('#kt_inactive_classes_table'))) {
            handleEditClick(button);
        }
    });

    // ----------------------------------------
    // 🔹 Cancel & Close buttons
    // ----------------------------------------
    ['cancel', 'close'].forEach(action => {
        const btn = element.querySelector(`[data-kt-edit-class-modal-action="${action}"]`);
        if (btn) {
            btn.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                modal.hide();
            });
        }
    });

    // ----------------------------------------
    // 🔹 Form Validation + Submit
    // ----------------------------------------
    const initValidation = () => {
        if (!form) return;

        const validator = FormValidation.formValidation(form, {
            fields: {
                'class_name_edit': {
                    validators: { notEmpty: { message: 'Name is required' } }
                }
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap: new FormValidation.plugins.Bootstrap5({
                    rowSelector: '.fv-row',
                    eleInvalidClass: '',
                    eleValidClass: ''
                })
            }
        });

        const submitButton = element.querySelector('[data-kt-edit-class-modal-action="submit"]');
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

                        fetch(`/classnames/${classId}`, {
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
                                    setTimeout(() => window.location.reload(), 1500);
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
                        toastr.warning('Please fill all required fields correctly');
                    }
                });
            });
        }
    };

    return {
        init: function () {
            initValidation();
        }
    };
}();


// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTActiveClassList.init();
    KTInactiveClassList.init();
    KTAddClassName.init();
    KTEditClassName.init();
});