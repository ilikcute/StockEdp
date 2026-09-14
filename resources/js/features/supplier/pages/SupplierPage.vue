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
                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
              />
            </svg>
            Master Supplier
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Kelola daftar supplier / pemasok barang.
          </p>
        </div>

        <!-- Primary Controls (Search, Filters, Reset, Action Button) -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          <!-- Search Input -->
          <div class="w-full sm:w-52">
            <BaseSearchInput
              :model-value="searchQuery"
              placeholder="Cari kode atau nama..."
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
            v-if="searchQuery || statusFilter || sortBy !== 'created_at'"
            type="button"
            class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 shadow-2xs cursor-pointer whitespace-nowrap"
            title="Reset Filter"
            @click="resetFilters"
          >
            Reset
          </button>

          <!-- Action Button -->
          <BaseButton
            v-if="hasPermission('suppliers.create')"
            id="btn-create-supplier"
            size="sm"
            @click="openCreateModal"
          >
            + Tambah Supplier
          </BaseButton>
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
              Kode
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Nama Supplier
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Kontak
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Email
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
              colspan="7"
              class="py-8 text-center text-xs text-gray-500"
            >
              Memuat data...
            </td>
          </tr>
          <tr v-else-if="store.items.length === 0">
            <td
              colspan="7"
              class="py-8 text-center text-xs text-gray-500"
            >
              Tidak ada data supplier yang ditemukan.
            </td>
          </tr>
          <tr
            v-for="(supplier, index) in store.items"
            :key="supplier.id"
            class="hover:bg-gray-50/80 transition-colors"
            :class="{ 'bg-gray-50/50': !supplier.is_active }"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(store.pagination, index) }}
            </td>
            <td class="py-1.5 px-2 font-mono font-medium text-gray-900 text-[11px] whitespace-nowrap">
              {{ supplier.code }}
            </td>
            <td class="py-1.5 px-2 font-medium text-gray-900 text-[11px] whitespace-nowrap">
              {{ supplier.name }}
            </td>
            <td class="py-1.5 px-2 text-gray-500 text-[11px] whitespace-nowrap">
              <template v-if="supplier.contact_person !== undefined">
                <div class="leading-tight">
                  {{ supplier.contact_person || '—' }}
                </div>
                <span
                  v-if="supplier.phone"
                  class="block text-[10px] text-gray-400"
                >{{ supplier.phone }}</span>
              </template>
              <span
                v-else
                class="text-gray-400 italic text-xs"
              >—</span>
            </td>
            <td class="py-1.5 px-2 text-gray-500 text-[11px] whitespace-nowrap">
              <span v-if="supplier.email !== undefined">{{ supplier.email || '—' }}</span>
              <span
                v-else
                class="text-gray-400 italic text-xs"
              >—</span>
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap">
              <span
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider"
                :class="supplier.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
              >
                {{ supplier.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="py-1.5 px-2 text-right whitespace-nowrap text-[11px] space-x-2">
              <button
                v-if="hasPermission('suppliers.update')"
                class="font-semibold text-indigo-600 hover:text-indigo-900 hover:underline cursor-pointer"
                @click="openEditModal(supplier)"
              >
                Ubah
              </button>
              <button
                v-if="hasPermission('suppliers.change_status')"
                class="font-semibold hover:underline cursor-pointer"
                :class="supplier.is_active ? 'text-rose-600 hover:text-rose-900' : 'text-emerald-600 hover:text-emerald-900'"
                @click="openStatusModal(supplier)"
              >
                {{ supplier.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
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

    <SupplierFormModal
      :is-open="isFormModalOpen"
      :supplier="selectedSupplier"
      @close="closeFormModal"
      @saved="fetchData"
    />
    <SupplierStatusModal
      :is-open="isStatusModalOpen"
      :supplier="selectedSupplier"
      @close="closeStatusModal"
      @status-changed="fetchData"
    />
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useSupplierStore } from '../stores/use_supplier_store';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import BaseButton from '@/shared/components/BaseButton.vue';
import BaseSearchInput from '@/shared/components/BaseSearchInput.vue';
import BaseAlert from '@/shared/components/BaseAlert.vue';
import { rowNumber } from '@/shared/utils/formatters';
import SupplierFormModal from '../components/SupplierFormModal.vue';
import SupplierStatusModal from '../components/SupplierStatusModal.vue';
import BasePagination from '@/shared/components/BasePagination.vue';

const store = useSupplierStore();
const authStore = useAuthStore();

const searchQuery = ref('');
const statusFilter = ref('');
const sortBy = ref('created_at');

const isFormModalOpen = ref(false);
const isStatusModalOpen = ref(false);
const selectedSupplier = ref(null);

const hasPermission = (p) => authStore.hasPermission(p);

function onSearch(value) {
    searchQuery.value = value;
    fetchData(1);
}

function resetFilters() {
    searchQuery.value = '';
    statusFilter.value = '';
    sortBy.value = 'created_at';
    fetchData(1);
}

watch([statusFilter, sortBy], () => fetchData(1));

const fetchData = (page = 1) => {
    store.fetchSuppliers({
        page,
        search: searchQuery.value,
        is_active: statusFilter.value,
        sort_by: sortBy.value,
        sort_order: sortBy.value === 'created_at' ? 'desc' : 'asc',
    });
};

const changePage = (page) => {
    if (page >= 1 && page <= store.pagination.last_page) fetchData(page);
};

const openCreateModal = () => {
    selectedSupplier.value = null;
    isFormModalOpen.value = true;
};

const openEditModal = (supplier) => {
    selectedSupplier.value = { ...supplier };
    isFormModalOpen.value = true;
};

const closeFormModal = () => {
    isFormModalOpen.value = false;
    setTimeout(() => { selectedSupplier.value = null; }, 200);
};

const openStatusModal = (supplier) => {
    selectedSupplier.value = { ...supplier };
    isStatusModalOpen.value = true;
};

const closeStatusModal = () => {
    isStatusModalOpen.value = false;
    setTimeout(() => { selectedSupplier.value = null; }, 200);
};

onMounted(() => fetchData());
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
