import apiClient from '@/shared/api/api_client';

const csvRequestConfig = (params = {}) => ({
    params,
    responseType: 'blob',
    timeout: 0,
    headers: {
        Accept: 'text/csv, application/json',
    },
});

export const reportingApi = {
    getFilterBaseOptions() {
        return apiClient.get('/reports/filter-options/base');
    },

    getFilterProductOptions(params = {}) {
        return apiClient.get('/reports/filter-options/products', { params });
    },

    getFilterSupplierOptions(params = {}) {
        return apiClient.get('/reports/filter-options/suppliers', { params });
    },

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

    exportInventoryBalances(params = {}) {
        return apiClient.get('/reports/inventory-balances/export', csvRequestConfig(params));
    },

    exportLowStock(params = {}) {
        return apiClient.get('/reports/low-stock/export', csvRequestConfig(params));
    },

    exportStockCard(params = {}) {
        return apiClient.get('/reports/stock-card/export', csvRequestConfig(params));
    },

    exportStockReceipts(params = {}) {
        return apiClient.get('/reports/stock-receipts/export', csvRequestConfig(params));
    },

    exportStockIssues(params = {}) {
        return apiClient.get('/reports/stock-issues/export', csvRequestConfig(params));
    },

    exportStockTransfers(params = {}) {
        return apiClient.get('/reports/stock-transfers/export', csvRequestConfig(params));
    },

    exportStockAdjustments(params = {}) {
        return apiClient.get('/reports/stock-adjustments/export', csvRequestConfig(params));
    },

    exportStockOpnames(params = {}) {
        return apiClient.get('/reports/stock-opnames/export', csvRequestConfig(params));
    },
};
