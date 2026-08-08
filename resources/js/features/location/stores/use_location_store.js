import { defineStore } from 'pinia';
import { ref } from 'vue';
import { locationApi } from '../api/location_api';

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
            items.value = response.data || [];
            pagination.value = response.meta || {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
            };
        } catch (err) {
            error.value = err.message || 'Gagal memuat data lokasi';
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
            successMessage.value = response.message;
            return true;
        } catch (err) {
            if (err.errors) {
                validationErrors.value = err.errors;
            } else {
                error.value = err.message || 'Gagal membuat lokasi';
            }
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
            successMessage.value = response.message;
            return true;
        } catch (err) {
            if (err.errors) {
                validationErrors.value = err.errors;
            } else {
                error.value = err.message || 'Gagal memperbarui lokasi';
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
            const response = await locationApi.changeStatus(id, isActive);
            successMessage.value = response.message;
            return true;
        } catch (err) {
            error.value = err.message || 'Gagal mengubah status lokasi';
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
