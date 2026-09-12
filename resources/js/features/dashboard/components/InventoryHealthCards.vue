<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
    <!-- Out of Stock Card -->
    <component
      :is="canNavigate('reports.inventory_balance.view') ? 'button' : 'div'"
      type="button"
      :class="[
        'bg-white rounded-xl border border-rose-200 p-3 shadow-xs text-left transition-all',
        canNavigate('reports.inventory_balance.view') ? 'hover:shadow-md cursor-pointer group focus:outline-none focus:ring-2 focus:ring-rose-500' : ''
      ]"
      @click="canNavigate('reports.inventory_balance.view') && navigateTo('reports.inventory-balances', locationId ? { location_id: locationId, zero_stock: 1 } : { zero_stock: 1 })"
    >
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-semibold text-rose-600 uppercase tracking-wider">Stok Habis (0)</span>
        <div class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center group-hover:scale-110 transition-transform">
          <svg
            class="w-3.5 h-3.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
        </div>
      </div>
      <div class="mt-1.5 flex items-baseline justify-between">
        <span
          id="stat-out-of-stock-count"
          class="text-xl font-bold text-gray-900"
        >{{ data.out_of_stock_count || 0 }}</span>
        <span
          v-if="canNavigate('reports.inventory_balance.view')"
          class="text-[11px] text-rose-600 font-medium group-hover:underline flex items-center gap-1"
        >
          Lihat &rarr;
        </span>
      </div>
    </component>

    <!-- Low Stock Card -->
    <component
      :is="canNavigate('reports.low_stock.view') ? 'button' : 'div'"
      type="button"
      :class="[
        'bg-white rounded-xl border border-amber-200 p-3 shadow-xs text-left transition-all',
        canNavigate('reports.low_stock.view') ? 'hover:shadow-md cursor-pointer group focus:outline-none focus:ring-2 focus:ring-amber-500' : ''
      ]"
      @click="canNavigate('reports.low_stock.view') && navigateTo('reports.low-stock', locationId ? { location_id: locationId } : {})"
    >
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-semibold text-amber-600 uppercase tracking-wider">Di Bawah Min. Stok</span>
        <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-110 transition-transform">
          <svg
            class="w-3.5 h-3.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"
            />
          </svg>
        </div>
      </div>
      <div class="mt-1.5 flex items-baseline justify-between">
        <span
          id="stat-low-stock-count"
          class="text-xl font-bold text-gray-900"
        >{{ data.low_stock_count || 0 }}</span>
        <span
          v-if="canNavigate('reports.low_stock.view')"
          class="text-[11px] text-amber-600 font-medium group-hover:underline flex items-center gap-1"
        >
          Laporan &rarr;
        </span>
      </div>
    </component>

    <!-- Active Opname Card -->
    <component
      :is="canNavigate('stock_opnames.view') ? 'button' : 'div'"
      type="button"
      :class="[
        'bg-white rounded-xl border border-purple-200 p-3 shadow-xs text-left transition-all',
        canNavigate('stock_opnames.view') ? 'hover:shadow-md cursor-pointer group focus:outline-none focus:ring-2 focus:ring-purple-500' : ''
      ]"
      @click="canNavigate('stock_opnames.view') && navigateTo('stockOpnames')"
    >
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-semibold text-purple-600 uppercase tracking-wider">Opname Aktif</span>
        <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center group-hover:scale-110 transition-transform">
          <svg
            class="w-3.5 h-3.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            />
          </svg>
        </div>
      </div>
      <div class="mt-1.5 flex items-baseline justify-between">
        <span
          id="stat-active-opname-count"
          class="text-xl font-bold text-gray-900"
        >{{ data.active_opname_count || 0 }}</span>
        <span
          v-if="canNavigate('stock_opnames.view')"
          class="text-[11px] text-purple-600 font-medium group-hover:underline flex items-center gap-1"
        >
          Kelola &rarr;
        </span>
      </div>
    </component>

    <!-- Frozen Location Card -->
    <component
      :is="canNavigate('locations.view') ? 'button' : 'div'"
      type="button"
      :class="[
        'bg-white rounded-xl border border-cyan-200 p-3 shadow-xs text-left transition-all',
        canNavigate('locations.view') ? 'hover:shadow-md cursor-pointer group focus:outline-none focus:ring-2 focus:ring-cyan-500' : ''
      ]"
      @click="canNavigate('locations.view') && navigateTo('locations.index')"
    >
      <div class="flex items-center justify-between">
        <span class="text-[11px] font-semibold text-cyan-600 uppercase tracking-wider">Lokasi Beku</span>
        <div class="w-7 h-7 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center group-hover:scale-110 transition-transform">
          <svg
            class="w-3.5 h-3.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
            />
          </svg>
        </div>
      </div>
      <div class="mt-1.5 flex items-baseline justify-between">
        <span
          id="stat-frozen-location-count"
          class="text-xl font-bold text-gray-900"
        >{{ data.frozen_location_count || 0 }}</span>
        <span
          v-if="canNavigate('locations.view')"
          class="text-[11px] text-cyan-600 font-medium group-hover:underline flex items-center gap-1"
        >
          Lokasi &rarr;
        </span>
      </div>
    </component>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../auth/stores/use_auth_store';

defineProps({
  data: {
    type: Object,
    default: () => ({
      low_stock_count: 0,
      out_of_stock_count: 0,
      active_opname_count: 0,
      frozen_location_count: 0,
    }),
  },
  locationId: {
    type: [String, Number],
    default: '',
  },
});

const router = useRouter();
const authStore = useAuthStore();

const canNavigate = (permission) => {
  return authStore && authStore.hasPermission ? authStore.hasPermission(permission) : false;
};

const navigateTo = (routeName, query = {}) => {
  if (router && routeName) {
    router.push({ name: routeName, query });
  }
};
</script>
