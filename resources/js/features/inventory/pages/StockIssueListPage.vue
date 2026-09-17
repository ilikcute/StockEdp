<template>
  <div class="space-y-3">
    <!-- Top Header & Filter Toolbar (Compact) -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-base font-bold text-gray-900 leading-tight flex items-center gap-1.5">
          <svg
            class="w-4 h-4 text-amber-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
            />
          </svg>
          Daftar Pengeluaran Stok (Goods Issues)
        </h1>
        <p class="text-[11px] text-gray-500 mt-0.5">
          Pencatatan pengeluaran barang untuk kebutuhan operasional, pemakaian internal, atau produksi.
        </p>
      </div>

      <!-- Actions, Filter & Search (Unified in Top Header) -->
      <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
        <!-- Search Input -->
        <div class="w-full sm:w-56">
          <input
            id="search-issues"
            v-model="searchQuery"
            type="text"
            placeholder="Cari Nomor Referensi..."
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 px-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            @input="handleSearch"
          >
        </div>

        <select
          v-model="statusFilter"
          class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option value="">
            Semua Status
          </option>
          <option value="DRAFT">
            Draft
          </option>
          <option value="POSTED">
            Posted
          </option>
          <option value="CANCELED">
            Canceled
          </option>
        </select>

        <!-- Department Filter -->
        <select
          v-model="departmentFilter"
          class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option value="">
            Semua Departemen
          </option>
          <option
            v-for="dept in departments"
            :key="dept.id"
            :value="dept.id"
          >
            {{ dept.code }} - {{ dept.name }}
          </option>
        </select>

        <router-link
          v-if="hasPermission('stock_issues.create')"
          to="/inventory/issues/create"
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
          <span>Buat Draft Baru</span>
        </router-link>
      </div>
    </div>

    <BaseAlert
      v-if="store.error"
      :message="store.error"
    />

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
              class="py-1.5 px-2 whitespace-nowrap min-w-[160px]"
            >
              Nomor Dokumen
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Tanggal
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap min-w-[150px]"
            >
              Departemen Tujuan
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 min-w-[180px]"
            >
              Tujuan / Keperluan
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap w-24"
            >
              Status
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
          <tr v-if="store.loading && (!store.issues?.data || store.issues.data.length === 0)">
            <td
              colspan="7"
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
                <span>Memuat data pengeluaran...</span>
              </div>
            </td>
          </tr>
          <BaseTableEmpty
            v-else-if="!store.issues?.data || store.issues.data.length === 0"
            :colspan="7"
            message="Tidak ada data pengeluaran yang cocok."
            :hint="hasActiveFilters ? 'Silakan sesuaikan filter pencarian Anda.' : ''"
          />
          <tr
            v-for="(item, index) in (store.issues?.data || [])"
            :key="item.id"
            class="hover:bg-gray-50/80 transition-colors"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(store.issues?.meta, index) }}
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap font-mono font-bold text-gray-900 text-[11px]">
              {{ item.issue_number }}
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap text-gray-600 text-[11px]">
              {{ item.date }}
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap">
              <span
                v-if="item.department"
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-teal-50 text-teal-700 border border-teal-200"
              >
                {{ item.department.code }} - {{ item.department.name }}
              </span>
              <span
                v-else
                class="text-gray-400 text-[11px]"
              >-</span>
            </td>
            <td class="py-1.5 px-2 text-gray-800 text-[11px]">
              {{ item.purpose }}
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap">
              <DocumentStatusBadge :status="item.status" />
            </td>
            <td class="py-1.5 px-2 text-center whitespace-nowrap">
              <router-link
                v-if="hasPermission('stock_issues.view')"
                :to="`/inventory/issues/${item.id}`"
                class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-900 hover:underline cursor-pointer"
              >
                Detail
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <BasePagination
      :pagination="store.issues?.meta"
      :loading="store.loading"
      @change="changePage"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useStockIssueStore } from '../stores/useStockIssueStore';
import { useDocumentList } from '../composables/use_document_list';
import { departmentApi } from '@/features/department/api/department_api.js';
import { rowNumber } from '@/shared/utils/formatters';
import BasePagination from '@/shared/components/BasePagination.vue';
import BaseAlert from '@/shared/components/BaseAlert.vue';
import BaseTableEmpty from '@/shared/components/BaseTableEmpty.vue';
import DocumentStatusBadge from '../components/DocumentStatusBadge.vue';

const store = useStockIssueStore();
const departments = ref([]);
const departmentFilter = ref('');

onMounted(async () => {
    try {
        const res = await departmentApi.getActive();
        departments.value = res.data?.data || [];
    } catch (e) {
        console.error('Failed to load active departments', e);
    }
});

const extraFilters = computed(() => ({
    department_id: departmentFilter.value || undefined,
}));

const { searchQuery, statusFilter, onSearch, changePage, hasPermission } = useDocumentList({
    store,
    fetch: (params) => store.fetchList(params),
    collection: 'issues',
    extraFilters,
});

const hasActiveFilters = computed(() => {
    return Boolean(searchQuery.value || statusFilter.value || departmentFilter.value);
});

let searchTimer = null;
const handleSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        onSearch();
    }, 300);
};
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