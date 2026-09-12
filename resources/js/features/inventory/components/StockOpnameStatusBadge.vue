<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
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
    DRAFT: { label: 'Draft', class: 'bg-gray-100 text-gray-700' },
    IN_PROGRESS: { label: 'Sedang Dihitung', class: 'bg-blue-100 text-blue-800' },
    COUNTED: { label: 'Menunggu Rekonsiliasi', class: 'bg-yellow-100 text-yellow-800' },
    POSTED: { label: 'Diposting', class: 'bg-green-100 text-green-800' },
    CANCELED: { label: 'Dibatalkan', class: 'bg-red-100 text-red-700' },
    CANCELLED: { label: 'Dibatalkan', class: 'bg-red-100 text-red-700' },
};

const entry = computed(() => statusMap[props.status] ?? { label: props.status, class: 'bg-gray-100 text-gray-700' });
const label = computed(() => entry.value.label);
const badgeClass = computed(() => entry.value.class);
</script>
