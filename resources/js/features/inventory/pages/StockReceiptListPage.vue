<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Penerimaan Stok
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Daftar dokumen penerimaan barang dari supplier.
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
        <router-link
          v-if="hasPermission('stock_receipts.create')"
          to="/inventory/receipts/create"
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
          class="block w-full rounded-md border-gray-300 pl-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          placeholder="Cari Nomor Referensi..."
        >
      </div>
      <div class="flex gap-2 flex-wrap sm:flex-nowrap">
        <select
          v-model="statusFilter"
          class="block rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
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
      class="mt-4 rounded-md bg-red-50 p-4"
    >
      <p class="text-sm font-medium text-red-800">
        {{ store.error }}
      </p>
    </div>

    <div class="mt-8 overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
      <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
          <tr>
            <th
              scope="col"
              class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"
            >
              Nomor Dokumen
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
              Supplier
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Status
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
          <tr v-if="store.loading && (!store.receipts?.data || store.receipts.data.length === 0)">
            <td
              colspan="5"
              class="py-10 text-center text-sm text-gray-500"
            >
              Memuat data...
            </td>
          </tr>
          <tr v-else-if="!store.receipts?.data || store.receipts.data.length === 0">
            <td
              colspan="5"
              class="py-10 text-center text-sm text-gray-500"
            >
              Tidak ada data.
            </td>
          </tr>
          <tr
            v-for="item in (store.receipts?.data || [])"
            :key="item.id"
          >
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
              {{ item.receipt_number }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.date }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.supplier?.name }}
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
                v-if="hasPermission('stock_receipts.view')"
                :to="`/inventory/receipts/${item.id}`"
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
      :pagination="store.receipts?.meta"
      :loading="store.isLoading"
      @change="changePage"
    />
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useStockReceiptStore } from '../stores/useStockReceiptStore';
import { useAuthStore } from '@features/auth/stores/use_auth_store';
import BasePagination from '@/shared/components/BasePagination.vue';

const store = useStockReceiptStore();
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
    store.fetchReceipts({
        page,
        search: searchQuery.value,
        status: statusFilter.value,
    });
};

const changePage = (page) => {
    if (page >= 1 && page <= store.receipts.meta.last_page) {
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
