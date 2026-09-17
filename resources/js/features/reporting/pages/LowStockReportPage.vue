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
              class="w-4 h-4 text-amber-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>
            Laporan Stok Minimum
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Monitoring produk yang jumlah stoknya berada di bawah batas minimum pada lokasi tertentu.
          </p>
        </div>

        <!-- Primary Controls (Location, Search, Filter Toggle, Reset, Export) -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          <!-- Location Selector (Required) -->
          <div class="w-full sm:w-56 min-w-[200px]">
            <BaseCombobox
              id="filter-location"
              v-model="filters.location_id"
              :options="masterStore.locations"
              placeholder="Pilih lokasi gudang / teknisi..."
              size="sm"
              :max-render-limit="100"
              required
            />
          </div>

          <!-- Search Input -->
          <div class="w-full sm:w-48">
            <input
              id="search"
              v-model="filters.search"
              type="text"
              class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 px-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              placeholder="Cari SKU atau nama produk..."
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
            :loading="exportStore.isExporting(reportKey)"
            :disabled="!filters.location_id"
            disabled-reason="Pilih lokasi terlebih dahulu."
            :error="exportStore.errorFor(reportKey)"
            :status="exportStore.statusFor(reportKey)"
            :validation-errors="exportStore.validationErrorsFor(reportKey)"
            :success-message="exportStore.successFor(reportKey)"
            @export="exportCsv"
            @dismiss="exportStore.clearFeedback(reportKey)"
          />
        </div>
      </div>

      <!-- Secondary Row: Advanced Filters (Category, Unit, Active Status, Sorting) -->
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

          <!-- Tampilkan Produk Nonaktif -->
          <label class="flex items-center gap-1.5 cursor-pointer select-none text-[11px] text-gray-700 pl-1">
            <input
              v-model="filters.include_inactive"
              type="checkbox"
              value="1"
              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5 cursor-pointer"
            >
            <span>Produk Nonaktif</span>
          </label>
        </div>

        <!-- Sorting & Pagination Count -->
        <div class="flex items-center gap-1.5 flex-wrap ml-auto">
          <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Urutkan:</span>
          <select
            v-model="filters.sort_by"
            class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="shortage_quantity">
              Defisit
            </option>
            <option value="minimum_stock">
              Stok Minimum
            </option>
            <option value="on_hand_quantity">
              Stok Saat Ini
            </option>
            <option value="product_name">
              Nama Produk
            </option>
            <option value="sku">
              SKU
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

    <!-- Validation Errors Alert -->
    <div
      v-if="Object.keys(store.validationErrors).length > 0"
      class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800 shadow-2xs flex items-start justify-between"
    >
      <ul class="list-disc pl-4 space-y-0.5">
        <li
          v-for="(errors, field) in store.validationErrors"
          :key="field"
        >
          {{ errors.join(', ') }}
        </li>
      </ul>
      <button
        type="button"
        class="text-amber-600 hover:text-amber-800 text-xs font-semibold ml-2 cursor-pointer"
        @click="store.validationErrors = {}"
      >
        Tutup
      </button>
    </div>

    <!-- Summary Badges (Compact) -->
    <div
      v-if="filters.location_id && store.data.length > 0"
      class="grid grid-cols-1 sm:grid-cols-3 gap-2.5"
    >
      <div class="bg-amber-50/70 px-3 py-2 rounded-xl shadow-2xs border border-amber-200">
        <div class="text-[10px] font-bold text-amber-800 uppercase tracking-wider">
          Total Item Defisit
        </div>
        <div class="mt-0.5 text-lg font-bold font-mono text-amber-900">
          {{ pageSummary.totalItems }} <span class="text-xs font-normal text-amber-700">SKU</span>
        </div>
      </div>
      <div class="bg-rose-50/70 px-3 py-2 rounded-xl shadow-2xs border border-rose-200">
        <div class="text-[10px] font-bold text-rose-800 uppercase tracking-wider">
          Total Kekurangan Stok (Shortage)
        </div>
        <div class="mt-0.5 text-lg font-bold font-mono text-rose-900">
          {{ formatQuantity(pageSummary.totalShortage) }} <span class="text-xs font-normal text-rose-700">Unit</span>
        </div>
      </div>
      <div class="bg-indigo-50/70 px-3 py-2 rounded-xl shadow-2xs border border-indigo-200">
        <div class="text-[10px] font-bold text-indigo-800 uppercase tracking-wider">
          Total Estimasi Biaya Defisit
        </div>
        <div class="mt-0.5 text-lg font-bold font-mono text-indigo-950">
          {{ formatRupiah(pageSummary.totalEstimatedCost) }}
        </div>
      </div>
    </div>

    <!-- Empty Location Prompt -->
    <div
      v-if="!filters.location_id"
      class="rounded-xl bg-white border border-gray-200 p-8 text-center shadow-2xs"
    >
      <div class="mx-auto w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mb-2">
        <svg
          class="w-5 h-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
          />
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
          />
        </svg>
      </div>
      <p class="text-xs font-semibold text-gray-800">
        Silakan pilih lokasi gudang atau teknisi
      </p>
      <p class="text-[11px] text-gray-500 mt-0.5">
        Pilih lokasi pada bilah filter di atas untuk menampilkan daftar produk di bawah batas stok minimum.
      </p>
    </div>

    <!-- Data Table -->
    <div
      v-else
      class="overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar relative"
    >
      <div
        v-if="store.loading"
        class="absolute inset-0 bg-white/50 z-20 flex items-center justify-center"
      >
        <span class="text-indigo-600 font-medium bg-white px-3 py-1.5 rounded-lg shadow-sm text-xs">Memuat data...</span>
      </div>

      <div
        v-if="selectedLocationName"
        class="px-3 py-1.5 bg-gray-50/80 border-b border-gray-200 text-xs text-gray-600 flex items-center justify-between"
      >
        <div class="flex items-center gap-1.5">
          <span class="text-[11px] text-gray-400">Lokasi Terpilih:</span>
          <span class="font-semibold text-gray-900">{{ selectedLocationName }}</span>
        </div>
        <div
          v-if="store.meta?.total !== undefined"
          class="text-[11px] text-gray-500 font-mono"
        >
          Total Defisit: <span class="font-bold text-rose-600">{{ store.meta.total }}</span> item
        </div>
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
              class="py-1.5 px-2 text-right whitespace-nowrap"
            >
              Harga Satuan
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap"
            >
              On-Hand
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap"
            >
              Min Stock
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap text-rose-700"
            >
              Shortage
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap text-rose-700"
            >
              Estimasi Defisit
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="store.loading && store.data.length === 0">
            <td
              colspan="8"
              class="py-8 text-center text-xs text-gray-400"
            >
              Memuat data stok minimum...
            </td>
          </tr>
          <BaseTableEmpty
            v-else-if="!store.loading && store.data.length === 0"
            :colspan="8"
            message="Tidak ada produk di bawah stok minimum pada lokasi ini."
            :hint="isAnyFilterActive ? 'Silakan sesuaikan parameter filter atau pencarian Anda.' : 'Semua produk pada lokasi ini memiliki stok yang aman dan mencukupi.'"
          />
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
                {{ item.product_name }}
              </div>
              <div class="text-[10px] text-gray-400 font-mono">
                {{ item.sku }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
              <div>{{ item.category_name || '-' }}</div>
              <div class="text-[10px] text-gray-400">
                {{ item.unit_name || '-' }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-[11px] text-right font-mono text-gray-700 whitespace-nowrap">
              {{ formatRupiah(item.unit_price) }}
            </td>
            <td class="py-1.5 px-2 text-[11px] text-right font-mono text-gray-900 font-semibold whitespace-nowrap">
              {{ formatQuantity(item.on_hand_quantity) }}
            </td>
            <td class="py-1.5 px-2 text-[11px] text-right font-mono text-gray-500 whitespace-nowrap">
              {{ formatQuantity(item.minimum_stock) }}
            </td>
            <td class="py-1.5 px-2 text-[11px] text-right font-mono font-semibold text-rose-600 whitespace-nowrap">
              -{{ formatQuantity(item.shortage_quantity) }}
            </td>
            <td class="py-1.5 px-2 text-[11px] text-right font-mono font-semibold text-rose-700 whitespace-nowrap">
              {{ formatRupiah(item.deficit_estimated_cost || (Number(item.shortage_quantity || 0) * (item.unit_price || 0))) }}
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
import { onMounted, watch, reactive, computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useLowStockReportStore } from '../stores/useLowStockReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import ReportExportControl from '../components/ReportExportControl.vue';
import BaseCombobox from '@/shared/components/BaseCombobox.vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import BaseTableEmpty from '@/shared/components/BaseTableEmpty.vue';
import { cleanReportExportFilters } from '../utils/reportHelpers';
import { formatRupiah, formatQuantity, rowNumber } from '@/shared/utils/formatters.js';

const route = useRoute();
const router = useRouter();
const store = useLowStockReportStore();
const masterStore = useReportFilterOptionsStore();
const exportStore = useReportCsvExportStore();
const reportKey = 'low-stock';

const showAdvancedFilters = ref(false);

const selectedLocationName = computed(() => {
    if (!filters.location_id) return '';
    const loc = masterStore.locations.find(l => String(l.id) === String(filters.location_id));
    return loc ? (loc.code ? `${loc.code} — ${loc.name}` : loc.name) : '';
});

const filters = reactive({
    location_id: '',
    search: '',
    category_id: '',
    unit_id: '',
    include_inactive: false,
    sort_by: 'shortage_quantity',
    sort_order: 'desc',
    per_page: '15',
});

const activeExtraFiltersCount = computed(() => {
    let count = 0;
    if (filters.category_id) count++;
    if (filters.unit_id) count++;
    if (filters.include_inactive) count++;
    if (filters.sort_by !== 'shortage_quantity' || filters.sort_order !== 'desc' || filters.per_page !== '15') count++;
    return count;
});

const isAnyFilterActive = computed(() => {
    return !!(
        filters.search ||
        filters.category_id ||
        filters.unit_id ||
        filters.include_inactive ||
        filters.sort_by !== 'shortage_quantity' ||
        filters.sort_order !== 'desc' ||
        filters.per_page !== '15'
    );
});

const resetAllFilters = () => {
    filters.search = '';
    filters.category_id = '';
    filters.unit_id = '';
    filters.include_inactive = false;
    filters.sort_by = 'shortage_quantity';
    filters.sort_order = 'desc';
    filters.per_page = '15';
    if (filters.location_id) {
        clearTimeout(debounceTimer);
        fetchData(1);
    }
};

const pageSummary = computed(() => {
    if (!store.data || store.data.length === 0) {
        return { totalItems: 0, totalShortage: 0, totalEstimatedCost: 0 };
    }
    let totalShortage = 0;
    let totalEstimatedCost = 0;
    for (const item of store.data) {
        const shortage = Number(item.shortage_quantity || 0);
        const price = Number(item.unit_price || 0);
        totalShortage += shortage;
        totalEstimatedCost += Number(item.deficit_estimated_cost || (shortage * price));
    }
    return {
        totalItems: store.meta?.total || store.data.length,
        totalShortage,
        totalEstimatedCost,
    };
});

let debounceTimer = null;
const debouncedFetch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        if (filters.location_id) {
            fetchData(1);
        } else {
            store.reset();
        }
    }, 500);
};

watch(() => ({ ...filters }), debouncedFetch, { deep: true });

// Sync filters when route query location_id changes
watch(() => route.query.location_id, (newLocId) => {
    if (newLocId && String(newLocId) !== String(filters.location_id)) {
        filters.location_id = String(newLocId);
        clearTimeout(debounceTimer);
        fetchData(1);
    }
});

// Update URL query when location filter changes so state is shareable & persists on reload
watch(() => filters.location_id, (newLocId) => {
    if (newLocId && String(route.query.location_id) !== String(newLocId)) {
        router.replace({ query: { ...route.query, location_id: newLocId } });
    }
});

const fetchData = (page = 1) => {
    if (!filters.location_id) return;

    store.fetchLowStock({
        page,
        ...filters,
        include_inactive: filters.include_inactive ? 1 : null,
    });
};

const exportCsv = async (format = 'xlsx') => {
    if (!filters.location_id) return;
    const exportFormat = typeof format === 'string' && (format.toLowerCase() === 'csv' || format.toLowerCase() === 'xlsx')
        ? format.toLowerCase()
        : 'xlsx';
    const params = cleanReportExportFilters({
        ...filters,
        include_inactive: filters.include_inactive ? 1 : null,
        format: exportFormat,
    });
    await exportStore.exportReport(reportKey, params);
};

const changePage = (page) => {
    if (!store.meta || page < 1 || page > store.meta.last_page) {
        return;
    }
    store.fetchLowStock({
        page,
        ...filters,
        include_inactive: filters.include_inactive ? 1 : null,
    });
};

const resolveInitialLocation = () => {
    // 1. From route query
    if (route.query.location_id) {
        const found = masterStore.locations.find(l => String(l.id) === String(route.query.location_id));
        if (found) {
            return String(found.id);
        }
    }

    // 2. Default to ADM location
    const adm = masterStore.locations.find(l =>
        (l.code && l.code.toUpperCase() === 'ADM') ||
        (l.name && l.name.toUpperCase().includes('ADM'))
    );
    if (adm) {
        return String(adm.id);
    }

    // 3. Fallback to first available location
    if (masterStore.locations.length > 0) {
        return String(masterStore.locations[0].id);
    }

    return '';
};

onMounted(async () => {
    await masterStore.fetchOptions();
    const initialLocation = resolveInitialLocation();
    if (initialLocation) {
        filters.location_id = initialLocation;
        if (String(route.query.location_id) !== String(initialLocation)) {
            router.replace({ query: { ...route.query, location_id: initialLocation } });
        }
        clearTimeout(debounceTimer);
        fetchData(1);
    }
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
