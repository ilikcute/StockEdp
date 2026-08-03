<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Penyesuaian Stok (Stock Adjustment)
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Daftar dokumen koreksi saldo stok karena barang ditemukan, rusak, kedaluwarsa, atau selisih fisik.
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <router-link
          v-if="hasPermission('stock_adjustments.create')"
          to="/inventory/adjustments/create"
          class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
        >
          Buat Adjustment Baru
        </router-link>
      </div>
    </div>

    <!-- Quick Tab Filters -->
    <div class="mt-4 border-b border-gray-200">
      <nav
        class="-mb-px flex space-x-8"
        aria-label="Tabs"
      >
        <button
          v-for="tab in tabs"
          :key="tab.value"
          type="button"
          :class="[
            activeTab === tab.value
              ? 'border-indigo-500 text-indigo-600 font-semibold'
              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
            'whitespace-nowrap border-b-2 py-4 px-1 text-sm'
          ]"
          @click="selectTab(tab.value)"
        >
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- Filters Section -->
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
      <div>
        <label
          for="search"
          class="sr-only"
        >Cari Nomor / Catatan</label>
        <input
          id="search"
          v-model="searchQuery"
          type="text"
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          placeholder="Cari Nomor / Catatan..."
        >
      </div>

      <div>
        <select
          id="directionFilter"
          v-model="directionFilter"
          aria-label="Filter Direction"
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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

    <!-- Error Alert -->
    <div
      v-if="store.error"
      class="mt-4 rounded-md bg-red-50 p-4"
    >
      <p class="text-sm font-medium text-red-800">
        {{ store.error }}
      </p>
    </div>

    <!-- Table -->
    <div class="mt-6 overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
      <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
          <tr>
            <th
              scope="col"
              class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"
            >
              Nomor Adjustment
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Tanggal
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Lokasi
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Arah
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Alasan
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Status
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Pembuat
            </th>
            <th
              scope="col"
              class="relative py-3.5 pl-3 pr-4 sm:pr-6"
            >
              <span class="sr-only">Aksi</span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-if="store.loadingList && (!store.adjustments.data || store.adjustments.data.length === 0)">
            <td
              colspan="8"
              class="py-10 text-center text-sm text-gray-500"
            >
              Memuat data penyesuaian stok...
            </td>
          </tr>
          <tr v-else-if="!store.adjustments.data || store.adjustments.data.length === 0">
            <td
              colspan="8"
              class="py-10 text-center text-sm text-gray-500"
            >
              <span v-if="hasActiveFilter">Filter tidak menemukan data adjustment.</span>
              <span v-else>Belum ada data penyesuaian stok.</span>
            </td>
          </tr>
          <tr
            v-for="item in store.adjustments.data"
            :key="item.id"
          >
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
              {{ item.adjustment_number }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.adjustment_date }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.location_name || '-' }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                :class="item.direction === 'INCREASE' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'"
              >
                {{ item.direction === 'INCREASE' ? '↑ Penambahan' : '↓ Pengurangan' }}
              </span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.reason_label || item.reason_code }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm">
              <span
                class="px-2 py-1 text-xs font-semibold rounded-full"
                :class="{
                  'bg-yellow-100 text-yellow-800': item.status === 'DRAFT',
                  'bg-green-100 text-green-800': item.status === 'POSTED',
                  'bg-gray-100 text-gray-800': item.status === 'CANCELED'
                }"
              >
                {{ item.status_label || item.status }}
              </span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.created_by || '-' }}
            </td>
            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
              <router-link
                v-if="hasPermission('stock_adjustments.view')"
                :to="`/inventory/adjustments/${item.id}`"
                class="text-indigo-600 hover:text-indigo-900"
              >
                Detail
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div
      v-if="store.adjustments.meta && store.adjustments.meta.total > 0"
      class="mt-4 flex items-center justify-between"
    >
      <p class="text-sm text-gray-700">
        Menampilkan
        <span class="font-medium">{{ (store.adjustments.meta.current_page - 1) * store.adjustments.meta.per_page + 1 }}</span>
        sampai
        <span class="font-medium">{{ Math.min(store.adjustments.meta.current_page * store.adjustments.meta.per_page, store.adjustments.meta.total) }}</span>
        dari
        <span class="font-medium">{{ store.adjustments.meta.total }}</span>
        data
      </p>
      <div class="flex gap-2">
        <button
          type="button"
          aria-label="Halaman Sebelumnya"
          class="px-3 py-1 text-sm rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-40"
          :disabled="store.adjustments.meta.current_page === 1"
          @click="changePage(store.adjustments.meta.current_page - 1)"
        >
          &laquo;
        </button>
        <button
          v-for="page in store.adjustments.meta.last_page"
          :key="page"
          type="button"
          :aria-label="`Halaman ${page}`"
          class="px-3 py-1 text-sm rounded border"
          :class="page === store.adjustments.meta.current_page ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 hover:bg-gray-50'"
          @click="changePage(page)"
        >
          {{ page }}
        </button>
        <button
          type="button"
          aria-label="Halaman Selanjutnya"
          class="px-3 py-1 text-sm rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-40"
          :disabled="store.adjustments.meta.current_page === store.adjustments.meta.last_page"
          @click="changePage(store.adjustments.meta.current_page + 1)"
        >
          &raquo;
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useStockAdjustmentStore } from '../stores/useStockAdjustmentStore';
import { useAuthStore } from '@features/auth/stores/use_auth_store';
import { locationApi } from '@features/location/api/location_api.js';

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
