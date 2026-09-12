<template>
  <div class="space-y-2">
    <div class="flex flex-col sm:flex-row sm:items-center space-y-1 sm:space-y-0 sm:space-x-2">
      <button
        type="button"
        :disabled="disabled || loading"
        :aria-busy="loading ? 'true' : 'false'"
        :aria-disabled="disabled || loading ? 'true' : 'false'"
        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white transition-colors bg-emerald-600 rounded-md shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed"
        @click="$emit('export')"
      >
        <svg
          v-if="loading"
          class="w-4 h-4 mr-2 text-white animate-spin"
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
          class="w-4 h-4 mr-2"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          />
        </svg>
        <span v-if="loading">Mengekspor...</span>
        <span v-else>Ekspor CSV</span>
      </button>

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
import ReportCsvExportFeedback from './ReportCsvExportFeedback.vue';

defineProps({
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

defineEmits(['export', 'dismiss']);
</script>
