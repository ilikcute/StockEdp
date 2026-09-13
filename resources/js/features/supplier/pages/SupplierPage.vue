<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <BasePageHeader
      title="Master Supplier"
      description="Kelola daftar supplier / pemasok barang."
    >
      <template #actions>
        <BaseButton
          v-if="hasPermission('suppliers.create')"
          @click="openCreateModal"
        >
          Tambah Supplier
        </BaseButton>
      </template>
    </BasePageHeader>

    <div class="mt-3 flex flex-col sm:flex-row justify-between gap-2.5">
      <div class="w-full sm:max-w-xs">
        <BaseSearchInput
          :model-value="searchQuery"
          placeholder="Cari kode atau nama..."
          @update:model-value="searchQuery = $event"
          @search="onSearch"
        />
      </div>
      <div class="flex gap-2">
        <select
          v-model="statusFilter"
          class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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
          class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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

    <div class="mt-4 overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar">
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
                <div class="leading-tight">{{ supplier.contact_person || '—' }}</div>
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
import BasePageHeader from '@/shared/components/BasePageHeader.vue';
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
