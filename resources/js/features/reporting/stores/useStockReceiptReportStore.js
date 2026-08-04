import { defineStore } from 'pinia';
import { reportingApi } from '../api/reportingApi';
import { normalizeApiError } from '@/shared/api/api_client';

let latestRequestId = 0;

export const useStockReceiptReportStore = defineStore('stockReceiptReport', {
    state: () => ({
        data: [],
        meta: null,
        summary: null,
        pagination: null,
        loading: false,
        error: null,
        status: null,
        validationErrors: {},
    }),

    actions: {
        async fetchReport(filters = {}) {
            const requestId = ++latestRequestId;
            this.loading = true;
            this.error = null;
            this.status = null;
            this.validationErrors = {};

            try {
                const response = await reportingApi.getStockReceipts(filters);
                if (requestId !== latestRequestId) return;

                const payload = response.data;
                this.data = payload.data;
                this.meta = payload.meta;
                this.summary = payload.meta?.summary ?? null;
                this.pagination = payload.pagination;
                this.status = response.status;
            } catch (err) {
                if (requestId !== latestRequestId) return;

                const normalized = normalizeApiError(err);
                this.error = normalized.message || 'Gagal memuat laporan penerimaan stok';
                this.status = normalized.status;
                this.validationErrors = normalized.errors || {};
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
            this.summary = null;
            this.pagination = null;
            this.loading = false;
            this.error = null;
            this.status = null;
            this.validationErrors = {};
        },
    },
});
