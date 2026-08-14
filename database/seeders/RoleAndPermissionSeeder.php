<?php

namespace Database\Seeders;

use App\Features\Auth\Enums\PermissionCode;
use App\Features\Auth\Enums\RoleCode;
use App\Features\Auth\Models\Permission;
use App\Features\Auth\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Roles
        $adminRole = Role::updateOrCreate(
            ['code' => RoleCode::ADMIN->value],
            ['name' => RoleCode::ADMIN->label(), 'description' => 'Akses penuh ke seluruh sistem']
        );

        $warehouseRole = Role::updateOrCreate(
            ['code' => RoleCode::WAREHOUSE_OFFICER->value],
            ['name' => RoleCode::WAREHOUSE_OFFICER->label(), 'description' => 'Petugas operasional gudang']
        );

        $supervisorRole = Role::updateOrCreate(
            ['code' => RoleCode::INVENTORY_SUPERVISOR->value],
            ['name' => RoleCode::INVENTORY_SUPERVISOR->label(), 'description' => 'Supervisor pemeriksaan & rekonsiliasi stok']
        );

        // 2. Buat Permissions
        $permissions = [
            // Master Data
            PermissionCode::PRODUCTS_VIEW->value => 'Melihat Daftar Produk',
            PermissionCode::PRODUCTS_CREATE->value => 'Membuat Produk',
            PermissionCode::PRODUCTS_UPDATE->value => 'Mengubah Produk',
            PermissionCode::PRODUCTS_CHANGE_STATUS->value => 'Mengubah Status Produk',
            PermissionCode::PRODUCTS_IMPORT->value => 'Mengimpor Produk Secara Masal',
            PermissionCode::CATEGORIES_VIEW->value => 'Melihat Kategori',
            PermissionCode::CATEGORIES_CREATE->value => 'Membuat Kategori',
            PermissionCode::CATEGORIES_UPDATE->value => 'Mengubah Kategori',
            PermissionCode::CATEGORIES_CHANGE_STATUS->value => 'Mengubah Status Kategori',
            PermissionCode::CATEGORIES_IMPORT->value => 'Mengimpor Kategori Secara Masal',
            PermissionCode::UNITS_VIEW->value => 'Melihat Satuan',
            PermissionCode::UNITS_CREATE->value => 'Membuat Satuan',
            PermissionCode::UNITS_UPDATE->value => 'Mengubah Satuan',
            PermissionCode::UNITS_CHANGE_STATUS->value => 'Mengubah Status Satuan',
            PermissionCode::UNITS_IMPORT->value => 'Mengimpor Satuan Secara Masal',
            PermissionCode::SUPPLIERS_VIEW->value => 'Melihat Supplier',
            PermissionCode::SUPPLIERS_CREATE->value => 'Membuat Supplier',
            PermissionCode::SUPPLIERS_UPDATE->value => 'Mengubah Supplier',
            PermissionCode::SUPPLIERS_CHANGE_STATUS->value => 'Mengubah Status Supplier',
            PermissionCode::LOCATIONS_VIEW->value => 'Melihat Lokasi',
            PermissionCode::LOCATIONS_CREATE->value => 'Membuat Lokasi',
            PermissionCode::LOCATIONS_UPDATE->value => 'Mengubah Lokasi',
            PermissionCode::LOCATIONS_CHANGE_STATUS->value => 'Mengubah Status Lokasi',
            PermissionCode::LOCATIONS_IMPORT->value => 'Mengimpor Lokasi Secara Masal',

            // Transactions
            PermissionCode::INVENTORY_BALANCES_VIEW->value => 'Melihat Saldo Stok',
            PermissionCode::INVENTORY_MOVEMENTS_VIEW->value => 'Melihat Pergerakan Stok',
            PermissionCode::STOCK_RECEIPTS_VIEW->value => 'Melihat Penerimaan Stok',
            PermissionCode::STOCK_RECEIPTS_CREATE->value => 'Membuat Draft Penerimaan Stok',
            PermissionCode::STOCK_RECEIPTS_UPDATE->value => 'Mengubah Draft Penerimaan Stok',
            PermissionCode::STOCK_RECEIPTS_POST->value => 'Memposting Penerimaan Stok',
            PermissionCode::STOCK_RECEIPTS_CANCEL->value => 'Membatalkan Penerimaan Stok',
            PermissionCode::STOCK_ISSUES_VIEW->value => 'Melihat Pengeluaran Stok',
            PermissionCode::STOCK_ISSUES_CREATE->value => 'Membuat Draft Pengeluaran Stok',
            PermissionCode::STOCK_ISSUES_UPDATE->value => 'Mengubah Draft Pengeluaran Stok',
            PermissionCode::STOCK_ISSUES_POST->value => 'Memposting Pengeluaran Stok',
            PermissionCode::STOCK_ISSUES_CANCEL->value => 'Membatalkan Pengeluaran Stok',
            PermissionCode::STOCK_TRANSFERS_VIEW->value => 'Melihat Transfer Stok',
            PermissionCode::STOCK_TRANSFERS_CREATE->value => 'Membuat Transfer Stok',
            PermissionCode::STOCK_TRANSFERS_UPDATE->value => 'Mengubah Transfer Stok',
            PermissionCode::STOCK_TRANSFERS_SEND->value => 'Mengirim Transfer Stok',
            PermissionCode::STOCK_TRANSFERS_RECEIVE->value => 'Menerima Transfer Stok',
            PermissionCode::STOCK_TRANSFERS_CANCEL->value => 'Membatalkan Transfer Stok',

            // Stock Adjustments (Fase 6) — granular per maker-checker design
            PermissionCode::STOCK_ADJUSTMENTS_VIEW->value => 'Melihat Stock Adjustment',
            PermissionCode::STOCK_ADJUSTMENTS_CREATE->value => 'Membuat Draft Stock Adjustment',
            PermissionCode::STOCK_ADJUSTMENTS_UPDATE->value => 'Mengubah Draft Stock Adjustment',
            PermissionCode::STOCK_ADJUSTMENTS_POST->value => 'Memposting Stock Adjustment',
            PermissionCode::STOCK_ADJUSTMENTS_CANCEL->value => 'Membatalkan Draft Stock Adjustment',

            // Stock Opname (Fase 7)
            PermissionCode::STOCK_OPNAMES_VIEW->value => 'Melihat Stock Opname',
            PermissionCode::STOCK_OPNAMES_CREATE->value => 'Membuat Draft Stock Opname',
            PermissionCode::STOCK_OPNAMES_UPDATE->value => 'Mengubah Draft Stock Opname',
            PermissionCode::STOCK_OPNAMES_START->value => 'Memulai Stock Opname (Snapshot & Freeze)',
            PermissionCode::STOCK_OPNAMES_COUNT->value => 'Menginput Kuantitas Fisik Opname',
            PermissionCode::STOCK_OPNAMES_COMPLETE->value => 'Menyelesaikan Perhitungan Opname',
            PermissionCode::STOCK_OPNAMES_REOPEN->value => 'Membuka Kembali Opname untuk Recount',
            PermissionCode::STOCK_OPNAMES_POST->value => 'Memposting Rekonsiliasi Opname',
            PermissionCode::STOCK_OPNAMES_CANCEL->value => 'Membatalkan Stock Opname',

            PermissionCode::INVENTORY_OPNAME->value => 'Stock Opname',

            // Dashboard & Replenishment
            PermissionCode::DASHBOARD_VIEW->value => 'Melihat Dashboard Operasional',
            PermissionCode::REPLENISHMENT_VIEW->value => 'Melihat Rekomendasi Reorder',

            // Reports & Users
            PermissionCode::REPORTS_VIEW->value => 'Melihat Laporan',
            PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW->value => 'Melihat Laporan Saldo Stok',
            PermissionCode::REPORTS_LOW_STOCK_VIEW->value => 'Melihat Laporan Stok Minimum',
            PermissionCode::REPORTS_STOCK_CARD_VIEW->value => 'Melihat Laporan Kartu Stok',
            PermissionCode::REPORTS_STOCK_RECEIPTS_VIEW->value => 'Melihat Laporan Penerimaan Stok',
            PermissionCode::REPORTS_STOCK_ISSUES_VIEW->value => 'Melihat Laporan Pengeluaran Stok',
            PermissionCode::REPORTS_STOCK_TRANSFERS_VIEW->value => 'Melihat Laporan Transfer Stok',
            PermissionCode::REPORTS_STOCK_ADJUSTMENTS_VIEW->value => 'Melihat Laporan Penyesuaian Stok',
            PermissionCode::REPORTS_STOCK_OPNAMES_VIEW->value => 'Melihat Laporan Stock Opname',
            PermissionCode::REPORTS_EXPORT->value => 'Mengekspor Laporan',
            PermissionCode::USERS_MANAGE->value => 'Mengelola Pengguna & Hak Akses',
        ];

        $permissionModels = [];
        foreach ($permissions as $code => $name) {
            $group = explode('.', $code)[0];
            $permissionModels[$code] = Permission::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'group' => $group]
            );
        }

        // 3. Menugaskan Permissions ke Roles
        // Admin mendapatkan seluruh permission
        $adminRole->permissions()->sync(array_column($permissionModels, 'id'));

        // Petugas Gudang
        $warehousePermissions = [
            PermissionCode::DASHBOARD_VIEW->value,
            PermissionCode::REPLENISHMENT_VIEW->value,
            PermissionCode::PRODUCTS_VIEW->value,
            PermissionCode::CATEGORIES_VIEW->value,
            PermissionCode::UNITS_VIEW->value,
            PermissionCode::SUPPLIERS_VIEW->value,
            PermissionCode::LOCATIONS_VIEW->value,
            PermissionCode::INVENTORY_BALANCES_VIEW->value,
            PermissionCode::INVENTORY_MOVEMENTS_VIEW->value,
            PermissionCode::STOCK_RECEIPTS_VIEW->value,
            PermissionCode::STOCK_RECEIPTS_CREATE->value,
            PermissionCode::STOCK_RECEIPTS_UPDATE->value,
            PermissionCode::STOCK_RECEIPTS_POST->value,
            PermissionCode::STOCK_RECEIPTS_CANCEL->value,
            PermissionCode::STOCK_ISSUES_VIEW->value,
            PermissionCode::STOCK_ISSUES_CREATE->value,
            PermissionCode::STOCK_ISSUES_UPDATE->value,
            PermissionCode::STOCK_ISSUES_POST->value,
            PermissionCode::STOCK_ISSUES_CANCEL->value,
            PermissionCode::STOCK_TRANSFERS_VIEW->value,
            PermissionCode::STOCK_TRANSFERS_CREATE->value,
            PermissionCode::STOCK_TRANSFERS_UPDATE->value,
            PermissionCode::STOCK_TRANSFERS_SEND->value,
            PermissionCode::STOCK_TRANSFERS_RECEIVE->value,
            PermissionCode::STOCK_TRANSFERS_CANCEL->value,
            PermissionCode::STOCK_ADJUSTMENTS_VIEW->value,
            PermissionCode::STOCK_ADJUSTMENTS_CREATE->value,
            PermissionCode::STOCK_ADJUSTMENTS_UPDATE->value,
            PermissionCode::STOCK_ADJUSTMENTS_CANCEL->value,
            PermissionCode::STOCK_OPNAMES_VIEW->value,
            PermissionCode::STOCK_OPNAMES_CREATE->value,
            PermissionCode::STOCK_OPNAMES_UPDATE->value,
            PermissionCode::STOCK_OPNAMES_COUNT->value,
            PermissionCode::STOCK_OPNAMES_CANCEL->value,
            PermissionCode::INVENTORY_OPNAME->value,
            PermissionCode::REPORTS_VIEW->value,
            PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW->value,
            PermissionCode::REPORTS_LOW_STOCK_VIEW->value,
            PermissionCode::REPORTS_STOCK_CARD_VIEW->value,
            PermissionCode::REPORTS_STOCK_RECEIPTS_VIEW->value,
            PermissionCode::REPORTS_STOCK_ISSUES_VIEW->value,
            PermissionCode::REPORTS_STOCK_TRANSFERS_VIEW->value,
            PermissionCode::REPORTS_STOCK_ADJUSTMENTS_VIEW->value,
            PermissionCode::REPORTS_STOCK_OPNAMES_VIEW->value,
        ];
        $warehouseRole->permissions()->sync(
            array_map(fn ($code) => $permissionModels[$code]->id, $warehousePermissions)
        );

        // Supervisor Inventory
        $supervisorPermissions = [
            PermissionCode::DASHBOARD_VIEW->value,
            PermissionCode::REPLENISHMENT_VIEW->value,
            PermissionCode::PRODUCTS_VIEW->value,
            PermissionCode::CATEGORIES_VIEW->value,
            PermissionCode::UNITS_VIEW->value,
            PermissionCode::SUPPLIERS_VIEW->value,
            PermissionCode::LOCATIONS_VIEW->value,
            PermissionCode::INVENTORY_BALANCES_VIEW->value,
            PermissionCode::INVENTORY_MOVEMENTS_VIEW->value,
            PermissionCode::STOCK_RECEIPTS_VIEW->value,
            PermissionCode::STOCK_ISSUES_VIEW->value,
            PermissionCode::STOCK_TRANSFERS_VIEW->value,
            PermissionCode::STOCK_ADJUSTMENTS_VIEW->value,
            PermissionCode::STOCK_ADJUSTMENTS_POST->value,
            PermissionCode::STOCK_OPNAMES_VIEW->value,
            PermissionCode::STOCK_OPNAMES_START->value,
            PermissionCode::STOCK_OPNAMES_COMPLETE->value,
            PermissionCode::STOCK_OPNAMES_REOPEN->value,
            PermissionCode::STOCK_OPNAMES_POST->value,
            PermissionCode::STOCK_OPNAMES_CANCEL->value,
            PermissionCode::INVENTORY_OPNAME->value,
            PermissionCode::REPORTS_VIEW->value,
            PermissionCode::REPORTS_INVENTORY_BALANCE_VIEW->value,
            PermissionCode::REPORTS_LOW_STOCK_VIEW->value,
            PermissionCode::REPORTS_STOCK_CARD_VIEW->value,
            PermissionCode::REPORTS_STOCK_RECEIPTS_VIEW->value,
            PermissionCode::REPORTS_STOCK_ISSUES_VIEW->value,
            PermissionCode::REPORTS_STOCK_TRANSFERS_VIEW->value,
            PermissionCode::REPORTS_STOCK_ADJUSTMENTS_VIEW->value,
            PermissionCode::REPORTS_STOCK_OPNAMES_VIEW->value,
            PermissionCode::REPORTS_EXPORT->value,
        ];
        $supervisorRole->permissions()->sync(
            array_map(fn ($code) => $permissionModels[$code]->id, $supervisorPermissions)
        );
    }
}
