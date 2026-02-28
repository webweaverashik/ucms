"use strict";

/**
 * Student Column Configuration
 * IMPORTANT: The order here must match EXACTLY with:
 * 1. The <th> elements in students-table.blade.php
 * 2. The DataTable columns array
 * 
 * Total: 19 columns (including combined Student column which splits to 2 in export)
 */
var StudentColumnConfig = [
    { key: 'counter', label: '#', visible: true, required: true },
    { key: 'student', label: 'Student', visible: true, required: true },  // Combined in display, split in export
    { key: 'class', label: 'Class', visible: true, required: false },
    { key: 'group', label: 'Group', visible: true, required: false },
    { key: 'batch', label: 'Batch', visible: true, required: false },
    { key: 'institution', label: 'Institution', visible: true, required: false },
    { key: 'mobile_home', label: 'Mobile (Home)', visible: true, required: false },
    { key: 'mobile_sms', label: 'Mobile (SMS)', visible: false, required: false },
    { key: 'mobile_whatsapp', label: 'Mobile (WhatsApp)', visible: false, required: false },
    { key: 'guardian_1', label: 'Guardian 1', visible: false, required: false },
    { key: 'guardian_2', label: 'Guardian 2', visible: false, required: false },
    { key: 'sibling_1', label: 'Sibling 1', visible: false, required: false },
    { key: 'sibling_2', label: 'Sibling 2', visible: false, required: false },
    { key: 'tuition_fee', label: 'Tuition Fee', visible: true, required: false },
    { key: 'payment_type', label: 'Payment Type', visible: true, required: false },
    { key: 'status', label: 'Status', visible: false, required: false },
    { key: 'admission_date', label: 'Admission Date', visible: false, required: false },
    { key: 'admitted_by', label: 'Admitted By', visible: false, required: false },
    { key: 'actions', label: 'Actions', visible: true, required: true }
];

// Total number of columns
var TOTAL_COLUMNS = StudentColumnConfig.length;  // 19

/**
 * Column Selector Manager for Students
 */
var KTStudentColumnSelector = (function () {
    // Storage key version - increment when column structure changes
    var STORAGE_VERSION = 'v4';
    
    // Get storage key based on user role
    var getStorageKey = function () {
        return 'students_column_visibility_' + STORAGE_VERSION + '_' + (typeof isAdmin !== 'undefined' && isAdmin ? 'admin' : 'user');
    };
    
    // Clear old storage keys to prevent conflicts
    var clearOldStorageKeys = function () {
        var keysToRemove = [];
        for (var i = 0; i < localStorage.length; i++) {
            var key = localStorage.key(i);
            if (key && key.startsWith('students_column_visibility_') && key.indexOf(STORAGE_VERSION) === -1) {
                keysToRemove.push(key);
            }
        }
        keysToRemove.forEach(function (key) {
            localStorage.removeItem(key);
        });
    };
    
    // Get saved column visibility from localStorage
    var getSavedVisibility = function () {
        try {
            var saved = localStorage.getItem(getStorageKey());
            if (saved) {
                var parsed = JSON.parse(saved);
                // Validate that the saved data has the correct number of columns
                if (Object.keys(parsed).length === TOTAL_COLUMNS) {
                    return parsed;
                }
            }
        } catch (e) {
            console.warn('Failed to parse saved column visibility:', e);
        }
        return null;
    };
    
    // Save column visibility to localStorage
    var saveVisibility = function (visibility) {
        try {
            localStorage.setItem(getStorageKey(), JSON.stringify(visibility));
        } catch (e) {
            console.warn('Failed to save column visibility:', e);
        }
    };
    
    // Get current visibility settings (saved or default)
    var getVisibility = function () {
        var saved = getSavedVisibility();
        if (saved) {
            return saved;
        }
        
        // Return defaults
        var defaults = {};
        for (var i = 0; i < TOTAL_COLUMNS; i++) {
            defaults[i] = StudentColumnConfig[i].visible;
        }
        return defaults;
    };
    
    // Initialize column selector checkboxes
    var initColumnSelector = function (container) {
        if (!container) return;
        
        var visibility = getVisibility();
        var html = '';
        
        for (var i = 0; i < TOTAL_COLUMNS; i++) {
            var col = StudentColumnConfig[i];
            var isVisible = visibility[i] !== undefined ? visibility[i] : col.visible;
            var isDisabled = col.required ? 'disabled' : '';
            var checkedAttr = isVisible ? 'checked' : '';
            
            html += '<div class="form-check form-check-custom form-check-solid mb-3">' +
                '<input class="form-check-input column-visibility-checkbox" type="checkbox" ' +
                'id="col_student_' + i + '" ' +
                'data-column-index="' + i + '" ' +
                'data-column-key="' + col.key + '" ' +
                checkedAttr + ' ' + isDisabled + '>' +
                '<label class="form-check-label fw-semibold text-gray-700" for="col_student_' + i + '">' +
                col.label +
                (col.required ? ' <span class="badge badge-sm badge-light-primary ms-1">Required</span>' : '') +
                '</label>' +
                '</div>';
        }
        
        container.innerHTML = html;
    };
    
    // Apply column visibility to DataTable
    var applyColumnVisibility = function (dt, container) {
        if (!dt || !container) return;
        
        var visibility = {};
        var checkboxes = container.querySelectorAll('.column-visibility-checkbox');
        
        checkboxes.forEach(function (checkbox) {
            var colIndex = parseInt(checkbox.getAttribute('data-column-index'));
            var isVisible = checkbox.checked;
            visibility[colIndex] = isVisible;
            
            // Apply to DataTable column with safety check
            try {
                if (colIndex >= 0 && colIndex < TOTAL_COLUMNS) {
                    var column = dt.column(colIndex);
                    if (column && typeof column.visible === 'function') {
                        column.visible(isVisible);
                    }
                }
            } catch (e) {
                console.warn('Failed to set column visibility for index ' + colIndex + ':', e);
            }
        });
        
        // Save to localStorage
        saveVisibility(visibility);
        
        // Adjust columns after visibility change
        try {
            dt.columns.adjust().draw(false);
        } catch (e) {
            console.warn('Failed to adjust columns:', e);
        }
    };
    
    // Apply initial column visibility when DataTable is first loaded
    var applyInitialVisibility = function (dt) {
        if (!dt) return;
        
        var visibility = getVisibility();
        
        // Use setTimeout to ensure DataTable is fully initialized
        setTimeout(function () {
            try {
                for (var i = 0; i < TOTAL_COLUMNS; i++) {
                    var isVisible = visibility[i] !== undefined ? visibility[i] : StudentColumnConfig[i].visible;
                    var column = dt.column(i);
                    if (column && typeof column.visible === 'function') {
                        column.visible(isVisible);
                    }
                }
                dt.columns.adjust().draw(false);
            } catch (e) {
                console.warn('Failed to apply initial column visibility:', e);
            }
        }, 10);
    };
    
    // Reset to default visibility
    var resetToDefaults = function (container) {
        if (!container) return;
        
        var checkboxes = container.querySelectorAll('.column-visibility-checkbox');
        checkboxes.forEach(function (checkbox) {
            var colIndex = parseInt(checkbox.getAttribute('data-column-index'));
            if (colIndex >= 0 && colIndex < TOTAL_COLUMNS) {
                var colConfig = StudentColumnConfig[colIndex];
                if (colConfig && !colConfig.required) {
                    checkbox.checked = colConfig.visible;
                }
            }
        });
    };
    
    // Get visible columns for export (splits 'student' into 'student_name' and 'student_unique_id')
    var getVisibleColumnsForExport = function () {
        var visibility = getVisibility();
        var visibleCols = [];
        
        for (var i = 0; i < TOTAL_COLUMNS; i++) {
            var col = StudentColumnConfig[i];
            var isVisible = visibility[i] !== undefined ? visibility[i] : col.visible;
            
            if (isVisible && col.key !== 'actions') {
                // Split 'student' column into separate name and ID columns for export
                if (col.key === 'student') {
                    visibleCols.push({ key: 'student_name', label: 'Student Name', index: i });
                    visibleCols.push({ key: 'student_unique_id', label: 'Student ID', index: i });
                } else {
                    visibleCols.push({ key: col.key, label: col.label, index: i });
                }
            }
        }
        
        return visibleCols;
    };
    
    return {
        init: function () {
            // Clear old storage keys
            clearOldStorageKeys();
        },
        initColumnSelector: initColumnSelector,
        applyColumnVisibility: applyColumnVisibility,
        applyInitialVisibility: applyInitialVisibility,
        resetToDefaults: resetToDefaults,
        getVisibility: getVisibility,
        saveVisibility: saveVisibility,
        getVisibleColumnsForExport: getVisibleColumnsForExport
    };
})();


/**
 * Students List DataTable Manager
 */
var KTStudentsList = (function () {
    // Define shared variables
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

    // Fetch and update branch counts on page load (for admin only)
    var fetchBranchCounts = function () {
        if (!isAdmin || typeof routeBranchCounts === 'undefined') {
            return;
        }

        $.ajax({
            url: routeBranchCounts,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success && response.counts) {
                    // Update all branch badges with their counts
                    Object.keys(response.counts).forEach(function (branchId) {
                        var badge = document.querySelector('.branch-count-badge[data-branch-id="' + branchId + '"]');
                        if (badge) {
                            badge.innerHTML = response.counts[branchId];
                            badge.classList.remove('badge-loading');
                        }
                    });

                    // Set 0 for branches with no students
                    var allBadges = document.querySelectorAll('.branch-count-badge');
                    allBadges.forEach(function (badge) {
                        var branchId = badge.getAttribute('data-branch-id');
                        if (!response.counts[branchId]) {
                            badge.innerHTML = '0';
                            badge.classList.remove('badge-loading');
                        }
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error('Failed to fetch branch counts:', error);
                // On error, show 0 or dash for all badges
                var allBadges = document.querySelectorAll('.branch-count-badge');
                allBadges.forEach(function (badge) {
                    badge.innerHTML = '-';
                    badge.classList.remove('badge-loading');
                });
            }
        });
    };

    // Build columns array (19 columns)
    var buildColumns = function () {
        var columns = [];

        // 0: Counter column
        columns.push({
            data: 'counter',
            orderable: false,
            searchable: false
        });

        // 1: Student column (combined name + ID in display)
        columns.push({
            data: 'student_name',
            orderable: true,
            render: function (data, type, row) {
                if (type === 'export') {
                    return data;
                }

                var showUrl = routeStudentShow.replace(':id', row.student_id);

                return '<div class="d-flex flex-column">' +
                    '<a href="' + showUrl + '" class="text-gray-800 text-hover-primary fw-bold">' +
                    escapeHtml(data) + '</a>' +
                    '<span class="text-muted fs-7">' + escapeHtml(row.student_unique_id) + '</span>' +
                    '</div>';
            }
        });

        // 2: Class name column
        columns.push({
            data: 'class_name',
            orderable: true,
            searchable: false,
            render: function (data, type, row) {
                if (type === 'export' || !data || data === '-') {
                    return data || '-';
                }
                var classId = row.class_id;
                if (classId && typeof routeClassShow !== 'undefined') {
                    var classUrl = routeClassShow.replace(':id', classId);
                    return '<a href="' + classUrl + '" class="text-gray-800 text-hover-primary">' + escapeHtml(data) + '</a>';
                }
                return escapeHtml(data);
            }
        });

        // 3: Group badge column
        columns.push({
            data: 'group_badge',
            orderable: false,
            searchable: false,
            render: function (data, type) {
                if (type === 'export') {
                    var temp = document.createElement('div');
                    temp.innerHTML = data;
                    return temp.textContent || temp.innerText || '-';
                }
                return data;
            }
        });

        // 4: Batch name column
        columns.push({
            data: 'batch_name',
            orderable: true,
            searchable: false
        });

        // 5: Institution column
        columns.push({
            data: 'institution_name',
            orderable: true,
            searchable: false
        });

        // 6: Mobile home column
        columns.push({
            data: 'mobile_home',
            orderable: false,
            searchable: false
        });

        // 7: Mobile SMS column
        columns.push({
            data: 'mobile_sms',
            orderable: false,
            searchable: false
        });

        // 8: Mobile WhatsApp column
        columns.push({
            data: 'mobile_whatsapp',
            orderable: false,
            searchable: false
        });

        // 9: Guardian 1 column
        columns.push({
            data: 'guardian_1',
            orderable: false,
            searchable: false,
            render: function (data, type, row) {
                if (type === 'export') {
                    // Build export string
                    var guardian = row.guardian_1_name || '';
                    if (guardian && row.guardian_1_relationship) {
                        guardian += ' (' + row.guardian_1_relationship + ')';
                    }
                    if (guardian && row.guardian_1_mobile) {
                        guardian += ' - ' + row.guardian_1_mobile;
                    }
                    return guardian || '-';
                }
                return data;
            }
        });

        // 10: Guardian 2 column
        columns.push({
            data: 'guardian_2',
            orderable: false,
            searchable: false,
            render: function (data, type, row) {
                if (type === 'export') {
                    var guardian = row.guardian_2_name || '';
                    if (guardian && row.guardian_2_relationship) {
                        guardian += ' (' + row.guardian_2_relationship + ')';
                    }
                    if (guardian && row.guardian_2_mobile) {
                        guardian += ' - ' + row.guardian_2_mobile;
                    }
                    return guardian || '-';
                }
                return data;
            }
        });

        // 11: Sibling 1 column
        columns.push({
            data: 'sibling_1',
            orderable: false,
            searchable: false,
            render: function (data, type, row) {
                if (type === 'export') {
                    var sibling = row.sibling_1_name || '';
                    if (sibling && row.sibling_1_relationship) {
                        sibling += ' (' + row.sibling_1_relationship + ')';
                    }
                    if (sibling && row.sibling_1_class) {
                        sibling += ' - Class: ' + row.sibling_1_class;
                    }
                    if (sibling && row.sibling_1_institution) {
                        sibling += ' - ' + row.sibling_1_institution;
                    }
                    return sibling || '-';
                }
                return data;
            }
        });

        // 12: Sibling 2 column
        columns.push({
            data: 'sibling_2',
            orderable: false,
            searchable: false,
            render: function (data, type, row) {
                if (type === 'export') {
                    var sibling = row.sibling_2_name || '';
                    if (sibling && row.sibling_2_relationship) {
                        sibling += ' (' + row.sibling_2_relationship + ')';
                    }
                    if (sibling && row.sibling_2_class) {
                        sibling += ' - Class: ' + row.sibling_2_class;
                    }
                    if (sibling && row.sibling_2_institution) {
                        sibling += ' - ' + row.sibling_2_institution;
                    }
                    return sibling || '-';
                }
                return data;
            }
        });

        // 13: Tuition fee column
        columns.push({
            data: 'tuition_fee',
            orderable: true,
            searchable: false
        });

        // 14: Payment info column
        columns.push({
            data: 'payment_info',
            orderable: false,
            searchable: false
        });

        // 15: Status column
        columns.push({
            data: 'status_badge',
            orderable: false,
            searchable: false,
            render: function (data, type, row) {
                if (type === 'export') {
                    return row.activation_status || '-';
                }
                return data;
            }
        });

        // 16: Admission Date column
        columns.push({
            data: 'admission_date',
            orderable: true,
            searchable: false,
            render: function (data, type, row) {
                if (type === 'export') {
                    return data + (row.admission_date_time ? ' ' + row.admission_date_time : '');
                }
                if (!data || data === '-') return '<span class="text-muted">-</span>';
                return '<div class="d-flex flex-column">' +
                    '<span class="fw-semibold">' + escapeHtml(data) + '</span>' +
                    (row.admission_date_time ? '<span class="text-muted fs-7">' + escapeHtml(row.admission_date_time) + '</span>' : '') +
                    '</div>';
            }
        });

        // 17: Admitted By column
        columns.push({
            data: 'admitted_by',
            orderable: false,
            searchable: false,
            render: function (data, type) {
                if (type === 'export') {
                    return data || '-';
                }
                if (!data || data === '-') return '<span class="text-muted">-</span>';
                return '<span class="badge badge-light-info">' + escapeHtml(data) + '</span>';
            }
        });

        // 18: Actions column
        columns.push({
            data: 'actions',
            orderable: false,
            searchable: false,
            className: 'not-export'
        });

        return columns;
    };

    // Get DataTable AJAX config
    var getDataTableConfig = function (tableId, branchId) {
        var config = {
            processing: true,
            serverSide: true,
            deferRender: true,
            ajax: {
                url: routeStudentsData,
                type: 'GET',
                data: function (d) {
                    // Add branch_id for admin tabs
                    if (branchId) {
                        d.branch_id = branchId;
                    }

                    // Add custom filters
                    var filters = getFilters();
                    Object.keys(filters).forEach(function (key) {
                        d[key] = filters[key];
                    });

                    return d;
                },
                dataSrc: function (json) {
                    // Update badge count if admin (as fallback/refresh)
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
            initComplete: function (settings, json) {
                // Get the DataTable API instance
                var api = this.api();
                
                // Apply initial column visibility after table is ready
                KTStudentColumnSelector.applyInitialVisibility(api);
                
                // Initialize column selector checkboxes
                var container = document.querySelector('.column-checkbox-list');
                if (container) {
                    KTStudentColumnSelector.initColumnSelector(container);
                }
            },
            drawCallback: function () {
                KTMenu.init();
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        };

        return config;
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
        // First, fetch all branch counts immediately
        fetchBranchCounts();

        if (branchIds && branchIds.length > 0) {
            var firstBranchId = branchIds[0];
            var firstTableId = 'kt_students_table_branch_' + firstBranchId;

            datatables[firstBranchId] = initSingleDatatable(firstTableId, firstBranchId);
            activeDatatable = datatables[firstBranchId];
            initializedTabs[firstBranchId] = true;
            currentBranchId = firstBranchId;
        }

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

    // Get formatted date time for export
    var getExportDateTime = function () {
        var now = new Date();
        return now.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
    };

    // Get export filename with timestamp
    var getExportFilename = function (extension) {
        var now = new Date();
        var timestamp = now.getFullYear().toString() +
            String(now.getMonth() + 1).padStart(2, '0') +
            String(now.getDate()).padStart(2, '0') + '_' +
            String(now.getHours()).padStart(2, '0') +
            String(now.getMinutes()).padStart(2, '0');

        return 'Students_Report_' + timestamp + '.' + extension;
    };

    // Show export success notification
    var showExportSuccess = function (type, rowCount) {
        var messages = {
            'copy': 'Data copied to clipboard successfully!',
            'excel': 'Excel file exported successfully!',
            'csv': 'CSV file exported successfully!',
            'pdf': 'PDF file exported successfully!'
        };

        toastr.success(messages[type] + ' (' + rowCount + ' rows)', 'Export Complete');
    };

    // Fetch all data for export using dedicated export endpoint
    var fetchAllDataForExport = function (callback) {
        var params = {
            search: getSearchValue()
        };

        if (isAdmin && currentBranchId) {
            params.branch_id = currentBranchId;
        }

        // Add filters
        var filters = getFilters();
        Object.keys(filters).forEach(function (key) {
            params[key] = filters[key];
        });

        Swal.fire({
            title: 'Preparing Export...',
            text: 'Fetching all data, please wait.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: function () {
                Swal.showLoading();
            }
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
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Data',
                        text: 'No data available to export.'
                    });
                }
            },
            error: function () {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Export Failed',
                    text: 'Failed to fetch data for export.'
                });
            }
        });
    };

    // Prepare export data based on visible columns
    var prepareExportData = function (data) {
        var visibleCols = KTStudentColumnSelector.getVisibleColumnsForExport();
        var headers = visibleCols.map(function (col) {
            return col.label;
        });

        var rows = [];
        data.forEach(function (row, index) {
            var rowData = [];
            visibleCols.forEach(function (col) {
                var value = '-';
                
                switch (col.key) {
                    case 'counter':
                        value = index + 1;
                        break;
                    case 'student_name':
                        value = row.student_name || '-';
                        break;
                    case 'student_unique_id':
                        value = row.student_unique_id || '-';
                        break;
                    case 'class':
                        value = row.class_name || '-';
                        break;
                    case 'group':
                        value = row.academic_group || '-';
                        break;
                    case 'batch':
                        value = row.batch_name || '-';
                        break;
                    case 'institution':
                        value = row.institution_name || '-';
                        break;
                    case 'mobile_home':
                        value = row.mobile_home || '-';
                        break;
                    case 'mobile_sms':
                        value = row.mobile_sms || '-';
                        break;
                    case 'mobile_whatsapp':
                        value = row.mobile_whatsapp || '-';
                        break;
                    case 'guardian_1':
                        value = row.guardian_1 || '-';
                        break;
                    case 'guardian_2':
                        value = row.guardian_2 || '-';
                        break;
                    case 'sibling_1':
                        value = row.sibling_1 || '-';
                        break;
                    case 'sibling_2':
                        value = row.sibling_2 || '-';
                        break;
                    case 'tuition_fee':
                        value = row.tuition_fee || '-';
                        break;
                    case 'payment_type':
                        value = row.payment_type || '-';
                        break;
                    case 'status':
                        value = row.activation_status || '-';
                        break;
                    case 'admission_date':
                        value = row.admission_date || '-';
                        break;
                    case 'admitted_by':
                        value = row.admitted_by || '-';
                        break;
                    default:
                        value = row[col.key] || '-';
                }
                
                rowData.push(value);
            });
            rows.push(rowData);
        });

        return { headers: headers, rows: rows };
    };

    // Export functions
    var copyToClipboard = function (data) {
        var exportData = prepareExportData(data);
        var exportDateTime = getExportDateTime();

        // Add title and timestamp
        var text = 'Students Report\n';
        text += 'Exported on: ' + exportDateTime + '\n\n';
        text += exportData.headers.join('\t') + '\n';

        exportData.rows.forEach(function (row) {
            text += row.join('\t') + '\n';
        });

        navigator.clipboard.writeText(text).then(function () {
            showExportSuccess('copy', exportData.rows.length);
        }).catch(function () {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showExportSuccess('copy', exportData.rows.length);
        });
    };

    var exportToExcel = function (data) {
        var exportData = prepareExportData(data);
        var exportDateTime = getExportDateTime();

        var wb = XLSX.utils.book_new();

        // Create worksheet data with title and timestamp
        var wsData = [
            ['Students Report'],
            ['Exported on: ' + exportDateTime],
            [],  // Empty row for spacing
            exportData.headers
        ].concat(exportData.rows);

        var ws = XLSX.utils.aoa_to_sheet(wsData);

        // Merge cells for title row
        ws['!merges'] = [
            { s: { r: 0, c: 0 }, e: { r: 0, c: exportData.headers.length - 1 } },  // Title row
            { s: { r: 1, c: 0 }, e: { r: 1, c: exportData.headers.length - 1 } }   // Timestamp row
        ];

        // Auto-size columns
        var colWidths = exportData.headers.map(function (header, index) {
            var maxWidth = header.length;
            exportData.rows.forEach(function (row) {
                var cellValue = String(row[index] || '');
                if (cellValue.length > maxWidth) {
                    maxWidth = cellValue.length;
                }
            });
            return { wch: Math.min(maxWidth + 2, 50) };
        });
        ws['!cols'] = colWidths;

        XLSX.utils.book_append_sheet(wb, ws, 'Students');
        XLSX.writeFile(wb, getExportFilename('xlsx'));
        showExportSuccess('excel', exportData.rows.length);
    };

    var exportToCSV = function (data) {
        var exportData = prepareExportData(data);
        var exportDateTime = getExportDateTime();

        var csvContent = '';

        // Add title and timestamp
        csvContent += '"Students Report"\n';
        csvContent += '"Exported on: ' + exportDateTime + '"\n';
        csvContent += '\n';  // Empty row for spacing

        csvContent += exportData.headers.map(function (h) {
            return '"' + h.replace(/"/g, '""') + '"';
        }).join(',') + '\n';

        exportData.rows.forEach(function (row) {
            csvContent += row.map(function (cell) {
                return '"' + String(cell).replace(/"/g, '""') + '"';
            }).join(',') + '\n';
        });

        var blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = getExportFilename('csv');
        link.click();
        URL.revokeObjectURL(link.href);

        showExportSuccess('csv', exportData.rows.length);
    };

    var exportToPDF = function (data) {
        var exportData = prepareExportData(data);
        var exportDateTime = getExportDateTime();

        var doc = new jspdf.jsPDF('l', 'mm', 'a4');

        doc.setFontSize(16);
        doc.text('Students Report', 14, 15);
        doc.setFontSize(10);
        doc.text('Exported on: ' + exportDateTime, 14, 22);

        doc.autoTable({
            head: [exportData.headers],
            body: exportData.rows,
            startY: 28,
            styles: {
                fontSize: 7,
                cellPadding: 2
            },
            headStyles: {
                fillColor: [63, 81, 181],
                textColor: 255,
                fontStyle: 'bold'
            },
            alternateRowStyles: {
                fillColor: [245, 245, 245]
            },
            didDrawPage: function (pageData) {
                doc.setFontSize(8);
                doc.text(
                    'Page ' + doc.internal.getNumberOfPages(),
                    doc.internal.pageSize.width / 2,
                    doc.internal.pageSize.height - 10,
                    { align: 'center' }
                );
            }
        });

        doc.save(getExportFilename('pdf'));
        showExportSuccess('pdf', exportData.rows.length);
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
                        case 'copy':
                            copyToClipboard(data);
                            break;
                        case 'excel':
                            exportToExcel(data);
                            break;
                        case 'csv':
                            exportToCSV(data);
                            break;
                        case 'pdf':
                            exportToPDF(data);
                            break;
                    }
                });
            });
        });
    };

    // Setup column selector event handlers
    var setupColumnSelector = function () {
        // Apply button click
        var applyBtn = document.querySelector('.column-apply-btn');
        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                var container = document.querySelector('.column-checkbox-list');
                if (activeDatatable && container) {
                    KTStudentColumnSelector.applyColumnVisibility(activeDatatable, container);
                    toastr.success('Column visibility updated');
                }
            });
        }

        // Reset button click
        var resetBtn = document.querySelector('.column-reset-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                var container = document.querySelector('.column-checkbox-list');
                if (container) {
                    KTStudentColumnSelector.resetToDefaults(container);
                    toastr.info('Column selection reset to defaults. Click Apply to save.');
                }
            });
        }
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
                    // Also refresh counts after filter
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
                    // Also refresh counts after reset
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
                            })
                            .then(function () {
                                if (activeDatatable) activeDatatable.ajax.reload();
                                // Refresh counts after deletion
                                if (isAdmin) fetchBranchCounts();
                            });
                        } else {
                            Swal.fire({
                                title: "Error!",
                                text: data.message,
                                icon: "error"
                            });
                        }
                    })
                    .catch(function () {
                        Swal.fire({
                            title: "Error!",
                            text: "Something went wrong.",
                            icon: "error"
                        });
                    });
                }
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

    // Handle toggle activation modal trigger from action dropdown
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
            var reasonTextarea = document.getElementById('activation_reason');

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

            // Clear previous reason and error
            if (reasonTextarea) {
                reasonTextarea.value = '';
            }
            var reasonError = document.getElementById('reason_error');
            if (reasonError) {
                reasonError.textContent = '';
            }
        });
    };

    // Handle toggle activation form submission via AJAX
    var handleToggleActivationSubmit = function () {
        var toggleForm = document.getElementById('kt_toggle_activation_form');
        if (!toggleForm) return;

        toggleForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var submitBtn = document.getElementById('kt_toggle_activation_submit');
            var reasonField = document.getElementById('activation_reason');
            var reasonError = document.getElementById('reason_error');

            // Validate reason field
            if (!reasonField.value.trim() || reasonField.value.trim().length < 3) {
                reasonError.textContent = 'Please provide a valid reason (at least 3 characters).';
                reasonField.focus();
                return;
            }

            // Clear error
            reasonError.textContent = '';

            // Show loading
            submitBtn.setAttribute('data-kt-indicator', 'on');
            submitBtn.disabled = true;

            // Prepare form data
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

                // Re-enable button
                submitBtn.removeAttribute('data-kt-indicator');
                submitBtn.disabled = false;

                if (response.success) {
                    // Close modal
                    if (toggleActivationModal) {
                        toggleActivationModal.hide();
                    }

                    // Reset form
                    toggleForm.reset();

                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Student status updated successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function () {
                        // Reload datatable
                        if (activeDatatable) {
                            activeDatatable.ajax.reload(null, false);
                        }
                        // Refresh counts after status change
                        if (isAdmin) fetchBranchCounts();
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
                        text: errorMessage
                    });
                }
            })
            .catch(function (error) {
                console.error('Toggle activation error:', error);

                // Re-enable button
                submitBtn.removeAttribute('data-kt-indicator');
                submitBtn.disabled = false;

                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An unexpected error occurred. Please try again.'
                });
            });
        });
    };

    // Handle modal close - reset form
    var handleModalReset = function () {
        var modalElement = document.getElementById('kt_toggle_activation_student_modal');
        if (modalElement) {
            modalElement.addEventListener('hidden.bs.modal', function () {
                var toggleForm = document.getElementById('kt_toggle_activation_form');
                if (toggleForm) {
                    toggleForm.reset();
                }
                var reasonError = document.getElementById('reason_error');
                if (reasonError) {
                    reasonError.textContent = '';
                }
            });
        }
    };

    return {
        init: function () {
            // Initialize column selector
            KTStudentColumnSelector.init();

            // Initialize datatables
            if (typeof isAdmin !== 'undefined' && isAdmin) {
                initAdminDatatables();
            } else {
                initNonAdminDatatable();
            }

            // Initialize modal
            initToggleActivationModal();

            // Setup handlers
            setupColumnSelector();
            exportButtons();
            handleSearch();
            handleDeletion();
            handleFilter();
            handleToggleActivationTrigger();
            handleToggleActivationSubmit();
            handleModalReset();
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
        getActiveDatatable: function () {
            return activeDatatable;
        }
    };
})();

// Initialize on DOM ready
KTUtil.onDOMContentLoaded(function () {
    KTStudentsList.init();
});
