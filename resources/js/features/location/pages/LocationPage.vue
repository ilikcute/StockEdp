<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Master Lokasi
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Kelola daftar lokasi penyimpanan barang.
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex items-center gap-2">
        <button
          v-if="hasPermission('locations.import')"
          id="btn-import-location"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto cursor-pointer"
          @click="isImportModalOpen = true"
        >
          📥 Import CSV
        </button>
        <button 
          v-if="hasPermission('locations.create')"
          id="btn-create-location"
          type="button" 
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto cursor-pointer"
          @click="openCreateModal"
        >
          Tambah Lokasi
        </button>
      </div>
    </div>

    <div class="mt-6 flex flex-col sm:flex-row justify-between gap-4">
      <div class="w-full sm:max-w-xs">
        <label
          for="search"
          class="sr-only"
        >Cari</label>
        <div class="relative rounded-md shadow-xs">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg
              class="h-5 w-5 text-gray-400"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
          <input 
            id="search" 
            v-model="searchQuery" 
            type="text" 
            name="search"
            class="block w-full rounded-md border border-gray-300 bg-white pl-10 pr-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" 
            placeholder="Cari kode atau nama..."
          >
        </div>
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

    <div
      v-if="store.error"
      class="mt-4 rounded-md bg-red-50 p-4 border border-red-200"
    >
      <p class="text-sm font-medium text-red-800">
        {{ store.error }}
      </p>
    </div>
        
    <div
      v-if="store.successMessage"
      class="mt-4 rounded-md bg-green-50 p-4 border border-green-200 flex items-center justify-between"
    >
      <p class="text-sm font-medium text-green-800">
        {{ store.successMessage }}
      </p>
      <button
        class="text-green-600 hover:text-green-800 font-bold"
        @click="store.clearMessages()"
      >
        ✕
      </button>
    </div>

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
                    Kode
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
                  >
                    Nama Lokasi
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
                  >
                    Alamat & Telp
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
                    Tidak ada data lokasi yang ditemukan.
                  </td>
                </tr>
                <tr
                  v-for="(location, index) in store.items"
                  :key="location.id"
                  :class="{'bg-gray-50': !location.is_active}"
                >
                  <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-center text-gray-500 sm:pl-6">
                    {{ (store.pagination?.from ? store.pagination.from + index : index + 1) }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
                    {{ location.code }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ location.name }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ location.address }} <span v-if="location.phone">({{ location.phone }})</span>
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    <span
                      class="inline-flex rounded-full px-2 text-xs font-semibold leading-5" 
                      :class="location.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    >
                      {{ location.is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                  </td>
                  <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                    <button 
                      v-if="hasPermission('locations.update')"
                      class="text-indigo-600 hover:text-indigo-900 mr-4"
                      @click="openEditModal(location)"
                    >
                      Ubah
                    </button>
                    <button 
                      v-if="hasPermission('locations.change_status')"
                      :class="location.is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900'"
                      @click="openStatusModal(location)"
                    >
                      {{ location.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
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

    <LocationFormModal 
      :is-open="isFormModalOpen" 
      :location="selectedLocation" 
      @close="closeFormModal" 
      @saved="fetchData" 
    />
        
    <LocationStatusModal 
      :is-open="isStatusModalOpen" 
      :location="selectedLocation" 
      @close="closeStatusModal" 
      @status-changed="fetchData" 
    />

    <MasterDataImportModal
      :show="isImportModalOpen"
      type="locations"
      title="Import Lokasi Masal"
      @close="isImportModalOpen = false"
      @imported="fetchData(1)"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useLocationStore } from '../stores/use_location_store';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import LocationFormModal from '../components/LocationFormModal.vue';
import LocationStatusModal from '../components/LocationStatusModal.vue';
import MasterDataImportModal from '../../master_data_import/components/MasterDataImportModal.vue';
import BasePagination from '@/shared/components/BasePagination.vue';

const store = useLocationStore();
const authStore = useAuthStore();

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const searchQuery = ref('');
const statusFilter = ref('');
const sortBy = ref('created_at');

const isFormModalOpen = ref(false);
const isStatusModalOpen = ref(false);
const isImportModalOpen = ref(false);
const selectedLocation = ref(null);

const hasPermission = (permission) => {
    return authStore.hasPermission(permission);
};

const fetchData = (page = 1) => {
    store.fetchLocations({
        page,
        search: searchQuery.value,
        is_active: statusFilter.value,
        sort_by: sortBy.value,
        sort_order: sortBy.value === 'created_at' ? 'desc' : 'asc'
    });
};

const debouncedSearch = debounce(() => {
    fetchData(1);
}, 400);

watch(searchQuery, () => {
    debouncedSearch();
});

watch([statusFilter, sortBy], () => {
    fetchData(1);
});

const changePage = (page) => {
    if (page >= 1 && page <= store.pagination.last_page) {
        fetchData(page);
    }
};

const openCreateModal = () => {
    selectedLocation.value = null;
    isFormModalOpen.value = true;
};

const openEditModal = (location) => {
    selectedLocation.value = Object.assign({}, location);
    isFormModalOpen.value = true;
};

const closeFormModal = () => {
    isFormModalOpen.value = false;
    setTimeout(() => {
        selectedLocation.value = null;
    }, 200);
};

const openStatusModal = (location) => {
    selectedLocation.value = Object.assign({}, location);
    isStatusModalOpen.value = true;
};

const closeStatusModal = () => {
    isStatusModalOpen.value = false;
    setTimeout(() => {
        selectedLocation.value = null;
    }, 200);
};

onMounted(() => {
    fetchData();
});
</script>
