import apiClient from '@shared/api/api_client.js';

export const productApi = {
    getAll: (params = {}) => apiClient.get('/products', { params }),
    getById: (id) => apiClient.get(`/products/${id}`),
    create: (data) => apiClient.post('/products', data),
    update: (id, data) => apiClient.put(`/products/${id}`, data),
    changeStatus: (id, isActive) => apiClient.patch(`/products/${id}/status`, { is_active: isActive }),
};
