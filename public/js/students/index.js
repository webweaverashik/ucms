"use strict";

/* =============================================================================
   DEDUPLICATION GUARD
   Metronic's layout may render @yield('content') more than once.
   This removes any duplicate #branchTabs / #branchTabsContent nodes.
   ============================================================================= */
(function () {
    var tabLists = document.querySelectorAll('ul#branchTabs');
    for (var i = 1; i < tabLists.length; i++) {
        tabLists[i].parentNode.removeChild(tabLists[i]);
    }
    var tabContents = document.querySelectorAll('div#branchTabsContent');
    for (var j = 1; j < tabContents.length; j++) {
        tabContents[j].parentNode.removeChild(tabContents[j]);
    }
})();


/**
 * Column Configuration - 19 columns
 * Must match <th> order in students-table.blade.php exactly
 */
var StudentColumnConfig = [
    { key: 'counter',         label: '#',                visible: true,  required: true  },
    { key: 'student',         label: 'Student',          visible: true,  required: true  },
    { key: 'class',           label: 'Class',            visible: true,  required: false },
    { key: 'group',           label: 'Group',            visible: true,  required: false },
    { key: 'batch',           label: 'Batch',            visible: true,  required: false },
    { key: 'institution',     label: 'Institution',      visible: true,  required: false },
    { key: 'mobile_home',     label: 'Mobile (Home)',    visible: true,  required: false },
    { key: 'mobile_sms',      label: 'Mobile (SMS)',     visible: false, required: false },
    { key: 'mobile_whatsapp', label: 'Mobile (WhatsApp)',visible: false, required: false },
    { key: 'guardian_1',      label: 'Guardian 1',       visible: false, required: false },
    { key: 'guardian_2',      label: 'Guardian 2',       visible: false, required: false },
    { key: 'sibling_1',       label: 'Sibling 1',       visible: false, required: false },
    { key: 'sibling_2',       label: 'Sibling 2',       visible: false, required: false },
    { key: 'tuition_fee',     label: 'Tuition Fee',     visible: true,  required: false },
    { key: 'payment_type',    label: 'Payment Type',    visible: true,  required: false },
    { key: 'status',          label: 'Status',           visible: false, required: false },
    { key: 'admission_date',  label: 'Admission Date',  visible: false, required: false },
    { key: 'admitted_by',     label: 'Admitted By',      visible: false, required: false },
    { key: 'actions',         label: 'Actions',          visible: true,  required: true  }
];

var TOTAL_COLUMNS = StudentColumnConfig.length; // 19


/**
 * Helper: Capitalize first letter
 */
function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}


/**
 * Helper: Reveal table, hide skeleton
 */
function revealTable(tableId) {
    var skeleton = document.getElementById('skeleton_' + tableId);
    var wrapper = document.getElementById('wrapper_' + tableId);
    if (skeleton) {
        skeleton.style.display = 'none';
    }
    if (wrapper) {
        wrapper.classList.add('visible');
    }
}


/**
 * Column Selector Module
 */
var KTStudentColumnSelector = (function () {
    var currentVisibility = null;
    var loaded = false;

    var getDefaults = function () {
        var d = {};
        for (var i = 0; i < TOTAL_COLUMNS; i++) {
            d[i] = StudentColumnConfig[i].visible;
        }
        return d;
    };

    var buildCheckboxes = function () {
        var el = document.getElementById('column_checkbox_list');
        if (!el) return;
        var html = '';
        for (var i = 0; i < TOTAL_COLUMNS; i++) {
            var c = StudentColumnConfig[i];
            var on = currentVisibility ? currentVisibility[i] : c.visible;
            html += '<div class="form-check form-check-custom form-check-solid mb-3">'
                  + '<input class="form-check-input column-vis-cb" type="checkbox" '
                  + 'id="col_vis_' + i + '" data-col="' + i + '" '
                  + (on ? 'checked ' : '') + (c.required ? 'disabled ' : '') + '>'
                  + '<label class="form-check-label fw-semibold text-gray-700" for="col_vis_' + i + '">'
                  + c.label
                  + (c.required ? ' <span class="badge badge-sm badge-light-primary ms-1">Required</span>' : '')
                  + '</label></div>';
        }
        el.innerHTML = html;
    };

    var loadFromServer = function (callback) {
        if (typeof routeColumnSettingsGet === 'undefined') {
            currentVisibility = getDefaults();
            loaded = true;
            buildCheckboxes();
            if (callback) callback();
            return;
        }
        $.ajax({
            url: routeColumnSettingsGet,
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.success && res.settings && typeof res.settings === 'object') {
                    currentVisibility = {};
                    for (var k in res.settings) {
                        if (res.settings.hasOwnProperty(k)) {
                            var idx = parseInt(k, 10);
                            if (!isNaN(idx) && idx >= 0 && idx < TOTAL_COLUMNS) {
                                var v = res.settings[k];
                                currentVisibility[idx] = (v === true || v === 1 || v === '1' || v === 'true');
                            }
                        }
                    }
                    // Fill missing indices with defaults and enforce required
                    for (var i = 0; i < TOTAL_COLUMNS; i++) {
                        if (currentVisibility[i] === undefined) {
                            currentVisibility[i] = StudentColumnConfig[i].visible;
                        }
                        if (StudentColumnConfig[i].required) {
                            currentVisibility[i] = true;
                        }
                    }
                } else {
                    currentVisibility = getDefaults();
                }
                loaded = true;
                buildCheckboxes();
                if (callback) callback();
            },
            error: function () {
                currentVisibility = getDefaults();
                loaded = true;
                buildCheckboxes();
                if (callback) callback();
            }
        });
    };

    var saveToServer = function () {
        if (typeof routeColumnSettingsSave === 'undefined') {
            toastr.error('Save route not configured');
            return;
        }
        var data = {};
        var cbs = document.querySelectorAll('.column-vis-cb');
        cbs.forEach(function (cb) {
            var idx = parseInt(cb.getAttribute('data-col'), 10);
            data[idx] = cb.checked;
        });
        // Enforce required and fill missing
        for (var i = 0; i < TOTAL_COLUMNS; i++) {
            if (StudentColumnConfig[i].required) data[i] = true;
            if (data[i] === undefined) data[i] = StudentColumnConfig[i].visible;
        }

        $.ajax({
            url: routeColumnSettingsSave,
            type: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: JSON.stringify({ visibility: data }),
            success: function (res) {
                if (res.success) {
                    currentVisibility = data;
                    KTStudentsList.applyVisibilityAll();
                    toastr.success(res.message || 'Column settings saved for all users');
                } else {
                    toastr.error(res.message || 'Save failed');
                }
            },
            error: function (xhr) {
                console.error('Save column settings error:', xhr.responseText);
                toastr.error('Failed to save column settings');
            }
        });
    };

    var applyTo = function (dt) {
        if (!dt || !loaded || !currentVisibility) return;
        try {
            for (var i = 0; i < TOTAL_COLUMNS; i++) {
                var col = dt.column(i);
                if (col && col.nodes().length >= 0) {
                    var vis = currentVisibility[i] !== undefined ? currentVisibility[i] : StudentColumnConfig[i].visible;
                    col.visible(vis, false);
                }
            }
            dt.columns.adjust().draw(false);
        } catch (e) {
            console.warn('Column visibility error:', e.message);
        }
    };

    var bindEvents = function () {
        var applyBtn = document.getElementById('column_apply_btn');
        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                saveToServer();
            });
        }

        var resetBtn = document.getElementById('column_reset_btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                currentVisibility = getDefaults();
                buildCheckboxes();
                toastr.info('Reset to defaults. Click Apply to save.');
            });
        }
    };

    var visibleForExport = function () {
        var cols = [];
        for (var i = 0; i < TOTAL_COLUMNS; i++) {
            var c = StudentColumnConfig[i];
            var vis = currentVisibility ? currentVisibility[i] : c.visible;
            if (!vis || c.key === 'actions' || c.key === 'counter') continue;

            if (c.key === 'student') {
                cols.push({ key: 'student_name', label: 'Student Name' });
                cols.push({ key: 'student_unique_id', label: 'Student ID' });
            } else if (c.key === 'class') {
                cols.push({ key: 'class_name', label: c.label });
            } else if (c.key === 'group') {
                cols.push({ key: 'academic_group', label: c.label });
            } else if (c.key === 'batch') {
                cols.push({ key: 'batch_name', label: c.label });
            } else if (c.key === 'institution') {
                cols.push({ key: 'institution_name', label: c.label });
            } else if (c.key === 'status') {
                cols.push({ key: 'activation_status', label: c.label });
            } else {
                cols.push({ key: c.key, label: c.label });
            }
        }
        return cols;
    };

    return {
        init: function (cb) {
            loadFromServer(function () {
                bindEvents();
                if (cb) cb();
            });
        },
        applyTo: applyTo,
        visibleForExport: visibleForExport
    };
})();


/**
 * Main Students List Module
 * Based on original KTStudentsList structure
 */
var KTStudentsList = function () {
    // Shared variables - same as original
    var datatables = {};
    var activeDatatable = null;
    var initializedTabs = {};
    var searchDebounceTimer = null;
    var currentBranchId = null;
    var toggleActivationModal = null;

    // Get current filters from the filter form
    var getFilters = function () {
        var filters = {};
        var filterForm = document.querySelector('[data-kt-students-list-table-filter="form"]');
        if (filterForm) {
            var filterSelects = filterForm.querySelectorAll('select[data-filter-field]');
            filterSelects.forEach(function (select) {
                var field = select.getAttribute('data-filter-field');
                var value = $(select).val();
                if (value && value !== '') {
                    filters[field] = value;
                }
            });
        }
        return filters;
    };

    // Get current search value
    var getSearchValue = function () {
        var searchInput = document.querySelector('[data-kt-students-list-table-filter="search"]');
        return searchInput ? searchInput.value : '';
    };

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Fetch and update branch counts
    var fetchBranchCounts = function () {
        if (!isAdmin || typeof routeBranchCounts === 'undefined') return;
        $.ajax({
            url: routeBranchCounts,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success && response.counts) {
                    Object.keys(response.counts).forEach(function (branchId) {
                        var badge = document.querySelector('.branch-count-badge[data-branch-id="' + branchId + '"]');
                        if (badge) {
                            badge.innerHTML = response.counts[branchId];
                            badge.classList.remove('badge-loading');
                        }
                    });
                    document.querySelectorAll('.branch-count-badge').forEach(function (badge) {
                        var branchId = badge.getAttribute('data-branch-id');
                        if (!response.counts[branchId]) {
                            badge.innerHTML = '0';
                            badge.classList.remove('badge-loading');
                        }
                    });
                }
            },
            error: function () {
                document.querySelectorAll('.branch-count-badge').forEach(function (badge) {
                    badge.innerHTML = '-';
                    badge.classList.remove('badge-loading');
                });
            }
        });
    };

    // Build 19 DataTable columns
    var buildColumns = function () {
        return [
            // 0 - counter
            { data: 'counter', orderable: false, searchable: false },
            // 1 - student (name + unique_id combo display)
            {
                data: 'student_name', orderable: true,
                render: function (data, type, row) {
                    if (type === 'export') return data;
                    var isActive = row.is_active;
                    var cls = isActive ? 'text-gray-800 text-hover-primary' : 'text-danger text-hover-danger';
                    var tip = isActive ? '' : 'title="Inactive Student" data-bs-toggle="tooltip" data-bs-placement="top"';
                    var showUrl = routeStudentShow.replace(':id', row.student_id);
                    return '<div class="d-flex flex-column">'
                         + '<a href="' + showUrl + '" class="' + cls + ' fw-bold mb-1" ' + tip + '>' + escapeHtml(data) + '</a>'
                         + '<span class="text-primary fs-7">' + escapeHtml(row.student_unique_id) + '</span></div>';
                }
            },
            // 2 - class
            {
                data: 'class_name', orderable: true, searchable: false,
                render: function (data, type, row) {
                    if (type === 'export' || !data || data === '-') return data || '-';
                    if (row.class_id && typeof routeClassShow !== 'undefined') {
                        return '<a href="' + routeClassShow.replace(':id', row.class_id) + '" class="text-gray-800 text-hover-primary">' + escapeHtml(data) + '</a>';
                    }
                    return escapeHtml(data);
                }
            },
            // 3 - group
            {
                data: 'group_badge', orderable: false, searchable: false,
                render: function (data, type, row) {
                    if (type === 'export') return row.academic_group || '-';
                    return data || '-';
                }
            },
            // 4 - batch
            { data: 'batch_name', orderable: true, searchable: false, render: function (d) { return d || '-'; } },
            // 5 - institution
            { data: 'institution_name', orderable: true, searchable: false, render: function (d) { return d || '-'; } },
            // 6 - mobile home
            { data: 'mobile_home', orderable: false, searchable: false, render: function (d) { return d || '-'; } },
            // 7 - mobile sms
            { data: 'mobile_sms', orderable: false, searchable: false, render: function (d) { return d || '-'; } },
            // 8 - mobile whatsapp
            { data: 'mobile_whatsapp', orderable: false, searchable: false, render: function (d) { return d || '-'; } },
            // 9 - guardian 1
            {
                data: 'guardian_1_name', orderable: false, searchable: false,
                render: function (data, type, row) {
                    if (!data) return '-';
                    if (type === 'export') {
                        var parts = [data];
                        if (row.guardian_1_relationship) parts.push(capitalize(row.guardian_1_relationship));
                        if (row.guardian_1_mobile) parts.push(row.guardian_1_mobile);
                        return parts.join(', ');
                    }
                    var html = '<div class="d-flex flex-column">';
                    html += '<span class="fw-semibold text-gray-800">' + escapeHtml(data) + '</span>';
                    var details = [];
                    if (row.guardian_1_relationship) details.push(capitalize(escapeHtml(row.guardian_1_relationship)));
                    if (row.guardian_1_mobile) details.push(escapeHtml(row.guardian_1_mobile));
                    if (details.length > 0) {
                        html += '<span class="text-muted fs-7">' + details.join(', ') + '</span>';
                    }
                    html += '</div>';
                    return html;
                }
            },
            // 10 - guardian 2
            {
                data: 'guardian_2_name', orderable: false, searchable: false,
                render: function (data, type, row) {
                    if (!data) return '-';
                    if (type === 'export') {
                        var parts = [data];
                        if (row.guardian_2_relationship) parts.push(capitalize(row.guardian_2_relationship));
                        if (row.guardian_2_mobile) parts.push(row.guardian_2_mobile);
                        return parts.join(', ');
                    }
                    var html = '<div class="d-flex flex-column">';
                    html += '<span class="fw-semibold text-gray-800">' + escapeHtml(data) + '</span>';
                    var details = [];
                    if (row.guardian_2_relationship) details.push(capitalize(escapeHtml(row.guardian_2_relationship)));
                    if (row.guardian_2_mobile) details.push(escapeHtml(row.guardian_2_mobile));
                    if (details.length > 0) {
                        html += '<span class="text-muted fs-7">' + details.join(', ') + '</span>';
                    }
                    html += '</div>';
                    return html;
                }
            },
            // 11 - sibling 1
            {
                data: 'sibling_1_name', orderable: false, searchable: false,
                render: function (data, type, row) {
                    if (!data) return '-';
                    if (type === 'export') {
                        var parts = [data];
                        if (row.sibling_1_relationship) parts.push(capitalize(row.sibling_1_relationship));
                        if (row.sibling_1_class) parts.push(row.sibling_1_class);
                        if (row.sibling_1_institution) parts.push(row.sibling_1_institution);
                        return parts.join(', ');
                    }
                    var html = '<div class="d-flex flex-column">';
                    html += '<span class="fw-semibold text-gray-800">' + escapeHtml(data) + '</span>';
                    var details = [];
                    if (row.sibling_1_relationship) details.push(capitalize(escapeHtml(row.sibling_1_relationship)));
                    if (row.sibling_1_class) details.push(escapeHtml(row.sibling_1_class));
                    if (row.sibling_1_institution) details.push(escapeHtml(row.sibling_1_institution));
                    if (details.length > 0) {
                        html += '<span class="text-muted fs-7">' + details.join(', ') + '</span>';
                    }
                    html += '</div>';
                    return html;
                }
            },
            // 12 - sibling 2
            {
                data: 'sibling_2_name', orderable: false, searchable: false,
                render: function (data, type, row) {
                    if (!data) return '-';
                    if (type === 'export') {
                        var parts = [data];
                        if (row.sibling_2_relationship) parts.push(capitalize(row.sibling_2_relationship));
                        if (row.sibling_2_class) parts.push(row.sibling_2_class);
                        if (row.sibling_2_institution) parts.push(row.sibling_2_institution);
                        return parts.join(', ');
                    }
                    var html = '<div class="d-flex flex-column">';
                    html += '<span class="fw-semibold text-gray-800">' + escapeHtml(data) + '</span>';
                    var details = [];
                    if (row.sibling_2_relationship) details.push(capitalize(escapeHtml(row.sibling_2_relationship)));
                    if (row.sibling_2_class) details.push(escapeHtml(row.sibling_2_class));
                    if (row.sibling_2_institution) details.push(escapeHtml(row.sibling_2_institution));
                    if (details.length > 0) {
                        html += '<span class="text-muted fs-7">' + details.join(', ') + '</span>';
                    }
                    html += '</div>';
                    return html;
                }
            },
            // 13 - tuition fee
            { data: 'tuition_fee', orderable: true, searchable: false, render: function (d) { return d || '-'; } },
            // 14 - payment type
            { data: 'payment_type', orderable: false, searchable: false, render: function (d) { return d || '-'; } },
            // 15 - status
            {
                data: 'activation_status', orderable: false, searchable: false,
                render: function (data) {
                    if (data === 'active') return '<span class="badge badge-light-success">Active</span>';
                    if (data === 'inactive') return '<span class="badge badge-light-danger">Inactive</span>';
                    return '<span class="badge badge-light-warning">Pending</span>';
                }
            },
            // 16 - admission date
            { data: 'admission_date', orderable: true, searchable: false, render: function (d) { return d || '-'; } },
            // 17 - admitted by
            { data: 'admitted_by', orderable: false, searchable: false, render: function (d) { return d || '-'; } },
            // 18 - actions
            { data: 'actions', orderable: false, searchable: false, className: 'not-export' }
        ];
    };

    // DataTable config
    var getDataTableConfig = function (tableId, branchId) {
        return {
            processing: true,
            serverSide: true,
            deferRender: true,
            ajax: {
                url: routeStudentsData,
                type: 'GET',
                data: function (d) {
                    if (branchId) d.branch_id = branchId;
                    var f = getFilters();
                    Object.keys(f).forEach(function (key) { d[key] = f[key]; });
                    return d;
                },
                dataSrc: function (json) {
                    if (isAdmin && branchId) {
                        var badge = document.querySelector('.branch-count-badge[data-branch-id="' + branchId + '"]');
                        if (badge) {
                            badge.textContent = json.recordsTotal;
                        }
                    }
                    return json.data;
                },
                error: function (xhr, error, thrown) {
                    console.error('DataTable AJAX error:', error, thrown);
                }
            },
            columns: buildColumns(),
            order: [],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            lengthChange: true,
            autoWidth: false,
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                emptyTable: 'No students found',
                zeroRecords: 'No matching students found'
            },
            initComplete: function () {
                var api = this.api();
                // Apply column visibility after DataTable is fully ready
                setTimeout(function () {
                    KTStudentColumnSelector.applyTo(api);
                    // Reveal table, hide skeleton
                    revealTable(tableId);
                }, 50);
            },
            drawCallback: function () {
                KTMenu.init();
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        };
    };

    // Initialize a single datatable
    var initSingleDatatable = function (tableId, branchId) {
        var table = document.getElementById(tableId);
        if (!table) return null;

        if ($.fn.DataTable.isDataTable('#' + tableId)) {
            return $('#' + tableId).DataTable();
        }

        var config = getDataTableConfig(tableId, branchId);
        return $('#' + tableId).DataTable(config);
    };

    // Initialize datatables for admin (multiple tabs)
    var initAdminDatatables = function () {
        fetchBranchCounts();

        if (branchIds && branchIds.length > 0) {
            var firstBranchId = branchIds[0];
            var firstTableId = 'kt_students_table_branch_' + firstBranchId;
            datatables[firstBranchId] = initSingleDatatable(firstTableId, firstBranchId);
            activeDatatable = datatables[firstBranchId];
            initializedTabs[firstBranchId] = true;
            currentBranchId = firstBranchId;
        }

        // Tab change handler - same pattern as original
        var tabLinks = document.querySelectorAll('#branchTabs a[data-bs-toggle="tab"]');
        tabLinks.forEach(function (tabLink) {
            tabLink.addEventListener('shown.bs.tab', function (event) {
                var branchId = event.target.getAttribute('data-branch-id');
                var tableId = 'kt_students_table_branch_' + branchId;
                currentBranchId = branchId;

                if (!initializedTabs[branchId]) {
                    datatables[branchId] = initSingleDatatable(tableId, branchId);
                    initializedTabs[branchId] = true;
                }

                activeDatatable = datatables[branchId];

                if (activeDatatable) {
                    activeDatatable.columns.adjust().draw(false);
                }
            });
        });
    };

    // Initialize datatable for non-admin
    var initNonAdminDatatable = function () {
        var table = document.getElementById('kt_students_table');
        if (!table) return;
        datatables['single'] = initSingleDatatable('kt_students_table', null);
        activeDatatable = datatables['single'];
    };

    // Apply visibility to all tables
    var applyVisibilityAll = function () {
        Object.keys(datatables).forEach(function (key) {
            if (datatables[key]) {
                KTStudentColumnSelector.applyTo(datatables[key]);
            }
        });
    };

    // =========================================================================
    // Export helpers
    // =========================================================================
    var getExportDateTime = function () {
        return new Date().toLocaleString('en-US', {
            year: 'numeric', month: 'short', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
        });
    };

    var getExportFilename = function (ext) {
        var now = new Date();
        var ts = '' + now.getFullYear() + String(now.getMonth() + 1).padStart(2, '0')
               + String(now.getDate()).padStart(2, '0') + '_'
               + String(now.getHours()).padStart(2, '0') + String(now.getMinutes()).padStart(2, '0');
        return 'Students_Report_' + ts + '.' + ext;
    };

    var showExportSuccess = function (type, rowCount) {
        var messages = {
            'copy': 'Data copied to clipboard successfully!',
            'excel': 'Excel file exported successfully!',
            'csv': 'CSV file exported successfully!',
            'pdf': 'PDF file exported successfully!'
        };
        toastr.success(messages[type] + ' (' + rowCount + ' rows)', 'Export Complete');
    };

    var fetchAllDataForExport = function (callback) {
        if (typeof routeStudentsExport === 'undefined') {
            toastr.error('Export route not defined');
            return;
        }

        var params = { export: true };
        if (isAdmin && currentBranchId) params.branch_id = currentBranchId;
        var f = getFilters();
        Object.keys(f).forEach(function (key) { params[key] = f[key]; });
        var searchVal = getSearchValue();
        if (searchVal) params['search[value]'] = searchVal;

        Swal.fire({
            title: 'Preparing Export...',
            text: 'Fetching all data, please wait.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function () { Swal.showLoading(); }
        });

        $.ajax({
            url: routeStudentsExport,
            type: 'GET',
            data: params,
            success: function (response) {
                Swal.close();
                if (response.data && response.data.length > 0) {
                    callback(response.data);
                } else {
                    Swal.fire({ icon: 'warning', title: 'No Data', text: 'No data available to export.' });
                }
            },
            error: function () {
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Export Failed', text: 'Failed to fetch data for export.' });
            }
        });
    };

    var prepareExportData = function (data) {
        var visCols = KTStudentColumnSelector.visibleForExport();
        var headers = ['#'];
        visCols.forEach(function (c) { headers.push(c.label); });

        var rows = [];
        data.forEach(function (row, index) {
            var r = [index + 1];
            visCols.forEach(function (c) { r.push(row[c.key] || '-'); });
            rows.push(r);
        });
        return { headers: headers, rows: rows };
    };

    var copyToClipboard = function (data) {
        var e = prepareExportData(data);
        var text = 'Students Report\nExported on: ' + getExportDateTime() + '\n\n';
        text += e.headers.join('\t') + '\n';
        e.rows.forEach(function (row) { text += row.join('\t') + '\n'; });

        navigator.clipboard.writeText(text).then(function () {
            showExportSuccess('copy', e.rows.length);
        }).catch(function () {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showExportSuccess('copy', e.rows.length);
        });
    };

    var exportToExcel = function (data) {
        var e = prepareExportData(data);
        var wb = XLSX.utils.book_new();
        var wsData = [['Students Report'], ['Exported on: ' + getExportDateTime()], [], e.headers].concat(e.rows);
        var ws = XLSX.utils.aoa_to_sheet(wsData);
        ws['!merges'] = [
            { s: { r: 0, c: 0 }, e: { r: 0, c: e.headers.length - 1 } },
            { s: { r: 1, c: 0 }, e: { r: 1, c: e.headers.length - 1 } }
        ];
        XLSX.utils.book_append_sheet(wb, ws, 'Students');
        XLSX.writeFile(wb, getExportFilename('xlsx'));
        showExportSuccess('excel', e.rows.length);
    };

    var exportToCSV = function (data) {
        var e = prepareExportData(data);
        var csvContent = '"Students Report"\n"Exported on: ' + getExportDateTime() + '"\n\n';
        csvContent += e.headers.map(function (h) { return '"' + h.replace(/"/g, '""') + '"'; }).join(',') + '\n';
        e.rows.forEach(function (row) {
            csvContent += row.map(function (cell) { return '"' + String(cell).replace(/"/g, '""') + '"'; }).join(',') + '\n';
        });
        var blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = getExportFilename('csv');
        link.click();
        URL.revokeObjectURL(link.href);
        showExportSuccess('csv', e.rows.length);
    };

    var exportToPDF = function (data) {
        var e = prepareExportData(data);
        var doc = new jspdf.jsPDF('l', 'mm', 'a4');
        doc.setFontSize(16);
        doc.text('Students Report', 14, 15);
        doc.setFontSize(10);
        doc.text('Exported on: ' + getExportDateTime(), 14, 22);
        doc.autoTable({
            head: [e.headers],
            body: e.rows,
            startY: 28,
            styles: { fontSize: 8, cellPadding: 2 },
            headStyles: { fillColor: [63, 81, 181], textColor: 255, fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            didDrawPage: function () {
                doc.setFontSize(8);
                doc.text('Page ' + doc.internal.getNumberOfPages(),
                    doc.internal.pageSize.width / 2,
                    doc.internal.pageSize.height - 10,
                    { align: 'center' });
            }
        });
        doc.save(getExportFilename('pdf'));
        showExportSuccess('pdf', e.rows.length);
    };

    // Hook export buttons
    var exportButtons = function () {
        var exportItems = document.querySelectorAll('#kt_table_report_dropdown_menu [data-row-export]');
        exportItems.forEach(function (exportItem) {
            exportItem.addEventListener('click', function (e) {
                e.preventDefault();
                var exportType = this.getAttribute('data-row-export');
                fetchAllDataForExport(function (data) {
                    switch (exportType) {
                        case 'copy':  copyToClipboard(data); break;
                        case 'excel': exportToExcel(data);   break;
                        case 'csv':   exportToCSV(data);     break;
                        case 'pdf':   exportToPDF(data);     break;
                    }
                });
            });
        });
    };

    // Search with debouncing
    var handleSearch = function () {
        var filterSearch = document.querySelector('[data-kt-students-list-table-filter="search"]');
        if (!filterSearch) return;

        filterSearch.addEventListener('keyup', function (e) {
            if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(function () {
                if (activeDatatable) activeDatatable.search(e.target.value).draw();
            }, 300);
        });
    };

    // Filter
    var handleFilter = function () {
        var filterForm = document.querySelector('[data-kt-students-list-table-filter="form"]');
        if (!filterForm) return;

        var filterButton = filterForm.querySelector('[data-kt-students-list-table-filter="filter"]');
        var resetButton = filterForm.querySelector('[data-kt-students-list-table-filter="reset"]');

        if (filterButton) {
            filterButton.addEventListener('click', function () {
                if (isAdmin) {
                    Object.keys(datatables).forEach(function (key) {
                        if (datatables[key]) datatables[key].ajax.reload();
                    });
                    fetchBranchCounts();
                } else if (activeDatatable) {
                    activeDatatable.ajax.reload();
                }
            });
        }

        if (resetButton) {
            resetButton.addEventListener('click', function () {
                var filterSelects = filterForm.querySelectorAll('select[data-filter-field]');
                filterSelects.forEach(function (select) {
                    $(select).val(null).trigger('change');
                });
                var searchInput = document.querySelector('[data-kt-students-list-table-filter="search"]');
                if (searchInput) searchInput.value = '';
                if (isAdmin) {
                    Object.keys(datatables).forEach(function (key) {
                        if (datatables[key]) datatables[key].search('').ajax.reload();
                    });
                    fetchBranchCounts();
                } else if (activeDatatable) {
                    activeDatatable.search('').ajax.reload();
                }
            });
        }
    };

    // Delete students
    var handleDeletion = function () {
        document.addEventListener('click', function (e) {
            var deleteBtn = e.target.closest('.delete-student');
            if (!deleteBtn) return;
            e.preventDefault();

            var studentId = deleteBtn.getAttribute('data-student-id');
            var url = routeDeleteStudent.replace(':id', studentId);

            Swal.fire({
                title: "Are you sure to delete this student?",
                text: "This action cannot be undone!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete!"
            }).then(function (result) {
                if (result.isConfirmed) {
                    fetch(url, {
                        method: "DELETE",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
                        }
                    })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (data.success) {
                            Swal.fire({
                                title: "Deleted!",
                                text: "The student has been removed.",
                                icon: "success",
                                timer: 2000,
                                showConfirmButton: false
                            }).then(function () {
                                if (activeDatatable) activeDatatable.ajax.reload();
                                if (isAdmin) fetchBranchCounts();
                            });
                        } else {
                            Swal.fire({ title: "Error!", text: data.message, icon: "error" });
                        }
                    })
                    .catch(function () {
                        Swal.fire({ title: "Error!", text: "Something went wrong.", icon: "error" });
                    });
                }
            });
        });
    };

    // Toggle Activation Modal
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
            var reasonTextarea = document.getElementById('activation_reason');

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
            var reasonError = document.getElementById('reason_error');
            if (reasonError) reasonError.textContent = '';
        });
    };

    var handleToggleActivationSubmit = function () {
        var toggleForm = document.getElementById('kt_toggle_activation_form');
        if (!toggleForm) return;

        toggleForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var submitBtn = document.getElementById('kt_toggle_activation_submit');
            var reasonField = document.getElementById('activation_reason');
            var reasonError = document.getElementById('reason_error');

            if (!reasonField.value.trim() || reasonField.value.trim().length < 3) {
                reasonError.textContent = 'Please provide a valid reason (at least 3 characters).';
                reasonField.focus();
                return;
            }
            reasonError.textContent = '';
            submitBtn.setAttribute('data-kt-indicator', 'on');
            submitBtn.disabled = true;

            var formData = new FormData(toggleForm);

            fetch(routeToggleActive, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
                submitBtn.removeAttribute('data-kt-indicator');
                submitBtn.disabled = false;

                if (response.success) {
                    if (toggleActivationModal) toggleActivationModal.hide();
                    toggleForm.reset();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Student status updated successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function () {
                        if (activeDatatable) activeDatatable.ajax.reload(null, false);
                        if (isAdmin) fetchBranchCounts();
                    });
                } else {
                    var errorMessage = response.message || 'Something went wrong.';
                    if (response.errors) {
                        var errorList = [];
                        Object.keys(response.errors).forEach(function (key) {
                            errorList.push(response.errors[key].join(', '));
                        });
                        errorMessage = errorList.join('\n');
                    }
                    Swal.fire({ icon: 'error', title: 'Error!', text: errorMessage });
                }
            })
            .catch(function (error) {
                console.error('Toggle activation error:', error);
                submitBtn.removeAttribute('data-kt-indicator');
                submitBtn.disabled = false;
                Swal.fire({ icon: 'error', title: 'Error!', text: 'An unexpected error occurred.' });
            });
        });
    };

    var handleModalReset = function () {
        var modalElement = document.getElementById('kt_toggle_activation_student_modal');
        if (modalElement) {
            modalElement.addEventListener('hidden.bs.modal', function () {
                var toggleForm = document.getElementById('kt_toggle_activation_form');
                if (toggleForm) toggleForm.reset();
                var reasonError = document.getElementById('reason_error');
                if (reasonError) reasonError.textContent = '';
            });
        }
    };

    return {
        init: function () {
            // Load column settings first, then initialize datatables
            KTStudentColumnSelector.init(function () {
                if (typeof isAdmin !== 'undefined' && isAdmin) {
                    initAdminDatatables();
                } else {
                    initNonAdminDatatable();
                }

                initToggleActivationModal();
                exportButtons();
                handleSearch();
                handleDeletion();
                handleFilter();
                handleToggleActivationTrigger();
                handleToggleActivationSubmit();
                handleModalReset();
            });
        },
        reload: function () {
            if (activeDatatable) activeDatatable.ajax.reload();
            if (isAdmin) fetchBranchCounts();
        },
        reloadAll: function () {
            Object.keys(datatables).forEach(function (key) {
                if (datatables[key]) datatables[key].ajax.reload();
            });
            if (isAdmin) fetchBranchCounts();
        },
        refreshCounts: function () {
            if (isAdmin) fetchBranchCounts();
        },
        applyVisibilityAll: applyVisibilityAll
    };
}();

// Initialize on DOM ready
KTUtil.onDOMContentLoaded(function () {
    KTStudentsList.init();
});
