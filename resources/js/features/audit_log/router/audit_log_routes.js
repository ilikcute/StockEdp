export const auditLogRoutes = [
    {
        path: '/audit-logs',
        name: 'audit-logs.index',
        component: () => import('../pages/AuditLogPage.vue'),
        meta: {
            title: 'Log Aktivitas Sistem',
            requiresAuth: true,
            permission: 'audit_logs.view',
        },
    },
];
