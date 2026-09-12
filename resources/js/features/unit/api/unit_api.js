import apiClient from '@shared/api/api_client.js';

export const unitApi = {
    getAll(params = {}) {
        return apiClient.get('/units', { params });
    },

    create(data) {
        return apiClient.post('/units', data);
    },

    update(id, data) {
        return apiClient.put(`/units/${id}`, data);
    },

    changeStatus(id, isActive) {
        return apiClient.patch(`/units/${id}/status`, { is_active: isActive });
    },
};
