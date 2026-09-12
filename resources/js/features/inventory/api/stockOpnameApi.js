import apiClient from '@/shared/api/api_client';

export const stockOpnameApi = {
    getOpnames(params = {}) {
        return apiClient.get('/stock-opnames', { params });
    },

    createOpname(payload) {
        return apiClient.post('/stock-opnames', payload);
    },

    getOpname(id) {
        return apiClient.get(`/stock-opnames/${id}`);
    },

    updateOpname(id, payload) {
        return apiClient.patch(`/stock-opnames/${id}`, payload);
    },

    startOpname(id) {
        return apiClient.post(`/stock-opnames/${id}/start`);
    },

    saveItemCount(opnameId, itemId, payload) {
        return apiClient.patch(`/stock-opnames/${opnameId}/items/${itemId}/count`, payload);
    },

    addUnexpectedProduct(opnameId, payload) {
        return apiClient.post(`/stock-opnames/${opnameId}/items`, payload);
    },

    completeOpname(id) {
        return apiClient.post(`/stock-opnames/${id}/complete`);
    },

    reopenOpname(id, payload) {
        return apiClient.post(`/stock-opnames/${id}/reopen`, payload);
    },

    postOpname(id) {
        return apiClient.post(`/stock-opnames/${id}/post`);
    },

    cancelOpname(id, payload) {
        return apiClient.post(`/stock-opnames/${id}/cancel`, payload);
    },
};
