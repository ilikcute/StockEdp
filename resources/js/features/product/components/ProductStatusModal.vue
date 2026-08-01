<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      @click.self="onCancel"
    >
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-gray-100 flex-shrink-0">
          <h2 class="text-lg font-semibold text-gray-900">
            {{ isActive ? 'Nonaktifkan Produk' : 'Aktifkan Produk' }}
          </h2>
        </div>
        <div class="p-6">
          <p class="text-sm text-gray-600">
            {{ isActive
              ? `Apakah Anda yakin ingin menonaktifkan produk "${product?.name}" (${product?.sku})? Produk yang dinonaktifkan tidak dapat dipilih dalam transaksi baru.`
              : `Apakah Anda yakin ingin mengaktifkan kembali produk "${product?.name}" (${product?.sku})?`
            }}
          </p>
        </div>
        <div class="px-6 pb-5 pt-2 flex justify-end gap-3 border-t border-gray-100 flex-shrink-0">
          <button
            type="button"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
            :disabled="store.isLoading"
            @click="onCancel"
          >
            Batal
          </button>
          <button
            type="button"
            class="px-4 py-2 text-sm font-semibold text-white rounded-md transition-colors shadow-sm"
            :class="isActive ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'"
            :disabled="store.isLoading"
            @click="handleConfirm"
          >
            {{ store.isLoading ? 'Memproses…' : (isActive ? 'Nonaktifkan' : 'Aktifkan') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import { useProductStore } from '../stores/use_product_store';

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    product: { type: Object, default: null },
});

const emit = defineEmits(['close', 'status-changed']);
const store = useProductStore();

const isActive = computed(() => props.product?.is_active ?? false);

const handleConfirm = async () => {
    if (!props.product) return;
    const success = await store.changeStatus(props.product.id, !isActive.value);
    if (success) {
        emit('status-changed');
        emit('close');
    }
};

const onCancel = () => {
    if (!store.isLoading) emit('close');
};
</script>
