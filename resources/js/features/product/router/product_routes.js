export const productRoutes = [
    {
        path: '/products',
        name: 'products.index',
        component: () => import('../pages/ProductPage.vue'),
        meta: {
            requiresAuth: true,
            permission: 'products.view',
            title: 'Master Produk',
        },
    },
];
