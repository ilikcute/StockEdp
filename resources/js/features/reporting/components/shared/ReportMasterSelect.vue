<template>
  <div>
    <label class="block text-xs font-medium text-gray-700">{{ label }}</label>
    <select
      :value="modelValue"
      :disabled="disabled"
      class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
      @change="emit('update:modelValue', $event.target.value)"
    >
      <option value="">
        {{ placeholder }}
      </option>
      <option
        v-for="item in options"
        :key="item.id"
        :value="item.id"
      >
        {{ item.name }}
      </option>
    </select>
    <p
      v-if="errorMessage"
      class="mt-1 text-xs text-amber-600"
    >
      {{ errorMessage }}
    </p>
  </div>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
    placeholder: { type: String, required: true },
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    disabled: { type: Boolean, default: false },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);
</script>
