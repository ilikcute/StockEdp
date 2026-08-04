<template>
  <div class="relative">
    <label class="block text-xs font-medium text-gray-700">Supplier</label>
    <div class="relative">
      <input
        :value="modelValue"
        type="text"
        placeholder="Cari supplier (min 2 karakter)..."
        :disabled="!!error"
        class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pr-8 disabled:bg-gray-100 disabled:text-gray-500"
        @input="onInput"
        @focus="onFocus"
      >
      <button
        v-if="(modelValue || selectedSupplierId) && !error"
        type="button"
        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs"
        @click="onClear"
      >
        &times;
      </button>
    </div>

    <p
      v-if="error"
      class="mt-1 text-xs text-amber-600"
    >
      {{ error }}
    </p>

    <div
      v-if="showDropdown && !error && (loading || suppliers.length > 0)"
      class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
    >
      <div
        v-if="loading"
        class="px-3 py-2 text-xs text-gray-500"
      >
        Mencari supplier...
      </div>
      <template v-else>
        <div
          v-for="sup in suppliers"
          :key="sup.id"
          class="cursor-pointer px-3 py-1.5 text-xs hover:bg-indigo-50 flex items-center justify-between"
          @click="onSelect(sup)"
        >
          <span class="font-medium text-gray-900">{{ sup.name }}</span>
          <span class="text-gray-500 text-[10px]">{{ sup.code }}</span>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    modelValue: { type: String, default: '' },
    selectedSupplierId: { type: [String, Number], default: '' },
    suppliers: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    error: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue', 'search', 'select-supplier', 'clear-supplier']);

const showDropdown = ref(false);

const onInput = (event) => {
    const text = event.target.value;
    showDropdown.value = true;
    emit('update:modelValue', text);
    emit('search', text);
};

const onFocus = () => {
    showDropdown.value = true;
    emit('search', '');
};

const onSelect = (supplier) => {
    showDropdown.value = false;
    emit('select-supplier', supplier);
};

const onClear = () => {
    showDropdown.value = false;
    emit('clear-supplier');
};
</script>
