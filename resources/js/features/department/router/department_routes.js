export const departmentRoutes = [
    {
        path: '/departments',
        name: 'departments.index',
        component: () => import('../pages/DepartmentPage.vue'),
        meta: {
            requiresAuth: true,
            permission: 'departments.view',
            title: 'Master Departemen',
        },
    },
];
