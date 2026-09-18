import apiClient from '@/shared/api/api_client';

export const storeAllocationApi = {
    getAll(params) {
        return apiClient.get('/store-allocations', { params });
    },
    getById(id) {
        return apiClient.get(`/store-allocations/${id}`);
    },
    create(data) {
        return apiClient.post('/store-allocations', data);
    },
    delete(id) {
        return apiClient.delete(`/store-allocations/${id}`);
    },
};
