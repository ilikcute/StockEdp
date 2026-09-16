<template>
  <div
    ref="panelContainerRef"
    :class="[
      compact ? 'bg-transparent p-0' : 'bg-white border border-blue-200 rounded-xl p-4 shadow-xs',
      'transition-all relative'
    ]"
  >
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2">
      <!-- Input Section -->
      <div class="flex-1 relative">
        <div
          v-if="!compact"
          class="flex items-center justify-between mb-1"
        >
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
              :class="compact ? 'w-4 h-4' : 'w-5 h-5'"
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
            :class="[
              'block w-full pl-9 pr-9 font-mono border border-gray-300 rounded-lg text-gray-900 focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:opacity-50 transition-colors',
              compact ? 'py-1.5 text-xs bg-white min-h-[36px]' : 'py-2.5 text-sm bg-gray-50 min-h-[44px]'
            ]"
            @input="onInput"
            @focus="onFocus"
            @keydown.down.prevent="onKeyDown"
            @keydown.up.prevent="onKeyUp"
            @keydown.enter.prevent="onKeyEnter"
            @keydown.esc.prevent="closeDropdown"
          >
          <button
            v-if="scanInput"
            type="button"
            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer"
            aria-label="Bersihkan Input Barcode"
            @click="clearInput"
          >
            &times;
          </button>
        </div>

        <!-- Live Search Suggestions Popover Dropdown -->
        <div
          v-if="enableLiveSearch && isDropdownOpen && (searchResults.length > 0 || isLiveSearching || scanInput.trim().length >= 2)"
          class="absolute left-0 right-0 top-full mt-1 z-50 bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden text-xs"
        >
          <!-- Header Status -->
          <div class="px-3 py-1.5 bg-gray-50 border-b border-gray-100 flex items-center justify-between text-[11px] text-gray-500">
            <span
              v-if="isLiveSearching"
              class="inline-flex items-center gap-1.5 text-indigo-600 font-medium"
            >
              <svg
                class="animate-spin h-3.5 w-3.5"
                viewBox="0 0 24 24"
                fill="none"
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
              Mencari produk...
            </span>
            <span
              v-else
              class="font-medium text-gray-700"
            >
              Ditemukan {{ searchResults.length }} produk (gunakan ↑↓ lalu Enter)
            </span>
            <span class="text-[10px] text-gray-400 font-mono">ESC untuk tutup</span>
          </div>

          <!-- Items list -->
          <div class="max-h-64 overflow-y-auto divide-y divide-gray-100 custom-scrollbar">
            <div
              v-for="(product, idx) in searchResults"
              :key="product.id"
              :class="[
                'px-3 py-2 flex items-center justify-between gap-2.5 cursor-pointer transition-colors',
                highlightedIndex === idx
                  ? 'bg-teal-50/90 text-teal-950 ring-1 ring-inset ring-teal-400/40'
                  : 'hover:bg-gray-50 text-gray-800'
              ]"
              @click="selectProduct(product)"
              @mouseenter="highlightedIndex = idx"
            >
              <!-- Info Produk -->
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span class="font-bold text-gray-900 leading-tight">
                    {{ product.name }}
                  </span>
                  <span
                    v-if="product.category?.name"
                    class="text-[9px] font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 px-1.5 py-0.2 rounded"
                  >
                    {{ product.category.name }}
                  </span>
                </div>
                <div class="flex items-center gap-2 mt-0.5 text-[10px] text-gray-500 font-mono flex-wrap">
                  <span class="bg-gray-100 px-1 rounded border border-gray-200 text-gray-700 font-bold">
                    SKU: {{ product.sku }}
                  </span>
                  <span
                    v-if="product.barcode"
                    class="text-gray-400"
                  >
                    Barcode: {{ product.barcode }}
                  </span>
                  <span
                    v-if="product.unit?.name"
                    class="text-gray-400"
                  >
                    ({{ product.unit.name }})
                  </span>
                  <span
                    v-if="product.unit_price"
                    class="text-indigo-600 font-semibold font-mono"
                  >
                    • Rp {{ Number(product.unit_price).toLocaleString('id-ID') }}
                  </span>
                </div>
              </div>

              <!-- Stock & Action Button -->
              <div class="shrink-0 flex items-center gap-2">
                <div
                  v-if="product.stockText !== undefined"
                  class="text-right"
                >
                  <div class="text-[9px] text-gray-400 uppercase font-semibold">
                    Saldo
                  </div>
                  <span
                    :class="[
                      'inline-block px-1.5 py-0.5 rounded text-[10px] font-bold font-mono',
                      product.hasStock
                        ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                        : 'bg-rose-50 text-rose-700 border border-rose-200'
                    ]"
                  >
                    {{ product.stockText }}
                  </span>
                </div>

                <span
                  class="inline-flex items-center gap-0.5 px-2 py-1 rounded-md text-[10px] font-semibold border transition-colors cursor-pointer"
                  :class="highlightedIndex === idx
                    ? 'bg-teal-600 text-white border-teal-600 shadow-xs'
                    : 'bg-white text-gray-700 border-gray-200'"
                >
                  + Tambah
                </span>
              </div>
            </div>

            <!-- Empty Search State -->
            <div
              v-if="!isLiveSearching && searchResults.length === 0 && scanInput.trim().length >= 2"
              class="p-4 text-center text-xs text-gray-500 space-y-1"
            >
              <div class="font-medium text-gray-700">
                Tidak ada produk yang cocok dengan "{{ scanInput.trim() }}".
              </div>
              <div class="text-[11px] text-gray-400">
                Tekan Enter untuk mencoba lookup scanner langsung ke server.
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Button for Manual Submit / Touch -->
      <div class="flex items-center gap-2 self-stretch sm:self-auto">
        <button
          id="btn-submit-scan"
          type="button"
          :disabled="disabled || !scanInput.trim()"
          :class="[
            'w-full sm:w-auto inline-flex items-center justify-center font-semibold rounded-lg shadow-xs transition-colors disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer',
            compact ? 'px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs min-h-[36px]' : 'px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs min-h-[44px]'
          ]"
          @click="onKeyEnter"
        >
          <svg
            v-if="isProcessing && !scanInput.trim()"
            class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white"
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
          <span>🔍 Scan / Tambah</span>
        </button>
      </div>
    </div>

    <!-- Status Message Bar -->
    <div
      v-if="statusMessage || !locationSelected"
      :class="compact ? 'mt-1.5 text-xs' : 'mt-3 text-xs'"
    >
      <div
        v-if="!locationSelected"
        class="p-2 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 font-medium flex items-center gap-2 text-xs"
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
          'p-2 rounded-lg font-medium flex items-center justify-between gap-2 border transition-all text-xs',
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
import { ref, computed, nextTick, watch, onMounted, onUnmounted } from 'vue';
import { useInventoryBarcodeScanner } from '../composables/use_inventory_barcode_scanner';
import { productApi } from '@/features/product/api/product_api.js';

const props = defineProps({
  label: { type: String, default: 'Scan Barcode / Masukkan SKU Produk (Scanner / Input Cepat)' },
  placeholder: { type: String, default: 'Scan barcode atau ketik SKU / Barcode produk (lalu tekan Enter)...' },
  disabled: { type: Boolean, default: false },
  locationSelected: { type: Boolean, default: true },
  compact: { type: Boolean, default: false },
  enableLiveSearch: { type: Boolean, default: false },
  products: { type: Array, default: () => [] },
  debounceMs: { type: Number, default: 250 },
});

const emit = defineEmits(['scan-success', 'scan-error']);

const inputRef = ref(null);
const panelContainerRef = ref(null);

const isDropdownOpen = ref(false);
const isLiveSearching = ref(false);
const searchResults = ref([]);
const highlightedIndex = ref(0);
let debounceTimer = null;

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

const closeDropdown = () => {
  isDropdownOpen.value = false;
  isLiveSearching.value = false;
};

const performSearch = async (query) => {
  const q = query.toLowerCase().trim();
  if (!q) {
    searchResults.value = [];
    isLiveSearching.value = false;
    return;
  }

  // 1. Search locally in props.products if provided
  let matches = [];
  if (Array.isArray(props.products) && props.products.length > 0) {
    matches = props.products.filter((p) => {
      const nameMatch = p.name && p.name.toLowerCase().includes(q);
      const skuMatch = p.sku && String(p.sku).toLowerCase().includes(q);
      const barcodeMatch = p.barcode && String(p.barcode).toLowerCase().includes(q);
      const catMatch = p.category?.name && p.category.name.toLowerCase().includes(q);
      return nameMatch || skuMatch || barcodeMatch || catMatch;
    });
  }

  // 2. Fallback to API if no local matches
  if (matches.length === 0) {
    try {
      const res = await productApi.getAll({ search: query, is_active: 1, per_page: 20 });
      const backendItems = res.data?.data?.data || res.data?.data || [];
      matches = backendItems;
    } catch {
      matches = [];
    }
  }

  searchResults.value = matches.slice(0, 20);
  highlightedIndex.value = 0;
  isLiveSearching.value = false;
  isDropdownOpen.value = true;
};

const onInput = () => {
  if (!props.enableLiveSearch) return;

  clearTimeout(debounceTimer);
  const q = (scanInput.value || '').trim();

  if (q.length < 2) {
    searchResults.value = [];
    isDropdownOpen.value = false;
    isLiveSearching.value = false;
    return;
  }

  isLiveSearching.value = true;
  debounceTimer = setTimeout(() => {
    performSearch(q);
  }, props.debounceMs);
};

const onFocus = () => {
  if (!props.enableLiveSearch) return;
  const q = (scanInput.value || '').trim();
  if (q.length >= 2 && searchResults.value.length > 0) {
    isDropdownOpen.value = true;
  }
};

const onKeyDown = () => {
  if (!isDropdownOpen.value || searchResults.value.length === 0) return;
  highlightedIndex.value = (highlightedIndex.value + 1) % searchResults.value.length;
};

const onKeyUp = () => {
  if (!isDropdownOpen.value || searchResults.value.length === 0) return;
  highlightedIndex.value = (highlightedIndex.value - 1 + searchResults.value.length) % searchResults.value.length;
};

const selectProduct = (product) => {
  if (!product || !product.id) return;

  if (!props.locationSelected) {
    emit('scan-error', 'Lokasi belum dipilih.');
    return;
  }

  closeDropdown();
  scanInput.value = '';
  status.value = 'FOUND';
  statusMessage.value = `✓ ${product.name} (${product.sku}) dipilih.`;
  emit('scan-success', product);
  focusInput();
};

const handleScan = () => {
  if (!props.locationSelected) {
    emit('scan-error', 'Lokasi belum dipilih.');
    return;
  }

  const code = scanInput.value.trim();
  if (!code) return;

  closeDropdown();

  // If local exact match exists
  if (props.enableLiveSearch && Array.isArray(props.products) && props.products.length > 0) {
    const exactMatch = props.products.find(
      (p) => (p.barcode && String(p.barcode).trim().toLowerCase() === code.toLowerCase()) ||
             (p.sku && String(p.sku).trim().toLowerCase() === code.toLowerCase())
    );
    if (exactMatch) {
      selectProduct(exactMatch);
      return;
    }
  }

  scanInput.value = '';
  enqueueScan(code);
  focusInput();
};

const onKeyEnter = () => {
  if (!props.locationSelected) {
    emit('scan-error', 'Lokasi belum dipilih.');
    return;
  }

  // 1. If dropdown is open and user has an item highlighted or available
  if (props.enableLiveSearch && isDropdownOpen.value && searchResults.value.length > 0 && highlightedIndex.value >= 0) {
    const selected = searchResults.value[highlightedIndex.value];
    if (selected) {
      selectProduct(selected);
      return;
    }
  }

  // 2. Check if the typed input matches an exact barcode or SKU in local products
  const code = (scanInput.value || '').trim();
  if (!code) return;

  if (props.enableLiveSearch && Array.isArray(props.products) && props.products.length > 0) {
    const exactMatch = props.products.find(
      (p) => (p.barcode && String(p.barcode).trim().toLowerCase() === code.toLowerCase()) ||
             (p.sku && String(p.sku).trim().toLowerCase() === code.toLowerCase())
    );
    if (exactMatch) {
      selectProduct(exactMatch);
      return;
    }
  }

  // 3. Fallback to standard scanner lookup
  closeDropdown();
  handleScan();
};

const clearInput = () => {
  scanInput.value = '';
  closeDropdown();
  focusInput();
};

const handleClickOutside = (event) => {
  if (panelContainerRef.value && !panelContainerRef.value.contains(event.target)) {
    closeDropdown();
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  clearTimeout(debounceTimer);
});

defineExpose({
  focusInput,
  clearInput,
});
</script>
