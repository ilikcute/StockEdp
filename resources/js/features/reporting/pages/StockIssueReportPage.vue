<template>
  <div class="space-y-6 p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Laporan Pengeluaran Stok
        </h1>
        <p class="text-xs text-gray-500 mt-1">
          Periode laporan menggunakan waktu posting movement pengeluaran (MOVEMENT_POSTED_AT).
        </p>
      </div>
      <div>
        <ReportCsvExportControl
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

    <StockIssueReportFilters
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
      empty-message="Tidak ada data pengeluaran stok yang sesuai filter."
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
      class="space-y-4"
    >
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs text-gray-500 px-1">
        <span>Menampilkan {{ store.data.length }} baris item pengeluaran</span>
        <span>Total filtered item: {{ store.pagination?.total || 0 }}</span>
      </div>

      <div class="rounded-lg bg-white shadow-sm border border-gray-300 overflow-hidden">
        <StockIssueReportTable
          :items="store.data"
          :pagination="store.pagination"
        />
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
import { useStockIssueReportStore } from '../stores/useStockIssueReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import { cleanReportFilters, cleanReportExportFilters, validatePeriod } from '../utils/reportHelpers';
import StockIssueReportFilters from '../components/issue/StockIssueReportFilters.vue';
import StockIssueReportTable from '../components/issue/StockIssueReportTable.vue';
import ReportPagination from '../components/ReportPagination.vue';
import ReportFeedbackPanels from '../components/ReportFeedbackPanels.vue';
import QuantityByUnitSummary from '../components/QuantityByUnitSummary.vue';
import ReportCsvExportControl from '../components/ReportCsvExportControl.vue';

const store = useStockIssueReportStore();
const masterStore = useReportFilterOptionsStore();
const exportStore = useReportCsvExportStore();
const reportKey = 'stock-issues';

const defaultFilters = {
    location_id: '',
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

const exportCsv = async () => {
    const periodCheck = validatePeriod(filters.start_date, filters.end_date);
    if (!periodCheck.valid) {
        localValidationError.value = periodCheck.message;
        return;
    }
    localValidationError.value = '';
    const params = cleanReportExportFilters({ ...filters });
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
    await masterStore.fetchBaseOptions();
});
</script>
