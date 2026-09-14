<template>
  <div class="space-y-3">
    <!-- Top Header & Filter Toolbar (Compact) -->
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
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
            />
          </svg>
          Stock Opname
        </h1>
        <p class="text-[11px] text-gray-500 mt-0.5">
          Daftar sesi penghitungan fisik persediaan barang dan rekonsiliasi selisih stok.
        </p>
      </div>

      <!-- Actions, Filter & Search (Unified in Top Header) -->
      <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
        <!-- Search Input -->
        <div class="w-full sm:w-48">
          <input
            id="search-opnames"
            v-model="searchQuery"
            type="text"
            placeholder="Cari No. Opname..."
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
          <option value="IN_PROGRESS">
            Sedang Dihitung
          </option>
          <option value="COUNTED">
            Menunggu Rekonsiliasi
          </option>
          <option value="POSTED">
            Diposting
          </option>
          <option value="CANCELED">
            Dibatalkan
          </option>
        </select>

        <!-- Location Filter -->
        <select
          v-model="locationFilter"
          class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          @change="fetchData(1)"
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

        <!-- Reset Button if filtered -->
        <button
          v-if="searchQuery || statusFilter || locationFilter"
          type="button"
          class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 shadow-2xs cursor-pointer"
          title="Reset Filter"
          @click="resetFilters"
        >
          Reset
        </button>

        <!-- Buat Sesi Baru Button -->
        <router-link
          v-if="hasPermission('stock_opnames.create')"
          to="/inventory/opnames/create"
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
          <span>Buat Sesi Baru</span>
        </router-link>
      </div>
    </div>

    <!-- Alert / Error -->
    <div
      v-if="store.error"
      class="rounded-lg bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800 flex items-center justify-between shadow-2xs"
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
    <div class="mt-4 overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar">
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
              Nomor Opname
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
              Tanggal
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
              Dibuat Oleh
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
          <tr v-if="store.loadingList && (!store.opnames?.data || store.opnames.data.length === 0)">
            <td
              colspan="7"
              class="py-8 text-center text-xs text-gray-500"
            >
              Memuat data...
            </td>
          </tr>
          <tr v-else-if="!store.opnames?.data || store.opnames.data.length === 0">
            <td
              colspan="7"
              class="py-8 text-center text-xs text-gray-500"
            >
              Tidak ada data stock opname.
            </td>
          </tr>
          <tr
            v-for="(row, index) in (store.opnames?.data || [])"
            :key="row.id"
            class="hover:bg-gray-50/80 transition-colors"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(store.opnames?.meta, index) }}
            </td>
            <td class="py-1.5 px-2 font-mono font-medium text-gray-900 text-[11px] whitespace-nowrap">
              {{ row.opname_number }}
            </td>
            <td class="py-1.5 px-2 text-gray-700 text-[11px] whitespace-nowrap">
              {{ row.location_name || '-' }}
            </td>
            <td class="py-1.5 px-2 text-gray-500 text-[11px] whitespace-nowrap">
              {{ row.opname_date }}
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <StockOpnameStatusBadge :status="row.status" />
            </td>
            <td class="py-1.5 px-2 text-gray-500 text-[11px] whitespace-nowrap">
              {{ row.created_by || '-' }}
            </td>
            <td class="py-1.5 px-2 text-right whitespace-nowrap text-[11px]">
              <router-link
                v-if="hasPermission('stock_opnames.view')"
                :to="`/inventory/opnames/${row.id}`"
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
      :pagination="store.opnames.meta"
      :loading="store.isLoading"
      @change="changePage"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useStockOpnameStore } from '../stores/useStockOpnameStore';
import { useAuthStore } from '@features/auth/stores/use_auth_store';
import { rowNumber } from '@/shared/utils/formatters';
import StockOpnameStatusBadge from '../components/StockOpnameStatusBadge.vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import apiClient from '@/shared/api/api_client';

const store = useStockOpnameStore();
const authStore = useAuthStore();

const locations = ref([]);
const currentPage = ref(1);
const searchQuery = ref('');
const statusFilter = ref('');
const locationFilter = ref('');
let searchTimer = null;

function hasPermission(permission) {
    return authStore.hasPermission(permission);
}

async function fetchData(page = 1) {
    currentPage.value = page;
    await store.fetchOpnames({
        page,
        search: searchQuery.value || undefined,
        status: statusFilter.value || undefined,
        location_id: locationFilter.value || undefined,
    });
}

function handleSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        fetchData(1);
    }, 300);
}

function resetFilters() {
    searchQuery.value = '';
    statusFilter.value = '';
    locationFilter.value = '';
    fetchData(1);
}

function changePage(page) {
    const last = store.opnames.meta?.last_page ?? 1;
    if (page >= 1 && page <= last) {
        fetchData(page);
    }
}

async function loadLocations() {
    try {
        const res = await apiClient.get('/locations', { params: { is_active: 1, per_page: 200 } });
        locations.value = res.data.data.data ?? res.data.data;
    } catch {
        // Non-critical; filters will just be locationless
    }
}

onMounted(async () => {
    await Promise.all([fetchData(), loadLocations()]);
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
