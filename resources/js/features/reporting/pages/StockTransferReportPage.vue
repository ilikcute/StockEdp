<template>
  <div class="space-y-3">
    <!-- TOP Header Compact -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-base font-bold text-gray-900 tracking-tight flex items-center gap-1.5">
            <svg
              class="w-4 h-4 text-blue-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
              />
            </svg>
            Laporan Transfer Stok
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Laporan antar lokasi berdasarkan waktu pengiriman (SENT_AT) atau penerimaan (RECEIVED_AT).
          </p>
        </div>
        <div class="flex items-center gap-2">
          <ReportCsvExportControl
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
    <StockTransferReportFilters
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
      empty-message="Tidak ada data transfer stok yang sesuai filter."
      @retry="fetchData(1)"
      @reset-filters="resetFilters"
    />

    <QuantityByUnitSummary
      v-if="store.summary"
      :summary="store.summary"
      :date-basis="filters.date_basis"
      date-basis-description="Total dihitung berdasarkan tanggal:"
    />

    <div
      v-if="!store.loading && !store.error && store.data.length > 0"
      class="space-y-3"
    >
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-[11px] text-gray-500 px-1">
        <span>Menampilkan {{ store.data.length }} baris item transfer</span>
        <span>Total filtered item: {{ store.pagination?.total || 0 }}</span>
      </div>

      <div class="rounded-xl bg-white shadow-2xs border border-gray-200 overflow-hidden">
        <StockTransferReportTable
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
import { useStockTransferReportStore } from '../stores/useStockTransferReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import { cleanReportFilters, cleanReportExportFilters, validatePeriod } from '../utils/reportHelpers';
import StockTransferReportFilters from '../components/transfer/StockTransferReportFilters.vue';
import StockTransferReportTable from '../components/transfer/StockTransferReportTable.vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import ReportFeedbackPanels from '../components/ReportFeedbackPanels.vue';
import QuantityByUnitSummary from '../components/QuantityByUnitSummary.vue';
import ReportCsvExportControl from '../components/ReportCsvExportControl.vue';

const route = useRoute();
const store = useStockTransferReportStore();
const masterStore = useReportFilterOptionsStore();
const exportStore = useReportCsvExportStore();
const reportKey = 'stock-transfers';

const defaultFilters = {
    origin_location_id: '',
    destination_location_id: '',
    status: '',
    date_basis: 'SENT_AT',
    product_id: '',
    category_id: '',
    unit_id: '',
    start_date: '',
    end_date: '',
    search: '',
    sort_by: 'sent_at',
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

watch(() => ({ ...filters }), (newVal, oldVal) => {
    if (newVal.date_basis !== oldVal.date_basis) {
        if (newVal.date_basis === 'RECEIVED_AT' && filters.status === 'IN_TRANSIT') {
            filters.status = '';
        }
        if (filters.sort_by === 'sent_at' || filters.sort_by === 'received_at') {
            filters.sort_by = newVal.date_basis.toLowerCase();
        }
    }
    debouncedFetch();
}, { deep: true });

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
    if (route.query.origin_location_id) {
        filters.origin_location_id = String(route.query.origin_location_id);
    }
    if (route.query.destination_location_id) {
        filters.destination_location_id = String(route.query.destination_location_id);
    }
    if (route.query.search) {
        filters.search = String(route.query.search);
    }
    await masterStore.fetchBaseOptions();
    fetchData(1);
});
</script>
