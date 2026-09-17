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
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
            />
          </svg>
          Alokasi Unit Toko
        </h1>
        <p class="text-[11px] text-gray-500 mt-0.5">
          Pencatatan alokasi unit bagus (GOOD) ke toko dan penarikan unit rusak (DEFECTIVE) oleh teknisi lapangan.
        </p>
      </div>

      <!-- Actions, Filter & Search (Unified in Top Header) -->
      <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
        <!-- Search Input -->
        <div class="w-full sm:w-56">
          <input
            id="search-allocations"
            v-model="searchQuery"
            type="text"
            placeholder="Cari No. Alokasi / Toko..."
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 px-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            @input="handleSearch"
          >
        </div>

        <!-- Store Filter -->
        <select
          v-model="selectedStoreId"
          class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          @change="loadData(1)"
        >
          <option value="">
            Semua Toko
          </option>
          <option
            v-for="storeOption in stores"
            :key="storeOption.id"
            :value="storeOption.id"
          >
            {{ storeOption.name }}
          </option>
        </select>

        <!-- Add Allocation Button -->
        <router-link
          v-if="hasPermission('store_allocations.create_own|store_allocations.create_for_others')"
          to="/inventory/store-allocations/create"
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
          <span>Catat Alokasi Baru</span>
        </router-link>
      </div>
    </div>

    <!-- Alert / Error -->
    <div
      v-if="store.error"
      class="rounded-lg bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800 flex items-center justify-between"
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
              class="py-12 text-center text-xs text-gray-400"
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
                <span>Memuat data alokasi...</span>
              </div>
            </td>
          </tr>
          <BaseTableEmpty
            v-else-if="!store.allocations?.data || store.allocations.data.length === 0"
            :colspan="8"
            message="Tidak ada data alokasi toko yang cocok."
            :hint="hasActiveFilters ? 'Silakan sesuaikan filter pencarian Anda.' : ''"
          />
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
    <BasePagination
      :pagination="store.allocations?.meta"
      :loading="store.loading"
      @change="changePage"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useStoreAllocationStore } from '../stores/useStoreAllocationStore';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import { storeApi } from '@/features/store/api/store_api';
import BasePagination from '@/shared/components/BasePagination.vue';
import BaseTableEmpty from '@/shared/components/BaseTableEmpty.vue';

const store = useStoreAllocationStore();
const authStore = useAuthStore();

const searchQuery = ref('');
const selectedStoreId = ref('');
const stores = ref([]);
let searchTimer = null;

const hasActiveFilters = computed(() => {
    return Boolean(searchQuery.value || selectedStoreId.value);
});

const hasPermission = (perm) => authStore.hasPermission(perm);

const loadData = (page = 1) => {
    store.fetchAllocations({
        page,
        search: searchQuery.value || undefined,
        store_id: selectedStoreId.value || undefined,
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

onMounted(async () => {
    try {
        const res = await storeApi.getAll({ is_active: 1, per_page: 1000 });
        stores.value = res.data?.data || res.data || [];
    } catch {
        // ignore
    }
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
