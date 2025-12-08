"use strict";

/**
 * Module: Date Range Picker
 */
var KTDateRangePicker = function () {
      // Init daterangepicker
      var initDaterangepicker = () => {
            var start = moment().startOf("month");
            var end = moment().endOf("month");

            var input = $("#attendance_daterangepicker");
            var hiddenInput = $("#date_range_value"); // Hidden input field to store the value

            function cb(start, end) {
                  // Display format
                  var displayFormat = start.format("DD-MM-YYYY") + " - " + end.format("DD-MM-YYYY");
                  // Value format (same as display for backend processing consistency)
                  var valueFormat = start.format("DD-MM-YYYY") + " - " + end.format("DD-MM-YYYY");

                  input.val(displayFormat); // Set the display value
                  hiddenInput.val(valueFormat); // Set the hidden input value for form submission
            }

            // Initialize daterangepicker
            input.daterangepicker({
                  startDate: start,
                  endDate: end,
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

            cb(start, end);
      }

      // Public methods
      return {
            init: function () {
                  initDaterangepicker();
            }
      };
}();


/**
 * KTAttendanceReportTable - final consolidated module
 */
var KTAttendanceReportTable = (function () {
      // --- Module-level state ---
      var table, datatable;
      var DATA_URL = "/reports/attendance/data";

      var form, submitButton, tableBody, dateInput, branchSelect, classSelect, batchSelect;

      var exportListenerAttached = false;
      var searchListenerAttached = false;
      var dtButtons = null;

      // --- helpers for logging ---
      function log() { if (window.console && console.log) console.log.apply(console, arguments); }
      function warn() { if (window.console && console.warn) console.warn.apply(console, arguments); }
      function error() { if (window.console && console.error) console.error.apply(console, arguments); }

      // --- small helper to create <td> with text safely ---
      function tdWithText(text) {
            var td = document.createElement("td");
            td.textContent = text === undefined || text === null ? "" : text;
            return td;
      }

      // --- ensure tbody exists before initializing DataTable ---
      function ensureTbodyExists() {
            try {
                  if (!table) return;
                  var existing = table.querySelector("tbody");
                  if (!existing) {
                        var newT = document.createElement("tbody");
                        newT.id = "kt_attendance_report_table_body";
                        newT.className = "text-gray-600 fw-semibold";
                        // empty row with 8 tds
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

      // --- DataTable init (destroy safe) ---
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
                  columnDefs: []
            });

            log("DataTable initialized.");
      }

      // --- Export Buttons (Buttons API preferred, fallback implemented) ---
      function exportButtons() {
            // Clean old Buttons instance & DOM
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

            // Try to create Buttons using DataTables Buttons extension
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
                        log("dtButtons created (Buttons extension available).");
                  } else {
                        dtButtons = null;
                        log("Buttons extension not present — using fallback exports.");
                  }
            } catch (e) {
                  dtButtons = null;
                  warn("exportButtons: dtButtons creation error:", e);
            }

            // Utilities for fallback exports (CSV, download, copy)
            function buildCsv() {
                  var rows = [];
                  try {
                        var dt = $($("#kt_attendance_report_table")).DataTable();
                        var data = dt.rows({ search: "applied", page: "all" }).data().toArray();
                        data.forEach(function (r) {
                              // r is an array of columns when we added via datatable API
                              var row = r.map(function (c) {
                                    if (typeof c === "string") return c.replace(/<[^>]*>/g, "").trim();
                                    return String(c);
                              });
                              rows.push(row);
                        });
                  } catch (e) {
                        // fallback DOM read
                        document.querySelectorAll("#kt_attendance_report_table tbody tr").forEach(function (tr) {
                              var cols = [];
                              tr.querySelectorAll("td,th").forEach(function (cell) { cols.push(cell.textContent.trim()); });
                              rows.push(cols);
                        });
                  }
                  var header = [];
                  document.querySelectorAll("#kt_attendance_report_table thead th").forEach(function (th) { header.push(th.textContent.trim()); });
                  if (header.length) rows.unshift(header);
                  return rows.map(r => r.map(c => '"' + String(c).replace(/"/g, '""') + '"').join(",")).join("\r\n");
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

            // Attach dropdown handler once
            if (!exportListenerAttached) {
                  var menu = document.getElementById("kt_table_report_dropdown_menu");
                  if (menu) {
                        menu.addEventListener("click", function (ev) {
                              var target = ev.target.closest("[data-row-export]");
                              if (!target) return;
                              ev.preventDefault();
                              var key = target.getAttribute("data-row-export"); // copy|excel|csv|pdf

                              // 1) Prefer Buttons API if available
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
                                                try { datatable.button(idx).trigger(); return; } catch (err) { warn("datatable.button(idx).trigger failed:", err); /* fallback below */ }
                                          }
                                    }
                              } catch (err) {
                                    warn("Export via Buttons API attempt error:", err);
                              }

                              // 2) Fallback logic
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
                                          var lines = csv2.split(/\r\n/).map(l => l.replace(/"/g, ""));
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
                  } else {
                        warn("exportButtons: #kt_table_report_dropdown_menu not found.");
                  }
            }
      }

      // --- search handler (multi-selector + debounce + single attach) ---
      function handleSearch() {
            var input = document.querySelector('[data-attendance-table-filter="search"]') ||
                  document.querySelector('[data-attendance-report-table-filter="search"]') ||
                  document.querySelector('[data-attendance-search="search"]') || null;

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

      // --- status detector + aggregator ---
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

      // --- Render using DataTables API (robust) ---
      function renderAggregatedTable(records) {
            try {
                  records = Array.isArray(records) ? records : [];
                  log("[Attendance] renderAggregatedTable called, records:", records.length);

                  // empty-state handling
                  if (!records.length) {
                        var msg = "No records found for the selected filters.";
                        if (typeof toastr !== "undefined") toastr.info(msg); else console.info(msg);

                        // If datatable API exists, clear & add a single empty row (8 columns)
                        if (datatable && typeof datatable.clear === "function") {
                              datatable.clear();
                              var empty = [];
                              for (var i = 0; i < 8; i++) empty.push("");
                              datatable.row.add(empty);
                              datatable.draw(false);
                              return;
                        } else {
                              // fallback: replace tbody
                              var newT = document.createElement("tbody");
                              newT.id = "kt_attendance_report_table_body";
                              newT.className = "text-gray-600 fw-semibold";
                              var rowHtml = "<tr>";
                              for (var j = 0; j < 8; j++) rowHtml += "<td></td>";
                              rowHtml += "</tr>";
                              newT.innerHTML = rowHtml;
                              var old = table.querySelector("tbody");
                              if (old) old.replaceWith(newT); else table.appendChild(newT);
                              tableBody = newT;
                              initDatatable(); exportButtons(); handleSearch();
                              return;
                        }
                  }

                  // Build dtRows as arrays matching header order
                  var dtRows = records.map(function (rec, idx) {
                        var nameHtml = '<div>' + (rec.name || "") + '</div>';
                        nameHtml += '<div class="text-muted small">' + (rec.uniqueId ? ("ID: " + rec.uniqueId) : "") + '</div>';
                        var viewHtml = '<button type="button" class="btn btn-sm btn-light btn-active-primary me-2 view-student-btn" data-student-id="' + (rec.studentId || "") + '">View</button>';
                        return [
                              idx + 1,
                              nameHtml,
                              rec.className || "",
                              rec.batchName || "",
                              rec.present || 0,
                              rec.absent || 0,
                              rec.late || 0,
                              viewHtml
                        ];
                  });

                  // Inject rows via DataTables API
                  if (datatable && typeof datatable.clear === "function" && typeof datatable.rows === "function") {
                        try { $(".dt-buttons").remove(); } catch (e) { /* ignore */ }
                        datatable.clear();
                        datatable.rows.add(dtRows);
                        try { datatable.columns.adjust(); } catch (e) { /* ignore */ }
                        datatable.draw(false);

                        // Attach view-button delegation once (idempotent)
                        try {
                              if (!table._viewBtnHandlerAttached) {
                                    table._viewBtnHandlerAttached = true;
                                    table.addEventListener("click", function (ev) {
                                          var btn = ev.target.closest(".view-student-btn");
                                          if (!btn) return;

                                          var sid = btn.getAttribute("data-student-id");
                                          if (sid) {
                                                window.open("/students/" + sid, "_blank"); // ✅ open in new tab
                                          } else {
                                                alert("Student ID not available for this record.");
                                          }
                                    });
                              }

                        } catch (e) { warn("view button delegation attach failed:", e); }

                        return;
                  }

                  // Fallback: replace tbody with built rows
                  var newTbody = document.createElement("tbody");
                  newTbody.id = "kt_attendance_report_table_body";
                  newTbody.className = "text-gray-600 fw-semibold";
                  var frag = document.createDocumentFragment();
                  records.forEach(function (rec, idx) {
                        var tr = document.createElement("tr");
                        var th = document.createElement("th"); th.scope = "row"; th.textContent = idx + 1; tr.appendChild(th);
                        var tdName = document.createElement("td");
                        var d1 = document.createElement("div"); d1.textContent = rec.name || ""; var d2 = document.createElement("div"); d2.className = "text-muted small"; d2.textContent = rec.uniqueId ? ("ID: " + rec.uniqueId) : "";
                        tdName.appendChild(d1); tdName.appendChild(d2); tr.appendChild(tdName);
                        tr.appendChild(tdWithText(rec.className || ""));
                        tr.appendChild(tdWithText(rec.batchName || ""));
                        tr.appendChild(tdWithText(rec.present || 0));
                        tr.appendChild(tdWithText(rec.absent || 0));
                        tr.appendChild(tdWithText(rec.late || 0));
                        var tdAct = document.createElement("td"); tdAct.className = "not-export";
                        var vb = document.createElement("button"); vb.type = "button"; vb.className = "btn btn-sm btn-light btn-active-primary me-2"; vb.textContent = "View"; vb.setAttribute("data-student-id", rec.studentId || "");
                        tdAct.appendChild(vb); tr.appendChild(tdAct);
                        frag.appendChild(tr);
                  });
                  newTbody.appendChild(frag);
                  var old = table.querySelector("tbody");
                  if (old) old.replaceWith(newTbody); else table.appendChild(newTbody);
                  tableBody = newTbody;
                  initDatatable(); exportButtons(); handleSearch();

            } catch (err) {
                  error("[Attendance] renderAggregatedTable error:", err);
            }
      }

      // --- Build query params (select2 / hidden input fallbacks) ---
      function buildQueryParams() {
            var params = new URLSearchParams();
            var dateRange = dateInput && dateInput.value ? dateInput.value.trim() : "";

            function getSelectValue(sel) {
                  if (!sel) return "";
                  if (sel.value) return sel.value;
                  if (sel.dataset && sel.dataset.selected) return sel.dataset.selected;
                  var hidden = form ? form.querySelector('input[name="' + (sel.name || "") + '"][type="hidden"]') : null;
                  if (hidden && hidden.value) return hidden.value;
                  var opt = sel.querySelector("option[selected]");
                  if (opt) return opt.value;
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

      // --- Defensive setLoading ---
      function setLoading(isLoading) {
            try {
                  if (!submitButton) submitButton = document.getElementById("submit_button");
                  if (!submitButton) { warn("setLoading: submit_button not found"); return; }
                  if (isLoading) {
                        if (!submitButton.dataset.originalText) submitButton.dataset.originalText = submitButton.innerHTML;
                        submitButton.disabled = true;
                        try { submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating'; } catch (e) { try { submitButton.textContent = "Generating..."; } catch (e2) { } }
                  } else {
                        submitButton.disabled = false;
                        try { submitButton.innerHTML = submitButton.dataset.originalText || "Generate"; } catch (e) { try { submitButton.textContent = submitButton.dataset.originalText || "Generate"; } catch (e2) { } }
                  }
            } catch (e) { warn("setLoading error:", e); }
      }

      // --- Fetch + timeout + aggregation + render ---
      async function fetchAttendance() {
            var TIMEOUT = 30000; // 30s
            setLoading(true);

            var qs = buildQueryParams();
            var url = qs ? DATA_URL + "?" + qs : DATA_URL;
            log("[Attendance] Request URL:", url);

            const controller = new AbortController();
            const signal = controller.signal;
            var timeoutId = setTimeout(function () { try { controller.abort(); log("[Attendance] Fetch aborted due to timeout"); } catch (e) { } }, TIMEOUT);

            try {
                  var res = await fetch(url, { method: "GET", headers: { Accept: "application/json" }, credentials: "same-origin", signal: signal });
                  clearTimeout(timeoutId);

                  if (!res.ok) {
                        var txt = await res.text();
                        try {
                              var jsonErr = JSON.parse(txt);
                              var msg = jsonErr.message || txt || ("Server error: " + res.status);
                              tableBody && (tableBody.innerHTML = "<tr>" + Array(8).fill("<td></td>").join("") + "</tr>");
                              warn("[Attendance] Non-ok response:", res.status, jsonErr);
                        } catch (e) {
                              tableBody && (tableBody.innerHTML = "<tr>" + Array(8).fill("<td></td>").join("") + "</tr>");
                              warn("[Attendance] Non-ok response (non-json):", res.status, txt);
                        }
                        initDatatable(); exportButtons(); handleSearch();
                        return;
                  }

                  var payload = await res.json();
                  log("[Attendance] Server payload:", payload);

                  var aggregated = aggregateRecords(payload);

                  // fallback quick grouping if aggregator empty but raw data present
                  if ((!Array.isArray(aggregated) || aggregated.length === 0) && Array.isArray(payload.data) && payload.data.length > 0) {
                        warn("[Attendance] aggregator returned empty; using quick fallback grouping.");
                        var quickMap = {};
                        payload.data.forEach(function (r) {
                              var sid = (r.student && r.student.id) || r.student_id || ("sid_missing_" + (r.id || Math.random().toString(36).slice(2)));
                              if (!quickMap[sid]) quickMap[sid] = { studentId: sid, name: (r.student && r.student.name) || r.student_name || "", uniqueId: (r.student && r.student.student_unique_id) || r.student_unique_id || "", className: (r.classname && r.classname.name) || r.class_name || "", batchName: (r.batch && r.batch.name) || r.batch_name || "", present: 0, absent: 0, late: 0 };
                              var st = getRecordStatus(r);
                              if (st === "present") quickMap[sid].present++; else if (st === "late") quickMap[sid].late++; else quickMap[sid].absent++;
                        });
                        aggregated = Object.keys(quickMap).map(function (k) { return quickMap[k]; });
                  }

                  renderAggregatedTable(aggregated);
                  // ensure exports are recreated for latest datatable instance
                  exportButtons();

            } catch (err) {
                  error("[Attendance] Fetch error:", err);
                  if (err && err.name === "AbortError") {
                        tableBody && (tableBody.innerHTML = "<tr>" + Array(8).fill("<td></td>").join("") + "</tr>");
                  } else {
                        tableBody && (tableBody.innerHTML = "<tr>" + Array(8).fill("<td></td>").join("") + "</tr>");
                  }
                  initDatatable(); exportButtons(); handleSearch();
            } finally {
                  try { clearTimeout(timeoutId); } catch (e) { }
                  try { setLoading(false); } catch (e) { warn("Error clearing loading state:", e); }
            }
      }

      // --- public init ---
      return {
            init: function () {
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

                  if (form) {
                        form.addEventListener("submit", function (e) {
                              e.preventDefault();
                              fetchAttendance();
                        });
                  } else {
                        warn("student_list_filter_form not found; submit handler not attached.");
                  }

                  // also re-wire exportButtons on initial load so dtButtons created
                  exportButtons();
            }
      };
})();



// On document ready
KTUtil.onDOMContentLoaded(function () {
      KTDateRangePicker.init();
      KTAttendanceReportTable.init();
});