/**
 * UCMS Dashboard - Chart Manager
 * Pure JavaScript with Chart.js
 */

'use strict';

const DashboardCharts = {
    // Chart color palette (Metronic compatible)
    colors: {
        primary: '#3699FF',
        success: '#1BC5BD',
        warning: '#FFA800',
        danger: '#F64E60',
        info: '#8950FC',
        dark: '#181C32',
        gray: '#E4E6EF',
        lightPrimary: 'rgba(54, 153, 255, 0.1)',
        lightSuccess: 'rgba(27, 197, 189, 0.1)',
        lightDanger: 'rgba(246, 78, 96, 0.1)',
    },

    // Chart instances
    instances: {},

    /**
     * Initialize all charts
     */
    init(data) {
        this.initPaymentChart(data.monthly_payments);
        this.initStudentDistChart(data.student_distribution);
        this.initAttendanceChart(data.attendance_analytics);
        this.initInvoiceStatusChart(data.invoice_status);
    },

    /**
     * Initialize Payment Overview Chart
     */
    initPaymentChart(data) {
        const ctx = document.getElementById('paymentChart');
        if (!ctx) return;

        // Destroy existing chart
        if (this.instances.payment) {
            this.instances.payment.destroy();
        }

        const chartData = data || {
            labels: [],
            collection: [],
            due: [],
        };

        this.instances.payment = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels || [],
                datasets: [
                    {
                        label: 'Collection',
                        data: chartData.collection || [],
                        backgroundColor: this.colors.primary,
                        borderRadius: 8,
                        barThickness: 30,
                    },
                    {
                        label: 'Due',
                        data: chartData.due || [],
                        backgroundColor: this.colors.danger,
                        borderRadius: 8,
                        barThickness: 30,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { family: 'Inter', size: 12 },
                        },
                    },
                    tooltip: {
                        backgroundColor: '#1e1e2d',
                        titleFont: { family: 'Inter', size: 13 },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 12,
                        callbacks: {
                            label: (context) => {
                                return `${context.dataset.label}: ৳${context.parsed.y.toLocaleString()}`;
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: '#f5f5f5',
                        },
                        ticks: {
                            font: { family: 'Inter', size: 11 },
                            color: '#7E8299',
                            callback: (value) => DashboardUtils.formatCurrency(value, true),
                        },
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Inter', size: 11 },
                            color: '#7E8299',
                        },
                    },
                },
            },
        });
    },

    /**
     * Update Payment Chart with new data
     */
    updatePaymentChart(data) {
        if (!this.instances.payment || !data) return;

        this.instances.payment.data.labels = data.labels || [];
        this.instances.payment.data.datasets[0].data = data.collection || [];
        this.instances.payment.data.datasets[1].data = data.due || [];
        this.instances.payment.update();
    },

    /**
     * Initialize Student Distribution Chart
     */
    initStudentDistChart(data) {
        const ctx = document.getElementById('studentDistChart');
        if (!ctx) return;

        if (this.instances.studentDist) {
            this.instances.studentDist.destroy();
        }

        const chartData = data || {
            labels: [],
            active: [],
            inactive: [],
        };

        this.instances.studentDist = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels || [],
                datasets: [
                    {
                        label: 'Active',
                        data: chartData.active || [],
                        borderColor: this.colors.success,
                        backgroundColor: this.colors.lightSuccess,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'Inactive',
                        data: chartData.inactive || [],
                        borderColor: this.colors.danger,
                        backgroundColor: this.colors.lightDanger,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { family: 'Inter', size: 12 },
                        },
                    },
                    tooltip: {
                        backgroundColor: '#1e1e2d',
                        titleFont: { family: 'Inter', size: 13 },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 12,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: '#f5f5f5',
                        },
                        ticks: {
                            font: { family: 'Inter', size: 11 },
                            color: '#7E8299',
                        },
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Inter', size: 11 },
                            color: '#7E8299',
                        },
                    },
                },
            },
        });
    },

    /**
     * Initialize Attendance Chart
     */
    initAttendanceChart(data) {
        const ctx = document.getElementById('attendanceChart');
        if (!ctx) return;

        if (this.instances.attendance) {
            this.instances.attendance.destroy();
        }

        const chartData = data || {
            labels: [],
            present: [],
            absent: [],
            late: [],
        };

        this.instances.attendance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels || [],
                datasets: [
                    {
                        label: 'Present',
                        data: chartData.present || [],
                        backgroundColor: this.colors.success,
                        borderRadius: 4,
                        barPercentage: 0.7,
                    },
                    {
                        label: 'Absent',
                        data: chartData.absent || [],
                        backgroundColor: this.colors.danger,
                        borderRadius: 4,
                        barPercentage: 0.7,
                    },
                    {
                        label: 'Late',
                        data: chartData.late || [],
                        backgroundColor: this.colors.warning,
                        borderRadius: 4,
                        barPercentage: 0.7,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { family: 'Inter', size: 12 },
                        },
                    },
                    tooltip: {
                        backgroundColor: '#1e1e2d',
                        titleFont: { family: 'Inter', size: 13 },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 12,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: true,
                        grid: {
                            drawBorder: false,
                            color: '#f5f5f5',
                        },
                        ticks: {
                            font: { family: 'Inter', size: 11 },
                            color: '#7E8299',
                        },
                    },
                    x: {
                        stacked: true,
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Inter', size: 11 },
                            color: '#7E8299',
                        },
                    },
                },
            },
        });
    },

    /**
     * Initialize Invoice Status Doughnut Chart
     */
    initInvoiceStatusChart(data) {
        const ctx = document.getElementById('invoiceStatusChart');
        if (!ctx) return;

        if (this.instances.invoiceStatus) {
            this.instances.invoiceStatus.destroy();
        }

        const chartData = data || {
            labels: ['Paid', 'Partially Paid', 'Due'],
            data: [0, 0, 0],
            colors: [this.colors.success, this.colors.warning, this.colors.danger],
        };

        this.instances.invoiceStatus = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData.labels || [],
                datasets: [
                    {
                        data: chartData.data || [],
                        backgroundColor: chartData.colors || [
                            this.colors.success,
                            this.colors.warning,
                            this.colors.danger,
                        ],
                        borderWidth: 0,
                        cutout: '75%',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e1e2d',
                        titleFont: { family: 'Inter', size: 13 },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 12,
                    },
                },
            },
        });

        // Update legend
        this.updateInvoiceStatusLegend(chartData);
    },

    /**
     * Update invoice status legend
     */
    updateInvoiceStatusLegend(data) {
        const legendContainer = document.getElementById('invoiceStatusLegend');
        if (!legendContainer || !data) return;

        const labels = data.labels || ['Paid', 'Partially Paid', 'Due'];
        const values = data.data || [0, 0, 0];
        const colors = ['#1BC5BD', '#FFA800', '#F64E60'];

        legendContainer.innerHTML = labels.map((label, i) => `
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <span class="bullet bullet-sm me-3" style="background-color: ${colors[i]}"></span>
                    <span class="text-gray-600 fs-7">${label}</span>
                </div>
                <span class="fw-bold text-gray-900">${DashboardUtils.formatNumber(values[i])}</span>
            </div>
        `).join('');
    },

    /**
     * Destroy all charts
     */
    destroyAll() {
        Object.values(this.instances).forEach(chart => {
            if (chart) chart.destroy();
        });
        this.instances = {};
    },
};
