<template>
  <div class="space-y-2">
    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
      Pilih File CSV:
    </label>
    <div class="flex items-center gap-3">
      <input
        ref="inputEl"
        type="file"
        accept=".csv,text/csv"
        class="block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 file:cursor-pointer border border-gray-200 rounded-xl p-1 bg-white cursor-pointer"
        @change="$emit('file-selected', $event)"
      >
      <button
        type="button"
        class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed shrink-0 cursor-pointer"
        :disabled="!selectedFile || isValidating || isCommitting"
        @click="$emit('validate')"
      >
        <span v-if="isValidating">Memvalidasi...</span>
        <span v-else>🔍 Validasi File</span>
      </button>
    </div>
    <p
      v-if="selectedFile"
      class="text-xs text-gray-500"
    >
      File dipilih: <strong>{{ selectedFile.name }}</strong> ({{ (selectedFile.size / 1024).toFixed(1) }} KB)
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  selectedFile: {
    type: Object,
    default: null,
  },
  isValidating: {
    type: Boolean,
    default: false,
  },
  isCommitting: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['file-selected', 'validate']);

const inputEl = ref(null);

defineExpose({
  inputEl,
});
</script>
