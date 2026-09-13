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
              :value="String(loc.id)"
            >
              {{ loc.code ? loc.code + ' — ' : '' }}{{ loc.name }}
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

        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Kondisi Stok</label>
          <select
            v-model="filters.condition"
            class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="!store.loading && store.data.length === 0">
            <td
              colspan="8"
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
              <div class="font-medium text-gray-900">
                {{ item.product_name || item.product?.name }}
              </div>
              <div class="text-[10px] text-gray-400 font-mono">
                SKU: {{ item.product_sku || item.product?.sku }}
              </div>
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
              {{ formatQuantity(item.on_hand_quantity ?? item.quantity) }}
            </td>
            <td class="py-1.5 px-2 text-[11px] font-mono text-right font-semibold text-indigo-700 whitespace-nowrap">
              {{ formatRupiah(item.total_value || (Number(item.on_hand_quantity ?? item.quantity ?? 0) * (item.unit_price || item.product?.unit_price || 0))) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div
      v-if="store.meta?.total > 0"
      class="mt-4 flex items-center justify-between border-t border-gray-200 bg-white px-3 py-2.5 sm:px-4 rounded-lg shadow-xs"
    >
      <div class="flex flex-1 flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <p class="text-xs text-gray-700">
            Menampilkan
            <span class="font-semibold text-gray-900">{{ store.meta.from }}</span>
            sampai
            <span class="font-semibold text-gray-900">{{ store.meta.to }}</span>
            dari
            <span class="font-semibold text-gray-900">{{ store.meta.total }}</span>
            item persediaan
          </p>
        </div>
        <div class="flex items-center gap-1.5 self-end sm:self-auto">
          <button
            :disabled="store.meta.current_page === 1"
            class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium text-gray-700 bg-white ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition-colors"
            @click="changePage(store.meta.current_page - 1)"
          >
            &larr; Sebelumnya
          </button>
          <span class="text-xs text-gray-500 font-mono px-1.5">
            Hal. {{ store.meta.current_page }} / {{ store.meta.last_page }}
          </span>
          <button
            :disabled="store.meta.current_page === store.meta.last_page"
            class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium text-gray-700 bg-white ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer transition-colors"
            @click="changePage(store.meta.current_page + 1)"
          >
            Berikutnya &rarr;
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, watch, reactive } from 'vue';
import { useRoute } from 'vue-router';
import { useInventoryBalanceReportStore } from '../stores/useInventoryBalanceReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import ReportCsvExportControl from '../components/ReportCsvExportControl.vue';
import { cleanReportExportFilters } from '../utils/reportHelpers';
import { formatRupiah, formatQuantity, rowNumber } from '@/shared/utils/formatters.js';

const route = useRoute();
const store = useInventoryBalanceReportStore();
const masterStore = useReportFilterOptionsStore();
const exportStore = useReportCsvExportStore();
const reportKey = 'inventory-balances';

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
