import apiClient from '@shared/api/api_client.js';

export const replenishmentApi = {
    getRecommendations(params = {}) {
        return apiClient.get('/replenishment-recommendations', { params });
    },

    getFilterOptions() {
        return apiClient.get('/replenishment-recommendations/filter-options');
    },

    validateAction(payload) {
        return apiClient.post('/replenishment-recommendations/validate-action', payload);
    },
};

export default replenishmentApi;
