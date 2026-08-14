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
        return `${base} bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800`;
      case 'INTERNAL_TRANSFER':
        return `${base} bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800`;
      case 'MIXED':
        return `${base} bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800`;
      case 'EXTERNAL_REORDER':
        return `${base} bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800`;
      default:
        return `${base} bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300`;
    }
  }

  if (props.priority) {
    switch (props.priority) {
      case 'CRITICAL':
        return `${base} bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300 font-bold border border-rose-300 dark:border-rose-700`;
      case 'WARNING':
        return `${base} bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800`;
      default:
        return `${base} bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300`;
    }
  }

  return `${base} bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300`;
});
</script>
