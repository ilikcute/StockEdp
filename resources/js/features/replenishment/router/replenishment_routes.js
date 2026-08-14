import ReplenishmentPage from '../pages/ReplenishmentPage.vue';

export const replenishmentRoutes = [
    {
        path: '/inventory/replenishment',
        name: 'inventory.replenishment',
        component: ReplenishmentPage,
        meta: {
            requiresAuth: true,
            permission: 'replenishment.view',
        },
    },
];

export default replenishmentRoutes;
