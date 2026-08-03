<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Laporan Saldo Stok
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Informasi ketersediaan stok produk di semua lokasi.
        </p>
      </div>
    </div>

    <!-- Filters -->
    <div class="mt-6 flex flex-col gap-4">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="w-full sm:w-auto flex-1 min-w-[200px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
          <input
            id="search"
            v-model="filters.search"
            type="text"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3"
            placeholder="Cari SKU atau Nama Produk..."
          >
        </div>
        
        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
          <select
            v-model="filters.location_id"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="">
              Semua Lokasi
            </option>
            <option
              v-for="loc in masterStore.locations"
              :key="loc.id"
              :value="loc.id"
            >
              {{ loc.name }}
            </option>
          </select>
        </div>

        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
          <select
            v-model="filters.category_id"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="">
              Semua Kategori
            </option>
            <option
              v-for="cat in masterStore.categories"
              :key="cat.id"
              :value="cat.id"
            >
              {{ cat.name }}
            </option>
          </select>
        </div>

        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
          <select
            v-model="filters.unit_id"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="">
              Semua Unit
            </option>
            <option
              v-for="unit in masterStore.units"
              :key="unit.id"
              :value="unit.id"
            >
              {{ unit.code }}
            </option>
          </select>
        </div>
      </div>

      <div class="flex flex-wrap gap-4 items-end">
        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Status Produk</label>
          <select
            v-model="filters.is_active"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="">
              Semua
            </option>
            <option value="1">
              Aktif
            </option>
            <option value="0">
              Nonaktif
            </option>
          </select>
        </div>

        <div class="flex gap-4">
          <label class="flex items-center gap-2">
            <input
              v-model="filters.positive_stock"
              type="checkbox"
              value="1"
              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            >
            <span class="text-sm text-gray-700">Stok Positif</span>
          </label>
          <label class="flex items-center gap-2">
            <input
              v-model="filters.zero_stock"
              type="checkbox"
              value="1"
              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            >
            <span class="text-sm text-gray-700">Stok Nol</span>
          </label>
        </div>

        <div class="flex gap-2 w-full sm:w-auto ml-auto">
          <select
            v-model="filters.sort_by"
            class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="id">
              ID
            </option>
            <option value="product_name">
              Nama Produk
            </option>
            <option value="on_hand_quantity">
              Stok On-Hand
            </option>
          </select>
          <select
            v-model="filters.sort_order"
            class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="asc">
              Menaik (Asc)
            </option>
            <option value="desc">
              Menurun (Desc)
            </option>
          </select>
          <select
            v-model="filters.per_page"
            class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="15">
              15 Baris
            </option>
            <option value="50">
              50 Baris
            </option>
            <option value="100">
              100 Baris
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-if="store.error"
      class="mt-4 rounded-md bg-red-50 p-4 border border-red-200"
    >
      <div class="flex">
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">
            Error memuat data
          </h3>
          <div class="mt-2 text-sm text-red-700">
            <p>{{ store.error }}</p>
          </div>
          <div class="mt-4">
            <button
              class="text-sm font-medium text-red-800 hover:text-red-900 bg-red-100 px-3 py-1.5 rounded-md"
              @click="fetchData(1)"
            >
              Coba Lagi
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Validation Errors -->
    <div
      v-if="Object.keys(store.validationErrors).length > 0"
      class="mt-4 rounded-md bg-yellow-50 p-4 border border-yellow-200"
    >
      <ul class="list-disc pl-5 text-sm text-yellow-700">
        <li
          v-for="(errors, field) in store.validationErrors"
          :key="field"
        >
          {{ errors.join(', ') }}
        </li>
      </ul>
    </div>

    <!-- Data Table -->
    <div class="mt-6 flex flex-col relative">
      <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
          <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <div
              v-if="store.loading"
              class="absolute inset-0 bg-white/50 z-10 flex items-center justify-center"
            >
              <span class="text-indigo-600 font-medium bg-white px-4 py-2 rounded-md shadow">Memuat data...</span>
            </div>

            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th
                    scope="col"
                    class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"
                  >
                    SKU / Barcode
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
                  >
                    Produk
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
                  >
                    Kategori / Unit
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
                  >
                    Lokasi
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900"
                  >
                    On-Hand
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900"
                  >
                    Available
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900"
                  >
                    Min Stock
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900"
                  >
                    Status
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr v-if="!store.loading && store.data.length === 0">
                  <td
                    colspan="8"
                    class="py-10 text-center text-sm text-gray-500"
                  >
                    Tidak ada data saldo stok yang sesuai kriteria filter.
                  </td>
                </tr>
                <tr
                  v-for="item in store.data"
                  :key="item.id"
                  :class="{'bg-red-50': item.is_below_minimum}"
                >
                  <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                    <div class="font-mono font-medium text-gray-900">
                      {{ item.product?.sku }}
                    </div>
                    <div class="text-xs text-gray-500">
                      {{ item.product?.barcode || '-' }}
                    </div>
                  </td>
                  <td class="px-3 py-4 text-sm text-gray-900">
                    <div class="font-medium text-gray-900">
                      {{ item.product?.name }}
                    </div>
                    <span
                      v-if="item.product?.is_active === false"
                      class="inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600 mt-1"
                    >Nonaktif</span>
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    <div>{{ item.product?.category?.name || '-' }}</div>
                    <div class="text-xs">
                      {{ item.product?.unit?.code || '-' }}
                    </div>
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    <div>{{ item.location?.name }}</div>
                    <span
                      v-if="item.location?.is_frozen"
                      class="inline-flex items-center rounded-md bg-blue-50 px-1.5 py-0.5 text-xs font-medium text-blue-700 mt-1"
                    >Frozen</span>
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 font-mono text-right font-medium">
                    {{ item.on_hand_quantity }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 font-mono text-right">
                    {{ item.available_quantity }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 font-mono text-right">
                    {{ item.minimum_stock_level }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                    <span
                      v-if="item.is_below_minimum"
                      class="inline-flex items-center rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700"
                    >Below Min</span>
                    <span
                      v-else
                      class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700"
                    >OK</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div
      v-if="store.meta && store.meta.total > 0"
      class="mt-4 flex items-center justify-between"
    >
      <p class="text-sm text-gray-700">
        Menampilkan
        <span class="font-medium">{{ store.meta.from || 0 }}</span>
        sampai
        <span class="font-medium">{{ store.meta.to || 0 }}</span>
        dari
        <span class="font-medium">{{ store.meta.total }}</span>
        data
      </p>
      <div class="flex gap-2">
        <button
          class="px-3 py-1 text-sm rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-40"
          :disabled="store.meta.current_page === 1 || store.loading"
          @click="changePage(store.meta.current_page - 1)"
        >
          Previous
        </button>
        <span class="px-3 py-1 text-sm font-medium text-gray-700">Halaman {{ store.meta.current_page }} / {{ store.meta.last_page }}</span>
        <button
          class="px-3 py-1 text-sm rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-40"
          :disabled="store.meta.current_page === store.meta.last_page || store.loading"
          @click="changePage(store.meta.current_page + 1)"
        >
          Next
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, watch, reactive } from 'vue';
import { useInventoryBalanceReportStore } from '../stores/useInventoryBalanceReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';

const store = useInventoryBalanceReportStore();
const masterStore = useReportFilterOptionsStore();

const filters = reactive({
    search: '',
    location_id: '',
    category_id: '',
    unit_id: '',
    is_active: '',
    positive_stock: false,
    zero_stock: false,
    sort_by: 'id',
    sort_order: 'desc',
    per_page: '15'
});

let debounceTimer = null;
const debouncedFetch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchData(1), 500);
};

// When any filter changes, reset to page 1
watch(() => ({...filters}), debouncedFetch, { deep: true });

const fetchData = (page = 1) => {
    store.fetchBalances({
        page,
        ...filters,
        positive_stock: filters.positive_stock ? 1 : null,
        zero_stock: filters.zero_stock ? 1 : null,
    });
};

const changePage = (page) => {
    if (page >= 1 && page <= store.meta.last_page) {
        store.fetchBalances({
            page,
            ...filters,
            positive_stock: filters.positive_stock ? 1 : null,
            zero_stock: filters.zero_stock ? 1 : null,
        });
    }
};

onMounted(async () => {
    await masterStore.fetchOptions();
    fetchData(1);
});
</script>
