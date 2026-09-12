<template>
  <div class="app-layout min-h-screen bg-gray-50 flex">
    <!-- Reusable Sidebar -->
    <AppSidebar
      v-if="authStore.isAuthenticated"
      :is-mobile-open="isMobileMenuOpen"
      :is-collapsed="isSidebarCollapsed"
      @update:is-collapsed="handleSidebarCollapse"
      @close-mobile="isMobileMenuOpen = false"
      @logout="handleLogout"
    />

    <!-- Content Column -->
    <div
      :class="[
        'flex-1 flex flex-col min-w-0 transition-all duration-200 ease-in-out',
        authStore.isAuthenticated ? (isSidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64') : ''
      ]"
    >
      <!-- Top Header Bar -->
      <header class="bg-white border-b border-gray-200 sticky top-0 z-30 pt-safe">
        <div class="px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
          <!-- Left side: Mobile Toggle & Page Title / Breadcrumb -->
          <div class="flex items-center gap-3">
            <!-- Mobile Menu Trigger -->
            <button
              v-if="authStore.isAuthenticated"
              type="button"
              class="lg:hidden p-2 rounded-lg text-gray-700 hover:text-indigo-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer min-w-[36px] min-h-[36px] flex items-center justify-center"
              aria-label="Buka menu navigasi"
              @click="isMobileMenuOpen = true"
            >
              <svg
                class="h-5 w-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16"
                />
              </svg>
            </button>

            <!-- Brand on mobile when sidebar is closed -->
            <div class="flex items-center gap-2 lg:hidden">
              <span class="w-6 h-6 rounded bg-indigo-600 text-white flex items-center justify-center text-[10px] font-black">
                EDP
              </span>
              <span class="text-sm font-bold text-gray-900">StockEdp</span>
            </div>

            <!-- Page context indicator on desktop -->
            <div class="hidden lg:flex items-center gap-2 text-xs text-gray-500">
              <span class="font-medium text-gray-700">{{ currentPageTitle }}</span>
            </div>
          </div>

          <!-- Right side: User Profile & Quick Actions -->
          <div
            v-if="authStore.isAuthenticated"
            class="flex items-center gap-3"
          >
            <!-- User Info Pill -->
            <div class="hidden sm:flex items-center gap-2 text-right">
              <div class="text-right">
                <p class="text-xs font-bold text-gray-900 leading-tight">
                  {{ authStore.user?.name }}
                </p>
                <p class="text-[10px] text-gray-400 font-mono">
                  {{ authStore.user?.username }}
                </p>
              </div>
              <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs border border-indigo-100">
                {{ (authStore.user?.name || 'U').charAt(0).toUpperCase() }}
              </div>
            </div>

            <!-- System Health Indicator -->
            <button
              v-if="authStore.isAuthenticated"
              type="button"
              class="hidden sm:flex items-center gap-1.5 px-2 py-1 rounded-lg text-xs font-medium transition-colors cursor-pointer"
              :class="healthStatusClass"
              :title="healthStatusTitle"
              @click="checkHealth"
            >
              <span
                class="w-2 h-2 rounded-full animate-pulse"
                :class="healthDotClass"
              />
              {{ healthStatusLabel }}
            </button>

            <!-- Quick Logout Button -->
            <button
              type="button"
              class="px-2.5 py-1 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors cursor-pointer"
              title="Keluar dari sesi"
              @click="handleLogout"
            >
              Keluar
            </button>
          </div>
        </div>
      </header>

      <!-- Main Page Content -->
      <main class="flex-1 w-full max-w-[1600px] mx-auto px-3 sm:px-5 lg:px-6 py-3.5 sm:py-4 pb-20 md:pb-6">
        <router-view />
      </main>

      <!-- Mobile Bottom Bar -->
      <MobileBottomBar
        v-if="authStore.isAuthenticated"
        @toggle-menu="isMobileMenuOpen = !isMobileMenuOpen"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import { systemApi } from '@/shared/api/system_api';
import AppSidebar from './navigation/AppSidebar.vue';
import MobileBottomBar from './navigation/MobileBottomBar.vue';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const isMobileMenuOpen = ref(false);
const isSidebarCollapsed = ref(localStorage.getItem('stockedp_sidebar_collapsed') === 'true');

const handleSidebarCollapse = (collapsed) => {
  isSidebarCollapsed.value = collapsed;
  try {
    localStorage.setItem('stockedp_sidebar_collapsed', String(collapsed));
  } catch {
    // ignore storage error
  }
};

watch(() => route.path, () => {
  isMobileMenuOpen.value = false;
});

// ---- Health Check ----
const healthStatus = ref('checking'); // 'healthy' | 'degraded' | 'checking' | 'error'

const healthStatusLabel = computed(() => {
  if (healthStatus.value === 'healthy') return 'Sistem OK';
  if (healthStatus.value === 'degraded') return 'Degraded';
  if (healthStatus.value === 'checking') return 'Checking...';
  return 'Offline';
});
const healthStatusTitle = computed(() => {
  if (healthStatus.value === 'healthy') return 'Sistem berjalan normal. Klik untuk cek ulang.';
  if (healthStatus.value === 'degraded') return 'Sistem berjalan dengan gangguan. Klik untuk cek ulang.';
  return 'Tidak dapat terhubung ke server. Klik untuk cek ulang.';
});
const healthStatusClass = computed(() => {
  if (healthStatus.value === 'healthy') return 'bg-green-50 text-green-700 hover:bg-green-100';
  if (healthStatus.value === 'degraded') return 'bg-yellow-50 text-yellow-700 hover:bg-yellow-100';
  if (healthStatus.value === 'checking') return 'bg-gray-50 text-gray-500';
  return 'bg-red-50 text-red-700 hover:bg-red-100';
});
const healthDotClass = computed(() => {
  if (healthStatus.value === 'healthy') return 'bg-green-500';
  if (healthStatus.value === 'degraded') return 'bg-yellow-500';
  if (healthStatus.value === 'checking') return 'bg-gray-400';
  return 'bg-red-500';
});

async function checkHealth() {
  healthStatus.value = 'checking';
  try {
    const response = await systemApi.getHealth();
    healthStatus.value = response.data?.data?.status || 'healthy';
  } catch {
    healthStatus.value = 'error';
  }
}

let healthInterval = null;
onMounted(() => {
  if (authStore.isAuthenticated) {
    checkHealth();
    healthInterval = setInterval(checkHealth, 60000); // check every 60s
  }
});
onUnmounted(() => {
  if (healthInterval) clearInterval(healthInterval);
});
// ---- End Health Check ----

const currentPageTitle = computed(() => {
  const path = route.path;
  if (path === '/dashboard') return 'Dashboard Operasional';
  if (path.startsWith('/inventory/store-allocations')) return 'Alokasi Toko & Teknisi';
  if (path.startsWith('/inventory/receipts')) return 'Penerimaan Stok (Goods Receipt)';
  if (path.startsWith('/inventory/issues')) return 'Pengeluaran Stok (Goods Issue)';
  if (path.startsWith('/inventory/transfers')) return 'Transfer Antar Lokasi';
  if (path.startsWith('/inventory/movements')) return 'Riwayat Pergerakan Stok';
  if (path.startsWith('/inventory/replenishment')) return 'Rekomendasi Replenishment';
  if (path.startsWith('/inventory/adjustments')) return 'Penyesuaian Stok';
  if (path.startsWith('/inventory/opnames')) return 'Stock Opname Fisik';
  if (path.startsWith('/reports/inventory-balances')) return 'Laporan Saldo Persediaan';
  if (path.startsWith('/reports/field-balances')) return 'Laporan Saldo Teknisi Lapangan';
  if (path.startsWith('/reports/store-allocations')) return 'Laporan Alokasi & Penarikan Toko';
  if (path.startsWith('/reports')) return 'Laporan & Intelijen Persediaan';
  if (path.startsWith('/stores')) return 'Master Toko Retail';
  if (path.startsWith('/products')) return 'Master Produk';
  if (path.startsWith('/categories')) return 'Master Kategori';
  if (path.startsWith('/locations')) return 'Master Lokasi Persediaan';
  if (path.startsWith('/suppliers')) return 'Master Supplier';
  if (path.startsWith('/users')) return 'Manajemen Pengguna';
  if (path === '/profile') return 'Profil Saya';
  return 'StockEdp Management';
});

async function handleLogout() {
  isMobileMenuOpen.value = false;
  await authStore.logout();
  router.push('/login');
}
</script>
