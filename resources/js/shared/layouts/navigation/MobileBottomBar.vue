<template>
  <nav
    v-if="authStore.isAuthenticated"
    class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-gray-200 pb-safe shadow-lg"
    aria-label="Navigasi Bawah Mobile"
  >
    <div class="grid grid-cols-5 h-14 items-center justify-around px-1 max-w-md mx-auto">
      <!-- 1. Dashboard -->
      <router-link
        to="/dashboard"
        class="flex flex-col items-center justify-center py-1 text-[10px] font-medium transition-colors"
        :class="isRouteActive('/dashboard') ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-900'"
      >
        <svg
          class="w-5 h-5 mb-0.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
          />
        </svg>
        <span>Dashboard</span>
      </router-link>

      <!-- 2. Master Data -->
      <router-link
        to="/products"
        class="flex flex-col items-center justify-center py-1 text-[10px] font-medium transition-colors"
        :class="isRouteActive(['/products', '/categories', '/units', '/suppliers', '/locations']) ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-900'"
      >
        <svg
          class="w-5 h-5 mb-0.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
          />
        </svg>
        <span>Master</span>
      </router-link>

      <!-- 3. Persediaan / Transaksi -->
      <router-link
        to="/inventory/transfers"
        class="flex flex-col items-center justify-center py-1 text-[10px] font-medium transition-colors"
        :class="isRouteActive(['/inventory/receipts', '/inventory/issues', '/inventory/transfers', '/inventory/adjustments', '/inventory/opnames', '/inventory/replenishment']) ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-900'"
      >
        <svg
          class="w-5 h-5 mb-0.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
          />
        </svg>
        <span>Persediaan</span>
      </router-link>

      <!-- 4. Laporan -->
      <router-link
        to="/reports/inventory-balances"
        class="flex flex-col items-center justify-center py-1 text-[10px] font-medium transition-colors"
        :class="isRouteActive(['/reports']) ? 'text-blue-600 font-bold' : 'text-gray-500 hover:text-gray-900'"
      >
        <svg
          class="w-5 h-5 mb-0.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          />
        </svg>
        <span>Laporan</span>
      </router-link>

      <!-- 5. Menu Drawer Trigger -->
      <button
        type="button"
        class="flex flex-col items-center justify-center py-1 text-[10px] font-medium text-gray-500 hover:text-gray-900 transition-colors cursor-pointer"
        aria-label="Buka Menu Lengkap"
        @click="emit('toggle-menu')"
      >
        <svg
          class="w-5 h-5 mb-0.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 6h16M4 12h16m-7 6h7"
          />
        </svg>
        <span>Menu</span>
      </button>
    </div>
  </nav>
</template>

<script setup>
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';

const route = useRoute();
const authStore = useAuthStore();

const emit = defineEmits(['toggle-menu']);

const isRouteActive = (paths) => {
  if (Array.isArray(paths)) {
    return paths.some((p) => route.path.startsWith(p));
  }
  return route.path === paths || route.path.startsWith(`${paths}/`);
};
</script>
