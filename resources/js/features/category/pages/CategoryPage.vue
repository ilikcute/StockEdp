<template>
  <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Kategori
        </h1>
        <p class="text-sm text-gray-500 mt-1">
          Kelola kategori produk inventory
        </p>
      </div>
      <button
        v-if="authStore.hasPermission('categories.create')"
        id="btn-create-category"
        class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors shadow-sm cursor-pointer"
        @click="openCreateModal"
      >
        Tambah Kategori
      </button>
    </div>

    <!-- Success feedback -->
    <div
      v-if="categoryStore.successMessage"
      class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800"
    >
      <span>{{ categoryStore.successMessage }}</span>
      <button
        class="ml-auto text-green-600 hover:text-green-800"
        @click="categoryStore.clearSuccess()"
      >
        ✕
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3 bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
      <div class="flex-1">
        <input
          v-model="searchQuery"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
          placeholder="Cari kode atau nama kategori…"
          @input="onSearchInput"
        >
      </div>
      <div class="w-full sm:w-48">
        <select
          v-model="filterActive"
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white"
          @change="loadCategories"
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
    </div>

    <!-- Loading -->
    <BaseLoading v-if="categoryStore.isLoading && !categoryStore.items.length" />

    <!-- Forbidden -->
    <div
      v-else-if="isForbidden"
      class="text-center py-12 bg-white rounded-xl border border-gray-100 shadow-sm"
    >
      <p class="text-gray-500">
        Anda tidak memiliki izin untuk melihat data kategori.
      </p>
    </div>

    <!-- Error -->
    <BaseError
      v-else-if="categoryStore.error && !isForbidden && !categoryStore.items.length"
      :message="categoryStore.error"
    />

    <!-- Empty -->
    <BaseEmpty v-else-if="!categoryStore.items.length && !categoryStore.isLoading" />

    <!-- Table -->
    <div
      v-else
      class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
    >
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
          <thead class="bg-gray-50/50">
            <tr>
              <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Kode
              </th>
              <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Nama
              </th>
              <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">
                Aksi
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr
              v-for="item in categoryStore.items"
              :key="item.id"
              class="hover:bg-gray-50/50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">
                {{ item.code }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ item.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span
                  class="px-2.5 py-1 text-xs font-semibold rounded-full"
                  :class="item.is_active ? 'text-green-700 bg-green-50' : 'text-gray-600 bg-gray-100'"
                >
                  {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                <button
                  v-if="authStore.hasPermission('categories.update')"
                  class="text-blue-600 hover:text-blue-800 font-medium transition-colors"
                  @click="openEditModal(item)"
                >
                  Edit
                </button>
                <button
                  v-if="authStore.hasPermission('categories.change_status')"
                  class="font-medium transition-colors"
                  :class="item.is_active ? 'text-amber-600 hover:text-amber-800' : 'text-green-600 hover:text-green-800'"
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
      <div
        v-if="categoryStore.pagination && categoryStore.pagination.last_page > 1"
        class="flex items-center justify-between px-6 py-3 border-t border-gray-100"
      >
        <span class="text-sm text-gray-500">
          {{ categoryStore.pagination.total }} kategori
        </span>
        <div class="flex gap-1">
          <button
            v-for="page in categoryStore.pagination.last_page"
            :key="page"
            class="px-3 py-1 text-sm rounded-md transition-colors"
            :class="page === categoryStore.pagination.current_page
              ? 'bg-blue-600 text-white'
              : 'text-gray-600 hover:bg-gray-100'"
            @click="goToPage(page)"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>

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
      @cancel="showStatusModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import BaseLoading from '@shared/components/BaseLoading.vue';
import BaseError from '@shared/components/BaseError.vue';
import BaseEmpty from '@shared/components/BaseEmpty.vue';
import CategoryFormModal from '../components/CategoryFormModal.vue';
import CategoryStatusModal from '../components/CategoryStatusModal.vue';
import { useCategoryStore } from '../stores/use_category_store.js';
import { useAuthStore } from '../../auth/stores/use_auth_store.js';

const categoryStore = useCategoryStore();
const authStore = useAuthStore();

const searchQuery = ref('');
const filterActive = ref('');
const currentPage = ref(1);
const showFormModal = ref(false);
const editingCategory = ref(null);
const showStatusModal = ref(false);
const statusCategory = ref(null);

let searchTimer = null;

const isForbidden = computed(() =>
    categoryStore.error?.includes('tidak memiliki izin') ?? false,
);

onMounted(() => {
    loadCategories();
});

function loadCategories() {
    const params = { page: currentPage.value, per_page: 15 };
    if (searchQuery.value) params.search = searchQuery.value;
    if (filterActive.value) params.is_active = filterActive.value;
    categoryStore.fetchAll(params);
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentPage.value = 1;
        loadCategories();
    }, 400);
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
</script>
