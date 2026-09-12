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
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        @click.self="$emit('update:modelValue', false)"
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
            v-if="modelValue"
            class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
            role="dialog"
            aria-modal="true"
            aria-labelledby="movement-detail-title"
          >
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
              <div>
                <h2 id="movement-detail-title" class="text-lg font-bold text-gray-900">
                  Detail Pergerakan Stok
                </h2>
                <p v-if="movement?.movement_id" class="text-xs text-gray-500 font-mono mt-0.5">
                  {{ movement.movement_id }}
                </p>
              </div>
              <button
                type="button"
                class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-1.5 transition-colors cursor-pointer"
                aria-label="Tutup"
                @click="$emit('update:modelValue', false)"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-16">
              <div class="text-center">
                <svg class="animate-spin w-8 h-8 text-indigo-500 mx-auto" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <p class="mt-3 text-sm text-gray-500">Memuat detail...</p>
              </div>
            </div>

            <!-- Content -->
            <div v-else-if="movement" class="px-6 py-5 space-y-5">
              <!-- Movement Type Badge -->
              <div class="flex items-center gap-3">
                <span
                  class="px-3 py-1.5 text-sm font-bold rounded-full"
                  :class="getBadgeClass(movement.movement_type)"
                >
                  {{ formatMovementType(movement.movement_type) }}
                </span>
                <span class="text-sm text-gray-500">
                  {{ formatTimestamp(movement.occurred_at) }}
                </span>
              </div>

              <!-- Grid Info -->
              <div class="grid grid-cols-2 gap-4">
                <!-- Produk -->
                <div class="col-span-2 bg-gray-50 rounded-xl p-4">
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Produk</p>
                  <p class="text-sm font-bold text-gray-900">{{ movement.product?.name || '-' }}</p>
                  <p class="text-xs text-gray-500 font-mono">{{ movement.product?.sku || '-' }}</p>
                  <p v-if="movement.product?.unit_price" class="text-xs text-indigo-600 font-semibold mt-1">
                    {{ formatRupiah(movement.product.unit_price) }}
                  </p>
                </div>

                <!-- Lokasi -->
                <div class="bg-blue-50 rounded-xl p-4">
                  <p class="text-xs font-semibold text-blue-500 uppercase tracking-wider mb-1">Lokasi</p>
                  <p class="text-sm font-bold text-blue-900">{{ movement.location?.name || '-' }}</p>
                </div>

                <!-- Dibuat Oleh -->
                <div class="bg-purple-50 rounded-xl p-4">
                  <p class="text-xs font-semibold text-purple-500 uppercase tracking-wider mb-1">Operator</p>
                  <p class="text-sm font-bold text-purple-900">{{ movement.creator?.name || '-' }}</p>
                </div>
              </div>

              <!-- Quantity Details -->
              <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl p-4">
                <p class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-3">Mutasi Stok</p>
                <div class="grid grid-cols-3 gap-3 text-center">
                  <div>
                    <p class="text-xs text-gray-500 mb-1">Sebelum</p>
                    <p class="text-lg font-black text-gray-700 font-mono">{{ formatQuantity(movement.quantity_before) }}</p>
                  </div>
                  <div class="flex flex-col items-center justify-center">
                    <div
                      class="text-xs font-bold px-2 py-1 rounded-lg"
                      :class="isInbound(movement.movement_type) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                    >
                      {{ isInbound(movement.movement_type) ? '+' : '-' }}{{ formatQuantity(Math.abs(movement.quantity)) }}
                    </div>
                    <svg class="w-4 h-4 text-gray-400 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500 mb-1">Sesudah</p>
                    <p class="text-lg font-black text-indigo-700 font-mono">{{ formatQuantity(movement.quantity_after) }}</p>
                  </div>
                </div>
              </div>

              <!-- Referensi -->
              <div class="border-t border-gray-100 pt-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Referensi Transaksi</p>
                <div class="flex items-center gap-3">
                  <span
                    v-if="movement.reference_type"
                    class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-md font-mono"
                  >
                    {{ movement.reference_type }}
                  </span>
                  <span class="text-sm font-semibold text-gray-800">
                    {{ movement.reference_number || '-' }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Error State -->
            <div v-else class="px-6 py-12 text-center">
              <p class="text-gray-500 text-sm">Gagal memuat detail pergerakan stok.</p>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
              <button
                id="btn-close-movement-detail"
                type="button"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors cursor-pointer"
                @click="$emit('update:modelValue', false)"
              >
                Tutup
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { formatRupiah, formatQuantity, formatTimestamp } from '@/shared/utils/formatters.js';

defineProps({
  modelValue: { type: Boolean, default: false },
  movement: { type: Object, default: null },
  loading: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);

const formatMovementType = (type) => {
  const map = {
    RECEIPT: 'Penerimaan',
    ISSUE: 'Pengeluaran',
    TRANSFER_IN: 'Transfer Masuk',
    TRANSFER_OUT: 'Transfer Keluar',
    ADJUSTMENT_IN: 'Penyesuaian Masuk',
    ADJUSTMENT_OUT: 'Penyesuaian Keluar',
  };
  return map[type] || type;
};

const getBadgeClass = (type) => {
  const map = {
    RECEIPT: 'bg-green-100 text-green-800',
    ISSUE: 'bg-red-100 text-red-800',
    TRANSFER_IN: 'bg-blue-100 text-blue-800',
    TRANSFER_OUT: 'bg-yellow-100 text-yellow-800',
    ADJUSTMENT_IN: 'bg-teal-100 text-teal-800',
    ADJUSTMENT_OUT: 'bg-orange-100 text-orange-800',
  };
  return map[type] || 'bg-gray-100 text-gray-800';
};

const isInbound = (type) => ['RECEIPT', 'TRANSFER_IN', 'ADJUSTMENT_IN'].includes(type);
</script>
