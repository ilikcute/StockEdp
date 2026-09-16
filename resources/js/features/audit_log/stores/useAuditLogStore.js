import { defineStore } from 'pinia';
import { auditLogApi } from '../api/auditLogApi';

export const useAuditLogStore = defineStore('auditLog', {
    state: () => ({
        logs: [],
        modules: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 25,
            total: 0,
            from: 0,
            to: 0,
        },
        loading: false,
        loadingModules: false,
        error: null,
        selectedLog: null,
    }),

    actions: {
        async fetchLogs(params = {}) {
            this.loading = true;
            this.error = null;

            try {
                const response = await auditLogApi.getAuditLogs(params);
                const data = response?.data;

                if (data?.success) {
                    this.logs = Array.isArray(data.data) ? data.data : [];
                    if (data.meta) {
                        this.meta = { ...this.meta, ...data.meta };
                    }
                } else {
                    this.error = data?.message || 'Gagal memuat log aktivitas.';
                }
            } catch (err) {
                console.error('Error fetching audit logs:', err);
                this.error = err.response?.data?.message || 'Gagal terhubung ke server saat memuat log aktivitas.';
                this.logs = [];
            } finally {
                this.loading = false;
            }
        },

        async fetchModules() {
            if (this.modules.length > 0) return;

            this.loadingModules = true;
            try {
                const response = await auditLogApi.getAuditModules();
                const data = response?.data;
                if (data?.success && Array.isArray(data.data)) {
                    this.modules = data.data;
                }
            } catch (err) {
                console.error('Error fetching audit modules:', err);
            } finally {
                this.loadingModules = false;
            }
        },

        setSelectedLog(log) {
            this.selectedLog = log;
        },

        clearSelectedLog() {
            this.selectedLog = null;
        },
    },
});
