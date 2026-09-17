<template>
  <div class="space-y-3">
    <!-- Top Header & Filter Toolbar (Compact) -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs space-y-2.5">
      <!-- Primary Header Row: Title & Primary Controls -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <!-- Title & Subtitle -->
        <div>
          <h1 class="text-base font-bold text-gray-900 leading-tight flex items-center gap-1.5">
            <svg
              class="w-4 h-4 text-indigo-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
              />
            </svg>
            Laporan Persediaan Lapangan Teknisi
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Monitoring persediaan unit di tangan teknisi: unit siap pasang (GOOD) vs unit rusak tarikan (DEFECTIVE).
          </p>
        </div>

        <!-- Primary Controls (Location, Search, Filter Toggle, Reset, Export) -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          <!-- Location Selector -->
          <div class="w-full sm:w-56 min-w-[200px]">
            <BaseCombobox
              id="filter-location"
              v-model="filters.location_id"
              :options="fieldLocations"
              placeholder="Pilih teknisi / lokasi..."
              size="sm"
              :max-render-limit="100"
            />
          </div>

          <!-- Search Input -->
          <div class="w-full sm:w-48">
            <input
              id="field-search"
              v-model="filters.search"
              type="text"
              class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 px-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              placeholder="Cari SKU atau nama produk..."
              @keydown.enter="fetchData(1)"
            >
          </div>

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

          <!-- Export Control -->
          <ReportExportControl
            size="sm"
            :loading="exportStore.isExporting('field-balances')"
            :disabled="false"
            :error="exportStore.errorFor('field-balances')"
            :status="exportStore.statusFor('field-balances')"
            :validation-errors="exportStore.validationErrorsFor('field-balances')"
            :success-message="exportStore.successFor('field-balances')"
            @export="exportCsv"
            @dismiss="exportStore.clearFeedback('field-balances')"
          />
        </div>
      </div>

      <!-- Secondary Row: Advanced Filters (Category, Sorting / Per Page) -->
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
                v-for="cat in categories"
                :key="cat.id"
                :value="cat.id"
              >
                {{ cat.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Sorting & Pagination Count -->
        <div class="flex items-center gap-1.5 flex-wrap ml-auto">
          <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Baris:</span>
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

    <!-- Error Alert -->
    <div
      v-if="store.error"
      class="rounded-lg bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800 flex items-center justify-between shadow-2xs"
    >
      <div class="flex items-center gap-2">
        <svg
          class="w-4 h-4 text-rose-600 flex-shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
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

    <!-- Summary Badges (Compact) -->
    <div
      v-if="store.summary"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5"
    >
      <div class="bg-emerald-50/70 px-3 py-2 rounded-xl shadow-2xs border border-emerald-200">
        <div class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">
          Total Unit Siap Pasang (GOOD)
        </div>
        <div class="mt-0.5 text-lg font-bold font-mono text-emerald-900">
          {{ formatQuantity(store.summary.total_good) }}
        </div>
      </div>
      <div class="bg-amber-50/70 px-3 py-2 rounded-xl shadow-2xs border border-amber-200">
        <div class="text-[10px] font-bold text-amber-800 uppercase tracking-wider">
          Total Unit Rusak Tarikan (DEFECTIVE)
        </div>
        <div class="mt-0.5 text-lg font-bold font-mono text-amber-900">
          {{ formatQuantity(store.summary.total_defective) }}
        </div>
      </div>
      <div class="bg-white px-3 py-2 rounded-xl shadow-2xs border border-gray-200">
        <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">
          Total Unit di Lapangan
        </div>
        <div class="mt-0.5 text-lg font-bold font-mono text-gray-900">
          {{ formatQuantity(store.summary.total_units) }}
        </div>
      </div>
      <div class="bg-indigo-50/70 px-3 py-2 rounded-xl shadow-2xs border border-indigo-200">
        <div class="text-[10px] font-bold text-indigo-800 uppercase tracking-wider">
          Total Nilai Persediaan
        </div>
        <div class="mt-0.5 text-lg font-bold font-mono text-indigo-950">
          {{ formatRupiah(store.summary.total_value) }}
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar">
      <table class="w-full text-left text-xs border-collapse">
        <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10">
          <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
            <th class="py-1.5 px-1.5 w-8 text-center whitespace-nowrap">
              No.
            </th>
            <th class="py-1.5 px-2 whitespace-nowrap">
              Teknisi Lapangan
            </th>
            <th class="py-1.5 px-2 whitespace-nowrap">
              Lokasi / Kode
            </th>
            <th class="py-1.5 px-2 whitespace-nowrap">
              SKU & Produk
            </th>
            <th class="py-1.5 px-2 whitespace-nowrap">
              Kategori
            </th>
            <th class="py-1.5 px-2 text-right whitespace-nowrap">
              Harga Satuan
            </th>
            <th class="py-1.5 px-2 text-right whitespace-nowrap">
              Siap Pasang (GOOD)
            </th>
            <th class="py-1.5 px-2 text-right whitespace-nowrap">
              Rusak (DEFECTIVE)
            </th>
            <th class="py-1.5 px-2 text-right whitespace-nowrap">
              Total Lapangan
            </th>
            <th class="py-1.5 px-2 text-right whitespace-nowrap">
              Total Nilai (Rp)
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="store.loading && store.data.length === 0">
            <td
              colspan="10"
              class="py-8 text-center text-xs text-gray-400"
            >
              Memuat saldo persediaan teknisi...
            </td>
          </tr>
          <tr v-else-if="!store.loading && store.data.length === 0">
            <td
              colspan="10"
              class="py-8 text-center text-xs text-gray-400"
            >
              Tidak ada data saldo lapangan yang sesuai filter.
            </td>
          </tr>
          <tr
            v-for="(row, idx) in store.data"
            :key="row.id"
            class="hover:bg-gray-50/80 transition-colors"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(idx) }}
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <div class="font-medium text-gray-900">
                {{ row.technician_name }}
              </div>
              <div class="text-[10px] text-gray-400">
                {{ row.technician_email }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <div class="font-medium text-gray-900">
                {{ row.location_name }}
              </div>
              <div class="text-[10px] text-gray-400 font-mono">
                {{ row.location_code }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <div class="font-medium text-gray-900">
                {{ row.product_name }}
              </div>
              <div class="text-[10px] text-gray-400 font-mono">
                SKU: {{ row.product_sku }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
              {{ row.category_name }}
            </td>
            <td class="py-1.5 px-2 text-right font-mono text-gray-700 text-[11px] whitespace-nowrap">
              {{ formatRupiah(row.unit_price) }}
            </td>
            <td class="py-1.5 px-2 text-right whitespace-nowrap">
              <span class="px-1.5 py-0.5 rounded font-mono font-bold text-[10px] bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                {{ formatQuantity(row.good_quantity) }} {{ row.unit_name }}
              </span>
            </td>
            <td class="py-1.5 px-2 text-right whitespace-nowrap">
              <span
                class="px-1.5 py-0.5 rounded font-mono font-bold text-[10px]"
                :class="Number(row.defective_quantity) > 0 ? 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20' : 'text-gray-400'"
              >
                {{ formatQuantity(row.defective_quantity) }} {{ row.unit_name }}
              </span>
            </td>
            <td class="py-1.5 px-2 text-right font-mono font-bold text-gray-900 text-[11px] whitespace-nowrap">
              {{ formatQuantity(row.total_quantity) }} {{ row.unit_name }}
            </td>
            <td class="py-1.5 px-2 text-right font-mono font-bold text-indigo-700 text-[11px] whitespace-nowrap">
              {{ formatRupiah(row.total_value ?? (Number(row.total_quantity) * Number(row.unit_price || 0))) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <BasePagination
      :pagination="store.pagination"
      :loading="store.loading"
      @change="fetchData"
    />
  </div>
</template>

<script setup>
import { reactive, ref, computed, watch, onMounted } from 'vue';
import { useFieldBalanceReportStore } from '../stores/useFieldBalanceReportStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import { locationApi } from '@/features/location/api/location_api';
import { reportingApi } from '../api/reportingApi';
import ReportExportControl from '../components/ReportExportControl.vue';
import BaseCombobox from '@/shared/components/BaseCombobox.vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import { formatRupiah, formatQuantity, rowNumber as calcRowNumber } from '@/shared/utils/formatters';

const store = useFieldBalanceReportStore();
const exportStore = useReportCsvExportStore();

const showAdvancedFilters = ref(false);
const fieldLocations = ref([]);
const categories = ref([]);

const filters = reactive({
    location_id: '',
    category_id: '',
    search: '',
    per_page: '15',
});

const activeExtraFiltersCount = computed(() => {
    let count = 0;
    if (filters.category_id) count++;
    if (filters.per_page !== '15') count++;
    return count;
});

const isAnyFilterActive = computed(() => {
    return !!(
        filters.location_id ||
        filters.search ||
        filters.category_id ||
        filters.per_page !== '15'
    );
});

const resetAllFilters = () => {
    filters.location_id = '';
    filters.category_id = '';
    filters.search = '';
    filters.per_page = '15';
    clearTimeout(debounceTimer);
    fetchData(1);
};

let debounceTimer = null;
const debouncedFetch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        fetchData(1);
    }, 400);
};

watch(() => ({ ...filters }), debouncedFetch, { deep: true });

const loadMetadata = async () => {
    try {
        const [locRes, baseRes] = await Promise.all([
            locationApi.getAll({ is_active: 1, per_page: 500 }),
            reportingApi.getFilterBaseOptions(),
        ]);
        const allLocs = locRes.data?.data?.data || locRes.data?.data || [];
        const filtered = allLocs.filter((l) => l.type === 'FIELD_PERSONNEL');
        const list = filtered.length > 0 ? filtered : allLocs;
        fieldLocations.value = list.map((loc) => ({
            ...loc,
            name: loc.user?.name ? `${loc.name} (${loc.user.name})` : loc.name,
        }));
        categories.value = baseRes.data?.data?.categories || baseRes.data?.categories || [];
    } catch {
        // quiet error
    }
};

const fetchData = (page = 1) => {
    const params = {
        page,
        location_id: filters.location_id || undefined,
        category_id: filters.category_id || undefined,
        search: filters.search || undefined,
        per_page: Number(filters.per_page) || 15,
    };
    store.fetchReport(params);
};

const exportCsv = (format = 'xlsx') => {
    const exportFormat = typeof format === 'string' && (format.toLowerCase() === 'csv' || format.toLowerCase() === 'xlsx')
        ? format.toLowerCase()
        : 'xlsx';
    const params = {
        location_id: filters.location_id || undefined,
        category_id: filters.category_id || undefined,
        search: filters.search || undefined,
        format: exportFormat,
    };
    exportStore.exportReport('field-balances', params);
};

const rowNumber = (idx) => {
    return calcRowNumber(store.pagination, idx);
};

onMounted(() => {
    loadMetadata();
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
