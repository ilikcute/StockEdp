import { defineStore } from 'pinia';
import { ref } from 'vue';
import { productSerialApi } from '../api/productSerialApi';

export const useProductSerialStore = defineStore('productSerial', () => {
    const serials = ref({ data: [], meta: {} });
    const selectedSerial = ref(null);
    const loading = ref(false);
    const detailLoading = ref(false);
    const error = ref(null);
    const lookupError = ref(null);

    async function fetchSerials(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await productSerialApi.getAll(params);
            serials.value = {
                data: response.data.data || [],
                meta: response.data.meta || {},
            };
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal memuat data serial number';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchSerialDetail(idOrNumber) {
        detailLoading.value = true;
        error.value = null;
        try {
            const response = await productSerialApi.getByIdOrNumber(idOrNumber);
            selectedSerial.value = response.data.data;
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Gagal memuat detail serial number';
            throw err;
        } finally {
            detailLoading.value = false;
        }
    }

    async function lookupSerial(sn) {
        detailLoading.value = true;
        lookupError.value = null;
        try {
            const response = await productSerialApi.lookup(sn);
            selectedSerial.value = response.data.data;
            return response.data.data;
        } catch (err) {
            lookupError.value = err.response?.data?.message || `Serial number "${sn}" tidak ditemukan.`;
            throw err;
        } finally {
            detailLoading.value = false;
        }
    }

    function clearSelectedSerial() {
        selectedSerial.value = null;
        lookupError.value = null;
    }

    return {
        serials,
        selectedSerial,
        loading,
        detailLoading,
        error,
        lookupError,
        fetchSerials,
        fetchSerialDetail,
        lookupSerial,
        clearSelectedSerial,
    };
});
