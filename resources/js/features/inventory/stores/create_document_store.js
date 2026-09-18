import { defineStore } from 'pinia';

function normalizeListPayload(raw, params) {
    let data = [];
    let meta = {};
    if (raw && raw.data && Array.isArray(raw.data.data)) {
        data = raw.data.data;
        meta = raw.data.meta || {};
    } else if (raw && Array.isArray(raw.data)) {
        data = raw.data;
        meta = raw.meta || {};
    } else if (Array.isArray(raw)) {
        data = raw;
    }

    const page = Number(meta.current_page) || Number(params.page) || 1;
    const perPage = Number(meta.per_page) || Number(params.per_page) || 15;
    const total = Number(meta.total) || data.length;
    const from = meta.from !== undefined && meta.from !== null ? Number(meta.from) : (total > 0 ? (page - 1) * perPage + 1 : null);
    const to = meta.to !== undefined && meta.to !== null ? Number(meta.to) : (total > 0 ? Math.min(page * perPage, total) : null);

    return {
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
}

export function createDocumentStore(storeId, config) {
    const { collectionKey, currentKey, api, messages } = config;

    return defineStore(storeId, {
        state: () => ({
            [collectionKey]: { data: [], meta: {} },
            [currentKey]: null,
            loading: false,
            error: null,
        }),

        actions: {
            async fetchList(params = {}) {
                this.loading = true;
                this.error = null;
                try {
                    const response = await api.list(params);
                    this[collectionKey] = normalizeListPayload(response.data, params);
                } catch (error) {
                    this.error = error.response?.data?.message || messages.listError;
                    throw error;
                } finally {
                    this.loading = false;
                }
            },

            async fetchById(id) {
                this.loading = true;
                this.error = null;
                try {
                    const response = await api.byId(id);
                    this[currentKey] = response.data.data;
                    return this[currentKey];
                } catch (error) {
                    this.error = error.response?.data?.message || messages.detailError;
                    throw error;
                } finally {
                    this.loading = false;
                }
            },

            async create(data) {
                this.loading = true;
                this.error = null;
                try {
                    const response = await api.create(data);
                    return response.data;
                } catch (error) {
                    this.error = error.response?.data?.message || messages.createError;
                    throw error;
                } finally {
                    this.loading = false;
                }
            },

            async update(id, data) {
                this.loading = true;
                this.error = null;
                try {
                    const response = await api.update(id, data);
                    return response.data;
                } catch (error) {
                    this.error = error.response?.data?.message || messages.updateError;
                    throw error;
                } finally {
                    this.loading = false;
                }
            },

            async post(id) {
                this.loading = true;
                this.error = null;
                try {
                    const response = await api.post(id);
                    if (this[currentKey] && this[currentKey].id === id) {
                        this[currentKey] = response.data.data;
                    }
                    return response.data;
                } catch (error) {
                    this.error = error.response?.data?.message || messages.postError;
                    throw error;
                } finally {
                    this.loading = false;
                }
            },

            async cancel(id) {
                this.loading = true;
                this.error = null;
                try {
                    const response = await api.cancel(id);
                    if (this[currentKey] && this[currentKey].id === id) {
                        this[currentKey] = response.data.data;
                    }
                    return response.data;
                } catch (error) {
                    this.error = error.response?.data?.message || messages.cancelError;
                    throw error;
                } finally {
                    this.loading = false;
                }
            },

            async deleteDocument(id) {
                this.loading = true;
                this.error = null;
                try {
                    const response = await api.delete(id);
                    return response.data;
                } catch (error) {
                    this.error = error.response?.data?.message || messages.deleteError || 'Gagal menghapus dokumen';
                    throw error;
                } finally {
                    this.loading = false;
                }
            },
        },
    });
}