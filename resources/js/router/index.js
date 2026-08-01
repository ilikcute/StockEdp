import { createRouter, createWebHistory } from 'vue-router';
import NotFoundPage from '@shared/pages/NotFoundPage.vue';
import { authRoutes } from '../features/auth/router/auth_routes.js';
import { categoryRoutes } from '../features/category/router/category_routes.js';
import { unitRoutes } from '../features/unit/router/unit_routes.js';
import { supplierRoutes } from '../features/supplier/router/supplier_routes.js';
import { locationRoutes } from '../features/location/router/location_routes.js';
import { productRoutes } from '../features/product/router/product_routes.js';
import { inventoryRoutes } from '../features/inventory/routes/index.js';
import { useAuthStore } from '../features/auth/stores/use_auth_store.js';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        // Rute fitur
        ...authRoutes,
        ...categoryRoutes,
        ...unitRoutes,
        ...supplierRoutes,
        ...locationRoutes,
        ...productRoutes,
        ...inventoryRoutes,

        // Fallback default redirect / ke /profile
        {
            path: '/',
            redirect: '/profile',
        },

        // Catchall 404
        {
            path: '/:pathMatch(.*)*',
            name: 'not-found',
            component: NotFoundPage,
        },
    ],
});

let isInitialized = false;

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();

    // 1. Inisialisasi session hanya sekali saat loading awal aplikasi (refresh page)
    if (!isInitialized) {
        await authStore.initialize();
        isInitialized = true;
    }

    const isAuthenticated = authStore.isAuthenticated;

    // 2. Cek Route Guard untuk Guest-Only Pages (misal: /login)
    if (to.meta.requiresGuest && isAuthenticated) {
        return next('/profile');
    }

    // 3. Cek Route Guard untuk Protected Pages (misal: /profile)
    if (to.meta.requiresAuth && !isAuthenticated) {
        // Simpan halaman target sebelumnya untuk redirect aman setelah login
        return next({
            path: '/login',
            query: { redirect: to.fullPath },
        });
    }

    // 4. Cek Permission jika route memerlukan permission spesifik
    if (to.meta.permission && !authStore.hasPermission(to.meta.permission)) {
        return next('/forbidden');
    }

    next();
});

export default router;
