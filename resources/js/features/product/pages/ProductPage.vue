<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Master Produk
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Kelola daftar produk, kategori, dan satuan.
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex items-center gap-2">
        <button
          v-if="hasPermission('products.import')"
          id="btn-import-product"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 cursor-pointer"
          @click="isImportModalOpen = true"
        >
          📥 Import CSV
        </button>
        <button
          v-if="hasPermission('products.create')"
          id="btn-create-product"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 cursor-pointer"
          @click="openCreateModal"
        >
          Tambah Produk
        </button>
      </div>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row justify-between gap-4">
      <div class="w-full sm:max-w-xs">
        <input
          id="search"
          v-model="searchQuery"
          type="text"
          class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          placeholder="Cari SKU, Barcode, atau Nama..."
        >
      </div>
      <div class="flex gap-2 flex-wrap sm:flex-nowrap">
        <select
          v-model="categoryFilter"
          class="block rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option value="">
            Semua Kategori
          </option>
          <option
            v-for="cat in categories"
            :key="cat.id"
            :value="cat.id"
          >
            {{ cat.name }}
          </option>
        </select>
        <select
          v-model="unitFilter"
          class="block rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option value="">
            Semua Satuan
          </option>
          <option
            v-for="unit in units"
            :key="unit.id"
            :value="unit.id"
          >
            {{ unit.name }}
          </option>
        </select>
        <select
          v-model="statusFilter"
          class="block rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option value="">
            Semua Status
          </option>
          <option value="true">
            Aktif
          </option>
          <option value="false">
            Nonaktif
          </option>
        </select>
        <select
          v-model="sortBy"
          class="block rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option value="created_at">
            Terbaru
          </option>
          <option value="sku">
            SKU
          </option>
          <option value="name">
            Nama
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
    <div
      v-if="store.successMessage"
      class="mt-4 rounded-md bg-green-50 p-4 border border-green-200 flex items-center justify-between"
    >
      <p class="text-sm font-medium text-green-800">
        {{ store.successMessage }}
      </p>
      <button
        class="text-green-600 hover:text-green-800 font-bold"
        @click="store.clearMessages()"
      >
        ✕
      </button>
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
              Kategori & Satuan
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Min Stock
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
          <tr v-if="store.isLoading && store.items.length === 0">
            <td
              colspan="7"
              class="py-10 text-center text-sm text-gray-500"
            >
              Memuat data...
            </td>
          </tr>
          <tr v-else-if="store.items.length === 0">
            <td
              colspan="7"
              class="py-10 text-center text-sm text-gray-500"
            >
              Tidak ada data produk yang ditemukan.
            </td>
          </tr>
          <tr
            v-for="(product, index) in store.items"
            :key="product.id"
            :class="{ 'bg-gray-50': !product.is_active }"
          >
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-center text-gray-500 sm:pl-6">
              {{ (store.pagination?.from ? store.pagination.from + index : ((filters.page - 1) * filters.per_page) + index + 1) }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm">
              <div class="font-mono font-medium text-gray-900">
                {{ product.sku }}
              </div>
              <div
                v-if="product.barcode"
                class="text-xs text-gray-500"
              >
                {{ product.barcode }}
              </div>
            </td>
            <td class="px-3 py-4 text-sm text-gray-900">
              <div class="font-medium text-gray-900">
                {{ product.name }}
              </div>
              <div
                v-if="product.description"
                class="text-xs text-gray-500 truncate max-w-[200px]"
                :title="product.description"
              >
                {{ product.description }}
              </div>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <div class="text-gray-900">
                {{ product.category_name }}
              </div>
              <div class="text-xs">
                {{ product.unit_name }}
              </div>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ product.minimum_stock }} {{ product.unit_abbreviation }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm">
              <span
                class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                :class="product.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
              >
                {{ product.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
              <button
                v-if="hasPermission('products.update')"
                class="text-indigo-600 hover:text-indigo-900 mr-4"
                @click="openEditModal(product)"
              >
                Ubah
              </button>
              <button
                v-if="hasPermission('products.change_status')"
                :class="product.is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'"
                @click="openStatusModal(product)"
              >
                {{ product.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <BasePagination
      :pagination="store.pagination"
      :loading="store.isLoading"
      @change="changePage"
    />

    <ProductFormModal
      :is-open="isFormModalOpen"
      :product="selectedProduct"
      :categories="categories"
      :units="units"
      @close="closeFormModal"
      @saved="fetchData"
    />
    <ProductStatusModal
      :is-open="isStatusModalOpen"
      :product="selectedProduct"
      @close="closeStatusModal"
      @status-changed="fetchData"
    />
    <MasterDataImportModal
      :show="isImportModalOpen"
      type="products"
      title="Import Produk Masal"
      @close="isImportModalOpen = false"
      @imported="fetchData(1)"
    />
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useProductStore } from '../stores/use_product_store';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import ProductFormModal from '../components/ProductFormModal.vue';
import ProductStatusModal from '../components/ProductStatusModal.vue';
import MasterDataImportModal from '../../master_data_import/components/MasterDataImportModal.vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import apiClient from '@/shared/api/api_client';

const store = useProductStore();
const authStore = useAuthStore();

const searchQuery = ref('');
const statusFilter = ref('');
const categoryFilter = ref('');
const unitFilter = ref('');
const sortBy = ref('created_at');

const isFormModalOpen = ref(false);
const isStatusModalOpen = ref(false);
const isImportModalOpen = ref(false);
const selectedProduct = ref(null);

const categories = ref([]);
const units = ref([]);

const hasPermission = (p) => authStore.hasPermission(p);



let debounceTimer = null;
const debouncedSearch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchData(1), 400);
};

watch(searchQuery, debouncedSearch);
watch([statusFilter, categoryFilter, unitFilter, sortBy], () => fetchData(1));

const fetchCategories = async () => {
    try {
        const response = await apiClient.get('/categories', { params: { is_active: true, per_page: 1000 } });
        categories.value = response.data;
    } catch (e) {
        console.error('Failed to load categories', e);
    }
};

const fetchUnits = async () => {
    try {
        const response = await apiClient.get('/units', { params: { is_active: true, per_page: 1000 } });
        units.value = response.data;
    } catch (e) {
        console.error('Failed to load units', e);
    }
};

const fetchData = (page = 1) => {
    store.fetchProducts({
        page,
        search: searchQuery.value,
        is_active: statusFilter.value,
        category_id: categoryFilter.value,
        unit_id: unitFilter.value,
        sort_by: sortBy.value,
        sort_order: sortBy.value === 'created_at' ? 'desc' : 'asc',
    });
};

const changePage = (page) => {
    if (page >= 1 && page <= (store.pagination?.last_page || 1)) fetchData(page);
};

const openCreateModal = () => {
    selectedProduct.value = null;
    isFormModalOpen.value = true;
};

const openEditModal = (product) => {
    selectedProduct.value = { ...product };
    isFormModalOpen.value = true;
};

const closeFormModal = () => {
    isFormModalOpen.value = false;
    setTimeout(() => { selectedProduct.value = null; }, 200);
};

const openStatusModal = (product) => {
    selectedProduct.value = { ...product };
    isStatusModalOpen.value = true;
};

const closeStatusModal = () => {
    isStatusModalOpen.value = false;
    setTimeout(() => { selectedProduct.value = null; }, 200);
};

onMounted(() => {
    fetchCategories();
    fetchUnits();
    fetchData();
});
</script>
