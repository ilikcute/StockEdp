export const userRoutes = [
    {
        path: '/users',
        name: 'users.index',
        component: () => import('../pages/UserManagementPage.vue'),
        meta: {
            requiresAuth: true,
            permission: 'users.manage',
            title: 'Pengelolaan Pengguna & Hak Akses',
        },
    },
];
