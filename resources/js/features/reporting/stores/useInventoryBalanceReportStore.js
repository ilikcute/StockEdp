import { defineStore } from 'pinia';
import { reportingApi } from '../api/reportingApi';
import { normalizeApiError } from '@/shared/api/api_client';

let latestRequestId = 0;

export const useInventoryBalanceReportStore = defineStore('inventoryBalanceReport', {
    state: () => ({
        data: [],
        meta: null,
        loading: false,
        error: null,
        validationErrors: {},
        status: null,
    }),
    actions: {
        async fetchBalances(params = {}) {
            const requestId = ++latestRequestId;
            this.loading = true;
            this.error = null;
            this.validationErrors = {};
            try {
                const response = await reportingApi.getInventoryBalances(params);
                if (requestId !== latestRequestId) return;
                this.data = response.data.data;
                this.meta = response.data.meta;
                this.status = response.status;
            } catch (error) {
                if (requestId !== latestRequestId) return;
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.validationErrors = normalized.errors || {};
                this.status = normalized.status;
                this.data = [];
                this.meta = null;
            } finally {
                if (requestId === latestRequestId) {
                    this.loading = false;
                }
            }
        },
        reset() {
            latestRequestId++;
            this.data = [];
            this.meta = null;
            this.error = null;
            this.validationErrors = {};
            this.status = null;
        }
    }
});
