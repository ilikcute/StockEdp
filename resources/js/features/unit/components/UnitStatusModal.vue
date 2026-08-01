<template>
  <BaseConfirmation
    v-model="visible"
    :title="title"
    :description="description"
    :confirm-label="confirmLabel"
    :danger="isDanger"
    :loading="loading"
    @confirm="onConfirm"
    @cancel="onCancel"
  />
</template>

<script setup>
import { computed } from 'vue';
import BaseConfirmation from '@shared/components/BaseConfirmation.vue';

const props = defineProps({
    modelValue: { type: Boolean, required: true },
    unit: { type: Object, default: null },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel']);

const visible = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

const isCurrentlyActive = computed(() => props.unit?.is_active ?? true);
const isDanger = computed(() => isCurrentlyActive.value);

const title = computed(() =>
    isCurrentlyActive.value ? 'Nonaktifkan Satuan' : 'Aktifkan Satuan',
);

const description = computed(() => {
    const name = props.unit?.name ?? '';
    return isCurrentlyActive.value
        ? `Apakah Anda yakin ingin menonaktifkan satuan "${name}"? Satuan yang tidak aktif tidak akan muncul pada pilihan baru, namun tetap tampil pada data historis.`
        : `Apakah Anda yakin ingin mengaktifkan kembali satuan "${name}"?`;
});

const confirmLabel = computed(() =>
    isCurrentlyActive.value ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan',
);

function onConfirm() {
    emit('confirm');
}

function onCancel() {
    emit('cancel');
}
</script>
