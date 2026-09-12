import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '@features/auth/stores/use_auth_store';
import { showToast } from '@/shared/utils/use_toast.js';

export function useDocumentDetail(config) {
    const { store, currentKey, id, toastTitle } = config;

    const authStore = useAuthStore();
    const isProcessing = ref(false);
    const showPostConfirm = ref(false);
    const showCancelConfirm = ref(false);

    const doc = computed(() => store[currentKey]);

    const totalQuantity = computed(() => {
        return (doc.value?.items || []).reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
    });

    const totalAmount = computed(() => {
        return (doc.value?.items || []).reduce((sum, item) => {
            const sub = item.subtotal ?? (Number(item.quantity || 0) * Number(item.unit_price ?? item.product?.unit_price ?? 0));
            return sum + Number(sub);
        }, 0);
    });

    const hasPermission = (permission) => {
        return authStore.hasPermission(permission);
    };

    const openPostConfirm = () => {
        showPostConfirm.value = true;
    };

    const openCancelConfirm = () => {
        showCancelConfirm.value = true;
    };

    const confirmPost = async () => {
        isProcessing.value = true;
        try {
            await store.post(id);
            showPostConfirm.value = false;
            showToast('Dokumen berhasil di-posting.', { type: 'success', title: toastTitle });
        } catch {
            // Error handled in store
        } finally {
            isProcessing.value = false;
        }
    };

    const confirmCancel = async () => {
        isProcessing.value = true;
        try {
            await store.cancel(id);
            showCancelConfirm.value = false;
            showToast('Draft berhasil dibatalkan.', { type: 'success', title: toastTitle });
        } catch {
            // Error handled in store
        } finally {
            isProcessing.value = false;
        }
    };

    onMounted(() => {
        store.fetchById(id);
    });

    return {
        store,
        doc,
        isProcessing,
        showPostConfirm,
        showCancelConfirm,
        totalQuantity,
        totalAmount,
        hasPermission,
        openPostConfirm,
        openCancelConfirm,
        confirmPost,
        confirmCancel,
    };
}