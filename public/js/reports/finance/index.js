"use strict";

/**
 * UCMS Finance Report Module
 * Revenue vs Cost Report with Cost Management (CostType & CostEntry)
 * Metronic 8 + Bootstrap 5 + DataTables + Toastr + Tagify
 */

var KTFinanceReport = (function () {
      // ============================================
      // STATE & CONFIGURATION
      // ============================================

      let financeChart = null;
      let costsDataTable = null;
      let costDatePicker = null;
      let costTypesTagify = null;
      let costModal = null;
      let editCostModal = null;
      let deleteModal = null;
      let existingCostDates = [];
      let availableCostTypes = [];
      let selectedCostEntries = {};
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
            elements.refreshCostsBtn = document.getElementById('refresh_costs_btn');
            elements.costRecordsTab = document.getElementById('cost_records_tab');

            // Collector Summary elements
            elements.collectorSummarySection = document.getElementById('collector_summary_section');
            elements.collectorSummaryCards = document.getElementById('collector_summary_cards');
            elements.collectorCountBadge = document.getElementById('collector_count_badge');

            // Add Cost elements
            elements.addCostBtn = document.getElementById('add_cost_btn');
            elements.addCostBtnTab = document.getElementById('add_cost_btn_tab');
            elements.costForm = document.getElementById('cost_form');
            elements.saveCostBtn = document.getElementById('save_cost_btn');
            elements.costEntriesList = document.getElementById('cost_entries_list');
            elements.costTotalSection = document.getElementById('cost_total_section');
            elements.costTotalAmount = document.getElementById('cost_total_amount');

            // Admin-only elements
            if (config.isAdmin) {
                  elements.editCostForm = document.getElementById('edit_cost_form');
                  elements.updateCostBtn = document.getElementById('update_cost_btn');
                  elements.confirmDeleteBtn = document.getElementById('confirm_delete_cost_btn');
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
      // MODALS INITIALIZATION
      // ============================================

      const initModals = function () {
            const costEl = document.getElementById('cost_modal');
            if (costEl) costModal = new bootstrap.Modal(costEl);

            if (config.isAdmin) {
                  const editCostEl = document.getElementById('edit_cost_modal');
                  const deleteEl = document.getElementById('delete_cost_modal');
                  if (editCostEl) editCostModal = new bootstrap.Modal(editCostEl);
                  if (deleteEl) deleteModal = new bootstrap.Modal(deleteEl);
            }
      };

      // ============================================
      // COST TYPES LOADING
      // ============================================

      const loadCostTypes = function (callback) {
            fetch(config.routes.costTypes, {
                  method: 'GET',
                  headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                  }
            })
                  .then(r => r.json())
                  .then(res => {
                        if (res.success && res.data) {
                              availableCostTypes = res.data;
                              if (callback) callback();
                        }
                  })
                  .catch(err => {
                        console.error('Error loading cost types:', err);
                        toastr.error('Failed to load cost types');
                  });
      };

      // ============================================
      // TAGIFY INITIALIZATION
      // ============================================

      const initCostTypesTagify = function () {
            const input = document.getElementById('cost_types_tagify');
            if (!input) return;

            // Destroy existing tagify instance if exists
            if (costTypesTagify) {
                  costTypesTagify.destroy();
                  costTypesTagify = null;
            }

            // Prepare whitelist from available cost types
            const whitelist = availableCostTypes.map(ct => ({
                  value: ct.name,
                  id: ct.id
            }));

            costTypesTagify = new Tagify(input, {
                  whitelist: whitelist,
                  enforceWhitelist: true,
                  dropdown: {
                        maxItems: 20,
                        enabled: 0,
                        closeOnSelect: false
                  },
                  originalInputValueFormat: valuesArr => valuesArr.map(item => item.id).join(',')
            });

            // Handle tag add
            costTypesTagify.on('add', function (e) {
                  const tagData = e.detail.data;
                  addCostEntryRow(tagData.id, tagData.value);
                  updateCostTotal();
            });

            // Handle tag remove
            costTypesTagify.on('remove', function (e) {
                  const tagData = e.detail.data;
                  removeCostEntryRow(tagData.id);
                  updateCostTotal();
            });
      };

      const resetTagify = function () {
            if (costTypesTagify) {
                  costTypesTagify.removeAllTags();
            }
            selectedCostEntries = {};
            renderEmptyEntriesList();
            updateCostTotal();
      };

      // ============================================
      // COST ENTRIES MANAGEMENT
      // ============================================

      const renderEmptyEntriesList = function () {
            elements.costEntriesList.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="ki-outline ki-information fs-3x text-gray-400 mb-3"></i>
                <p class="mb-0">Select cost types above to add entries</p>
            </div>`;
            elements.costTotalSection?.classList.add('d-none');
      };

      const addCostEntryRow = function (costTypeId, costTypeName) {
            // Convert to string for consistent comparison
            const costTypeIdStr = String(costTypeId);

            // Check if entry already exists
            if (selectedCostEntries[costTypeIdStr]) return;

            selectedCostEntries[costTypeIdStr] = {
                  cost_type_id: costTypeId,
                  name: costTypeName,
                  amount: 0
            };

            // Remove empty state if present
            const emptyState = elements.costEntriesList.querySelector('.text-center');
            if (emptyState) {
                  elements.costEntriesList.innerHTML = '';
            }

            const row = document.createElement('div');
            row.className = 'cost-entry-row';
            row.id = `cost_entry_row_${costTypeIdStr}`;
            row.innerHTML = `
            <span class="cost-type-badge">${costTypeName}</span>
            <div class="flex-grow-1"></div>
            <div class="input-group input-group-solid amount-input">
                <span class="input-group-text">৳</span>
                <input type="number" class="form-control form-control-solid entry-amount-input" 
                       data-cost-type-id="${costTypeIdStr}" min="1" step="1" placeholder="0">
            </div>`;

            elements.costEntriesList.appendChild(row);
            elements.costTotalSection?.classList.remove('d-none');

            // Add event listener for amount input
            const amountInput = row.querySelector('.entry-amount-input');
            amountInput.addEventListener('input', function () {
                  updateEntryAmount(costTypeIdStr, this.value);
            });

            // Focus on the amount input
            setTimeout(() => {
                  amountInput?.focus();
            }, 100);
      };

      const removeCostEntryRow = function (costTypeId) {
            const costTypeIdStr = String(costTypeId);
            delete selectedCostEntries[costTypeIdStr];

            const row = document.getElementById(`cost_entry_row_${costTypeIdStr}`);
            if (row) {
                  row.remove();
            }

            // Show empty state if no entries
            if (Object.keys(selectedCostEntries).length === 0) {
                  renderEmptyEntriesList();
            }
      };

      const removeEntryFromTagify = function (costTypeId) {
            const costTypeIdStr = String(costTypeId);
            if (costTypesTagify) {
                  const tagToRemove = costTypesTagify.value.find(tag => String(tag.id) === costTypeIdStr);
                  if (tagToRemove) {
                        costTypesTagify.removeTags([tagToRemove]);
                  }
            }
      };

      const updateEntryAmount = function (costTypeId, value) {
            const costTypeIdStr = String(costTypeId);
            if (selectedCostEntries[costTypeIdStr]) {
                  selectedCostEntries[costTypeIdStr].amount = parseInt(value) || 0;
                  updateCostTotal();
            }
      };

      const updateCostTotal = function () {
            let total = 0;
            Object.values(selectedCostEntries).forEach(entry => {
                  total += entry.amount || 0;
            });

            if (elements.costTotalAmount) {
                  elements.costTotalAmount.textContent = formatCurrency(total);
            }

            // Also update edit modal total if visible
            const editTotal = document.getElementById('edit_cost_total');
            if (editTotal && editCostModal?._isShown) {
                  let editSum = 0;
                  document.querySelectorAll('#edit_entries_list .edit-entry-amount').forEach(input => {
                        editSum += parseInt(input.value) || 0;
                  });
                  editTotal.textContent = formatCurrency(editSum);
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
      // COST DATE PICKER
      // ============================================

      const initCostDatePicker = function (branchId = null) {
            const $costDate = $('#cost_date');
            const costDateInput = document.getElementById('cost_date');
            const dateHelpText = document.getElementById('date_help_text');

            // Destroy existing picker
            if ($costDate.data('daterangepicker')) {
                  $costDate.data('daterangepicker').remove();
            }
            $costDate.val('');

            if (!branchId) {
                  costDateInput.disabled = true;
                  costDateInput.classList.add('bg-secondary');
                  costDateInput.placeholder = 'Select branch first';
                  if (dateHelpText) dateHelpText.textContent = 'Please select a branch first';
                  return;
            }

            costDateInput.disabled = false;
            costDateInput.classList.remove('bg-secondary');
            costDateInput.placeholder = 'Select available date';
            if (dateHelpText) dateHelpText.textContent = 'Dates with existing costs are disabled';

            $costDate.daterangepicker({
                  singleDatePicker: true,
                  showDropdowns: true,
                  autoApply: true,
                  autoUpdateInput: false,
                  maxDate: moment(),
                  locale: { format: 'DD-MM-YYYY' },
                  isInvalidDate: function (date) {
                        return existingCostDates.includes(date.format('DD-MM-YYYY'));
                  }
            });

            $costDate.on('apply.daterangepicker', function (ev, picker) {
                  const dateStr = picker.startDate.format('DD-MM-YYYY');
                  if (!existingCostDates.includes(dateStr)) {
                        $(this).val(dateStr);
                  } else {
                        $(this).val('');
                        toastr.warning('This date already has a cost record.');
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
                        existingCostDates = res.success && res.data ? res.data.map(c => formatDate(c.cost_date)) : [];
                        if (callback) callback();
                  })
                  .catch(() => {
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

            const columns = [
                  {
                        data: 'cost_date',
                        className: 'ps-4',
                        render: data => `<span class="fw-semibold text-gray-800">${formatDate(data)}</span>`
                  },
                  {
                        data: 'branch',
                        render: data => data ? `<span class="badge badge-light-primary">${data.branch_name} (${data.branch_prefix})</span>` : '-'
                  },
                  {
                        data: 'entries',
                        render: function (data) {
                              if (!data || data.length === 0) return '<span class="text-muted">No entries</span>';
                              return data.map(entry => `
                        <span class="entry-badge">
                            <span class="type-name">${entry.cost_type?.name || 'Unknown'}</span>
                            <span class="type-amount">${formatCurrency(entry.amount)}</span>
                        </span>
                    `).join('');
                        }
                  },
                  {
                        data: 'total_amount',
                        className: 'text-end',
                        render: data => `<span class="fw-bold text-primary fs-5">${formatCurrency(data)}</span>`
                  },
                  {
                        data: 'created_by',
                        render: data => data ? `<span class="text-gray-600">${data.name}</span>` : '-'
                  }
            ];

            if (config.isAdmin) {
                  columns.push({
                        data: null,
                        className: 'text-center pe-4',
                        orderable: false,
                        render: (data, type, row) => `
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-icon btn-sm btn-light-primary" 
                                onclick="KTFinanceReport.openEditCostModal(${row.id})" title="Edit">
                            <i class="ki-outline ki-pencil fs-6"></i>
                        </button>
                        <button type="button" class="btn btn-icon btn-sm btn-light-danger" 
                                onclick="KTFinanceReport.openDeleteModal(${row.id})" title="Delete">
                            <i class="ki-outline ki-trash fs-6"></i>
                        </button>
                    </div>`
                  });
            }

            costsDataTable = $(table).DataTable({
                  processing: true,
                  serverSide: false,
                  ajax: {
                        url: config.routes.costs,
                        type: 'GET',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        dataSrc: function (json) {
                              if (json.success) {
                                    existingCostDates = json.data.map(c => formatDate(c.cost_date));
                                    return json.data;
                              }
                              return [];
                        }
                  },
                  columns: columns,
                  order: [[0, 'desc']],
                  pageLength: 10,
                  language: { emptyTable: "No cost records found" },
                  drawCallback: () => KTMenu.init()
            });
      };

      const reloadCostsDataTable = function () {
            if (costsDataTable) costsDataTable.ajax.reload(null, false);
      };

      // ============================================
      // ADD COST MODAL
      // ============================================

      const openAddCostModal = function () {
            document.getElementById('cost_modal_title').textContent = 'Add Daily Cost';
            elements.costForm?.reset();
            resetTagify();

            if (config.isAdmin) {
                  $('#cost_branch_id').val(null).trigger('change.select2');
                  const costDateInput = document.getElementById('cost_date');
                  costDateInput.disabled = true;
                  costDateInput.classList.add('bg-secondary');
                  costDateInput.placeholder = 'Select branch first';
                  existingCostDates = [];
            } else {
                  const branchId = config.userBranchId;
                  if (branchId) {
                        updateExistingCostDates(branchId, () => initCostDatePicker(branchId));
                  }
            }

            // Load cost types and init tagify
            loadCostTypes(() => {
                  initCostTypesTagify();
            });

            costModal?.show();
      };

      const saveCost = function () {
            const costDate = document.getElementById('cost_date').value;
            const branchId = document.getElementById('cost_branch_id')?.value;

            // Validation
            if (!costDate) {
                  toastr.error('Please select a date');
                  return;
            }
            if (config.isAdmin && !branchId) {
                  toastr.error('Please select a branch');
                  return;
            }

            const entries = Object.values(selectedCostEntries).filter(e => e.amount > 0);
            if (entries.length === 0) {
                  toastr.error('Please add at least one cost entry with amount');
                  return;
            }

            // Check for invalid amounts
            const invalidEntries = Object.values(selectedCostEntries).filter(e => e.amount < 1 && e.amount !== 0);
            if (invalidEntries.length > 0) {
                  toastr.error('Amount must be at least 1 for each entry');
                  return;
            }

            setButtonLoading(elements.saveCostBtn, true);

            fetch(config.routes.storeCost, {
                  method: 'POST',
                  headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                  },
                  body: JSON.stringify({
                        cost_date: costDate,
                        branch_id: branchId || config.userBranchId,
                        entries: entries.map(e => ({
                              cost_type_id: e.cost_type_id,
                              amount: e.amount
                        }))
                  })
            })
                  .then(r => r.json())
                  .then(res => {
                        setButtonLoading(elements.saveCostBtn, false);
                        if (res.success) {
                              costModal?.hide();
                              reloadCostsDataTable();
                              toastr.success(res.message || 'Cost added successfully!');
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
      // EDIT COST MODAL (Admin Only)
      // ============================================

      const openEditCostModal = function (id) {
            if (!config.isAdmin) {
                  toastr.error('Permission denied');
                  return;
            }

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
                              document.getElementById('edit_cost_id').value = cost.id;
                              document.getElementById('edit_cost_date').textContent = formatDate(cost.cost_date);
                              document.getElementById('edit_cost_branch').textContent = cost.branch
                                    ? `${cost.branch.branch_name} (${cost.branch.branch_prefix})`
                                    : '-';

                              // Render entries (without delete button)
                              const entriesList = document.getElementById('edit_entries_list');
                              entriesList.innerHTML = '';

                              cost.entries.forEach(entry => {
                                    const row = document.createElement('div');
                                    row.className = 'edit-entry-row';
                                    row.id = `edit_entry_row_${entry.id}`;
                                    row.innerHTML = `
                            <span class="entry-type-name">${entry.cost_type?.name || 'Unknown'}</span>
                            <div class="input-group input-group-solid entry-amount-input">
                                <span class="input-group-text">৳</span>
                                <input type="number" class="form-control form-control-solid edit-entry-amount" 
                                       data-entry-id="${entry.id}" value="${entry.amount}" min="1" step="1"
                                       oninput="KTFinanceReport.updateEditTotal()">
                            </div>`;
                                    entriesList.appendChild(row);
                              });

                              updateEditTotal();
                              editCostModal?.show();
                        } else {
                              toastr.error('Failed to load cost data');
                        }
                  })
                  .catch(err => {
                        toastr.error('Failed to load cost data');
                        console.error('Load cost error:', err);
                  });
      };

      const updateEditTotal = function () {
            let total = 0;
            document.querySelectorAll('#edit_entries_list .edit-entry-amount').forEach(input => {
                  total += parseInt(input.value) || 0;
            });
            const editTotal = document.getElementById('edit_cost_total');
            if (editTotal) editTotal.textContent = formatCurrency(total);
      };

      const updateCost = function () {
            if (!config.isAdmin) return;

            const costId = document.getElementById('edit_cost_id').value;
            const entries = [];
            let hasError = false;

            document.querySelectorAll('#edit_entries_list .edit-entry-amount').forEach(input => {
                  const amount = parseInt(input.value) || 0;
                  if (amount < 1) {
                        hasError = true;
                  }
                  entries.push({
                        id: parseInt(input.dataset.entryId),
                        amount: amount
                  });
            });

            if (hasError) {
                  toastr.error('Amount must be at least 1 for each entry');
                  return;
            }

            if (entries.length === 0) {
                  toastr.error('No valid entries to update');
                  return;
            }

            setButtonLoading(elements.updateCostBtn, true);

            const url = config.routes.updateCost.replace(':id', costId);

            fetch(url, {
                  method: 'PUT',
                  headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                  },
                  body: JSON.stringify({ entries: entries })
            })
                  .then(r => r.json())
                  .then(res => {
                        setButtonLoading(elements.updateCostBtn, false);
                        if (res.success) {
                              editCostModal?.hide();
                              reloadCostsDataTable();
                              toastr.success(res.message || 'Cost updated successfully!');
                        } else {
                              toastr.error(res.message || 'Failed to update cost');
                        }
                  })
                  .catch(err => {
                        setButtonLoading(elements.updateCostBtn, false);
                        toastr.error('Failed to update cost');
                        console.error('Update cost error:', err);
                  });
      };

      // ============================================
      // DELETE MODAL (Admin Only)
      // ============================================

      const openDeleteModal = function (id) {
            if (!config.isAdmin) return;
            document.getElementById('delete_cost_id').value = id;
            deleteModal?.show();
      };

      const confirmDelete = function () {
            if (!config.isAdmin) return;

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

            elements.costRecordsTab?.addEventListener('shown.bs.tab', () => {
                  if (costsDataTable) costsDataTable.columns.adjust().draw(false);
            });

            elements.refreshCostsBtn?.addEventListener('click', () => {
                  reloadCostsDataTable();
                  toastr.info('Cost records refreshed!');
            });

            elements.exportExcelBtn?.addEventListener('click', exportToExcel);
            elements.exportChartBtn?.addEventListener('click', exportChart);

            elements.addCostBtn?.addEventListener('click', openAddCostModal);
            elements.addCostBtnTab?.addEventListener('click', openAddCostModal);

            elements.costForm?.addEventListener('submit', e => {
                  e.preventDefault();
                  saveCost();
            });

            if (config.isAdmin) {
                  $('#cost_branch_id').on('change', function () {
                        const branchId = $(this).val();
                        if (branchId) {
                              updateExistingCostDates(branchId, () => initCostDatePicker(branchId));
                        } else {
                              initCostDatePicker(null);
                        }
                  });

                  elements.editCostForm?.addEventListener('submit', e => {
                        e.preventDefault();
                        updateCost();
                  });

                  elements.confirmDeleteBtn?.addEventListener('click', confirmDelete);
            }
      };

      // ============================================
      // INIT
      // ============================================

      const init = function () {
            initElements();
            initModals();
            initDateRangePicker();
            initCostsDataTable();
            initEvents();
            loadCostTypes();
      };

      // ============================================
      // PUBLIC API
      // ============================================

      return {
            init: init,
            openEditCostModal: openEditCostModal,
            openDeleteModal: openDeleteModal,
            updateEditTotal: updateEditTotal,
            reloadCostsDataTable: reloadCostsDataTable
      };
})();

// DOM Ready
KTUtil.onDOMContentLoaded(function () {
      KTFinanceReport.init();
});