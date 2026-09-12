<template>
  <div class="space-y-6 p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Laporan Penerimaan Stok
        </h1>
        <p class="text-xs text-gray-500 mt-1">
          Periode laporan menggunakan waktu posting movement penerimaan (MOVEMENT_POSTED_AT).
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

    <StockReceiptReportFilters
      :filters="filters"
      :master-store="masterStore"
      :product-search="productSearch"
      :supplier-search="supplierSearch"
      @update:filter="(key, val) => filters[key] = val"
      @update:product-search="val => productSearch = val"
      @update:supplier-search="val => supplierSearch = val"
      @product-search="onProductSearch"
      @select-product="selectProduct"
      @clear-product="clearProduct"
      @supplier-search="onSupplierSearch"
      @select-supplier="selectSupplier"
      @clear-supplier="clearSupplier"
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
      empty-message="Tidak ada data penerimaan stok yang sesuai filter."
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
        <span>Menampilkan {{ store.data.length }} baris item penerimaan</span>
        <span>Total filtered item: {{ store.pagination?.total || 0 }}</span>
      </div>

      <div class="rounded-lg bg-white shadow-sm border border-gray-300 overflow-hidden">
        <StockReceiptReportTable
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
import { useStockReceiptReportStore } from '../stores/useStockReceiptReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import { cleanReportFilters, cleanReportExportFilters, validatePeriod } from '../utils/reportHelpers';
import StockReceiptReportFilters from '../components/receipt/StockReceiptReportFilters.vue';
import StockReceiptReportTable from '../components/receipt/StockReceiptReportTable.vue';
import ReportPagination from '../components/ReportPagination.vue';
import ReportFeedbackPanels from '../components/ReportFeedbackPanels.vue';
import QuantityByUnitSummary from '../components/QuantityByUnitSummary.vue';
import ReportCsvExportControl from '../components/ReportCsvExportControl.vue';

const store = useStockReceiptReportStore();
const masterStore = useReportFilterOptionsStore();
const exportStore = useReportCsvExportStore();
const reportKey = 'stock-receipts';

const defaultFilters = {
    supplier_id: '',
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
const supplierSearch = ref('');
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

let supplierSearchTimer = null;
const onSupplierSearch = (query) => {
    supplierSearch.value = query;
    filters.supplier_id = '';
    clearTimeout(supplierSearchTimer);
    supplierSearchTimer = setTimeout(() => {
        if (query.trim().length >= 2 || query === '') {
            masterStore.searchSuppliers(query);
        }
    }, 300);
};

const selectSupplier = (sup) => {
    filters.supplier_id = sup.id;
    supplierSearch.value = sup.name;
};

const clearSupplier = () => {
    filters.supplier_id = '';
    supplierSearch.value = '';
    masterStore.resetSuppliers();
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
    clearTimeout(supplierSearchTimer);

    productSearch.value = '';
    supplierSearch.value = '';
    masterStore.resetProducts();
    masterStore.resetSuppliers();

    Object.assign(filters, defaultFilters);
    store.reset();
    hasFetched.value = false;
    debouncedFetch();
};

onMounted(async () => {
    await masterStore.fetchBaseOptions();
    await masterStore.searchSuppliers('');
});
</script>
