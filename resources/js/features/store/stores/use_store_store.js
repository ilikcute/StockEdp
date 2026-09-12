import { defineStore } from 'pinia';
import { ref } from 'vue';
import { storeApi } from '../api/store_api';
import { normalizeApiError } from '@shared/api/api_client.js';

export const useStoreStore = defineStore('storeFeature', () => {
    const items = ref([]);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });

    const isLoading = ref(false);
    const error = ref(null);
    const validationErrors = ref({});
    const successMessage = ref('');

    const fetchStores = async (params = {}) => {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await storeApi.getAll(params);
            items.value = response.data?.data || [];
            const meta = response.data?.meta || {};
            const page = Number(meta.current_page) || 1;
            const perPage = Number(meta.per_page) || 15;
            const total = Number(meta.total) || 0;
            const from = meta.from !== undefined && meta.from !== null ? Number(meta.from) : (total > 0 ? (page - 1) * perPage + 1 : null);
            const to = meta.to !== undefined && meta.to !== null ? Number(meta.to) : (total > 0 ? Math.min(page * perPage, total) : null);

            pagination.value = {
                current_page: page,
                last_page: Number(meta.last_page) || 1,
                per_page: perPage,
                total,
                from,
                to,
            };
        } catch (err) {
            const normalized = normalizeApiError(err);
            error.value = normalized.message || 'Gagal memuat data toko';
        } finally {
            isLoading.value = false;
        }
    };

    const createStore = async (data) => {
        isLoading.value = true;
        validationErrors.value = {};
        error.value = null;
        try {
            const response = await storeApi.create(data);
            successMessage.value = response.data?.message || 'Toko berhasil dibuat.';
            return true;
        } catch (err) {
            const normalized = normalizeApiError(err);
            if (normalized.status === 422) {
                validationErrors.value = normalized.errors;
            }
            error.value = normalized.message || 'Gagal membuat toko';
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const updateStore = async (id, data) => {
        isLoading.value = true;
        validationErrors.value = {};
        error.value = null;
        try {
            const response = await storeApi.update(id, data);
            successMessage.value = response.data?.message || 'Toko berhasil diperbarui.';
            return true;
        } catch (err) {
            const normalized = normalizeApiError(err);
            if (normalized.status === 422) {
                validationErrors.value = normalized.errors;
            }
            error.value = normalized.message || 'Gagal memperbarui toko';
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const changeStatus = async (id, isActive) => {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await storeApi.changeStatus(id, isActive);
            successMessage.value = response.data?.message || 'Status toko berhasil diubah.';
            return true;
        } catch (err) {
            const normalized = normalizeApiError(err);
            error.value = normalized.message || 'Gagal mengubah status toko';
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const clearMessages = () => {
        error.value = null;
        successMessage.value = '';
        validationErrors.value = {};
    };

    return {
        items,
        pagination,
        isLoading,
        error,
        validationErrors,
        successMessage,
        fetchStores,
        createStore,
        updateStore,
        changeStatus,
        clearMessages,
    };
});
