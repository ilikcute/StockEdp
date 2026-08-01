import apiClient from '@/shared/api/api_client';

export const locationApi = {
    getAll: (params) => apiClient.get('/locations', { params }),
    getById: (id) => apiClient.get(`/locations/${id}`),
    create: (data) => apiClient.post('/locations', data),
    update: (id, data) => apiClient.put(`/locations/${id}`, data),
    changeStatus: (id, isActive) => apiClient.patch(`/locations/${id}/status`, { is_active: isActive }),
};
