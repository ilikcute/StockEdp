<template>
  <div class="space-y-6 p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Laporan Hasil Stock Opname
        </h1>
        <p class="text-xs text-gray-500 mt-1">
          Periode laporan menggunakan waktu posting opname (POSTED_AT).
        </p>
      </div>
    </div>

    <StockOpnameReportFilters
      :filters="filters"
      :master-store="masterStore"
      @update:filter="(key, val) => filters[key] = val"
      @product-search="onProductSearch"
      @select-product="selectProduct"
      @reset="resetFilters"
    />

    <div
      v-if="localValidationError"
      class="rounded-md bg-amber-50 p-4 text-sm text-amber-700"
    >
      {{ localValidationError }}
    </div>

    <ReportFeedbackPanels
      :loading="store.loading"
      :error="store.error"
      :status="store.status"
      :validation-errors="store.validationErrors"
      :has-data="store.data.length > 0"
      :has-fetched="hasFetched"
      @retry="fetchData(1)"
      @reset="resetFilters"
    />

    <div
      v-if="store.summary"
      class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
    >
      <div class="rounded-lg bg-white p-4 shadow">
        <div class="text-xs font-medium text-gray-500">
          Total Filtered Rows
        </div>
        <div class="mt-1 text-xl font-bold text-gray-900">
          {{ store.summary.total_rows }}
        </div>
      </div>
      <div class="rounded-lg bg-white p-4 shadow">
        <div class="text-xs font-medium text-gray-500">
          Total Dokumen Opname
        </div>
        <div class="mt-1 text-xl font-bold text-gray-900">
          {{ store.summary.total_documents }}
        </div>
      </div>
      <div class="rounded-lg bg-white p-4 shadow">
        <div class="text-xs font-medium text-gray-500">
          Item Selisih Positif
        </div>
        <div class="mt-1 text-xl font-bold text-green-600">
          {{ store.summary.positive_item_count }}
        </div>
      </div>
      <div class="rounded-lg bg-white p-4 shadow">
        <div class="text-xs font-medium text-gray-500">
          Item Selisih Negatif
        </div>
        <div class="mt-1 text-xl font-bold text-red-600">
          {{ store.summary.negative_item_count }}
        </div>
      </div>
    </div>

    <QuantityByUnitSummary
      v-if="store.summary"
      :summary="store.summary"
    />

    <div
      v-if="!store.loading && !store.error && store.data.length > 0"
      class="space-y-4"
    >
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs text-gray-500 px-1">
        <span>Menampilkan {{ store.data.length }} baris item opname</span>
        <span>Total filtered item: {{ store.pagination?.total || 0 }}</span>
      </div>

      <div class="rounded-lg bg-white shadow overflow-hidden">
        <StockOpnameReportTable :items="store.data" />
      </div>

      <ReportPagination
        :pagination="store.pagination"
        :loading="store.loading"
        @page-change="fetchData"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { useStockOpnameReportStore } from '../stores/useStockOpnameReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';
import { cleanReportFilters, validatePeriod } from '../utils/reportHelpers';
import StockOpnameReportFilters from '../components/opname/StockOpnameReportFilters.vue';
import StockOpnameReportTable from '../components/opname/StockOpnameReportTable.vue';
import ReportPagination from '../components/ReportPagination.vue';
import ReportFeedbackPanels from '../components/ReportFeedbackPanels.vue';
import QuantityByUnitSummary from '../components/QuantityByUnitSummary.vue';

const store = useStockOpnameReportStore();
const masterStore = useReportFilterOptionsStore();

const defaultFilters = {
    location_id: '',
    product_id: '',
    category_id: '',
    unit_id: '',
    variance_direction: '',
    is_unexpected: '',
    start_date: '',
    end_date: '',
    search: '',
    sort_by: 'posted_at',
    sort_order: 'desc',
    per_page: '15',
};

const filters = reactive({ ...defaultFilters });
const localValidationError = ref('');
const hasFetched = ref(false);

let debounceTimer = null;
const debouncedFetch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchData(1), 500);
};

watch(() => ({ ...filters }), debouncedFetch, { deep: true });

let productSearchTimer = null;
const onProductSearch = (query) => {
    clearTimeout(productSearchTimer);
    productSearchTimer = setTimeout(() => {
        if (query.trim().length >= 2) {
            masterStore.searchProducts(query);
        }
    }, 400);
};

const selectProduct = (prod) => {
    filters.product_id = prod.id;
};

const buildParams = (page) => {
    const periodCheck = validatePeriod(filters.start_date, filters.end_date);
    if (!periodCheck.valid) {
        localValidationError.value = periodCheck.message;
        return null;
    }
    localValidationError.value = '';
    return cleanReportFilters({ page, ...filters });
};

const fetchData = async (page = 1) => {
    const params = buildParams(page);
    if (!params) return;
    hasFetched.value = true;
    await store.fetchReport(params);
};

const resetFilters = () => {
    clearTimeout(debounceTimer);
    Object.assign(filters, defaultFilters);
    store.reset();
    hasFetched.value = false;
    debouncedFetch();
};

onMounted(async () => {
    await masterStore.fetchBaseOptions();
    fetchData(1);
});
</script>
