<template>
  <div
    class="base-loading"
    role="status"
    :aria-label="label"
  >
    <span
      class="base-loading__spinner"
      aria-hidden="true"
    />
    <span
      v-if="label"
      class="base-loading__label"
    >{{ label }}</span>
  </div>
</template>

<script setup>
defineProps({
    label: {
        type: String,
        default: 'Memuat data...',
    },
});
</script>
