/**
 * Shared API client untuk berkomunikasi dengan Laravel REST API.
 *
 * - baseURL diambil dari VITE_API_BASE_URL
 * - Timeout dari VITE_API_TIMEOUT_MS
 * - Komponen tidak boleh mengimpor file ini langsung;
 *   gunakan composable atau Pinia store sebagai perantara.
 */

import axios from 'axios';

const apiClient = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL ?? '/api/v1',
    timeout: Number(import.meta.env.VITE_API_TIMEOUT_MS ?? 10000),
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
    withCredentials: true,
    withXSRFToken: true,
});

// Response interceptor untuk menangani session expiration (401)
apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        // Jika status 401 dan bukan request inisialisasi auth/me, alihkan ke login
        if (error.response && error.response.status === 401) {
            const isAuthMeRequest = error.config.url.endsWith('/auth/me');
            if (!isAuthMeRequest) {
                // Hapus data session di level memory jika diperlukan, alihkan
                window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname);
            }
        }
        return Promise.reject(error);
    }
);

/**
 * Normalisasi error dari Axios menjadi objek yang konsisten.
 *
 * @param {import('axios').AxiosError} error
 * @returns {{ message: string, errors: Record<string, string[]>, status: number }}
 */
export function normalizeApiError(error) {
    if (error.response) {
        const { data, status } = error.response;
        return {
            message: data?.message ?? 'Permintaan tidak dapat diproses.',
            errors: data?.errors ?? {},
            status,
        };
    }

    if (error.request) {
        return {
            message: 'Tidak dapat terhubung ke server. Periksa koneksi jaringan.',
            errors: {},
            status: 0,
        };
    }

    return {
        message: error.message ?? 'Terjadi kesalahan yang tidak diketahui.',
        errors: {},
        status: 0,
    };
}

export default apiClient;
