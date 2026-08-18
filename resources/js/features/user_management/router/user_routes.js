import AppLayout from '@shared/layouts/AppLayout.vue';
import UserManagementPage from '../pages/UserManagementPage.vue';

export const userRoutes = [
    {
        path: '/users',
        component: AppLayout,
        meta: {
            requiresAuth: true,
            permission: 'users.manage',
        },
        children: [
            {
                path: '',
                name: 'users.index',
                component: UserManagementPage,
            },
        ],
    },
];
