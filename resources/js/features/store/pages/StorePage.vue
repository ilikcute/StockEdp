<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <BasePageHeader
      title="Master Toko"
      description="Kelola data toko / unit kerja untuk tujuan alokasi & penggantian unit."
    >
      <template #actions>
        <BaseButton
          v-if="hasPermission('stores.import')"
          id="btn-import-store"
          variant="secondary"
          @click="isImportModalOpen = true"
        >
          📥 Import CSV
        </BaseButton>
        <BaseButton
          v-if="hasPermission('stores.create')"
          id="btn-create-store"
          @click="openCreateModal"
        >
          Tambah Toko
        </BaseButton>
      </template>
    </BasePageHeader>

    <div class="mt-6 flex flex-col sm:flex-row justify-between gap-4">
      <div class="w-full sm:max-w-xs">
        <BaseSearchInput
          :model-value="searchQuery"
          placeholder="Cari kode, nama, atau alamat..."
          @update:model-value="searchQuery = $event"
          @search="onSearch"
        />
      </div>
      <div class="w-full sm:max-w-xs flex gap-2">
        <select 
          v-model="statusFilter"
          class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
          class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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

    <div class="mt-8 flex flex-col">
      <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
          <div class="overflow-x-auto touch-scroll shadow-xs border border-gray-200 rounded-xl bg-white">
            <table class="min-w-full divide-y divide-gray-200">
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
                    Kode Toko
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
                  >
                    Nama Toko
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
                  >
                    Telepon
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
                  >
                    Alamat
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
                <tr v-if="store.isLoading && store.items.length === 0">
                  <td
                    colspan="7"
                    class="py-10 text-center text-sm text-gray-500"
                  >
                    Memuat data...
                  </td>
                </tr>
                <tr v-else-if="store.items.length === 0">
                  <td
                    colspan="7"
                    class="py-10 text-center text-sm text-gray-500"
                  >
                    Tidak ada data toko yang ditemukan.
                  </td>
                </tr>
                <tr
                  v-for="(storeItem, index) in store.items"
                  :key="storeItem.id"
                  :class="{'bg-gray-50': !storeItem.is_active}"
                >
                  <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-center text-gray-500 sm:pl-6">
                    {{ rowNumber(store.pagination, index) }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold text-gray-900">
                    {{ storeItem.code }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                    {{ storeItem.name }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ storeItem.phone || '-' }}
                  </td>
                  <td class="px-3 py-4 text-sm text-gray-500 max-w-xs truncate">
                    {{ storeItem.address || '-' }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    <span
                      class="inline-flex rounded-full px-2 text-xs font-semibold leading-5" 
                      :class="storeItem.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    >
                      {{ storeItem.is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                  </td>
                  <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                    <button 
                      v-if="hasPermission('stores.update')"
                      class="text-indigo-600 hover:text-indigo-900 mr-4"
                      @click="openEditModal(storeItem)"
                    >
                      Ubah
                    </button>
                    <button 
                      v-if="hasPermission('stores.change_status')"
                      :class="storeItem.is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'"
                      @click="openStatusModal(storeItem)"
                    >
                      {{ storeItem.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
        
    <BasePagination
      :pagination="store.pagination"
      :loading="store.isLoading"
      @change="changePage"
    />

    <StoreFormModal 
      :is-open="isFormModalOpen" 
      :store-data="selectedStore" 
      @close="closeFormModal" 
      @saved="fetchData" 
    />
        
    <StoreStatusModal 
      :is-open="isStatusModalOpen" 
      :store-data="selectedStore" 
      @close="closeStatusModal" 
      @status-changed="fetchData" 
    />

    <MasterDataImportModal
      :show="isImportModalOpen"
      type="stores"
      title="Import Toko Masal"
      @close="isImportModalOpen = false"
      @imported="fetchData(1)"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useStoreStore } from '../stores/use_store_store';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import BaseButton from '@/shared/components/BaseButton.vue';
import BaseSearchInput from '@/shared/components/BaseSearchInput.vue';
import BaseAlert from '@/shared/components/BaseAlert.vue';
import BasePageHeader from '@/shared/components/BasePageHeader.vue';
import { rowNumber } from '@/shared/utils/formatters';
import StoreFormModal from '../components/StoreFormModal.vue';
import StoreStatusModal from '../components/StoreStatusModal.vue';
import MasterDataImportModal from '../../master_data_import/components/MasterDataImportModal.vue';
import BasePagination from '@/shared/components/BasePagination.vue';

const store = useStoreStore();
const authStore = useAuthStore();

const searchQuery = ref('');
const statusFilter = ref('');
const sortBy = ref('created_at');

const isFormModalOpen = ref(false);
const isStatusModalOpen = ref(false);
const isImportModalOpen = ref(false);
const selectedStore = ref(null);

const hasPermission = (permission) => {
    return authStore.hasPermission(permission);
};

const fetchData = (page = 1) => {
    store.fetchStores({
        page,
        search: searchQuery.value,
        is_active: statusFilter.value,
        sort_by: sortBy.value,
        sort_order: sortBy.value === 'created_at' ? 'desc' : 'asc'
    });
};

function onSearch(value) {
    searchQuery.value = value;
    fetchData(1);
}

watch([statusFilter, sortBy], () => {
    fetchData(1);
});

const changePage = (page) => {
    if (page >= 1 && page <= store.pagination.last_page) {
        fetchData(page);
    }
};

const openCreateModal = () => {
    selectedStore.value = null;
    isFormModalOpen.value = true;
};

const openEditModal = (item) => {
    selectedStore.value = Object.assign({}, item);
    isFormModalOpen.value = true;
};

const closeFormModal = () => {
    isFormModalOpen.value = false;
    setTimeout(() => {
        selectedStore.value = null;
    }, 200);
};

const openStatusModal = (item) => {
    selectedStore.value = Object.assign({}, item);
    isStatusModalOpen.value = true;
};

const closeStatusModal = () => {
    isStatusModalOpen.value = false;
    setTimeout(() => {
        selectedStore.value = null;
    }, 200);
};

onMounted(() => {
    fetchData();
});
</script>
