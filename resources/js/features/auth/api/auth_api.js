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
     * Memperbarui identitas profil (nama & email).
     *
     * @param {Object} data
     * @param {string} data.name
     * @param {string} data.email
     */
    updateProfile(data) {
        return apiClient.patch('/auth/profile', data);
    },

    /**
     * Memperbarui kata sandi pengguna.
     *
     * @param {Object} data
     * @param {string} data.current_password
     * @param {string} data.password
     * @param {string} data.password_confirmation
     */
    updatePassword(data) {
        return apiClient.patch('/auth/profile/password', data);
    },

    /**
     * Keluar dari sistem (invalidate session).
     */
    logout() {
        return apiClient.post('/auth/logout');
    },
};
