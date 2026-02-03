"use strict";

// Shared modal instance
var toggleActivationModal = null;

var KTStudentsActions = function () {
    // Delete pending students
    const handleDeletion = function () {
        document.querySelectorAll('.delete-student').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                let studentId = this.getAttribute('data-student-id');
                let url = routeDeleteStudent.replace(':id', studentId); // Replace ':id' with actual student ID

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

    // Initialize Toggle Activation Modal
    var initToggleActivationModal = function () {
        var modalElement = document.getElementById('kt_toggle_activation_student_modal');
        if (modalElement) {
            toggleActivationModal = new bootstrap.Modal(modalElement);
        }
    };

    // Handle toggle activation modal trigger from action dropdown/menu
    var handleToggleActivationTrigger = function () {
        document.addEventListener('click', function (e) {
            var toggleButton = e.target.closest('[data-bs-target="#kt_toggle_activation_student_modal"]');
            if (!toggleButton) return;

            e.preventDefault();

            var studentId = toggleButton.getAttribute('data-student-id');
            var studentName = toggleButton.getAttribute('data-student-name');
            var studentUniqueId = toggleButton.getAttribute('data-student-unique-id');
            var activeStatus = toggleButton.getAttribute('data-active-status');

            // Populate hidden fields
            document.getElementById('student_id').value = studentId;

            // Set the NEW status (opposite of current)
            document.getElementById('activation_status').value = (activeStatus === 'active') ? 'inactive' : 'active';

            // Update modal title and label based on current status
            var modalTitle = document.getElementById('toggle-activation-modal-title');
            var reasonLabel = document.getElementById('reason_label');
            var reasonTextarea = document.querySelector('#kt_toggle_activation_student_modal textarea[name="reason"]');

            if (activeStatus === 'active') {
                modalTitle.textContent = 'Deactivate Student - ' + studentName + ' (' + studentUniqueId + ')';
                reasonLabel.textContent = 'Deactivation Reason';
                if (reasonTextarea) {
                    reasonTextarea.placeholder = 'Write the reason for deactivating this student';
                }
            } else {
                modalTitle.textContent = 'Activate Student - ' + studentName + ' (' + studentUniqueId + ')';
                reasonLabel.textContent = 'Activation Reason';
                if (reasonTextarea) {
                    reasonTextarea.placeholder = 'Write the reason for activating this student';
                }
            }

            // Clear previous reason
            if (reasonTextarea) {
                reasonTextarea.value = '';
            }
        });
    };

    // Handle toggle activation form submission via AJAX
    var handleToggleActivationSubmit = function () {
        var toggleForm = document.querySelector('#kt_toggle_activation_student_modal form');
        if (!toggleForm) return;

        toggleForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var submitBtn = toggleForm.querySelector('button[type="submit"]');
            var originalBtnText = submitBtn.innerHTML;

            // Validate reason field
            var reasonField = toggleForm.querySelector('textarea[name="reason"]');
            if (!reasonField.value.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Reason Required',
                    text: 'Please provide a reason for this status change.',
                    buttonsStyling: false,
                    confirmButtonText: 'Ok, got it!',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                });
                reasonField.focus();
                return;
            }

            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';

            // Prepare form data
            var formData = new FormData(toggleForm);

            // Get CSRF token
            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                return;
            }

            // Send AJAX request
            fetch(toggleForm.getAttribute('action'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { status: response.status, data: data };
                    });
                })
                .then(function (result) {
                    var response = result.data;

                    // Re-enable button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;

                    if (response.success) {
                        // Close modal
                        if (toggleActivationModal) {
                            toggleActivationModal.hide();
                        }

                        // Reset form
                        toggleForm.reset();

                        // Determine action text for message
                        var newStatus = document.getElementById('activation_status').value;
                        var actionText = newStatus === 'active' ? 'activated' : 'deactivated';

                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Student has been ' + actionText + ' successfully.',
                            buttonsStyling: false,
                            confirmButtonText: 'Ok, got it!',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            }
                        }).then(function () {
                            // Reload page to reflect changes
                            location.reload();
                        });
                    } else {
                        // Show error message
                        var errorMessage = response.message || 'Something went wrong.';

                        if (response.errors) {
                            var errorList = [];
                            Object.keys(response.errors).forEach(function (key) {
                                errorList.push(response.errors[key].join(', '));
                            });
                            errorMessage = errorList.join('\n');
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage,
                            buttonsStyling: false,
                            confirmButtonText: 'Ok, got it!',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            }
                        });
                    }
                })
                .catch(function (error) {
                    console.error('Toggle activation error:', error);

                    // Re-enable button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;

                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An unexpected error occurred. Please try again.',
                        buttonsStyling: false,
                        confirmButtonText: 'Ok, got it!',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        }
                    });
                });
        });
    };

    // Handle modal cancel/close button
    var handleModalClose = function () {
        var modalElement = document.getElementById('kt_toggle_activation_student_modal');
        if (!modalElement) return;

        var cancelButton = modalElement.querySelector('button[type="reset"]');
        var closeButton = modalElement.querySelector('[data-bs-dismiss="modal"]');
        var toggleForm = modalElement.querySelector('form');

        // Handle cancel button click
        if (cancelButton) {
            cancelButton.addEventListener('click', function (e) {
                e.preventDefault();
                if (toggleForm) {
                    toggleForm.reset();
                }
                if (toggleActivationModal) {
                    toggleActivationModal.hide();
                }
            });
        }

        // Reset form when modal is hidden
        modalElement.addEventListener('hidden.bs.modal', function () {
            if (toggleForm) {
                toggleForm.reset();
            }
        });
    };

    return {
        // Public functions
        init: function () {
            handleDeletion();
            initToggleActivationModal();
            handleToggleActivationTrigger();
            handleToggleActivationSubmit();
            handleModalClose();
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
            "autoWidth": false, // Disable auto width
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
            console.log('Invoice to be deleted:', invoiceId);

            const url = routeDeleteInvoice.replace(':id', invoiceId); // Replace ':id' with actual invoice ID

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
    var element;
    var form;
    var modal;
    var submitButton;
    var validator;
    var invoiceId = null;

    // Helper function to get CSRF token
    var getCsrfToken = function () {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    };

    // Helper function to format month year for display
    var formatMonthYear = function (monthYear) {
        if (!monthYear) return '';

        const [month, year] = monthYear.split('_');
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];

        return `${monthNames[parseInt(month) - 1]} ${year}`;
    };

    // Handle edit button click
    var handleEditClick = function () {
        document.addEventListener('click', function (e) {
            const button = e.target.closest("[data-bs-target='#kt_modal_edit_invoice']");
            if (!button) return;

            invoiceId = button.getAttribute('data-invoice-id');
            if (!invoiceId) return;

            // Clear form
            if (form) form.reset();

            // Fetch invoice data
            fetch(`/invoices/${invoiceId}/view-ajax`, {
                method: 'GET',
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
                    if (!data.success || !data.data) {
                        throw new Error(data.message || 'Invalid response data');
                    }

                    const invoice = data.data;

                    // Set modal title
                    const titleEl = document.getElementById('kt_modal_edit_invoice_title');
                    if (titleEl) {
                        titleEl.textContent = `Update Invoice ${invoice.invoice_number}`;
                    }

                    // Get the type name from the response
                    const invoiceTypeName = invoice.invoice_type_name || '';

                    // Show/hide month year wrapper based on type name
                    const monthYearWrapper = document.getElementById('month_year_id_edit');
                    if (monthYearWrapper) {
                        monthYearWrapper.style.display = invoiceTypeName === 'Tuition Fee' ? '' : 'none';
                    }

                    // Set invoice amount
                    const amountInput = element.querySelector("input[name='invoice_amount_edit']");
                    if (amountInput) {
                        amountInput.value = invoice.total_amount;
                    }

                    // Set student select (using Select2)
                    const studentSelect = $("select[name='invoice_student_edit']");
                    if (studentSelect.length) {
                        studentSelect.val(invoice.student_id).trigger('change');
                    }

                    // Set invoice type select (using Select2)
                    const typeSelect = $("select[name='invoice_type_edit']");
                    if (typeSelect.length) {
                        typeSelect.val(invoice.invoice_type_id).trigger('change');
                    }

                    // Set month_year field
                    const monthYearSelect = $("select[name='invoice_month_year_edit']");
                    if (monthYearSelect.length) {
                        monthYearSelect.empty();
                        const formattedMonthYear = formatMonthYear(invoice.month_year);
                        const option = new Option(formattedMonthYear, invoice.month_year, true, true);
                        monthYearSelect.append(option).trigger('change');
                    }

                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error(error.message || 'Failed to load invoice details');
                });
        });
    };

    // Handle modal close/cancel
    var handleModalClose = function () {
        const cancelButton = element.querySelector('[data-kt-edit-invoice-modal-action="cancel"]');
        const closeButton = element.querySelector('[data-kt-edit-invoice-modal-action="close"]');

        if (cancelButton) {
            cancelButton.addEventListener('click', function (e) {
                e.preventDefault();
                if (form) form.reset();
                if (validator) validator.resetForm();
                modal.hide();
            });
        }

        if (closeButton) {
            closeButton.addEventListener('click', function (e) {
                e.preventDefault();
                if (form) form.reset();
                if (validator) validator.resetForm();
                modal.hide();
            });
        }

        // Reset form when modal is hidden
        element.addEventListener('hidden.bs.modal', function () {
            if (form) form.reset();
            if (validator) validator.resetForm();
        });
    };

    // Initialize form validation
    var initValidation = function () {
        if (!form) return;

        validator = FormValidation.formValidation(
            form,
            {
                fields: {
                    'invoice_amount_edit': {
                        validators: {
                            notEmpty: {
                                message: 'Amount is required'
                            },
                            greaterThan: {
                                min: 50,
                                message: 'Amount must be at least 50'
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
    };

    // Handle form submission via AJAX
    var handleFormSubmit = function () {
        submitButton = element.querySelector('[data-kt-edit-invoice-modal-action="submit"]');
        if (!submitButton) return;

        submitButton.addEventListener('click', function (e) {
            e.preventDefault();

            if (validator) {
                validator.validate().then(function (status) {
                    if (status === 'Valid') {
                        // Show loading indicator
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        submitButton.disabled = true;

                        // Prepare form data
                        const formData = new FormData(form);
                        formData.append('_token', getCsrfToken());
                        formData.append('_method', 'PUT');

                        // Submit via AJAX
                        fetch(`/invoices/${invoiceId}`, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(response => {
                                return response.json().then(data => {
                                    if (!response.ok) {
                                        // Handle validation errors
                                        if (response.status === 422 && data.errors) {
                                            let errorMessages = [];
                                            Object.keys(data.errors).forEach(key => {
                                                errorMessages.push(data.errors[key][0]);
                                            });
                                            throw new Error(errorMessages.join('<br>'));
                                        }
                                        throw new Error(data.message || 'Something went wrong');
                                    }
                                    return data;
                                });
                            })
                            .then(data => {
                                // Hide loading indicator
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;

                                if (data.success) {
                                    // Show success message
                                    Swal.fire({
                                        text: data.message || 'Invoice updated successfully!',
                                        icon: 'success',
                                        buttonsStyling: false,
                                        confirmButtonText: 'Ok, got it!',
                                        customClass: {
                                            confirmButton: 'btn btn-primary'
                                        }
                                    }).then(function (result) {
                                        if (result.isConfirmed) {
                                            modal.hide();
                                            // Reload the page
                                            window.location.reload();
                                        }
                                    });
                                } else {
                                    throw new Error(data.message || 'Failed to update invoice');
                                }
                            })
                            .catch(error => {
                                // Hide loading indicator
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;

                                // Show error message
                                Swal.fire({
                                    html: error.message || 'Something went wrong. Please try again.',
                                    icon: 'error',
                                    buttonsStyling: false,
                                    confirmButtonText: 'Ok, got it!',
                                    customClass: {
                                        confirmButton: 'btn btn-primary'
                                    }
                                });
                            });
                    } else {
                        // Show validation error message
                        Swal.fire({
                            text: 'Please fill all required fields correctly.',
                            icon: 'warning',
                            buttonsStyling: false,
                            confirmButtonText: 'Ok, got it!',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            }
                        });
                    }
                });
            }
        });
    };

    return {
        init: function () {
            element = document.getElementById('kt_modal_edit_invoice');
            if (!element) {
                return;
            }

            form = element.querySelector('#kt_modal_edit_invoice_form');
            modal = bootstrap.Modal.getOrCreateInstance(element);

            handleEditClick();
            handleModalClose();
            initValidation();
            handleFormSubmit();
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
            "autoWidth": false, // Disable auto width
            'columnDefs': [
                { orderable: false, targets: 7 }, // Disable ordering on column Guardian
            ]
        });

        // Re-init functions on every table re-draw -- more info: https://datatables.net/reference/event/draw
        datatable.on('draw', function () {

        });
    }

    // Delete Transaction
    const handleDeletion = function () {
        document.addEventListener('click', function (e) {
            const deleteBtn = e.target.closest('.delete-txn');
            if (!deleteBtn) return;

            e.preventDefault();

            let txnId = deleteBtn.getAttribute('data-txn-id');
            console.log('TXN ID:', txnId);
            let url = routeDeleteTxn.replace(':id', txnId);

            Swal.fire({
                title: 'Are you sure you want to delete?',
                text: "Once deleted, this transaction will be removed.",
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
                                    text: 'Transaction deleted successfully.',
                                    icon: 'success',
                                    confirmButtonText: 'Okay',
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Failed!', 'Transaction could not be deleted.', 'error');
                            }
                        })
                        .catch(error => {
                            console.error("Fetch Error:", error);
                            Swal.fire('Failed!', 'An error occurred. Please contact support.', 'error');
                        });
                }
            });
        });
    };

    // Transaction approval AJAX
    const handleTransactionApproval = function () {
        document.querySelectorAll('.approve-txn').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                let txnId = this.getAttribute('data-txn-id');
                console.log("TXN ID:", txnId);

                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to approve this transaction?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, approve!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/transactions/${txnId}/approve`, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: "Approved!",
                                        text: "Transaction approved successfully.",
                                        icon: "success",
                                    }).then(() => {
                                        location.reload(); // Reload to reflect changes
                                    });
                                } else {
                                    Swal.fire({
                                        title: "Error!",
                                        text: data.message,
                                        icon: "warning",
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

    // Statement Download Handler
    const handleStatementDownload = function () {
        document.addEventListener('click', function (e) {
            const downloadBtn = e.target.closest('.download-statement');
            if (!downloadBtn) return;

            e.preventDefault();

            const studentId = downloadBtn.getAttribute('data-student-id');
            const year = downloadBtn.getAttribute('data-year');
            const invoiceId = downloadBtn.getAttribute('data-invoice-id');


            if (!studentId || !year) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Missing student or year information.',
                    icon: 'error',
                });
                return;
            }

            // Show loading state on button
            const originalIcon = downloadBtn.innerHTML;
            downloadBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            downloadBtn.style.pointerEvents = 'none';

            // Create FormData for POST request
            const formData = new FormData();
            formData.append('student_id', studentId);
            formData.append('statement_year', year);
            formData.append('invoice_id', invoiceId);

            fetch(routeDownloadStatement, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        // Try to parse error message from response
                        return response.text().then(text => {
                            throw new Error(text || 'Server error occurred');
                        });
                    }
                    return response.text();
                })
                .then(html => {
                    // Create a new window with the HTML content
                    const printWindow = window.open("", "_blank", "width=900,height=700,scrollbars=yes,resizable=yes");

                    if (printWindow) {
                        printWindow.document.open();
                        printWindow.document.write(html);
                        printWindow.document.close();

                        // Focus on the new window
                        printWindow.focus();
                    } else {
                        // Popup blocked
                        Swal.fire({
                            title: 'Popup Blocked!',
                            text: 'Please allow popups for this website to view the statement.',
                            icon: 'warning',
                        });
                    }

                    // Restore button state
                    downloadBtn.innerHTML = originalIcon;
                    downloadBtn.style.pointerEvents = 'auto';
                })
                .catch(error => {
                    console.error("Statement Download Error:", error);

                    // Check if the error message indicates no transactions
                    const errorMessage = error.message.toLowerCase();
                    if (errorMessage.includes('no transactions')) {
                        Swal.fire({
                            title: 'No Data Found',
                            text: 'No transactions found for the selected year.',
                            icon: 'info',
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to load statement. Please try again.',
                            icon: 'error',
                        });
                    }

                    // Restore button state
                    downloadBtn.innerHTML = originalIcon;
                    downloadBtn.style.pointerEvents = 'auto';
                });
        });
    };

    return {
        // Public functions
        init: function () {
            table = document.getElementById('kt_student_view_transactions_table');

            if (!table) {
                return;
            }

            initDatatable();
            handleDeletion();
            handleTransactionApproval();
            handleStatementDownload();
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
            "autoWidth": false, // Disable auto width
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
    var activationTable;
    var classChangeTable;
    var secondaryClassTable;

    // Private functions
    var initActivationDatatable = function () {
        var table = document.getElementById('kt_students_acitivation_table');
        if (!table) return;

        // Init datatable --- more info on datatables: https://datatables.net/manual/
        $(table).DataTable({
            "info": true,
            'order': [],
            "lengthMenu": [10, 25, 50, 100],
            "pageLength": 10,
            "lengthChange": true,
            "autoWidth": false,
            'columnDefs': [
                { orderable: false, targets: 2 },
            ]
        });
    }

    var initClassChangeHistoryDatatable = function () {
        var table = document.getElementById('kt_students_class_change_history_table');
        if (!table) return;

        // Init datatable
        $(table).DataTable({
            "info": true,
            'order': [],
            "lengthMenu": [10, 25, 50, 100],
            "pageLength": 10,
            "lengthChange": true,
            "autoWidth": false,
            'columnDefs': [
                { orderable: false, targets: [1, 2] },
            ]
        });
    }

    var initSecondaryClassHistoryDatatable = function () {
        var table = document.getElementById('kt_students_secondary_class_history_table');
        if (!table) return;

        // Init datatable
        $(table).DataTable({
            "info": true,
            'order': [],
            "lengthMenu": [10, 25, 50, 100],
            "pageLength": 10,
            "lengthChange": true,
            "autoWidth": false,
            'columnDefs': [
                { orderable: false, targets: [1, 2] },
            ]
        });
    }

    return {
        // Public functions
        init: function () {
            initActivationDatatable();
            initClassChangeHistoryDatatable();
            initSecondaryClassHistoryDatatable();
        }
    }
}();

var KTStudentViewAttendance = function () {
    // Shared variables
    var calendar;
    var calendarEl;

    // --- 1. Calendar Logic ---
    var initCalendar = function () {
        calendarEl = document.getElementById('kt_attendance_calendar');
        if (!calendarEl) {
            return;
        }

        var eventsData = JSON.parse(calendarEl.getAttribute('data-events'));

        calendar = new FullCalendar.Calendar(calendarEl, {
            // Define header toolbar
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listMonth'
            },
            // --- Custom View Settings ---
            views: {
                listMonth: {
                    buttonText: 'List',
                    displayEventTime: false, // Hides "All day" text
                    listDayFormat: false,    // Hides default DateHeader rows (optional, keeps list clean)
                    listDaySideFormat: false // Hides side headers
                }
            },
            initialView: 'dayGridMonth',
            height: 'auto',
            contentHeight: 650,
            aspectRatio: 3,
            initialDate: new Date(),
            navLinks: true,
            editable: false,
            dayMaxEvents: true,
            events: eventsData,

            // Tooltip Logic (Works for both Grid and List views)
            eventDidMount: function (info) {
                var remarks = info.event.extendedProps.description;
                if (remarks) {
                    new bootstrap.Tooltip(info.el, {
                        title: remarks,
                        placement: 'top',
                        trigger: 'hover',
                        container: 'body'
                    });
                }
            },

            // --- CUSTOM CONTENT RENDERING ---
            eventContent: function (arg) {
                // A. Logic for LIST View (Show Date + Status)
                if (arg.view.type === 'listMonth') {
                    // 1. Format Date manually to 07-Dec-2025
                    var dateObj = arg.event.start;
                    var day = dateObj.getDate().toString().padStart(2, '0');
                    var month = dateObj.toLocaleString('en-US', { month: 'short' });
                    var year = dateObj.getFullYear();
                    var formattedDate = day + '-' + month + '-' + year;

                    // 2. Return Custom HTML layout
                    return {
                        html: `
                            <div class="d-flex align-items-center">
                                <span class="min-w-100px fw-bold text-gray-800 fs-6 me-4">${formattedDate}</span>
                                <span class="badge" style="background-color: ${arg.event.backgroundColor}; color: white; font-size: 0.9rem;">
                                    ${arg.event.title}
                                </span>
                            </div>
                        `
                    };
                }

                // B. Logic for GRID View (Standard Month View)
                return {
                    html: '<div class="fc-content" style="color: white; padding: 1px 2px;">' + arg.event.title + '</div>'
                };
            }
        });

        calendar.render();
    }

    // --- 2. Pie Chart Logic ---
    var initPieChart = function () {
        var wrapper = document.getElementById('kt_attendance_pie_chart_wrapper');
        var canvas = document.getElementById('kt_attendance_pie_chart');

        if (!wrapper || !canvas) {
            return;
        }

        // 1. Get Data
        var eventsData = JSON.parse(wrapper.getAttribute('data-events'));

        // 2. Filter Data for Current Month
        var now = new Date();
        var currentMonth = now.getMonth();
        var currentYear = now.getFullYear();

        var stats = { present: 0, absent: 0, late: 0, others: 0 };

        eventsData.forEach(function (event) {
            var eventDate = new Date(event.start);
            if (eventDate.getMonth() === currentMonth && eventDate.getFullYear() === currentYear) {
                var status = event.title.toLowerCase();
                if (status === 'present') stats.present++;
                else if (status === 'absent') stats.absent++;
                else if (status === 'late') stats.late++;
                else stats.others++;
            }
        });

        // 3. Define Chart Data
        var data = {
            labels: ['Present', 'Absent', 'Late'],
            datasets: [{
                data: [stats.present, stats.absent, stats.late, stats.others],
                backgroundColor: ['#50cd89', '#f1416c', '#ffc700'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        };

        // 4. Render Chart
        var ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: data,
            // Register the DataLabels plugin here
            plugins: [ChartDataLabels],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 13
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                var value = context.raw || 0;
                                return context.label + ': ' + value + ' days';
                            }
                        }
                    },
                    // --- DATA LABELS CONFIGURATION (Percentage on Chart) ---
                    datalabels: {
                        color: '#ffffff', // White text
                        font: {
                            weight: 'bold',
                            size: 14
                        },
                        formatter: function (value, context) {
                            // Hide label if value is 0
                            if (value === 0) return null;

                            // Calculate Total
                            var dataset = context.chart.data.datasets[0].data;
                            var total = dataset.reduce((acc, val) => acc + val, 0);

                            // Calculate Percentage
                            var percentage = Math.round((value / total) * 100) + '%';
                            return percentage;
                        }
                    }
                }
            }
        });
    }

    // --- 3. Handle Tab Logic ---
    var handleTabSwitch = function () {
        var tabLink = document.querySelector('a[href="#kt_student_view_attendance_tab"]');
        if (!tabLink) {
            tabLink = document.querySelector('button[data-bs-target="#kt_student_view_attendance_tab"]');
        }

        if (tabLink) {
            tabLink.addEventListener('shown.bs.tab', function (e) {
                if (calendar) {
                    calendar.updateSize();
                }
            });
        }
    }

    return {
        init: function () {
            initCalendar();
            initPieChart();
            handleTabSwitch();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTStudentsActions.init();
    KTStudentsInvoicesView.init();
    KTEditInvoiceModal.init();
    KTStudentsTransactionsView.init();
    KTStudentsSheetsView.init();
    KTStudentsActivity.init();
    KTStudentViewAttendance.init();
});