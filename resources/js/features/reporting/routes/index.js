import InventoryBalanceReportPage from '../pages/InventoryBalanceReportPage.vue';
import InventoryMovementReportPage from '../pages/InventoryMovementReportPage.vue';
import LowStockReportPage from '../pages/LowStockReportPage.vue';
import StockCardReportPage from '../pages/StockCardReportPage.vue';
import StockReceiptReportPage from '../pages/StockReceiptReportPage.vue';
import StockIssueReportPage from '../pages/StockIssueReportPage.vue';
import StockTransferReportPage from '../pages/StockTransferReportPage.vue';
import StockAdjustmentReportPage from '../pages/StockAdjustmentReportPage.vue';
import StockOpnameReportPage from '../pages/StockOpnameReportPage.vue';

export const reportingRoutes = [
    {
        path: '/reports/inventory-balances',
        name: 'reports.inventory-balances',
        component: InventoryBalanceReportPage,
        meta: {
            requiresAuth: true,
            permission: 'reports.inventory_balance.view',
        },
    },
    {
        path: '/reports/low-stock',
        name: 'reports.low-stock',
        component: LowStockReportPage,
        meta: {
            requiresAuth: true,
            permission: 'reports.low_stock.view',
        },
    },
    {
        path: '/reports/inventory-movement',
        name: 'reports.inventory-movement',
        component: InventoryMovementReportPage,
        meta: {
            requiresAuth: true,
            permission: 'reports.inventory_movement.view',
        },
    },
    {
        path: '/reports/stock-card',
        name: 'reports.stock-card',
        component: StockCardReportPage,
        meta: {
            requiresAuth: true,
            permission: 'reports.stock_card.view',
        },
    },
    {
        path: '/reports/stock-receipts',
        name: 'reports.stock-receipts',
        component: StockReceiptReportPage,
        meta: {
            requiresAuth: true,
            permission: 'reports.stock_receipts.view',
        },
    },
    {
        path: '/reports/stock-issues',
        name: 'reports.stock-issues',
        component: StockIssueReportPage,
        meta: {
            requiresAuth: true,
            permission: 'reports.stock_issues.view',
        },
    },
    {
        path: '/reports/stock-transfers',
        name: 'reports.stock-transfers',
        component: StockTransferReportPage,
        meta: {
            requiresAuth: true,
            permission: 'reports.stock_transfers.view',
        },
    },
    {
        path: '/reports/stock-adjustments',
        name: 'reports.stock-adjustments',
        component: StockAdjustmentReportPage,
        meta: {
            requiresAuth: true,
            permission: 'reports.stock_adjustments.view',
        },
    },
    {
        path: '/reports/stock-opnames',
        name: 'reports.stock-opnames',
        component: StockOpnameReportPage,
        meta: {
            requiresAuth: true,
            permission: 'reports.stock_opnames.view',
        },
    },
];
