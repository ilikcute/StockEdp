import { defineStore } from 'pinia';
import { inventoryApi } from '../api/inventoryApi';
import { normalizeApiError } from '@/shared/api/api_client';

export const useStockTransferStore = defineStore('stockTransfer', {
    state: () => ({
        transfers: {
            data: [],
            meta: {},
        },
        currentTransfer: null,
        loadingList: false,
        loadingDetail: false,
        loadingAction: false,
        error: null,
        validationErrors: {},
        status: null,
    }),

    actions: {
        async fetchTransfers(params = {}) {
            this.loadingList = true;
            this.error = null;
            try {
                const response = await inventoryApi.getTransfers(params);
                const raw = response.data;
                let data = [];
                let meta = {};
                if (raw && raw.data && Array.isArray(raw.data.data)) {
                    data = raw.data.data;
                    meta = raw.data.meta || {};
                } else if (raw && Array.isArray(raw.data)) {
                    data = raw.data;
                    meta = raw.meta || {};
                } else if (Array.isArray(raw)) {
                    data = raw;
                }

                const page = Number(meta.current_page) || Number(params.page) || 1;
                const perPage = Number(meta.per_page) || Number(params.per_page) || 15;
                const total = Number(meta.total) || data.length;
                const from = meta.from !== undefined && meta.from !== null ? Number(meta.from) : (total > 0 ? (page - 1) * perPage + 1 : null);
                const to = meta.to !== undefined && meta.to !== null ? Number(meta.to) : (total > 0 ? Math.min(page * perPage, total) : null);

                this.transfers = {
                    data,
                    meta: {
                        ...meta,
                        current_page: page,
                        per_page: perPage,
                        total,
                        from,
                        to,
                    },
                };
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                throw error;
            } finally {
                this.loadingList = false;
            }
        },

        async fetchTransferById(id) {
            this.loadingDetail = true;
            this.error = null;
            try {
                const response = await inventoryApi.getTransferById(id);
                this.currentTransfer = response.data.data;
                return this.currentTransfer;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                throw error;
            } finally {
                this.loadingDetail = false;
            }
        },

        async createTransfer(data) {
            this.loadingAction = true;
            this.error = null;
            this.validationErrors = {};
            try {
                const response = await inventoryApi.createTransfer(data);
                return response.data;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.validationErrors = normalized.errors;
                this.status = normalized.status;
                throw error;
            } finally {
                this.loadingAction = false;
            }
        },

        async updateTransfer(id, data) {
            this.loadingAction = true;
            this.error = null;
            this.validationErrors = {};
            try {
                const response = await inventoryApi.updateTransfer(id, data);
                return response.data;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.validationErrors = normalized.errors;
                this.status = normalized.status;
                throw error;
            } finally {
                this.loadingAction = false;
            }
        },

        async sendTransfer(id) {
            this.loadingAction = true;
            this.error = null;
            try {
                const response = await inventoryApi.sendTransfer(id);
                await this.fetchTransferById(id);
                return response.data;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                if (normalized.status === 409) {
                    await this.fetchTransferById(id);
                }
                throw error;
            } finally {
                this.loadingAction = false;
            }
        },

        async receiveTransfer(id, receivedItems = []) {
            this.loadingAction = true;
            this.error = null;
            try {
                const response = await inventoryApi.receiveTransfer(id, { items: receivedItems });
                await this.fetchTransferById(id);
                return response.data;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                if (normalized.status === 409) {
                    await this.fetchTransferById(id);
                }
                throw error;
            } finally {
                this.loadingAction = false;
            }
        },

        async cancelTransfer(id) {
            this.loadingAction = true;
            this.error = null;
            try {
                const response = await inventoryApi.cancelTransfer(id);
                await this.fetchTransferById(id);
                return response.data;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                if (normalized.status === 409) {
                    await this.fetchTransferById(id);
                }
                throw error;
            } finally {
                this.loadingAction = false;
            }
        },

        async deleteTransfer(id) {
            this.loadingAction = true;
            this.error = null;
            try {
                const response = await inventoryApi.deleteTransfer(id);
                return response.data;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                throw error;
            } finally {
                this.loadingAction = false;
            }
        }
    }
});