import apiClient from '@shared/api/api_client.js';

export const categoryApi = {
    getAll(params = {}) {
        return apiClient.get('/categories', { params });
    },

    create(data) {
        return apiClient.post('/categories', data);
    },

    update(id, data) {
        return apiClient.put(`/categories/${id}`, data);
    },

    changeStatus(id, isActive) {
        return apiClient.patch(`/categories/${id}/status`, { is_active: isActive });
    },
};
