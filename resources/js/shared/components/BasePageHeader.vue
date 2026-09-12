<template>
  <div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
      <h1 class="text-xl font-semibold text-gray-900">
        {{ title }}
      </h1>
      <p
        v-if="description"
        class="mt-2 text-sm text-gray-700"
      >
        {{ description }}
      </p>
    </div>
    <div
      v-if="$slots.actions"
      class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none flex items-center gap-2"
    >
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
});
</script>