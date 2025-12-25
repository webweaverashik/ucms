/**
 * UCMS Dashboard - Table Renderers
 * Pure JavaScript (No jQuery)
 */

'use strict';

const DashboardTables = {
    /**
     * Render recent transactions table
     */
    renderTransactions(transactions) {
        const tbody = document.getElementById('recentTransactionsBody');
        if (!tbody) return;

        if (!transactions || transactions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-5 text-muted">
                        No recent transactions found
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = transactions.map(t => `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-40px me-3">
                            <span class="symbol-label bg-light-primary text-primary fw-bold">
                                ${DashboardUtils.getInitials(t.student_name)}
                            </span>
                        </div>
                        <div>
                            <a href="#" class="text-gray-900 fw-bold text-hover-primary fs-6">
                                ${t.student_name}
                            </a>
                            <span class="text-muted fw-semibold d-block fs-7">
                                ${t.class_name}
                            </span>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="text-gray-900 fw-bold">
                        ${DashboardUtils.formatCurrency(t.amount)}
                    </span>
                </td>
                <td>
                    <span class="badge ${DashboardUtils.getInvoiceTypeBadgeClass(t.type)}">
                        ${DashboardUtils.getInvoiceTypeLabel(t.type)}
                    </span>
                </td>
                <td class="text-end">
                    ${DashboardUtils.getStatusBadge(t.status)}
                </td>
            </tr>
        `).join('');
    },

    /**
     * Render top employees list
     */
    renderTopEmployees(employees) {
        const container = document.getElementById('topEmployeesBody');
        if (!container) return;

        if (!employees || employees.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5 text-muted">
                    No employee data available
                </div>
            `;
            return;
        }

        container.innerHTML = employees.map((e, index) => `
            <div class="d-flex align-items-center mb-6">
                <div class="symbol symbol-30px me-4">
                    <span class="symbol-label bg-light-secondary text-gray-600 fw-bold fs-7">
                        ${index + 1}
                    </span>
                </div>
                <div class="symbol symbol-40px me-4">
                    <span class="symbol-label bg-light-primary text-primary fw-bold">
                        ${e.initials || DashboardUtils.getInitials(e.name)}
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div>
                            <span class="text-gray-900 fw-bold">${e.name}</span>
                            <span class="text-muted fs-8 ms-2">${e.email || ''}</span>
                        </div>
                        <span class="text-gray-900 fw-bold fs-7">${e.transaction_count} txns</span>
                    </div>
                    <div class="progress h-6px bg-light-primary">
                        <div class="progress-bar bg-primary" role="progressbar" 
                             style="width: ${e.percentage}%" 
                             aria-valuenow="${e.percentage}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    },

    /**
     * Render top subjects list
     */
    renderTopSubjects(subjects) {
        const container = document.getElementById('topSubjectsBody');
        if (!container) return;

        if (!subjects || subjects.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5 text-muted">
                    No subject data available
                </div>
            `;
            return;
        }

        container.innerHTML = subjects.map(s => `
            <div class="mb-5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-gray-900 fw-bold">${s.name}</span>
                    <span class="text-muted fs-7">${DashboardUtils.formatNumber(s.student_count)} students</span>
                </div>
                <div class="progress h-8px bg-light-info">
                    <div class="progress-bar bg-info" role="progressbar" 
                         style="width: ${s.percentage}%" 
                         aria-valuenow="${s.percentage}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
            </div>
        `).join('');
    },

    /**
     * Render login activities table
     */
    renderLoginActivities(activities) {
        const tbody = document.getElementById('loginActivitiesBody');
        if (!tbody) return;

        if (!activities || activities.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        No login activities found
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = activities.map(a => `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="bullet bullet-dot bg-success me-3" style="width: 8px; height: 8px;"></span>
                        <span class="text-gray-900 fw-bold">${a.user_name}</span>
                    </div>
                </td>
                <td>
                    ${DashboardUtils.getRoleBadge(a.user_type)}
                </td>
                <td>
                    <span class="text-gray-600 font-monospace fs-7">${a.ip_address}</span>
                </td>
                <td>
                    <span class="text-gray-600 fs-7">${a.device}</span>
                </td>
                <td class="text-end">
                    <span class="text-muted fs-7">${a.created_at_human}</span>
                </td>
            </tr>
        `).join('');
    },

    /**
     * Render batch stats grid
     */
    renderBatchStats(batches) {
        const container = document.getElementById('batchStatsGrid');
        if (!container) return;

        if (!batches || batches.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5 text-muted">
                    No batch data available
                </div>
            `;
            return;
        }

        container.innerHTML = batches.map((b, index) => {
            const color = DashboardUtils.getBatchColorClass(index);
            return `
                <div class="col-6 col-md-3">
                    <div class="card ${color.bg} border ${color.border}">
                        <div class="card-body py-4">
                            <div class="${color.text} fw-bolder fs-2">
                                ${DashboardUtils.formatNumber(b.student_count)}
                            </div>
                            <div class="${color.text} fw-semibold fs-7 mt-1">
                                ${b.name_bn || b.name}
                            </div>
                            <div class="text-muted fs-8">
                                (${b.name})
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    },

    /**
     * Show loading skeleton for table
     */
    showTableSkeleton(tableId, columns = 4) {
        const tbody = document.getElementById(tableId);
        if (!tbody) return;

        const rows = Array(5).fill(0).map(() => `
            <tr>
                ${Array(columns).fill(0).map(() => `
                    <td>
                        <div class="skeleton" style="height: 20px; width: ${Math.random() * 50 + 50}%;"></div>
                    </td>
                `).join('')}
            </tr>
        `).join('');

        tbody.innerHTML = rows;
    },
};
