import apiClient from '@/shared/api/api_client';

export const dashboardApi = {
    getDashboard: (params = {}) => apiClient.get('/dashboard', { params }),
    getMovementSummary: (params = {}) => apiClient.get('/dashboard/inventory-movement-summary', { params }),
};

export default dashboardApi;
