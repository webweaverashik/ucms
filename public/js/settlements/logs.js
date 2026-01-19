"use strict";

// Class definition
var KTWalletLogs = function () {
    // Shared variables
    var table;
    var datatable;
    var filterUser = '';
    var filterType = '';
    var filterDateFrom = '';
    var filterDateTo = '';
    var filterSearch = '';

    // Menu instances
    var filterMenu;
    var exportMenu;

    // Flatpickr instances
    var dateFromPicker;
    var dateToPicker;

    // Init DataTable
    var initDatatable = function () {
        datatable = $(table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: KT_LOGS_DATA_URL,
                type: 'GET',
                data: function (d) {
                    d.user_id = filterUser;
                    d.type = filterType;
                    d.date_from = filterDateFrom;
                    d.date_to = filterDateTo;
                    d.search = filterSearch;
                },
                error: function (xhr, error, thrown) {
                    console.error('DataTables AJAX error:', error, thrown);
                }
            },
            columns: [
                {
                    data: 'DT_RowIndex',
                    name: 'id',
                    orderable: false,
                    searchable: false
                },
                {
                    data: null,
                    name: 'created_at',
                    render: function (data, type, row) {
                        return '<span class="text-gray-800">' + row.created_at + '</span>' +
                            '<span class="text-gray-500 d-block fs-7">' + row.created_at_time + '</span>';
                    }
                },
                {
                    data: null,
                    name: 'user_id',
                    orderable: false,
                    render: function (data, type, row) {
                        var photoHtml = '';
                        if (row.user_photo) {
                            photoHtml = '<div class="symbol-label"><img src="' + row.user_photo + '" alt="' + row.user_name + '" class="w-100" /></div>';
                        } else {
                            photoHtml = '<div class="symbol-label fs-6 bg-light-primary text-primary">' + row.user_initial + '</div>';
                        }

                        return '<div class="d-flex align-items-center">' +
                            '<div class="symbol symbol-circle symbol-40px overflow-hidden me-3">' + photoHtml + '</div>' +
                            '<div class="d-flex flex-column">' +
                            '<a href="/settlements/' + row.user_id + '" class="text-gray-800 text-hover-primary mb-1">' + row.user_name + '</a>' +
                            '</div></div>';
                    }
                },
                {
                    data: null,
                    name: 'type',
                    orderable: false,
                    render: function (data, type, row) {
                        var badgeClass = 'badge-warning';
                        if (row.type === 'collection') badgeClass = 'badge-success';
                        else if (row.type === 'settlement') badgeClass = 'badge-info';
                        return '<span class="badge ' + badgeClass + '">' + row.type_label + '</span>';
                    }
                },
                {
                    data: null,
                    name: 'description',
                    orderable: false,
                    render: function (data, type, row) {
                        if (row.payment_invoice_id) {
                            return '<a href="/invoices/' + row.payment_invoice_id + '" class="text-gray-800 text-hover-primary text-wrap" target="_blank">' + row.description + '</a>';
                        }
                        return '<span class="text-gray-800">' + row.description + '</span>';
                    }
                },
                {
                    data: null,
                    name: 'amount',
                    className: 'text-end',
                    render: function (data, type, row) {
                        var colorClass = row.amount >= 0 ? 'text-success' : 'text-danger';
                        return '<span class="' + colorClass + ' fw-bold">' + row.amount_formatted + '</span>';
                    }
                },
                {
                    data: 'old_balance_formatted',
                    name: 'old_balance',
                    className: 'text-end',
                    render: function (data) {
                        return '<span class="text-gray-600">' + data + '</span>';
                    }
                },
                {
                    data: 'new_balance_formatted',
                    name: 'new_balance',
                    className: 'text-end',
                    render: function (data) {
                        return '<span class="text-gray-800 fw-bold">' + data + '</span>';
                    }
                },
                {
                    data: 'creator_name',
                    name: 'created_by',
                    orderable: false,
                    render: function (data) {
                        return '<span class="text-gray-700">' + data + '</span>';
                    }
                }
            ],
            order: [[1, 'desc']],
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            language: {
                processing: '<div class="d-flex justify-content-center"><span class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></span></div>',
                emptyTable: '<div class="d-flex flex-column align-items-center py-10"><i class="ki-outline ki-document fs-3x text-gray-400 mb-3"></i><span class="text-gray-500 fs-5">No transactions found</span></div>'
            }
        });
    }

    // Initialize Flatpickr
    var initFlatpickr = function () {
        var commonConfig = {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M, Y",
            allowInput: false
        };

        dateFromPicker = flatpickr("#filter_date_from", commonConfig);
        dateToPicker = flatpickr("#filter_date_to", commonConfig);
    }

    // Initialize custom dropdown menus
    var initMenus = function () {
        var filterBtn = document.getElementById('kt_filter_btn');
        var filterMenuEl = document.getElementById('kt_filter_menu');
        var exportBtn = document.getElementById('kt_export_btn');
        var exportMenuEl = document.getElementById('kt_export_menu');

        // Helper function to position dropdown below button
        var positionDropdown = function (button, menu) {
            var rect = button.getBoundingClientRect();
            var menuWidth = menu.offsetWidth || 300;
            
            // Calculate left position - align right edge of menu with right edge of button
            var left = rect.right - menuWidth;
            
            // Make sure menu doesn't go off-screen on the left
            if (left < 10) {
                left = 10;
            }
            
            menu.style.position = 'fixed';
            menu.style.top = (rect.bottom + 5) + 'px';
            menu.style.left = left + 'px';
            menu.style.right = 'auto';
            menu.style.zIndex = '1000';
        };

        if (filterBtn && filterMenuEl) {
            // Position and toggle filter menu
            filterBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                
                // Hide export menu if open
                exportMenuEl.classList.remove('show');
                
                // Toggle filter menu
                if (filterMenuEl.classList.contains('show')) {
                    filterMenuEl.classList.remove('show');
                } else {
                    filterMenuEl.classList.add('show');
                    positionDropdown(filterBtn, filterMenuEl);
                }
            });
        }

        if (exportBtn && exportMenuEl) {
            // Position and toggle export menu
            exportBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                
                // Hide filter menu if open
                filterMenuEl.classList.remove('show');
                
                // Toggle export menu
                if (exportMenuEl.classList.contains('show')) {
                    exportMenuEl.classList.remove('show');
                } else {
                    exportMenuEl.classList.add('show');
                    positionDropdown(exportBtn, exportMenuEl);
                }
            });
        }

        // Close menus when clicking outside
        document.addEventListener('click', function (e) {
            if (!filterMenuEl.contains(e.target) && e.target !== filterBtn) {
                filterMenuEl.classList.remove('show');
            }
            if (!exportMenuEl.contains(e.target) && e.target !== exportBtn) {
                exportMenuEl.classList.remove('show');
            }
        });

        // Prevent menu close when clicking inside filter menu
        filterMenuEl.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        // Reposition menus on window scroll or resize
        window.addEventListener('scroll', function () {
            if (filterMenuEl.classList.contains('show')) {
                positionDropdown(filterBtn, filterMenuEl);
            }
            if (exportMenuEl.classList.contains('show')) {
                positionDropdown(exportBtn, exportMenuEl);
            }
        });

        window.addEventListener('resize', function () {
            if (filterMenuEl.classList.contains('show')) {
                positionDropdown(filterBtn, filterMenuEl);
            }
            if (exportMenuEl.classList.contains('show')) {
                positionDropdown(exportBtn, exportMenuEl);
            }
        });
    }

    // Handle search with debounce
    var handleSearch = function () {
        var searchInput = document.getElementById('kt_filter_search');
        var debounceTimer;

        if (searchInput) {
            searchInput.addEventListener('keyup', function (e) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    filterSearch = e.target.value;
                    datatable.ajax.reload();
                }, 500);
            });
        }
    }

    // Handle filter apply button
    var handleFilterApply = function () {
        var applyBtn = document.getElementById('kt_filter_apply');
        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                // Get filter values
                filterUser = $('#filter_user').val() || '';
                filterType = $('#filter_type').val() || '';
                filterDateFrom = $('#filter_date_from').val() || '';
                filterDateTo = $('#filter_date_to').val() || '';

                // Hide menu
                document.getElementById('kt_filter_menu').classList.remove('show');

                datatable.ajax.reload();
            });
        }
    }

    // Handle filter reset button
    var handleFilterReset = function () {
        var resetBtn = document.getElementById('kt_filter_reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                // Reset filter values
                filterUser = '';
                filterType = '';
                filterDateFrom = '';
                filterDateTo = '';

                // Reset form fields
                $('#filter_user').val('').trigger('change');
                $('#filter_type').val('').trigger('change');
                
                // Clear flatpickr
                if (dateFromPicker) dateFromPicker.clear();
                if (dateToPicker) dateToPicker.clear();

                // Hide menu
                document.getElementById('kt_filter_menu').classList.remove('show');

                datatable.ajax.reload();
            });
        }
    }

    // Get current filters
    var getCurrentFilters = function () {
        return {
            user_id: filterUser,
            type: filterType,
            date_from: filterDateFrom,
            date_to: filterDateTo,
            search: filterSearch
        };
    }

    // Show loading alert
    var showLoadingAlert = function (message) {
        return Swal.fire({
            text: message || 'Please wait...',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    // Show success toastr
    var showSuccessToastr = function (message) {
        if (typeof toastr !== 'undefined') {
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: 'toastr-top-right',
                timeOut: 3000
            };
            toastr.success(message);
        } else {
            Swal.fire({
                text: message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        }
    }

    // Handle Copy export
    var handleCopyExport = function () {
        var btn = document.querySelector('[data-kt-export="copy"]');
        if (!btn) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            
            // Hide menu
            document.getElementById('kt_export_menu').classList.remove('show');

            showLoadingAlert('Copying data to clipboard...');

            $.ajax({
                url: KT_LOGS_EXPORT_URL,
                type: 'GET',
                data: getCurrentFilters(),
                success: function (response) {
                    Swal.close();

                    if (response.success && response.data) {
                        var text = response.data.map(function (row) {
                            return [row.sl, row.date, row.user, row.type, row.description, row.amount, row.old_balance, row.new_balance, row.created_by].join('\t');
                        }).join('\n');

                        var header = '#\tDate\tUser\tType\tDescription\tAmount\tOld Balance\tNew Balance\tCreated By\n';
                        navigator.clipboard.writeText(header + text).then(function () {
                            showSuccessToastr('Data copied to clipboard successfully!');
                        });
                    }
                },
                error: function () {
                    Swal.close();
                    Swal.fire({
                        text: 'Failed to copy data',
                        icon: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'Ok',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                }
            });
        });
    }

    // Handle Excel export
    var handleExcelExport = function () {
        var btn = document.querySelector('[data-kt-export="excel"]');
        if (!btn) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            
            // Hide menu
            document.getElementById('kt_export_menu').classList.remove('show');

            showLoadingAlert('Generating Excel file...');

            $.ajax({
                url: KT_LOGS_EXPORT_URL,
                type: 'GET',
                data: getCurrentFilters(),
                success: function (response) {
                    Swal.close();

                    if (response.success && response.data) {
                        var exportData = response.data.map(function (row) {
                            return {
                                '#': row.sl,
                                'Date': row.date,
                                'User': row.user,
                                'Type': row.type,
                                'Description': row.description,
                                'Amount': Math.round(row.amount),
                                'Old Balance': Math.round(row.old_balance),
                                'New Balance': Math.round(row.new_balance),
                                'Created By': row.created_by
                            };
                        });

                        var ws = XLSX.utils.json_to_sheet(exportData);
                        var wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, 'Wallet Logs');

                        ws['!cols'] = [
                            { wch: 5 }, { wch: 20 }, { wch: 20 }, { wch: 12 },
                            { wch: 40 }, { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 15 }
                        ];

                        XLSX.writeFile(wb, 'wallet_logs_' + new Date().toISOString().slice(0, 10) + '.xlsx');
                        showSuccessToastr('Excel file downloaded successfully!');
                    }
                },
                error: function () {
                    Swal.close();
                    Swal.fire({
                        text: 'Failed to export data',
                        icon: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'Ok',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                }
            });
        });
    }

    // Handle CSV export
    var handleCsvExport = function () {
        var btn = document.querySelector('[data-kt-export="csv"]');
        if (!btn) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            
            // Hide menu
            document.getElementById('kt_export_menu').classList.remove('show');

            showLoadingAlert('Generating CSV file...');

            $.ajax({
                url: KT_LOGS_EXPORT_URL,
                type: 'GET',
                data: getCurrentFilters(),
                success: function (response) {
                    Swal.close();

                    if (response.success && response.data) {
                        var exportData = response.data.map(function (row) {
                            return {
                                '#': row.sl,
                                'Date': row.date,
                                'User': row.user,
                                'Type': row.type,
                                'Description': row.description,
                                'Amount': Math.round(row.amount),
                                'Old Balance': Math.round(row.old_balance),
                                'New Balance': Math.round(row.new_balance),
                                'Created By': row.created_by
                            };
                        });

                        var ws = XLSX.utils.json_to_sheet(exportData);
                        var csv = XLSX.utils.sheet_to_csv(ws);

                        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                        var link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = 'wallet_logs_' + new Date().toISOString().slice(0, 10) + '.csv';
                        link.click();

                        showSuccessToastr('CSV file downloaded successfully!');
                    }
                },
                error: function () {
                    Swal.close();
                    Swal.fire({
                        text: 'Failed to export data',
                        icon: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'Ok',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                }
            });
        });
    }

    // Handle PDF export
    var handlePdfExport = function () {
        var btn = document.querySelector('[data-kt-export="pdf"]');
        if (!btn) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            
            // Hide menu
            document.getElementById('kt_export_menu').classList.remove('show');

            showLoadingAlert('Generating PDF file...');

            $.ajax({
                url: KT_LOGS_EXPORT_URL,
                type: 'GET',
                data: getCurrentFilters(),
                success: function (response) {
                    Swal.close();

                    if (response.success && response.data) {
                        var { jsPDF } = window.jspdf;
                        var doc = new jsPDF('l', 'mm', 'a4');

                        doc.setFontSize(16);
                        doc.text('All Wallet Transactions', 14, 15);

                        doc.setFontSize(10);
                        doc.text('Exported: ' + response.exported_at, 14, 22);

                        var tableData = response.data.map(function (row) {
                            return [
                                row.sl,
                                row.date,
                                row.user,
                                row.type,
                                row.description,
                                Math.round(row.amount).toLocaleString(),
                                Math.round(row.old_balance).toLocaleString(),
                                Math.round(row.new_balance).toLocaleString(),
                                row.created_by
                            ];
                        });

                        doc.autoTable({
                            head: [['#', 'Date', 'User', 'Type', 'Description', 'Amount', 'Old Bal', 'New Bal', 'Created By']],
                            body: tableData,
                            startY: 28,
                            styles: { fontSize: 8, cellPadding: 2, overflow: 'linebreak' },
                            headStyles: { fillColor: [59, 130, 246] },
                            columnStyles: {
                                0: { cellWidth: 10 },
                                1: { cellWidth: 32 },
                                2: { cellWidth: 28 },
                                3: { cellWidth: 18 },
                                4: { cellWidth: 'auto' },
                                5: { cellWidth: 22, halign: 'right' },
                                6: { cellWidth: 22, halign: 'right' },
                                7: { cellWidth: 22, halign: 'right' },
                                8: { cellWidth: 25 }
                            }
                        });

                        doc.save('wallet_logs_' + new Date().toISOString().slice(0, 10) + '.pdf');
                        showSuccessToastr('PDF file downloaded successfully!');
                    }
                },
                error: function () {
                    Swal.close();
                    Swal.fire({
                        text: 'Failed to export data',
                        icon: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'Ok',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                }
            });
        });
    }

    // Initialize Select2
    var initSelect2 = function () {
        $('#filter_user').select2({
            minimumResultsForSearch: 10,
            dropdownParent: $('#kt_filter_menu')
        });

        $('#filter_type').select2({
            minimumResultsForSearch: Infinity,
            dropdownParent: $('#kt_filter_menu')
        });
    }

    // Public methods
    return {
        init: function () {
            table = document.getElementById('kt_logs_table');

            if (table) {
                initDatatable();
                initFlatpickr();
                initMenus();
                initSelect2();
                handleSearch();
                handleFilterApply();
                handleFilterReset();
                handleCopyExport();
                handleExcelExport();
                handleCsvExport();
                handlePdfExport();
            }
        }
    }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTWalletLogs.init();
});