<template>
  <div class="space-y-3">
    <!-- TOP Header & Filter Toolbar Compact -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs space-y-2.5">
      <!-- Primary Controls Row -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <!-- Title & Subtitle -->
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
                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
              />
            </svg>
            Master Produk
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Kelola daftar produk, kategori, dan satuan persediaan.
          </p>
        </div>

        <!-- Primary Controls (Search, Toggle Filter, Reset, Action Buttons) -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          <!-- Search Input -->
          <div class="w-full sm:w-48">
            <BaseSearchInput
              :model-value="searchQuery"
              placeholder="Cari SKU, Barcode, atau Nama..."
              @update:model-value="searchQuery = $event"
              @search="onSearch"
            />
          </div>

          <!-- Toggle Advanced Filters -->
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium shadow-2xs transition-colors cursor-pointer whitespace-nowrap',
              showAdvancedFilters || activeFiltersCount > 0
                ? 'bg-indigo-50 border-indigo-200 text-indigo-700 hover:bg-indigo-100'
                : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
            ]"
            @click="showAdvancedFilters = !showAdvancedFilters"
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
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
              />
            </svg>
            <span>Filter</span>
            <span
              v-if="activeFiltersCount > 0"
              class="inline-flex items-center justify-center px-1.5 py-0.2 text-[10px] font-bold text-white bg-indigo-600 rounded-full"
            >
              {{ activeFiltersCount }}
            </span>
          </button>

          <!-- Reset Filter -->
          <button
            v-if="isAnyFilterActive"
            type="button"
            class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 shadow-2xs cursor-pointer whitespace-nowrap"
            title="Reset Filter"
            @click="resetAllFilters"
          >
            Reset
          </button>

          <!-- Action Buttons -->
          <BaseButton
            v-if="hasPermission('products.import')"
            id="btn-import-product"
            variant="secondary"
            size="sm"
            @click="isImportModalOpen = true"
          >
            📥 Import CSV
          </BaseButton>
          <BaseButton
            v-if="hasPermission('products.create')"
            id="btn-create-product"
            size="sm"
            @click="openCreateModal"
          >
            + Tambah Produk
          </BaseButton>
        </div>
      </div>

      <!-- Secondary Row: Advanced Filters (Category, Unit, Status, Sorting) -->
      <div
        v-show="showAdvancedFilters"
        class="border-t border-gray-100 pt-2 flex flex-wrap items-center justify-between gap-2.5 text-xs"
      >
        <div class="flex items-center gap-2 flex-wrap">
          <!-- Kategori -->
          <div class="flex items-center gap-1">
            <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Kategori:</span>
            <select
              id="filter-category"
              v-model="categoryFilter"
              class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
          </div>

          <!-- Satuan -->
          <div class="flex items-center gap-1">
            <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Satuan:</span>
            <select
              id="filter-unit"
              v-model="unitFilter"
              class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
          </div>

          <!-- Status -->
          <div class="flex items-center gap-1">
            <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Status:</span>
            <select
              v-model="statusFilter"
              class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
          </div>

          <!-- Sort By -->
          <div class="flex items-center gap-1">
            <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Urutkan:</span>
            <select
              v-model="sortBy"
              class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
      </div>
    </div>

    <BaseAlert
      v-if="store.error"
      :message="store.error"
    />
    <BaseAlert
      v-if="store.successMessage"
      variant="success"
      :message="store.successMessage"
      dismissible
      @dismiss="store.clearMessages()"
    />

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
              SKU & Barcode
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Produk
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Kategori & Satuan
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Harga Satuan
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Min Stock
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Status
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
          <tr v-if="store.isLoading && store.items.length === 0">
            <td
              colspan="8"
              class="py-8 text-center text-xs text-gray-500"
            >
              Memuat data...
            </td>
          </tr>
          <tr v-else-if="store.items.length === 0">
            <td
              colspan="8"
              class="py-8 text-center text-xs text-gray-500"
            >
              Tidak ada data produk yang ditemukan.
            </td>
          </tr>
          <tr
            v-for="(product, index) in store.items"
            :key="product.id"
            class="hover:bg-gray-50/80 transition-colors"
            :class="{ 'bg-gray-50/50': !product.is_active }"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(store.pagination, index) }}
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <div class="font-mono font-medium text-gray-900 leading-tight">
                {{ product.sku }}
              </div>
              <div
                v-if="product.barcode"
                class="text-[10px] text-gray-400 font-mono"
              >
                {{ product.barcode }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-[11px]">
              <div class="font-medium text-gray-900 leading-tight">
                {{ product.name }}
              </div>
              <div
                v-if="product.description"
                class="text-[10px] text-gray-400 truncate max-w-[200px]"
                :title="product.description"
              >
                {{ product.description }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
              <div class="text-gray-900 font-medium leading-tight">
                {{ product.category_name }}
              </div>
              <div class="text-[10px] text-gray-400">
                {{ product.unit_name }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-[11px] font-medium text-gray-900 whitespace-nowrap">
              {{ formatRupiah(product.unit_price) }}
            </td>
            <td class="py-1.5 px-2 text-[11px] text-gray-500 whitespace-nowrap">
              {{ formatQuantity(product.minimum_stock) }} {{ product.unit_abbreviation }}
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap">
              <span
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider"
                :class="product.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
              >
                {{ product.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="py-1.5 px-2 text-right whitespace-nowrap text-[11px] space-x-2">
              <button
                v-if="hasPermission('products.update')"
                class="font-semibold text-indigo-600 hover:text-indigo-900 hover:underline cursor-pointer"
                @click="openEditModal(product)"
              >
                Ubah
              </button>
              <button
                v-if="hasPermission('products.change_status')"
                class="font-semibold hover:underline cursor-pointer"
                :class="product.is_active ? 'text-rose-600 hover:text-rose-900' : 'text-emerald-600 hover:text-emerald-900'"
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
import { computed, onMounted, ref, watch } from 'vue';
import { useProductStore } from '../stores/use_product_store';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import BaseButton from '@/shared/components/BaseButton.vue';
import BaseSearchInput from '@/shared/components/BaseSearchInput.vue';
import BaseAlert from '@/shared/components/BaseAlert.vue';
import ProductFormModal from '../components/ProductFormModal.vue';
import ProductStatusModal from '../components/ProductStatusModal.vue';
import MasterDataImportModal from '../../master_data_import/components/MasterDataImportModal.vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import apiClient from '@/shared/api/api_client';
import { formatQuantity, formatRupiah, rowNumber } from '@/shared/utils/formatters';

const store = useProductStore();
const authStore = useAuthStore();

const searchQuery = ref('');
const statusFilter = ref('');
const categoryFilter = ref('');
const unitFilter = ref('');
const sortBy = ref('created_at');
const showAdvancedFilters = ref(false);

const activeFiltersCount = computed(() => {
    let count = 0;
    if (categoryFilter.value) count++;
    if (unitFilter.value) count++;
    if (statusFilter.value) count++;
    if (sortBy.value !== 'created_at') count++;
    return count;
});

const isAnyFilterActive = computed(() => {
    return !!(
        searchQuery.value ||
        categoryFilter.value ||
        unitFilter.value ||
        statusFilter.value ||
        sortBy.value !== 'created_at'
    );
});

const resetAllFilters = () => {
    searchQuery.value = '';
    categoryFilter.value = '';
    unitFilter.value = '';
    statusFilter.value = '';
    sortBy.value = 'created_at';
    fetchData(1);
};

const isFormModalOpen = ref(false);
const isStatusModalOpen = ref(false);
const isImportModalOpen = ref(false);
const selectedProduct = ref(null);

const categories = ref([]);
const units = ref([]);

const hasPermission = (p) => authStore.hasPermission(p);

function onSearch(value) {
    searchQuery.value = value;
    fetchData(1);
}

watch([statusFilter, categoryFilter, unitFilter, sortBy], () => fetchData(1));

const fetchCategories = async () => {
    try {
        const response = await apiClient.get('/categories', { params: { is_active: true, per_page: 1000 } });
        categories.value = response.data?.data || [];
    } catch (e) {
        console.error('Failed to load categories', e);
    }
};

const fetchUnits = async () => {
    try {
        const response = await apiClient.get('/units', { params: { is_active: true, per_page: 1000 } });
        units.value = response.data?.data || [];
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
