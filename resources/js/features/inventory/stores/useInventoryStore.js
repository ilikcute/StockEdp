import { defineStore } from 'pinia';
import { inventoryApi } from '../api/inventoryApi';

export const useInventoryStore = defineStore('inventory', {
    state: () => ({
        movements: {
            data: [],
            meta: null
        },
        selectedMovement: null,
        movementDetailLoading: false,
        loading: false,
        error: null,
    }),
    
    actions: {
        async fetchMovements(params = {}) {
            this.loading = true;
            this.error = null;
            try {
                const response = await inventoryApi.getMovements(params);
                const data = response.data?.data || [];
                const meta = response.data?.meta || {};
                const page = Number(meta.current_page) || Number(params.page) || 1;
                const perPage = Number(meta.per_page) || Number(params.per_page) || 15;
                const total = Number(meta.total) || data.length;
                const from = meta.from !== undefined && meta.from !== null ? Number(meta.from) : (total > 0 ? (page - 1) * perPage + 1 : null);
                const to = meta.to !== undefined && meta.to !== null ? Number(meta.to) : (total > 0 ? Math.min(page * perPage, total) : null);

                this.movements = {
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
                this.error = error.response?.data?.message || 'Gagal memuat pergerakan stok';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async fetchMovementById(id) {
            this.movementDetailLoading = true;
            this.selectedMovement = null;
            try {
                const response = await inventoryApi.getMovementById(id);
                this.selectedMovement = response.data?.data || response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Gagal memuat detail pergerakan stok';
                throw error;
            } finally {
                this.movementDetailLoading = false;
            }
        },
    }
});
