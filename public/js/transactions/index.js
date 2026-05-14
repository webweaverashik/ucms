"use strict";

var KTAllTransactionsList = (function () {
      // ─── Shared state ────────────────────────────────────────────────────────────
      var datatables = {};
      var activeDatatable = null;
      var activeBranchId = null;
      var initializedTabs = {};
      var currentSearchValue = "";
      var currentPaymentTypeFilter = "";
      var currentShowDeleted = false;
      var currentDateFrom = "";   // "YYYY-MM-DD" or ""
      var currentDateTo = "";   // "YYYY-MM-DD" or ""
      var searchDebounceTimer = null;

      // ─── DataTable config ────────────────────────────────────────────────────────
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
                              d.show_deleted = currentShowDeleted ? "1" : "0";
                              d.date_from = currentDateFrom;
                              d.date_to = currentDateTo;
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
                        { data: "payment_type_filter", name: "payment_type_filter", visible: false },
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
                        processing: '<div class="d-flex align-items-center"><span class="spinner-border spinner-border-sm me-2" role="status"></span> Loading...</div>',
                        emptyTable: "No transactions found",
                        zeroRecords: "No matching transactions found",
                  },
                  drawCallback: function () {
                        KTMenu.init();
                        initTooltips();
                  },
            };
      };

      // ─── Tooltip init ────────────────────────────────────────────────────────────
      var initTooltips = function () {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                  new bootstrap.Tooltip(el);
            });
      };

      // ─── Single table init ───────────────────────────────────────────────────────
      var initSingleDatatable = function (tableId, branchId) {
            var table = document.getElementById(tableId);
            if (!table) return null;
            return $(table).DataTable(getDataTableConfig(branchId));
      };

      // ─── Admin: lazy-load per branch tab ─────────────────────────────────────────
      var initAdminDatatables = function () {
            if (branchIds && branchIds.length > 0) {
                  var firstBranchId = branchIds[0];
                  var firstTableId = "kt_transactions_table_branch_" + firstBranchId;
                  datatables[firstBranchId] = initSingleDatatable(firstTableId, firstBranchId);
                  activeDatatable = datatables[firstBranchId];
                  activeBranchId = firstBranchId;
                  initializedTabs[firstBranchId] = true;
            }

            var tabLinks = document.querySelectorAll('#transactionBranchTabs a[data-bs-toggle="tab"]');
            tabLinks.forEach(function (tabLink) {
                  tabLink.addEventListener("shown.bs.tab", function (event) {
                        var branchId = event.target.getAttribute("data-branch-id");
                        var tableId = "kt_transactions_table_branch_" + branchId;
                        activeBranchId = branchId;

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

      // ─── Non-admin: single table ─────────────────────────────────────────────────
      var initNonAdminDatatable = function () {
            var table = document.getElementById("kt_transactions_table");
            if (!table) return;
            var branchId = table.getAttribute("data-branch-id") || "";
            datatables["single"] = initSingleDatatable("kt_transactions_table", branchId);
            activeDatatable = datatables["single"];
            activeBranchId = branchId;
      };

      // ─── Search ──────────────────────────────────────────────────────────────────
      var handleSearch = function () {
            var filterSearch = document.querySelector('[data-transaction-table-filter="search"]');
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

      // ─── Date Range Filter (flatpickr + presets) ─────────────────────────────────
      var datePickerInstance = null;

      /**
       * Format a Date object to "YYYY-MM-DD".
       */
      var toYMD = function (date) {
            var y = date.getFullYear();
            var m = String(date.getMonth() + 1).padStart(2, "0");
            var d = String(date.getDate()).padStart(2, "0");
            return y + "-" + m + "-" + d;
      };

      /**
       * Format "YYYY-MM-DD" to a human-friendly "DD MMM YYYY".
       */
      var toDisplayDate = function (ymd) {
            if (!ymd) return "";
            var parts = ymd.split("-");
            var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            return parts[2] + " " + months[parseInt(parts[1], 10) - 1] + " " + parts[0];
      };

      var updateDateRangeDisplay = function () {
            var display = document.getElementById("selected_date_range_display");
            var text = document.getElementById("selected_date_range_text");
            if (!display || !text) return;

            if (currentDateFrom || currentDateTo) {
                  var label = currentDateFrom && currentDateTo
                        ? toDisplayDate(currentDateFrom) + " → " + toDisplayDate(currentDateTo)
                        : currentDateFrom
                              ? "From " + toDisplayDate(currentDateFrom)
                              : "To " + toDisplayDate(currentDateTo);
                  text.textContent = label;
                  display.style.display = "";
            } else {
                  display.style.display = "none";
            }
      };

      var clearActivePreset = function () {
            document.querySelectorAll(".date-preset-btn").forEach(function (btn) {
                  btn.classList.remove("active");
            });
      };

      var applyPreset = function (preset) {
            var today = new Date();
            var from, to;

            switch (preset) {
                  case "today":
                        from = to = toYMD(today);
                        break;

                  case "yesterday":
                        var yesterday = new Date(today);
                        yesterday.setDate(today.getDate() - 1);
                        from = to = toYMD(yesterday);
                        break;

                  case "last7":
                        var d7 = new Date(today);
                        d7.setDate(today.getDate() - 6);
                        from = toYMD(d7);
                        to = toYMD(today);
                        break;

                  case "last30":
                        var d30 = new Date(today);
                        d30.setDate(today.getDate() - 29);
                        from = toYMD(d30);
                        to = toYMD(today);
                        break;

                  case "thismonth":
                        var tmStart = new Date(today.getFullYear(), today.getMonth(), 1);
                        var tmEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        from = toYMD(tmStart);
                        to = toYMD(tmEnd);
                        break;

                  case "lastmonth":
                        var lmStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                        var lmEnd = new Date(today.getFullYear(), today.getMonth(), 0);
                        from = toYMD(lmStart);
                        to = toYMD(lmEnd);
                        break;

                  case "custom":
                        // Show flatpickr input and let user pick
                        document.getElementById("custom_date_range_wrapper").style.display = "";
                        if (datePickerInstance) {
                              datePickerInstance.open();
                        }
                        return; // Don't set dates yet – wait for flatpickr onChange

                  default:
                        from = to = "";
            }

            // Hide custom wrapper for non-custom presets
            document.getElementById("custom_date_range_wrapper").style.display = "none";
            if (datePickerInstance) {
                  datePickerInstance.clear();
            }

            currentDateFrom = from;
            currentDateTo = to;
            updateDateRangeDisplay();
      };

      var initDateRangeFilter = function () {
            var customInput = document.getElementById("custom_date_range_input");
            if (!customInput) return;

            // Init flatpickr in range mode
            datePickerInstance = flatpickr(customInput, {
                  mode: "range",
                  dateFormat: "Y-m-d",
                  allowInput: false,
                  disableMobile: true,
                  onChange: function (selectedDates) {
                        if (selectedDates.length === 2) {
                              currentDateFrom = toYMD(selectedDates[0]);
                              currentDateTo = toYMD(selectedDates[1]);
                              updateDateRangeDisplay();
                        } else if (selectedDates.length === 1) {
                              currentDateFrom = toYMD(selectedDates[0]);
                              currentDateTo = "";
                        }
                  },
            });

            // Preset button clicks
            document.querySelectorAll(".date-preset-btn").forEach(function (btn) {
                  btn.addEventListener("click", function (e) {
                        e.preventDefault();
                        clearActivePreset();
                        this.classList.add("active");
                        applyPreset(this.getAttribute("data-preset"));
                  });
            });

            // Clear date range badge
            var clearBtn = document.getElementById("clear_date_range");
            if (clearBtn) {
                  clearBtn.addEventListener("click", function (e) {
                        e.preventDefault();
                        currentDateFrom = "";
                        currentDateTo = "";
                        clearActivePreset();
                        document.getElementById("custom_date_range_wrapper").style.display = "none";
                        if (datePickerInstance) {
                              datePickerInstance.clear();
                        }
                        updateDateRangeDisplay();
                  });
            }
      };

      // ─── Filter (payment type + show deleted + date range) ───────────────────────
      var handleFilter = function () {
            var filterForm = document.querySelector('[data-transaction-table-filter="form"]');
            if (!filterForm) return;

            var filterButton = filterForm.querySelector('[data-transaction-table-filter="filter"]');
            var resetButton = filterForm.querySelector('[data-transaction-table-filter="reset"]');
            var paymentTypeSelect = document.getElementById("payment_type_filter_select");
            var showDeletedCheckbox = document.getElementById("show_deleted_filter");

            if (filterButton) {
                  filterButton.addEventListener("click", function () {
                        currentPaymentTypeFilter = paymentTypeSelect ? paymentTypeSelect.value : "";
                        currentShowDeleted = showDeletedCheckbox ? showDeletedCheckbox.checked : false;
                        // Note: currentDateFrom / currentDateTo are already set by preset/flatpickr handlers
                        updateDeletedModeIndicator();
                        updateActiveDateIndicator();
                        if (activeDatatable) {
                              activeDatatable.ajax.reload();
                        }
                  });
            }

            if (resetButton) {
                  resetButton.addEventListener("click", function () {
                        // Payment type
                        if (paymentTypeSelect) {
                              $(paymentTypeSelect).val(null).trigger("change");
                        }
                        // Show deleted
                        if (showDeletedCheckbox) {
                              showDeletedCheckbox.checked = false;
                        }
                        // Date range
                        currentDateFrom = "";
                        currentDateTo = "";
                        clearActivePreset();
                        document.getElementById("custom_date_range_wrapper").style.display = "none";
                        if (datePickerInstance) {
                              datePickerInstance.clear();
                        }
                        updateDateRangeDisplay();

                        currentPaymentTypeFilter = "";
                        currentShowDeleted = false;

                        updateDeletedModeIndicator();
                        updateActiveDateIndicator();

                        if (activeDatatable) {
                              activeDatatable.ajax.reload();
                        }
                  });
            }
      };

      // ─── UI indicator: deleted mode ───────────────────────────────────────────────
      var updateDeletedModeIndicator = function () {
            var existing = document.getElementById("deleted_mode_indicator");
            if (currentShowDeleted) {
                  if (!existing) {
                        var cardTitle = document.querySelector(".card-title");
                        if (cardTitle) {
                              var indicator = document.createElement("span");
                              indicator.id = "deleted_mode_indicator";
                              indicator.className = "badge badge-danger ms-3";
                              indicator.innerHTML = '<i class="ki-outline ki-trash text-white me-1"></i>Showing Deleted Only';
                              cardTitle.appendChild(indicator);
                        }
                  }
            } else {
                  if (existing) existing.remove();
            }
      };

      // ─── UI indicator: active date filter ────────────────────────────────────────
      var updateActiveDateIndicator = function () {
            var existing = document.getElementById("active_date_indicator");
            if (currentDateFrom || currentDateTo) {
                  if (!existing) {
                        var cardTitle = document.querySelector(".card-title");
                        if (cardTitle) {
                              var indicator = document.createElement("span");
                              indicator.id = "active_date_indicator";
                              indicator.className = "badge badge-light-primary ms-2";
                              cardTitle.appendChild(indicator);
                        }
                        existing = document.getElementById("active_date_indicator");
                  }
                  if (existing) {
                        var label = currentDateFrom && currentDateTo
                              ? toDisplayDate(currentDateFrom) + " → " + toDisplayDate(currentDateTo)
                              : currentDateFrom
                                    ? "From " + toDisplayDate(currentDateFrom)
                                    : "To " + toDisplayDate(currentDateTo);
                        existing.innerHTML = '<i class="ki-outline ki-calendar fs-7 me-1"></i>' + label;
                  }
            } else {
                  if (existing) existing.remove();
            }
      };

      // ─── Export ──────────────────────────────────────────────────────────────────
      var handleExport = function () {
            var exportItems = document.querySelectorAll("#kt_table_report_dropdown_menu [data-row-export]");
            exportItems.forEach(function (exportItem) {
                  exportItem.addEventListener("click", function (e) {
                        e.preventDefault();
                        var exportType = this.getAttribute("data-row-export");
                        var exportBtn = document.getElementById("export_dropdown_btn");

                        if (exportBtn) {
                              exportBtn.classList.add("export-loading");
                              exportBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Exporting...';
                        }

                        fetchExportData()
                              .then(function (result) {
                                    var data = result.data;
                                    var showDeleted = result.showDeleted;
                                    switch (exportType) {
                                          case "copy": copyToClipboard(data, showDeleted); break;
                                          case "excel": exportToExcel(data, showDeleted); break;
                                          case "csv": exportToCSV(data, showDeleted); break;
                                          case "pdf": exportToPDF(data, showDeleted); break;
                                    }
                              })
                              .catch(function (error) {
                                    console.error("Export error:", error);
                                    toastr.error("Failed to export data. Please try again.");
                              })
                              .finally(function () {
                                    if (exportBtn) {
                                          exportBtn.classList.remove("export-loading");
                                          exportBtn.innerHTML = '<i class="ki-outline ki-exit-up fs-2"></i>Export';
                                    }
                              });
                  });
            });
      };

      var fetchExportData = function () {
            return new Promise(function (resolve, reject) {
                  var params = new URLSearchParams({
                        branch_id: activeBranchId || "",
                        search: currentSearchValue,
                        payment_type_filter: currentPaymentTypeFilter,
                        show_deleted: currentShowDeleted ? "1" : "0",
                        date_from: currentDateFrom,
                        date_to: currentDateTo,
                  });

                  fetch(routeExportData + "?" + params.toString(), {
                        method: "GET",
                        headers: {
                              "X-CSRF-TOKEN": csrfToken,
                              "Accept": "application/json",
                        },
                  })
                        .then(function (response) {
                              if (!response.ok) throw new Error("Network response was not ok");
                              return response.json();
                        })
                        .then(function (data) {
                              resolve({ data: data.data, showDeleted: data.show_deleted });
                        })
                        .catch(function (error) { reject(error); });
            });
      };

      // ─── Copy to clipboard ───────────────────────────────────────────────────────
      var copyToClipboard = function (data, showDeleted) {
            var headers = ["SL", "Invoice No.", "Voucher No.", "Amount (Tk)", "Payment Type", "Student", "Payment Date", "Received By"];
            if (showDeleted) headers.push("Deleted At");

            var text = headers.join("\t") + "\n";
            data.forEach(function (row) {
                  var rowData = [row.sl, row.invoice_no, row.voucher_no, row.amount_paid, row.payment_type, row.student, row.payment_date, row.received_by];
                  if (showDeleted) rowData.push(row.deleted_at || "");
                  text += rowData.join("\t") + "\n";
            });

            navigator.clipboard.writeText(text)
                  .then(function () { toastr.success("Data copied to clipboard!"); })
                  .catch(function () {
                        var ta = document.createElement("textarea");
                        ta.value = text;
                        document.body.appendChild(ta);
                        ta.select();
                        document.execCommand("copy");
                        document.body.removeChild(ta);
                        toastr.success("Data copied to clipboard!");
                  });
      };

      // ─── Export to Excel ─────────────────────────────────────────────────────────
      var exportToExcel = function (data, showDeleted) {
            var headers = ["SL", "Invoice No.", "Voucher No.", "Amount (Tk)", "Payment Type", "Student", "Payment Date", "Received By"];
            if (showDeleted) headers.push("Deleted At");

            var wsData = [headers];
            data.forEach(function (row) {
                  var rowData = [row.sl, row.invoice_no, row.voucher_no, row.amount_paid, row.payment_type, row.student, row.payment_date, row.received_by];
                  if (showDeleted) rowData.push(row.deleted_at || "");
                  wsData.push(rowData);
            });

            var ws = XLSX.utils.aoa_to_sheet(wsData);
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, showDeleted ? "Deleted Transactions" : "Transactions");

            var colWidths = [{ wch: 5 }, { wch: 15 }, { wch: 25 }, { wch: 12 }, { wch: 12 }, { wch: 30 }, { wch: 22 }, { wch: 15 }];
            if (showDeleted) colWidths.push({ wch: 22 });
            ws["!cols"] = colWidths;

            var fileName = (showDeleted ? "Deleted_" : "") + "Transactions_Report_" + new Date().toISOString().slice(0, 10) + ".xlsx";
            XLSX.writeFile(wb, fileName);
            toastr.success("Excel file downloaded successfully!");
      };

      // ─── Export to CSV ───────────────────────────────────────────────────────────
      var exportToCSV = function (data, showDeleted) {
            var headers = ["SL", "Invoice No.", "Voucher No.", "Amount (Tk)", "Payment Type", "Student", "Payment Date", "Received By"];
            if (showDeleted) headers.push("Deleted At");

            var wsData = [headers];
            data.forEach(function (row) {
                  var rowData = [row.sl, row.invoice_no, row.voucher_no, row.amount_paid, row.payment_type, row.student, row.payment_date, row.received_by];
                  if (showDeleted) rowData.push(row.deleted_at || "");
                  wsData.push(rowData);
            });

            var ws = XLSX.utils.aoa_to_sheet(wsData);
            var csv = XLSX.utils.sheet_to_csv(ws);
            var blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
            var link = document.createElement("a");
            var fileName = (showDeleted ? "Deleted_" : "") + "Transactions_Report_" + new Date().toISOString().slice(0, 10) + ".csv";
            link.href = URL.createObjectURL(blob);
            link.download = fileName;
            link.click();
            URL.revokeObjectURL(link.href);
            toastr.success("CSV file downloaded successfully!");
      };

      // ─── Export to PDF ───────────────────────────────────────────────────────────
      var exportToPDF = function (data, showDeleted) {
            var { jsPDF } = window.jspdf;
            var doc = new jsPDF("l", "mm", "a4");

            var headerRow = ["SL", "Invoice No.", "Voucher No.", "Amount", "Type", "Student", "Payment Date", "Received By"];
            if (showDeleted) headerRow.push("Deleted At");

            var rows = data.map(function (row) {
                  var rowData = [row.sl, row.invoice_no, row.voucher_no, row.amount_paid, row.payment_type, row.student, row.payment_date, row.received_by];
                  if (showDeleted) rowData.push(row.deleted_at || "");
                  return rowData;
            });

            var title = (showDeleted ? "Deleted " : "") + "Transactions Report";
            doc.setFontSize(16);
            doc.text(title, 14, 15);
            doc.setFontSize(10);
            doc.text("Generated on: " + new Date().toLocaleString(), 14, 22);

            // Date range subtitle
            if (currentDateFrom || currentDateTo) {
                  var rangeLabel = currentDateFrom && currentDateTo
                        ? "Period: " + toDisplayDate(currentDateFrom) + " → " + toDisplayDate(currentDateTo)
                        : currentDateFrom
                              ? "From: " + toDisplayDate(currentDateFrom)
                              : "To: " + toDisplayDate(currentDateTo);
                  doc.setFontSize(9);
                  doc.text(rangeLabel, 14, 28);
            }

            var colStyles = {
                  0: { cellWidth: 10 }, 1: { cellWidth: 25 }, 2: { cellWidth: 35 },
                  3: { cellWidth: 18 }, 4: { cellWidth: 18 }, 5: { cellWidth: 45 },
                  6: { cellWidth: 35 }, 7: { cellWidth: 22 },
            };
            if (showDeleted) colStyles[8] = { cellWidth: 35 };

            doc.autoTable({
                  head: [headerRow],
                  body: rows,
                  startY: (currentDateFrom || currentDateTo) ? 33 : 28,
                  styles: { fontSize: 7, cellPadding: 2 },
                  headStyles: { fillColor: showDeleted ? [220, 53, 69] : [41, 128, 185], textColor: 255, fontStyle: "bold" },
                  alternateRowStyles: { fillColor: showDeleted ? [255, 235, 238] : [245, 245, 245] },
                  columnStyles: colStyles,
                  didDrawPage: function () {
                        doc.setFontSize(8);
                        doc.text("Page " + doc.internal.getNumberOfPages(), doc.internal.pageSize.width - 20, doc.internal.pageSize.height - 10);
                  },
            });

            var fileName = (showDeleted ? "Deleted_" : "") + "Transactions_Report_" + new Date().toISOString().slice(0, 10) + ".pdf";
            doc.save(fileName);
            toastr.success("PDF file downloaded successfully!");
      };

      // ─── Delete ──────────────────────────────────────────────────────────────────
      var handleDeletion = function () {
            document.addEventListener("click", function (e) {
                  var deleteBtn = e.target.closest(".delete-txn");
                  if (!deleteBtn) return;
                  e.preventDefault();

                  var txnId = deleteBtn.getAttribute("data-txn-id");
                  var isApproved = deleteBtn.getAttribute("data-is-approved") === "1";
                  var url = routeDeleteTxn.replace(":id", txnId);

                  var warningTitle = isApproved ? "Delete Successful Transaction?" : "Are you sure you want to delete?";
                  var warningText = isApproved
                        ? "This transaction has been successful. Deleting it will:\n\n• Reverse the wallet collection\n• Restore the invoice amount due\n• Create an adjustment log\n• Decrease the collector's total collected amount\n\nNote: Successful transactions can only be deleted within 24 hours of creation.\n\nThis action cannot be undone."
                        : "Once deleted, this unapproved transaction will be removed.";

                  Swal.fire({
                        title: warningTitle,
                        text: warningText,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#d33",
                        cancelButtonColor: "#6c757d",
                        confirmButtonText: isApproved ? "Yes, delete and reverse" : "Yes, delete it",
                        cancelButtonText: "Cancel",
                        customClass: { popup: "swal-wide" },
                  }).then(function (result) {
                        if (!result.isConfirmed) return;

                        var originalContent = deleteBtn.innerHTML;
                        deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
                        deleteBtn.style.pointerEvents = "none";

                        fetch(url, {
                              method: "DELETE",
                              headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
                        })
                              .then(function (r) { return r.json(); })
                              .then(function (data) {
                                    if (data.success) {
                                          Swal.fire({ title: "Deleted!", text: data.message || "Transaction deleted successfully.", icon: "success", confirmButtonText: "Okay" })
                                                .then(function () {
                                                      if (activeDatatable) activeDatatable.ajax.reload(null, false);
                                                });
                                    } else {
                                          deleteBtn.innerHTML = originalContent;
                                          deleteBtn.style.pointerEvents = "auto";
                                          Swal.fire({ title: "Failed!", text: data.message || "Transaction could not be deleted.", icon: "error" });
                                    }
                              })
                              .catch(function (error) {
                                    console.error("Fetch Error:", error);
                                    deleteBtn.innerHTML = originalContent;
                                    deleteBtn.style.pointerEvents = "auto";
                                    Swal.fire({ title: "Error!", text: "An error occurred. Please try again or contact support.", icon: "error" });
                              });
                  });
            });
      };

      // ─── Approve ─────────────────────────────────────────────────────────────────
      var handleApproval = function () {
            document.addEventListener("click", function (e) {
                  var approveBtn = e.target.closest(".approve-txn");
                  if (!approveBtn) return;
                  e.preventDefault();

                  var txnId = approveBtn.getAttribute("data-txn-id");

                  Swal.fire({
                        title: "Are you sure?",
                        text: "Do you want to approve this transaction?",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Yes, approve!",
                  }).then(function (result) {
                        if (!result.isConfirmed) return;

                        var originalContent = approveBtn.innerHTML;
                        approveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
                        approveBtn.style.pointerEvents = "none";

                        fetch("/transactions/" + txnId + "/approve", {
                              method: "POST",
                              headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": csrfToken },
                        })
                              .then(function (r) { return r.json(); })
                              .then(function (data) {
                                    if (data.success) {
                                          Swal.fire({ title: "Approved!", text: "Transaction approved successfully.", icon: "success" })
                                                .then(function () {
                                                      if (activeDatatable) activeDatatable.ajax.reload(null, false);
                                                });
                                    } else {
                                          approveBtn.innerHTML = originalContent;
                                          approveBtn.style.pointerEvents = "auto";
                                          Swal.fire({ title: "Error!", text: data.message, icon: "warning" });
                                    }
                              })
                              .catch(function (error) {
                                    console.error("Fetch Error:", error);
                                    approveBtn.innerHTML = originalContent;
                                    approveBtn.style.pointerEvents = "auto";
                                    Swal.fire({ title: "Error!", text: "Something went wrong. Please try again.", icon: "error" });
                              });
                  });
            });
      };

      // ─── Statement Download ──────────────────────────────────────────────────────
      var handleStatementDownload = function () {
            document.addEventListener("click", function (e) {
                  var downloadBtn = e.target.closest(".download-statement");
                  if (!downloadBtn) return;
                  e.preventDefault();

                  var studentId = downloadBtn.getAttribute("data-student-id");
                  var year = downloadBtn.getAttribute("data-year");
                  var invoiceId = downloadBtn.getAttribute("data-invoice-id");

                  if (!studentId || !year) {
                        Swal.fire({ title: "Error!", text: "Missing student or year information.", icon: "error" });
                        return;
                  }

                  var originalIcon = downloadBtn.innerHTML;
                  downloadBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                  downloadBtn.style.pointerEvents = "none";

                  var formData = new FormData();
                  formData.append("student_id", studentId);
                  formData.append("statement_year", year);
                  formData.append("invoice_id", invoiceId);

                  fetch(routeDownloadStatement, {
                        method: "POST",
                        headers: { "X-CSRF-TOKEN": csrfToken },
                        body: formData,
                  })
                        .then(function (response) {
                              if (!response.ok) {
                                    return response.text().then(function (text) { throw new Error(text || "Server error occurred"); });
                              }
                              return response.text();
                        })
                        .then(function (html) {
                              var printWindow = window.open("", "_blank", "width=900,height=700,scrollbars=yes,resizable=yes");
                              if (printWindow) {
                                    printWindow.document.open();
                                    printWindow.document.write(html);
                                    printWindow.document.close();
                                    printWindow.focus();
                              } else {
                                    Swal.fire({ title: "Popup Blocked!", text: "Please allow popups for this website to view the statement.", icon: "warning" });
                              }
                              downloadBtn.innerHTML = originalIcon;
                              downloadBtn.style.pointerEvents = "auto";
                        })
                        .catch(function (error) {
                              console.error("Statement Download Error:", error);
                              var msg = error.message.toLowerCase();
                              if (msg.includes("no transactions")) {
                                    Swal.fire({ title: "No Data Found", text: "No transactions found for the selected year.", icon: "info" });
                              } else {
                                    Swal.fire({ title: "Error!", text: "Failed to load statement. Please try again.", icon: "error" });
                              }
                              downloadBtn.innerHTML = originalIcon;
                              downloadBtn.style.pointerEvents = "auto";
                        });
            });
      };

      // ─── Refresh ─────────────────────────────────────────────────────────────────
      var refreshTable = function () {
            if (activeDatatable) {
                  activeDatatable.ajax.reload(null, false);
            }
      };

      // ─── Public API ──────────────────────────────────────────────────────────────
      return {
            init: function () {
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
                  initDateRangeFilter();
            },
            getActiveDatatable: function () { return activeDatatable; },
            refreshTable: refreshTable,
      };
})();

// =============================================================================
// KTAddTransaction — Add Transaction Modal
// =============================================================================
var KTAddTransaction = (function () {
      var element = document.getElementById("kt_modal_add_transaction");
      if (!element) {
            return { init: function () { } };
      }

      var form = element.querySelector("#kt_modal_add_transaction_form");
      var modal = bootstrap.Modal.getOrCreateInstance(element);
      var branchSelect = document.getElementById("transaction_branch_select");
      var studentSelect = document.getElementById("transaction_student_select");
      var invoiceSelect = document.getElementById("student_due_invoice_select");
      var amountInput = document.getElementById("transaction_amount_input");

      var invoices = [];
      var isPartiallyPaidInvoice = false;

      var formatMonthYear = function (raw) {
            if (!raw) return "";
            var parts = raw.split("_");
            var month = parseInt(parts[0], 10);
            var year = parts[1];
            var names = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            return (month >= 1 && month <= 12 && year) ? names[month - 1] + " " + year : raw;
      };

      var getRemarksInput = function () {
            return form ? form.querySelector('input[name="transaction_remarks"]') : null;
      };

      var handleBranchSelect = function () {
            if (!branchSelect) return;
            $(branchSelect).on("change", function () {
                  var branchId = $(this).val();
                  var $stu = $(studentSelect);
                  $stu.empty().append('<option value="">Select a student</option>');
                  $(invoiceSelect).empty().append('<option value="">Select Due Invoice</option>');
                  $(amountInput).val("").prop("disabled", true);
                  if (!branchId) return;
                  if (typeof studentsByBranch !== "undefined" && studentsByBranch[branchId]) {
                        studentsByBranch[branchId].forEach(function (student) {
                              $stu.append('<option value="' + student.id + '">' + student.name + ' (' + student.student_unique_id + ')</option>');
                        });
                  }
            });
      };

      var handleStudentSelect = function () {
            $(studentSelect).on("change", function () {
                  var studentId = $(this).val();
                  if (!studentId) return;
                  $.ajax({
                        url: "/students/" + studentId + "/due-invoices",
                        method: "GET",
                        success: function (response) {
                              invoices = response;
                              var $inv = $(invoiceSelect);
                              $inv.empty().append('<option value="">Select Due Invoice</option>');
                              if (response.length === 0) {
                                    $inv.append('<option disabled>No due invoices found</option>');
                              } else {
                                    response.forEach(function (invoice) {
                                          var total = Number(invoice.total_amount).toLocaleString("en-BD");
                                          var due = Number(invoice.amount_due).toLocaleString("en-BD");
                                          var label = invoice.month_year ? formatMonthYear(invoice.month_year) : (invoice.invoice_type || "Unknown");
                                          $inv.append('<option value="' + invoice.id + '">' + invoice.invoice_number + ' (' + label + ') - Total: ৳' + total + ', Due: ৳' + due + '</option>');
                                    });
                              }
                              $(amountInput).val("").prop("disabled", true).removeClass("is-invalid");
                              $("#transaction_amount_error").remove();
                              $('input[name="transaction_type"]').prop("disabled", false);
                        },
                        error: function () { alert("Failed to load due invoices. Please try again."); },
                  });
            });
      };

      var handleInvoiceSelect = function () {
            $(invoiceSelect).on("change", function () {
                  var selectedId = $(this).val();
                  var invoice = invoices.find(function (inv) { return inv.id == selectedId; });
                  if (!invoice) return;

                  var $amt = $(amountInput);
                  $amt.val(invoice.amount_due).prop("disabled", false).data("max", invoice.amount_due).attr("min", 1);

                  var $full = $('input[name="transaction_type"][value="full"]');
                  var $partial = $('input[name="transaction_type"][value="partial"]');

                  if (invoice.amount_due < invoice.total_amount) {
                        isPartiallyPaidInvoice = true;
                        $full.prop("disabled", true).prop("checked", false);
                        $partial.prop("checked", true);
                        $amt.val("");
                  } else {
                        isPartiallyPaidInvoice = false;
                        $full.prop("disabled", false);
                        $partial.prop("disabled", false);
                        $full.prop("checked", true);
                        $amt.val(invoice.amount_due);
                  }
            });
      };

      var updateRemarksLabel = function (isRequired) {
            var label = form ? form.querySelector('label[for="transaction_remarks_input"]') : null;
            var optionalSpan = form ? form.querySelector('#transaction_remarks_optional') : null;
            if (isRequired) {
                  if (optionalSpan) optionalSpan.style.display = "none";
                  if (label && !label.classList.contains("required")) label.classList.add("required");
            } else {
                  if (optionalSpan) optionalSpan.style.display = "";
                  if (label) label.classList.remove("required");
            }
      };

      var handlePaymentTypeChange = function () {
            $('input[name="transaction_type"]').on("change", function () {
                  var paymentType = $(this).val();
                  var $amt = $(amountInput);
                  var selectedId = $(invoiceSelect).val();
                  var invoice = invoices.find(function (inv) { return inv.id == selectedId; });
                  var remarksInput = getRemarksInput();

                  if (paymentType === "discounted") {
                        $amt.attr("min", 0);
                        if (invoice) $amt.val("");
                        updateRemarksLabel(true);
                  } else {
                        $amt.attr("min", 1);
                        if (invoice) $amt.val(paymentType === "partial" ? "" : invoice.amount_due);
                        updateRemarksLabel(false);
                        if (remarksInput) {
                              $(remarksInput).removeClass("is-invalid");
                              $("#transaction_remarks_error").remove();
                        }
                  }
            });
      };

      var validateRemarks = function (showError) {
            var paymentType = $('input[name="transaction_type"]:checked').val();
            var remarksInput = getRemarksInput();
            if (!remarksInput) return true;
            if (paymentType !== "discounted") {
                  $(remarksInput).removeClass("is-invalid");
                  $("#transaction_remarks_error").remove();
                  return true;
            }
            if (remarksInput.value.trim().length === 0) {
                  if (showError) {
                        $(remarksInput).addClass("is-invalid");
                        if (!document.getElementById("transaction_remarks_error")) {
                              $(remarksInput).after('<div class="invalid-feedback" id="transaction_remarks_error">Remarks is required for discounted payment.</div>');
                        }
                  }
                  return false;
            }
            $(remarksInput).removeClass("is-invalid");
            $("#transaction_remarks_error").remove();
            return true;
      };

      var handleAmountValidation = function () {
            $(amountInput).on("input", function () {
                  var amount = parseFloat($(this).val());
                  var maxAmount = parseFloat($(this).data("max"));
                  var paymentType = $('input[name="transaction_type"]:checked').val();
                  $(this).removeClass("is-invalid");
                  $("#transaction_amount_error").remove();

                  var isValid = true, errorMessage = "";
                  if (isNaN(amount)) {
                        isValid = false; errorMessage = "Please enter a valid number";
                  } else if (paymentType === "discounted") {
                        if (amount < 0) { isValid = false; errorMessage = "Amount cannot be negative"; }
                        else if (amount >= maxAmount) { isValid = false; errorMessage = "For discounted payment, amount must be less than the due amount of ৳" + maxAmount; }
                  } else if (amount < 1) {
                        isValid = false; errorMessage = "Amount must be at least ৳1";
                  } else if (paymentType === "partial" && !isPartiallyPaidInvoice && amount >= maxAmount) {
                        isValid = false; errorMessage = "For partial payment, amount must be less than the due amount of ৳" + maxAmount;
                  } else if (paymentType === "partial" && isPartiallyPaidInvoice && amount > maxAmount) {
                        isValid = false; errorMessage = "Amount must be less than or equal to the due amount of ৳" + maxAmount;
                  } else if (paymentType === "full" && amount != maxAmount) {
                        isValid = false; errorMessage = "For full payment, amount must be exactly ৳" + maxAmount;
                  }

                  if (!isValid) {
                        $(this).addClass("is-invalid");
                        $(this).after('<div class="invalid-feedback" id="transaction_amount_error">' + errorMessage + '</div>');
                  }
            });
      };

      var handleRemarksValidation = function () {
            var remarksInput = getRemarksInput();
            if (!remarksInput) return;
            $(remarksInput).on("input", function () {
                  if ($('input[name="transaction_type"]:checked').val() === "discounted" && this.value.trim().length > 0) {
                        $(this).removeClass("is-invalid");
                        $("#transaction_remarks_error").remove();
                  }
            });
      };

      var handleFormSubmit = function () {
            $(form).on("submit", function (e) {
                  e.preventDefault();
                  var amount = parseFloat($(amountInput).val());
                  var maxAmount = parseFloat($(amountInput).data("max"));
                  var paymentType = $('input[name="transaction_type"]:checked').val();

                  var isValid = true;
                  if (isNaN(amount)) {
                        isValid = false;
                  } else if (paymentType === "discounted") {
                        if (amount < 0 || amount >= maxAmount) isValid = false;
                  } else if (amount < 1) {
                        isValid = false;
                  } else if (paymentType === "partial" && !isPartiallyPaidInvoice && amount >= maxAmount) {
                        isValid = false;
                  } else if (paymentType === "partial" && isPartiallyPaidInvoice && amount > maxAmount) {
                        isValid = false;
                  } else if (paymentType === "full" && amount != maxAmount) {
                        isValid = false;
                  }

                  if (!isValid || $(amountInput).hasClass("is-invalid")) {
                        toastr.warning("Please enter a valid amount.");
                        return false;
                  }
                  if (!validateRemarks(true)) {
                        toastr.warning("Remarks is required for discounted payment.");
                        return false;
                  }

                  var submitBtn = form.querySelector('[data-kt-add-transaction-modal-action="submit"]');
                  submitBtn.setAttribute("data-kt-indicator", "on");
                  submitBtn.disabled = true;

                  fetch(form.action, {
                        method: "POST",
                        headers: { "X-CSRF-TOKEN": csrfToken, "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" },
                        body: new FormData(form),
                  })
                        .then(function (response) {
                              if (!response.ok) return response.json().then(function (err) { throw err; });
                              return response.json();
                        })
                        .then(function (data) {
                              if (data.success) {
                                    toastr.success(data.message || "Transaction recorded successfully.");
                                    var txn = data.transaction;
                                    resetForm();
                                    modal.hide();

                                    if (txn && txn.is_approved) {
                                          Swal.fire({
                                                title: "Transaction Successful!",
                                                text: "Do you want to download the payment statement?",
                                                icon: "success",
                                                showCancelButton: true,
                                                confirmButtonColor: "#3085d6",
                                                cancelButtonColor: "#6c757d",
                                                confirmButtonText: "Yes, download",
                                                cancelButtonText: "No, just reload",
                                          }).then(function (result) {
                                                if (result.isConfirmed) {
                                                      downloadStatementAndRefresh(txn.student_id, txn.year, txn.invoice_id);
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
                                          }).then(function () {
                                                KTAllTransactionsList.refreshTable();
                                          });
                                    }
                              } else {
                                    toastr.error(data.message || "Failed to record transaction.");
                                    submitBtn.removeAttribute("data-kt-indicator");
                                    submitBtn.disabled = false;
                              }
                        })
                        .catch(function (error) {
                              console.error("Transaction Error:", error);
                              var errorMessage = error.message || (error.errors ? Object.values(error.errors).flat().join("\n") : "An error occurred. Please try again.");
                              toastr.error(errorMessage);
                              submitBtn.removeAttribute("data-kt-indicator");
                              submitBtn.disabled = false;
                        });

                  return false;
            });
      };

      var downloadStatementAndRefresh = function (studentId, year, invoiceId) {
            var formData = new FormData();
            formData.append("student_id", studentId);
            formData.append("statement_year", year);
            formData.append("invoice_id", invoiceId);

            fetch(routeDownloadStatement, {
                  method: "POST",
                  headers: { "X-CSRF-TOKEN": csrfToken },
                  body: formData,
            })
                  .then(function (response) {
                        if (!response.ok) throw new Error("Failed to load statement");
                        return response.text();
                  })
                  .then(function (html) {
                        var printWindow = window.open("", "_blank", "width=900,height=700,scrollbars=yes,resizable=yes");
                        if (printWindow) {
                              printWindow.document.open();
                              printWindow.document.write(html);
                              printWindow.document.close();
                              printWindow.focus();
                        } else {
                              Swal.fire({ title: "Popup Blocked!", text: "Please allow popups for this website to view the statement.", icon: "warning" });
                        }
                        KTAllTransactionsList.refreshTable();
                  })
                  .catch(function (error) {
                        console.error("Statement Download Error:", error);
                        toastr.error("Failed to download statement.");
                        KTAllTransactionsList.refreshTable();
                  });
      };

      var resetForm = function () {
            if (form) form.reset();
            if (branchSelect && $(branchSelect).data("select2")) $(branchSelect).val(null).trigger("change");
            if (studentSelect && $(studentSelect).data("select2")) $(studentSelect).val(null).trigger("change");
            if (invoiceSelect && $(invoiceSelect).data("select2")) $(invoiceSelect).val(null).trigger("change");
            if (amountInput) { amountInput.value = ""; amountInput.disabled = true; }
            $(amountInput).removeClass("is-invalid");
            $("#transaction_amount_error").remove();
            var remarksInput = getRemarksInput();
            if (remarksInput) $(remarksInput).removeClass("is-invalid");
            $("#transaction_remarks_error").remove();
            updateRemarksLabel(false);
            invoices = [];
            isPartiallyPaidInvoice = false;
      };

      var handleCloseModal = function () {
            var cancelButton = element.querySelector('[data-kt-add-transaction-modal-action="cancel"]');
            if (cancelButton) {
                  cancelButton.addEventListener("click", function (e) { e.preventDefault(); resetForm(); modal.hide(); });
            }
            var closeButton = element.querySelector('[data-kt-add-transaction-modal-action="close"]');
            if (closeButton) {
                  closeButton.addEventListener("click", function (e) { e.preventDefault(); resetForm(); modal.hide(); });
            }
      };

      return {
            init: function () {
                  handleBranchSelect();
                  handleStudentSelect();
                  handleInvoiceSelect();
                  handlePaymentTypeChange();
                  handleAmountValidation();
                  handleRemarksValidation();
                  handleFormSubmit();
                  handleCloseModal();
            },
      };
})();

// ─── Boot ─────────────────────────────────────────────────────────────────────
KTUtil.onDOMContentLoaded(function () {
      KTAllTransactionsList.init();
      KTAddTransaction.init();
});