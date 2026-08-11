<template>
  <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Satuan
        </h1>
        <p class="text-sm text-gray-500 mt-1">
          Kelola satuan ukuran produk inventory
        </p>
      </div>
      <div class="flex items-center gap-2">
        <button
          v-if="authStore.hasPermission('units.import')"
          id="btn-import-unit"
          class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 rounded-md transition-colors shadow-xs cursor-pointer"
          @click="showImportModal = true"
        >
          📥 Import CSV
        </button>
        <button
          v-if="authStore.hasPermission('units.create')"
          id="btn-create-unit"
          class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors shadow-sm cursor-pointer"
          @click="openCreateModal"
        >
          Tambah Satuan
        </button>
      </div>
    </div>

    <!-- Success feedback -->
    <div
      v-if="unitStore.successMessage"
      class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800"
    >
      <span>{{ unitStore.successMessage }}</span>
      <button
        class="ml-auto text-green-600 hover:text-green-800"
        @click="unitStore.clearSuccess()"
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
          placeholder="Cari kode, nama, atau simbol satuan…"
          @input="onSearchInput"
        >
      </div>
      <div class="w-full sm:w-48">
        <select
          v-model="filterActive"
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white"
          @change="loadUnits"
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
    <BaseLoading v-if="unitStore.isLoading && !unitStore.items.length" />

    <!-- Forbidden -->
    <div
      v-else-if="isForbidden"
      class="text-center py-12 bg-white rounded-xl border border-gray-100 shadow-sm"
    >
      <p class="text-gray-500">
        Anda tidak memiliki izin untuk melihat data satuan.
      </p>
    </div>

    <!-- Error -->
    <BaseError
      v-else-if="unitStore.error && !isForbidden && !unitStore.items.length"
      :message="unitStore.error"
    />

    <!-- Empty -->
    <BaseEmpty v-else-if="!unitStore.items.length && !unitStore.isLoading" />

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
                Simbol
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
              v-for="item in unitStore.items"
              :key="item.id"
              class="hover:bg-gray-50/50 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">
                {{ item.code }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ item.name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                <span class="px-2 py-0.5 bg-gray-100 text-gray-800 rounded font-mono text-xs">{{ item.symbol }}</span>
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
                  v-if="authStore.hasPermission('units.update')"
                  class="text-blue-600 hover:text-blue-800 font-medium transition-colors cursor-pointer"
                  @click="openEditModal(item)"
                >
                  Edit
                </button>
                <button
                  v-if="authStore.hasPermission('units.change_status')"
                  class="font-medium transition-colors cursor-pointer"
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
        v-if="unitStore.pagination && unitStore.pagination.total > 0"
        class="px-6 py-3 border-t border-gray-100"
      >
        <BasePagination
          :pagination="unitStore.pagination"
          :loading="unitStore.isLoading"
          @change="goToPage"
        />
      </div>
    </div>

    <!-- Modals -->
    <UnitFormModal
      :visible="showFormModal"
      :unit="editingUnit"
      :errors="unitStore.validationErrors"
      :loading="unitStore.isLoading"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    />

    <UnitStatusModal
      v-model="showStatusModal"
      :unit="statusUnit"
      :loading="unitStore.isLoading"
      @confirm="handleStatusConfirm"
      @cancel="showStatusModal = false"
    />

    <MasterDataImportModal
      :show="showImportModal"
      type="units"
      title="Import Satuan Masal"
      @close="showImportModal = false"
      @imported="onImportSuccess"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import BaseLoading from '@shared/components/BaseLoading.vue';
import BaseError from '@shared/components/BaseError.vue';
import BaseEmpty from '@shared/components/BaseEmpty.vue';
import BasePagination from '@shared/components/BasePagination.vue';
import UnitFormModal from '../components/UnitFormModal.vue';
import UnitStatusModal from '../components/UnitStatusModal.vue';
import MasterDataImportModal from '../../master_data_import/components/MasterDataImportModal.vue';
import { useUnitStore } from '../stores/use_unit_store.js';
import { useAuthStore } from '../../auth/stores/use_auth_store.js';

const unitStore = useUnitStore();
const authStore = useAuthStore();

const searchQuery = ref('');
const filterActive = ref('');
const currentPage = ref(1);
const showFormModal = ref(false);
const showImportModal = ref(false);
const editingUnit = ref(null);
const showStatusModal = ref(false);
const statusUnit = ref(null);

let searchTimer = null;

const isForbidden = computed(() =>
    unitStore.error?.includes('tidak memiliki izin') ?? false,
);

onMounted(() => {
    loadUnits();
});

function loadUnits() {
    const params = { page: currentPage.value, per_page: 15 };
    if (searchQuery.value) params.search = searchQuery.value;
    if (filterActive.value) params.is_active = filterActive.value;
    unitStore.fetchAll(params);
}

function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentPage.value = 1;
        loadUnits();
    }, 400);
}

function goToPage(page) {
    currentPage.value = page;
    loadUnits();
}

function openCreateModal() {
    editingUnit.value = null;
    unitStore.clearErrors();
    showFormModal.value = true;
}

function openEditModal(unit) {
    editingUnit.value = unit;
    unitStore.clearErrors();
    showFormModal.value = true;
}

function closeFormModal() {
    showFormModal.value = false;
    editingUnit.value = null;
    unitStore.clearErrors();
}

async function handleFormSubmit(formData) {
    try {
        if (editingUnit.value) {
            await unitStore.update(editingUnit.value.id, formData);
        } else {
            await unitStore.create(formData);
        }
        closeFormModal();
        loadUnits();
    } catch {
        // Errors handled by store
    }
}

function openStatusModal(unit) {
    statusUnit.value = unit;
    showStatusModal.value = true;
}

async function handleStatusConfirm() {
    if (!statusUnit.value) return;
    try {
        await unitStore.changeStatus(statusUnit.value.id, !statusUnit.value.is_active);
        showStatusModal.value = false;
        statusUnit.value = null;
        loadUnits();
    } catch {
        // Errors handled by store
    }
}

function onImportSuccess() {
    loadUnits();
}
</script>
