"use strict";

/**
 * Annual Due Report Module
 *
 * Handles three tabs:
 *   1. Tuition Summary  — Class × Month pivot table (uses month_year column)
 *   2. Tuition Detailed — Sortable / filterable table
 *   3. Invoice Type-wise Due — Summary + Detailed for non-tuition fees (uses created_at)
 *
 * Features:
 *   - AJAX data loading, stats cards, SheetJS Excel export.
 *   - Clickable student count badges open a modal with individual invoice details.
 *   - Invoice numbers link to invoices.show, student names link to students.show.
 */
var KTAnnualDueReport = (function () {

    // ─── DOM References ───
    var _form, _reportContainer, _loader, _emptyState, _statsContainer;
    var _tuitionSumContainer, _tuitionDetContainer;
    var _otherSumContainer, _otherDetContainer;

    // Modal DOM
    var _invoicesModal, _invoicesModalTitle, _invoicesModalBody, _invoicesModalLoader;

    // ─── State ───
    var _data = null;                       // Full AJAX response

    // Tuition detailed state
    var _tuitionFiltered = [];
    var _tuitionSortCol = "month_num";
    var _tuitionSortDir = "asc";

    // Other detailed state
    var _otherFiltered = [];
    var _otherSortCol = "month_num";
    var _otherSortDir = "asc";

    // ─── Constants ───
    var MONTH_NAMES = [
        "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];
    var MONTH_SHORT = [
        "Jan", "Feb", "Mar", "Apr", "May", "Jun",
        "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
    ];

    // ═══════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════

    /** Format number as BDT with Indian numbering (e.g. ৳ 1,25,000). Returns em-dash for zero. */
    var _fmt = function (amount) {
        if (!amount || amount === 0) return "—";
        return "৳ " + Number(amount).toLocaleString("en-IN");
    };

    /** Always shows the number, even zero */
    var _fmtRaw = function (amount) {
        return "৳ " + Number(amount || 0).toLocaleString("en-IN");
    };

    /** SweetAlert2 wrapper */
    var _swal = function (text, icon) {
        if (typeof Swal === "undefined") { alert(text); return; }
        Swal.fire({
            text: text, icon: icon || "info",
            buttonsStyling: false, confirmButtonText: "OK",
            customClass: { confirmButton: "btn btn-primary" }
        });
    };

    /** Safely destroy + re-create Select2 */
    var _reinitSelect2 = function (el, placeholder) {
        if (typeof $.fn.select2 === "undefined") return;
        var $el = $(el);
        try { $el.select2("destroy"); } catch (e) { /* ignore */ }
        $el.select2({ placeholder: placeholder, allowClear: true, minimumResultsForSearch: -1 });
    };

    /** Sort icon for table headers */
    var _sortIcon = function (currentCol, targetCol, dir) {
        if (currentCol !== targetCol) {
            return '<i class="ki-outline ki-arrow-up-down fs-7 ms-1 text-gray-400"></i>';
        }
        return dir === "asc"
            ? '<i class="ki-outline ki-arrow-up fs-7 ms-1 text-primary"></i>'
            : '<i class="ki-outline ki-arrow-down fs-7 ms-1 text-primary"></i>';
    };

    /** Generic sort for data arrays */
    var _sortArray = function (arr, col, dir) {
        arr.sort(function (a, b) {
            var valA = a[col], valB = b[col];
            if (typeof valA === "string") {
                var cmp = valA.localeCompare(valB);
                return dir === "asc" ? cmp : -cmp;
            }
            return dir === "asc" ? valA - valB : valB - valA;
        });
    };

    /** Escape HTML to prevent XSS */
    var _esc = function (str) {
        if (!str) return "";
        var div = document.createElement("div");
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    };

    // ═══════════════════════════════════════════
    //  INITIALIZATION
    // ═══════════════════════════════════════════

    var _initDom = function () {
        _form               = document.getElementById("annual_due_form");
        _reportContainer    = document.getElementById("report_container");
        _loader             = document.getElementById("report_loader");
        _emptyState         = document.getElementById("empty_state");
        _statsContainer     = document.getElementById("stats_container");

        _tuitionSumContainer = document.getElementById("tuition_summary_container");
        _tuitionDetContainer = document.getElementById("tuition_detailed_container");
        _otherSumContainer   = document.getElementById("other_summary_container");
        _otherDetContainer   = document.getElementById("other_detailed_container");

        // Modal
        _invoicesModal       = document.getElementById("kt_modal_invoices");
        _invoicesModalTitle  = document.getElementById("invoices_modal_title");
        _invoicesModalBody   = document.getElementById("invoices_modal_body");
        _invoicesModalLoader = document.getElementById("invoices_modal_loader");
    };

    var _initEvents = function () {
        // Form submit — only way to load the report
        _form.addEventListener("submit", function (e) {
            e.preventDefault();
            _loadReport();
        });

        // ─── Tuition detailed filters ───
        $(document).on("input", "#tuition_search", function () { _applyTuitionFilters(); });
        $(document).on("change", "#tuition_month_filter, #tuition_class_filter", function () {
            _applyTuitionFilters();
        });
        $(document).on("click", "#tuition_clear_btn", function () {
            $("#tuition_search").val("");
            _resetSelect2("#tuition_month_filter", "All Months");
            _resetSelect2("#tuition_class_filter", "All Classes");
            _applyTuitionFilters();
        });

        // ─── Other detailed filters ───
        $(document).on("input", "#other_search", function () { _applyOtherFilters(); });
        $(document).on("change", "#other_month_filter, #other_type_filter, #other_class_filter", function () {
            _applyOtherFilters();
        });
        $(document).on("click", "#other_clear_btn", function () {
            $("#other_search").val("");
            _resetSelect2("#other_month_filter", "All Months");
            _resetSelect2("#other_type_filter", "All Types");
            _resetSelect2("#other_class_filter", "All Classes");
            _applyOtherFilters();
        });

        // ─── Student Due Link — open invoices modal ───
        $(document).on("click", ".student-due-link", function (e) {
            e.preventDefault();
            var $el = $(this);

            var params = {
                type:      $el.data("type"),
                month_num: $el.data("month-num"),
                class_id:  $el.data("class-id"),
                batch_id:  $el.data("batch-id"),
                year:      $("#year_select").val(),
                branch_id: $("#branch_id").val()
            };

            // Build modal title parts
            var titleParts = [_esc($el.data("month")), _esc($el.data("class")), _esc($el.data("batch"))];

            if (params.type === "other") {
                params.invoice_type_id = $el.data("invoice-type-id");
                titleParts = [_esc($el.data("month")), _esc($el.data("invoice-type")), _esc($el.data("class")), _esc($el.data("batch"))];
            }

            _loadInvoicesModal(params, titleParts);
        });
    };

    var _resetSelect2 = function (selector, placeholder) {
        var $el = $(selector);
        if ($el.hasClass("select2-hidden-accessible")) {
            $el.val("").trigger("change");
        } else {
            $el.val("");
        }
    };

    // ═══════════════════════════════════════════
    //  AJAX DATA LOADING
    // ═══════════════════════════════════════════

    var _loadReport = function () {
        var branchId = $("#branch_id").val();
        var year     = $("#year_select").val();

        if (!branchId || !year) {
            _swal("Please select both branch and year.", "warning");
            return;
        }

        // UI: show loader
        _loader.classList.remove("d-none");
        _reportContainer.classList.add("d-none");
        _emptyState.classList.add("d-none");

        var btn = document.getElementById("generate_report_btn");
        btn.setAttribute("data-kt-indicator", "on");
        btn.disabled = true;

        $.ajax({
            url: reportDataUrl,
            type: "GET",
            data: { year: year, branch_id: branchId },
            headers: { "X-Requested-With": "XMLHttpRequest" },
            success: function (res) {
                if (!res.success) {
                    _swal(res.message || "Failed to load report.", "error");
                    return;
                }

                _data = res;

                // Check if any data exists at all
                var hasTuition = res.tuition && res.tuition.detailed && res.tuition.detailed.length > 0;
                var hasOther   = res.other && res.other.detailed && res.other.detailed.length > 0;

                if (!hasTuition && !hasOther) {
                    _emptyState.classList.remove("d-none");
                    return;
                }

                // Reset sort states
                _tuitionSortCol = "month_num";
                _tuitionSortDir = "asc";
                _otherSortCol   = "month_num";
                _otherSortDir   = "asc";

                // Copy data for filtering
                _tuitionFiltered = hasTuition ? res.tuition.detailed.slice() : [];
                _otherFiltered   = hasOther ? res.other.detailed.slice() : [];

                // Render everything
                _renderStats();
                _renderTuitionSummary();
                _populateTuitionFilters();
                _renderTuitionDetailed();
                _renderOtherSummary();
                _populateOtherFilters();
                _renderOtherDetailed();

                // Update other fees badge
                var badge = document.getElementById("other_fees_badge");
                if (badge) {
                    badge.textContent = hasOther ? res.other.total_invoices + " invoices" : "";
                }

                _reportContainer.classList.remove("d-none");
            },
            error: function (xhr) {
                var msg = "An error occurred while loading the report.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                _swal(msg, "error");
            },
            complete: function () {
                _loader.classList.add("d-none");
                btn.removeAttribute("data-kt-indicator");
                btn.disabled = false;
            }
        });
    };

    // ═══════════════════════════════════════════
    //  STATS CARDS
    // ═══════════════════════════════════════════

    var _renderStats = function () {
        var d = _data;
        var cards = [
            {
                icon: "ki-outline ki-dollar",
                bg: "bg-light-danger", color: "text-danger",
                label: "Grand Total Due",
                value: _fmtRaw(d.grand_total),
                sub: d.total_invoices + " invoices"
            },
            {
                icon: "ki-outline ki-purchase",
                bg: "bg-light-primary", color: "text-primary",
                label: "Tuition Fee Due",
                value: _fmtRaw(d.tuition.grand_total),
                sub: d.tuition.total_invoices + " invoices · " + d.tuition.total_classes + " classes"
            },
            {
                icon: "ki-outline ki-bill",
                bg: "bg-light-warning", color: "text-warning",
                label: "Other Fees Due",
                value: _fmtRaw(d.other.grand_total),
                sub: d.other.total_invoices + " invoices · " + d.other.total_types + " types"
            },
            {
                icon: "ki-outline ki-bank",
                bg: "bg-light-success", color: "text-success",
                label: "Branch",
                value: d.branch_name,
                sub: d.branch_prefix + " | Year: " + d.year
            }
        ];

        var html = "";
        cards.forEach(function (c) {
            html += '<div class="col-lg-3 col-md-6">';
            html += '  <div class="card card-flush h-100 border-0 shadow-sm stat-card">';
            html += '    <div class="card-body d-flex align-items-center">';
            html += '      <div class="d-flex align-items-center justify-content-center rounded-circle ' + c.bg + '" style="width:50px;height:50px;min-width:50px;">';
            html += '        <i class="' + c.icon + ' fs-2 ' + c.color + '"></i>';
            html += '      </div>';
            html += '      <div class="ms-4">';
            html += '        <div class="text-gray-500 fs-7 fw-semibold">' + c.label + '</div>';
            html += '        <div class="text-gray-900 fs-2 fw-bold">' + c.value + '</div>';
            if (c.sub) {
                html += '    <div class="text-gray-500 fs-8">' + c.sub + '</div>';
            }
            html += '      </div>';
            html += '    </div>';
            html += '  </div>';
            html += '</div>';
        });

        _statsContainer.innerHTML = html;
    };

    // ═══════════════════════════════════════════
    //  TAB 1: TUITION SUMMARY TABLE
    // ═══════════════════════════════════════════

    var _renderTuitionSummary = function () {
        var summary = _data.tuition.summary;
        var classNames = Object.keys(summary);

        if (classNames.length === 0) {
            _tuitionSumContainer.innerHTML =
                '<div class="text-center py-10 text-gray-500">No tuition fee dues found.</div>';
            return;
        }

        var html = '<div class="table-responsive">';
        html += '<table class="table table-bordered table-row-bordered table-row-gray-200 align-middle gs-3 gy-3 summary-table">';

        // Header
        html += '<thead><tr class="fw-bold text-gray-800 bg-light">';
        html += '<th class="min-w-150px ps-4 rounded-start">Class</th>';
        for (var i = 0; i < 12; i++) {
            html += '<th class="min-w-110px text-center">' + MONTH_SHORT[i] + '</th>';
        }
        html += '<th class="min-w-130px text-center rounded-end bg-light-warning fw-bolder">Total</th>';
        html += '</tr></thead>';

        // Body
        html += '<tbody>';
        var monthTotals = new Array(12).fill(0);
        var grandTotal = 0;

        classNames.forEach(function (cls) {
            var cd = summary[cls];
            var rowTotal = cd.total || 0;

            html += '<tr>';
            html += '<td class="ps-4 fw-semibold text-gray-800">' + _esc(cls) + '</td>';

            for (var m = 0; m < 12; m++) {
                var amt = cd.months[MONTH_NAMES[m]] || 0;
                monthTotals[m] += amt;

                html += amt > 0
                    ? '<td class="text-center"><span class="text-danger fw-semibold">' + _fmtRaw(amt) + '</span></td>'
                    : '<td class="text-center text-gray-400">—</td>';
            }

            grandTotal += rowTotal;
            html += '<td class="text-center fw-bold text-danger bg-light-warning">' + _fmtRaw(rowTotal) + '</td>';
            html += '</tr>';
        });
        html += '</tbody>';

        // Footer
        html += '<tfoot><tr class="footer-dark">';
        html += '<td class="ps-4 rounded-start">Monthly Total</td>';
        for (var m = 0; m < 12; m++) {
            html += monthTotals[m] > 0
                ? '<td class="text-center">' + _fmtRaw(monthTotals[m]) + '</td>'
                : '<td class="text-center">—</td>';
        }
        html += '<td class="text-center rounded-end footer-grand-total">' + _fmtRaw(grandTotal) + '</td>';
        html += '</tr></tfoot>';

        html += '</table></div>';
        _tuitionSumContainer.innerHTML = html;
    };

    // ═══════════════════════════════════════════
    //  TAB 2: TUITION DETAILED TABLE
    // ═══════════════════════════════════════════

    var _populateTuitionFilters = function () {
        var detail = _data.tuition.detailed;

        // Month filter
        var months = [];
        detail.forEach(function (r) { if (months.indexOf(r.month) === -1) months.push(r.month); });
        months.sort(function (a, b) { return MONTH_NAMES.indexOf(a) - MONTH_NAMES.indexOf(b); });
        var mHtml = '<option value="">All Months</option>';
        months.forEach(function (m) { mHtml += '<option value="' + m + '">' + m + '</option>'; });
        document.getElementById("tuition_month_filter").innerHTML = mHtml;

        // Class filter
        var classes = [];
        detail.forEach(function (r) { if (classes.indexOf(r.class) === -1) classes.push(r.class); });
        var cHtml = '<option value="">All Classes</option>';
        classes.forEach(function (c) { cHtml += '<option value="' + c + '">' + c + '</option>'; });
        document.getElementById("tuition_class_filter").innerHTML = cHtml;

        _reinitSelect2(document.getElementById("tuition_month_filter"), "All Months");
        _reinitSelect2(document.getElementById("tuition_class_filter"), "All Classes");
    };

    var _applyTuitionFilters = function () {
        if (!_data) return;
        var search = ($("#tuition_search").val() || "").toLowerCase().trim();
        var month  = $("#tuition_month_filter").val() || "";
        var cls    = $("#tuition_class_filter").val() || "";

        _tuitionFiltered = _data.tuition.detailed.filter(function (r) {
            var ok = true;
            if (search) {
                ok = r.class.toLowerCase().indexOf(search) > -1 ||
                     r.batch.toLowerCase().indexOf(search) > -1 ||
                     r.month.toLowerCase().indexOf(search) > -1;
            }
            if (ok && month) ok = r.month === month;
            if (ok && cls)   ok = r.class === cls;
            return ok;
        });

        _sortArray(_tuitionFiltered, _tuitionSortCol, _tuitionSortDir);
        _renderTuitionDetailed();
    };

    var _renderTuitionDetailed = function () {
        var rows = _tuitionFiltered;

        if (rows.length === 0) {
            _tuitionDetContainer.innerHTML =
                '<div class="text-center py-10 text-gray-500 fs-6">No records match the current filters.</div>';
            return;
        }

        var si = function (col) { return _sortIcon(_tuitionSortCol, col, _tuitionSortDir); };

        var html = '<div class="table-responsive">';
        html += '<table class="table table-row-bordered table-row-gray-200 align-middle gs-3 gy-3 detailed-table">';

        // Header
        html += '<thead><tr class="fw-bold text-gray-800 bg-light">';
        html += '<th class="min-w-50px text-center rounded-start">#</th>';
        html += '<th class="min-w-120px sortable-header" data-sort="month_num" data-tab="tuition">Month ' + si("month_num") + '</th>';
        html += '<th class="min-w-150px sortable-header" data-sort="class" data-tab="tuition">Class ' + si("class") + '</th>';
        html += '<th class="min-w-150px sortable-header" data-sort="batch" data-tab="tuition">Batch ' + si("batch") + '</th>';
        html += '<th class="min-w-130px text-center sortable-header" data-sort="student_count" data-tab="tuition">Students ' + si("student_count") + '</th>';
        html += '<th class="min-w-140px text-end rounded-end sortable-header" data-sort="due_amount" data-tab="tuition">Due Amount ' + si("due_amount") + '</th>';
        html += '</tr></thead>';

        // Body
        html += '<tbody>';
        var totalDue = 0, totalStudents = 0;

        rows.forEach(function (r, i) {
            totalDue += r.due_amount;
            totalStudents += r.student_count;

            html += '<tr>';
            html += '<td class="text-center text-gray-500 fw-semibold">' + (i + 1) + '</td>';
            html += '<td><span class="badge badge-light-primary fw-semibold">' + _esc(r.month) + '</span></td>';
            html += '<td class="fw-semibold text-gray-800">' + _esc(r.class) + '</td>';
            html += '<td class="text-gray-700">' + _esc(r.batch) + '</td>';

            // Clickable student count badge
            html += '<td class="text-center">';
            html += '<a href="javascript:void(0);" class="badge badge-light-info student-due-link"';
            html += ' data-type="tuition"';
            html += ' data-month-num="' + r.month_num + '"';
            html += ' data-month="' + _esc(r.month) + '"';
            html += ' data-class-id="' + r.class_id + '"';
            html += ' data-class="' + _esc(r.class) + '"';
            html += ' data-batch-id="' + r.batch_id + '"';
            html += ' data-batch="' + _esc(r.batch) + '"';
            html += '>' + r.student_count + ' students</a>';
            html += '</td>';

            html += '<td class="text-end fw-bold text-danger">' + _fmtRaw(r.due_amount) + '</td>';
            html += '</tr>';
        });
        html += '</tbody>';

        // Footer
        html += '<tfoot><tr class="footer-dark">';
        html += '<td class="rounded-start" colspan="4">Total (' + rows.length + ' records)</td>';
        html += '<td class="text-center">' + totalStudents + '</td>';
        html += '<td class="text-end rounded-end footer-grand-total">' + _fmtRaw(totalDue) + '</td>';
        html += '</tr></tfoot>';

        html += '</table></div>';
        _tuitionDetContainer.innerHTML = html;

        // Bind sort handlers
        _tuitionDetContainer.querySelectorAll('.sortable-header[data-tab="tuition"]').forEach(function (th) {
            th.style.cursor = "pointer";
            th.addEventListener("click", function () {
                var col = this.getAttribute("data-sort");
                if (_tuitionSortCol === col) {
                    _tuitionSortDir = _tuitionSortDir === "asc" ? "desc" : "asc";
                } else {
                    _tuitionSortCol = col;
                    _tuitionSortDir = "asc";
                }
                _sortArray(_tuitionFiltered, _tuitionSortCol, _tuitionSortDir);
                _renderTuitionDetailed();
            });
        });
    };

    // ═══════════════════════════════════════════
    //  TAB 3: OTHER FEES — SUMMARY TABLE
    // ═══════════════════════════════════════════

    var _renderOtherSummary = function () {
        var summary = _data.other.summary;
        var typeNames = Object.keys(summary);

        if (typeNames.length === 0) {
            _otherSumContainer.innerHTML =
                '<div class="text-center py-10 text-gray-500">No other fee dues found.</div>';
            return;
        }

        var html = '<div class="table-responsive">';
        html += '<table class="table table-bordered table-row-bordered table-row-gray-200 align-middle gs-3 gy-3 summary-table">';

        // Header
        html += '<thead><tr class="fw-bold text-gray-800 bg-light">';
        html += '<th class="min-w-170px ps-4 rounded-start">Invoice Type</th>';
        for (var i = 0; i < 12; i++) {
            html += '<th class="min-w-110px text-center">' + MONTH_SHORT[i] + '</th>';
        }
        html += '<th class="min-w-130px text-center rounded-end bg-light-warning fw-bolder">Total</th>';
        html += '</tr></thead>';

        // Body
        html += '<tbody>';
        var monthTotals = new Array(12).fill(0);
        var grandTotal = 0;

        typeNames.forEach(function (typeName) {
            var td = summary[typeName];
            var rowTotal = td.total || 0;

            html += '<tr>';
            html += '<td class="ps-4 fw-semibold text-gray-800">';
            html += '  <span class="badge badge-light-warning me-2">' + _esc(typeName.charAt(0)) + '</span>';
            html += _esc(typeName);
            html += '</td>';

            for (var m = 0; m < 12; m++) {
                var amt = td.months[MONTH_NAMES[m]] || 0;
                monthTotals[m] += amt;

                html += amt > 0
                    ? '<td class="text-center"><span class="text-warning fw-semibold">' + _fmtRaw(amt) + '</span></td>'
                    : '<td class="text-center text-gray-400">—</td>';
            }

            grandTotal += rowTotal;
            html += '<td class="text-center fw-bold text-warning bg-light-warning">' + _fmtRaw(rowTotal) + '</td>';
            html += '</tr>';
        });
        html += '</tbody>';

        // Footer
        html += '<tfoot><tr class="footer-dark">';
        html += '<td class="ps-4 rounded-start">Monthly Total</td>';
        for (var m = 0; m < 12; m++) {
            html += monthTotals[m] > 0
                ? '<td class="text-center">' + _fmtRaw(monthTotals[m]) + '</td>'
                : '<td class="text-center">—</td>';
        }
        html += '<td class="text-center rounded-end footer-grand-total">' + _fmtRaw(grandTotal) + '</td>';
        html += '</tr></tfoot>';

        html += '</table></div>';
        _otherSumContainer.innerHTML = html;
    };

    // ═══════════════════════════════════════════
    //  TAB 3: OTHER FEES — DETAILED TABLE
    // ═══════════════════════════════════════════

    var _populateOtherFilters = function () {
        var detail = _data.other.detailed;

        // Month filter
        var months = [];
        detail.forEach(function (r) { if (months.indexOf(r.month) === -1) months.push(r.month); });
        months.sort(function (a, b) { return MONTH_NAMES.indexOf(a) - MONTH_NAMES.indexOf(b); });
        var mHtml = '<option value="">All Months</option>';
        months.forEach(function (m) { mHtml += '<option value="' + m + '">' + m + '</option>'; });
        document.getElementById("other_month_filter").innerHTML = mHtml;

        // Type filter
        var types = [];
        detail.forEach(function (r) { if (types.indexOf(r.invoice_type) === -1) types.push(r.invoice_type); });
        types.sort();
        var tHtml = '<option value="">All Types</option>';
        types.forEach(function (t) { tHtml += '<option value="' + t + '">' + t + '</option>'; });
        document.getElementById("other_type_filter").innerHTML = tHtml;

        // Class filter
        var classes = [];
        detail.forEach(function (r) { if (classes.indexOf(r.class) === -1) classes.push(r.class); });
        var cHtml = '<option value="">All Classes</option>';
        classes.forEach(function (c) { cHtml += '<option value="' + c + '">' + c + '</option>'; });
        document.getElementById("other_class_filter").innerHTML = cHtml;

        _reinitSelect2(document.getElementById("other_month_filter"), "All Months");
        _reinitSelect2(document.getElementById("other_type_filter"), "All Types");
        _reinitSelect2(document.getElementById("other_class_filter"), "All Classes");
    };

    var _applyOtherFilters = function () {
        if (!_data) return;
        var search = ($("#other_search").val() || "").toLowerCase().trim();
        var month  = $("#other_month_filter").val() || "";
        var type   = $("#other_type_filter").val() || "";
        var cls    = $("#other_class_filter").val() || "";

        _otherFiltered = _data.other.detailed.filter(function (r) {
            var ok = true;
            if (search) {
                ok = r.invoice_type.toLowerCase().indexOf(search) > -1 ||
                     r.class.toLowerCase().indexOf(search) > -1 ||
                     r.batch.toLowerCase().indexOf(search) > -1 ||
                     r.month.toLowerCase().indexOf(search) > -1;
            }
            if (ok && month) ok = r.month === month;
            if (ok && type)  ok = r.invoice_type === type;
            if (ok && cls)   ok = r.class === cls;
            return ok;
        });

        _sortArray(_otherFiltered, _otherSortCol, _otherSortDir);
        _renderOtherDetailed();
    };

    var _renderOtherDetailed = function () {
        var rows = _otherFiltered;

        if (rows.length === 0) {
            _otherDetContainer.innerHTML =
                '<div class="text-center py-10 text-gray-500 fs-6">No records match the current filters.</div>';
            return;
        }

        var si = function (col) { return _sortIcon(_otherSortCol, col, _otherSortDir); };

        var html = '<div class="table-responsive">';
        html += '<table class="table table-row-bordered table-row-gray-200 align-middle gs-3 gy-3 detailed-table">';

        // Header
        html += '<thead><tr class="fw-bold text-gray-800 bg-light">';
        html += '<th class="min-w-50px text-center rounded-start">#</th>';
        html += '<th class="min-w-110px sortable-header" data-sort="month_num" data-tab="other">Month ' + si("month_num") + '</th>';
        html += '<th class="min-w-160px sortable-header" data-sort="invoice_type" data-tab="other">Invoice Type ' + si("invoice_type") + '</th>';
        html += '<th class="min-w-140px sortable-header" data-sort="class" data-tab="other">Class ' + si("class") + '</th>';
        html += '<th class="min-w-140px sortable-header" data-sort="batch" data-tab="other">Batch ' + si("batch") + '</th>';
        html += '<th class="min-w-120px text-center sortable-header" data-sort="student_count" data-tab="other">Students ' + si("student_count") + '</th>';
        html += '<th class="min-w-140px text-end rounded-end sortable-header" data-sort="due_amount" data-tab="other">Due Amount ' + si("due_amount") + '</th>';
        html += '</tr></thead>';

        // Body
        html += '<tbody>';
        var totalDue = 0, totalStudents = 0;

        rows.forEach(function (r, i) {
            totalDue += r.due_amount;
            totalStudents += r.student_count;

            html += '<tr>';
            html += '<td class="text-center text-gray-500 fw-semibold">' + (i + 1) + '</td>';
            html += '<td><span class="badge badge-light-primary fw-semibold">' + _esc(r.month) + '</span></td>';
            html += '<td><span class="badge badge-light-warning fw-semibold">' + _esc(r.invoice_type) + '</span></td>';
            html += '<td class="fw-semibold text-gray-800">' + _esc(r.class) + '</td>';
            html += '<td class="text-gray-700">' + _esc(r.batch) + '</td>';

            // Clickable student count badge
            html += '<td class="text-center">';
            html += '<a href="javascript:void(0);" class="badge badge-light-info student-due-link"';
            html += ' data-type="other"';
            html += ' data-month-num="' + r.month_num + '"';
            html += ' data-month="' + _esc(r.month) + '"';
            html += ' data-class-id="' + r.class_id + '"';
            html += ' data-class="' + _esc(r.class) + '"';
            html += ' data-batch-id="' + r.batch_id + '"';
            html += ' data-batch="' + _esc(r.batch) + '"';
            html += ' data-invoice-type-id="' + r.invoice_type_id + '"';
            html += ' data-invoice-type="' + _esc(r.invoice_type) + '"';
            html += '>' + r.student_count + ' students</a>';
            html += '</td>';

            html += '<td class="text-end fw-bold text-warning">' + _fmtRaw(r.due_amount) + '</td>';
            html += '</tr>';
        });
        html += '</tbody>';

        // Footer
        html += '<tfoot><tr class="footer-dark">';
        html += '<td class="rounded-start" colspan="5">Total (' + rows.length + ' records)</td>';
        html += '<td class="text-center">' + totalStudents + '</td>';
        html += '<td class="text-end rounded-end footer-grand-total">' + _fmtRaw(totalDue) + '</td>';
        html += '</tr></tfoot>';

        html += '</table></div>';
        _otherDetContainer.innerHTML = html;

        // Bind sort handlers
        _otherDetContainer.querySelectorAll('.sortable-header[data-tab="other"]').forEach(function (th) {
            th.style.cursor = "pointer";
            th.addEventListener("click", function () {
                var col = this.getAttribute("data-sort");
                if (_otherSortCol === col) {
                    _otherSortDir = _otherSortDir === "asc" ? "desc" : "asc";
                } else {
                    _otherSortCol = col;
                    _otherSortDir = "asc";
                }
                _sortArray(_otherFiltered, _otherSortCol, _otherSortDir);
                _renderOtherDetailed();
            });
        });
    };

    // ═══════════════════════════════════════════
    //  INVOICES MODAL (Student Due Click)
    // ═══════════════════════════════════════════

    /**
     * Load invoices for a specific group and show the modal.
     *
     * @param {Object} params  - AJAX query params (type, year, branch_id, month_num, class_id, batch_id, invoice_type_id)
     * @param {Array}  titleParts - Array of strings for the modal subtitle badges
     */
    var _loadInvoicesModal = function (params, titleParts) {
        // Set modal title
        var typeLabel = params.type === "tuition" ? "Tuition Fee" : "Other Fee";
        _invoicesModalTitle.innerHTML = '<i class="ki-outline ki-document fs-3 text-primary me-2"></i> Due Invoices — ' + _esc(typeLabel);

        // Build subtitle badges
        var subtitleHtml = '<div class="modal-subtitle">';
        titleParts.forEach(function (part) {
            subtitleHtml += '<span class="badge badge-light-primary">' + part + '</span>';
        });
        subtitleHtml += '</div>';

        // Show loader, clear body
        _invoicesModalBody.innerHTML = subtitleHtml;
        _invoicesModalLoader.classList.remove("d-none");

        // Show the modal
        $(_invoicesModal).modal("show");

        $.ajax({
            url: invoicesDataUrl,
            type: "GET",
            data: params,
            headers: { "X-Requested-With": "XMLHttpRequest" },
            success: function (res) {
                if (res.success) {
                    _renderInvoicesModal(res, subtitleHtml);
                } else {
                    _invoicesModalBody.innerHTML = subtitleHtml
                        + '<div class="text-center py-8 text-danger fw-semibold">'
                        + '<i class="ki-outline ki-information-3 fs-2x text-danger mb-3 d-block"></i>'
                        + (res.message || "Failed to load invoices.")
                        + '</div>';
                }
            },
            error: function (xhr) {
                var msg = "An error occurred while loading invoices.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                _invoicesModalBody.innerHTML = subtitleHtml
                    + '<div class="text-center py-8 text-danger fw-semibold">'
                    + '<i class="ki-outline ki-information-3 fs-2x text-danger mb-3 d-block"></i>'
                    + _esc(msg)
                    + '</div>';
            },
            complete: function () {
                _invoicesModalLoader.classList.add("d-none");
            }
        });
    };

    /**
     * Render the invoices table inside the modal.
     *
     * @param {Object} res           - AJAX response { data, total_due, total_invoices }
     * @param {String} subtitleHtml  - Pre-built subtitle badges HTML
     */
    var _renderInvoicesModal = function (res, subtitleHtml) {
        var invoices = res.data;

        if (!invoices || invoices.length === 0) {
            _invoicesModalBody.innerHTML = subtitleHtml
                + '<div class="text-center py-8 text-gray-500">'
                + '<i class="ki-outline ki-information-3 fs-2x text-gray-400 mb-3 d-block"></i>'
                + 'No invoices found for the selected criteria.'
                + '</div>';
            return;
        }

        var html = subtitleHtml;
        html += '<div class="table-responsive">';
        html += '<table class="table table-row-bordered table-row-gray-200 align-middle gs-3 gy-3 modal-table">';

        // Header
        html += '<thead>';
        html += '<tr class="fw-bold text-gray-800 bg-light">';
        html += '<th class="min-w-40px text-center ps-3 rounded-start">SL</th>';
        html += '<th class="min-w-160px">Invoice Number</th>';
        html += '<th class="min-w-200px">Student</th>';
        html += '<th class="min-w-120px text-end pe-3 rounded-end">Amount Due</th>';
        html += '</tr>';
        html += '</thead>';

        // Body
        html += '<tbody>';
        invoices.forEach(function (inv) {
            html += '<tr>';
            html += '<td class="text-center text-gray-500 fw-semibold ps-3">' + inv.sl + '</td>';

            // Invoice number — link to invoices.show
            html += '<td>';
            html += '<a href="' + invoiceShowBaseUrl + '/' + inv.id + '" class="text-primary fw-semibold" target="_blank">';
            html += '<i class="ki-outline ki-document fs-6 me-1"></i>';
            html += _esc(inv.invoice_number);
            html += '</a>';
            html += '</td>';

            // Student name — link to students.show
            html += '<td>';
            html += '<a href="' + studentShowBaseUrl + '/' + inv.student_id + '" class="text-gray-900 fw-semibold" target="_blank">';
            html += _esc(inv.student_name);
            if (inv.student_uid) {
                html += ' <span class="text-gray-500 fs-8">(' + _esc(inv.student_uid) + ')</span>';
            }
            html += '</a>';
            html += '</td>';

            // Amount due
            html += '<td class="text-end fw-bold text-danger pe-3">' + _fmtRaw(inv.amount_due) + '</td>';
            html += '</tr>';
        });
        html += '</tbody>';

        // Footer — Total Due row
        html += '<tfoot>';
        html += '<tr class="footer-dark">';
        html += '<td class="ps-3 rounded-start" colspan="3">';
        html += 'Total (' + res.total_invoices + ' invoice' + (res.total_invoices > 1 ? 's' : '') + ')';
        html += '</td>';
        html += '<td class="text-end pe-3 rounded-end footer-grand-total">' + _fmtRaw(res.total_due) + '</td>';
        html += '</tr>';
        html += '</tfoot>';

        html += '</table>';
        html += '</div>';

        _invoicesModalBody.innerHTML = html;
    };

    // ═══════════════════════════════════════════
    //  EXCEL EXPORT (SheetJS)
    // ═══════════════════════════════════════════

    var _exportExcel = function (mode) {
        if (!_data) {
            _swal("No report data available. Generate a report first.", "warning");
            return;
        }
        if (typeof XLSX === "undefined") {
            _swal("XLSX library not loaded. Please refresh the page.", "error");
            return;
        }

        var d = _data;
        var wb = XLSX.utils.book_new();
        var fileName = "Annual_Due_Report_" + d.branch_prefix + "_" + d.year;

        // ── Tuition Summary Sheet ──
        if (mode === "all" || mode === "tuition_summary") {
            var ts = [];
            ts.push(["Annual Due Report — Tuition Fee Summary"]);
            ts.push(["Branch: " + d.branch_name + " (" + d.branch_prefix + ")", "", "Year: " + d.year]);
            ts.push([]);

            var hdr = ["Class"];
            MONTH_SHORT.forEach(function (m) { hdr.push(m); });
            hdr.push("Total");
            ts.push(hdr);

            var mT = new Array(12).fill(0);
            var gT = 0;

            Object.keys(d.tuition.summary).forEach(function (cls) {
                var cd = d.tuition.summary[cls];
                var row = [cls];
                for (var m = 0; m < 12; m++) {
                    var amt = cd.months[MONTH_NAMES[m]] || 0;
                    row.push(amt || "");
                    mT[m] += amt;
                }
                row.push(cd.total || 0);
                gT += cd.total || 0;
                ts.push(row);
            });

            var tRow = ["Total"];
            mT.forEach(function (t) { tRow.push(t || ""); });
            tRow.push(gT);
            ts.push(tRow);

            var ws1 = XLSX.utils.aoa_to_sheet(ts);
            ws1["!cols"] = [{ wch: 22 }];
            for (var c = 0; c < 13; c++) ws1["!cols"].push({ wch: 14 });
            XLSX.utils.book_append_sheet(wb, ws1, "Tuition Summary");
        }

        // ── Tuition Detailed Sheet ──
        if (mode === "all" || mode === "tuition_detailed") {
            var td = [];
            td.push(["Annual Due Report — Tuition Fee Detailed"]);
            td.push(["Branch: " + d.branch_name + " (" + d.branch_prefix + ")", "", "Year: " + d.year]);
            td.push([]);
            td.push(["#", "Month", "Class", "Batch", "Students with Due", "Due Amount"]);

            var src = _tuitionFiltered.length > 0 ? _tuitionFiltered : d.tuition.detailed;
            var tDue = 0, tStu = 0;
            src.forEach(function (r, i) {
                tDue += r.due_amount;
                tStu += r.student_count;
                td.push([i + 1, r.month, r.class, r.batch, r.student_count, r.due_amount]);
            });
            td.push(["", "", "", "Total", tStu, tDue]);

            var ws2 = XLSX.utils.aoa_to_sheet(td);
            ws2["!cols"] = [{ wch: 6 }, { wch: 12 }, { wch: 22 }, { wch: 22 }, { wch: 18 }, { wch: 16 }];
            XLSX.utils.book_append_sheet(wb, ws2, "Tuition Detailed");
        }

        // ── Other Fees Summary Sheet ──
        if (mode === "all" || mode === "other_summary") {
            var os = [];
            os.push(["Annual Due Report — Other Fees Summary (Invoice Type-wise)"]);
            os.push(["Branch: " + d.branch_name + " (" + d.branch_prefix + ")", "", "Year: " + d.year]);
            os.push([]);

            var oHdr = ["Invoice Type"];
            MONTH_SHORT.forEach(function (m) { oHdr.push(m); });
            oHdr.push("Total");
            os.push(oHdr);

            var oMT = new Array(12).fill(0);
            var oGT = 0;

            Object.keys(d.other.summary).forEach(function (typeName) {
                var td2 = d.other.summary[typeName];
                var row = [typeName];
                for (var m = 0; m < 12; m++) {
                    var amt = td2.months[MONTH_NAMES[m]] || 0;
                    row.push(amt || "");
                    oMT[m] += amt;
                }
                row.push(td2.total || 0);
                oGT += td2.total || 0;
                os.push(row);
            });

            var oTRow = ["Total"];
            oMT.forEach(function (t) { oTRow.push(t || ""); });
            oTRow.push(oGT);
            os.push(oTRow);

            var ws3 = XLSX.utils.aoa_to_sheet(os);
            ws3["!cols"] = [{ wch: 24 }];
            for (var c2 = 0; c2 < 13; c2++) ws3["!cols"].push({ wch: 14 });
            XLSX.utils.book_append_sheet(wb, ws3, "Other Fees Summary");
        }

        // ── Other Fees Detailed Sheet ──
        if (mode === "all" || mode === "other_detailed") {
            var od = [];
            od.push(["Annual Due Report — Other Fees Detailed"]);
            od.push(["Branch: " + d.branch_name + " (" + d.branch_prefix + ")", "", "Year: " + d.year]);
            od.push([]);
            od.push(["#", "Month", "Invoice Type", "Class", "Batch", "Students with Due", "Due Amount"]);

            var oSrc = _otherFiltered.length > 0 ? _otherFiltered : d.other.detailed;
            var oDue = 0, oStu = 0;
            oSrc.forEach(function (r, i) {
                oDue += r.due_amount;
                oStu += r.student_count;
                od.push([i + 1, r.month, r.invoice_type, r.class, r.batch, r.student_count, r.due_amount]);
            });
            od.push(["", "", "", "", "Total", oStu, oDue]);

            var ws4 = XLSX.utils.aoa_to_sheet(od);
            ws4["!cols"] = [{ wch: 6 }, { wch: 12 }, { wch: 22 }, { wch: 22 }, { wch: 22 }, { wch: 18 }, { wch: 16 }];
            XLSX.utils.book_append_sheet(wb, ws4, "Other Fees Detailed");
        }

        XLSX.writeFile(wb, fileName + ".xlsx");

        if (typeof Swal !== "undefined") {
            Swal.fire({
                text: "Report exported successfully!",
                icon: "success",
                buttonsStyling: false,
                confirmButtonText: "OK",
                customClass: { confirmButton: "btn btn-primary" },
                timer: 2000,
                timerProgressBar: true
            });
        }
    };

    // ═══════════════════════════════════════════
    //  PUBLIC API
    // ═══════════════════════════════════════════

    return {
        init: function () {
            _initDom();
            _initEvents();
        },
        exportExcel: function (mode) {
            _exportExcel(mode);
        }
    };
})();

// DOM Ready
KTUtil.onDOMContentLoaded(function () {
    KTAnnualDueReport.init();
});
