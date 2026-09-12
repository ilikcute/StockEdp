<?php

namespace App\Features\Auth\Enums;

enum PermissionCode: string
{
    // Master Data
    case PRODUCTS_VIEW = 'products.view';
    case PRODUCTS_CREATE = 'products.create';
    case PRODUCTS_UPDATE = 'products.update';
    case PRODUCTS_CHANGE_STATUS = 'products.change_status';
    case PRODUCTS_IMPORT = 'products.import';
    case CATEGORIES_VIEW = 'categories.view';
    case CATEGORIES_CREATE = 'categories.create';
    case CATEGORIES_UPDATE = 'categories.update';
    case CATEGORIES_CHANGE_STATUS = 'categories.change_status';
    case CATEGORIES_IMPORT = 'categories.import';
    case UNITS_VIEW = 'units.view';
    case UNITS_CREATE = 'units.create';
    case UNITS_UPDATE = 'units.update';
    case UNITS_CHANGE_STATUS = 'units.change_status';
    case UNITS_IMPORT = 'units.import';
    case SUPPLIERS_VIEW = 'suppliers.view';
    case SUPPLIERS_CREATE = 'suppliers.create';
    case SUPPLIERS_UPDATE = 'suppliers.update';
    case SUPPLIERS_CHANGE_STATUS = 'suppliers.change_status';
    case LOCATIONS_VIEW = 'locations.view';
    case LOCATIONS_CREATE = 'locations.create';
    case LOCATIONS_UPDATE = 'locations.update';
    case LOCATIONS_CHANGE_STATUS = 'locations.change_status';
    case LOCATIONS_IMPORT = 'locations.import';

    // Transactions
    case INVENTORY_BALANCES_VIEW = 'inventory.balances.view';
    case INVENTORY_MOVEMENTS_VIEW = 'inventory.movements.view';

    case STOCK_RECEIPTS_VIEW = 'stock_receipts.view';
    case STOCK_RECEIPTS_CREATE = 'stock_receipts.create';
    case STOCK_RECEIPTS_UPDATE = 'stock_receipts.update';
    case STOCK_RECEIPTS_POST = 'stock_receipts.post';
    case STOCK_RECEIPTS_CANCEL = 'stock_receipts.cancel';

    case STOCK_ISSUES_VIEW = 'stock_issues.view';
    case STOCK_ISSUES_CREATE = 'stock_issues.create';
    case STOCK_ISSUES_UPDATE = 'stock_issues.update';
    case STOCK_ISSUES_POST = 'stock_issues.post';
    case STOCK_ISSUES_CANCEL = 'stock_issues.cancel';
    case STOCK_TRANSFERS_VIEW = 'stock_transfers.view';
    case STOCK_TRANSFERS_CREATE = 'stock_transfers.create';
    case STOCK_TRANSFERS_UPDATE = 'stock_transfers.update';
    case STOCK_TRANSFERS_SEND = 'stock_transfers.send';
    case STOCK_TRANSFERS_RECEIVE = 'stock_transfers.receive';
    case STOCK_TRANSFERS_CANCEL = 'stock_transfers.cancel';

    case STOCK_ADJUSTMENTS_VIEW = 'stock_adjustments.view';
    case STOCK_ADJUSTMENTS_CREATE = 'stock_adjustments.create';
    case STOCK_ADJUSTMENTS_UPDATE = 'stock_adjustments.update';
    case STOCK_ADJUSTMENTS_POST = 'stock_adjustments.post';
    case STOCK_ADJUSTMENTS_CANCEL = 'stock_adjustments.cancel';

    case STOCK_OPNAMES_VIEW = 'stock_opnames.view';
    case STOCK_OPNAMES_CREATE = 'stock_opnames.create';
    case STOCK_OPNAMES_UPDATE = 'stock_opnames.update';
    case STOCK_OPNAMES_START = 'stock_opnames.start';
    case STOCK_OPNAMES_COUNT = 'stock_opnames.count';
    case STOCK_OPNAMES_COMPLETE = 'stock_opnames.complete';
    case STOCK_OPNAMES_REOPEN = 'stock_opnames.reopen';
    case STOCK_OPNAMES_POST = 'stock_opnames.post';
    case STOCK_OPNAMES_CANCEL = 'stock_opnames.cancel';

    case INVENTORY_OPNAME = 'inventory.opname';

    // Dashboard & Replenishment
    case DASHBOARD_VIEW = 'dashboard.view';
    case REPLENISHMENT_VIEW = 'replenishment.view';

    // Reports & Users
    case REPORTS_VIEW = 'reports.view';
    case REPORTS_INVENTORY_BALANCE_VIEW = 'reports.inventory_balance.view';
    case REPORTS_LOW_STOCK_VIEW = 'reports.low_stock.view';
    case REPORTS_STOCK_CARD_VIEW = 'reports.stock_card.view';
    case REPORTS_STOCK_RECEIPTS_VIEW = 'reports.stock_receipts.view';
    case REPORTS_STOCK_ISSUES_VIEW = 'reports.stock_issues.view';
    case REPORTS_STOCK_TRANSFERS_VIEW = 'reports.stock_transfers.view';
    case REPORTS_STOCK_ADJUSTMENTS_VIEW = 'reports.stock_adjustments.view';
    case REPORTS_STOCK_OPNAMES_VIEW = 'reports.stock_opnames.view';
    case REPORTS_INVENTORY_MOVEMENT_VIEW = 'reports.inventory_movement.view';
    case REPORTS_EXPORT = 'reports.export';
    case USERS_MANAGE = 'users.manage';

    public function group(): string
    {
        return explode('.', $this->value)[0];
    }
}
