<template>
  <div class="space-y-3">
    <!-- TOP Header & Filter Toolbar Compact -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs space-y-2.5">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <!-- Title & Subtitle -->
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
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
              />
            </svg>
            Master Departemen
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Kelola master divisi & departemen tujuan pengeluaran barang operasional kantor/toko.
          </p>
        </div>

        <!-- Primary Controls -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          <!-- Search Input -->
          <div class="w-full sm:w-48">
            <BaseSearchInput
              :model-value="searchQuery"
              placeholder="Cari kode, nama..."
              size="sm"
              @update:model-value="searchQuery = $event"
              @search="onSearch"
            />
          </div>

          <!-- Status Filter -->
          <select
            v-model="statusFilter"
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
            <option value="name">
              Nama (A-Z)
            </option>
            <option value="code">
              Kode
            </option>
            <option value="created_at">
              Terbaru
            </option>
          </select>

          <!-- Reset Button -->
          <button
            v-if="searchQuery || statusFilter || sortBy !== 'name'"
            type="button"
            class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 shadow-2xs cursor-pointer whitespace-nowrap"
            title="Reset Filter"
            @click="resetFilters"
          >
            Reset
          </button>

          <!-- Action Button -->
          <BaseButton
            v-if="hasPermission('departments.create')"
            id="btn-create-department"
            size="sm"
            @click="openCreateModal"
          >
            + Tambah Departemen
          </BaseButton>
        </div>
      </div>
    </div>

    <BaseAlert
      v-if="departmentStore.error"
      :message="departmentStore.error"
    />
    <BaseAlert
      v-if="departmentStore.successMessage"
      variant="success"
      :message="departmentStore.successMessage"
      dismissible
      @dismiss="departmentStore.clearMessages()"
    />

    <!-- Compact Table -->
    <div class="overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar">
      <table class="w-full text-left text-xs border-collapse">
        <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10">
          <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
            <th
              scope="col"
              class="py-1.5 px-2 w-10 text-center"
            >
              No.
            </th>
            <th
              scope="col"
              class="py-1.5 px-3 w-32 whitespace-nowrap"
            >
              Kode
            </th>
            <th
              scope="col"
              class="py-1.5 px-3 whitespace-nowrap"
            >
              Nama Departemen
            </th>
            <th
              scope="col"
              class="py-1.5 px-3"
            >
              Keterangan
            </th>
            <th
              scope="col"
              class="py-1.5 px-3 w-24 whitespace-nowrap text-center"
            >
              Status
            </th>
            <th
              scope="col"
              class="py-1.5 px-3 w-28 text-right whitespace-nowrap"
            >
              Aksi
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="departmentStore.isLoading && departmentStore.items.length === 0">
            <td
              colspan="6"
              class="py-8 text-center text-xs text-gray-500"
            >
              Memuat data departemen...
            </td>
          </tr>
          <tr v-else-if="departmentStore.items.length === 0">
            <td
              colspan="6"
              class="py-8 text-center text-xs text-gray-500"
            >
              Tidak ada data departemen yang ditemukan.
            </td>
          </tr>
          <tr
            v-for="(dept, index) in departmentStore.items"
            :key="dept.id"
            class="hover:bg-gray-50/80 transition-colors"
            :class="{'bg-gray-50/50': !dept.is_active}"
          >
            <td class="py-1.5 px-2 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(departmentStore.pagination, index) }}
            </td>
            <td class="py-1.5 px-3 font-mono font-bold text-indigo-700 text-[11px] whitespace-nowrap">
              {{ dept.code }}
            </td>
            <td class="py-1.5 px-3 font-medium text-gray-900 text-[11px] whitespace-nowrap">
              {{ dept.name }}
            </td>
            <td class="py-1.5 px-3 text-gray-500 text-[11px] max-w-sm truncate">
              {{ dept.description || '-' }}
            </td>
            <td class="py-1.5 px-3 text-center whitespace-nowrap">
              <span
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider"
                :class="dept.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
              >
                {{ dept.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="py-1.5 px-3 text-right whitespace-nowrap text-[11px] space-x-2">
              <button
                v-if="hasPermission('departments.update')"
                class="font-semibold text-indigo-600 hover:text-indigo-900 hover:underline cursor-pointer"
                @click="openEditModal(dept)"
              >
                Ubah
              </button>
              <button
                v-if="hasPermission('departments.change_status')"
                class="font-semibold hover:underline cursor-pointer"
                :class="dept.is_active ? 'text-rose-600 hover:text-rose-900' : 'text-emerald-600 hover:text-emerald-900'"
                @click="openStatusModal(dept)"
              >
                {{ dept.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <BasePagination
      :pagination="departmentStore.pagination"
      :loading="departmentStore.isLoading"
      @change="changePage"
    />

    <DepartmentFormModal
      :is-open="isFormModalOpen"
      :department-data="selectedDepartment"
      @close="closeFormModal"
      @saved="fetchData"
    />

    <DepartmentStatusModal
      :is-open="isStatusModalOpen"
      :department-data="selectedDepartment"
      @close="closeStatusModal"
      @status-changed="fetchData"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useDepartmentStore } from '../stores/use_department_store';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import BaseButton from '@/shared/components/BaseButton.vue';
import BaseSearchInput from '@/shared/components/BaseSearchInput.vue';
import BaseAlert from '@/shared/components/BaseAlert.vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import { rowNumber } from '@/shared/utils/formatters';
import DepartmentFormModal from '../components/DepartmentFormModal.vue';
import DepartmentStatusModal from '../components/DepartmentStatusModal.vue';

const departmentStore = useDepartmentStore();
const authStore = useAuthStore();

const searchQuery = ref('');
const statusFilter = ref('');
const sortBy = ref('name');

const isFormModalOpen = ref(false);
const isStatusModalOpen = ref(false);
const selectedDepartment = ref(null);

const hasPermission = (permission) => {
    return authStore.hasPermission(permission);
};

const fetchData = (page = 1) => {
    departmentStore.fetchDepartments({
        page,
        search: searchQuery.value,
        is_active: statusFilter.value,
        sort_by: sortBy.value,
        sort_order: sortBy.value === 'created_at' ? 'desc' : 'asc',
    });
};

function onSearch(value) {
    searchQuery.value = value;
    fetchData(1);
}

function resetFilters() {
    searchQuery.value = '';
    statusFilter.value = '';
    sortBy.value = 'name';
    fetchData(1);
}

function changePage(page) {
    fetchData(page);
}

function openCreateModal() {
    selectedDepartment.value = null;
    isFormModalOpen.value = true;
}

function openEditModal(dept) {
    selectedDepartment.value = { ...dept };
    isFormModalOpen.value = true;
}

function closeFormModal() {
    isFormModalOpen.value = false;
    selectedDepartment.value = null;
}

function openStatusModal(dept) {
    selectedDepartment.value = { ...dept };
    isStatusModalOpen.value = true;
}

function closeStatusModal() {
    isStatusModalOpen.value = false;
    selectedDepartment.value = null;
}

watch([statusFilter, sortBy], () => {
    fetchData(1);
});

onMounted(() => {
    fetchData(1);
});
</script>
