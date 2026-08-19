<template>
  <div class="space-y-6">
    <!-- 1. Header & Title -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">
          Pusat Rekomendasi Reorder & Action Center
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Analisis kekurangan stok, transfer in-transit, dan alokasi surplus gudang internal untuk dukungan keputusan reorder dan persiapan transfer.
        </p>
      </div>

      <div
        v-if="generatedAt"
        class="text-xs text-gray-500 text-right bg-white px-3 py-1.5 rounded-lg border border-gray-200 self-start sm:self-auto shadow-xs"
      >
        <span class="text-gray-400">Terakhir diperbarui:</span>
        <span class="font-medium text-gray-700 ml-1">{{ formatTimestamp(generatedAt) }}</span>
      </div>
    </div>

    <!-- 2. Target Frozen Warning Banner -->
    <div
      v-if="isTargetLocationFrozen"
      class="p-4 rounded-xl bg-amber-50 border border-amber-200 flex items-start gap-3 shadow-xs"
    >
      <svg
        class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"
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
      <div class="text-sm text-amber-800">
        <span class="font-semibold">Lokasi Target Dibekukan (Frozen):</span>
        Lokasi target saat ini sedang dibekukan oleh sesi Stock Opname aktif. Rekomendasi tetap ditampilkan untuk perencanaan, namun pembuatan dokumen transfer dinonaktifkan hingga lokasi dibuka kembali.
      </div>
    </div>

    <!-- 3. Error Banner -->
    <div
      v-if="error"
      class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-sm text-rose-800 flex items-center justify-between shadow-xs"
    >
      <div class="flex items-center gap-2">
        <svg
          class="w-4 h-4 text-rose-600 shrink-0"
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
        class="text-xs font-semibold text-rose-600 hover:text-rose-800 underline ml-2 cursor-pointer"
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
      @review-transfer-items="openReviewModal"
    />

    <!-- 7. Pagination & Informational Disclaimer -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2 text-xs text-gray-500">
      <div class="italic max-w-lg">
        Rekomendasi dihitung dari kondisi stok saat ini. Validasi stok final tetap dilakukan secara live sebelum formulir transfer disiapkan.
      </div>

      <!-- Pagination Controls -->
      <div
        v-if="meta.total > 0"
        class="flex items-center gap-2"
      >
        <span class="text-xs font-medium mr-2 text-gray-600">
          Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari {{ meta.total }} produk
        </span>

        <button
          type="button"
          :disabled="meta.current_page <= 1 || loading"
          class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-xs hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
          @click="changePage(meta.current_page - 1)"
        >
          Sebelumnya
        </button>

        <span class="px-2 font-medium text-gray-700">
          {{ meta.current_page }} / {{ meta.last_page }}
        </span>

        <button
          type="button"
          :disabled="meta.current_page >= meta.last_page || loading"
          class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-xs hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
          @click="changePage(meta.current_page + 1)"
        >
          Berikutnya
        </button>
      </div>
    </div>

    <!-- 8. Action Review Modal -->
    <ReplenishmentActionReviewModal
      :is-open="isReviewModalOpen"
      :review-items="reviewItems"
      :target-location-id="Number(filters.location_id)"
      :validating="validatingAction"
      :conflict-error="conflictError"
      :general-error="generalError"
      @close="closeReviewModal"
      @validate-and-proceed="handleValidateAndProceedTransfer"
      @refresh-data="fetchRecommendations"
    />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useReplenishment } from '../composables/use_replenishment.js';
import ReplenishmentSummaryCards from '../components/ReplenishmentSummaryCards.vue';
import ReplenishmentFilterBar from '../components/ReplenishmentFilterBar.vue';
import ReplenishmentRecommendationTable from '../components/ReplenishmentRecommendationTable.vue';
import ReplenishmentActionReviewModal from '../components/ReplenishmentActionReviewModal.vue';

const router = useRouter();

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
  isReviewModalOpen,
  reviewItems,
  validatingAction,
  conflictError,
  generalError,
  fetchFilterOptions,
  fetchRecommendations,
  openReviewModal,
  closeReviewModal,
  validateAction,
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

const handleValidateAndProceedTransfer = async (payload) => {
  const res = await validateAction({
    target_location_id: payload.target_location_id,
    items: payload.items,
  });

  if (res.success) {
    closeReviewModal();
    const firstItem = payload.items[0];

    // Navigate to transfer create form with safe prefill parameters and history state
    router.push({
      path: '/inventory/transfers/create',
      query: {
        source: 'replenishment',
        origin_location_id: firstItem.source_location_id,
        destination_location_id: payload.target_location_id,
        product_id: firstItem.product_id,
        quantity: firstItem.requested_quantity,
      },
      state: {
        replenishment_items: payload.items.map((i) => ({
          product_id: i.product_id,
          quantity: i.requested_quantity,
        })),
      },
    });
  }
};

const formatTimestamp = (isoString) => {
  if (!isoString) return '';
  const date = new Date(isoString);
  return date.toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
};

onMounted(async () => {
  await fetchFilterOptions();
  if (filterOptions.locations?.length > 0 && !filters.location_id) {
    filters.location_id = filterOptions.locations[0].id;
    await fetchRecommendations();
  }
});
</script>
