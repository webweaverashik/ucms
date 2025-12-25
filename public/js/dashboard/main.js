/**
 * UCMS Dashboard - Main Controller
 * Pure JavaScript (No jQuery)
 */

'use strict';

// Dashboard State Management
const DashboardState = {
    currentBranch: DashboardConfig.currentBranch || 'all',
    isLoading: false,
    charts: {},
    data: DashboardConfig.initialData || {},
};

// ========================================
// Dashboard Controller
// ========================================
const DashboardController = {
    /**
     * Initialize dashboard
     */
    init() {
        this.bindEvents();
        this.loadAllData();
    },

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Branch tab switching
        document.querySelectorAll('.branch-tab').forEach(tab => {
            tab.addEventListener('click', (e) => this.handleBranchSwitch(e));
        });

        // Refresh button
        const refreshBtn = document.getElementById('refreshDashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => this.refreshDashboard());
        }

        // Payment chart period change
        const periodSelect = document.getElementById('paymentChartPeriod');
        if (periodSelect) {
            periodSelect.addEventListener('change', (e) => this.handlePeriodChange(e));
        }
    },

    /**
     * Handle branch tab switch
     */
    handleBranchSwitch(e) {
        const tab = e.currentTarget;
        const branch = tab.dataset.branch;

        if (DashboardState.currentBranch === branch) return;

        // Update active state
        document.querySelectorAll('.branch-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        DashboardState.currentBranch = branch;

        // Reload data for new branch
        this.loadAllData();
    },

    /**
     * Handle payment chart period change
     */
    handlePeriodChange(e) {
        const months = parseInt(e.target.value);
        this.loadMonthlyPayments(months);
    },

    /**
     * Refresh dashboard
     */
    async refreshDashboard() {
        // Clear cache first
        await DashboardCache.clearCache(DashboardState.currentBranch);
        
        // Reload all data
        await this.loadAllData();

        DashboardUtils.showToast('Dashboard refreshed successfully!', 'success');
    },

    /**
     * Load all dashboard data
     */
    async loadAllData() {
        if (DashboardState.isLoading) return;

        DashboardState.isLoading = true;
        this.showLoadingState();

        try {
            const branchParam = DashboardState.currentBranch === 'all' ? '' : DashboardState.currentBranch;
            const url = `${DashboardConfig.apiBaseUrl}/all?branch_id=${branchParam}`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': DashboardConfig.csrfToken,
                },
            });

            if (!response.ok) throw new Error('Failed to fetch dashboard data');

            const result = await response.json();

            if (result.success) {
                DashboardState.data = result.data;
                this.renderDashboard(result.data);
            }
        } catch (error) {
            console.error('Dashboard load error:', error);
            DashboardUtils.showToast('Failed to load dashboard data', 'error');
        } finally {
            DashboardState.isLoading = false;
        }
    },

    /**
     * Load monthly payments data
     */
    async loadMonthlyPayments(months = 6) {
        try {
            const branchParam = DashboardState.currentBranch === 'all' ? '' : DashboardState.currentBranch;
            const url = `${DashboardConfig.apiBaseUrl}/monthly-payments?branch_id=${branchParam}&months=${months}`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': DashboardConfig.csrfToken,
                },
            });

            const result = await response.json();

            if (result.success) {
                DashboardCharts.updatePaymentChart(result.data);
            }
        } catch (error) {
            console.error('Monthly payments load error:', error);
        }
    },

    /**
     * Show loading state
     */
    showLoadingState() {
        // Add loading indicators to tables
        const tables = ['recentTransactionsBody', 'loginActivitiesBody'];
        tables.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                `;
            }
        });
    },

    /**
     * Render all dashboard components
     */
    renderDashboard(data) {
        // Update stats cards
        this.updateStats(data.stats);

        // Initialize/update charts
        DashboardCharts.init(data);

        // Render tables
        DashboardTables.renderTransactions(data.recent_transactions || []);
        DashboardTables.renderTopEmployees(data.top_employees || []);
        DashboardTables.renderTopSubjects(data.top_subjects || []);
        DashboardTables.renderBatchStats(data.batch_stats || []);

        // Render login activities (admin only)
        if (data.login_activities) {
            DashboardTables.renderLoginActivities(data.login_activities);
        }
    },

    /**
     * Update stats cards
     */
    updateStats(stats) {
        if (!stats) return;

        const updates = {
            'statTotalStudents': DashboardUtils.formatNumber(stats.total_students),
            'statActiveStudents': DashboardUtils.formatNumber(stats.active_students),
            'statDueInvoices': DashboardUtils.formatNumber(stats.due_invoices),
            'statTotalCollection': DashboardUtils.formatCurrency(stats.current_month_collection, true),
            'statPendingStudents': DashboardUtils.formatNumber(stats.pending_students),
            'statInactiveStudents': DashboardUtils.formatNumber(stats.inactive_students),
            'statPaidInvoices': DashboardUtils.formatNumber(stats.paid_invoices),
            'statPartialInvoices': DashboardUtils.formatNumber(stats.partially_paid_invoices),
        };

        for (const [id, value] of Object.entries(updates)) {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = value;
            }
        }

        // Update active percentage badge
        const activePercentEl = document.getElementById('statActivePercentage');
        if (activePercentEl) {
            activePercentEl.textContent = `${stats.active_percentage || 0}% Active`;
        }

        // Update collection trend
        const trendEl = document.getElementById('statCollectionTrend');
        if (trendEl && stats.collection_trend !== undefined) {
            const trend = stats.collection_trend;
            const isPositive = trend >= 0;
            trendEl.className = `badge badge-light-${isPositive ? 'success' : 'danger'} fs-8`;
            trendEl.innerHTML = `
                <i class="ki-outline ki-arrow-${isPositive ? 'up' : 'down'} fs-9 text-${isPositive ? 'success' : 'danger'} me-1"></i>
                ${Math.abs(trend)}%
            `;
        }
    },
};

// ========================================
// Initialize on DOM Ready
// ========================================
document.addEventListener('DOMContentLoaded', () => {
    DashboardController.init();
});
