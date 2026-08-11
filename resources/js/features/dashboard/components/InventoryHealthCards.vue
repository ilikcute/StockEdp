<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Out of Stock Card -->
    <div
      class="bg-white dark:bg-gray-800 rounded-xl border border-rose-200 dark:border-rose-900/50 p-4 shadow-sm hover:shadow-md transition-all cursor-pointer group"
      @click="navigateTo('/inventory-balances')"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider">Stok Habis (0)</span>
        <div class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center group-hover:scale-110 transition-transform">
          <svg
            class="w-4 h-4"
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
      <div class="mt-2 flex items-baseline justify-between">
        <span
          id="stat-out-of-stock-count"
          class="text-2xl font-bold text-gray-900 dark:text-white"
        >{{ data.out_of_stock_count || 0 }}</span>
        <span class="text-xs text-rose-600 dark:text-rose-400 font-medium group-hover:underline flex items-center gap-1">
          Lihat Persediaan &rarr;
        </span>
      </div>
    </div>

    <!-- Low Stock Card -->
    <div
      class="bg-white dark:bg-gray-800 rounded-xl border border-amber-200 dark:border-amber-900/50 p-4 shadow-sm hover:shadow-md transition-all cursor-pointer group"
      @click="navigateTo('/reports/low-stock')"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wider">Di Bawah Min. Stok</span>
        <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform">
          <svg
            class="w-4 h-4"
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
      <div class="mt-2 flex items-baseline justify-between">
        <span
          id="stat-low-stock-count"
          class="text-2xl font-bold text-gray-900 dark:text-white"
        >{{ data.low_stock_count || 0 }}</span>
        <span class="text-xs text-amber-600 dark:text-amber-400 font-medium group-hover:underline flex items-center gap-1">
          Laporan Stok &rarr;
        </span>
      </div>
    </div>

    <!-- Active Opname Card -->
    <div
      class="bg-white dark:bg-gray-800 rounded-xl border border-purple-200 dark:border-purple-900/50 p-4 shadow-sm hover:shadow-md transition-all cursor-pointer group"
      @click="navigateTo('/stock-opnames')"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase tracking-wider">Opname Aktif</span>
        <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center group-hover:scale-110 transition-transform">
          <svg
            class="w-4 h-4"
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
      <div class="mt-2 flex items-baseline justify-between">
        <span
          id="stat-active-opname-count"
          class="text-2xl font-bold text-gray-900 dark:text-white"
        >{{ data.active_opname_count || 0 }}</span>
        <span class="text-xs text-purple-600 dark:text-purple-400 font-medium group-hover:underline flex items-center gap-1">
          Kelola Opname &rarr;
        </span>
      </div>
    </div>

    <!-- Frozen Location Card -->
    <div
      class="bg-white dark:bg-gray-800 rounded-xl border border-cyan-200 dark:border-cyan-900/50 p-4 shadow-sm hover:shadow-md transition-all cursor-pointer group"
      @click="navigateTo('/locations')"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs font-medium text-cyan-600 dark:text-cyan-400 uppercase tracking-wider">Lokasi Beku</span>
        <div class="w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400 flex items-center justify-center group-hover:scale-110 transition-transform">
          <svg
            class="w-4 h-4"
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
      <div class="mt-2 flex items-baseline justify-between">
        <span
          id="stat-frozen-location-count"
          class="text-2xl font-bold text-gray-900 dark:text-white"
        >{{ data.frozen_location_count || 0 }}</span>
        <span class="text-xs text-cyan-600 dark:text-cyan-400 font-medium group-hover:underline flex items-center gap-1">
          Status Lokasi &rarr;
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';

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
});

const router = useRouter();

const navigateTo = (path) => {
  if (router) {
    router.push(path);
  }
};
</script>
