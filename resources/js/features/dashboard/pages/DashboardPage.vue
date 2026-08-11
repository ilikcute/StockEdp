<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 p-4 sm:p-6 lg:p-8">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
          <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm font-black shadow-sm">
            EDP
          </span>
          Dashboard Operasional Persediaan
        </h1>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          Pusat pemantauan kesehatan persediaan, antrean operasional, dan peringatan real-time.
        </p>
      </div>
    </div>

    <!-- Filter Bar -->
    <DashboardFilterBar
      v-model:location-id="filters.location_id"
      v-model:period="filters.period"
      :locations="locations"
      :loading="loading"
      :date-from="dashboardData?.filters?.date_from"
      :date-to="dashboardData?.filters?.date_to"
      @refresh="loadAll"
      @update:location-id="onFilterChange"
      @update:period="onFilterChange"
    />

    <!-- Error Alert -->
    <div
      v-if="error"
      class="mb-6 p-4 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-xl text-rose-800 dark:text-rose-200 text-xs flex items-center justify-between"
    >
      <div class="flex items-center gap-2">
        <svg
          class="w-4 h-4 text-rose-600 dark:text-rose-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>{{ error }}</span>
      </div>
      <button
        type="button"
        class="underline font-semibold hover:text-rose-900"
        @click="loadAll"
      >
        Coba Lagi
      </button>
    </div>

    <!-- Loading Skeleton -->
    <div
      v-if="loading && !dashboardData"
      class="space-y-6 animate-pulse"
    >
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="i in 4"
          :key="i"
          class="h-24 bg-gray-200 dark:bg-gray-800 rounded-xl"
        />
      </div>
      <div class="h-40 bg-gray-200 dark:bg-gray-800 rounded-xl" />
      <div class="h-48 bg-gray-200 dark:bg-gray-800 rounded-xl" />
    </div>

    <template v-else-if="dashboardData">
      <!-- 1. Inventory Health Summary Cards -->
      <InventoryHealthCards :data="dashboardData.inventory_health" />

      <!-- 2. Operational Queue Cards -->
      <OperationalQueueCards :data="dashboardData.operational_queue" />

      <!-- 3. Computed Alert Center -->
      <DashboardAlertList :alerts="dashboardData.alerts" />

      <!-- 4. Top Movement Products Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <TopIssuedProducts :products="dashboardData.top_issued_products" />
        <TopReceivedProducts :products="dashboardData.top_received_products" />
      </div>

      <!-- 5. Recent Inventory Activity Table -->
      <RecentInventoryActivity :activities="dashboardData.recent_activity" />
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import apiClient from '@/shared/api/api_client';
import { useDashboard } from '../composables/use_dashboard';
import DashboardFilterBar from '../components/DashboardFilterBar.vue';
import InventoryHealthCards from '../components/InventoryHealthCards.vue';
import OperationalQueueCards from '../components/OperationalQueueCards.vue';
import DashboardAlertList from '../components/DashboardAlertList.vue';
import RecentInventoryActivity from '../components/RecentInventoryActivity.vue';
import TopIssuedProducts from '../components/TopIssuedProducts.vue';
import TopReceivedProducts from '../components/TopReceivedProducts.vue';

const locations = ref([]);
const { loading, error, dashboardData, filters, fetchDashboard } = useDashboard();

const fetchLocations = async () => {
  try {
    const res = await apiClient.get('/locations', { params: { is_active: 1, per_page: 200 } });
    if (res?.data?.success) {
      locations.value = res.data.data?.data || res.data.data || [];
    }
  } catch (err) {
    console.error('Failed to load locations for dashboard filter:', err);
  }
};

const loadAll = async () => {
  await Promise.all([
    fetchLocations(),
    fetchDashboard(),
  ]);
};

const onFilterChange = () => {
  fetchDashboard();
};

onMounted(() => {
  loadAll();
});
</script>
