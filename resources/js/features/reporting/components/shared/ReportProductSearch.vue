<template>
  <div class="relative">
    <label class="block text-xs font-medium text-gray-700">Produk</label>
    <div class="relative">
      <input
        :value="modelValue"
        type="text"
        placeholder="Cari produk (min 2 karakter)..."
        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 pr-8"
        @input="onInput"
        @focus="showDropdown = true"
      >
      <button
        v-if="modelValue || selectedProductId"
        type="button"
        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs cursor-pointer"
        @click="onClear"
      >
        &times;
      </button>
    </div>

    <div
      v-if="showDropdown && (loading || products.length > 0)"
      class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-md bg-white py-1 shadow-lg border border-gray-300"
    >
      <div
        v-if="loading"
        class="px-3 py-2 text-xs text-gray-500"
      >
        Mencari produk...
      </div>
      <template v-else>
        <div
          v-for="prod in products"
          :key="prod.id"
          class="cursor-pointer px-3 py-1.5 text-xs hover:bg-indigo-50 flex items-center justify-between"
          @click="onSelect(prod)"
        >
          <span class="font-medium text-gray-900">{{ prod.name }}</span>
          <span class="text-gray-500 text-[10px]">{{ prod.sku }}</span>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    modelValue: { type: String, default: '' },
    selectedProductId: { type: [String, Number], default: '' },
    products: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'search', 'select-product', 'clear-product']);

const showDropdown = ref(false);

const onInput = (event) => {
    const text = event.target.value;
    showDropdown.value = true;
    emit('update:modelValue', text);
    emit('search', text);
};

const onSelect = (product) => {
    showDropdown.value = false;
    emit('select-product', product);
};

const onClear = () => {
    showDropdown.value = false;
    emit('clear-product');
};
</script>
