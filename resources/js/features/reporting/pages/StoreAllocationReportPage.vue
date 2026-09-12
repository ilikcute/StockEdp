<template>
  <div class="space-y-6 p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Laporan Histori Kerusakan & Alokasi Toko
        </h1>
        <p class="text-xs text-gray-500 mt-1">
          Histori penggantian unit operasional toko, unit bagus yang terpasang, dan unit rusak yang ditarik teknisi.
        </p>
      </div>
      <div>
        <ReportCsvExportControl
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

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-200 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Tanggal Mulai</label>
          <input
            v-model="filters.start_date"
            type="date"
            class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
          >
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Tanggal Akhir</label>
          <input
            v-model="filters.end_date"
            type="date"
            class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
          >
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Toko</label>
          <select
            v-model="filters.store_id"
            class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
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
        <div>
          <label class="block text-xs font-semibold text-gray-700 mb-1">Pencarian</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="No. alokasi, serial number, alasan..."
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

    <!-- Feedback / Alerts -->
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
      <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-200">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
          Total Alokasi Selesai
        </div>
        <div class="mt-1 text-2xl font-bold text-gray-900">
          {{ store.summary.total_allocations }} Dokumen
        </div>
      </div>
      <div class="bg-emerald-50/60 p-4 rounded-xl shadow-xs border border-emerald-200">
        <div class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">
          Total Unit Baru Dipasang
        </div>
        <div class="mt-1 text-2xl font-bold text-emerald-900">
          {{ store.summary.total_installed }} Unit
        </div>
      </div>
      <div class="bg-amber-50/60 p-4 rounded-xl shadow-xs border border-amber-200">
        <div class="text-xs font-semibold text-amber-800 uppercase tracking-wider">
          Total Unit Rusak Ditarik
        </div>
        <div class="mt-1 text-2xl font-bold text-amber-900">
          {{ store.summary.total_pulled }} Unit
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-gray-50 text-gray-700 font-semibold border-b border-gray-200">
              <th class="py-3 px-3 w-10 text-center">
                No.
              </th>
              <th class="py-3 px-3">
                No. Alokasi & Tanggal
              </th>
              <th class="py-3 px-3">
                Toko
              </th>
              <th class="py-3 px-3">
                Teknisi / Lokasi
              </th>
              <th class="py-3 px-3">
                Unit Dipasang (GOOD)
              </th>
              <th class="py-3 px-3 text-right w-16">
                Qty
              </th>
              <th class="py-3 px-3">
                S/N Baru
              </th>
              <th class="py-3 px-3">
                Unit Ditarik (DEFECTIVE)
              </th>
              <th class="py-3 px-3 text-right w-16">
                Qty
              </th>
              <th class="py-3 px-3">
                S/N Rusak
              </th>
              <th class="py-3 px-3">
                Alasan Kerusakan
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="store.loading && store.data.length === 0">
              <td
                colspan="11"
                class="py-10 text-center text-gray-500"
              >
                Memuat data histori alokasi...
              </td>
            </tr>
            <tr v-else-if="!store.loading && store.data.length === 0">
              <td
                colspan="11"
                class="py-10 text-center text-gray-400"
              >
                Tidak ada data alokasi yang sesuai filter.
              </td>
            </tr>
            <tr
              v-for="(row, idx) in store.data"
              :key="row.id"
              class="hover:bg-gray-50/80"
            >
              <td class="py-2.5 px-3 text-center text-gray-400 font-mono">
                {{ rowNumber(idx) }}
              </td>
              <td class="py-2.5 px-3">
                <div class="font-mono font-bold text-gray-900">
                  {{ row.allocation_number }}
                </div>
                <div class="text-[11px] text-gray-500">
                  {{ row.allocated_at }}
                </div>
              </td>
              <td class="py-2.5 px-3">
                <div class="font-medium text-gray-900">
                  {{ row.store_name }}
                </div>
                <div class="text-[11px] text-gray-500 font-mono">
                  {{ row.store_code }} {{ row.store_address ? `• ${row.store_address}` : '' }}
                </div>
              </td>
              <td class="py-2.5 px-3">
                <div class="font-medium text-gray-900">
                  {{ row.technician_name }}
                </div>
                <div class="text-[11px] text-gray-500">
                  {{ row.technician_location_name }}
                </div>
              </td>
              <td class="py-2.5 px-3">
                <div class="font-medium text-emerald-900">
                  {{ row.product_name }}
                </div>
                <div class="text-[11px] font-mono text-gray-500">
                  SKU: {{ row.product_sku }}
                </div>
              </td>
              <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-700">
                {{ row.quantity }}
              </td>
              <td class="py-2.5 px-3 font-mono text-gray-700">
                {{ row.serial_number || '-' }}
              </td>
              <td class="py-2.5 px-3">
                <div
                  v-if="row.pulled_product_name"
                  class="font-medium text-amber-900"
                >
                  {{ row.pulled_product_name }}
                  <div class="text-[11px] font-mono text-gray-500">
                    SKU: {{ row.pulled_product_sku }}
                  </div>
                </div>
                <span
                  v-else
                  class="text-gray-400 italic"
                >-</span>
              </td>
              <td class="py-2.5 px-3 text-right font-mono font-bold text-amber-700">
                {{ row.pulled_quantity || '-' }}
              </td>
              <td class="py-2.5 px-3 font-mono text-gray-700">
                {{ row.pulled_serial_number || '-' }}
              </td>
              <td class="py-2.5 px-3 text-gray-700">
                {{ row.defective_reason || '-' }}
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
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import { useStoreAllocationReportStore } from '../stores/useStoreAllocationReportStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import { storeApi } from '@/features/store/api/store_api';
import ReportCsvExportControl from '../components/ReportCsvExportControl.vue';

const store = useStoreAllocationReportStore();
const exportStore = useReportCsvExportStore();

const stores = ref([]);

const filters = reactive({
    start_date: '',
    end_date: '',
    store_id: '',
    search: '',
});

const loadStores = async () => {
    try {
        const res = await storeApi.getAll({ is_active: 1, per_page: 500 });
        stores.value = res.data?.data?.data || res.data?.data || [];
    } catch {
        // quiet error
    }
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

const resetFilters = () => {
    filters.start_date = '';
    filters.end_date = '';
    filters.store_id = '';
    filters.search = '';
    fetchData(1);
};

const exportCsv = () => {
    const params = {
        start_date: filters.start_date || undefined,
        end_date: filters.end_date || undefined,
        store_id: filters.store_id || undefined,
        search: filters.search || undefined,
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
