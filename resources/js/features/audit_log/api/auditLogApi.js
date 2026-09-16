import apiClient from '@/shared/api/api_client';

export const auditLogApi = {
    /**
     * Mengambil daftar log aktivitas terpaginasi
     * @param {Object} params - search, module, action, user_id, date_from, date_to, per_page, page
     */
    getAuditLogs: (params = {}) => apiClient.get('/audit-logs', { params }),

    /**
     * Mengambil daftar modul unik beserta label untuk filter
     */
    getAuditModules: () => apiClient.get('/audit-logs/modules'),
};
