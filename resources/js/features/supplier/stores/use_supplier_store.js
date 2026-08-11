import { defineStore } from 'pinia';
import { ref } from 'vue';
import { supplierApi } from '../api/supplier_api.js';
import { normalizeApiError } from '@shared/api/api_client.js';

export const useSupplierStore = defineStore('supplier', () => {
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

    const fetchSuppliers = async (params = {}) => {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await supplierApi.getAll(params);
            items.value = response.data?.data || [];
            pagination.value = response.data?.meta || {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
            };
        } catch (err) {
            const normalized = normalizeApiError(err);
            error.value = normalized.message || 'Gagal memuat data supplier';
        } finally {
            isLoading.value = false;
        }
    };

    const createSupplier = async (data) => {
        isLoading.value = true;
        validationErrors.value = {};
        error.value = null;
        try {
            const response = await supplierApi.create(data);
            successMessage.value = response.data?.message || 'Supplier berhasil dibuat.';
            return true;
        } catch (err) {
            const normalized = normalizeApiError(err);
            if (normalized.status === 422) {
                validationErrors.value = normalized.errors;
            }
            error.value = normalized.message || 'Gagal membuat supplier';
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const updateSupplier = async (id, data) => {
        isLoading.value = true;
        validationErrors.value = {};
        error.value = null;
        try {
            const response = await supplierApi.update(id, data);
            successMessage.value = response.data?.message || 'Supplier berhasil diperbarui.';
            return true;
        } catch (err) {
            const normalized = normalizeApiError(err);
            if (normalized.status === 422) {
                validationErrors.value = normalized.errors;
            }
            error.value = normalized.message || 'Gagal memperbarui supplier';
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const changeStatus = async (id, isActive) => {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await supplierApi.changeStatus(id, isActive);
            successMessage.value = response.data?.message || 'Status supplier berhasil diubah.';
            return true;
        } catch (err) {
            const normalized = normalizeApiError(err);
            error.value = normalized.message || 'Gagal mengubah status supplier';
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
        fetchSuppliers,
        createSupplier,
        updateSupplier,
        changeStatus,
        clearMessages,
    };
});
