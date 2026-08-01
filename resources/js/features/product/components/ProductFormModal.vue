<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      @click.self="onClose"
    >
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b border-gray-100 flex-shrink-0">
          <h2 class="text-lg font-semibold text-gray-900">
            {{ isEditing ? 'Edit Produk' : 'Tambah Produk' }}
          </h2>
        </div>

        <form
          class="p-6 space-y-4 overflow-y-auto"
          @submit.prevent="handleSubmit"
        >
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label
                for="prd-sku"
                class="block text-sm font-medium text-gray-700 mb-1"
              >SKU <span class="text-red-500">*</span></label>
              <input
                id="prd-sku"
                v-model="form.sku"
                type="text"
                class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 uppercase"
                :class="store.validationErrors?.sku ? 'border-red-500' : 'border-gray-300'"
                placeholder="Contoh: PRD-001"
                required
              >
              <p
                v-if="store.validationErrors?.sku"
                class="mt-1 text-xs text-red-600"
              >
                {{ store.validationErrors.sku[0] }}
              </p>
            </div>

            <div>
              <label
                for="prd-barcode"
                class="block text-sm font-medium text-gray-700 mb-1"
              >Barcode</label>
              <input
                id="prd-barcode"
                v-model="form.barcode"
                type="text"
                class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                :class="store.validationErrors?.barcode ? 'border-red-500' : 'border-gray-300'"
                placeholder="Scan atau ketik barcode"
              >
              <p
                v-if="store.validationErrors?.barcode"
                class="mt-1 text-xs text-red-600"
              >
                {{ store.validationErrors.barcode[0] }}
              </p>
            </div>
          </div>

          <div>
            <label
              for="prd-name"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Nama Produk <span class="text-red-500">*</span></label>
            <input
              id="prd-name"
              v-model="form.name"
              type="text"
              class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              :class="store.validationErrors?.name ? 'border-red-500' : 'border-gray-300'"
              placeholder="Contoh: Laptop Dell Inspiron"
              required
            >
            <p
              v-if="store.validationErrors?.name"
              class="mt-1 text-xs text-red-600"
            >
              {{ store.validationErrors.name[0] }}
            </p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label
                for="prd-category"
                class="block text-sm font-medium text-gray-700 mb-1"
              >Kategori <span class="text-red-500">*</span></label>
              <select
                id="prd-category"
                v-model="form.category_id"
                class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                :class="store.validationErrors?.category_id ? 'border-red-500' : 'border-gray-300'"
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
                class="mt-1 text-xs text-red-600"
              >
                {{ store.validationErrors.category_id[0] }}
              </p>
            </div>

            <div>
              <label
                for="prd-unit"
                class="block text-sm font-medium text-gray-700 mb-1"
              >Satuan <span class="text-red-500">*</span></label>
              <select
                id="prd-unit"
                v-model="form.unit_id"
                class="w-full px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                :class="store.validationErrors?.unit_id ? 'border-red-500' : 'border-gray-300'"
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
                class="mt-1 text-xs text-red-600"
              >
                {{ store.validationErrors.unit_id[0] }}
              </p>
            </div>
          </div>

          <div>
            <label
              for="prd-minstock"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Stok Minimum</label>
            <input
              id="prd-minstock"
              v-model.number="form.minimum_stock"
              type="number"
              step="0.01"
              min="0"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              placeholder="0"
            >
          </div>

          <div>
            <label
              for="prd-desc"
              class="block text-sm font-medium text-gray-700 mb-1"
            >Deskripsi</label>
            <textarea
              id="prd-desc"
              v-model="form.description"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
              rows="3"
              placeholder="Deskripsi produk (opsional)"
            />
          </div>

          <div class="flex justify-end gap-3 pt-2 border-t border-gray-100 flex-shrink-0">
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
              :disabled="store.isLoading"
              @click="onClose"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors shadow-sm"
              :disabled="store.isLoading"
            >
              {{ store.isLoading ? 'Menyimpan…' : (isEditing ? 'Perbarui' : 'Simpan') }}
            </button>
          </div>
        </form>
      </div>
    </div>
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
            form.minimum_stock = props.product.minimum_stock ?? 0;
        } else {
            Object.assign(form, { sku: '', barcode: '', name: '', description: '', category_id: '', unit_id: '', minimum_stock: 0 });
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
