"use strict";

/**
 * UCMS Finance Report Module
 * Revenue vs Cost Report
 * Metronic 8 + Bootstrap 5 + DataTables + Toastr
 */

var KTFinanceReport = (function () {
    // ============================================
    // STATE & CONFIGURATION
    // ============================================
    let financeChart = null;
    let reportData = null;

    const config = window.FinanceReportConfig || {};
    const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Gradient classes for collector cards
    const collectorGradients = [
        'collector-gradient-1',
        'collector-gradient-2',
        'collector-gradient-3',
        'collector-gradient-4',
        'collector-gradient-5',
        'collector-gradient-6',
        'collector-gradient-7',
        'collector-gradient-8',
        'collector-gradient-9',
        'collector-gradient-10'
    ];

    // ============================================
    // ELEMENTS CACHE
    // ============================================
    const elements = {};

    const initElements = function () {
        elements.form = document.getElementById('finance_report_form');
        elements.dateRangeInput = document.getElementById('finance_daterangepicker');
        elements.branchSelect = document.getElementById('branch_id');
        elements.generateBtn = document.getElementById('generate_report_btn');
        elements.loader = document.getElementById('finance_report_loader');
        elements.summaryCards = document.getElementById('summary_cards');
        elements.resultContainer = document.getElementById('finance_report_result');
        elements.chartSection = document.getElementById('chart_section');
        elements.chartCanvas = document.getElementById('finance_report_graph');
        elements.exportButtons = document.getElementById('export_buttons');
        elements.exportExcelBtn = document.getElementById('export_excel_btn');
        elements.exportChartBtn = document.getElementById('export_chart_btn');

        // Collector Summary elements
        elements.collectorSummarySection = document.getElementById('collector_summary_section');
        elements.collectorSummaryCards = document.getElementById('collector_summary_cards');
        elements.collectorCountBadge = document.getElementById('collector_count_badge');
    };

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    const formatCurrency = function (amount) {
        return '৳' + parseInt(amount).toLocaleString('en-BD');
    };

    const formatDate = function (dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
    };

    const setButtonLoading = function (btn, loading) {
        if (!btn) return;
        if (loading) {
            btn.setAttribute('data-kt-indicator', 'on');
            btn.disabled = true;
        } else {
            btn.removeAttribute('data-kt-indicator');
            btn.disabled = false;
        }
    };

    const showLoader = function () {
        elements.loader?.classList.remove('d-none');
        elements.summaryCards?.classList.add('d-none');
        elements.collectorSummarySection?.classList.add('d-none');
        elements.resultContainer.innerHTML = '';
        elements.chartSection?.classList.add('d-none');
        elements.exportButtons?.classList.remove('show');
    };

    const hideLoader = function () {
        elements.loader?.classList.add('d-none');
    };

    const getGradientClass = function (index) {
        return collectorGradients[index % collectorGradients.length];
    };

    // ============================================
    // DATE RANGE PICKER
    // ============================================
    const initDateRangePicker = function () {
        if (!elements.dateRangeInput) return;

        const start = moment().startOf('month');
        const end = moment();

        $(elements.dateRangeInput).daterangepicker({
            startDate: start,
            endDate: end,
            maxDate: moment(),
            locale: { format: 'DD-MM-YYYY' },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });

        elements.dateRangeInput.value = start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY');
    };

    // ============================================
    // GENERATE REPORT
    // ============================================
    const generateReport = function (e) {
        e.preventDefault();

        if (!elements.dateRangeInput?.value) {
            toastr.error('Please select a date range');
            return;
        }

        const branchId = document.getElementById('branch_id')?.value;
        if (config.isAdmin && !branchId) {
            toastr.error('Please select a branch');
            return;
        }

        setButtonLoading(elements.generateBtn, true);
        showLoader();

        const formData = new FormData(elements.form);

        fetch(config.routes.generate, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
            .then(r => r.json())
            .then(res => {
                setButtonLoading(elements.generateBtn, false);
                hideLoader();

                if (!res.success) {
                    toastr.error(res.message || 'Failed to generate report');
                    return;
                }

                if (!Object.keys(res.report).length) {
                    elements.resultContainer.innerHTML = `
                    <div class="alert alert-warning d-flex align-items-center">
                        <i class="ki-outline ki-information-3 fs-2x me-3 text-warning"></i>
                        <div>No data found for the selected date range and branch.</div>
                    </div>`;
                    return;
                }

                reportData = res;
                renderSummaryCards(res);
                renderCollectorSummary(res);
                renderChart(res);
                renderReportTable(res);

                elements.summaryCards?.classList.remove('d-none');
                elements.chartSection?.classList.remove('d-none');
                elements.exportButtons?.classList.add('show');

                toastr.success('Report generated successfully!');
            })
            .catch(err => {
                setButtonLoading(elements.generateBtn, false);
                hideLoader();
                toastr.error(err.message || 'Failed to generate report');
            });
    };

    const renderSummaryCards = function (data) {
        let totalRevenue = 0;
        let totalCost = 0;

        Object.keys(data.report).forEach(date => {
            totalRevenue += Object.values(data.report[date]).reduce((a, b) => a + parseInt(b), 0);
            totalCost += parseInt(data.costs[date] || 0);
        });

        const netProfit = totalRevenue - totalCost;
        const profitMargin = totalRevenue > 0 ? ((netProfit / totalRevenue) * 100).toFixed(1) : 0;

        document.getElementById('total_revenue').textContent = formatCurrency(totalRevenue);
        document.getElementById('total_cost').textContent = formatCurrency(totalCost);
        document.getElementById('net_profit').textContent = formatCurrency(netProfit);
        document.getElementById('profit_margin').textContent = profitMargin + '%';
    };

    // ============================================
    // COLLECTOR SUMMARY RENDERING
    // ============================================
    const renderCollectorSummary = function (data) {
        const collectors = data.collectors || {};
        const collectorReport = data.collectorReport || {};
        const collectorIds = Object.keys(collectors);

        // Hide section if no collectors
        if (collectorIds.length === 0) {
            elements.collectorSummarySection?.classList.add('d-none');
            return;
        }

        // Calculate collector totals
        const collectorTotals = {};
        let grandTotal = 0;

        collectorIds.forEach(id => {
            collectorTotals[id] = 0;
        });

        Object.keys(collectorReport).forEach(date => {
            const dailyData = collectorReport[date] || {};
            collectorIds.forEach(id => {
                const amount = parseInt(dailyData[id] || 0);
                collectorTotals[id] += amount;
                grandTotal += amount;
            });
        });

        // Sort collectors by total amount (descending)
        const sortedCollectors = collectorIds
            .map(id => ({
                id: id,
                name: collectors[id],
                total: collectorTotals[id]
            }))
            .sort((a, b) => b.total - a.total);

        // Update badge
        if (elements.collectorCountBadge) {
            elements.collectorCountBadge.textContent = `${sortedCollectors.length} Collector${sortedCollectors.length > 1 ? 's' : ''}`;
        }

        // Build cards HTML
        let cardsHtml = '';

        sortedCollectors.forEach((collector, index) => {
            const percentage = grandTotal > 0 ? ((collector.total / grandTotal) * 100).toFixed(1) : 0;
            const gradientClass = getGradientClass(index);
            const rank = index + 1;

            cardsHtml += `
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                <div class="card collector-summary-card ${gradientClass} h-100">
                    <span class="collector-rank">#${rank}</span>
                    <div class="card-body d-flex align-items-center gap-3 py-4">
                        <div class="collector-icon-wrapper">
                            <i class="ki-outline ki-profile-user collector-icon"></i>
                        </div>
                        <div class="collector-info">
                            <div class="collector-name" title="${collector.name}">${collector.name}</div>
                            <div class="collector-amount">${formatCurrency(collector.total)}</div>
                            <div class="collector-meta">
                                <span class="collector-percentage">${percentage}% of total</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        });

        // Render cards
        if (elements.collectorSummaryCards) {
            elements.collectorSummaryCards.innerHTML = cardsHtml;
        }

        // Show section
        elements.collectorSummarySection?.classList.remove('d-none');
    };

    const renderChart = function (data) {
        if (!elements.chartCanvas) return;
        if (financeChart) financeChart.destroy();

        const labels = Object.keys(data.report).sort();
        const revenue = labels.map(d => Object.values(data.report[d]).reduce((a, b) => a + parseInt(b), 0));
        const costs = labels.map(d => parseInt(data.costs[d] || 0));

        financeChart = new Chart(elements.chartCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue',
                        data: revenue,
                        backgroundColor: 'rgba(0, 158, 247, 0.85)',
                        borderColor: 'rgb(0, 158, 247)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Cost',
                        data: costs,
                        backgroundColor: 'rgba(241, 65, 108, 0.85)',
                        borderColor: 'rgb(241, 65, 108)',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: ctx => ctx.dataset.label + ': ' + formatCurrency(ctx.raw)
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: { callback: value => '৳' + value.toLocaleString() }
                    }
                }
            }
        });
    };

    const renderReportTable = function (data) {
        const dates = Object.keys(data.report).sort().reverse();
        const classes = data.classes;
        const classesInfo = data.classesInfo || [];
        const collectors = data.collectors || {};
        const collectorIds = Object.keys(collectors);
        const collectorReport = data.collectorReport || {};

        let grandTotalRevenue = 0, grandTotalCost = 0;

        // Initialize class grand totals
        let classGrandTotals = {};
        classes.forEach(cls => {
            classGrandTotals[cls] = 0;
        });

        // Initialize collector grand totals
        let collectorGrandTotals = {};
        collectorIds.forEach(id => {
            collectorGrandTotals[id] = 0;
        });

        // Build table header with original Metronic classes
        let html = `
            <h4 class="fw-bold text-gray-800 mb-4">Revenue vs Cost Details</h4>
            <div class="table-responsive">
            <table class="table table-bordered table-row-bordered align-middle text-center">
            <thead>
                <tr class="fw-bold text-gray-700 bg-light">
                    <th rowspan="2" class="min-w-100px align-middle">Date</th>
                    ${classes.length > 0 ? `<th colspan="${classes.length}" class="bg-light-info text-info">Class-wise Revenue</th>` : ''}
                    <th rowspan="2" class="min-w-120px bg-light-primary align-middle">Total Revenue</th>
                    ${collectorIds.length > 0 ? `<th colspan="${collectorIds.length}" class="bg-light-success text-success">Collected By</th>` : ''}
                    <th rowspan="2" class="min-w-100px bg-light-danger align-middle">Cost</th>
                    <th rowspan="2" class="min-w-100px bg-light-warning align-middle">Net Profit</th>
                </tr>
                <tr class="fw-bold text-muted bg-light">`;

        // Class column headers
        classesInfo.forEach(cls => {
            html += cls.is_active
                ? `<th class="min-w-80px fs-7">${cls.name}</th>`
                : `<th class="min-w-80px fs-7"><span class="text-gray-500">${cls.name}</span><span class="badge badge-light-danger fs-8 ms-1"><i class="ki-outline ki-cross-circle fs-7"></i></span></th>`;
        });

        // Collector column headers
        collectorIds.forEach(id => {
            const name = collectors[id];
            html += `<th class="min-w-100px fs-7 text-success" title="${name}">${name.length > 15 ? name.substring(0, 12) + '...' : name}</th>`;
        });

        html += `</tr></thead><tbody>`;

        // Build table body rows
        dates.forEach(date => {
            const dailyData = data.report[date];
            const dailyCollectorData = collectorReport[date] || {};
            let dailyTotal = 0;

            html += `<tr><td class="fw-semibold">${date}</td>`;

            // Class columns
            classes.forEach(cls => {
                const value = parseInt(dailyData[cls] || 0);
                dailyTotal += value;
                classGrandTotals[cls] += value;
                html += `<td class="text-gray-700">${value > 0 ? formatCurrency(value) : '<span class="text-muted">-</span>'}</td>`;
            });

            // Total revenue column
            html += `<td class="fw-bold bg-light-primary text-primary">${formatCurrency(dailyTotal)}</td>`;

            // Collector columns
            collectorIds.forEach(id => {
                const value = parseInt(dailyCollectorData[id] || 0);
                collectorGrandTotals[id] += value;
                html += `<td class="text-gray-700">${value > 0 ? formatCurrency(value) : '<span class="text-muted">-</span>'}</td>`;
            });

            // Cost and Net Profit columns
            const cost = parseInt(data.costs[date] || 0);
            const net = dailyTotal - cost;
            grandTotalRevenue += dailyTotal;
            grandTotalCost += cost;

            html += `
                <td class="fw-bold text-danger bg-light-danger">${formatCurrency(cost)}</td>
                <td class="fw-bold ${net >= 0 ? 'text-success bg-light-success' : 'text-danger bg-light-danger'}">${formatCurrency(net)}</td>
            </tr>`;
        });

        // Build footer row with Grand Total
        const grandNet = grandTotalRevenue - grandTotalCost;

        // Using inline styles as fallback for guaranteed visibility in both light/dark modes
        const footerRowStyle = 'background-color: #1e1e2d !important;';
        const footerCellBaseStyle = 'background-color: #1e1e2d; border-color: #3f4254; padding: 14px 10px; vertical-align: middle; font-weight: 700;';
        const labelStyle = footerCellBaseStyle + ' color: #ffffff; font-size: 1rem; text-align: left;';
        const classStyle = footerCellBaseStyle + ' color: #00d9ff;';
        const revenueStyle = footerCellBaseStyle + ' color: #ffc700;';
        const costStyle = footerCellBaseStyle + ' color: #ff6b8a;';
        const collectorStyle = footerCellBaseStyle + ' color: #e4e6ef; font-weight: 600;';
        const profitPositiveStyle = footerCellBaseStyle + ' color: #50cd89;';
        const profitNegativeStyle = footerCellBaseStyle + ' color: #f1416c;';
        const mutedStyle = 'color: #7e8299; font-weight: 400;';

        html += `</tbody><tfoot><tr class="finance-table-footer-row" style="${footerRowStyle}">
            <td class="finance-table-footer-label" style="${labelStyle}">Grand Total</td>`;

        // Class totals in footer - show hyphen if zero
        classes.forEach(cls => {
            const classTotal = classGrandTotals[cls];
            html += `<td class="finance-table-footer-class-cell" style="${classStyle}">${classTotal > 0 ? formatCurrency(classTotal) : '<span style="' + mutedStyle + '">-</span>'}</td>`;
        });

        // Grand total revenue
        html += `<td class="finance-table-footer-revenue" style="${revenueStyle}">${formatCurrency(grandTotalRevenue)}</td>`;

        // Collector totals
        collectorIds.forEach(id => {
            html += `<td class="finance-table-footer-collector" style="${collectorStyle}">${formatCurrency(collectorGrandTotals[id])}</td>`;
        });

        // Grand total cost
        html += `<td class="finance-table-footer-cost" style="${costStyle}">${formatCurrency(grandTotalCost)}</td>`;

        // Grand net profit
        const profitStyle = grandNet >= 0 ? profitPositiveStyle : profitNegativeStyle;
        const profitClass = grandNet >= 0 ? 'finance-table-footer-profit-positive' : 'finance-table-footer-profit-negative';
        html += `<td class="${profitClass}" style="${profitStyle}">${formatCurrency(grandNet)}</td>`;

        html += `</tr></tfoot></table></div>`;

        elements.resultContainer.innerHTML = html;
    };

    // ============================================
    // EXPORT FUNCTIONS
    // ============================================
    const exportToExcel = function () {
        if (!reportData) {
            toastr.error('Please generate a report first');
            return;
        }

        const dates = Object.keys(reportData.report).sort();
        const classes = reportData.classes;
        const collectors = reportData.collectors || {};
        const collectorIds = Object.keys(collectors);
        const collectorReport = reportData.collectorReport || {};

        // Get date range from input
        const dateRange = elements.dateRangeInput?.value || '';

        // Get branch name from select
        let branchName = 'All Branches';
        if (elements.branchSelect) {
            const selectedOption = elements.branchSelect.options[elements.branchSelect.selectedIndex];
            if (selectedOption && selectedOption.value) {
                branchName = selectedOption.text.trim();
            }
        }

        // Calculate total columns
        const totalColumns = 1 + classes.length + 1 + collectorIds.length + 2; // Date + Classes + Total Revenue + Collectors + Cost + Net Profit

        const wsData = [];

        // ============================================
        // ROW 1: Title
        // ============================================
        const titleRow = ['Finance Report - Revenue vs Cost'];
        for (let i = 1; i < totalColumns; i++) {
            titleRow.push('');
        }
        wsData.push(titleRow);

        // ============================================
        // ROW 2: Date Range
        // ============================================
        const dateRangeRow = ['Date Range: ' + dateRange];
        for (let i = 1; i < totalColumns; i++) {
            dateRangeRow.push('');
        }
        wsData.push(dateRangeRow);

        // ============================================
        // ROW 3: Branch
        // ============================================
        const branchRow = ['Branch: ' + branchName];
        for (let i = 1; i < totalColumns; i++) {
            branchRow.push('');
        }
        wsData.push(branchRow);

        // ============================================
        // ROW 4: Empty row for spacing
        // ============================================
        const emptyRow = [];
        for (let i = 0; i < totalColumns; i++) {
            emptyRow.push('');
        }
        wsData.push(emptyRow);

        // ============================================
        // ROW 5: Grouped Headers
        // ============================================
        const groupedHeader = [''];

        // Class-wise Revenue group header
        if (classes.length > 0) {
            groupedHeader.push('Class-wise Revenue');
            for (let i = 1; i < classes.length; i++) {
                groupedHeader.push('');
            }
        }

        // Total Revenue (no group)
        groupedHeader.push('');

        // Collected By group header
        if (collectorIds.length > 0) {
            groupedHeader.push('Collected By');
            for (let i = 1; i < collectorIds.length; i++) {
                groupedHeader.push('');
            }
        }

        // Cost and Net Profit (no group)
        groupedHeader.push('');
        groupedHeader.push('');

        wsData.push(groupedHeader);

        // ============================================
        // ROW 6: Sub-headers (Column names)
        // ============================================
        const subHeader = ['Date'];

        // Class names
        classes.forEach(cls => {
            subHeader.push(cls);
        });

        // Total Revenue
        subHeader.push('Total Revenue');

        // Collector names
        collectorIds.forEach(id => {
            subHeader.push(collectors[id]);
        });

        // Cost and Net Profit
        subHeader.push('Cost');
        subHeader.push('Net Profit');

        wsData.push(subHeader);

        // ============================================
        // Initialize totals
        // ============================================
        let grandRevenue = 0, grandCost = 0;

        let classExcelTotals = {};
        classes.forEach(cls => {
            classExcelTotals[cls] = 0;
        });

        let collectorTotals = {};
        collectorIds.forEach(id => {
            collectorTotals[id] = 0;
        });

        // ============================================
        // DATA ROWS
        // ============================================
        dates.forEach(date => {
            const row = [date];
            let dailyTotal = 0;

            classes.forEach(cls => {
                const value = parseInt(reportData.report[date][cls] || 0);
                dailyTotal += value;
                classExcelTotals[cls] += value;
                row.push(value > 0 ? value : '');
            });

            row.push(dailyTotal);

            collectorIds.forEach(id => {
                const value = parseInt((collectorReport[date] || {})[id] || 0);
                collectorTotals[id] += value;
                row.push(value > 0 ? value : '');
            });

            const cost = parseInt(reportData.costs[date] || 0);
            const netProfit = dailyTotal - cost;

            row.push(cost > 0 ? cost : '');
            row.push(netProfit);

            wsData.push(row);

            grandRevenue += dailyTotal;
            grandCost += cost;
        });

        // ============================================
        // GRAND TOTAL ROW
        // ============================================
        const totalRow = ['Grand Total'];

        // Class totals (show empty string if zero)
        classes.forEach(cls => {
            const classTotal = classExcelTotals[cls];
            totalRow.push(classTotal > 0 ? classTotal : '');
        });

        // Grand revenue
        totalRow.push(grandRevenue);

        // Collector totals
        collectorIds.forEach(id => {
            const collectorTotal = collectorTotals[id];
            totalRow.push(collectorTotal > 0 ? collectorTotal : '');
        });

        // Grand cost and net profit
        totalRow.push(grandCost);
        totalRow.push(grandRevenue - grandCost);

        wsData.push(totalRow);

        // ============================================
        // CREATE WORKSHEET
        // ============================================
        const ws = XLSX.utils.aoa_to_sheet(wsData);

        // ============================================
        // MERGE CELLS
        // ============================================
        ws['!merges'] = [];

        // Merge title row (Row 1)
        ws['!merges'].push({ s: { r: 0, c: 0 }, e: { r: 0, c: totalColumns - 1 } });

        // Merge date range row (Row 2)
        ws['!merges'].push({ s: { r: 1, c: 0 }, e: { r: 1, c: totalColumns - 1 } });

        // Merge branch row (Row 3)
        ws['!merges'].push({ s: { r: 2, c: 0 }, e: { r: 2, c: totalColumns - 1 } });

        // Merge "Class-wise Revenue" group header (Row 5)
        if (classes.length > 1) {
            ws['!merges'].push({ s: { r: 4, c: 1 }, e: { r: 4, c: classes.length } });
        }

        // Merge "Collected By" group header (Row 5)
        if (collectorIds.length > 1) {
            const collectorStartCol = 1 + classes.length + 1; // After Date + Classes + Total Revenue
            ws['!merges'].push({ s: { r: 4, c: collectorStartCol }, e: { r: 4, c: collectorStartCol + collectorIds.length - 1 } });
        }

        // ============================================
        // SET COLUMN WIDTHS
        // ============================================
        const colWidths = [{ wch: 14 }]; // Date column

        // Class columns
        classes.forEach(() => {
            colWidths.push({ wch: 15 });
        });

        // Total Revenue column
        colWidths.push({ wch: 18 });

        // Collector columns
        collectorIds.forEach(() => {
            colWidths.push({ wch: 18 });
        });

        // Cost and Net Profit columns
        colWidths.push({ wch: 15 });
        colWidths.push({ wch: 15 });

        ws['!cols'] = colWidths;

        // ============================================
        // CREATE WORKBOOK AND DOWNLOAD
        // ============================================
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Finance Report');

        const fileName = 'finance_report_' + moment().format('YYYY-MM-DD_HH-mm') + '.xlsx';
        XLSX.writeFile(wb, fileName);

        toastr.success('Excel file downloaded!');
    };

    const exportChart = function () {
        if (!financeChart) {
            toastr.error('Please generate a report first');
            return;
        }

        const canvas = elements.chartCanvas;
        const tempCanvas = document.createElement('canvas');
        const ctx = tempCanvas.getContext('2d');

        tempCanvas.width = canvas.width;
        tempCanvas.height = canvas.height;

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
        ctx.drawImage(canvas, 0, 0);

        const link = document.createElement('a');
        link.download = 'finance_chart_' + moment().format('YYYY-MM-DD_HH-mm') + '.png';
        link.href = tempCanvas.toDataURL('image/png', 1.0);
        link.click();

        toastr.success('Chart downloaded!');
    };

    // ============================================
    // EVENT LISTENERS
    // ============================================
    const initEvents = function () {
        elements.form?.addEventListener('submit', generateReport);
        elements.exportExcelBtn?.addEventListener('click', exportToExcel);
        elements.exportChartBtn?.addEventListener('click', exportChart);
    };

    // ============================================
    // INIT
    // ============================================
    const init = function () {
        initElements();
        initDateRangePicker();
        initEvents();
    };

    // ============================================
    // PUBLIC API
    // ============================================
    return {
        init: init,
    };
})();

// DOM Ready
KTUtil.onDOMContentLoaded(function () {
    KTFinanceReport.init();
});
