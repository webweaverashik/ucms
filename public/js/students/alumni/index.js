"use strict";

var KTAlumniStudentsList = function () {
    // Define shared variables
    var datatables = {};
    var activeDatatable = null;
    var initializedTabs = {};
    var currentFilters = {};
    var searchDebounceTimer = null;
    var currentBranchId = null;
    var toggleActivationModal = null;

    // Get current filters from the filter form
    var getFilters = function () {
        var filters = {};
        var filterForm = document.querySelector('[data-kt-alumni-table-filter="form"]');
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
        var searchInput = document.querySelector('[data-kt-alumni-table-filter="search"]');
        return searchInput ? searchInput.value : '';
    };

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Get DataTable columns configuration
    var getColumns = function () {
        var columns = [
            {
                data: 'counter',
                name: 'counter',
                orderable: false,
                searchable: false,
                className: 'pe-2'
            },
            {
                data: 'student',
                name: 'student',
                orderable: true,
                render: function (data, type, row) {
                    if (type === 'export') {
                        return data.name + ' (' + data.student_unique_id + ')';
                    }
                    var statusClass = data.is_active ? 'text-gray-800 text-hover-primary' : 'text-danger';
                    var tooltip = data.is_active ? '' : 'title="Inactive Student" data-bs-toggle="tooltip" data-bs-placement="top"';
                    var showUrl = routeStudentShow.replace(':id', data.id);

                    return '<div class="d-flex align-items-center">' +
                        '<div class="d-flex flex-column text-start">' +
                        '<a href="' + showUrl + '" class="' + statusClass + ' mb-1" ' + tooltip + '>' + escapeHtml(data.name) + '</a>' +
                        '<span class="fw-bold fs-base">' + escapeHtml(data.student_unique_id) + '</span>' +
                        '</div>' +
                        '</div>';
                }
            },
            {
                data: 'class_name',
                name: 'class_name',
                orderable: true
            },
            {
                data: 'group_badge',
                name: 'group_badge',
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
            },
            {
                data: 'batch_name',
                name: 'batch_name',
                orderable: true
            },
            {
                data: 'institution_name',
                name: 'institution_name',
                orderable: true,
                render: function (data, type) {
                    if (type === 'export') {
                        return data || '-';
                    }
                    return escapeHtml(data) || '-';
                }
            },
            {
                data: 'home_mobile',
                name: 'home_mobile',
                orderable: false,
                searchable: true
            },
            {
                data: 'tuition_fee',
                name: 'tuition_fee',
                orderable: true
            },
            {
                data: 'payment_info',
                name: 'payment_info',
                orderable: false
            }
        ];

        // Add actions column only for admin
        if (typeof isAdmin !== 'undefined' && isAdmin) {
            columns.push({
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false,
                className: 'not-export'
            });
        }

        return columns;
    };

    // Fetch and update branch counts on page load (for admin only)
    var fetchBranchCounts = function () {
        if (!isAdmin || typeof routeAlumniBranchCounts === 'undefined') {
            return;
        }

        $.ajax({
            url: routeAlumniBranchCounts,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.success && response.counts) {
                    // Update all branch badges with their counts
                    Object.keys(response.counts).forEach(function (branchId) {
                        var badge = document.querySelector('.alumni-branch-count-badge[data-branch-id="' + branchId + '"]');
                        if (badge) {
                            badge.innerHTML = response.counts[branchId];
                            badge.classList.remove('badge-loading');
                        }
                    });

                    // Set 0 for branches with no students
                    var allBadges = document.querySelectorAll('.alumni-branch-count-badge');
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
                console.error('Failed to fetch alumni branch counts:', error);
                // On error, show 0 or dash for all badges
                var allBadges = document.querySelectorAll('.alumni-branch-count-badge');
                allBadges.forEach(function (badge) {
                    badge.innerHTML = '-';
                    badge.classList.remove('badge-loading');
                });
            }
        });
    };

    // Get DataTable AJAX config
    var getDataTableConfig = function (tableId, branchId) {
        var config = {
            processing: true,
            serverSide: true,
            deferRender: true,
            ajax: {
                url: routeAlumniData,
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
                        var badge = document.querySelector('.alumni-branch-count-badge[data-branch-id="' + branchId + '"]');
                        if (badge) {
                            badge.textContent = json.recordsTotal;
                        }
                    }
                    return json.data;
                },
                error: function (xhr, error, thrown) {
                    console.error('DataTable AJAX error:', error, thrown);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error loading data',
                        text: 'Please refresh the page and try again.'
                    });
                }
            },
            columns: getColumns(),
            order: [[0, 'desc']],
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10,
            lengthChange: true,
            autoWidth: false,
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                emptyTable: 'No alumni students found',
                zeroRecords: 'No matching alumni students found'
            },
            drawCallback: function () {
                // Reinitialize menus after each draw
                KTMenu.init();

                // Initialize tooltips
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        };

        return config;
    };

    // Initialize a single datatable with server-side processing
    var initSingleDatatable = function (tableId, branchId) {
        var table = document.getElementById(tableId);
        if (!table) {
            return null;
        }

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

        // Initialize the first branch tab (it's active by default)
        if (branchIds && branchIds.length > 0) {
            var firstBranchId = branchIds[0];
            var firstTableId = 'kt_alumni_students_table_branch_' + firstBranchId;

            datatables[firstBranchId] = initSingleDatatable(firstTableId, firstBranchId);
            activeDatatable = datatables[firstBranchId];
            initializedTabs[firstBranchId] = true;
            currentBranchId = firstBranchId;
        }

        // Setup tab change event listener for lazy loading
        var tabLinks = document.querySelectorAll('#alumniBranchTabs a[data-bs-toggle="tab"]');
        tabLinks.forEach(function (tabLink) {
            tabLink.addEventListener('shown.bs.tab', function (event) {
                var branchId = event.target.getAttribute('data-branch-id');
                var tableId = 'kt_alumni_students_table_branch_' + branchId;

                currentBranchId = branchId;

                // Initialize datatable for this tab if not already done
                if (!initializedTabs[branchId]) {
                    datatables[branchId] = initSingleDatatable(tableId, branchId);
                    initializedTabs[branchId] = true;
                }

                // Set active datatable
                activeDatatable = datatables[branchId];

                // Adjust columns for responsive display
                if (activeDatatable) {
                    activeDatatable.columns.adjust().draw(false);
                }
            });
        });
    };

    // Initialize datatable for non-admin (single table)
    var initNonAdminDatatable = function () {
        var table = document.getElementById('kt_alumni_students_table');
        if (!table) {
            return;
        }

        datatables['single'] = initSingleDatatable('kt_alumni_students_table', null);
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
        return 'Alumni_Students_Report_' + timestamp + '.' + extension;
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
            url: routeAlumniData,
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

    // Copy to clipboard
    var copyToClipboard = function (data) {
        var exportData = prepareExportData(data);
        var exportDateTime = getExportDateTime();

        // Add title and timestamp
        var text = 'Alumni Students Report\n';
        text += 'Exported on: ' + exportDateTime + '\n\n';
        text += exportData.headers.join('\t') + '\n';

        exportData.rows.forEach(function (row) {
            text += row.join('\t') + '\n';
        });

        navigator.clipboard.writeText(text).then(function () {
            showExportSuccess('copy', exportData.rows.length);
        }).catch(function () {
            // Fallback for older browsers
            var textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showExportSuccess('copy', exportData.rows.length);
        });
    };

    // Export to Excel
    var exportToExcel = function (data) {
        var exportData = prepareExportData(data);
        var exportDateTime = getExportDateTime();

        var wb = XLSX.utils.book_new();

        // Create worksheet data with title and timestamp
        var wsData = [
            ['Alumni Students Report'],
            ['Exported on: ' + exportDateTime],
            [], // Empty row for spacing
            exportData.headers
        ].concat(exportData.rows);

        var ws = XLSX.utils.aoa_to_sheet(wsData);

        // Merge cells for title row
        ws['!merges'] = [
            { s: { r: 0, c: 0 }, e: { r: 0, c: exportData.headers.length - 1 } }, // Title row
            { s: { r: 1, c: 0 }, e: { r: 1, c: exportData.headers.length - 1 } }  // Timestamp row
        ];

        // Set column widths
        ws['!cols'] = [
            { wch: 5 },   // #
            { wch: 35 },  // Student
            { wch: 20 },  // Class
            { wch: 12 },  // Group
            { wch: 15 },  // Batch
            { wch: 45 },  // Institution
            { wch: 15 },  // Mobile
            { wch: 12 },  // Fee
            { wch: 15 }   // Payment Type
        ];

        XLSX.utils.book_append_sheet(wb, ws, 'Alumni Students');
        XLSX.writeFile(wb, getExportFilename('xlsx'));
        showExportSuccess('excel', exportData.rows.length);
    };

    // Export to CSV
    var exportToCSV = function (data) {
        var exportData = prepareExportData(data);
        var exportDateTime = getExportDateTime();

        var csvContent = '';

        // Add title and timestamp
        csvContent += '"Alumni Students Report"\n';
        csvContent += '"Exported on: ' + exportDateTime + '"\n';
        csvContent += '\n'; // Empty row for spacing

        // Add headers
        csvContent += exportData.headers.map(function (h) {
            return '"' + h.replace(/"/g, '""') + '"';
        }).join(',') + '\n';

        // Add rows
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

    // Export to PDF
    var exportToPDF = function (data) {
        var exportData = prepareExportData(data);
        var exportDateTime = getExportDateTime();

        var doc = new jspdf.jsPDF('l', 'mm', 'a4');

        // Add title
        doc.setFontSize(16);
        doc.text('Alumni Students Report', 14, 15);

        // Add timestamp
        doc.setFontSize(10);
        doc.text('Exported on: ' + exportDateTime, 14, 22);

        // Add table
        doc.autoTable({
            head: [exportData.headers],
            body: exportData.rows,
            startY: 28,
            styles: {
                fontSize: 8,
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
            columnStyles: {
                0: { cellWidth: 10 },  // #
                1: { cellWidth: 50 },  // Student
                2: { cellWidth: 30 },  // Class
                3: { cellWidth: 20 },  // Group
                4: { cellWidth: 25 },  // Batch
                5: { cellWidth: 55 },  // Institution
                6: { cellWidth: 25 },  // Mobile
                7: { cellWidth: 20 },  // Fee
                8: { cellWidth: 25 }   // Payment Type
            },
            didDrawPage: function (data) {
                // Footer with page number
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
        var exportItems = document.querySelectorAll('#kt_alumni_export_dropdown_menu [data-alumni-export]');

        exportItems.forEach(function (exportItem) {
            exportItem.addEventListener('click', function (e) {
                e.preventDefault();
                var exportType = this.getAttribute('data-alumni-export');

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

    // Search Datatable with debouncing
    var handleSearch = function () {
        var filterSearch = document.querySelector('[data-kt-alumni-table-filter="search"]');
        if (!filterSearch) return;

        filterSearch.addEventListener('keyup', function (e) {
            if (searchDebounceTimer) {
                clearTimeout(searchDebounceTimer);
            }

            var searchValue = e.target.value;

            // Debounce search to avoid too many requests
            searchDebounceTimer = setTimeout(function () {
                if (activeDatatable) {
                    activeDatatable.search(searchValue).draw();
                }
            }, 300);
        });
    };

    // Filter Datatable
    var handleFilter = function () {
        var filterForm = document.querySelector('[data-kt-alumni-table-filter="form"]');
        if (!filterForm) return;

        var filterButton = filterForm.querySelector('[data-kt-alumni-table-filter="filter"]');
        var resetButton = filterForm.querySelector('[data-kt-alumni-table-filter="reset"]');

        // Filter datatable on submit
        if (filterButton) {
            filterButton.addEventListener('click', function () {
                // Reload all initialized datatables with new filters
                if (isAdmin) {
                    Object.keys(datatables).forEach(function (key) {
                        if (datatables[key]) {
                            datatables[key].ajax.reload();
                        }
                    });
                    // Also refresh counts after filter
                    fetchBranchCounts();
                } else if (activeDatatable) {
                    activeDatatable.ajax.reload();
                }
            });
        }

        // Reset datatable
        if (resetButton) {
            resetButton.addEventListener('click', function () {
                // Clear all filter values
                var filterSelects = filterForm.querySelectorAll('select[data-filter-field]');
                filterSelects.forEach(function (select) {
                    $(select).val(null).trigger('change');
                });

                // Clear search input
                var searchInput = document.querySelector('[data-kt-alumni-table-filter="search"]');
                if (searchInput) {
                    searchInput.value = '';
                }

                // Reload all initialized datatables
                if (isAdmin) {
                    Object.keys(datatables).forEach(function (key) {
                        if (datatables[key]) {
                            datatables[key].search('').ajax.reload();
                        }
                    });
                    // Also refresh counts after reset
                    fetchBranchCounts();
                } else if (activeDatatable) {
                    activeDatatable.search('').ajax.reload();
                }
            });
        }
    };

    // Delete student handler
    var handleDeletion = function () {
        document.addEventListener('click', function (e) {
            var deleteBtn = e.target.closest('.delete-student');
            if (!deleteBtn) return;

            e.preventDefault();
            var studentId = deleteBtn.getAttribute('data-student-id');
            var url = routeDeleteStudent.replace(':id', studentId);

            Swal.fire({
                title: "Are you sure to delete this Alumni student?",
                text: "This action cannot be undone!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete!",
            }).then(function (result) {
                if (result.isConfirmed) {
                    fetch(url, {
                        method: "DELETE",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        },
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.success) {
                                Swal.fire({
                                    title: "Deleted!",
                                    text: "Alumni student deleted successfully.",
                                    icon: "success",
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(function () {
                                    // Reload the active datatable
                                    if (activeDatatable) {
                                        activeDatatable.ajax.reload();
                                    }
                                    // Reload branch counts
                                    if (isAdmin) {
                                        fetchBranchCounts();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: "Error!",
                                    text: data.message || "Could not delete the student.",
                                    icon: "error",
                                });
                            }
                        })
                        .catch(function (error) {
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
                            if (isAdmin) {
                                fetchBranchCounts();
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
        // Public functions
        init: function () {
            // Check if admin or non-admin based on the presence of tabs
            if (typeof isAdmin !== 'undefined' && isAdmin) {
                initAdminDatatables();
            } else {
                initNonAdminDatatable();
            }

            // Initialize modal
            initToggleActivationModal();

            // Setup handlers
            exportButtons();
            handleSearch();
            handleDeletion();
            handleFilter();
            handleToggleActivationTrigger();
            handleToggleActivationSubmit();
            handleModalReset();
        },

        // Public method to reload active datatable
        reload: function () {
            if (activeDatatable) {
                activeDatatable.ajax.reload();
            }
            if (isAdmin) {
                fetchBranchCounts();
            }
        },

        // Public method to reload all datatables
        reloadAll: function () {
            Object.keys(datatables).forEach(function (key) {
                if (datatables[key]) {
                    datatables[key].ajax.reload();
                }
            });
            if (isAdmin) {
                fetchBranchCounts();
            }
        },

        // Public method to refresh branch counts
        refreshCounts: function () {
            if (isAdmin) {
                fetchBranchCounts();
            }
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTAlumniStudentsList.init();
});