"use strict";

/**
 * Attendance Report Module
 * For Metronic 8.2.6 + Bootstrap 5.3
 *
 * This file handles:
 * - Date Range Picker initialization
 * - Branch-Batch dynamic loading
 * - DataTable with aggregation
 * - Form validation
 * - Export functionality
 */

/**
 * Module: Date Range Picker
 */
var KTDateRangePicker = (function () {
      var input;
      var defaultStart, defaultEnd;

      // Init daterangepicker
      var initDaterangepicker = function () {
            defaultStart = moment().startOf("month");
            defaultEnd = moment().endOf("month");

            input = $("#attendance_daterangepicker");
            var hiddenInput = $("#date_range_value");

            function cb(start, end) {
                  var displayFormat = start.format("DD-MM-YYYY") + " - " + end.format("DD-MM-YYYY");
                  var valueFormat = start.format("DD-MM-YYYY") + " - " + end.format("DD-MM-YYYY");

                  input.val(displayFormat);
                  if (hiddenInput.length) {
                        hiddenInput.val(valueFormat);
                  }
            }

            input.daterangepicker({
                  startDate: defaultStart,
                  endDate: defaultEnd,
                  locale: {
                        format: "DD-MM-YYYY"
                  },
                  ranges: {
                        "Today": [moment(), moment()],
                        "Yesterday": [moment().subtract(1, "days"), moment().subtract(1, "days")],
                        "Last 7 Days": [moment().subtract(6, "days"), moment()],
                        "Last 30 Days": [moment().subtract(29, "days"), moment()],
                        "This Month": [moment().startOf("month"), moment().endOf("month")],
                        "Last Month": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")]
                  }
            }, cb);

            cb(defaultStart, defaultEnd);
      };

      return {
            init: function () {
                  initDaterangepicker();
            }
      };
})();

/**
 * Module: Branch-Batch Loader
 * Handles dynamic batch loading based on branch selection
 */
var KTBranchBatchLoader = (function () {
      var branchSelect, batchSelect;
      var config;

      // Get the URL for fetching batches
      function getBatchesUrl(branchId) {
            return config.getBatchesUrl.replace(':branchId', branchId);
      }

      // Load batches for a given branch ID
      function loadBatches(branchId) {
            if (!branchId) {
                  clearBatches();
                  disableBatchSelect();
                  return;
            }

            // Show loading state
            if (batchSelect.tagName === 'SELECT') {
                  $(batchSelect).prop('disabled', true);

                  // Clear existing options and show loading
                  $(batchSelect).empty().append('<option value="">Loading batches...</option>');

                  // Trigger select2 update if applicable
                  if ($(batchSelect).hasClass('select2-hidden-accessible')) {
                        $(batchSelect).trigger('change');
                  }
            }

            // Make AJAX request
            $.ajax({
                  url: getBatchesUrl(branchId),
                  type: 'GET',
                  dataType: 'json',
                  success: function (response) {
                        populateBatches(response.batches || []);
                        enableBatchSelect();
                  },
                  error: function (xhr, status, error) {
                        console.error('Error loading batches:', error);

                        var errorMessage = 'Error loading batches';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                              errorMessage = xhr.responseJSON.message;
                        }

                        if (typeof toastr !== 'undefined') {
                              toastr.error(errorMessage);
                        }

                        clearBatches();
                        disableBatchSelect();
                  }
            });
      }

      // Populate batch dropdown with options
      function populateBatches(batches) {
            if (batchSelect.tagName !== 'SELECT') return;

            $(batchSelect).empty();
            $(batchSelect).append('<option value="">Select batch</option>');

            if (batches && batches.length > 0) {
                  batches.forEach(function (batch) {
                        $(batchSelect).append(
                              $('<option></option>')
                                    .val(batch.id)
                                    .text(batch.name)
                        );
                  });
            }

            // Trigger select2 update if applicable
            if ($(batchSelect).hasClass('select2-hidden-accessible')) {
                  $(batchSelect).trigger('change');
            }
      }

      // Clear batch dropdown
      function clearBatches() {
            if (batchSelect.tagName !== 'SELECT') return;

            $(batchSelect).empty();
            $(batchSelect).append('<option value="">Select batch</option>');

            if ($(batchSelect).hasClass('select2-hidden-accessible')) {
                  $(batchSelect).trigger('change');
            }
      }

      // Enable batch select
      function enableBatchSelect() {
            if (batchSelect.tagName !== 'SELECT') return;
            $(batchSelect).prop('disabled', false);
      }

      // Disable batch select
      function disableBatchSelect() {
            if (batchSelect.tagName !== 'SELECT') return;
            $(batchSelect).prop('disabled', true);
      }

      // Initialize branch change listener (admin only)
      function initBranchChangeListener() {
            if (!config.isAdmin) return;

            $(branchSelect).on('change', function () {
                  var branchId = $(this).val();
                  loadBatches(branchId);
            });
      }

      // Initialize for non-admin users
      function initNonAdminBatches() {
            if (config.isAdmin) return;

            // For non-admin users, branch is already set via hidden input
            // Batches are pre-loaded from server
            if (batchSelect && batchSelect.tagName === 'SELECT') {
                  enableBatchSelect();
            }
      }

      return {
            init: function () {
                  config = window.AttendanceReportConfig || {};
                  branchSelect = document.getElementById('student_branch_group');
                  batchSelect = document.getElementById('student_batch_group');

                  if (!batchSelect) {
                        console.warn('KTBranchBatchLoader: batch select element not found');
                        return;
                  }

                  if (config.isAdmin) {
                        // Admin: disable batch initially, enable after branch selection
                        disableBatchSelect();
                        initBranchChangeListener();
                  } else {
                        // Non-admin: batches already loaded from server
                        initNonAdminBatches();
                  }
            },

            // Public method to reload batches
            reloadBatches: function (branchId) {
                  loadBatches(branchId);
            }
      };
})();

/**
 * Module: Select2 Input Group Fix
 * Fixes Select2 width issues within Bootstrap input-groups
 */
var KTSelect2InputGroupFix = (function () {

      function applyFix() {
            // Find all Select2 containers within input-groups
            $('.input-group .select2-container').each(function () {
                  var $container = $(this);
                  var $inputGroup = $container.closest('.input-group');

                  $container.css({
                        'flex': '1 1 auto',
                        'width': 'auto',
                        'min-width': '0'
                  });

                  $container.find('.select2-selection').css({
                        'height': '100%',
                        'min-height': '43.5px',
                        'display': 'flex',
                        'align-items': 'center'
                  });
            });
      }

      function initObserver() {
            var observer = new MutationObserver(function (mutations) {
                  mutations.forEach(function (mutation) {
                        if (mutation.addedNodes.length) {
                              mutation.addedNodes.forEach(function (node) {
                                    if (node.classList && node.classList.contains('select2-container')) {
                                          applyFix();
                                    }
                              });
                        }
                  });
            });

            var form = document.getElementById('student_list_filter_form');
            if (form) {
                  observer.observe(form, { childList: true, subtree: true });
            }
      }

      return {
            init: function () {
                  $(document).ready(function () {
                        setTimeout(applyFix, 100);

                        $(document).on('select2:open', function () {
                              setTimeout(applyFix, 50);
                        });
                  });

                  initObserver();
            }
      };
})();

/**
 * KTAttendanceReportTable - Handles DataTable, FormValidation, report generation
 */
var KTAttendanceReportTable = (function () {
      // Module-level state
      var table, datatable, validator;
      var DATA_URL = "/reports/attendance/data";

      var form, submitButton, tableBody, dateInput, branchSelect, classSelect, batchSelect;

      var exportListenerAttached = false;
      var searchListenerAttached = false;
      var dtButtons = null;
      var config;

      // Logging helpers
      function log() { if (window.console && console.log) console.log.apply(console, arguments); }
      function warn() { if (window.console && console.warn) console.warn.apply(console, arguments); }
      function error() { if (window.console && console.error) console.error.apply(console, arguments); }

      // Helper to create <td> with text safely
      function tdWithText(text) {
            var td = document.createElement("td");
            td.textContent = text === undefined || text === null ? "" : text;
            return td;
      }

      // Ensure tbody exists before initializing DataTable
      function ensureTbodyExists() {
            try {
                  if (!table) return;
                  var existing = table.querySelector("tbody");
                  if (!existing) {
                        var newT = document.createElement("tbody");
                        newT.id = "kt_attendance_report_table_body";
                        newT.className = "text-gray-600 fw-semibold";
                        var r = "<tr>";
                        for (var i = 0; i < 8; i++) r += "<td></td>";
                        r += "</tr>";
                        newT.innerHTML = r;
                        table.appendChild(newT);
                        tableBody = newT;
                        log("Created missing <tbody> for table.");
                  } else {
                        tableBody = existing;
                  }
            } catch (e) {
                  warn("ensureTbodyExists error:", e);
            }
      }

      // DataTable initialization (destroy safe)
      function initDatatable() {
            try {
                  if (!table) { warn("initDatatable: missing table element"); return; }
                  if ($.fn.dataTable.isDataTable(table)) {
                        try { datatable.destroy(); } catch (e) { warn("datatable.destroy() failed:", e); }
                  }
            } catch (e) { /* ignore */ }

            ensureTbodyExists();

            datatable = $(table).DataTable({
                  info: true,
                  order: [],
                  lengthMenu: [10, 25, 50, 100],
                  pageLength: 10,
                  lengthChange: true,
                  autoWidth: false,
                  columnDefs: [
                        { targets: -1, orderable: false } // Disable sorting on Actions column
                  ]
            });

            log("DataTable initialized.");
      }

      // Export Buttons
      function exportButtons() {
            try { if (dtButtons && typeof dtButtons.destroy === "function") dtButtons.destroy(); } catch (e) { /* ignore */ }
            try { $(".dt-buttons").remove(); } catch (e) { /* ignore */ }
            try { $("#kt_hidden_export_buttons").empty(); } catch (e) { /* ignore */ }

            if (!document.getElementById("kt_hidden_export_buttons")) {
                  var div = document.createElement("div");
                  div.id = "kt_hidden_export_buttons";
                  div.style.display = "none";
                  document.body.appendChild(div);
            }

            var documentTitle = "Attendance Report";

            try {
                  if (datatable && $.fn.dataTable && $.fn.dataTable.Buttons) {
                        dtButtons = new $.fn.dataTable.Buttons(datatable, {
                              buttons: [
                                    { extend: "copyHtml5", className: "buttons-copy", title: documentTitle, exportOptions: { columns: ":visible:not(.not-export)" } },
                                    { extend: "excelHtml5", className: "buttons-excel", title: documentTitle, exportOptions: { columns: ":visible:not(.not-export)" } },
                                    { extend: "csvHtml5", className: "buttons-csv", title: documentTitle, exportOptions: { columns: ":visible:not(.not-export)" } },
                                    {
                                          extend: "pdfHtml5", className: "buttons-pdf", title: documentTitle, exportOptions: { columns: ":visible:not(.not-export)" },
                                          customize: function (doc) {
                                                doc.pageMargins = [20, 20, 20, 40];
                                                doc.defaultStyle.fontSize = 10;
                                                if (typeof getPdfFooterWithPrintTime === "function") doc.footer = getPdfFooterWithPrintTime();
                                          }
                                    }
                              ]
                        });
                        try { dtButtons.container().appendTo("#kt_hidden_export_buttons"); } catch (e) { /* ignore */ }
                        log("dtButtons created.");
                  } else {
                        dtButtons = null;
                        log("Buttons extension not present — using fallback exports.");
                  }
            } catch (e) {
                  dtButtons = null;
                  warn("exportButtons: dtButtons creation error:", e);
            }

            // Fallback export utilities
            function buildCsv() {
                  var rows = [];
                  try {
                        var dt = $(table).DataTable();
                        var data = dt.rows({ search: "applied", page: "all" }).data().toArray();
                        data.forEach(function (r) {
                              var row = r.map(function (c) {
                                    if (typeof c === "string") return c.replace(/<[^>]*>/g, "").trim();
                                    return String(c);
                              });
                              rows.push(row);
                        });
                  } catch (e) {
                        table.querySelectorAll("tbody tr").forEach(function (tr) {
                              var cols = [];
                              tr.querySelectorAll("td,th").forEach(function (cell) { cols.push(cell.textContent.trim()); });
                              rows.push(cols);
                        });
                  }
                  var header = [];
                  table.querySelectorAll("thead th").forEach(function (th) { header.push(th.textContent.trim()); });
                  if (header.length) rows.unshift(header);
                  return rows.map(function (r) { return r.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(","); }).join("\r\n");
            }

            function triggerDownload(filename, text, mime) {
                  var blob = new Blob([text], { type: mime || "text/csv;charset=utf-8;" });
                  var link = document.createElement("a");
                  link.href = URL.createObjectURL(blob);
                  link.download = filename;
                  document.body.appendChild(link);
                  link.click();
                  setTimeout(function () { document.body.removeChild(link); URL.revokeObjectURL(link.href); }, 150);
            }

            function copyToClipboard(text) {
                  if (navigator.clipboard && navigator.clipboard.writeText) return navigator.clipboard.writeText(text);
                  var ta = document.createElement("textarea"); ta.value = text; document.body.appendChild(ta); ta.select();
                  try { document.execCommand("copy"); } catch (e) { warn("copy execCommand failed", e); }
                  document.body.removeChild(ta); return Promise.resolve();
            }

            if (!exportListenerAttached) {
                  var menu = document.getElementById("kt_table_report_dropdown_menu");
                  if (menu) {
                        menu.addEventListener("click", function (ev) {
                              var target = ev.target.closest("[data-row-export]");
                              if (!target) return;
                              ev.preventDefault();
                              var key = target.getAttribute("data-row-export");

                              try {
                                    if (dtButtons && datatable && typeof datatable.button === "function") {
                                          var nodes = datatable.buttons().nodes().toArray();
                                          var idx = -1;
                                          for (var i = 0; i < nodes.length; i++) {
                                                var cls = nodes[i].className || "";
                                                if (key === "copy" && cls.indexOf("buttons-copy") !== -1) { idx = i; break; }
                                                if (key === "excel" && cls.indexOf("buttons-excel") !== -1) { idx = i; break; }
                                                if (key === "csv" && cls.indexOf("buttons-csv") !== -1) { idx = i; break; }
                                                if (key === "pdf" && cls.indexOf("buttons-pdf") !== -1) { idx = i; break; }
                                          }
                                          if (idx >= 0) {
                                                try { datatable.button(idx).trigger(); return; } catch (err) { warn("datatable.button(idx).trigger failed:", err); }
                                          }
                                    }
                              } catch (err) {
                                    warn("Export via Buttons API attempt error:", err);
                              }

                              // Fallback exports
                              if (key === "copy") {
                                    var csvText = buildCsv();
                                    copyToClipboard(csvText).then(function () {
                                          if (typeof toastr !== "undefined") toastr.success("Table copied to clipboard");
                                          else alert("Table copied to clipboard");
                                    }).catch(function (e) { error("Copy fallback error:", e); alert("Copy failed"); });
                                    return;
                              }

                              if (key === "excel" || key === "csv") {
                                    var csv = buildCsv();
                                    triggerDownload(documentTitle + ".csv", csv, "text/csv;charset=utf-8;");
                                    return;
                              }

                              if (key === "pdf") {
                                    if (window.pdfMake) {
                                          var csv2 = buildCsv();
                                          var lines = csv2.split(/\r\n/).map(function (l) { return l.replace(/"/g, ""); });
                                          var docDef = { content: [{ text: documentTitle, style: "header" }, { text: lines.join("\n"), style: "table" }], styles: { header: { fontSize: 14, bold: true } } };
                                          pdfMake.createPdf(docDef).download(documentTitle + ".pdf");
                                    } else {
                                          alert("PDF export requires pdfMake. Use CSV export or include pdfMake.");
                                    }
                                    return;
                              }

                              warn("Unknown export key:", key);
                        });
                        exportListenerAttached = true;
                        log("Export dropdown handler attached.");
                  }
            }
      }

      // Search handler
      function handleSearch() {
            var input = document.querySelector('[data-attendance-table-filter="search"]');
            if (!input) { warn("handleSearch: search input not found."); return; }
            if (searchListenerAttached) return;
            searchListenerAttached = true;

            var timer = null;
            input.addEventListener("keyup", function (e) {
                  var val = e.target.value || "";
                  if (timer) clearTimeout(timer);
                  timer = setTimeout(function () {
                        try { if (datatable && typeof datatable.search === "function") datatable.search(val).draw(); } catch (err) { warn("search handler error:", err); }
                  }, 180);
            });
            log("Search listener attached.");
      }

      // Status detector
      function getRecordStatus(r) {
            var vals = [r.status, r.attendance_status, r.attendance, r.present, r.is_present, r.isPresent, r.type];
            for (var i = 0; i < vals.length; i++) {
                  var v = vals[i];
                  if (v === undefined || v === null) continue;
                  if (typeof v === "boolean") return v ? "present" : "absent";
                  if (typeof v === "number") { if (v === 1) return "present"; if (v === 0) return "absent"; }
                  if (typeof v === "string") {
                        var s = v.trim().toLowerCase();
                        if (!s) continue;
                        if (s.indexOf("present") !== -1 || s === "p" || s === "1" || s === "true") return "present";
                        if (s.indexOf("late") !== -1 || s === "l") return "late";
                        if (s.indexOf("absent") !== -1 || s === "a" || s === "0" || s === "false") return "absent";
                  }
            }
            return "absent";
      }

      // Aggregate records by student
      function aggregateRecords(payload) {
            var rows = payload && Array.isArray(payload.data) ? payload.data : [];
            var map = {};
            rows.forEach(function (r) {
                  var student = r.student || {};
                  var sid = student.id || r.student_id || null;
                  var uniqueId = student.student_unique_id || r.student_unique_id || r.student_uniqueid || "";
                  var key = sid ? "id_" + sid : "u_" + (uniqueId || Math.random().toString(36).slice(2));
                  if (!map[key]) {
                        map[key] = {
                              studentId: sid || "",
                              name: student.name || r.student_name || r.name || "Unknown",
                              uniqueId: uniqueId || "",
                              className: (r.classname && r.classname.name) || r.class_name || r.className || "",
                              batchName: (r.batch && r.batch.name) || r.batch_name || r.batchName || "",
                              present: 0,
                              absent: 0,
                              late: 0
                        };
                  }
                  var st = getRecordStatus(r);
                  if (st === "present") map[key].present++;
                  else if (st === "late") map[key].late++;
                  else map[key].absent++;
            });
            return Object.keys(map).map(function (k) { return map[k]; });
      }

      // Render using DataTables API
      function renderAggregatedTable(records) {
            try {
                  records = Array.isArray(records) ? records : [];
                  log("[Attendance] renderAggregatedTable called, records:", records.length);

                  if (!records.length) {
                        var msg = "No records found for the selected filters.";
                        if (typeof toastr !== "undefined") toastr.info(msg);

                        if (datatable && typeof datatable.clear === "function") {
                              datatable.clear();
                              datatable.draw(false);
                        }
                        return;
                  }

                  var dtRows = records.map(function (rec, idx) {
                        var nameHtml = '<div class="d-flex flex-column">' +
                              '<span class="text-gray-800 fw-bold">' + (rec.name || "") + '</span>' +
                              '<span class="text-muted fs-7">' + (rec.uniqueId ? ("ID: " + rec.uniqueId) : "") + '</span>' +
                              '</div>';
                        var viewHtml = '<a href="/students/' + (rec.studentId || "") + '" class="btn btn-sm btn-light btn-active-primary" target="_blank">View</a>';
                        return [
                              idx + 1,
                              nameHtml,
                              rec.className || "",
                              rec.batchName || "",
                              '<span class="badge badge-light-success fs-7 fw-bold">' + (rec.present || 0) + '</span>',
                              '<span class="badge badge-light-danger fs-7 fw-bold">' + (rec.absent || 0) + '</span>',
                              '<span class="badge badge-light-warning fs-7 fw-bold">' + (rec.late || 0) + '</span>',
                              viewHtml
                        ];
                  });

                  if (datatable && typeof datatable.clear === "function") {
                        try { $(".dt-buttons").remove(); } catch (e) { /* ignore */ }
                        datatable.clear();
                        datatable.rows.add(dtRows);
                        try { datatable.columns.adjust(); } catch (e) { /* ignore */ }
                        datatable.draw(false);
                  }

            } catch (err) {
                  error("[Attendance] renderAggregatedTable error:", err);
            }
      }

      // Build query params
      function buildQueryParams() {
            var params = new URLSearchParams();
            var dateRange = dateInput && dateInput.value ? dateInput.value.trim() : "";

            function getSelectValue(sel) {
                  if (!sel) return "";
                  if (sel.tagName === 'INPUT') return sel.value || "";
                  if (sel.value) return sel.value;
                  return "";
            }

            var branchId = getSelectValue(branchSelect);
            var classId = getSelectValue(classSelect);
            var batchId = getSelectValue(batchSelect);

            if (dateRange) params.append("date_range", dateRange);
            if (branchId) params.append("branch_id", branchId);
            if (classId) params.append("class_id", classId);
            if (batchId) params.append("batch_id", batchId);

            return params.toString();
      }

      // Loading state (Metronic indicator pattern)
      function setLoading(isLoading) {
            try {
                  if (!submitButton) submitButton = document.getElementById("submit_button");
                  if (!submitButton) return;

                  if (isLoading) {
                        submitButton.disabled = true;
                        submitButton.setAttribute("data-kt-indicator", "on");
                  } else {
                        submitButton.disabled = false;
                        submitButton.setAttribute("data-kt-indicator", "off");
                  }
            } catch (e) { warn("setLoading error:", e); }
      }

      // Initialize FormValidation
      function initValidation() {
            if (!form) return;

            // Build validation fields based on user role
            var validationFields = {
                  'date_range': {
                        validators: {
                              notEmpty: { message: 'Date range is required' }
                        }
                  },
                  'class_id': {
                        validators: {
                              notEmpty: { message: 'Class is required' }
                        }
                  },
                  'batch_id': {
                        validators: {
                              notEmpty: { message: 'Batch is required' }
                        }
                  }
            };

            // Add branch validation only for admin users
            if (config.isAdmin) {
                  validationFields['branch_id'] = {
                        validators: {
                              notEmpty: { message: 'Branch is required' }
                        }
                  };
            }

            validator = FormValidation.formValidation(
                  form,
                  {
                        fields: validationFields,
                        plugins: {
                              trigger: new FormValidation.plugins.Trigger(),
                              bootstrap: new FormValidation.plugins.Bootstrap5({
                                    rowSelector: '.fv-row',
                                    eleInvalidClass: '',
                                    eleValidClass: ''
                              })
                        }
                  }
            );

            // Handle form submission
            submitButton.addEventListener('click', function (e) {
                  e.preventDefault();

                  validator.validate().then(function (status) {
                        if (status === 'Valid') {
                              fetchAttendance();
                        } else {
                              toastr.warning('Please fill all required fields');
                        }
                  });
            });

            // Revalidate on select2 change
            $(form).find('select[data-control="select2"]').on('change', function () {
                  var fieldName = $(this).attr('name');
                  if (fieldName && validator) {
                        validator.revalidateField(fieldName);
                  }
            });
      }

      // Fetch attendance data
      async function fetchAttendance() {
            var TIMEOUT = 30000;
            setLoading(true);

            var qs = buildQueryParams();
            var url = qs ? DATA_URL + "?" + qs : DATA_URL;
            log("[Attendance] Request URL:", url);

            var controller = new AbortController();
            var signal = controller.signal;
            var timeoutId = setTimeout(function () { controller.abort(); }, TIMEOUT);

            try {
                  var res = await fetch(url, {
                        method: "GET",
                        headers: { Accept: "application/json" },
                        credentials: "same-origin",
                        signal: signal
                  });
                  clearTimeout(timeoutId);

                  if (!res.ok) {
                        var txt = await res.text();
                        try {
                              var jsonErr = JSON.parse(txt);
                              if (typeof toastr !== "undefined") toastr.error(jsonErr.message || "Server error");
                        } catch (e) {
                              if (typeof toastr !== "undefined") toastr.error("Server error: " + res.status);
                        }
                        return;
                  }

                  var payload = await res.json();
                  log("[Attendance] Server payload:", payload);

                  var aggregated = aggregateRecords(payload);
                  renderAggregatedTable(aggregated);
                  exportButtons();

            } catch (err) {
                  error("[Attendance] Fetch error:", err);
                  if (err && err.name === "AbortError") {
                        if (typeof toastr !== "undefined") toastr.error("Request timed out. Please try again.");
                  } else {
                        if (typeof toastr !== "undefined") toastr.error("An error occurred while fetching data.");
                  }
            } finally {
                  clearTimeout(timeoutId);
                  setLoading(false);
            }
      }

      // Public init
      return {
            init: function () {
                  config = window.AttendanceReportConfig || {};

                  table = document.getElementById("kt_attendance_report_table");
                  if (!table) { error("KTAttendanceReportTable: table element not found"); return; }

                  form = document.getElementById("student_list_filter_form");
                  submitButton = document.getElementById("submit_button");
                  dateInput = document.getElementById("attendance_daterangepicker");
                  branchSelect = document.getElementById("student_branch_group");
                  classSelect = document.getElementById("student_class_group");
                  batchSelect = document.getElementById("student_batch_group");

                  ensureTbodyExists();
                  initDatatable();
                  exportButtons();
                  handleSearch();
                  initValidation();

                  // Prevent default form submission
                  if (form) {
                        form.addEventListener("submit", function (e) {
                              e.preventDefault();
                        });
                  }
            }
      };
})();

// Initialize on DOM ready
KTUtil.onDOMContentLoaded(function () {
      KTDateRangePicker.init();
      KTBranchBatchLoader.init();
      KTSelect2InputGroupFix.init();
      KTAttendanceReportTable.init();
});