export const inventoryRoutes = [
    {
        path: '/inventory/balances',
        name: 'inventory.balances',
        component: () => import('../pages/InventoryBalancePage.vue'),
        meta: {
            title: 'Saldo Stok',
            requiresAuth: true,
            permission: 'inventory.balances.view'
        }
    },
    {
        path: '/inventory/movements',
        name: 'inventory.movements',
        component: () => import('../pages/StockMovementPage.vue'),
        meta: {
            title: 'Riwayat Pergerakan Stok',
            requiresAuth: true,
            permission: 'inventory.movements.view'
        }
    },
    {
        path: '/inventory/receipts',
        name: 'inventory.receipts',
        component: () => import('../pages/StockReceiptListPage.vue'),
        meta: {
            title: 'Penerimaan Stok',
            requiresAuth: true,
            permission: 'stock_receipts.view'
        }
    },
    {
        path: '/inventory/receipts/create',
        name: 'inventory.receipts.create',
        component: () => import('../pages/StockReceiptFormPage.vue'),
        meta: {
            title: 'Buat Penerimaan Stok',
            requiresAuth: true,
            permission: 'stock_receipts.create'
        }
    },
    {
        path: '/inventory/receipts/:id',
        name: 'inventory.receipts.detail',
        component: () => import('../pages/StockReceiptDetailPage.vue'),
        meta: {
            title: 'Detail Penerimaan Stok',
            requiresAuth: true,
            permission: 'stock_receipts.view'
        }
    },
    {
        path: '/inventory/receipts/:id/edit',
        name: 'inventory.receipts.edit',
        component: () => import('../pages/StockReceiptFormPage.vue'),
        meta: {
            title: 'Edit Penerimaan Stok',
            requiresAuth: true,
            permission: 'stock_receipts.update'
        }
    },
    {
        path: '/inventory/issues',
        name: 'inventory.issues',
        component: () => import('../pages/StockIssueListPage.vue'),
        meta: {
            title: 'Pengeluaran Stok',
            requiresAuth: true,
            permission: 'stock_issues.view'
        }
    },
    {
        path: '/inventory/issues/create',
        name: 'inventory.issues.create',
        component: () => import('../pages/StockIssueFormPage.vue'),
        meta: {
            title: 'Buat Pengeluaran Stok',
            requiresAuth: true,
            permission: 'stock_issues.create'
        }
    },
    {
        path: '/inventory/issues/:id',
        name: 'inventory.issues.detail',
        component: () => import('../pages/StockIssueDetailPage.vue'),
        meta: {
            title: 'Detail Pengeluaran Stok',
            requiresAuth: true,
            permission: 'stock_issues.view'
        }
    },
    {
        path: '/inventory/issues/:id/edit',
        name: 'inventory.issues.edit',
        component: () => import('../pages/StockIssueFormPage.vue'),
        meta: {
            title: 'Edit Pengeluaran Stok',
            requiresAuth: true,
            permission: 'stock_issues.update'
        }
    },
    {
        path: '/inventory/transfers',
        name: 'inventory.transfers',
        component: () => import('../pages/StockTransferListPage.vue'),
        meta: {
            title: 'Transfer Stok',
            requiresAuth: true,
            permission: 'stock_transfers.view'
        }
    },
    {
        path: '/inventory/transfers/create',
        name: 'inventory.transfers.create',
        component: () => import('../pages/StockTransferFormPage.vue'),
        meta: {
            title: 'Buat Transfer Stok',
            requiresAuth: true,
            permission: 'stock_transfers.create'
        }
    },
    {
        path: '/inventory/transfers/:id',
        name: 'inventory.transfers.detail',
        component: () => import('../pages/StockTransferDetailPage.vue'),
        meta: {
            title: 'Detail Transfer Stok',
            requiresAuth: true,
            permission: 'stock_transfers.view'
        }
    },
    {
        path: '/inventory/transfers/:id/edit',
        name: 'inventory.transfers.edit',
        component: () => import('../pages/StockTransferFormPage.vue'),
        meta: {
            title: 'Edit Transfer Stok',
            requiresAuth: true,
            permission: 'stock_transfers.update'
        }
    },
    // ── Stock Opname ──────────────────────────────────────────
    {
        path: '/inventory/opnames',
        name: 'stockOpnames',
        component: () => import('../pages/StockOpnameListPage.vue'),
        meta: {
            title: 'Stock Opname',
            requiresAuth: true,
            permission: 'stock_opnames.view'
        }
    },
    {
        path: '/inventory/opnames/create',
        name: 'stockOpnamesCreate',
        component: () => import('../pages/StockOpnameFormPage.vue'),
        meta: {
            title: 'Buat Sesi Stock Opname',
            requiresAuth: true,
            permission: 'stock_opnames.create'
        }
    },
    {
        path: '/inventory/opnames/:id',
        name: 'stockOpnamesDetail',
        component: () => import('../pages/StockOpnameDetailPage.vue'),
        meta: {
            title: 'Detail Stock Opname',
            requiresAuth: true,
            permission: 'stock_opnames.view'
        }
    },
    {
        path: '/inventory/opnames/:id/edit',
        name: 'stockOpnamesEdit',
        component: () => import('../pages/StockOpnameFormPage.vue'),
        meta: {
            title: 'Edit Draft Stock Opname',
            requiresAuth: true,
            permission: 'stock_opnames.update'
        }
    },
    {
        path: '/inventory/opnames/:id/count',
        name: 'stockOpnamesCount',
        component: () => import('../pages/StockOpnameCountPage.vue'),
        meta: {
            title: 'Ruang Hitung — Stock Opname',
            requiresAuth: true,
            permission: 'stock_opnames.update'
        }
    }
];
