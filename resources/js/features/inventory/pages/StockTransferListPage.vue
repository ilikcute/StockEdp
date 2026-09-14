<template>
  <div class="space-y-3">
    <!-- Top Header & Filter Toolbar (Compact) -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-base font-bold text-gray-900 leading-tight flex items-center gap-1.5">
          <svg
            class="w-4 h-4 text-blue-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
            />
          </svg>
          Daftar Transfer Stok Antar Gudang
        </h1>
        <p class="text-[11px] text-gray-500 mt-0.5">
          Pencatatan perpindahan fisik stok barang antar lokasi dan penerimaan transfer.
        </p>
      </div>

      <!-- Actions, Filter & Search (Unified in Top Header) -->
      <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
        <!-- Search Input -->
        <div class="w-full sm:w-56">
          <input
            id="search"
            v-model="searchQuery"
            type="text"
            placeholder="Cari Nomor Transfer..."
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 px-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            @input="handleSearch"
          >
        </div>

        <!-- Status Filter -->
        <select
          v-model="statusFilter"
          class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          @change="fetchData(1)"
        >
          <option value="">
            Semua Status
          </option>
          <option value="DRAFT">
            Draft
          </option>
          <option value="IN_TRANSIT">
            Dikirim / In-Transit
          </option>
          <option value="RECEIVED">
            Diterima
          </option>
          <option value="CANCELED">
            Dibatalkan
          </option>
        </select>

        <router-link
          v-if="hasPermission('stock_transfers.create')"
          to="/inventory/transfers/create"
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
          <span>Buat Transfer Baru</span>
        </router-link>
      </div>
    </div>

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

    <!-- High-Density Table Container -->
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
              class="py-1.5 px-2 whitespace-nowrap min-w-[150px]"
            >
              Nomor Transfer
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Tanggal
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 min-w-[150px]"
            >
              Asal (Origin)
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 min-w-[150px]"
            >
              Tujuan (Destination)
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap w-24"
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
              class="py-1.5 px-2 text-center whitespace-nowrap w-16"
            >
              Aksi
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="store.loadingList && (!store.transfers.data || store.transfers.data.length === 0)">
            <td
              colspan="8"
              class="py-8 text-center text-xs text-gray-500"
            >
              <div class="flex items-center justify-center gap-2">
                <svg
                  class="animate-spin h-4 w-4 text-indigo-600"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  />
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8v8H4z"
                  />
                </svg>
                <span>Memuat data transfer stok...</span>
              </div>
            </td>
          </tr>
          <tr v-else-if="!store.transfers.data || store.transfers.data.length === 0">
            <td
              colspan="8"
              class="py-8 text-center text-xs text-gray-400"
            >
              <span v-if="statusFilter || searchQuery">Filter tidak menemukan data.</span>
              <span v-else>Belum ada data transfer stok.</span>
            </td>
          </tr>
          <tr
            v-for="(item, index) in (store.transfers.data || [])"
            :key="item.id"
            class="hover:bg-gray-50/80 transition-colors"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(store.transfers?.meta, index) }}
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap font-mono font-bold text-gray-900 text-[11px]">
              {{ item.transfer_number }}
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap text-gray-600 text-[11px]">
              {{ item.transfer_date }}
            </td>
            <td class="py-1.5 px-2 text-gray-800 text-[11px]">
              {{ item.origin_location_name || '-' }}
            </td>
            <td class="py-1.5 px-2 text-gray-800 text-[11px]">
              {{ item.destination_location_name || '-' }}
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap">
              <span 
                class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider"
                :class="{
                  'bg-yellow-100 text-yellow-800': item.status === 'DRAFT',
                  'bg-blue-100 text-blue-800': item.status === 'IN_TRANSIT',
                  'bg-emerald-100 text-emerald-800': item.status === 'RECEIVED',
                  'bg-rose-100 text-rose-800': item.status === 'DISCREPANCY',
                  'bg-gray-100 text-gray-800': item.status === 'CANCELED'
                }"
              >
                {{ ({ DRAFT: 'Draft', 'IN_TRANSIT': 'In-Transit', RECEIVED: 'Diterima', DISCREPANCY: 'Selisih', CANCELED: 'Dibatalkan' })[item.status] || item.status }}
              </span>
            </td>
            <td class="py-1.5 px-2 text-gray-500 whitespace-nowrap text-[11px]">
              {{ item.created_by || '-' }}
            </td>
            <td class="py-1.5 px-2 text-center whitespace-nowrap">
              <router-link
                v-if="hasPermission('stock_transfers.view')"
                :to="`/inventory/transfers/${item.id}`"
                class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-900 hover:underline cursor-pointer"
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
      :pagination="store.transfers.meta"
      :loading="store.isLoading"
      @change="changePage"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useStockTransferStore } from '../stores/useStockTransferStore';
import { useAuthStore } from '@features/auth/stores/use_auth_store';
import { rowNumber } from '@/shared/utils/formatters';
import BasePagination from '@/shared/components/BasePagination.vue';

const store = useStockTransferStore();
const authStore = useAuthStore();

const searchQuery = ref('');
const statusFilter = ref('');

let debounceTimer = null;
const handleSearch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => fetchData(1), 300);
};

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
