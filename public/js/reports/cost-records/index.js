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
      let currentBranchId = null;
      let editingCostData = null;

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
                  elements.addEditEntryBtn = document.getElementById('add_edit_entry_btn');
                  elements.addEditOtherBtn = document.getElementById('add_edit_other_btn');
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
                       placeholder="Description (e.g., Office Supplies)" value="${escapeHtml(description)}"
                       data-row-id="${otherCostCounter}">
                <div class="input-group input-group-solid" style="width: 160px;">
                    <span class="input-group-text">৳</span>
                    <input type="number" class="form-control form-control-solid other-amount" 
                           min="1" step="1" placeholder="0" value="${amount}"
                           data-row-id="${otherCostCounter}">
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
                  updateCostTotal();
            });

            amountInput.addEventListener('input', function () {
                  updateOtherCostEntry(this.dataset.rowId);
                  updateCostTotal();
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
      const getDataTableColumns = function (showActions) {
            const columns = [
                  {
                        data: 'cost_date',
                        className: 'ps-4',
                        render: data => `<span class="fw-semibold text-gray-800">${formatDate(data)}</span>`
                  },
                  {
                        data: 'branch',
                        render: data => data
                              ? `<span class="badge badge-light-primary">${escapeHtml(data.branch_name)} (${escapeHtml(data.branch_prefix)})</span>`
                              : '-'
                  },
                  {
                        data: 'entries',
                        render: function (data) {
                              if (!data || data.length === 0) return '<span class="text-muted">No entries</span>';
                              return data.map(entry => {
                                    const typeName = entry.cost_type?.name || 'Unknown';
                                    const description = entry.description ? ` - ${escapeHtml(entry.description)}` : '';
                                    const displayName = typeName.toLowerCase() === 'others' && entry.description
                                          ? `Others: ${escapeHtml(entry.description)}`
                                          : escapeHtml(typeName);
                                    return `
                            <span class="entry-badge">
                                <span class="type-name">${displayName}</span>
                                <span class="type-amount">${formatCurrency(entry.amount)}</span>
                            </span>`;
                              }).join('');
                        }
                  },
                  {
                        data: 'total_amount',
                        className: 'text-end',
                        render: data => `<span class="fw-bold text-primary fs-5">${formatCurrency(data)}</span>`
                  },
                  {
                        data: 'created_by',
                        render: data => data ? `<span class="text-gray-600">${escapeHtml(data.name)}</span>` : '-'
                  }
            ];

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

      const initCostsDataTable = function (tableId, branchId = null) {
            const table = document.getElementById(tableId);
            if (!table) return null;

            const showActions = config.isAdmin;

            let ajaxUrl = config.routes.costs;
            if (branchId) {
                  ajaxUrl += '?branch_id=' + branchId;
            }

            const dt = $(table).DataTable({
                  processing: true,
                  serverSide: false,
                  ajax: {
                        url: ajaxUrl,
                        type: 'GET',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        dataSrc: function (json) {
                              return json.success ? json.data : [];
                        }
                  },
                  columns: getDataTableColumns(showActions),
                  order: [[0, 'desc']],
                  pageLength: 10,
                  language: {
                        emptyTable: "No cost records found"
                  },
                  drawCallback: () => KTMenu.init()
            });

            return dt;
      };

      const initAllDataTables = function () {
            if (config.hasMultipleBranches) {
                  // Initialize "All Branches" table
                  costsDataTables['all'] = initCostsDataTable('costs_datatable_all', null);

                  // Initialize per-branch tables
                  config.branches.forEach(branch => {
                        costsDataTables[branch.id] = initCostsDataTable(`costs_datatable_${branch.id}`, branch.id);
                  });
            } else {
                  // Single table for non-admin or single branch
                  costsDataTables['single'] = initCostsDataTable('costs_datatable', config.userBranchId);
            }
      };

      const reloadCurrentDataTable = function () {
            if (config.hasMultipleBranches) {
                  // Reload all tables
                  Object.values(costsDataTables).forEach(dt => {
                        if (dt) dt.ajax.reload(null, false);
                  });
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

            // Prepare regular entries
            const entries = Object.values(selectedCostEntries).filter(e => e.amount > 0).map(e => ({
                  cost_type_id: e.cost_type_id,
                  amount: e.amount
            }));

            // Prepare other entries - find "Others" cost type
            const othersCostType = availableCostTypes.find(ct => ct.name.toLowerCase() === 'others');
            const validOtherEntries = otherCostEntries.filter(e => e.description && e.amount > 0);

            if (othersCostType) {
                  validOtherEntries.forEach(e => {
                        entries.push({
                              cost_type_id: othersCostType.id,
                              amount: e.amount,
                              description: e.description
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
                              reloadCurrentDataTable();
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
                              editingCostData = cost;

                              document.getElementById('edit_cost_id').value = cost.id;
                              document.getElementById('edit_cost_date').textContent = formatDate(cost.cost_date);
                              document.getElementById('edit_cost_branch').textContent = cost.branch
                                    ? `${cost.branch.branch_name} (${cost.branch.branch_prefix})`
                                    : '-';

                              renderEditEntries(cost.entries);
                              populateEditCostTypeSelect(cost.entries);
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
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-light-danger" 
                        onclick="KTCostRecords.deleteEditEntry(${entry.id})" title="Delete Entry">
                    <i class="ki-outline ki-trash fs-6"></i>
                </button>`;
                  entriesList.appendChild(row);
            });
      };

      const populateEditCostTypeSelect = function (existingEntries) {
            const select = document.getElementById('edit_new_cost_type');
            if (!select) return;

            // Get IDs of existing non-"others" entries
            const existingTypeIds = existingEntries
                  .filter(e => e.cost_type?.name?.toLowerCase() !== 'others')
                  .map(e => e.cost_type_id);

            // Clear and repopulate
            select.innerHTML = '<option value="">-- Select Cost Type --</option>';

            availableCostTypes
                  .filter(ct => ct.name.toLowerCase() !== 'others')
                  .filter(ct => !existingTypeIds.includes(ct.id))
                  .forEach(ct => {
                        const option = document.createElement('option');
                        option.value = ct.id;
                        option.textContent = ct.name;
                        select.appendChild(option);
                  });

            // Reinitialize Select2
            $(select).trigger('change.select2');
      };

      const updateEditTotal = function () {
            let total = 0;
            document.querySelectorAll('#edit_entries_list .edit-entry-amount').forEach(input => {
                  total += parseInt(input.value) || 0;
            });
            const editTotal = document.getElementById('edit_cost_total');
            if (editTotal) editTotal.textContent = formatCurrency(total);
      };

      const addEditEntry = function () {
            const costTypeId = document.getElementById('edit_new_cost_type').value;
            const amount = parseInt(document.getElementById('edit_new_amount').value) || 0;
            const costId = document.getElementById('edit_cost_id').value;

            if (!costTypeId) {
                  toastr.error('Please select a cost type');
                  return;
            }
            if (amount < 1) {
                  toastr.error('Amount must be at least 1');
                  return;
            }

            const url = config.routes.addEntry.replace(':id', costId);

            fetch(url, {
                  method: 'POST',
                  headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                  },
                  body: JSON.stringify({
                        cost_type_id: costTypeId,
                        amount: amount
                  })
            })
                  .then(r => r.json())
                  .then(res => {
                        if (res.success) {
                              // Refresh the modal
                              openEditCostModal(costId);
                              document.getElementById('edit_new_amount').value = '';
                              toastr.success('Entry added successfully!');
                        } else {
                              toastr.error(res.message || 'Failed to add entry');
                        }
                  })
                  .catch(err => {
                        toastr.error('Failed to add entry');
                        console.error('Add entry error:', err);
                  });
      };

      const addEditOtherEntry = function () {
            const description = document.getElementById('edit_other_description').value.trim();
            const amount = parseInt(document.getElementById('edit_other_amount').value) || 0;
            const costId = document.getElementById('edit_cost_id').value;

            if (!description) {
                  toastr.error('Please enter a description');
                  return;
            }
            if (amount < 1) {
                  toastr.error('Amount must be at least 1');
                  return;
            }

            const othersCostType = availableCostTypes.find(ct => ct.name.toLowerCase() === 'others');
            if (!othersCostType) {
                  toastr.error('Others cost type not found');
                  return;
            }

            const url = config.routes.addEntry.replace(':id', costId);

            fetch(url, {
                  method: 'POST',
                  headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                  },
                  body: JSON.stringify({
                        cost_type_id: othersCostType.id,
                        amount: amount,
                        description: description
                  })
            })
                  .then(r => r.json())
                  .then(res => {
                        if (res.success) {
                              // Refresh the modal
                              openEditCostModal(costId);
                              document.getElementById('edit_other_description').value = '';
                              document.getElementById('edit_other_amount').value = '';
                              toastr.success('Other entry added successfully!');
                        } else {
                              toastr.error(res.message || 'Failed to add entry');
                        }
                  })
                  .catch(err => {
                        toastr.error('Failed to add entry');
                        console.error('Add other entry error:', err);
                  });
      };

      const deleteEditEntry = function (entryId) {
            if (!confirm('Are you sure you want to delete this entry?')) return;

            const url = config.routes.deleteEntry.replace(':id', entryId);

            fetch(url, {
                  method: 'DELETE',
                  headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                  }
            })
                  .then(r => r.json())
                  .then(res => {
                        if (res.success) {
                              const row = document.getElementById(`edit_entry_row_${entryId}`);
                              if (row) row.remove();
                              updateEditTotal();
                              reloadCurrentDataTable();
                              toastr.success('Entry deleted successfully!');

                              // Refresh cost type select
                              const costId = document.getElementById('edit_cost_id').value;
                              openEditCostModal(costId);
                        } else {
                              toastr.error(res.message || 'Failed to delete entry');
                        }
                  })
                  .catch(err => {
                        toastr.error('Failed to delete entry');
                        console.error('Delete entry error:', err);
                  });
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
                              reloadCurrentDataTable();
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
                              reloadCurrentDataTable();
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
                  reloadCurrentDataTable();
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

                  elements.addEditEntryBtn?.addEventListener('click', addEditEntry);
                  elements.addEditOtherBtn?.addEventListener('click', addEditOtherEntry);
            }

            // Tab change event - reload specific table when tab is shown
            if (config.hasMultipleBranches) {
                  document.querySelectorAll('#branch_tabs .nav-link').forEach(tab => {
                        tab.addEventListener('shown.bs.tab', function (e) {
                              const branchId = this.dataset.branchId;
                              currentBranchId = branchId || null;

                              // Adjust columns on tab switch
                              const tableKey = branchId || 'all';
                              if (costsDataTables[tableKey]) {
                                    costsDataTables[tableKey].columns.adjust();
                              }
                        });
                  });
            }
      };

      // ============================================
      // INIT
      // ============================================
      const init = function () {
            initElements();
            initModals();
            initAllDataTables();
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
            deleteEditEntry: deleteEditEntry,
            reloadCurrentDataTable: reloadCurrentDataTable
      };
})();

KTUtil.onDOMContentLoaded(function () {
      KTCostRecords.init();
});
