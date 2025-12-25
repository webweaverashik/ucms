/**
 * UCMS Dashboard - Cache Manager
 * Pure JavaScript (No jQuery)
 */

'use strict';

const DashboardCache = {
    /**
     * Clear dashboard cache via API
     */
    async clearCache(branchId = null) {
        try {
            const response = await fetch(`${DashboardConfig.apiBaseUrl}/clear-cache`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': DashboardConfig.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    branch_id: branchId === 'all' ? null : branchId,
                }),
            });

            if (!response.ok) {
                throw new Error('Failed to clear cache');
            }

            const result = await response.json();
            return result.success;
        } catch (error) {
            console.error('Cache clear error:', error);
            return false;
        }
    },

    /**
     * Local storage cache for client-side caching
     */
    local: {
        prefix: 'ucms_dashboard_',
        ttl: 5 * 60 * 1000, // 5 minutes

        /**
         * Set item in local cache
         */
        set(key, data) {
            try {
                const item = {
                    data: data,
                    timestamp: Date.now(),
                };
                localStorage.setItem(this.prefix + key, JSON.stringify(item));
            } catch (e) {
                console.warn('Local storage error:', e);
            }
        },

        /**
         * Get item from local cache
         */
        get(key) {
            try {
                const item = localStorage.getItem(this.prefix + key);
                if (!item) return null;

                const parsed = JSON.parse(item);
                
                // Check if expired
                if (Date.now() - parsed.timestamp > this.ttl) {
                    this.remove(key);
                    return null;
                }

                return parsed.data;
            } catch (e) {
                console.warn('Local storage error:', e);
                return null;
            }
        },

        /**
         * Remove item from local cache
         */
        remove(key) {
            try {
                localStorage.removeItem(this.prefix + key);
            } catch (e) {
                console.warn('Local storage error:', e);
            }
        },

        /**
         * Clear all dashboard local cache
         */
        clear() {
            try {
                const keys = Object.keys(localStorage);
                keys.forEach(key => {
                    if (key.startsWith(this.prefix)) {
                        localStorage.removeItem(key);
                    }
                });
            } catch (e) {
                console.warn('Local storage error:', e);
            }
        },

        /**
         * Get cache key for branch-specific data
         */
        getBranchKey(type, branchId) {
            return `${type}_branch_${branchId || 'all'}`;
        },
    },

    /**
     * Session storage cache for temporary data
     */
    session: {
        prefix: 'ucms_dashboard_session_',

        set(key, data) {
            try {
                sessionStorage.setItem(this.prefix + key, JSON.stringify(data));
            } catch (e) {
                console.warn('Session storage error:', e);
            }
        },

        get(key) {
            try {
                const item = sessionStorage.getItem(this.prefix + key);
                return item ? JSON.parse(item) : null;
            } catch (e) {
                console.warn('Session storage error:', e);
                return null;
            }
        },

        remove(key) {
            try {
                sessionStorage.removeItem(this.prefix + key);
            } catch (e) {
                console.warn('Session storage error:', e);
            }
        },

        clear() {
            try {
                const keys = Object.keys(sessionStorage);
                keys.forEach(key => {
                    if (key.startsWith(this.prefix)) {
                        sessionStorage.removeItem(key);
                    }
                });
            } catch (e) {
                console.warn('Session storage error:', e);
            }
        },
    },
};
