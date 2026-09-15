import apiClient from '@/shared/api/api_client';

export const monthEndApi = {
    /**
     * Mengambil daftar seluruh periode tutup buku
     */
    getPeriods: () => apiClient.get('/month-end/periods'),

    /**
     * Mengambil ringkasan periode & deteksi transaksi draft (pre-closing check)
     * @param {{ year?: number, month?: number }} params
     */
    getSummary: (params = {}) => apiClient.get('/month-end/summary', { params }),

    /**
     * Mengambil rincian snapshot saldo per produk & lokasi
     * @param {number|string} periodId
     * @param {{ location_id?: number, condition?: string, search?: string, per_page?: number, page?: number }} params
     */
    getSnapshots: (periodId, params = {}) => apiClient.get(`/month-end/periods/${periodId}/snapshots`, { params }),

    /**
     * Eksekusi penutupan buku periode bulanan
     * @param {{ year: number, month: number, notes?: string, force?: boolean }} data
     */
    closePeriod: (data) => apiClient.post('/month-end/periods/close', data),

    /**
     * Eksekusi pembukaan kembali (re-open) periode yang telah dikunci
     * @param {number|string} periodId
     * @param {{ reopen_reason: string }} data
     */
    reopenPeriod: (periodId, data) => apiClient.post(`/month-end/periods/${periodId}/reopen`, data),
};
