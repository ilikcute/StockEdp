<template>
  <div class="space-y-3">
    <!-- TOP Header & Filter Toolbar Compact -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs space-y-2.5">
      <!-- Primary Controls Row -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <h1 class="text-base font-bold text-gray-900 tracking-tight flex items-center gap-1.5">
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
                d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"
              />
            </svg>
            Master Satuan
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Kelola daftar satuan ukuran produk inventory.
          </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          <BaseButton
            v-if="authStore.hasPermission('units.import')"
            id="btn-import-unit"
            variant="secondary"
            size="sm"
            @click="showImportModal = true"
          >
            📥 Import CSV
          </BaseButton>
          <BaseButton
            v-if="authStore.hasPermission('units.create')"
            id="btn-create-unit"
            size="sm"
            @click="openCreateModal"
          >
            + Tambah Satuan
          </BaseButton>
        </div>
      </div>

      <!-- Filters & Search Row -->
      <div class="flex flex-col sm:flex-row justify-between gap-2 pt-2 border-t border-gray-100">
        <div class="w-full sm:max-w-xs">
          <BaseSearchInput
            :model-value="searchQuery"
            placeholder="Cari kode, nama, atau simbol..."
            @update:model-value="searchQuery = $event"
            @search="onSearch"
          />
        </div>
        <div class="flex gap-2 flex-wrap sm:flex-nowrap">
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
        </div>
      </div>
    </div>

    <!-- Alerts -->
    <BaseAlert
      v-if="unitStore.error && !isForbidden"
      :message="unitStore.error"
    />
    <BaseAlert
      v-if="unitStore.successMessage"
      variant="success"
      :message="unitStore.successMessage"
      dismissible
      @dismiss="unitStore.clearSuccess()"
    />

    <!-- Forbidden Message -->
    <div
      v-if="isForbidden"
      class="text-center py-12 bg-white rounded-xl border border-gray-200 shadow-2xs"
    >
      <p class="text-gray-500 text-xs">
        Anda tidak memiliki izin untuk melihat data satuan.
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
              Nama Satuan
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Simbol
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
          <tr v-if="unitStore.isLoading && !unitStore.items.length">
            <td
              colspan="7"
              class="py-8 text-center text-xs text-gray-500"
            >
              Memuat data...
            </td>
          </tr>
          <tr v-else-if="!unitStore.items.length">
            <td
              colspan="7"
              class="py-8 text-center text-xs text-gray-500"
            >
              Tidak ada data satuan yang ditemukan.
            </td>
          </tr>
          <tr
            v-for="(item, index) in unitStore.items"
            :key="item.id"
            class="hover:bg-gray-50/80 transition-colors"
            :class="{ 'bg-gray-50/50': !item.is_active }"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(unitStore.pagination, index) }}
            </td>
            <td class="py-1.5 px-2 font-mono font-medium text-gray-900 text-[11px] whitespace-nowrap">
              {{ item.code }}
            </td>
            <td class="py-1.5 px-2 font-medium text-gray-900 text-[11px] whitespace-nowrap">
              {{ item.name }}
            </td>
            <td class="py-1.5 px-2 text-gray-600 font-mono text-[11px] whitespace-nowrap">
              {{ item.symbol || '-' }}
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
                v-if="authStore.hasPermission('units.update')"
                class="font-semibold text-indigo-600 hover:text-indigo-900 hover:underline cursor-pointer"
                @click="openEditModal(item)"
              >
                Ubah
              </button>
              <button
                v-if="authStore.hasPermission('units.change_status')"
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
      :pagination="unitStore.pagination"
      :loading="unitStore.isLoading"
      @change="goToPage"
    />

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
import { computed, onMounted, ref, watch } from 'vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import BaseButton from '@/shared/components/BaseButton.vue';
import BaseSearchInput from '@/shared/components/BaseSearchInput.vue';
import BaseAlert from '@/shared/components/BaseAlert.vue';
import { rowNumber } from '@/shared/utils/formatters.js';
import UnitFormModal from '../components/UnitFormModal.vue';
import UnitStatusModal from '../components/UnitStatusModal.vue';
import MasterDataImportModal from '../../master_data_import/components/MasterDataImportModal.vue';
import { useUnitStore } from '../stores/use_unit_store.js';
import { useAuthStore } from '../../auth/stores/use_auth_store.js';

const unitStore = useUnitStore();
const authStore = useAuthStore();

const searchQuery = ref('');
const filterActive = ref('');
const sortBy = ref('created_at');
const currentPage = ref(1);

const showFormModal = ref(false);
const showImportModal = ref(false);
const editingUnit = ref(null);
const showStatusModal = ref(false);
const statusUnit = ref(null);

const isForbidden = computed(() =>
    unitStore.error?.includes('tidak memiliki izin') ?? false,
);

function onSearch(value) {
    searchQuery.value = value;
    currentPage.value = 1;
    loadUnits();
}

watch([filterActive, sortBy], () => {
    currentPage.value = 1;
    loadUnits();
});

onMounted(() => {
    loadUnits();
});

function loadUnits() {
    const params = {
        page: currentPage.value,
        per_page: 15,
        sort_by: sortBy.value,
        sort_order: sortBy.value === 'created_at' ? 'desc' : 'asc',
    };
    if (searchQuery.value) params.search = searchQuery.value;
    if (filterActive.value) params.is_active = filterActive.value;
    unitStore.fetchAll(params);
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
