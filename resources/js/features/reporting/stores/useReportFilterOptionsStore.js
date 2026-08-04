import { defineStore } from 'pinia';
import { locationApi } from '@/features/location/api/location_api';
import { categoryApi } from '@/features/category/api/category_api';
import { unitApi } from '@/features/unit/api/unit_api';
import { productApi } from '@/features/product/api/product_api';
import { supplierApi } from '@/features/supplier/api/supplier_api';
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
    }),
    actions: {
        async fetchOptions() {
            this.loading = true;
            this.error = null;
            this.status = null;
            try {
                const [locRes, catRes, unitRes, supRes] = await Promise.all([
                    locationApi.getAll({ per_page: 100 }),
                    categoryApi.getAll({ per_page: 100 }),
                    unitApi.getAll({ per_page: 100 }),
                    supplierApi.getAll({ per_page: 100 }),
                ]);
                this.locations = locRes.data.data;
                this.categories = catRes.data.data;
                this.units = unitRes.data.data;
                this.suppliers = supRes.data.data;
                this.status = locRes.status;
            } catch (err) {
                const normalized = normalizeApiError(err);
                this.error = normalized.message || 'Gagal memuat opsi filter';
                this.status = normalized.status;
            } finally {
                this.loading = false;
            }
        },
        async searchProducts(search = '') {
            const requestId = ++latestProductSearchRequestId;
            this.loadingProducts = true;
            try {
                const response = await productApi.getAll({ search, per_page: 20 });
                if (requestId !== latestProductSearchRequestId) return;
                this.products = response.data.data;
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
            try {
                const response = await supplierApi.getAll({ search, per_page: 20 });
                if (requestId !== latestSupplierSearchRequestId) return;
                this.suppliers = response.data.data;
            } catch (err) {
                if (requestId !== latestSupplierSearchRequestId) return;
                const normalized = normalizeApiError(err);
                this.error = normalized.message;
            } finally {
                if (requestId === latestSupplierSearchRequestId) {
                    this.loadingSuppliers = false;
                }
            }
        },
    },
});
