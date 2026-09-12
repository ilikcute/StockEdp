import apiClient from '@/shared/api/api_client';

export const storeApi = {
    getAll: (params) => apiClient.get('/stores', { params }),
    getById: (id) => apiClient.get(`/stores/${id}`),
    create: (data) => apiClient.post('/stores', data),
    update: (id, data) => apiClient.put(`/stores/${id}`, data),
    changeStatus: (id, isActive) => apiClient.patch(`/stores/${id}/status`, { is_active: isActive }),
};
