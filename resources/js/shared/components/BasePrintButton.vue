<template>
  <button
    type="button"
    :disabled="disabled || loading"
    :class="buttonClasses"
    @click="$emit('click', $event)"
  >
    <!-- Spinner saat Loading / Preparing Print -->
    <svg
      v-if="loading"
      class="animate-spin -ml-0.5 h-3.5 w-3.5"
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
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
      />
    </svg>

    <!-- Printer Icon -->
    <svg
      v-else
      class="w-3.5 h-3.5 shrink-0"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
      />
    </svg>

    <span>{{ loading ? loadingLabel : label }}</span>
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: {
    type: String,
    default: 'Cetak Dokumen',
  },
  loadingLabel: {
    type: String,
    default: 'Menyiapkan...',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  variant: {
    type: String,
    default: 'outline', // 'primary' | 'secondary' | 'outline'
    validator: (v) => ['primary', 'secondary', 'outline'].includes(v),
  },
  size: {
    type: String,
    default: 'sm', // 'sm' | 'md'
  },
});

defineEmits(['click']);

const buttonClasses = computed(() => {
  const base = [
    'inline-flex items-center gap-1.5 font-semibold rounded-lg shadow-2xs transition-all cursor-pointer',
    'focus:outline-none focus:ring-2 focus:ring-indigo-500/20 active:scale-98',
    'disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none',
  ];

  if (props.size === 'md') {
    base.push('px-3.5 py-2 text-sm');
  } else {
    base.push('px-3 py-1.5 text-xs');
  }

  if (props.variant === 'primary') {
    base.push('bg-indigo-600 text-white hover:bg-indigo-700 border border-transparent');
  } else if (props.variant === 'secondary') {
    base.push('bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-300');
  } else {
    // outline
    base.push('bg-white text-gray-700 hover:bg-gray-50 border border-gray-300');
  }

  return base.join(' ');
});
</script>
