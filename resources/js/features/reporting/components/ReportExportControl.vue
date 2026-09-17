<template>
  <div class="space-y-2">
    <div class="flex flex-col sm:flex-row sm:items-center space-y-1 sm:space-y-0 sm:space-x-2">
      <!-- Split Button Container -->
      <div
        ref="dropdownRef"
        class="relative inline-flex items-stretch rounded-lg shadow-xs"
      >
        <!-- Primary Action: Excel Export -->
        <button
          type="button"
          :disabled="disabled || loading"
          :aria-busy="loading ? 'true' : 'false'"
          :aria-disabled="disabled || loading ? 'true' : 'false'"
          :class="[
            'inline-flex items-center justify-center font-semibold text-white transition-colors bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap cursor-pointer rounded-l-lg',
            size === 'sm'
              ? 'gap-1.5 px-3 py-1.5 text-xs'
              : 'gap-2 px-4 py-2 text-sm'
          ]"
          title="Unduh laporan dalam format Microsoft Excel (.xlsx)"
          @click="handleExport('xlsx')"
        >
          <svg
            v-if="loading"
            :class="size === 'sm' ? 'w-3.5 h-3.5 mr-1 text-white animate-spin' : 'w-4 h-4 mr-1.5 text-white animate-spin'"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            aria-hidden="true"
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
              d="4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
          </svg>
          <svg
            v-else
            :class="size === 'sm' ? 'w-3.5 h-3.5' : 'w-4 h-4'"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
            aria-hidden="true"
          >
            <!-- Spreadsheet Table Grid Icon -->
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M3 10h18M3 14h18m-9-4v8m-7 4h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
          <span v-if="loading">Mengekspor...</span>
          <span v-else>Ekspor Excel</span>
        </button>

        <!-- Dropdown Toggle for Format Options -->
        <button
          type="button"
          :disabled="disabled || loading"
          :aria-expanded="isDropdownOpen ? 'true' : 'false'"
          aria-haspopup="true"
          :class="[
            'inline-flex items-center justify-center font-semibold text-white transition-colors bg-emerald-600 hover:bg-emerald-700 border-l border-emerald-700/60 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer rounded-r-lg',
            size === 'sm' ? 'px-2 py-1.5 text-xs' : 'px-2.5 py-2 text-sm'
          ]"
          title="Pilih format ekspor lain"
          @click="toggleDropdown"
        >
          <svg
            class="w-3.5 h-3.5 transition-transform duration-200"
            :class="{ 'rotate-180': isDropdownOpen }"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M19 9l-7 7-7-7"
            />
          </svg>
        </button>

        <!-- Dropdown Menu -->
        <div
          v-if="isDropdownOpen"
          class="absolute right-0 top-full mt-1.5 w-52 bg-white rounded-lg shadow-lg border border-gray-200 py-1.5 z-50 text-left"
          role="menu"
          aria-orientation="vertical"
        >
          <button
            type="button"
            class="w-full px-3 py-2 text-xs text-gray-700 hover:bg-emerald-50 hover:text-emerald-800 flex items-center gap-2 transition-colors cursor-pointer text-left"
            role="menuitem"
            @click="handleExport('xlsx')"
          >
            <div class="w-6 h-6 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-[10px] shrink-0">
              XLS
            </div>
            <div class="flex-1">
              <div class="font-semibold text-gray-800">
                Microsoft Excel (.xlsx)
              </div>
              <div class="text-[10px] text-gray-400">
                Format native dengan styling
              </div>
            </div>
          </button>

          <div class="my-1 border-t border-gray-100" />

          <button
            type="button"
            class="w-full px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 hover:text-gray-900 flex items-center gap-2 transition-colors cursor-pointer text-left"
            role="menuitem"
            @click="handleExport('csv')"
          >
            <div class="w-6 h-6 rounded bg-gray-100 text-gray-600 flex items-center justify-center font-bold text-[10px] shrink-0">
              CSV
            </div>
            <div class="flex-1">
              <div class="font-semibold text-gray-800">
                File CSV (.csv)
              </div>
              <div class="text-[10px] text-gray-400">
                Format teks pemisah koma
              </div>
            </div>
          </button>
        </div>
      </div>

      <p
        v-if="disabledReason && disabled && !loading"
        class="text-xs text-gray-500 self-center"
      >
        {{ disabledReason }}
      </p>
    </div>

    <ReportCsvExportFeedback
      :error="error"
      :status="status"
      :validation-errors="validationErrors"
      :success-message="successMessage"
      @dismiss="$emit('dismiss')"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import ReportCsvExportFeedback from './ReportCsvExportFeedback.vue';

const props = defineProps({
    size: {
        type: String,
        default: 'md',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    disabledReason: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: null,
    },
    status: {
        type: Number,
        default: null,
    },
    validationErrors: {
        type: Object,
        default: () => ({}),
    },
    successMessage: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['export', 'dismiss']);

const isDropdownOpen = ref(false);
const dropdownRef = ref(null);

const toggleDropdown = () => {
    if (props.disabled || props.loading) return;
    isDropdownOpen.value = !isDropdownOpen.value;
};

const handleExport = (format = 'xlsx') => {
    isDropdownOpen.value = false;
    emit('export', format);
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isDropdownOpen.value = false;
    }
};

const handleKeydown = (event) => {
    if (event.key === 'Escape' && isDropdownOpen.value) {
        isDropdownOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
});
</script>
