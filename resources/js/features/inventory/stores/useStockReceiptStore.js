import { createDocumentStore } from './create_document_store';
import { inventoryApi } from '../api/inventoryApi';

export const useStockReceiptStore = createDocumentStore('stockReceipt', {
    collectionKey: 'receipts',
    currentKey: 'currentReceipt',
    api: {
        list: (params) => inventoryApi.getReceipts(params),
        byId: (id) => inventoryApi.getReceiptById(id),
        create: (data) => inventoryApi.createReceipt(data),
        update: (id, data) => inventoryApi.updateReceipt(id, data),
        post: (id) => inventoryApi.postReceipt(id),
        cancel: (id) => inventoryApi.cancelReceipt(id),
    },
    messages: {
        listError: 'Gagal memuat daftar dokumen penerimaan',
        detailError: 'Gagal memuat detail dokumen',
        createError: 'Gagal membuat draft penerimaan',
        updateError: 'Gagal mengubah draft penerimaan',
        postError: 'Gagal memposting dokumen',
        cancelError: 'Gagal membatalkan dokumen',
    },
});