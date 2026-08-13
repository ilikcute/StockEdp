import { ref } from 'vue';
import { productApi } from '@/features/product/api/product_api.js';

export function useInventoryBarcodeScanner(onProductFound) {
    const scanInput = ref('');
    const status = ref('READY'); // READY | SCANNING | FOUND | NOT_FOUND | INACTIVE | ERROR
    const statusMessage = ref('');
    const lastScannedProduct = ref(null);
    const isProcessing = ref(false);

    // Rapid Scan Queue
    const scanQueue = [];

    const processQueue = async () => {
        if (isProcessing.value || scanQueue.length === 0) return;

        isProcessing.value = true;
        const rawBarcode = scanQueue.shift();
        const barcode = String(rawBarcode || '').trim();

        if (!barcode) {
            status.value = 'ERROR';
            statusMessage.value = 'Barcode tidak boleh kosong.';
            isProcessing.value = false;
            processQueue();
            return;
        }

        status.value = 'SCANNING';
        statusMessage.value = `Mencari barcode ${barcode}...`;

        try {
            const response = await productApi.lookupBarcode(barcode);
            const product = response.data?.data;

            status.value = 'FOUND';
            statusMessage.value = `✓ ${product.name} (${product.sku}) ditemukan.`;
            lastScannedProduct.value = product;

            if (typeof onProductFound === 'function') {
                onProductFound(product);
            }
        } catch (err) {
            const errCode = err.response?.data?.code;
            const errMsg = err.response?.data?.message;

            if (errCode === 'BARCODE_NOT_FOUND' || err.response?.status === 404) {
                status.value = 'NOT_FOUND';
                statusMessage.value = `Barcode "${barcode}" tidak ditemukan.`;
            } else if (errCode === 'PRODUCT_INACTIVE' || err.response?.status === 409) {
                status.value = 'INACTIVE';
                statusMessage.value = `Produk dengan barcode "${barcode}" sudah tidak aktif.`;
            } else {
                status.value = 'ERROR';
                statusMessage.value = errMsg || 'Gagal memproses scanner barcode.';
            }
        } finally {
            isProcessing.value = false;

            // Trigger haptic feedback if available (progressive enhancement)
            if (typeof window !== 'undefined' && window.navigator && window.navigator.vibrate) {
                try {
                    if (status.value === 'FOUND') {
                        window.navigator.vibrate(50);
                    } else if (['NOT_FOUND', 'INACTIVE', 'ERROR'].includes(status.value)) {
                        window.navigator.vibrate([100, 50, 100]);
                    }
                } catch {
                    // Ignore vibration failure
                }
            }

            // Process next in queue
            if (scanQueue.length > 0) {
                processQueue();
            }
        }
    };

    const enqueueScan = (barcode) => {
        if (!barcode) return;
        scanQueue.push(barcode);
        processQueue();
    };

    const resetStatus = () => {
        status.value = 'READY';
        statusMessage.value = '';
    };

    return {
        scanInput,
        status,
        statusMessage,
        lastScannedProduct,
        isProcessing,
        enqueueScan,
        resetStatus,
    };
}
