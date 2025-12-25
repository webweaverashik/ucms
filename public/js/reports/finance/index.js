"use strict";

/**
 * UCMS Finance Report Module
 * Revenue vs Cost Report with Cost Management
 * Metronic 8 + Bootstrap 5 + DataTables + Toastr
 */

var KTFinanceReport = (function () {

      // ============================================
      // STATE & CONFIGURATION
      // ============================================
      let financeChart = null;
      let costsDataTable = null;
      let costDatePicker = null;
      let costModal = null;
      let deleteModal = null;
      let inlineEditModal = null;
      let existingCostDates = [];
      let reportData = null;

      const config = window.FinanceReportConfig || {};
      const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

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

            elements.addCostBtn = document.getElementById('add_cost_btn');
            elements.costForm = document.getElementById('cost_form');
            elements.refreshCostsBtn = document.getElementById('refresh_costs_btn');

            elements.saveCostBtn = document.getElementById('save_cost_btn');
            elements.confirmDeleteBtn = document.getElementById('confirm_delete_cost_btn');
            elements.saveInlineEditBtn = document.getElementById('save_inline_edit_btn');
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
            elements.resultContainer.innerHTML = '';
            elements.chartSection?.classList.add('d-none');
            elements.exportButtons?.classList.remove('show');
      };

      const hideLoader = function () {
            elements.loader?.classList.add('d-none');
      };

      // ============================================
      // MODALS INITIALIZATION
      // ============================================
      const initModals = function () {
            const costEl = document.getElementById('cost_modal');
            const deleteEl = document.getElementById('delete_cost_modal');
            const inlineEditEl = document.getElementById('inline_edit_modal');

            if (costEl) costModal = new bootstrap.Modal(costEl);
            if (deleteEl) deleteModal = new bootstrap.Modal(deleteEl);
            if (inlineEditEl) inlineEditModal = new bootstrap.Modal(inlineEditEl);
      };

      // ============================================
      // DATE RANGE PICKER
      // ============================================
      const initDateRangePicker = function () {
            if (!elements.dateRangeInput) return;

            const start = moment().subtract(6, 'days');
            const end = moment();

            $(elements.dateRangeInput).daterangepicker({
                  startDate: start,
                  endDate: end,
                  maxDate: moment(),
                  locale: {
                        format: 'DD-MM-YYYY'
                  },
                  ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                  }
            }, function (s, e) {
                  elements.dateRangeInput.value = s.format('DD-MM-YYYY') + ' - ' + e.format('DD-MM-YYYY');
            });

            elements.dateRangeInput.value = start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY');
      };

      // ============================================
      // COST DATE PICKER (with disabled dates)
      // ============================================
      const initCostDatePicker = function (branchId = null) {
            const $costDate = $('#cost_date');
            const costDateInput = document.getElementById('cost_date');
            const dateHelpText = document.getElementById('date_help_text');

            // Destroy existing picker
            if ($costDate.data('daterangepicker')) {
                  $costDate.data('daterangepicker').remove();
            }

            // Clear the date value - user must select
            $costDate.val('');

            // For admin, branch must be selected first
            if (config.isAdmin && !branchId) {
                  costDateInput.disabled = true;
                  costDateInput.classList.add('bg-secondary');
                  costDateInput.placeholder = 'Select branch first';
                  if (dateHelpText) {
                        dateHelpText.textContent = 'Please select a branch first';
                  }
                  return;
            }

            // Enable the date field
            costDateInput.disabled = false;
            costDateInput.classList.remove('bg-secondary');
            costDateInput.placeholder = 'Select available date';
            if (dateHelpText) {
                  dateHelpText.textContent = 'Select an available date (dates with existing costs are disabled)';
            }

            // Initialize date picker with disabled dates
            $costDate.daterangepicker({
                  singleDatePicker: true,
                  showDropdowns: true,
                  autoApply: true,
                  autoUpdateInput: false, // Don't auto-fill the date
                  maxDate: moment(),
                  locale: {
                        format: 'DD-MM-YYYY'
                  },
                  isInvalidDate: function (date) {
                        const dateStr = date.format('DD-MM-YYYY');
                        return existingCostDates.includes(dateStr);
                  }
            }, function (selectedDate) {
                  const dateStr = selectedDate.format('DD-MM-YYYY');

                  // Only set value if date is not in existing dates
                  if (!existingCostDates.includes(dateStr)) {
                        $costDate.val(dateStr);
                  }
            });

            // Handle apply event to ensure date is set
            $costDate.on('apply.daterangepicker', function (ev, picker) {
                  const dateStr = picker.startDate.format('DD-MM-YYYY');
                  if (!existingCostDates.includes(dateStr)) {
                        $(this).val(dateStr);
                  } else {
                        $(this).val('');
                        toastr.warning('This date already has a cost record. Please select another date.');
                  }
            });
      };

      const updateExistingCostDates = function (branchId = null, callback = null) {
            const targetBranchId = branchId || config.userBranchId || document.getElementById('cost_branch_id')?.value;

            if (!targetBranchId) {
                  existingCostDates = [];
                  if (callback) callback();
                  return;
            }

            fetch(config.routes.costs + '?branch_id=' + targetBranchId, {
                  method: 'GET',
                  headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                  }
            })
                  .then(r => r.json())
                  .then(res => {
                        if (res.success && res.data) {
                              existingCostDates = res.data.map(c => formatDate(c.cost_date));
                        } else {
                              existingCostDates = [];
                        }
                        if (callback) callback();
                  })
                  .catch(err => {
                        console.error('Error fetching cost dates:', err);
                        existingCostDates = [];
                        if (callback) callback();
                  });
      };

      // ============================================
      // COSTS DATATABLE
      // ============================================
      const initCostsDataTable = function () {
            const table = document.getElementById('costs_datatable');
            if (!table) return;

            costsDataTable = $(table).DataTable({
                  processing: true,
                  serverSide: false,
                  ajax: {
                        url: config.routes.costs,
                        type: 'GET',
                        headers: {
                              'X-CSRF-TOKEN': csrfToken
                        },
                        dataSrc: function (json) {
                              if (json.success) {
                                    existingCostDates = json.data.map(c => formatDate(c.cost_date));
                                    return json.data;
                              }
                              return [];
                        },
                        error: function (xhr, error, thrown) {
                              console.error('DataTable AJAX error:', error);
                              toastr.error('Failed to load cost records');
                        }
                  },
                  columns: [
                        {
                              data: 'cost_date',
                              className: 'ps-4',
                              render: function (data) {
                                    return `<span class="fw-semibold text-gray-800">${formatDate(data)}</span>`;
                              }
                        },
                        {
                              data: 'branch',
                              render: function (data) {
                                    if (!data) return '-';
                                    return `<span class="badge badge-light-primary">${data.branch_name} (${data.branch_prefix})</span>`;
                              }
                        },
                        {
                              data: 'amount',
                              className: 'text-end',
                              render: function (data, type, row) {
                                    return `
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <span class="fw-bold text-gray-900">${formatCurrency(data)}</span>
                                <button type="button" class="btn btn-icon btn-sm btn-light-primary amount-edit-btn"
                                    onclick="KTFinanceReport.openInlineEditModal(${row.id}, ${data})" title="Edit Amount">
                                    <i class="ki-outline ki-pencil fs-6"></i>
                                </button>
                            </div>`;
                              }
                        },
                        {
                              data: 'description',
                              render: function (data) {
                                    if (!data) return '<span class="text-muted fst-italic">No description</span>';
                                    return `<span class="text-gray-700">${data.length > 50 ? data.substring(0, 50) + '...' : data}</span>`;
                              }
                        },
                        {
                              data: 'created_by',
                              render: function (data, type, row) {
                                    if (!row.created_by) return '-';
                                    return `<span class="text-gray-600">${row.created_by.name}</span>`;
                              }
                        },
                        {
                              data: null,
                              className: 'text-center pe-4',
                              orderable: false,
                              render: function (data, type, row) {
                                    return `
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-icon btn-sm btn-light-primary"
                                    onclick="KTFinanceReport.openEditCostModal(${row.id})" title="Edit">
                                    <i class="ki-outline ki-pencil fs-6"></i>
                                </button>
                                <button type="button" class="btn btn-icon btn-sm btn-light-danger"
                                    onclick="KTFinanceReport.openDeleteModal(${row.id})" title="Delete">
                                    <i class="ki-outline ki-trash fs-6"></i>
                                </button>
                            </div>`;
                              }
                        }
                  ],
                  order: [[0, 'desc']],
                  pageLength: 10,
                  lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                  language: {
                        emptyTable: "No cost records found",
                        zeroRecords: "No matching records found"
                  },
                  drawCallback: function () {
                        KTMenu.init();
                  }
            });
      };

      const reloadCostsDataTable = function () {
            if (costsDataTable) {
                  costsDataTable.ajax.reload(null, false);
            }
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

            // Check if branch is selected (required for admin)
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

                        // Calculate totals
                        let totalRevenue = 0;
                        let totalCost = 0;
                        Object.keys(res.report).forEach(date => {
                              totalRevenue += Object.values(res.report[date]).reduce((a, b) => a + parseInt(b), 0);
                              totalCost += parseInt(res.costs[date] || 0);
                        });
                        const netProfit = totalRevenue - totalCost;
                        const profitMargin = totalRevenue > 0 ? ((netProfit / totalRevenue) * 100).toFixed(1) : 0;

                        // Update summary cards
                        document.getElementById('total_revenue').textContent = formatCurrency(totalRevenue);
                        document.getElementById('total_cost').textContent = formatCurrency(totalCost);
                        document.getElementById('net_profit').textContent = formatCurrency(netProfit);
                        document.getElementById('profit_margin').textContent = profitMargin + '%';

                        elements.summaryCards?.classList.remove('d-none');
                        elements.exportButtons?.classList.add('show');

                        renderChart(res);
                        elements.chartSection?.classList.remove('d-none');

                        renderReportTable(res);

                        toastr.success('Report generated successfully!');
                  })
                  .catch(err => {
                        setButtonLoading(elements.generateBtn, false);
                        hideLoader();
                        toastr.error(err.message || 'Failed to generate report');
                        console.error('Generate report error:', err);
                  });
      };

      // ============================================
      // RENDER REPORT TABLE
      // ============================================
      const renderReportTable = function (data) {
            const dates = Object.keys(data.report).sort().reverse();
            const classes = data.classes;

            let grandTotalRevenue = 0;
            let grandTotalCost = 0;

            let html = `
            <h4 class="fw-bold text-gray-800 mb-4">Revenue vs Cost Details</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-row-bordered align-middle text-center">
                    <thead>
                        <tr class="fw-bold text-gray-700 bg-light">
                            <th class="min-w-100px">Date</th>`;

            classes.forEach(cls => {
                  html += `<th class="min-w-100px">${cls}</th>`;
            });

            html += `
                            <th class="min-w-120px bg-light-primary">Total Revenue</th>
                            <th class="min-w-100px bg-light-danger">Cost</th>
                            <th class="min-w-100px bg-light-success">Net Profit</th>
                        </tr>
                    </thead>
                    <tbody>`;

            dates.forEach(date => {
                  const dailyData = data.report[date];
                  let dailyTotal = 0;

                  html += `<tr><td class="fw-semibold">${date}</td>`;

                  classes.forEach(cls => {
                        const value = parseInt(dailyData[cls] || 0);
                        dailyTotal += value;
                        html += `<td>${formatCurrency(value)}</td>`;
                  });

                  const cost = parseInt(data.costs[date] || 0);
                  const net = dailyTotal - cost;
                  grandTotalRevenue += dailyTotal;
                  grandTotalCost += cost;

                  html += `
                <td class="fw-bold bg-light-primary">${formatCurrency(dailyTotal)}</td>
                <td class="fw-bold text-danger bg-light-danger">${formatCurrency(cost)}</td>
                <td class="fw-bold ${net >= 0 ? 'text-success bg-light-success' : 'text-danger bg-light-danger'}">${formatCurrency(net)}</td>
            </tr>`;
            });

            const grandNet = grandTotalRevenue - grandTotalCost;

            html += `
                    </tbody>
                    <tfoot>
                        <tr class="fw-bolder bg-gray-900 text-white">
                            <td colspan="${classes.length + 1}">Grand Total</td>
                            <td>${formatCurrency(grandTotalRevenue)}</td>
                            <td>${formatCurrency(grandTotalCost)}</td>
                            <td class="${grandNet >= 0 ? 'text-success' : 'text-danger'}">${formatCurrency(grandNet)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>`;

            elements.resultContainer.innerHTML = html;
      };

      // ============================================
      // RENDER CHART (Full Width - Revenue & Cost only)
      // ============================================
      const renderChart = function (data) {
            if (!elements.chartCanvas) return;

            if (financeChart) {
                  financeChart.destroy();
            }

            const labels = Object.keys(data.report).sort();
            const revenue = labels.map(d =>
                  Object.values(data.report[d]).reduce((a, b) => a + parseInt(b), 0)
            );
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
                              legend: {
                                    position: 'top',
                                    labels: {
                                          usePointStyle: true,
                                          padding: 20,
                                          font: {
                                                family: 'inherit',
                                                size: 13
                                          }
                                    }
                              },
                              tooltip: {
                                    callbacks: {
                                          label: function (context) {
                                                return context.dataset.label + ': ' + formatCurrency(context.raw);
                                          }
                                    }
                              }
                        },
                        scales: {
                              x: {
                                    grid: {
                                          display: false
                                    }
                              },
                              y: {
                                    beginAtZero: true,
                                    ticks: {
                                          callback: function (value) {
                                                return '৳' + value.toLocaleString();
                                          }
                                    }
                              }
                        }
                  }
            });
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
            const wsData = [];

            // Header row
            const header = ['Date', ...classes, 'Total Revenue', 'Cost', 'Net Profit'];
            wsData.push(header);

            // Data rows
            let grandRevenue = 0, grandCost = 0;
            dates.forEach(date => {
                  const row = [date];
                  let dailyTotal = 0;
                  classes.forEach(cls => {
                        const value = parseInt(reportData.report[date][cls] || 0);
                        dailyTotal += value;
                        row.push(value);
                  });
                  const cost = parseInt(reportData.costs[date] || 0);
                  const net = dailyTotal - cost;
                  row.push(dailyTotal, cost, net);
                  wsData.push(row);
                  grandRevenue += dailyTotal;
                  grandCost += cost;
            });

            // Grand total row
            wsData.push(['Grand Total', ...Array(classes.length).fill(''), grandRevenue, grandCost, grandRevenue - grandCost]);

            const ws = XLSX.utils.aoa_to_sheet(wsData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Finance Report');

            const fileName = 'finance_report_' + moment().format('YYYY-MM-DD_HH-mm') + '.xlsx';
            XLSX.writeFile(wb, fileName);

            toastr.success('Excel file downloaded successfully!');
      };

      const exportChart = function () {
            if (!financeChart) {
                  toastr.error('Please generate a report first');
                  return;
            }

            const link = document.createElement('a');
            link.download = 'finance_chart_' + moment().format('YYYY-MM-DD_HH-mm') + '.png';
            link.href = financeChart.toBase64Image();
            link.click();

            toastr.success('Chart image downloaded successfully!');
      };

      // ============================================
      // COST MODAL FUNCTIONS
      // ============================================
      const openAddCostModal = function () {
            document.getElementById('cost_modal_title').textContent = 'Add Daily Cost';
            elements.costForm?.reset();
            document.getElementById('cost_id').value = '';
            document.getElementById('cost_date').value = '';

            // Reset branch select for admin
            if (config.isAdmin) {
                  $('#cost_branch_id').val('').trigger('change');
                  // Disable date field until branch is selected
                  const costDateInput = document.getElementById('cost_date');
                  costDateInput.disabled = true;
                  costDateInput.classList.add('bg-secondary');
                  costDateInput.placeholder = 'Select branch first';

                  const dateHelpText = document.getElementById('date_help_text');
                  if (dateHelpText) {
                        dateHelpText.textContent = 'Please select a branch first';
                  }

                  existingCostDates = [];
            } else {
                  // For non-admin, fetch existing dates and init picker
                  updateExistingCostDates(config.userBranchId, function () {
                        initCostDatePicker(config.userBranchId);
                  });
            }

            costModal?.show();
      };

      const openEditCostModal = function (id) {
            document.getElementById('cost_modal_title').textContent = 'Edit Cost';

            const url = config.routes.showCost.replace(':id', id);

            fetch(url, {
                  method: 'GET',
                  headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                  }
            })
                  .then(r => r.json())
                  .then(res => {
                        if (res.success && res.data) {
                              const cost = res.data;
                              document.getElementById('cost_id').value = cost.id;
                              document.getElementById('cost_amount').value = parseInt(cost.amount);
                              document.getElementById('cost_description').value = cost.description || '';

                              const branchId = cost.branch_id;

                              if (config.isAdmin && document.getElementById('cost_branch_id')) {
                                    // Set branch but disable it during edit (can't change branch)
                                    $('#cost_branch_id').val(branchId).trigger('change');
                              }

                              // Fetch existing dates for this branch, excluding current cost date
                              updateExistingCostDates(branchId, function () {
                                    // Remove the current date from existing dates for edit mode
                                    const currentDate = formatDate(cost.cost_date);
                                    const filteredDates = existingCostDates.filter(d => d !== currentDate);
                                    existingCostDates = filteredDates;

                                    // Enable date field
                                    const costDateInput = document.getElementById('cost_date');
                                    costDateInput.disabled = false;
                                    costDateInput.classList.remove('bg-secondary');

                                    const dateHelpText = document.getElementById('date_help_text');
                                    if (dateHelpText) {
                                          dateHelpText.textContent = 'Select an available date (dates with existing costs are disabled)';
                                    }

                                    // Destroy existing picker
                                    const $costDate = $('#cost_date');
                                    if ($costDate.data('daterangepicker')) {
                                          $costDate.data('daterangepicker').remove();
                                    }

                                    // Initialize with current date selected
                                    $costDate.daterangepicker({
                                          singleDatePicker: true,
                                          showDropdowns: true,
                                          autoApply: true,
                                          autoUpdateInput: true,
                                          maxDate: moment(),
                                          startDate: moment(cost.cost_date),
                                          locale: {
                                                format: 'DD-MM-YYYY'
                                          },
                                          isInvalidDate: function (date) {
                                                const dateStr = date.format('DD-MM-YYYY');
                                                return filteredDates.includes(dateStr);
                                          }
                                    }, function (selectedDate) {
                                          const dateStr = selectedDate.format('DD-MM-YYYY');
                                          if (!filteredDates.includes(dateStr)) {
                                                $costDate.val(dateStr);
                                          }
                                    });

                                    // Set the current date value
                                    $costDate.val(currentDate);

                                    // Handle apply event
                                    $costDate.on('apply.daterangepicker', function (ev, picker) {
                                          const dateStr = picker.startDate.format('DD-MM-YYYY');
                                          if (!filteredDates.includes(dateStr)) {
                                                $(this).val(dateStr);
                                          } else {
                                                $(this).val(currentDate);
                                                toastr.warning('This date already has a cost record. Please select another date.');
                                          }
                                    });

                                    costModal?.show();
                              });
                        } else {
                              toastr.error('Failed to load cost data');
                        }
                  })
                  .catch(err => {
                        toastr.error('Failed to load cost data');
                        console.error('Load cost error:', err);
                  });
      };

      const saveCost = function (e) {
            e.preventDefault();

            const costId = document.getElementById('cost_id').value;
            const costDate = document.getElementById('cost_date').value;
            const amount = document.getElementById('cost_amount').value;
            const description = document.getElementById('cost_description').value;
            const branchId = document.getElementById('cost_branch_id')?.value;

            // Validate branch for admin
            if (config.isAdmin && !branchId) {
                  toastr.error('Please select a branch');
                  return;
            }

            // Validate date is selected
            if (!costDate) {
                  toastr.error('Please select a date');
                  return;
            }

            // Validate amount
            if (!amount) {
                  toastr.error('Please enter an amount');
                  return;
            }

            // Validate amount is integer
            if (!Number.isInteger(Number(amount)) || Number(amount) < 1) {
                  toastr.error('Amount must be a positive whole number');
                  return;
            }

            // Double-check date is not in existing dates (for new costs)
            if (!costId && existingCostDates.includes(costDate)) {
                  toastr.error('A cost record already exists for this date. Please select another date.');
                  return;
            }

            setButtonLoading(elements.saveCostBtn, true);

            const isUpdate = !!costId;
            const url = isUpdate
                  ? config.routes.updateCost.replace(':id', costId)
                  : config.routes.storeCost;
            const method = isUpdate ? 'PUT' : 'POST';

            fetch(url, {
                  method: method,
                  headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                  },
                  body: JSON.stringify({
                        cost_date: costDate,
                        amount: parseInt(amount),
                        description: description,
                        branch_id: branchId
                  })
            })
                  .then(r => r.json())
                  .then(res => {
                        setButtonLoading(elements.saveCostBtn, false);

                        if (res.success) {
                              costModal?.hide();
                              reloadCostsDataTable();
                              toastr.success(res.message || 'Cost saved successfully!');
                        } else {
                              toastr.error(res.message || 'Failed to save cost');
                        }
                  })
                  .catch(err => {
                        setButtonLoading(elements.saveCostBtn, false);
                        toastr.error('Failed to save cost');
                        console.error('Save cost error:', err);
                  });
      };

      // ============================================
      // INLINE EDIT MODAL
      // ============================================
      const openInlineEditModal = function (id, amount) {
            document.getElementById('inline_edit_cost_id').value = id;
            document.getElementById('inline_edit_amount').value = parseInt(amount);
            inlineEditModal?.show();

            setTimeout(() => {
                  document.getElementById('inline_edit_amount')?.focus();
            }, 300);
      };

      const saveInlineEdit = function () {
            const id = document.getElementById('inline_edit_cost_id').value;
            const amount = document.getElementById('inline_edit_amount').value;

            if (!amount || !Number.isInteger(Number(amount)) || Number(amount) < 1) {
                  toastr.error('Please enter a valid whole number');
                  return;
            }

            setButtonLoading(elements.saveInlineEditBtn, true);

            // First get the current cost data
            const showUrl = config.routes.showCost.replace(':id', id);

            fetch(showUrl, {
                  method: 'GET',
                  headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                  }
            })
                  .then(r => r.json())
                  .then(res => {
                        if (res.success && res.data) {
                              const cost = res.data;
                              const updateUrl = config.routes.updateCost.replace(':id', id);

                              return fetch(updateUrl, {
                                    method: 'PUT',
                                    headers: {
                                          'Content-Type': 'application/json',
                                          'Accept': 'application/json',
                                          'X-CSRF-TOKEN': csrfToken
                                    },
                                    body: JSON.stringify({
                                          cost_date: formatDate(cost.cost_date),
                                          amount: parseInt(amount),
                                          description: cost.description,
                                          branch_id: cost.branch_id
                                    })
                              });
                        }
                        throw new Error('Failed to fetch cost data');
                  })
                  .then(r => r.json())
                  .then(res => {
                        setButtonLoading(elements.saveInlineEditBtn, false);

                        if (res.success) {
                              inlineEditModal?.hide();
                              reloadCostsDataTable();
                              toastr.success('Amount updated successfully!');
                        } else {
                              toastr.error(res.message || 'Failed to update amount');
                        }
                  })
                  .catch(err => {
                        setButtonLoading(elements.saveInlineEditBtn, false);
                        toastr.error('Failed to update amount');
                        console.error('Inline edit error:', err);
                  });
      };

      // ============================================
      // DELETE MODAL
      // ============================================
      const openDeleteModal = function (id) {
            document.getElementById('delete_cost_id').value = id;
            deleteModal?.show();
      };

      const confirmDelete = function () {
            const id = document.getElementById('delete_cost_id').value;

            setButtonLoading(elements.confirmDeleteBtn, true);

            const url = config.routes.deleteCost.replace(':id', id);

            fetch(url, {
                  method: 'DELETE',
                  headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                  }
            })
                  .then(r => r.json())
                  .then(res => {
                        setButtonLoading(elements.confirmDeleteBtn, false);

                        if (res.success) {
                              deleteModal?.hide();
                              reloadCostsDataTable();
                              toastr.success(res.message || 'Cost deleted successfully!');
                        } else {
                              toastr.error(res.message || 'Failed to delete cost');
                        }
                  })
                  .catch(err => {
                        setButtonLoading(elements.confirmDeleteBtn, false);
                        toastr.error('Failed to delete cost');
                        console.error('Delete error:', err);
                  });
      };

      // ============================================
      // EVENT LISTENERS
      // ============================================
      const initEvents = function () {
            // Form submit
            if (elements.form) {
                  elements.form.addEventListener('submit', generateReport);
            }

            // Add cost button
            if (elements.addCostBtn) {
                  elements.addCostBtn.addEventListener('click', openAddCostModal);
            }

            // Cost form submit
            if (elements.costForm) {
                  elements.costForm.addEventListener('submit', saveCost);
            }

            // Refresh costs button
            if (elements.refreshCostsBtn) {
                  elements.refreshCostsBtn.addEventListener('click', function () {
                        reloadCostsDataTable();
                        toastr.info('Cost records refreshed!');
                  });
            }

            // Export buttons
            if (elements.exportExcelBtn) {
                  elements.exportExcelBtn.addEventListener('click', exportToExcel);
            }

            if (elements.exportChartBtn) {
                  elements.exportChartBtn.addEventListener('click', exportChart);
            }

            // Delete confirm
            if (elements.confirmDeleteBtn) {
                  elements.confirmDeleteBtn.addEventListener('click', confirmDelete);
            }

            // Inline edit save
            if (elements.saveInlineEditBtn) {
                  elements.saveInlineEditBtn.addEventListener('click', saveInlineEdit);
            }

            // Enter key on inline edit
            document.getElementById('inline_edit_amount')?.addEventListener('keypress', function (e) {
                  if (e.key === 'Enter') {
                        e.preventDefault();
                        saveInlineEdit();
                  }
            });

            // Branch change in cost modal (for admin)
            if (config.isAdmin) {
                  $('#cost_branch_id').on('change', function () {
                        const selectedBranchId = $(this).val();

                        if (selectedBranchId) {
                              // Show loading state on date field
                              const costDateInput = document.getElementById('cost_date');
                              costDateInput.placeholder = 'Loading available dates...';

                              // Fetch existing dates for selected branch, then init date picker
                              updateExistingCostDates(selectedBranchId, function () {
                                    initCostDatePicker(selectedBranchId);
                              });
                        } else {
                              // No branch selected, disable date field
                              const costDateInput = document.getElementById('cost_date');
                              const $costDate = $('#cost_date');

                              // Destroy existing picker
                              if ($costDate.data('daterangepicker')) {
                                    $costDate.data('daterangepicker').remove();
                              }

                              costDateInput.value = '';
                              costDateInput.disabled = true;
                              costDateInput.classList.add('bg-secondary');
                              costDateInput.placeholder = 'Select branch first';

                              const dateHelpText = document.getElementById('date_help_text');
                              if (dateHelpText) {
                                    dateHelpText.textContent = 'Please select a branch first';
                              }

                              existingCostDates = [];
                        }
                  });
            }
      };

      // ============================================
      // PUBLIC INIT
      // ============================================
      const init = function () {
            initElements();
            initModals();
            initDateRangePicker();
            initCostsDataTable();
            initEvents();
            updateExistingCostDates();
      };

      // ============================================
      // PUBLIC API
      // ============================================
      return {
            init: init,
            openEditCostModal: openEditCostModal,
            openDeleteModal: openDeleteModal,
            openInlineEditModal: openInlineEditModal,
            reloadCostsDataTable: reloadCostsDataTable
      };

})();

// ============================================
// DOM READY
// ============================================
KTUtil.onDOMContentLoaded(function () {
      KTFinanceReport.init();
});