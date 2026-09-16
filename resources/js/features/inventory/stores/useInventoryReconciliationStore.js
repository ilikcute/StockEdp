import { defineStore } from 'pinia';
import { inventoryReconciliationApi } from '../api/inventoryReconciliationApi';

export const useInventoryReconciliationStore = defineStore('inventoryReconciliation', {
    state: () => ({
        scanResults: {
            summary: {
                total_scanned_pairs: 0,
                matching_pairs: 0,
                discrepant_pairs: 0,
                status: 'SYNCED',
                scanned_at: null,
            },
            discrepancies: []
        },
        hasScanned: false,
        loadingScan: false,
        loadingApply: false,
        lastApplyResult: null,
        error: null,
        filters: {
            location_id: '',
            search: ''
        }
    }),

    getters: {
        filteredDiscrepancies(state) {
            let list = state.scanResults.discrepancies || [];
            if (!state.filters.search) {
                return list;
            }
            const q = state.filters.search.toLowerCase().trim();
            return list.filter(item => {
                const sku = (item.product_sku || '').toLowerCase();
                const name = (item.product_name || '').toLowerCase();
                const loc = (item.location_name || '').toLowerCase();
                return sku.includes(q) || name.includes(q) || loc.includes(q);
            });
        }
    },

    actions: {
        async runScan(customParams = {}) {
            this.loadingScan = true;
            this.error = null;
            try {
                const params = {
                    ...customParams
                };
                if (this.filters.location_id) {
                    params.location_id = this.filters.location_id;
                }

                const response = await inventoryReconciliationApi.scan(params);
                const raw = response.data?.data || {};
                this.scanResults = {
                    summary: raw.summary || {
                        total_scanned_pairs: 0,
                        matching_pairs: 0,
                        discrepant_pairs: 0,
                        status: 'SYNCED',
                        scanned_at: null,
                    },
                    discrepancies: raw.discrepancies || []
                };
                this.hasScanned = true;
                return this.scanResults;
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal memindai rekonsiliasi saldo stok.';
                throw err;
            } finally {
                this.loadingScan = false;
            }
        },

        async applyReconciliation(keys = null) {
            this.loadingApply = true;
            this.error = null;
            try {
                const payload = {};
                if (Array.isArray(keys) && keys.length > 0) {
                    payload.keys = keys;
                }

                const response = await inventoryReconciliationApi.apply(payload);
                this.lastApplyResult = response.data?.data || null;

                // Refresh scan after apply
                await this.runScan();

                return this.lastApplyResult;
            } catch (err) {
                this.error = err.response?.data?.message || 'Gagal menerapkan sinkronisasi saldo stok.';
                throw err;
            } finally {
                this.loadingApply = false;
            }
        },

        resetFilters() {
            this.filters.location_id = '';
            this.filters.search = '';
        }
    }
});

export default useInventoryReconciliationStore;
