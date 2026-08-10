<?php

namespace App\Features\MasterDataImport\Enums;

use App\Features\Auth\Enums\PermissionCode;

enum MasterDataImportType: string
{
    case PRODUCTS = 'products';
    case CATEGORIES = 'categories';
    case UNITS = 'units';
    case LOCATIONS = 'locations';

    /**
     * Get the canonical template filename.
     */
    public function templateFilename(): string
    {
        return match ($this) {
            self::PRODUCTS => 'template_products.csv',
            self::CATEGORIES => 'template_categories.csv',
            self::UNITS => 'template_units.csv',
            self::LOCATIONS => 'template_locations.csv',
        };
    }

    /**
     * Get the required canonical headers in lowercase.
     *
     * @return array<int, string>
     */
    public function canonicalHeaders(): array
    {
        return match ($this) {
            self::CATEGORIES => ['code', 'name', 'description'],
            self::UNITS => ['code', 'name', 'symbol', 'description'],
            self::LOCATIONS => ['code', 'name', 'description', 'address', 'phone'],
            self::PRODUCTS => ['sku', 'barcode', 'name', 'description', 'category_code', 'unit_code', 'minimum_stock'],
        };
    }

    /**
     * Get the required permission code for this import type.
     */
    public function requiredPermission(): PermissionCode
    {
        return match ($this) {
            self::PRODUCTS => PermissionCode::PRODUCTS_IMPORT,
            self::CATEGORIES => PermissionCode::CATEGORIES_IMPORT,
            self::UNITS => PermissionCode::UNITS_IMPORT,
            self::LOCATIONS => PermissionCode::LOCATIONS_IMPORT,
        };
    }

    /**
     * Human-readable label for response messages.
     */
    public function label(): string
    {
        return match ($this) {
            self::PRODUCTS => 'Produk',
            self::CATEGORIES => 'Kategori',
            self::UNITS => 'Satuan',
            self::LOCATIONS => 'Lokasi',
        };
    }
}
