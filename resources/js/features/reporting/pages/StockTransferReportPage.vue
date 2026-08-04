<template>
  <div class="space-y-6 p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Laporan Transfer Stok
        </h1>
        <p
          v-if="dateBasisDescription"
          class="text-xs text-gray-500 mt-1"
        >
          {{ dateBasisDescription }}
        </p>
      </div>
    </div>

    <StockTransferReportFilters
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
        <span>Menampilkan {{ store.data.length }} baris item transfer</span>
        <span>Total filtered item: {{ store.pagination?.total || 0 }}</span>
      </div>

      <div class="rounded-lg bg-white shadow overflow-hidden">
        <StockTransferReportTable :items="store.data" />
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
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useStockTransferReportStore } from '../stores/useStockTransferReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';
import { cleanReportFilters, getDateBasisDescription, validatePeriod } from '../utils/reportHelpers';
import StockTransferReportFilters from '../components/transfer/StockTransferReportFilters.vue';
import StockTransferReportTable from '../components/transfer/StockTransferReportTable.vue';
import ReportPagination from '../components/ReportPagination.vue';
import ReportFeedbackPanels from '../components/ReportFeedbackPanels.vue';
import QuantityByUnitSummary from '../components/QuantityByUnitSummary.vue';

const store = useStockTransferReportStore();
const masterStore = useReportFilterOptionsStore();

const defaultFilters = {
    date_basis: 'SENT_AT',
    status: '',
    search: '',
    origin_location_id: '',
    destination_location_id: '',
    product_id: '',
    category_id: '',
    unit_id: '',
    start_date: '',
    end_date: '',
    sort_by: 'sent_at',
    sort_order: 'desc',
    per_page: '15',
};

const filters = reactive({ ...defaultFilters });
const localValidationError = ref('');
const hasFetched = ref(false);

const dateBasisDescription = computed(() => getDateBasisDescription(store.meta?.date_basis));

watch(() => filters.status, (newStatus) => {
    if (newStatus === 'SENT' && filters.date_basis === 'RECEIVED_AT') {
        filters.date_basis = 'SENT_AT';
    }
});

watch(() => filters.date_basis, (newBasis) => {
    if (newBasis === 'RECEIVED_AT') {
        if (filters.status === 'SENT') {
            filters.status = '';
        }

        if (filters.sort_by === 'sent_at') {
            filters.sort_by = 'received_at';
        }

        return;
    }

    if (filters.sort_by === 'received_at') {
        filters.sort_by = 'sent_at';
    }
});

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
    if (filters.status === 'SENT' && filters.date_basis === 'RECEIVED_AT') {
        localValidationError.value = 'Status SENT tidak dapat dikombinasikan dengan date_basis RECEIVED_AT.';
        return null;
    }

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
