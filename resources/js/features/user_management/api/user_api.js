import apiClient from '@shared/api/api_client.js';

export const userApi = {
    getUsers(params = {}) {
        return apiClient.get('/users', { params });
    },

    getFormOptions() {
        return apiClient.get('/users/form-options');
    },

    getUser(id) {
        return apiClient.get(`/users/${id}`);
    },

    createUser(data) {
        return apiClient.post('/users', data);
    },

    updateUser(id, data) {
        return apiClient.put(`/users/${id}`, data);
    },

    updateUserStatus(id, isActive) {
        return apiClient.patch(`/users/${id}/status`, { is_active: isActive });
    },

    getRoles() {
        return apiClient.get('/roles');
    },

    getPermissions() {
        return apiClient.get('/permissions');
    },
};
