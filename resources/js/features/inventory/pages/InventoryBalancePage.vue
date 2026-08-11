<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Saldo Stok
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Informasi ketersediaan stok produk di semua lokasi.
        </p>
      </div>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row justify-between gap-4">
      <div class="w-full sm:max-w-xs">
        <input
          id="search"
          v-model="searchQuery"
          type="text"
          class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          placeholder="Cari SKU atau Nama Produk..."
        >
      </div>
      <div class="flex gap-2 flex-wrap sm:flex-nowrap">
        <select
          v-model="sortBy"
          class="block rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option value="id">
            Terbaru
          </option>
          <option value="quantity">
            Kuantitas
          </option>
        </select>
        <select
          v-model="sortOrder"
          class="block rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option value="desc">
            Menurun (Desc)
          </option>
          <option value="asc">
            Menaik (Asc)
          </option>
        </select>
      </div>
    </div>

    <div
      v-if="inventoryStore.error"
      class="mt-4 rounded-md bg-red-50 p-4 border border-red-200"
    >
      <p class="text-sm font-medium text-red-800">
        {{ inventoryStore.error }}
      </p>
    </div>

    <div class="mt-8 overflow-hidden shadow-sm border border-gray-300 md:rounded-lg">
      <table class="min-w-full divide-y divide-gray-300">
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
              SKU & Barcode
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Produk
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
              Saldo Stok
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-if="inventoryStore.loading && inventoryStore.balances.data.length === 0">
            <td
              colspan="5"
              class="py-10 text-center text-sm text-gray-500"
            >
              Memuat data...
            </td>
          </tr>
          <tr v-else-if="inventoryStore.balances.data.length === 0">
            <td
              colspan="5"
              class="py-10 text-center text-sm text-gray-500"
            >
              Tidak ada data saldo stok.
            </td>
          </tr>
          <tr
            v-for="(item, index) in inventoryStore.balances.data"
            :key="item.id"
          >
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-center text-gray-500 sm:pl-6">
              {{ (inventoryStore.balances?.meta?.from ? inventoryStore.balances.meta.from + index : ((page - 1) * 15) + index + 1) }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm">
              <div class="font-mono font-medium text-gray-900">
                {{ item.product?.sku }}
              </div>
              <div
                v-if="item.product?.barcode"
                class="text-xs text-gray-500"
              >
                {{ item.product.barcode }}
              </div>
            </td>
            <td class="px-3 py-4 text-sm text-gray-900">
              <div class="font-medium text-gray-900">
                {{ item.product?.name }}
              </div>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.location?.name }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 font-mono font-medium">
              {{ item.quantity }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <BasePagination
      :pagination="inventoryStore.balances.meta"
      :loading="inventoryStore.isLoading"
      @change="changePage"
    />
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useInventoryStore } from '../stores/useInventoryStore';
import BasePagination from '@/shared/components/BasePagination.vue';

const inventoryStore = useInventoryStore();

const searchQuery = ref('');
const sortBy = ref('id');
const sortOrder = ref('desc');

let debounceTimer = null;
const debouncedSearch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchData(1), 400);
};

watch(searchQuery, debouncedSearch);
watch([sortBy, sortOrder], () => fetchData(1));

const fetchData = (page = 1) => {
    inventoryStore.fetchBalances({
        page,
        search: searchQuery.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value,
    });
};

const changePage = (page) => {
    if (page >= 1 && page <= inventoryStore.balances.meta.last_page) {
        fetchData(page);
    }
};

onMounted(() => {
    fetchData();
});
</script>
