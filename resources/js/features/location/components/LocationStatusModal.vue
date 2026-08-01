<template>
  <BaseConfirmation
    :is-open="isOpen"
    :title="title"
    :message="message"
    :confirm-text="confirmText"
    :confirm-button-class="confirmButtonClass"
    :is-loading="store.isLoading"
    @confirm="handleConfirm"
    @cancel="$emit('close')"
  />
</template>

<script setup>
import { computed } from 'vue';
import { useLocationStore } from '../stores/use_location_store';
import BaseConfirmation from '@/shared/components/BaseConfirmation.vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true
    },
    location: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'status-changed']);
const store = useLocationStore();

const isActive = computed(() => props.location?.is_active ?? false);
const title = computed(() => isActive.value ? 'Nonaktifkan Lokasi' : 'Aktifkan Lokasi');
const message = computed(() => 
    isActive.value 
        ? `Apakah Anda yakin ingin menonaktifkan lokasi "${props.location?.name}"? Lokasi yang dinonaktifkan tidak dapat digunakan dalam transaksi baru.`
        : `Apakah Anda yakin ingin mengaktifkan kembali lokasi "${props.location?.name}"?`
);

const confirmText = computed(() => isActive.value ? 'Nonaktifkan' : 'Aktifkan');
const confirmButtonClass = computed(() => 
    isActive.value 
        ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500 text-white' 
        : 'bg-green-600 hover:bg-green-700 focus:ring-green-500 text-white'
);

const handleConfirm = async () => {
    if (!props.location) return;
    
    const success = await store.changeStatus(props.location.id, !isActive.value);
    if (success) {
        emit('status-changed');
        emit('close');
    }
};
</script>
