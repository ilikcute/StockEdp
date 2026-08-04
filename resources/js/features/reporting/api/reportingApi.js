import apiClient from '@/shared/api/api_client';

export const reportingApi = {
    getInventoryBalances(params = {}) {
        return apiClient.get('/reports/inventory-balances', { params });
    },

    getLowStock(params = {}) {
        return apiClient.get('/reports/low-stock', { params });
    },

    getStockCard(params = {}) {
        return apiClient.get('/reports/stock-card', { params });
    },

    getStockReceipts(params = {}) {
        return apiClient.get('/reports/stock-receipts', { params });
    },

    getStockIssues(params = {}) {
        return apiClient.get('/reports/stock-issues', { params });
    },

    getStockTransfers(params = {}) {
        return apiClient.get('/reports/stock-transfers', { params });
    },

    getStockAdjustments(params = {}) {
        return apiClient.get('/reports/stock-adjustments', { params });
    },

    getStockOpnames(params = {}) {
        return apiClient.get('/reports/stock-opnames', { params });
    },
};
