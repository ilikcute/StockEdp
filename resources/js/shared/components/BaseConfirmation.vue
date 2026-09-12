<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200 ease-out"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-150 ease-in"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        :aria-describedby="descriptionId || undefined"
        @keydown.escape="onCancel"
      >
        <div
          class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs"
          aria-hidden="true"
          @click="onCancel"
        />
        <div class="flex min-h-screen items-center justify-center px-4 py-8">
          <div
            ref="panelRef"
            class="relative w-full max-w-md rounded-lg bg-white shadow-xl"
          >
            <div class="px-6 pt-6 pb-4">
              <div class="flex items-start gap-4">
                <div
                  class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                  :class="iconBgClasses"
                >
                  <svg
                    class="h-5 w-5"
                    :class="iconColorClasses"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                    aria-hidden="true"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                    />
                  </svg>
                </div>
                <div class="min-w-0 flex-1">
                  <h2
                    :id="titleId"
                    class="text-base font-semibold leading-6 text-gray-900"
                  >
                    {{ title }}
                  </h2>
                  <div
                    v-if="description || $slots.description"
                    :id="descriptionId"
                    class="mt-2 space-y-1 text-sm text-gray-500"
                  >
                    <slot name="description">
                      {{ description }}
                    </slot>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex flex-row-reverse gap-2 rounded-b-lg bg-gray-50 px-6 py-4">
              <button
                ref="confirmBtnRef"
                type="button"
                :disabled="loading"
                class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-semibold text-white shadow-sm disabled:cursor-not-allowed disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-offset-2"
                :class="confirmButtonClasses"
                @click="onConfirm"
              >
                <svg
                  v-if="loading"
                  class="mr-2 h-4 w-4 animate-spin"
                  fill="none"
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  />
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8v8z"
                  />
                </svg>
                {{ loading ? loadingLabel || confirmLabel : confirmLabel }}
              </button>
              <button
                type="button"
                :disabled="loading"
                class="inline-flex items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                @click="onCancel"
              >
                {{ cancelLabel }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, ref, useId } from 'vue';

const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true,
    },
    title: {
        type: String,
        default: 'Konfirmasi',
    },
    description: {
        type: String,
        default: 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    },
    confirmLabel: {
        type: String,
        default: 'Ya, Lanjutkan',
    },
    cancelLabel: {
        type: String,
        default: 'Batal',
    },
    loadingLabel: {
        type: String,
        default: '',
    },
    /**
     * Tampilkan tombol konfirmasi dengan warna bahaya (merah).
     * Disarankan untuk tindakan yang bersifat destruktif.
     */
    danger: {
        type: Boolean,
        default: false,
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'danger', 'warning'].includes(value),
    },
    loading: {
        type: Boolean,
        default: false,
    },
    titleId: {
        type: String,
        default: '',
    },
    descriptionId: {
        type: String,
        default: '',
    },
});

defineOptions({ inheritAttrs: false });

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel']);

const uid = useId();

const confirmBtnRef = ref(null);
const panelRef = ref(null);

const resolvedVariant = computed(() => (props.danger ? 'danger' : props.variant));

const iconBgClasses = computed(() => ({
    primary: 'bg-indigo-100',
    danger: 'bg-red-100',
    warning: 'bg-yellow-100',
}[resolvedVariant.value]));

const iconColorClasses = computed(() => ({
    primary: 'text-indigo-600',
    danger: 'text-red-600',
    warning: 'text-yellow-600',
}[resolvedVariant.value]));

const confirmButtonClasses = computed(() => ({
    primary: 'bg-indigo-600 hover:bg-indigo-500 focus:ring-indigo-500',
    danger: 'bg-red-600 hover:bg-red-500 focus:ring-red-500',
    warning: 'bg-yellow-600 hover:bg-yellow-500 focus:ring-yellow-500',
}[resolvedVariant.value]));

const titleId = computed(() => props.titleId || `base-confirmation-title-${uid}`);
const descriptionId = computed(() => props.descriptionId || `base-confirmation-description-${uid}`);

function onConfirm() {
    if (!props.loading) {
        emit('confirm');
    }
}

function onCancel() {
    if (!props.loading) {
        emit('update:modelValue', false);
        emit('cancel');
    }
}

onMounted(() => {
    confirmBtnRef.value?.focus();
});
</script>