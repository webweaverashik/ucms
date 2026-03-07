"use strict";

/**
 * Attendance Report Module
 * For Metronic 8.2.6 + Bootstrap 5.3
 *
 * This file handles:
 * - Date Range Picker initialization
 * - Branch-Batch dynamic loading
 * - Academic Group visibility toggle
 * - DataTable with aggregation
 * - Form validation
 * - Chart visualization with ApexCharts
 * - Export functionality with title, date range, and export time
 * - Chart export as JPG
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
 * Module: Academic Group Handler
 * Handles showing/hiding academic group dropdown based on class selection
 */
var KTAcademicGroupHandler = (function () {
    var classSelect, academicGroupWrapper, academicGroupSelect;
    var config;

    // Check if class requires academic group selection
    function classRequiresGroup(classNumeral) {
        return config.groupRequiredClasses.indexOf(classNumeral) !== -1;
    }

    // Get selected class numeral
    function getSelectedClassNumeral() {
        var selectedOption = classSelect.options[classSelect.selectedIndex];
        return selectedOption ? selectedOption.dataset.classNumeral : null;
    }

    // Reinitialize Select2 for element
    function reinitSelect2(element, selectFirstOption) {
        if (!element) return;
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            var $el = jQuery(element);
            if ($el.data('select2')) {
                $el.select2('destroy');
            }
            $el.select2({
                placeholder: element.dataset.placeholder || 'Select an option',
                allowClear: element.dataset.allowClear === 'true',
                minimumResultsForSearch: element.dataset.hideSearch === 'true' ? Infinity : 0,
                dropdownParent: $(element.dataset.dropdownParent || 'body')
            });
            
            // Auto-select first option if requested (for "All Groups")
            if (selectFirstOption && element.options.length > 0) {
                $el.val(element.options[0].value).trigger('change');
            }
        }
    }

    // Toggle element visibility
    function toggleElement(element, show) {
        if (!element) return;
        if (show) {
            element.classList.remove('d-none');
        } else {
            element.classList.add('d-none');
        }
    }

    // Handle class change
    function handleClassChange() {
        var classNumeral = getSelectedClassNumeral();

        if (classNumeral && classRequiresGroup(classNumeral)) {
            toggleElement(academicGroupWrapper, true);
            // Auto-select "All Groups" (first option with empty value) when shown
            if (academicGroupSelect) {
                // Reinitialize Select2 and auto-select "All Groups" (first option)
                reinitSelect2(academicGroupSelect, true);
            }
        } else {
            toggleElement(academicGroupWrapper, false);
            // Clear selection when hidden
            if (academicGroupSelect) {
                academicGroupSelect.value = '';
                reinitSelect2(academicGroupSelect, false);
            }
        }
    }

    // Initialize class change listener
    function initClassChangeListener() {
        if (!classSelect) return;

        // For Select2
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            jQuery(classSelect).on('select2:select select2:clear', handleClassChange);
        }
        // Native fallback
        classSelect.addEventListener('change', handleClassChange);
    }

    return {
        init: function () {
            config = window.AttendanceReportConfig || {};
            classSelect = document.getElementById('student_class_group');
            academicGroupWrapper = document.getElementById('academic_group_wrapper');
            academicGroupSelect = document.getElementById('student_academic_group');

            if (!classSelect || !academicGroupWrapper) {
                console.warn('KTAcademicGroupHandler: Required elements not found');
                return;
            }

            initClassChangeListener();
        },

        // Public method to check if group is required for current class
        isGroupRequired: function () {
            var classNumeral = getSelectedClassNumeral();
            return classNumeral && classRequiresGroup(classNumeral);
        },

        // Public method to get selected group
        getSelectedGroup: function () {
            return academicGroupSelect ? academicGroupSelect.value : null;
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
 * Module: Attendance Chart
 * Handles chart rendering with ApexCharts
 */
var KTAttendanceChart = (function () {
    var pieChart = null;
    var barChart = null;
    var chartCard;
    var chartData = {
        present: 0,
        late: 0,
        absent: 0,
        dailyData: []
    };
    var reportInfo = {
        dateRange: '',
        groupName: 'All',
        totalStudents: 0
    };

    // Chart colors
    var colors = {
        present: '#50cd89',  // Success green
        late: '#ffc700',     // Warning yellow
        absent: '#f1416c'    // Danger red
    };

    // Initialize pie chart
    function initPieChart() {
        var el = document.getElementById('attendance_pie_chart');
        if (!el) return;

        var options = {
            series: [0, 0, 0],
            chart: {
                type: 'donut',
                height: 350,
                fontFamily: 'inherit',
                animations: {
                    enabled: true
                }
            },
            labels: ['Present', 'Late', 'Absent'],
            colors: [colors.present, colors.late, colors.absent],
            legend: {
                position: 'bottom',
                fontSize: '14px',
                fontWeight: 500,
                labels: {
                    colors: '#5e6278'
                },
                markers: {
                    width: 12,
                    height: 12,
                    radius: 12
                },
                itemMargin: {
                    horizontal: 10,
                    vertical: 5
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '16px',
                                fontWeight: 600,
                                color: '#181c32'
                            },
                            value: {
                                show: true,
                                fontSize: '24px',
                                fontWeight: 700,
                                color: '#181c32',
                                formatter: function (val) {
                                    return parseInt(val);
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total Records',
                                fontSize: '14px',
                                fontWeight: 500,
                                color: '#a1a5b7',
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce(function (a, b) {
                                        return a + b;
                                    }, 0);
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val, opts) {
                    return opts.w.config.series[opts.seriesIndex];
                },
                dropShadow: {
                    enabled: false
                }
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        height: 300
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }],
            stroke: {
                width: 0
            },
            tooltip: {
                y: {
                    formatter: function (val, opts) {
                        var total = opts.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0);
                        var percentage = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                        return percentage + '% (' + val + ' records)';
                    }
                }
            }
        };

        if (typeof ApexCharts !== 'undefined') {
            pieChart = new ApexCharts(el, options);
            pieChart.render();
        }
    }

    // Initialize bar chart for daily breakdown
    function initBarChart() {
        var el = document.getElementById('attendance_bar_chart');
        if (!el) return;

        var options = {
            series: [{
                name: 'Present',
                data: []
            }, {
                name: 'Late',
                data: []
            }, {
                name: 'Absent',
                data: []
            }],
            chart: {
                type: 'bar',
                height: 350,
                stacked: true,
                fontFamily: 'inherit',
                toolbar: {
                    show: false
                },
                animations: {
                    enabled: true
                }
            },
            colors: [colors.present, colors.late, colors.absent],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '60%',
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: [],
                labels: {
                    style: {
                        colors: '#a1a5b7',
                        fontSize: '12px'
                    },
                    rotate: -45,
                    rotateAlways: false
                }
            },
            yaxis: {
                title: {
                    text: 'Number of Records',
                    style: {
                        color: '#a1a5b7',
                        fontSize: '12px',
                        fontWeight: 500
                    }
                },
                labels: {
                    style: {
                        colors: '#a1a5b7',
                        fontSize: '12px'
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                fontSize: '13px',
                fontWeight: 500,
                labels: {
                    colors: '#5e6278'
                },
                markers: {
                    width: 12,
                    height: 12,
                    radius: 12
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (val, opts) {
                        // Calculate daily total for the hovered bar (sum of all series at that data point)
                        // Use opts.w.globals.series for ApexCharts bar chart
                        try {
                            var dataPointIndex = opts.dataPointIndex;
                            var series = opts.w && opts.w.globals && opts.w.globals.series ? opts.w.globals.series : [];
                            var dailyTotal = 0;
                            series.forEach(function (s) {
                                dailyTotal += (s[dataPointIndex] || 0);
                            });
                            var percentage = dailyTotal > 0 ? ((val / dailyTotal) * 100).toFixed(1) : 0;
                            return percentage + '% (' + val + ' records)';
                        } catch (e) {
                            return val + ' records';
                        }
                    }
                }
            },
            grid: {
                borderColor: '#eff2f5',
                strokeDashArray: 4
            },
            title: {
                text: 'Daily Attendance Breakdown',
                align: 'left',
                style: {
                    fontSize: '14px',
                    fontWeight: 600,
                    color: '#181c32'
                }
            }
        };

        if (typeof ApexCharts !== 'undefined') {
            barChart = new ApexCharts(el, options);
            barChart.render();
        }
    }

    // Update charts with data
    function updateCharts(data) {
        chartData = data;

        // Update pie chart
        if (pieChart) {
            pieChart.updateSeries([data.present, data.late, data.absent]);
        }

        // Update bar chart
        if (barChart && data.dailyData && data.dailyData.length > 0) {
            var categories = data.dailyData.map(function (d) { return d.date; });
            var presentData = data.dailyData.map(function (d) { return d.present; });
            var lateData = data.dailyData.map(function (d) { return d.late; });
            var absentData = data.dailyData.map(function (d) { return d.absent; });

            barChart.updateOptions({
                xaxis: {
                    categories: categories
                }
            });

            barChart.updateSeries([
                { name: 'Present', data: presentData },
                { name: 'Late', data: lateData },
                { name: 'Absent', data: absentData }
            ]);
        }

        // Update summary cards
        updateSummaryCards(data);
    }

    // Update summary cards - Focus on percentages
    function updateSummaryCards(data) {
        var total = data.present + data.late + data.absent;
        
        var presentEl = document.getElementById('summary_present');
        var lateEl = document.getElementById('summary_late');
        var absentEl = document.getElementById('summary_absent');
        var presentPercentEl = document.getElementById('summary_present_percent');
        var latePercentEl = document.getElementById('summary_late_percent');
        var absentPercentEl = document.getElementById('summary_absent_percent');
        var totalStudentsEl = document.getElementById('display_total_students');

        // Update counts (secondary info)
        if (presentEl) presentEl.textContent = data.present;
        if (lateEl) lateEl.textContent = data.late;
        if (absentEl) absentEl.textContent = data.absent;

        // Update percentages (primary focus)
        if (total > 0) {
            var presentPercent = ((data.present / total) * 100).toFixed(1);
            var latePercent = ((data.late / total) * 100).toFixed(1);
            var absentPercent = ((data.absent / total) * 100).toFixed(1);
            
            if (presentPercentEl) presentPercentEl.textContent = presentPercent + '%';
            if (latePercentEl) latePercentEl.textContent = latePercent + '%';
            if (absentPercentEl) absentPercentEl.textContent = absentPercent + '%';
        } else {
            if (presentPercentEl) presentPercentEl.textContent = '0%';
            if (latePercentEl) latePercentEl.textContent = '0%';
            if (absentPercentEl) absentPercentEl.textContent = '0%';
        }

        if (totalStudentsEl) totalStudentsEl.textContent = reportInfo.totalStudents;
    }

    // Set report info
    function setReportInfo(info) {
        reportInfo = info;

        var dateRangeEl = document.getElementById('display_date_range');
        var groupEl = document.getElementById('display_group');
        var totalStudentsEl = document.getElementById('display_total_students');

        if (dateRangeEl) dateRangeEl.textContent = info.dateRange || '-';
        if (groupEl) groupEl.textContent = info.groupName || 'All';
        if (totalStudentsEl) totalStudentsEl.textContent = info.totalStudents || 0;
    }

    // Show chart card
    function showChartCard() {
        if (chartCard) {
            chartCard.classList.remove('d-none');
        }
    }

    // Hide chart card
    function hideChartCard() {
        if (chartCard) {
            chartCard.classList.add('d-none');
        }
    }

    return {
        init: function () {
            chartCard = document.getElementById('attendance_chart_card');

            // Initialize charts
            initPieChart();
            initBarChart();
        },

        // Update chart with new data
        update: function (data, info) {
            setReportInfo(info);
            updateCharts(data);
            showChartCard();
        },

        // Hide chart
        hide: function () {
            hideChartCard();
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
    var form, submitButton, tableBody, dateInput, branchSelect, classSelect, batchSelect, academicGroupSelect;
    var exportListenerAttached = false;
    var searchListenerAttached = false;
    var dtButtons = null;
    var config;
    var currentReportData = null;

    // Logging helpers (set DEBUG to true to enable console logging)
    var DEBUG = false;
    
    function log() {
        if (DEBUG && window.console && console.log) console.log.apply(console, arguments);
    }

    function warn() {
        if (DEBUG && window.console && console.warn) console.warn.apply(console, arguments);
    }

    function error() {
        if (DEBUG && window.console && console.error) console.error.apply(console, arguments);
    }

    // Helper to create <td> with text safely
    function tdWithText(text) {
        var td = document.createElement("td");
        td.textContent = text === undefined || text === null ? "" : text;
        return td;
    }

    // Build student show URL
    function getStudentShowUrl(studentId) {
        if (!config.studentShowUrl) {
            return '#';
        }
        return config.studentShowUrl.replace('__STUDENT_ID__', encodeURIComponent(studentId));
    }

    // Check if class requires academic group
    function classRequiresGroup(classNumeral) {
        return config.groupRequiredClasses && config.groupRequiredClasses.indexOf(classNumeral) !== -1;
    }

    // Get selected class numeral
    function getSelectedClassNumeral() {
        if (!classSelect) return null;
        var selectedOption = classSelect.options[classSelect.selectedIndex];
        return selectedOption ? selectedOption.dataset.classNumeral : null;
    }

    // Get academic group badge class
    function getGroupBadgeClass(groupName) {
        if (!groupName) return 'badge-light-secondary';
        switch (groupName.toLowerCase()) {
            case 'science':
                return 'badge-light-info';
            case 'commerce':
                return 'badge-light-success';
            case 'arts':
                return 'badge-light-warning';
            default:
                return 'badge-light-secondary';
        }
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Format current date time for export
    function getExportDateTime() {
        return moment().format('DD-MM-YYYY hh:mm:ss A');
    }

    // Get report title for export
    function getReportTitle() {
        return 'Attendance Report';
    }

    // Get date range for export
    function getExportDateRange() {
        return dateInput ? dateInput.value : '';
    }

    // Get selected group name
    function getSelectedGroupName() {
        if (academicGroupSelect && academicGroupSelect.value) {
            return academicGroupSelect.value;
        }
        return 'All Groups';
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

    // Show/hide group column using DataTables API
    function updateGroupColumnVisibility(showGroup) {
        // Use DataTables column visibility API to ensure header and data stay in sync
        if (datatable && typeof datatable.column === 'function') {
            try {
                datatable.column(3).visible(showGroup);
            } catch (e) {
                warn('updateGroupColumnVisibility error:', e);
            }
        }
    }

    // Update report info display
    function updateReportInfoDisplay(dateRange, groupName) {
        var dateRangeEl = document.getElementById('display_date_range');
        var groupEl = document.getElementById('display_group');

        if (dateRangeEl) {
            dateRangeEl.textContent = dateRange || '-';
        }
        if (groupEl) {
            groupEl.textContent = groupName || 'All';
        }
    }

    // DataTable initialization (destroy safe)
    function initDatatable() {
        try {
            if (!table) {
                warn("initDatatable: missing table element");
                return;
            }
            if ($.fn.dataTable.isDataTable(table)) {
                try {
                    datatable.destroy();
                } catch (e) {
                    warn("datatable.destroy() failed:", e);
                }
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
            columns: [
                { data: 0 }, // #
                { data: 1 }, // Student Name
                { data: 2 }, // Class
                { data: 3, visible: false }, // Group - hidden by default
                { data: 4 }, // Batch
                { data: 5 }, // Present
                { data: 6 }, // Absent
                { data: 7 }  // Late
            ]
        });

        log("DataTable initialized.");
    }

    // Export Buttons
    function exportButtons() {
        try {
            if (dtButtons && typeof dtButtons.destroy === "function") dtButtons.destroy();
        } catch (e) { /* ignore */ }

        try {
            $(".dt-buttons").remove();
        } catch (e) { /* ignore */ }

        try {
            $("#kt_hidden_export_buttons").empty();
        } catch (e) { /* ignore */ }

        if (!document.getElementById("kt_hidden_export_buttons")) {
            var div = document.createElement("div");
            div.id = "kt_hidden_export_buttons";
            div.style.display = "none";
            document.body.appendChild(div);
        }

        var reportTitle = getReportTitle();
        var dateRange = getExportDateRange();
        var exportTime = getExportDateTime();
        var groupName = getSelectedGroupName();

        var documentTitle = reportTitle + ' (' + dateRange + ')';

        // Build export header info
        var exportHeaderInfo = [
            reportTitle,
            'Date Range: ' + dateRange,
            'Group: ' + groupName,
            'Exported: ' + exportTime,
            '' // Empty row for spacing
        ];

        try {
            if (datatable && $.fn.dataTable && $.fn.dataTable.Buttons) {
                dtButtons = new $.fn.dataTable.Buttons(datatable, {
                    buttons: [
                        {
                            extend: "copyHtml5",
                            className: "buttons-copy",
                            title: documentTitle,
                            messageTop: exportHeaderInfo.join('\n'),
                            exportOptions: {
                                columns: ":visible:not(.not-export)"
                            }
                        },
                        {
                            extend: "excelHtml5",
                            className: "buttons-excel",
                            title: documentTitle,
                            messageTop: exportHeaderInfo.join('\n'),
                            exportOptions: {
                                columns: ":visible:not(.not-export)"
                            },
                            customize: function (xlsx) {
                                var sheet = xlsx.xl.worksheets['sheet1.xml'];
                                // Add styling if needed
                            }
                        },
                        {
                            extend: "csvHtml5",
                            className: "buttons-csv",
                            title: documentTitle,
                            exportOptions: {
                                columns: ":visible:not(.not-export)"
                            },
                            customize: function (csv) {
                                // Prepend header info to CSV
                                var headerLines = exportHeaderInfo.join('\n') + '\n\n';
                                return headerLines + csv;
                            }
                        },
                        {
                            extend: "pdfHtml5",
                            className: "buttons-pdf",
                            title: reportTitle,
                            exportOptions: {
                                columns: ":visible:not(.not-export)"
                            },
                            customize: function (doc) {
                                // Add header with title, date range, and export time
                                doc.content.splice(0, 0, {
                                    text: reportTitle,
                                    style: 'title',
                                    alignment: 'center',
                                    margin: [0, 0, 0, 10]
                                });
                                doc.content.splice(1, 0, {
                                    text: 'Date Range: ' + dateRange + ' | Group: ' + groupName,
                                    style: 'subheader',
                                    alignment: 'center',
                                    margin: [0, 0, 0, 5]
                                });
                                doc.content.splice(2, 0, {
                                    text: 'Exported: ' + exportTime,
                                    style: 'exportTime',
                                    alignment: 'center',
                                    margin: [0, 0, 0, 15]
                                });

                                // Define custom styles
                                doc.styles.title = {
                                    fontSize: 16,
                                    bold: true,
                                    color: '#333333'
                                };
                                doc.styles.subheader = {
                                    fontSize: 11,
                                    bold: false,
                                    color: '#666666'
                                };
                                doc.styles.exportTime = {
                                    fontSize: 9,
                                    bold: false,
                                    color: '#999999',
                                    italics: true
                                };

                                // Page margins
                                doc.pageMargins = [20, 20, 20, 40];
                                doc.defaultStyle.fontSize = 10;

                                // Add footer with page numbers
                                doc.footer = function (currentPage, pageCount) {
                                    return {
                                        columns: [
                                            {
                                                text: 'Generated by Attendance System',
                                                alignment: 'left',
                                                margin: [20, 0],
                                                fontSize: 8,
                                                color: '#999999'
                                            },
                                            {
                                                text: 'Page ' + currentPage.toString() + ' of ' + pageCount,
                                                alignment: 'right',
                                                margin: [0, 0, 20, 0],
                                                fontSize: 8,
                                                color: '#999999'
                                            }
                                        ]
                                    };
                                };
                            }
                        }
                    ]
                });

                try {
                    dtButtons.container().appendTo("#kt_hidden_export_buttons");
                } catch (e) { /* ignore */ }

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
                    tr.querySelectorAll("td,th").forEach(function (cell) {
                        cols.push(cell.textContent.trim());
                    });
                    rows.push(cols);
                });
            }
            var header = [];
            table.querySelectorAll("thead th:not(.d-none)").forEach(function (th) {
                header.push(th.textContent.trim());
            });
            if (header.length) rows.unshift(header);

            // Prepend export info
            var exportInfo = [
                [reportTitle],
                ['Date Range: ' + dateRange],
                ['Group: ' + groupName],
                ['Exported: ' + exportTime],
                [''] // Empty row
            ];

            return exportInfo.concat(rows).map(function (r) {
                return r.map(function (c) {
                    return '"' + String(c).replace(/"/g, '""') + '"';
                }).join(",");
            }).join("\r\n");
        }

        function triggerDownload(filename, text, mime) {
            var blob = new Blob([text], { type: mime || "text/csv;charset=utf-8;" });
            var link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            setTimeout(function () {
                document.body.removeChild(link);
                URL.revokeObjectURL(link.href);
            }, 150);
        }

        function copyToClipboard(text) {
            if (navigator.clipboard && navigator.clipboard.writeText)
                return navigator.clipboard.writeText(text);
            var ta = document.createElement("textarea");
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand("copy"); } catch (e) { warn("copy execCommand failed", e); }
            document.body.removeChild(ta);
            return Promise.resolve();
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
                                try {
                                    datatable.button(idx).trigger();
                                    return;
                                } catch (err) {
                                    warn("datatable.button(idx).trigger failed:", err);
                                }
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
                        }).catch(function (e) {
                            error("Copy fallback error:", e);
                            alert("Copy failed");
                        });
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
                            var docDef = {
                                content: [
                                    { text: reportTitle, style: "header" },
                                    { text: "Date Range: " + dateRange + " | Group: " + groupName, style: "subheader" },
                                    { text: "Exported: " + exportTime, style: "exportTime" },
                                    { text: lines.join("\n"), style: "table" }
                                ],
                                styles: {
                                    header: { fontSize: 14, bold: true },
                                    subheader: { fontSize: 10, margin: [0, 5, 0, 5] },
                                    exportTime: { fontSize: 8, italics: true, color: 'gray' }
                                }
                            };
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
        if (!input) {
            warn("handleSearch: search input not found.");
            return;
        }
        if (searchListenerAttached) return;
        searchListenerAttached = true;

        var timer = null;
        input.addEventListener("keyup", function (e) {
            var val = e.target.value || "";
            if (timer) clearTimeout(timer);
            timer = setTimeout(function () {
                try {
                    if (datatable && typeof datatable.search === "function")
                        datatable.search(val).draw();
                } catch (err) {
                    warn("search handler error:", err);
                }
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
            if (typeof v === "number") {
                if (v === 1) return "present";
                if (v === 0) return "absent";
            }
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

    // Aggregate records by student and prepare chart data
    function aggregateRecords(payload) {
        var rows = payload && Array.isArray(payload.data) ? payload.data : [];
        var map = {};
        var dailyMap = {};
        var totals = { present: 0, late: 0, absent: 0 };

        rows.forEach(function (r) {
            var student = r.student || {};
            var sid = student.id || r.student_id || null;
            var uniqueId = student.student_unique_id || r.student_unique_id || r.student_uniqueid || "";
            var key = sid ? "id_" + sid : "u_" + (uniqueId || Math.random().toString(36).slice(2));

            // Get attendance date for daily breakdown
            var attendanceDate = r.attendance_date || '';
            if (attendanceDate && typeof moment !== 'undefined') {
                // Format date for display
                var formattedDate = moment(attendanceDate).format('DD-MM');
                if (!dailyMap[formattedDate]) {
                    dailyMap[formattedDate] = { date: formattedDate, present: 0, late: 0, absent: 0 };
                }
            }

            if (!map[key]) {
                map[key] = {
                    studentId: sid || "",
                    name: student.name || r.student_name || r.name || "Unknown",
                    uniqueId: uniqueId || "",
                    className: (r.classname && r.classname.name) || r.class_name || r.className || "",
                    classNumeral: (r.classname && r.classname.class_numeral) || r.class_numeral || "",
                    batchName: (r.batch && r.batch.name) || r.batch_name || r.batchName || "",
                    academicGroup: student.academic_group || r.academic_group || "",
                    present: 0,
                    absent: 0,
                    late: 0
                };
            }

            var st = getRecordStatus(r);
            if (st === "present") {
                map[key].present++;
                totals.present++;
                if (dailyMap[formattedDate]) dailyMap[formattedDate].present++;
            } else if (st === "late") {
                map[key].late++;
                totals.late++;
                if (dailyMap[formattedDate]) dailyMap[formattedDate].late++;
            } else {
                map[key].absent++;
                totals.absent++;
                if (dailyMap[formattedDate]) dailyMap[formattedDate].absent++;
            }
        });

        // Convert daily map to sorted array
        var dailyData = Object.keys(dailyMap).sort(function (a, b) {
            return moment(a, 'DD-MM').valueOf() - moment(b, 'DD-MM').valueOf();
        }).map(function (key) {
            return dailyMap[key];
        });

        var records = Object.keys(map).map(function (k) { return map[k]; });

        return {
            records: records,
            chartData: {
                present: totals.present,
                late: totals.late,
                absent: totals.absent,
                dailyData: dailyData
            }
        };
    }

    // Render using DataTables API
    function renderAggregatedTable(records, supportsGroup, isAllGroups) {
        try {
            records = Array.isArray(records) ? records : [];
            log("[Attendance] renderAggregatedTable called, records:", records.length);

            // Update group column visibility
            var showGroupColumn = supportsGroup && isAllGroups;
            updateGroupColumnVisibility(showGroupColumn);

            // Update report info display
            updateReportInfoDisplay(getExportDateRange(), getSelectedGroupName());

            if (!records.length) {
                var msg = "No records found for the selected filters.";
                if (typeof toastr !== "undefined") toastr.info(msg);
                if (datatable && typeof datatable.clear === "function") {
                    datatable.clear();
                    datatable.draw(false);
                }
                // Hide chart if no data
                KTAttendanceChart.hide();
                return;
            }

            var dtRows = records.map(function (rec, idx) {
                var studentUrl = getStudentShowUrl(rec.studentId);

                // Build name with link
                var nameHtml = '<div class="d-flex flex-column">' +
                    '<a href="' + studentUrl + '" target="_blank" rel="noopener noreferrer" class="text-gray-800 text-hover-primary fw-bold">' +
                    escapeHtml(rec.name) + '</a>' +
                    '<span class="text-muted fs-7">' + (rec.uniqueId ? ("ID: " + escapeHtml(rec.uniqueId)) : "") + '</span>' +
                    '</div>';

                // Academic group badge
                var groupHtml = '';
                if (rec.academicGroup) {
                    var badgeClass = getGroupBadgeClass(rec.academicGroup);
                    groupHtml = '<span class="badge ' + badgeClass + ' fs-7">' + escapeHtml(rec.academicGroup) + '</span>';
                } else {
                    groupHtml = '<span class="text-muted fs-8">-</span>';
                }

                return [
                    idx + 1,
                    nameHtml,
                    escapeHtml(rec.className) || "",
                    groupHtml,
                    escapeHtml(rec.batchName) || "",
                    '<span class="badge badge-light-success fs-7 fw-bold">' + (rec.present || 0) + '</span>',
                    '<span class="badge badge-light-danger fs-7 fw-bold">' + (rec.absent || 0) + '</span>',
                    '<span class="badge badge-light-warning fs-7 fw-bold">' + (rec.late || 0) + '</span>'
                ];
            });

            if (datatable && typeof datatable.clear === "function") {
                try { $(".dt-buttons").remove(); } catch (e) { /* ignore */ }

                datatable.clear();
                datatable.rows.add(dtRows);

                // Update column visibility after adding rows
                updateGroupColumnVisibility(showGroupColumn);

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
        var academicGroup = academicGroupSelect ? academicGroupSelect.value : "";

        if (dateRange) params.append("date_range", dateRange);
        if (branchId) params.append("branch_id", branchId);
        if (classId) params.append("class_id", classId);
        if (batchId) params.append("batch_id", batchId);
        if (academicGroup) params.append("academic_group", academicGroup);

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
        } catch (e) {
            warn("setLoading error:", e);
        }
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

    // Fetch attendance data async
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

            currentReportData = payload;

            var result = aggregateRecords(payload);
            var supportsGroup = payload.supports_group || false;
            var isAllGroups = payload.is_all_groups || false;

            // Render table
            renderAggregatedTable(result.records, supportsGroup, isAllGroups);
            exportButtons();

            // Update chart
            if (result.records.length > 0) {
                KTAttendanceChart.update(result.chartData, {
                    dateRange: getExportDateRange(),
                    groupName: getSelectedGroupName(),
                    totalStudents: result.records.length
                });
            } else {
                KTAttendanceChart.hide();
            }

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
            if (!table) {
                error("KTAttendanceReportTable: table element not found");
                return;
            }

            form = document.getElementById("student_list_filter_form");
            submitButton = document.getElementById("submit_button");
            dateInput = document.getElementById("attendance_daterangepicker");
            branchSelect = document.getElementById("student_branch_group");
            classSelect = document.getElementById("student_class_group");
            batchSelect = document.getElementById("student_batch_group");
            academicGroupSelect = document.getElementById("student_academic_group");

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
    KTAcademicGroupHandler.init();
    KTSelect2InputGroupFix.init();
    KTAttendanceChart.init();
    KTAttendanceReportTable.init();
});
