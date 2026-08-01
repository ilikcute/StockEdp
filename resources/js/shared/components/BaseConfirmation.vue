<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="base-confirmation-backdrop"
      role="dialog"
      aria-modal="true"
      :aria-labelledby="titleId"
      :aria-describedby="descriptionId"
      @click.self="onCancel"
    >
      <div class="base-confirmation-dialog">
        <h2
          :id="titleId"
          class="base-confirmation-dialog__title"
        >
          {{ title }}
        </h2>
        <p
          :id="descriptionId"
          class="base-confirmation-dialog__description"
        >
          {{ description }}
        </p>
        <div class="base-confirmation-dialog__actions">
          <button
            type="button"
            class="base-confirmation-dialog__cancel"
            :disabled="loading"
            @click="onCancel"
          >
            {{ cancelLabel }}
          </button>
          <button
            type="button"
            class="base-confirmation-dialog__confirm"
            :class="{ 'base-confirmation-dialog__confirm--danger': danger }"
            :disabled="loading"
            @click="onConfirm"
          >
            <span
              v-if="loading"
              aria-hidden="true"
            >…</span>
            <span v-else>{{ confirmLabel }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';

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
    /**
     * Tampilkan tombol konfirmasi dengan warna bahaya (merah).
     * Gunakan untuk tindakan yang bersifat destruktif.
     */
    danger: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel']);

const titleId = computed(() => 'confirmation-title');
const descriptionId = computed(() => 'confirmation-description');

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
</script>
