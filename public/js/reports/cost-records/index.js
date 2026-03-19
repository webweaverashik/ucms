"use strict";

/**
 * UCMS Cost Records Module
 * Cost Management with DataTable
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
    let availableCostTypes = [];
    let selectedCostEntries = {};
    let otherCostEntries = [];
    let otherCostCounter = 0;
    let activeBranchId = null;
    let summaryData = null;

    // Filter states
    let currentSearchValue = '';
    let currentDateRange = { start: null, end: null };
    let currentCostTypeFilter = [];
    let searchDebounceTimer = null;

    const config = window.CostRecordsConfig || {};
    const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // ============================================
    // ELEMENTS CACHE
    // ============================================
    const elements = {};

    const initElements = function () {
        elements.searchInput = document.querySelector('[data-cost-table-filter="search"]');
        elements.filterBtn = document.getElementById('filter_btn');
        elements.filterMenu = document.getElementById('filter_menu');
        elements.filterApplyBtn = document.getElementById('filter_apply_btn');
        elements.filterResetBtn = document.getElementById('filter_reset_btn');
        elements.exportBtn = document.getElementById('export_btn');
        elements.exportMenu = document.getElementById('export_menu');
        elements.dateRangeFilter = document.getElementById('date_range_filter');
        elements.costTypeFilter = document.getElementById('cost_type_filter');
        elements.activeFiltersContainer = document.getElementById('active_filters_container');
        elements.clearAllFiltersBtn = document.getElementById('clear_all_filters_btn');

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
        elements.summaryContent = document.getElementById('summary_content');
        elements.summaryTableBody = document.getElementById('summary_table_body');
        elements.summaryChartContainer = document.getElementById('summary_chart');
        elements.exportSummaryExcelBtn = document.getElementById('export_summary_excel');
        elements.exportSummaryPdfBtn = document.getElementById('export_summary_pdf');
        elements.exportChartPngBtn = document.getElementById('export_chart_png');

        // Stat elements
        elements.statTotalCost = document.getElementById('stat_total_cost');
        elements.statTotalEntries = document.getElementById('stat_total_entries');
        elements.statDailyAverage = document.getElementById('stat_daily_average');

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
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const d = String(now.getDate()).padStart(2, '0');
        const h = String(now.getHours()).padStart(2, '0');
        const min = String(now.getMinutes()).padStart(2, '0');
        return `${y}-${m}-${d}_${h}${min}`;
    };

    const getBranchNameForFile = function () {
        if (config.hasBranchTabs && activeBranchId) {
            const branch = config.branches.find(b => b.id == activeBranchId);
            if (branch) return branch.name.replace(/\s+/g, '_');
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
    // MENU HANDLERS
    // ============================================
    const positionMenu = function (btn, menu) {
        if (!btn || !menu) return;

        const rect = btn.getBoundingClientRect();
        const menuWidth = 300;
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

            elements.exportMenu.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        }

        // Close menus on outside click
        document.addEventListener('click', function (e) {
            const isSelect2 = e.target.closest('.select2-container, .select2-dropdown, .select2-search, .select2-results');
            const isDaterangepicker = e.target.closest('.daterangepicker');

            if (isSelect2 || isDaterangepicker) return;

            if (elements.filterMenu && elements.filterMenu.classList.contains('show')) {
                if (!elements.filterMenu.contains(e.target) && !elements.filterBtn.contains(e.target)) {
                    elements.filterMenu.classList.remove('show');
                }
            }

            if (elements.exportMenu && elements.exportMenu.classList.contains('show')) {
                if (!elements.exportMenu.contains(e.target) && !elements.exportBtn.contains(e.target)) {
                    elements.exportMenu.classList.remove('show');
                }
            }
        });

        // Reposition on scroll
        window.addEventListener('scroll', function () {
            if (elements.filterMenu && elements.filterMenu.classList.contains('show')) {
                positionMenu(elements.filterBtn, elements.filterMenu);
            }
            if (elements.exportMenu && elements.exportMenu.classList.contains('show')) {
                positionMenu(elements.exportBtn, elements.exportMenu);
            }
        }, { passive: true });

        // Close on resize
        window.addEventListener('resize', closeAllMenus);
    };

    // ============================================
    // FILTER HANDLERS
    // ============================================
    const initDateRangeFilter = function () {
        if (!elements.dateRangeFilter) return;

        $(elements.dateRangeFilter).daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'DD-MM-YYYY'
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
            parentEl: '#filter_menu'
        });

        $(elements.dateRangeFilter).on('apply.daterangepicker', function (ev, picker) {
            $(this).val(picker.startDate.format('DD-MM-YYYY') + ' - ' + picker.endDate.format('DD-MM-YYYY'));
            currentDateRange = {
                start: picker.startDate.format('DD-MM-YYYY'),
                end: picker.endDate.format('DD-MM-YYYY')
            };
        });

        $(elements.dateRangeFilter).on('cancel.daterangepicker', function () {
            $(this).val('');
            currentDateRange = { start: null, end: null };
        });
    };

    const initCostTypeFilterSelect = function () {
        if (!elements.costTypeFilter) return;

        $(elements.costTypeFilter).select2({
            placeholder: 'Select cost types',
            allowClear: true,
            multiple: true,
            dropdownParent: $('#filter_menu'),
            width: '100%'
        });
    };

    const applyFilters = function () {
        // Get cost type filter values
        if (elements.costTypeFilter) {
            currentCostTypeFilter = $(elements.costTypeFilter).val() || [];
        }

        // Update active filters display
        updateActiveFiltersDisplay();

        // Reload DataTable
        reloadActiveBranchTable();
    };

    const resetFilters = function () {
        // Reset date range
        if (elements.dateRangeFilter) {
            $(elements.dateRangeFilter).val('');
        }
        currentDateRange = { start: null, end: null };

        // Reset cost type filter
        if (elements.costTypeFilter) {
            $(elements.costTypeFilter).val(null).trigger('change');
        }
        currentCostTypeFilter = [];

        // Update display
        updateActiveFiltersDisplay();

        // Reload DataTable
        reloadActiveBranchTable();
    };

    const updateActiveFiltersDisplay = function () {
        if (!elements.activeFiltersContainer) return;

        let badges = [];

        if (currentDateRange.start && currentDateRange.end) {
            badges.push(`<span class="badge badge-light-primary fs-7 me-2 mb-2">Date: ${currentDateRange.start} - ${currentDateRange.end} <a href="#" class="ms-1 text-primary clear-filter" data-filter="date">&times;</a></span>`);
        }

        if (currentCostTypeFilter.length > 0) {
            const typeNames = currentCostTypeFilter.map(id => {
                const ct = config.costTypes.find(c => c.id == id);
                return ct ? ct.name : id;
            }).join(', ');
            badges.push(`<span class="badge badge-light-info fs-7 me-2 mb-2">Types: ${escapeHtml(typeNames)} <a href="#" class="ms-1 text-info clear-filter" data-filter="costtype">&times;</a></span>`);
        }

        if (badges.length > 0) {
            elements.activeFiltersContainer.innerHTML = `
                <div class="d-flex flex-wrap align-items-center">
                    ${badges.join('')}
                    <a href="#" id="clear_all_filters_btn" class="text-danger fs-7 mb-2">Clear All</a>
                </div>`;
            elements.activeFiltersContainer.classList.remove('d-none');

            // Re-attach clear all handler
            const clearAllBtn = document.getElementById('clear_all_filters_btn');
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    resetFilters();
                });
            }

            // Attach individual clear handlers
            document.querySelectorAll('.clear-filter').forEach(function (el) {
                el.addEventListener('click', function (e) {
                    e.preventDefault();
                    const filterType = this.dataset.filter;
                    if (filterType === 'date') {
                        if (elements.dateRangeFilter) $(elements.dateRangeFilter).val('');
                        currentDateRange = { start: null, end: null };
                    } else if (filterType === 'costtype') {
                        if (elements.costTypeFilter) $(elements.costTypeFilter).val(null).trigger('change');
                        currentCostTypeFilter = [];
                    }
                    updateActiveFiltersDisplay();
                    reloadActiveBranchTable();
                });
            });
        } else {
            elements.activeFiltersContainer.innerHTML = '';
            elements.activeFiltersContainer.classList.add('d-none');
        }
    };

    const initFilterEvents = function () {
        // Apply button
        if (elements.filterApplyBtn) {
            elements.filterApplyBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                applyFilters();
                if (elements.filterMenu) elements.filterMenu.classList.remove('show');
            });
        }

        // Reset button
        if (elements.filterResetBtn) {
            elements.filterResetBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                resetFilters();
                if (elements.filterMenu) elements.filterMenu.classList.remove('show');
            });
        }
    };

    // ============================================
    // SEARCH HANDLER
    // ============================================
    const initSearch = function () {
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
        if (!elements.costEntriesList) return;
        elements.costEntriesList.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="ki-outline ki-information fs-3x text-gray-400 mb-3"></i>
                <p class="mb-0">Select cost types above to add entries</p>
            </div>`;
        if (elements.costTotalSection) elements.costTotalSection.classList.add('d-none');
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
        if (elements.costTotalSection) elements.costTotalSection.classList.remove('d-none');

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
                    placeholder="Description (required)" value="${escapeHtml(description)}" data-row-id="${otherCostCounter}" required>
                <div class="input-group input-group-solid" style="width: 160px;">
                    <span class="input-group-text">৳</span>
                    <input type="number" class="form-control form-control-solid other-amount"
                        min="1" step="1" placeholder="0" value="${amount}" data-row-id="${otherCostCounter}" required>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-light-danger remove-other-cost" data-row-id="${otherCostCounter}">
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

        if (elements.costTotalSection) elements.costTotalSection.classList.remove('d-none');
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
            if (elements.costTotalSection) elements.costTotalSection.classList.remove('d-none');
        } else {
            if (elements.costTotalSection) elements.costTotalSection.classList.add('d-none');
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
                data: null,
                className: 'ps-4',
                orderable: false,
                render: function (data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'cost_date',
                orderable: false,
                render: data => `<span class="fw-semibold text-gray-800">${formatDate(data)}</span>`
            }
        ];

        if (showBranchColumn) {
            columns.push({
                data: 'branch',
                orderable: false,
                render: data => data
                    ? `<span class="badge badge-light-primary">${escapeHtml(data.branch_name)}</span>`
                    : '-'
            });
        }

        columns.push({
            data: 'entries',
            orderable: false,
            render: function (data) {
                if (!data || data.length === 0) return '<span class="text-muted">No entries</span>';

                return data.map(entry => {
                    const typeName = entry.cost_type?.name || 'Unknown';
                    const isOthers = typeName.toLowerCase() === 'others';
                    const displayName = isOthers && entry.description
                        ? `Others: ${escapeHtml(entry.description)}`
                        : escapeHtml(typeName);

                    return `<span class="entry-badge">
                        <span class="type-name">${displayName}</span>
                        <span class="type-amount">${formatCurrency(entry.amount)}</span>
                    </span>`;
                }).join('');
            }
        });

        columns.push({
            data: 'total_amount',
            className: 'text-end',
            orderable: false,
            render: data => `<span class="fw-bold text-primary fs-5">${formatCurrency(data)}</span>`
        });

        columns.push({
            data: 'created_by',
            orderable: false,
            render: data => data ? `<span class="text-gray-600">${escapeHtml(data.name)}</span>` : '-'
        });

        if (showActions) {
            columns.push({
                data: null,
                className: 'text-end pe-4',
                orderable: false,
                render: (data, type, row) => `
                    <div class="d-flex justify-content-end gap-1">
                        <button type="button" class="btn btn-icon btn-sm btn-light-primary" onclick="KTCostRecords.openEditCostModal(${row.id})" title="Edit">
                            <i class="ki-outline ki-pencil fs-6"></i>
                        </button>
                        <button type="button" class="btn btn-icon btn-sm btn-light-danger" onclick="KTCostRecords.openDeleteModal(${row.id})" title="Delete">
                            <i class="ki-outline ki-trash fs-6"></i>
                        </button>
                    </div>`
            });
        }

        return columns;
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
                data: function (d) {
                    d.branch_id = branchId;
                    d.search_value = currentSearchValue;
                    if (currentDateRange.start && currentDateRange.end) {
                        d.start_date = currentDateRange.start;
                        d.end_date = currentDateRange.end;
                    }
                    if (currentCostTypeFilter.length > 0) {
                        d.cost_type_ids = currentCostTypeFilter.join(',');
                    }
                },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                dataSrc: function (json) {
                    updateBranchCount(branchId, json.data?.length || 0);
                    return json.success ? json.data : [];
                },
                error: function (xhr, error, thrown) {
                    console.error('DataTable AJAX Error:', error, thrown);
                    toastr.error('Failed to load cost records');
                }
            },
            columns: getDataTableColumns(false, config.isAdmin),
            pageLength: 10,
            language: {
                emptyTable: "No cost records found",
                processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
            },
            drawCallback: function () {
                KTMenu.init();
            }
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
                data: function (d) {
                    d.search_value = currentSearchValue;
                    if (currentDateRange.start && currentDateRange.end) {
                        d.start_date = currentDateRange.start;
                        d.end_date = currentDateRange.end;
                    }
                    if (currentCostTypeFilter.length > 0) {
                        d.cost_type_ids = currentCostTypeFilter.join(',');
                    }
                },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                dataSrc: function (json) {
                    return json.success ? json.data : [];
                },
                error: function (xhr, error, thrown) {
                    console.error('DataTable AJAX Error:', error, thrown);
                    toastr.error('Failed to load cost records');
                }
            },
            columns: getDataTableColumns(config.isAdmin, config.isAdmin),
            pageLength: 10,
            language: {
                emptyTable: "No cost records found"
            },
            drawCallback: function () {
                KTMenu.init();
            }
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
    const initExportHandlers = function () {
        document.querySelectorAll('[data-export-type]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                const exportType = this.dataset.exportType;
                handleExport(exportType);
                if (elements.exportMenu) elements.exportMenu.classList.remove('show');
            });
        });
    };

    const handleExport = function (type) {
        setButtonLoading(elements.exportBtn, true);

        const params = new URLSearchParams();
        if (config.hasBranchTabs && activeBranchId) {
            params.append('branch_id', activeBranchId);
        }
        params.append('search_value', currentSearchValue);
        if (currentDateRange.start && currentDateRange.end) {
            params.append('start_date', currentDateRange.start);
            params.append('end_date', currentDateRange.end);
        }
        if (currentCostTypeFilter.length > 0) {
            params.append('cost_type_ids', currentCostTypeFilter.join(','));
        }

        fetch(`${config.routes.exportCosts}?${params.toString()}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
            .then(r => r.json())
            .then(res => {
                setButtonLoading(elements.exportBtn, false);
                if (res.success && res.data) {
                    const branchName = res.branch_name || getBranchNameForFile();
                    switch (type) {
                        case 'copy':
                            copyToClipboard(res.data, branchName);
                            break;
                        case 'excel':
                            exportToExcel(res.data, branchName);
                            break;
                        case 'csv':
                            exportToCSV(res.data, branchName);
                            break;
                        case 'pdf':
                            exportToPDF(res.data, branchName);
                            break;
                    }
                } else {
                    toastr.error(res.message || 'Failed to export data');
                }
            })
            .catch(err => {
                setButtonLoading(elements.exportBtn, false);
                console.error('Export error:', err);
                toastr.error('Failed to export data');
            });
    };

    const copyToClipboard = function (data, branchName) {
        const headers = ['SL', 'Date', 'Cost Entries', 'Total (Tk)', 'Created By'];
        let text = headers.join('\t') + '\n';

        data.forEach((row, index) => {
            const entries = row.entries ? row.entries.map(e => {
                const name = e.cost_type?.name || 'Unknown';
                return `${name}: ${e.amount}`;
            }).join(', ') : '';

            text += [
                index + 1,
                formatDate(row.cost_date),
                entries,
                row.total_amount,
                row.created_by?.name || ''
            ].join('\t') + '\n';
        });

        navigator.clipboard.writeText(text)
            .then(() => toastr.success('Data copied to clipboard!'))
            .catch(() => toastr.error('Failed to copy data'));
    };

    const exportToExcel = function (data, branchName) {
        const headers = ['SL', 'Date', 'Cost Entries', 'Total (Tk)', 'Created By'];
        const wsData = [headers];

        data.forEach((row, index) => {
            const entries = row.entries ? row.entries.map(e => {
                const name = e.cost_type?.name || 'Unknown';
                return `${name}: ${e.amount}`;
            }).join(', ') : '';

            wsData.push([
                index + 1,
                formatDate(row.cost_date),
                entries,
                row.total_amount,
                row.created_by?.name || ''
            ]);
        });

        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Cost Records');

        ws['!cols'] = [
            { wch: 5 },
            { wch: 12 },
            { wch: 50 },
            { wch: 15 },
            { wch: 20 }
        ];

        const fileName = `Cost_Records_${branchName}_${getTimestamp()}.xlsx`;
        XLSX.writeFile(wb, fileName);
        toastr.success('Excel file downloaded!');
    };

    const exportToCSV = function (data, branchName) {
        const headers = ['SL', 'Date', 'Cost Entries', 'Total (Tk)', 'Created By'];
        const wsData = [headers];

        data.forEach((row, index) => {
            const entries = row.entries ? row.entries.map(e => {
                const name = e.cost_type?.name || 'Unknown';
                return `${name}: ${e.amount}`;
            }).join(', ') : '';

            wsData.push([
                index + 1,
                formatDate(row.cost_date),
                entries,
                row.total_amount,
                row.created_by?.name || ''
            ]);
        });

        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const csv = XLSX.utils.sheet_to_csv(ws);
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const fileName = `Cost_Records_${branchName}_${getTimestamp()}.csv`;
        link.href = URL.createObjectURL(blob);
        link.download = fileName;
        link.click();
        URL.revokeObjectURL(link.href);
        toastr.success('CSV file downloaded!');
    };

    const exportToPDF = function (data, branchName) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4');

        const headers = [['SL', 'Date', 'Cost Entries', 'Total (Tk)', 'Created By']];
        const rows = data.map((row, index) => {
            const entries = row.entries ? row.entries.map(e => {
                const name = e.cost_type?.name || 'Unknown';
                return `${name}: Tk ${e.amount}`;
            }).join('\n') : '';

            return [
                index + 1,
                formatDate(row.cost_date),
                entries,
                'Tk ' + row.total_amount,
                row.created_by?.name || ''
            ];
        });

        doc.setFontSize(16);
        doc.text('Cost Records Report', 14, 15);
        doc.setFontSize(10);
        doc.text(`Branch: ${branchName.replace(/_/g, ' ')}`, 14, 22);
        doc.text(`Generated: ${new Date().toLocaleString()}`, 14, 28);

        doc.autoTable({
            head: headers,
            body: rows,
            startY: 35,
            styles: { fontSize: 8, cellPadding: 2 },
            headStyles: { fillColor: [41, 128, 185], textColor: 255, fontStyle: 'bold' },
            columnStyles: {
                0: { cellWidth: 10 },
                1: { cellWidth: 25 },
                2: { cellWidth: 120 },
                3: { cellWidth: 25 },
                4: { cellWidth: 30 }
            }
        });

        const fileName = `Cost_Records_${branchName}_${getTimestamp()}.pdf`;
        doc.save(fileName);
        toastr.success('PDF file downloaded!');
    };

    // ============================================
    // ADD COST MODAL
    // ============================================
    const openAddCostModal = function () {
        const branchId = config.isAdmin ? null : config.userBranchId;

        if (!config.isAdmin && branchId) {
            checkTodayCostExists(branchId, (exists) => {
                if (exists) {
                    toastr.warning('Cost record already exists for today.');
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
            return e.description && e.description.trim() !== '' && e.amount > 0;
        });

        if (othersCostType) {
            validOtherEntries.forEach(e => {
                entries.push({
                    cost_type_id: othersCostType.id,
                    amount: e.amount,
                    description: e.description.trim()
                });
            });
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
            locale: {
                format: 'DD-MM-YYYY'
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

        elements.summaryDateRange.value = start.format('DD-MM-YYYY') + ' - ' + end.format('DD-MM-YYYY');
    };

    const generateSummary = function () {
        if (!elements.summaryDateRange) {
            toastr.error('Date range is required');
            return;
        }

        const dateRangeVal = elements.summaryDateRange.value;
        if (!dateRangeVal || !dateRangeVal.includes(' - ')) {
            toastr.error('Please select a valid date range');
            return;
        }

        const [startDate, endDate] = dateRangeVal.split(' - ');

        // Get branch_id value
        let branchId = null;
        if (elements.summaryBranchFilter) {
            const rawValue = $(elements.summaryBranchFilter).val();
            console.log('=== BRANCH DEBUG ===');
            console.log('Raw branch value:', rawValue);
            console.log('Type of rawValue:', typeof rawValue);
            
            // Only set branchId if it's a valid non-empty value
            if (rawValue !== null && rawValue !== '' && rawValue !== 'null' && rawValue !== 'undefined' && rawValue !== '0') {
                branchId = parseInt(rawValue, 10);
                if (isNaN(branchId) || branchId <= 0) {
                    branchId = null;
                }
            }
            console.log('Parsed branchId:', branchId);
        }

        // Build URL params
        const params = new URLSearchParams();
        params.append('start_date', startDate);
        params.append('end_date', endDate);
        
        // Only append branch_id if it has a valid value
        if (branchId !== null && branchId > 0) {
            params.append('branch_id', branchId.toString());
        }

        const fullUrl = `${config.routes.costSummary}?${params.toString()}`;
        console.log('Request URL:', fullUrl);
        console.log('Params string:', params.toString());

        setButtonLoading(elements.generateSummaryBtn, true);

        fetch(fullUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
            .then(r => {
                console.log('Response status:', r.status);
                return r.json();
            })
            .then(res => {
                console.log('Response data:', res);
                setButtonLoading(elements.generateSummaryBtn, false);

                if (res.success && res.data) {
                    summaryData = res.data;
                    renderSummaryStats(res.data);
                    renderSummaryTable(res.data.summary || []);
                    renderSummaryChart(res.data.summary || []);

                    if (elements.summaryContent) {
                        elements.summaryContent.classList.remove('d-none');
                    }
                } else {
                    toastr.error(res.message || 'Failed to generate summary');
                }
            })
            .catch(err => {
                setButtonLoading(elements.generateSummaryBtn, false);
                console.error('Summary error:', err);
                toastr.error('Failed to generate summary');
            });
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

        if (!summary || summary.length === 0) {
            elements.summaryTableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted py-5">No data found for the selected period</td>
                </tr>`;
            return;
        }

        // Calculate totals from summary items
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
                        <span class="fw-semibold text-gray-800">${escapeHtml(item.cost_type_name)}</span>
                        ${description ? `<br><small class="text-muted">${escapeHtml(description)}</small>` : ''}
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-primary">${item.entry_count}</span>
                    </td>
                    <td class="text-end fw-bold">${formatCurrency(item.total_amount)}</td>
                    <td class="text-end">
                        <span class="badge badge-light-info">${percentage}%</span>
                    </td>
                </tr>`;
        });

        // Add total row
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

        if (summaryChart) {
            summaryChart.destroy();
            summaryChart = null;
        }

        if (!summary || summary.length === 0) {
            elements.summaryChartContainer.innerHTML = '<div class="text-center text-muted py-10">No data to display</div>';
            return;
        }

        const categories = summary.map(item => item.cost_type_name);
        const amounts = summary.map(item => parseInt(item.total_amount) || 0);

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
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return 'Tk ' + val.toLocaleString();
                    }
                }
            },
            fill: {
                opacity: 1,
                colors: ['#009EF7']
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return 'Tk ' + val.toLocaleString();
                    }
                }
            }
        };

        summaryChart = new ApexCharts(elements.summaryChartContainer, options);
        summaryChart.render();
    };

    const exportChartPng = function () {
        if (!summaryChart) {
            toastr.warning('Please generate summary first');
            return;
        }

        summaryChart.dataURI().then(({ imgURI }) => {
            const link = document.createElement('a');
            link.href = imgURI;
            link.download = `Cost_Summary_Chart_${getSummaryBranchName()}_${getTimestamp()}.png`;
            link.click();
            toastr.success('Chart exported as PNG!');
        });
    };

    const exportSummaryExcel = function () {
        if (!summaryData || !summaryData.summary || summaryData.summary.length === 0) {
            toastr.warning('Please generate summary first');
            return;
        }

        const headers = ['Cost Type', 'Description', 'Entries', 'Amount (Tk)', 'Percentage'];
        const wsData = [headers];

        let grandTotal = 0;
        summaryData.summary.forEach(item => {
            grandTotal += parseInt(item.total_amount) || 0;
        });

        summaryData.summary.forEach(item => {
            const percentage = grandTotal > 0 ? ((item.total_amount / grandTotal) * 100).toFixed(1) : 0;
            wsData.push([
                item.cost_type_name,
                item.cost_type_description || '',
                item.entry_count,
                item.total_amount,
                percentage + '%'
            ]);
        });

        // Add total row
        wsData.push(['Total', '', summaryData.total_entries, summaryData.total_cost, '100%']);

        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Cost Summary');

        ws['!cols'] = [
            { wch: 20 },
            { wch: 30 },
            { wch: 10 },
            { wch: 15 },
            { wch: 12 }
        ];

        const fileName = `Cost_Summary_${getSummaryBranchName()}_${getTimestamp()}.xlsx`;
        XLSX.writeFile(wb, fileName);
        toastr.success('Excel file downloaded!');
    };

    const exportSummaryPdf = function () {
        if (!summaryData || !summaryData.summary || summaryData.summary.length === 0) {
            toastr.warning('Please generate summary first');
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');

        let grandTotal = 0;
        summaryData.summary.forEach(item => {
            grandTotal += parseInt(item.total_amount) || 0;
        });

        const headers = [['Cost Type', 'Entries', 'Amount (Tk)', 'Percentage']];
        const rows = summaryData.summary.map(item => {
            const percentage = grandTotal > 0 ? ((item.total_amount / grandTotal) * 100).toFixed(1) : 0;
            return [
                item.cost_type_name,
                item.entry_count,
                'Tk ' + parseInt(item.total_amount).toLocaleString(),
                percentage + '%'
            ];
        });

        // Add total row
        rows.push(['Total', summaryData.total_entries, 'Tk ' + parseInt(summaryData.total_cost).toLocaleString(), '100%']);

        doc.setFontSize(16);
        doc.text('Cost Summary Report', 14, 15);
        doc.setFontSize(10);
        doc.text(`Branch: ${getSummaryBranchName().replace(/_/g, ' ')}`, 14, 22);
        doc.text(`Period: ${summaryData.date_range?.start || ''} to ${summaryData.date_range?.end || ''}`, 14, 28);
        doc.text(`Generated: ${new Date().toLocaleString()}`, 14, 34);

        doc.autoTable({
            head: headers,
            body: rows,
            startY: 42,
            styles: { fontSize: 10, cellPadding: 3 },
            headStyles: { fillColor: [41, 128, 185], textColor: 255, fontStyle: 'bold' },
            columnStyles: {
                0: { cellWidth: 60 },
                1: { cellWidth: 25, halign: 'center' },
                2: { cellWidth: 40, halign: 'right' },
                3: { cellWidth: 30, halign: 'right' }
            }
        });

        const fileName = `Cost_Summary_${getSummaryBranchName()}_${getTimestamp()}.pdf`;
        doc.save(fileName);
        toastr.success('PDF file downloaded!');
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
        elements.exportSummaryExcelBtn?.addEventListener('click', exportSummaryExcel);
        elements.exportSummaryPdfBtn?.addEventListener('click', exportSummaryPdf);
        elements.exportChartPngBtn?.addEventListener('click', exportChartPng);

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

        // Tab change events
        document.querySelectorAll('#main_tabs a[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function (e) {
                const targetId = e.target.getAttribute('href');
                const toolbar = document.getElementById('cost_records_toolbar');

                if (targetId === '#tab_cost_records') {
                    if (toolbar) toolbar.classList.remove('d-none');
                } else {
                    if (toolbar) toolbar.classList.add('d-none');
                }
            });
        });
    };

    // ============================================
    // INIT
    // ============================================
    const init = function () {
        initElements();
        initModals();
        initMenuHandlers();
        initDateRangeFilter();
        initCostTypeFilterSelect();
        initFilterEvents();
        initSearch();
        initCostsDataTable();
        initExportHandlers();
        initSummaryDateRange();
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
