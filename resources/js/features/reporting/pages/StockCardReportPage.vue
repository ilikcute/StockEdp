<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Kartu Stok (Stock Card)
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Riwayat pergerakan stok untuk suatu produk di lokasi tertentu dalam periode waktu tertentu.
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <ReportCsvExportControl
          :loading="exportStore.isExporting(reportKey)"
          :disabled="!canFetch"
          disabled-reason="Pilih produk, lokasi, dan periode terlebih dahulu."
          :error="exportStore.errorFor(reportKey)"
          :status="exportStore.statusFor(reportKey)"
          :validation-errors="exportStore.validationErrorsFor(reportKey)"
          :success-message="exportStore.successFor(reportKey)"
          @export="exportCsv"
          @dismiss="exportStore.clearFeedback(reportKey)"
        />
      </div>
    </div>

    <!-- Filters -->
    <div class="mt-6 flex flex-col gap-4">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="w-full sm:w-auto flex-1 min-w-[250px] relative">
          <label class="block text-sm font-medium text-gray-700 mb-1">Produk <span class="text-red-500">*</span></label>
          <div class="relative">
            <input
              v-model="productSearch"
              type="text"
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              placeholder="Cari & Pilih Produk..."
              @input="onProductSearch"
              @focus="showProductDropdown = true"
            >
            <div
              v-if="selectedProduct"
              class="absolute inset-y-0 right-0 pr-3 flex items-center"
            >
              <button
                class="text-gray-400 hover:text-gray-600 cursor-pointer"
                @click="clearProduct"
              >
                <span class="sr-only">Clear</span>
                &times;
              </button>
            </div>
            <div
              v-if="showProductDropdown && masterStore.products.length > 0"
              class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base border border-gray-300 overflow-auto sm:text-sm"
            >
              <div
                v-for="prod in masterStore.products"
                :key="prod.id"
                class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-50"
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
        </div>

        <div class="w-full sm:w-auto min-w-[200px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi <span class="text-red-500">*</span></label>
          <select
            v-model="filters.location_id"
            class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="">
              -- Pilih Lokasi --
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
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
          <input
            v-model="filters.start_date"
            type="date"
            class="block w-full rounded-md border border-gray-300 bg-white py-2 px-3 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
        </div>

        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir <span class="text-red-500">*</span></label>
          <input
            v-model="filters.end_date"
            type="date"
            class="block w-full rounded-md border border-gray-300 bg-white py-2 px-3 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
        </div>
      </div>
      
      <div class="flex justify-between items-center">
        <div>
          <button
            :disabled="!canFetch || store.loading"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-gray-400 disabled:cursor-not-allowed"
            @click="fetchData(1)"
          >
            Tampilkan Kartu Stok
          </button>
        </div>
        <div class="flex gap-2">
          <select
            v-model="filters.per_page"
            class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
            @change="fetchData(1)"
          >
            <option value="15">
              15 Baris
            </option>
            <option value="50">
              50 Baris
            </option>
            <option value="100">
              100 Baris
            </option>
          </select>
        </div>
      </div>
      
      <!-- Filter Error Validation UX -->
      <div
        v-if="localValidationError"
        class="text-sm text-red-600 font-medium"
      >
        {{ localValidationError }}
      </div>
    </div>

    <!-- Error State -->
    <div
      v-if="store.error"
      class="mt-4 rounded-md bg-red-50 p-4 border border-red-200"
    >
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">
            Error memuat data
          </h3>
          <div class="mt-2 text-sm text-red-700">
            <p>{{ store.error }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Validation Errors -->
    <div
      v-if="Object.keys(store.validationErrors).length > 0"
      class="mt-4 rounded-md bg-yellow-50 p-4 border border-yellow-200"
    >
      <ul class="list-disc pl-5 text-sm text-yellow-700">
        <li
          v-for="(errors, field) in store.validationErrors"
          :key="field"
        >
          {{ errors.join(', ') }}
        </li>
      </ul>
    </div>

    <!-- Prompt / Loading -->
    <div
      v-if="!hasFetchedData"
      class="mt-6 rounded-md bg-blue-50 p-4 border border-blue-200 text-center py-10"
    >
      <p class="text-sm font-medium text-blue-800">
        Pilih produk, lokasi, dan periode untuk melihat kartu stok.
      </p>
    </div>

    <div
      v-else-if="store.loading"
      class="mt-6 text-center py-10"
    >
      <p class="text-sm text-gray-500 font-medium">
        Memuat kartu stok...
      </p>
    </div>

    <!-- Stock Card Data -->
    <div
      v-else
      class="mt-6"
    >
      <div class="bg-gray-50 p-4 rounded-lg border border-gray-300 shadow-sm mb-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
          <div>
            <span class="block text-gray-500">Opening Balance:</span>
            <span class="font-mono font-medium text-gray-900">{{ store.summary?.opening_balance || '-' }}</span>
          </div>
          <div>
            <span class="block text-gray-500">Total Masuk:</span>
            <span class="font-mono font-medium text-green-700">+{{ store.summary?.total_quantity_in || '-' }}</span>
          </div>
          <div>
            <span class="block text-gray-500">Total Keluar:</span>
            <span class="font-mono font-medium text-red-700">-{{ store.summary?.total_quantity_out || '-' }}</span>
          </div>
          <div>
            <span class="block text-gray-500">Closing Balance:</span>
            <span class="font-mono font-medium text-gray-900">{{ store.summary?.closing_balance || '-' }}</span>
          </div>
        </div>
        <div
          v-if="store.meta?.date_basis"
          class="mt-2 text-xs text-gray-500"
        >
          Periode laporan menggunakan waktu posting transaksi. (Date Basis: {{ store.meta.date_basis }})
        </div>
      </div>

      <div class="flex flex-col relative">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div class="overflow-hidden shadow-sm border border-gray-300 md:rounded-lg">
              <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                  <tr>
                    <th
                      scope="col"
                      class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6 border-b border-gray-300"
                    >
                      Tanggal Dokumen
                    </th>
                    <th
                      scope="col"
                      class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
                    >
                      Tanggal Posting
                    </th>
                    <th
                      scope="col"
                      class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
                    >
                      Referensi / Tipe
                    </th>
                    <th
                      scope="col"
                      class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 border-b border-gray-300"
                    >
                      Qty Before
                    </th>
                    <th
                      scope="col"
                      class="px-3 py-3.5 text-right text-sm font-semibold text-green-700 border-b border-gray-300"
                    >
                      Masuk
                    </th>
                    <th
                      scope="col"
                      class="px-3 py-3.5 text-right text-sm font-semibold text-red-700 border-b border-gray-300"
                    >
                      Keluar
                    </th>
                    <th
                      scope="col"
                      class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 border-b border-gray-300"
                    >
                      Qty After
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                  <tr v-if="store.data.length === 0">
                    <td
                      colspan="7"
                      class="py-10 text-center text-sm text-gray-500"
                    >
                      Tidak ada pergerakan stok pada periode ini.
                    </td>
                  </tr>
                  <tr
                    v-for="item in store.data"
                    :key="item.id"
                    class="hover:bg-gray-50"
                  >
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-500 sm:pl-6 font-mono">
                      {{ item.document_date || '-' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 font-mono">
                      {{ item.movement_posted_at || item.occurred_at || '-' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                      <div class="font-medium">
                        {{ item.reference_number || item.movement_id }}
                      </div>
                      <div class="text-xs text-gray-500">
                        {{ item.movement_type }}
                      </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-right font-mono text-gray-500">
                      {{ item.quantity_before }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-right font-mono font-medium text-green-600">
                      {{ item.quantity_in !== '0.0000' ? '+' + item.quantity_in : '-' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-right font-mono font-medium text-red-600">
                      {{ item.quantity_out !== '0.0000' ? '-' + item.quantity_out : '-' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-right font-mono font-semibold text-gray-900">
                      {{ item.quantity_after }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div
        v-if="store.meta?.total > 0"
        class="mt-4 flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 rounded-lg shadow-sm"
      >
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
          <div>
            <p class="text-sm text-gray-700">
              Menampilkan
              <span class="font-medium">{{ store.meta.from }}</span>
              sampai
              <span class="font-medium">{{ store.meta.to }}</span>
              dari
              <span class="font-medium">{{ store.meta.total }}</span>
              hasil
            </p>
          </div>
          <div>
            <nav
              class="isolate inline-flex -space-x-px rounded-md shadow-sm"
              aria-label="Pagination"
            >
              <button
                :disabled="store.meta.current_page === 1"
                class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50"
                @click="changePage(store.meta.current_page - 1)"
              >
                Previous
              </button>
              <button
                :disabled="store.meta.current_page === store.meta.last_page"
                class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 ml-2"
                @click="changePage(store.meta.current_page + 1)"
              >
                Next
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed, watch } from 'vue';
import { useStockCardReportStore } from '../stores/useStockCardReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import { toLocalDateInputValue, cleanReportExportFilters } from '../utils/reportHelpers';
import ReportCsvExportControl from '../components/ReportCsvExportControl.vue';

const store = useStockCardReportStore();
const masterStore = useReportFilterOptionsStore();
const exportStore = useReportCsvExportStore();
const reportKey = 'stock-card';

const productSearch = ref('');
const showProductDropdown = ref(false);
const selectedProduct = ref(null);
const localValidationError = ref('');
const hasFetchedData = ref(false);

const filters = reactive({
    product_id: '',
    location_id: '',
    start_date: '',
    end_date: '',
    per_page: '15',
});

const parseDateOnly = (dateStr) => {
    if (!dateStr || !dateStr.includes('-')) return null;
    const [y, m, d] = dateStr.split('-').map(Number);
    return new Date(y, m - 1, d);
};

const today = new Date();
const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
filters.end_date = toLocalDateInputValue(today);
filters.start_date = toLocalDateInputValue(firstDayOfMonth);

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
    selectedProduct.value = prod;
    filters.product_id = prod.id;
    productSearch.value = prod.name;
    showProductDropdown.value = false;
};

const clearProduct = () => {
    selectedProduct.value = null;
    filters.product_id = '';
    productSearch.value = '';
};

watch(productSearch, (val) => {
    if (!val) {
        clearProduct();
    }
});

const canFetch = computed(() => {
    return filters.product_id && filters.location_id && filters.start_date && filters.end_date;
});

const validateFilters = () => {
    localValidationError.value = '';

    if (!filters.product_id || !filters.location_id || !filters.start_date || !filters.end_date) {
        localValidationError.value = 'Mohon lengkapi produk, lokasi, tanggal mulai, dan tanggal akhir.';
        return false;
    }

    const start = parseDateOnly(filters.start_date);
    const end = parseDateOnly(filters.end_date);

    if (!start || !end) {
        localValidationError.value = 'Format tanggal tidak valid.';
        return false;
    }

    if (start > end) {
        localValidationError.value = 'Tanggal mulai tidak boleh melewati tanggal akhir.';
        return false;
    }

    const diffTime = end.getTime() - start.getTime();
    const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays > 366) {
        localValidationError.value = 'Rentang waktu maksimal adalah 366 hari.';
        return false;
    }

    return true;
};

const fetchData = async (page = 1) => {
    if (!validateFilters()) return;

    hasFetchedData.value = true;

    await store.fetchStockCard({
        page,
        ...filters,
    });
};

const exportCsv = async () => {
    if (!validateFilters()) return;
    const params = cleanReportExportFilters({ ...filters });
    await exportStore.exportReport(reportKey, params);
};

const changePage = (page) => {
    if (!store.meta || page < 1 || page > store.meta.last_page) {
        return;
    }
    fetchData(page);
};

onMounted(async () => {
    await masterStore.fetchOptions();
});
</script>
