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

                this.adjustments = {
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
                if (response?.data?.data) {
                    this.currentAdjustment = response.data.data;
                }
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
                if (response?.data?.data) {
                    this.currentAdjustment = response.data.data;
                }
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

        async deleteAdjustment(id) {
            if (this.loadingAction) return;
            this.loadingAction = true;
            this.error = null;
            try {
                const response = await stockAdjustmentApi.deleteAdjustment(id);
                return response.data;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
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
