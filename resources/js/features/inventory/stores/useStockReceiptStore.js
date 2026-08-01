import { defineStore } from 'pinia';
import { inventoryApi } from '../api/inventoryApi';

export const useStockReceiptStore = defineStore('stockReceipt', {
    state: () => ({
        receipts: {
            data: [],
            meta: {},
        },
        currentReceipt: null,
        loading: false,
        error: null,
    }),

    actions: {
        async fetchReceipts(params = {}) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.getReceipts(params);
                this.receipts = response.data.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal memuat daftar dokumen penerimaan';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchReceiptById(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.getReceiptById(id);
                this.currentReceipt = response.data.data;
                return this.currentReceipt;
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal memuat detail dokumen';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async createReceipt(data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.createReceipt(data);
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal membuat draft penerimaan';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateReceipt(id, data) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.updateReceipt(id, data);
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal mengubah draft penerimaan';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async postReceipt(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.postReceipt(id);
                if (this.currentReceipt && this.currentReceipt.id === id) {
                    this.currentReceipt = response.data.data;
                }
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal memposting dokumen';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async cancelReceipt(id) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.cancelReceipt(id);
                if (this.currentReceipt && this.currentReceipt.id === id) {
                    this.currentReceipt = response.data.data;
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
