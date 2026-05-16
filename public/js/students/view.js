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
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

            var formData = new FormData(toggleForm);
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
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                    var response = result.data;

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
                        if (response.errors) errorMessage = Object.values(response.errors).map(e => e.join(', ')).join('\n');
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
            const url = routeDeleteInvoice.replace(':id', invoiceId);

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
        const names = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
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
                    const invoice = data.data;
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
        const closeButton = element.querySelector('[data-kt-edit-invoice-modal-action="close"]');

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
                        notEmpty: { message: 'Amount is required' },
                        greaterThan: { min: 50, message: 'Amount must be at least 50' }
                    }
                }
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
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
            form = element.querySelector('#kt_modal_edit_invoice_form');
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

            let txnId = deleteBtn.getAttribute("data-txn-id");
            let isApproved = deleteBtn.getAttribute("data-is-approved") === "1";
            let url = routeDeleteTxn.replace(":id", txnId);

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
            const year = btn.getAttribute('data-year');
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
        const filterForm = document.querySelector('[data-kt-notes-distribution-table-filter="form"]');
        const filterButton = filterForm.querySelector('[data-kt-notes-distribution-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-kt-notes-distribution-table-filter="reset"]');
        const selects = filterForm.querySelectorAll('select');

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
            makeTable('kt_students_acitivation_table', [2]);
            makeTable('kt_students_class_change_history_table', [1, 2]);
            makeTable('kt_students_secondary_class_history_table', [1, 2]);
        }
    };
}();


// =========================================================================
// KTStudentViewAttendance — Calendar · Pie Chart · Export
// =========================================================================
var KTStudentViewAttendance = function () {
    var calendar;
    var calendarEl;
    var pieChartInstance = null;
    var allEventsData = [];
    var currentOverviewMonth; // { year, month } — 0-based month

    // ── Helpers ──────────────────────────────────────────────────────────────

    var MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];

    var getMonthName = function (year, month) {
        return MONTH_NAMES[month] + ' ' + year;
    };

    var pad = function (n) { return n.toString().padStart(2, '0'); };

    var getStatsForMonth = function (year, month) {
        var stats = { present: 0, absent: 0, late: 0 };
        allEventsData.forEach(function (event) {
            var d = new Date(event.start);
            if (d.getFullYear() === year && d.getMonth() === month) {
                var s = event.title.toLowerCase();
                if (s === 'present') stats.present++;
                else if (s === 'absent') stats.absent++;
                else if (s === 'late') stats.late++;
                // any other status is ignored
            }
        });
        return stats;
    };

    // ── Overview Card ─────────────────────────────────────────────────────────

    var updateOverview = function (year, month) {
        currentOverviewMonth = { year: year, month: month };

        var label = getMonthName(year, month);
        var titleEl = document.getElementById('kt_attendance_overview_title');
        var labelEl = document.getElementById('kt_overview_month_label');
        if (titleEl) titleEl.textContent = 'Overview (' + label + ')';
        if (labelEl) labelEl.textContent = label;

        var stats = getStatsForMonth(year, month);
        var total = stats.present + stats.absent + stats.late;
        var rate = total > 0 ? Math.round((stats.present / total) * 100) : 0;

        var setEl = function (id, val) {
            var el = document.getElementById(id);
            if (el) el.textContent = val;
        };
        setEl('kt_stat_present', stats.present);
        setEl('kt_stat_absent', stats.absent);
        setEl('kt_stat_late', stats.late);
        setEl('kt_stat_rate', rate + '%');

        var bar = document.getElementById('kt_stat_rate_bar');
        if (bar) bar.style.width = rate + '%';

        // ── Pie Chart ──
        var canvas = document.getElementById('kt_attendance_pie_chart');
        var emptyMsg = document.getElementById('kt_pie_empty_msg');
        if (!canvas) return;

        if (pieChartInstance) {
            pieChartInstance.destroy();
            pieChartInstance = null;
        }

        if (total === 0) {
            canvas.style.display = 'none';
            if (emptyMsg) {
                emptyMsg.classList.remove('d-none');
                emptyMsg.classList.add('d-flex');
            }
            return;
        }

        // Has data — show canvas, hide empty msg
        canvas.style.display = '';
        if (emptyMsg) {
            emptyMsg.classList.add('d-none');
            emptyMsg.classList.remove('d-flex');
        }

        var ctx = canvas.getContext('2d');
        pieChartInstance = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Present', 'Absent', 'Late'],
                datasets: [{
                    data: [stats.present, stats.absent, stats.late],
                    backgroundColor: ['#50cd89', '#f1416c', '#ffc700'],
                    borderWidth: 0,
                    hoverOffset: 6
                }]
            },
            plugins: [ChartDataLabels],
            options: {
                responsive: false,
                maintainAspectRatio: false,
                layout: { padding: 10 },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': ' + context.raw + ' days';
                            }
                        }
                    },
                    datalabels: {
                        color: '#ffffff',
                        font: { weight: 'bold', size: 13 },
                        formatter: function (value, context) {
                            if (value === 0) return null;
                            var dataset = context.chart.data.datasets[0].data;
                            var sum = dataset.reduce(function (a, b) { return a + b; }, 0);
                            return Math.round((value / sum) * 100) + '%';
                        }
                    }
                }
            }
        });
    };

    // ── Calendar ──────────────────────────────────────────────────────────────

    var initCalendar = function () {
        calendarEl = document.getElementById('kt_attendance_calendar');
        if (!calendarEl) return;

        allEventsData = JSON.parse(calendarEl.getAttribute('data-events') || '[]');

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
                },
                dayGridMonth: {
                    showNonCurrentDates: false   // hide adjacent-month dates
                }
            },
            fixedWeekCount: false,              // no trailing empty rows
            initialView: 'dayGridMonth',
            height: 'auto',
            contentHeight: 650,
            aspectRatio: 3,
            initialDate: new Date(),
            navLinks: true,
            editable: false,
            dayMaxEvents: true,
            events: allEventsData,

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

            // Keep overview card in sync when calendar month changes
            datesSet: function (info) {
                var d = info.view.currentStart;
                updateOverview(d.getFullYear(), d.getMonth());
            },

            eventContent: function (arg) {
                if (arg.view.type === 'listMonth') {
                    var d = arg.event.start;
                    var fmt = pad(d.getDate()) + '-'
                        + d.toLocaleString('en-US', { month: 'short' }) + '-'
                        + d.getFullYear();
                    var time = arg.event.extendedProps.time || '';
                    var remarks = arg.event.extendedProps.description || '';

                    return {
                        html: '<div class="d-flex align-items-center flex-wrap gap-3 py-1">'
                            + '<span class="min-w-100px fw-bold text-gray-800 fs-6">' + fmt + '</span>'
                            + '<span class="badge" style="background-color:' + arg.event.backgroundColor
                            + ';color:white;font-size:0.9rem;">' + arg.event.title + '</span>'
                            + (time ? '<span class="text-gray-500 fs-7 ms-2"><i class="ki-outline ki-time fs-7 me-1"></i>' + time + '</span>' : '')
                            + (remarks ? '<span class="text-gray-500 fs-7 ms-2"><i class="ki-outline ki-message-text-2 fs-7 me-1"></i>' + remarks + '</span>' : '')
                            + '</div>'
                    };
                }
                return {
                    html: '<div class="fc-content" style="color:white;padding:1px 2px;">'
                        + arg.event.title + '</div>'
                };
            }
        });

        calendar.render();
    };

    // ── Export — captures both cards into one PNG ─────────────────────────────

    var initExport = function () {
        var btn = document.getElementById('kt_attendance_export_btn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            if (typeof html2canvas === 'undefined') {
                alert('html2canvas is not loaded. Add it to your vendor-js stack.');
                return;
            }

            // Immediately update button UI so browser repaints before heavy work
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2 align-middle" role="status" aria-hidden="true"></span>Exporting…';

            // Defer the heavy html2canvas work so the button repaint flushes first
            setTimeout(function () {

                // Meta
                var studentName = (calendarEl && calendarEl.getAttribute('data-student-name')) || 'Student';
                var studentId = (calendarEl && calendarEl.getAttribute('data-student-id')) || '';
                var monthLabel = calendar ? getMonthName(
                    calendar.getDate().getFullYear(),
                    calendar.getDate().getMonth()
                ) : '';

                // File name: StudentName_ID_Attendance_YYYYMMDD_HHMMSS.png
                var now = new Date();
                var ts = now.getFullYear()
                    + pad(now.getMonth() + 1)
                    + pad(now.getDate())
                    + '_' + pad(now.getHours())
                    + pad(now.getMinutes())
                    + pad(now.getSeconds());
                var fileName = studentName.replace(/\s+/g, '_')
                    + (studentId ? '_' + studentId : '')
                    + '_Attendance_' + ts + '.png';

                // Build a temporary off-screen wrapper that holds both cards
                var historyCard = document.getElementById('kt_attendance_history_card');
                var overviewCard = document.getElementById('kt_attendance_overview_card');

                var wrapper = document.createElement('div');
                wrapper.style.cssText = [
                    'position:absolute',
                    'left:-9999px',
                    'top:0',
                    'width:' + historyCard.offsetWidth + 'px',
                    'background:#ffffff',
                    'padding:0',
                    'font-family:inherit'
                ].join(';');

                // ── Header banner ──
                var banner = document.createElement('div');
                banner.style.cssText = 'padding:16px 24px 12px;background:#f9f9f9;border-bottom:1px solid #e4e6ef;';
                banner.innerHTML = '<div style="font-size:1.15rem;font-weight:700;color:#181c32;line-height:1.3;">'
                    + studentName
                    + (studentId ? ' <span style="color:#7e8299;font-size:0.9rem;font-weight:600;">(' + studentId + ')</span>' : '')
                    + '</div>'
                    + '<div style="font-size:0.82rem;color:#7e8299;margin-top:3px;">Attendance Report — ' + monthLabel + '</div>';
                wrapper.appendChild(banner);

                // ── Clone helper ──
                var cloneCard = function (sourceCard) {
                    var clone = sourceCard.cloneNode(true);
                    var toolbar = clone.querySelector('.card-toolbar');
                    if (toolbar) toolbar.remove();
                    clone.style.marginBottom = '0';
                    clone.style.borderRadius = '0';
                    clone.style.boxShadow = 'none';
                    return clone;
                };

                // ── Snapshot pie chart pixels into cloned canvas ──
                var overviewClone = cloneCard(overviewCard);
                var clonedCanvas = overviewClone.querySelector('#kt_attendance_pie_chart');
                if (clonedCanvas && pieChartInstance) {
                    var sourceCanvas = document.getElementById('kt_attendance_pie_chart');
                    clonedCanvas.width = sourceCanvas.width;
                    clonedCanvas.height = sourceCanvas.height;
                    clonedCanvas.style.width = sourceCanvas.style.width || sourceCanvas.offsetWidth + 'px';
                    clonedCanvas.style.height = sourceCanvas.style.height || sourceCanvas.offsetHeight + 'px';
                    clonedCanvas.getContext('2d').drawImage(sourceCanvas, 0, 0);
                }

                wrapper.appendChild(cloneCard(historyCard));
                wrapper.appendChild(overviewClone);
                document.body.appendChild(wrapper);

                html2canvas(wrapper, {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false
                }).then(function (canvasEl) {
                    document.body.removeChild(wrapper);

                    btn.disabled = false;
                    btn.innerHTML = '<i class="ki-outline ki-picture fs-4 me-1"></i>Export Image';

                    var link = document.createElement('a');
                    link.href = canvasEl.toDataURL('image/png');
                    link.download = fileName;
                    link.click();
                }).catch(function (err) {
                    document.body.removeChild(wrapper);

                    btn.disabled = false;
                    btn.innerHTML = '<i class="ki-outline ki-picture fs-4 me-1"></i>Export Image';

                    console.error('Attendance export error:', err);
                });

            }, 50); // 50ms is enough for the browser to flush the repaint
        });
    };

    // ── Overview month navigation ─────────────────────────────────────────────

    var initOverviewNav = function () {
        var prevBtn = document.getElementById('kt_overview_prev_month');
        var nextBtn = document.getElementById('kt_overview_next_month');

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                var m = currentOverviewMonth.month - 1;
                var y = currentOverviewMonth.year;
                if (m < 0) { m = 11; y--; }
                if (calendar) calendar.gotoDate(new Date(y, m, 1));
                // datesSet fires automatically and calls updateOverview
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                var m = currentOverviewMonth.month + 1;
                var y = currentOverviewMonth.year;
                if (m > 11) { m = 0; y++; }
                if (calendar) calendar.gotoDate(new Date(y, m, 1));
            });
        }
    };

    // ── Tab switch — resize calendar ──────────────────────────────────────────

    var handleTabSwitch = function () {
        var tabLink = document.querySelector('a[href="#kt_student_view_attendance_tab"]')
            || document.querySelector('button[data-bs-target="#kt_student_view_attendance_tab"]');
        if (tabLink) {
            tabLink.addEventListener('shown.bs.tab', function () {
                if (calendar) calendar.updateSize();
            });
        }
    };

    return {
        init: function () {
            initCalendar();      // triggers datesSet → updateOverview for current month
            initExport();
            initOverviewNav();
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