import CategoryPage from '../pages/CategoryPage.vue';

export const categoryRoutes = [
    {
        path: '/categories',
        name: 'categories',
        component: CategoryPage,
        meta: {
            requiresAuth: true,
            permission: 'categories.view',
        },
    },
];
