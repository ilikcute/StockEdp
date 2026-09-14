<template>
  <div class="space-y-3">
    <!-- Top Header Toolbar (Compact) -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
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
              d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
            />
          </svg>
          Penyesuaian Stok (Stock Adjustment)
        </h1>
        <p class="text-[11px] text-gray-500 mt-0.5">
          Daftar dokumen koreksi saldo stok karena barang ditemukan, rusak, kedaluwarsa, atau selisih fisik.
        </p>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2">
        <router-link
          v-if="hasPermission('stock_adjustments.create')"
          to="/inventory/adjustments/create"
          class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 transition-colors cursor-pointer whitespace-nowrap"
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
              d="M12 4v16m8-8H4"
            />
          </svg>
          <span>Buat Adjustment Baru</span>
        </router-link>
      </div>
    </div>

    <!-- Quick Tab Filters & Filter Bar (Compact) -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs space-y-2.5">
      <!-- Quick Tab Filters -->
      <div class="border-b border-gray-100 pb-1.5">
        <nav
          class="-mb-px flex space-x-4"
          aria-label="Tabs"
        >
          <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            :class="[
              activeTab === tab.value
                ? 'border-indigo-600 text-indigo-600 font-bold'
                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 font-medium',
              'whitespace-nowrap border-b-2 pb-1 px-1 text-xs cursor-pointer transition-colors'
            ]"
            @click="selectTab(tab.value)"
          >
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <!-- Filters Section -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2">
        <div>
          <label
            for="search"
            class="sr-only"
          >Cari Nomor / Catatan</label>
          <input
            id="search"
            v-model="searchQuery"
            type="text"
            class="block w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            placeholder="Cari Nomor / Catatan..."
          >
        </div>

        <div>
          <select
            id="directionFilter"
            v-model="directionFilter"
            aria-label="Filter Direction"
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="">
              Semua Arah (Direction)
            </option>
            <option value="INCREASE">
              Penambahan Stok (INCREASE)
            </option>
            <option value="DECREASE">
              Pengurangan Stok (DECREASE)
            </option>
          </select>
        </div>

        <div>
          <select
            id="reasonFilter"
            v-model="reasonFilter"
            aria-label="Filter Alasan"
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="">
              Semua Alasan (Reason)
            </option>
            <option value="FOUND">
              Barang ditemukan
            </option>
            <option value="DAMAGED">
              Barang rusak
            </option>
            <option value="EXPIRED">
              Barang kedaluwarsa
            </option>
            <option value="LOST">
              Kehilangan barang
            </option>
            <option value="RECORDING_ERROR">
              Kesalahan pencatatan
            </option>
            <option value="ADMINISTRATIVE">
              Koreksi administratif
            </option>
            <option value="OTHER">
              Lain-lain
            </option>
          </select>
        </div>

        <div>
          <select
            id="locationFilter"
            v-model="locationFilter"
            aria-label="Filter Lokasi"
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="">
              Semua Lokasi
            </option>
            <option
              v-for="loc in locations"
              :key="loc.id"
              :value="loc.id"
            >
              {{ loc.name }}
            </option>
          </select>
        </div>

        <div>
          <select
            id="statusFilter"
            v-model="statusFilter"
            aria-label="Filter Status"
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            <option value="">
              Semua Status
            </option>
            <option value="DRAFT">
              Draft
            </option>
            <option value="POSTED">
              Diposting
            </option>
            <option value="CANCELED">
              Dibatalkan
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Error Alert -->
    <div
      v-if="store.error"
      class="rounded-lg bg-rose-50 p-2.5 border border-rose-200 text-xs text-rose-800 flex items-center justify-between shadow-2xs"
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

    <!-- Table -->
    <div class="overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar">
      <table class="w-full text-left text-xs border-collapse">
        <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10">
          <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
            <th
              scope="col"
              class="py-1.5 px-1.5 w-8 text-center"
            >
              No.
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Nomor Adjustment
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Tanggal
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Lokasi
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Arah
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Alasan
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Status
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Pembuat
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap"
            >
              Aksi
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="store.loadingList && (!store.adjustments.data || store.adjustments.data.length === 0)">
            <td
              colspan="9"
              class="py-8 text-center text-xs text-gray-500"
            >
              Memuat data penyesuaian stok...
            </td>
          </tr>
          <tr v-else-if="!store.adjustments.data || store.adjustments.data.length === 0">
            <td
              colspan="9"
              class="py-8 text-center text-xs text-gray-500"
            >
              <span v-if="hasActiveFilter">Filter tidak menemukan data adjustment.</span>
              <span v-else>Belum ada data penyesuaian stok.</span>
            </td>
          </tr>
          <tr
            v-for="(item, index) in (store.adjustments.data || [])"
            :key="item.id"
            class="hover:bg-gray-50/80 transition-colors"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(store.adjustments?.meta, index) }}
            </td>
            <td class="py-1.5 px-2 text-[11px] font-medium text-gray-900 whitespace-nowrap">
              {{ item.adjustment_number }}
            </td>
            <td class="py-1.5 px-2 text-[11px] text-gray-500 whitespace-nowrap">
              {{ item.adjustment_date }}
            </td>
            <td class="py-1.5 px-2 text-[11px] text-gray-500 whitespace-nowrap">
              {{ item.location_name || '-' }}
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <span
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider"
                :class="item.direction === 'INCREASE' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-orange-50 text-orange-700 border border-orange-200'"
              >
                {{ item.direction === 'INCREASE' ? '↑ Penambahan' : '↓ Pengurangan' }}
              </span>
            </td>
            <td class="py-1.5 px-2 text-[11px] text-gray-500 whitespace-nowrap">
              {{ item.reason_label || item.reason_code }}
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <span
                class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider inline-flex items-center"
                :class="{
                  'bg-amber-50 text-amber-700 border border-amber-200': item.status === 'DRAFT',
                  'bg-emerald-50 text-emerald-700 border border-emerald-200': item.status === 'POSTED',
                  'bg-gray-100 text-gray-600 border border-gray-200': item.status === 'CANCELED'
                }"
              >
                {{ item.status_label || item.status }}
              </span>
            </td>
            <td class="py-1.5 px-2 text-[11px] text-gray-500 whitespace-nowrap">
              {{ item.created_by || '-' }}
            </td>
            <td class="py-1.5 px-2 text-right whitespace-nowrap text-[11px]">
              <router-link
                v-if="hasPermission('stock_adjustments.view')"
                :to="`/inventory/adjustments/${item.id}`"
                class="font-semibold text-indigo-600 hover:text-indigo-900 hover:underline"
              >
                Detail
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <BasePagination
      :pagination="store.adjustments.meta"
      :loading="store.isLoading"
      @change="changePage"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useStockAdjustmentStore } from '../stores/useStockAdjustmentStore';
import { useAuthStore } from '@features/auth/stores/use_auth_store';
import { locationApi } from '@features/location/api/location_api.js';
import { rowNumber } from '@/shared/utils/formatters';
import BasePagination from '@/shared/components/BasePagination.vue';

const store = useStockAdjustmentStore();
const authStore = useAuthStore();

const searchQuery = ref('');
const statusFilter = ref('');
const directionFilter = ref('');
const reasonFilter = ref('');
const locationFilter = ref('');
const activeTab = ref('ALL');

const locations = ref([]);

const tabs = [
  { name: 'Semua', value: 'ALL' },
  { name: 'Draft', value: 'DRAFT' },
  { name: 'Diposting', value: 'POSTED' },
  { name: 'Dibatalkan', value: 'CANCELED' },
];

const hasActiveFilter = computed(() => {
  return searchQuery.value || statusFilter.value || directionFilter.value || reasonFilter.value || locationFilter.value;
});

const selectTab = (tabValue) => {
  activeTab.value = tabValue;
  statusFilter.value = tabValue === 'ALL' ? '' : tabValue;
};

let debounceTimer = null;
const debouncedSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => fetchData(1), 400);
};

watch(searchQuery, debouncedSearch);
watch([statusFilter, directionFilter, reasonFilter, locationFilter], () => {
  if (statusFilter.value !== activeTab.value && activeTab.value !== 'ALL') {
    activeTab.value = statusFilter.value || 'ALL';
  }
  fetchData(1);
});

const fetchData = (page = 1) => {
  store.fetchAdjustments({
    page,
    search: searchQuery.value,
    status: statusFilter.value,
    direction: directionFilter.value,
    reason_code: reasonFilter.value,
    location_id: locationFilter.value,
  });
};

const changePage = (page) => {
  if (page >= 1 && page <= store.adjustments.meta.last_page) {
    fetchData(page);
  }
};

const hasPermission = (permission) => {
  return authStore.hasPermission(permission);
};

onMounted(async () => {
  try {
    const locRes = await locationApi.getAll({ is_active: true, per_page: 100 });
    locations.value = locRes.data.data.data || locRes.data.data || [];
  } catch {
    // Ignore error loading location master for filter
  }
  fetchData();
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
