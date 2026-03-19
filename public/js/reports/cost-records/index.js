"use strict";

/**
 * UCMS Cost Records Module
 * Cost Management with DataTable, Summary & Charts
 * Metronic 8 + Bootstrap 5 + DataTables + Tagify + ApexCharts
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
    let summaryChart = null;

    let availableCostTypes = [];
    let selectedCostEntries = {};
    let otherCostEntries = [];
    let otherCostCounter = 0;
    let activeBranchId = null;

    // Filter state
    let currentSearchValue = '';
    let currentDateRange = { start: null, end: null };
    let currentBranchFilter = '';
    let searchDebounceTimer = null;

    const config = window.CostRecordsConfig || {};
    const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // ============================================
    // ELEMENTS CACHE
    // ============================================
    const elements = {};

    const initElements = function () {
        elements.searchInput = document.querySelector('[data-cost-table-filter="search"]');
        elements.filterDateRange = document.getElementById('filter_date_range');
        elements.filterBranchSelect = document.getElementById('filter_branch_select');
        elements.addCostBtn = document.getElementById('add_cost_btn');
        elements.costForm = document.getElementById('cost_form');
        elements.saveCostBtn = document.getElementById('save_cost_btn');
        elements.costEntriesList = document.getElementById('cost_entries_list');
        elements.costTotalSection = document.getElementById('cost_total_section');
        elements.costTotalAmount = document.getElementById('cost_total_amount');
        elements.otherCostsContainer = document.getElementById('other_costs_container');
        elements.addOtherCostBtn = document.getElementById('add_other_cost_btn');

        // Summary elements
        elements.summaryDateRange = document.getElementById('summary_date_range');
        elements.summaryBranchSelect = document.getElementById('summary_branch_select');
        elements.generateSummaryBtn = document.getElementById('generate_summary_btn');
        elements.summaryContent = document.getElementById('summary_content');
        elements.summaryEmptyState = document.getElementById('summary_empty_state');
        elements.summaryTableBody = document.getElementById('summary_table_body');
        elements.summaryChart = document.getElementById('summary_chart');
        elements.exportSummaryExcelBtn = document.getElementById('export_summary_excel_btn');
        elements.exportSummaryPdfBtn = document.getElementById('export_summary_pdf_btn');

        // Toolbars
        elements.costRecordsToolbar = document.getElementById('cost_records_toolbar');
        elements.costSummaryToolbar = document.getElementById('cost_summary_toolbar');
        elements.searchWrapper = document.getElementById('search_wrapper');

        if (config.isAdmin) {
            elements.editCostForm = document.getElementById('edit_cost_form');
            elements.updateCostBtn = document.getElementById('update_cost_btn');
            elements.confirmDeleteBtn = document.getElementById('confirm_delete_cost_btn');
        }
    };

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================
    const formatCurrency = function (amount, forPdf = false) {
        const formattedAmount = parseInt(amount).toLocaleString('en-BD');
        return forPdf ? 'Tk ' + formattedAmount : '৳' + formattedAmount;
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
        if (!text) return '';
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
    // DATE RANGE PICKERS
    // ============================================
    const initDateRangePickers = function () {
        // Filter date range picker
        if (elements.filterDateRange) {
            $(elements.filterDateRange).daterangepicker({
                autoUpdateInput: false,
                maxDate: moment(),
                locale: {
                    format: 'DD-MM-YYYY',
                    cancelLabel: 'Clear'
                },
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment()],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            });

            $(elements.filterDateRange).on('apply.daterangepicker', function (ev, picker) {
                $(this).val(picker.startDate.format('DD-MM-YYYY') + ' - ' + picker.endDate.format('DD-MM-YYYY'));
            });

            $(elements.filterDateRange).on('cancel.daterangepicker', function () {
                $(this).val('');
            });
        }

        // Summary date range picker with current month pre-selected
        if (elements.summaryDateRange) {
            const startOfMonth = moment().startOf('month');
            const today = moment();

            $(elements.summaryDateRange).daterangepicker({
                startDate: startOfMonth,
                endDate: today,
                maxDate: moment(),
                locale: {
                    format: 'DD-MM-YYYY'
                },
                ranges: {
                    'This Month': [moment().startOf('month'), moment()],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Last 3 Months': [moment().subtract(3, 'months').startOf('month'), moment()],
                    'Last 6 Months': [moment().subtract(6, 'months').startOf('month'), moment()],
                    'This Year': [moment().startOf('year'), moment()]
                }
            });

            // Set initial value
            elements.summaryDateRange.value = startOfMonth.format('DD-MM-YYYY') + ' - ' + today.format('DD-MM-YYYY');
        }
    };

    // ============================================
    // COST TYPES LOADING
    // ============================================
    const loadCostTypes = function (callback) {
        // Use preloaded cost types if available
        if (config.costTypes && config.costTypes.length > 0) {
            availableCostTypes = config.costTypes;
            if (callback) callback();
            return;
        }

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

        // Filter out "Others" type from tagify - handled separately
        const whitelist = availableCostTypes
            .filter(ct => ct.name.toLowerCase() !== 'others')
            .map(ct => ({ value: ct.name, id: ct.id }));

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

        const descInput = row.querySelector('.other-description');
        const amountInput = row.querySelector('.other-amount');
        const removeBtn = row.querySelector('.remove-other-cost');

        descInput.addEventListener('input', function () {
            updateOtherCostEntry(this.dataset.rowId);
            validateOtherCostRow(this.dataset.rowId);
            updateCostTotal();
        });

        amountInput.addEventListener('input', function () {
            updateOtherCostEntry(this.dataset.rowId);
            validateOtherCostRow(this.dataset.rowId);
            updateCostTotal();
        });

        removeBtn.addEventListener('click', function () {
            removeOtherCostRow(this.dataset.rowId);
        });

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

        descInput.classList.remove('is-invalid');
        amountInput.classList.remove('is-invalid');

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

        Object.values(selectedCostEntries).forEach(entry => {
            total += entry.amount || 0;
        });

        otherCostEntries.forEach(entry => {
            total += entry.amount || 0;
        });

        if (elements.costTotalAmount) {
            elements.costTotalAmount.textContent = formatCurrency(total);
        }

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
    // SEARCH HANDLER
    // ============================================
    const handleSearch = function () {
        if (!elements.searchInput) return;

        elements.searchInput.addEventListener('keyup', function (e) {
            clearTimeout(searchDebounceTimer);
            currentSearchValue = e.target.value;

            searchDebounceTimer = setTimeout(function () {
                reloadActiveBranchTable();
            }, 400);
        });
    };

    // ============================================
    // FILTER HANDLER
    // ============================================
    const handleFilter = function () {
        const filterButton = document.querySelector('[data-cost-table-filter="filter"]');
        const resetButton = document.querySelector('[data-cost-table-filter="reset"]');

        if (filterButton) {
            filterButton.addEventListener('click', function () {
                // Get date range
                const dateRangeVal = elements.filterDateRange?.value || '';
                if (dateRangeVal) {
                    const parts = dateRangeVal.split(' - ');
                    if (parts.length === 2) {
                        currentDateRange.start = parts[0];
                        currentDateRange.end = parts[1];
                    }
                } else {
                    currentDateRange.start = null;
                    currentDateRange.end = null;
                }

                // Get branch filter
                currentBranchFilter = elements.filterBranchSelect?.value || '';

                reloadCostsDataTable();
            });
        }

        if (resetButton) {
            resetButton.addEventListener('click', function () {
                if (elements.filterDateRange) {
                    $(elements.filterDateRange).val('');
                }
                if (elements.filterBranchSelect) {
                    $(elements.filterBranchSelect).val(null).trigger('change');
                }

                currentDateRange = { start: null, end: null };
                currentBranchFilter = '';

                reloadCostsDataTable();
            });
        }
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

    const getAjaxData = function (branchId) {
        return function (d) {
            d.branch_id = branchId || currentBranchFilter || '';
            d.search_value = currentSearchValue;
            d.start_date = currentDateRange.start || '';
            d.end_date = currentDateRange.end || '';
        };
    };

    const initCostsDataTable = function () {
        if (config.hasBranchTabs) {
            config.branches.forEach((branch, index) => {
                initBranchDataTable(branch.id, index === 0);
            });

            if (config.branches.length > 0) {
                activeBranchId = config.branches[0].id;
            }

            document.querySelectorAll('#branch_tabs a[data-bs-toggle="tab"]').forEach(tab => {
                tab.addEventListener('shown.bs.tab', function () {
                    const branchId = this.dataset.branchId;
                    activeBranchId = branchId;

                    if (costsDataTables[branchId]) {
                        costsDataTables[branchId].ajax.reload(null, false);
                        costsDataTables[branchId].columns.adjust();
                    }
                });
            });
        } else {
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
                url: config.routes.costsData,
                type: 'GET',
                data: getAjaxData(branchId),
                headers: { 'X-CSRF-TOKEN': csrfToken },
                dataSrc: function (json) {
                    updateBranchCount(branchId, json.data?.length || 0);
                    return json.success ? json.data : [];
                }
            },
            columns: getDataTableColumns(false, true),
            order: [[0, 'desc']],
            pageLength: 10,
            language: {
                emptyTable: "No cost records found for this branch",
                processing: '<div class="d-flex justify-content-center"><span class="spinner-border spinner-border-sm" role="status"></span><span class="ms-2">Loading...</span></div>'
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
                url: config.routes.costsData,
                type: 'GET',
                data: getAjaxData(null),
                headers: { 'X-CSRF-TOKEN': csrfToken },
                dataSrc: function (json) {
                    return json.success ? json.data : [];
                }
            },
            columns: getDataTableColumns(showBranch, showActions),
            order: [[0, 'desc']],
            pageLength: 10,
            language: {
                emptyTable: "No cost records found",
                processing: '<div class="d-flex justify-content-center"><span class="spinner-border spinner-border-sm" role="status"></span><span class="ms-2">Loading...</span></div>'
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

    const reloadCostsDataTable = function () {
        if (config.hasBranchTabs) {
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
    // EXPORT HANDLERS
    // ============================================
    const fetchExportData = function () {
        return new Promise((resolve, reject) => {
            const params = new URLSearchParams({
                branch_id: activeBranchId || currentBranchFilter || '',
                search_value: currentSearchValue,
                start_date: currentDateRange.start || '',
                end_date: currentDateRange.end || ''
            });

            fetch(`${config.routes.costsExport}?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => resolve(data.data))
                .catch(error => reject(error));
        });
    };

    const copyToClipboard = function (data) {
        let headers = ['SL', 'Date', 'Branch', 'Cost Entries', 'Total Amount', 'Created By'];
        let text = headers.join('\t') + '\n';

        data.forEach((row, index) => {
            text += [
                index + 1,
                row.cost_date,
                row.branch_name,
                row.entries_text,
                row.total_amount,
                row.created_by
            ].join('\t') + '\n';
        });

        navigator.clipboard.writeText(text)
            .then(() => toastr.success('Data copied to clipboard!'))
            .catch(() => {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                toastr.success('Data copied to clipboard!');
            });
    };

    const exportToExcel = function (data) {
        const headers = ['SL', 'Date', 'Branch', 'Cost Entries', 'Total Amount (Tk)', 'Created By'];
        const wsData = [headers];

        data.forEach((row, index) => {
            wsData.push([
                index + 1,
                row.cost_date,
                row.branch_name,
                row.entries_text,
                row.total_amount_raw,
                row.created_by
            ]);
        });

        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Cost Records');

        ws['!cols'] = [
            { wch: 5 }, { wch: 12 }, { wch: 20 }, { wch: 50 }, { wch: 15 }, { wch: 20 }
        ];

        const fileName = `Cost_Records_${new Date().toISOString().slice(0, 10)}.xlsx`;
        XLSX.writeFile(wb, fileName);
        toastr.success('Excel file downloaded successfully!');
    };

    const exportToCSV = function (data) {
        const headers = ['SL', 'Date', 'Branch', 'Cost Entries', 'Total Amount (Tk)', 'Created By'];
        const wsData = [headers];

        data.forEach((row, index) => {
            wsData.push([
                index + 1,
                row.cost_date,
                row.branch_name,
                row.entries_text,
                row.total_amount_raw,
                row.created_by
            ]);
        });

        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const csv = XLSX.utils.sheet_to_csv(ws);

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const fileName = `Cost_Records_${new Date().toISOString().slice(0, 10)}.csv`;
        link.href = URL.createObjectURL(blob);
        link.download = fileName;
        link.click();
        URL.revokeObjectURL(link.href);
        toastr.success('CSV file downloaded successfully!');
    };

    const exportToPDF = function (data) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4');

        const headers = [['SL', 'Date', 'Branch', 'Cost Entries', 'Total (Tk)', 'Created By']];
        const rows = data.map((row, index) => [
            index + 1,
            row.cost_date,
            row.branch_name,
            row.entries_text,
            row.total_amount_raw,
            row.created_by
        ]);

        doc.setFontSize(16);
        doc.text('Cost Records Report', 14, 15);

        doc.setFontSize(10);
        doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 22);

        doc.autoTable({
            head: headers,
            body: rows,
            startY: 28,
            styles: { fontSize: 8, cellPadding: 2 },
            headStyles: { fillColor: [41, 128, 185], textColor: 255, fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            columnStyles: {
                0: { cellWidth: 10 },
                1: { cellWidth: 25 },
                2: { cellWidth: 35 },
                3: { cellWidth: 80 },
                4: { cellWidth: 25 },
                5: { cellWidth: 30 }
            },
            margin: { top: 28 }
        });

        const fileName = `Cost_Records_${new Date().toISOString().slice(0, 10)}.pdf`;
        doc.save(fileName);
        toastr.success('PDF file downloaded successfully!');
    };

    const handleExport = function () {
        document.querySelectorAll('[data-cost-export]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const exportType = this.dataset.costExport;

                fetchExportData()
                    .then(data => {
                        if (!data || data.length === 0) {
                            toastr.warning('No data to export');
                            return;
                        }

                        switch (exportType) {
                            case 'copy':
                                copyToClipboard(data);
                                break;
                            case 'excel':
                                exportToExcel(data);
                                break;
                            case 'csv':
                                exportToCSV(data);
                                break;
                            case 'pdf':
                                exportToPDF(data);
                                break;
                        }
                    })
                    .catch(err => {
                        console.error('Export error:', err);
                        toastr.error('Failed to export data');
                    });
            });
        });
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

        const othersCostType = availableCostTypes.find(ct => ct.name.toLowerCase() === 'others');

        const entries = Object.values(selectedCostEntries)
            .filter(e => e.amount > 0)
            .map(e => ({
                cost_type_id: e.cost_type_id,
                amount: e.amount
            }));

        if (!validateAllOtherCostRows()) {
            toastr.error('Please fill in both description and amount for all Other Costs entries');
            return;
        }

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
    // EDIT COST MODAL
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
    // DELETE MODAL
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
    // COST SUMMARY
    // ============================================
    const generateSummary = function () {
        const dateRangeVal = elements.summaryDateRange?.value || '';
        if (!dateRangeVal) {
            toastr.error('Please select a date range');
            return;
        }

        const parts = dateRangeVal.split(' - ');
        if (parts.length !== 2) {
            toastr.error('Invalid date range format');
            return;
        }

        const branchId = elements.summaryBranchSelect?.value || '';

        setButtonLoading(elements.generateSummaryBtn, true);

        const params = new URLSearchParams({
            start_date: parts[0],
            end_date: parts[1],
            branch_id: branchId
        });

        fetch(`${config.routes.costSummary}?${params.toString()}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
            .then(r => r.json())
            .then(res => {
                setButtonLoading(elements.generateSummaryBtn, false);

                if (res.success) {
                    displaySummary(res.data);
                } else {
                    toastr.error(res.message || 'Failed to generate summary');
                }
            })
            .catch(err => {
                setButtonLoading(elements.generateSummaryBtn, false);
                toastr.error('Failed to generate summary');
                console.error('Summary error:', err);
            });
    };

    const displaySummary = function (data) {
        elements.summaryEmptyState?.classList.add('d-none');
        elements.summaryContent?.classList.remove('d-none');

        // Update cards
        document.getElementById('summary_total_cost').textContent = formatCurrency(data.total_cost);
        document.getElementById('summary_total_days').textContent = data.total_days;
        document.getElementById('summary_daily_avg').textContent = formatCurrency(data.daily_average);

        // Update table
        elements.summaryTableBody.innerHTML = '';

        data.breakdown.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <span class="fw-semibold text-gray-800">${escapeHtml(item.name)}</span>
                </td>
                <td class="text-end">
                    <span class="fw-bold text-primary">${formatCurrency(item.amount)}</span>
                </td>
                <td class="text-end">
                    <span class="badge badge-light-success">${item.percentage}%</span>
                </td>`;
            elements.summaryTableBody.appendChild(row);
        });

        document.getElementById('summary_table_total').textContent = formatCurrency(data.total_cost);

        // Render chart
        renderSummaryChart(data.breakdown);
    };

    const renderSummaryChart = function (breakdown) {
        const categories = breakdown.map(item => item.name);
        const amounts = breakdown.map(item => item.amount);

        const options = {
            series: [{
                name: 'Amount',
                data: amounts
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: categories,
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '11px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Amount (Tk)'
                },
                labels: {
                    formatter: function (val) {
                        return 'Tk ' + val.toLocaleString('en-BD');
                    }
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return '৳' + val.toLocaleString('en-BD');
                    }
                }
            },
            colors: ['#009EF7']
        };

        if (summaryChart) {
            summaryChart.destroy();
        }

        summaryChart = new ApexCharts(elements.summaryChart, options);
        summaryChart.render();
    };

    // ============================================
    // SUMMARY EXPORT
    // ============================================
    const exportSummaryToExcel = function () {
        const tableBody = document.getElementById('summary_table_body');
        if (!tableBody || tableBody.children.length === 0) {
            toastr.warning('No summary data to export');
            return;
        }

        const headers = ['Cost Type', 'Amount (Tk)', 'Percentage'];
        const wsData = [headers];

        Array.from(tableBody.children).forEach(row => {
            const cells = row.querySelectorAll('td');
            wsData.push([
                cells[0]?.textContent?.trim() || '',
                cells[1]?.textContent?.trim().replace(/[৳,]/g, '') || '',
                cells[2]?.textContent?.trim() || ''
            ]);
        });

        // Add total row
        const totalEl = document.getElementById('summary_table_total');
        wsData.push(['Total', totalEl?.textContent?.trim().replace(/[৳,]/g, '') || '', '100%']);

        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Cost Summary');

        ws['!cols'] = [{ wch: 25 }, { wch: 15 }, { wch: 12 }];

        const fileName = `Cost_Summary_${new Date().toISOString().slice(0, 10)}.xlsx`;
        XLSX.writeFile(wb, fileName);
        toastr.success('Summary exported to Excel!');
    };

    const exportSummaryToPdf = function () {
        const tableBody = document.getElementById('summary_table_body');
        if (!tableBody || tableBody.children.length === 0) {
            toastr.warning('No summary data to export');
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');

        // Title
        doc.setFontSize(18);
        doc.text('Cost Summary Report', 14, 20);

        // Date range
        const dateRange = elements.summaryDateRange?.value || '';
        doc.setFontSize(10);
        doc.text(`Date Range: ${dateRange}`, 14, 28);
        doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 34);

        // Summary cards
        const totalCost = document.getElementById('summary_total_cost')?.textContent?.replace('৳', 'Tk ') || 'Tk 0';
        const totalDays = document.getElementById('summary_total_days')?.textContent || '0';
        const dailyAvg = document.getElementById('summary_daily_avg')?.textContent?.replace('৳', 'Tk ') || 'Tk 0';

        doc.setFontSize(11);
        doc.text(`Total Cost: ${totalCost}`, 14, 44);
        doc.text(`Total Days: ${totalDays}`, 80, 44);
        doc.text(`Daily Average: ${dailyAvg}`, 140, 44);

        // Table headers
        const headers = [['Cost Type', 'Amount (Tk)', 'Percentage']];
        const rows = [];

        Array.from(tableBody.children).forEach(row => {
            const cells = row.querySelectorAll('td');
            rows.push([
                cells[0]?.textContent?.trim() || '',
                cells[1]?.textContent?.trim().replace('৳', 'Tk ') || '',
                cells[2]?.textContent?.trim() || ''
            ]);
        });

        // Add total row
        const totalEl = document.getElementById('summary_table_total');
        rows.push(['Total', totalEl?.textContent?.trim().replace('৳', 'Tk ') || '', '100%']);

        doc.autoTable({
            head: headers,
            body: rows,
            startY: 52,
            styles: { fontSize: 10, cellPadding: 3 },
            headStyles: { fillColor: [0, 158, 247], textColor: 255, fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            footStyles: { fillColor: [230, 230, 230], fontStyle: 'bold' }
        });

        const fileName = `Cost_Summary_${new Date().toISOString().slice(0, 10)}.pdf`;
        doc.save(fileName);
        toastr.success('Summary exported to PDF!');
    };

    // ============================================
    // TAB SWITCHING
    // ============================================
    const handleTabSwitch = function () {
        const mainTabs = document.querySelectorAll('#main_tabs a[data-bs-toggle="tab"]');

        mainTabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (e) {
                const targetId = e.target.getAttribute('href');

                if (targetId === '#cost_records_pane') {
                    // Show records toolbar, hide summary toolbar
                    elements.costRecordsToolbar?.classList.remove('d-none');
                    elements.costSummaryToolbar?.classList.add('d-none');
                    elements.searchWrapper?.classList.remove('d-none');
                } else if (targetId === '#cost_summary_pane') {
                    // Hide records toolbar, show summary toolbar
                    elements.costRecordsToolbar?.classList.add('d-none');
                    elements.costSummaryToolbar?.classList.remove('d-none');
                    elements.searchWrapper?.classList.add('d-none');
                }
            });
        });
    };

    // ============================================
    // EVENT LISTENERS
    // ============================================
    const initEvents = function () {
        elements.addCostBtn?.addEventListener('click', openAddCostModal);

        elements.costForm?.addEventListener('submit', e => {
            e.preventDefault();
            saveCost();
        });

        elements.addOtherCostBtn?.addEventListener('click', () => {
            addOtherCostRow();
        });

        // Summary events
        elements.generateSummaryBtn?.addEventListener('click', generateSummary);
        elements.exportSummaryExcelBtn?.addEventListener('click', exportSummaryToExcel);
        elements.exportSummaryPdfBtn?.addEventListener('click', exportSummaryToPdf);

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
        initDateRangePickers();
        initCostsDataTable();
        handleSearch();
        handleFilter();
        handleExport();
        handleTabSwitch();
        initEvents();
        loadCostTypes();

        // Re-init KTMenu for dynamic elements
        KTMenu.init();
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
