import DashboardPage from '../pages/DashboardPage.vue';

export const dashboardRoutes = [
    {
        path: '/dashboard',
        name: 'dashboard',
        component: DashboardPage,
        meta: {
            requiresAuth: true,
            permission: 'dashboard.view',
        },
    },
];

export default dashboardRoutes;
