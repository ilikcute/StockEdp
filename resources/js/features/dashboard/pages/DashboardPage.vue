<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2.5">
          <span class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm font-black shadow-sm">
            EDP
          </span>
          Dashboard Operasional Persediaan
        </h1>
        <p class="text-xs text-gray-500 mt-1">
          Pusat pemantauan kesehatan persediaan, antrean operasional, dan peringatan real-time.
        </p>
      </div>

      <div
        v-if="dashboardData?.generated_at"
        class="text-xs text-gray-500 text-right bg-white px-3 py-1.5 rounded-lg border border-gray-200 self-start sm:self-auto shadow-xs"
      >
        <span class="text-gray-400">Terakhir diperbarui:</span>
        <span class="font-medium text-gray-700 ml-1">{{ formatTimestamp(dashboardData.generated_at) }}</span>
      </div>
    </div>

    <!-- Filter Bar -->
    <DashboardFilterBar
      v-model:location-id="filters.location_id"
      v-model:period="filters.period"
      :locations="dashboardData?.filter_options?.locations || []"
      :loading="loading"
      :date-from="dashboardData?.filters?.date_from"
      :date-to="dashboardData?.filters?.date_to"
      @refresh="fetchDashboard"
      @update:location-id="onFilterChange"
      @update:period="onFilterChange"
    />

    <!-- Error Alert -->
    <div
      v-if="error"
      class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-center justify-between"
    >
      <div class="flex items-center gap-2">
        <svg
          class="w-4 h-4 text-rose-600"
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
        class="underline font-semibold hover:text-rose-900 cursor-pointer"
        @click="fetchDashboard"
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
          class="h-24 bg-gray-200 rounded-xl"
        />
      </div>
      <div class="h-40 bg-gray-200 rounded-xl" />
      <div class="h-48 bg-gray-200 rounded-xl" />
    </div>

    <template v-else-if="dashboardData">
      <!-- 1. Inventory Health Summary Cards -->
      <InventoryHealthCards :data="dashboardData.inventory_health" />

      <!-- 2. Inventory Movement Intelligence Cards -->
      <InventoryIntelligenceCards
        :movement-data="dashboardData.inventory_movement"
        :location-id="filters.location_id"
      />

      <!-- 3. Operational Queue Cards -->
      <OperationalQueueCards :data="dashboardData.operational_queue" />

      <!-- 4. Period Activity Cards -->
      <PeriodActivityCards :data="dashboardData.period_activity" />

      <!-- 5. Computed Alert Center -->
      <DashboardAlertList :alerts="dashboardData.alerts" />

      <!-- 6. Top Movement Products Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <TopIssuedProducts :products="dashboardData.top_issued_products" />
        <TopReceivedProducts :products="dashboardData.top_received_products" />
      </div>

      <!-- 7. Recent Inventory Activity Table -->
      <RecentInventoryActivity :activities="dashboardData.recent_activity" />
    </template>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useDashboard } from '../composables/use_dashboard';
import DashboardFilterBar from '../components/DashboardFilterBar.vue';
import InventoryHealthCards from '../components/InventoryHealthCards.vue';
import InventoryIntelligenceCards from '../components/InventoryIntelligenceCards.vue';
import OperationalQueueCards from '../components/OperationalQueueCards.vue';
import PeriodActivityCards from '../components/PeriodActivityCards.vue';
import DashboardAlertList from '../components/DashboardAlertList.vue';
import RecentInventoryActivity from '../components/RecentInventoryActivity.vue';
import TopIssuedProducts from '../components/TopIssuedProducts.vue';
import TopReceivedProducts from '../components/TopReceivedProducts.vue';

const { loading, error, dashboardData, filters, fetchDashboard } = useDashboard();

const onFilterChange = () => {
  fetchDashboard();
};

const formatTimestamp = (isoString) => {
  if (!isoString) return '-';
  try {
    const d = new Date(isoString);
    return d.toLocaleString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }) + ' WIB';
  } catch {
    return isoString;
  }
};

onMounted(() => {
  fetchDashboard();
});
</script>
