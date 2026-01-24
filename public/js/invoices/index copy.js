"use strict";

// Store DataTables instances and filters
var InvoiceManager = {
    tables: { due: {}, paid: {} },
    filters: { due: {}, paid: {} },
    initialized: { due: {}, paid: {} },

    // Reload all initialized due tables
    reloadDueTables: function () {
        var self = this;
        Object.keys(self.tables.due).forEach(function (tableId) {
            if (self.tables.due[tableId]) {
                self.tables.due[tableId].ajax.reload(null, false);
            }
        });
    },

    // Reload all initialized paid tables
    reloadPaidTables: function () {
        var self = this;
        Object.keys(self.tables.paid).forEach(function (tableId) {
            if (self.tables.paid[tableId]) {
                self.tables.paid[tableId].ajax.reload(null, false);
            }
        });
    },

    // Reload all tables
    reloadAllTables: function () {
        this.reloadDueTables();
        this.reloadPaidTables();
    },

    // Reload specific table by ID
    reloadTable: function (tableId) {
        if (this.tables.due[tableId]) {
            this.tables.due[tableId].ajax.reload(null, false);
        }
        if (this.tables.paid[tableId]) {
            this.tables.paid[tableId].ajax.reload(null, false);
        }
    },

    // Update branch due counts badges
    updateBranchDueCounts: function () {
        if (typeof routeBranchDueCounts === 'undefined') return;

        $.get(routeBranchDueCounts, function (response) {
            if (response.counts) {
                Object.keys(response.counts).forEach(function (branchId) {
                    var count = response.counts[branchId];
                    var tabLink = document.querySelector('#branchTabsDue .nav-link[data-branch-id="' + branchId + '"]');
                    if (tabLink) {
                        // Get the badge color from data attribute
                        var badgeColor = tabLink.getAttribute('data-badge-color') || 'badge-light-primary';

                        // Remove existing badge
                        var existingBadge = tabLink.querySelector('.badge');
                        if (existingBadge) {
                            existingBadge.remove();
                        }
                        // Add new badge if count > 0
                        if (count > 0) {
                            var badge = document.createElement('span');
                            badge.className = 'badge ' + badgeColor + ' badge-sm ms-2';
                            badge.textContent = count;
                            tabLink.appendChild(badge);
                        }
                    }
                });
            }
        });
    },

    // Get current active due table ID
    getActiveDueTableId: function () {
        if (isAdmin) {
            var activeTab = document.querySelector('#branchTabsDue .nav-link.active');
            if (activeTab) {
                var branchId = activeTab.getAttribute('data-branch-id');
                return 'kt_due_invoices_table_' + branchId;
            }
        }
        return 'kt_due_invoices_table';
    },

    // Get current active paid table ID
    getActivePaidTableId: function () {
        if (isAdmin) {
            var activeTab = document.querySelector('#branchTabsPaid .nav-link.active');
            if (activeTab) {
                var branchId = activeTab.getAttribute('data-branch-id');
                return 'kt_paid_invoices_table_' + branchId;
            }
        }
        return 'kt_paid_invoices_table';
    }
};

// Utility Functions
var InvoiceUtils = {
    getCsrfToken: function () {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    },
    escapeHtml: function (text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    formatDate: function () {
        var now = new Date();
        return now.toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    },
    formatDateShort: function () {
        var now = new Date();
        return now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).replace(/[,:]/g, '-');
    }
};

// Due Invoices DataTable Manager
var KTDueInvoicesList = function () {

    var initTable = function (tableId, branchId) {
        var table = document.getElementById(tableId);
        if (!table || InvoiceManager.initialized.due[tableId]) return;

        InvoiceManager.initialized.due[tableId] = true;
        InvoiceManager.filters.due[tableId] = {};

        // Load filter options
        loadFilterOptions(branchId, tableId);

        // Column indexes:
        // 0-SL, 1-Invoice No, 2-Student, 3-Invoice Type, 4-Billing Month, 5-Total Amount,
        // 6-Remaining, 7-Due Date, 8-Status, 9-Last Comment, 10-Created At, 11-Actions

        // Initialize DataTable with server-side processing
        InvoiceManager.tables.due[tableId] = $(table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: routeUnpaidAjax,
                type: 'GET',
                data: function (d) {
                    d.branch_id = branchId;
                    var filters = InvoiceManager.filters.due[tableId] || {};
                    if (filters.invoice_type) d.invoice_type = filters.invoice_type;
                    if (filters.due_date) d.due_date = filters.due_date;
                    if (filters.status) d.status = filters.status;
                    if (filters.billing_month) d.billing_month = filters.billing_month;
                    return d;
                },
                dataSrc: function (json) {
                    return json.data || [];
                }
            },
            columns: [
                { data: 'sl', orderable: false },
                {
                    data: null, render: function (data) {
                        var url = routeInvoiceShow.replace(':id', data.id);
                        var badge = data.comments_count > 0 ? '<span class="badge badge-circle badge-sm badge-primary ms-1">' + data.comments_count + '</span>' : '';
                        return '<a class="text-gray-600 text-hover-primary" href="' + url + '">' + InvoiceUtils.escapeHtml(data.invoice_number) + '</a>' + badge;
                    }
                },
                {
                    data: null, render: function (data) {
                        var url = routeStudentShow.replace(':id', data.student_id);
                        return '<a class="text-gray-600 text-hover-primary" href="' + url + '" target="_blank">' + InvoiceUtils.escapeHtml(data.student_name) + ', ' + InvoiceUtils.escapeHtml(data.student_unique_id) + '</a>';
                    }
                },
                { data: 'invoice_type' },
                {
                    data: null, render: function (data) {
                        return data.billing_month === 'One Time' ? '<span class="badge badge-primary rounded-pill">One Time</span>' : InvoiceUtils.escapeHtml(data.billing_month);
                    }
                },
                { data: 'total_amount' },
                { data: 'amount_due' },
                { data: 'due_date' },
                { data: 'status_html', orderable: false },
                {
                    data: null, orderable: false, render: function (data) {
                        if (!data.last_comment) return '-';
                        var truncated = data.last_comment.length > 50 ? data.last_comment.substring(0, 50) + '...' : data.last_comment;
                        return '<div class="text-gray-800 fs-7">' + InvoiceUtils.escapeHtml(truncated) + '</div>';
                    }
                },
                {
                    data: null, render: function (data) {
                        return data.created_at + '<br><small class="text-muted">' + data.created_at_time + '</small>';
                    }
                },
                {
                    data: null, orderable: false, render: function (data) {
                        var actions = '';
                        if (data.status === 'due') {
                            if (canEditInvoice) {
                                actions += '<div class="menu-item px-3"><a href="#" data-invoice-id="' + data.id + '" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_invoice" class="menu-link text-hover-primary px-3"><i class="ki-outline ki-pencil fs-3 me-2"></i> Edit Invoice</a></div>';
                            }
                            if (canViewInvoice) {
                                actions += '<div class="menu-item px-3"><a href="#" data-invoice-id="' + data.id + '" data-invoice-number="' + data.invoice_number + '" data-bs-toggle="modal" data-bs-target="#kt_modal_add_comment" class="menu-link text-hover-primary px-3 add-comment-btn"><i class="ki-outline ki-messages fs-3 me-2"></i> Add Comment</a></div>';
                            }
                            if (canDeleteInvoice) {
                                actions += '<div class="menu-item px-3"><a href="#" data-invoice-id="' + data.id + '" class="menu-link text-hover-danger px-3 delete-invoice"><i class="ki-outline ki-trash fs-3 me-2"></i> Delete Invoice</a></div>';
                            }
                        } else if (data.status === 'partially_paid') {
                            if (canViewInvoice) {
                                actions += '<div class="menu-item px-3"><a href="#" data-invoice-id="' + data.id + '" data-invoice-number="' + data.invoice_number + '" data-bs-toggle="modal" data-bs-target="#kt_modal_add_comment" class="menu-link text-hover-primary px-3 add-comment-btn"><i class="ki-outline ki-messages fs-3 me-2"></i> Add Comment</a></div>';
                            }
                        }
                        return '<a href="#" class="btn btn-light btn-active-light-primary btn-sm" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions <i class="ki-outline ki-down fs-5 m-0"></i></a>' +
                            '<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-175px py-4" data-kt-menu="true">' + actions + '</div>';
                    }
                }
            ],
            order: [[10, 'desc']], // Default sort by created_at descending
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            drawCallback: function () {
                KTMenu.createInstances();
            }
        });

        // Search handler with debounce
        var searchInput = document.querySelector('.due-invoice-search[data-table-id="' + tableId + '"]');
        if (searchInput) {
            var searchTimeout;
            searchInput.addEventListener('keyup', function () {
                var value = this.value;
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    InvoiceManager.tables.due[tableId].search(value).draw();
                }, 500);
            });
        }
    };

    var loadFilterOptions = function (branchId, tableId) {
        $.get(routeFilterOptions, { branch_id: branchId }, function (data) {
            // Populate invoice type filter
            var typeSelect = document.querySelector('.filter-invoice-type[data-table-id="' + tableId + '"]');
            if (typeSelect) {
                typeSelect.innerHTML = '<option></option>';
                invoiceTypes.forEach(function (type) {
                    typeSelect.innerHTML += '<option value="ucms_' + type.type_name + '">' + type.type_name + '</option>';
                });
                $(typeSelect).select2({ placeholder: 'Select option', allowClear: true });
            }

            // Populate billing month filter
            var monthSelect = document.querySelector('.filter-billing-month[data-table-id="' + tableId + '"]');
            if (monthSelect && data.dueMonths) {
                monthSelect.innerHTML = '<option></option>';
                data.dueMonths.forEach(function (month) {
                    monthSelect.innerHTML += '<option value="' + month.value + '">' + month.label + '</option>';
                });
                $(monthSelect).select2({ placeholder: 'Select option', allowClear: true });
            }
        });
    };

    var handleFilter = function () {
        // Apply filter
        $(document).on('click', '.filter-apply-btn', function () {
            var tableId = this.getAttribute('data-table-id');
            var filters = {};

            var typeSelect = document.querySelector('.filter-invoice-type[data-table-id="' + tableId + '"]');
            var dueDateSelect = document.querySelector('.filter-due-date[data-table-id="' + tableId + '"]');
            var statusSelect = document.querySelector('.filter-status[data-table-id="' + tableId + '"]');
            var monthSelect = document.querySelector('.filter-billing-month[data-table-id="' + tableId + '"]');

            if (typeSelect && typeSelect.value) filters.invoice_type = typeSelect.value;
            if (dueDateSelect && dueDateSelect.value) filters.due_date = dueDateSelect.value;
            if (statusSelect && statusSelect.value) filters.status = statusSelect.value;
            if (monthSelect && monthSelect.value) filters.billing_month = monthSelect.value;

            InvoiceManager.filters.due[tableId] = filters;
            if (InvoiceManager.tables.due[tableId]) {
                InvoiceManager.tables.due[tableId].ajax.reload();
            }
        });

        // Reset filter
        $(document).on('click', '.filter-reset-btn', function () {
            var tableId = this.getAttribute('data-table-id');

            var typeSelect = document.querySelector('.filter-invoice-type[data-table-id="' + tableId + '"]');
            var dueDateSelect = document.querySelector('.filter-due-date[data-table-id="' + tableId + '"]');
            var statusSelect = document.querySelector('.filter-status[data-table-id="' + tableId + '"]');
            var monthSelect = document.querySelector('.filter-billing-month[data-table-id="' + tableId + '"]');

            if (typeSelect) $(typeSelect).val(null).trigger('change');
            if (dueDateSelect) $(dueDateSelect).val(null).trigger('change');
            if (statusSelect) $(statusSelect).val(null).trigger('change');
            if (monthSelect) $(monthSelect).val(null).trigger('change');

            InvoiceManager.filters.due[tableId] = {};
            if (InvoiceManager.tables.due[tableId]) {
                InvoiceManager.tables.due[tableId].ajax.reload();
            }
        });
    };

    var handleDeletion = function () {
        $(document).on('click', '.delete-invoice', function (e) {
            e.preventDefault();
            var invoiceId = this.getAttribute('data-invoice-id');
            var url = routeDeleteInvoice.replace(':id', invoiceId);

            Swal.fire({
                title: "Are you sure to delete this invoice?",
                text: "This action cannot be undone!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete!",
            }).then(function (result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': InvoiceUtils.getCsrfToken() },
                        success: function (data) {
                            if (data.success) {
                                Swal.fire("Deleted!", "The invoice has been deleted successfully.", "success");
                                // Reload tables via AJAX and update badge counts
                                InvoiceManager.reloadDueTables();
                                InvoiceManager.updateBranchDueCounts();
                            } else {
                                Swal.fire("Error!", data.error || "Something went wrong.", "error");
                            }
                        },
                        error: function () {
                            Swal.fire("Error!", "Something went wrong. Please try again.", "error");
                        }
                    });
                }
            });
        });
    };

    var handleBranchTabs = function () {
        if (!isAdmin) return;

        $(document).on('shown.bs.tab', '#branchTabsDue .nav-link', function () {
            var branchId = this.getAttribute('data-branch-id');
            var tableId = 'kt_due_invoices_table_' + branchId;

            if (!InvoiceManager.initialized.due[tableId]) {
                initTable(tableId, branchId);
            }
        });
    };

    return {
        init: function () {
            if (isAdmin && firstBranchId) {
                initTable('kt_due_invoices_table_' + firstBranchId, firstBranchId);
            } else if (!isAdmin) {
                initTable('kt_due_invoices_table', null);
            }
            handleFilter();
            handleDeletion();
            handleBranchTabs();
        }
    };
}();

// Paid Invoices DataTable Manager
var KTPaidInvoicesList = function () {

    var initTable = function (tableId, branchId) {
        var table = document.getElementById(tableId);
        if (!table || InvoiceManager.initialized.paid[tableId]) return;

        InvoiceManager.initialized.paid[tableId] = true;
        InvoiceManager.filters.paid[tableId] = {};

        loadFilterOptions(branchId, tableId);

        // Column indexes:
        // 0-SL, 1-Invoice No, 2-Student, 3-Invoice Type, 4-Amount, 5-Billing Month,
        // 6-Due Date, 7-Status, 8-Last Comment, 9-Created At

        InvoiceManager.tables.paid[tableId] = $(table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: routePaidAjax,
                type: 'GET',
                data: function (d) {
                    d.branch_id = branchId;
                    var filters = InvoiceManager.filters.paid[tableId] || {};
                    if (filters.invoice_type) d.invoice_type = filters.invoice_type;
                    if (filters.due_date) d.due_date = filters.due_date;
                    if (filters.billing_month) d.billing_month = filters.billing_month;
                    return d;
                },
                dataSrc: function (json) {
                    return json.data || [];
                }
            },
            columns: [
                { data: 'sl', orderable: false },
                {
                    data: null, render: function (data) {
                        var url = routeInvoiceShow.replace(':id', data.id);
                        var badge = data.comments_count > 0 ? '<span class="badge badge-circle badge-sm badge-primary ms-1">' + data.comments_count + '</span>' : '';
                        return '<a class="text-gray-600 text-hover-primary" href="' + url + '">' + InvoiceUtils.escapeHtml(data.invoice_number) + '</a>' + badge;
                    }
                },
                {
                    data: null, render: function (data) {
                        var url = routeStudentShow.replace(':id', data.student_id);
                        return '<a class="text-gray-600 text-hover-primary" href="' + url + '">' + InvoiceUtils.escapeHtml(data.student_name) + ', ' + InvoiceUtils.escapeHtml(data.student_unique_id) + '</a>';
                    }
                },
                { data: 'invoice_type' },
                { data: 'total_amount' },
                { data: 'billing_month' },
                { data: 'due_date' },
                {
                    data: null, orderable: false, render: function () {
                        return '<span class="badge badge-success rounded-pill">Paid</span>';
                    }
                },
                {
                    data: null, orderable: false, render: function (data) {
                        if (!data.last_comment) return '-';
                        var truncated = data.last_comment.length > 50 ? data.last_comment.substring(0, 50) + '...' : data.last_comment;
                        return '<div class="text-gray-800 fs-7">' + InvoiceUtils.escapeHtml(truncated) + '</div>';
                    }
                },
                {
                    data: null, render: function (data) {
                        return data.created_at + '<br><small class="text-muted">' + data.created_at_time + '</small>';
                    }
                }
            ],
            order: [[9, 'desc']], // Default sort by created_at descending
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10
        });

        // Search handler with debounce
        var searchInput = document.querySelector('.paid-invoice-search[data-table-id="' + tableId + '"]');
        if (searchInput) {
            var searchTimeout;
            searchInput.addEventListener('keyup', function () {
                var value = this.value;
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    InvoiceManager.tables.paid[tableId].search(value).draw();
                }, 500);
            });
        }
    };

    var loadFilterOptions = function (branchId, tableId) {
        $.get(routeFilterOptions, { branch_id: branchId }, function (data) {
            var typeSelect = document.querySelector('.filter-invoice-type-paid[data-table-id="' + tableId + '"]');
            if (typeSelect) {
                typeSelect.innerHTML = '<option></option>';
                invoiceTypes.forEach(function (type) {
                    typeSelect.innerHTML += '<option value="ucms_' + type.type_name + '">' + type.type_name + '</option>';
                });
                $(typeSelect).select2({ placeholder: 'Select option', allowClear: true });
            }

            var monthSelect = document.querySelector('.filter-billing-month-paid[data-table-id="' + tableId + '"]');
            if (monthSelect && data.paidMonths) {
                monthSelect.innerHTML = '<option></option>';
                data.paidMonths.forEach(function (month) {
                    monthSelect.innerHTML += '<option value="' + month.value + '">' + month.label + '</option>';
                });
                $(monthSelect).select2({ placeholder: 'Select option', allowClear: true });
            }
        });
    };

    var handleFilter = function () {
        $(document).on('click', '.filter-apply-btn-paid', function () {
            var tableId = this.getAttribute('data-table-id');
            var filters = {};

            var typeSelect = document.querySelector('.filter-invoice-type-paid[data-table-id="' + tableId + '"]');
            var dueDateSelect = document.querySelector('.filter-due-date-paid[data-table-id="' + tableId + '"]');
            var monthSelect = document.querySelector('.filter-billing-month-paid[data-table-id="' + tableId + '"]');

            if (typeSelect && typeSelect.value) filters.invoice_type = typeSelect.value;
            if (dueDateSelect && dueDateSelect.value) filters.due_date = dueDateSelect.value;
            if (monthSelect && monthSelect.value) filters.billing_month = monthSelect.value;

            InvoiceManager.filters.paid[tableId] = filters;
            if (InvoiceManager.tables.paid[tableId]) {
                InvoiceManager.tables.paid[tableId].ajax.reload();
            }
        });

        $(document).on('click', '.filter-reset-btn-paid', function () {
            var tableId = this.getAttribute('data-table-id');

            var typeSelect = document.querySelector('.filter-invoice-type-paid[data-table-id="' + tableId + '"]');
            var dueDateSelect = document.querySelector('.filter-due-date-paid[data-table-id="' + tableId + '"]');
            var monthSelect = document.querySelector('.filter-billing-month-paid[data-table-id="' + tableId + '"]');

            if (typeSelect) $(typeSelect).val(null).trigger('change');
            if (dueDateSelect) $(dueDateSelect).val(null).trigger('change');
            if (monthSelect) $(monthSelect).val(null).trigger('change');

            InvoiceManager.filters.paid[tableId] = {};
            if (InvoiceManager.tables.paid[tableId]) {
                InvoiceManager.tables.paid[tableId].ajax.reload();
            }
        });
    };

    var handleBranchTabs = function () {
        if (!isAdmin) return;

        $(document).on('shown.bs.tab', '#branchTabsPaid .nav-link', function () {
            var branchId = this.getAttribute('data-branch-id');
            var tableId = 'kt_paid_invoices_table_' + branchId;

            if (!InvoiceManager.initialized.paid[tableId]) {
                initTable(tableId, branchId);
            }
        });
    };

    var handleMainTabSwitch = function () {
        $('a[href="#kt_paid_invoices_tab"]').on('shown.bs.tab', function () {
            if (isAdmin && firstBranchId) {
                var tableId = 'kt_paid_invoices_table_' + firstBranchId;
                if (!InvoiceManager.initialized.paid[tableId]) {
                    initTable(tableId, firstBranchId);
                }
            } else if (!isAdmin) {
                if (!InvoiceManager.initialized.paid['kt_paid_invoices_table']) {
                    initTable('kt_paid_invoices_table', null);
                }
            }
        });
    };

    return {
        init: function () {
            handleFilter();
            handleBranchTabs();
            handleMainTabSwitch();
        }
    };
}();

// Export Manager
var KTExportManager = function () {

    var fetchExportData = function (tableId, type, callback) {
        var table = document.getElementById(tableId);
        var branchId = table ? table.getAttribute('data-branch-id') : null;
        var filters = type === 'due' ? InvoiceManager.filters.due[tableId] : InvoiceManager.filters.paid[tableId];
        filters = filters || {};

        var params = { type: type, branch_id: branchId };
        if (filters.invoice_type) params.invoice_type = filters.invoice_type;
        if (filters.due_date) params.due_date = filters.due_date;
        if (filters.status) params.status = filters.status;
        if (filters.billing_month) params.billing_month = filters.billing_month;

        // Add search value
        var dt = type === 'due' ? InvoiceManager.tables.due[tableId] : InvoiceManager.tables.paid[tableId];
        if (dt) {
            var searchValue = dt.search();
            if (searchValue) params.search = searchValue;
        }

        $.get(routeExportAjax, params, function (response) {
            callback(response.data || []);
        }).fail(function () {
            toastr.error('Failed to fetch export data');
            callback([]);
        });
    };

    var formatExportData = function (data, type) {
        return data.map(function (row, index) {
            var lastCommentExport = '';
            if (row.last_comment) {
                lastCommentExport = row.last_comment;
                if (row.last_comment_by) lastCommentExport += ' [By: ' + row.last_comment_by + ']';
                if (row.last_comment_at) lastCommentExport += ' [At: ' + row.last_comment_at + ']';
            }

            if (type === 'due') {
                return {
                    'SL': index + 1,
                    'Invoice No.': row.invoice_number,
                    'Student Name': row.student_name,
                    'Student ID': row.student_unique_id,
                    'Mobile': row.mobile || '',
                    'Invoice Type': row.invoice_type,
                    'Billing Month': row.billing_month,
                    'Total Amount (Tk)': row.total_amount,
                    'Remaining (Tk)': row.amount_due,
                    'Due Date': row.due_date,
                    'Status': row.status_text,
                    'Last Comment': lastCommentExport,
                    'Created At': row.created_at + ' ' + row.created_at_time
                };
            } else {
                return {
                    'SL': index + 1,
                    'Invoice No.': row.invoice_number,
                    'Student Name': row.student_name,
                    'Student ID': row.student_unique_id,
                    'Mobile': row.mobile || '',
                    'Invoice Type': row.invoice_type,
                    'Amount (Tk)': row.total_amount,
                    'Billing Month': row.billing_month,
                    'Due Date': row.due_date,
                    'Status': row.status_text || 'Paid',
                    'Last Comment': lastCommentExport,
                    'Created At': row.created_at + ' ' + row.created_at_time
                };
            }
        });
    };

    var copyToClipboard = function (data) {
        if (data.length === 0) { toastr.warning('No data to export'); return; }
        var headers = Object.keys(data[0]);
        var text = headers.join('\t') + '\n';
        data.forEach(function (row) {
            text += headers.map(function (h) { return row[h] || ''; }).join('\t') + '\n';
        });
        navigator.clipboard.writeText(text).then(function () {
            toastr.success('Data copied to clipboard');
        }).catch(function () { toastr.error('Failed to copy to clipboard'); });
    };

    var exportExcel = function (data, type) {
        if (data.length === 0) { toastr.warning('No data to export'); return; }

        var title = type === 'due' ? 'Due Invoices Report' : 'Paid Invoices Report';
        var fileName = title + '_' + InvoiceUtils.formatDateShort();

        var ws = XLSX.utils.aoa_to_sheet([]);
        XLSX.utils.sheet_add_aoa(ws, [[title]], { origin: 'A1' });
        XLSX.utils.sheet_add_aoa(ws, [['Generated: ' + InvoiceUtils.formatDate()]], { origin: 'A2' });
        XLSX.utils.sheet_add_aoa(ws, [['']], { origin: 'A3' });
        XLSX.utils.sheet_add_json(ws, data, { origin: 'A4' });

        var colWidths = Object.keys(data[0]).map(function (key) {
            return { wch: Math.max(key.length, 15) };
        });
        ws['!cols'] = colWidths;
        ws['!merges'] = [{ s: { r: 0, c: 0 }, e: { r: 0, c: Object.keys(data[0]).length - 1 } }];

        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Invoices');
        XLSX.writeFile(wb, fileName + '.xlsx');
        toastr.success('Excel file downloaded');
    };

    var exportCSV = function (data, type) {
        if (data.length === 0) { toastr.warning('No data to export'); return; }

        var title = type === 'due' ? 'Due Invoices Report' : 'Paid Invoices Report';
        var fileName = title + '_' + InvoiceUtils.formatDateShort();

        var ws = XLSX.utils.json_to_sheet(data);
        var csv = XLSX.utils.sheet_to_csv(ws);
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = fileName + '.csv';
        link.click();
        toastr.success('CSV file downloaded');
    };

    var exportPDF = function (data, type) {
        if (data.length === 0) { toastr.warning('No data to export'); return; }

        var jsPDF = window.jspdf.jsPDF;
        var doc = new jsPDF('l', 'mm', 'a4');

        var title = type === 'due' ? 'Due Invoices Report' : 'Paid Invoices Report';
        var fileName = title + '_' + InvoiceUtils.formatDateShort();

        doc.setFontSize(16);
        doc.text(title, 14, 15);
        doc.setFontSize(10);
        doc.text('Generated: ' + InvoiceUtils.formatDate(), 14, 22);

        var headers = Object.keys(data[0]);
        var rows = data.map(function (row) {
            return headers.map(function (h) { return row[h] || ''; });
        });

        doc.autoTable({
            head: [headers],
            body: rows,
            startY: 28,
            styles: { fontSize: 7 },
            headStyles: { fillColor: [41, 128, 185] },
            margin: { left: 10, right: 10 },
            didDrawPage: function () {
                doc.setFontSize(8);
                doc.text('Page ' + doc.internal.getNumberOfPages(), doc.internal.pageSize.width / 2, doc.internal.pageSize.height - 10, { align: 'center' });
            }
        });

        doc.save(fileName + '.pdf');
        toastr.success('PDF file downloaded');
    };

    var handleExportButtons = function () {
        $(document).on('click', '.export-btn', function (e) {
            e.preventDefault();

            var tableId = this.getAttribute('data-table-id');
            var type = this.getAttribute('data-type');
            var exportType = this.getAttribute('data-export');
            var btn = this;

            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';

            fetchExportData(tableId, type, function (rawData) {
                var data = formatExportData(rawData, type);

                switch (exportType) {
                    case 'copy': copyToClipboard(data); break;
                    case 'excel': exportExcel(data, type); break;
                    case 'csv': exportCSV(data, type); break;
                    case 'pdf': exportPDF(data, type); break;
                }

                btn.innerHTML = exportType === 'copy' ? 'Copy to clipboard' :
                    exportType === 'excel' ? 'Export as Excel' :
                        exportType === 'csv' ? 'Export as CSV' : 'Export as PDF';
            });
        });
    };

    return {
        init: function () {
            handleExportButtons();
        }
    };
}();

// Create Invoice Modal
var KTCreateInvoiceModal = function () {
    var element, form, modal, submitButton, validator;
    var studentSelect, invoiceTypeSelect, monthYearSelect, invoiceAmountInput;
    var monthYearTypeRadios, monthYearTypeWrapper, monthYearWrapper;
    var invoiceData = { tuitionFee: null, paymentStyle: null, lastInvoiceMonth: null, oldestInvoiceMonth: null };

    var getSelectedTypeName = function () {
        var selectedOption = invoiceTypeSelect.options[invoiceTypeSelect.selectedIndex];
        return selectedOption ? (selectedOption.getAttribute('data-type-name') || selectedOption.text.trim()) : '';
    };

    var formatMonthYear = function (month, year) {
        var date = new Date(year, month - 1, 1);
        return date.toLocaleString('default', { month: 'long' }) + ' ' + year;
    };

    var setMonthYearOption = function (value, text) {
        monthYearSelect.innerHTML = '<option value=""></option>';
        if (value && text) {
            var option = document.createElement('option');
            option.value = value;
            option.textContent = text;
            monthYearSelect.appendChild(option);
            $(monthYearSelect).trigger('change');
        }
    };

    var calculateNewInvoiceMonth = function () {
        var month, year;
        if (invoiceData.lastInvoiceMonth) {
            var parts = invoiceData.lastInvoiceMonth.split('_').map(Number);
            month = parts[0] + 1; year = parts[1];
            if (month > 12) { month = 1; year++; }
        } else {
            var currentDate = new Date();
            if (invoiceData.paymentStyle === 'due') {
                month = currentDate.getMonth(); year = currentDate.getFullYear();
                if (month === 0) { month = 12; year--; }
            } else {
                month = currentDate.getMonth() + 1; year = currentDate.getFullYear();
            }
        }
        var monthStr = String(month).padStart(2, '0');
        return { value: monthStr + '_' + year, text: formatMonthYear(month, year) };
    };

    var calculateOldInvoiceMonth = function () {
        var month, year;
        if (invoiceData.oldestInvoiceMonth) {
            var parts = invoiceData.oldestInvoiceMonth.split('_').map(Number);
            month = parts[0] - 1; year = parts[1];
            if (month < 1) { month = 12; year--; }
        } else {
            var currentDate = new Date();
            if (invoiceData.paymentStyle === 'due') {
                month = currentDate.getMonth() - 1; year = currentDate.getFullYear();
                if (month < 0) { month = 11; year--; }
            } else {
                month = currentDate.getMonth(); year = currentDate.getFullYear();
                if (month === 0) { month = 12; year--; }
            }
        }
        var monthStr = String(month).padStart(2, '0');
        return { value: monthStr + '_' + year, text: formatMonthYear(month, year) };
    };

    var handleStudentChange = function () {
        $(studentSelect).on('change', function () {
            var studentId = this.value;
            if (studentId) {
                invoiceTypeSelect.disabled = false;
                monthYearTypeRadios.forEach(function (radio) { radio.disabled = false; });
                monthYearSelect.innerHTML = '<option value="">Loading...</option>';
                monthYearSelect.disabled = true;
                invoiceAmountInput.value = '';
                invoiceAmountInput.disabled = true;

                $.get('/students/' + studentId + '/invoice-months-data', function (data) {
                    invoiceData.tuitionFee = data.tuition_fee;
                    invoiceData.paymentStyle = data.payment_style;
                    invoiceData.lastInvoiceMonth = data.last_invoice_month;
                    invoiceData.oldestInvoiceMonth = data.oldest_invoice_month;

                    var newMonth = calculateNewInvoiceMonth();
                    setMonthYearOption(newMonth.value, newMonth.text);
                    monthYearSelect.disabled = false;

                    var selectedTypeName = getSelectedTypeName();
                    if (selectedTypeName === 'Tuition Fee' && monthYearSelect.value) {
                        invoiceAmountInput.value = invoiceData.tuitionFee || '';
                        invoiceAmountInput.disabled = false;
                    }
                }).fail(function () {
                    monthYearSelect.innerHTML = '<option value="">Error loading months</option>';
                    toastr.error('Failed to load invoice data');
                });
            } else {
                invoiceTypeSelect.disabled = true;
                monthYearSelect.disabled = true;
                monthYearSelect.innerHTML = '<option value=""></option>';
                invoiceAmountInput.value = '';
                invoiceAmountInput.disabled = true;
                monthYearTypeRadios.forEach(function (radio) { radio.disabled = true; });
                $(invoiceTypeSelect).val(null).trigger('change');
                $(monthYearSelect).trigger('change');
            }
        });
    };

    var handleMonthYearTypeChange = function () {
        monthYearTypeRadios.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (!studentSelect.value) return;
                var monthData = this.value === 'new_invoice' ? calculateNewInvoiceMonth() : calculateOldInvoiceMonth();
                setMonthYearOption(monthData.value, monthData.text);

                var selectedTypeName = getSelectedTypeName();
                if (monthYearSelect.value && selectedTypeName === 'Tuition Fee') {
                    invoiceAmountInput.value = invoiceData.tuitionFee || '';
                    invoiceAmountInput.disabled = false;
                } else {
                    invoiceAmountInput.value = '';
                    invoiceAmountInput.disabled = true;
                }
            });
        });
    };

    var handleMonthYearChange = function () {
        $(monthYearSelect).on('change', function () {
            var selectedTypeName = getSelectedTypeName();
            if (this.value) {
                invoiceAmountInput.disabled = false;
                if (selectedTypeName === 'Tuition Fee') invoiceAmountInput.value = invoiceData.tuitionFee || '';
            } else {
                invoiceAmountInput.disabled = true;
            }
        });
    };

    var handleInvoiceTypeChange = function () {
        $(invoiceTypeSelect).on('change', function () {
            var selectedTypeName = getSelectedTypeName();
            var studentId = studentSelect.value;

            if (selectedTypeName === 'Sheet Fee') {
                monthYearTypeWrapper.style.display = 'none';
                monthYearWrapper.style.display = 'none';
                monthYearSelect.required = false;
                monthYearTypeRadios.forEach(function (radio) { radio.disabled = true; });
                invoiceAmountInput.disabled = true;
                invoiceAmountInput.value = '';

                if (studentId) {
                    $.get('/students/' + studentId + '/sheet-fee', function (data) {
                        if (data.sheet_fee) {
                            invoiceAmountInput.value = data.sheet_fee;
                            invoiceAmountInput.disabled = false;
                        } else {
                            invoiceAmountInput.value = '0';
                            invoiceAmountInput.disabled = false;
                            toastr.warning('No sheet fee found for the student\'s class.');
                        }
                    }).fail(function () {
                        invoiceAmountInput.value = '';
                        invoiceAmountInput.disabled = false;
                        toastr.error('Failed to fetch sheet fee.');
                    });
                }
            } else if (selectedTypeName !== 'Tuition Fee') {
                monthYearTypeWrapper.style.display = 'none';
                monthYearWrapper.style.display = 'none';
                monthYearSelect.required = false;
                monthYearTypeRadios.forEach(function (radio) { radio.disabled = true; });
                invoiceAmountInput.disabled = false;
                invoiceAmountInput.value = '';
            } else {
                monthYearTypeWrapper.style.display = '';
                monthYearWrapper.style.display = '';
                monthYearSelect.required = true;
                monthYearTypeRadios.forEach(function (radio) { radio.disabled = false; });
                invoiceAmountInput.disabled = !monthYearSelect.value;
                if (monthYearSelect.value) invoiceAmountInput.value = invoiceData.tuitionFee || '';
            }
        });
    };

    var resetForm = function () {
        form.reset();
        $(studentSelect).val(null).trigger('change');
        $(invoiceTypeSelect).val(null).trigger('change');
        $(monthYearSelect).empty().append('<option value=""></option>').trigger('change');
        invoiceTypeSelect.disabled = true;
        monthYearSelect.disabled = true;
        invoiceAmountInput.value = '';
        invoiceAmountInput.disabled = true;
        monthYearTypeWrapper.style.display = '';
        monthYearWrapper.style.display = '';
        monthYearSelect.required = true;
        monthYearTypeRadios.forEach(function (radio) { radio.disabled = true; });
        document.getElementById('new_invoice_input').checked = true;
        invoiceData = { tuitionFee: null, paymentStyle: null, lastInvoiceMonth: null, oldestInvoiceMonth: null };
        if (validator) validator.resetForm();
    };

    var handleModalClose = function () {
        var cancelButton = element.querySelector('[data-kt-add-invoice-modal-action="cancel"]');
        var closeButton = element.querySelector('[data-kt-add-invoice-modal-action="close"]');
        cancelButton.addEventListener('click', function (e) { e.preventDefault(); resetForm(); modal.hide(); });
        closeButton.addEventListener('click', function (e) { e.preventDefault(); resetForm(); modal.hide(); });
        element.addEventListener('hidden.bs.modal', function () { resetForm(); });
    };

    var initValidation = function () {
        validator = FormValidation.formValidation(form, {
            fields: {
                'invoice_student': { validators: { notEmpty: { message: 'Student is required' } } },
                'invoice_type': { validators: { notEmpty: { message: 'Invoice type is required' } } },
                'invoice_amount': { validators: { notEmpty: { message: 'Amount is required' }, greaterThan: { min: 50, message: 'Amount must be at least 50' } } }
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: '.fv-row', eleInvalidClass: '', eleValidClass: '' })
            }
        });
    };

    var handleFormSubmit = function () {
        submitButton = element.querySelector('[data-kt-add-invoice-modal-action="submit"]');
        submitButton.addEventListener('click', function (e) {
            e.preventDefault();
            if (validator) {
                validator.validate().then(function (status) {
                    if (status === 'Valid') {
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        submitButton.disabled = true;

                        var formData = new FormData(form);
                        formData.append('_token', InvoiceUtils.getCsrfToken());

                        $.ajax({
                            url: routeStoreInvoice,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (data) {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                if (data.success) {
                                    Swal.fire({ text: data.message || 'Invoice created successfully!', icon: 'success', buttonsStyling: false, confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' } }).then(function (result) {
                                        if (result.isConfirmed) {
                                            modal.hide();
                                            resetForm();
                                            // Reload tables via AJAX and update badge counts
                                            InvoiceManager.reloadDueTables();
                                            InvoiceManager.updateBranchDueCounts();
                                        }
                                    });
                                } else {
                                    Swal.fire({ html: data.message || 'Failed to create invoice', icon: 'error', buttonsStyling: false, confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' } });
                                }
                            },
                            error: function (xhr) {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                var message = 'Something went wrong. Please try again.';
                                if (xhr.responseJSON && xhr.responseJSON.message) message = xhr.responseJSON.message;
                                if (xhr.responseJSON && xhr.responseJSON.errors) {
                                    var errors = [];
                                    $.each(xhr.responseJSON.errors, function (key, val) { errors.push(val[0]); });
                                    message = errors.join('<br>');
                                }
                                Swal.fire({ html: message, icon: 'error', buttonsStyling: false, confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' } });
                            }
                        });
                    } else {
                        Swal.fire({ text: 'Please fill all required fields correctly.', icon: 'warning', buttonsStyling: false, confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' } });
                    }
                });
            }
        });
    };

    return {
        init: function () {
            element = document.getElementById('kt_modal_create_invoice');
            if (!element) return;
            form = element.querySelector('#kt_modal_add_invoice_form');
            modal = new bootstrap.Modal(element);
            studentSelect = element.querySelector('select[name="invoice_student"]');
            invoiceTypeSelect = element.querySelector('select[name="invoice_type"]');
            monthYearSelect = element.querySelector('select[name="invoice_month_year"]');
            invoiceAmountInput = element.querySelector('input[name="invoice_amount"]');
            monthYearTypeRadios = element.querySelectorAll('input[name="month_year_type"]');
            monthYearTypeWrapper = document.getElementById('month_year_type_id');
            monthYearWrapper = document.getElementById('month_year_id');
            monthYearTypeRadios.forEach(function (radio) { radio.disabled = true; });
            handleStudentChange();
            handleMonthYearTypeChange();
            handleMonthYearChange();
            handleInvoiceTypeChange();
            handleModalClose();
            initValidation();
            handleFormSubmit();
        }
    };
}();

// Edit Invoice Modal
var KTEditInvoiceModal = function () {
    var element, form, modal, submitButton, validator, invoiceId = null;

    var formatMonthYear = function (monthYear) {
        if (!monthYear) return '-';
        var parts = monthYear.split('_');
        if (parts.length !== 2) return '-';
        var monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        var monthIndex = parseInt(parts[0]) - 1;
        if (monthIndex < 0 || monthIndex > 11) return '-';
        return monthNames[monthIndex] + ' ' + parts[1];
    };

    var handleEditClick = function () {
        $(document).on('click', "[data-bs-target='#kt_modal_edit_invoice']", function () {
            invoiceId = this.getAttribute('data-invoice-id');
            if (!invoiceId) return;
            if (form) form.reset();

            document.getElementById('edit_student_display').innerHTML = '<span class="text-muted">Loading...</span>';
            document.getElementById('edit_invoice_type_display').innerHTML = '<span class="text-muted">Loading...</span>';
            document.getElementById('edit_month_year_display').innerHTML = '<span class="text-muted">Loading...</span>';

            $.get('/invoices/' + invoiceId + '/view-ajax', function (data) {
                if (!data.success || !data.data) { toastr.error('Invalid response data'); modal.hide(); return; }

                var invoice = data.data;
                var titleEl = document.getElementById('kt_modal_edit_invoice_title');
                if (titleEl) titleEl.textContent = 'Update Invoice ' + invoice.invoice_number;

                var studentDisplay = document.getElementById('edit_student_display');
                if (studentDisplay) studentDisplay.innerHTML = '<span class="fw-semibold">' + (invoice.student_name || 'Unknown') + (invoice.student_unique_id ? ' (' + invoice.student_unique_id + ')' : '') + '</span>';

                var typeDisplay = document.getElementById('edit_invoice_type_display');
                if (typeDisplay) typeDisplay.innerHTML = '<span class="fw-semibold">' + (invoice.invoice_type_name || '-') + '</span>';

                var monthYearWrapperEdit = document.getElementById('month_year_id_edit');
                if (monthYearWrapperEdit) monthYearWrapperEdit.style.display = invoice.invoice_type_name === 'Tuition Fee' ? '' : 'none';

                var monthYearDisplay = document.getElementById('edit_month_year_display');
                if (monthYearDisplay) monthYearDisplay.innerHTML = '<span class="fw-semibold">' + formatMonthYear(invoice.month_year) + '</span>';

                var amountInput = element.querySelector("input[name='invoice_amount_edit']");
                if (amountInput) amountInput.value = invoice.total_amount;
            }).fail(function () { toastr.error('Failed to load invoice details'); modal.hide(); });
        });
    };

    var handleModalClose = function () {
        var cancelButton = element.querySelector('[data-kt-edit-invoice-modal-action="cancel"]');
        var closeButton = element.querySelector('[data-kt-edit-invoice-modal-action="close"]');
        if (cancelButton) cancelButton.addEventListener('click', function (e) { e.preventDefault(); if (form) form.reset(); if (validator) validator.resetForm(); modal.hide(); });
        if (closeButton) closeButton.addEventListener('click', function (e) { e.preventDefault(); if (form) form.reset(); if (validator) validator.resetForm(); modal.hide(); });
        element.addEventListener('hidden.bs.modal', function () { if (form) form.reset(); if (validator) validator.resetForm(); invoiceId = null; });
    };

    var initValidation = function () {
        if (!form) return;
        validator = FormValidation.formValidation(form, {
            fields: { 'invoice_amount_edit': { validators: { notEmpty: { message: 'Amount is required' }, greaterThan: { min: 50, message: 'Amount must be at least 50' } } } },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: '.fv-row', eleInvalidClass: '', eleValidClass: '' }) }
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

                        var formData = new FormData(form);
                        formData.append('_token', InvoiceUtils.getCsrfToken());
                        formData.append('_method', 'PUT');

                        $.ajax({
                            url: '/invoices/' + invoiceId,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (data) {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                if (data.success) {
                                    Swal.fire({ text: data.message || 'Invoice updated successfully!', icon: 'success', buttonsStyling: false, confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' } }).then(function (result) {
                                        if (result.isConfirmed) {
                                            modal.hide();
                                            // Reload tables via AJAX and update badge counts
                                            InvoiceManager.reloadDueTables();
                                            InvoiceManager.updateBranchDueCounts();
                                        }
                                    });
                                } else {
                                    Swal.fire({ html: data.message || 'Failed to update invoice', icon: 'error', buttonsStyling: false, confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' } });
                                }
                            },
                            error: function (xhr) {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                var message = 'Something went wrong. Please try again.';
                                if (xhr.responseJSON && xhr.responseJSON.message) message = xhr.responseJSON.message;
                                Swal.fire({ html: message, icon: 'error', buttonsStyling: false, confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' } });
                            }
                        });
                    } else {
                        Swal.fire({ text: 'Please fill all required fields correctly.', icon: 'warning', buttonsStyling: false, confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' } });
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

// Add Comment Modal
var KTAddCommentModal = function () {
    var element, form, modal, submitButton, validator, invoiceId = null, invoiceNumber = null;

    var escapeHtml = function (text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    var loadComments = function (invoiceId) {
        var commentsLoading = document.getElementById('comments_loading');
        var commentsList = document.getElementById('comments_list');
        var noComments = document.getElementById('no_comments');

        commentsLoading.classList.remove('d-none');
        commentsList.innerHTML = '';
        noComments.classList.add('d-none');

        var url = routeGetComments.replace(':id', invoiceId);

        $.get(url, function (data) {
            commentsLoading.classList.add('d-none');
            if (data.success && data.comments && data.comments.length > 0) {
                var html = '';
                data.comments.forEach(function (comment) {
                    html += '<div class="d-flex flex-column mb-4 pb-3 border-bottom border-gray-200">' +
                        '<div class="d-flex align-items-center mb-1">' +
                        '<span class="fw-bold text-gray-800 fs-6 me-2">' + escapeHtml(comment.commented_by) + '</span>' +
                        '<span class="text-muted fs-7">' + comment.created_at + '</span>' +
                        '</div>' +
                        '<span class="text-gray-600 fs-6">' + escapeHtml(comment.comment) + '</span>' +
                        '</div>';
                });
                commentsList.innerHTML = html;
            } else {
                noComments.classList.remove('d-none');
            }
        }).fail(function () {
            commentsLoading.classList.add('d-none');
            commentsList.innerHTML = '<div class="text-center text-danger py-3">Failed to load comments</div>';
        });
    };

    var handleAddCommentClick = function () {
        $(document).on('click', '.add-comment-btn', function () {
            invoiceId = this.getAttribute('data-invoice-id');
            invoiceNumber = this.getAttribute('data-invoice-number');
            if (!invoiceId) return;

            document.getElementById('comment_invoice_id').value = invoiceId;
            var titleEl = document.getElementById('kt_modal_add_comment_title');
            if (titleEl) titleEl.textContent = 'Comments - Invoice ' + (invoiceNumber || invoiceId);

            form.querySelector('textarea[name="comment"]').value = '';
            loadComments(invoiceId);
        });
    };

    var handleModalClose = function () {
        var cancelButton = element.querySelector('[data-kt-add-comment-modal-action="cancel"]');
        var closeButton = element.querySelector('[data-kt-add-comment-modal-action="close"]');
        if (cancelButton) cancelButton.addEventListener('click', function (e) { e.preventDefault(); resetForm(); modal.hide(); });
        if (closeButton) closeButton.addEventListener('click', function (e) { e.preventDefault(); resetForm(); modal.hide(); });
        element.addEventListener('hidden.bs.modal', function () { resetForm(); });
    };

    var resetForm = function () {
        if (form) form.reset();
        if (validator) validator.resetForm();
        invoiceId = null;
        invoiceNumber = null;
        document.getElementById('comment_invoice_id').value = '';
        document.getElementById('comments_list').innerHTML = '';
        document.getElementById('no_comments').classList.add('d-none');
        document.getElementById('comments_loading').classList.remove('d-none');
    };

    var initValidation = function () {
        if (!form) return;
        validator = FormValidation.formValidation(form, {
            fields: { 'comment': { validators: { notEmpty: { message: 'Comment is required' }, stringLength: { min: 3, max: 1000, message: 'Comment must be between 3 and 1000 characters' } } } },
            plugins: { trigger: new FormValidation.plugins.Trigger(), bootstrap: new FormValidation.plugins.Bootstrap5({ rowSelector: '.fv-row', eleInvalidClass: '', eleValidClass: '' }) }
        });
    };

    var handleFormSubmit = function () {
        submitButton = element.querySelector('[data-kt-add-comment-modal-action="submit"]');
        if (!submitButton) return;

        submitButton.addEventListener('click', function (e) {
            e.preventDefault();
            if (validator) {
                validator.validate().then(function (status) {
                    if (status === 'Valid') {
                        submitButton.setAttribute('data-kt-indicator', 'on');
                        submitButton.disabled = true;

                        var formData = new FormData(form);
                        formData.append('_token', InvoiceUtils.getCsrfToken());

                        $.ajax({
                            url: routeStoreComment,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (data) {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                if (data.success) {
                                    form.querySelector('textarea[name="comment"]').value = '';
                                    validator.resetForm();
                                    toastr.success(data.message || 'Comment added successfully!');
                                    loadComments(invoiceId);
                                    // Reload tables via AJAX to update comment count
                                    InvoiceManager.reloadDueTables();
                                    InvoiceManager.reloadPaidTables();
                                } else {
                                    Swal.fire({ html: data.message || 'Failed to add comment', icon: 'error', buttonsStyling: false, confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' } });
                                }
                            },
                            error: function (xhr) {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;
                                var message = 'Something went wrong. Please try again.';
                                if (xhr.responseJSON && xhr.responseJSON.message) message = xhr.responseJSON.message;
                                Swal.fire({ html: message, icon: 'error', buttonsStyling: false, confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' } });
                            }
                        });
                    } else {
                        Swal.fire({ text: 'Please enter a valid comment (3-1000 characters).', icon: 'warning', buttonsStyling: false, confirmButtonText: 'Ok, got it!', customClass: { confirmButton: 'btn btn-primary' } });
                    }
                });
            }
        });
    };

    return {
        init: function () {
            element = document.getElementById('kt_modal_add_comment');
            if (!element) return;
            form = element.querySelector('#kt_modal_add_comment_form');
            modal = bootstrap.Modal.getOrCreateInstance(element);
            handleAddCommentClick();
            handleModalClose();
            initValidation();
            handleFormSubmit();
        }
    };
}();

// Initialize on document ready
KTUtil.onDOMContentLoaded(function () {
    KTDueInvoicesList.init();
    KTPaidInvoicesList.init();
    KTExportManager.init();
    KTCreateInvoiceModal.init();
    KTEditInvoiceModal.init();
    KTAddCommentModal.init();
});