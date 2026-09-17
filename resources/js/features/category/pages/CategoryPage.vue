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
                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
              />
            </svg>
            Master Kategori
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Kelola daftar kategori produk inventory.
          </p>
        </div>

        <!-- Primary Controls (Search, Filters, Reset, Action Buttons) -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          <!-- Search Input -->
          <div class="w-full sm:w-48">
            <BaseSearchInput
              :model-value="searchQuery"
              placeholder="Cari kode atau nama..."
              size="sm"
              @update:model-value="searchQuery = $event"
              @search="onSearch"
            />
          </div>

          <!-- Status Filter -->
          <select
            v-model="filterActive"
            class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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

          <!-- Sort By -->
          <select
            v-model="sortBy"
            class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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

          <!-- Reset Button -->
          <button
            v-if="searchQuery || filterActive || sortBy !== 'created_at'"
            type="button"
            class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 shadow-2xs cursor-pointer whitespace-nowrap"
            title="Reset Filter"
            @click="resetFilters"
          >
            Reset
          </button>

          <!-- Action Buttons -->
          <BaseButton
            v-if="authStore.hasPermission('categories.import')"
            id="btn-import-category"
            variant="secondary"
            size="sm"
            @click="showImportModal = true"
          >
            📥 Import CSV
          </BaseButton>
          <BaseButton
            v-if="authStore.hasPermission('categories.create')"
            id="btn-create-category"
            size="sm"
            @click="openCreateModal"
          >
            + Tambah Kategori
          </BaseButton>
        </div>
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
      class="text-center py-12 bg-white rounded-xl border border-gray-200 shadow-2xs"
    >
      <p class="text-gray-500 text-xs">
        Anda tidak memiliki izin untuk melihat data kategori.
      </p>
    </div>

    <!-- Table -->
    <div
      v-else
      class="overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar"
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
              class="py-12 text-center text-xs text-gray-400"
            >
              <div class="flex items-center justify-center gap-2">
                <svg
                  class="animate-spin h-4 w-4 text-indigo-600"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  />
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8v8H4z"
                  />
                </svg>
                <span>Memuat data kategori...</span>
              </div>
            </td>
          </tr>
          <BaseTableEmpty
            v-else-if="!categoryStore.items.length"
            :colspan="6"
            message="Tidak ada data kategori yang cocok."
            :hint="hasActiveFilters ? 'Silakan sesuaikan filter pencarian Anda.' : ''"
          />
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
import BaseTableEmpty from '@/shared/components/BaseTableEmpty.vue';
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

const hasActiveFilters = computed(() => {
    return Boolean(searchQuery.value || filterActive.value);
});

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

function resetFilters() {
    searchQuery.value = '';
    filterActive.value = '';
    sortBy.value = 'created_at';
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
