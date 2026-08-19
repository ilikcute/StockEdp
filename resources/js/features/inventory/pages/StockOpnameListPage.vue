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
    <div class="mt-6">
      <StockOpnameFilters
        :locations="locations"
        @filter="onFilter"
      />
    </div>

    <!-- Error -->
    <div
      v-if="store.error"
      class="mt-4 rounded-md bg-red-50 p-4 border border-red-200"
    >
      <p class="text-sm font-medium text-red-800">
        {{ store.error }}
      </p>
    </div>

    <!-- Table -->
    <div class="mt-6 overflow-x-auto touch-scroll shadow-xs border border-gray-200 rounded-xl bg-white">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              scope="col"
              class="py-3.5 pl-4 pr-3 text-center text-sm font-semibold text-gray-900 sm:pl-6 border-b border-gray-300 w-16"
            >
              No.
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Nomor Opname
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Lokasi
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Tanggal
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Status
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Dibuat Oleh
            </th>
            <th
              scope="col"
              class="relative py-3.5 pl-3 pr-4 sm:pr-6 border-b border-gray-300"
            >
              <span class="sr-only">Aksi</span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-if="store.loadingList && (!store.opnames?.data || store.opnames.data.length === 0)">
            <td
              colspan="7"
              class="py-10 text-center text-sm text-gray-500"
            >
              Memuat data...
            </td>
          </tr>
          <tr v-else-if="!store.opnames?.data || store.opnames.data.length === 0">
            <td
              colspan="7"
              class="py-10 text-center text-sm text-gray-500"
            >
              Tidak ada data stock opname.
            </td>
          </tr>
          <tr
            v-for="(row, index) in (store.opnames?.data || [])"
            :key="row.id"
          >
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-center text-gray-500 sm:pl-6">
              {{ (store.opnames?.meta?.from ? store.opnames.meta.from + index : index + 1) }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900 font-mono">
              {{ row.opname_number }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">
              {{ row.location_name || '-' }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ row.opname_date }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm">
              <StockOpnameStatusBadge :status="row.status" />
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ row.created_by || '-' }}
            </td>
            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
              <router-link
                v-if="hasPermission('stock_opnames.view')"
                :to="`/inventory/opnames/${row.id}`"
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
