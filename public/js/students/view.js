"use strict";

var KTStudentsActions = function () {
    // Delete pending students
    const handleDeletion = function () {
        document.querySelectorAll('.delete-student').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();

                let studentId = this.getAttribute('data-student-id');
                let url = routeDeleteStudent.replace(':id', studentId);  // Replace ':id' with actual student ID

                Swal.fire({
                    title: "Are you sure to delete this student?",
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
                                        text: "The student has been removed successfully.",
                                        icon: "success",
                                    }).then(() => {
                                        window.location.href = '/students';
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

    // Toggle activation modal AJAX
    const handleToggleActivationAJAX = function () {
        const toggleButtons = document.querySelectorAll('[data-bs-target="#kt_toggle_activation_student_modal"]');

        toggleButtons.forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const studentId = this.getAttribute('data-student-id');
                const studentName = this.getAttribute('data-student-name');
                const studentUniqueId = this.getAttribute('data-student-unique-id');
                const activeStatus = this.getAttribute('data-active-status');

                // Set hidden field values
                document.getElementById('student_id').value = studentId;
                document.getElementById('activation_status').value = (activeStatus === 'active') ? 'inactive' : 'active';


                // Update modal title and label
                const modalTitle = document.getElementById('toggle-activation-modal-title');
                const reasonLabel = document.getElementById('reason_label');

                if (activeStatus === 'active') {
                    modalTitle.textContent = `Deactivate Student - ${studentName} (${studentUniqueId})`;
                    reasonLabel.textContent = 'Deactivation Reason';
                } else {
                    modalTitle.textContent = `Activate Student - ${studentName} (${studentUniqueId})`;
                    reasonLabel.textContent = 'Activation Reason';
                }
            });
        });
    }

    return {
        // Public functions  
        init: function () {
            handleDeletion();
            handleToggleActivationAJAX();
        }
    }
}();

var KTStudentsInvoicesView = function () {
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
                { orderable: false, targets: 7 }, // Disable ordering on column Guardian                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }


    var handleDeletion = function () {
        document.addEventListener('click', function (e) {
            const target = e.target.closest('.delete-invoice');
            if (!target) return;

            e.preventDefault();

            const invoiceId = target.getAttribute('data-invoice-id');
            console.log('Invoice to be deleted: ', invoiceId);

            const url = routeDeleteInvoice.replace(':id', invoiceId);  // Replace ':id' with actual invoice ID

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
                                    location.reload(); // Refresh to reflect changes
                                });
                            } else {
                                Swal.fire({
                                    title: "Error!",
                                    text: data.error || "Deletion failed.",
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
            table = document.getElementById('kt_student_view_invoices_table');

            if (!table) {
                return;
            }

            initDatatable();
            handleDeletion();
        }
    }
}();

var KTEditInvoiceModal = function () {
    // Shared variables
    const element = document.getElementById('kt_modal_edit_invoice');

    // Early return if element doesn't exist
    if (!element) {
        console.error('Modal element not found');
        return {
            init: function () { }
        };
    }

    const form = element.querySelector('#kt_modal_edit_invoice_form');
    const modal = bootstrap.Modal.getOrCreateInstance(element);

    let invoiceId = null; // Declare globally

    // Init edit invoice modal
    var initEditInvoice = () => {
        // Cancel and close button handlers
        ['cancel', 'close'].forEach(action => {
            const btn = element.querySelector(`[data-kt-edit-invoice-modal-action="${action}"]`);
            if (btn) {
                btn.addEventListener('click', e => {
                    e.preventDefault();
                    if (form) form.reset();
                    modal.hide();
                });
            }
        });

        // Delegate edit button click using document
        document.addEventListener('click', function (e) {
            const button = e.target.closest("[data-bs-target='#kt_modal_edit_invoice']");
            if (!button) return;

            invoiceId = button.getAttribute("data-invoice-id");
            if (!invoiceId) return;

            console.log("Invoice ID:", invoiceId);
            if (form) form.reset();

            fetch(`/invoices/${invoiceId}/view-ajax`)
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
                    if (!data.success || !data.data) throw new Error(data.message || 'Invalid response data');

                    const invoice = data.data;

                    // Set modal title
                    const titleEl = document.getElementById("kt_modal_edit_invoice_title");
                    if (titleEl) {
                        titleEl.textContent = `Update Invoice ${invoice.invoice_number}`;
                    }

                    // Show/hide #month_year_id_edit based on invoice_type
                    const monthYearWrapper = document.querySelector("#month_year_id_edit");
                    if (monthYearWrapper) {
                        monthYearWrapper.style.display = (invoice.invoice_type === 'tuition_fee') ? '' : 'none';
                    }

                    // Populate inputs
                    document.querySelector("input[name='invoice_amount_edit']").value = invoice.total_amount;

                    // Helper for select2 values
                    const setSelect2Value = (name, value) => {
                        const el = $(`select[name="${name}"]`);
                        if (el.length) {
                            el.val(value).trigger('change');
                        }
                    };

                    setSelect2Value("invoice_type_edit", invoice.invoice_type);

                    // Month-year handling
                    const monthYearSelect = $("select[name='invoice_month_year_edit']");
                    if (monthYearSelect.length) {
                        monthYearSelect.empty();
                        const [month, year] = (invoice.month_year || '').split('_');
                        const monthName = new Date(`${year}-${month}-01`).toLocaleString('default', { month: 'long' });

                        const formatted = `${monthName} ${year}`;
                        const option = new Option(formatted, invoice.month_year, true, true);
                        monthYearSelect.append(option).trigger('change');
                    }

                    modal.show();
                })
                .catch(error => {
                    console.error("Error:", error);
                    toastr.error(error.message || "Failed to load invoice details");
                });
        });
    };


    // Form validation
    var initValidation = function () {
        if (!form) return;

        var validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'invoice_amount_edit': {
                        validators: {
                            notEmpty: {
                                message: 'Amount is required'
                            }
                        }
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
            }
        );

        const submitButton = element.querySelector('[data-kt-edit-invoice-modal-action="submit"]');

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

                        fetch(`/invoices/${invoiceId}`, {
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
                                    toastr.success(data.message || 'Invoice updated successfully');
                                    modal.hide();
                                    window.location.reload();
                                } else {
                                    throw new Error(data.message || 'Invoice Update failed');
                                }
                            })
                            .catch(error => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                toastr.error(error.message || 'Failed to update invoice');
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
            initEditInvoice();
            initValidation();
        }
    };
}();

var KTStudentsTransactionsView = function () {
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
                { orderable: false, targets: 4 }, // Disable ordering on column Guardian                
                { orderable: false, targets: 7 }, // Disable ordering on column Guardian                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }

    return {
        // Public functions  
        init: function () {
            table = document.getElementById('kt_student_view_transactions_table');

            if (!table) {
                return;
            }

            initDatatable();
        }
    }
}();

var KTStudentsSheetsView = function () {
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
                { orderable: false, targets: 4 }, // Disable ordering on column Guardian                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }

    // Search Datatable --- official docs reference: https://datatables.net/reference/api/search()
    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-notes-distribution-table-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            datatable.search(e.target.value).draw();
        });
    }


    // Filter Datatable
    var handleFilter = function () {
        // Select filter options
        const filterForm = document.querySelector('[data-kt-notes-distribution-table-filter="form"]');
        const filterButton = filterForm.querySelector('[data-kt-notes-distribution-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-kt-notes-distribution-table-filter="reset"]');
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
            table = document.getElementById('kt_student_view_sheets_table');

            if (!table) {
                return;
            }

            initDatatable();
            handleSearch();
            handleFilter();
        }
    }
}();

var KTStudentsActivity = function () {
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
                { orderable: false, targets: 2 }, // Disable ordering on column Guardian                
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }


    return {
        // Public functions  
        init: function () {
            table = document.getElementById('kt_students_acitivation_table');

            if (!table) {
                return;
            }

            initDatatable();
        }
    }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTStudentsActions.init();
    KTStudentsInvoicesView.init();
    KTEditInvoiceModal.init();
    KTStudentsTransactionsView.init();
    KTStudentsSheetsView.init();
    KTStudentsActivity.init();
});