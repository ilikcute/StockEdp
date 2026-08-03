import { defineStore } from 'pinia';
import apiClient from '@/shared/api/api_client';

export const useReportFilterOptionsStore = defineStore('reportFilterOptions', {
    state: () => ({
        locations: [],
        categories: [],
        units: [],
        products: [], // Loaded on demand with search/limit
        loading: false,
        error: null,
    }),
    actions: {
        async fetchOptions() {
            this.loading = true;
            this.error = null;
            try {
                // Fetch locations, categories, units concurrently (usually they are small)
                const [locRes, catRes, unitRes] = await Promise.all([
                    apiClient.get('/locations', { params: { per_page: 100 } }),
                    apiClient.get('/categories', { params: { per_page: 100 } }),
                    apiClient.get('/units', { params: { per_page: 100 } })
                ]);
                this.locations = locRes.data.data || locRes.data;
                this.categories = catRes.data.data || catRes.data;
                this.units = unitRes.data.data || unitRes.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal memuat opsi filter';
            } finally {
                this.loading = false;
            }
        },
        async searchProducts(search = '') {
            try {
                const response = await apiClient.get('/products', { 
                    params: { search, per_page: 20 } 
                });
                this.products = response.data.data;
            } catch (err) {
                console.error('Failed to search products:', err);
            }
        }
    }
});
