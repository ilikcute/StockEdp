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
              Nomor Opname
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
              Tanggal
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
              Dibuat Oleh
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
          <tr v-if="store.loadingList && store.opnames.data.length === 0">
            <td
              colspan="6"
              class="py-10 text-center text-sm text-gray-500"
            >
              Memuat data...
            </td>
          </tr>
          <tr v-else-if="store.opnames.data.length === 0">
            <td
              colspan="6"
              class="py-10 text-center text-sm text-gray-500"
            >
              Tidak ada data stock opname.
            </td>
          </tr>
          <tr
            v-for="row in store.opnames.data"
            :key="row.id"
          >
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 font-mono">
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
    <div
      v-if="store.opnames.meta && store.opnames.meta.total > 0"
      class="mt-4 flex items-center justify-between"
    >
      <p class="text-sm text-gray-700">
        Menampilkan
        <span class="font-medium">{{ pageStart }}</span>
        sampai
        <span class="font-medium">{{ pageEnd }}</span>
        dari
        <span class="font-medium">{{ store.opnames.meta.total }}</span>
        data
      </p>
      <div class="flex gap-2">
        <button
          class="px-3 py-1 text-sm rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-40"
          :disabled="currentPage === 1"
          @click="changePage(currentPage - 1)"
        >
          &laquo;
        </button>
        <button
          v-for="page in pageNumbers"
          :key="page"
          class="px-3 py-1 text-sm rounded border"
          :class="page === currentPage ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 hover:bg-gray-50'"
          @click="changePage(page)"
        >
          {{ page }}
        </button>
        <button
          class="px-3 py-1 text-sm rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-40"
          :disabled="currentPage === store.opnames.meta.last_page"
          @click="changePage(currentPage + 1)"
        >
          &raquo;
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useStockOpnameStore } from '../stores/useStockOpnameStore';
import { useAuthStore } from '@features/auth/stores/use_auth_store';
import StockOpnameStatusBadge from '../components/StockOpnameStatusBadge.vue';
import StockOpnameFilters from '../components/StockOpnameFilters.vue';
import apiClient from '@/shared/api/api_client';

const store = useStockOpnameStore();
const authStore = useAuthStore();

const locations = ref([]);
const currentPage = ref(1);
const activeFilters = ref({});

const pageStart = computed(() => {
    const meta = store.opnames.meta;
    if (!meta?.per_page) return 1;
    return (meta.current_page - 1) * meta.per_page + 1;
});

const pageEnd = computed(() => {
    const meta = store.opnames.meta;
    if (!meta?.per_page) return 0;
    return Math.min(meta.current_page * meta.per_page, meta.total);
});

const pageNumbers = computed(() => {
    const last = store.opnames.meta?.last_page ?? 1;
    return Array.from({ length: last }, (_, i) => i + 1);
});

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
