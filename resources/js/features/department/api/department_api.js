import apiClient from '@/shared/api/api_client';

export const departmentApi = {
    getAll: (params) => apiClient.get('/departments', { params }),
    getActive: () => apiClient.get('/departments/active'),
    getById: (id) => apiClient.get(`/departments/${id}`),
    create: (data) => apiClient.post('/departments', data),
    update: (id, data) => apiClient.put(`/departments/${id}`, data),
    changeStatus: (id, isActive) => apiClient.patch(`/departments/${id}/status`, { is_active: isActive }),
};
