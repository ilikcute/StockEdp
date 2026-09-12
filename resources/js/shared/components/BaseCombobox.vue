<template>
  <div
    ref="containerRef"
    class="relative w-full text-left"
  >
    <!-- Trigger / Input Display -->
    <div
      class="relative flex items-center w-full rounded-md border shadow-xs transition-colors"
      :class="[
        isOpen ? 'border-indigo-500 ring-2 ring-indigo-500/20 bg-white' : 'border-gray-300 bg-white hover:border-gray-400',
        disabled ? 'bg-gray-50 opacity-60 cursor-not-allowed' : 'cursor-pointer',
        sizeClasses
      ]"
      @click="toggleDropdown"
    >
      <!-- Search input when open OR placeholder/display when closed -->
      <input
        :id="id"
        ref="inputRef"
        v-model="searchQuery"
        type="text"
        autocomplete="off"
        spellcheck="false"
        :placeholder="selectedItemLabel || placeholder"
        :disabled="disabled"
        :required="required && !modelValue"
        class="w-full bg-transparent border-0 p-0 focus:ring-0 focus:outline-none truncate"
        :class="[
          sizeTextClasses,
          selectedItemLabel && !isOpen
            ? 'text-gray-900 font-medium placeholder:text-gray-900 placeholder:font-medium'
            : 'text-gray-900 placeholder:text-gray-400'
        ]"
        @input="onSearchInput"
        @keydown.down.prevent="onKeyDown"
        @keydown.up.prevent="onKeyUp"
        @keydown.enter.prevent="onKeyEnter"
        @keydown.esc.prevent="closeDropdown"
        @focus="openDropdown"
      >

      <!-- Clear Button -->
      <button
        v-if="modelValue && !disabled"
        type="button"
        tabindex="-1"
        class="ml-1 text-gray-400 hover:text-gray-600 p-0.5 rounded-full hover:bg-gray-100 cursor-pointer flex-shrink-0"
        title="Hapus Pilihan"
        @click.stop="clearSelection"
      >
        <svg
          class="w-3.5 h-3.5"
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

      <!-- Dropdown Chevron Toggle -->
      <div class="ml-1 flex items-center text-gray-400 pointer-events-none flex-shrink-0">
        <svg
          class="w-4 h-4 transition-transform duration-150"
          :class="{ 'rotate-180': isOpen }"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 9l-7 7-7-7"
          />
        </svg>
      </div>
    </div>

    <!-- Dropdown Menu -->
    <div
      v-if="isOpen"
      class="absolute left-0 right-0 z-50 mt-1 max-h-60 overflow-y-auto rounded-lg bg-white py-1 shadow-lg ring-1 ring-black/10 focus:outline-none text-xs"
    >
      <!-- Quick Search Status / Counter -->
      <div
        v-if="filteredOptions.length > 0"
        class="px-3 py-1.5 text-[11px] font-semibold text-gray-400 border-b border-gray-100 flex items-center justify-between"
      >
        <span
          v-if="isSearching"
          class="text-indigo-600 flex items-center gap-1 font-medium"
        >
          <svg
            class="animate-spin h-3 w-3 text-indigo-600"
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
          Mencari...
        </span>
        <span v-else>Hasil Pencarian ({{ filteredOptions.length }})</span>
        <span
          v-if="filteredOptions.length > maxRenderLimit"
          class="text-amber-600"
        >
          Top {{ maxRenderLimit }} ditampilkan
        </span>
      </div>

      <!-- Option Items -->
      <ul
        ref="listRef"
        role="listbox"
        class="divide-y divide-gray-50"
      >
        <li
          v-for="(option, index) in renderedOptions"
          :key="option.id"
          role="option"
          :aria-selected="String(option.id) === String(modelValue)"
          :class="[
            'px-3 py-2 cursor-pointer transition-colors flex items-center justify-between gap-2',
            index === activeIndex ? 'bg-indigo-50 text-indigo-900 font-semibold' : 'text-gray-700 hover:bg-gray-50',
            String(option.id) === String(modelValue) ? 'bg-indigo-50/50 text-indigo-700' : ''
          ]"
          @mouseenter="activeIndex = index"
          @mousedown.prevent="selectOption(option)"
        >
          <!-- eslint-disable vue/no-v-html -->
          <div class="flex items-center gap-2 truncate flex-1">
            <!-- Product Option: (SKU/Barcode) - Name - Price -->
            <template v-if="option.sku || option.barcode">
              <!-- Code Badge: (SKU/Barcode) -->
              <span
                class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 font-bold flex-shrink-0"
                v-html="highlightMatch(`(${getProductCodes(option)})`)"
              />
              <span class="text-gray-400 text-xs flex-shrink-0">-</span>
              <!-- Name -->
              <span
                class="truncate text-xs text-gray-900"
                v-html="highlightMatch(option.name)"
              />
              <span class="text-gray-400 text-xs flex-shrink-0 ml-auto">-</span>
              <!-- Price Badge -->
              <span class="text-[11px] font-semibold text-gray-800 font-mono flex-shrink-0 pl-1">
                {{ formatOptionPrice(option) }}
              </span>
            </template>

            <!-- Other Options (e.g. Locations, Suppliers, etc.) -->
            <template v-else>
              <!-- Code Badge if available -->
              <span
                v-if="option.code"
                class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-700 font-bold flex-shrink-0"
                v-html="highlightMatch(option.code)"
              />
              <span
                class="truncate"
                v-html="highlightMatch(option.name || formatOptionLabel(option))"
              />
            </template>
          </div>
          <!-- eslint-enable vue/no-v-html -->

          <!-- Selected Checkmark -->
          <svg
            v-if="String(option.id) === String(modelValue)"
            class="w-4 h-4 text-indigo-600 flex-shrink-0 ml-1"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2.5"
              d="M5 13l4 4L19 7"
            />
          </svg>
        </li>
      </ul>

      <!-- Empty State -->
      <div
        v-if="filteredOptions.length === 0"
        class="px-3 py-4 text-center text-gray-400 text-xs"
      >
        Tidak ada data yang cocok dengan "<span class="font-semibold text-gray-600">{{ searchQuery }}</span>".
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: 'Pilih...',
  },
  id: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'sm', // 'xs', 'sm', 'md'
  },
  formatLabel: {
    type: Function,
    default: null,
  },
  maxRenderLimit: {
    type: Number,
    default: 50, // Virtual windowing to prevent DOM overload with 681+ items
  },
});

const emit = defineEmits(['update:modelValue', 'change', 'select']);

const containerRef = ref(null);
const inputRef = ref(null);
const listRef = ref(null);

const isOpen = ref(false);
const searchQuery = ref('');
const debouncedQuery = ref('');
const isSearching = ref(false);
const activeIndex = ref(0);
let debounceTimeout = null;

// Size variant classes
const sizeClasses = computed(() => {
  switch (props.size) {
    case 'xs':
      return 'px-2 py-1 min-h-[30px]';
    case 'md':
      return 'px-3 py-2 min-h-[42px]';
    case 'sm':
    default:
      return 'px-2.5 py-1.5 min-h-[36px]';
  }
});

const sizeTextClasses = computed(() => {
  switch (props.size) {
    case 'xs':
      return 'text-xs';
    case 'md':
      return 'text-sm';
    case 'sm':
    default:
      return 'text-xs';
  }
});

const getProductCodes = (option) => {
  if (!option) return '';
  const parts = Array.from(new Set([option.sku, option.barcode].filter(Boolean)));
  return parts.join(' / ');
};

const formatOptionPrice = (option) => {
  if (!option) return '';
  const priceVal = option.unit_price !== undefined && option.unit_price !== null ? Number(option.unit_price) : 0;
  return `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(priceVal)}`;
};

const formatOptionLabel = (option) => {
  if (!option) return '';
  if (props.formatLabel && typeof props.formatLabel === 'function') {
    return props.formatLabel(option);
  }
  if (option.sku || option.barcode) {
    const codes = getProductCodes(option);
    const codePrefix = codes ? `(${codes}) - ` : '';
    const priceFormatted = formatOptionPrice(option);
    return `${codePrefix}${option.name} - ${priceFormatted}`;
  }
  if (option.code && option.name) {
    return `${option.code} — ${option.name}`;
  }
  return option.name || option.label || option.code || String(option.id);
};

// Find selected option
const selectedOption = computed(() => {
  if (!props.modelValue && props.modelValue !== 0) return null;
  return props.options.find((opt) => String(opt.id) === String(props.modelValue)) || null;
});

const selectedItemLabel = computed(() => {
  if (selectedOption.value) {
    return formatOptionLabel(selectedOption.value);
  }
  return '';
});

// Debounced input handler (120ms debounce for live responsive search)
const onSearchInput = () => {
  isOpen.value = true;
  activeIndex.value = 0;
  isSearching.value = true;
  if (debounceTimeout) clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => {
    debouncedQuery.value = searchQuery.value;
    isSearching.value = false;
  }, 120);
};

// Filtered options with multi-key search (code, name, sku, barcode, price)
const filteredOptions = computed(() => {
  const query = debouncedQuery.value.trim().toLowerCase();
  if (!query) {
    return props.options;
  }

  return props.options.filter((opt) => {
    const nameMatch = opt.name && String(opt.name).toLowerCase().includes(query);
    const codeMatch = opt.code && String(opt.code).toLowerCase().includes(query);
    const skuMatch = opt.sku && String(opt.sku).toLowerCase().includes(query);
    const barcodeMatch = opt.barcode && String(opt.barcode).toLowerCase().includes(query);
    const priceMatch = (opt.unit_price !== undefined && opt.unit_price !== null) && String(opt.unit_price).includes(query);
    return Boolean(nameMatch || codeMatch || skuMatch || barcodeMatch || priceMatch);
  });
});

// Rendered slice for high performance (virtual windowing)
const renderedOptions = computed(() => {
  return filteredOptions.value.slice(0, props.maxRenderLimit);
});

// Highlight matching search text
const highlightMatch = (text) => {
  const query = debouncedQuery.value.trim();
  if (!query || !text) return text || '';
  const escaped = query.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
  const regex = new RegExp(`(${escaped})`, 'gi');
  return String(text).replace(regex, '<span class="bg-amber-100 text-amber-900 font-bold px-0.5 rounded">$1</span>');
};

const openDropdown = () => {
  if (props.disabled) return;
  isOpen.value = true;
  activeIndex.value = 0;
};

const closeDropdown = () => {
  isOpen.value = false;
  searchQuery.value = '';
  debouncedQuery.value = '';
  isSearching.value = false;
};

const toggleDropdown = () => {
  if (props.disabled) return;
  if (isOpen.value) {
    closeDropdown();
  } else {
    openDropdown();
    nextTick(() => {
      inputRef.value?.focus();
    });
  }
};

const selectOption = (option) => {
  emit('update:modelValue', option.id);
  emit('change', option.id);
  emit('select', option);
  closeDropdown();
};

const clearSelection = () => {
  emit('update:modelValue', '');
  emit('change', '');
  emit('select', null);
  searchQuery.value = '';
  debouncedQuery.value = '';
  isSearching.value = false;
  closeDropdown();
};

// Keyboard navigation
const onKeyDown = () => {
  if (!isOpen.value) {
    openDropdown();
    return;
  }
  if (renderedOptions.value.length === 0) return;
  activeIndex.value = (activeIndex.value + 1) % renderedOptions.value.length;
  scrollToActive();
};

const onKeyUp = () => {
  if (!isOpen.value) {
    openDropdown();
    return;
  }
  if (renderedOptions.value.length === 0) return;
  activeIndex.value = (activeIndex.value - 1 + renderedOptions.value.length) % renderedOptions.value.length;
  scrollToActive();
};

const onKeyEnter = () => {
  if (debounceTimeout) {
    clearTimeout(debounceTimeout);
    debouncedQuery.value = searchQuery.value;
    isSearching.value = false;
  }
  if (isOpen.value && renderedOptions.value[activeIndex.value]) {
    selectOption(renderedOptions.value[activeIndex.value]);
  } else if (!isOpen.value) {
    openDropdown();
  }
};

const scrollToActive = () => {
  nextTick(() => {
    if (listRef.value) {
      const activeEl = listRef.value.children[activeIndex.value];
      if (activeEl) {
        activeEl.scrollIntoView({ block: 'nearest' });
      }
    }
  });
};

// Click outside handling
const handleClickOutside = (e) => {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    closeDropdown();
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  if (debounceTimeout) clearTimeout(debounceTimeout);
  document.removeEventListener('click', handleClickOutside);
});
</script>
