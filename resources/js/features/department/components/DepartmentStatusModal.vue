<template>
  <BaseConfirmation
    :is-open="isOpen"
    :title="title"
    :message="message"
    :confirm-text="confirmText"
    :confirm-button-class="confirmButtonClass"
    :is-loading="departmentStore.isLoading"
    @confirm="handleConfirm"
    @cancel="$emit('close')"
  />
</template>

<script setup>
import { computed } from 'vue';
import { useDepartmentStore } from '../stores/use_department_store';
import BaseConfirmation from '@/shared/components/BaseConfirmation.vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true,
    },
    departmentData: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'status-changed']);
const departmentStore = useDepartmentStore();

const isActive = computed(() => props.departmentData?.is_active ?? false);
const title = computed(() => isActive.value ? 'Nonaktifkan Departemen' : 'Aktifkan Departemen');
const message = computed(() =>
    isActive.value
        ? `Apakah Anda yakin ingin menonaktifkan departemen "${props.departmentData?.name}"? Departemen nonaktif tidak akan muncul di opsi pengeluaran barang.`
        : `Apakah Anda yakin ingin mengaktifkan kembali departemen "${props.departmentData?.name}"?`
);

const confirmText = computed(() => isActive.value ? 'Nonaktifkan' : 'Aktifkan');
const confirmButtonClass = computed(() =>
    isActive.value
        ? 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500 text-white'
        : 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500 text-white'
);

const handleConfirm = async () => {
    if (!props.departmentData) return;

    const success = await departmentStore.changeStatus(props.departmentData.id, !isActive.value);
    if (success) {
        emit('status-changed');
        emit('close');
    }
};
</script>
