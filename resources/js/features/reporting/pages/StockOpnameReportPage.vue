<template>
  <div class="space-y-3">
    <!-- TOP Header Compact -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-base font-bold text-gray-900 tracking-tight flex items-center gap-1.5">
            <svg
              class="w-4 h-4 text-teal-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
              />
            </svg>
            Laporan Hasil Stock Opname
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Periode laporan menggunakan waktu posting opname (POSTED_AT).
          </p>
        </div>
        <div class="flex items-center gap-2">
          <ReportExportControl
            size="sm"
            :loading="exportStore.isExporting(reportKey)"
            :disabled="false"
            :error="exportStore.errorFor(reportKey)"
            :status="exportStore.statusFor(reportKey)"
            :validation-errors="exportStore.validationErrorsFor(reportKey)"
            :success-message="exportStore.successFor(reportKey)"
            @export="exportCsv"
            @dismiss="exportStore.clearFeedback(reportKey)"
          />
        </div>
      </div>
    </div>

    <!-- Filters Compact -->
    <StockOpnameReportFilters
      :filters="filters"
      :master-store="masterStore"
      :product-search="productSearch"
      @update:filter="(key, val) => filters[key] = val"
      @update:product-search="val => productSearch = val"
      @product-search="onProductSearch"
      @select-product="selectProduct"
      @clear-product="clearProduct"
      @reset="resetFilters"
    />

    <ReportFeedbackPanels
      :loading="store.loading"
      :error="store.error"
      :status="store.status"
      :validation-errors="store.validationErrors"
      :local-validation-error="localValidationError"
      :has-data="store.data.length > 0"
      :has-fetched="hasFetched"
      empty-message="Tidak ada data hasil opname yang sesuai filter."
      @retry="fetchData(1)"
      @reset-filters="resetFilters"
    />

    <QuantityByUnitSummary
      v-if="store.summary"
      :summary="store.summary"
      :total-documents="store.summary.total_documents"
      :total-rows="store.summary.total_rows"
    />

    <div
      v-if="!store.loading && !store.error && store.data.length > 0"
      class="space-y-3"
    >
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-[11px] text-gray-500 px-1">
        <span>Menampilkan {{ store.data.length }} baris item hasil opname</span>
        <span>Total filtered item: {{ store.pagination?.total || 0 }}</span>
      </div>

      <div class="rounded-xl bg-white shadow-2xs border border-gray-200 overflow-hidden">
        <StockOpnameReportTable
          :items="store.data"
          :pagination="store.pagination"
        />
      </div>

      <BasePagination
        :pagination="store.pagination"
        :loading="store.loading"
        @change="fetchData"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useStockOpnameReportStore } from '../stores/useStockOpnameReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import { cleanReportFilters, cleanReportExportFilters, validatePeriod } from '../utils/reportHelpers';
import StockOpnameReportFilters from '../components/opname/StockOpnameReportFilters.vue';
import StockOpnameReportTable from '../components/opname/StockOpnameReportTable.vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import ReportFeedbackPanels from '../components/ReportFeedbackPanels.vue';
import QuantityByUnitSummary from '../components/QuantityByUnitSummary.vue';
import ReportExportControl from '../components/ReportExportControl.vue';

const route = useRoute();
const store = useStockOpnameReportStore();
const masterStore = useReportFilterOptionsStore();
const exportStore = useReportCsvExportStore();
const reportKey = 'stock-opnames';

const defaultFilters = {
    location_id: '',
    variance_direction: '',
    is_unexpected: '',
    product_id: '',
    category_id: '',
    unit_id: '',
    start_date: '',
    end_date: '',
    search: '',
    sort_by: 'posted_at',
    sort_order: 'desc',
    per_page: '15',
};

const filters = reactive({ ...defaultFilters });
const productSearch = ref('');
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
    productSearch.value = query;
    filters.product_id = '';
    clearTimeout(productSearchTimer);
    productSearchTimer = setTimeout(() => {
        if (query.trim().length >= 2) {
            masterStore.searchProducts(query);
        }
    }, 300);
};

const selectProduct = (prod) => {
    filters.product_id = prod.id;
    productSearch.value = prod.name;
};

const clearProduct = () => {
    filters.product_id = '';
    productSearch.value = '';
    masterStore.resetProducts();
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
    if (!params) {
        store.reset();
        hasFetched.value = false;
        return;
    }
    hasFetched.value = true;
    await store.fetchReport(params);
};

const exportCsv = async (format = 'xlsx') => {
    const exportFormat = typeof format === 'string' && (format.toLowerCase() === 'csv' || format.toLowerCase() === 'xlsx')
        ? format.toLowerCase()
        : 'xlsx';
    const periodCheck = validatePeriod(filters.start_date, filters.end_date);
    if (!periodCheck.valid) {
        localValidationError.value = periodCheck.message;
        return;
    }
    localValidationError.value = '';
    const params = cleanReportExportFilters({ ...filters, format: exportFormat });
    await exportStore.exportReport(reportKey, params);
};

const resetFilters = () => {
    clearTimeout(debounceTimer);
    clearTimeout(productSearchTimer);

    productSearch.value = '';
    masterStore.resetProducts();

    Object.assign(filters, defaultFilters);
    store.reset();
    hasFetched.value = false;
    debouncedFetch();
};

onMounted(async () => {
    if (route.query.location_id) {
        filters.location_id = String(route.query.location_id);
    }
    if (route.query.search) {
        filters.search = String(route.query.search);
    }
    await masterStore.fetchBaseOptions();
    fetchData(1);
});
</script>
