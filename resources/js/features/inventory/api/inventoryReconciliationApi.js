import apiClient from '@/shared/api/api_client';

export const inventoryReconciliationApi = {
    scan(params = {}) {
        return apiClient.get('/inventory/reconciliation/scan', { params });
    },

    apply(data = {}) {
        return apiClient.post('/inventory/reconciliation/apply', data);
    }
};

export default inventoryReconciliationApi;
