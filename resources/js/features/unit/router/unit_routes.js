import UnitPage from '../pages/UnitPage.vue';

export const unitRoutes = [
    {
        path: '/units',
        name: 'units',
        component: UnitPage,
        meta: {
            requiresAuth: true,
            permission: 'units.manage',
        },
    },
];
