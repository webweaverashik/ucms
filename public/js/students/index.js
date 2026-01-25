"use strict";

var KTStudentsList = function () {
    // Define shared variables
    var datatables = {};
    var activeDatatable = null;
    var initializedTabs = {};
    var searchDebounceTimer = null;
    var currentBranchId = null;
    var toggleActivationModal = null;
    var bulkToggleModal = null;

    // Store selected student IDs across pages (using Set for uniqueness)
    var selectedStudents = new Set();
    // Track which pages have selections for multi-page indicator
    var pagesWithSelections = new Set();

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

    // Get current page number from active datatable
    var getCurrentPage = function () {
        if (activeDatatable) {
            return activeDatatable.page.info().page;
        }
        return 0;
    };

    // Update bulk actions toolbar visibility and count
    var updateBulkActionsToolbar = function () {
        var toolbar = document.getElementById('bulk_actions_toolbar');
        var selectedCountEl = document.getElementById('selected_count');
        var multipageIndicator = document.getElementById('multipage_indicator');

        if (!toolbar) return;

        var count = selectedStudents.size;

        // Update count display
        if (selectedCountEl) {
            selectedCountEl.textContent = count;
        }

        // Show/hide multi-page indicator
        if (multipageIndicator) {
            if (pagesWithSelections.size > 1) {
                multipageIndicator.style.display = 'inline';
            } else {
                multipageIndicator.style.display = 'none';
            }
        }

        // Show/hide toolbar
        if (count > 0) {
            toolbar.style.display = 'flex';
            toolbar.style.cssText = 'display: flex !important;';
        } else {
            toolbar.style.display = 'none';
            toolbar.style.cssText = 'display: none !important;';
        }

        // Update header checkboxes state
        updateHeaderCheckboxes();
    };

    // Update header checkbox state based on current page selection
    var updateHeaderCheckboxes = function () {
        var headerCheckboxes = document.querySelectorAll('.header-checkbox');

        headerCheckboxes.forEach(function (headerCheckbox) {
            var table = headerCheckbox.closest('table');
            if (!table) return;

            var rowCheckboxes = table.querySelectorAll('.row-checkbox');
            var checkedCount = 0;
            var totalCount = rowCheckboxes.length;

            rowCheckboxes.forEach(function (checkbox) {
                if (checkbox.checked) {
                    checkedCount++;
                }
            });

            if (totalCount === 0) {
                headerCheckbox.checked = false;
                headerCheckbox.indeterminate = false;
            } else if (checkedCount === 0) {
                headerCheckbox.checked = false;
                headerCheckbox.indeterminate = false;
            } else if (checkedCount === totalCount) {
                headerCheckbox.checked = true;
                headerCheckbox.indeterminate = false;
            } else {
                headerCheckbox.checked = false;
                headerCheckbox.indeterminate = true;
            }
        });
    };

    // Sync checkboxes with selected students after table redraw
    var syncCheckboxesWithSelection = function () {
        var rowCheckboxes = document.querySelectorAll('.row-checkbox');

        rowCheckboxes.forEach(function (checkbox) {
            var studentId = parseInt(checkbox.value);
            checkbox.checked = selectedStudents.has(studentId);
        });

        updateHeaderCheckboxes();
        updateBulkActionsToolbar();
    };

    // Clear all selections
    var clearAllSelections = function () {
        selectedStudents.clear();
        pagesWithSelections.clear();

        // Uncheck all checkboxes
        var allCheckboxes = document.querySelectorAll('.row-checkbox, .header-checkbox');
        allCheckboxes.forEach(function (checkbox) {
            checkbox.checked = false;
            checkbox.indeterminate = false;
        });

        updateBulkActionsToolbar();
    };

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

    // Build columns array based on permissions
    var buildColumns = function () {
        var columns = [];

        // Checkbox column - only if user can deactivate
        if (typeof canDeactivate !== 'undefined' && canDeactivate) {
            columns.push({
                data: 'checkbox',
                orderable: false,
                searchable: false,
                className: 'text-center not-export',
                render: function (data, type, row) {
                    var isChecked = selectedStudents.has(row.student.id) ? 'checked' : '';
                    return '<div class="form-check form-check-sm form-check-custom form-check-solid justify-content-center">' +
                        '<input class="form-check-input row-checkbox" type="checkbox" value="' + row.student.id + '" ' + isChecked + ' />' +
                        '</div>';
                }
            });
        }

        // Counter column
        columns.push({
            data: 'counter',
            orderable: false,
            searchable: false
        });

        // Student column
        columns.push({
            data: 'student',
            orderable: true,
            render: function (data, type, row) {
                if (type === 'export') {
                    return data.name + ' (' + data.student_unique_id + ')';
                }

                var nameClass = data.is_active ? 'text-gray-800 text-hover-primary' : 'text-danger';
                var tooltip = data.is_active ? '' : 'title="Inactive Student" data-bs-toggle="tooltip" data-bs-placement="top"';
                var showUrl = routeStudentShow.replace(':id', data.id);

                return '<div class="d-flex align-items-center">' +
                    '<div class="d-flex flex-column text-start">' +
                    '<a href="' + showUrl + '" class="' + nameClass + ' mb-1" ' + tooltip + '>' + escapeHtml(data.name) + '</a>' +
                    '<span class="fw-bold fs-base">' + escapeHtml(data.student_unique_id) + '</span>' +
                    '</div></div>';
            }
        });

        // Class name column
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

        // Group badge column
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

        // Batch name column
        columns.push({
            data: 'batch_name',
            orderable: true,
            searchable: false
        });

        // Institution column
        columns.push({
            data: 'institution_name',
            orderable: true,
            searchable: false
        });

        // Home mobile column
        columns.push({
            data: 'home_mobile',
            orderable: false,
            searchable: false
        });

        // Tuition fee column
        columns.push({
            data: 'tuition_fee',
            orderable: true,
            searchable: false
        });

        // Payment info column
        columns.push({
            data: 'payment_info',
            orderable: false,
            searchable: false
        });

        // Actions column
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
            drawCallback: function () {
                KTMenu.init();
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });

                // Sync checkboxes after draw
                syncCheckboxesWithSelection();
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

                // Sync checkboxes when switching tabs
                syncCheckboxesWithSelection();
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

    // Handle checkbox events
    var handleCheckboxEvents = function () {
        // Skip if user doesn't have deactivate permission
        if (typeof canDeactivate === 'undefined' || !canDeactivate) {
            return;
        }

        // Handle header checkbox (select all on current page)
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('header-checkbox')) {
                var table = e.target.closest('table');
                if (!table) return;

                var rowCheckboxes = table.querySelectorAll('.row-checkbox');
                var isChecked = e.target.checked;
                var currentPage = getCurrentPage();

                rowCheckboxes.forEach(function (checkbox) {
                    checkbox.checked = isChecked;
                    var studentId = parseInt(checkbox.value);

                    if (isChecked) {
                        selectedStudents.add(studentId);
                        pagesWithSelections.add(currentPage);
                    } else {
                        selectedStudents.delete(studentId);
                    }
                });

                // Update pages with selections
                if (!isChecked) {
                    updatePagesWithSelections();
                }

                updateBulkActionsToolbar();
            }
        });

        // Handle individual row checkbox
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('row-checkbox')) {
                var studentId = parseInt(e.target.value);
                var currentPage = getCurrentPage();

                if (e.target.checked) {
                    selectedStudents.add(studentId);
                    pagesWithSelections.add(currentPage);
                } else {
                    selectedStudents.delete(studentId);
                    updatePagesWithSelections();
                }

                updateBulkActionsToolbar();
            }
        });

        // Handle clear selection button
        var clearBtn = document.getElementById('btn_clear_selection');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                clearAllSelections();
            });
        }
    };

    // Update which pages have selections (recalculate after deselection)
    var updatePagesWithSelections = function () {
        // We can't easily track which page each student is on after they're deselected
        // So we just check if there are still selections
        if (selectedStudents.size === 0) {
            pagesWithSelections.clear();
        }
    };

    // Handle bulk activation buttons
    var handleBulkActivation = function () {
        // Skip if user doesn't have deactivate permission
        if (typeof canDeactivate === 'undefined' || !canDeactivate) {
            return;
        }

        var bulkActivateBtn = document.getElementById('btn_bulk_activate');
        var bulkDeactivateBtn = document.getElementById('btn_bulk_deactivate');

        if (bulkActivateBtn) {
            bulkActivateBtn.addEventListener('click', function () {
                openBulkModal('active');
            });
        }

        if (bulkDeactivateBtn) {
            bulkDeactivateBtn.addEventListener('click', function () {
                openBulkModal('inactive');
            });
        }
    };

    // Open bulk modal
    var openBulkModal = function (status) {
        if (selectedStudents.size === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: 'Please select at least one student.'
            });
            return;
        }

        // Update modal content
        var actionText = status === 'active' ? 'activate' : 'deactivate';
        var modalTitle = document.getElementById('bulk-toggle-activation-modal-title');
        var actionTypeText = document.getElementById('bulk_action_type');
        var countDisplay = document.getElementById('bulk_student_count');
        var reasonLabel = document.getElementById('bulk_reason_label');
        var statusInput = document.getElementById('bulk_activation_status');
        var submitBtn = document.getElementById('kt_bulk_toggle_activation_submit');

        if (modalTitle) {
            modalTitle.textContent = 'Bulk ' + (status === 'active' ? 'Activation' : 'Deactivation');
        }

        if (actionTypeText) {
            actionTypeText.textContent = actionText;
        }

        if (countDisplay) {
            countDisplay.textContent = selectedStudents.size;
        }

        if (reasonLabel) {
            reasonLabel.textContent = (status === 'active' ? 'Activation' : 'Deactivation') + ' Reason';
        }

        if (statusInput) {
            statusInput.value = status;
        }

        if (submitBtn) {
            submitBtn.className = 'btn ' + (status === 'active' ? 'btn-success' : 'btn-warning');
            // Reset button text
            submitBtn.innerHTML = '<span class="indicator-label">Submit</span><span class="indicator-progress">Please wait...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>';
        }

        // Populate hidden student IDs
        var container = document.getElementById('bulk_student_ids_container');
        if (container) {
            container.innerHTML = '';
            selectedStudents.forEach(function (studentId) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'student_ids[]';
                input.value = studentId;
                container.appendChild(input);
            });
        }

        // Clear previous reason
        var reasonTextarea = document.getElementById('bulk_activation_reason');
        if (reasonTextarea) {
            reasonTextarea.value = '';
        }

        // Show modal
        if (bulkToggleModal) {
            bulkToggleModal.show();
        }
    };

    // Handle bulk form submission
    var handleBulkFormSubmit = function () {
        // Skip if user doesn't have deactivate permission
        if (typeof canDeactivate === 'undefined' || !canDeactivate) {
            return;
        }

        var bulkForm = document.getElementById('kt_bulk_toggle_activation_form');
        if (!bulkForm) return;

        bulkForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var submitBtn = document.getElementById('kt_bulk_toggle_activation_submit');
            if (!submitBtn) return;

            var originalBtnText = submitBtn.innerHTML;

            // Validate reason field
            var reasonField = document.getElementById('bulk_activation_reason');
            if (!reasonField || !reasonField.value.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Reason Required',
                    text: 'Please provide a reason for this bulk status change.'
                });
                if (reasonField) reasonField.focus();
                return;
            }

            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.querySelector('.indicator-label').style.display = 'none';
            submitBtn.querySelector('.indicator-progress').style.display = 'inline-block';

            // Prepare form data
            var formData = new FormData(bulkForm);

            // Get CSRF token
            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error('CSRF token not found');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                return;
            }

            // Send AJAX request
            fetch(routeBulkToggleActive, {
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
                    submitBtn.querySelector('.indicator-label').style.display = 'inline-block';
                    submitBtn.querySelector('.indicator-progress').style.display = 'none';

                    if (response.success) {
                        // Close modal
                        if (bulkToggleModal) {
                            bulkToggleModal.hide();
                        }

                        // Reset form
                        bulkForm.reset();

                        // Clear selections
                        clearAllSelections();

                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Student statuses updated successfully.',
                            timer: 2500,
                            showConfirmButton: false
                        }).then(function () {
                            // Reload all datatables to reflect changes
                            if (isAdmin) {
                                Object.keys(datatables).forEach(function (key) {
                                    if (datatables[key]) {
                                        datatables[key].ajax.reload(null, false);
                                    }
                                });
                                fetchBranchCounts();
                            } else if (activeDatatable) {
                                activeDatatable.ajax.reload(null, false);
                            }
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
                    console.error('Bulk toggle activation error:', error);

                    // Re-enable button
                    submitBtn.disabled = false;
                    submitBtn.querySelector('.indicator-label').style.display = 'inline-block';
                    submitBtn.querySelector('.indicator-progress').style.display = 'none';

                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An unexpected error occurred. Please try again.'
                    });
                });
        });
    };

    // Fetch all data for export
    var fetchAllDataForExport = function (callback) {
        var params = {
            export: true,
            draw: 1,
            start: 0,
            length: -1,
            'search[value]': getSearchValue()
        };

        if (isAdmin && currentBranchId) {
            params.branch_id = currentBranchId;
        }

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
            url: routeStudentsData,
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

    // Prepare export data
    var prepareExportData = function (data) {
        var headers = ['#', 'Student', 'Class', 'Group', 'Batch', 'Institution', 'Mobile (Home)', 'Tuition Fee', 'Payment Type'];
        var rows = [];

        data.forEach(function (row, index) {
            var groupText = '-';
            if (row.group_badge) {
                var temp = document.createElement('div');
                temp.innerHTML = row.group_badge;
                groupText = temp.textContent || temp.innerText || '-';
            }

            rows.push([
                index + 1,
                row.student.name + ' (' + row.student.student_unique_id + ')',
                row.class_name || '-',
                groupText,
                row.batch_name || '-',
                row.institution_name || '-',
                row.home_mobile || '-',
                row.tuition_fee || '-',
                row.payment_info || '-'
            ]);
        });

        return { headers: headers, rows: rows };
    };

    // Export functions
    var copyToClipboard = function (data) {
        var exportData = prepareExportData(data);
        var text = exportData.headers.join('\t') + '\n';
        exportData.rows.forEach(function (row) {
            text += row.join('\t') + '\n';
        });

        navigator.clipboard.writeText(text).then(function () {
            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: exportData.rows.length + ' rows copied to clipboard.',
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(function () {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: exportData.rows.length + ' rows copied to clipboard.',
                timer: 2000,
                showConfirmButton: false
            });
        });
    };

    var exportToExcel = function (data) {
        var exportData = prepareExportData(data);
        var wb = XLSX.utils.book_new();
        var wsData = [exportData.headers].concat(exportData.rows);
        var ws = XLSX.utils.aoa_to_sheet(wsData);

        ws['!cols'] = [
            { wch: 5 },
            { wch: 35 },
            { wch: 20 },
            { wch: 12 },
            { wch: 15 },
            { wch: 40 },
            { wch: 15 },
            { wch: 12 },
            { wch: 15 }
        ];

        XLSX.utils.book_append_sheet(wb, ws, 'Students');
        XLSX.writeFile(wb, 'Student_Lists_Report.xlsx');
    };

    var exportToCSV = function (data) {
        var exportData = prepareExportData(data);
        var csvContent = '';

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
        link.download = 'Student_Lists_Report.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    };

    var exportToPDF = function (data) {
        var exportData = prepareExportData(data);
        var doc = new jspdf.jsPDF('l', 'mm', 'a4');

        doc.setFontSize(16);
        doc.text('Student Lists Report', 14, 15);

        doc.setFontSize(10);
        doc.text('Generated: ' + new Date().toLocaleString(), 14, 22);

        doc.autoTable({
            head: [exportData.headers],
            body: exportData.rows,
            startY: 28,
            styles: { fontSize: 8, cellPadding: 2 },
            headStyles: {
                fillColor: [63, 81, 181],
                textColor: 255,
                fontStyle: 'bold'
            },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            columnStyles: {
                0: { cellWidth: 10 },
                1: { cellWidth: 50 },
                2: { cellWidth: 30 },
                3: { cellWidth: 20 },
                4: { cellWidth: 25 },
                5: { cellWidth: 55 },
                6: { cellWidth: 25 },
                7: { cellWidth: 20 },
                8: { cellWidth: 25 }
            },
            didDrawPage: function (data) {
                doc.setFontSize(8);
                doc.text(
                    'Page ' + doc.internal.getNumberOfPages(),
                    doc.internal.pageSize.width / 2,
                    doc.internal.pageSize.height - 10,
                    { align: 'center' }
                );
            }
        });

        doc.save('Student_Lists_Report.pdf');
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
                // Clear selections when filter changes
                clearAllSelections();

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

                // Clear selections when filter resets
                clearAllSelections();

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
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.success) {
                                Swal.fire({
                                    title: "Deleted!",
                                    text: "The student has been removed.",
                                    icon: "success"
                                })
                                    .then(function () {
                                        // Remove from selections if selected
                                        selectedStudents.delete(parseInt(studentId));
                                        updateBulkActionsToolbar();

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

        var bulkModalElement = document.getElementById('kt_bulk_toggle_activation_modal');
        if (bulkModalElement) {
            bulkToggleModal = new bootstrap.Modal(bulkModalElement);
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
                    text: 'Please provide a reason for this status change.'
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

                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Student status updated successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function () {
                            // Reload datatable to reflect changes
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
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;

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
                var toggleForm = modalElement.querySelector('form');
                if (toggleForm) {
                    toggleForm.reset();
                }
            });
        }

        var bulkModalElement = document.getElementById('kt_bulk_toggle_activation_modal');
        if (bulkModalElement) {
            bulkModalElement.addEventListener('hidden.bs.modal', function () {
                var bulkForm = bulkModalElement.querySelector('form');
                if (bulkForm) {
                    bulkForm.reset();
                }
                // Reset submit button state
                var submitBtn = document.getElementById('kt_bulk_toggle_activation_submit');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    var indicatorLabel = submitBtn.querySelector('.indicator-label');
                    var indicatorProgress = submitBtn.querySelector('.indicator-progress');
                    if (indicatorLabel) indicatorLabel.style.display = 'inline-block';
                    if (indicatorProgress) indicatorProgress.style.display = 'none';
                }
            });
        }
    };

    return {
        init: function () {
            // Initialize datatables
            if (typeof isAdmin !== 'undefined' && isAdmin) {
                initAdminDatatables();
            } else {
                initNonAdminDatatable();
            }

            // Initialize modals
            initToggleActivationModal();

            // Setup handlers
            handleCheckboxEvents();
            handleBulkActivation();
            handleBulkFormSubmit();
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

        clearSelections: function () {
            clearAllSelections();
        },

        getSelectedStudents: function () {
            return Array.from(selectedStudents);
        }
    };
}();

// Initialize on DOM ready
KTUtil.onDOMContentLoaded(function () {
    KTStudentsList.init();
});