"use strict";

/**
 * UCMS Cost Records Module
 * Cost Management with DataTable (Today Only Entry)
 * Metronic 8 + Bootstrap 5 + DataTables + Tagify
 */
var KTCostRecords = (function () {
      // ============================================
      // STATE & CONFIGURATION
      // ============================================
      let costsDataTable = null;
      let costTypesTagify = null;
      let costModal = null;
      let editCostModal = null;
      let deleteModal = null;
      let availableCostTypes = [];
      let selectedCostEntries = {};

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
            <span class="cost-type-badge">${costTypeName}</span>
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

            if (Object.keys(selectedCostEntries).length === 0) {
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

      const updateCostTotal = function () {
            let total = 0;
            Object.values(selectedCostEntries).forEach(entry => {
                  total += entry.amount || 0;
            });

            if (elements.costTotalAmount) {
                  elements.costTotalAmount.textContent = formatCurrency(total);
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
                        render: data => data
                              ? `<span class="badge badge-light-primary">${data.branch_name} (${data.branch_prefix})</span>`
                              : '-'
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

            costsDataTable = $(table).DataTable({
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
                  columns: columns,
                  order: [[0, 'desc']],
                  pageLength: 10,
                  language: {
                        emptyTable: "No cost records found"
                  },
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
            const branchId = config.isAdmin ?
                  null :
                  config.userBranchId;

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

            const entries = Object.values(selectedCostEntries).filter(e => e.amount > 0);
            if (entries.length === 0) {
                  toastr.error('Please add at least one cost entry with amount');
                  return;
            }

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
                        branch_id: branchId,
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
                                    oninput="KTCostRecords.updateEditTotal()">
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