export const locationRoutes = [
    {
        path: '/locations',
        name: 'locations.index',
        component: () => import('../pages/LocationPage.vue'),
        meta: {
            requiresAuth: true,
            permission: 'locations.view',
            title: 'Master Lokasi'
        }
    }
];
