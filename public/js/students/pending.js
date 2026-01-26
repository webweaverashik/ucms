"use strict";

/**
 * Pending Students List - AJAX DataTable Handler
 * Handles server-side processing for pending students list
 */
var KTPendingStudentsList = (function () {
    // Define shared variables
    var datatables = {};
    var activeDatatable = null;
    var initializedTabs = {};
    var searchDebounceTimer = null;
    var currentBranchId = null;

    // Get current filters from the filter form
    var getFilters = function () {
        var filters = {};
        var filterForm = document.querySelector('[data-kt-pending-table-filter="form"]');
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
        var searchInput = document.querySelector('[data-kt-pending-table-filter="search"]');
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
        if (!isAdmin || typeof routePendingBranchCounts === 'undefined') {
            return;
        }

        $.ajax({
            url: routePendingBranchCounts,
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

                    // Set 0 for branches with no pending students
                    branchIds.forEach(function (branchId) {
                        if (!response.counts[branchId]) {
                            var badge = document.querySelector('.branch-count-badge[data-branch-id="' + branchId + '"]');
                            if (badge) {
                                badge.innerHTML = '0';
                                badge.classList.remove('badge-loading');
                            }
                        }
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error('Failed to fetch branch counts:', error);
                // On error, show dash for all badges
                document.querySelectorAll('.branch-count-badge.badge-loading').forEach(function (badge) {
                    badge.classList.remove('badge-loading');
                    badge.innerHTML = '-';
                });
            }
        });
    };

    // Build columns array
    var buildColumns = function () {
        var columns = [];

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
                var showUrl = routeStudentShow.replace(':id', data.id);

                // Eligibility indicator - green checkmark for students ready to approve
                var eligibilityIndicator = '';
                if (data.is_eligible_for_approval) {
                    eligibilityIndicator = '<span class="badge badge-circle badge-success ms-2" ' +
                        'data-bs-toggle="tooltip" data-bs-placement="top" ' +
                        'title="Ready to approve - No pending tuition fee">' +
                        '<i class="bi bi-check-lg text-white fs-7"></i></span>';
                }

                return '<div class="d-flex align-items-center">' +
                    '<div class="symbol symbol-circle symbol-50px overflow-hidden me-3">' +
                    '<a href="' + showUrl + '">' +
                    '<div class="symbol-label">' +
                    '<img src="' + data.photo_url + '" alt="' + escapeHtml(data.name) + '" class="w-100" />' +
                    '</div></a></div>' +
                    '<div class="d-flex flex-column text-start">' +
                    '<div class="d-flex align-items-center">' +
                    '<a href="' + showUrl + '" class="text-gray-800 text-hover-primary">' + escapeHtml(data.name) + '</a>' +
                    eligibilityIndicator +
                    '</div>' +
                    '<span class="fw-bold fs-base">' + escapeHtml(data.student_unique_id) + '</span>' +
                    '</div></div>';
            }
        });

        // Class name column
        columns.push({
            data: 'class_name',
            orderable: true,
            searchable: false
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
            orderable: false,
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

        // Admission date column
        columns.push({
            data: 'admission_date',
            orderable: true,
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
                url: routePendingData,
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
                    // Update badge count if admin
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
            order: [[9, 'desc']], // Order by admission date desc
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            lengthChange: true,
            autoWidth: false,
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                emptyTable: 'No pending students found',
                zeroRecords: 'No matching students found'
            },
            drawCallback: function () {
                // Re-initialize KTMenu for action dropdowns
                KTMenu.init();

                // Initialize Bootstrap tooltips for eligibility indicators
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
            var firstTableId = 'kt_pending_students_table_branch_' + firstBranchId;

            datatables[firstBranchId] = initSingleDatatable(firstTableId, firstBranchId);
            activeDatatable = datatables[firstBranchId];
            initializedTabs[firstBranchId] = true;
            currentBranchId = firstBranchId;
        }

        // Handle tab switching
        var tabLinks = document.querySelectorAll('#pendingBranchTabs a[data-bs-toggle="tab"]');
        tabLinks.forEach(function (tabLink) {
            tabLink.addEventListener('shown.bs.tab', function (event) {
                var branchId = event.target.getAttribute('data-branch-id');
                var tableId = 'kt_pending_students_table_branch_' + branchId;
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

    // Initialize datatable for non-admin (single table)
    var initNonAdminDatatable = function () {
        var table = document.getElementById('kt_pending_students_table');
        if (!table) return;

        datatables['single'] = initSingleDatatable('kt_pending_students_table', null);
        activeDatatable = datatables['single'];
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
            url: routePendingData,
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
        return 'Pending_Students_Report_' + timestamp + '.' + extension;
    };

    // Show export success notification
    var showExportSuccess = function (type, rowCount) {
        var messages = {
            'copy': 'Data copied to clipboard successfully!',
            'excel': 'Excel file exported successfully!',
            'csv': 'CSV file exported successfully!',
            'pdf': 'PDF file exported successfully!'
        };

        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toastr-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "3000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        toastr.success(messages[type] + ' (' + rowCount + ' rows)', 'Export Complete');
    };

    // Prepare export data
    var prepareExportData = function (data) {
        var headers = ['#', 'Student', 'Class', 'Group', 'Batch', 'Institution', 'Mobile (Home)', 'Tuition Fee', 'Payment Type', 'Admission Date'];
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
                row.payment_info || '-',
                row.admission_date || '-'
            ]);
        });

        return { headers: headers, rows: rows };
    };

    // Copy to clipboard
    var copyToClipboard = function (data) {
        var exportData = prepareExportData(data);
        var exportDateTime = getExportDateTime();

        // Add title and timestamp
        var text = 'Pending Students Report\n';
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
            ['Pending Students Report'],
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
            { wch: 40 },  // Institution
            { wch: 15 },  // Mobile
            { wch: 12 },  // Fee
            { wch: 15 },  // Payment Type
            { wch: 15 }   // Admission Date
        ];

        XLSX.utils.book_append_sheet(wb, ws, 'Pending Students');
        XLSX.writeFile(wb, getExportFilename('xlsx'));

        showExportSuccess('excel', exportData.rows.length);
    };

    // Export to CSV
    var exportToCSV = function (data) {
        var exportData = prepareExportData(data);
        var exportDateTime = getExportDateTime();
        var csvContent = '';

        // Add title and timestamp
        csvContent += '"Pending Students Report"\n';
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

        // Create and download file
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
        doc.text('Pending Students Report', 14, 15);

        // Add timestamp
        doc.setFontSize(10);
        doc.text('Exported on: ' + exportDateTime, 14, 22);

        // Create table
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
                0: { cellWidth: 8 },   // #
                1: { cellWidth: 45 },  // Student
                2: { cellWidth: 25 },  // Class
                3: { cellWidth: 18 },  // Group
                4: { cellWidth: 20 },  // Batch
                5: { cellWidth: 50 },  // Institution
                6: { cellWidth: 22 },  // Mobile
                7: { cellWidth: 18 },  // Fee
                8: { cellWidth: 22 },  // Payment Type
                9: { cellWidth: 22 }   // Admission Date
            },
            didDrawPage: function (data) {
                // Add page number
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

    // Handle search functionality with debouncing
    var handleSearch = function () {
        var searchInput = document.querySelector('[data-kt-pending-table-filter="search"]');
        if (!searchInput) return;

        searchInput.addEventListener('keyup', function (e) {
            if (searchDebounceTimer) clearTimeout(searchDebounceTimer);

            searchDebounceTimer = setTimeout(function () {
                if (activeDatatable) {
                    activeDatatable.search(e.target.value).draw();
                }
            }, 300);
        });
    };

    // Handle filter functionality
    var handleFilter = function () {
        var filterForm = document.querySelector('[data-kt-pending-table-filter="form"]');
        if (!filterForm) return;

        var filterButton = filterForm.querySelector('[data-kt-pending-table-filter="filter"]');
        var resetButton = filterForm.querySelector('[data-kt-pending-table-filter="reset"]');

        if (filterButton) {
            filterButton.addEventListener('click', function () {
                if (isAdmin) {
                    Object.keys(datatables).forEach(function (key) {
                        if (datatables[key]) datatables[key].ajax.reload();
                    });
                    // Refresh counts after filter
                    fetchBranchCounts();
                } else if (activeDatatable) {
                    activeDatatable.ajax.reload();
                }
            });
        }

        if (resetButton) {
            resetButton.addEventListener('click', function () {
                // Clear all filter values
                var filterSelects = filterForm.querySelectorAll('select[data-filter-field]');
                filterSelects.forEach(function (select) {
                    $(select).val(null).trigger('change');
                });

                // Clear search
                var searchInput = document.querySelector('[data-kt-pending-table-filter="search"]');
                if (searchInput) searchInput.value = '';

                if (isAdmin) {
                    Object.keys(datatables).forEach(function (key) {
                        if (datatables[key]) datatables[key].search('').ajax.reload();
                    });
                    // Refresh counts after reset
                    fetchBranchCounts();
                } else if (activeDatatable) {
                    activeDatatable.search('').ajax.reload();
                }
            });
        }
    };

    // Handle student deletion
    var handleDeletion = function () {
        document.addEventListener('click', function (e) {
            var deleteBtn = e.target.closest('.delete-student');
            if (!deleteBtn) return;

            e.preventDefault();

            var studentId = deleteBtn.getAttribute('data-student-id');
            var url = routeDeleteStudent.replace(':id', studentId);

            Swal.fire({
                title: 'Are you sure to delete this student?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete!'
            }).then(function (result) {
                if (result.isConfirmed) {
                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'The student has been removed.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(function () {
                                    // Reload the datatable
                                    if (activeDatatable) {
                                        activeDatatable.ajax.reload(null, false);
                                    }
                                    // Refresh counts
                                    if (isAdmin) fetchBranchCounts();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message || 'Failed to delete student.',
                                    icon: 'error'
                                });
                            }
                        })
                        .catch(function (error) {
                            console.error('Delete Error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'Something went wrong. Please try again.',
                                icon: 'error'
                            });
                        });
                }
            });
        });
    };

    // Send approval request
    var sendApprovalRequest = function (studentId, confirmDue) {
        var url = routeApproveStudent.replace(':id', studentId);

        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                active_status: 'active',
                confirm_due: confirmDue || false
            })
        }).then(function (response) { return response.json(); });
    };

    // Handle student approval
    var handleApproval = function () {
        document.addEventListener('click', function (e) {
            var approveBtn = e.target.closest('.approve-student');
            if (!approveBtn) return;

            e.preventDefault();

            var studentId = approveBtn.getAttribute('data-student-id');
            var studentName = approveBtn.getAttribute('data-student-name');

            Swal.fire({
                title: 'Approve Student?',
                text: 'Do you want to approve "' + studentName + '"?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#50cd89',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, approve!',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we approve the student.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: function () {
                            Swal.showLoading();
                        }
                    });

                    sendApprovalRequest(studentId, false)
                        .then(function (data) {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Approved!',
                                    text: 'The student has been approved successfully.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(function () {
                                    // Reload the datatable
                                    if (activeDatatable) {
                                        activeDatatable.ajax.reload(null, false);
                                    }
                                    // Refresh counts
                                    if (isAdmin) fetchBranchCounts();
                                });
                            } else if (data.requires_confirmation && isAdmin) {
                                // Admin: Show confirmation for due tuition fee
                                Swal.fire({
                                    title: 'Tuition Fee Due',
                                    text: 'Would you like to approve this student? This student tuition fee is still due.',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#50cd89',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: 'Yes, approve anyway!',
                                    cancelButtonText: 'Cancel'
                                }).then(function (confirmResult) {
                                    if (confirmResult.isConfirmed) {
                                        // Show loading again
                                        Swal.fire({
                                            title: 'Processing...',
                                            text: 'Please wait while we approve the student.',
                                            allowOutsideClick: false,
                                            allowEscapeKey: false,
                                            didOpen: function () {
                                                Swal.showLoading();
                                            }
                                        });

                                        // Send request with confirmation
                                        sendApprovalRequest(studentId, true)
                                            .then(function (data) {
                                                if (data.success) {
                                                    Swal.fire({
                                                        title: 'Approved!',
                                                        text: 'The student has been approved successfully.',
                                                        icon: 'success',
                                                        timer: 2000,
                                                        showConfirmButton: false
                                                    }).then(function () {
                                                        // Reload the datatable
                                                        if (activeDatatable) {
                                                            activeDatatable.ajax.reload(null, false);
                                                        }
                                                        // Refresh counts
                                                        if (isAdmin) fetchBranchCounts();
                                                    });
                                                } else {
                                                    Swal.fire({
                                                        title: 'Error!',
                                                        text: data.message || 'Failed to approve student.',
                                                        icon: 'error'
                                                    });
                                                }
                                            })
                                            .catch(function (error) {
                                                console.error('Approval Error:', error);
                                                Swal.fire({
                                                    title: 'Error!',
                                                    text: 'Something went wrong. Please try again.',
                                                    icon: 'error'
                                                });
                                            });
                                    }
                                });
                            } else {
                                // Manager or other error
                                Swal.fire({
                                    title: 'Cannot Approve',
                                    text: data.message || 'Failed to approve student.',
                                    icon: 'warning'
                                });
                            }
                        })
                        .catch(function (error) {
                            console.error('Approval Error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'Something went wrong. Please try again.',
                                icon: 'error'
                            });
                        });
                }
            });
        });
    };

    // Public functions
    return {
        init: function () {
            // Initialize datatables
            if (typeof isAdmin !== 'undefined' && isAdmin) {
                initAdminDatatables();
            } else {
                initNonAdminDatatable();
            }

            // Setup handlers
            exportButtons();
            handleSearch();
            handleFilter();
            handleDeletion();
            handleApproval();
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
        }
    };
})();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTPendingStudentsList.init();
});