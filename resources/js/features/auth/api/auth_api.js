import apiClient from '@shared/api/api_client.js';

export const authApi = {
    /**
     * Mengambil CSRF Cookie dari Laravel Sanctum untuk inisialisasi session.
     */
    getCsrfCookie() {
        return apiClient.get('/sanctum/csrf-cookie', { baseURL: '/' });
    },

    /**
     * Melakukan login.
     *
     * @param {Object} credentials
     * @param {string} credentials.login
     * @param {string} credentials.password
     */
    login(credentials) {
        return apiClient.post('/auth/login', credentials);
    },

    /**
     * Mengambil data profil user yang sedang login.
     */
    getMe() {
        return apiClient.get('/auth/me');
    },

    /**
     * Keluar dari sistem (invalidate session).
     */
    logout() {
        return apiClient.post('/auth/logout');
    },
};
