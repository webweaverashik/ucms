"use strict";

// =========================================================================
// Shared state
// =========================================================================
var toggleActivationModal = null;

// =========================================================================
// KTStudentsActions — Delete / Toggle Activation
// =========================================================================
var KTStudentsActions = function () {

    const handleDeletion = function () {
        document.querySelectorAll('.delete-student').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                let studentId = this.getAttribute('data-student-id');
                let url = routeDeleteStudent.replace(':id', studentId);

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
                                    Swal.fire({ title: "Error!", text: data.message, icon: "error" });
                                }
                            })
                            .catch(() => {
                                Swal.fire({ title: "Error!", text: "Something went wrong. Please try again.", icon: "error" });
                            });
                    }
                });
            });
        });
    };

    var initToggleActivationModal = function () {
        var modalElement = document.getElementById('kt_toggle_activation_student_modal');
        if (modalElement) {
            toggleActivationModal = new bootstrap.Modal(modalElement);
        }
    };

    var handleToggleActivationTrigger = function () {
        document.addEventListener('click', function (e) {
            var toggleButton = e.target.closest('[data-bs-target="#kt_toggle_activation_student_modal"]');
            if (!toggleButton) return;

            e.preventDefault();

            var studentId = toggleButton.getAttribute('data-student-id');
            var studentName = toggleButton.getAttribute('data-student-name');
            var studentUniqueId = toggleButton.getAttribute('data-student-unique-id');
            var activeStatus = toggleButton.getAttribute('data-active-status');

            document.getElementById('student_id').value = studentId;
            document.getElementById('activation_status').value = (activeStatus === 'active') ? 'inactive' : 'active';

            var modalTitle = document.getElementById('toggle-activation-modal-title');
            var reasonLabel = document.getElementById('reason_label');
            var reasonTextarea = document.querySelector('#kt_toggle_activation_student_modal textarea[name="reason"]');

            if (activeStatus === 'active') {
                modalTitle.textContent = 'Deactivate Student - ' + studentName + ' (' + studentUniqueId + ')';
                reasonLabel.textContent = 'Deactivation Reason';
                if (reasonTextarea) reasonTextarea.placeholder = 'Write the reason for deactivating this student';
            } else {
                modalTitle.textContent = 'Activate Student - ' + studentName + ' (' + studentUniqueId + ')';
                reasonLabel.textContent = 'Activation Reason';
                if (reasonTextarea) reasonTextarea.placeholder = 'Write the reason for activating this student';
            }

            if (reasonTextarea) reasonTextarea.value = '';
        });
    };

    var handleToggleActivationSubmit = function () {
        var toggleForm = document.querySelector('#kt_toggle_activation_student_modal form');
        if (!toggleForm) return;

        toggleForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var submitBtn = toggleForm.querySelector('button[type="submit"]');
            var originalBtnText = submitBtn.innerHTML;
            var reasonField = toggleForm.querySelector('textarea[name="reason"]');

            if (!reasonField.value.trim()) {
                Swal.fire({
                    icon: 'warning', title: 'Reason Required',
                    text: 'Please provide a reason for this status change.',
                    buttonsStyling: false, confirmButtonText: 'Ok, got it!',
                    customClass: { confirmButton: 'btn btn-primary' }
                });
                reasonField.focus();
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...';

            var formData = new FormData(toggleForm);
            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                return;
            }

            fetch(toggleForm.getAttribute('action'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json().then(data => ({ status: response.status, data })))
                .then(function (result) {
                    var response = result.data;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;

                    if (response.success) {
                        if (toggleActivationModal) toggleActivationModal.hide();
                        toggleForm.reset();

                        var newStatus = document.getElementById('activation_status').value;
                        var actionText = newStatus === 'active' ? 'activated' : 'deactivated';

                        Swal.fire({
                            icon: 'success', title: 'Success!',
                            text: response.message || 'Student has been ' + actionText + ' successfully.',
                            buttonsStyling: false, confirmButtonText: 'Ok, got it!',
                            customClass: { confirmButton: 'btn btn-primary' }
                        }).then(() => location.reload());
                    } else {
                        var errorMessage = response.message || 'Something went wrong.';
                        if (response.errors) {
                            errorMessage = Object.values(response.errors).map(e => e.join(', ')).join('\n');
                        }
                        Swal.fire({
                            icon: 'error', title: 'Error!', text: errorMessage,
                            buttonsStyling: false, confirmButtonText: 'Ok, got it!',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
                    }
                })
                .catch(function () {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                    Swal.fire({
                        icon: 'error', title: 'Error!',
                        text: 'An unexpected error occurred. Please try again.',
                        buttonsStyling: false, confirmButtonText: 'Ok, got it!',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                });
        });
    };

    var handleModalClose = function () {
        var modalElement = document.getElementById('kt_toggle_activation_student_modal');
        if (!modalElement) return;

        var cancelButton = modalElement.querySelector('button[type="reset"]');
        var closeButton = modalElement.querySelector('[data-bs-dismiss="modal"]');
        var toggleForm = modalElement.querySelector('form');

        if (cancelButton) {
            cancelButton.addEventListener('click', function (e) {
                e.preventDefault();
                if (toggleForm) toggleForm.reset();
                if (toggleActivationModal) toggleActivationModal.hide();
            });
        }

        modalElement.addEventListener('hidden.bs.modal', function () {
            if (toggleForm) toggleForm.reset();
        });
    };

    return {
        init: function () {
            handleDeletion();
            initToggleActivationModal();
            handleToggleActivationTrigger();
            handleToggleActivationSubmit();
            handleModalClose();
        }
    };
}();


// =========================================================================
// KTStudentsInvoicesView — Invoices DataTable + Delete
// =========================================================================
var KTStudentsInvoicesView = function () {
    var table;
    var datatable;

    var initDatatable = function () {
        datatable = $(table).DataTable({
            info: true,
            order: [],
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            lengthChange: true,
            autoWidth: false,
            columnDefs: [{ orderable: false, targets: 7 }]
        });
    };

    var handleDeletion = function () {
        document.addEventListener('click', function (e) {
            const target = e.target.closest('.delete-invoice');
            if (!target) return;

            e.preventDefault();

            const invoiceId = target.getAttribute('data-invoice-id');
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
                                Swal.fire({ title: "Deleted!", text: "The invoice has been deleted successfully.", icon: "success" })
                                    .then(() => location.reload());
                            } else {
                                Swal.fire({ title: "Error!", text: data.error || "Deletion failed.", icon: "error" });
                            }
                        })
                        .catch(() => {
                            Swal.fire({ title: "Error!", text: "Something went wrong. Please try again.", icon: "error" });
                        });
                }
            });
        });
    };

    return {
        init: function () {
            table = document.getElementById('kt_student_view_invoices_table');
            if (!table) return;
            initDatatable();
            handleDeletion();
        }
    };
}();


// =========================================================================
// KTEditInvoiceModal — Edit Invoice Modal
// =========================================================================
var KTEditInvoiceModal = function () {
    var element;
    var form;
    var modal;
    var submitButton;
    var validator;
    var invoiceId = null;

    var getCsrfToken = function () {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    };

    var formatMonthYear = function (monthYear) {
        if (!monthYear) return '';
        const [month, year] = monthYear.split('_');
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        return `${monthNames[parseInt(month) - 1]} ${year}`;
    };

    var handleEditClick = function () {
        document.addEventListener('click', function (e) {
            const button = e.target.closest("[data-bs-target='#kt_modal_edit_invoice']");
            if (!button) return;

            invoiceId = button.getAttribute('data-invoice-id');
            if (!invoiceId) return;

            if (form) form.reset();

            fetch(`/invoices/${invoiceId}/view-ajax`, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (!data.success || !data.data) throw new Error(data.message || 'Invalid response data');

                    const invoice = data.data;
                    const invoiceTypeName = invoice.invoice_type_name || '';

                    const titleEl = document.getElementById('kt_modal_edit_invoice_title');
                    if (titleEl) titleEl.textContent = `Update Invoice ${invoice.invoice_number}`;

                    const monthYearWrapper = document.getElementById('month_year_id_edit');
                    if (monthYearWrapper) {
                        monthYearWrapper.style.display = invoiceTypeName === 'Tuition Fee' ? '' : 'none';
                    }

                    const amountInput = element.querySelector("input[name='invoice_amount_edit']");
                    if (amountInput) amountInput.value = invoice.total_amount;

                    const studentSelect = $("select[name='invoice_student_edit']");
                    if (studentSelect.length) studentSelect.val(invoice.student_id).trigger('change');

                    const typeSelect = $("select[name='invoice_type_edit']");
                    if (typeSelect.length) typeSelect.val(invoice.invoice_type_id).trigger('change');

                    const monthYearSelect = $("select[name='invoice_month_year_edit']");
                    if (monthYearSelect.length) {
                        monthYearSelect.empty();
                        const option = new Option(formatMonthYear(invoice.month_year), invoice.month_year, true, true);
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

        element.addEventListener('hidden.bs.modal', function () {
            if (form) form.reset();
            if (validator) validator.resetForm();
        });
    };

    var initValidation = function () {
        if (!form) return;
        validator = FormValidation.formValidation(form, {
            fields: {
                'invoice_amount_edit': {
                    validators: {
                        notEmpty: { message: 'Amount is required' },
                        greaterThan: { min: 50, message: 'Amount must be at least 50' }
                    }
                }
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap: new FormValidation.plugins.Bootstrap5({
                    rowSelector: '.fv-row', eleInvalidClass: '', eleValidClass: ''
                })
            }
        });
    };

    var handleFormSubmit = function () {
        submitButton = element.querySelector('[data-kt-edit-invoice-modal-action="submit"]');
        if (!submitButton) return;

        submitButton.addEventListener('click', function (e) {
            e.preventDefault();

            if (validator) {
                validator.validate().then(function (status) {
                    if (status === 'Valid') {
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        submitButton.disabled = true;

                        const formData = new FormData(form);
                        formData.append('_token', getCsrfToken());
                        formData.append('_method', 'PUT');

                        fetch(`/invoices/${invoiceId}`, {
                            method: 'POST',
                            body: formData,
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        })
                            .then(response => {
                                return response.json().then(data => {
                                    if (!response.ok) {
                                        if (response.status === 422 && data.errors) {
                                            throw new Error(Object.values(data.errors).map(e => e[0]).join('<br>'));
                                        }
                                        throw new Error(data.message || 'Something went wrong');
                                    }
                                    return data;
                                });
                            })
                            .then(data => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;

                                if (data.success) {
                                    Swal.fire({
                                        text: data.message || 'Invoice updated successfully!', icon: 'success',
                                        buttonsStyling: false, confirmButtonText: 'Ok, got it!',
                                        customClass: { confirmButton: 'btn btn-primary' }
                                    }).then(result => {
                                        if (result.isConfirmed) { modal.hide(); window.location.reload(); }
                                    });
                                } else {
                                    throw new Error(data.message || 'Failed to update invoice');
                                }
                            })
                            .catch(error => {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                Swal.fire({
                                    html: error.message || 'Something went wrong.', icon: 'error',
                                    buttonsStyling: false, confirmButtonText: 'Ok, got it!',
                                    customClass: { confirmButton: 'btn btn-primary' }
                                });
                            });
                    } else {
                        Swal.fire({
                            text: 'Please fill all required fields correctly.', icon: 'warning',
                            buttonsStyling: false, confirmButtonText: 'Ok, got it!',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
                    }
                });
            }
        });
    };

    return {
        init: function () {
            element = document.getElementById('kt_modal_edit_invoice');
            if (!element) return;

            form = element.querySelector('#kt_modal_edit_invoice_form');
            modal = bootstrap.Modal.getOrCreateInstance(element);

            handleEditClick();
            handleModalClose();
            initValidation();
            handleFormSubmit();
        }
    };
}();


// =========================================================================
// KTStudentsTransactionsView — Transactions DataTable + Actions
// =========================================================================
var KTStudentsTransactionsView = function () {
    var table;
    var datatable;

    var initDatatable = function () {
        datatable = $(table).DataTable({
            info: true,
            order: [],
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            lengthChange: true,
            autoWidth: false,
            columnDefs: [{ orderable: false, targets: 7 }]
        });
    };

    var handleTransactionDeletion = function () {
        document.addEventListener("click", function (e) {
            const deleteBtn = e.target.closest(".delete-txn");
            if (!deleteBtn) return;

            e.preventDefault();

            let txnId = deleteBtn.getAttribute("data-txn-id");
            let isApproved = deleteBtn.getAttribute("data-is-approved") === "1";
            let url = routeDeleteTxn.replace(":id", txnId);

            let warningTitle = "Are you sure you want to delete?";
            let warningText = "Once deleted, this unapproved transaction will be removed.";

            if (isApproved) {
                warningTitle = "Delete Successful Transaction?";
                warningText = "This transaction has been successful. Deleting it will:\n\n"
                    + "• Reverse the wallet collection\n"
                    + "• Restore the invoice amount due\n"
                    + "• Create an adjustment log\n"
                    + "• Decrease the collector's total collected amount\n\n"
                    + "Note: Approved transactions can only be deleted within 24 hours.\n\n"
                    + "This action cannot be undone.";
            }

            Swal.fire({
                title: warningTitle, text: warningText, icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33", cancelButtonColor: "#6c757d",
                confirmButtonText: isApproved ? "Yes, delete and reverse" : "Yes, delete it",
                cancelButtonText: "Cancel",
                customClass: { popup: 'swal-wide' }
            }).then((result) => {
                if (result.isConfirmed) {
                    const originalContent = deleteBtn.innerHTML;
                    deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
                    deleteBtn.style.pointerEvents = 'none';

                    fetch(url, {
                        method: "DELETE",
                        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({ title: "Deleted!", text: data.message || "Transaction deleted.", icon: "success" })
                                    .then(() => window.location.reload());
                            } else {
                                deleteBtn.innerHTML = originalContent;
                                deleteBtn.style.pointerEvents = 'auto';
                                Swal.fire({ title: "Failed!", text: data.message || "Could not delete.", icon: "error" });
                            }
                        })
                        .catch(() => {
                            deleteBtn.innerHTML = originalContent;
                            deleteBtn.style.pointerEvents = 'auto';
                            Swal.fire({ title: "Error!", text: "An error occurred. Please try again.", icon: "error" });
                        });
                }
            });
        });
    };

    const handleTransactionApproval = function () {
        document.querySelectorAll('.approve-txn').forEach(item => {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                let txnId = this.getAttribute('data-txn-id');

                Swal.fire({
                    title: 'Are you sure?', text: "Do you want to approve this transaction?", icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33',
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
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({ title: "Approved!", text: "Transaction approved successfully.", icon: "success" })
                                        .then(() => location.reload());
                                } else {
                                    Swal.fire({ title: "Error!", text: data.message, icon: "warning" });
                                }
                            })
                            .catch(() => {
                                Swal.fire({ title: "Error!", text: "Something went wrong.", icon: "error" });
                            });
                    }
                });
            });
        });
    };

    const handleStatementDownload = function () {
        document.addEventListener('click', function (e) {
            const downloadBtn = e.target.closest('.download-statement');
            if (!downloadBtn) return;

            e.preventDefault();

            const studentId = downloadBtn.getAttribute('data-student-id');
            const year = downloadBtn.getAttribute('data-year');
            const invoiceId = downloadBtn.getAttribute('data-invoice-id');

            if (!studentId || !year) {
                Swal.fire({ title: 'Error!', text: 'Missing student or year information.', icon: 'error' });
                return;
            }

            const originalIcon = downloadBtn.innerHTML;
            downloadBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            downloadBtn.style.pointerEvents = 'none';

            const formData = new FormData();
            formData.append('student_id', studentId);
            formData.append('statement_year', year);
            formData.append('invoice_id', invoiceId);

            fetch(routeDownloadStatement, {
                method: "POST",
                headers: { "X-CSRF-TOKEN": csrfToken },
                body: formData
            })
                .then(response => {
                    if (!response.ok) return response.text().then(t => { throw new Error(t || 'Server error'); });
                    return response.text();
                })
                .then(html => {
                    const printWindow = window.open("", "_blank", "width=900,height=700,scrollbars=yes,resizable=yes");
                    if (printWindow) {
                        printWindow.document.open();
                        printWindow.document.write(html);
                        printWindow.document.close();
                        printWindow.focus();
                    } else {
                        Swal.fire({ title: 'Popup Blocked!', text: 'Please allow popups for this site.', icon: 'warning' });
                    }
                    downloadBtn.innerHTML = originalIcon;
                    downloadBtn.style.pointerEvents = 'auto';
                })
                .catch(error => {
                    const msg = error.message.toLowerCase();
                    if (msg.includes('no transactions')) {
                        Swal.fire({ title: 'No Data Found', text: 'No transactions found for the selected year.', icon: 'info' });
                    } else {
                        Swal.fire({ title: 'Error!', text: 'Failed to load statement. Please try again.', icon: 'error' });
                    }
                    downloadBtn.innerHTML = originalIcon;
                    downloadBtn.style.pointerEvents = 'auto';
                });
        });
    };

    return {
        init: function () {
            table = document.getElementById('kt_student_view_transactions_table');
            if (!table) return;
            initDatatable();
            handleTransactionDeletion();
            handleTransactionApproval();
            handleStatementDownload();
        }
    };
}();


// =========================================================================
// KTStudentsSheetsView — Sheets DataTable + Search + Filter
// =========================================================================
var KTStudentsSheetsView = function () {
    var table;
    var datatable;

    var initDatatable = function () {
        datatable = $(table).DataTable({
            info: true,
            order: [],
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            lengthChange: true,
            autoWidth: false,
            columnDefs: [{ orderable: false, targets: 4 }]
        });
    };

    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-notes-distribution-table-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            datatable.search(e.target.value).draw();
        });
    };

    var handleFilter = function () {
        const filterForm = document.querySelector('[data-kt-notes-distribution-table-filter="form"]');
        const filterButton = filterForm.querySelector('[data-kt-notes-distribution-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-kt-notes-distribution-table-filter="reset"]');
        const selectOptions = filterForm.querySelectorAll('select');

        filterButton.addEventListener('click', function () {
            var filterString = '';
            selectOptions.forEach((item, index) => {
                if (item.value && item.value !== '') {
                    if (index !== 0) filterString += ' ';
                    filterString += item.value;
                }
            });
            datatable.search(filterString).draw();
        });

        resetButton.addEventListener('click', function () {
            selectOptions.forEach(item => $(item).val(null).trigger('change'));
            datatable.search('').draw();
        });
    };

    return {
        init: function () {
            table = document.getElementById('kt_student_view_sheets_table');
            if (!table) return;
            initDatatable();
            handleSearch();
            handleFilter();
        }
    };
}();


// =========================================================================
// KTStudentsActivity — Activation / Class-change / Secondary-class tables
// =========================================================================
var KTStudentsActivity = function () {

    var initActivationDatatable = function () {
        var table = document.getElementById('kt_students_acitivation_table');
        if (!table) return;
        $(table).DataTable({
            info: true, order: [], lengthMenu: [10, 25, 50, 100], pageLength: 10,
            lengthChange: true, autoWidth: false,
            columnDefs: [{ orderable: false, targets: 2 }]
        });
    };

    var initClassChangeHistoryDatatable = function () {
        var table = document.getElementById('kt_students_class_change_history_table');
        if (!table) return;
        $(table).DataTable({
            info: true, order: [], lengthMenu: [10, 25, 50, 100], pageLength: 10,
            lengthChange: true, autoWidth: false,
            columnDefs: [{ orderable: false, targets: [1, 2] }]
        });
    };

    var initSecondaryClassHistoryDatatable = function () {
        var table = document.getElementById('kt_students_secondary_class_history_table');
        if (!table) return;
        $(table).DataTable({
            info: true, order: [], lengthMenu: [10, 25, 50, 100], pageLength: 10,
            lengthChange: true, autoWidth: false,
            columnDefs: [{ orderable: false, targets: [1, 2] }]
        });
    };

    return {
        init: function () {
            initActivationDatatable();
            initClassChangeHistoryDatatable();
            initSecondaryClassHistoryDatatable();
        }
    };
}();


// =========================================================================
// KTStudentViewAttendance — Calendar + Pie Chart + Export
// =========================================================================
var KTStudentViewAttendance = function () {

    // Module-level references
    var calendar = null;
    var calendarEl = null;
    var pieChart = null;
    var allEventsData = [];

    // ─── Helpers ──────────────────────────────────────────────────────────

    /**
     * Parse a YYYY-MM-DD string as a LOCAL date (avoids UTC midnight rollback).
     */
    var parseLocalDate = function (dateStr) {
        var parts = dateStr.split('-');
        return new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
    };

    /**
     * Gather attendance stats for a given month/year from allEventsData.
     */
    var calcStats = function (month, year) {
        var stats = { present: 0, absent: 0, late: 0 };
        allEventsData.forEach(function (event) {
            var d = parseLocalDate(event.start);
            if (d.getMonth() === month && d.getFullYear() === year) {
                var s = event.title.toLowerCase();
                if (s === 'present') stats.present++;
                else if (s === 'absent') stats.absent++;
                else if (s === 'late') stats.late++;
            }
        });
        return stats;
    };

    // ─── Pie-chart sync ───────────────────────────────────────────────────

    /**
     * Re-render the pie chart for the given month/year and update the
     * "Overview (Month Year)" heading.
     */
    var syncPieToMonth = function (month, year) {
        if (!pieChart) return;

        var stats = calcStats(month, year);

        // Update title
        var titleEl = document.getElementById('kt_attendance_overview_title');
        if (titleEl) {
            var label = new Date(year, month, 1).toLocaleString('en-US', { month: 'long', year: 'numeric' });
            titleEl.textContent = 'Overview (' + label + ')';
        }

        // Update dataset & repaint
        pieChart.data.datasets[0].data = [stats.present, stats.absent, stats.late];
        pieChart.update('active');
    };

    // ─── Calendar ─────────────────────────────────────────────────────────

    var initCalendar = function () {
        calendarEl = document.getElementById('kt_attendance_calendar');
        if (!calendarEl) return;

        allEventsData = JSON.parse(calendarEl.getAttribute('data-events'));

        calendar = new FullCalendar.Calendar(calendarEl, {

            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listMonth'
            },

            views: {
                listMonth: {
                    buttonText: 'List',
                    displayEventTime: false,
                    listDayFormat: false,
                    listDaySideFormat: false
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
            events: allEventsData,

            // ── Fires every time the visible date range changes ──────────
            datesSet: function (info) {
                // currentStart is the canonical start of the rendered period
                // (e.g. 2025-11-01 for November, regardless of padding cells)
                var cs = info.view.currentStart;
                syncPieToMonth(cs.getMonth(), cs.getFullYear());
            },

            // ── Tooltip on hover ─────────────────────────────────────────
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

            // ── Custom event rendering ────────────────────────────────────
            eventContent: function (arg) {

                // ── LIST VIEW : date | time | badge | remark ─────────────
                if (arg.view.type === 'listMonth') {
                    var d = arg.event.start;
                    var day = String(d.getDate()).padStart(2, '0');
                    var mon = d.toLocaleString('en-US', { month: 'short' });
                    var yr = d.getFullYear();
                    var fmtDate = day + '-' + mon + '-' + yr;

                    var time = arg.event.extendedProps.time || '';
                    var remarks = arg.event.extendedProps.description || '';
                    var bgColor = arg.event.backgroundColor;

                    var timeHtml = time
                        ? '<span class="d-inline-flex align-items-center text-gray-500 fs-7 me-4">'
                        + '<i class="ki-outline ki-time fs-7 me-1 text-gray-400"></i>' + time
                        + '</span>'
                        : '';

                    var remarkHtml = remarks
                        ? '<span class="d-inline-flex align-items-center text-muted fs-7 ms-2">'
                        + '<i class="ki-outline ki-message-text-2 fs-7 me-1"></i>'
                        + '<em>' + remarks + '</em>'
                        + '</span>'
                        : '';

                    return {
                        html:
                            '<div class="d-flex align-items-center flex-wrap py-1 gap-2">'
                            + '<span class="fw-bold text-gray-800 fs-6 me-3" style="min-width:110px">' + fmtDate + '</span>'
                            + timeHtml
                            + '<span class="badge rounded-pill" style="background-color:' + bgColor + ';color:#fff;">'
                            + arg.event.title
                            + '</span>'
                            + remarkHtml
                            + '</div>'
                    };
                }

                // ── GRID VIEW : standard coloured dot ────────────────────
                return {
                    html: '<div class="fc-content" style="color:#fff;padding:1px 2px;">'
                        + arg.event.title + '</div>'
                };
            }
        });

        calendar.render();
    };

    // ─── Pie Chart ────────────────────────────────────────────────────────

    var initPieChart = function () {
        var wrapper = document.getElementById('kt_attendance_pie_chart_wrapper');
        var canvas = document.getElementById('kt_attendance_pie_chart');
        if (!wrapper || !canvas) return;

        // Seed with the current calendar month
        var now = new Date();
        var stats = calcStats(now.getMonth(), now.getFullYear());

        var ctx = canvas.getContext('2d');

        pieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Present', 'Absent', 'Late'],
                datasets: [{
                    data: [stats.present, stats.absent, stats.late],
                    backgroundColor: ['#50cd89', '#f1416c', '#ffc700'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            plugins: [ChartDataLabels],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    animateRotate: true,
                    duration: 600
                },
                plugins: {
                    // ── Legend with count + percentage ──────────────────
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 24,
                            font: { size: 13 },
                            generateLabels: function (chart) {
                                var ds = chart.data.datasets[0];
                                var total = ds.data.reduce(function (a, b) { return a + b; }, 0);
                                return chart.data.labels.map(function (label, i) {
                                    var val = ds.data[i];
                                    var pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                    return {
                                        text: label + ' \u2014 ' + val + ' days (' + pct + '%)',
                                        fillStyle: ds.backgroundColor[i],
                                        strokeStyle: ds.backgroundColor[i],
                                        pointStyle: 'circle',
                                        index: i
                                    };
                                });
                            }
                        }
                    },
                    // ── Tooltip ─────────────────────────────────────────
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                var val = context.raw || 0;
                                var total = context.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                return ' ' + context.label + ': ' + val + ' days (' + pct + '%)';
                            }
                        }
                    },
                    // ── On-slice labels: count on first line, % on second ─
                    datalabels: {
                        color: '#ffffff',
                        anchor: 'center',
                        align: 'center',
                        font: { weight: 'bold', size: 13 },
                        formatter: function (value, context) {
                            if (!value || value === 0) return null;
                            var total = context.chart.data.datasets[0].data
                                .reduce(function (a, b) { return a + b; }, 0);
                            if (total === 0) return null;
                            var pct = Math.round((value / total) * 100);
                            // Array = two lines inside the slice
                            return [String(value), '(' + pct + '%)'];
                        }
                    }
                }
            }
        });
    };

    // ─── Export ───────────────────────────────────────────────────────────

    var handleExport = function () {
        var exportBtn = document.getElementById('kt_attendance_export_btn');
        if (!exportBtn) return;

        exportBtn.addEventListener('click', function () {

            // Guard: html2canvas must be loaded
            if (typeof html2canvas === 'undefined') {
                Swal.fire({
                    title: 'Export Unavailable',
                    text: 'html2canvas library is not loaded.',
                    icon: 'warning',
                    buttonsStyling: false,
                    confirmButtonText: 'OK',
                    customClass: { confirmButton: 'btn btn-primary' }
                });
                return;
            }

            var originalHTML = exportBtn.innerHTML;
            exportBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Exporting...';
            exportBtn.disabled = true;

            var historyCard = document.getElementById('kt_attendance_history_card');
            var overviewCard = document.getElementById('kt_attendance_overview_card');

            if (!historyCard || !overviewCard) {
                exportBtn.innerHTML = originalHTML;
                exportBtn.disabled = false;
                return;
            }

            var opts = { scale: 2, useCORS: true, backgroundColor: '#ffffff', logging: false };

            Promise.all([
                html2canvas(historyCard, opts),
                html2canvas(overviewCard, opts)
            ])
                .then(function (canvases) {
                    var c1 = canvases[0];
                    var c2 = canvases[1];

                    var gap = 32;          // vertical gap between cards (px, already scaled)
                    var padding = 30;          // outer padding on all sides
                    var bgColor = '#f5f8fa';

                    var combined = document.createElement('canvas');
                    combined.width = Math.max(c1.width, c2.width) + padding * 2;
                    combined.height = c1.height + gap + c2.height + padding * 2;

                    var ctx = combined.getContext('2d');
                    ctx.fillStyle = bgColor;
                    ctx.fillRect(0, 0, combined.width, combined.height);

                    ctx.drawImage(c1, padding, padding);
                    ctx.drawImage(c2, padding, padding + c1.height + gap);

                    // ── Build filename from student name shown in page title ──
                    var headingEl = document.querySelector('.page-heading');
                    var nameSlug = headingEl
                        ? headingEl.textContent.trim().split(',')[0].replace(/\s+/g, '_').toLowerCase()
                        : 'student';
                    var monthLabel = (document.getElementById('kt_attendance_overview_title') || {}).textContent || '';
                    var monthSlug = monthLabel.replace(/[^a-z0-9]/gi, '_').replace(/_+/g, '_').toLowerCase();
                    var filename = 'attendance_' + nameSlug + '_' + monthSlug + '.png';

                    var link = document.createElement('a');
                    link.href = combined.toDataURL('image/png', 1.0);
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    exportBtn.innerHTML = originalHTML;
                    exportBtn.disabled = false;
                })
                .catch(function (err) {
                    console.error('Attendance export error:', err);
                    exportBtn.innerHTML = originalHTML;
                    exportBtn.disabled = false;

                    Swal.fire({
                        title: 'Export Failed',
                        text: 'Could not export the attendance image. Please try again.',
                        icon: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                });
        });
    };

    // ─── Tab switch — fix calendar size ───────────────────────────────────

    var handleTabSwitch = function () {
        var tabLink = document.querySelector('a[href="#kt_student_view_attendance_tab"]')
            || document.querySelector('button[data-bs-target="#kt_student_view_attendance_tab"]');

        if (tabLink) {
            tabLink.addEventListener('shown.bs.tab', function () {
                if (calendar) calendar.updateSize();
            });
        }
    };

    // ─── Public ───────────────────────────────────────────────────────────

    return {
        init: function () {
            initCalendar();
            initPieChart();
            handleExport();
            handleTabSwitch();
        }
    };
}();


// =========================================================================
// Bootstrap — initialise all modules on DOMContentLoaded
// =========================================================================
KTUtil.onDOMContentLoaded(function () {
    KTStudentsActions.init();
    KTStudentsInvoicesView.init();
    KTEditInvoiceModal.init();
    KTStudentsTransactionsView.init();
    KTStudentsSheetsView.init();
    KTStudentsActivity.init();
    KTStudentViewAttendance.init();
});