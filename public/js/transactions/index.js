"use strict";

var KTAllTransactionsList = (function () {
      // Define shared variables
      var datatables = {};
      var activeDatatable = null;
      var activeBranchId = null;
      var initializedTabs = {};
      var currentSearchValue = "";
      var currentPaymentTypeFilter = "";
      var currentShowDeleted = false;
      var searchDebounceTimer = null;

      // Get DataTable config for AJAX loading
      var getDataTableConfig = function (branchId) {
            return {
                  processing: true,
                  serverSide: true,
                  ajax: {
                        url: routeAjaxData,
                        type: "GET",
                        data: function (d) {
                              d.branch_id = branchId;
                              d.payment_type_filter = currentPaymentTypeFilter;
                              d.show_deleted = currentShowDeleted ? '1' : '0';
                        },
                        error: function (xhr, error, thrown) {
                              console.error("DataTables AJAX error:", error, thrown);
                              toastr.error("Failed to load transactions. Please refresh the page.");
                        },
                  },
                  columns: [
                        { data: "sl", orderable: false, searchable: false },
                        { data: "invoice_no", name: "invoice_no" },
                        { data: "voucher_no", name: "voucher_no" },
                        { data: "amount_paid", name: "amount_paid" },
                        {
                              data: "payment_type_filter",
                              name: "payment_type_filter",
                              visible: false,
                        },
                        { data: "payment_type", name: "payment_type", orderable: false },
                        { data: "student", name: "student" },
                        { data: "payment_date", name: "payment_date" },
                        { data: "received_by", name: "received_by" },
                        { data: "actions", orderable: false, searchable: false },
                  ],
                  order: [],
                  pageLength: 10,
                  lengthMenu: [10, 25, 50, 100],
                  language: {
                        processing:
                              '<div class="d-flex align-items-center"><span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading...</div>',
                        emptyTable: "No transactions found",
                        zeroRecords: "No matching transactions found",
                  },
                  drawCallback: function () {
                        KTMenu.init();
                        initTooltips();
                  },
            };
      };

      // Initialize tooltips
      var initTooltips = function () {
            var tooltipTriggerList = [].slice.call(
                  document.querySelectorAll('[data-bs-toggle="tooltip"]')
            );
            tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                  new bootstrap.Tooltip(tooltipTriggerEl);
            });
      };

      // Initialize a single datatable
      var initSingleDatatable = function (tableId, branchId) {
            var table = document.getElementById(tableId);
            if (!table) {
                  return null;
            }

            var config = getDataTableConfig(branchId);
            var datatable = $(table).DataTable(config);

            return datatable;
      };

      // Initialize datatables for admin (multiple tabs)
      var initAdminDatatables = function () {
            if (branchIds && branchIds.length > 0) {
                  var firstBranchId = branchIds[0];
                  var firstTableId = "kt_transactions_table_branch_" + firstBranchId;

                  datatables[firstBranchId] = initSingleDatatable(firstTableId, firstBranchId);
                  activeDatatable = datatables[firstBranchId];
                  activeBranchId = firstBranchId;
                  initializedTabs[firstBranchId] = true;
            }

            // Setup tab change event listener for lazy loading
            var tabLinks = document.querySelectorAll(
                  '#transactionBranchTabs a[data-bs-toggle="tab"]'
            );
            tabLinks.forEach(function (tabLink) {
                  tabLink.addEventListener("shown.bs.tab", function (event) {
                        var branchId = event.target.getAttribute("data-branch-id");
                        var tableId = "kt_transactions_table_branch_" + branchId;

                        activeBranchId = branchId;

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
            var table = document.getElementById("kt_transactions_table");
            if (!table) {
                  return;
            }

            var branchId = table.getAttribute("data-branch-id") || "";
            datatables["single"] = initSingleDatatable("kt_transactions_table", branchId);
            activeDatatable = datatables["single"];
            activeBranchId = branchId;
      };

      // Search Handler with debounce
      var handleSearch = function () {
            const filterSearch = document.querySelector(
                  '[data-transaction-table-filter="search"]'
            );
            if (!filterSearch) return;

            filterSearch.addEventListener("keyup", function (e) {
                  clearTimeout(searchDebounceTimer);
                  currentSearchValue = e.target.value;

                  searchDebounceTimer = setTimeout(function () {
                        if (activeDatatable) {
                              activeDatatable.search(currentSearchValue).draw();
                        }
                  }, 400);
            });
      };

      // Filter Handler
      var handleFilter = function () {
            const filterForm = document.querySelector(
                  '[data-transaction-table-filter="form"]'
            );
            if (!filterForm) return;

            const filterButton = filterForm.querySelector(
                  '[data-transaction-table-filter="filter"]'
            );
            const resetButton = filterForm.querySelector(
                  '[data-transaction-table-filter="reset"]'
            );
            const paymentTypeSelect = document.getElementById(
                  "payment_type_filter_select"
            );
            const showDeletedCheckbox = document.getElementById("show_deleted_filter");

            // Filter datatable on submit
            if (filterButton) {
                  filterButton.addEventListener("click", function () {
                        currentPaymentTypeFilter = paymentTypeSelect
                              ? paymentTypeSelect.value
                              : "";
                        currentShowDeleted = showDeletedCheckbox
                              ? showDeletedCheckbox.checked
                              : false;

                        // Update UI to show deleted mode indicator
                        updateDeletedModeIndicator();

                        if (activeDatatable) {
                              activeDatatable.ajax.reload();
                        }
                  });
            }

            // Reset filter
            if (resetButton) {
                  resetButton.addEventListener("click", function () {
                        if (paymentTypeSelect) {
                              $(paymentTypeSelect).val(null).trigger("change");
                        }
                        if (showDeletedCheckbox) {
                              showDeletedCheckbox.checked = false;
                        }
                        currentPaymentTypeFilter = "";
                        currentShowDeleted = false;

                        // Update UI to show deleted mode indicator
                        updateDeletedModeIndicator();

                        if (activeDatatable) {
                              activeDatatable.ajax.reload();
                        }
                  });
            }
      };

      // Update UI indicator when showing deleted transactions
      var updateDeletedModeIndicator = function () {
            const existingIndicator = document.getElementById("deleted_mode_indicator");

            if (currentShowDeleted) {
                  if (!existingIndicator) {
                        const cardTitle = document.querySelector(".card-title");
                        if (cardTitle) {
                              const indicator = document.createElement("span");
                              indicator.id = "deleted_mode_indicator";
                              indicator.className = "badge badge-danger ms-3";
                              indicator.innerHTML = '<i class="ki-outline ki-trash text-white me-1"></i>Showing Deleted Only';
                              cardTitle.appendChild(indicator);
                        }
                  }
            } else {
                  if (existingIndicator) {
                        existingIndicator.remove();
                  }
            }
      };

      // Export handlers using SheetJS and jsPDF
      var handleExport = function () {
            const exportItems = document.querySelectorAll(
                  "#kt_table_report_dropdown_menu [data-row-export]"
            );

            exportItems.forEach((exportItem) => {
                  exportItem.addEventListener("click", function (e) {
                        e.preventDefault();

                        const exportType = this.getAttribute("data-row-export");
                        const exportBtn = document.getElementById("export_dropdown_btn");

                        // Show loading state
                        if (exportBtn) {
                              exportBtn.classList.add("export-loading");
                              exportBtn.innerHTML =
                                    '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Exporting...';
                        }

                        // Fetch all data for export
                        fetchExportData()
                              .then((result) => {
                                    const data = result.data;
                                    const showDeleted = result.showDeleted;

                                    switch (exportType) {
                                          case "copy":
                                                copyToClipboard(data, showDeleted);
                                                break;
                                          case "excel":
                                                exportToExcel(data, showDeleted);
                                                break;
                                          case "csv":
                                                exportToCSV(data, showDeleted);
                                                break;
                                          case "pdf":
                                                exportToPDF(data, showDeleted);
                                                break;
                                    }
                              })
                              .catch((error) => {
                                    console.error("Export error:", error);
                                    toastr.error("Failed to export data. Please try again.");
                              })
                              .finally(() => {
                                    // Reset button state
                                    if (exportBtn) {
                                          exportBtn.classList.remove("export-loading");
                                          exportBtn.innerHTML =
                                                '<i class="ki-outline ki-exit-up fs-2"></i>Export';
                                    }
                              });
                  });
            });
      };

      // Fetch export data from server
      var fetchExportData = function () {
            return new Promise((resolve, reject) => {
                  const params = new URLSearchParams({
                        branch_id: activeBranchId || "",
                        search: currentSearchValue,
                        payment_type_filter: currentPaymentTypeFilter,
                        show_deleted: currentShowDeleted ? "1" : "0",
                  });

                  fetch(`${routeExportData}?${params.toString()}`, {
                        method: "GET",
                        headers: {
                              "X-CSRF-TOKEN": csrfToken,
                              Accept: "application/json",
                        },
                  })
                        .then((response) => {
                              if (!response.ok) throw new Error("Network response was not ok");
                              return response.json();
                        })
                        .then((data) => resolve({ data: data.data, showDeleted: data.show_deleted }))
                        .catch((error) => reject(error));
            });
      };

      // Copy to clipboard
      var copyToClipboard = function (data, showDeleted) {
            let headers = [
                  "SL",
                  "Invoice No.",
                  "Voucher No.",
                  "Amount (Tk)",
                  "Payment Type",
                  "Student",
                  "Payment Date",
                  "Received By",
            ];

            // Add Deleted At column for deleted transactions export
            if (showDeleted) {
                  headers.push("Deleted At");
            }

            let text = headers.join("\t") + "\n";
            data.forEach((row) => {
                  let rowData = [
                        row.sl,
                        row.invoice_no,
                        row.voucher_no,
                        row.amount_paid,
                        row.payment_type,
                        row.student,
                        row.payment_date,
                        row.received_by,
                  ];

                  if (showDeleted) {
                        rowData.push(row.deleted_at || "");
                  }

                  text += rowData.join("\t") + "\n";
            });

            navigator.clipboard
                  .writeText(text)
                  .then(() => {
                        toastr.success("Data copied to clipboard!");
                  })
                  .catch((err) => {
                        console.error("Copy failed:", err);
                        // Fallback for older browsers
                        const textarea = document.createElement("textarea");
                        textarea.value = text;
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand("copy");
                        document.body.removeChild(textarea);
                        toastr.success("Data copied to clipboard!");
                  });
      };

      // Export to Excel using SheetJS
      var exportToExcel = function (data, showDeleted) {
            let headers = [
                  "SL",
                  "Invoice No.",
                  "Voucher No.",
                  "Amount (Tk)",
                  "Payment Type",
                  "Student",
                  "Payment Date",
                  "Received By",
            ];

            if (showDeleted) {
                  headers.push("Deleted At");
            }

            const wsData = [headers];

            data.forEach((row) => {
                  let rowData = [
                        row.sl,
                        row.invoice_no,
                        row.voucher_no,
                        row.amount_paid,
                        row.payment_type,
                        row.student,
                        row.payment_date,
                        row.received_by,
                  ];

                  if (showDeleted) {
                        rowData.push(row.deleted_at || "");
                  }

                  wsData.push(rowData);
            });

            const ws = XLSX.utils.aoa_to_sheet(wsData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, showDeleted ? "Deleted Transactions" : "Transactions");

            // Set column widths
            let colWidths = [
                  { wch: 5 },
                  { wch: 15 },
                  { wch: 25 },
                  { wch: 12 },
                  { wch: 12 },
                  { wch: 30 },
                  { wch: 22 },
                  { wch: 15 },
            ];

            if (showDeleted) {
                  colWidths.push({ wch: 22 });
            }

            ws["!cols"] = colWidths;

            const fileName = showDeleted
                  ? `Deleted_Transactions_Report_${new Date().toISOString().slice(0, 10)}.xlsx`
                  : `Transactions_Report_${new Date().toISOString().slice(0, 10)}.xlsx`;
            XLSX.writeFile(wb, fileName);
            toastr.success("Excel file downloaded successfully!");
      };

      // Export to CSV using SheetJS
      var exportToCSV = function (data, showDeleted) {
            let headers = [
                  "SL",
                  "Invoice No.",
                  "Voucher No.",
                  "Amount (Tk)",
                  "Payment Type",
                  "Student",
                  "Payment Date",
                  "Received By",
            ];

            if (showDeleted) {
                  headers.push("Deleted At");
            }

            const wsData = [headers];

            data.forEach((row) => {
                  let rowData = [
                        row.sl,
                        row.invoice_no,
                        row.voucher_no,
                        row.amount_paid,
                        row.payment_type,
                        row.student,
                        row.payment_date,
                        row.received_by,
                  ];

                  if (showDeleted) {
                        rowData.push(row.deleted_at || "");
                  }

                  wsData.push(rowData);
            });

            const ws = XLSX.utils.aoa_to_sheet(wsData);
            const csv = XLSX.utils.sheet_to_csv(ws);

            const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
            const link = document.createElement("a");
            const fileName = showDeleted
                  ? `Deleted_Transactions_Report_${new Date().toISOString().slice(0, 10)}.csv`
                  : `Transactions_Report_${new Date().toISOString().slice(0, 10)}.csv`;

            link.href = URL.createObjectURL(blob);
            link.download = fileName;
            link.click();
            URL.revokeObjectURL(link.href);

            toastr.success("CSV file downloaded successfully!");
      };

      // Export to PDF using jsPDF
      var exportToPDF = function (data, showDeleted) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF("l", "mm", "a4"); // Landscape orientation

            let headerRow = [
                  "SL",
                  "Invoice No.",
                  "Voucher No.",
                  "Amount",
                  "Type",
                  "Student",
                  "Payment Date",
                  "Received By",
            ];

            if (showDeleted) {
                  headerRow.push("Deleted At");
            }

            const headers = [headerRow];

            const rows = data.map((row) => {
                  let rowData = [
                        row.sl,
                        row.invoice_no,
                        row.voucher_no,
                        row.amount_paid,
                        row.payment_type,
                        row.student,
                        row.payment_date,
                        row.received_by,
                  ];

                  if (showDeleted) {
                        rowData.push(row.deleted_at || "");
                  }

                  return rowData;
            });

            // Title
            doc.setFontSize(16);
            const title = showDeleted ? "Deleted Transactions Report" : "Transactions Report";
            doc.text(title, 14, 15);

            // Date
            doc.setFontSize(10);
            doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 22);

            // Column styles
            let columnStyles = {
                  0: { cellWidth: 10 },
                  1: { cellWidth: 25 },
                  2: { cellWidth: 35 },
                  3: { cellWidth: 18 },
                  4: { cellWidth: 18 },
                  5: { cellWidth: 45 },
                  6: { cellWidth: 35 },
                  7: { cellWidth: 22 },
            };

            if (showDeleted) {
                  columnStyles[8] = { cellWidth: 35 };
            }

            // Table
            doc.autoTable({
                  head: headers,
                  body: rows,
                  startY: 28,
                  styles: {
                        fontSize: 7,
                        cellPadding: 2,
                  },
                  headStyles: {
                        fillColor: showDeleted ? [220, 53, 69] : [41, 128, 185],
                        textColor: 255,
                        fontStyle: "bold",
                  },
                  alternateRowStyles: {
                        fillColor: showDeleted ? [255, 235, 238] : [245, 245, 245],
                  },
                  columnStyles: columnStyles,
                  margin: { top: 28 },
                  didDrawPage: function (data) {
                        // Footer
                        doc.setFontSize(8);
                        doc.text(
                              `Page ${doc.internal.getNumberOfPages()}`,
                              doc.internal.pageSize.width - 20,
                              doc.internal.pageSize.height - 10
                        );
                  },
            });

            const fileName = showDeleted
                  ? `Deleted_Transactions_Report_${new Date().toISOString().slice(0, 10)}.pdf`
                  : `Transactions_Report_${new Date().toISOString().slice(0, 10)}.pdf`;
            doc.save(fileName);
            toastr.success("PDF file downloaded successfully!");
      };

      // Delete Transaction - Updated with better confirmation for approved transactions
      var handleDeletion = function () {
            document.addEventListener("click", function (e) {
                  const deleteBtn = e.target.closest(".delete-txn");
                  if (!deleteBtn) return;

                  e.preventDefault();

                  let txnId = deleteBtn.getAttribute("data-txn-id");
                  let isApproved = deleteBtn.getAttribute("data-is-approved") === "1";
                  let url = routeDeleteTxn.replace(":id", txnId);

                  // Different warning message based on whether transaction is approved
                  let warningTitle = "Are you sure you want to delete?";
                  let warningText = "Once deleted, this unapproved transaction will be removed.";

                  if (isApproved) {
                        warningTitle = "Delete Approved Transaction?";
                        warningText = "This transaction has been approved. Deleting it will:\n\n• Reverse the wallet collection\n• Restore the invoice amount due\n• Create an adjustment log\n• Decrease the collector's total collected amount\n\nNote: Approved transactions can only be deleted within 24 hours of creation.\n\nThis action cannot be undone.";
                  }

                  Swal.fire({
                        title: warningTitle,
                        text: warningText,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#6c757d",
                        confirmButtonText: isApproved ? "Yes, delete and reverse" : "Yes, delete it",
                        cancelButtonText: "Cancel",
                        customClass: {
                              popup: 'swal-wide'
                        }
                  }).then((result) => {
                        if (result.isConfirmed) {
                              // Show loading state on button
                              const originalContent = deleteBtn.innerHTML;
                              deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
                              deleteBtn.style.pointerEvents = 'none';

                              fetch(url, {
                                    method: "DELETE",
                                    headers: {
                                          "Content-Type": "application/json",
                                          "X-CSRF-TOKEN": csrfToken,
                                    },
                              })
                                    .then((response) => response.json())
                                    .then((data) => {
                                          if (data.success) {
                                                Swal.fire({
                                                      title: "Deleted!",
                                                      text: data.message || "Transaction deleted successfully.",
                                                      icon: "success",
                                                      confirmButtonText: "Okay",
                                                }).then(() => {
                                                      if (activeDatatable) {
                                                            activeDatatable.ajax.reload(null, false);
                                                      }
                                                });
                                          } else {
                                                // Restore button state
                                                deleteBtn.innerHTML = originalContent;
                                                deleteBtn.style.pointerEvents = 'auto';

                                                Swal.fire({
                                                      title: "Failed!",
                                                      text: data.message || "Transaction could not be deleted.",
                                                      icon: "error",
                                                });
                                          }
                                    })
                                    .catch((error) => {
                                          console.error("Fetch Error:", error);

                                          // Restore button state
                                          deleteBtn.innerHTML = originalContent;
                                          deleteBtn.style.pointerEvents = 'auto';

                                          Swal.fire({
                                                title: "Error!",
                                                text: "An error occurred. Please try again or contact support.",
                                                icon: "error",
                                          });
                                    });
                        }
                  });
            });
      };

      // Transaction approval AJAX
      var handleApproval = function () {
            document.addEventListener("click", function (e) {
                  const approveBtn = e.target.closest(".approve-txn");
                  if (!approveBtn) return;

                  e.preventDefault();

                  let txnId = approveBtn.getAttribute("data-txn-id");

                  Swal.fire({
                        title: "Are you sure?",
                        text: "Do you want to approve this transaction?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, approve!",
                  }).then((result) => {
                        if (result.isConfirmed) {
                              // Show loading state
                              const originalContent = approveBtn.innerHTML;
                              approveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
                              approveBtn.style.pointerEvents = 'none';

                              fetch(`/transactions/${txnId}/approve`, {
                                    method: "POST",
                                    headers: {
                                          "Content-Type": "application/json",
                                          "X-CSRF-TOKEN": csrfToken,
                                    },
                              })
                                    .then((response) => response.json())
                                    .then((data) => {
                                          if (data.success) {
                                                Swal.fire({
                                                      title: "Approved!",
                                                      text: "Transaction approved successfully.",
                                                      icon: "success",
                                                }).then(() => {
                                                      if (activeDatatable) {
                                                            activeDatatable.ajax.reload(null, false);
                                                      }
                                                });
                                          } else {
                                                // Restore button state
                                                approveBtn.innerHTML = originalContent;
                                                approveBtn.style.pointerEvents = 'auto';

                                                Swal.fire({
                                                      title: "Error!",
                                                      text: data.message,
                                                      icon: "warning",
                                                });
                                          }
                                    })
                                    .catch((error) => {
                                          console.error("Fetch Error:", error);

                                          // Restore button state
                                          approveBtn.innerHTML = originalContent;
                                          approveBtn.style.pointerEvents = 'auto';

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

      // Statement Download Handler
      var handleStatementDownload = function () {
            document.addEventListener("click", function (e) {
                  const downloadBtn = e.target.closest(".download-statement");
                  if (!downloadBtn) return;

                  e.preventDefault();

                  const studentId = downloadBtn.getAttribute("data-student-id");
                  const year = downloadBtn.getAttribute("data-year");
                  const invoiceId = downloadBtn.getAttribute("data-invoice-id");

                  if (!studentId || !year) {
                        Swal.fire({
                              title: "Error!",
                              text: "Missing student or year information.",
                              icon: "error",
                        });
                        return;
                  }

                  // Show loading state on button
                  const originalIcon = downloadBtn.innerHTML;
                  downloadBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                  downloadBtn.style.pointerEvents = "none";

                  // Create FormData for POST request
                  const formData = new FormData();
                  formData.append("student_id", studentId);
                  formData.append("statement_year", year);
                  formData.append("invoice_id", invoiceId);

                  fetch(routeDownloadStatement, {
                        method: "POST",
                        headers: {
                              "X-CSRF-TOKEN": csrfToken,
                        },
                        body: formData,
                  })
                        .then((response) => {
                              if (!response.ok) {
                                    return response.text().then((text) => {
                                          throw new Error(text || "Server error occurred");
                                    });
                              }
                              return response.text();
                        })
                        .then((html) => {
                              // Create a new window with the HTML content
                              const printWindow = window.open(
                                    "",
                                    "_blank",
                                    "width=900,height=700,scrollbars=yes,resizable=yes"
                              );

                              if (printWindow) {
                                    printWindow.document.open();
                                    printWindow.document.write(html);
                                    printWindow.document.close();
                                    printWindow.focus();
                              } else {
                                    Swal.fire({
                                          title: "Popup Blocked!",
                                          text: "Please allow popups for this website to view the statement.",
                                          icon: "warning",
                                    });
                              }

                              // Restore button state
                              downloadBtn.innerHTML = originalIcon;
                              downloadBtn.style.pointerEvents = "auto";
                        })
                        .catch((error) => {
                              console.error("Statement Download Error:", error);

                              const errorMessage = error.message.toLowerCase();
                              if (errorMessage.includes("no transactions")) {
                                    Swal.fire({
                                          title: "No Data Found",
                                          text: "No transactions found for the selected year.",
                                          icon: "info",
                                    });
                              } else {
                                    Swal.fire({
                                          title: "Error!",
                                          text: "Failed to load statement. Please try again.",
                                          icon: "error",
                                    });
                              }

                              // Restore button state
                              downloadBtn.innerHTML = originalIcon;
                              downloadBtn.style.pointerEvents = "auto";
                        });
            });
      };

      // Refresh current datatable (can be called after creating new transaction)
      var refreshTable = function () {
            if (activeDatatable) {
                  activeDatatable.ajax.reload(null, false);
            }
      };

      return {
            init: function () {
                  // Check if admin or non-admin based on the presence of tabs
                  if (typeof isAdmin !== "undefined" && isAdmin) {
                        initAdminDatatables();
                  } else {
                        initNonAdminDatatable();
                  }

                  handleSearch();
                  handleFilter();
                  handleExport();
                  handleDeletion();
                  handleApproval();
                  handleStatementDownload();
            },

            getActiveDatatable: function () {
                  return activeDatatable;
            },

            refreshTable: refreshTable,
      };
})();

var KTAddTransaction = (function () {
      // Shared variables
      const element = document.getElementById("kt_modal_add_transaction");

      // Early return if element doesn't exist
      if (!element) {
            return {
                  init: function () { },
            };
      }

      const form = element.querySelector("#kt_modal_add_transaction_form");
      const modal = bootstrap.Modal.getOrCreateInstance(element);
      const branchSelect = document.getElementById("transaction_branch_select");
      const studentSelect = document.getElementById("transaction_student_select");
      const invoiceSelect = document.getElementById("student_due_invoice_select");
      const amountInput = document.getElementById("transaction_amount_input");

      // Store invoices data
      let invoices = [];

      // Track if selected invoice is partially paid
      let isPartiallyPaidInvoice = false;

      // Format "07_2025" to "July 2025"
      var formatMonthYear = function (raw) {
            if (!raw) return "";
            const [monthStr, year] = raw.split("_");
            const month = parseInt(monthStr, 10);
            const monthNames = [
                  "January",
                  "February",
                  "March",
                  "April",
                  "May",
                  "June",
                  "July",
                  "August",
                  "September",
                  "October",
                  "November",
                  "December",
            ];
            if (month >= 1 && month <= 12 && year) {
                  return `${monthNames[month - 1]} ${year}`;
            }
            return raw;
      };

      // Handle branch select (for admin)
      var handleBranchSelect = function () {
            if (!branchSelect) return;

            $(branchSelect).on("change", function () {
                  const branchId = $(this).val();
                  const $studentSelect = $(studentSelect);

                  // Clear student select
                  $studentSelect
                        .empty()
                        .append('<option value="">Select a student</option>');

                  // Clear invoice select
                  $(invoiceSelect)
                        .empty()
                        .append('<option value="">Select Due Invoice</option>');

                  // Reset amount input
                  $(amountInput).val("").prop("disabled", true);

                  if (!branchId) return;

                  // Populate students for selected branch
                  if (
                        typeof studentsByBranch !== "undefined" &&
                        studentsByBranch[branchId]
                  ) {
                        studentsByBranch[branchId].forEach((student) => {
                              $studentSelect.append(
                                    `<option value="${student.id}">${student.name} (${student.student_unique_id})</option>`
                              );
                        });
                  }
            });
      };

      // Fetch invoices on student select
      var handleStudentSelect = function () {
            $(studentSelect).on("change", function () {
                  const studentId = $(this).val();
                  if (!studentId) return;

                  $.ajax({
                        url: `/students/${studentId}/due-invoices`,
                        method: "GET",
                        success: function (response) {
                              invoices = response;
                              const $invoiceSelect = $(invoiceSelect);

                              $invoiceSelect
                                    .empty()
                                    .append(`<option value="">Select Due Invoice</option>`);

                              if (response.length === 0) {
                                    $invoiceSelect.append(
                                          `<option disabled>No due invoices found</option>`
                                    );
                              } else {
                                    response.forEach((invoice) => {
                                          const total = Number(invoice.total_amount).toLocaleString(
                                                "en-BD"
                                          );
                                          const due = Number(invoice.amount_due).toLocaleString("en-BD");
                                          const label = invoice.month_year
                                                ? formatMonthYear(invoice.month_year)
                                                : invoice.invoice_type || "Unknown";

                                          $invoiceSelect.append(
                                                `<option value="${invoice.id}">
                                    ${invoice.invoice_number} (${label}) - Total: ৳${total}, Due: ৳${due}
                                </option>`
                                          );
                                    });
                              }

                              $(amountInput).val("").prop("disabled", true).removeClass("is-invalid");
                              $("#transaction_amount_error").remove();
                              $('input[name="transaction_type"]').prop("disabled", false);
                        },
                        error: function () {
                              alert("Failed to load due invoices. Please try again.");
                        },
                  });
            });
      };

      // Populate amount and adjust payment options when invoice selected
      var handleInvoiceSelect = function () {
            $(invoiceSelect).on("change", function () {
                  const selectedId = $(this).val();
                  const invoice = invoices.find((inv) => inv.id == selectedId);

                  if (invoice) {
                        const $amountInput = $(amountInput);
                        $amountInput
                              .val(invoice.amount_due)
                              .prop("disabled", false)
                              .data("max", invoice.amount_due)
                              .attr("min", 1);

                        const $fullPaymentOption = $(
                              'input[name="transaction_type"][value="full"]'
                        );
                        const $partialPaymentOption = $(
                              'input[name="transaction_type"][value="partial"]'
                        );

                        if (invoice.amount_due < invoice.total_amount) {
                              // Invoice is partially paid
                              isPartiallyPaidInvoice = true;
                              $fullPaymentOption.prop("disabled", true).prop("checked", false);
                              $partialPaymentOption.prop("checked", true);
                              $amountInput.val("");
                        } else {
                              // Fresh invoice
                              isPartiallyPaidInvoice = false;
                              $fullPaymentOption.prop("disabled", false);
                              $partialPaymentOption.prop("disabled", false);
                              $fullPaymentOption.prop("checked", true);
                              $amountInput.val(invoice.amount_due);
                        }
                  }
            });
      };

      // Toggle input behavior for payment type
      var handlePaymentTypeChange = function () {
            $('input[name="transaction_type"]').on("change", function () {
                  const paymentType = $(this).val();
                  const $amountInput = $(amountInput);
                  const selectedId = $(invoiceSelect).val();
                  const invoice = invoices.find((inv) => inv.id == selectedId);

                  if (invoice) {
                        if (paymentType === "partial") {
                              $amountInput.val("");
                        } else if (paymentType === "discounted") {
                              $amountInput.val("");
                        } else {
                              $amountInput.val(invoice.amount_due);
                        }
                  }
            });
      };

      // Validate amount input
      var handleAmountValidation = function () {
            $(amountInput).on("input", function () {
                  const amount = parseFloat($(this).val());
                  const maxAmount = parseFloat($(this).data("max"));
                  const paymentType = $('input[name="transaction_type"]:checked').val();

                  // Remove previous error state
                  $(this).removeClass("is-invalid");
                  $("#transaction_amount_error").remove();

                  // Validate the amount
                  let isValid = true;
                  let errorMessage = "";

                  if (isNaN(amount)) {
                        isValid = false;
                        errorMessage = "Please enter a valid number";
                  } else if (amount < 1) {
                        isValid = false;
                        errorMessage = "Amount must be at least ৳1";
                  } else if (
                        (paymentType === "partial" || paymentType === "discounted") &&
                        !isPartiallyPaidInvoice &&
                        amount >= maxAmount
                  ) {
                        isValid = false;
                        errorMessage = `For ${paymentType} payment, amount must be less than the due amount of ৳${maxAmount}`;
                  } else if (
                        (paymentType === "partial" || paymentType === "discounted") &&
                        isPartiallyPaidInvoice &&
                        amount > maxAmount
                  ) {
                        isValid = false;
                        errorMessage = `Amount must be less than or equal to the due amount of ৳${maxAmount}`;
                  } else if (paymentType === "full" && amount != maxAmount) {
                        isValid = false;
                        errorMessage = `For full payment, amount must be exactly ৳${maxAmount}`;
                  }

                  if (!isValid) {
                        $(this).addClass("is-invalid");
                        $(this).after(
                              `<div class="invalid-feedback" id="transaction_amount_error">
                        ${errorMessage}
                    </div>`
                        );
                  }
            });
      };

      // Form submission via AJAX
      var handleFormSubmit = function () {
            $(form).on("submit", function (e) {
                  e.preventDefault();

                  const amount = parseFloat($(amountInput).val());
                  const maxAmount = parseFloat($(amountInput).data("max"));
                  const paymentType = $('input[name="transaction_type"]:checked').val();

                  let isValid = true;

                  if (isNaN(amount)) {
                        isValid = false;
                  } else if (amount < 1) {
                        isValid = false;
                  } else if (
                        (paymentType === "partial" || paymentType === "discounted") &&
                        !isPartiallyPaidInvoice &&
                        amount >= maxAmount
                  ) {
                        isValid = false;
                  } else if (
                        (paymentType === "partial" || paymentType === "discounted") &&
                        isPartiallyPaidInvoice &&
                        amount > maxAmount
                  ) {
                        isValid = false;
                  } else if (paymentType === "full" && amount != maxAmount) {
                        isValid = false;
                  }

                  if (!isValid || $(amountInput).hasClass("is-invalid")) {
                        toastr.warning("Please enter a valid amount.");
                        return false;
                  }

                  // Get submit button and show loading state
                  const submitBtn = form.querySelector(
                        '[data-kt-add-transaction-modal-action="submit"]'
                  );
                  submitBtn.setAttribute("data-kt-indicator", "on");
                  submitBtn.disabled = true;

                  // Prepare form data
                  const formData = new FormData(form);

                  // Submit via AJAX
                  fetch(form.action, {
                        method: "POST",
                        headers: {
                              "X-CSRF-TOKEN": csrfToken,
                              Accept: "application/json",
                              "X-Requested-With": "XMLHttpRequest",
                        },
                        body: formData,
                  })
                        .then((response) => {
                              if (!response.ok) {
                                    return response.json().then((err) => {
                                          throw err;
                                    });
                              }
                              return response.json();
                        })
                        .then((data) => {
                              if (data.success) {
                                    toastr.success(
                                          data.message || "Transaction recorded successfully."
                                    );

                                    const transactionData = data.transaction;
                                    resetForm();
                                    modal.hide();

                                    if (transactionData && transactionData.is_approved) {
                                          Swal.fire({
                                                title: "Transaction Successful!",
                                                text: "Do you want to download the payment statement?",
                                                icon: "success",
                                                showCancelButton: true,
                                                confirmButtonColor: "#3085d6",
                                                cancelButtonColor: "#6c757d",
                                                confirmButtonText: "Yes, download",
                                                cancelButtonText: "No, just reload",
                                          }).then((result) => {
                                                if (result.isConfirmed) {
                                                      downloadStatementAndRefresh(
                                                            transactionData.student_id,
                                                            transactionData.year,
                                                            transactionData.invoice_id
                                                      );
                                                } else {
                                                      KTAllTransactionsList.refreshTable();
                                                }
                                          });
                                    } else {
                                          Swal.fire({
                                                title: "Transaction Recorded!",
                                                text: "This transaction requires approval before the statement can be downloaded.",
                                                icon: "info",
                                                confirmButtonText: "OK",
                                          }).then(() => {
                                                KTAllTransactionsList.refreshTable();
                                          });
                                    }
                              } else {
                                    toastr.error(data.message || "Failed to record transaction.");
                                    submitBtn.removeAttribute("data-kt-indicator");
                                    submitBtn.disabled = false;
                              }
                        })
                        .catch((error) => {
                              console.error("Transaction Error:", error);

                              let errorMessage = "An error occurred. Please try again.";
                              if (error.message) {
                                    errorMessage = error.message;
                              } else if (error.errors) {
                                    errorMessage = Object.values(error.errors).flat().join("\n");
                              }

                              toastr.error(errorMessage);
                              submitBtn.removeAttribute("data-kt-indicator");
                              submitBtn.disabled = false;
                        });

                  return false;
            });
      };

      // Download statement and then refresh table (no page reload)
      var downloadStatementAndRefresh = function (studentId, year, invoiceId) {
            const formData = new FormData();
            formData.append("student_id", studentId);
            formData.append("statement_year", year);
            formData.append("invoice_id", invoiceId);

            fetch(routeDownloadStatement, {
                  method: "POST",
                  headers: {
                        "X-CSRF-TOKEN": csrfToken,
                  },
                  body: formData,
            })
                  .then((response) => {
                        if (!response.ok) {
                              throw new Error("Failed to load statement");
                        }
                        return response.text();
                  })
                  .then((html) => {
                        const printWindow = window.open(
                              "",
                              "_blank",
                              "width=900,height=700,scrollbars=yes,resizable=yes"
                        );

                        if (printWindow) {
                              printWindow.document.open();
                              printWindow.document.write(html);
                              printWindow.document.close();
                              printWindow.focus();
                        } else {
                              Swal.fire({
                                    title: "Popup Blocked!",
                                    text: "Please allow popups for this website to view the statement.",
                                    icon: "warning",
                              });
                        }

                        KTAllTransactionsList.refreshTable();
                  })
                  .catch((error) => {
                        console.error("Statement Download Error:", error);
                        toastr.error("Failed to download statement.");
                        KTAllTransactionsList.refreshTable();
                  });
      };

      // Reset form and close modal
      var resetForm = function () {
            if (form) form.reset();

            if (branchSelect && $(branchSelect).data("select2")) {
                  $(branchSelect).val(null).trigger("change");
            }

            if (studentSelect && $(studentSelect).data("select2")) {
                  $(studentSelect).val(null).trigger("change");
            }

            if (invoiceSelect && $(invoiceSelect).data("select2")) {
                  $(invoiceSelect).val(null).trigger("change");
            }

            if (amountInput) {
                  amountInput.value = "";
                  amountInput.disabled = true;
            }

            $(amountInput).removeClass("is-invalid");
            $("#transaction_amount_error").remove();

            // Reset invoices array and flags
            invoices = [];
            isPartiallyPaidInvoice = false;
      };

      // Handle modal close actions
      var handleCloseModal = function () {
            const cancelButton = element.querySelector(
                  '[data-kt-add-transaction-modal-action="cancel"]'
            );
            if (cancelButton) {
                  cancelButton.addEventListener("click", function (e) {
                        e.preventDefault();
                        resetForm();
                        modal.hide();
                  });
            }

            const closeButton = element.querySelector(
                  '[data-kt-add-transaction-modal-action="close"]'
            );
            if (closeButton) {
                  closeButton.addEventListener("click", function (e) {
                        e.preventDefault();
                        resetForm();
                        modal.hide();
                  });
            }
      };

      return {
            init: function () {
                  handleBranchSelect();
                  handleStudentSelect();
                  handleInvoiceSelect();
                  handlePaymentTypeChange();
                  handleAmountValidation();
                  handleFormSubmit();
                  handleCloseModal();
            },
      };
})();

// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTAllTransactionsList.init();
      KTAddTransaction.init();
});
