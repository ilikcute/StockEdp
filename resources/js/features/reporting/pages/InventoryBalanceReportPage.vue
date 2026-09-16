<template>
  <div class="space-y-3">
    <!-- Top Header & Integrated Filter Bar (Compact) -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs space-y-2.5">
      <!-- Main Row: Title & Primary Controls -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <div>
          <h1 class="text-base font-bold text-gray-900 leading-tight flex items-center gap-1.5">
            <svg
              class="w-4 h-4 text-emerald-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
              />
            </svg>
            Laporan Saldo Stok
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Informasi ketersediaan stok produk di semua lokasi gudang dan teknisi.
          </p>
        </div>

        <!-- Primary Controls (Search, Quick Filter, Export, Toggle) -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          <!-- Search Input -->
          <div class="w-full sm:w-56">
            <input
              id="search"
              v-model="filters.search"
              type="text"
              class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 px-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              placeholder="Cari SKU atau Nama Produk..."
            >
          </div>

          <!-- Quick Location Filter -->
          <select
            v-model="filters.location_id"
            class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="">
              Semua Lokasi
            </option>
            <option
              v-for="loc in masterStore.locations"
              :key="loc.id"
              :value="String(loc.id)"
            >
              {{ loc.code ? loc.code + ' — ' : '' }}{{ loc.name }}
            </option>
          </select>

          <!-- Quick Condition Filter -->
          <select
            v-model="filters.condition"
            class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="">
              Semua Kondisi
            </option>
            <option value="GOOD">
              Bagus (GOOD)
            </option>
            <option value="DEFECTIVE">
              Rusak (DEFECTIVE)
            </option>
          </select>

          <!-- Toggle Advanced Filters -->
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium shadow-2xs transition-colors cursor-pointer whitespace-nowrap',
              showAdvancedFilters || activeExtraFiltersCount > 0
                ? 'bg-indigo-50 border-indigo-200 text-indigo-700 hover:bg-indigo-100'
                : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
            ]"
            @click="showAdvancedFilters = !showAdvancedFilters"
          >
            <svg
              class="w-3.5 h-3.5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
              />
            </svg>
            <span>Filter</span>
            <span
              v-if="activeExtraFiltersCount > 0"
              class="inline-flex items-center justify-center px-1.5 py-0.2 text-[10px] font-bold text-white bg-indigo-600 rounded-full"
            >
              {{ activeExtraFiltersCount }}
            </span>
          </button>

          <!-- Reset Filter -->
          <button
            v-if="isAnyFilterActive"
            type="button"
            class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 shadow-2xs cursor-pointer whitespace-nowrap"
            title="Reset Filter"
            @click="resetAllFilters"
          >
            Reset
          </button>

          <!-- Export CSV Control -->
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

      <!-- Secondary Row: Advanced Filters (Category, Unit, Active Status, Stock Flags, Sorting) -->
      <div
        v-show="showAdvancedFilters"
        class="border-t border-gray-100 pt-2 flex flex-wrap items-center justify-between gap-2.5 text-xs"
      >
        <div class="flex items-center gap-2 flex-wrap">
          <!-- Kategori -->
          <div class="flex items-center gap-1">
            <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Kategori:</span>
            <select
              v-model="filters.category_id"
              class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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

          <!-- Unit -->
          <div class="flex items-center gap-1">
            <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Satuan:</span>
            <select
              v-model="filters.unit_id"
              class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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

          <!-- Status Produk -->
          <div class="flex items-center gap-1">
            <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Status:</span>
            <select
              v-model="filters.is_active"
              class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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

          <!-- Checkboxes -->
          <div class="flex items-center gap-3 pl-1">
            <label class="flex items-center gap-1.5 cursor-pointer select-none text-[11px] text-gray-700">
              <input
                v-model="filters.positive_stock"
                type="checkbox"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5 cursor-pointer"
              >
              <span>Stok Positif</span>
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer select-none text-[11px] text-gray-700">
              <input
                v-model="filters.zero_stock"
                type="checkbox"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5 cursor-pointer"
              >
              <span>Stok Nol</span>
            </label>
          </div>
        </div>

        <!-- Sorting & Pagination Count -->
        <div class="flex items-center gap-1.5 flex-wrap ml-auto">
          <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Urutkan:</span>
          <select
            v-model="filters.sort_by"
            class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
            class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
            class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="15">
              15 / hal
            </option>
            <option value="50">
              50 / hal
            </option>
            <option value="100">
              100 / hal
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-if="store.error"
      class="rounded-lg bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800 flex items-center justify-between shadow-2xs"
    >
      <div>
        <span class="font-semibold">Error memuat data: </span>
        <span>{{ store.error }}</span>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="font-semibold text-rose-700 hover:text-rose-900 bg-rose-100 px-2.5 py-1 rounded text-xs cursor-pointer"
          @click="fetchData(1)"
        >
          Coba Lagi
        </button>
        <button
          type="button"
          class="text-rose-500 hover:text-rose-700 text-xs font-semibold cursor-pointer"
          @click="store.error = null"
        >
          Tutup
        </button>
      </div>
    </div>

    <!-- Data Table -->
    <div class="mt-4 overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar relative">
      <div
        v-if="store.loading"
        class="absolute inset-0 bg-white/50 z-20 flex items-center justify-center"
      >
        <span class="text-indigo-600 font-medium bg-white px-3 py-1.5 rounded-lg shadow-sm text-xs">Memuat data...</span>
      </div>
      <table class="w-full text-left text-xs border-collapse">
        <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10">
          <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
            <th
              scope="col"
              class="py-1.5 px-1.5 w-8 text-center whitespace-nowrap"
            >
              No.
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              SKU / Produk
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Kategori / Unit
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Lokasi
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-center whitespace-nowrap"
            >
              Kondisi
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap"
            >
              Harga Satuan
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap"
            >
              Stok Posisi
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap"
            >
              Total Nilai
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-center whitespace-nowrap w-24"
            >
              Aksi
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="!store.loading && store.data.length === 0">
            <td
              colspan="9"
              class="py-8 text-center text-xs text-gray-400"
            >
              Tidak ada data saldo stok yang ditemukan.
            </td>
          </tr>
          <tr
            v-for="(item, index) in store.data"
            :key="item.id"
            class="hover:bg-gray-50/80 transition-colors"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(store.meta, index) }}
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <router-link
                :to="{
                  path: '/reports/stock-card',
                  query: {
                    product_id: item.product_id || item.product?.id,
                    location_id: item.location_id || item.location?.id,
                    sku: item.product_sku || item.product?.sku,
                    name: item.product_name || item.product?.name
                  }
                }"
                class="group block hover:text-indigo-600 transition-colors"
                title="Klik untuk melihat Detail Histori Transaksi di Kartu Stok"
              >
                <div class="font-medium text-gray-900 group-hover:text-indigo-600 flex items-center gap-1.5">
                  <span class="group-hover:underline underline-offset-2">{{ item.product_name || item.product?.name }}</span>
                  <svg
                    class="w-3 h-3 text-indigo-500 opacity-60 group-hover:opacity-100 transition-opacity shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                    />
                  </svg>
                </div>
                <div class="text-[10px] text-gray-400 group-hover:text-indigo-500 font-mono">
                  SKU: {{ item.product_sku || item.product?.sku }}
                </div>
              </router-link>
            </td>
            <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
              <div>{{ item.category_name || item.product?.category?.name || '-' }}</div>
              <div class="text-[10px] text-gray-400">
                {{ item.unit_name || item.product?.unit?.code || '-' }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
              <span
                v-if="item.location_name || item.location?.name"
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-700"
              >
                {{ item.location_name || item.location?.name }}
              </span>
              <span v-else>-</span>
            </td>
            <td class="py-1.5 px-2 text-[11px] text-center whitespace-nowrap">
              <span
                class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider inline-flex items-center"
                :class="item.condition === 'DEFECTIVE' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20'"
              >
                {{ item.condition === 'DEFECTIVE' ? 'RUSAK' : 'BAGUS' }}
              </span>
            </td>
            <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-700 whitespace-nowrap">
              {{ formatRupiah(item.unit_price || item.product?.unit_price || 0) }}
            </td>
            <td class="py-1.5 px-2 text-[11px] font-mono text-right font-semibold text-gray-900 whitespace-nowrap">
              <router-link
                :to="{
                  path: '/reports/stock-card',
                  query: {
                    product_id: item.product_id || item.product?.id,
                    location_id: item.location_id || item.location?.id,
                    sku: item.product_sku || item.product?.sku,
                    name: item.product_name || item.product?.name
                  }
                }"
                class="hover:text-indigo-600 hover:underline underline-offset-2 transition-colors cursor-pointer"
                title="Lihat Kartu Stok"
              >
                {{ formatQuantity(item.on_hand_quantity ?? item.quantity) }}
              </router-link>
            </td>
            <td class="py-1.5 px-2 text-[11px] font-mono text-right font-semibold text-indigo-700 whitespace-nowrap">
              {{ formatRupiah(item.total_value || (Number(item.on_hand_quantity ?? item.quantity ?? 0) * (item.unit_price || item.product?.unit_price || 0))) }}
            </td>
            <!-- Action Column -->
            <td class="py-1.5 px-2 text-center whitespace-nowrap">
              <router-link
                :to="{
                  path: '/reports/stock-card',
                  query: {
                    product_id: item.product_id || item.product?.id,
                    location_id: item.location_id || item.location?.id,
                    sku: item.product_sku || item.product?.sku,
                    name: item.product_name || item.product?.name
                  }
                }"
                class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-1 text-[10px] font-semibold text-indigo-700 hover:bg-indigo-100 hover:text-indigo-800 transition-colors shadow-2xs border border-indigo-100/80 cursor-pointer"
                title="Buka Kartu Stok item ini"
              >
                <svg
                  class="w-3 h-3 text-indigo-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  />
                </svg>
                <span>Kartu Stok</span>
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <BasePagination
      :pagination="store.meta"
      :loading="store.loading"
      @change="changePage"
    />
  </div>
</template>

<script setup>
import { onMounted, watch, reactive, ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useInventoryBalanceReportStore } from '../stores/useInventoryBalanceReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import ReportCsvExportControl from '../components/ReportCsvExportControl.vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import { cleanReportExportFilters } from '../utils/reportHelpers';
import { formatRupiah, formatQuantity, rowNumber } from '@/shared/utils/formatters.js';

const route = useRoute();
const store = useInventoryBalanceReportStore();
const masterStore = useReportFilterOptionsStore();
const exportStore = useReportCsvExportStore();
const reportKey = 'inventory-balances';

const showAdvancedFilters = ref(false);

const filters = reactive({
    search: '',
    location_id: '',
    category_id: '',
    unit_id: '',
    condition: '',
    is_active: '',
    positive_stock: false,
    zero_stock: false,
    sort_by: 'id',
    sort_order: 'desc',
    per_page: '15',
});

const activeExtraFiltersCount = computed(() => {
    let count = 0;
    if (filters.category_id) count++;
    if (filters.unit_id) count++;
    if (filters.is_active !== '') count++;
    if (filters.positive_stock) count++;
    if (filters.zero_stock) count++;
    return count;
});

const isAnyFilterActive = computed(() => {
    return Boolean(
        filters.search ||
        filters.location_id ||
        filters.condition ||
        filters.category_id ||
        filters.unit_id ||
        filters.is_active !== '' ||
        filters.positive_stock ||
        filters.zero_stock
    );
});

const resetAllFilters = () => {
    filters.search = '';
    filters.location_id = '';
    filters.condition = '';
    filters.category_id = '';
    filters.unit_id = '';
    filters.is_active = '';
    filters.positive_stock = false;
    filters.zero_stock = false;
    filters.sort_by = 'id';
    filters.sort_order = 'desc';
    filters.per_page = '15';
};

let debounceTimer = null;
const debouncedFetch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchData(1), 300);
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

const exportCsv = async (format = 'xlsx') => {
    const exportFormat = typeof format === 'string' && (format.toLowerCase() === 'csv' || format.toLowerCase() === 'xlsx')
        ? format.toLowerCase()
        : 'xlsx';
    const params = cleanReportExportFilters({
        ...filters,
        positive_stock: filters.positive_stock ? 1 : null,
        zero_stock: filters.zero_stock ? 1 : null,
        format: exportFormat,
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
    if (route.query.location_id) {
        filters.location_id = String(route.query.location_id);
    }
    if (route.query.zero_stock !== undefined) {
        filters.zero_stock = Boolean(Number(route.query.zero_stock));
    }
    await masterStore.fetchOptions();
    fetchData(1);
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
