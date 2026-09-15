/**
 * Pemetaan hierarki Menu Group sesuai susunan Menu Sidebar StockEdp.
 * Menjamin fleksibilitas: jika ada izin / modul baru yang ditambahkan di masa mendatang,
 * modul tersebut akan otomatis muncul dan dikelompokkan secara dinamis.
 */

export const KNOWN_SUBGROUP_LABELS = {
    // 1. Menu Utama
    dashboard: 'Dashboard Operasional',

    // 2. Master Data
    products: 'Master Produk',
    categories: 'Master Kategori',
    units: 'Master Satuan',
    suppliers: 'Master Supplier',
    locations: 'Master Lokasi Gudang',
    stores: 'Master Toko',
    departments: 'Master Departemen',

    // 3. Transaksi Persediaan
    inventory: 'Saldo & Riwayat Pergerakan',
    store_allocations: 'Alokasi Penggantian Toko',
    stock_receipts: 'Penerimaan Stok (Inbound)',
    stock_issues: 'Pengeluaran Stok (Outbound)',
    stock_transfers: 'Transfer Stok Antar Gudang',
    stock_adjustments: 'Penyesuaian Stok (Stock Adjustment)',
    stock_opnames: 'Stock Opname Fisik',
    replenishment: 'Rekomendasi Reorder / Replenishment',

    // 4. Laporan
    reports: 'Laporan & Ekspor Persediaan',

    // 5. Pengaturan
    users: 'Pengelolaan Pengguna & Hak Akses',
};

export const SIDEBAR_MENU_SECTIONS = [
    {
        id: 'main_menu',
        title: 'Menu Utama',
        description: 'Akses dashboard operasional & ringkasan metrik persediaan',
        icon: 'dashboard',
        badgeColor: 'bg-indigo-50 text-indigo-700 border-indigo-200',
        groupKeys: ['dashboard'],
    },
    {
        id: 'master_data',
        title: 'Master Data',
        description: 'Data entitas master: produk, kategori, satuan, supplier, lokasi, toko, dan departemen',
        icon: 'database',
        badgeColor: 'bg-blue-50 text-blue-700 border-blue-200',
        groupKeys: [
            'products',
            'categories',
            'units',
            'suppliers',
            'locations',
            'stores',
            'departments',
        ],
    },
    {
        id: 'inventory_transactions',
        title: 'Transaksi Persediaan',
        description: 'Mutasi barang: alokasi toko, penerimaan, pengeluaran, transfer, adjustment, dan opname',
        icon: 'transfer',
        badgeColor: 'bg-amber-50 text-amber-700 border-amber-200',
        groupKeys: [
            'inventory',
            'store_allocations',
            'stock_receipts',
            'stock_issues',
            'stock_transfers',
            'stock_adjustments',
            'stock_opnames',
            'replenishment',
        ],
    },
    {
        id: 'reports',
        title: 'Laporan & Analisis',
        description: 'Laporan persediaan, kartu stok, histori mutasi, dan ekspor laporan',
        icon: 'chart',
        badgeColor: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        groupKeys: ['reports'],
    },
    {
        id: 'user_management',
        title: 'Pengaturan & Pengguna',
        description: 'Pengelolaan akun pengguna, peran otorisasi (RBAC), dan hak akses sistem',
        icon: 'users',
        badgeColor: 'bg-purple-50 text-purple-700 border-purple-200',
        groupKeys: ['users'],
    },
];

/**
 * Format nama subgroup secara dinamis.
 * Jika terdapat di kamus, gunakan nama ramah.
 * Jika belum ada (fitur baru), otomatis konversi snake_case menjadi Title Case.
 */
export function formatSubgroupName(groupKey) {
    if (KNOWN_SUBGROUP_LABELS[groupKey]) {
        return KNOWN_SUBGROUP_LABELS[groupKey];
    }
    return String(groupKey)
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

/**
 * Membangun struktur hierarkis permission per Section Menu Sidebar.
 * Mendukung filter pencarian nama & kode permission secara real-time.
 * Secara otomatis menampung permission dari fitur/modul baru yang belum terdaftar.
 *
 * @param {Object} allPermissionsGrouped Objek permissions dikelompokkan oleh backend ({ [group]: Permission[] })
 * @param {string} searchQuery Kata kunci pencarian
 * @returns {Array} Daftar section dengan subgroups dan permissions
 */
export function buildGroupedMenuSections(allPermissionsGrouped, searchQuery = '') {
    if (!allPermissionsGrouped || typeof allPermissionsGrouped !== 'object') {
        return [];
    }

    const query = (searchQuery || '').toLowerCase().trim();
    const mappedGroupKeys = new Set();
    const resultSections = [];

    // 1. Proses Section Standar yang telah terdefinisi sesuai Sidebar
    for (const sectionDef of SIDEBAR_MENU_SECTIONS) {
        const sectionSubgroups = [];

        for (const gKey of sectionDef.groupKeys) {
            mappedGroupKeys.add(gKey);
            const rawPerms = allPermissionsGrouped[gKey] || [];
            if (rawPerms.length === 0) continue;

            const filteredPerms = query
                ? rawPerms.filter(
                      (p) =>
                          (p.name && p.name.toLowerCase().includes(query)) ||
                          (p.code && p.code.toLowerCase().includes(query))
                  )
                : rawPerms;

            if (filteredPerms.length > 0) {
                sectionSubgroups.push({
                    key: gKey,
                    label: formatSubgroupName(gKey),
                    permissions: filteredPerms,
                    totalCount: rawPerms.length,
                });
            }
        }

        if (sectionSubgroups.length > 0) {
            resultSections.push({
                id: sectionDef.id,
                title: sectionDef.title,
                description: sectionDef.description,
                icon: sectionDef.icon,
                badgeColor: sectionDef.badgeColor,
                subgroups: sectionSubgroups,
            });
        }
    }

    // 2. Dynamic Catch-All: Otomatis tangkap jika ada Fitur / Halaman Baru yang kodenya belum dipetakan
    const unmappedKeys = Object.keys(allPermissionsGrouped).filter(
        (key) => !mappedGroupKeys.has(key)
    );

    if (unmappedKeys.length > 0) {
        const additionalSubgroups = [];

        for (const gKey of unmappedKeys) {
            const rawPerms = allPermissionsGrouped[gKey] || [];
            if (rawPerms.length === 0) continue;

            const filteredPerms = query
                ? rawPerms.filter(
                      (p) =>
                          (p.name && p.name.toLowerCase().includes(query)) ||
                          (p.code && p.code.toLowerCase().includes(query))
                  )
                : rawPerms;

            if (filteredPerms.length > 0) {
                additionalSubgroups.push({
                    key: gKey,
                    label: formatSubgroupName(gKey),
                    permissions: filteredPerms,
                    totalCount: rawPerms.length,
                });
            }
        }

        if (additionalSubgroups.length > 0) {
            resultSections.push({
                id: 'additional_features',
                title: 'Modul & Fitur Tambahan',
                description: 'Hak akses untuk modul dan fitur baru yang ditambahkan ke sistem',
                icon: 'puzzle',
                badgeColor: 'bg-rose-50 text-rose-700 border-rose-200',
                subgroups: additionalSubgroups,
            });
        }
    }

    return resultSections;
}
