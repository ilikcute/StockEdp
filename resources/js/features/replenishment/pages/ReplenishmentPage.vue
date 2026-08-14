<template>
  <div class="space-y-6">
    <!-- 1. Header & Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">
          Pusat Rekomendasi Reorder & Replenishment
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
          Analisis kekurangan stok, transfer in-transit, dan alokasi surplus gudang internal untuk dukungan keputusan reorder.
        </p>
      </div>
    </div>

    <!-- 2. Target Frozen Warning Banner -->
    <div
      v-if="isTargetLocationFrozen"
      class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-start gap-3 shadow-sm"
    >
      <svg
        class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"
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
      <div class="text-sm text-amber-800 dark:text-amber-200">
        <span class="font-semibold">Lokasi Target Dibekukan (Frozen):</span>
        Lokasi target saat ini sedang dibekukan oleh sesi Stock Opname aktif. Rekomendasi tetap ditampilkan untuk perencanaan, namun pembuatan dokumen transfer dinonaktifkan hingga lokasi dibuka kembali.
      </div>
    </div>

    <!-- 3. Error Banner -->
    <div
      v-if="error"
      class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-sm text-rose-800 dark:text-rose-200 flex items-center justify-between"
    >
      <span>{{ error }}</span>
      <button
        type="button"
        class="text-xs font-semibold text-rose-600 hover:text-rose-800 underline ml-2"
        @click="fetchRecommendations"
      >
        Coba Lagi
      </button>
    </div>

    <!-- 4. Summary Metrics Cards -->
    <ReplenishmentSummaryCards :summary="summary" />

    <!-- 5. Filters Bar -->
    <ReplenishmentFilterBar
      :filters="filters"
      :filter-options="filterOptions"
      :loading="loading || filterOptionsLoading"
      :generated-at="generatedAt"
      @update:filter="handleFilterUpdate"
      @search="handleFilterChange"
      @reset="resetFilters"
      @refresh="fetchRecommendations"
    />

    <!-- 6. Recommendations Table -->
    <ReplenishmentRecommendationTable
      :items="recommendations"
      :target-location-id="filters.location_id"
      :loading="loading"
    />

    <!-- 7. Pagination & Informational Disclaimer -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2 text-xs text-slate-500 dark:text-slate-400">
      <div class="italic max-w-lg">
        Rekomendasi dihitung dari kondisi stok saat ini. Validasi stok final tetap dilakukan saat transaksi Transfer diproses.
      </div>

      <!-- Pagination Controls -->
      <div
        v-if="meta.total > 0"
        class="flex items-center gap-2"
      >
        <span class="text-xs font-medium mr-2">
          Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari {{ meta.total }} produk
        </span>

        <button
          type="button"
          :disabled="meta.current_page <= 1 || loading"
          class="px-2.5 py-1 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 disabled:opacity-40 hover:bg-slate-50 transition-colors"
          @click="changePage(meta.current_page - 1)"
        >
          Sebelumnya
        </button>

        <span class="px-2 font-mono font-semibold text-slate-700 dark:text-slate-200">
          {{ meta.current_page }} / {{ meta.last_page }}
        </span>

        <button
          type="button"
          :disabled="meta.current_page >= meta.last_page || loading"
          class="px-2.5 py-1 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 disabled:opacity-40 hover:bg-slate-50 transition-colors"
          @click="changePage(meta.current_page + 1)"
        >
          Berikutnya
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useReplenishment } from '../composables/use_replenishment.js';
import ReplenishmentSummaryCards from '../components/ReplenishmentSummaryCards.vue';
import ReplenishmentFilterBar from '../components/ReplenishmentFilterBar.vue';
import ReplenishmentRecommendationTable from '../components/ReplenishmentRecommendationTable.vue';

const {
  loading,
  filterOptionsLoading,
  error,
  recommendations,
  generatedAt,
  summary,
  meta,
  filterOptions,
  filters,
  fetchFilterOptions,
  fetchRecommendations,
  changePage,
  resetFilters,
} = useReplenishment();

const isTargetLocationFrozen = computed(() => {
  if (recommendations.value.length > 0) {
    return recommendations.value[0].target_is_frozen ?? false;
  }
  return false;
});

const handleFilterUpdate = ({ key, value }) => {
  filters[key] = value;
  if (key !== 'search') {
    handleFilterChange();
  }
};

const handleFilterChange = () => {
  filters.page = 1;
  fetchRecommendations();
};

onMounted(async () => {
  await fetchFilterOptions();
  if (filters.location_id) {
    await fetchRecommendations();
  }
});
</script>
