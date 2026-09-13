<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Stock Opname
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Daftar sesi penghitungan stok fisik (Stock Opname).
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <router-link
          v-if="hasPermission('stock_opnames.create')"
          to="/inventory/opnames/create"
          class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
        >
          Buat Sesi Baru
        </router-link>
      </div>
    </div>

    <!-- Filters -->
    <div class="mt-3">
      <StockOpnameFilters
        :locations="locations"
        @filter="onFilter"
      />
    </div>

    <!-- Error -->
    <div
      v-if="store.error"
      class="mt-3 rounded-md bg-red-50 p-3 border border-red-200"
    >
      <p class="text-xs font-medium text-red-800">
        {{ store.error }}
      </p>
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
import StockOpnameFilters from '../components/StockOpnameFilters.vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import apiClient from '@/shared/api/api_client';

const store = useStockOpnameStore();
const authStore = useAuthStore();

const locations = ref([]);
const currentPage = ref(1);
const activeFilters = ref({});

function hasPermission(permission) {
    return authStore.hasPermission(permission);
}

async function fetchData(page = 1) {
    currentPage.value = page;
    await store.fetchOpnames({ page, ...activeFilters.value });
}

function onFilter(params) {
    activeFilters.value = params;
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
