<template>
  <button
    v-bind="$attrs"
    :type="type"
    :disabled="disabled || loading"
    class="inline-flex items-center justify-center rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
    :class="[
      sizeClasses,
      variantClasses,
      { 'w-full': block, 'cursor-pointer': !(disabled || loading) },
    ]"
  >
    <svg
      v-if="loading"
      class="mr-2 h-4 w-4 animate-spin"
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
        d="M4 12a8 8 0 018-8v8z"
      />
    </svg>
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'button',
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'danger', 'warning', 'ghost'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md'].includes(value),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    block: {
        type: Boolean,
        default: false,
    },
});

defineOptions({ inheritAttrs: false });

const sizeClasses = computed(() => ({
    xs: 'px-2.5 py-1.5 text-xs',
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2 text-sm',
}[props.size]));

const variantClasses = computed(() => ({
    primary: 'bg-indigo-600 text-white shadow-xs hover:bg-indigo-700 focus:ring-indigo-500',
    secondary: 'border border-gray-300 bg-white text-gray-700 shadow-xs hover:bg-gray-50 focus:ring-indigo-500',
    danger: 'bg-red-600 text-white shadow-xs hover:bg-red-700 focus:ring-red-500',
    warning: 'bg-amber-500 text-white shadow-xs hover:bg-amber-600 focus:ring-amber-500',
    ghost: 'text-indigo-600 hover:bg-indigo-50 hover:text-indigo-900 focus:ring-indigo-500',
}[props.variant]));
</script>