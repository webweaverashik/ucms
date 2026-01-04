"use strict";

/**
 * UCMS Cost Records Module
 * Cost Management with DataTable (Today Only Entry)
 * Branch Tabs for Admin + Others Cost Type Support
 * Metronic 8 + Bootstrap 5 + DataTables + Tagify
 */

var KTCostRecords = (function () {
      // ============================================
      // STATE & CONFIGURATION
      // ============================================
      let costsDataTables = {};
      let costTypesTagify = null;
      let costModal = null;
      let editCostModal = null;
      let deleteModal = null;
      let availableCostTypes = [];
      let selectedCostEntries = {};
      let otherCostEntries = [];
      let otherCostCounter = 0;
      let activeBranchId = null;

      const config = window.CostRecordsConfig || {};
      const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

      // ============================================
      // ELEMENTS CACHE
      // ============================================
      const elements = {};

      const initElements = function () {
            elements.addCostBtn = document.getElementById('add_cost_btn');
            elements.refreshCostsBtn = document.getElementById('refresh_costs_btn');
            elements.costForm = document.getElementById('cost_form');
            elements.saveCostBtn = document.getElementById('save_cost_btn');
            elements.costEntriesList = document.getElementById('cost_entries_list');
            elements.costTotalSection = document.getElementById('cost_total_section');
            elements.costTotalAmount = document.getElementById('cost_total_amount');
            elements.otherCostsContainer = document.getElementById('other_costs_container');
            elements.addOtherCostBtn = document.getElementById('add_other_cost_btn');

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

      const escapeHtml = function (text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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

            if (costTypesTagify) {
                  costTypesTagify.destroy();
                  costTypesTagify = null;
            }

            // Filter out "Others" type from tagify - it's handled separately
            const whitelist = availableCostTypes
                  .filter(ct => ct.name.toLowerCase() !== 'others')
                  .map(ct => ({
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

            costTypesTagify.on('add', function (e) {
                  const tagData = e.detail.data;
                  addCostEntryRow(tagData.id, tagData.value);
                  updateCostTotal();
            });

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
            otherCostEntries = [];
            otherCostCounter = 0;
            renderEmptyEntriesList();
            clearOtherCosts();
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
            const costTypeIdStr = String(costTypeId);
            if (selectedCostEntries[costTypeIdStr]) return;

            selectedCostEntries[costTypeIdStr] = {
                  cost_type_id: costTypeId,
                  name: costTypeName,
                  amount: 0
            };

            const emptyState = elements.costEntriesList.querySelector('.text-center');
            if (emptyState) {
                  elements.costEntriesList.innerHTML = '';
            }

            const row = document.createElement('div');
            row.className = 'cost-entry-row';
            row.id = `cost_entry_row_${costTypeIdStr}`;
            row.innerHTML = `
            <span class="cost-type-badge">${escapeHtml(costTypeName)}</span>
            <div class="flex-grow-1"></div>
            <div class="input-group input-group-solid amount-input">
                <span class="input-group-text">৳</span>
                <input type="number" class="form-control form-control-solid entry-amount-input" 
                       data-cost-type-id="${costTypeIdStr}" min="1" step="1" placeholder="0">
            </div>`;

            elements.costEntriesList.appendChild(row);
            elements.costTotalSection?.classList.remove('d-none');

            const amountInput = row.querySelector('.entry-amount-input');
            amountInput.addEventListener('input', function () {
                  updateEntryAmount(costTypeIdStr, this.value);
            });

            setTimeout(() => amountInput?.focus(), 100);
      };

      const removeCostEntryRow = function (costTypeId) {
            const costTypeIdStr = String(costTypeId);
            delete selectedCostEntries[costTypeIdStr];

            const row = document.getElementById(`cost_entry_row_${costTypeIdStr}`);
            if (row) row.remove();

            if (Object.keys(selectedCostEntries).length === 0 && otherCostEntries.length === 0) {
                  renderEmptyEntriesList();
            }
      };

      const updateEntryAmount = function (costTypeId, value) {
            const costTypeIdStr = String(costTypeId);
            if (selectedCostEntries[costTypeIdStr]) {
                  selectedCostEntries[costTypeIdStr].amount = parseInt(value) || 0;
                  updateCostTotal();
            }
      };

      // ============================================
      // OTHER COSTS MANAGEMENT
      // ============================================
      const clearOtherCosts = function () {
            if (elements.otherCostsContainer) {
                  elements.otherCostsContainer.innerHTML = '';
            }
            otherCostEntries = [];
            otherCostCounter = 0;
      };

      const addOtherCostRow = function (description = '', amount = '') {
            otherCostCounter++;
            const rowId = `other_cost_row_${otherCostCounter}`;

            const row = document.createElement('div');
            row.className = 'other-cost-row';
            row.id = rowId;
            row.innerHTML = `
            <div class="d-flex gap-2 align-items-center mb-2">
                <input type="text" class="form-control form-control-solid other-description flex-grow-1" 
                       placeholder="Description (required)" value="${escapeHtml(description)}"
                       data-row-id="${otherCostCounter}" required>
                <div class="input-group input-group-solid" style="width: 160px;">
                    <span class="input-group-text">৳</span>
                    <input type="number" class="form-control form-control-solid other-amount" 
                           min="1" step="1" placeholder="0" value="${amount}"
                           data-row-id="${otherCostCounter}" required>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-light-danger remove-other-cost" 
                        data-row-id="${otherCostCounter}">
                    <i class="ki-outline ki-trash fs-6"></i>
                </button>
            </div>`;

            elements.otherCostsContainer.appendChild(row);

            // Add event listeners
            const descInput = row.querySelector('.other-description');
            const amountInput = row.querySelector('.other-amount');
            const removeBtn = row.querySelector('.remove-other-cost');

            descInput.addEventListener('input', function () {
                  updateOtherCostEntry(this.dataset.rowId);
                  validateOtherCostRow(this.dataset.rowId);
                  updateCostTotal();
            });

            descInput.addEventListener('blur', function () {
                  validateOtherCostRow(this.dataset.rowId);
            });

            amountInput.addEventListener('input', function () {
                  updateOtherCostEntry(this.dataset.rowId);
                  validateOtherCostRow(this.dataset.rowId);
                  updateCostTotal();
            });

            amountInput.addEventListener('blur', function () {
                  validateOtherCostRow(this.dataset.rowId);
            });

            removeBtn.addEventListener('click', function () {
                  removeOtherCostRow(this.dataset.rowId);
            });

            // Initialize entry in array
            otherCostEntries.push({
                  rowId: otherCostCounter,
                  description: description,
                  amount: parseInt(amount) || 0
            });

            elements.costTotalSection?.classList.remove('d-none');
            setTimeout(() => descInput?.focus(), 100);
      };

      const validateOtherCostRow = function (rowId) {
            const row = document.getElementById(`other_cost_row_${rowId}`);
            if (!row) return true;

            const descInput = row.querySelector('.other-description');
            const amountInput = row.querySelector('.other-amount');

            const description = descInput.value.trim();
            const amount = parseInt(amountInput.value) || 0;

            let isValid = true;

            // Reset validation styles
            descInput.classList.remove('is-invalid');
            amountInput.classList.remove('is-invalid');

            // If either field has value, both must be filled
            if (description && amount <= 0) {
                  amountInput.classList.add('is-invalid');
                  isValid = false;
            }

            if (!description && amount > 0) {
                  descInput.classList.add('is-invalid');
                  isValid = false;
            }

            return isValid;
      };

      const validateAllOtherCostRows = function () {
            let allValid = true;
            otherCostEntries.forEach(entry => {
                  if (!validateOtherCostRow(entry.rowId)) {
                        allValid = false;
                  }
            });
            return allValid;
      };

      const updateOtherCostEntry = function (rowId) {
            const row = document.getElementById(`other_cost_row_${rowId}`);
            if (!row) return;

            const description = row.querySelector('.other-description').value.trim();
            const amount = parseInt(row.querySelector('.other-amount').value) || 0;

            const entryIndex = otherCostEntries.findIndex(e => e.rowId == rowId);
            if (entryIndex !== -1) {
                  otherCostEntries[entryIndex].description = description;
                  otherCostEntries[entryIndex].amount = amount;
            }
      };

      const removeOtherCostRow = function (rowId) {
            const row = document.getElementById(`other_cost_row_${rowId}`);
            if (row) row.remove();

            otherCostEntries = otherCostEntries.filter(e => e.rowId != rowId);
            updateCostTotal();

            if (Object.keys(selectedCostEntries).length === 0 && otherCostEntries.length === 0) {
                  renderEmptyEntriesList();
            }
      };

      const updateCostTotal = function () {
            let total = 0;

            // Sum from regular entries
            Object.values(selectedCostEntries).forEach(entry => {
                  total += entry.amount || 0;
            });

            // Sum from other costs
            otherCostEntries.forEach(entry => {
                  total += entry.amount || 0;
            });

            if (elements.costTotalAmount) {
                  elements.costTotalAmount.textContent = formatCurrency(total);
            }

            // Show/hide total section
            if (total > 0 || Object.keys(selectedCostEntries).length > 0 || otherCostEntries.length > 0) {
                  elements.costTotalSection?.classList.remove('d-none');
            } else {
                  elements.costTotalSection?.classList.add('d-none');
            }
      };

      // ============================================
      // CHECK TODAY COST EXISTS
      // ============================================
      const checkTodayCostExists = function (branchId, callback) {
            if (!branchId) {
                  callback(false);
                  return;
            }

            fetch(config.routes.checkTodayCost + '?branch_id=' + branchId, {
                  method: 'GET',
                  headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                  }
            })
                  .then(r => r.json())
                  .then(res => {
                        callback(res.exists || false);
                  })
                  .catch(() => {
                        callback(false);
                  });
      };

      // ============================================
      // COSTS DATATABLE
      // ============================================
      const getDataTableColumns = function (showBranchColumn, showActions) {
            const columns = [
                  {
                        data: 'cost_date',
                        className: 'ps-4',
                        render: data => `<span class="fw-semibold text-gray-800">${formatDate(data)}</span>`
                  }
            ];

            if (showBranchColumn) {
                  columns.push({
                        data: 'branch',
                        render: data => data
                              ? `<span class="badge badge-light-primary">${escapeHtml(data.branch_name)} (${escapeHtml(data.branch_prefix)})</span>`
                              : '-'
                  });
            }

            columns.push({
                  data: 'entries',
                  render: function (data) {
                        if (!data || data.length === 0) return '<span class="text-muted">No entries</span>';
                        return data.map(entry => {
                              const typeName = entry.cost_type?.name || 'Unknown';
                              const isOthers = typeName.toLowerCase() === 'others';
                              const displayName = isOthers && entry.description
                                    ? `Others: ${escapeHtml(entry.description)}`
                                    : escapeHtml(typeName);
                              return `
                        <span class="entry-badge">
                            <span class="type-name">${displayName}</span>
                            <span class="type-amount">${formatCurrency(entry.amount)}</span>
                        </span>`;
                        }).join('');
                  }
            });

            columns.push({
                  data: 'total_amount',
                  className: 'text-end',
                  render: data => `<span class="fw-bold text-primary fs-5">${formatCurrency(data)}</span>`
            });

            columns.push({
                  data: 'created_by',
                  render: data => data ? `<span class="text-gray-600">${escapeHtml(data.name)}</span>` : '-'
            });

            if (showActions) {
                  columns.push({
                        data: null,
                        className: 'text-center pe-4',
                        orderable: false,
                        render: (data, type, row) => `
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-icon btn-sm btn-light-primary" 
                                onclick="KTCostRecords.openEditCostModal(${row.id})" title="Edit">
                            <i class="ki-outline ki-pencil fs-6"></i>
                        </button>
                        <button type="button" class="btn btn-icon btn-sm btn-light-danger" 
                                onclick="KTCostRecords.openDeleteModal(${row.id})" title="Delete">
                            <i class="ki-outline ki-trash fs-6"></i>
                        </button>
                    </div>`
                  });
            }

            return columns;
      };

      const initCostsDataTable = function () {
            if (config.hasBranchTabs) {
                  // Initialize DataTable for each branch tab
                  config.branches.forEach((branch, index) => {
                        initBranchDataTable(branch.id, index === 0);
                  });

                  // Set first branch as active
                  if (config.branches.length > 0) {
                        activeBranchId = config.branches[0].id;
                  }

                  // Handle tab change
                  document.querySelectorAll('#branch_tabs a[data-bs-toggle="tab"]').forEach(tab => {
                        tab.addEventListener('shown.bs.tab', function (e) {
                              const branchId = this.dataset.branchId;
                              activeBranchId = branchId;

                              // Reload and adjust columns
                              if (costsDataTables[branchId]) {
                                    costsDataTables[branchId].ajax.reload(null, false);
                                    costsDataTables[branchId].columns.adjust();
                              }
                        });
                  });

                  // Load counts for all branches
                  loadBranchCounts();
            } else {
                  // Single DataTable for non-admin or single branch
                  initSingleDataTable();
            }
      };

      const initBranchDataTable = function (branchId, isActive) {
            const table = document.getElementById(`costs_datatable_${branchId}`);
            if (!table) return;

            costsDataTables[branchId] = $(table).DataTable({
                  processing: true,
                  serverSide: false,
                  ajax: {
                        url: config.routes.costs,
                        type: 'GET',
                        data: { branch_id: branchId },
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        dataSrc: function (json) {
                              // Update badge count
                              updateBranchCount(branchId, json.data?.length || 0);
                              return json.success ? json.data : [];
                        }
                  },
                  columns: getDataTableColumns(false, true), // No branch column, show actions
                  order: [[0, 'desc']],
                  pageLength: 10,
                  language: {
                        emptyTable: "No cost records found for this branch"
                  },
                  drawCallback: () => KTMenu.init()
            });
      };

      const initSingleDataTable = function () {
            const table = document.getElementById('costs_datatable');
            if (!table) return;

            const showBranch = config.isAdmin;
            const showActions = config.isAdmin;

            costsDataTables['single'] = $(table).DataTable({
                  processing: true,
                  serverSide: false,
                  ajax: {
                        url: config.routes.costs,
                        type: 'GET',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        dataSrc: function (json) {
                              return json.success ? json.data : [];
                        }
                  },
                  columns: getDataTableColumns(showBranch, showActions),
                  order: [[0, 'desc']],
                  pageLength: 10,
                  language: {
                        emptyTable: "No cost records found"
                  },
                  drawCallback: () => KTMenu.init()
            });
      };

      const updateBranchCount = function (branchId, count) {
            const badge = document.querySelector(`.branch-count[data-branch-id="${branchId}"]`);
            if (badge) {
                  badge.textContent = count;
            }
      };

      const loadBranchCounts = function () {
            // Counts are loaded automatically when each DataTable loads
      };

      const reloadCostsDataTable = function () {
            if (config.hasBranchTabs) {
                  // Reload all branch DataTables
                  Object.values(costsDataTables).forEach(dt => {
                        dt.ajax.reload(null, false);
                  });
            } else if (costsDataTables['single']) {
                  costsDataTables['single'].ajax.reload(null, false);
            }
      };

      const reloadActiveBranchTable = function () {
            if (config.hasBranchTabs && activeBranchId && costsDataTables[activeBranchId]) {
                  costsDataTables[activeBranchId].ajax.reload(null, false);
            } else if (costsDataTables['single']) {
                  costsDataTables['single'].ajax.reload(null, false);
            }
      };

      // ============================================
      // ADD COST MODAL
      // ============================================
      const openAddCostModal = function () {
            const branchId = config.isAdmin ? null : config.userBranchId;

            if (!config.isAdmin && branchId) {
                  checkTodayCostExists(branchId, (exists) => {
                        if (exists) {
                              toastr.warning('Cost record already exists for today. Please edit the existing record.');
                              return;
                        }
                        showCostModal();
                  });
            } else {
                  showCostModal();
            }
      };

      const showCostModal = function () {
            document.getElementById('cost_modal_title').textContent = "Add Today's Cost";
            elements.costForm?.reset();
            resetTagify();

            document.getElementById('cost_date').value = config.todayDate;

            if (config.isAdmin) {
                  $('#cost_branch_id').val(null).trigger('change.select2');
            }

            loadCostTypes(() => {
                  initCostTypesTagify();
            });

            costModal?.show();
      };

      const saveCost = function () {
            const costDate = document.getElementById('cost_date').value;
            const branchId = document.getElementById('cost_branch_id')?.value || config.userBranchId;

            if (!costDate) {
                  toastr.error('Date is required');
                  return;
            }

            if (config.isAdmin && !branchId) {
                  toastr.error('Please select a branch');
                  return;
            }

            // Find "Others" cost type
            const othersCostType = availableCostTypes.find(ct => ct.name.toLowerCase() === 'others');

            // Prepare regular entries
            const entries = Object.values(selectedCostEntries)
                  .filter(e => e.amount > 0)
                  .map(e => ({
                        cost_type_id: e.cost_type_id,
                        amount: e.amount
                  }));

            // Validate other cost entries - if added, both description and amount are required
            if (!validateAllOtherCostRows()) {
                  toastr.error('Please fill in both description and amount for all Other Costs entries');
                  return;
            }

            // Prepare valid other entries (both description and amount filled)
            const validOtherEntries = otherCostEntries.filter(e => {
                  const hasDescription = e.description && e.description.trim() !== '';
                  const hasAmount = e.amount > 0;
                  return hasDescription && hasAmount;
            });

            if (othersCostType) {
                  validOtherEntries.forEach(e => {
                        entries.push({
                              cost_type_id: othersCostType.id,
                              amount: e.amount,
                              description: e.description.trim()
                        });
                  });
            } else if (validOtherEntries.length > 0) {
                  toastr.error('Others cost type not found. Please contact administrator.');
                  return;
            }

            if (entries.length === 0) {
                  toastr.error('Please add at least one cost entry with amount');
                  return;
            }

            // Validate amounts
            const invalidEntries = entries.filter(e => e.amount < 1);
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
                        branch_id: branchId,
                        entries: entries
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
      // EDIT COST MODAL (Admin Only - Amount Edit Only)
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

                              renderEditEntries(cost.entries);
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

      const renderEditEntries = function (entries) {
            const entriesList = document.getElementById('edit_entries_list');
            entriesList.innerHTML = '';

            entries.forEach(entry => {
                  const typeName = entry.cost_type?.name || 'Unknown';
                  const isOthers = typeName.toLowerCase() === 'others';
                  const displayName = isOthers && entry.description
                        ? `Others: ${escapeHtml(entry.description)}`
                        : escapeHtml(typeName);

                  const row = document.createElement('div');
                  row.className = 'edit-entry-row';
                  row.id = `edit_entry_row_${entry.id}`;
                  row.innerHTML = `
                <span class="entry-type-name">${displayName}</span>
                <div class="input-group input-group-solid entry-amount-input">
                    <span class="input-group-text">৳</span>
                    <input type="number" class="form-control form-control-solid edit-entry-amount" 
                           data-entry-id="${entry.id}" value="${entry.amount}" min="1" step="1"
                           oninput="KTCostRecords.updateEditTotal()">
                </div>`;
                  entriesList.appendChild(row);
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
                  if (amount < 1) hasError = true;
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
                  body: JSON.stringify({ entries })
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
      // EVENT LISTENERS
      // ============================================
      const initEvents = function () {
            elements.addCostBtn?.addEventListener('click', openAddCostModal);

            elements.refreshCostsBtn?.addEventListener('click', () => {
                  reloadCostsDataTable();
                  toastr.info('Cost records refreshed!');
            });

            elements.costForm?.addEventListener('submit', e => {
                  e.preventDefault();
                  saveCost();
            });

            elements.addOtherCostBtn?.addEventListener('click', () => {
                  addOtherCostRow();
            });

            if (config.isAdmin) {
                  $('#cost_branch_id').on('change', function () {
                        const branchId = $(this).val();
                        if (branchId) {
                              checkTodayCostExists(branchId, (exists) => {
                                    if (exists) {
                                          toastr.warning('Cost record already exists for today for this branch.');
                                          $(this).val(null).trigger('change.select2');
                                    }
                              });
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

KTUtil.onDOMContentLoaded(function () {
      KTCostRecords.init();
});
