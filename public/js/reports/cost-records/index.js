"use strict";

/**
 * UCMS Cost Records Module
 * Cost Management with DataTable and Summary
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
    let summaryChart = null;
    let summaryData = null;
    let availableCostTypes = [];
    let selectedCostEntries = {};
    let otherCostEntries = [];
    let otherCostCounter = 0;
    let activeBranchId = null;
    let searchDebounceTimer = null;

    // Filter state
    let currentSearchValue = '';
    let currentDateRange = { start: null, end: null };
    let currentCostTypeIds = [];

    const config = window.CostRecordsConfig || {};
    const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // ============================================
    // ELEMENTS CACHE
    // ============================================
    const elements = {};

    const initElements = function () {
        // Card elements
        elements.searchInput = document.querySelector('[data-cost-table-filter="search"]');
        elements.filterBtn = document.getElementById('filter_btn');
        elements.filterMenu = document.getElementById('filter_menu');
        elements.exportBtn = document.getElementById('export_btn');
        elements.exportMenu = document.getElementById('export_menu');
        elements.filterApplyBtn = document.getElementById('filter_apply_btn');
        elements.filterResetBtn = document.getElementById('filter_reset_btn');
        elements.activeFiltersContainer = document.getElementById('active_filters_container');
        elements.clearAllFiltersBtn = document.getElementById('clear_all_filters_btn');

        // Filter inputs
        elements.filterDateRange = document.getElementById('filter_date_range');
        elements.filterCostTypes = document.getElementById('filter_cost_types');

        // Add cost modal
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
        elements.summaryBranchFilter = document.getElementById('summary_branch_select');
        elements.generateSummaryBtn = document.getElementById('generate_summary_btn');
        elements.summaryTableBody = document.getElementById('summary_table_body');
        elements.summaryChartContainer = document.getElementById('summary_chart');
        elements.noSummaryData = document.getElementById('no_summary_data');
        elements.summaryContent = document.getElementById('summary_content');

        // Summary stats
        elements.statTotalCost = document.getElementById('stat_total_cost');
        elements.statTotalEntries = document.getElementById('stat_total_entries');
        elements.statDailyAverage = document.getElementById('stat_daily_average');

        // Summary export buttons
        elements.exportSummaryExcel = document.getElementById('export_summary_excel');
        elements.exportSummaryPdf = document.getElementById('export_summary_pdf');
        elements.exportChartPng = document.getElementById('export_chart_png');

        // Admin elements
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
        const prefix = forPdf ? 'Tk ' : '৳';
        return prefix + parseInt(amount || 0).toLocaleString('en-BD');
    };

    const formatDate = function (dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
    };

    const getTimestamp = function () {
        const now = new Date();
        const date = now.toISOString().slice(0, 10);
        const time = now.toTimeString().slice(0, 5).replace(':', '');
        return `${date}_${time}`;
    };

    const getBranchNameForFile = function () {
        if (config.hasBranchTabs && activeBranchId) {
            const branch = config.branches.find(b => b.id == activeBranchId);
            if (branch) {
                return branch.name.replace(/\s+/g, '_');
            }
        }
        return 'All_Branches';
    };

    const getSummaryBranchName = function () {
        if (summaryData && summaryData.branch_name) {
            return summaryData.branch_name.replace(/\s+/g, '_');
        }
        return 'All_Branches';
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
    // MENU POSITIONING & HANDLING
    // ============================================
    const positionMenu = function (btn, menu) {
        if (!btn || !menu) return;

        const rect = btn.getBoundingClientRect();
        const menuWidth = menu.offsetWidth || 300;
        const menuHeight = menu.offsetHeight || 400;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        let left = rect.left;
        if (left + menuWidth > viewportWidth - 10) {
            left = rect.right - menuWidth;
        }
        if (left < 10) left = 10;

        let top = rect.bottom + 5;
        if (top + menuHeight > viewportHeight - 10) {
            top = rect.top - menuHeight - 5;
            if (top < 10) top = 10;
        }

        menu.style.position = 'fixed';
        menu.style.top = top + 'px';
        menu.style.left = left + 'px';
    };

    const closeAllMenus = function () {
        if (elements.filterMenu) elements.filterMenu.classList.remove('show');
        if (elements.exportMenu) elements.exportMenu.classList.remove('show');
    };

    const initMenuHandlers = function () {
        // Filter button
        if (elements.filterBtn && elements.filterMenu) {
            elements.filterBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (elements.exportMenu) elements.exportMenu.classList.remove('show');
                elements.filterMenu.classList.toggle('show');
                if (elements.filterMenu.classList.contains('show')) {
                    positionMenu(elements.filterBtn, elements.filterMenu);
                }
            });

            // Prevent menu close when clicking inside
            elements.filterMenu.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        }

        // Export button
        if (elements.exportBtn && elements.exportMenu) {
            elements.exportBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (elements.filterMenu) elements.filterMenu.classList.remove('show');
                elements.exportMenu.classList.toggle('show');
                if (elements.exportMenu.classList.contains('show')) {
                    positionMenu(elements.exportBtn, elements.exportMenu);
                }
            });
        }

        // Close menus on outside click
        document.addEventListener('click', function (e) {
            if (elements.filterMenu && elements.filterMenu.classList.contains('show')) {
                const isInsideFilterMenu = elements.filterMenu.contains(e.target);
                const isFilterBtn = elements.filterBtn && elements.filterBtn.contains(e.target);
                const isSelect2 = e.target.closest('.select2-container, .select2-dropdown, .select2-search, .select2-results');
                const isDaterangepicker = e.target.closest('.daterangepicker');

                if (!isInsideFilterMenu && !isFilterBtn && !isSelect2 && !isDaterangepicker) {
                    elements.filterMenu.classList.remove('show');
                }
            }

            if (elements.exportMenu && elements.exportMenu.classList.contains('show')) {
                const isInsideExportMenu = elements.exportMenu.contains(e.target);
                const isExportBtn = elements.exportBtn && elements.exportBtn.contains(e.target);

                if (!isInsideExportMenu && !isExportBtn) {
                    elements.exportMenu.classList.remove('show');
                }
            }
        });

        // Reposition menus on scroll
        window.addEventListener('scroll', function () {
            if (elements.filterMenu && elements.filterMenu.classList.contains('show')) {
                positionMenu(elements.filterBtn, elements.filterMenu);
            }
            if (elements.exportMenu && elements.exportMenu.classList.contains('show')) {
                positionMenu(elements.exportBtn, elements.exportMenu);
            }
        }, { passive: true });

        // Close menus on resize
        window.addEventListener('resize', function () {
            closeAllMenus();
        });
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
    // FILTER FUNCTIONS
    // ============================================
    const initDateRangePicker = function () {
        if (!elements.filterDateRange) return;

        $(elements.filterDateRange).daterangepicker({
            autoUpdateInput: false,
            locale: {
                format: 'DD-MM-YYYY',
                cancelLabel: 'Clear'
            },
            maxDate: moment(),
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment()],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            },
            opens: 'left',
            drops: 'down'
        });

        $(elements.filterDateRange).on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('DD-MM-YYYY') + ' - ' + picker.endDate.format('DD-MM-YYYY'));
        });

        $(elements.filterDateRange).on('cancel.daterangepicker', function () {
            $(this).val('');
        });
    };

    const initCostTypesSelect = function () {
        if (!elements.filterCostTypes) return;

        $(elements.filterCostTypes).select2({
            placeholder: 'Select cost types',
            allowClear: true,
            multiple: true,
            width: '100%',
            dropdownParent: $(elements.filterMenu)
        });
    };

    const applyFilters = function () {
        // Get date range
        const dateRangeVal = elements.filterDateRange ? elements.filterDateRange.value : '';
        if (dateRangeVal && dateRangeVal.includes(' - ')) {
            const parts = dateRangeVal.split(' - ');
            currentDateRange.start = parts[0].trim();
            currentDateRange.end = parts[1].trim();
        } else {
            currentDateRange.start = null;
            currentDateRange.end = null;
        }

        // Get cost types
        const costTypeVals = elements.filterCostTypes ? $(elements.filterCostTypes).val() : [];
        currentCostTypeIds = costTypeVals || [];

        // Update active filters display
        updateActiveFiltersDisplay();

        // Reload DataTable
        reloadCostsDataTable();

        // Close menu
        if (elements.filterMenu) elements.filterMenu.classList.remove('show');
    };

    const resetFilters = function () {
        // Clear date range
        if (elements.filterDateRange) {
            $(elements.filterDateRange).val('');
        }

        // Clear cost types
        if (elements.filterCostTypes) {
            $(elements.filterCostTypes).val(null).trigger('change');
        }

        // Reset state
        currentDateRange = { start: null, end: null };
        currentCostTypeIds = [];
        currentSearchValue = '';

        // Clear search input
        if (elements.searchInput) {
            elements.searchInput.value = '';
        }

        // Update display
        updateActiveFiltersDisplay();

        // Reload DataTable
        reloadCostsDataTable();

        // Close menu
        if (elements.filterMenu) elements.filterMenu.classList.remove('show');
    };

    const updateActiveFiltersDisplay = function () {
        if (!elements.activeFiltersContainer) return;

        const filters = [];

        if (currentDateRange.start && currentDateRange.end) {
            filters.push({
                type: 'date',
                label: `${currentDateRange.start} to ${currentDateRange.end}`,
                icon: 'ki-calendar'
            });
        }

        if (currentCostTypeIds.length > 0) {
            const names = [];
            currentCostTypeIds.forEach(id => {
                const ct = config.costTypes.find(c => c.id == id);
                if (ct) names.push(ct.name);
            });
            filters.push({
                type: 'costTypes',
                label: names.join(', '),
                icon: 'ki-category'
            });
        }

        if (filters.length === 0) {
            elements.activeFiltersContainer.classList.add('d-none');
            elements.activeFiltersContainer.innerHTML = '';
            return;
        }

        let html = '<div class="d-flex flex-wrap gap-2 align-items-center">';
        html += '<span class="text-muted fs-7 me-2">Active Filters:</span>';

        filters.forEach(f => {
            html += `
                <span class="badge badge-light-primary d-flex align-items-center gap-1 px-3 py-2">
                    <i class="ki-outline ${f.icon} fs-7"></i>
                    <span>${escapeHtml(f.label)}</span>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-1 p-0" 
                            data-filter-remove="${f.type}" style="width: 16px; height: 16px;">
                        <i class="ki-outline ki-cross fs-7"></i>
                    </button>
                </span>`;
        });

        html += '<a href="#" id="clear_all_filters_btn" class="text-primary fs-7 ms-2">Clear All</a>';
        html += '</div>';

        elements.activeFiltersContainer.innerHTML = html;
        elements.activeFiltersContainer.classList.remove('d-none');

        // Attach remove handlers
        elements.activeFiltersContainer.querySelectorAll('[data-filter-remove]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const type = this.dataset.filterRemove;
                removeFilter(type);
            });
        });

        // Attach clear all handler
        const clearAllBtn = document.getElementById('clear_all_filters_btn');
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', function (e) {
                e.preventDefault();
                resetFilters();
            });
        }
    };

    const removeFilter = function (type) {
        if (type === 'date') {
            currentDateRange = { start: null, end: null };
            if (elements.filterDateRange) $(elements.filterDateRange).val('');
        } else if (type === 'costTypes') {
            currentCostTypeIds = [];
            if (elements.filterCostTypes) $(elements.filterCostTypes).val(null).trigger('change');
        }

        updateActiveFiltersDisplay();
        reloadCostsDataTable();
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
            updateCostTotal();
        });

        amountInput.addEventListener('input', function () {
            updateOtherCostEntry(this.dataset.rowId);
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
    // COSTS DATATABLE
    // ============================================
    const getDataTableColumns = function (showActions) {
        const columns = [
            {
                data: null,
                className: 'ps-4 w-50px',
                orderable: false,
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'cost_date',
                className: 'w-100px',
                orderable: false,
                render: data => `<span class="fw-semibold text-gray-800">${formatDate(data)}</span>`
            },
            {
                data: 'entries',
                orderable: false,
                render: function (data) {
                    if (!data || data.length === 0) return '<span class="text-muted">No entries</span>';

                    return data.map(entry => {
                        const typeName = entry.cost_type?.name || 'Unknown';
                        const isOthers = typeName.toLowerCase() === 'others';
                        const displayName = isOthers && entry.description
                            ? `Others (${escapeHtml(entry.description)})`
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
                className: 'text-end w-120px',
                orderable: false,
                render: data => `<span class="fw-bold text-primary fs-5">${formatCurrency(data)}</span>`
            },
            {
                data: 'created_by',
                className: 'w-120px',
                orderable: false,
                render: data => data ? `<span class="text-gray-600">${escapeHtml(data.name)}</span>` : '-'
            }
        ];

        if (showActions) {
            columns.push({
                data: null,
                className: 'text-end pe-4 w-100px not-export',
                orderable: false,
                render: (data, type, row) => `
                    <div class="d-flex justify-content-end gap-1">
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

    const getAjaxParams = function (branchId) {
        const params = {
            branch_id: branchId || '',
            search_value: currentSearchValue,
            start_date: currentDateRange.start || '',
            end_date: currentDateRange.end || '',
            cost_type_ids: currentCostTypeIds.join(',')
        };
        return params;
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
            ordering: false,
            ajax: {
                url: config.routes.costsData,
                type: 'GET',
                data: function () {
                    return getAjaxParams(branchId);
                },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                dataSrc: function (json) {
                    updateBranchCount(branchId, json.data?.length || 0);
                    return json.success ? json.data : [];
                },
                error: function (xhr, error, thrown) {
                    console.error('DataTable error:', error);
                    toastr.error('Failed to load cost records');
                }
            },
            columns: getDataTableColumns(true),
            pageLength: 10,
            language: {
                emptyTable: "No cost records found for this branch"
            },
            drawCallback: () => KTMenu && KTMenu.init()
        });
    };

    const initSingleDataTable = function () {
        const table = document.getElementById('costs_datatable');
        if (!table) return;

        costsDataTables['single'] = $(table).DataTable({
            processing: true,
            serverSide: false,
            ordering: false,
            ajax: {
                url: config.routes.costsData,
                type: 'GET',
                data: function () {
                    return getAjaxParams(config.userBranchId);
                },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                dataSrc: function (json) {
                    return json.success ? json.data : [];
                },
                error: function (xhr, error, thrown) {
                    console.error('DataTable error:', error);
                    toastr.error('Failed to load cost records');
                }
            },
            columns: getDataTableColumns(config.isAdmin),
            pageLength: 10,
            language: {
                emptyTable: "No cost records found"
            },
            drawCallback: () => KTMenu && KTMenu.init()
        });
    };

    const updateBranchCount = function (branchId, count) {
        const badge = document.querySelector(`.branch-count-badge[data-branch-id="${branchId}"]`);
        if (badge) badge.textContent = count;
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

    // ============================================
    // SEARCH HANDLER
    // ============================================
    const initSearch = function () {
        if (!elements.searchInput) return;

        elements.searchInput.addEventListener('keyup', function () {
            clearTimeout(searchDebounceTimer);
            const value = this.value;

            searchDebounceTimer = setTimeout(function () {
                currentSearchValue = value;
                reloadCostsDataTable();
            }, 400);
        });
    };

    // ============================================
    // EXPORT FUNCTIONS
    // ============================================
    const fetchExportData = function () {
        return new Promise((resolve, reject) => {
            const params = new URLSearchParams(getAjaxParams(activeBranchId || config.userBranchId));

            fetch(`${config.routes.exportCosts}?${params.toString()}`, {
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
                .then(result => {
                    if (result.success) {
                        resolve({ data: result.data, meta: result.meta });
                    } else {
                        reject(new Error(result.message || 'Export failed'));
                    }
                })
                .catch(error => reject(error));
        });
    };

    const copyToClipboard = function (data, meta) {
        const headers = ['SL', 'Date', 'Cost Entries', 'Total (Tk)', 'Created By'];
        let text = `${meta.title}\n`;
        text += `Branch: ${meta.branch_name}\n`;
        text += `Date Range: ${meta.date_range}\n`;
        text += `Exported: ${meta.export_time}\n\n`;
        text += headers.join('\t') + '\n';

        data.forEach(row => {
            text += [
                row.sl,
                row.date,
                row.cost_entries,
                'Tk ' + parseInt(row.total_amount).toLocaleString(),
                row.created_by
            ].join('\t') + '\n';
        });

        text += `\nGrand Total: Tk ${parseInt(meta.grand_total).toLocaleString()}`;
        text += `\nTotal Records: ${meta.total_records}`;

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

    const exportToExcel = function (data, meta) {
        // Header rows
        const titleRow = [meta.title];
        const branchRow = ['Branch:', meta.branch_name];
        const dateRangeRow = ['Date Range:', meta.date_range];
        const exportTimeRow = ['Exported:', meta.export_time];
        const emptyRow = [''];
        const headers = ['SL', 'Date', 'Cost Entries', 'Total (Tk)', 'Created By'];

        const wsData = [titleRow, branchRow, dateRangeRow, exportTimeRow, emptyRow, headers];

        data.forEach(row => {
            wsData.push([
                row.sl,
                row.date,
                row.cost_entries,
                parseInt(row.total_amount),
                row.created_by
            ]);
        });

        // Add totals
        wsData.push(emptyRow);
        wsData.push(['Grand Total:', '', '', parseInt(meta.grand_total), '']);
        wsData.push(['Total Records:', '', '', meta.total_records, '']);

        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Cost Records');

        ws['!cols'] = [
            { wch: 5 },
            { wch: 12 },
            { wch: 60 },
            { wch: 15 },
            { wch: 15 }
        ];

        const branchName = meta.branch_name.replace(/\s+/g, '_');
        const fileName = `Cost_Records_${branchName}_${getTimestamp()}.xlsx`;
        XLSX.writeFile(wb, fileName);
        toastr.success('Excel file downloaded successfully!');
    };

    const exportToCSV = function (data, meta) {
        const titleRow = [meta.title];
        const branchRow = ['Branch:', meta.branch_name];
        const dateRangeRow = ['Date Range:', meta.date_range];
        const exportTimeRow = ['Exported:', meta.export_time];
        const emptyRow = [''];
        const headers = ['SL', 'Date', 'Cost Entries', 'Total (Tk)', 'Created By'];

        const wsData = [titleRow, branchRow, dateRangeRow, exportTimeRow, emptyRow, headers];

        data.forEach(row => {
            wsData.push([
                row.sl,
                row.date,
                row.cost_entries,
                parseInt(row.total_amount),
                row.created_by
            ]);
        });

        wsData.push(emptyRow);
        wsData.push(['Grand Total:', '', '', parseInt(meta.grand_total), '']);

        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const csv = XLSX.utils.sheet_to_csv(ws);

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const branchName = meta.branch_name.replace(/\s+/g, '_');
        link.href = URL.createObjectURL(blob);
        link.download = `Cost_Records_${branchName}_${getTimestamp()}.csv`;
        link.click();
        URL.revokeObjectURL(link.href);
        toastr.success('CSV file downloaded successfully!');
    };

    const exportToPDF = function (data, meta) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4');

        // Title
        doc.setFontSize(16);
        doc.setFont(undefined, 'bold');
        doc.text(meta.title, 14, 15);

        // Meta info
        doc.setFontSize(10);
        doc.setFont(undefined, 'normal');
        doc.text(`Branch: ${meta.branch_name}`, 14, 23);
        doc.text(`Date Range: ${meta.date_range}`, 14, 29);
        doc.text(`Exported: ${meta.export_time}`, 14, 35);

        const headers = [['SL', 'Date', 'Cost Entries', 'Total (Tk)', 'Created By']];

        const rows = data.map(row => [
            row.sl,
            row.date,
            row.cost_entries,
            'Tk ' + parseInt(row.total_amount).toLocaleString(),
            row.created_by
        ]);

        doc.autoTable({
            head: headers,
            body: rows,
            startY: 42,
            styles: { fontSize: 8, cellPadding: 2 },
            headStyles: { fillColor: [41, 128, 185], textColor: 255, fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            columnStyles: {
                0: { cellWidth: 12 },
                1: { cellWidth: 25 },
                2: { cellWidth: 140 },
                3: { cellWidth: 30 },
                4: { cellWidth: 35 }
            },
            didDrawPage: function () {
                doc.setFontSize(8);
                doc.text(
                    `Page ${doc.internal.getNumberOfPages()}`,
                    doc.internal.pageSize.width - 20,
                    doc.internal.pageSize.height - 10
                );
            }
        });

        // Add totals after table
        const finalY = doc.lastAutoTable.finalY + 10;
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.text(`Grand Total: Tk ${parseInt(meta.grand_total).toLocaleString()}`, 14, finalY);
        doc.text(`Total Records: ${meta.total_records}`, 14, finalY + 6);

        const branchName = meta.branch_name.replace(/\s+/g, '_');
        doc.save(`Cost_Records_${branchName}_${getTimestamp()}.pdf`);
        toastr.success('PDF file downloaded successfully!');
    };

    const initExportHandlers = function () {
        document.querySelectorAll('[data-export-type]').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const type = this.dataset.exportType;

                if (elements.exportMenu) elements.exportMenu.classList.remove('show');

                setButtonLoading(elements.exportBtn, true);

                fetchExportData()
                    .then(({ data, meta }) => {
                        setButtonLoading(elements.exportBtn, false);

                        if (data.length === 0) {
                            toastr.warning('No data to export');
                            return;
                        }

                        switch (type) {
                            case 'copy':
                                copyToClipboard(data, meta);
                                break;
                            case 'excel':
                                exportToExcel(data, meta);
                                break;
                            case 'csv':
                                exportToCSV(data, meta);
                                break;
                            case 'pdf':
                                exportToPDF(data, meta);
                                break;
                        }
                    })
                    .catch(err => {
                        setButtonLoading(elements.exportBtn, false);
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
                ? `Others (${escapeHtml(entry.description)})`
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
    // COST SUMMARY
    // ============================================
    const initSummaryDateRange = function () {
        if (!elements.summaryDateRange) return;

        const start = moment().startOf('month');
        const end = moment();

        $(elements.summaryDateRange).daterangepicker({
            startDate: start,
            endDate: end,
            maxDate: moment(),
            locale: { format: 'DD-MM-YYYY' },
            ranges: {
                'This Month': [moment().startOf('month'), moment()],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 3 Months': [moment().subtract(3, 'months').startOf('month'), moment()],
                'This Year': [moment().startOf('year'), moment()]
            }
        });

        elements.summaryDateRange.value = start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY');
    };

    const generateSummary = function () {
        const dateRangeVal = elements.summaryDateRange ? elements.summaryDateRange.value : '';
        if (!dateRangeVal || !dateRangeVal.includes(' - ')) {
            toastr.error('Please select a date range');
            return;
        }

        const parts = dateRangeVal.split(' - ');
        const startDate = parts[0].trim();
        const endDate = parts[1].trim();

        let branchId = null;
        if (elements.summaryBranchFilter) {
            const rawValue = $(elements.summaryBranchFilter).val();
            if (rawValue !== null && rawValue !== '' && rawValue !== 'null' && rawValue !== 'undefined' && rawValue !== '0') {
                branchId = parseInt(rawValue, 10);
                if (isNaN(branchId) || branchId <= 0) {
                    branchId = null;
                }
            }
        }

        const params = new URLSearchParams();
        params.append('start_date', startDate);
        params.append('end_date', endDate);
        if (branchId !== null && branchId !== '') {
            params.append('branch_id', branchId);
        }

        setButtonLoading(elements.generateSummaryBtn, true);

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

                if (res.success && res.data) {
                    summaryData = res.data;
                    const summaryItems = res.data.summary || [];

                    if (summaryItems.length === 0) {
                        showNoSummaryData();
                        return;
                    }

                    showSummaryContent();
                    renderSummaryStats(res.data);
                    renderSummaryTable(summaryItems);
                    renderSummaryChart(summaryItems);
                } else {
                    toastr.error(res.message || 'Failed to generate summary');
                    showNoSummaryData();
                }
            })
            .catch(err => {
                setButtonLoading(elements.generateSummaryBtn, false);
                console.error('Summary error:', err);
                toastr.error('Failed to generate summary');
                showNoSummaryData();
            });
    };

    const showNoSummaryData = function () {
        if (elements.noSummaryData) elements.noSummaryData.classList.remove('d-none');
        if (elements.summaryContent) elements.summaryContent.classList.add('d-none');
    };

    const showSummaryContent = function () {
        if (elements.noSummaryData) elements.noSummaryData.classList.add('d-none');
        if (elements.summaryContent) elements.summaryContent.classList.remove('d-none');
    };

    const renderSummaryStats = function (data) {
        if (elements.statTotalCost) {
            elements.statTotalCost.textContent = formatCurrency(data.total_cost || 0);
        }
        if (elements.statTotalEntries) {
            elements.statTotalEntries.textContent = (data.total_entries || 0).toLocaleString();
        }
        if (elements.statDailyAverage) {
            elements.statDailyAverage.textContent = formatCurrency(data.daily_average || 0);
        }
    };

    const renderSummaryTable = function (summary) {
        if (!elements.summaryTableBody) return;

        let grandTotal = 0;
        let totalEntries = 0;

        summary.forEach(item => {
            grandTotal += parseInt(item.total_amount) || 0;
            totalEntries += parseInt(item.entry_count) || 0;
        });

        let html = '';
        summary.forEach(item => {
            const percentage = grandTotal > 0 ? ((item.total_amount / grandTotal) * 100).toFixed(1) : 0;
            const description = item.cost_type_description || '';

            html += `
                <tr>
                    <td>
                        <div class="fw-semibold text-gray-800">${escapeHtml(item.cost_type_name)}</div>
                        ${description ? `<div class="text-muted fs-7">${escapeHtml(description)}</div>` : ''}
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-primary">${item.entry_count}</span>
                    </td>
                    <td class="text-end fw-semibold">${formatCurrency(item.total_amount)}</td>
                    <td class="text-end">
                        <span class="badge badge-light-info">${percentage}%</span>
                    </td>
                </tr>`;
        });

        // Total row
        html += `
            <tr class="fw-bold bg-light">
                <td>Total</td>
                <td class="text-center">${totalEntries}</td>
                <td class="text-end text-primary fs-5">${formatCurrency(grandTotal)}</td>
                <td class="text-end">100%</td>
            </tr>`;

        elements.summaryTableBody.innerHTML = html;
    };

    const renderSummaryChart = function (summary) {
        if (!elements.summaryChartContainer) return;

        const categories = summary.map(item => item.cost_type_name);
        const data = summary.map(item => parseInt(item.total_amount) || 0);

        const options = {
            series: [{
                name: 'Amount',
                data: data
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 4
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: categories,
                labels: {
                    rotate: -45,
                    style: { fontSize: '11px' }
                }
            },
            yaxis: {
                labels: {
                    formatter: val => 'Tk ' + parseInt(val).toLocaleString()
                }
            },
            fill: { opacity: 1 },
            colors: ['#009EF7'],
            tooltip: {
                y: {
                    formatter: val => 'Tk ' + parseInt(val).toLocaleString()
                }
            }
        };

        if (summaryChart) {
            summaryChart.destroy();
        }

        summaryChart = new ApexCharts(elements.summaryChartContainer, options);
        summaryChart.render();
    };

    // ============================================
    // SUMMARY EXPORT
    // ============================================
    const exportSummaryToExcel = function () {
        if (!summaryData || !summaryData.summary || summaryData.summary.length === 0) {
            toastr.warning('No summary data to export');
            return;
        }

        const titleRow = ['Cost Summary Report'];
        const branchRow = ['Branch:', summaryData.branch_name];
        const dateRangeRow = ['Date Range:', `${summaryData.date_range.start} to ${summaryData.date_range.end}`];
        const exportTimeRow = ['Exported:', new Date().toLocaleString()];
        const emptyRow = [''];
        const headers = ['Cost Type', 'Description', 'Entries', 'Total (Tk)', 'Percentage'];

        const wsData = [titleRow, branchRow, dateRangeRow, exportTimeRow, emptyRow, headers];

        let grandTotal = 0;
        summaryData.summary.forEach(item => {
            grandTotal += parseInt(item.total_amount) || 0;
        });

        summaryData.summary.forEach(item => {
            const percentage = grandTotal > 0 ? ((item.total_amount / grandTotal) * 100).toFixed(1) + '%' : '0%';
            wsData.push([
                item.cost_type_name,
                item.cost_type_description || '',
                item.entry_count,
                parseInt(item.total_amount),
                percentage
            ]);
        });

        wsData.push(emptyRow);
        wsData.push(['Total', '', summaryData.total_entries, grandTotal, '100%']);
        wsData.push(['Daily Average', '', '', summaryData.daily_average, '']);

        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Cost Summary');

        ws['!cols'] = [
            { wch: 20 },
            { wch: 40 },
            { wch: 10 },
            { wch: 15 },
            { wch: 12 }
        ];

        const branchName = getSummaryBranchName();
        const fileName = `Cost_Summary_${branchName}_${getTimestamp()}.xlsx`;
        XLSX.writeFile(wb, fileName);
        toastr.success('Excel file downloaded successfully!');
    };

    const exportSummaryToPDF = function () {
        if (!summaryData || !summaryData.summary || summaryData.summary.length === 0) {
            toastr.warning('No summary data to export');
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');

        doc.setFontSize(16);
        doc.setFont(undefined, 'bold');
        doc.text('Cost Summary Report', 14, 15);

        doc.setFontSize(10);
        doc.setFont(undefined, 'normal');
        doc.text(`Branch: ${summaryData.branch_name}`, 14, 25);
        doc.text(`Date Range: ${summaryData.date_range.start} to ${summaryData.date_range.end}`, 14, 31);
        doc.text(`Exported: ${new Date().toLocaleString()}`, 14, 37);

        let grandTotal = 0;
        summaryData.summary.forEach(item => {
            grandTotal += parseInt(item.total_amount) || 0;
        });

        const headers = [['Cost Type', 'Entries', 'Total (Tk)', 'Percentage']];
        const rows = summaryData.summary.map(item => {
            const percentage = grandTotal > 0 ? ((item.total_amount / grandTotal) * 100).toFixed(1) + '%' : '0%';
            return [
                item.cost_type_name,
                item.entry_count,
                'Tk ' + parseInt(item.total_amount).toLocaleString(),
                percentage
            ];
        });

        // Add total row
        rows.push(['Total', summaryData.total_entries, 'Tk ' + grandTotal.toLocaleString(), '100%']);

        doc.autoTable({
            head: headers,
            body: rows,
            startY: 45,
            styles: { fontSize: 9, cellPadding: 3 },
            headStyles: { fillColor: [41, 128, 185], textColor: 255, fontStyle: 'bold' },
            alternateRowStyles: { fillColor: [245, 245, 245] },
            columnStyles: {
                0: { cellWidth: 60 },
                1: { cellWidth: 25, halign: 'center' },
                2: { cellWidth: 40, halign: 'right' },
                3: { cellWidth: 30, halign: 'right' }
            }
        });

        const finalY = doc.lastAutoTable.finalY + 10;
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.text(`Daily Average: Tk ${parseInt(summaryData.daily_average).toLocaleString()}`, 14, finalY);

        const branchName = getSummaryBranchName();
        doc.save(`Cost_Summary_${branchName}_${getTimestamp()}.pdf`);
        toastr.success('PDF file downloaded successfully!');
    };

    const exportChartToPNG = function () {
        if (!summaryChart) {
            toastr.warning('No chart to export');
            return;
        }

        summaryChart.dataURI().then(({ imgURI }) => {
            const link = document.createElement('a');
            const branchName = getSummaryBranchName();
            link.href = imgURI;
            link.download = `Cost_Summary_Chart_${branchName}_${getTimestamp()}.png`;
            link.click();
            toastr.success('Chart image downloaded successfully!');
        });
    };

    // ============================================
    // EVENT LISTENERS
    // ============================================
    const initEvents = function () {
        // Menu handlers
        initMenuHandlers();

        // Search
        initSearch();

        // Filter apply/reset
        if (elements.filterApplyBtn) {
            elements.filterApplyBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                applyFilters();
            });
        }

        if (elements.filterResetBtn) {
            elements.filterResetBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                resetFilters();
            });
        }

        // Export handlers
        initExportHandlers();

        // Add cost button
        elements.addCostBtn?.addEventListener('click', openAddCostModal);

        // Cost form submit
        elements.costForm?.addEventListener('submit', e => {
            e.preventDefault();
            saveCost();
        });

        // Add other cost button
        elements.addOtherCostBtn?.addEventListener('click', () => {
            addOtherCostRow();
        });

        // Branch select change (admin)
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

        // Summary generation
        elements.generateSummaryBtn?.addEventListener('click', generateSummary);

        // Summary exports
        if (elements.exportSummaryExcel) {
            elements.exportSummaryExcel.addEventListener('click', function (e) {
                e.preventDefault();
                exportSummaryToExcel();
            });
        }

        if (elements.exportSummaryPdf) {
            elements.exportSummaryPdf.addEventListener('click', function (e) {
                e.preventDefault();
                exportSummaryToPDF();
            });
        }

        if (elements.exportChartPng) {
            elements.exportChartPng.addEventListener('click', function (e) {
                e.preventDefault();
                exportChartToPNG();
            });
        }
    };

    // ============================================
    // INIT
    // ============================================
    const init = function () {
        initElements();
        initModals();
        initDateRangePicker();
        initCostTypesSelect();
        initSummaryDateRange();
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
