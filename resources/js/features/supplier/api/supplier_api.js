import apiClient from '@shared/api/api_client.js';

export const supplierApi = {
    getAll: (params = {}) => apiClient.get('/suppliers', { params }),
    getById: (id) => apiClient.get(`/suppliers/${id}`),
    create: (data) => apiClient.post('/suppliers', data),
    update: (id, data) => apiClient.put(`/suppliers/${id}`, data),
    changeStatus: (id, isActive) => apiClient.patch(`/suppliers/${id}/status`, { is_active: isActive }),
};
