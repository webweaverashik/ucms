"use strict";

var KTDueInvoicesList = function () {
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
                { orderable: false, targets: 13 }, // Disable ordering on column Actions                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }


    // Hook export buttons
    var exportButtonsDue = function () {
        const documentTitle = 'Due Invoices Report';

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
        const filterSearch = document.querySelector('[data-kt-due-invoice-table-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            datatable.search(e.target.value).draw();
        });
    }

    // Filter Datatable
    var handleFilter = function () {
        // Select filter options
        const filterForm = document.querySelector('[data-kt-due-invoice-table-filter="form"]');
        const filterButton = filterForm.querySelector('[data-kt-due-invoice-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-kt-due-invoice-table-filter="reset"]');
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
            const deleteBtn = e.target.closest('.delete-invoice');
            if (!deleteBtn) return;

            e.preventDefault();

            const invoiceId = deleteBtn.getAttribute('data-invoice-id');
            const url = routeDeleteInvoice.replace(':id', invoiceId);

            Swal.fire({
                title: "Are you sure to delete this invoice?",
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
                                    text: "The invoice has been deleted successfully.",
                                    icon: "success",
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: "Error!",
                                    text: data.error || "Something went wrong.",
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
    };


    return {
        // Public functions  
        init: function () {
            table = document.getElementById('kt_due_invoices_table');

            if (!table) {
                return;
            }

            initDatatable();
            exportButtonsDue();
            handleSearch();
            handleFilter();
            handleDeletion();
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