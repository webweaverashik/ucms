"use strict";

/**
 * UCMS Finance Report Module
 * Revenue vs Cost Report with Cost Management
 * Metronic 8 + Bootstrap 5 + DataTables + Toastr + FormValidation
 * 
 * Note: Cost Edit/Delete functionality is restricted to Admin users only
 * Updated: Now includes user-wise (collector) revenue breakdown
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
      let costFormValidator = null;

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

            elements.refreshCostsBtn = document.getElementById('refresh_costs_btn');
            elements.costRecordsTab = document.getElementById('cost_records_tab');

            // Add Cost elements - available to all users
            elements.addCostBtn = document.getElementById('add_cost_btn');
            elements.addCostBtnTab = document.getElementById('add_cost_btn_tab');
            elements.costForm = document.getElementById('cost_form');
            elements.saveCostBtn = document.getElementById('save_cost_btn');

            // Admin-only elements (Edit/Delete)
            if (config.isAdmin) {
                  elements.confirmDeleteBtn = document.getElementById('confirm_delete_cost_btn');
                  elements.saveInlineEditBtn = document.getElementById('save_inline_edit_btn');
            }
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

      // Generate random color for collector chart
      const generateColors = function (count) {
            const colors = [
                  { bg: 'rgba(0, 158, 247, 0.85)', border: 'rgb(0, 158, 247)' },      // Primary blue
                  { bg: 'rgba(80, 205, 137, 0.85)', border: 'rgb(80, 205, 137)' },    // Success green
                  { bg: 'rgba(255, 199, 0, 0.85)', border: 'rgb(255, 199, 0)' },      // Warning yellow
                  { bg: 'rgba(125, 82, 179, 0.85)', border: 'rgb(125, 82, 179)' },    // Purple
                  { bg: 'rgba(255, 87, 34, 0.85)', border: 'rgb(255, 87, 34)' },      // Orange
                  { bg: 'rgba(0, 188, 212, 0.85)', border: 'rgb(0, 188, 212)' },      // Cyan
                  { bg: 'rgba(233, 30, 99, 0.85)', border: 'rgb(233, 30, 99)' },      // Pink
                  { bg: 'rgba(63, 81, 181, 0.85)', border: 'rgb(63, 81, 181)' },      // Indigo
                  { bg: 'rgba(139, 195, 74, 0.85)', border: 'rgb(139, 195, 74)' },    // Light green
                  { bg: 'rgba(121, 85, 72, 0.85)', border: 'rgb(121, 85, 72)' },      // Brown
            ];

            const result = [];
            for (let i = 0; i < count; i++) {
                  result.push(colors[i % colors.length]);
            }
            return result;
      };

      // ============================================
      // MODALS INITIALIZATION
      // ============================================
      const initModals = function () {
            // Cost modal available to all users (for Add)
            const costEl = document.getElementById('cost_modal');
            if (costEl) costModal = new bootstrap.Modal(costEl);

            // Delete and Inline Edit modals - Admin only
            if (config.isAdmin) {
                  const deleteEl = document.getElementById('delete_cost_modal');
                  const inlineEditEl = document.getElementById('inline_edit_modal');

                  if (deleteEl) deleteModal = new bootstrap.Modal(deleteEl);
                  if (inlineEditEl) inlineEditModal = new bootstrap.Modal(inlineEditEl);
            }
      };

      // ============================================
      // FORM VALIDATION (FormValidation Plugin) - Available to all users
      // ============================================
      const initCostFormValidation = function () {
            const form = document.getElementById('cost_form');
            if (!form) return;

            // Define validators - branch validation only for admin (non-admin has fixed branch)
            const validators = {};

            // Branch validation only for admin
            if (config.isAdmin) {
                  validators['branch_id'] = {
                        validators: {
                              notEmpty: {
                                    message: 'Branch is required'
                              },
                              callback: {
                                    message: 'Please select a branch',
                                    callback: function (input) {
                                          return input.value !== '' && input.value !== null;
                                    }
                              }
                        }
                  };
            }

            // Common validators for all users
            validators['cost_date'] = {
                  'cost_date': {
                        validators: {
                              notEmpty: {
                                    message: 'Date is required'
                              },
                              callback: {
                                    message: 'Please select an available date',
                                    callback: function (input) {
                                          const value = input.value;
                                          if (!value) return false;

                                          // Check if date is in existing dates (only for new costs)
                                          const costId = document.getElementById('cost_id').value;
                                          if (!costId && existingCostDates.includes(value)) {
                                                return {
                                                      valid: false,
                                                      message: 'A cost record already exists for this date'
                                                };
                                          }
                                          return true;
                                    }
                              }
                        }
                  },
                  'amount': {
                        validators: {
                              notEmpty: {
                                    message: 'Amount is required'
                              },
                              integer: {
                                    message: 'Amount must be a whole number (no decimals)'
                              },
                              greaterThan: {
                                    min: 1,
                                    message: 'Amount must be at least 1'
                              }
                        }
                  },
                  'description': {
                        validators: {
                              stringLength: {
                                    max: 500,
                                    message: 'Description must be less than 500 characters'
                              }
                        }
                  }
            };

            // Initialize FormValidation with delayed trigger (only on blur/submit)
            costFormValidator = FormValidation.formValidation(form, {
                  fields: validators,
                  plugins: {
                        trigger: new FormValidation.plugins.Trigger({
                              event: {
                                    default: 'blur',
                                    'branch_id': 'change',
                                    'cost_date': 'change'
                              }
                        }),
                        bootstrap: new FormValidation.plugins.Bootstrap5({
                              rowSelector: '.fv-row',
                              eleInvalidClass: 'is-invalid',
                              eleValidClass: ''
                        }),
                        submitButton: new FormValidation.plugins.SubmitButton()
                  }
            });

            // Handle form submission
            costFormValidator.on('core.form.valid', function () {
                  saveCost();
            });

            costFormValidator.on('core.form.invalid', function () {
                  toastr.error('Please fill all required fields correctly');
            });
      };

      // Revalidate specific field
      const revalidateField = function (fieldName) {
            if (costFormValidator) {
                  costFormValidator.revalidateField(fieldName);
            }
      };

      // Reset form validation
      const resetFormValidation = function () {
            if (costFormValidator) {
                  costFormValidator.resetForm(true);
            }
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
      // COST DATE PICKER (with disabled dates) - Available to all users
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

            // Branch must be selected first
            if (!branchId) {
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
                  autoUpdateInput: false,
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

                  if (!existingCostDates.includes(dateStr)) {
                        $costDate.val(dateStr);
                  }
            });

            // Handle apply event to ensure date is set
            $costDate.on('apply.daterangepicker', function (ev, picker) {
                  const dateStr = picker.startDate.format('DD-MM-YYYY');
                  if (!existingCostDates.includes(dateStr)) {
                        $(this).val(dateStr);
                        if (!isModalInitializing) {
                              revalidateField('cost_date');
                        }
                  } else {
                        $(this).val('');
                        toastr.warning('This date already has a cost record. Please select another date.');
                        if (!isModalInitializing) {
                              revalidateField('cost_date');
                        }
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

            // Define columns based on user role
            const columns = [
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
                        className: 'text-center',
                        render: function (data, type, row) {
                              // Only show inline edit button for admin
                              if (config.isAdmin) {
                                    return `
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                          <span class="fw-bold text-gray-900">${formatCurrency(data)}</span>
                                          <button type="button" class="btn btn-icon btn-sm btn-light-primary amount-edit-btn"
                                                onclick="KTFinanceReport.openInlineEditModal(${row.id}, ${data})" title="Edit Amount">
                                                <i class="ki-outline ki-pencil fs-6"></i>
                                          </button>
                                    </div>`;
                              } else {
                                    return `<span class="fw-bold text-gray-900">${formatCurrency(data)}</span>`;
                              }
                        }
                  },
                  {
                        data: 'description',
                        className: 'text-center',
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
                  }
            ];

            // Add actions column only for admin
            if (config.isAdmin) {
                  columns.push({
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
                  });
            }

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
                  columns: columns,
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
      // RENDER REPORT TABLE (with User/Collector columns)
      // ============================================
      const renderReportTable = function (data) {
            const dates = Object.keys(data.report).sort().reverse();
            const classes = data.classes;
            const classesInfo = data.classesInfo || []; // Contains id, name, is_active
            const collectors = data.collectors || {};
            const collectorIds = Object.keys(collectors);
            const collectorReport = data.collectorReport || {};

            let grandTotalRevenue = 0;
            let grandTotalCost = 0;
            let collectorGrandTotals = {};

            // Initialize collector grand totals
            collectorIds.forEach(id => {
                  collectorGrandTotals[id] = 0;
            });

            // Calculate number of class columns for colspan
            const classColCount = classes.length;
            const collectorColCount = collectorIds.length;

            let html = `
            <h4 class="fw-bold text-gray-800 mb-4">Revenue vs Cost Details</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-row-bordered align-middle text-center">
                    <thead>
                        <tr class="fw-bold text-gray-700 bg-light">
                            <th rowspan="2" class="min-w-100px align-middle">Date</th>`;

            // Class columns header
            if (classColCount > 0) {
                  html += `<th colspan="${classColCount}" class="bg-light-info text-info">Class-wise Revenue</th>`;
            }

            html += `<th rowspan="2" class="min-w-120px bg-light-primary align-middle">Total Revenue</th>`;

            // Collector columns header (if any collectors exist)
            if (collectorColCount > 0) {
                  html += `<th colspan="${collectorColCount}" class="bg-light-success text-success">Collected By (User-wise)</th>`;
            }

            html += `
                            <th rowspan="2" class="min-w-100px bg-light-danger align-middle">Cost</th>
                            <th rowspan="2" class="min-w-100px bg-light-warning align-middle">Net Profit</th>
                        </tr>
                        <tr class="fw-bold text-muted bg-light">`;

            // Class sub-headers with inactive indicator
            classesInfo.forEach(cls => {
                  if (cls.is_active) {
                        html += `<th class="min-w-80px fs-7">${cls.name}</th>`;
                  } else {
                        html += `<th class="min-w-80px fs-7">
                              <span class="text-gray-500">${cls.name}</span>
                              <span class="badge badge-light-danger fs-8 ms-1" title="Inactive Class">
                                    <i class="ki-outline ki-cross-circle fs-7"></i>
                              </span>
                        </th>`;
                  }
            });

            // Collector sub-headers
            collectorIds.forEach(id => {
                  const name = collectors[id];
                  // Truncate long names
                  const displayName = name.length > 15 ? name.substring(0, 12) + '...' : name;
                  html += `<th class="min-w-100px fs-7 text-success" title="${name}">${displayName}</th>`;
            });

            html += `</tr>
                    </thead>
                    <tbody>`;

            dates.forEach(date => {
                  const dailyData = data.report[date];
                  const dailyCollectorData = collectorReport[date] || {};
                  let dailyTotal = 0;

                  html += `<tr><td class="fw-semibold">${date}</td>`;

                  // Class columns
                  classes.forEach(cls => {
                        const value = parseInt(dailyData[cls] || 0);
                        dailyTotal += value;
                        html += `<td class="text-gray-700">${value > 0 ? formatCurrency(value) : '<span class="text-muted">-</span>'}</td>`;
                  });

                  // Total Revenue
                  html += `<td class="fw-bold bg-light-primary text-primary">${formatCurrency(dailyTotal)}</td>`;

                  // Collector columns
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

            html += `
                    </tbody>
                    <tfoot>
                        <tr class="fw-bolder bg-gray-900 text-white">
                            <td>Grand Total</td>`;

            // Empty cells for class columns
            classes.forEach(() => {
                  html += `<td>-</td>`;
            });

            // Grand total revenue
            html += `<td class="text-warning">${formatCurrency(grandTotalRevenue)}</td>`;

            // Collector grand totals
            collectorIds.forEach(id => {
                  html += `<td class="text-info">${formatCurrency(collectorGrandTotals[id])}</td>`;
            });

            // Grand total cost and net profit
            html += `
                            <td class="text-danger">${formatCurrency(grandTotalCost)}</td>
                            <td class="${grandNet >= 0 ? 'text-success' : 'text-danger'}">${formatCurrency(grandNet)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>`;

            // Add Collector Summary Card
            if (collectorColCount > 0) {
                  html += `
                  <div class="separator separator-dashed my-8"></div>
                  <h4 class="fw-bold text-gray-800 mb-4">
                        <i class="ki-outline ki-profile-user fs-2 text-primary me-2"></i>
                        Collector-wise Summary
                  </h4>
                  <div class="row g-4 mb-5">`;

                  // Sort collectors by total amount (descending)
                  const sortedCollectors = collectorIds.sort((a, b) => collectorGrandTotals[b] - collectorGrandTotals[a]);

                  sortedCollectors.forEach((id, index) => {
                        const name = collectors[id];
                        const total = collectorGrandTotals[id];
                        const percentage = grandTotalRevenue > 0 ? ((total / grandTotalRevenue) * 100).toFixed(1) : 0;

                        // Different colors for each card
                        const cardColors = ['primary', 'success', 'info', 'warning', 'danger', 'dark'];
                        const color = cardColors[index % cardColors.length];

                        html += `
                        <div class="col-xl-3 col-md-4 col-sm-6">
                              <div class="card border border-${color} border-dashed bg-light-${color}">
                                    <div class="card-body py-4 px-5">
                                          <div class="d-flex align-items-center">
                                                <div class="symbol symbol-45px symbol-circle me-3">
                                                      <span class="symbol-label bg-${color} text-white fs-4 fw-bold">
                                                            ${name.charAt(0).toUpperCase()}
                                                      </span>
                                                </div>
                                                <div class="flex-grow-1">
                                                      <span class="text-gray-800 fw-bold d-block fs-6">${name}</span>
                                                      <span class="text-${color} fw-semibold d-block fs-3">${formatCurrency(total)}</span>
                                                </div>
                                                <div class="text-end">
                                                      <span class="badge badge-light-${color} fs-7">${percentage}%</span>
                                                </div>
                                          </div>
                                    </div>
                              </div>
                        </div>`;
                  });

                  html += `</div>`;
            }

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
      // EXPORT FUNCTIONS (Updated for collectors)
      // ============================================
      const exportToExcel = function () {
            if (!reportData) {
                  toastr.error('Please generate a report first');
                  return;
            }

            const dates = Object.keys(reportData.report).sort();
            const classes = reportData.classes;
            const classesInfo = reportData.classesInfo || [];
            const collectors = reportData.collectors || {};
            const collectorIds = Object.keys(collectors);
            const collectorReport = reportData.collectorReport || {};
            const wsData = [];

            // Header row with inactive indicator
            const classHeaders = classesInfo.map(cls => cls.is_active ? cls.name : `${cls.name} (Inactive)`);
            const header = ['Date', ...classHeaders, 'Total Revenue'];

            // Add collector headers
            collectorIds.forEach(id => {
                  header.push(collectors[id] + ' (Collected)');
            });

            header.push('Cost', 'Net Profit');
            wsData.push(header);

            // Data rows
            let grandRevenue = 0, grandCost = 0;
            let collectorGrandTotals = {};
            collectorIds.forEach(id => { collectorGrandTotals[id] = 0; });

            dates.forEach(date => {
                  const row = [date];
                  let dailyTotal = 0;

                  // Class-wise values
                  classes.forEach(cls => {
                        const value = parseInt(reportData.report[date][cls] || 0);
                        dailyTotal += value;
                        row.push(value);
                  });

                  row.push(dailyTotal);

                  // Collector-wise values
                  const dailyCollectorData = collectorReport[date] || {};
                  collectorIds.forEach(id => {
                        const value = parseInt(dailyCollectorData[id] || 0);
                        collectorGrandTotals[id] += value;
                        row.push(value);
                  });

                  const cost = parseInt(reportData.costs[date] || 0);
                  const net = dailyTotal - cost;
                  row.push(cost, net);
                  wsData.push(row);
                  grandRevenue += dailyTotal;
                  grandCost += cost;
            });

            // Grand total row
            const grandTotalRow = ['Grand Total', ...Array(classes.length).fill(''), grandRevenue];
            collectorIds.forEach(id => {
                  grandTotalRow.push(collectorGrandTotals[id]);
            });
            grandTotalRow.push(grandCost, grandRevenue - grandCost);
            wsData.push(grandTotalRow);

            // Add empty row and collector summary
            wsData.push([]);
            wsData.push(['Collector Summary']);
            wsData.push(['Collector Name', 'Total Collected', 'Percentage']);
            collectorIds.forEach(id => {
                  const total = collectorGrandTotals[id];
                  const percentage = grandRevenue > 0 ? ((total / grandRevenue) * 100).toFixed(1) + '%' : '0%';
                  wsData.push([collectors[id], total, percentage]);
            });

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

            // Create a temporary canvas with white background
            const originalCanvas = elements.chartCanvas;
            const tempCanvas = document.createElement('canvas');
            const tempCtx = tempCanvas.getContext('2d');

            // Set dimensions
            tempCanvas.width = originalCanvas.width;
            tempCanvas.height = originalCanvas.height;

            // Fill with white background
            tempCtx.fillStyle = '#ffffff';
            tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);

            // Draw the chart on top of white background
            tempCtx.drawImage(originalCanvas, 0, 0);

            // Download the image
            const link = document.createElement('a');
            link.download = 'finance_chart_' + moment().format('YYYY-MM-DD_HH-mm') + '.png';
            link.href = tempCanvas.toDataURL('image/png', 1.0);
            link.click();

            toastr.success('Chart image downloaded successfully!');
      };

      // ============================================
      // COST MODAL FUNCTIONS
      // Add Cost: Available to all users
      // Edit Cost: Admin only
      // ============================================
      let isModalInitializing = false;

      const openAddCostModal = function () {
            isModalInitializing = true;

            document.getElementById('cost_modal_title').textContent = 'Add Daily Cost';
            elements.costForm?.reset();
            document.getElementById('cost_id').value = '';
            document.getElementById('cost_date').value = '';

            // Reset form validation
            resetFormValidation();

            if (config.isAdmin) {
                  // Admin: Reset branch select and disable date until branch is selected
                  $('#cost_branch_id').val(null).trigger('change.select2');

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
                  // Non-admin: Branch is pre-selected, initialize date picker immediately
                  const branchId = config.userBranchId;
                  if (branchId) {
                        updateExistingCostDates(branchId, function () {
                              initCostDatePicker(branchId);
                        });
                  }
            }

            costModal?.show();

            // Reset flag after modal is shown
            setTimeout(function () {
                  isModalInitializing = false;
            }, 300);
      };

      const openEditCostModal = function (id) {
            // Check if user is admin
            if (!config.isAdmin) {
                  toastr.error('You do not have permission to edit costs');
                  return;
            }

            isModalInitializing = true;

            document.getElementById('cost_modal_title').textContent = 'Edit Cost';

            // Reset form validation
            resetFormValidation();

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

                              if (document.getElementById('cost_branch_id')) {
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
                                                revalidateField('cost_date');
                                          }
                                    });

                                    // Set the current date value
                                    $costDate.val(currentDate);

                                    // Handle apply event
                                    $costDate.on('apply.daterangepicker', function (ev, picker) {
                                          const dateStr = picker.startDate.format('DD-MM-YYYY');
                                          if (!filteredDates.includes(dateStr)) {
                                                $(this).val(dateStr);
                                                if (!isModalInitializing) {
                                                      revalidateField('cost_date');
                                                }
                                          } else {
                                                $(this).val(currentDate);
                                                toastr.warning('This date already has a cost record. Please select another date.');
                                          }
                                    });

                                    costModal?.show();

                                    // Reset flag after modal is shown
                                    setTimeout(function () {
                                          isModalInitializing = false;
                                    }, 300);
                              });
                        } else {
                              toastr.error('Failed to load cost data');
                              isModalInitializing = false;
                        }
                  })
                  .catch(err => {
                        toastr.error('Failed to load cost data');
                        console.error('Load cost error:', err);
                        isModalInitializing = false;
                  });
      };

      const saveCost = function () {
            const costId = document.getElementById('cost_id').value;

            // Only admin can update existing costs
            if (costId && !config.isAdmin) {
                  toastr.error('You do not have permission to edit costs');
                  return;
            }

            const costDate = document.getElementById('cost_date').value;
            const amount = document.getElementById('cost_amount').value;
            const description = document.getElementById('cost_description').value;
            const branchId = document.getElementById('cost_branch_id')?.value;

            // Double-check date is not in existing dates (for new costs)
            if (!costId && existingCostDates.includes(costDate)) {
                  toastr.error('A cost record already exists for this date. Please select another date.');
                  return;
            }

            setButtonLoading(elements.saveCostBtn, true);

            const isUpdate = !!costId;

            // Only admin can use update route
            if (isUpdate && !config.isAdmin) {
                  setButtonLoading(elements.saveCostBtn, false);
                  toastr.error('You do not have permission to edit costs');
                  return;
            }

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

      // Handle form submit event (for validation)
      const handleCostFormSubmit = function (e) {
            e.preventDefault();

            if (costFormValidator) {
                  costFormValidator.validate();
            } else {
                  saveCost();
            }
      };

      // ============================================
      // INLINE EDIT MODAL (Admin Only)
      // ============================================
      const openInlineEditModal = function (id, amount) {
            // Check if user is admin
            if (!config.isAdmin) {
                  toastr.error('You do not have permission to edit costs');
                  return;
            }

            document.getElementById('inline_edit_cost_id').value = id;
            document.getElementById('inline_edit_amount').value = parseInt(amount);
            inlineEditModal?.show();

            setTimeout(() => {
                  document.getElementById('inline_edit_amount')?.focus();
            }, 300);
      };

      const saveInlineEdit = function () {
            // Check if user is admin
            if (!config.isAdmin) {
                  toastr.error('You do not have permission to edit costs');
                  return;
            }

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
      // DELETE MODAL (Admin Only)
      // ============================================
      const openDeleteModal = function (id) {
            // Check if user is admin
            if (!config.isAdmin) {
                  toastr.error('You do not have permission to delete costs');
                  return;
            }

            document.getElementById('delete_cost_id').value = id;
            deleteModal?.show();
      };

      const confirmDelete = function () {
            // Check if user is admin
            if (!config.isAdmin) {
                  toastr.error('You do not have permission to delete costs');
                  return;
            }

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

            // Cost Records tab - refresh DataTable when shown
            if (elements.costRecordsTab) {
                  elements.costRecordsTab.addEventListener('shown.bs.tab', function () {
                        if (costsDataTable) {
                              costsDataTable.columns.adjust().draw(false);
                        }
                  });
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

            // Add cost buttons - available to all users
            if (elements.addCostBtn) {
                  elements.addCostBtn.addEventListener('click', openAddCostModal);
            }

            if (elements.addCostBtnTab) {
                  elements.addCostBtnTab.addEventListener('click', openAddCostModal);
            }

            // Cost form submit - available to all users (for Add)
            if (elements.costForm) {
                  elements.costForm.addEventListener('submit', handleCostFormSubmit);
            }

            // Revalidate amount field on input
            document.getElementById('cost_amount')?.addEventListener('input', function () {
                  revalidateField('amount');
            });

            // Revalidate description field on input
            document.getElementById('cost_description')?.addEventListener('input', function () {
                  revalidateField('description');
            });

            // Branch change in cost modal - Admin only (non-admin has fixed branch)
            if (config.isAdmin) {
                  $('#cost_branch_id').on('change', function () {
                        if (isModalInitializing) {
                              return;
                        }

                        const selectedBranchId = $(this).val();

                        revalidateField('branch_id');

                        if (selectedBranchId) {
                              const costDateInput = document.getElementById('cost_date');
                              costDateInput.placeholder = 'Loading available dates...';

                              updateExistingCostDates(selectedBranchId, function () {
                                    initCostDatePicker(selectedBranchId);
                              });
                        } else {
                              const costDateInput = document.getElementById('cost_date');
                              const $costDate = $('#cost_date');

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

                  // Delete confirm - Admin only
                  if (elements.confirmDeleteBtn) {
                        elements.confirmDeleteBtn.addEventListener('click', confirmDelete);
                  }

                  // Inline edit save - Admin only
                  if (elements.saveInlineEditBtn) {
                        elements.saveInlineEditBtn.addEventListener('click', saveInlineEdit);
                  }

                  // Enter key on inline edit - Admin only
                  document.getElementById('inline_edit_amount')?.addEventListener('keypress', function (e) {
                        if (e.key === 'Enter') {
                              e.preventDefault();
                              saveInlineEdit();
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

            // Initialize cost form validation for all users (Add Cost available to all)
            initCostFormValidation();

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