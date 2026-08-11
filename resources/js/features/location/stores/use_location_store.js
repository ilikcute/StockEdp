import { defineStore } from 'pinia';
import { ref } from 'vue';
import { locationApi } from '../api/location_api';
import { normalizeApiError } from '@shared/api/api_client.js';

export const useLocationStore = defineStore('location', () => {
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

    const fetchLocations = async (params = {}) => {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await locationApi.getAll(params);
            items.value = response.data?.data || [];
            pagination.value = response.data?.meta || {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
            };
        } catch (err) {
            const normalized = normalizeApiError(err);
            error.value = normalized.message || 'Gagal memuat data lokasi';
        } finally {
            isLoading.value = false;
        }
    };

    const createLocation = async (data) => {
        isLoading.value = true;
        validationErrors.value = {};
        error.value = null;
        try {
            const response = await locationApi.create(data);
            successMessage.value = response.data?.message || 'Lokasi berhasil dibuat.';
            return true;
        } catch (err) {
            const normalized = normalizeApiError(err);
            if (normalized.status === 422) {
                validationErrors.value = normalized.errors;
            }
            error.value = normalized.message || 'Gagal membuat lokasi';
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const updateLocation = async (id, data) => {
        isLoading.value = true;
        validationErrors.value = {};
        error.value = null;
        try {
            const response = await locationApi.update(id, data);
            successMessage.value = response.data?.message || 'Lokasi berhasil diperbarui.';
            return true;
        } catch (err) {
            const normalized = normalizeApiError(err);
            if (normalized.status === 422) {
                validationErrors.value = normalized.errors;
            }
            error.value = normalized.message || 'Gagal memperbarui lokasi';
            return false;
        } finally {
            isLoading.value = false;
        }
    };

    const changeStatus = async (id, isActive) => {
        isLoading.value = true;
        error.value = null;
        try {
            const response = await locationApi.changeStatus(id, isActive);
            successMessage.value = response.data?.message || 'Status lokasi berhasil diubah.';
            return true;
        } catch (err) {
            const normalized = normalizeApiError(err);
            error.value = normalized.message || 'Gagal mengubah status lokasi';
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
        fetchLocations,
        createLocation,
        updateLocation,
        changeStatus,
        clearMessages,
    };
});
