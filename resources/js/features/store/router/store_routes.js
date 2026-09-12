export const storeRoutes = [
    {
        path: '/stores',
        name: 'stores.index',
        component: () => import('../pages/StorePage.vue'),
        meta: {
            requiresAuth: true,
            permission: 'stores.view',
            title: 'Master Toko'
        }
    }
];
