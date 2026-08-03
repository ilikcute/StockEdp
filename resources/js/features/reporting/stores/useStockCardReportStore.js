import { defineStore } from 'pinia';
import { reportingApi } from '../api/reportingApi';
import { normalizeApiError } from '@/shared/api/api_client';

export const useStockCardReportStore = defineStore('stockCardReport', {
    state: () => ({
        data: [],
        meta: null,
        summary: null,
        loading: false,
        error: null,
        validationErrors: {},
        status: null,
    }),
    actions: {
        async fetchStockCard(params = {}) {
            this.loading = true;
            this.error = null;
            this.validationErrors = {};
            try {
                const response = await reportingApi.getStockCard(params);
                this.data = response.data.data;
                this.meta = response.data.meta;
                this.summary = response.data.summary;
                this.status = response.status;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.validationErrors = normalized.errors || {};
                this.status = normalized.status;
                this.data = [];
                this.meta = null;
                this.summary = null;
            } finally {
                this.loading = false;
            }
        },
        reset() {
            this.data = [];
            this.meta = null;
            this.summary = null;
            this.error = null;
            this.validationErrors = {};
            this.status = null;
        }
    }
});
