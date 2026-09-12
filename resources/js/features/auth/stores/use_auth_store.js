import { defineStore } from 'pinia';
import { authApi } from '../api/auth_api.js';
import { normalizeApiError } from '@shared/api/api_client.js';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        isAuthenticated: false,
        isLoading: false,
        error: null,
        validationErrors: {},
    }),

    getters: {
        isAdmin: (state) => {
            return state.user?.roles?.includes('ADMIN') ?? false;
        },
    },

    actions: {
        /**
         * Menugaskan state user.
         */
        setUser(user) {
            this.user = user;
            this.isAuthenticated = !!user;
            this.error = null;
            this.validationErrors = {};
        },

        /**
         * Pengecekan permission client-side (bukan keamanan utama).
         *
         * @param {string} permission
         * @returns {boolean}
         */
        hasPermission(permission) {
            if (this.isAdmin) {
                return true;
            }
            return this.user?.permissions?.includes(permission) ?? false;
        },

        /**
         * Inisialisasi session (mengambil CSRF dan profile user aktif saat refresh page).
         */
        async initialize() {
            this.isLoading = true;
            try {
                const response = await authApi.getMe();
                if (response.data?.success) {
                    this.setUser(response.data.data);
                }
            } catch {
                // Jangan lempar error jika 401 saat inisialisasi awal (guest)
                this.setUser(null);
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Melakukan login.
         */
        async login(credentials) {
            this.isLoading = true;
            this.error = null;
            this.validationErrors = {};

            try {
                // 1. Ambil CSRF cookie terlebih dahulu
                await authApi.getCsrfCookie();

                // 2. Kirim kredensial login
                const response = await authApi.login(credentials);

                if (response.data?.success) {
                    this.setUser(response.data.data.user);
                    return true;
                }
            } catch (err) {
                const normalized = normalizeApiError(err);
                if (normalized.status === 422) {
                    this.validationErrors = normalized.errors;
                    this.error = normalized.message;
                } else {
                    this.error = normalized.message;
                }
                throw err;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Melakukan logout.
         */
        async logout() {
            this.isLoading = true;
            try {
                await authApi.logout();
            } catch {
                // Abaikan error jaringan saat logout, tetap bersihkan state
            } finally {
                this.setUser(null);
                this.isLoading = false;
            }
        },
    },
});
