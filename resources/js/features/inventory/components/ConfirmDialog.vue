<template>
  <!-- Confirmation dialog (backdrop + modal) -->
  <div
    class="fixed inset-0 z-50 overflow-y-auto"
    role="dialog"
    aria-modal="true"
    :aria-labelledby="titleId"
  >
    <!-- Backdrop -->
    <div
      class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
      aria-hidden="true"
      @click="$emit('cancel')"
    />

    <div class="flex min-h-screen items-center justify-center px-4 py-8">
      <div
        ref="panelRef"
        class="relative w-full max-w-md rounded-lg bg-white shadow-xl"
        @keydown.escape="$emit('cancel')"
      >
        <!-- Header -->
        <div class="px-6 pt-6 pb-4">
          <div class="flex items-start gap-4">
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
              :class="iconBg"
            >
              <svg
                class="h-5 w-5"
                :class="iconColor"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                />
              </svg>
            </div>
            <div>
              <h3
                :id="titleId"
                class="text-base font-semibold leading-6 text-gray-900"
              >
                {{ title }}
              </h3>
              <div class="mt-2 text-sm text-gray-500 space-y-1">
                <slot />
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex flex-row-reverse gap-2 rounded-b-lg bg-gray-50 px-6 py-4">
          <button
            ref="confirmBtnRef"
            type="button"
            :disabled="loading"
            class="inline-flex justify-center rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
            :class="confirmBtnClass"
            @click="$emit('confirm')"
          >
            <span
              v-if="loading"
              class="mr-2"
              aria-hidden="true"
            >
              <svg
                class="h-4 w-4 animate-spin"
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
                  d="M4 12a8 8 0 018-8v8z"
                />
              </svg>
            </span>
            {{ loading ? 'Memproses...' : confirmText }}
          </button>
          <button
            type="button"
            :disabled="loading"
            class="inline-flex justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-50"
            @click="$emit('cancel')"
          >
            Batal
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    confirmText: { type: String, default: 'Ya, Lanjutkan' },
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'danger', 'warning'].includes(v),
    },
    loading: { type: Boolean, default: false },
    titleId: { type: String, default: 'confirm-dialog-title' },
});

defineEmits(['confirm', 'cancel']);

const confirmBtnRef = ref(null);
const panelRef = ref(null);

const iconBg = {
    primary: 'bg-blue-100',
    danger: 'bg-red-100',
    warning: 'bg-yellow-100',
}[props.variant];

const iconColor = {
    primary: 'text-blue-600',
    danger: 'text-red-600',
    warning: 'text-yellow-600',
}[props.variant];

const confirmBtnClass = {
    primary: 'bg-blue-600 hover:bg-blue-500 focus-visible:outline-blue-600',
    danger: 'bg-red-600 hover:bg-red-500 focus-visible:outline-red-600',
    warning: 'bg-yellow-600 hover:bg-yellow-500 focus-visible:outline-yellow-600',
}[props.variant];

onMounted(() => {
    confirmBtnRef.value?.focus();
});
</script>
