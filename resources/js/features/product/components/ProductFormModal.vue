<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs"
        role="dialog"
        aria-modal="true"
        @click.self="onClose"
      >
        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="isOpen"
            class="bg-white rounded-xl shadow-xl border border-gray-200 w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden"
          >
            <!-- Header Compact -->
            <div class="px-4 py-2.5 border-b border-gray-200 flex items-center justify-between bg-gray-50/80 shrink-0">
              <div class="flex items-center gap-2">
                <div class="p-1 rounded-md bg-indigo-50 text-indigo-600 border border-indigo-100">
                  <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                    />
                  </svg>
                </div>
                <h2 class="text-xs font-bold text-gray-900">
                  {{ isEditing ? 'Edit Produk' : 'Tambah Produk' }}
                </h2>
              </div>
              <button
                type="button"
                class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md p-1 transition-colors cursor-pointer"
                aria-label="Tutup"
                @click="onClose"
              >
                <svg
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>

            <!-- Form Body Compact -->
            <form
              class="flex flex-col flex-1 overflow-hidden"
              @submit.prevent="handleSubmit"
            >
              <div class="p-4 space-y-3 overflow-y-auto custom-scrollbar flex-1">
                <!-- SKU & Barcode -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label
                      for="prd-sku"
                      class="block text-[11px] font-semibold text-gray-700 mb-1"
                    >SKU <span class="text-red-500">*</span></label>
                    <input
                      id="prd-sku"
                      v-model="form.sku"
                      type="text"
                      class="w-full px-2.5 py-1.5 border rounded-lg text-xs shadow-2xs focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 uppercase"
                      :class="store.validationErrors?.sku ? 'border-red-500 bg-red-50/20' : 'border-gray-300 bg-white'"
                      placeholder="Contoh: PRD-001"
                      required
                    >
                    <p
                      v-if="store.validationErrors?.sku"
                      class="mt-0.5 text-[10px] text-red-600 font-medium"
                    >
                      {{ store.validationErrors.sku[0] }}
                    </p>
                  </div>

                  <div>
                    <label
                      for="prd-barcode"
                      class="block text-[11px] font-semibold text-gray-700 mb-1"
                    >Barcode</label>
                    <input
                      id="prd-barcode"
                      v-model="form.barcode"
                      type="text"
                      class="w-full px-2.5 py-1.5 border rounded-lg text-xs shadow-2xs focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                      :class="store.validationErrors?.barcode ? 'border-red-500 bg-red-50/20' : 'border-gray-300 bg-white'"
                      placeholder="Scan atau ketik barcode"
                    >
                    <p
                      v-if="store.validationErrors?.barcode"
                      class="mt-0.5 text-[10px] text-red-600 font-medium"
                    >
                      {{ store.validationErrors.barcode[0] }}
                    </p>
                  </div>
                </div>

                <!-- Nama Produk -->
                <div>
                  <label
                    for="prd-name"
                    class="block text-[11px] font-semibold text-gray-700 mb-1"
                  >Nama Produk <span class="text-red-500">*</span></label>
                  <input
                    id="prd-name"
                    v-model="form.name"
                    type="text"
                    class="w-full px-2.5 py-1.5 border rounded-lg text-xs shadow-2xs focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    :class="store.validationErrors?.name ? 'border-red-500 bg-red-50/20' : 'border-gray-300 bg-white'"
                    placeholder="Contoh: Laptop Dell Inspiron"
                    required
                  >
                  <p
                    v-if="store.validationErrors?.name"
                    class="mt-0.5 text-[10px] text-red-600 font-medium"
                  >
                    {{ store.validationErrors.name[0] }}
                  </p>
                </div>

                <!-- Kategori & Satuan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label
                      for="prd-category"
                      class="block text-[11px] font-semibold text-gray-700 mb-1"
                    >Kategori <span class="text-red-500">*</span></label>
                    <select
                      id="prd-category"
                      v-model="form.category_id"
                      class="w-full px-2.5 py-1.5 border rounded-lg text-xs shadow-2xs focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                      :class="store.validationErrors?.category_id ? 'border-red-500 bg-red-50/20' : 'border-gray-300 bg-white'"
                      required
                    >
                      <option
                        value=""
                        disabled
                      >
                        Pilih Kategori
                      </option>
                      <option
                        v-for="cat in categories"
                        :key="cat.id"
                        :value="cat.id"
                      >
                        {{ cat.name }}
                      </option>
                    </select>
                    <p
                      v-if="store.validationErrors?.category_id"
                      class="mt-0.5 text-[10px] text-red-600 font-medium"
                    >
                      {{ store.validationErrors.category_id[0] }}
                    </p>
                  </div>

                  <div>
                    <label
                      for="prd-unit"
                      class="block text-[11px] font-semibold text-gray-700 mb-1"
                    >Satuan <span class="text-red-500">*</span></label>
                    <select
                      id="prd-unit"
                      v-model="form.unit_id"
                      class="w-full px-2.5 py-1.5 border rounded-lg text-xs shadow-2xs focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                      :class="store.validationErrors?.unit_id ? 'border-red-500 bg-red-50/20' : 'border-gray-300 bg-white'"
                      required
                    >
                      <option
                        value=""
                        disabled
                      >
                        Pilih Satuan
                      </option>
                      <option
                        v-for="u in units"
                        :key="u.id"
                        :value="u.id"
                      >
                        {{ u.name }} ({{ u.abbreviation || u.symbol }})
                      </option>
                    </select>
                    <p
                      v-if="store.validationErrors?.unit_id"
                      class="mt-0.5 text-[10px] text-red-600 font-medium"
                    >
                      {{ store.validationErrors.unit_id[0] }}
                    </p>
                  </div>
                </div>

                <!-- Stok Minimum & Harga Satuan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label
                      for="prd-minstock"
                      class="block text-[11px] font-semibold text-gray-700 mb-1"
                    >Stok Minimum</label>
                    <input
                      id="prd-minstock"
                      v-model.number="form.minimum_stock"
                      type="number"
                      step="1"
                      min="0"
                      class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs shadow-2xs focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="0"
                    >
                  </div>

                  <div>
                    <label
                      for="prd-unitprice"
                      class="block text-[11px] font-semibold text-gray-700 mb-1"
                    >Harga Satuan (Rp)</label>
                    <div class="relative rounded-lg shadow-2xs">
                      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5">
                        <span class="text-gray-500 text-[11px] font-mono">Rp</span>
                      </div>
                      <input
                        id="prd-unitprice"
                        v-model.number="form.unit_price"
                        type="number"
                        step="0.01"
                        min="0"
                        class="w-full pl-8 pr-2.5 py-1.5 border rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 font-mono"
                        :class="store.validationErrors?.unit_price ? 'border-red-500 bg-red-50/20' : 'border-gray-300 bg-white'"
                        placeholder="0"
                      >
                    </div>
                    <p
                      v-if="store.validationErrors?.unit_price"
                      class="mt-0.5 text-[10px] text-red-600 font-medium"
                    >
                      {{ store.validationErrors.unit_price[0] }}
                    </p>
                  </div>
                </div>

                <!-- Deskripsi -->
                <div>
                  <label
                    for="prd-desc"
                    class="block text-[11px] font-semibold text-gray-700 mb-1"
                  >Deskripsi</label>
                  <textarea
                    id="prd-desc"
                    v-model="form.description"
                    class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs shadow-2xs focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    rows="2"
                    placeholder="Deskripsi produk (opsional)"
                  />
                </div>
              </div>

              <!-- Footer Compact -->
              <div class="px-4 py-2.5 border-t border-gray-200 flex items-center justify-end gap-2 bg-gray-50/50 shrink-0">
                <button
                  type="button"
                  class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg shadow-2xs transition-colors cursor-pointer"
                  :disabled="store.isLoading"
                  @click="onClose"
                >
                  Batal
                </button>
                <button
                  type="submit"
                  class="px-3.5 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-2xs transition-colors disabled:opacity-50 cursor-pointer"
                  :disabled="store.isLoading"
                >
                  {{ store.isLoading ? 'Menyimpan…' : (isEditing ? 'Perbarui' : 'Simpan') }}
                </button>
              </div>
            </form>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, reactive, watch } from 'vue';
import { useProductStore } from '../stores/use_product_store';

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    product: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);
const store = useProductStore();

const isEditing = computed(() => !!props.product);

const form = reactive({
    sku: '',
    barcode: '',
    name: '',
    description: '',
    category_id: '',
    unit_id: '',
    minimum_stock: 0,
    unit_price: 0,
});

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        store.clearMessages();
        if (props.product) {
            form.sku = props.product.sku ?? '';
            form.barcode = props.product.barcode ?? '';
            form.name = props.product.name ?? '';
            form.description = props.product.description ?? '';
            form.category_id = props.product.category_id ?? '';
            form.unit_id = props.product.unit_id ?? '';
            form.minimum_stock = props.product.minimum_stock !== null && props.product.minimum_stock !== undefined ? Number(props.product.minimum_stock) : 0;
            form.unit_price = props.product.unit_price !== null && props.product.unit_price !== undefined ? Number(props.product.unit_price) : 0;
        } else {
            Object.assign(form, { sku: '', barcode: '', name: '', description: '', category_id: '', unit_id: '', minimum_stock: 0, unit_price: 0 });
        }
    }
});

const handleSubmit = async () => {
    const success = isEditing.value
        ? await store.updateProduct(props.product.id, form)
        : await store.createProduct(form);

    if (success) {
        emit('saved');
        onClose();
    }
};

const onClose = () => {
    if (!store.isLoading) emit('close');
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 4px;
  width: 4px;
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
