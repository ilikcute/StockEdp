<template>
  <div class="px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="sm:flex sm:items-center justify-between">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Alokasi Unit Toko
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Pencatatan alokasi unit bagus (GOOD) ke toko dan penarikan unit rusak (DEFECTIVE) oleh teknisi lapangan.
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:flex-none">
        <router-link
          v-if="hasPermission('store_allocations.create')"
          to="/inventory/store-allocations/create"
          class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 cursor-pointer"
        >
          <span>+ Catat Alokasi Baru</span>
        </router-link>
      </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="flex flex-col sm:flex-row gap-3">
      <div class="flex-1">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari nomor alokasi, toko, atau teknisi..."
          class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
          @input="handleSearch"
        >
      </div>
    </div>

    <!-- Alert / Error -->
    <div
      v-if="store.error"
      class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800"
    >
      {{ store.error }}
    </div>

    <!-- Table -->
    <div class="overflow-x-auto shadow-xs border border-gray-200 rounded-xl bg-white">
      <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
        <thead class="bg-gray-50 text-gray-700 font-semibold">
          <tr>
            <th class="py-3 px-4 w-12 text-center">
              No.
            </th>
            <th class="py-3 px-4">
              No. Alokasi
            </th>
            <th class="py-3 px-4">
              Tanggal
            </th>
            <th class="py-3 px-4">
              Toko
            </th>
            <th class="py-3 px-4">
              Teknisi / Lokasi
            </th>
            <th class="py-3 px-4">
              Item Dipasang
            </th>
            <th class="py-3 px-4">
              Item Ditarik
            </th>
            <th class="py-3 px-4 text-center w-24">
              Aksi
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="store.loading && (!store.allocations?.data || store.allocations.data.length === 0)">
            <td
              colspan="8"
              class="py-10 text-center text-gray-500"
            >
              Memuat data alokasi...
            </td>
          </tr>
          <tr v-else-if="!store.allocations?.data || store.allocations.data.length === 0">
            <td
              colspan="8"
              class="py-10 text-center text-gray-400"
            >
              Belum ada riwayat alokasi toko.
            </td>
          </tr>
          <tr
            v-for="(item, index) in (store.allocations?.data || [])"
            :key="item.id"
            class="hover:bg-gray-50"
          >
            <td class="py-3 px-4 text-center text-gray-400 font-mono text-xs">
              {{ rowNumber(store.allocations?.meta, index) }}
            </td>
            <td class="py-3 px-4 font-mono font-medium text-gray-900">
              {{ item.allocation_number }}
            </td>
            <td class="py-3 px-4 text-gray-600 whitespace-nowrap">
              {{ item.allocated_at }}
            </td>
            <td class="py-3 px-4">
              <div class="font-medium text-gray-900">
                {{ item.store_name }}
              </div>
              <div
                v-if="item.store_code"
                class="text-xs text-gray-500 font-mono"
              >
                {{ item.store_code }}
              </div>
            </td>
            <td class="py-3 px-4">
              <div class="font-medium text-gray-900">
                {{ item.technician_name }}
              </div>
              <div class="text-xs text-gray-500">
                {{ item.technician_location_name }}
              </div>
            </td>
            <td class="py-3 px-4">
              <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                ✓ {{ countInstalled(item) }} Unit
              </span>
            </td>
            <td class="py-3 px-4">
              <span
                v-if="countPulled(item) > 0"
                class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20"
              >
                ⚠ {{ countPulled(item) }} Unit
              </span>
              <span
                v-else
                class="text-xs text-gray-400"
              >-</span>
            </td>
            <td class="py-3 px-4 text-center whitespace-nowrap">
              <router-link
                :to="`/inventory/store-allocations/${item.id}`"
                class="text-indigo-600 hover:text-indigo-900 font-semibold text-xs"
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
      v-if="store.allocations?.meta?.last_page > 1"
      class="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6"
    >
      <div class="text-sm text-gray-700">
        Menampilkan halaman {{ store.allocations.meta.current_page }} dari {{ store.allocations.meta.last_page }}
      </div>
      <div class="flex gap-2">
        <button
          :disabled="store.allocations.meta.current_page <= 1"
          class="rounded border border-gray-300 px-3 py-1 text-sm disabled:opacity-50"
          @click="changePage(store.allocations.meta.current_page - 1)"
        >
          Sebelumnya
        </button>
        <button
          :disabled="store.allocations.meta.current_page >= store.allocations.meta.last_page"
          class="rounded border border-gray-300 px-3 py-1 text-sm disabled:opacity-50"
          @click="changePage(store.allocations.meta.current_page + 1)"
        >
          Selanjutnya
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useStoreAllocationStore } from '../stores/useStoreAllocationStore';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';

const store = useStoreAllocationStore();
const authStore = useAuthStore();

const searchQuery = ref('');
let searchTimer = null;

const hasPermission = (perm) => authStore.hasPermission(perm);

const loadData = (page = 1) => {
    store.fetchAllocations({
        page,
        search: searchQuery.value || undefined,
    });
};

const handleSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        loadData(1);
    }, 300);
};

const changePage = (page) => {
    loadData(page);
};

const rowNumber = (meta, index) => {
    if (!meta) return index + 1;
    return (meta.current_page - 1) * meta.per_page + index + 1;
};

const countInstalled = (allocation) => {
    if (!allocation.items) return 0;
    return allocation.items.reduce((sum, i) => sum + Number(i.quantity || 0), 0);
};

const countPulled = (allocation) => {
    if (!allocation.items) return 0;
    return allocation.items.reduce((sum, i) => sum + Number(i.pulled_quantity || 0), 0);
};

onMounted(() => {
    loadData();
});
</script>
