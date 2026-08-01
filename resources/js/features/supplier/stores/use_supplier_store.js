import { defineStore } from 'pinia';
import { ref } from 'vue';
import { supplierApi } from '../api/supplier_api.js';

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
            items.value = response.data;
            pagination.value = response.meta;
        } catch (err) {
            error.value = err.message || 'Gagal memuat data supplier';
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
            successMessage.value = response.message;
            return true;
        } catch (err) {
            if (err.errors) {
                validationErrors.value = err.errors;
            } else {
                error.value = err.message || 'Gagal membuat supplier';
            }
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
            successMessage.value = response.message;
            return true;
        } catch (err) {
            if (err.errors) {
                validationErrors.value = err.errors;
            } else {
                error.value = err.message || 'Gagal memperbarui supplier';
            }
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
            successMessage.value = response.message;
            return true;
        } catch (err) {
            error.value = err.message || 'Gagal mengubah status supplier';
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
