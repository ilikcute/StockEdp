<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Laporan Transfer Stok
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Daftar item transfer stok berdasarkan waktu kirim atau terima.
        </p>
      </div>
    </div>

    <div class="mt-6 flex flex-col gap-4">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Date Basis</label>
          <select
            v-model="filters.date_basis"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="SENT_AT">
              Waktu Kirim (SENT_AT)
            </option>
            <option value="RECEIVED_AT">
              Waktu Terima (RECEIVED_AT)
            </option>
          </select>
        </div>
        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="">
              Semua Status
            </option>
            <option
              value="SENT"
              :disabled="filters.date_basis === 'RECEIVED_AT'"
            >
              Dikirim (SENT)
            </option>
            <option value="RECEIVED">
              Diterima (RECEIVED)
            </option>
          </select>
        </div>
        <div class="w-full sm:w-auto flex-1 min-w-[200px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
          <input
            v-model="filters.search"
            type="text"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3"
            placeholder="Nomor transfer, produk..."
          >
        </div>
      </div>

      <div class="flex flex-wrap gap-4 items-end">
        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi Asal</label>
          <select
            v-model="filters.origin_location_id"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="">
              Semua Asal
            </option>
            <option
              v-for="loc in masterStore.locations"
              :key="loc.id"
              :value="loc.id"
            >
              {{ loc.name }}
            </option>
          </select>
        </div>
        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi Tujuan</label>
          <select
            v-model="filters.destination_location_id"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="">
              Semua Tujuan
            </option>
            <option
              v-for="loc in masterStore.locations"
              :key="loc.id"
              :value="loc.id"
            >
              {{ loc.name }}
            </option>
          </select>
        </div>
        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
          <select
            v-model="filters.category_id"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="">
              Semua Kategori
            </option>
            <option
              v-for="cat in masterStore.categories"
              :key="cat.id"
              :value="cat.id"
            >
              {{ cat.name }}
            </option>
          </select>
        </div>
        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
          <select
            v-model="filters.unit_id"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="">
              Semua Satuan
            </option>
            <option
              v-for="unit in masterStore.units"
              :key="unit.id"
              :value="unit.id"
            >
              {{ unit.code }}
            </option>
          </select>
        </div>
      </div>

      <div class="flex flex-wrap gap-4 items-end">
        <div class="w-full sm:w-auto flex-1 min-w-[250px] relative">
          <label class="block text-sm font-medium text-gray-700 mb-1">Produk</label>
          <input
            v-model="productSearch"
            type="text"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3"
            placeholder="Cari produk..."
            @input="onProductSearch"
            @focus="showProductDropdown = true"
          >
          <div
            v-if="showProductDropdown && masterStore.products.length > 0"
            class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 ring-1 ring-black ring-opacity-5 overflow-auto sm:text-sm"
          >
            <div
              v-for="prod in masterStore.products"
              :key="prod.id"
              class="cursor-pointer py-2 pl-3 pr-9 hover:bg-indigo-50"
              @click="selectProduct(prod)"
            >
              <div class="font-medium text-gray-900">
                {{ prod.name }}
              </div>
              <div class="text-xs text-gray-500">
                {{ prod.sku }}
              </div>
            </div>
          </div>
        </div>
        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
          <input
            v-model="filters.start_date"
            type="date"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3"
          >
        </div>
        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
          <input
            v-model="filters.end_date"
            type="date"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3"
          >
        </div>
        <div class="flex gap-2">
          <select
            v-model="filters.sort_by"
            class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="sent_at">
              Waktu Kirim
            </option>
            <option value="received_at">
              Waktu Terima
            </option>
            <option value="transfer_number">
              Nomor Transfer
            </option>
            <option value="id">
              ID
            </option>
          </select>
          <select
            v-model="filters.sort_order"
            class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="desc">
              Desc
            </option>
            <option value="asc">
              Asc
            </option>
          </select>
          <select
            v-model="filters.per_page"
            class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="15">
              15
            </option>
            <option value="50">
              50
            </option>
            <option value="100">
              100
            </option>
          </select>
        </div>
      </div>
    </div>

    <ReportFeedbackPanels
      :error="store.error"
      :status="store.status"
      :validation-errors="store.validationErrors"
      :local-validation-error="localValidationError"
      @retry="fetchData(store.pagination?.current_page || 1)"
      @reset-filters="resetFilters"
    />

    <div
      v-if="hasFetched && !store.error && store.status !== 403"
      class="mt-6 space-y-4"
    >
      <QuantityByUnitSummary
        :summary="store.summary"
        :date-basis="store.meta?.date_basis"
        :date-basis-description="dateBasisDescription"
      >
        <template #extra>
          <div v-if="store.summary?.status_counts">
            <span class="block text-gray-500">Status SENT</span>
            <span class="font-medium text-gray-900">{{ store.summary.status_counts.SENT }}</span>
          </div>
          <div v-if="store.summary?.status_counts">
            <span class="block text-gray-500">Status RECEIVED</span>
            <span class="font-medium text-gray-900">{{ store.summary.status_counts.RECEIVED }}</span>
          </div>
          <div v-if="store.summary?.in_transit_item_count !== undefined">
            <span class="block text-gray-500">In-Transit (Item)</span>
            <span class="font-medium text-amber-700">{{ store.summary.in_transit_item_count }}</span>
          </div>
          <div v-if="store.summary?.in_transit_document_count !== undefined">
            <span class="block text-gray-500">In-Transit (Dokumen)</span>
            <span class="font-medium text-amber-700">{{ store.summary.in_transit_document_count }}</span>
          </div>
        </template>
      </QuantityByUnitSummary>

      <div class="flex flex-col relative">
        <div
          v-if="store.loading"
          class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center"
        >
          <span class="text-indigo-600 font-medium bg-white px-4 py-2 rounded-md shadow">Memuat data...</span>
        </div>
        <div class="-mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
              <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                      No. Transfer
                    </th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Tgl Dokumen
                    </th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Status
                    </th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Asal → Tujuan
                    </th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Produk
                    </th>
                    <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">
                      Qty
                    </th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Sent At/By
                    </th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Received At/By
                    </th>
                    <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      Transit
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                  <tr v-if="!store.loading && store.data.length === 0">
                    <td
                      colspan="9"
                      class="py-10 text-center text-sm text-gray-500"
                    >
                      Tidak ada data transfer stok yang sesuai filter.
                    </td>
                  </tr>
                  <tr
                    v-for="item in store.data"
                    :key="item.item_id"
                  >
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                      {{ item.transfer_number }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      {{ item.document_date || '-' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                      <span
                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
                        :class="item.status === 'SENT' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800'"
                      >
                        {{ getTransferStatusLabel(item.status) }}
                      </span>
                      <span
                        v-if="item.is_in_transit"
                        class="ml-1 inline-flex items-center rounded-md bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800"
                      >
                        In Transit
                      </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      {{ item.origin_location?.name }} → {{ item.destination_location?.name }}
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-900">
                      <div class="font-mono text-xs text-gray-500">
                        {{ item.product?.sku }}
                      </div>
                      <div>{{ item.product?.name }}</div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm font-mono text-right text-gray-900">
                      {{ item.quantity }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      <div>{{ item.sent_at || '-' }}</div>
                      <div class="text-xs">
                        {{ item.sent_by?.name || '-' }}
                      </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      <div>{{ item.received_at || '-' }}</div>
                      <div class="text-xs">
                        {{ item.received_by?.name || '-' }}
                      </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      {{ formatTransitDuration(item.transit_duration_seconds) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
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
import {
    cleanReportFilters,
    formatTransitDuration,
    getDateBasisDescription,
    getTransferStatusLabel,
    validatePeriod,
} from '../utils/reportHelpers';
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
const productSearch = ref('');
const showProductDropdown = ref(false);

const dateBasisDescription = computed(() => getDateBasisDescription(store.meta?.date_basis));

watch(() => filters.status, (newStatus) => {
    if (newStatus === 'SENT' && filters.date_basis === 'RECEIVED_AT') {
        filters.date_basis = 'SENT_AT';
    }
});

watch(() => filters.date_basis, (newBasis) => {
    if (newBasis === 'RECEIVED_AT' && filters.status === 'SENT') {
        filters.status = '';
    }
});

let debounceTimer = null;
const debouncedFetch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchData(1), 500);
};

watch(() => ({ ...filters }), debouncedFetch, { deep: true });

let productSearchTimer = null;
const onProductSearch = () => {
    clearTimeout(productSearchTimer);
    productSearchTimer = setTimeout(() => {
        if (productSearch.value.trim().length >= 2) {
            masterStore.searchProducts(productSearch.value);
        }
    }, 400);
};

const selectProduct = (prod) => {
    filters.product_id = prod.id;
    productSearch.value = prod.name;
    showProductDropdown.value = false;
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
    Object.assign(filters, defaultFilters);
    productSearch.value = '';
    store.reset();
    hasFetched.value = false;
    fetchData(1);
};

onMounted(async () => {
    await masterStore.fetchOptions();
    fetchData(1);
});
</script>
