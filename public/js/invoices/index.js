"use strict";

/**
 * Column Configuration
 * IMPORTANT: The order here must match EXACTLY with:
 * 1. The <th> elements in the blade templates
 * 2. The DataTable columns array
 */
var ColumnConfig = {
    // Due invoices columns - matching exact order in due_invoice_table.blade.php
    due: [
        { key: 'sl', label: 'SL', visible: true, required: true },
        { key: 'invoice_number', label: 'Invoice No.', visible: true, required: true },
        { key: 'student_name', label: 'Student', visible: true, required: false },
        { key: 'mobile', label: 'Mobile', visible: true, required: false },
        { key: 'class_name', label: 'Class', visible: false, required: false },
        { key: 'institution', label: 'Institution', visible: false, required: false },
        { key: 'tuition_fee', label: 'Tuition Fee', visible: false, required: false },
        { key: 'activation_status', label: 'Activation', visible: false, required: false },
        { key: 'invoice_type', label: 'Invoice Type', visible: true, required: false },
        { key: 'billing_month', label: 'Billing Month', visible: true, required: false },
        { key: 'total_amount', label: 'Total (Tk)', visible: true, required: false },
        { key: 'amount_due', label: 'Remaining (Tk)', visible: true, required: false },
        { key: 'due_date', label: 'Due Date', visible: true, required: false },
        { key: 'status', label: 'Status', visible: true, required: false },
        { key: 'last_comment', label: 'Last Comment', visible: true, required: false },
        { key: 'created_at', label: 'Created At', visible: true, required: false },
        { key: 'actions', label: 'Actions', visible: true, required: true }
    ],
    // Paid invoices columns - matching exact order in paid_invoice_table.blade.php
    paid: [
        { key: 'sl', label: 'SL', visible: true, required: true },
        { key: 'invoice_number', label: 'Invoice No.', visible: true, required: true },
        { key: 'student_name', label: 'Student', visible: true, required: false },
        { key: 'mobile', label: 'Mobile', visible: true, required: false },
        { key: 'class_name', label: 'Class', visible: false, required: false },
        { key: 'institution', label: 'Institution', visible: false, required: false },
        { key: 'tuition_fee', label: 'Tuition Fee', visible: false, required: false },
        { key: 'activation_status', label: 'Activation', visible: false, required: false },
        { key: 'invoice_type', label: 'Invoice Type', visible: true, required: false },
        { key: 'total_amount', label: 'Amount (Tk)', visible: true, required: false },
        { key: 'billing_month', label: 'Billing Month', visible: true, required: false },
        { key: 'due_date', label: 'Due Date', visible: true, required: false },
        { key: 'status', label: 'Status', visible: true, required: false },
        { key: 'last_comment', label: 'Last Comment', visible: true, required: false },
        { key: 'paid_at', label: 'Paid At', visible: true, required: false }
    ]
};

// Store DataTables instances and filters
var InvoiceManager = {
    tables: { due: {}, paid: {} },
    filters: { due: {}, paid: {} },
    initialized: { due: {}, paid: {} },
    columnVisibility: { due: {}, paid: {} },

    // Get column visibility for a table
    getColumnVisibility: function (tableId, type) {
        var storageKey = 'invoice_columns_' + tableId;
        var stored = localStorage.getItem(storageKey);

        if (stored) {
            try {
                return JSON.parse(stored);
            } catch (e) {
                console.error('Error parsing stored column visibility:', e);
            }
        }

        // Return default visibility from config
        var config = ColumnConfig[type] || ColumnConfig.due;
        var visibility = {};
        config.forEach(function (col, index) {
            visibility[index] = col.visible;
        });
        return visibility;
    },

    // Save column visibility for a table
    saveColumnVisibility: function (tableId, visibility) {
        var storageKey = 'invoice_columns_' + tableId;
        localStorage.setItem(storageKey, JSON.stringify(visibility));
    },

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
                        var badgeColor = tabLink.getAttribute('data-badge-color') || 'badge-light-primary';
                        var existingBadge = tabLink.querySelector('.badge');
                        if (existingBadge) {
                            existingBadge.remove();
                        }
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
        return now.toLocaleString('en-GB', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    },

    formatDateTimeForFile: function () {
        const now = new Date();

        const date = now.toLocaleDateString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }).replace(/\s/g, '-');

        const time = now.toLocaleTimeString('en-GB', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        }).replace(/:/g, '-');

        return date + '_' + time;
    }

};

// Column Selector Manager
var KTColumnSelector = function () {

    // Initialize column selector for a table
    var initColumnSelector = function (tableId, type) {
        var container = document.querySelector('.column-checkbox-list[data-table-id="' + tableId + '"][data-type="' + type + '"]');
        if (!container) return;

        var config = ColumnConfig[type] || ColumnConfig.due;
        var visibility = InvoiceManager.getColumnVisibility(tableId, type);

        // Build checkboxes HTML
        var html = '';
        config.forEach(function (col, index) {
            var isVisible = visibility[index] !== undefined ? visibility[index] : col.visible;
            var isDisabled = col.required ? 'disabled' : '';
            var checkedAttr = isVisible ? 'checked' : '';

            html += '<div class="form-check form-check-custom form-check-solid mb-3">' +
                '<input class="form-check-input column-visibility-checkbox" type="checkbox" ' +
                'id="col_' + tableId + '_' + index + '" ' +
                'data-column-index="' + index + '" ' +
                'data-column-key="' + col.key + '" ' +
                checkedAttr + ' ' + isDisabled + '>' +
                '<label class="form-check-label fw-semibold text-gray-700" for="col_' + tableId + '_' + index + '">' +
                col.label +
                (col.required ? ' <span class="badge badge-sm badge-light-primary ms-1">Required</span>' : '') +
                '</label>' +
                '</div>';
        });

        container.innerHTML = html;
    };

    // Apply column visibility to DataTable
    var applyColumnVisibility = function (tableId, type) {
        var container = document.querySelector('.column-checkbox-list[data-table-id="' + tableId + '"][data-type="' + type + '"]');
        if (!container) return;

        var dt = type === 'due' ? InvoiceManager.tables.due[tableId] : InvoiceManager.tables.paid[tableId];
        if (!dt) return;

        var visibility = {};
        var checkboxes = container.querySelectorAll('.column-visibility-checkbox');

        checkboxes.forEach(function (checkbox) {
            var colIndex = parseInt(checkbox.getAttribute('data-column-index'));
            var isVisible = checkbox.checked;
            visibility[colIndex] = isVisible;

            // Apply to DataTable column
            var column = dt.column(colIndex);
            if (column) {
                column.visible(isVisible);
            }
        });

        // Save to localStorage
        InvoiceManager.saveColumnVisibility(tableId, visibility);

        // Adjust columns after visibility change
        dt.columns.adjust().draw(false);
    };

    // Reset to default visibility
    var resetToDefaults = function (tableId, type) {
        var container = document.querySelector('.column-checkbox-list[data-table-id="' + tableId + '"][data-type="' + type + '"]');
        if (!container) return;

        var config = ColumnConfig[type] || ColumnConfig.due;
        var checkboxes = container.querySelectorAll('.column-visibility-checkbox');

        checkboxes.forEach(function (checkbox) {
            var colIndex = parseInt(checkbox.getAttribute('data-column-index'));
            var colConfig = config[colIndex];
            if (colConfig && !colConfig.required) {
                checkbox.checked = colConfig.visible;
            }
        });
    };

    // Apply initial column visibility when table is initialized
    var applyInitialVisibility = function (tableId, type, dt) {
        var visibility = InvoiceManager.getColumnVisibility(tableId, type);

        Object.keys(visibility).forEach(function (index) {
            var colIndex = parseInt(index);
            var isVisible = visibility[colIndex];
            var column = dt.column(colIndex);
            if (column) {
                column.visible(isVisible);
            }
        });
    };

    // Handle events
    var handleEvents = function () {
        // Apply button click
        $(document).on('click', '.column-apply-btn', function () {
            var tableId = this.getAttribute('data-table-id');
            var type = this.getAttribute('data-type');
            applyColumnVisibility(tableId, type);
            toastr.success('Column visibility updated');
        });

        // Reset button click
        $(document).on('click', '.column-reset-btn', function () {
            var tableId = this.getAttribute('data-table-id');
            var type = this.getAttribute('data-type');
            resetToDefaults(tableId, type);
            toastr.info('Column selection reset to defaults. Click Apply to save.');
        });
    };

    return {
        init: function () {
            handleEvents();
        },
        initForTable: function (tableId, type) {
            initColumnSelector(tableId, type);
        },
        applyInitialVisibility: applyInitialVisibility,
        getVisibleColumns: function (tableId, type) {
            var visibility = InvoiceManager.getColumnVisibility(tableId, type);
            var config = ColumnConfig[type] || ColumnConfig.due;
            var visibleCols = [];

            config.forEach(function (col, index) {
                var isVisible = visibility[index] !== undefined ? visibility[index] : col.visible;
                if (isVisible && col.key !== 'actions') {
                    visibleCols.push({
                        key: col.key,
                        label: col.label,
                        index: index
                    });
                }
            });

            return visibleCols;
        }
    };
}();

// Due Invoices DataTable Manager
var KTDueInvoicesList = function () {

    var initTable = function (tableId, branchId) {
        var table = document.getElementById(tableId);
        if (!table || InvoiceManager.initialized.due[tableId]) return;

        InvoiceManager.initialized.due[tableId] = true;
        InvoiceManager.filters.due[tableId] = {};

        // Load filter options
        loadFilterOptions(branchId, tableId);

        // Initialize column selector
        KTColumnSelector.initForTable(tableId, 'due');

        // Column definitions - MUST match exact order of <th> elements in blade template
        var columns = [
            // 0: SL
            { data: 'sl', orderable: false },
            // 1: Invoice No.
            {
                data: null,
                render: function (data) {
                    var url = routeInvoiceShow.replace(':id', data.id);
                    var badge = data.comments_count > 0
                        ? '<span class="badge badge-circle badge-sm badge-primary ms-1">' + data.comments_count + '</span>'
                        : '';
                    return '<a href="' + url + '" target="_blank" class="text-gray-800 text-hover-primary">' +
                        InvoiceUtils.escapeHtml(data.invoice_number) + '</a>' + badge;
                }
            },
            // 2: Student
            {
                data: null,
                render: function (data) {
                    var url = routeStudentShow.replace(':id', data.student_id);
                    return '<a href="' + url + '" target="_blank" class="text-gray-800 text-hover-primary">' +
                        InvoiceUtils.escapeHtml(data.student_name) +
                        '<br><small class="text-muted">' + InvoiceUtils.escapeHtml(data.student_unique_id) + '</small></a>';
                }
            },
            // 3: Mobile
            { data: 'mobile', orderable: false, defaultContent: '-' },
            // 4: Class
            { data: 'class_name', defaultContent: '-' },
            // 5: Institution
            { data: 'institution', defaultContent: '-' },
            // 6: Tuition Fee
            {
                data: 'tuition_fee',
                defaultContent: '-',
                render: function (data) {
                    return data ? '৳' + data : '-';
                }
            },
            // 7: Activation Status
            {
                data: null,
                orderable: false,
                render: function (data) {
                    return data.activation_status_html || '-';
                }
            },
            // 8: Invoice Type
            { data: 'invoice_type', defaultContent: '-' },
            // 9: Billing Month
            {
                data: null,
                render: function (data) {
                    return data.billing_month === 'One Time'
                        ? '<span class="badge badge-primary rounded-pill">One Time</span>'
                        : InvoiceUtils.escapeHtml(data.billing_month);
                }
            },
            // 10: Total Amount
            { data: 'total_amount', defaultContent: '0' },
            // 11: Amount Due (Remaining)
            { data: 'amount_due', defaultContent: '0' },
            // 12: Due Date
            { data: 'due_date', defaultContent: '-' },
            // 13: Status
            {
                data: 'status_html',
                orderable: false,
                defaultContent: '-'
            },
            // 14: Last Comment
            {
                data: null,
                orderable: false,
                render: function (data) {
                    if (!data.last_comment) return '-';
                    var truncated = data.last_comment.length > 30
                        ? data.last_comment.substring(0, 30) + '...'
                        : data.last_comment;
                    return '<div class="text-gray-700 fs-7" title="' + InvoiceUtils.escapeHtml(data.last_comment) + '">' +
                        InvoiceUtils.escapeHtml(truncated) + '</div>';
                }
            },
            // 15: Created At
            {
                data: null,
                render: function (data) {
                    return data.created_at + '<br><small class="text-muted">' + data.created_at_time + '</small>';
                }
            },
            // 16: Actions
            {
                data: null,
                orderable: false,
                className: 'text-end',
                render: function (data) {
                    var actions = '';

                    if (data.status === 'due') {
                        if (canEditInvoice) {
                            actions += '<div class="menu-item px-3"><a href="#" data-invoice-id="' + data.id +
                                '" data-bs-toggle="modal" data-bs-target="#kt_modal_edit_invoice" ' +
                                'class="menu-link text-hover-primary px-3"><i class="ki-outline ki-pencil fs-3 me-2"></i> Edit</a></div>';
                        }
                        if (canViewInvoice) {
                            actions += '<div class="menu-item px-3"><a href="#" data-invoice-id="' + data.id +
                                '" data-invoice-number="' + data.invoice_number +
                                '" data-bs-toggle="modal" data-bs-target="#kt_modal_add_comment" ' +
                                'class="menu-link text-hover-primary px-3 add-comment-btn"><i class="ki-outline ki-messages fs-3 me-2"></i> Comment</a></div>';
                        }
                        if (canDeleteInvoice) {
                            actions += '<div class="menu-item px-3"><a href="#" data-invoice-id="' + data.id +
                                '" class="menu-link text-hover-danger px-3 delete-invoice">' +
                                '<i class="ki-outline ki-trash fs-3 me-2"></i> Delete</a></div>';
                        }
                    } else if (data.status === 'partially_paid') {
                        if (canViewInvoice) {
                            actions += '<div class="menu-item px-3"><a href="#" data-invoice-id="' + data.id +
                                '" data-invoice-number="' + data.invoice_number +
                                '" data-bs-toggle="modal" data-bs-target="#kt_modal_add_comment" ' +
                                'class="menu-link text-hover-primary px-3 add-comment-btn"><i class="ki-outline ki-messages fs-3 me-2"></i> Comment</a></div>';
                        }
                    }

                    if (!actions) {
                        return '<span class="text-muted">-</span>';
                    }

                    return '<a href="#" class="btn btn-light btn-active-light-primary btn-sm" ' +
                        'data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions ' +
                        '<i class="ki-outline ki-down fs-5 m-0"></i></a>' +
                        '<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 ' +
                        'menu-state-bg-light-primary fw-semibold fs-7 w-150px py-4" data-kt-menu="true">' +
                        actions + '</div>';
                }
            }
        ];

        // Initialize DataTable
        var dt = $(table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: routeUnpaidAjax,
                type: 'GET',
                data: function (d) {
                    d.branch_id = branchId;
                    var filters = InvoiceManager.filters.due[tableId] || {};
                    if (filters.class_id) d.class_id = filters.class_id;
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
            columns: columns,
            order: [[15, 'desc']], // Sort by created_at descending
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            scrollX: true,
            drawCallback: function () {
                KTMenu.createInstances();
            },
            initComplete: function () {
                // Apply saved column visibility
                KTColumnSelector.applyInitialVisibility(tableId, 'due', this.api());
            }
        });

        InvoiceManager.tables.due[tableId] = dt;

        // Search handler with debounce
        var searchInput = document.querySelector('.due-invoice-search[data-table-id="' + tableId + '"]');
        if (searchInput) {
            var searchTimeout;
            searchInput.addEventListener('keyup', function () {
                var value = this.value;
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    dt.search(value).draw();
                }, 500);
            });
        }
    };

    var loadFilterOptions = function (branchId, tableId) {
        $.get(routeFilterOptions, { branch_id: branchId }, function (data) {
            // Populate class filter
            // var classSelect = document.querySelector('.filter-class-name[data-table-id="' + tableId + '"]');
            // if (classSelect) {
            //     classSelect.innerHTML = '<option></option>';
            //     classNames.forEach(function (cls) {
            //         classSelect.innerHTML += `<option value="${cls.id}">${cls.name}</option>`;
            //     });
            //     $(classSelect).select2({ placeholder: 'Select class', allowClear: true });
            // }


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

            // var classSelect = document.querySelector('.filter-class-name[data-table-id="' + tableId + '"]');
            var typeSelect = document.querySelector('.filter-invoice-type[data-table-id="' + tableId + '"]');
            var dueDateSelect = document.querySelector('.filter-due-date[data-table-id="' + tableId + '"]');
            var statusSelect = document.querySelector('.filter-status[data-table-id="' + tableId + '"]');
            var monthSelect = document.querySelector('.filter-billing-month[data-table-id="' + tableId + '"]');

            // if (classSelect && classSelect.value) { filters.class_id = classSelect.value; }
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

            // if (classSelect) $(classSelect).val(null).trigger('change');
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

        // Initialize column selector
        KTColumnSelector.initForTable(tableId, 'paid');

        // Column definitions - MUST match exact order of <th> elements in blade template
        var columns = [
            // 0: SL
            { data: 'sl', orderable: false },
            // 1: Invoice No.
            {
                data: null,
                render: function (data) {
                    var url = routeInvoiceShow.replace(':id', data.id);
                    var badge = data.comments_count > 0
                        ? '<span class="badge badge-circle badge-sm badge-primary ms-1">' + data.comments_count + '</span>'
                        : '';
                    return '<a href="' + url + '" target="_blank" class="text-gray-600 text-hover-primary">' +
                        InvoiceUtils.escapeHtml(data.invoice_number) + '</a>' + badge;
                }
            },
            // 2: Student
            {
                data: null,
                render: function (data) {
                    var url = routeStudentShow.replace(':id', data.student_id);
                    return '<a href="' + url + '" target="_blank" class="text-gray-600 text-hover-primary">' +
                        InvoiceUtils.escapeHtml(data.student_name) +
                        '<br><small class="text-muted">' + InvoiceUtils.escapeHtml(data.student_unique_id) + '</small></a>';
                }
            },
            // 3: Mobile
            { data: 'mobile', orderable: false, defaultContent: '-' },
            // 4: Class
            { data: 'class_name', defaultContent: '-' },
            // 5: Institution
            { data: 'institution', defaultContent: '-' },
            // 6: Tuition Fee
            {
                data: 'tuition_fee',
                defaultContent: '-',
                render: function (data) {
                    return data ? '৳' + data : '-';
                }
            },
            // 7: Activation Status
            {
                data: null,
                orderable: false,
                render: function (data) {
                    return data.activation_status_html || '-';
                }
            },
            // 8: Invoice Type
            { data: 'invoice_type', defaultContent: '-' },
            // 9: Amount (Total)
            { data: 'total_amount', defaultContent: '0' },
            // 10: Billing Month
            { data: 'billing_month', defaultContent: '-' },
            // 11: Due Date
            { data: 'due_date', defaultContent: '-' },
            // 12: Status
            {
                data: null,
                orderable: false,
                render: function () {
                    return '<span class="badge badge-success rounded-pill">Paid</span>';
                }
            },
            // 13: Last Comment
            {
                data: null,
                orderable: false,
                render: function (data) {
                    if (!data.last_comment) return '-';
                    var truncated = data.last_comment.length > 30
                        ? data.last_comment.substring(0, 30) + '...'
                        : data.last_comment;
                    return '<div class="text-gray-700 fs-7" title="' + InvoiceUtils.escapeHtml(data.last_comment) + '">' +
                        InvoiceUtils.escapeHtml(truncated) + '</div>';
                }
            },
            // 14: Paid At
            {
                data: null,
                render: function (data) {
                    return data.updated_at + '<br><small class="text-muted">' + data.updated_at_time + '</small>';
                }
            }
        ];

        // Initialize DataTable
        var dt = $(table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: routePaidAjax,
                type: 'GET',
                data: function (d) {
                    d.branch_id = branchId;
                    var filters = InvoiceManager.filters.paid[tableId] || {};
                    if (filters.class_id) d.class_id = filters.class_id;
                    if (filters.invoice_type) d.invoice_type = filters.invoice_type;
                    if (filters.due_date) d.due_date = filters.due_date;
                    if (filters.billing_month) d.billing_month = filters.billing_month;
                    return d;
                },
                dataSrc: function (json) {
                    return json.data || [];
                }
            },
            columns: columns,
            order: [[14, 'desc']], // Sort by paid_at descending
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            scrollX: true,
            drawCallback: function () {
                KTMenu.createInstances();
            },
            initComplete: function () {
                // Apply saved column visibility
                KTColumnSelector.applyInitialVisibility(tableId, 'paid', this.api());
            }
        });

        InvoiceManager.tables.paid[tableId] = dt;

        // Search handler with debounce
        var searchInput = document.querySelector('.paid-invoice-search[data-table-id="' + tableId + '"]');
        if (searchInput) {
            var searchTimeout;
            searchInput.addEventListener('keyup', function () {
                var value = this.value;
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    dt.search(value).draw();
                }, 500);
            });
        }
    };

    var loadFilterOptions = function (branchId, tableId) {
        $.get(routeFilterOptions, { branch_id: branchId }, function (data) {
            // Populate class filter
            // var classSelect = document.querySelector('.filter-class-name-paid[data-table-id="' + tableId + '"]');
            // if (classSelect) {
            //     classSelect.innerHTML = '<option></option>';
            //     classNames.forEach(function (cls) {
            //         classSelect.innerHTML += `<option value="${cls.id}">${cls.name}</option>`;
            //     });
            //     $(classSelect).select2({ placeholder: 'Select class', allowClear: true });
            // }


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

            // var classSelect = document.querySelector('.filter-class-name-paid[data-table-id="' + tableId + '"]');
            var typeSelect = document.querySelector('.filter-invoice-type-paid[data-table-id="' + tableId + '"]');
            var dueDateSelect = document.querySelector('.filter-due-date-paid[data-table-id="' + tableId + '"]');
            var monthSelect = document.querySelector('.filter-billing-month-paid[data-table-id="' + tableId + '"]');

            // if (classSelect && classSelect.value) { filters.class_id = classSelect.value; }
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

            // if (classSelect) $(classSelect).val(null).trigger('change');
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

        if (filters.class_id) params.class_id = filters.class_id;
        if (filters.invoice_type) params.invoice_type = filters.invoice_type;
        if (filters.due_date) params.due_date = filters.due_date;
        if (filters.status) params.status = filters.status;
        if (filters.billing_month) params.billing_month = filters.billing_month;

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

    var formatExportData = function (data, type, tableId) {
        // Get visible columns
        var visibleColumns = KTColumnSelector.getVisibleColumns(tableId, type);

        // Create column key to label mapping
        var columnMap = {};
        visibleColumns.forEach(function (col) {
            columnMap[col.key] = col.label;
        });

        return data.map(function (row, index) {
            var exportRow = {};

            // Always include SL
            exportRow['SL'] = index + 1;

            // Map data based on visible columns
            visibleColumns.forEach(function (col) {
                if (col.key === 'sl') return; // Already added

                var label = col.label;
                var value = '';

                switch (col.key) {
                    case 'invoice_number':
                        value = row.invoice_number || '';
                        break;
                    case 'student_name':
                        value = row.student_name || '';
                        break;
                    case 'mobile':
                        value = row.mobile || '';
                        break;
                    case 'class_name':
                        value = row.class_name || '';
                        break;
                    case 'institution':
                        value = row.institution || '';
                        break;
                    case 'tuition_fee':
                        value = row.tuition_fee || '';
                        break;
                    case 'activation_status':
                        value = row.activation_status || '';
                        break;
                    case 'invoice_type':
                        value = row.invoice_type || '';
                        break;
                    case 'billing_month':
                        value = row.billing_month || '';
                        break;
                    case 'total_amount':
                        value = row.total_amount || '0';
                        break;
                    case 'amount_due':
                        value = row.amount_due || '0';
                        break;
                    case 'due_date':
                        value = row.due_date || '';
                        break;
                    case 'status':
                        value = row.status_text || '';
                        break;
                    case 'last_comment':
                        value = row.last_comment || '';
                        if (row.last_comment_by) value += ' [By: ' + row.last_comment_by + ']';
                        if (row.last_comment_at) value += ' [At: ' + row.last_comment_at + ']';
                        break;
                    case 'created_at':
                        value = (row.created_at || '') + ' ' + (row.created_at_time || '');
                        break;
                    case 'paid_at':
                        value = (row.updated_at || '') + ' ' + (row.updated_at_time || '');
                        break;
                    default:
                        value = row[col.key] || '';
                }

                exportRow[label] = value;
            });

            return exportRow;
        });
    };

    var copyToClipboard = function (data) {
        if (data.length === 0) {
            toastr.warning('No data to export');
            return;
        }

        var headers = Object.keys(data[0]);
        var text = headers.join('\t') + '\n';
        data.forEach(function (row) {
            text += headers.map(function (h) { return row[h] || ''; }).join('\t') + '\n';
        });

        navigator.clipboard.writeText(text).then(function () {
            toastr.success('Data copied to clipboard');
        }).catch(function () {
            toastr.error('Failed to copy to clipboard');
        });
    };

    var exportExcel = function (data, type) {
        if (data.length === 0) {
            toastr.warning('No data to export');
            return;
        }

        var title = type === 'due' ? 'Due Invoices Report' : 'Paid Invoices Report';
        var fileName = title + '_' + InvoiceUtils.formatDateTimeForFile();

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
        if (data.length === 0) {
            toastr.warning('No data to export');
            return;
        }

        var title = type === 'due' ? 'Due Invoices Report' : 'Paid Invoices Report';
        var fileName = title + '_' + InvoiceUtils.formatDateTimeForFile();

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
        if (data.length === 0) {
            toastr.warning('No data to export');
            return;
        }

        var jsPDF = window.jspdf.jsPDF;
        var doc = new jsPDF('l', 'mm', 'a4');

        var title = type === 'due' ? 'Due Invoices Report' : 'Paid Invoices Report';
        var fileName = title + '_' + InvoiceUtils.formatDateTimeForFile();

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
                doc.text('Page ' + doc.internal.getNumberOfPages(),
                    doc.internal.pageSize.width / 2,
                    doc.internal.pageSize.height - 10,
                    { align: 'center' });
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
            var originalText = btn.textContent;

            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Exporting...';

            fetchExportData(tableId, type, function (rawData) {
                var data = formatExportData(rawData, type, tableId);

                switch (exportType) {
                    case 'copy':
                        copyToClipboard(data);
                        break;
                    case 'excel':
                        exportExcel(data, type);
                        break;
                    case 'csv':
                        exportCSV(data, type);
                        break;
                    case 'pdf':
                        exportPDF(data, type);
                        break;
                }

                btn.textContent = originalText;
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
            month = parts[0] + 1;
            year = parts[1];
            if (month > 12) { month = 1; year++; }
        } else {
            var currentDate = new Date();
            if (invoiceData.paymentStyle === 'due') {
                month = currentDate.getMonth();
                year = currentDate.getFullYear();
                if (month === 0) { month = 12; year--; }
            } else {
                month = currentDate.getMonth() + 1;
                year = currentDate.getFullYear();
            }
        }
        var monthStr = String(month).padStart(2, '0');
        return { value: monthStr + '_' + year, text: formatMonthYear(month, year) };
    };

    var calculateOldInvoiceMonth = function () {
        var month, year;
        if (invoiceData.oldestInvoiceMonth) {
            var parts = invoiceData.oldestInvoiceMonth.split('_').map(Number);
            month = parts[0] - 1;
            year = parts[1];
            if (month < 1) { month = 12; year--; }
        } else {
            var currentDate = new Date();
            if (invoiceData.paymentStyle === 'due') {
                month = currentDate.getMonth() - 1;
                year = currentDate.getFullYear();
                if (month < 0) { month = 11; year--; }
            } else {
                month = currentDate.getMonth();
                year = currentDate.getFullYear();
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

                var monthData = this.value === 'new_invoice'
                    ? calculateNewInvoiceMonth()
                    : calculateOldInvoiceMonth();
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
                if (selectedTypeName === 'Tuition Fee') {
                    invoiceAmountInput.value = invoiceData.tuitionFee || '';
                }
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
                if (monthYearSelect.value) {
                    invoiceAmountInput.value = invoiceData.tuitionFee || '';
                }
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

        cancelButton.addEventListener('click', function (e) {
            e.preventDefault();
            resetForm();
            modal.hide();
        });

        closeButton.addEventListener('click', function (e) {
            e.preventDefault();
            resetForm();
            modal.hide();
        });

        element.addEventListener('hidden.bs.modal', function () {
            resetForm();
        });
    };

    var initValidation = function () {
        validator = FormValidation.formValidation(form, {
            fields: {
                'invoice_student': { validators: { notEmpty: { message: 'Student is required' } } },
                'invoice_type': { validators: { notEmpty: { message: 'Invoice type is required' } } },
                'invoice_amount': {
                    validators: {
                        notEmpty: { message: 'Amount is required' },
                        greaterThan: { min: 50, message: 'Amount must be at least 50' }
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
                                    Swal.fire({
                                        text: data.message || 'Invoice created successfully!',
                                        icon: 'success',
                                        buttonsStyling: false,
                                        confirmButtonText: 'Ok, got it!',
                                        customClass: { confirmButton: 'btn btn-primary' }
                                    }).then(function (result) {
                                        if (result.isConfirmed) {
                                            modal.hide();
                                            resetForm();
                                            InvoiceManager.reloadDueTables();
                                            InvoiceManager.updateBranchDueCounts();
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        html: data.message || 'Failed to create invoice',
                                        icon: 'error',
                                        buttonsStyling: false,
                                        confirmButtonText: 'Ok, got it!',
                                        customClass: { confirmButton: 'btn btn-primary' }
                                    });
                                }
                            },
                            error: function (xhr) {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;

                                var message = 'Something went wrong. Please try again.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                if (xhr.responseJSON && xhr.responseJSON.errors) {
                                    var errors = [];
                                    $.each(xhr.responseJSON.errors, function (key, val) {
                                        errors.push(val[0]);
                                    });
                                    message = errors.join('<br>');
                                }

                                Swal.fire({
                                    html: message,
                                    icon: 'error',
                                    buttonsStyling: false,
                                    confirmButtonText: 'Ok, got it!',
                                    customClass: { confirmButton: 'btn btn-primary' }
                                });
                            }
                        });
                    } else {
                        Swal.fire({
                            text: 'Please fill all required fields correctly.',
                            icon: 'warning',
                            buttonsStyling: false,
                            confirmButtonText: 'Ok, got it!',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
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

        var monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
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
                if (!data.success || !data.data) {
                    toastr.error('Invalid response data');
                    modal.hide();
                    return;
                }

                var invoice = data.data;
                var titleEl = document.getElementById('kt_modal_edit_invoice_title');
                if (titleEl) titleEl.textContent = 'Update Invoice ' + invoice.invoice_number;

                var studentDisplay = document.getElementById('edit_student_display');
                if (studentDisplay) {
                    studentDisplay.innerHTML = '<span class="fw-semibold">' +
                        (invoice.student_name || 'Unknown') +
                        (invoice.student_unique_id ? ' (' + invoice.student_unique_id + ')' : '') + '</span>';
                }

                var typeDisplay = document.getElementById('edit_invoice_type_display');
                if (typeDisplay) {
                    typeDisplay.innerHTML = '<span class="fw-semibold">' + (invoice.invoice_type_name || '-') + '</span>';
                }

                var monthYearWrapperEdit = document.getElementById('month_year_id_edit');
                if (monthYearWrapperEdit) {
                    monthYearWrapperEdit.style.display = invoice.invoice_type_name === 'Tuition Fee' ? '' : 'none';
                }

                var monthYearDisplay = document.getElementById('edit_month_year_display');
                if (monthYearDisplay) {
                    monthYearDisplay.innerHTML = '<span class="fw-semibold">' + formatMonthYear(invoice.month_year) + '</span>';
                }

                var amountInput = element.querySelector("input[name='invoice_amount_edit']");
                if (amountInput) amountInput.value = invoice.total_amount;
            }).fail(function () {
                toastr.error('Failed to load invoice details');
                modal.hide();
            });
        });
    };

    var handleModalClose = function () {
        var cancelButton = element.querySelector('[data-kt-edit-invoice-modal-action="cancel"]');
        var closeButton = element.querySelector('[data-kt-edit-invoice-modal-action="close"]');

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
            invoiceId = null;
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
                    rowSelector: '.fv-row',
                    eleInvalidClass: '',
                    eleValidClass: ''
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
                                    Swal.fire({
                                        text: data.message || 'Invoice updated successfully!',
                                        icon: 'success',
                                        buttonsStyling: false,
                                        confirmButtonText: 'Ok, got it!',
                                        customClass: { confirmButton: 'btn btn-primary' }
                                    }).then(function (result) {
                                        if (result.isConfirmed) {
                                            modal.hide();
                                            InvoiceManager.reloadDueTables();
                                            InvoiceManager.updateBranchDueCounts();
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        html: data.message || 'Failed to update invoice',
                                        icon: 'error',
                                        buttonsStyling: false,
                                        confirmButtonText: 'Ok, got it!',
                                        customClass: { confirmButton: 'btn btn-primary' }
                                    });
                                }
                            },
                            error: function (xhr) {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;

                                var message = 'Something went wrong. Please try again.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }

                                Swal.fire({
                                    html: message,
                                    icon: 'error',
                                    buttonsStyling: false,
                                    confirmButtonText: 'Ok, got it!',
                                    customClass: { confirmButton: 'btn btn-primary' }
                                });
                            }
                        });
                    } else {
                        Swal.fire({
                            text: 'Please fill all required fields correctly.',
                            icon: 'warning',
                            buttonsStyling: false,
                            confirmButtonText: 'Ok, got it!',
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

        if (cancelButton) {
            cancelButton.addEventListener('click', function (e) {
                e.preventDefault();
                resetForm();
                modal.hide();
            });
        }

        if (closeButton) {
            closeButton.addEventListener('click', function (e) {
                e.preventDefault();
                resetForm();
                modal.hide();
            });
        }

        element.addEventListener('hidden.bs.modal', function () {
            resetForm();
        });
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
            fields: {
                'comment': {
                    validators: {
                        notEmpty: { message: 'Comment is required' },
                        stringLength: { min: 3, max: 1000, message: 'Comment must be between 3 and 1000 characters' }
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
                                    InvoiceManager.reloadDueTables();
                                    InvoiceManager.reloadPaidTables();
                                } else {
                                    Swal.fire({
                                        html: data.message || 'Failed to add comment',
                                        icon: 'error',
                                        buttonsStyling: false,
                                        confirmButtonText: 'Ok, got it!',
                                        customClass: { confirmButton: 'btn btn-primary' }
                                    });
                                }
                            },
                            error: function (xhr) {
                                submitButton.removeAttribute('data-kt-indicator');
                                submitButton.disabled = false;

                                var message = 'Something went wrong. Please try again.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }

                                Swal.fire({
                                    html: message,
                                    icon: 'error',
                                    buttonsStyling: false,
                                    confirmButtonText: 'Ok, got it!',
                                    customClass: { confirmButton: 'btn btn-primary' }
                                });
                            }
                        });
                    } else {
                        Swal.fire({
                            text: 'Please enter a valid comment (3-1000 characters).',
                            icon: 'warning',
                            buttonsStyling: false,
                            confirmButtonText: 'Ok, got it!',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
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
    KTColumnSelector.init();
    KTDueInvoicesList.init();
    KTPaidInvoicesList.init();
    KTExportManager.init();
    KTCreateInvoiceModal.init();
    KTEditInvoiceModal.init();
    KTAddCommentModal.init();
});