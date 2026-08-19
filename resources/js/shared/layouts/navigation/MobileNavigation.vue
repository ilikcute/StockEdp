<template>
  <div>
    <!-- Backdrop Overlay -->
    <div
      v-if="isOpen"
      class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs z-40 lg:hidden transition-opacity"
      @click="emit('close')"
    />

    <!-- Slide-down / Slide-over Drawer -->
    <div
      v-if="isOpen"
      id="mobile-navigation"
      class="fixed inset-x-0 top-14 sm:top-16 z-50 lg:hidden bg-white border-b border-gray-200 shadow-2xl max-h-[80vh] overflow-y-auto touch-scroll divide-y divide-gray-100"
    >
      <!-- User Profile Header -->
      <div class="px-4 py-3 bg-gray-50/80 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full bg-blue-600 text-white font-bold flex items-center justify-center text-sm shadow-xs">
            {{ (authStore.user?.name || 'U').charAt(0).toUpperCase() }}
          </div>
          <div>
            <div class="text-xs font-bold text-gray-900 leading-tight">
              {{ authStore.user?.name }}
            </div>
            <div class="text-[11px] text-gray-500 font-mono">
              {{ authStore.user?.username }}
            </div>
          </div>
        </div>

        <button
          type="button"
          class="px-2.5 py-1 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-md transition-colors cursor-pointer"
          @click="emit('logout')"
        >
          Keluar
        </button>
      </div>

      <!-- Navigation Links Sections -->
      <div class="p-4 space-y-4">
        <!-- 1. Menu Utama -->
        <div class="space-y-1">
          <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-2">
            Menu Utama
          </div>
          <router-link
            v-if="authStore.hasPermission('dashboard.view')"
            to="/dashboard"
            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold text-gray-700 hover:text-blue-600 hover:bg-blue-50/50 transition-colors"
            active-class="bg-blue-50 text-blue-600 font-bold"
            @click="emit('close')"
          >
            <span>🏠 Dashboard Persediaan</span>
          </router-link>
          <router-link
            to="/profile"
            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold text-gray-700 hover:text-blue-600 hover:bg-blue-50/50 transition-colors"
            active-class="bg-blue-50 text-blue-600 font-bold"
            @click="emit('close')"
          >
            <span>👤 Profil Pengguna</span>
          </router-link>
          <router-link
            v-if="authStore.hasPermission('users.manage')"
            to="/users"
            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold text-gray-700 hover:text-blue-600 hover:bg-blue-50/50 transition-colors"
            active-class="bg-blue-50 text-blue-600 font-bold"
            @click="emit('close')"
          >
            <span>👥 Pengelolaan Pengguna & Hak Akses</span>
          </router-link>
        </div>

        <!-- 2. Master Data -->
        <div
          v-if="hasMasterPermission"
          class="space-y-1 pt-2 border-t border-gray-100"
        >
          <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-2">
            Master Data
          </div>
          <template
            v-for="item in masterNavLinks"
            :key="item.to"
          >
            <router-link
              v-if="authStore.hasPermission(item.permission)"
              :to="item.to"
              class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50/50 transition-colors"
              active-class="bg-blue-50 text-blue-600 font-bold"
              @click="emit('close')"
            >
              <span>{{ item.label }}</span>
              <span class="text-gray-400 text-[11px]">→</span>
            </router-link>
          </template>
        </div>

        <!-- 3. Persediaan & Operasional -->
        <div
          v-if="hasInventoryPermission"
          class="space-y-1 pt-2 border-t border-gray-100"
        >
          <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-2">
            Persediaan & Transaksi
          </div>
          <template
            v-for="item in inventoryNavLinks"
            :key="item.to"
          >
            <router-link
              v-if="authStore.hasPermission(item.permission)"
              :to="item.to"
              class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50/50 transition-colors"
              active-class="bg-blue-50 text-blue-600 font-bold"
              @click="emit('close')"
            >
              <span>{{ item.label }}</span>
              <span class="text-gray-400 text-[11px]">→</span>
            </router-link>
          </template>
        </div>

        <!-- 4. Laporan Persediaan -->
        <div
          v-if="hasInventoryReportPermission"
          class="space-y-1 pt-2 border-t border-gray-100 pb-2"
        >
          <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-2">
            Laporan Persediaan
          </div>
          <template
            v-for="item in inventoryReportNavLinks"
            :key="item.to"
          >
            <router-link
              v-if="authStore.hasPermission(item.permission)"
              :to="item.to"
              class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50/50 transition-colors"
              active-class="bg-blue-50 text-blue-600 font-bold"
              @click="emit('close')"
            >
              <span>{{ item.label }}</span>
              <span class="text-gray-400 text-[11px]">→</span>
            </router-link>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';

defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'logout']);
const authStore = useAuthStore();

const masterNavLinks = [
    { to: '/products', label: 'Produk', permission: 'products.view' },
    { to: '/categories', label: 'Kategori', permission: 'categories.view' },
    { to: '/units', label: 'Satuan', permission: 'units.view' },
    { to: '/suppliers', label: 'Supplier', permission: 'suppliers.view' },
    { to: '/locations', label: 'Lokasi', permission: 'locations.view' },
];

const inventoryNavLinks = [
    { to: '/inventory/receipts', label: 'Penerimaan Barang (Inbound)', permission: 'stock_receipts.view' },
    { to: '/inventory/issues', label: 'Pengeluaran Barang (Outbound)', permission: 'stock_issues.view' },
    { to: '/inventory/transfers', label: 'Transfer Stok Antar Gudang', permission: 'stock_transfers.view' },
    { to: '/inventory/adjustments', label: 'Penyesuaian Stok (Adjustment)', permission: 'stock_adjustments.view' },
    { to: '/inventory/opnames', label: 'Stock Opname (Fisik)', permission: 'stock_opnames.view' },
    { to: '/inventory/replenishment', label: 'Action Center Replenishment', permission: 'replenishment.view' },
];

const inventoryReportNavLinks = [
    { to: '/reports/inventory-balances', label: 'Saldo Persediaan', permission: 'reports.inventory_balance.view' },
    { to: '/reports/low-stock', label: 'Stok Menipis (Low Stock)', permission: 'reports.low_stock.view' },
    { to: '/reports/stock-card', label: 'Kartu Stok', permission: 'reports.stock_card.view' },
    { to: '/reports/inventory-movement', label: 'Pergerakan Barang (Fast/Slow Moving)', permission: 'reports.inventory_movement.view' },
    { to: '/reports/stock-receipts', label: 'Laporan Penerimaan', permission: 'reports.stock_receipts.view' },
    { to: '/reports/stock-issues', label: 'Laporan Pengeluaran', permission: 'reports.stock_issues.view' },
    { to: '/reports/stock-transfers', label: 'Laporan Transfer', permission: 'reports.stock_transfers.view' },
    { to: '/reports/stock-adjustments', label: 'Laporan Penyesuaian', permission: 'reports.stock_adjustments.view' },
    { to: '/reports/stock-opnames', label: 'Laporan Stock Opname', permission: 'reports.stock_opnames.view' },
];

const hasMasterPermission = computed(() => {
    return masterNavLinks.some((item) => authStore.hasPermission(item.permission));
});

const hasInventoryPermission = computed(() => {
    return inventoryNavLinks.some((item) => authStore.hasPermission(item.permission));
});

const hasInventoryReportPermission = computed(() => {
    return inventoryReportNavLinks.some((item) => authStore.hasPermission(item.permission));
});
</script>
