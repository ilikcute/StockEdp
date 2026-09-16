<template>
  <div class="space-y-3">
    <!-- Top Header & Filter Toolbar (Compact) -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs space-y-2.5">
      <!-- Primary Controls Row -->
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
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
              />
            </svg>
            Laporan Histori Kerusakan & Alokasi Toko
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Histori penggantian unit operasional toko, unit baru terpasang, dan unit rusak tarikan teknisi.
          </p>
        </div>

        <!-- Controls (Search, Store Filter, Toggle Filter, Reset, Export CSV) -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          <!-- Search Input -->
          <div class="w-full sm:w-56">
            <input
              id="search"
              v-model="filters.search"
              type="text"
              placeholder="No. alokasi, S/N, alasan..."
              class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 px-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              @input="handleSearch"
            >
          </div>

          <!-- Store Selector -->
          <div class="w-full sm:w-48">
            <select
              v-model="filters.store_id"
              class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              @change="fetchData(1)"
            >
              <option value="">
                Semua Toko
              </option>
              <option
                v-for="st in stores"
                :key="st.id"
                :value="st.id"
              >
                {{ st.name }} ({{ st.code }})
              </option>
            </select>
          </div>

          <!-- Toggle Date Filter -->
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium shadow-2xs transition-colors cursor-pointer whitespace-nowrap',
              showDateFilter || hasActiveDates
                ? 'bg-indigo-50 border-indigo-200 text-indigo-700 hover:bg-indigo-100'
                : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
            ]"
            @click="showDateFilter = !showDateFilter"
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
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
            <span>Tanggal</span>
            <span
              v-if="hasActiveDates"
              class="inline-flex items-center justify-center w-2 h-2 bg-indigo-600 rounded-full"
            />
          </button>

          <!-- Reset Filter -->
          <button
            v-if="isAnyFilterActive"
            type="button"
            class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 shadow-2xs cursor-pointer whitespace-nowrap"
            title="Reset Filter"
            @click="resetFilters"
          >
            Reset
          </button>

          <!-- Export CSV Control (Compact size="sm") -->
          <ReportCsvExportControl
            size="sm"
            :loading="exportStore.isExporting('store-allocations')"
            :disabled="false"
            :error="exportStore.errorFor('store-allocations')"
            :status="exportStore.statusFor('store-allocations')"
            :validation-errors="exportStore.validationErrorsFor('store-allocations')"
            :success-message="exportStore.successFor('store-allocations')"
            @export="exportCsv"
            @dismiss="exportStore.clearFeedback('store-allocations')"
          />
        </div>
      </div>

      <!-- Collapsible Date Filter Row -->
      <div
        v-if="showDateFilter || hasActiveDates"
        class="border-t border-gray-100 pt-2.5 flex flex-wrap items-center gap-3 text-xs"
      >
        <div class="flex items-center gap-2">
          <span class="text-[11px] font-semibold text-gray-600">Mulai:</span>
          <input
            v-model="filters.start_date"
            type="date"
            class="rounded-lg border border-gray-300 bg-white py-1 px-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            @change="fetchData(1)"
          >
        </div>
        <div class="flex items-center gap-2">
          <span class="text-[11px] font-semibold text-gray-600">Sampai:</span>
          <input
            v-model="filters.end_date"
            type="date"
            class="rounded-lg border border-gray-300 bg-white py-1 px-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            @change="fetchData(1)"
          >
        </div>
        <button
          v-if="hasActiveDates"
          type="button"
          class="text-xs text-gray-500 hover:text-rose-600 cursor-pointer font-medium"
          @click="clearDates"
        >
          Hapus Periode
        </button>
      </div>
    </div>

    <!-- Alert Error Compact -->
    <div
      v-if="store.error"
      class="rounded-lg bg-rose-50 border border-rose-200 p-2.5 text-xs text-rose-800 flex items-center justify-between shadow-2xs"
    >
      <span>{{ store.error }}</span>
      <button
        type="button"
        class="text-rose-500 hover:text-rose-700 text-xs font-semibold cursor-pointer"
        @click="store.error = null"
      >
        Tutup
      </button>
    </div>

    <!-- Summary Badges (Compact) -->
    <div
      v-if="store.summary"
      class="grid grid-cols-2 sm:grid-cols-4 gap-2"
    >
      <!-- Total Alokasi Selesai -->
      <div class="bg-white px-3 py-2 rounded-xl shadow-2xs border border-gray-200 flex items-center gap-2.5">
        <div class="p-1.5 bg-blue-50 text-blue-600 rounded-lg shrink-0">
          <svg
            class="w-4 h-4"
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
        </div>
        <div class="min-w-0">
          <div class="text-[10px] font-bold uppercase tracking-wider text-gray-500 truncate">
            Total Alokasi
          </div>
          <div class="text-lg font-bold font-mono text-gray-900 leading-tight">
            {{ formatQuantity(store.summary.total_allocations) }} <span class="text-xs font-normal text-gray-500">Dok</span>
          </div>
        </div>
      </div>

      <!-- Total Unit Baru Dipasang -->
      <div class="bg-emerald-50/50 px-3 py-2 rounded-xl shadow-2xs border border-emerald-200 flex items-center gap-2.5">
        <div class="p-1.5 bg-emerald-100 text-emerald-700 rounded-lg shrink-0">
          <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5 13l4 4L19 7"
            />
          </svg>
        </div>
        <div class="min-w-0">
          <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 truncate">
            Dipasang (GOOD)
          </div>
          <div class="text-lg font-bold font-mono text-emerald-900 leading-tight">
            {{ formatQuantity(store.summary.total_installed) }} <span class="text-xs font-normal text-emerald-700">Unit</span>
          </div>
        </div>
      </div>

      <!-- Total Unit Rusak Ditarik -->
      <div class="bg-amber-50/50 px-3 py-2 rounded-xl shadow-2xs border border-amber-200 flex items-center gap-2.5">
        <div class="p-1.5 bg-amber-100 text-amber-700 rounded-lg shrink-0">
          <svg
            class="w-4 h-4"
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
        </div>
        <div class="min-w-0">
          <div class="text-[10px] font-bold uppercase tracking-wider text-amber-800 truncate">
            Ditarik (DEFECTIVE)
          </div>
          <div class="text-lg font-bold font-mono text-amber-900 leading-tight">
            {{ formatQuantity(store.summary.total_pulled) }} <span class="text-xs font-normal text-amber-700">Unit</span>
          </div>
        </div>
      </div>

      <!-- Total Nilai Alokasi Dipasang -->
      <div class="bg-indigo-50/50 px-3 py-2 rounded-xl shadow-2xs border border-indigo-200 flex items-center gap-2.5">
        <div class="p-1.5 bg-indigo-100 text-indigo-700 rounded-lg shrink-0">
          <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </div>
        <div class="min-w-0">
          <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-800 truncate">
            Total Nilai Dipasang
          </div>
          <div class="text-lg font-bold font-mono text-indigo-900 leading-tight">
            {{ formatRupiah(store.summary.total_value) }}
          </div>
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
              No. Alokasi & Tanggal
            </th>
            <th class="py-1.5 px-2 whitespace-nowrap">
              Toko
            </th>
            <th class="py-1.5 px-2 whitespace-nowrap">
              Teknisi / Lokasi
            </th>
            <th class="py-1.5 px-2 whitespace-nowrap">
              Unit Dipasang (GOOD)
            </th>
            <th class="py-1.5 px-2 text-right whitespace-nowrap">
              Harga Satuan
            </th>
            <th class="py-1.5 px-2 text-right w-16 whitespace-nowrap">
              Qty
            </th>
            <th class="py-1.5 px-2 text-right whitespace-nowrap">
              Total Nilai (Rp)
            </th>
            <th class="py-1.5 px-2 whitespace-nowrap">
              S/N Baru
            </th>
            <th class="py-1.5 px-2 whitespace-nowrap">
              Unit Ditarik (DEFECTIVE)
            </th>
            <th class="py-1.5 px-2 text-right w-16 whitespace-nowrap">
              Qty
            </th>
            <th class="py-1.5 px-2 whitespace-nowrap">
              S/N Rusak
            </th>
            <th class="py-1.5 px-2 whitespace-nowrap">
              Alasan Kerusakan
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="store.loading && store.data.length === 0">
            <td
              colspan="13"
              class="py-8 text-center text-xs text-gray-400"
            >
              Memuat data histori alokasi...
            </td>
          </tr>
          <tr v-else-if="!store.loading && store.data.length === 0">
            <td
              colspan="13"
              class="py-8 text-center text-xs text-gray-400"
            >
              Tidak ada data alokasi yang sesuai filter.
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
              <div class="font-mono font-medium text-indigo-600">
                {{ row.allocation_number }}
              </div>
              <div class="text-[10px] text-gray-400">
                {{ row.allocated_at }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <div class="font-medium text-gray-900">
                {{ row.store_name }}
              </div>
              <div class="text-[10px] text-gray-400 font-mono">
                {{ row.store_code }} {{ row.store_address ? `• ${row.store_address}` : '' }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <div class="font-medium text-gray-900">
                {{ row.technician_name }}
              </div>
              <div class="text-[10px] text-gray-400">
                {{ row.technician_location_name }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <div class="font-medium text-emerald-900">
                {{ row.product_name }}
              </div>
              <div class="text-[10px] font-mono text-gray-400">
                SKU: {{ row.product_sku }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-right font-mono text-gray-700 text-[11px] whitespace-nowrap">
              {{ formatRupiah(row.unit_price) }}
            </td>
            <td class="py-1.5 px-2 text-right font-mono font-bold text-emerald-700 text-[11px] whitespace-nowrap">
              {{ formatQuantity(row.quantity) }}
            </td>
            <td class="py-1.5 px-2 text-right font-mono font-bold text-indigo-700 text-[11px] whitespace-nowrap">
              {{ formatRupiah(row.total_value) }}
            </td>
            <td class="py-1.5 px-2 font-mono text-gray-700 text-[11px] whitespace-nowrap">
              {{ row.serial_number || '-' }}
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <div
                v-if="row.pulled_product_name"
                class="font-medium text-amber-900"
              >
                {{ row.pulled_product_name }}
                <div class="text-[10px] font-mono text-gray-400">
                  SKU: {{ row.pulled_product_sku }}
                </div>
              </div>
              <span
                v-else
                class="text-gray-400 italic text-[11px]"
              >-</span>
            </td>
            <td class="py-1.5 px-2 text-right font-mono font-bold text-amber-700 text-[11px] whitespace-nowrap">
              {{ row.pulled_quantity ? formatQuantity(row.pulled_quantity) : '-' }}
            </td>
            <td class="py-1.5 px-2 font-mono text-gray-700 text-[11px] whitespace-nowrap">
              {{ row.pulled_serial_number || '-' }}
            </td>
            <td class="py-1.5 px-2 text-gray-700 text-[11px]">
              {{ row.defective_reason || '-' }}
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
import { computed, onMounted, reactive, ref } from 'vue';
import { useStoreAllocationReportStore } from '../stores/useStoreAllocationReportStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import { storeApi } from '@/features/store/api/store_api';
import { formatQuantity, formatRupiah } from '@/shared/utils/formatters';
import ReportCsvExportControl from '../components/ReportCsvExportControl.vue';
import BasePagination from '@/shared/components/BasePagination.vue';

const store = useStoreAllocationReportStore();
const exportStore = useReportCsvExportStore();

const stores = ref([]);
const showDateFilter = ref(false);

const filters = reactive({
    start_date: '',
    end_date: '',
    store_id: '',
    search: '',
});

const hasActiveDates = computed(() => Boolean(filters.start_date || filters.end_date));
const isAnyFilterActive = computed(() => Boolean(filters.search || filters.store_id || filters.start_date || filters.end_date));

const loadStores = async () => {
    try {
        const res = await storeApi.getAll({ is_active: 1, per_page: 500 });
        stores.value = res.data?.data?.data || res.data?.data || [];
    } catch {
        // quiet error
    }
};

let searchTimer = null;
const handleSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        fetchData(1);
    }, 300);
};

const fetchData = (page = 1) => {
    const params = {
        page,
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
        store_id: filters.store_id || undefined,
        search: filters.search || undefined,
    };
    store.fetchReport(params);
};

const clearDates = () => {
    filters.start_date = '';
    filters.end_date = '';
    fetchData(1);
};

const resetFilters = () => {
    filters.start_date = '';
    filters.end_date = '';
    filters.store_id = '';
    filters.search = '';
    showDateFilter.value = false;
    fetchData(1);
};

const exportCsv = (format = 'xlsx') => {
    const exportFormat = typeof format === 'string' && (format.toLowerCase() === 'csv' || format.toLowerCase() === 'xlsx')
        ? format.toLowerCase()
        : 'xlsx';
    const params = {
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
        store_id: filters.store_id || undefined,
        search: filters.search || undefined,
        format: exportFormat,
    };
    exportStore.exportReport('store-allocations', params);
};

const rowNumber = (idx) => {
    if (!store.pagination) return idx + 1;
    return (store.pagination.current_page - 1) * store.pagination.per_page + idx + 1;
};

onMounted(() => {
    loadStores();
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
