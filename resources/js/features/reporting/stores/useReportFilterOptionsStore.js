import { defineStore } from 'pinia';
import { reportingApi } from '@/features/reporting/api/reportingApi';
import { normalizeApiError } from '@/shared/api/api_client';

let latestProductSearchRequestId = 0;
let latestSupplierSearchRequestId = 0;

export const useReportFilterOptionsStore = defineStore('reportFilterOptions', {
    state: () => ({
        locations: [],
        categories: [],
        units: [],
        products: [],
        suppliers: [],
        loading: false,
        loadingProducts: false,
        loadingSuppliers: false,
        error: null,
        status: null,
        supplierError: null,
    }),
    actions: {
        async fetchBaseOptions() {
            this.loading = true;
            this.error = null;
            this.status = null;
            try {
                const response = await reportingApi.getFilterBaseOptions();
                this.locations = response.data.data.locations || [];
                this.categories = response.data.data.categories || [];
                this.units = response.data.data.units || [];
                this.status = response.status;
            } catch (err) {
                const normalized = normalizeApiError(err);
                this.error = normalized.message || 'Gagal memuat opsi filter';
                this.status = normalized.status;
            } finally {
                this.loading = false;
            }
        },
        async fetchOptions() {
            return this.fetchBaseOptions();
        },
        async searchProducts(search = '') {
            const requestId = ++latestProductSearchRequestId;
            this.loadingProducts = true;
            try {
                const response = await reportingApi.getFilterProductOptions({ search, per_page: 20 });
                if (requestId !== latestProductSearchRequestId) return;
                this.products = response.data.data || [];
            } catch (err) {
                if (requestId !== latestProductSearchRequestId) return;
                const normalized = normalizeApiError(err);
                this.error = normalized.message;
            } finally {
                if (requestId === latestProductSearchRequestId) {
                    this.loadingProducts = false;
                }
            }
        },
        async searchSuppliers(search = '') {
            const requestId = ++latestSupplierSearchRequestId;
            this.loadingSuppliers = true;
            this.supplierError = null;
            try {
                const response = await reportingApi.getFilterSupplierOptions({ search, per_page: 20 });
                if (requestId !== latestSupplierSearchRequestId) return;
                this.suppliers = response.data.data || [];
            } catch (err) {
                if (requestId !== latestSupplierSearchRequestId) return;
                const normalized = normalizeApiError(err);
                this.supplierError = normalized.status === 403
                    ? 'Akses daftar supplier tidak diizinkan.'
                    : (normalized.message || 'Gagal memuat daftar supplier.');
            } finally {
                if (requestId === latestSupplierSearchRequestId) {
                    this.loadingSuppliers = false;
                }
            }
        },
        resetProducts() {
            latestProductSearchRequestId++;
            this.products = [];
            this.loadingProducts = false;
        },
        resetSuppliers() {
            latestSupplierSearchRequestId++;
            this.suppliers = [];
            this.supplierError = null;
            this.loadingSuppliers = false;
        },
    },
});
