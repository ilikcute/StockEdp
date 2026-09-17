import apiClient from '@shared/api/api_client.js';

export const productSerialApi = {
    getAll: (params = {}) => apiClient.get('/product-serials', { params }),
    getByIdOrNumber: (idOrNumber) => apiClient.get(`/product-serials/${idOrNumber}`),
    lookup: (sn) => apiClient.get('/product-serials/lookup', { params: { sn } }),
};
