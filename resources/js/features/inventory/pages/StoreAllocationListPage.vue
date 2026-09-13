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
    <div class="mt-3 flex flex-col sm:flex-row gap-2.5">
      <div class="flex-1">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari nomor alokasi, toko, atau teknisi..."
          class="block w-full rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          @input="handleSearch"
        >
      </div>
    </div>

    <!-- Alert / Error -->
    <div
      v-if="store.error"
      class="mt-3 rounded-lg bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800"
    >
      {{ store.error }}
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
              No. Alokasi
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
              Toko
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Teknisi / Lokasi
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Item Dipasang
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Item Ditarik
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
          <tr v-if="store.loading && (!store.allocations?.data || store.allocations.data.length === 0)">
            <td
              colspan="8"
              class="py-8 text-center text-xs text-gray-500"
            >
              Memuat data alokasi...
            </td>
          </tr>
          <tr v-else-if="!store.allocations?.data || store.allocations.data.length === 0">
            <td
              colspan="8"
              class="py-8 text-center text-xs text-gray-400"
            >
              Belum ada riwayat alokasi toko.
            </td>
          </tr>
          <tr
            v-for="(item, index) in (store.allocations?.data || [])"
            :key="item.id"
            class="hover:bg-gray-50/80 transition-colors"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(store.allocations?.meta, index) }}
            </td>
            <td class="py-1.5 px-2 font-mono font-medium text-gray-900 text-[11px] whitespace-nowrap">
              {{ item.allocation_number }}
            </td>
            <td class="py-1.5 px-2 text-gray-600 text-[11px] whitespace-nowrap">
              {{ item.allocated_at }}
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <div class="font-medium text-gray-900 leading-tight">
                {{ item.store_name }}
              </div>
              <div
                v-if="item.store_code"
                class="text-[10px] text-gray-400 font-mono"
              >
                {{ item.store_code }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <div class="font-medium text-gray-900 leading-tight">
                {{ item.technician_name }}
              </div>
              <div class="text-[10px] text-gray-400">
                {{ item.technician_location_name }}
              </div>
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap">
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                ✓ {{ countInstalled(item) }} Unit
              </span>
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap">
              <span
                v-if="countPulled(item) > 0"
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200"
              >
                ⚠ {{ countPulled(item) }} Unit
              </span>
              <span
                v-else
                class="text-[11px] text-gray-400"
              >-</span>
            </td>
            <td class="py-1.5 px-2 text-right whitespace-nowrap text-[11px]">
              <router-link
                :to="`/inventory/store-allocations/${item.id}`"
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
