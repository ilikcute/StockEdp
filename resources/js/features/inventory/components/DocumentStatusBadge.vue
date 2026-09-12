<template>
  <span
    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
    :class="badgeClass"
  >
    {{ status }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        default: '',
    },
});

const badgeClass = computed(() => ({
    'bg-yellow-100 text-yellow-800': props.status === 'DRAFT',
    'bg-green-100 text-green-800': props.status === 'POSTED',
    'bg-gray-100 text-gray-800': props.status === 'CANCELED',
}));
</script>