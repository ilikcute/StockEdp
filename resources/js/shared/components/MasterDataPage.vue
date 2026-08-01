<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ title }}
        </h1>
        <p class="text-sm text-gray-500 mt-1">
          {{ subtitle }}
        </p>
      </div>
      <button
        v-if="canManage"
        id="btn-create"
        class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors shadow-sm cursor-pointer"
        @click="openCreateModal"
      >
        Tambah {{ entityName }}
      </button>
    </div>

    <!-- Filters & Search -->
    <div class="flex flex-col sm:flex-row gap-3 bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
      <div class="flex-1 relative">
        <input
          v-model="searchQuery"
          type="text"
          class="w-full pl-3 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
          :placeholder="`Cari ${entityName.toLowerCase()}…`"
          @input="onSearchInput"
        >
      </div>
      <div class="w-full sm:w-48">
        <select
          v-model="filterActive"
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 bg-white"
          @change="onFilter"
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
    <BaseLoading v-if="store.isLoading" />

    <!-- Error -->
    <BaseError
      v-else-if="store.error && !store.isLoading"
      :message="store.error"
    />

    <!-- Table -->
    <div
      v-else
      class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden"
    >
      <div class="overflow-x-auto">
        <BaseEmpty v-if="!store.items.length" />
        <table
          v-else
          class="min-w-full divide-y divide-gray-100"
        >
          <thead class="bg-gray-50/50">
            <tr>
              <slot name="columns" />
              <th class="px-6 py-3.5 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">
                Aksi
              </th>
            </tr>
          </thead>
          <slot name="tbody">
            <tbody class="divide-y divide-gray-100 bg-white">
              <tr
                v-for="item in store.items"
                :key="item.id"
                class="hover:bg-gray-50/30 transition-colors"
              >
                <slot
                  name="row"
                  :item="item"
                />
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                  <button
                    v-if="canManage"
                    class="text-blue-600 hover:text-blue-900 font-semibold cursor-pointer"
                    @click="openEditModal(item)"
                  >
                    Edit
                  </button>
                  <button
                    v-if="canManage"
                    class="text-red-600 hover:text-red-900 font-semibold cursor-pointer"
                    @click="confirmDelete(item)"
                  >
                    Hapus
                  </button>
                </td>
              </tr>
            </tbody>
          </slot>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div
      v-if="store.pagination && store.pagination.last_page > 1"
      class="flex items-center justify-between border-t border-gray-100 pt-4"
    >
      <button
        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
        :disabled="currentPage <= 1"
        @click="changePage(currentPage - 1)"
      >
        Sebelumnya
      </button>
      <span class="text-sm text-gray-500">
        Halaman {{ currentPage }} dari {{ store.pagination.last_page }}
      </span>
      <button
        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
        :disabled="currentPage >= store.pagination.last_page"
        @click="changePage(currentPage + 1)"
      >
        Berikutnya
      </button>
    </div>

    <!-- Create / Edit Modal -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs"
        @click.self="closeModal"
      >
        <div
          class="w-full max-w-lg bg-white rounded-xl shadow-xl border border-gray-100 flex flex-col max-h-[90vh]"
          role="dialog"
          aria-modal="true"
        >
          <!-- Modal Header -->
          <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900">
              {{ editingItem ? `Edit ${entityName}` : `Tambah ${entityName}` }}
            </h2>
            <button
              class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer"
              @click="closeModal"
            >
              &times;
            </button>
          </div>

          <!-- Modal Body -->
          <div class="p-6 overflow-y-auto space-y-4">
            <slot
              name="form"
              :form="form"
              :errors="store.validationErrors"
              :is-edit="!!editingItem"
            />
          </div>

          <!-- Modal Footer -->
          <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 rounded-b-xl flex justify-end gap-3">
            <button
              class="px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 rounded-md transition-colors cursor-pointer"
              :disabled="store.isLoading"
              @click="closeModal"
            >
              Batal
            </button>
            <button
              id="btn-submit"
              class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors shadow-sm disabled:opacity-50 cursor-pointer"
              :disabled="store.isLoading"
              @click="submitForm"
            >
              <span v-if="store.isLoading">Menyimpan…</span>
              <span v-else>Simpan</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Delete Confirmation -->
    <BaseConfirmation
      v-model="showDeleteConfirm"
      title="Hapus Data"
      :description="`Yakin ingin menghapus ${entityName.toLowerCase()} ini? Tindakan tidak dapat dibatalkan.`"
      confirm-label="Ya, Hapus"
      :loading="store.isLoading"
      danger
      @confirm="executeDelete"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import BaseLoading from '@shared/components/BaseLoading.vue';
import BaseError from '@shared/components/BaseError.vue';
import BaseEmpty from '@shared/components/BaseEmpty.vue';
import BaseConfirmation from '@shared/components/BaseConfirmation.vue';

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    entityName: { type: String, required: true },
    store: { type: Object, required: true },
    canManage: { type: Boolean, default: false },
    defaultForm: { type: Object, required: true },
});

const emit = defineEmits(['load', 'submit', 'delete']);

// Search & filter state
const searchQuery = ref('');
const filterActive = ref('');
const currentPage = ref(1);

// Modal state
const showModal = ref(false);
const showDeleteConfirm = ref(false);
const editingItem = ref(null);
const deletingItem = ref(null);
const form = ref({ ...props.defaultForm });

// Debounced search
let searchTimeout = null;
function onSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        loadData();
    }, 400);
}

function onFilter() {
    currentPage.value = 1;
    loadData();
}

function loadData() {
    const params = { page: currentPage.value };
    if (searchQuery.value) params.search = searchQuery.value;
    if (filterActive.value !== '') params.is_active = filterActive.value;
    emit('load', params);
}

function changePage(page) {
    currentPage.value = page;
    loadData();
}

// Modal
function openCreateModal() {
    editingItem.value = null;
    form.value = { ...props.defaultForm };
    props.store.clearErrors();
    showModal.value = true;
}

function openEditModal(item) {
    editingItem.value = item;
    form.value = { ...props.defaultForm, ...item };
    props.store.clearErrors();
    showModal.value = true;
}

function closeModal() {
    if (!props.store.isLoading) {
        showModal.value = false;
        editingItem.value = null;
    }
}

async function submitForm() {
    emit('submit', { form: form.value, editingItem: editingItem.value, done: closeModal });
}

// Delete
function confirmDelete(item) {
    deletingItem.value = item;
    showDeleteConfirm.value = true;
}

async function executeDelete() {
    if (deletingItem.value) {
        emit('delete', { item: deletingItem.value, done: () => { showDeleteConfirm.value = false; deletingItem.value = null; } });
    }
}

// Expose for parent to trigger initial load
defineExpose({ loadData });

// Auto-load on mount
loadData();
</script>
