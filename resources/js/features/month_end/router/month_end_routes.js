export const monthEndRoutes = [
    {
        path: '/inventory/month-end',
        name: 'inventory.month-end',
        component: () => import('../pages/MonthEndClosingPage.vue'),
        meta: {
            title: 'Tutup Buku Bulanan',
            requiresAuth: true,
            permission: 'month_end.view',
        },
    },
];
