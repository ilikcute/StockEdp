import LoginPage from '../pages/LoginPage.vue';
import ProfilePage from '../pages/ProfilePage.vue';
import ForbiddenPage from '../../../shared/pages/ForbiddenPage.vue';

export const authRoutes = [
    {
        path: '/login',
        name: 'login',
        component: LoginPage,
        meta: {
            requiresGuest: true,
        },
    },
    {
        path: '/profile',
        name: 'profile',
        component: ProfilePage,
        meta: {
            requiresAuth: true,
        },
    },
    {
        path: '/forbidden',
        name: 'forbidden',
        component: ForbiddenPage,
        meta: {
            requiresAuth: true,
        },
    },
];

