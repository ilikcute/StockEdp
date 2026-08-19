<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Pengeluaran Stok
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Daftar dokumen pengeluaran barang.
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <router-link
          v-if="hasPermission('stock_issues.create')"
          to="/inventory/issues/create"
          class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
        >
          Buat Draft Baru
        </router-link>
      </div>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row justify-between gap-4">
      <div class="w-full sm:max-w-xs">
        <input
          id="search"
          v-model="searchQuery"
          type="text"
          class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          placeholder="Cari Nomor Referensi..."
        >
      </div>
      <div class="flex gap-2 flex-wrap sm:flex-nowrap">
        <select
          v-model="statusFilter"
          class="block rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
      </div>
    </div>

    <div
      v-if="store.error"
      class="mt-4 rounded-md bg-red-50 p-4 border border-red-200"
    >
      <p class="text-sm font-medium text-red-800">
        {{ store.error }}
      </p>
    </div>

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
              Nomor Dokumen
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
              Tujuan / Alasan
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Status
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
          <tr v-if="store.loading && (!store.issues?.data || store.issues.data.length === 0)">
            <td
              colspan="6"
              class="py-10 text-center text-sm text-gray-500"
            >
              Memuat data...
            </td>
          </tr>
          <tr v-else-if="!store.issues?.data || store.issues.data.length === 0">
            <td
              colspan="6"
              class="py-10 text-center text-sm text-gray-500"
            >
              Tidak ada data.
            </td>
          </tr>
          <tr
            v-for="(item, index) in (store.issues?.data || [])"
            :key="item.id"
          >
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-center text-gray-500 sm:pl-6">
              {{ (store.issues?.meta?.from ? store.issues.meta.from + index : index + 1) }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
              {{ item.issue_number }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.date }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.purpose }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm">
              <span 
                class="px-2 py-1 text-xs font-semibold rounded-full"
                :class="{
                  'bg-yellow-100 text-yellow-800': item.status === 'DRAFT',
                  'bg-green-100 text-green-800': item.status === 'POSTED',
                  'bg-gray-100 text-gray-800': item.status === 'CANCELED'
                }"
              >
                {{ item.status }}
              </span>
            </td>
            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
              <router-link
                v-if="hasPermission('stock_issues.view')"
                :to="`/inventory/issues/${item.id}`"
                class="text-indigo-600 hover:text-indigo-900"
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
      :loading="store.isLoading"
      @change="changePage"
    />
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useStockIssueStore } from '../stores/useStockIssueStore';
import { useAuthStore } from '@features/auth/stores/use_auth_store';
import BasePagination from '@/shared/components/BasePagination.vue';

const store = useStockIssueStore();
const authStore = useAuthStore();

const searchQuery = ref('');
const statusFilter = ref('');

let debounceTimer = null;
const debouncedSearch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchData(1), 400);
};

watch(searchQuery, debouncedSearch);
watch([statusFilter], () => fetchData(1));

const fetchData = (page = 1) => {
    store.fetchIssues({
        page,
        search: searchQuery.value,
        status: statusFilter.value,
    });
};

const changePage = (page) => {
    if (page >= 1 && page <= store.issues.meta.last_page) {
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
