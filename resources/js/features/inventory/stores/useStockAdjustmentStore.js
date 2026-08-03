import { defineStore } from 'pinia';
import { stockAdjustmentApi } from '../api/stockAdjustmentApi';
import { normalizeApiError } from '@/shared/api/api_client';

export const useStockAdjustmentStore = defineStore('stockAdjustment', {
    state: () => ({
        adjustments: {
            data: [],
            meta: {},
        },
        currentAdjustment: null,
        loadingList: false,
        loadingDetail: false,
        loadingAction: false,
        error: null,
        validationErrors: {},
        status: null,
    }),

    actions: {
        async fetchAdjustments(params = {}) {
            this.loadingList = true;
            this.error = null;
            try {
                const response = await stockAdjustmentApi.getAdjustments(params);
                this.adjustments = response.data.data;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                throw error;
            } finally {
                this.loadingList = false;
            }
        },

        async fetchAdjustmentById(id) {
            this.loadingDetail = true;
            this.error = null;
            try {
                const response = await stockAdjustmentApi.getAdjustmentById(id);
                this.currentAdjustment = response.data.data;
                return this.currentAdjustment;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                throw error;
            } finally {
                this.loadingDetail = false;
            }
        },

        async createAdjustment(data) {
            if (this.loadingAction) return;
            this.loadingAction = true;
            this.error = null;
            this.validationErrors = {};
            try {
                const response = await stockAdjustmentApi.createAdjustment(data);
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

        async updateAdjustment(id, data) {
            if (this.loadingAction) return;
            this.loadingAction = true;
            this.error = null;
            this.validationErrors = {};
            try {
                const response = await stockAdjustmentApi.updateAdjustment(id, data);
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

        async postAdjustment(id) {
            if (this.loadingAction) return;
            this.loadingAction = true;
            this.error = null;
            try {
                const response = await stockAdjustmentApi.postAdjustment(id);
                await this.fetchAdjustmentById(id);
                return response.data;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                if (normalized.status === 409) {
                    await this.fetchAdjustmentById(id);
                }
                throw error;
            } finally {
                this.loadingAction = false;
            }
        },

        async cancelAdjustment(id) {
            if (this.loadingAction) return;
            this.loadingAction = true;
            this.error = null;
            try {
                const response = await stockAdjustmentApi.cancelAdjustment(id);
                await this.fetchAdjustmentById(id);
                return response.data;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                if (normalized.status === 409) {
                    await this.fetchAdjustmentById(id);
                }
                throw error;
            } finally {
                this.loadingAction = false;
            }
        },

        resetFormErrors() {
            this.error = null;
            this.validationErrors = {};
            this.status = null;
        },

        resetActiveAdjustment() {
            this.currentAdjustment = null;
            this.error = null;
            this.validationErrors = {};
            this.status = null;
        },
    },
});
