<template>
  <div
    class="base-error"
    role="alert"
  >
    <p class="base-error__message">
      {{ message }}
    </p>
    <slot />
  </div>
</template>

<script setup>
defineProps({
    /**
     * Pesan error yang ditampilkan ke pengguna.
     * Harus berupa kalimat yang dapat dipahami, bukan stack trace.
     */
    message: {
        type: String,
        default: 'Terjadi kesalahan. Silakan coba lagi.',
    },
});
</script>
