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
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs"
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
            class="bg-white rounded-xl shadow-xl border border-gray-200 w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden"
            role="dialog"
            aria-modal="true"
            aria-labelledby="movement-detail-title"
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
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                  </svg>
                </div>
                <div>
                  <h2
                    id="movement-detail-title"
                    class="text-xs font-bold text-gray-900"
                  >
                    Detail Pergerakan Stok
                  </h2>
                  <p
                    v-if="movement?.movement_id"
                    class="text-[10px] text-gray-500 font-mono leading-none mt-0.5"
                  >
                    {{ movement.movement_id }}
                  </p>
                </div>
              </div>
              <button
                type="button"
                class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-md p-1 transition-colors cursor-pointer"
                aria-label="Tutup"
                @click="$emit('update:modelValue', false)"
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

            <!-- Loading State Compact -->
            <div
              v-if="loading"
              class="flex items-center justify-center py-10"
            >
              <div class="text-center">
                <svg
                  class="animate-spin w-6 h-6 text-indigo-500 mx-auto"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  />
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                  />
                </svg>
                <p class="mt-2 text-xs text-gray-500">
                  Memuat detail mutasi...
                </p>
              </div>
            </div>

            <!-- Content Compact -->
            <div
              v-else-if="movement"
              class="p-3.5 space-y-2.5 overflow-y-auto custom-scrollbar flex-1"
            >
              <!-- Movement Type Badge & Timestamp Strip -->
              <div class="flex items-center justify-between bg-gray-50/80 border border-gray-200/70 rounded-lg px-2.5 py-1.5">
                <div class="flex items-center gap-1.5">
                  <span class="text-[10px] text-gray-500 font-medium">Tipe:</span>
                  <span
                    class="px-1.5 py-0.5 text-[10px] font-bold rounded uppercase tracking-wider"
                    :class="getBadgeClass(movement.movement_type)"
                  >
                    {{ formatMovementType(movement.movement_type) }}
                  </span>
                </div>
                <div class="text-[10px] text-gray-500 font-mono">
                  {{ formatTimestamp(movement.occurred_at) }}
                </div>
              </div>

              <!-- Info Produk Card -->
              <div class="bg-gray-50/60 border border-gray-200 rounded-lg p-2.5">
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">
                  Produk
                </div>
                <div class="text-xs font-semibold text-gray-900 leading-tight">
                  {{ movement.product?.name || '-' }}
                </div>
                <div class="flex items-center justify-between mt-1 text-[11px]">
                  <span class="text-[10px] text-gray-500 font-mono">
                    SKU: {{ movement.product?.sku || '-' }}
                  </span>
                  <span
                    v-if="movement.product?.unit_price"
                    class="font-mono text-indigo-700 font-medium text-[11px]"
                  >
                    {{ formatRupiah(movement.product.unit_price) }}
                  </span>
                </div>
              </div>

              <!-- Grid Lokasi & Operator -->
              <div class="grid grid-cols-2 gap-2">
                <!-- Lokasi -->
                <div class="bg-white border border-gray-200 rounded-lg p-2">
                  <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">
                    Lokasi
                  </div>
                  <div class="text-[11px] font-semibold text-gray-800 truncate">
                    {{ movement.location?.name || '-' }}
                  </div>
                </div>

                <!-- Operator -->
                <div class="bg-white border border-gray-200 rounded-lg p-2">
                  <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">
                    Operator
                  </div>
                  <div class="text-[11px] font-semibold text-gray-800 truncate">
                    {{ movement.creator?.name || 'Sistem' }}
                  </div>
                </div>
              </div>

              <!-- Quantity Flow: Sebelum -> Mutasi -> Sesudah -->
              <div class="bg-indigo-50/40 border border-indigo-100 rounded-lg p-2.5">
                <div class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                  <span>Perubahan Stok Fisik</span>
                  <span class="text-[9px] font-normal text-indigo-500">Unit: Satuan Standar</span>
                </div>
                <div class="grid grid-cols-3 gap-1.5 items-center text-center bg-white/80 rounded-md border border-indigo-100/60 p-2">
                  <!-- Sebelum -->
                  <div>
                    <div class="text-[10px] text-gray-400 font-medium mb-0.5">
                      Sebelum
                    </div>
                    <div class="text-xs font-bold text-gray-700 font-mono">
                      {{ formatQuantity(movement.quantity_before) }}
                    </div>
                  </div>

                  <!-- Delta Mutasi -->
                  <div class="flex flex-col items-center justify-center">
                    <span
                      class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[11px] font-mono font-bold"
                      :class="isInbound(movement.movement_type) ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                    >
                      {{ isInbound(movement.movement_type) ? '+' : '-' }}{{ formatQuantity(Math.abs(movement.quantity)) }}
                    </span>
                    <svg
                      class="w-3.5 h-3.5 text-gray-400 mt-0.5"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 7l5 5m0 0l-5 5m5-5H6"
                      />
                    </svg>
                  </div>

                  <!-- Sesudah -->
                  <div>
                    <div class="text-[10px] text-gray-400 font-medium mb-0.5">
                      Sesudah
                    </div>
                    <div class="text-xs font-bold text-indigo-700 font-mono">
                      {{ formatQuantity(movement.quantity_after) }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Referensi Transaksi -->
              <div class="bg-white border border-gray-200 rounded-lg p-2 flex items-center justify-between">
                <div>
                  <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">
                    Referensi Dokumen
                  </div>
                  <div class="text-[11px] font-mono font-semibold text-gray-800">
                    {{ movement.reference_number || '-' }}
                  </div>
                </div>
                <span
                  v-if="movement.reference_type"
                  class="px-1.5 py-0.5 text-[10px] font-medium bg-gray-100 text-gray-700 rounded border border-gray-200"
                >
                  {{ formatReferenceType(movement.reference_type) }}
                </span>
              </div>
            </div>

            <!-- Error State Compact -->
            <div
              v-else
              class="p-6 text-center"
            >
              <p class="text-gray-500 text-xs">
                Gagal memuat rincian pergerakan stok.
              </p>
            </div>

            <!-- Footer Compact -->
            <div class="px-4 py-2 border-t border-gray-200 bg-gray-50/50 flex justify-end shrink-0">
              <button
                id="btn-close-movement-detail"
                type="button"
                class="px-3 py-1 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 shadow-2xs transition-colors cursor-pointer"
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
    ADJUSTMENT_IN: 'Penyesuaian (+)',
    ADJUSTMENT_OUT: 'Penyesuaian (-)',
    OPNAME_IN: 'Opname (+)',
    OPNAME_OUT: 'Opname (-)',
    REVERSAL: 'Pembatalan',
  };
  return map[type] || type;
};

const formatReferenceType = (type) => {
  if (!type) return '';
  const clean = type.includes('\\') ? type.split('\\').pop() : type;
  const map = {
    StockAdjustment: 'Penyesuaian Stok',
    StockReceipt: 'Penerimaan Barang',
    StockIssue: 'Pengeluaran Barang',
    StockTransfer: 'Transfer Antar Gudang',
    StockOpname: 'Stock Opname',
    StoreAllocation: 'Alokasi Toko',
  };
  return map[clean] || clean;
};

const getBadgeClass = (type) => {
  if (['RECEIPT', 'TRANSFER_IN', 'ADJUSTMENT_IN', 'OPNAME_IN'].includes(type)) {
    return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
  }
  if (['ISSUE', 'TRANSFER_OUT', 'ADJUSTMENT_OUT', 'OPNAME_OUT'].includes(type)) {
    return 'bg-amber-100 text-amber-800 border border-amber-200';
  }
  if (type === 'REVERSAL') {
    return 'bg-rose-100 text-rose-800 border border-rose-200';
  }
  return 'bg-gray-100 text-gray-800 border border-gray-200';
};

const isInbound = (type) => ['RECEIPT', 'TRANSFER_IN', 'ADJUSTMENT_IN', 'OPNAME_IN'].includes(type);
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
