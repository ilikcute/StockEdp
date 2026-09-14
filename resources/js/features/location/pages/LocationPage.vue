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
                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
              />
            </svg>
            Master Lokasi
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Kelola daftar lokasi penyimpanan barang.
          </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          <BaseButton
            v-if="hasPermission('locations.import')"
            id="btn-import-location"
            variant="secondary"
            size="sm"
            @click="isImportModalOpen = true"
          >
            📥 Import CSV
          </BaseButton>
          <BaseButton
            v-if="hasPermission('locations.create')"
            id="btn-create-location"
            size="sm"
            @click="openCreateModal"
          >
            + Tambah Lokasi
          </BaseButton>
        </div>
      </div>

      <!-- Filters & Search Row -->
      <div class="flex flex-col sm:flex-row justify-between gap-2 pt-2 border-t border-gray-100">
        <div class="w-full sm:max-w-xs">
          <BaseSearchInput
            :model-value="searchQuery"
            placeholder="Cari kode atau nama..."
            @update:model-value="searchQuery = $event"
            @search="onSearch"
          />
        </div>
        <div class="w-full sm:max-w-xs flex gap-2">
          <select
            v-model="statusFilter"
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
              Nama Lokasi
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Tipe Lokasi
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Alamat & Telp
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
              Tidak ada data lokasi yang ditemukan.
            </td>
          </tr>
          <tr
            v-for="(location, index) in store.items"
            :key="location.id"
            class="hover:bg-gray-50/80 transition-colors"
            :class="{'bg-gray-50/50': !location.is_active}"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(store.pagination, index) }}
            </td>
            <td class="py-1.5 px-2 font-mono font-medium text-gray-900 text-[11px] whitespace-nowrap">
              {{ location.code }}
            </td>
            <td class="py-1.5 px-2 font-medium text-gray-900 text-[11px] whitespace-nowrap">
              {{ location.name }}
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap text-[11px]">
              <span
                v-if="location.type === 'FIELD_PERSONNEL'"
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200"
              >
                Teknisi: {{ location.user_name || 'Belum ditugaskan' }}
              </span>
              <span
                v-else-if="location.type === 'DAMAGED_STORAGE'"
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200"
              >
                Gudang Afkir
              </span>
              <span
                v-else
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-gray-100 text-gray-700 border border-gray-200"
              >
                Gudang Induk
              </span>
            </td>
            <td class="py-1.5 px-2 text-gray-500 text-[11px] whitespace-nowrap">
              {{ location.address || '-' }} <span v-if="location.phone">({{ location.phone }})</span>
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap">
              <span
                class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider" 
                :class="location.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
              >
                {{ location.is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="py-1.5 px-2 text-right whitespace-nowrap text-[11px] space-x-2">
              <button 
                v-if="hasPermission('locations.update')"
                class="font-semibold text-indigo-600 hover:text-indigo-900 hover:underline cursor-pointer"
                @click="openEditModal(location)"
              >
                Ubah
              </button>
              <button 
                v-if="hasPermission('locations.change_status')"
                class="font-semibold hover:underline cursor-pointer"
                :class="location.is_active ? 'text-rose-600 hover:text-rose-900' : 'text-emerald-600 hover:text-emerald-900'"
                @click="openStatusModal(location)"
              >
                {{ location.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
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
import BaseButton from '@/shared/components/BaseButton.vue';
import BaseSearchInput from '@/shared/components/BaseSearchInput.vue';
import BaseAlert from '@/shared/components/BaseAlert.vue';
import { rowNumber } from '@/shared/utils/formatters';
import LocationFormModal from '../components/LocationFormModal.vue';
import LocationStatusModal from '../components/LocationStatusModal.vue';
import MasterDataImportModal from '../../master_data_import/components/MasterDataImportModal.vue';
import BasePagination from '@/shared/components/BasePagination.vue';

const store = useLocationStore();
const authStore = useAuthStore();

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
