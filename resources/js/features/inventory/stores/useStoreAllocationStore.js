import { defineStore } from 'pinia';
import { ref } from 'vue';
import { storeAllocationApi } from '../api/store_allocation_api';

export const useStoreAllocationStore = defineStore('storeAllocation', () => {
    const allocations = ref({ data: [], meta: {} });
    const currentAllocation = ref(null);
    const loading = ref(false);
    const error = ref(null);

    async function fetchAllocations(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await storeAllocationApi.getAll(params);
            allocations.value = response.data;
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal memuat daftar alokasi toko';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchAllocationById(id) {
        loading.value = true;
        error.value = null;
        try {
            const response = await storeAllocationApi.getById(id);
            currentAllocation.value = response.data.data;
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal memuat detail alokasi toko';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createAllocation(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await storeAllocationApi.create(data);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal membuat alokasi toko';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    return {
        allocations,
        currentAllocation,
        loading,
        error,
        fetchAllocations,
        fetchAllocationById,
        createAllocation,
    };
});
