<template>
  <div class="space-y-3.5">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
      <div>
        <h1 class="text-lg font-bold text-gray-900 flex items-center gap-2">
          <span class="w-6 h-6 rounded-md bg-blue-600 text-white flex items-center justify-center text-[10px] font-black shadow-2xs">
            EDP
          </span>
          Dashboard Operasional Persediaan
        </h1>
        <p class="text-[11px] text-gray-500 mt-0.5">
          Kesehatan stok, antrean operasional dokumen, dan pergerakan persediaan real-time.
        </p>
      </div>

      <div
        v-if="dashboardData?.generated_at"
        class="text-[11px] text-gray-500 text-right bg-white px-2.5 py-1 rounded-lg border border-gray-200 self-start sm:self-auto shadow-2xs"
      >
        <span class="text-gray-400">Sinkronisasi:</span>
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
      class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-center justify-between"
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
      class="space-y-3.5 animate-pulse"
    >
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
        <div
          v-for="i in 4"
          :key="i"
          class="h-20 bg-gray-200 rounded-xl"
        />
      </div>
      <div class="h-24 bg-gray-200 rounded-xl" />
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-3.5">
        <div class="h-36 bg-gray-200 rounded-xl" />
        <div class="h-36 bg-gray-200 rounded-xl" />
      </div>
    </div>

    <template v-else-if="dashboardData">
      <!-- 1. Inventory Health Summary Cards -->
      <InventoryHealthCards
        :data="dashboardData.inventory_health"
        :location-id="filters.location_id"
      />

      <!-- 2. Replenishment Action Center Ribbon -->
      <ReplenishmentActionCard :location-id="filters.location_id" />

      <!-- 3. Operational Queue & Period Activity Grid (Side by side) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-3.5 items-stretch">
        <OperationalQueueCards :data="dashboardData.operational_queue" />
        <PeriodActivityCards :data="dashboardData.period_activity" />
      </div>

      <!-- 4. Movement Intelligence & Alert Center Grid (Side by side) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-3.5 items-stretch">
        <InventoryIntelligenceCards
          :movement-data="dashboardData.inventory_movement"
          :location-id="filters.location_id"
        />
        <DashboardAlertList :alerts="dashboardData.alerts" />
      </div>

      <!-- 5. Top Movement Products Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-3.5 items-stretch">
        <TopIssuedProducts :products="dashboardData.top_issued_products" />
        <TopReceivedProducts :products="dashboardData.top_received_products" />
      </div>

      <!-- 6. Recent Inventory Activity Table -->
      <RecentInventoryActivity :activities="dashboardData.recent_activity" />
    </template>
  </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { useDashboard } from '../composables/use_dashboard';
import DashboardFilterBar from '../components/DashboardFilterBar.vue';
import InventoryHealthCards from '../components/InventoryHealthCards.vue';
import ReplenishmentActionCard from '../components/ReplenishmentActionCard.vue';
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

// Reaktif terhadap perubahan filters (misal ketika auto-select gudang induk aktif)
watch(
  () => [filters.location_id, filters.period],
  (newVals, oldVals) => {
    if (oldVals && (newVals[0] !== oldVals[0] || newVals[1] !== oldVals[1])) {
      fetchDashboard();
    }
  }
);

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
