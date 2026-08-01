export const supplierRoutes = [
    {
        path: '/suppliers',
        name: 'suppliers.index',
        component: () => import('../pages/SupplierPage.vue'),
        meta: {
            requiresAuth: true,
            permission: 'suppliers.view',
            title: 'Master Supplier',
        },
    },
];
