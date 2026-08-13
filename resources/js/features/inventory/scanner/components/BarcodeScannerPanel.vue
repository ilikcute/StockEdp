<template>
  <div class="bg-white border border-blue-200 rounded-xl p-4 shadow-xs transition-all">
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
      <!-- Input Section -->
      <div class="flex-1 relative">
        <div class="flex items-center justify-between mb-1">
          <label
            for="barcode-scanner-input"
            class="block text-xs font-bold text-gray-700 uppercase tracking-wider"
          >
            {{ label }}
          </label>
          <span
            v-if="queueLength > 0"
            class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full"
          >
            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-ping" />
            Antrean: {{ queueLength }} scan
          </span>
        </div>
        <div class="relative flex items-center">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
          </div>
          <input
            id="barcode-scanner-input"
            ref="inputRef"
            v-model="scanInput"
            type="text"
            autocomplete="off"
            autocorrect="off"
            autocapitalize="off"
            spellcheck="false"
            :disabled="disabled"
            :placeholder="placeholder"
            class="block w-full pl-10 pr-10 py-2.5 text-sm font-mono bg-gray-50 border border-gray-300 rounded-lg text-gray-900 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 min-h-[44px]"
            @keydown.enter.prevent="handleScan"
          >
          <button
            v-if="scanInput"
            type="button"
            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none min-h-[44px] min-w-[44px] justify-center cursor-pointer"
            aria-label="Bersihkan Input Barcode"
            @click="clearInput"
          >
            &times;
          </button>
        </div>
      </div>

      <!-- Action Button for Manual Submit / Touch -->
      <div class="flex items-end gap-2 self-stretch sm:self-auto pt-1 sm:pt-0">
        <button
          id="btn-submit-scan"
          type="button"
          :disabled="disabled || !scanInput.trim()"
          class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-xs transition-colors disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[44px] cursor-pointer"
          @click="handleScan"
        >
          <svg
            v-if="isProcessing && !scanInput.trim()"
            class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
          </svg>
          <span>🔍 Scan / Cari</span>
        </button>
      </div>
    </div>

    <!-- Status Message Bar -->
    <div
      v-if="statusMessage || !locationSelected"
      class="mt-3 text-xs"
    >
      <div
        v-if="!locationSelected"
        class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 font-medium flex items-center gap-2"
      >
        <svg
          class="w-4 h-4 text-amber-600 flex-shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        <span>Pilih lokasi terlebih dahulu sebelum melakukan scan barcode.</span>
      </div>

      <div
        v-else-if="statusMessage"
        :class="[
          'p-2.5 rounded-lg font-medium flex items-center justify-between gap-2 border transition-all',
          statusClasses
        ]"
      >
        <div class="flex items-center gap-2">
          <span>{{ statusMessage }}</span>
        </div>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 cursor-pointer"
          @click="resetStatus"
        >
          &times;
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue';
import { useInventoryBarcodeScanner } from '../composables/use_inventory_barcode_scanner';

const props = defineProps({
  label: { type: String, default: 'Scan Barcode Produk (HID / Bluetooth / Manual)' },
  placeholder: { type: String, default: 'Arahkan scanner ke barcode atau ketik angka barcode...' },
  disabled: { type: Boolean, default: false },
  locationSelected: { type: Boolean, default: true },
});

const emit = defineEmits(['scan-success', 'scan-error']);

const inputRef = ref(null);

const focusInput = () => {
  nextTick(() => {
    if (inputRef.value && !props.disabled) {
      inputRef.value.focus();
    }
  });
};

const {
  scanInput,
  status,
  statusMessage,
  isProcessing,
  queueLength,
  enqueueScan,
  resetStatus,
} = useInventoryBarcodeScanner(
  async (product) => {
    emit('scan-success', product);
  },
  () => {
    focusInput();
  }
);

watch(
  () => isProcessing.value,
  (busy) => {
    if (!busy) {
      focusInput();
    }
  }
);

const statusClasses = computed(() => {
  switch (status.value) {
    case 'FOUND':
      return 'bg-emerald-50 border-emerald-200 text-emerald-800';
    case 'NOT_FOUND':
      return 'bg-amber-50 border-amber-200 text-amber-800';
    case 'INACTIVE':
      return 'bg-rose-50 border-rose-200 text-rose-800';
    case 'ERROR':
      return 'bg-rose-50 border-rose-200 text-rose-800';
    case 'SCANNING':
      return 'bg-blue-50 border-blue-200 text-blue-800';
    default:
      return 'bg-gray-50 border-gray-200 text-gray-700';
  }
});

const handleScan = () => {
  if (!props.locationSelected) {
    emit('scan-error', 'Lokasi belum dipilih.');
    return;
  }

  const code = scanInput.value.trim();
  if (!code) return;

  scanInput.value = '';
  enqueueScan(code);
  focusInput();
};

const clearInput = () => {
  scanInput.value = '';
  focusInput();
};

defineExpose({
  focusInput,
  clearInput,
});
</script>
