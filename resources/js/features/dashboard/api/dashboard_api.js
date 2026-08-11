import apiClient from '@/shared/api/api_client';

export const dashboardApi = {
    getDashboard: (params = {}) => apiClient.get('/dashboard', { params }),
};

export default dashboardApi;
