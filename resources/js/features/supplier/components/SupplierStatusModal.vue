<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      @click.self="onCancel"
    >
      <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="text-lg font-semibold text-gray-900">
            {{ isActive ? 'Nonaktifkan Supplier' : 'Aktifkan Supplier' }}
          </h2>
        </div>
        <div class="p-6">
          <p class="text-sm text-gray-600">
            {{ isActive
              ? `Apakah Anda yakin ingin menonaktifkan supplier "${supplier?.name}"? Supplier yang dinonaktifkan tidak dapat digunakan dalam transaksi baru.`
              : `Apakah Anda yakin ingin mengaktifkan kembali supplier "${supplier?.name}"?`
            }}
          </p>
        </div>
        <div class="px-6 pb-5 flex justify-end gap-3">
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
import { useSupplierStore } from '../stores/use_supplier_store';

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    supplier: { type: Object, default: null },
});

const emit = defineEmits(['close', 'status-changed']);
const store = useSupplierStore();

const isActive = computed(() => props.supplier?.is_active ?? false);

const handleConfirm = async () => {
    if (!props.supplier) return;
    const success = await store.changeStatus(props.supplier.id, !isActive.value);
    if (success) {
        emit('status-changed');
        emit('close');
    }
};

const onCancel = () => {
    if (!store.isLoading) emit('close');
};
</script>
