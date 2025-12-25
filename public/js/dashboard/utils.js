/**
 * UCMS Dashboard - Utility Functions
 * Pure JavaScript (No jQuery)
 */

'use strict';

const DashboardUtils = {
    /**
     * Format number with thousand separators
     */
    formatNumber(num) {
        if (num === null || num === undefined) return '0';
        return new Intl.NumberFormat('en-IN').format(num);
    },

    /**
     * Format currency (Taka)
     */
    formatCurrency(amount, short = false) {
        if (amount === null || amount === undefined) amount = 0;

        if (short) {
            if (amount >= 10000000) {
                return '৳' + (amount / 10000000).toFixed(2) + ' Cr';
            }
            if (amount >= 100000) {
                return '৳' + (amount / 100000).toFixed(2) + ' L';
            }
            if (amount >= 1000) {
                return '৳' + (amount / 1000).toFixed(1) + 'K';
            }
        }

        return '৳' + this.formatNumber(amount);
    },

    /**
     * Get initials from name
     */
    getInitials(name) {
        if (!name) return 'U';
        return name
            .split(' ')
            .slice(0, 2)
            .map(word => word.charAt(0).toUpperCase())
            .join('');
    },

    /**
     * Get status badge HTML
     */
    getStatusBadge(status) {
        const badges = {
            paid: '<span class="badge badge-light-success">Paid</span>',
            partial: '<span class="badge badge-light-warning">Partial</span>',
            partially_paid: '<span class="badge badge-light-warning">Partial</span>',
            due: '<span class="badge badge-light-danger">Due</span>',
            pending: '<span class="badge badge-light-danger">Due</span>',
        };
        return badges[status] || `<span class="badge badge-light-secondary">${status}</span>`;
    },

    /**
     * Get role badge HTML
     */
    getRoleBadge(role) {
        const badges = {
            admin: '<span class="badge badge-light-danger">Admin</span>',
            manager: '<span class="badge badge-light-primary">Manager</span>',
            accountant: '<span class="badge badge-light-info">Accountant</span>',
            teacher: '<span class="badge badge-light-success">Teacher</span>',
            student: '<span class="badge badge-light-warning">Student</span>',
            guardian: '<span class="badge badge-light-dark">Guardian</span>',
        };
        return badges[role] || `<span class="badge badge-light-secondary">${role}</span>`;
    },

    /**
     * Get invoice type label
     */
    getInvoiceTypeLabel(type) {
        const labels = {
            tuition_fee: 'Tuition',
            sheet_fee: 'Sheet',
            exam_fee: 'Exam',
            admission_fee: 'Admission',
            model_test_fee: 'Model Test',
            diary_fee: 'Diary',
            book_fee: 'Book',
            others_fee: 'Others',
        };
        return labels[type] || type;
    },

    /**
     * Get invoice type badge class
     */
    getInvoiceTypeBadgeClass(type) {
        const classes = {
            tuition_fee: 'badge-light-primary',
            sheet_fee: 'badge-light-info',
            exam_fee: 'badge-light-warning',
            admission_fee: 'badge-light-success',
            model_test_fee: 'badge-light-dark',
        };
        return classes[type] || 'badge-light-secondary';
    },

    /**
     * Get batch name in Bengali
     */
    getBatchNameBn(name) {
        const map = {
            'Orun': 'অরুণ',
            'Usha': 'ঊষা',
            'Proloy': 'প্রলয়',
            'Dhumketu': 'ধূমকেতু',
        };
        return map[name] || name;
    },

    /**
     * Get batch color class
     */
    getBatchColorClass(index) {
        const colors = [
            { bg: 'bg-light-primary', text: 'text-primary', border: 'border-primary' },
            { bg: 'bg-light-success', text: 'text-success', border: 'border-success' },
            { bg: 'bg-light-info', text: 'text-info', border: 'border-info' },
            { bg: 'bg-light-warning', text: 'text-warning', border: 'border-warning' },
            { bg: 'bg-light-danger', text: 'text-danger', border: 'border-danger' },
        ];
        return colors[index % colors.length];
    },

    /**
     * Show toast notification
     */
    showToast(message, type = 'success') {
        // Check if Toastr is available (Metronic default)
        if (typeof toastr !== 'undefined') {
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: 'toastr-bottom-right',
                timeOut: 3000,
            };
            
            if (type === 'success') {
                toastr.success(message);
            } else if (type === 'error') {
                toastr.error(message);
            } else if (type === 'warning') {
                toastr.warning(message);
            } else {
                toastr.info(message);
            }
            return;
        }

        // Fallback: Create custom toast
        this.showCustomToast(message, type);
    },

    /**
     * Custom toast fallback
     */
    showCustomToast(message, type) {
        // Remove existing toast
        const existingToast = document.getElementById('custom-toast');
        if (existingToast) {
            existingToast.remove();
        }

        const bgColor = type === 'success' ? '#1BC5BD' : 
                       type === 'error' ? '#F64E60' : 
                       type === 'warning' ? '#FFA800' : '#3699FF';

        const toast = document.createElement('div');
        toast.id = 'custom-toast';
        toast.innerHTML = `
            <div style="
                position: fixed;
                bottom: 20px;
                right: 20px;
                padding: 15px 25px;
                background: ${bgColor};
                color: white;
                border-radius: 8px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                z-index: 9999;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideIn 0.3s ease;
            ">
                <i class="ki-outline ki-${type === 'success' ? 'check-circle' : 'information'} fs-3"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    },

    /**
     * Create skeleton loader
     */
    createSkeleton(width = '100%', height = '20px') {
        return `<div class="skeleton" style="width: ${width}; height: ${height};"></div>`;
    },

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Format date
     */
    formatDate(dateString, format = 'short') {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        
        if (format === 'short') {
            return date.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            });
        }
        
        if (format === 'long') {
            return date.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        }

        return dateString;
    },

    /**
     * Calculate percentage
     */
    calculatePercentage(value, total) {
        if (!total || total === 0) return 0;
        return Math.round((value / total) * 100);
    },
};

// Add CSS for skeleton animation if not exists
if (!document.getElementById('dashboard-utils-css')) {
    const style = document.createElement('style');
    style.id = 'dashboard-utils-css';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
        }
        
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    `;
    document.head.appendChild(style);
}
