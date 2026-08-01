import { defineStore } from 'pinia';
import { inventoryApi } from '../api/inventoryApi';

export const useInventoryStore = defineStore('inventory', {
    state: () => ({
        balances: {
            data: [],
            meta: null
        },
        movements: {
            data: [],
            meta: null
        },
        loading: false,
        error: null,
    }),
    
    actions: {
        async fetchBalances(params = {}) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.getBalances(params);
                this.balances = {
                    data: response.data.data,
                    meta: response.data.meta
                };
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal memuat saldo stok';
                throw error;
            } finally {
                this.loading = false;
            }
        },
        
        async fetchMovements(params = {}) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.getMovements(params);
                this.movements = {
                    data: response.data.data,
                    meta: response.data.meta
                };
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal memuat pergerakan stok';
                throw error;
            } finally {
                this.loading = false;
            }
        }
    }
});
