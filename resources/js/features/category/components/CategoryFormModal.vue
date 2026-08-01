<template>
  <Teleport to="body">
    <div
      v-if="visible"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      @click.self="onClose"
    >
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="text-lg font-semibold text-gray-900">
            {{ isEditing ? 'Edit Kategori' : 'Tambah Kategori' }}
          </h2>
        </div>

        <form
          class="p-6 space-y-4"
          @submit.prevent="onSubmit"
        >
          <div>
            <label
              for="cat-code"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Kode <span class="text-red-500">*</span></label>
            <input
              id="cat-code"
              v-model="form.code"
              type="text"
              class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              :class="errors.code ? 'border-red-500' : 'border-gray-300'"
              placeholder="Contoh: ELE"
            >
            <p
              v-if="errors.code"
              class="mt-1 text-xs text-red-600"
            >
              {{ errors.code[0] }}
            </p>
          </div>

          <div>
            <label
              for="cat-name"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Nama <span class="text-red-500">*</span></label>
            <input
              id="cat-name"
              v-model="form.name"
              type="text"
              class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              :class="errors.name ? 'border-red-500' : 'border-gray-300'"
              placeholder="Contoh: Elektronik"
            >
            <p
              v-if="errors.name"
              class="mt-1 text-xs text-red-600"
            >
              {{ errors.name[0] }}
            </p>
          </div>

          <div>
            <label
              for="cat-description"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Deskripsi</label>
            <textarea
              id="cat-description"
              v-model="form.description"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              rows="3"
              placeholder="Deskripsi kategori (opsional)"
            />
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
              :disabled="loading"
              @click="onClose"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors shadow-sm"
              :disabled="loading"
            >
              {{ loading ? 'Menyimpan…' : (isEditing ? 'Perbarui' : 'Simpan') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    visible: { type: Boolean, required: true },
    category: { type: Object, default: null },
    errors: { type: Object, default: () => ({}) },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const isEditing = computed(() => !!props.category);

const form = reactive({ code: '', name: '', description: '' });

watch(() => props.visible, (val) => {
    if (val && props.category) {
        form.code = props.category.code ?? '';
        form.name = props.category.name ?? '';
        form.description = props.category.description ?? '';
    } else if (val) {
        form.code = '';
        form.name = '';
        form.description = '';
    }
});

function onSubmit() {
    emit('submit', { ...form });
}

function onClose() {
    if (!props.loading) {
        emit('close');
    }
}
</script>
