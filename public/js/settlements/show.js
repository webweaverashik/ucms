"use strict";

// Class definition
var KTWalletHistory = function () {
    // Shared variables
    var table;
    var datatable;
    var modal;
    var modalElement;
    var form;
    var submitButton;
    var currentBalance = 0;

    // Adjustment modal variables
    var adjustmentModal;
    var adjustmentModalElement;
    var adjustmentForm;
    var adjustmentSubmitButton;

    // Filter variables
    var filterType = '';
    var filterDateFrom = '';
    var filterDateTo = '';
    var filterSearch = '';

    // Flatpickr instances
    var dateFromPicker;
    var dateToPicker;

    // Init DataTable with AJAX
    var initDatatable = function () {
        datatable = $(table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: KT_SHOW_DATA_URL,
                type: 'GET',
                data: function (d) {
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
                if (exportMenuEl) exportMenuEl.classList.remove('show');
                
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
                if (filterMenuEl) filterMenuEl.classList.remove('show');
                
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
            if (filterMenuEl && !filterMenuEl.contains(e.target) && e.target !== filterBtn) {
                filterMenuEl.classList.remove('show');
            }
            if (exportMenuEl && !exportMenuEl.contains(e.target) && e.target !== exportBtn) {
                exportMenuEl.classList.remove('show');
            }
        });

        // Prevent menu close when clicking inside filter menu
        if (filterMenuEl) {
            filterMenuEl.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        }

        // Reposition menus on window scroll or resize
        window.addEventListener('scroll', function () {
            if (filterMenuEl && filterMenuEl.classList.contains('show')) {
                positionDropdown(filterBtn, filterMenuEl);
            }
            if (exportMenuEl && exportMenuEl.classList.contains('show')) {
                positionDropdown(exportBtn, exportMenuEl);
            }
        });

        window.addEventListener('resize', function () {
            if (filterMenuEl && filterMenuEl.classList.contains('show')) {
                positionDropdown(filterBtn, filterMenuEl);
            }
            if (exportMenuEl && exportMenuEl.classList.contains('show')) {
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
                filterType = '';
                filterDateFrom = '';
                filterDateTo = '';

                // Reset form fields
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

    // Get current balance from data attribute
    var initBalance = function () {
        var balanceElement = document.getElementById('current_balance_display');
        if (balanceElement) {
            currentBalance = parseFloat(balanceElement.dataset.walletBalance) || 0;
        }
    }

    // Handle settlement button click
    var handleSettleButton = function () {
        $(document).on('click', '.btn-settle', function (e) {
            e.preventDefault();

            var balance = parseFloat($(this).data('balance'));
            currentBalance = balance;

            $('#modal_current_balance').text('৳' + balance.toLocaleString('en-US', { minimumFractionDigits: 0 }));
            $('#settlement_amount').val('').attr('max', balance);
            $('#amount_error').text('');
            $('textarea[name="notes"]').val('');

            if (modal) {
                modal.show();
            }
        });
    }

    // Handle full amount button
    var handleFullAmount = function () {
        $('#btn_full_amount').on('click', function () {
            $('#settlement_amount').val(Math.floor(currentBalance));
        });
    }

    // Handle form submit
    var handleFormSubmit = function () {
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var amount = parseFloat($('#settlement_amount').val());

            // Validate amount
            if (isNaN(amount) || amount <= 0) {
                $('#amount_error').text('Please enter a valid amount');
                return;
            }

            if (amount > currentBalance) {
                $('#amount_error').text('Amount cannot exceed current balance');
                return;
            }

            $('#amount_error').text('');

            // Show loading
            submitButton.setAttribute('data-kt-indicator', 'on');
            submitButton.disabled = true;

            // Submit via AJAX
            $.ajax({
                url: form.action,
                method: 'POST',
                data: $(form).serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    submitButton.removeAttribute('data-kt-indicator');
                    submitButton.disabled = false;
                    modal.hide();

                    Swal.fire({
                        text: response.message || "Settlement recorded successfully!",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok!",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    }).then(function () {
                        location.reload();
                    });
                },
                error: function (xhr) {
                    submitButton.removeAttribute('data-kt-indicator');
                    submitButton.disabled = false;

                    var message = 'Something went wrong!';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.error) {
                        message = xhr.responseJSON.error;
                    }

                    Swal.fire({
                        text: message,
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                }
            });
        });
    }

    // Handle modal hidden event - reset form
    var handleModalReset = function () {
        if (!modalElement) return;

        modalElement.addEventListener('hidden.bs.modal', function () {
            form.reset();
            $('#amount_error').text('');
        });
    }

    // Handle adjustment button click
    var handleAdjustmentButton = function () {
        $(document).on('click', '.btn-adjustment', function (e) {
            e.preventDefault();

            var balance = parseFloat($(this).data('balance'));
            currentBalance = balance;

            $('#adj_modal_current_balance').text('৳' + balance.toLocaleString('en-US', { minimumFractionDigits: 0 }));
            $('#adjustment_amount').val('');
            $('#adjustment_reason').val('');
            $('#adj_amount_error').text('');
            $('input[name="adjustment_type"][value="increase"]').prop('checked', true);

            if (adjustmentModal) {
                adjustmentModal.show();
            }
        });
    }

    // Handle adjustment form submit
    var handleAdjustmentFormSubmit = function () {
        if (!adjustmentForm) return;

        adjustmentForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var amount = parseFloat($('#adjustment_amount').val());
            var adjustmentType = $('input[name="adjustment_type"]:checked').val();
            var reason = $('#adjustment_reason').val().trim();

            // Validate amount
            if (isNaN(amount) || amount <= 0) {
                $('#adj_amount_error').text('Please enter a valid amount');
                return;
            }

            // Validate reason
            if (!reason) {
                $('#adj_amount_error').text('Please enter a reason for the adjustment');
                return;
            }

            // Check if decrease would result in negative balance
            if (adjustmentType === 'decrease' && amount > currentBalance) {
                $('#adj_amount_error').text('Cannot decrease more than current balance (৳' + currentBalance.toLocaleString('en-US', { minimumFractionDigits: 0 }) + ')');
                return;
            }

            $('#adj_amount_error').text('');

            // Prepare the actual amount (negative for decrease)
            var finalAmount = adjustmentType === 'decrease' ? -amount : amount;

            // Show loading
            adjustmentSubmitButton.setAttribute('data-kt-indicator', 'on');
            adjustmentSubmitButton.disabled = true;

            // Submit via AJAX
            $.ajax({
                url: adjustmentForm.action,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    user_id: $('#adjustment_user_id').val(),
                    amount: finalAmount,
                    reason: reason
                },
                success: function (response) {
                    adjustmentSubmitButton.removeAttribute('data-kt-indicator');
                    adjustmentSubmitButton.disabled = false;
                    adjustmentModal.hide();

                    Swal.fire({
                        text: response.message || "Adjustment recorded successfully!",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok!",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    }).then(function () {
                        location.reload();
                    });
                },
                error: function (xhr) {
                    adjustmentSubmitButton.removeAttribute('data-kt-indicator');
                    adjustmentSubmitButton.disabled = false;

                    var message = 'Something went wrong!';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        text: message,
                        icon: "error",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                }
            });
        });
    }

    // Handle adjustment modal hidden event - reset form
    var handleAdjustmentModalReset = function () {
        if (!adjustmentModalElement) return;

        adjustmentModalElement.addEventListener('hidden.bs.modal', function () {
            adjustmentForm.reset();
            $('#adj_amount_error').text('');
        });
    }

    // Handle Copy export
    var handleCopyExport = function () {
        var btn = document.querySelector('[data-kt-export="copy"]');
        if (!btn) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            
            // Hide menu
            var exportMenu = document.getElementById('kt_export_menu');
            if (exportMenu) exportMenu.classList.remove('show');

            showLoadingAlert('Copying data to clipboard...');

            $.ajax({
                url: KT_SHOW_EXPORT_URL,
                type: 'GET',
                data: getCurrentFilters(),
                success: function (response) {
                    Swal.close();

                    if (response.success && response.data) {
                        var text = response.data.map(function (row) {
                            return [row.sl, row.date, row.type, row.description, row.amount, row.old_balance, row.new_balance, row.created_by].join('\t');
                        }).join('\n');

                        var header = '#\tDate\tType\tDescription\tAmount\tOld Balance\tNew Balance\tCreated By\n';
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
            var exportMenu = document.getElementById('kt_export_menu');
            if (exportMenu) exportMenu.classList.remove('show');

            showLoadingAlert('Generating Excel file...');

            $.ajax({
                url: KT_SHOW_EXPORT_URL,
                type: 'GET',
                data: getCurrentFilters(),
                success: function (response) {
                    Swal.close();

                    if (response.success && response.data) {
                        var exportData = response.data.map(function (row) {
                            return {
                                '#': row.sl,
                                'Date': row.date,
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
                        XLSX.utils.book_append_sheet(wb, ws, 'Wallet History');

                        ws['!cols'] = [
                            { wch: 5 }, { wch: 20 }, { wch: 12 }, { wch: 40 },
                            { wch: 12 }, { wch: 12 }, { wch: 12 }, { wch: 15 }
                        ];

                        var fileName = response.user_name.replace(/\s+/g, '_') + '_wallet_history_' + new Date().toISOString().slice(0, 10) + '.xlsx';
                        XLSX.writeFile(wb, fileName);

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
            var exportMenu = document.getElementById('kt_export_menu');
            if (exportMenu) exportMenu.classList.remove('show');

            showLoadingAlert('Generating CSV file...');

            $.ajax({
                url: KT_SHOW_EXPORT_URL,
                type: 'GET',
                data: getCurrentFilters(),
                success: function (response) {
                    Swal.close();

                    if (response.success && response.data) {
                        var exportData = response.data.map(function (row) {
                            return {
                                '#': row.sl,
                                'Date': row.date,
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
                        var fileName = response.user_name.replace(/\s+/g, '_') + '_wallet_history_' + new Date().toISOString().slice(0, 10) + '.csv';
                        link.download = fileName;
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
            var exportMenu = document.getElementById('kt_export_menu');
            if (exportMenu) exportMenu.classList.remove('show');

            showLoadingAlert('Generating PDF file...');

            $.ajax({
                url: KT_SHOW_EXPORT_URL,
                type: 'GET',
                data: getCurrentFilters(),
                success: function (response) {
                    Swal.close();

                    if (response.success && response.data) {
                        var { jsPDF } = window.jspdf;
                        var doc = new jsPDF('l', 'mm', 'a4');

                        doc.setFontSize(16);
                        doc.text(response.user_name + ' - Wallet History', 14, 15);

                        doc.setFontSize(10);
                        doc.text('Exported: ' + response.exported_at, 14, 22);

                        var tableData = response.data.map(function (row) {
                            return [
                                row.sl,
                                row.date,
                                row.type,
                                row.description,
                                Math.round(row.amount).toLocaleString(),
                                Math.round(row.old_balance).toLocaleString(),
                                Math.round(row.new_balance).toLocaleString(),
                                row.created_by
                            ];
                        });

                        doc.autoTable({
                            head: [['#', 'Date', 'Type', 'Description', 'Amount', 'Old Bal', 'New Bal', 'Created By']],
                            body: tableData,
                            startY: 28,
                            styles: { fontSize: 8, cellPadding: 2, overflow: 'linebreak' },
                            headStyles: { fillColor: [59, 130, 246] },
                            columnStyles: {
                                0: { cellWidth: 10 },
                                1: { cellWidth: 32 },
                                2: { cellWidth: 18 },
                                3: { cellWidth: 'auto' },
                                4: { cellWidth: 22, halign: 'right' },
                                5: { cellWidth: 22, halign: 'right' },
                                6: { cellWidth: 22, halign: 'right' },
                                7: { cellWidth: 28 }
                            }
                        });

                        var fileName = response.user_name.replace(/\s+/g, '_') + '_wallet_history_' + new Date().toISOString().slice(0, 10) + '.pdf';
                        doc.save(fileName);

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
        var filterMenu = document.getElementById('kt_filter_menu');
        if (filterMenu) {
            $('#filter_type').select2({
                minimumResultsForSearch: Infinity,
                dropdownParent: $(filterMenu)
            });
        }
    }

    // Public methods
    return {
        init: function () {
            table = document.getElementById('kt_wallet_logs_table');
            modalElement = document.getElementById('kt_modal_settlement');
            form = document.getElementById('kt_modal_settlement_form');
            submitButton = document.getElementById('btn_submit_settlement');

            // Adjustment modal elements
            adjustmentModalElement = document.getElementById('kt_modal_adjustment');
            adjustmentForm = document.getElementById('kt_modal_adjustment_form');
            adjustmentSubmitButton = document.getElementById('btn_submit_adjustment');

            if (modalElement) {
                modal = new bootstrap.Modal(modalElement);
            }

            if (adjustmentModalElement) {
                adjustmentModal = new bootstrap.Modal(adjustmentModalElement);
            }

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

            initBalance();
            handleSettleButton();
            handleFullAmount();

            if (form) {
                handleFormSubmit();
                handleModalReset();
            }

            // Initialize adjustment handlers
            handleAdjustmentButton();
            if (adjustmentForm) {
                handleAdjustmentFormSubmit();
                handleAdjustmentModalReset();
            }
        }
    }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
    KTWalletHistory.init();
});