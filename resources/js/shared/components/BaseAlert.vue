<template>
  <Transition
    enter-active-class="transition-opacity duration-200 ease-out"
    enter-from-class="opacity-0"
    leave-active-class="transition-opacity duration-150 ease-in"
    leave-to-class="opacity-0"
  >
    <div
      v-if="visible"
      role="alert"
      class="mt-4 flex items-start gap-3 rounded-md border p-4"
      :class="wrapperClasses"
    >
      <svg
        class="mt-0.5 h-5 w-5 shrink-0"
        :class="iconClasses"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="2"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path
          v-if="icon === 'alert'"
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
        />
        <path
          v-else-if="icon === 'check'"
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
        />
        <path
          v-else
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"
        />
      </svg>
      <div class="flex-1 text-sm">
        <p
          v-if="title"
          class="font-medium"
          :class="titleClasses"
        >
          {{ title }}
        </p>
        <p
          :class="[messageClasses, { 'mt-1': title }]"
        >
          {{ message }}
        </p>
      </div>
      <button
        v-if="dismissible"
        type="button"
        :aria-label="dismissLabel"
        class="-m-1 rounded-md p-1 font-bold opacity-70 hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-offset-2"
        :class="dismissClasses"
        @click="dismiss"
      >
        ✕
      </button>
    </div>
  </Transition>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: true,
    },
    variant: {
        type: String,
        default: 'error',
        validator: (value) => ['error', 'success', 'warning', 'info'].includes(value),
    },
    title: {
        type: String,
        default: '',
    },
    message: {
        type: String,
        required: true,
    },
    dismissible: {
        type: Boolean,
        default: false,
    },
    dismissLabel: {
        type: String,
        default: 'Tutup pesan',
    },
});

const emit = defineEmits(['update:modelValue', 'dismiss']);

const visible = ref(props.modelValue);

watch(
    () => props.modelValue,
    (value) => {
        visible.value = value;
    },
);

watch(visible, (value) => {
    emit('update:modelValue', value);
});

const styles = computed(() => ({
    error: {
        wrapper: 'border-rose-200 bg-rose-50',
        icon: 'text-rose-600',
        title: 'text-rose-800',
        message: 'text-rose-700',
        dismiss: 'text-rose-600 focus:ring-rose-500',
        iconName: 'alert',
    },
    success: {
        wrapper: 'border-emerald-200 bg-emerald-50',
        icon: 'text-emerald-600',
        title: 'text-emerald-800',
        message: 'text-emerald-700',
        dismiss: 'text-emerald-600 focus:ring-emerald-500',
        iconName: 'check',
    },
    warning: {
        wrapper: 'border-amber-200 bg-amber-50',
        icon: 'text-amber-600',
        title: 'text-amber-800',
        message: 'text-amber-700',
        dismiss: 'text-amber-600 focus:ring-amber-500',
        iconName: 'alert',
    },
    info: {
        wrapper: 'border-sky-200 bg-sky-50',
        icon: 'text-sky-600',
        title: 'text-sky-800',
        message: 'text-sky-700',
        dismiss: 'text-sky-600 focus:ring-sky-500',
        iconName: 'info',
    },
}[props.variant]));

const wrapperClasses = computed(() => styles.value.wrapper);
const iconClasses = computed(() => styles.value.icon);
const titleClasses = computed(() => styles.value.title);
const messageClasses = computed(() => styles.value.message);
const dismissClasses = computed(() => styles.value.dismiss);
const icon = computed(() => styles.value.iconName);

function dismiss() {
    visible.value = false;
    emit('dismiss');
}
</script>