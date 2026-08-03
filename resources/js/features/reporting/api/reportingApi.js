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
};
