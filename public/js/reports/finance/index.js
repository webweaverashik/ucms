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
            let collectorGrandTotals = {};
            collectorIds.forEach(id => { collectorGrandTotals[id] = 0; });

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

            classesInfo.forEach(cls => {
                  html += cls.is_active
                        ? `<th class="min-w-80px fs-7">${cls.name}</th>`
                        : `<th class="min-w-80px fs-7"><span class="text-gray-500">${cls.name}</span><span class="badge badge-light-danger fs-8 ms-1"><i class="ki-outline ki-cross-circle fs-7"></i></span></th>`;
            });

            collectorIds.forEach(id => {
                  const name = collectors[id];
                  html += `<th class="min-w-100px fs-7 text-success" title="${name}">${name.length > 15 ? name.substring(0, 12) + '...' : name}</th>`;
            });

            html += `</tr></thead><tbody>`;

            dates.forEach(date => {
                  const dailyData = data.report[date];
                  const dailyCollectorData = collectorReport[date] || {};
                  let dailyTotal = 0;

                  html += `<tr><td class="fw-semibold">${date}</td>`;

                  classes.forEach(cls => {
                        const value = parseInt(dailyData[cls] || 0);
                        dailyTotal += value;
                        html += `<td class="text-gray-700">${value > 0 ? formatCurrency(value) : '<span class="text-muted">-</span>'}</td>`;
                  });

                  html += `<td class="fw-bold bg-light-primary text-primary">${formatCurrency(dailyTotal)}</td>`;

                  collectorIds.forEach(id => {
                        const value = parseInt(dailyCollectorData[id] || 0);
                        collectorGrandTotals[id] += value;
                        html += `<td class="text-gray-700">${value > 0 ? formatCurrency(value) : '<span class="text-muted">-</span>'}</td>`;
                  });

                  const cost = parseInt(data.costs[date] || 0);
                  const net = dailyTotal - cost;
                  grandTotalRevenue += dailyTotal;
                  grandTotalCost += cost;

                  html += `
                <td class="fw-bold text-danger bg-light-danger">${formatCurrency(cost)}</td>
                <td class="fw-bold ${net >= 0 ? 'text-success bg-light-success' : 'text-danger bg-light-danger'}">${formatCurrency(net)}</td>
            </tr>`;
            });

            const grandNet = grandTotalRevenue - grandTotalCost;

            html += `</tbody><tfoot><tr class="fw-bolder bg-gray-900 text-white"><td>Grand Total</td>`;
            classes.forEach(() => { html += `<td>-</td>`; });
            html += `<td class="text-warning">${formatCurrency(grandTotalRevenue)}</td>`;
            collectorIds.forEach(id => {
                  html += `<td class="text-white">${formatCurrency(collectorGrandTotals[id])}</td>`;
            });
            html += `<td class="text-danger">${formatCurrency(grandTotalCost)}</td>`;
            html += `<td class="${grandNet >= 0 ? 'text-success' : 'text-danger'}">${formatCurrency(grandNet)}</td></tr></tfoot></table></div>`;

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

            const wsData = [];

            // Header row
            const header = ['Date', ...classes, 'Total Revenue'];
            collectorIds.forEach(id => { header.push(collectors[id]); });
            header.push('Cost', 'Net Profit');
            wsData.push(header);

            let grandRevenue = 0, grandCost = 0;
            let collectorTotals = {};
            collectorIds.forEach(id => { collectorTotals[id] = 0; });

            dates.forEach(date => {
                  const row = [date];
                  let dailyTotal = 0;

                  classes.forEach(cls => {
                        const value = parseInt(reportData.report[date][cls] || 0);
                        dailyTotal += value;
                        row.push(value);
                  });

                  row.push(dailyTotal);

                  collectorIds.forEach(id => {
                        const value = parseInt((collectorReport[date] || {})[id] || 0);
                        collectorTotals[id] += value;
                        row.push(value);
                  });

                  const cost = parseInt(reportData.costs[date] || 0);
                  row.push(cost, dailyTotal - cost);
                  wsData.push(row);

                  grandRevenue += dailyTotal;
                  grandCost += cost;
            });

            // Grand total row
            const totalRow = ['Grand Total', ...Array(classes.length).fill(''), grandRevenue];
            collectorIds.forEach(id => { totalRow.push(collectorTotals[id]); });
            totalRow.push(grandCost, grandRevenue - grandCost);
            wsData.push(totalRow);

            const ws = XLSX.utils.aoa_to_sheet(wsData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Finance Report');
            XLSX.writeFile(wb, 'finance_report_' + moment().format('YYYY-MM-DD_HH-mm') + '.xlsx');

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