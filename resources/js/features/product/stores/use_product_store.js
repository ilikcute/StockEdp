import { defineStore } from 'pinia';
import { ref } from 'vue';
import { productApi } from '../api/product_api.js';
import { normalizeApiError } from '@shared/api/api_client.js';

export const useProductStore = defineStore('product', () => {
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

    const fetchProducts = async (params = {}) => {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await productApi.getAll(params);
            items.value = response.data?.data || [];
            pagination.value = response.data?.meta || {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
            };
        } catch (err) {
            const normalized = normalizeApiError(err);
            error.value = normalized.message || 'Gagal memuat data produk';
        } finally {
            isLoading.value = false;
        }
    };

    const createProduct = async (data) => {
        isLoading.value = true;
        validationErrors.value = {};
        error.value = null;
        try {
            const response = await productApi.create(data);
            successMessage.value = response.data?.message || 'Produk berhasil dibuat.';
            return true;
        } catch (err) {
            const normalized = normalizeApiError(err);
            if (normalized.status === 422) {
                validationErrors.value = normalized.errors;
            }
            error.value = normalized.message || 'Gagal membuat produk';
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const updateProduct = async (id, data) => {
        isLoading.value = true;
        validationErrors.value = {};
        error.value = null;
        try {
            const response = await productApi.update(id, data);
            successMessage.value = response.data?.message || 'Produk berhasil diperbarui.';
            return true;
        } catch (err) {
            const normalized = normalizeApiError(err);
            if (normalized.status === 422) {
                validationErrors.value = normalized.errors;
            }
            error.value = normalized.message || 'Gagal memperbarui produk';
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const changeStatus = async (id, isActive) => {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await productApi.changeStatus(id, isActive);
            successMessage.value = response.data?.message || 'Status produk berhasil diubah.';
            return true;
        } catch (err) {
            const normalized = normalizeApiError(err);
            error.value = normalized.message || 'Gagal mengubah status produk';
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
        fetchProducts,
        createProduct,
        updateProduct,
        changeStatus,
        clearMessages,
    };
});
