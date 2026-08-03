<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Riwayat Pergerakan Stok
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Log histori mutasi persediaan barang.
        </p>
      </div>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row justify-between gap-4">
      <div class="w-full sm:max-w-xs">
        <input
          id="search"
          v-model="searchQuery"
          type="text"
          class="block w-full rounded-md border-gray-300 pl-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          placeholder="Cari SKU atau Referensi..."
        >
      </div>
      <div class="flex gap-2 flex-wrap sm:flex-nowrap">
        <select
          v-model="movementTypeFilter"
          class="block rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
        >
          <option value="">
            Semua Jenis
          </option>
          <option value="RECEIPT">
            Penerimaan
          </option>
          <option value="ISSUE">
            Pengeluaran
          </option>
          <option value="TRANSFER_IN">
            Transfer Masuk
          </option>
          <option value="TRANSFER_OUT">
            Transfer Keluar
          </option>
          <option value="ADJUSTMENT_IN">
            Penyesuaian Masuk
          </option>
          <option value="ADJUSTMENT_OUT">
            Penyesuaian Keluar
          </option>
        </select>
      </div>
    </div>

    <div
      v-if="inventoryStore.error"
      class="mt-4 rounded-md bg-red-50 p-4"
    >
      <p class="text-sm font-medium text-red-800">
        {{ inventoryStore.error }}
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
              Waktu
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Produk & Lokasi
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Jenis
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Jumlah & Saldo
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Referensi
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-if="inventoryStore.loading && inventoryStore.movements.data.length === 0">
            <td
              colspan="5"
              class="py-10 text-center text-sm text-gray-500"
            >
              Memuat data...
            </td>
          </tr>
          <tr v-else-if="inventoryStore.movements.data.length === 0">
            <td
              colspan="5"
              class="py-10 text-center text-sm text-gray-500"
            >
              Tidak ada data pergerakan stok.
            </td>
          </tr>
          <tr
            v-for="item in inventoryStore.movements.data"
            :key="item.id"
          >
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-500 sm:pl-6">
              {{ new Date(item.occurred_at).toLocaleString('id-ID') }}
            </td>
            <td class="px-3 py-4 text-sm text-gray-900">
              <div class="font-medium text-gray-900">
                {{ item.product?.name }} <span class="text-gray-500 font-mono text-xs">({{ item.product?.sku }})</span>
              </div>
              <div class="text-xs text-gray-500">
                {{ item.location?.name }}
              </div>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm">
              <span 
                class="px-2 py-1 text-xs font-semibold rounded-full"
                :class="getBadgeClass(item.movement_type)"
              >
                {{ formatMovementType(item.movement_type) }}
              </span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <div class="font-mono text-gray-900">
                Mutasi: {{ item.quantity }}
              </div>
              <div class="font-mono text-xs">
                Akhir: {{ item.quantity_after }}
              </div>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <div>{{ item.reference_number || '-' }}</div>
              <div class="text-xs">
                oleh {{ item.creator?.name }}
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="inventoryStore.movements.meta && inventoryStore.movements.meta.total > 0"
      class="mt-4 flex items-center justify-between"
    >
      <p class="text-sm text-gray-700">
        Menampilkan
        <span class="font-medium">{{ (inventoryStore.movements.meta.current_page - 1) * inventoryStore.movements.meta.per_page + 1 }}</span>
        sampai
        <span class="font-medium">{{ Math.min(inventoryStore.movements.meta.current_page * inventoryStore.movements.meta.per_page, inventoryStore.movements.meta.total) }}</span>
        dari
        <span class="font-medium">{{ inventoryStore.movements.meta.total }}</span>
        data
      </p>
      <div class="flex gap-2">
        <button
          class="px-3 py-1 text-sm rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-40"
          :disabled="inventoryStore.movements.meta.current_page === 1"
          @click="changePage(inventoryStore.movements.meta.current_page - 1)"
        >
          &laquo;
        </button>
        <button
          v-for="page in inventoryStore.movements.meta.last_page"
          :key="page"
          class="px-3 py-1 text-sm rounded border"
          :class="page === inventoryStore.movements.meta.current_page ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 hover:bg-gray-50'"
          @click="changePage(page)"
        >
          {{ page }}
        </button>
        <button
          class="px-3 py-1 text-sm rounded border border-gray-300 hover:bg-gray-50 disabled:opacity-40"
          :disabled="inventoryStore.movements.meta.current_page === inventoryStore.movements.meta.last_page"
          @click="changePage(inventoryStore.movements.meta.current_page + 1)"
        >
          &raquo;
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useInventoryStore } from '../stores/useInventoryStore';

const inventoryStore = useInventoryStore();

const searchQuery = ref('');
const movementTypeFilter = ref('');

let debounceTimer = null;
const debouncedSearch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchData(1), 400);
};

watch(searchQuery, debouncedSearch);
watch([movementTypeFilter], () => fetchData(1));

const fetchData = (page = 1) => {
    inventoryStore.fetchMovements({
        page,
        search: searchQuery.value,
        movement_type: movementTypeFilter.value,
    });
};

const changePage = (page) => {
    if (page >= 1 && page <= inventoryStore.movements.meta.last_page) {
        fetchData(page);
    }
};

const formatMovementType = (type) => {
    const map = {
        'RECEIPT': 'Penerimaan',
        'ISSUE': 'Pengeluaran',
        'TRANSFER_IN': 'Transfer Masuk',
        'TRANSFER_OUT': 'Transfer Keluar',
        'ADJUSTMENT_IN': 'Penyesuaian Masuk',
        'ADJUSTMENT_OUT': 'Penyesuaian Keluar',
    };
    return map[type] || type;
};

const getBadgeClass = (type) => {
    const map = {
        'RECEIPT': 'bg-green-100 text-green-800',
        'ISSUE': 'bg-red-100 text-red-800',
        'TRANSFER_IN': 'bg-blue-100 text-blue-800',
        'TRANSFER_OUT': 'bg-yellow-100 text-yellow-800',
        'ADJUSTMENT_IN': 'bg-teal-100 text-teal-800',
        'ADJUSTMENT_OUT': 'bg-orange-100 text-orange-800',
    };
    return map[type] || 'bg-gray-100 text-gray-800';
};

onMounted(() => {
    fetchData();
});
</script>
