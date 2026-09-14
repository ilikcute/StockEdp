<template>
  <div class="relative">
    <svg
      :class="[
        'pointer-events-none absolute top-1/2 -translate-y-1/2 text-gray-400',
        size === 'sm' ? 'left-2.5 h-3.5 w-3.5' : 'left-3 h-4 w-4'
      ]"
      fill="none"
      viewBox="0 0 24 24"
      stroke-width="2"
      stroke="currentColor"
      aria-hidden="true"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"
      />
    </svg>
    <input
      :id="id"
      v-model="query"
      type="text"
      :placeholder="placeholder"
      :disabled="disabled"
      :class="[
        'block w-full border border-gray-300 bg-white placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-50',
        size === 'sm'
          ? 'rounded-lg py-1.5 pl-8 pr-7 text-xs shadow-2xs'
          : 'rounded-md py-2 pl-10 pr-9 text-sm shadow-xs'
      ]"
    >
    <button
      v-if="clearable && query"
      type="button"
      :aria-label="clearLabel"
      :class="[
        'absolute top-1/2 -translate-y-1/2 rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors cursor-pointer',
        size === 'sm' ? 'right-2 p-0.5' : 'right-2.5 p-0.5'
      ]"
      @click="clear"
    >
      <svg
        :class="size === 'sm' ? 'h-3.5 w-3.5' : 'h-4 w-4'"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="2"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M6 18L18 6M6 6l12 12"
        />
      </svg>
    </button>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    id: {
        type: String,
        default: undefined,
    },
    placeholder: {
        type: String,
        default: 'Cari...',
    },
    debounce: {
        type: Number,
        default: 400,
    },
    clearable: {
        type: Boolean,
        default: true,
    },
    clearLabel: {
        type: String,
        default: 'Hapus pencarian',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: 'sm',
        validator: (value) => ['sm', 'md'].includes(value),
    },
});

const emit = defineEmits(['update:modelValue', 'search', 'clear']);

const query = ref(props.modelValue);

let timer = null;

watch(
    () => props.modelValue,
    (value) => {
        if (value !== query.value) {
            query.value = value;
        }
    },
);

watch(query, (value) => {
    emit('update:modelValue', value);
    clearTimeout(timer);
    timer = setTimeout(() => {
        emit('search', value);
    }, props.debounce);
});

function clear() {
    query.value = '';
    clearTimeout(timer);
    emit('search', '');
    emit('clear');
}
</script>