<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Laporan Saldo Stok
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Informasi ketersediaan stok produk di semua lokasi.
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
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

    <!-- Filters -->
    <div class="mt-6 flex flex-col gap-4">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="w-full sm:w-auto flex-1 min-w-[200px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
          <input
            id="search"
            v-model="filters.search"
            type="text"
            class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            placeholder="Cari SKU atau Nama Produk..."
          >
        </div>
        
        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
          <select
            v-model="filters.location_id"
            class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="">
              Semua Lokasi
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
            class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
          <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
          <select
            v-model="filters.unit_id"
            class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="">
              Semua Unit
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
        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Status Produk</label>
          <select
            v-model="filters.is_active"
            class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="">
              Semua
            </option>
            <option value="1">
              Aktif
            </option>
            <option value="0">
              Nonaktif
            </option>
          </select>
        </div>

        <div class="flex gap-4">
          <label class="flex items-center gap-2">
            <input
              v-model="filters.positive_stock"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            >
            <span class="text-sm text-gray-700">Hanya Stok Positif</span>
          </label>
          <label class="flex items-center gap-2">
            <input
              v-model="filters.zero_stock"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            >
            <span class="text-sm text-gray-700">Hanya Stok Nol</span>
          </label>
        </div>

        <div class="flex gap-2 w-full sm:w-auto ml-auto">
          <select
            v-model="filters.sort_by"
            class="block rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="id">
              ID
            </option>
            <option value="product_id">
              Produk
            </option>
            <option value="location_id">
              Lokasi
            </option>
            <option value="quantity">
              Kuantitas
            </option>
            <option value="created_at">
              Waktu Dibuat
            </option>
          </select>
          <select
            v-model="filters.sort_order"
            class="block rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="desc">
              Menurun (Desc)
            </option>
            <option value="asc">
              Menaik (Asc)
            </option>
          </select>
          <select
            v-model="filters.per_page"
            class="block rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
          <div class="mt-4">
            <button
              class="text-sm font-medium text-red-800 hover:text-red-900 bg-red-100 px-3 py-1.5 rounded-md cursor-pointer"
              @click="fetchData(1)"
            >
              Coba Lagi
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="mt-6 flex flex-col relative">
      <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
          <div class="overflow-hidden shadow-sm border border-gray-300 md:rounded-lg">
            <div
              v-if="store.loading"
              class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center"
            >
              <span class="text-indigo-600 font-medium bg-white px-4 py-2 rounded-md shadow">Memuat data...</span>
            </div>
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    scope="col"
                    class="py-3.5 pl-4 pr-3 text-center text-sm font-semibold text-gray-900 sm:pl-6 border-b border-gray-300 w-16"
                  >
                    No.
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
                  >
                    SKU / Produk
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
                  >
                    Kategori / Unit
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
                  >
                    Lokasi
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 border-b border-gray-300"
                  >
                    Stok Posisi
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr v-if="!store.loading && store.data.length === 0">
                  <td
                    colspan="5"
                    class="py-10 text-center text-sm text-gray-500"
                  >
                    Tidak ada data saldo stok yang ditemukan.
                  </td>
                </tr>
                <tr
                  v-for="(item, index) in store.data"
                  :key="item.id"
                  class="hover:bg-gray-50"
                >
                  <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-center text-gray-500 sm:pl-6">
                    {{ (store.pagination?.from ? store.pagination.from + index : ((filters.page - 1) * filters.per_page) + index + 1) }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm">
                    <div class="font-medium text-gray-900">
                      {{ item.product?.name }}
                    </div>
                    <div class="text-xs text-gray-500 font-mono">
                      {{ item.product?.sku }}
                    </div>
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    <div>{{ item.product?.category?.name || '-' }}</div>
                    <div class="text-xs text-gray-400">
                      {{ item.product?.unit?.code || '-' }}
                    </div>
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    <span
                      v-if="item.location"
                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"
                    >
                      {{ item.location.name }}
                    </span>
                    <span v-else>-</span>
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-right font-mono font-medium text-gray-900">
                    {{ item.quantity }}
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
</template>

<script setup>
import { onMounted, watch, reactive } from 'vue';
import { useInventoryBalanceReportStore } from '../stores/useInventoryBalanceReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import ReportCsvExportControl from '../components/ReportCsvExportControl.vue';
import { cleanReportExportFilters } from '../utils/reportHelpers';

const store = useInventoryBalanceReportStore();
const masterStore = useReportFilterOptionsStore();
const exportStore = useReportCsvExportStore();
const reportKey = 'inventory-balances';

const filters = reactive({
    search: '',
    location_id: '',
    category_id: '',
    unit_id: '',
    is_active: '',
    positive_stock: false,
    zero_stock: false,
    sort_by: 'id',
    sort_order: 'desc',
    per_page: '15',
});

let debounceTimer = null;
const debouncedFetch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchData(1), 500);
};

watch(() => ({ ...filters }), debouncedFetch, { deep: true });

const fetchData = (page = 1) => {
    store.fetchBalances({
        page,
        ...filters,
        positive_stock: filters.positive_stock ? 1 : null,
        zero_stock: filters.zero_stock ? 1 : null,
    });
};

const exportCsv = async () => {
    const params = cleanReportExportFilters({
        ...filters,
        positive_stock: filters.positive_stock ? 1 : null,
        zero_stock: filters.zero_stock ? 1 : null,
    });
    await exportStore.exportReport(reportKey, params);
};

const changePage = (page) => {
    if (!store.meta || page < 1 || page > store.meta.last_page) {
        return;
    }
    store.fetchBalances({
        page,
        ...filters,
        positive_stock: filters.positive_stock ? 1 : null,
        zero_stock: filters.zero_stock ? 1 : null,
    });
};

onMounted(async () => {
    await masterStore.fetchOptions();
    fetchData(1);
});
</script>
