import InventoryBalanceReportPage from '../pages/InventoryBalanceReportPage.vue';
import LowStockReportPage from '../pages/LowStockReportPage.vue';
import StockCardReportPage from '../pages/StockCardReportPage.vue';

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
        path: '/reports/stock-card',
        name: 'reports.stock-card',
        component: StockCardReportPage,
        meta: {
            requiresAuth: true,
            permission: 'reports.stock_card.view',
        },
    },
];
