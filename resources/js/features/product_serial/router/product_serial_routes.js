export const productSerialRoutes = [
    {
        path: '/inventory/product-serials',
        name: 'inventory.product-serials',
        component: () => import('../pages/ProductSerialPage.vue'),
        meta: {
            title: 'Pelacakan Serial Number',
            requiresAuth: true,
            permission: 'product_serials.view',
        },
    },
];
