<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Master Kategori
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Kelola daftar kategori produk inventory.
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex items-center gap-2">
        <button
          v-if="authStore.hasPermission('categories.import')"
          id="btn-import-category"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 cursor-pointer"
          @click="showImportModal = true"
        >
          📥 Import CSV
        </button>
        <button
          v-if="authStore.hasPermission('categories.create')"
          id="btn-create-category"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 cursor-pointer"
          @click="openCreateModal"
        >
          Tambah Kategori
        </button>
      </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="mt-6 flex flex-col sm:flex-row justify-between gap-4">
      <div class="w-full sm:max-w-xs">
        <input
          id="search"
          v-model="searchQuery"
          type="text"
          class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          placeholder="Cari kode atau nama..."
        >
      </div>
      <div class="flex gap-2 flex-wrap sm:flex-nowrap">
        <select
          v-model="filterActive"
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
          <option value="code">
            Kode
          </option>
          <option value="name">
            Nama
          </option>
        </select>
      </div>
    </div>

    <!-- Alerts -->
    <div
      v-if="categoryStore.error && !isForbidden"
      class="mt-4 rounded-md bg-red-50 p-4 border border-red-200"
    >
      <p class="text-sm font-medium text-red-800">
        {{ categoryStore.error }}
      </p>
    </div>
    <div
      v-if="categoryStore.successMessage"
      class="mt-4 rounded-md bg-green-50 p-4 border border-green-200 flex items-center justify-between"
    >
      <p class="text-sm font-medium text-green-800">
        {{ categoryStore.successMessage }}
      </p>
      <button
        class="text-green-600 hover:text-green-800 font-bold"
        @click="categoryStore.clearSuccess()"
      >
        ✕
      </button>
    </div>

    <!-- Forbidden Message -->
    <div
      v-if="isForbidden"
      class="mt-6 text-center py-12 bg-white rounded-lg border border-gray-300 shadow-sm"
    >
      <p class="text-gray-500 text-sm">
        Anda tidak memiliki izin untuk melihat data kategori.
      </p>
    </div>

    <!-- Table -->
    <div
      v-else
      class="mt-8 overflow-hidden shadow-sm border border-gray-300 md:rounded-lg"
    >
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
              Kode
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Nama Kategori
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Deskripsi
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
          <tr v-if="categoryStore.isLoading && !categoryStore.items.length">
            <td
              colspan="6"
              class="py-10 text-center text-sm text-gray-500"
            >
              Memuat data...
            </td>
          </tr>
          <tr v-else-if="!categoryStore.items.length">
            <td
              colspan="6"
              class="py-10 text-center text-sm text-gray-500"
            >
              Tidak ada data kategori yang ditemukan.
            </td>
          </tr>
          <tr
            v-for="(item, index) in categoryStore.items"
            :key="item.id"
            :class="{ 'bg-gray-50': !item.is_active }"
          >
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-center text-gray-500 sm:pl-6">
              {{ (categoryStore.pagination?.from ? categoryStore.pagination.from + index : index + 1) }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
              {{ item.code }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700 font-medium">
              {{ item.name }}
            </td>
            <td class="px-3 py-4 text-sm text-gray-500 max-w-xs truncate">
              {{ item.description || '-' }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm">
              <span
                class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
              >
                {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
              <button
                v-if="authStore.hasPermission('categories.update')"
                class="text-indigo-600 hover:text-indigo-900 mr-4 font-medium"
                @click="openEditModal(item)"
              >
                Ubah
              </button>
              <button
                v-if="authStore.hasPermission('categories.change_status')"
                class="font-medium"
                :class="item.is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'"
                @click="openStatusModal(item)"
              >
                {{ item.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <BasePagination
      :pagination="categoryStore.pagination"
      :loading="categoryStore.isLoading"
      @change="goToPage"
    />

    <!-- Modals -->
    <CategoryFormModal
      :visible="showFormModal"
      :category="editingCategory"
      :errors="categoryStore.validationErrors"
      :loading="categoryStore.isLoading"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    />

    <CategoryStatusModal
      v-model="showStatusModal"
      :category="statusCategory"
      :loading="categoryStore.isLoading"
      @confirm="handleStatusConfirm"
    />

    <MasterDataImportModal
      :show="showImportModal"
      type="categories"
      title="Import Kategori Masal"
      @close="showImportModal = false"
      @imported="onImportSuccess"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import BasePagination from '@shared/components/BasePagination.vue';
import CategoryFormModal from '../components/CategoryFormModal.vue';
import CategoryStatusModal from '../components/CategoryStatusModal.vue';
import MasterDataImportModal from '../../master_data_import/components/MasterDataImportModal.vue';
import { useCategoryStore } from '../stores/use_category_store.js';
import { useAuthStore } from '../../auth/stores/use_auth_store.js';

const categoryStore = useCategoryStore();
const authStore = useAuthStore();

const searchQuery = ref('');
const filterActive = ref('');
const sortBy = ref('created_at');
const currentPage = ref(1);

const showFormModal = ref(false);
const showImportModal = ref(false);
const editingCategory = ref(null);
const showStatusModal = ref(false);
const statusCategory = ref(null);

let searchTimer = null;

const isForbidden = computed(() =>
    categoryStore.error?.includes('tidak memiliki izin') ?? false,
);

const debouncedSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentPage.value = 1;
        loadCategories();
    }, 400);
};

watch(searchQuery, debouncedSearch);
watch([filterActive, sortBy], () => {
    currentPage.value = 1;
    loadCategories();
});

onMounted(() => {
    loadCategories();
});

function loadCategories() {
    const params = {
        page: currentPage.value,
        per_page: 15,
        sort_by: sortBy.value,
        sort_order: sortBy.value === 'created_at' ? 'desc' : 'asc',
    };
    if (searchQuery.value) params.search = searchQuery.value;
    if (filterActive.value) params.is_active = filterActive.value;
    categoryStore.fetchAll(params);
}

function goToPage(page) {
    currentPage.value = page;
    loadCategories();
}

function openCreateModal() {
    editingCategory.value = null;
    categoryStore.clearErrors();
    showFormModal.value = true;
}

function openEditModal(category) {
    editingCategory.value = category;
    categoryStore.clearErrors();
    showFormModal.value = true;
}

function closeFormModal() {
    showFormModal.value = false;
    editingCategory.value = null;
    categoryStore.clearErrors();
}

async function handleFormSubmit(formData) {
    try {
        if (editingCategory.value) {
            await categoryStore.update(editingCategory.value.id, formData);
        } else {
            await categoryStore.create(formData);
        }
        closeFormModal();
        loadCategories();
    } catch {
        // Errors handled by store
    }
}

function openStatusModal(category) {
    statusCategory.value = category;
    showStatusModal.value = true;
}

async function handleStatusConfirm() {
    if (!statusCategory.value) return;
    try {
        await categoryStore.changeStatus(statusCategory.value.id, !statusCategory.value.is_active);
        showStatusModal.value = false;
        statusCategory.value = null;
        loadCategories();
    } catch {
        // Errors handled by store
    }
}

function onImportSuccess() {
    loadCategories();
}
</script>
