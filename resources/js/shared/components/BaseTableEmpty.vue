<template>
  <tr>
    <td
      :colspan="colspan"
      class="py-12 text-center text-xs text-gray-400"
    >
      <div class="flex flex-col items-center justify-center gap-1.5">
        <slot name="icon">
          <svg
            class="w-8 h-8 text-gray-300"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
            />
          </svg>
        </slot>
        <span class="font-medium text-gray-500">
          <slot>{{ message }}</slot>
        </span>
        <span
          v-if="hint"
          class="text-[11px] text-gray-400"
        >
          {{ hint }}
        </span>
        <slot name="action" />
      </div>
    </td>
  </tr>
</template>

<script setup>
defineProps({
  colspan: {
    type: [Number, String],
    default: 8,
  },
  message: {
    type: String,
    default: 'Tidak ada data yang cocok.',
  },
  hint: {
    type: String,
    default: '',
  },
});
</script>
