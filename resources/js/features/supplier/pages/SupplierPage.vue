<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Master Supplier
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Kelola daftar supplier / pemasok barang.
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <button
          v-if="hasPermission('suppliers.create')"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
          @click="openCreateModal"
        >
          Tambah Supplier
        </button>
      </div>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row justify-between gap-4">
      <div class="w-full sm:max-w-xs">
        <input
          id="search"
          v-model="searchQuery"
          type="text"
          class="block w-full rounded-md border-gray-300 pl-3 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          placeholder="Cari kode atau nama..."
        >
      </div>
      <div class="flex gap-2">
        <select
          v-model="statusFilter"
          class="block rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
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
          class="block rounded-md border-gray-300 py-2 pl-3 pr-10 text-base focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
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

    <div
      v-if="store.error"
      class="mt-4 rounded-md bg-red-50 p-4"
    >
      <p class="text-sm font-medium text-red-800">
        {{ store.error }}
      </p>
    </div>
    <div
      v-if="store.successMessage"
      class="mt-4 rounded-md bg-green-50 p-4"
    >
      <p class="text-sm font-medium text-green-800">
        {{ store.successMessage }}
      </p>
    </div>

    <div class="mt-8 overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
      <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
          <tr>
            <th
              scope="col"
              class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"
            >
              Kode
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Nama Supplier
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Kontak
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Email
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
            >
              Status
            </th>
            <th
              scope="col"
              class="relative py-3.5 pl-3 pr-4 sm:pr-6"
            >
              <span class="sr-only">Aksi</span>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-if="store.isLoading && store.items.length === 0">
            <td
              colspan="6"
              class="py-10 text-center text-sm text-gray-500"
            >
              Memuat data...
            </td>
          </tr>
          <tr v-else-if="store.items.length === 0">
            <td
              colspan="6"
              class="py-10 text-center text-sm text-gray-500"
            >
              Tidak ada data supplier yang ditemukan.
            </td>
          </tr>
          <tr
            v-for="supplier in store.items"
            :key="supplier.id"
            :class="{ 'bg-gray-50': !supplier.is_active }"
          >
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-mono font-medium text-gray-900 sm:pl-6">
              {{ supplier.code }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
              {{ supplier.name }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <template v-if="supplier.contact_person !== undefined">
                {{ supplier.contact_person || '—' }}
                <span
                  v-if="supplier.phone"
                  class="block text-xs text-gray-400"
                >{{ supplier.phone }}</span>
              </template>
              <span
                v-else
                class="text-gray-400 italic text-xs"
              >—</span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              <span v-if="supplier.email !== undefined">{{ supplier.email || '—' }}</span>
              <span
                v-else
                class="text-gray-400 italic text-xs"
              >—</span>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm">
              <span
                class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                :class="supplier.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
              >
                {{ supplier.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
              <button
                v-if="hasPermission('suppliers.update')"
                class="text-indigo-600 hover:text-indigo-900 mr-4"
                @click="openEditModal(supplier)"
              >
                Ubah
              </button>
              <button
                v-if="hasPermission('suppliers.change_status')"
                :class="supplier.is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'"
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

let debounceTimer = null;
const debouncedSearch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchData(1), 400);
};

watch(searchQuery, debouncedSearch);
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
