<template>
  <span
    class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider"
    :class="badgeClass"
  >
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const statusMap = {
    DRAFT: { label: 'Draft', class: 'bg-gray-100 text-gray-700 border border-gray-200' },
    IN_PROGRESS: { label: 'Sedang Dihitung', class: 'bg-blue-50 text-blue-700 border border-blue-200' },
    COUNTED: { label: 'Menunggu Rekonsiliasi', class: 'bg-amber-50 text-amber-700 border border-amber-200' },
    POSTED: { label: 'Diposting', class: 'bg-emerald-50 text-emerald-700 border border-emerald-200' },
    CANCELED: { label: 'Dibatalkan', class: 'bg-red-50 text-red-700 border border-red-200' },
    CANCELLED: { label: 'Dibatalkan', class: 'bg-red-50 text-red-700 border border-red-200' },
};

const entry = computed(() => statusMap[props.status] ?? { label: props.status, class: 'bg-gray-100 text-gray-700' });
const label = computed(() => entry.value.label);
const badgeClass = computed(() => entry.value.class);
</script>
