import apiClient from '@/shared/api/api_client';

export const stockAdjustmentApi = {
    getAdjustments(params) {
        return apiClient.get('/stock-adjustments', { params });
    },

    getAdjustmentById(id) {
        return apiClient.get(`/stock-adjustments/${id}`);
    },

    createAdjustment(data) {
        return apiClient.post('/stock-adjustments', data);
    },

    updateAdjustment(id, data) {
        return apiClient.patch(`/stock-adjustments/${id}`, data);
    },

    postAdjustment(id) {
        return apiClient.post(`/stock-adjustments/${id}/post`);
    },

    cancelAdjustment(id) {
        return apiClient.post(`/stock-adjustments/${id}/cancel`);
    },
};
