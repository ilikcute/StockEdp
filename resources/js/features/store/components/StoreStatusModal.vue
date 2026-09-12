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
import { useStoreStore } from '../stores/use_store_store';
import BaseConfirmation from '@/shared/components/BaseConfirmation.vue';

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true
    },
    storeData: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'status-changed']);
const store = useStoreStore();

const isActive = computed(() => props.storeData?.is_active ?? false);
const title = computed(() => isActive.value ? 'Nonaktifkan Toko' : 'Aktifkan Toko');
const message = computed(() => 
    isActive.value 
        ? `Apakah Anda yakin ingin menonaktifkan toko "${props.storeData?.name}"? Toko yang dinonaktifkan tidak dapat dipilih untuk alokasi baru.`
        : `Apakah Anda yakin ingin mengaktifkan kembali toko "${props.storeData?.name}"?`
);

const confirmText = computed(() => isActive.value ? 'Nonaktifkan' : 'Aktifkan');
const confirmButtonClass = computed(() => 
    isActive.value 
        ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500 text-white' 
        : 'bg-green-600 hover:bg-green-700 focus:ring-green-500 text-white'
);

const handleConfirm = async () => {
    if (!props.storeData) return;
    
    const success = await store.changeStatus(props.storeData.id, !isActive.value);
    if (success) {
        emit('status-changed');
        emit('close');
    }
};
</script>
