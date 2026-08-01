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
                this.transfers = response.data.data;
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

        async receiveTransfer(id) {
            this.loadingAction = true;
            this.error = null;
            try {
                const response = await inventoryApi.receiveTransfer(id);
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
        }
    }
});