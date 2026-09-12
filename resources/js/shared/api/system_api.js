import apiClient from '@/shared/api/api_client';

export const systemApi = {
    getHealth() {
        return apiClient.get('/health');
    },
};

