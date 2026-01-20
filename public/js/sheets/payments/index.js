"use strict";

var AllPaymentsList = function () {
      // Define shared variables
      var table;
      var datatable;
      var filterSheetGroup = '';
      var filterPaymentStatus = '';
      var filterBranchId = '';
      var searchValue = '';

      // Private functions
      var initDatatable = function () {
            // Define columns
            var columns = [
                  { data: 'sl', name: 'sl', orderable: false, searchable: false },
                  { data: 'sheet_group', name: 'sheet_group' },
                  { data: 'invoice_no', name: 'invoice_no' },
                  { data: 'amount', name: 'amount' },
                  { data: 'status_filter', name: 'status_filter', visible: false },
                  { data: 'status', name: 'status', orderable: false },
                  { data: 'paid', name: 'paid' },
                  { data: 'student', name: 'student' },
                  { data: 'payment_date', name: 'payment_date' }
            ];

            // Order by payment date (column index 8)
            var orderColumnIndex = 8;

            // Init datatable with server-side processing
            datatable = $(table).DataTable({
                  processing: true,
                  serverSide: true,
                  ajax: {
                        url: sheetPaymentsConfig.dataUrl,
                        type: 'GET',
                        data: function (d) {
                              d.sheet_group = filterSheetGroup;
                              d.payment_status = filterPaymentStatus;
                              d.branch_id = filterBranchId;
                        },
                        beforeSend: function () {
                              // Show loading indicator with smooth transition
                              $(table).addClass('table-loading');
                              $(table).closest('.card-body').addClass('loading');
                        },
                        complete: function () {
                              // Hide loading indicator with smooth transition
                              $(table).removeClass('table-loading');
                              $(table).closest('.card-body').removeClass('loading');
                        },
                        error: function (xhr, error, thrown) {
                              console.error('DataTables AJAX error:', error, xhr.responseText);
                              $(table).removeClass('table-loading');
                              $(table).closest('.card-body').removeClass('loading');
                              Swal.fire({
                                    text: "Failed to load data. Please refresh the page.",
                                    icon: "error",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: {
                                          confirmButton: "btn btn-primary"
                                    }
                              });
                        }
                  },
                  columns: columns,
                  order: [[orderColumnIndex, 'desc']], // Order by payment date descending
                  info: true,
                  lengthMenu: [10, 25, 50, 100],
                  pageLength: 10,
                  lengthChange: true,
                  autoWidth: false,
                  language: {
                        processing: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>',
                        emptyTable: "No payments found",
                        zeroRecords: "No matching payments found"
                  },
                  drawCallback: function () {
                        // Reinitialize KTMenu for any dynamic content
                        if (typeof KTMenu !== 'undefined') {
                              KTMenu.createInstances();
                        }
                  }
            });

            // Re-init functions on every table re-draw
            datatable.on('draw', function () {
                  // Any additional functionality on draw
            });
      }

      // Handle branch tab switching
      var handleBranchTabs = function () {
            var branchTabs = document.querySelectorAll('#branchTabs .nav-link');

            if (branchTabs.length === 0) {
                  return;
            }

            // Set initial branch filter from active tab
            var activeTab = document.querySelector('#branchTabs .nav-link.active');
            if (activeTab) {
                  filterBranchId = activeTab.getAttribute('data-branch-id');
            }

            branchTabs.forEach(function (tab) {
                  tab.addEventListener('shown.bs.tab', function (e) {
                        var branchId = this.getAttribute('data-branch-id');

                        // Don't reload if same branch is clicked
                        if (filterBranchId === branchId) {
                              return;
                        }

                        // Update filter and reload
                        filterBranchId = branchId;

                        // Reload datatable with new branch filter (reset to first page)
                        datatable.ajax.reload(null, true);
                  });
            });
      }

      // Fetch all data for export
      var fetchExportData = function (callback) {
            Swal.fire({
                  title: 'Preparing Export...',
                  html: 'Fetching all data, please wait.',
                  allowOutsideClick: false,
                  allowEscapeKey: false,
                  didOpen: () => {
                        Swal.showLoading();
                  }
            });

            $.ajax({
                  url: sheetPaymentsConfig.exportUrl,
                  type: 'GET',
                  data: {
                        search: searchValue,
                        sheet_group: filterSheetGroup,
                        payment_status: filterPaymentStatus,
                        branch_id: filterBranchId
                  },
                  success: function (response) {
                        Swal.close();
                        if (response.success && response.data) {
                              callback(response.data);
                        } else {
                              Swal.fire({
                                    text: "Failed to fetch export data.",
                                    icon: "error",
                                    buttonsStyling: false,
                                    confirmButtonText: "Ok, got it!",
                                    customClass: {
                                          confirmButton: "btn btn-primary"
                                    }
                              });
                        }
                  },
                  error: function (xhr, error, thrown) {
                        Swal.close();
                        console.error('Export AJAX error:', error);
                        Swal.fire({
                              text: "Failed to fetch export data. Please try again.",
                              icon: "error",
                              buttonsStyling: false,
                              confirmButtonText: "Ok, got it!",
                              customClass: {
                                    confirmButton: "btn btn-primary"
                              }
                        });
                  }
            });
      }

      // Get current timestamp for footer
      var getCurrentTimestamp = function () {
            var now = new Date();
            return now.toLocaleString('en-US', {
                  year: 'numeric',
                  month: 'short',
                  day: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit',
                  second: '2-digit'
            });
      }

      // Get export headers
      var getExportHeaders = function () {
            return ['SL', 'Sheet Group', 'Invoice No.', 'Amount (Tk)', 'Status', 'Paid (Tk)', 'Student', 'Payment Date'];
      }

      // Get export row data
      var getExportRowData = function (row) {
            return [
                  row.sl,
                  row.sheet_group,
                  row.invoice_no,
                  row.amount,
                  row.status,
                  row.paid,
                  row.student,
                  row.payment_date
            ];
      }

      // Export to clipboard
      var exportToClipboard = function (data) {
            var headers = getExportHeaders();
            var rows = data.map(function (row) {
                  return getExportRowData(row).join('\t');
            });

            var content = headers.join('\t') + '\n' + rows.join('\n');

            navigator.clipboard.writeText(content).then(function () {
                  Swal.fire({
                        text: "Data copied to clipboard successfully!",
                        icon: "success",
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                  });
            }).catch(function (err) {
                  console.error('Failed to copy:', err);
                  // Fallback for older browsers
                  var textArea = document.createElement('textarea');
                  textArea.value = content;
                  textArea.style.position = 'fixed';
                  textArea.style.left = '-999999px';
                  textArea.style.top = '-999999px';
                  document.body.appendChild(textArea);
                  textArea.focus();
                  textArea.select();

                  try {
                        document.execCommand('copy');
                        Swal.fire({
                              text: "Data copied to clipboard successfully!",
                              icon: "success",
                              showConfirmButton: false,
                              timer: 1500,
                              timerProgressBar: true
                        });
                  } catch (e) {
                        Swal.fire({
                              text: "Failed to copy to clipboard.",
                              icon: "error",
                              buttonsStyling: false,
                              confirmButtonText: "Ok, got it!",
                              customClass: {
                                    confirmButton: "btn btn-primary"
                              }
                        });
                  }

                  document.body.removeChild(textArea);
            });
      }

      // Export to Excel using SheetJS
      var exportToExcel = function (data) {
            var headers = getExportHeaders();
            var rows = data.map(function (row) {
                  return getExportRowData(row);
            });

            var wsData = [headers].concat(rows);
            var ws = XLSX.utils.aoa_to_sheet(wsData);

            // Set column widths
            ws['!cols'] = [
                  { wch: 5 },   // SL
                  { wch: 25 },  // Sheet Group
                  { wch: 20 },  // Invoice No.
                  { wch: 12 },  // Amount
                  { wch: 12 },  // Status
                  { wch: 12 },  // Paid
                  { wch: 35 },  // Student
                  { wch: 22 }   // Payment Date
            ];

            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Sheet Payments");

            // Generate filename with timestamp
            var filename = 'Sheet_Payments_Report_' + new Date().toISOString().slice(0, 10) + '.xlsx';

            XLSX.writeFile(wb, filename);

            Swal.fire({
                  text: "Excel file exported successfully!",
                  icon: "success",
                  showConfirmButton: false,
                  timer: 1500,
                  timerProgressBar: true
            });
      }

      // Export to CSV using SheetJS
      var exportToCSV = function (data) {
            var headers = getExportHeaders();
            var rows = data.map(function (row) {
                  return getExportRowData(row);
            });

            var wsData = [headers].concat(rows);
            var ws = XLSX.utils.aoa_to_sheet(wsData);
            var csv = XLSX.utils.sheet_to_csv(ws);

            // Create download link
            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            var url = URL.createObjectURL(blob);

            var filename = 'Sheet_Payments_Report_' + new Date().toISOString().slice(0, 10) + '.csv';

            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            Swal.fire({
                  text: "CSV file exported successfully!",
                  icon: "success",
                  showConfirmButton: false,
                  timer: 1500,
                  timerProgressBar: true
            });
      }

      // Export to PDF using jsPDF
      var exportToPDF = function (data) {
            const { jsPDF } = window.jspdf;
            var doc = new jsPDF('l', 'mm', 'a4'); // Landscape orientation

            var headers = [getExportHeaders()];
            var rows = data.map(function (row) {
                  return getExportRowData(row);
            });

            // Add title
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text('Sheet Payments Report', 14, 15);

            // Add timestamp and branch info
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            var subtitle = 'Generated: ' + getCurrentTimestamp();
            var activeTab = document.querySelector('#branchTabs .nav-link.active');
            if (activeTab) {
                  // Get only the branch name text, not the badge count
                  var branchName = activeTab.childNodes[1] ? activeTab.childNodes[1].textContent.trim() : activeTab.textContent.trim().split('\n')[0].trim();
                  subtitle += ' | Branch: ' + branchName;
            }
            doc.text(subtitle, 14, 22);

            // Column widths
            var columnStyles = {
                  0: { cellWidth: 10 },  // SL
                  1: { cellWidth: 35 },  // Sheet Group
                  2: { cellWidth: 30 },  // Invoice No.
                  3: { cellWidth: 25 },  // Amount
                  4: { cellWidth: 20 },  // Status
                  5: { cellWidth: 25 },  // Paid
                  6: { cellWidth: 55 },  // Student
                  7: { cellWidth: 35 }   // Payment Date
            };

            // Generate table
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
                  alternateRowStyles: {
                        fillColor: [245, 245, 245]
                  },
                  columnStyles: columnStyles,
                  margin: { left: 14, right: 14 },
                  didDrawPage: function (data) {
                        // Footer
                        var pageCount = doc.internal.getNumberOfPages();
                        doc.setFontSize(8);
                        doc.setFont('helvetica', 'normal');
                        doc.text(
                              'Page ' + doc.internal.getCurrentPageInfo().pageNumber + ' of ' + pageCount,
                              doc.internal.pageSize.width / 2,
                              doc.internal.pageSize.height - 10,
                              { align: 'center' }
                        );
                        doc.text(
                              'Printed: ' + getCurrentTimestamp(),
                              14,
                              doc.internal.pageSize.height - 10
                        );
                  }
            });

            // Generate filename with timestamp
            var filename = 'Sheet_Payments_Report_' + new Date().toISOString().slice(0, 10) + '.pdf';

            doc.save(filename);

            Swal.fire({
                  text: "PDF file exported successfully!",
                  icon: "success",
                  showConfirmButton: false,
                  timer: 1500,
                  timerProgressBar: true
            });
      }

      // Hook export buttons
      var exportButtons = function () {
            const exportItems = document.querySelectorAll('#kt_table_report_dropdown_menu [data-row-export]');

            exportItems.forEach(function (exportItem) {
                  exportItem.addEventListener('click', function (e) {
                        e.preventDefault();
                        var exportType = this.getAttribute('data-row-export');

                        fetchExportData(function (data) {
                              if (data.length === 0) {
                                    Swal.fire({
                                          text: "No data available to export.",
                                          icon: "warning",
                                          buttonsStyling: false,
                                          confirmButtonText: "Ok, got it!",
                                          customClass: {
                                                confirmButton: "btn btn-primary"
                                          }
                                    });
                                    return;
                              }

                              switch (exportType) {
                                    case 'copy':
                                          exportToClipboard(data);
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
                                    default:
                                          console.warn('Unknown export type:', exportType);
                              }
                        });
                  });
            });
      };

      // Search Datatable with debounce
      var handleSearch = function () {
            var searchInput = document.querySelector('[data-sheet-payments-table-filter="search"]');
            var searchTimeout = null;

            searchInput.addEventListener('keyup', function (e) {
                  searchValue = e.target.value;

                  // Debounce search to avoid too many requests
                  clearTimeout(searchTimeout);
                  searchTimeout = setTimeout(function () {
                        datatable.search(searchValue).draw();
                  }, 500);
            });
      }

      // Filter Datatable
      var handleFilter = function () {
            // Select filter options
            var filterForm = document.querySelector('[data-sheet-payments-table-filter="form"]');
            var filterButton = filterForm.querySelector('[data-sheet-payments-table-filter="filter"]');
            var resetButton = filterForm.querySelector('[data-sheet-payments-table-filter="reset"]');
            var sheetGroupSelect = filterForm.querySelector('[data-sheet-payments-table-filter="sheet_group"]');
            var paymentStatusSelect = filterForm.querySelector('[data-sheet-payments-table-filter="payment_status"]');

            // Filter datatable on submit
            filterButton.addEventListener('click', function () {
                  filterSheetGroup = $(sheetGroupSelect).val() || '';
                  filterPaymentStatus = $(paymentStatusSelect).val() || '';

                  // Reload datatable with new filters
                  datatable.ajax.reload();
            });

            // Reset datatable
            resetButton.addEventListener('click', function () {
                  // Reset Select2 dropdowns
                  $(sheetGroupSelect).val(null).trigger('change');
                  $(paymentStatusSelect).val(null).trigger('change');

                  // Reset filter variables
                  filterSheetGroup = '';
                  filterPaymentStatus = '';
                  searchValue = '';

                  // Clear search input
                  document.querySelector('[data-sheet-payments-table-filter="search"]').value = '';

                  // Reload datatable
                  datatable.search('').ajax.reload();
            });
      }

      return {
            // Public functions  
            init: function () {
                  table = document.getElementById('kt_sheet_payments_table');

                  if (!table) {
                        return;
                  }

                  // Initialize branch tabs first to set initial filter
                  handleBranchTabs();
                  initDatatable();
                  exportButtons();
                  handleSearch();
                  handleFilter();
            }
      }
}();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      AllPaymentsList.init();
});