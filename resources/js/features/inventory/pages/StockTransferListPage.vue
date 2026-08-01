<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Transfer Stok
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Daftar dokumen perpindahan stok antar lokasi/gudang.
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <router-link
          v-if="hasPermission('stock_transfers.create')"
          to="/inventory/transfers/create"
          class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
        >
          Buat Transfer Baru
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

    <div class="mt-6 flex flex-col sm:flex-row justify-between gap-4">
      <div class="w-full sm:max-w-xs">
        <label
          for="search"
          class="sr-only"
        >Cari Nomor Transfer</label>
        <input
          id="search"
          v-model="searchQuery"
          type="text"
          class="block w-full rounded-md border-gray-300 pl-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          placeholder="Cari Nomor Transfer..."
        >
      </div>
      <div class="flex gap-2 flex-wrap sm:flex-nowrap">
        <select
          id="statusFilter"
          v-model="statusFilter"
          aria-label="Filter Status"
          class="block rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
        >
          <option value="">
            Semua Status
          </option>
          <option value="DRAFT">
            Draft
          </option>
          <option value="SENT">
            Dikirim (In-Transit)
          </option>
          <option value="RECEIVED">
            Diterima
          </option>
          <option value="CANCELED">
            Dibatalkan
          </option>
        </select>
      </div>
    </div>

    <div
      v-if="store.error"
      class="mt-4 rounded-md bg-red-50 p-4"
    >
      <p class="text-sm font-medium text-red-800">
        {{ store.error }}
      </p>
    </div>

    <div class="mt-8 overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
      <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
          <tr>
            <th
              scope="col"
              class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"
            >
              Nomor Transfer
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
              Asal (Origin)
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Tujuan (Destination)
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
          <tr v-if="store.loadingList && (!store.transfers.data || store.transfers.data.length === 0)">
            <td
              colspan="7"
              class="py-10 text-center text-sm text-gray-500"
            >
              Memuat data transfer stok...
            </td>
          </tr>
          <tr v-else-if="!store.transfers.data || store.transfers.data.length === 0">
            <td
              colspan="7"
              class="py-10 text-center text-sm text-gray-500"
            >
              <span v-if="statusFilter || searchQuery">Filter tidak menemukan data.</span>
              <span v-else>Belum ada data transfer stok.</span>
            </td>
          </tr>
          <tr
            v-for="item in store.transfers.data"
            :key="item.id"
          >
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
              {{ item.transfer_number }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.transfer_date }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.origin_location_name || '-' }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.destination_location_name || '-' }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm">
              <span 
                class="px-2 py-1 text-xs font-semibold rounded-full"
                :class="{
                  'bg-yellow-100 text-yellow-800': item.status === 'DRAFT',
                  'bg-blue-100 text-blue-800': item.status === 'SENT',
                  'bg-green-100 text-green-800': item.status === 'RECEIVED',
                  'bg-gray-100 text-gray-800': item.status === 'CANCELED'
                }"
              >
                {{ item.status === 'SENT' ? 'Dikirim (In-Transit)' : item.status }}
              </span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.created_by || '-' }}
            </td>
            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
              <router-link
                v-if="hasPermission('stock_transfers.view')"
                :to="`/inventory/transfers/${item.id}`"
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
      v-if="store.transfers.meta && store.transfers.meta.total > 0"
      class="mt-4 flex items-center justify-between"
    >
      <p class="text-sm text-gray-700">
        Menampilkan
        <span class="font-medium">{{ (store.transfers.meta.current_page - 1) * store.transfers.meta.per_page + 1 }}</span>
        sampai
        <span class="font-medium">{{ Math.min(store.transfers.meta.current_page * store.transfers.meta.per_page, store.transfers.meta.total) }}</span>
        dari
        <span class="font-medium">{{ store.transfers.meta.total }}</span>
        data
      </p>
      <div class="flex gap-2">
        <button
          type="button"
          aria-label="Halaman Sebelumnya"
          class="px-3 py-1 text-sm rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-40"
          :disabled="store.transfers.meta.current_page === 1"
          @click="changePage(store.transfers.meta.current_page - 1)"
        >
          &laquo;
        </button>
        <button
          v-for="page in store.transfers.meta.last_page"
          :key="page"
          type="button"
          :aria-label="`Halaman ${page}`"
          class="px-3 py-1 text-sm rounded border"
          :class="page === store.transfers.meta.current_page ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 hover:bg-gray-50'"
          @click="changePage(page)"
        >
          {{ page }}
        </button>
        <button
          type="button"
          aria-label="Halaman Selanjutnya"
          class="px-3 py-1 text-sm rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-40"
          :disabled="store.transfers.meta.current_page === store.transfers.meta.last_page"
          @click="changePage(store.transfers.meta.current_page + 1)"
        >
          &raquo;
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useStockTransferStore } from '../stores/useStockTransferStore';
import { useAuthStore } from '@features/auth/stores/use_auth_store';

const store = useStockTransferStore();
const authStore = useAuthStore();

const searchQuery = ref('');
const statusFilter = ref('');
const activeTab = ref('ALL');

const tabs = [
  { name: 'Semua', value: 'ALL' },
  { name: 'Draft', value: 'DRAFT' },
  { name: 'Dikirim / In-Transit', value: 'SENT' },
  { name: 'Diterima', value: 'RECEIVED' },
  { name: 'Dibatalkan', value: 'CANCELED' },
];

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
watch(statusFilter, (newVal) => {
  activeTab.value = newVal || 'ALL';
  fetchData(1);
});

const fetchData = (page = 1) => {
  store.fetchTransfers({
    page,
    search: searchQuery.value,
    status: statusFilter.value,
  });
};

const changePage = (page) => {
  if (page >= 1 && page <= store.transfers.meta.last_page) {
    fetchData(page);
  }
};

const hasPermission = (permission) => {
  return authStore.hasPermission(permission);
};

onMounted(() => {
  fetchData();
});
</script>
