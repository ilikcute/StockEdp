<template>
  <span :class="badgeClass">
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  type: {
    type: String,
    default: '',
  },
  priority: {
    type: String,
    default: '',
  },
});

const label = computed(() => {
  if (props.type) {
    switch (props.type) {
      case 'INBOUND_COVERED':
        return 'Ditutup Inbound';
      case 'INTERNAL_TRANSFER':
        return 'Transfer Internal';
      case 'MIXED':
        return 'Sebagian Transfer & Reorder';
      case 'EXTERNAL_REORDER':
        return 'Reorder Eksternal';
      default:
        return props.type;
    }
  }

  if (props.priority) {
    switch (props.priority) {
      case 'CRITICAL':
        return 'Kritis (0 / Minus)';
      case 'WARNING':
        return 'Peringatan (< Min)';
      default:
        return props.priority;
    }
  }

  return '';
});

const badgeClass = computed(() => {
  const base = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold';

  if (props.type) {
    switch (props.type) {
      case 'INBOUND_COVERED':
        return `${base} bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20`;
      case 'INTERNAL_TRANSFER':
        return `${base} bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20`;
      case 'MIXED':
        return `${base} bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20`;
      case 'EXTERNAL_REORDER':
        return `${base} bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20`;
      default:
        return `${base} bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20`;
    }
  }

  if (props.priority) {
    switch (props.priority) {
      case 'CRITICAL':
        return `${base} bg-rose-50 text-rose-700 font-bold ring-1 ring-inset ring-rose-600/30`;
      case 'WARNING':
        return `${base} bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20`;
      default:
        return `${base} bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20`;
    }
  }

  return `${base} bg-gray-50 text-gray-700 ring-1 ring-inset ring-gray-600/20`;
});
</script>
