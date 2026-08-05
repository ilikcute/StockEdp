<template>
  <div class="app-layout min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center space-x-8">
          <span class="text-xl font-bold text-gray-900 tracking-tight">Inventory System</span>

          <nav
            v-if="authStore.isAuthenticated"
            class="hidden md:flex items-center space-x-2"
          >
            <router-link
              to="/profile"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 transition-colors"
              active-class="text-blue-600 bg-blue-50/50"
            >
              Profil Saya
            </router-link>

            <router-link
              v-if="authStore.hasPermission('products.view')"
              to="/products"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 transition-colors"
              active-class="text-blue-600 bg-blue-50/50"
            >
              Produk
            </router-link>

            <router-link
              v-if="authStore.hasPermission('categories.view')"
              to="/categories"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 transition-colors"
              active-class="text-blue-600 bg-blue-50/50"
            >
              Kategori
            </router-link>

            <router-link
              v-if="authStore.hasPermission('units.view')"
              to="/units"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 transition-colors"
              active-class="text-blue-600 bg-blue-50/50"
            >
              Satuan
            </router-link>

            <div
              v-if="hasAnyInventoryPermission"
              class="relative"
            >
              <button
                type="button"
                class="flex items-center px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 transition-colors"
                :class="{ 'text-blue-600 bg-blue-50/50': isInventoryActive }"
                @click="isInventoryOpen = !isInventoryOpen"
              >
                Persediaan
                <svg
                  class="ml-1 h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </button>

              <div
                v-if="isInventoryOpen"
                class="absolute left-0 mt-2 w-56 max-h-[70vh] overflow-y-auto rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 py-1"
              >
                <router-link
                  v-if="authStore.hasPermission('inventory.movements.view')"
                  to="/inventory/movements"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  active-class="bg-gray-100 text-blue-600 font-medium"
                  @click="isInventoryOpen = false"
                >
                  Riwayat Pergerakan
                </router-link>
                <router-link
                  v-if="authStore.hasPermission('stock_receipts.view')"
                  to="/inventory/receipts"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  active-class="bg-gray-100 text-blue-600 font-medium"
                  @click="isInventoryOpen = false"
                >
                  Penerimaan Stok
                </router-link>
                <router-link
                  v-if="authStore.hasPermission('stock_issues.view')"
                  to="/inventory/issues"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  active-class="bg-gray-100 text-blue-600 font-medium"
                  @click="isInventoryOpen = false"
                >
                  Pengeluaran Stok
                </router-link>
                <router-link
                  v-if="authStore.hasPermission('stock_transfers.view')"
                  to="/inventory/transfers"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  active-class="bg-gray-100 text-blue-600 font-medium"
                  @click="isInventoryOpen = false"
                >
                  Transfer Stok
                </router-link>
                <router-link
                  v-if="authStore.hasPermission('stock_adjustments.view')"
                  to="/inventory/adjustments"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  active-class="bg-gray-100 text-blue-600 font-medium"
                  @click="isInventoryOpen = false"
                >
                  Penyesuaian Stok
                </router-link>
                <router-link
                  v-if="authStore.hasPermission('stock_opnames.view')"
                  to="/inventory/opnames"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  active-class="bg-gray-100 text-blue-600 font-medium"
                  @click="isInventoryOpen = false"
                >
                  Stock Opname
                </router-link>
              </div>
            </div>

            <router-link
              v-if="authStore.hasPermission('suppliers.view')"
              to="/suppliers"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 transition-colors"
              active-class="text-blue-600 bg-blue-50/50"
            >
              Supplier
            </router-link>

            <router-link
              v-if="authStore.hasPermission('locations.view')"
              to="/locations"
              class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 transition-colors"
              active-class="text-blue-600 bg-blue-50/50"
            >
              Lokasi
            </router-link>

            <!-- Laporan Dropdown -->
            <div
              v-if="hasAnyReportPermission"
              class="relative"
            >
              <button
                type="button"
                class="flex items-center px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50 transition-colors"
                :class="{ 'text-blue-600 bg-blue-50/50': isReportActive }"
                @click="isReportOpen = !isReportOpen"
              >
                Laporan
                <svg
                  class="ml-1 h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </button>

              <div
                v-if="isReportOpen"
                class="absolute left-0 mt-2 w-56 max-h-[70vh] overflow-y-auto rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50 py-1"
              >
                <p class="px-4 pt-2 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wide">
                  Persediaan
                </p>
                <router-link
                  v-if="authStore.hasPermission('reports.inventory_balance.view')"
                  to="/reports/inventory-balances"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  active-class="bg-gray-100 text-blue-600 font-medium"
                  @click="isReportOpen = false"
                >
                  Saldo Stok
                </router-link>
                <router-link
                  v-if="authStore.hasPermission('reports.low_stock.view')"
                  to="/reports/low-stock"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  active-class="bg-gray-100 text-blue-600 font-medium"
                  @click="isReportOpen = false"
                >
                  Stok Minimum
                </router-link>
                <router-link
                  v-if="authStore.hasPermission('reports.stock_card.view')"
                  to="/reports/stock-card"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  active-class="bg-gray-100 text-blue-600 font-medium"
                  @click="isReportOpen = false"
                >
                  Kartu Stok
                </router-link>

                <p
                  v-if="hasAnyTransactionReportPermission"
                  class="px-4 pt-3 pb-1 text-xs font-semibold text-gray-400 uppercase tracking-wide border-t border-gray-100 mt-1"
                >
                  Transaksi
                </p>
                <router-link
                  v-if="authStore.hasPermission('reports.stock_receipts.view')"
                  to="/reports/stock-receipts"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  active-class="bg-gray-100 text-blue-600 font-medium"
                  @click="isReportOpen = false"
                >
                  Penerimaan Stok
                </router-link>
                <router-link
                  v-if="authStore.hasPermission('reports.stock_issues.view')"
                  to="/reports/stock-issues"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  active-class="bg-gray-100 text-blue-600 font-medium"
                  @click="isReportOpen = false"
                >
                  Pengeluaran Stok
                </router-link>
                <router-link
                  v-if="authStore.hasPermission('reports.stock_transfers.view')"
                  to="/reports/stock-transfers"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  active-class="bg-gray-100 text-blue-600 font-medium"
                  @click="isReportOpen = false"
                >
                  Transfer Stok
                </router-link>
                <router-link
                  v-if="authStore.hasPermission('reports.stock_adjustments.view')"
                  to="/reports/stock-adjustments"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  active-class="bg-gray-100 text-blue-600 font-medium"
                  @click="isReportOpen = false"
                >
                  Stock Adjustment
                </router-link>
                <router-link
                  v-if="authStore.hasPermission('reports.stock_opnames.view')"
                  to="/reports/stock-opnames"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  active-class="bg-gray-100 text-blue-600 font-medium"
                  @click="isReportOpen = false"
                >
                  Hasil Stock Opname
                </router-link>
              </div>
            </div>
          </nav>
        </div>

        <!-- User Dropdown / Menu -->
        <div
          v-if="authStore.isAuthenticated"
          class="flex items-center space-x-4"
        >
          <div class="text-right hidden sm:block">
            <p class="text-sm font-semibold text-gray-900">
              {{ authStore.user?.name }}
            </p>
            <p class="text-xs text-gray-500">
              {{ authStore.user?.username }}
            </p>
          </div>

          <button
            class="px-3 py-1.5 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-md transition-colors"
            @click="handleLogout"
          >
            Keluar
          </button>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { useAuthStore } from '../../features/auth/stores/use_auth_store.js';
import { useRouter, useRoute } from 'vue-router';
import { computed, ref } from 'vue';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const isReportOpen = ref(false);
const isInventoryOpen = ref(false);

const inventoryPermissions = [
    'inventory.movements.view',
    'stock_receipts.view',
    'stock_issues.view',
    'stock_transfers.view',
    'stock_adjustments.view',
    'stock_opnames.view',
];

const reportPermissions = [
    'reports.inventory_balance.view',
    'reports.low_stock.view',
    'reports.stock_card.view',
    'reports.stock_receipts.view',
    'reports.stock_issues.view',
    'reports.stock_transfers.view',
    'reports.stock_adjustments.view',
    'reports.stock_opnames.view',
];

const transactionReportPermissions = [
    'reports.stock_receipts.view',
    'reports.stock_issues.view',
    'reports.stock_transfers.view',
    'reports.stock_adjustments.view',
    'reports.stock_opnames.view',
];

const hasAnyReportPermission = computed(() =>
    reportPermissions.some((permission) => authStore.hasPermission(permission)),
);

const hasAnyInventoryPermission = computed(() =>
    inventoryPermissions.some((permission) => authStore.hasPermission(permission)),
);

const hasAnyTransactionReportPermission = computed(() =>
    transactionReportPermissions.some((permission) => authStore.hasPermission(permission)),
);

const isReportActive = computed(() => route.path.startsWith('/reports/'));
const isInventoryActive = computed(() => route.path.startsWith('/inventory/'));

async function handleLogout() {
    await authStore.logout();
    router.push('/login');
}
</script>
