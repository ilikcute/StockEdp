<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-xs"
      role="dialog"
      aria-modal="true"
      @click.self="onClose"
    >
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
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
            {{ isEditing ? 'Edit Departemen' : 'Tambah Departemen' }}
          </h2>
          <button
            type="button"
            class="text-gray-400 hover:text-gray-600 text-lg leading-none cursor-pointer"
            @click="onClose"
          >
            &times;
          </button>
        </div>

        <form
          class="p-5 space-y-3.5"
          @submit.prevent="handleSubmit"
        >
          <div>
            <label
              for="department-code"
              class="block text-xs font-semibold text-gray-700 mb-1"
            >
              Kode Departemen <span class="text-red-500">*</span>
            </label>
            <input
              id="department-code"
              v-model="form.code"
              type="text"
              class="w-full px-3 py-1.5 border rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 uppercase font-mono"
              :class="departmentStore.validationErrors?.code ? 'border-red-500' : 'border-gray-300'"
              placeholder="Contoh: IT, FAD, OPR"
              required
            >
            <p
              v-if="departmentStore.validationErrors?.code"
              class="mt-1 text-[11px] text-red-600"
            >
              {{ departmentStore.validationErrors.code[0] }}
            </p>
          </div>

          <div>
            <label
              for="department-name"
              class="block text-xs font-semibold text-gray-700 mb-1"
            >
              Nama Departemen <span class="text-red-500">*</span>
            </label>
            <input
              id="department-name"
              v-model="form.name"
              type="text"
              class="w-full px-3 py-1.5 border rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500"
              :class="departmentStore.validationErrors?.name ? 'border-red-500' : 'border-gray-300'"
              placeholder="Contoh: Finance, Accounting, Tax"
              required
            >
            <p
              v-if="departmentStore.validationErrors?.name"
              class="mt-1 text-[11px] text-red-600"
            >
              {{ departmentStore.validationErrors.name[0] }}
            </p>
          </div>

          <div>
            <label
              for="department-description"
              class="block text-xs font-semibold text-gray-700 mb-1"
            >
              Keterangan / Fungsi
            </label>
            <textarea
              id="department-description"
              v-model="form.description"
              rows="2"
              class="w-full px-3 py-1.5 border rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 resize-none"
              :class="departmentStore.validationErrors?.description ? 'border-red-500' : 'border-gray-300'"
              placeholder="Deskripsi singkat divisi / bagian..."
            />
            <p
              v-if="departmentStore.validationErrors?.description"
              class="mt-1 text-[11px] text-red-600"
            >
              {{ departmentStore.validationErrors.description[0] }}
            </p>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
            <BaseButton
              type="button"
              variant="secondary"
              size="sm"
              @click="onClose"
            >
              Batal
            </BaseButton>
            <BaseButton
              type="submit"
              size="sm"
              :disabled="departmentStore.isLoading"
            >
              {{ departmentStore.isLoading ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Departemen') }}
            </BaseButton>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useDepartmentStore } from '../stores/use_department_store';
import BaseButton from '@/shared/components/BaseButton.vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true,
    },
    departmentData: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);
const departmentStore = useDepartmentStore();

const isEditing = computed(() => !!props.departmentData?.id);

const form = ref({
    code: '',
    name: '',
    description: '',
});

watch(
    () => props.departmentData,
    (val) => {
        if (val) {
            form.value = {
                code: val.code || '',
                name: val.name || '',
                description: val.description || '',
            };
        } else {
            form.value = {
                code: '',
                name: '',
                description: '',
            };
        }
        departmentStore.clearMessages();
    },
    { immediate: true }
);

const onClose = () => {
    departmentStore.clearMessages();
    emit('close');
};

const handleSubmit = async () => {
    const success = isEditing.value
        ? await departmentStore.updateDepartment(props.departmentData.id, form.value)
        : await departmentStore.createDepartment(form.value);

    if (success) {
        emit('saved');
        emit('close');
    }
};
</script>
