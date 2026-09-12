<template>
  <div class="space-y-2">
    <!-- Success Feedback -->
    <div
      v-if="successMessage"
      role="status"
      class="flex items-center justify-between p-3 text-sm rounded-md bg-emerald-50 text-emerald-800 border border-emerald-200"
    >
      <span>{{ successMessage }}</span>
      <button
        type="button"
        class="ml-2 text-emerald-600 hover:text-emerald-800 focus:outline-none"
        aria-label="Tutup pesan"
        @click="$emit('dismiss')"
      >
        &times;
      </button>
    </div>

    <!-- Generic Error Feedback -->
    <div
      v-if="error"
      role="alert"
      class="flex items-center justify-between p-3 text-sm rounded-md bg-rose-50 text-rose-800 border border-rose-200"
    >
      <span>{{ error }}</span>
      <button
        type="button"
        class="ml-2 text-rose-600 hover:text-rose-800 focus:outline-none"
        aria-label="Tutup pesan error"
        @click="$emit('dismiss')"
      >
        &times;
      </button>
    </div>

    <!-- Field Validation Errors -->
    <div
      v-if="hasValidationErrors"
      role="alert"
      class="p-3 text-sm rounded-md bg-rose-50 text-rose-800 border border-rose-200"
    >
      <div class="flex items-center justify-between mb-1 font-semibold">
        <span>Gagal mengekspor laporan:</span>
        <button
          type="button"
          class="text-rose-600 hover:text-rose-800 focus:outline-none"
          aria-label="Tutup validasi error"
          @click="$emit('dismiss')"
        >
          &times;
        </button>
      </div>
      <ul class="pl-4 list-disc space-y-0.5">
        <template
          v-for="(messages, field) in validationErrors"
          :key="field"
        >
          <li
            v-for="(msg, idx) in messagesList(messages)"
            :key="idx"
          >
            {{ msg }}
          </li>
        </template>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
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

defineEmits(['dismiss']);

const hasValidationErrors = computed(() => {
    return props.validationErrors && Object.keys(props.validationErrors).length > 0;
});

const messagesList = (messages) => {
    if (Array.isArray(messages)) return messages;
    if (typeof messages === 'string') return [messages];
    return [];
};
</script>
