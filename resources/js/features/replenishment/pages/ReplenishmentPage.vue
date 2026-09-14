<template>
  <div class="space-y-3">
    <!-- 1. Header & Title (Compact) -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-base font-bold text-gray-900 leading-tight flex items-center gap-1.5">
          <svg
            class="w-4 h-4 text-indigo-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 10V3L4 14h7v7l9-11h-7z"
            />
          </svg>
          Pusat Rekomendasi Reorder & Action Center
        </h1>
        <p class="text-[11px] text-gray-500 mt-0.5">
          Analisis kekurangan stok, transfer in-transit, dan alokasi surplus gudang internal untuk dukungan keputusan reorder dan persiapan transfer.
        </p>
      </div>

      <div
        v-if="generatedAt"
        class="flex items-center gap-1.5 text-[11px] text-gray-500 bg-gray-50 px-2.5 py-1 rounded-lg border border-gray-200 shrink-0 font-mono shadow-2xs"
      >
        <span class="text-gray-400">Diperbarui:</span>
        <span class="font-medium text-gray-700">{{ formatTimestamp(generatedAt) }}</span>
      </div>
    </div>

    <!-- 2. Target Frozen Warning Banner -->
    <div
      v-if="isTargetLocationFrozen"
      class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 flex items-start gap-2.5 shadow-2xs text-xs text-amber-800"
    >
      <svg
        class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"
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
      <div>
        <span class="font-bold">Lokasi Target Dibekukan (Frozen):</span>
        Lokasi target saat ini sedang dibekukan oleh sesi Stock Opname aktif. Rekomendasi tetap ditampilkan untuk perencanaan, namun pembuatan dokumen transfer dinonaktifkan hingga lokasi dibuka kembali.
      </div>
    </div>

    <!-- 3. Error Banner -->
    <div
      v-if="error"
      class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-center justify-between shadow-2xs"
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

    <!-- 7. Informational Disclaimer & Pagination -->
    <div class="space-y-2">
      <div class="text-[11px] text-gray-500 italic">
        * Rekomendasi dihitung dari kondisi stok saat ini. Validasi stok final tetap dilakukan secara live sebelum formulir transfer disiapkan.
      </div>

      <BasePagination
        :pagination="meta"
        :loading="loading"
        @change="changePage"
      />
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
import { formatTimestamp } from '@/shared/utils/formatters.js';
import ReplenishmentSummaryCards from '../components/ReplenishmentSummaryCards.vue';
import ReplenishmentFilterBar from '../components/ReplenishmentFilterBar.vue';
import ReplenishmentRecommendationTable from '../components/ReplenishmentRecommendationTable.vue';
import ReplenishmentActionReviewModal from '../components/ReplenishmentActionReviewModal.vue';
import BasePagination from '@/shared/components/BasePagination.vue';

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

onMounted(async () => {
  await fetchFilterOptions();
  if (filterOptions.locations?.length > 0 && !filters.location_id) {
    filters.location_id = filterOptions.locations[0].id;
    await fetchRecommendations();
  }
});
</script>
