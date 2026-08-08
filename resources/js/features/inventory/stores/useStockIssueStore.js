import { defineStore } from 'pinia';
import { inventoryApi } from '../api/inventoryApi';

export const useStockIssueStore = defineStore('stockIssue', {
    state: () => ({
        issues: {
            data: [],
            meta: {},
        },
        currentIssue: null,
        loading: false,
        error: null,
    }),

    actions: {
        async fetchIssues(params = {}) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.getIssues(params);
                const raw = response.data;
                if (raw && Array.isArray(raw.data)) {
                    this.issues = {
                        data: raw.data,
                        meta: raw.meta || {},
                    };
                } else if (Array.isArray(raw)) {
                    this.issues = {
                        data: raw,
                        meta: {},
                    };
                } else {
                    this.issues = {
                        data: [],
                        meta: {},
                    };
                }
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal memuat daftar dokumen pengeluaran';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchIssueById(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.getIssueById(id);
                this.currentIssue = response.data.data;
                return this.currentIssue;
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal memuat detail dokumen';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createIssue(data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.createIssue(data);
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal membuat draft pengeluaran';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateIssue(id, data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.updateIssue(id, data);
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal mengubah draft pengeluaran';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async postIssue(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.postIssue(id);
                if (this.currentIssue && this.currentIssue.id === id) {
                    this.currentIssue = response.data.data;
                }
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal memposting dokumen, stok mungkin tidak mencukupi.';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async cancelIssue(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.cancelIssue(id);
                if (this.currentIssue && this.currentIssue.id === id) {
                    this.currentIssue = response.data.data;
                }
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal membatalkan dokumen';
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});
