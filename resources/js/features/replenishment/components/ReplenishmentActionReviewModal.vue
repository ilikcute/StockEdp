<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-xs flex items-center justify-center p-4"
    @click.self="$emit('close')"
  >
    <div class="relative w-full max-w-4xl max-h-[90vh] flex flex-col rounded-xl bg-white shadow-xl border border-gray-200 overflow-hidden">
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-gray-100 flex items-start justify-between bg-white shrink-0">
        <div>
          <div class="flex items-center gap-2.5">
            <h2 class="text-lg font-bold text-gray-900">
              Review & Validasi Aksi Transfer Persediaan
            </h2>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
              {{ reviewItems.length }} Item Dipilih
            </span>
          </div>
          <p class="text-xs text-gray-500 mt-1">
            Verifikasi kuantitas alokasi transfer antar gudang sebelum diarahkan ke formulir pembuatan transfer resmi.
          </p>
        </div>

        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-lg hover:bg-gray-100 cursor-pointer"
          @click="$emit('close')"
        >
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
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Conflict / Stale Alert Banner -->
      <div
        v-if="conflictError"
        class="mx-6 mt-4 p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-amber-900 text-xs flex items-start justify-between gap-3 shrink-0"
      >
        <div class="flex items-start gap-2">
          <svg
            class="w-4 h-4 text-amber-600 shrink-0 mt-0.5"
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
          <div>
            <span class="font-bold">Kondisi Persediaan Telah Berubah (Stale Data):</span>
            <p class="mt-0.5">
              {{ conflictError }}
            </p>
          </div>
        </div>

        <button
          type="button"
          class="shrink-0 px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-md transition-colors cursor-pointer"
          @click="handleRefreshAndClose"
        >
          Muat Ulang Data
        </button>
      </div>

      <!-- General Error Alert -->
      <div
        v-else-if="generalError"
        class="mx-6 mt-4 p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 text-xs flex items-center gap-2 shrink-0"
      >
        <svg
          class="w-4 h-4 text-rose-600 shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>{{ generalError }}</span>
      </div>

      <!-- Table of Items to Review -->
      <div class="flex-1 overflow-y-auto p-6 space-y-4">
        <div class="rounded-xl border border-gray-200 overflow-hidden shadow-2xs">
          <table class="min-w-full divide-y divide-gray-200 text-xs">
            <thead class="bg-gray-50 text-gray-600 font-semibold">
              <tr>
                <th
                  scope="col"
                  class="py-3 pl-4 pr-3 text-left"
                >
                  Produk & SKU
                </th>
                <th
                  scope="col"
                  class="px-3 py-3 text-left"
                >
                  Rute Transfer
                </th>
                <th
                  scope="col"
                  class="px-3 py-3 text-right"
                >
                  Kebutuhan Bersih
                </th>
                <th
                  scope="col"
                  class="px-3 py-3 text-right"
                >
                  Surplus Sumber
                </th>
                <th
                  scope="col"
                  class="px-3 py-3 text-right w-44"
                >
                  Kuantitas Transfer
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <tr
                v-for="item in localItems"
                :key="`${item.product_id}-${item.source_location_id}`"
                class="hover:bg-gray-50/50"
              >
                <!-- Produk -->
                <td class="py-3 pl-4 pr-3">
                  <div class="font-bold text-gray-900">
                    {{ item.product_name }}
                  </div>
                  <div class="text-[11px] text-gray-500 font-mono">
                    {{ item.sku }} <span
                      v-if="item.barcode"
                      class="text-gray-400"
                    >• {{ item.barcode }}</span>
                  </div>
                </td>

                <!-- Rute Transfer -->
                <td class="px-3 py-3">
                  <div class="flex items-center gap-1.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                      {{ item.source_location_name }}
                    </span>
                    <svg
                      class="w-3.5 h-3.5 text-gray-400 shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 8l4 4m0 0l-4 4m4-4H3"
                      />
                    </svg>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-800 border border-indigo-200">
                      {{ item.target_location_name }}
                    </span>
                  </div>
                </td>

                <!-- Kebutuhan Bersih -->
                <td class="px-3 py-3 text-right font-mono text-gray-700">
                  {{ item.target_net_need }}
                </td>

                <!-- Surplus Sumber -->
                <td class="px-3 py-3 text-right font-mono text-emerald-700 font-medium">
                  {{ item.source_available_surplus }}
                </td>

                <!-- Input Kuantitas Transfer -->
                <td class="px-3 py-3 text-right">
                  <div class="space-y-1">
                    <input
                      v-model="item.requested_quantity"
                      type="text"
                      inputmode="decimal"
                      :class="[
                        'block w-full text-right rounded-md border px-2.5 py-1.5 text-xs font-mono font-bold shadow-2xs focus:outline-none focus:ring-1 text-gray-900',
                        getItemError(item)
                          ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500 bg-rose-50/40 text-rose-900'
                          : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white'
                      ]"
                    >
                    <div
                      v-if="getItemError(item)"
                      class="text-[10px] text-rose-600 font-medium text-right"
                    >
                      {{ getItemError(item) }}
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="bg-gray-50 rounded-xl p-3.5 border border-gray-200 text-xs text-gray-600 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <svg
              class="w-4 h-4 text-indigo-600 shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <span>Setelah validasi berhasil, Anda akan diarahkan ke formulir Stock Transfer dengan data yang telah diprefill. Tidak ada mutasi otomatis.</span>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between shrink-0">
        <button
          type="button"
          class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors cursor-pointer"
          @click="$emit('close')"
        >
          Batal
        </button>

        <button
          type="button"
          :disabled="validating || !isFormValid"
          class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-600 cursor-pointer"
          @click="handleValidateAndProceed"
        >
          <svg
            v-if="validating"
            class="animate-spin -ml-0.5 mr-1 h-4 w-4 text-white"
            xmlns="http://www.w3.org/2000/svg"
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
              d="M4 12a8 8 0 018-8v8H4z"
            />
          </svg>
          <span>{{ validating ? 'Memvalidasi...' : 'Validasi & Siapkan Formulir Transfer' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import {
  isValidDecimal4String,
  compareDecimal4Strings,
  normalizeDecimal4String
} from '../../inventory/scanner/utils/decimal_string.js';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  reviewItems: {
    type: Array,
    default: () => [],
  },
  targetLocationId: {
    type: Number,
    required: true,
  },
  validating: {
    type: Boolean,
    default: false,
  },
  conflictError: {
    type: String,
    default: null,
  },
  generalError: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['close', 'validate-and-proceed', 'refresh-data']);

const localItems = ref([]);

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      localItems.value = props.reviewItems.map((i) => ({
        product_id: i.product_id,
        product_name: i.product_name,
        sku: i.sku,
        barcode: i.barcode,
        source_location_id: i.source_location_id,
        source_location_name: i.source_location_name,
        target_location_id: props.targetLocationId,
        target_location_name: i.target_location_name || 'Gudang Tujuan',
        target_net_need: i.target_net_need,
        source_available_surplus: i.source_available_surplus,
        requested_quantity: normalizeDecimal4String(i.suggested_transfer_quantity || i.requested_quantity || '1.0000'),
      }));
    }
  },
  { immediate: true }
);

const getItemError = (item) => {
  const qty = item.requested_quantity;
  if (!qty || !isValidDecimal4String(qty)) {
    return 'Format desimal tidak valid (maks 4 digit).';
  }
  if (compareDecimal4Strings(qty, '0.0000') <= 0) {
    return 'Kuantitas harus lebih dari 0.0000.';
  }
  if (compareDecimal4Strings(qty, item.source_available_surplus) > 0) {
    return `Melebihi surplus sumber (${item.source_available_surplus}).`;
  }
  return null;
};

const isFormValid = computed(() => {
  if (localItems.value.length === 0) return false;
  return localItems.value.every((item) => getItemError(item) === null);
});

const handleValidateAndProceed = () => {
  if (!isFormValid.value) return;

  emit('validate-and-proceed', {
    target_location_id: props.targetLocationId,
    items: localItems.value.map((i) => ({
      product_id: i.product_id,
      source_location_id: i.source_location_id,
      requested_quantity: normalizeDecimal4String(i.requested_quantity),
    })),
    raw_items: localItems.value,
  });
};

const handleRefreshAndClose = () => {
  emit('refresh-data');
  emit('close');
};
</script>
