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
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({ title: "Deleted!", text: "The student has been removed successfully.", icon: "success" })
                                        .then(() => { window.location.href = '/students'; });
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
        if (modalElement) toggleActivationModal = new bootstrap.Modal(modalElement);
    };

    var handleToggleActivationTrigger = function () {
        document.addEventListener('click', function (e) {
            var toggleButton = e.target.closest('[data-bs-target="#kt_toggle_activation_student_modal"]');
            if (!toggleButton) return;
            e.preventDefault();

            var studentId       = toggleButton.getAttribute('data-student-id');
            var studentName     = toggleButton.getAttribute('data-student-name');
            var studentUniqueId = toggleButton.getAttribute('data-student-unique-id');
            var activeStatus    = toggleButton.getAttribute('data-active-status');

            document.getElementById('student_id').value        = studentId;
            document.getElementById('activation_status').value = (activeStatus === 'active') ? 'inactive' : 'active';

            var modalTitle    = document.getElementById('toggle-activation-modal-title');
            var reasonLabel   = document.getElementById('reason_label');
            var reasonTextarea = document.querySelector('#kt_toggle_activation_student_modal textarea[name="reason"]');

            if (activeStatus === 'active') {
                modalTitle.textContent  = 'Deactivate Student - ' + studentName + ' (' + studentUniqueId + ')';
                reasonLabel.textContent = 'Deactivation Reason';
                if (reasonTextarea) reasonTextarea.placeholder = 'Write the reason for deactivating this student';
            } else {
                modalTitle.textContent  = 'Activate Student - ' + studentName + ' (' + studentUniqueId + ')';
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

            var submitBtn       = toggleForm.querySelector('button[type="submit"]');
            var originalBtnText = submitBtn.innerHTML;
            var reasonField     = toggleForm.querySelector('textarea[name="reason"]');

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

            submitBtn.disabled  = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

            var formData  = new FormData(toggleForm);
            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) { submitBtn.disabled = false; submitBtn.innerHTML = originalBtnText; return; }

            fetch(toggleForm.getAttribute('action'), {
                method: 'POST', body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(r => r.json().then(d => ({ status: r.status, data: d })))
                .then(function (result) {
                    submitBtn.disabled  = false;
                    submitBtn.innerHTML = originalBtnText;
                    var response = result.data;

                    if (response.success) {
                        if (toggleActivationModal) toggleActivationModal.hide();
                        toggleForm.reset();
                        var newStatus  = document.getElementById('activation_status').value;
                        var actionText = newStatus === 'active' ? 'activated' : 'deactivated';
                        Swal.fire({
                            icon: 'success', title: 'Success!',
                            text: response.message || 'Student has been ' + actionText + ' successfully.',
                            buttonsStyling: false, confirmButtonText: 'Ok, got it!',
                            customClass: { confirmButton: 'btn btn-primary' }
                        }).then(() => location.reload());
                    } else {
                        var errorMessage = response.message || 'Something went wrong.';
                        if (response.errors) errorMessage = Object.values(response.errors).map(e => e.join(', ')).join('\n');
                        Swal.fire({
                            icon: 'error', title: 'Error!', text: errorMessage,
                            buttonsStyling: false, confirmButtonText: 'Ok, got it!',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
                    }
                })
                .catch(function () {
                    submitBtn.disabled  = false;
                    submitBtn.innerHTML = originalBtnText;
                    Swal.fire({
                        icon: 'error', title: 'Error!', text: 'An unexpected error occurred.',
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
        var toggleForm   = modalElement.querySelector('form');

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
            info: true, order: [], lengthMenu: [10, 25, 50, 100], pageLength: 10,
            lengthChange: true, autoWidth: false,
            columnDefs: [{ orderable: false, targets: 7 }]
        });
    };

    var handleDeletion = function () {
        document.addEventListener('click', function (e) {
            const target = e.target.closest('.delete-invoice');
            if (!target) return;
            e.preventDefault();

            const invoiceId = target.getAttribute('data-invoice-id');
            const url       = routeDeleteInvoice.replace(':id', invoiceId);

            Swal.fire({
                title: "Are you sure to delete this invoice?",
                text: "This action cannot be undone!",
                icon: "warning",
                showCancelButton: true, confirmButtonColor: "#d33", cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete!",
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(url, {
                        method: "DELETE",
                        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content") },
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({ title: "Deleted!", text: "The invoice has been deleted successfully.", icon: "success" })
                                    .then(() => location.reload());
                            } else {
                                Swal.fire({ title: "Error!", text: data.error || "Deletion failed.", icon: "error" });
                            }
                        })
                        .catch(() => Swal.fire({ title: "Error!", text: "Something went wrong.", icon: "error" }));
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
    var element, form, modal, submitButton, validator;
    var invoiceId = null;

    var getCsrfToken = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    var formatMonthYear = function (monthYear) {
        if (!monthYear) return '';
        const [month, year] = monthYear.split('_');
        const names = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        return `${names[parseInt(month) - 1]} ${year}`;
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
                .then(r => { if (!r.ok) throw new Error('Network error'); return r.json(); })
                .then(data => {
                    if (!data.success || !data.data) throw new Error(data.message || 'Invalid response');
                    const invoice         = data.data;
                    const invoiceTypeName = invoice.invoice_type_name || '';

                    const titleEl = document.getElementById('kt_modal_edit_invoice_title');
                    if (titleEl) titleEl.textContent = `Update Invoice ${invoice.invoice_number}`;

                    const myw = document.getElementById('month_year_id_edit');
                    if (myw) myw.style.display = invoiceTypeName === 'Tuition Fee' ? '' : 'none';

                    const amountInput = element.querySelector("input[name='invoice_amount_edit']");
                    if (amountInput) amountInput.value = invoice.total_amount;

                    $("select[name='invoice_student_edit']").val(invoice.student_id).trigger('change');
                    $("select[name='invoice_type_edit']").val(invoice.invoice_type_id).trigger('change');

                    const mySelect = $("select[name='invoice_month_year_edit']");
                    mySelect.empty().append(new Option(formatMonthYear(invoice.month_year), invoice.month_year, true, true)).trigger('change');

                    modal.show();
                })
                .catch(error => { console.error(error); toastr.error(error.message || 'Failed to load invoice'); });
        });
    };

    var handleModalClose = function () {
        const cancelButton = element.querySelector('[data-kt-edit-invoice-modal-action="cancel"]');
        const closeButton  = element.querySelector('[data-kt-edit-invoice-modal-action="close"]');

        [cancelButton, closeButton].forEach(btn => {
            if (btn) btn.addEventListener('click', e => {
                e.preventDefault();
                if (form) form.reset();
                if (validator) validator.resetForm();
                modal.hide();
            });
        });
        element.addEventListener('hidden.bs.modal', () => {
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
                        notEmpty:    { message: 'Amount is required' },
                        greaterThan: { min: 50, message: 'Amount must be at least 50' }
                    }
                }
            },
            plugins: {
                trigger:   new FormValidation.plugins.Trigger(),
                bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: '.fv-row', eleInvalidClass: '', eleValidClass: '' })
            }
        });
    };

    var handleFormSubmit = function () {
        submitButton = element.querySelector('[data-kt-edit-invoice-modal-action="submit"]');
        if (!submitButton) return;

        submitButton.addEventListener('click', function (e) {
            e.preventDefault();
            if (!validator) return;

            validator.validate().then(function (status) {
                if (status !== 'Valid') {
                    Swal.fire({ text: 'Please fill all required fields correctly.', icon: 'warning', buttonsStyling: false, confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' } });
                    return;
                }

                submitButton.setAttribute('data-kt-indicator', 'on');
                submitButton.disabled = true;

                const formData = new FormData(form);
                formData.append('_token', getCsrfToken());
                formData.append('_method', 'PUT');

                fetch(`/invoices/${invoiceId}`, {
                    method: 'POST', body: formData,
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(r => r.json().then(d => { if (!r.ok) throw new Error(d.message || 'Error'); return d; }))
                    .then(data => {
                        submitButton.removeAttribute('data-kt-indicator');
                        submitButton.disabled = false;
                        if (data.success) {
                            Swal.fire({ text: data.message || 'Invoice updated!', icon: 'success', buttonsStyling: false, confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' } })
                                .then(r => { if (r.isConfirmed) { modal.hide(); window.location.reload(); } });
                        } else {
                            throw new Error(data.message || 'Failed to update');
                        }
                    })
                    .catch(error => {
                        submitButton.removeAttribute('data-kt-indicator');
                        submitButton.disabled = false;
                        Swal.fire({ html: error.message || 'Something went wrong.', icon: 'error', buttonsStyling: false, confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' } });
                    });
            });
        });
    };

    return {
        init: function () {
            element = document.getElementById('kt_modal_edit_invoice');
            if (!element) return;
            form  = element.querySelector('#kt_modal_edit_invoice_form');
            modal = bootstrap.Modal.getOrCreateInstance(element);
            handleEditClick(); handleModalClose(); initValidation(); handleFormSubmit();
        }
    };
}();


// =========================================================================
// KTStudentsTransactionsView — Transactions DataTable + Actions
// =========================================================================
var KTStudentsTransactionsView = function () {
    var table, datatable;

    var initDatatable = function () {
        datatable = $(table).DataTable({
            info: true, order: [], lengthMenu: [10, 25, 50, 100], pageLength: 10,
            lengthChange: true, autoWidth: false,
            columnDefs: [{ orderable: false, targets: 7 }]
        });
    };

    var handleTransactionDeletion = function () {
        document.addEventListener("click", function (e) {
            const deleteBtn = e.target.closest(".delete-txn");
            if (!deleteBtn) return;
            e.preventDefault();

            let txnId      = deleteBtn.getAttribute("data-txn-id");
            let isApproved = deleteBtn.getAttribute("data-is-approved") === "1";
            let url        = routeDeleteTxn.replace(":id", txnId);

            Swal.fire({
                title: isApproved ? "Delete Successful Transaction?" : "Are you sure you want to delete?",
                text: isApproved
                    ? "This will reverse the wallet collection, restore the invoice due, and create an adjustment log. Cannot be undone."
                    : "Once deleted, this unapproved transaction will be removed.",
                icon: "warning", showCancelButton: true,
                confirmButtonColor: "#d33", cancelButtonColor: "#6c757d",
                confirmButtonText: isApproved ? "Yes, delete and reverse" : "Yes, delete it",
                customClass: { popup: 'swal-wide' }
            }).then((result) => {
                if (result.isConfirmed) {
                    const orig = deleteBtn.innerHTML;
                    deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                    deleteBtn.style.pointerEvents = 'none';

                    fetch(url, {
                        method: "DELETE",
                        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken }
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({ title: "Deleted!", text: data.message || "Transaction deleted.", icon: "success" })
                                    .then(() => window.location.reload());
                            } else {
                                deleteBtn.innerHTML = orig; deleteBtn.style.pointerEvents = 'auto';
                                Swal.fire({ title: "Failed!", text: data.message || "Could not delete.", icon: "error" });
                            }
                        })
                        .catch(() => {
                            deleteBtn.innerHTML = orig; deleteBtn.style.pointerEvents = 'auto';
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
                    title: 'Are you sure?', text: "Approve this transaction?", icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, approve!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/transactions/${txnId}/approve`, {
                            method: "POST",
                            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content") }
                        })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({ title: "Approved!", text: "Transaction approved.", icon: "success" }).then(() => location.reload());
                                } else {
                                    Swal.fire({ title: "Error!", text: data.message, icon: "warning" });
                                }
                            })
                            .catch(() => Swal.fire({ title: "Error!", text: "Something went wrong.", icon: "error" }));
                    }
                });
            });
        });
    };

    const handleStatementDownload = function () {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.download-statement');
            if (!btn) return;
            e.preventDefault();

            const studentId = btn.getAttribute('data-student-id');
            const year      = btn.getAttribute('data-year');
            const invoiceId = btn.getAttribute('data-invoice-id');

            if (!studentId || !year) { Swal.fire({ title: 'Error!', text: 'Missing data.', icon: 'error' }); return; }

            const orig = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btn.style.pointerEvents = 'none';

            const fd = new FormData();
            fd.append('student_id', studentId);
            fd.append('statement_year', year);
            fd.append('invoice_id', invoiceId);

            fetch(routeDownloadStatement, { method: "POST", headers: { "X-CSRF-TOKEN": csrfToken }, body: fd })
                .then(r => { if (!r.ok) return r.text().then(t => { throw new Error(t || 'Server error'); }); return r.text(); })
                .then(html => {
                    const w = window.open("", "_blank", "width=900,height=700,scrollbars=yes");
                    if (w) { w.document.open(); w.document.write(html); w.document.close(); w.focus(); }
                    else Swal.fire({ title: 'Popup Blocked!', text: 'Allow popups for this site.', icon: 'warning' });
                    btn.innerHTML = orig; btn.style.pointerEvents = 'auto';
                })
                .catch(error => {
                    if (error.message.toLowerCase().includes('no transactions'))
                        Swal.fire({ title: 'No Data', text: 'No transactions for the selected year.', icon: 'info' });
                    else
                        Swal.fire({ title: 'Error!', text: 'Failed to load statement.', icon: 'error' });
                    btn.innerHTML = orig; btn.style.pointerEvents = 'auto';
                });
        });
    };

    return {
        init: function () {
            table = document.getElementById('kt_student_view_transactions_table');
            if (!table) return;
            initDatatable(); handleTransactionDeletion(); handleTransactionApproval(); handleStatementDownload();
        }
    };
}();


// =========================================================================
// KTStudentsSheetsView — Sheets DataTable + Search + Filter
// =========================================================================
var KTStudentsSheetsView = function () {
    var table, datatable;

    var initDatatable = function () {
        datatable = $(table).DataTable({
            info: true, order: [], lengthMenu: [10, 25, 50, 100], pageLength: 10,
            lengthChange: true, autoWidth: false,
            columnDefs: [{ orderable: false, targets: 4 }]
        });
    };

    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-kt-notes-distribution-table-filter="search"]');
        filterSearch.addEventListener('keyup', e => datatable.search(e.target.value).draw());
    };

    var handleFilter = function () {
        const filterForm   = document.querySelector('[data-kt-notes-distribution-table-filter="form"]');
        const filterButton = filterForm.querySelector('[data-kt-notes-distribution-table-filter="filter"]');
        const resetButton  = filterForm.querySelector('[data-kt-notes-distribution-table-filter="reset"]');
        const selects      = filterForm.querySelectorAll('select');

        filterButton.addEventListener('click', function () {
            datatable.search(Array.from(selects).filter(s => s.value).map(s => s.value).join(' ')).draw();
        });
        resetButton.addEventListener('click', function () {
            selects.forEach(s => $(s).val(null).trigger('change'));
            datatable.search('').draw();
        });
    };

    return {
        init: function () {
            table = document.getElementById('kt_student_view_sheets_table');
            if (!table) return;
            initDatatable(); handleSearch(); handleFilter();
        }
    };
}();


// =========================================================================
// KTStudentsActivity — Activation / Class-change / Secondary-class tables
// =========================================================================
var KTStudentsActivity = function () {
    var makeTable = function (id, disabledTargets) {
        var el = document.getElementById(id);
        if (!el) return;
        $(el).DataTable({
            info: true, order: [], lengthMenu: [10, 25, 50, 100], pageLength: 10,
            lengthChange: true, autoWidth: false,
            columnDefs: [{ orderable: false, targets: disabledTargets }]
        });
    };

    return {
        init: function () {
            makeTable('kt_students_acitivation_table',             [2]);
            makeTable('kt_students_class_change_history_table',    [1, 2]);
            makeTable('kt_students_secondary_class_history_table', [1, 2]);
        }
    };
}();


// =========================================================================
// KTStudentViewAttendance — Calendar · Pie Chart · Export
// =========================================================================
var KTStudentViewAttendance = function () {

    // ── Module state ──────────────────────────────────────────────────────
    var calendar      = null;
    var pieChart      = null;
    var allEventsData = [];
    var emptyStateEl  = null;   // lazily created empty-state overlay

    // ── Date helpers ──────────────────────────────────────────────────────

    /** Parse "YYYY-MM-DD" as LOCAL midnight (avoids UTC rollback). */
    var parseLocal = function (dateStr) {
        var p = dateStr.split('-');
        return new Date(+p[0], +p[1] - 1, +p[2]);
    };

    /** Tally Present/Absent/Late for a given month. */
    var calcStats = function (month, year) {
        var s = { present: 0, absent: 0, late: 0 };
        allEventsData.forEach(function (ev) {
            var d = parseLocal(ev.start);
            if (d.getMonth() === month && d.getFullYear() === year) {
                var t = ev.title.toLowerCase();
                if      (t === 'present') s.present++;
                else if (t === 'absent')  s.absent++;
                else if (t === 'late')    s.late++;
            }
        });
        return s;
    };

    // ── Pie-chart visibility ──────────────────────────────────────────────

    /**
     * Show the canvas (hasData = true) or swap it for a friendly empty state.
     * The empty-state div is created lazily and reused.
     */
    var setPieVisibility = function (hasData) {
        var canvas  = document.getElementById('kt_attendance_pie_chart');
        var wrapper = document.getElementById('kt_attendance_pie_chart_wrapper');
        if (!canvas || !wrapper) return;

        if (!emptyStateEl) {
            emptyStateEl = document.createElement('div');
            emptyStateEl.id        = 'kt_pie_empty_state';
            emptyStateEl.className = 'd-none flex-column align-items-center justify-content-center w-100';
            emptyStateEl.style.cssText = 'min-height:240px;';
            emptyStateEl.innerHTML =
                '<div class="mb-4">'
                +   '<i class="ki-outline ki-chart-pie-3 fs-3x text-gray-300"></i>'
                + '</div>'
                + '<h5 class="text-gray-500 fw-semibold mb-1">No Attendance Records</h5>'
                + '<p class="text-gray-400 fs-6 mb-0">No data available for the selected month.</p>';
            wrapper.appendChild(emptyStateEl);
        }

        if (hasData) {
            canvas.classList.remove('d-none');
            emptyStateEl.classList.remove('d-flex');
            emptyStateEl.classList.add('d-none');
        } else {
            canvas.classList.add('d-none');
            emptyStateEl.classList.remove('d-none');
            emptyStateEl.classList.add('d-flex');
        }
    };

    // ── Pie-chart sync ────────────────────────────────────────────────────

    /**
     * Re-populate the pie chart (or show empty state) for the given month/year.
     * Also updates the "Overview (Month Year)" heading.
     */
    var syncPie = function (month, year) {
        // Always update the title first
        var titleEl = document.getElementById('kt_attendance_overview_title');
        if (titleEl) {
            titleEl.textContent = 'Overview ('
                + new Date(year, month, 1).toLocaleString('en-US', { month: 'long', year: 'numeric' })
                + ')';
        }

        if (!pieChart) return;

        var stats = calcStats(month, year);
        var total = stats.present + stats.absent + stats.late;

        if (total === 0) {
            setPieVisibility(false);
            return;
        }

        setPieVisibility(true);
        pieChart.data.datasets[0].data = [stats.present, stats.absent, stats.late];
        pieChart.update('active');
    };

    // ── Calendar ──────────────────────────────────────────────────────────

    var initCalendar = function () {
        var calEl = document.getElementById('kt_attendance_calendar');
        if (!calEl) return;

        allEventsData = JSON.parse(calEl.getAttribute('data-events'));

        calendar = new FullCalendar.Calendar(calEl, {

            headerToolbar: {
                left:   'prev,next today',
                center: 'title',
                right:  'dayGridMonth,listMonth'
            },
            views: {
                listMonth: {
                    buttonText:        'List',
                    displayEventTime:  false,
                    listDayFormat:     false,
                    listDaySideFormat: false
                }
            },
            initialView:   'dayGridMonth',
            height:        'auto',
            contentHeight: 650,
            aspectRatio:   3,
            initialDate:   new Date(),
            navLinks:      true,
            editable:      false,
            dayMaxEvents:  true,
            events:        allEventsData,

            // ── Sync pie every time the visible range changes ────────────
            datesSet: function (info) {
                var cs = info.view.currentStart;     // canonical period start (e.g. Nov 1)
                syncPie(cs.getMonth(), cs.getFullYear());
            },

            // ── Tooltip on hover ─────────────────────────────────────────
            eventDidMount: function (info) {
                var remarks = info.event.extendedProps.description;
                if (remarks) {
                    new bootstrap.Tooltip(info.el, {
                        title: remarks, placement: 'top', trigger: 'hover', container: 'body'
                    });
                }
            },

            // ── Custom render ────────────────────────────────────────────
            eventContent: function (arg) {

                // LIST VIEW: date · time · status badge · remark
                if (arg.view.type === 'listMonth') {
                    var d       = arg.event.start;
                    var dateStr = String(d.getDate()).padStart(2, '0')
                                + '-' + d.toLocaleString('en-US', { month: 'short' })
                                + '-' + d.getFullYear();

                    var time    = arg.event.extendedProps.time        || '';
                    var remarks = arg.event.extendedProps.description || '';
                    var bg      = arg.event.backgroundColor;

                    var timeHtml = time
                        ? '<span class="d-inline-flex align-items-center text-gray-500 fs-7 me-4">'
                          + '<i class="ki-outline ki-time fs-7 me-1 text-gray-400"></i>' + time + '</span>'
                        : '';

                    var remarkHtml = remarks
                        ? '<span class="d-inline-flex align-items-center text-muted fs-7 ms-3 fst-italic">'
                          + '<i class="ki-outline ki-message-text-2 fs-7 me-1"></i>' + remarks + '</span>'
                        : '';

                    return {
                        html: '<div class="d-flex align-items-center flex-wrap py-1 gap-2">'
                            + '<span class="fw-bold text-gray-800 fs-6" style="min-width:110px">' + dateStr + '</span>'
                            + timeHtml
                            + '<span class="badge rounded-pill" style="background:' + bg + ';color:#fff">'
                            + arg.event.title + '</span>'
                            + remarkHtml
                            + '</div>'
                    };
                }

                // GRID VIEW: default coloured dot
                return {
                    html: '<div style="color:#fff;padding:1px 2px">' + arg.event.title + '</div>'
                };
            }
        });

        calendar.render();
    };

    // ── Pie chart ─────────────────────────────────────────────────────────

    var initPieChart = function () {
        var wrapper = document.getElementById('kt_attendance_pie_chart_wrapper');
        var canvas  = document.getElementById('kt_attendance_pie_chart');
        if (!wrapper || !canvas) return;

        var now   = new Date();
        var stats = calcStats(now.getMonth(), now.getFullYear());
        var total = stats.present + stats.absent + stats.late;

        // Show empty state immediately if no records this month
        if (total === 0) {
            setPieVisibility(false);
        }

        var ctx = canvas.getContext('2d');

        pieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Present', 'Absent', 'Late'],
                datasets: [{
                    data:            [stats.present, stats.absent, stats.late],
                    backgroundColor: ['#50cd89', '#f1416c', '#ffc700'],
                    borderWidth:     0,
                    hoverOffset:     8
                }]
            },
            plugins: [ChartDataLabels],
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                animation: { animateRotate: true, duration: 600 },
                plugins: {
                    // Legend: label + count + %
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding:       24,
                            font:          { size: 13 },
                            generateLabels: function (chart) {
                                var ds    = chart.data.datasets[0];
                                var total = ds.data.reduce((a, b) => a + b, 0);
                                return chart.data.labels.map(function (label, i) {
                                    var val = ds.data[i];
                                    var pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                    return {
                                        text:        label + ' \u2014 ' + val + ' days (' + pct + '%)',
                                        fillStyle:   ds.backgroundColor[i],
                                        strokeStyle: ds.backgroundColor[i],
                                        pointStyle:  'circle',
                                        index:       i
                                    };
                                });
                            }
                        }
                    },
                    // Tooltip: count + %
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var val   = ctx.raw || 0;
                                var total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                var pct   = total > 0 ? Math.round((val / total) * 100) : 0;
                                return ' ' + ctx.label + ': ' + val + ' days (' + pct + '%)';
                            }
                        }
                    },
                    // On-slice: count on line 1, % on line 2
                    datalabels: {
                        color:  '#ffffff',
                        anchor: 'center',
                        align:  'center',
                        font:   { weight: 'bold', size: 13 },
                        formatter: function (value, context) {
                            if (!value) return null;
                            var total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            if (!total) return null;
                            return [String(value), '(' + Math.round((value / total) * 100) + '%)'];
                        }
                    }
                }
            }
        });

        // Sync visibility once after chart is created
        if (total === 0) setPieVisibility(false);
    };

    // ── Export ────────────────────────────────────────────────────────────
    //
    // Strategy: build a canvas image ENTIRELY from our in-memory data.
    // No DOM capture → no layout re-render → no browser hang.
    // The only async step is loading the Chart.js base64 image, which
    // resolves almost instantly since it's already in memory.
    // ─────────────────────────────────────────────────────────────────────

    /** Draw one stat card (Present / Absent / Late box). */
    var drawStatCard = function (ctx, x, y, w, h, label, value, pct, accent) {
        // Card background
        ctx.fillStyle = '#ffffff';
        _roundRect(ctx, x, y, w, h, 8);
        ctx.fill();

        // Left accent stripe
        ctx.fillStyle = accent;
        _roundRect(ctx, x, y, 5, h, 3);
        ctx.fill();

        // Value
        ctx.fillStyle  = '#181c32';
        ctx.font       = 'bold 28px -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif';
        ctx.textAlign  = 'left';
        ctx.fillText(String(value), x + 18, y + 40);

        // Label
        ctx.fillStyle = '#5e6278';
        ctx.font      = '12px -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif';
        ctx.fillText(label, x + 18, y + 58);

        // Percentage (right-aligned)
        ctx.fillStyle = accent;
        ctx.font      = 'bold 12px -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText(pct + '%', x + w - 14, y + 40);
        ctx.textAlign = 'left';
    };

    /** Minimal rounded-rect path helper. */
    var _roundRect = function (ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y); ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r); ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h); ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r); ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    };

    /**
     * Build and return a Promise<HTMLCanvasElement> without touching the DOM.
     * Everything is drawn synchronously except for the pie-chart image load
     * (which resolves in one microtask since the data URL is already in RAM).
     */
    var buildExportCanvas = function (month, year, monthLabel) {
        return new Promise(function (resolve) {

            var stats  = calcStats(month, year);
            var total  = stats.present + stats.absent + stats.late;

            // Events for this month, sorted oldest-first
            var rows = allEventsData
                .filter(function (ev) {
                    var d = parseLocal(ev.start);
                    return d.getMonth() === month && d.getFullYear() === year;
                })
                .sort((a, b) => a.start.localeCompare(b.start));

            // ── Layout constants (logical pixels; we scale ×2 for retina) ──
            var DPR        = 2;
            var W          = 860;
            var PAD        = 28;
            var ACCENT_H   = 4;
            var HEADER_H   = 82;     // title + subtitle + separator
            var STATS_H    = 88;     // 3 stat cards
            var GAP        = 16;
            var CHART_H    = 260;    // pie chart or "no data" placeholder
            var ROW_H      = 28;
            var LIST_HDR_H = 34;
            var LIST_H     = LIST_HDR_H + Math.max(rows.length, 1) * ROW_H + 8;
            var FOOTER_H   = 44;

            var H = ACCENT_H + PAD + HEADER_H + GAP + STATS_H + GAP
                  + Math.max(CHART_H, LIST_H) + GAP + FOOTER_H + PAD;

            // ── Canvas ───────────────────────────────────────────────────
            var canvas   = document.createElement('canvas');
            canvas.width  = W * DPR;
            canvas.height = H * DPR;

            var ctx = canvas.getContext('2d');
            ctx.scale(DPR, DPR);

            // ── Palette ──────────────────────────────────────────────────
            var C = {
                bg:      '#f5f8fa',
                white:   '#ffffff',
                accent:  '#009ef7',
                present: '#50cd89',
                absent:  '#f1416c',
                late:    '#ffc700',
                dark:    '#181c32',
                mid:     '#5e6278',
                light:   '#a1a5b7',
                border:  '#e4e6ef'
            };

            // ── Background ───────────────────────────────────────────────
            ctx.fillStyle = C.bg;
            ctx.fillRect(0, 0, W, H);

            // ── Top accent bar ───────────────────────────────────────────
            ctx.fillStyle = C.accent;
            ctx.fillRect(0, 0, W, ACCENT_H);

            // ── Header ───────────────────────────────────────────────────
            var hY = ACCENT_H + PAD;

            ctx.fillStyle = C.dark;
            ctx.font      = 'bold 20px -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif';
            ctx.fillText('Attendance Report', PAD, hY + 22);

            ctx.fillStyle = C.mid;
            ctx.font      = '13px -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif';
            ctx.fillText(monthLabel, PAD, hY + 44);

            // Timestamp (right-aligned)
            var ts = 'Generated ' + new Date().toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });
            ctx.textAlign  = 'right';
            ctx.fillStyle  = C.light;
            ctx.font       = '11px -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif';
            ctx.fillText(ts, W - PAD, hY + 22);
            ctx.textAlign  = 'left';

            // Separator
            ctx.strokeStyle = C.border;
            ctx.lineWidth   = 1;
            ctx.beginPath();
            ctx.moveTo(PAD, hY + 58);
            ctx.lineTo(W - PAD, hY + 58);
            ctx.stroke();

            // ── Stat cards ───────────────────────────────────────────────
            var sY       = hY + HEADER_H;
            var cardW    = Math.floor((W - PAD * 2 - 16) / 3);
            var cardData = [
                { label: 'Present', value: stats.present, color: C.present },
                { label: 'Absent',  value: stats.absent,  color: C.absent  },
                { label: 'Late',    value: stats.late,     color: C.late    }
            ];
            cardData.forEach(function (card, i) {
                var pct = total > 0 ? Math.round((card.value / total) * 100) : 0;
                drawStatCard(ctx, PAD + i * (cardW + 8), sY, cardW, 72, card.label, card.value, pct, card.color);
            });

            // ── Content area ─────────────────────────────────────────────
            var contentY = sY + STATS_H + GAP;
            var halfW    = Math.floor((W - PAD * 2 - 16) / 2);

            // Right panel: attendance list ─────────────────────────────────
            var listX = PAD + halfW + 16;
            var listW = halfW;

            // List header row
            ctx.fillStyle = '#eff2f5';
            _roundRect(ctx, listX, contentY, listW, LIST_HDR_H, 6);
            ctx.fill();

            ctx.fillStyle = C.mid;
            ctx.font      = 'bold 10px -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif';
            ctx.fillText('DATE',   listX + 10,               contentY + 22);
            ctx.fillText('STATUS', listX + listW * 0.46,     contentY + 22);
            ctx.fillText('TIME',   listX + listW * 0.74,     contentY + 22);

            if (rows.length === 0) {
                // Empty list state
                ctx.fillStyle = C.light;
                ctx.font      = '12px -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('No records this month', listX + listW / 2, contentY + LIST_HDR_H + 34);
                ctx.textAlign = 'left';
            } else {
                rows.forEach(function (ev, idx) {
                    var rY = contentY + LIST_HDR_H + idx * ROW_H;

                    // Alternate row background
                    if (idx % 2 === 0) {
                        ctx.fillStyle = C.white;
                        ctx.fillRect(listX, rY, listW, ROW_H);
                    }

                    // Date
                    var d       = parseLocal(ev.start);
                    var dateStr = String(d.getDate()).padStart(2, '0')
                                + '-' + d.toLocaleString('en-US', { month: 'short' })
                                + '-' + d.getFullYear();
                    ctx.fillStyle = C.dark;
                    ctx.font      = '11px -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif';
                    ctx.fillText(dateStr, listX + 10, rY + ROW_H * 0.65);

                    // Status dot
                    ctx.beginPath();
                    ctx.arc(listX + listW * 0.46 - 1, rY + ROW_H * 0.48, 4, 0, Math.PI * 2);
                    ctx.fillStyle = ev.color || C.present;
                    ctx.fill();

                    // Status text
                    ctx.fillStyle = C.mid;
                    ctx.font      = '11px -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif';
                    ctx.fillText(ev.title, listX + listW * 0.46 + 8, rY + ROW_H * 0.65);

                    // Time
                    if (ev.time) {
                        ctx.fillStyle = C.light;
                        ctx.font      = '10px -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif';
                        ctx.fillText(ev.time, listX + listW * 0.74, rY + ROW_H * 0.65);
                    }
                });

                // List bottom border
                var listBottom = contentY + LIST_HDR_H + rows.length * ROW_H;
                ctx.strokeStyle = C.border;
                ctx.lineWidth   = 1;
                ctx.beginPath();
                ctx.moveTo(listX, listBottom); ctx.lineTo(listX + listW, listBottom);
                ctx.stroke();
            }

            // ── Footer ───────────────────────────────────────────────────
            var drawFooter = function () {
                ctx.fillStyle = C.light;
                ctx.font      = '10px -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('Exported from Student Management System', W / 2, H - PAD + 14);
                ctx.textAlign = 'left';
            };

            // ── Left panel: pie chart or "no data" text ───────────────────
            if (total > 0 && pieChart) {
                var pieImg = new Image();
                pieImg.onload = function () {
                    // Centre the pie chart image within the left half
                    var sz   = Math.min(halfW - 10, CHART_H);
                    var imgX = PAD + Math.floor((halfW - sz) / 2);
                    var imgY = contentY + Math.floor((Math.max(CHART_H, LIST_H) - sz) / 2);
                    ctx.drawImage(pieImg, imgX, imgY, sz, sz);
                    drawFooter();
                    resolve(canvas);
                };
                pieImg.onerror = function () { drawFooter(); resolve(canvas); };
                // toBase64Image() is synchronous; onload fires in next microtask
                pieImg.src = pieChart.toBase64Image();
            } else {
                // No-data placeholder on the left
                ctx.fillStyle = '#eff2f5';
                _roundRect(ctx, PAD, contentY, halfW, CHART_H, 8);
                ctx.fill();

                ctx.fillStyle = C.light;
                ctx.font      = '13px -apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('No attendance data', PAD + halfW / 2, contentY + CHART_H / 2 - 8);
                ctx.fillText('for this month',     PAD + halfW / 2, contentY + CHART_H / 2 + 12);
                ctx.textAlign = 'left';
                drawFooter();
                resolve(canvas);
            }
        });
    };

    // ── Export button handler ─────────────────────────────────────────────

    var handleExport = function () {
        var exportBtn = document.getElementById('kt_attendance_export_btn');
        if (!exportBtn) return;

        exportBtn.addEventListener('click', function () {
            var originalHTML = exportBtn.innerHTML;
            exportBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Preparing…';
            exportBtn.disabled  = true;

            // Read current calendar month from the pie-chart title (already updated)
            var titleEl    = document.getElementById('kt_attendance_overview_title');
            var monthLabel = titleEl
                ? titleEl.textContent.replace('Overview (', '').replace(')', '').trim()
                : new Date().toLocaleString('en-US', { month: 'long', year: 'numeric' });

            // Derive month/year integers from the calendar's current view
            var currentStart = calendar ? calendar.view.currentStart : new Date();
            var month        = currentStart.getMonth();
            var year         = currentStart.getFullYear();

            // Build filename slug from the month label
            var slug = monthLabel.replace(/\s+/g, '_').toLowerCase();

            // Yield to browser to repaint the button, then build canvas
            requestAnimationFrame(function () {
                setTimeout(function () {
                    buildExportCanvas(month, year, monthLabel)
                        .then(function (canvas) {
                            var link      = document.createElement('a');
                            link.download = 'attendance_' + slug + '.png';
                            link.href     = canvas.toDataURL('image/png', 1.0);
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        })
                        .catch(function (err) {
                            console.error('Export error:', err);
                            Swal.fire({
                                title: 'Export Failed',
                                text: 'Could not generate the attendance image. Please try again.',
                                icon: 'error',
                                buttonsStyling: false, confirmButtonText: 'OK',
                                customClass: { confirmButton: 'btn btn-primary' }
                            });
                        })
                        .finally(function () {
                            exportBtn.innerHTML = originalHTML;
                            exportBtn.disabled  = false;
                        });
                }, 60);   // small delay lets the spinner render
            });
        });
    };

    // ── Tab-switch: fix calendar size ─────────────────────────────────────

    var handleTabSwitch = function () {
        var tabLink = document.querySelector('a[href="#kt_student_view_attendance_tab"]')
            || document.querySelector('button[data-bs-target="#kt_student_view_attendance_tab"]');
        if (tabLink) {
            tabLink.addEventListener('shown.bs.tab', function () {
                if (calendar) calendar.updateSize();
            });
        }
    };

    // ── Public ────────────────────────────────────────────────────────────

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
// Bootstrap — init all modules on DOMContentLoaded
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