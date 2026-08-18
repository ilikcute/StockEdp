<template>
  <div class="space-y-6">
    <!-- Role Cards Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div
        v-for="role in roles"
        :key="role.id"
        :class="[
          'rounded-xl p-5 border shadow-xs transition-all',
          selectedRoleId === role.id
            ? 'bg-indigo-50/40 border-indigo-300 ring-1 ring-indigo-500'
            : 'bg-white border-gray-200 hover:border-gray-300'
        ]"
      >
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-sm font-bold text-gray-900">
                {{ role.name }}
              </h3>
              <span
                :class="[
                  'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold ring-1 ring-inset',
                  role.code === 'ADMIN'
                    ? 'bg-purple-50 text-purple-700 ring-purple-600/20'
                    : role.code === 'INVENTORY_SUPERVISOR'
                      ? 'bg-blue-50 text-blue-700 ring-blue-600/20'
                      : 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                ]"
              >
                {{ role.code }}
              </span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              {{ role.description || 'Peran otorisasi sistem persediaan.' }}
            </p>
          </div>
        </div>

        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
          <span class="text-gray-500">
            Total Pengguna: <strong class="text-gray-800">{{ role.users_count ?? 0 }}</strong>
          </span>
          <span class="text-gray-500">
            Hak Akses: <strong class="text-indigo-600">{{ role.permissions?.length ?? 0 }}</strong> izin
          </span>
        </div>
      </div>
    </div>

    <!-- Permissions Breakdown Matrix by Group -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-bold text-gray-900">
            Matriks Hak Akses Berdasarkan Fitur
          </h3>
          <p class="text-xs text-gray-500 mt-0.5">
            Daftar izin operasional dan modul yang diberikan untuk masing-masing peran.
          </p>
        </div>
      </div>

      <div
        v-if="loading"
        class="py-12 text-center text-gray-500"
      >
        <div class="inline-flex items-center gap-2">
          <svg
            class="animate-spin h-5 w-5 text-indigo-600"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8v8H4z"
            />
          </svg>
          <span class="text-sm font-medium">Memuat rincian hak akses...</span>
        </div>
      </div>

      <div
        v-else
        class="divide-y divide-gray-200"
      >
        <div
          v-for="(perms, groupName) in allPermissions"
          :key="groupName"
          class="p-5 space-y-3"
        >
          <div class="flex items-center gap-2">
            <span class="h-2 w-2 rounded-full bg-indigo-600" />
            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
              {{ formatGroupName(groupName) }}
            </h4>
            <span class="text-xs text-gray-400">({{ perms.length }} izin)</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
            <div
              v-for="perm in perms"
              :key="perm.id"
              class="p-2.5 rounded-lg border border-gray-100 bg-gray-50/60 text-xs space-y-1"
            >
              <div class="font-semibold text-gray-900">
                {{ perm.name }}
              </div>
              <div class="text-[11px] font-mono text-gray-400">
                {{ perm.code }}
              </div>

              <!-- Roles that have this permission -->
              <div class="flex flex-wrap gap-1 pt-1">
                <span
                  v-for="role in rolesWithPermission(perm.code)"
                  :key="role.id"
                  :class="[
                    'inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold',
                    role.code === 'ADMIN'
                      ? 'bg-purple-100 text-purple-800'
                      : role.code === 'INVENTORY_SUPERVISOR'
                        ? 'bg-blue-100 text-blue-800'
                        : 'bg-emerald-100 text-emerald-800'
                  ]"
                >
                  {{ role.name }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  roles: {
    type: Array,
    default: () => [],
  },
  allPermissions: {
    type: Object,
    default: () => ({}),
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const selectedRoleId = ref(null);

const groupLabels = {
  products: 'Master Data Produk',
  categories: 'Master Data Kategori',
  units: 'Master Data Satuan',
  suppliers: 'Master Data Supplier',
  locations: 'Master Data Lokasi Gudang',
  inventory: 'Persediaan & Saldo Stok',
  stock_receipts: 'Penerimaan Stok (Inbound)',
  stock_issues: 'Pengeluaran Stok (Outbound)',
  stock_transfers: 'Transfer Antar Gudang',
  stock_adjustments: 'Penyesuaian Stok (Stock Adjustment)',
  stock_opnames: 'Stock Opname Fisik',
  dashboard: 'Dashboard Operasional',
  replenishment: 'Rekomendasi Reorder',
  reports: 'Laporan & Ekspor Persediaan',
  users: 'Pengelolaan Pengguna & Hak Akses',
};

const formatGroupName = (group) => {
  return groupLabels[group] || group.replace(/_/g, ' ');
};

const rolesWithPermission = (permCode) => {
  return props.roles.filter((role) => {
    if (role.code === 'ADMIN') return true; // Admin has all permissions
    return role.permissions?.some((p) => p.code === permCode);
  });
};
</script>
