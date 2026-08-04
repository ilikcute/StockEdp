<template>
  <div class="relative">
    <label class="block text-xs font-medium text-gray-700">Produk</label>
    <input
      v-model="productSearch"
      type="text"
      placeholder="Cari produk (min 2 karakter)..."
      class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
      @input="emit('product-search', productSearch)"
      @focus="showProductDropdown = true"
    >
    <div
      v-if="showProductDropdown && products.length > 0"
      class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
    >
      <div
        v-for="prod in products"
        :key="prod.id"
        class="cursor-pointer px-3 py-1 text-xs hover:bg-indigo-50"
        @click="onSelectProduct(prod)"
      >
        {{ prod.name }} ({{ prod.sku }})
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
    products: { type: Array, required: true },
});

const emit = defineEmits(['product-search', 'select-product']);

const productSearch = ref('');
const showProductDropdown = ref(false);

const onSelectProduct = (prod) => {
    productSearch.value = prod.name;
    showProductDropdown.value = false;
    emit('select-product', prod);
};
</script>
