<template>
  <div class="space-y-3">
    <!-- TOP Header Compact -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-base font-bold text-gray-900 tracking-tight flex items-center gap-1.5">
            <svg
              class="w-4 h-4 text-purple-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
              />
            </svg>
            Laporan Penyesuaian Stok (Adjustment)
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Periode laporan berbasis waktu posting movement adjustment (MOVEMENT_POSTED_AT).
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

    <StockAdjustmentReportFilters
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
      empty-message="Tidak ada data adjustment stok yang sesuai filter."
      @retry="fetchData(1)"
      @reset-filters="resetFilters"
    />

    <!-- Compact Financial & Inventory Summary -->
    <div
      v-if="store.summary"
      class="bg-white rounded-lg border border-gray-200 shadow-2xs p-3 space-y-2.5"
    >
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
        <!-- Card 1: Dokumen -->
        <div class="bg-slate-50/70 rounded-md p-2.5 border border-slate-200/80">
          <span class="block text-[11px] text-gray-500 font-medium">Total Dokumen</span>
          <span class="text-base font-bold text-gray-900 font-mono leading-tight">
            {{ store.summary.total_documents ?? 0 }}
          </span>
          <span class="block text-[10px] text-gray-400 mt-0.5">Dokumen Penyesuaian</span>
        </div>

        <!-- Card 2: Total Item -->
        <div class="bg-slate-50/70 rounded-md p-2.5 border border-slate-200/80">
          <span class="block text-[11px] text-gray-500 font-medium">Total Item</span>
          <span class="text-base font-bold text-gray-900 font-mono leading-tight">
            {{ store.summary.total_rows ?? 0 }}
          </span>
          <span class="block text-[10px] text-gray-400 mt-0.5">Baris Item Tercatat</span>
        </div>

        <!-- Card 3: Nilai Total (Bruto) -->
        <div class="bg-blue-50/40 rounded-md p-2.5 border border-blue-100">
          <span class="block text-[11px] text-blue-700 font-medium">Nilai Total (Bruto)</span>
          <span class="text-sm sm:text-base font-bold text-blue-900 font-mono leading-tight">
            {{ formatRupiah(store.summary.total_amount ?? 0) }}
          </span>
          <span class="block text-[10px] text-blue-500 mt-0.5">Gross Adjustment</span>
        </div>

        <!-- Card 4: Penambahan (+) -->
        <div class="bg-emerald-50/40 rounded-md p-2.5 border border-emerald-100">
          <span class="block text-[11px] text-emerald-700 font-medium">Penambahan (+)</span>
          <span class="text-sm sm:text-base font-bold text-emerald-700 font-mono leading-tight">
            +{{ formatRupiah(store.summary.total_increase_amount ?? 0) }}
          </span>
          <span class="block text-[10px] text-emerald-600 mt-0.5">Stok Bertambah</span>
        </div>

        <!-- Card 5: Pengurangan (-) -->
        <div class="bg-rose-50/40 rounded-md p-2.5 border border-rose-100">
          <span class="block text-[11px] text-rose-700 font-medium">Pengurangan (-)</span>
          <span class="text-sm sm:text-base font-bold text-rose-700 font-mono leading-tight">
            -{{ formatRupiah(store.summary.total_decrease_amount ?? 0) }}
          </span>
          <span class="block text-[10px] text-rose-600 mt-0.5">Stok Berkurang</span>
        </div>

        <!-- Card 6: Nilai Netto (+/-) -->
        <div
          class="rounded-md p-2.5 border"
          :class="(store.summary.net_amount ?? 0) >= 0 ? 'bg-indigo-50/40 border-indigo-100 text-indigo-900' : 'bg-amber-50/40 border-amber-100 text-amber-900'"
        >
          <span
            class="block text-[11px] font-medium"
            :class="(store.summary.net_amount ?? 0) >= 0 ? 'text-indigo-700' : 'text-amber-700'"
          >
            Nilai Netto (+/-)
          </span>
          <span class="text-sm sm:text-base font-bold font-mono leading-tight">
            {{ (store.summary.net_amount ?? 0) > 0 ? '+' : '' }}{{ formatRupiah(store.summary.net_amount ?? 0) }}
          </span>
          <span class="block text-[10px] text-gray-400 mt-0.5">Selisih Bersih</span>
        </div>
      </div>

      <!-- Kuantitas per Satuan Compact Pills -->
      <div
        v-if="store.summary.quantity_by_unit && store.summary.quantity_by_unit.length > 0"
        class="pt-2 border-t border-gray-100 flex flex-wrap items-center gap-1.5 text-xs"
      >
        <span class="text-[11px] font-medium text-gray-500 mr-1">Kuantitas per Satuan:</span>
        <span
          v-for="unit in store.summary.quantity_by_unit"
          :key="unit.unit_id"
          class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700 text-[11px]"
        >
          <span class="text-gray-500 mr-1">{{ unit.unit_name || unit.unit_code }}:</span>
          <span class="font-mono font-semibold text-gray-900">{{ formatQuantity(unit.total_quantity, false) }}</span>
        </span>
      </div>
    </div>

    <div
      v-if="!store.loading && !store.error && store.data.length > 0"
      class="space-y-3"
    >
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs text-gray-500 px-1">
        <span>Menampilkan <strong class="text-gray-700 font-mono">{{ store.data.length }}</strong> baris item adjustment</span>
        <span>Total filtered item: <strong class="text-gray-700 font-mono">{{ store.pagination?.total || 0 }}</strong></span>
      </div>

      <div class="rounded-xl bg-white shadow-2xs border border-gray-200 overflow-hidden">
        <StockAdjustmentReportTable
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
import { useStockAdjustmentReportStore } from '../stores/useStockAdjustmentReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import { cleanReportFilters, cleanReportExportFilters, validatePeriod } from '../utils/reportHelpers';
import { formatRupiah, formatQuantity } from '@/shared/utils/formatters.js';
import StockAdjustmentReportFilters from '../components/adjustment/StockAdjustmentReportFilters.vue';
import StockAdjustmentReportTable from '../components/adjustment/StockAdjustmentReportTable.vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import ReportFeedbackPanels from '../components/ReportFeedbackPanels.vue';
import ReportCsvExportControl from '../components/ReportCsvExportControl.vue';

const route = useRoute();
const store = useStockAdjustmentReportStore();
const masterStore = useReportFilterOptionsStore();
const exportStore = useReportCsvExportStore();
const reportKey = 'stock-adjustments';

const defaultFilters = {
    direction: '',
    reason_code: '',
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
