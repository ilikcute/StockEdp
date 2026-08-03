<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Kartu Stok (Stock Card)
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Riwayat pergerakan stok untuk suatu produk di lokasi tertentu dalam periode waktu tertentu.
        </p>
      </div>
    </div>

    <!-- Filters -->
    <div class="mt-6 flex flex-col gap-4">
      <div class="flex flex-wrap gap-4 items-end">
        <div class="w-full sm:w-auto flex-1 min-w-[250px] relative">
          <label class="block text-sm font-medium text-gray-700 mb-1">Produk <span class="text-red-500">*</span></label>
          <div class="relative">
            <input
              v-model="productSearch"
              type="text"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3"
              placeholder="Cari & Pilih Produk..."
              @input="onProductSearch"
              @focus="showProductDropdown = true"
            >
            <div
              v-if="selectedProduct"
              class="absolute inset-y-0 right-0 pr-3 flex items-center"
            >
              <button
                class="text-gray-400 hover:text-gray-600"
                @click="clearProduct"
              >
                <span class="sr-only">Clear</span>
                &times;
              </button>
            </div>
            <div
              v-if="showProductDropdown && masterStore.products.length > 0"
              class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto sm:text-sm"
            >
              <div
                v-for="prod in masterStore.products"
                :key="prod.id"
                class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-50"
                @click="selectProduct(prod)"
              >
                <div class="font-medium text-gray-900">
                  {{ prod.name }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ prod.sku }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="w-full sm:w-auto min-w-[200px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi <span class="text-red-500">*</span></label>
          <select
            v-model="filters.location_id"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
          >
            <option value="">
              -- Pilih Lokasi --
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
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
          <input
            v-model="filters.start_date"
            type="date"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3"
          >
        </div>

        <div class="w-full sm:w-auto min-w-[150px]">
          <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir <span class="text-red-500">*</span></label>
          <input
            v-model="filters.end_date"
            type="date"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3"
          >
        </div>
      </div>
      
      <div class="flex justify-between items-center">
        <div>
          <button
            :disabled="!canFetch || store.loading"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:bg-gray-400 disabled:cursor-not-allowed"
            @click="fetchData(1)"
          >
            Tampilkan Kartu Stok
          </button>
        </div>
        <div class="flex gap-2">
          <select
            v-model="filters.per_page"
            class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 pl-3 pr-10"
            @change="fetchData(1)"
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
      
      <!-- Filter Error Validation UX -->
      <div
        v-if="localValidationError"
        class="text-sm text-red-600 font-medium"
      >
        {{ localValidationError }}
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

    <!-- Prompt / Loading -->
    <div
      v-if="!hasFetchedData"
      class="mt-6 rounded-md bg-blue-50 p-4 border border-blue-200 text-center py-10"
    >
      <p class="text-sm font-medium text-blue-800">
        Pilih produk, lokasi, dan periode untuk melihat kartu stok.
      </p>
    </div>

    <div
      v-else-if="store.loading"
      class="mt-6 text-center py-10"
    >
      <p class="text-sm text-gray-500 font-medium">
        Memuat kartu stok...
      </p>
    </div>

    <!-- Stock Card Data -->
    <div
      v-else
      class="mt-6"
    >
      <div class="bg-gray-50 p-4 rounded-t-lg border border-gray-200 shadow-sm mb-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
          <div>
            <span class="block text-gray-500">Opening Balance:</span>
            <span class="font-mono font-medium text-gray-900">{{ store.summary?.opening_balance || '-' }}</span>
          </div>
          <div>
            <span class="block text-gray-500">Total Masuk:</span>
            <span class="font-mono font-medium text-green-700">+{{ store.summary?.total_quantity_in || '-' }}</span>
          </div>
          <div>
            <span class="block text-gray-500">Total Keluar:</span>
            <span class="font-mono font-medium text-red-700">-{{ store.summary?.total_quantity_out || '-' }}</span>
          </div>
          <div>
            <span class="block text-gray-500">Closing Balance:</span>
            <span class="font-mono font-medium text-gray-900">{{ store.summary?.closing_balance || '-' }}</span>
          </div>
        </div>
        <div
          v-if="store.meta?.date_basis"
          class="mt-2 text-xs text-gray-500"
        >
          Periode laporan menggunakan waktu posting transaksi. (Date Basis: {{ store.meta.date_basis }})
        </div>
      </div>

      <div class="flex flex-col relative">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
              <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                  <tr>
                    <th
                      scope="col"
                      class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"
                    >
                      Tanggal Dokumen
                    </th>
                    <th
                      scope="col"
                      class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
                    >
                      Tanggal Posting
                    </th>
                    <th
                      scope="col"
                      class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
                    >
                      Referensi / Tipe
                    </th>
                    <th
                      scope="col"
                      class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900"
                    >
                      Qty Before
                    </th>
                    <th
                      scope="col"
                      class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900"
                    >
                      Masuk
                    </th>
                    <th
                      scope="col"
                      class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900"
                    >
                      Keluar
                    </th>
                    <th
                      scope="col"
                      class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900"
                    >
                      Qty After
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                  <tr v-if="store.data.length === 0">
                    <td
                      colspan="7"
                      class="py-10 text-center text-sm text-gray-500"
                    >
                      Tidak ada pergerakan stok dalam periode ini.
                    </td>
                  </tr>
                  <tr
                    v-for="item in store.data"
                    :key="item.id"
                  >
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6 text-gray-900">
                      {{ item.document_date || '-' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      {{ item.occurred_at || item.posted_at || '-' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                      <div class="font-medium text-gray-900">
                        {{ item.reference_number || '-' }}
                      </div>
                      <div class="text-xs text-gray-500">
                        {{ item.movement_type }}
                      </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 font-mono text-right">
                      {{ item.quantity_before }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-green-700 font-mono font-medium text-right bg-green-50/30">
                      <span v-if="item.direction === 'in' || item.quantity_in && item.quantity_in !== '0.0000'">+{{ item.quantity_in || item.quantity }}</span>
                      <span v-else>-</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-red-700 font-mono font-medium text-right bg-red-50/30">
                      <span v-if="item.direction === 'out' || item.quantity_out && item.quantity_out !== '0.0000'">-{{ item.quantity_out || item.quantity }}</span>
                      <span v-else>-</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 font-mono font-bold text-right bg-gray-50/50">
                      {{ item.quantity_after }}
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
          Menampilkan <span class="font-medium">{{ store.meta.from || 0 }}</span> sampai <span class="font-medium">{{ store.meta.to || 0 }}</span> dari <span class="font-medium">{{ store.meta.total }}</span> pergerakan
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
  </div>
</template>

<script setup>
import { onMounted, ref, watch, reactive, computed } from 'vue';
import { useStockCardReportStore } from '../stores/useStockCardReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';

const store = useStockCardReportStore();
const masterStore = useReportFilterOptionsStore();

const filters = reactive({
    product_id: '',
    location_id: '',
    start_date: '',
    end_date: '',
    per_page: '15'
});

const productSearch = ref('');
const selectedProduct = ref(null);
const showProductDropdown = ref(false);
const localValidationError = ref('');
const hasFetchedData = ref(false);

// Format date helper for default values
const today = new Date();
const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
filters.end_date = today.toISOString().split('T')[0];
filters.start_date = firstDayOfMonth.toISOString().split('T')[0];

let productSearchTimer = null;
const onProductSearch = () => {
    clearTimeout(productSearchTimer);
    productSearchTimer = setTimeout(() => {
        if (productSearch.value.trim().length >= 2) {
            masterStore.searchProducts(productSearch.value);
        }
    }, 400);
};

const selectProduct = (prod) => {
    selectedProduct.value = prod;
    filters.product_id = prod.id;
    productSearch.value = prod.name;
    showProductDropdown.value = false;
};

const clearProduct = () => {
    selectedProduct.value = null;
    filters.product_id = '';
    productSearch.value = '';
};

// Close dropdown when clicking outside (simplified for this example)
watch(productSearch, (val) => {
    if (!val) {
        clearProduct();
    }
});

const canFetch = computed(() => {
    return filters.product_id && filters.location_id && filters.start_date && filters.end_date;
});

const validateFilters = () => {
    localValidationError.value = '';
    
    if (!filters.product_id || !filters.location_id || !filters.start_date || !filters.end_date) {
        localValidationError.value = 'Mohon lengkapi produk, lokasi, tanggal mulai, dan tanggal akhir.';
        return false;
    }
    
    const start = new Date(filters.start_date);
    const end = new Date(filters.end_date);
    
    if (start > end) {
        localValidationError.value = 'Tanggal mulai tidak boleh melewati tanggal akhir.';
        return false;
    }
    
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays > 366) {
        localValidationError.value = 'Rentang waktu maksimal adalah 366 hari.';
        return false;
    }
    
    return true;
};

const fetchData = async (page = 1) => {
    if (!validateFilters()) return;
    
    hasFetchedData.value = true;
    
    await store.fetchStockCard({
        page,
        ...filters
    });
};

const changePage = (page) => {
    if (page >= 1 && page <= store.meta.last_page) {
        fetchData(page);
    }
};

onMounted(async () => {
    await masterStore.fetchOptions();
});
</script>
