<template>
  <div class="space-y-6 p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Laporan Persediaan Lapangan Teknisi
        </h1>
        <p class="text-xs text-gray-500 mt-1">
          Monitoring persediaan unit di tangan teknisi lapangan: pemisahan unit bagus siap pasang (GOOD) vs unit rusak tarikan toko (DEFECTIVE).
        </p>
      </div>
      <div>
        <ReportCsvExportControl
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

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-200 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Lokasi Teknisi</label>
          <select
            v-model="filters.location_id"
            class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="">
              Semua Teknisi / Lokasi
            </option>
            <option
              v-for="loc in fieldLocations"
              :key="loc.id"
              :value="loc.id"
            >
              {{ loc.name }} ({{ loc.code }}) - {{ loc.user?.name || 'Teknisi' }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Kategori</label>
          <select
            v-model="filters.category_id"
            class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
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
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Pencarian Produk / Teknisi</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="SKU, nama produk, teknisi..."
            class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
            @keydown.enter="fetchData(1)"
          >
        </div>
        <div class="flex gap-2">
          <button
            type="button"
            class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 cursor-pointer"
            @click="fetchData(1)"
          >
            Terapkan Filter
          </button>
          <button
            type="button"
            class="rounded-md bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 cursor-pointer"
            @click="resetFilters"
          >
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Error Alert -->
    <div
      v-if="store.error"
      class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800"
    >
      {{ store.error }}
    </div>

    <!-- Summary Badges -->
    <div
      v-if="store.summary"
      class="grid grid-cols-1 sm:grid-cols-3 gap-4"
    >
      <div class="bg-emerald-50/70 p-4 rounded-xl shadow-xs border border-emerald-200">
        <div class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">
          Total Unit Siap Pasang (GOOD)
        </div>
        <div class="mt-1 text-2xl font-bold text-emerald-900">
          {{ formatNumber(store.summary.total_good) }}
        </div>
      </div>
      <div class="bg-amber-50/70 p-4 rounded-xl shadow-xs border border-amber-200">
        <div class="text-xs font-semibold text-amber-800 uppercase tracking-wider">
          Total Unit Rusak Tarikan (DEFECTIVE)
        </div>
        <div class="mt-1 text-2xl font-bold text-amber-900">
          {{ formatNumber(store.summary.total_defective) }}
        </div>
      </div>
      <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-200">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
          Total Unit di Lapangan
        </div>
        <div class="mt-1 text-2xl font-bold text-gray-900">
          {{ formatNumber(store.summary.total_units) }}
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
              Siap Pasang (GOOD)
            </th>
            <th class="py-1.5 px-2 text-right whitespace-nowrap">
              Rusak (DEFECTIVE)
            </th>
            <th class="py-1.5 px-2 text-right whitespace-nowrap">
              Total Lapangan
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="store.loading && store.data.length === 0">
            <td
              colspan="8"
              class="py-8 text-center text-xs text-gray-400"
            >
              Memuat saldo persediaan teknisi...
            </td>
          </tr>
          <tr v-else-if="!store.loading && store.data.length === 0">
            <td
              colspan="8"
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
            <td class="py-1.5 px-2 text-right whitespace-nowrap">
              <span class="px-1.5 py-0.5 rounded font-mono font-bold text-[10px] bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                {{ row.good_quantity }} {{ row.unit_name }}
              </span>
            </td>
            <td class="py-1.5 px-2 text-right whitespace-nowrap">
              <span
                class="px-1.5 py-0.5 rounded font-mono font-bold text-[10px]"
                :class="Number(row.defective_quantity) > 0 ? 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20' : 'text-gray-400'"
              >
                {{ row.defective_quantity }} {{ row.unit_name }}
              </span>
            </td>
            <td class="py-1.5 px-2 text-right font-mono font-bold text-gray-900 text-[11px] whitespace-nowrap">
              {{ row.total_quantity }} {{ row.unit_name }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

      <!-- Pagination -->
      <div
        v-if="store.pagination?.last_page > 1"
        class="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6"
      >
        <div class="text-xs text-gray-700">
          Halaman {{ store.pagination.current_page }} dari {{ store.pagination.last_page }} (Total: {{ store.pagination.total }} baris)
        </div>
        <div class="flex gap-2">
          <button
            :disabled="store.pagination.current_page <= 1"
            class="rounded border border-gray-300 px-2.5 py-1 text-xs disabled:opacity-50"
            @click="fetchData(store.pagination.current_page - 1)"
          >
            Sebelumnya
          </button>
          <button
            :disabled="store.pagination.current_page >= store.pagination.last_page"
            class="rounded border border-gray-300 px-2.5 py-1 text-xs disabled:opacity-50"
            @click="fetchData(store.pagination.current_page + 1)"
          >
            Selanjutnya
          </button>
        </div>
      </div>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { useFieldBalanceReportStore } from '../stores/useFieldBalanceReportStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import { locationApi } from '@/features/location/api/location_api';
import { reportingApi } from '../api/reportingApi';
import ReportCsvExportControl from '../components/ReportCsvExportControl.vue';

const store = useFieldBalanceReportStore();
const exportStore = useReportCsvExportStore();

const fieldLocations = ref([]);
const categories = ref([]);

const filters = reactive({
    location_id: '',
    category_id: '',
    search: '',
});

const loadMetadata = async () => {
    try {
        const [locRes, baseRes] = await Promise.all([
            locationApi.getAll({ is_active: 1, per_page: 500 }),
            reportingApi.getFilterBaseOptions(),
        ]);
        const allLocs = locRes.data?.data?.data || locRes.data?.data || [];
        fieldLocations.value = allLocs.filter((l) => l.type === 'FIELD_PERSONNEL');
        if (fieldLocations.value.length === 0) {
            fieldLocations.value = allLocs;
        }
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
    };
    store.fetchReport(params);
};

const resetFilters = () => {
    filters.location_id = '';
    filters.category_id = '';
    filters.search = '';
    fetchData(1);
};

const exportCsv = () => {
    const params = {
        location_id: filters.location_id || undefined,
        category_id: filters.category_id || undefined,
        search: filters.search || undefined,
    };
    exportStore.exportReport('field-balances', params);
};

const rowNumber = (idx) => {
    if (!store.pagination) return idx + 1;
    return (store.pagination.current_page - 1) * store.pagination.per_page + idx + 1;
};

const formatNumber = (num) => {
    return Number(num || 0).toLocaleString('id-ID');
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
