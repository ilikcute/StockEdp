<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <BasePageHeader
      title="Master Kategori"
      description="Kelola daftar kategori produk inventory."
    >
      <template #actions>
        <BaseButton
          v-if="authStore.hasPermission('categories.import')"
          id="btn-import-category"
          variant="secondary"
          @click="showImportModal = true"
        >
          📥 Import CSV
        </BaseButton>
        <BaseButton
          v-if="authStore.hasPermission('categories.create')"
          id="btn-create-category"
          @click="openCreateModal"
        >
          Tambah Kategori
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Search & Filter Controls -->
    <div class="mt-3 flex flex-col sm:flex-row justify-between gap-2.5">
      <div class="w-full sm:max-w-xs">
        <BaseSearchInput
          :model-value="searchQuery"
          placeholder="Cari kode atau nama..."
          @update:model-value="searchQuery = $event"
          @search="onSearch"
        />
      </div>
      <div class="flex gap-2 flex-wrap sm:flex-nowrap">
        <select
          v-model="filterActive"
          class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
          class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
    <BaseAlert
      v-if="categoryStore.error && !isForbidden"
      :message="categoryStore.error"
    />
    <BaseAlert
      v-if="categoryStore.successMessage"
      variant="success"
      :message="categoryStore.successMessage"
      dismissible
      @dismiss="categoryStore.clearSuccess()"
    />

    <!-- Forbidden Message -->
    <div
      v-if="isForbidden"
      class="mt-4 text-center py-12 bg-white rounded-lg border border-gray-300 shadow-sm"
    >
      <p class="text-gray-500 text-xs">
        Anda tidak memiliki izin untuk melihat data kategori.
      </p>
    </div>

    <!-- Table -->
    <div
      v-else
      class="mt-4 overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar"
    >
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
              Kode
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Nama Kategori
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Deskripsi
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
          <tr v-if="categoryStore.isLoading && !categoryStore.items.length">
            <td
              colspan="6"
              class="py-8 text-center text-xs text-gray-500"
            >
              Memuat data...
            </td>
          </tr>
          <tr v-else-if="!categoryStore.items.length">
            <td
              colspan="6"
              class="py-8 text-center text-xs text-gray-500"
            >
              Tidak ada data kategori yang ditemukan.
            </td>
          </tr>
          <tr
            v-for="(item, index) in categoryStore.items"
            :key="item.id"
            class="hover:bg-gray-50/80 transition-colors"
            :class="{ 'bg-gray-50/50': !item.is_active }"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(categoryStore.pagination, index) }}
            </td>
            <td class="py-1.5 px-2 font-mono font-medium text-gray-900 text-[11px] whitespace-nowrap">
              {{ item.code }}
            </td>
            <td class="py-1.5 px-2 font-medium text-gray-900 text-[11px] whitespace-nowrap">
              {{ item.name }}
            </td>
            <td class="py-1.5 px-2 text-gray-500 text-[11px] max-w-xs truncate">
              {{ item.description || '-' }}
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap">
              <span
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider"
                :class="item.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
              >
                {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="py-1.5 px-2 text-right whitespace-nowrap text-[11px] space-x-2">
              <button
                v-if="authStore.hasPermission('categories.update')"
                class="font-semibold text-indigo-600 hover:text-indigo-900 hover:underline cursor-pointer"
                @click="openEditModal(item)"
              >
                Ubah
              </button>
              <button
                v-if="authStore.hasPermission('categories.change_status')"
                class="font-semibold hover:underline cursor-pointer"
                :class="item.is_active ? 'text-rose-600 hover:text-rose-900' : 'text-emerald-600 hover:text-emerald-900'"
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
import BasePagination from '@/shared/components/BasePagination.vue';
import BaseButton from '@/shared/components/BaseButton.vue';
import BaseSearchInput from '@/shared/components/BaseSearchInput.vue';
import BaseAlert from '@/shared/components/BaseAlert.vue';
import BasePageHeader from '@/shared/components/BasePageHeader.vue';
import { rowNumber } from '@/shared/utils/formatters.js';
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

const isForbidden = computed(() =>
    categoryStore.error?.includes('tidak memiliki izin') ?? false,
);

function onSearch(value) {
    searchQuery.value = value;
    currentPage.value = 1;
    loadCategories();
}

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

async function openEditModal(category) {
    editingCategory.value = category;
    categoryStore.clearErrors();
    showFormModal.value = true;
    // Fetch fresh data from API to activate show endpoint
    try {
        const fresh = await categoryStore.fetchById(category.id);
        if (fresh) editingCategory.value = fresh;
    } catch {
        // Use existing data if fetch fails
    }
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
