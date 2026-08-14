export const masterNavLinks = [
    { to: '/products', label: 'Produk', permission: 'products.view' },
    { to: '/categories', label: 'Kategori', permission: 'categories.view' },
    { to: '/units', label: 'Satuan', permission: 'units.view' },
    { to: '/suppliers', label: 'Supplier', permission: 'suppliers.view' },
    { to: '/locations', label: 'Lokasi', permission: 'locations.view' },
];

export const inventoryNavLinks = [
    { to: '/inventory/movements', label: 'Riwayat Pergerakan', permission: 'inventory.movements.view' },
    { to: '/inventory/receipts', label: 'Penerimaan Stok', permission: 'stock_receipts.view' },
    { to: '/inventory/issues', label: 'Pengeluaran Stok', permission: 'stock_issues.view' },
    { to: '/inventory/transfers', label: 'Transfer Stok', permission: 'stock_transfers.view' },
    { to: '/inventory/replenishment', label: 'Rekomendasi Reorder', permission: 'replenishment.view' },
    { to: '/inventory/adjustments', label: 'Penyesuaian Stok', permission: 'stock_adjustments.view' },
    { to: '/inventory/opnames', label: 'Stock Opname', permission: 'stock_opnames.view' },
];

export const inventoryReportNavLinks = [
    { to: '/reports/inventory-balances', label: 'Saldo Stok', permission: 'reports.inventory_balance.view' },
    { to: '/reports/low-stock', label: 'Stok Minimum', permission: 'reports.low_stock.view' },
    { to: '/reports/stock-card', label: 'Kartu Stok', permission: 'reports.stock_card.view' },
];

export const transactionReportNavLinks = [
    { to: '/reports/stock-receipts', label: 'Penerimaan Stok', permission: 'reports.stock_receipts.view' },
    { to: '/reports/stock-issues', label: 'Pengeluaran Stok', permission: 'reports.stock_issues.view' },
    { to: '/reports/stock-transfers', label: 'Transfer Stok', permission: 'reports.stock_transfers.view' },
    { to: '/reports/stock-adjustments', label: 'Stock Adjustment', permission: 'reports.stock_adjustments.view' },
    { to: '/reports/stock-opnames', label: 'Hasil Stock Opname', permission: 'reports.stock_opnames.view' },
];

export const masterPermissions = masterNavLinks.map((item) => item.permission);
export const inventoryPermissions = inventoryNavLinks.map((item) => item.permission);
export const inventoryReportPermissions = inventoryReportNavLinks.map((item) => item.permission);
export const transactionReportPermissions = transactionReportNavLinks.map((item) => item.permission);

export const reportPermissions = [
    ...inventoryReportPermissions,
    ...transactionReportPermissions,
];

export function hasAnyPermission(authStore, permissions) {
    return permissions.some((permission) => authStore.hasPermission(permission));
}
