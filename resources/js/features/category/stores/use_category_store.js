import { defineStore } from 'pinia';
import { ref } from 'vue';
import { categoryApi } from '../api/category_api.js';
import { normalizeApiError } from '@shared/api/api_client.js';

export const useCategoryStore = defineStore('category', () => {
    const items = ref([]);
    const pagination = ref(null);
    const isLoading = ref(false);
    const error = ref(null);
    const validationErrors = ref({});
    const successMessage = ref(null);

    async function fetchAll(params = {}) {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await categoryApi.getAll(params);
            items.value = response.data.data;
            pagination.value = response.data.meta ?? null;
        } catch (err) {
            const normalized = normalizeApiError(err);
            if (normalized.status === 403) {
                error.value = 'Anda tidak memiliki izin untuk melihat data kategori.';
            } else {
                error.value = normalized.message;
            }
        } finally {
            isLoading.value = false;
        }
    }

    async function create(data) {
        isLoading.value = true;
        error.value = null;
        validationErrors.value = {};
        successMessage.value = null;
        try {
            const response = await categoryApi.create(data);
            successMessage.value = response.data.message;
            return response.data.data;
        } catch (err) {
            const normalized = normalizeApiError(err);
            if (normalized.status === 422) {
                validationErrors.value = normalized.errors;
            }
            error.value = normalized.message;
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function update(id, data) {
        isLoading.value = true;
        error.value = null;
        validationErrors.value = {};
        successMessage.value = null;
        try {
            const response = await categoryApi.update(id, data);
            successMessage.value = response.data.message;
            return response.data.data;
        } catch (err) {
            const normalized = normalizeApiError(err);
            if (normalized.status === 422) {
                validationErrors.value = normalized.errors;
            }
            error.value = normalized.message;
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function changeStatus(id, isActive) {
        isLoading.value = true;
        error.value = null;
        successMessage.value = null;
        try {
            const response = await categoryApi.changeStatus(id, isActive);
            successMessage.value = response.data.message;
            return response.data.data;
        } catch (err) {
            error.value = normalizeApiError(err).message;
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    function clearErrors() {
        error.value = null;
        validationErrors.value = {};
    }

    function clearSuccess() {
        successMessage.value = null;
    }

    return {
        items,
        pagination,
        isLoading,
        error,
        validationErrors,
        successMessage,
        fetchAll,
        create,
        update,
        changeStatus,
        clearErrors,
        clearSuccess,
    };
});
