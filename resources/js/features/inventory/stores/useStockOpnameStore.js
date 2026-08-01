import { defineStore } from 'pinia';
import { stockOpnameApi } from '../api/stockOpnameApi';
import { normalizeApiError } from '@/shared/api/api_client';

export const useStockOpnameStore = defineStore('stockOpname', {
    state: () => ({
        // List & pagination
        opnames: { data: [], meta: {} },
        loadingList: false,

        // Detail
        currentOpname: null,
        loadingDetail: false,

        // Per-action loading flags (document-level)
        loadingAction: {
            start: false,
            complete: false,
            reopen: false,
            post: false,
            cancel: false,
            save: false,
        },

        // Per-item counting loading (keyed by item id)
        loadingItemCount: {},

        // Errors
        error: null,
        status: null,
        validationErrors: {},
        conflictError: null,

        // Count conflict state (per item)
        // { itemId: { savedInput: '...', serverQuantity: '...' } }
        countConflicts: {},
    }),

    getters: {
        currentItems: (state) => state.currentOpname?.items ?? [],
        abilities: (state) => state.currentOpname?.abilities ?? {},
    },

    actions: {
        // ──────────────────────────────────────────────
        // LIST
        // ──────────────────────────────────────────────
        async fetchOpnames(params = {}) {
            this.loadingList = true;
            this.error = null;
            try {
                const response = await stockOpnameApi.getOpnames(params);
                this.opnames = response.data.data;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                throw error;
            } finally {
                this.loadingList = false;
            }
        },

        // ──────────────────────────────────────────────
        // DETAIL
        // ──────────────────────────────────────────────
        async fetchOpname(id) {
            this.loadingDetail = true;
            this.error = null;
            try {
                const response = await stockOpnameApi.getOpname(id);
                this.currentOpname = response.data.data;
                return this.currentOpname;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                throw error;
            } finally {
                this.loadingDetail = false;
            }
        },

        // ──────────────────────────────────────────────
        // CREATE / UPDATE
        // ──────────────────────────────────────────────
        async createOpname(data) {
            this.loadingAction.save = true;
            this.error = null;
            this.validationErrors = {};
            try {
                const response = await stockOpnameApi.createOpname(data);
                return response.data;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.validationErrors = normalized.errors ?? {};
                this.status = normalized.status;
                throw error;
            } finally {
                this.loadingAction.save = false;
            }
        },

        async updateOpname(id, data) {
            this.loadingAction.save = true;
            this.error = null;
            this.validationErrors = {};
            try {
                const response = await stockOpnameApi.updateOpname(id, data);
                return response.data;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.validationErrors = normalized.errors ?? {};
                this.status = normalized.status;
                throw error;
            } finally {
                this.loadingAction.save = false;
            }
        },

        // ──────────────────────────────────────────────
        // START
        // ──────────────────────────────────────────────
        async startOpname(id) {
            this.loadingAction.start = true;
            this.error = null;
            try {
                await stockOpnameApi.startOpname(id);
                await this.fetchOpname(id);
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                if (normalized.status === 409) {
                    await this.fetchOpname(id);
                }
                throw error;
            } finally {
                this.loadingAction.start = false;
            }
        },

        // ──────────────────────────────────────────────
        // COUNT ITEM
        // ──────────────────────────────────────────────
        async saveItemCount(opnameId, itemId, payload) {
            this.loadingItemCount = { ...this.loadingItemCount, [itemId]: true };
            // Clear previous conflict for this item
            const conflicts = { ...this.countConflicts };
            delete conflicts[itemId];
            this.countConflicts = conflicts;

            try {
                const response = await stockOpnameApi.saveItemCount(opnameId, itemId, payload);
                const updatedItem = response.data.data;

                // Update the item in currentOpname.items directly (no optimistic)
                if (this.currentOpname?.items) {
                    this.currentOpname = {
                        ...this.currentOpname,
                        items: this.currentOpname.items.map((item) =>
                            item.id === itemId ? updatedItem : item,
                        ),
                    };
                }

                return updatedItem;
            } catch (error) {
                const normalized = normalizeApiError(error);

                if (normalized.status === 409) {
                    // Optimistic concurrency conflict – store pending input for UX
                    this.countConflicts = {
                        ...this.countConflicts,
                        [itemId]: {
                            userInput: payload.counted_quantity,
                            code: normalized.errors?.code ?? 'COUNT_VERSION_CONFLICT',
                        },
                    };
                    // Reload authoritative data from server
                    await this.fetchOpname(opnameId);
                } else {
                    this.error = normalized.message;
                    this.status = normalized.status;
                }

                throw error;
            } finally {
                this.loadingItemCount = { ...this.loadingItemCount, [itemId]: false };
            }
        },

        resolveCountConflict(itemId) {
            const conflicts = { ...this.countConflicts };
            delete conflicts[itemId];
            this.countConflicts = conflicts;
        },

        // ──────────────────────────────────────────────
        // ADD UNEXPECTED PRODUCT
        // ──────────────────────────────────────────────
        async addUnexpectedProduct(opnameId, payload) {
            this.loadingAction.save = true;
            this.error = null;
            this.validationErrors = {};
            try {
                const response = await stockOpnameApi.addUnexpectedProduct(opnameId, payload);
                await this.fetchOpname(opnameId);
                return response.data;
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.validationErrors = normalized.errors ?? {};
                this.status = normalized.status;
                if (normalized.status === 409) {
                    await this.fetchOpname(opnameId);
                }
                throw error;
            } finally {
                this.loadingAction.save = false;
            }
        },

        // ──────────────────────────────────────────────
        // COMPLETE
        // ──────────────────────────────────────────────
        async completeOpname(id) {
            this.loadingAction.complete = true;
            this.error = null;
            try {
                await stockOpnameApi.completeOpname(id);
                await this.fetchOpname(id);
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                if (normalized.status === 409) {
                    await this.fetchOpname(id);
                }
                throw error;
            } finally {
                this.loadingAction.complete = false;
            }
        },

        // ──────────────────────────────────────────────
        // REOPEN
        // ──────────────────────────────────────────────
        async reopenOpname(id, payload) {
            this.loadingAction.reopen = true;
            this.error = null;
            this.validationErrors = {};
            try {
                await stockOpnameApi.reopenOpname(id, payload);
                await this.fetchOpname(id);
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.validationErrors = normalized.errors ?? {};
                this.status = normalized.status;
                throw error;
            } finally {
                this.loadingAction.reopen = false;
            }
        },

        // ──────────────────────────────────────────────
        // POST
        // ──────────────────────────────────────────────
        async postOpname(id) {
            this.loadingAction.post = true;
            this.error = null;
            try {
                await stockOpnameApi.postOpname(id);
                await this.fetchOpname(id);
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.status = normalized.status;
                if (normalized.status === 409) {
                    await this.fetchOpname(id);
                }
                throw error;
            } finally {
                this.loadingAction.post = false;
            }
        },

        // ──────────────────────────────────────────────
        // CANCEL
        // ──────────────────────────────────────────────
        async cancelOpname(id, payload) {
            this.loadingAction.cancel = true;
            this.error = null;
            this.validationErrors = {};
            try {
                await stockOpnameApi.cancelOpname(id, payload);
                await this.fetchOpname(id);
            } catch (error) {
                const normalized = normalizeApiError(error);
                this.error = normalized.message;
                this.validationErrors = normalized.errors ?? {};
                this.status = normalized.status;
                throw error;
            } finally {
                this.loadingAction.cancel = false;
            }
        },

        // ──────────────────────────────────────────────
        // RESET
        // ──────────────────────────────────────────────
        resetErrors() {
            this.error = null;
            this.validationErrors = {};
            this.conflictError = null;
            this.countConflicts = {};
            this.status = null;
        },

        resetActiveOpname() {
            this.currentOpname = null;
            this.countConflicts = {};
            this.loadingItemCount = {};
            this.resetErrors();
        },
    },
});
