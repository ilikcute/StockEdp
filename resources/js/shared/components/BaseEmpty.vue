<template>
  <div
    class="base-empty"
    role="status"
  >
    <slot name="icon">
      <span
        class="base-empty__icon"
        aria-hidden="true"
      >📭</span>
    </slot>
    <p class="base-empty__message">
      {{ message }}
    </p>
    <slot />
  </div>
</template>

<script setup>
defineProps({
    message: {
        type: String,
        default: 'Tidak ada data yang ditampilkan.',
    },
});
</script>
