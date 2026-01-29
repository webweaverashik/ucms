"use strict";

var KTSMSList = function () {
    // Define shared variables
    var table;
    var datatable;
    var filterStatus = '';
    var searchValue = '';
    var searchTimeout;

    // Private functions
    var initDatatable = function () {
        // Init datatable with server-side processing
        datatable = $(table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: smsLogsDataUrl,
                type: 'GET',
                data: function (d) {
                    // Add custom filter parameters
                    d.status = filterStatus;
                },
                error: function (xhr, error, thrown) {
                    console.error('DataTable AJAX error:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to load SMS logs. Please refresh the page.',
                        icon: 'error'
                    });
                }
            },
            columns: [
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
                {
                    data: 'recipient',
                    name: 'recipient'
                },
                {
                    data: 'message_body',
                    name: 'message_body',
                    orderable: false,
                    render: function (data, type, row) {
                        // Show full message body with word wrap
                        return '<div class="text-wrap" style="max-width: 400px; word-break: break-word;">' + escapeHtml(data || '') + '</div>';
                    }
                },
                {
                    data: 'status_badge',
                    name: 'status',
                    orderable: true,
                    searchable: false
                },
                {
                    data: 'api_error',
                    name: 'api_error',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'sent_at',
                    name: 'updated_at',
                    orderable: true
                },
                {
                    data: 'sent_by',
                    name: 'created_by',
                    orderable: true
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ],
            order: [], // Order by sent_at desc
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            info: true,
            lengthChange: true,
            autoWidth: false,
            language: {
                processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                emptyTable: "No SMS logs available",
                zeroRecords: "No matching SMS logs found"
            },
            drawCallback: function () {
                // Reinitialize retry handlers after each draw
                handleRetry();
            }
        });
    };

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    // Search Datatable with debounce
    var handleSearch = function () {
        const filterSearch = document.querySelector('[data-sms-logs-table-filter="search"]');
        filterSearch.addEventListener('keyup', function (e) {
            clearTimeout(searchTimeout);
            searchValue = e.target.value;

            // Debounce search to avoid too many requests
            searchTimeout = setTimeout(function () {
                datatable.search(searchValue).draw();
            }, 500);
        });
    };

    // Filter Datatable
    var handleFilter = function () {
        const filterForm = document.querySelector('[data-sms-logs-table-filter="form"]');
        const filterButton = filterForm.querySelector('[data-sms-logs-table-filter="filter"]');
        const resetButton = filterForm.querySelector('[data-sms-logs-table-filter="reset"]');
        const statusSelect = filterForm.querySelector('[data-sms-logs-table-filter="status"]');

        // Filter datatable on submit
        filterButton.addEventListener('click', function () {
            filterStatus = statusSelect.value || '';
            datatable.ajax.reload();
        });

        // Reset datatable
        resetButton.addEventListener('click', function () {
            // Reset Select2 dropdown
            $(statusSelect).val(null).trigger('change');
            filterStatus = '';
            datatable.ajax.reload();
        });
    };

    // Export functionality using SheetJS and jsPDF
    var exportButtons = function () {
        const exportItems = document.querySelectorAll('#kt_table_report_dropdown_menu [data-row-export]');

        exportItems.forEach(function (exportItem) {
            exportItem.addEventListener('click', function (e) {
                e.preventDefault();
                const exportType = this.getAttribute('data-row-export');

                // Show loading
                Swal.fire({
                    title: 'Exporting...',
                    text: 'Please wait while we prepare your export.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Fetch all data for export
                var exportUrl = smsLogsExportUrl + '?status=' + encodeURIComponent(filterStatus) + '&search=' + encodeURIComponent(searchValue);

                fetch(exportUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => response.json())
                    .then(result => {
                        Swal.close();

                        if (!result.data || result.data.length === 0) {
                            Swal.fire({
                                title: 'No Data',
                                text: 'There is no data to export.',
                                icon: 'info'
                            });
                            return;
                        }

                        switch (exportType) {
                            case 'copy':
                                copyToClipboard(result.data);
                                break;
                            case 'excel':
                                exportToExcel(result.data);
                                break;
                            case 'csv':
                                exportToCSV(result.data);
                                break;
                            case 'pdf':
                                exportToPDF(result.data);
                                break;
                        }
                    })
                    .catch(error => {
                        Swal.close();
                        console.error('Export error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to export data. Please try again.',
                            icon: 'error'
                        });
                    });
            });
        });
    };

    // Copy to clipboard
    function copyToClipboard(data) {
        var headers = ['SL', 'Recipient', 'Message', 'Status', 'Failed Reason', 'Sent At', 'Sent By'];
        var text = headers.join('\t') + '\n';

        data.forEach(function (row) {
            text += [
                row.sl,
                row.recipient,
                row.message_body,
                row.status,
                row.api_error,
                row.sent_at,
                row.sent_by
            ].join('\t') + '\n';
        });

        navigator.clipboard.writeText(text).then(function () {
            Swal.fire({
                title: 'Copied!',
                text: 'Data has been copied to clipboard.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(function (err) {
            console.error('Copy failed:', err);
            Swal.fire({
                title: 'Error',
                text: 'Failed to copy to clipboard.',
                icon: 'error'
            });
        });
    }

    // Export to Excel using SheetJS
    function exportToExcel(data) {
        var headers = ['SL', 'Recipient', 'Message', 'Status', 'Failed Reason', 'Sent At', 'Sent By'];
        var wsData = [headers];

        data.forEach(function (row) {
            wsData.push([
                row.sl,
                row.recipient,
                row.message_body,
                row.status,
                row.api_error,
                row.sent_at,
                row.sent_by
            ]);
        });

        var ws = XLSX.utils.aoa_to_sheet(wsData);

        // Set column widths
        ws['!cols'] = [
            { wch: 5 },   // SL
            { wch: 15 },  // Recipient
            { wch: 50 },  // Message
            { wch: 10 },  // Status
            { wch: 30 },  // Failed Reason
            { wch: 20 },  // Sent At
            { wch: 15 }   // Sent By
        ];

        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'SMS Logs');

        XLSX.writeFile(wb, 'SMS_Logs_Report_' + getCurrentDateTime() + '.xlsx');

        Swal.fire({
            title: 'Exported!',
            text: 'Excel file has been downloaded.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // Export to CSV using SheetJS
    function exportToCSV(data) {
        var headers = ['SL', 'Recipient', 'Message', 'Status', 'Failed Reason', 'Sent At', 'Sent By'];
        var wsData = [headers];

        data.forEach(function (row) {
            wsData.push([
                row.sl,
                row.recipient,
                row.message_body,
                row.status,
                row.api_error,
                row.sent_at,
                row.sent_by
            ]);
        });

        var ws = XLSX.utils.aoa_to_sheet(wsData);
        var csv = XLSX.utils.sheet_to_csv(ws);

        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        var url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'SMS_Logs_Report_' + getCurrentDateTime() + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        Swal.fire({
            title: 'Exported!',
            text: 'CSV file has been downloaded.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // Export to PDF using jsPDF
    function exportToPDF(data) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4'); // Landscape orientation

        var headers = [['SL', 'Recipient', 'Message', 'Status', 'Failed Reason', 'Sent At', 'Sent By']];
        var rows = [];

        data.forEach(function (row) {
            rows.push([
                row.sl,
                row.recipient,
                row.message_body || '',
                row.status,
                row.api_error || '',
                row.sent_at,
                row.sent_by
            ]);
        });

        // Add title
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text('SMS Logs Report', 14, 15);

        // Add generation date
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text('Generated: ' + new Date().toLocaleString(), 14, 22);

        // Add table
        doc.autoTable({
            head: headers,
            body: rows,
            startY: 28,
            styles: {
                fontSize: 8,
                cellPadding: 2
            },
            headStyles: {
                fillColor: [41, 128, 185],
                textColor: 255,
                fontStyle: 'bold'
            },
            columnStyles: {
                0: { cellWidth: 10 },  // SL
                1: { cellWidth: 25 },  // Recipient
                2: { cellWidth: 80 },  // Message
                3: { cellWidth: 20 },  // Status
                4: { cellWidth: 40 },  // Failed Reason
                5: { cellWidth: 35 },  // Sent At
                6: { cellWidth: 25 }   // Sent By
            },
            alternateRowStyles: {
                fillColor: [245, 245, 245]
            },
            didDrawPage: function (data) {
                // Footer
                var pageCount = doc.internal.getNumberOfPages();
                doc.setFontSize(8);
                doc.text('Page ' + data.pageNumber + ' of ' + pageCount, doc.internal.pageSize.width - 25, doc.internal.pageSize.height - 10);
            }
        });

        doc.save('SMS_Logs_Report_' + getCurrentDateTime() + '.pdf');

        Swal.fire({
            title: 'Exported!',
            text: 'PDF file has been downloaded.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }

    // Get current date time for filename
    function getCurrentDateTime() {
        var now = new Date();
        return now.getFullYear() + '-' +
            String(now.getMonth() + 1).padStart(2, '0') + '-' +
            String(now.getDate()).padStart(2, '0') + '_' +
            String(now.getHours()).padStart(2, '0') + '-' +
            String(now.getMinutes()).padStart(2, '0');
    }

    // SMS Retry AJAX handler
    var handleRetry = function () {
        document.querySelectorAll('.retry-sms').forEach(function (item) {
            // Remove existing listeners to prevent duplicates
            item.replaceWith(item.cloneNode(true));
        });

        document.querySelectorAll('.retry-sms').forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                var smsLogId = this.getAttribute('data-sms-log-id');

                Swal.fire({
                    title: 'Retry SMS?',
                    text: "Do you want to retry sending this SMS?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, retry!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        Swal.fire({
                            title: 'Retrying...',
                            text: 'Please wait while we send the SMS.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch(smsLogsRetryUrl + '/' + smsLogId + '/retry', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: data.message || 'SMS retry successful!',
                                        icon: 'success'
                                    }).then(() => {
                                        // Reload datatable to show updated data
                                        datatable.ajax.reload(null, false);
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Failed!',
                                        text: data.message || 'SMS retry failed.',
                                        icon: 'warning'
                                    });
                                }
                            })
                            .catch(error => {
                                console.error('Retry error:', error);
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Something went wrong. Please try again.',
                                    icon: 'error'
                                });
                            });
                    }
                });
            });
        });
    };

    return {
        // Public functions
        init: function () {
            table = document.getElementById('kt_sms_logs_table');

            if (!table) {
                return;
            }

            initDatatable();
            exportButtons();
            handleSearch();
            handleFilter();
        }
    };
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTSMSList.init();
});