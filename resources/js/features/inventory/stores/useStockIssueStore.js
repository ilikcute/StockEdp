import { createDocumentStore } from './create_document_store';
import { inventoryApi } from '../api/inventoryApi';

export const useStockIssueStore = createDocumentStore('stockIssue', {
    collectionKey: 'issues',
    currentKey: 'currentIssue',
    api: {
        list: (params) => inventoryApi.getIssues(params),
        byId: (id) => inventoryApi.getIssueById(id),
        create: (data) => inventoryApi.createIssue(data),
        update: (id, data) => inventoryApi.updateIssue(id, data),
        post: (id) => inventoryApi.postIssue(id),
        cancel: (id) => inventoryApi.cancelIssue(id),
    },
    messages: {
        listError: 'Gagal memuat daftar dokumen pengeluaran',
        detailError: 'Gagal memuat detail dokumen',
        createError: 'Gagal membuat draft pengeluaran',
        updateError: 'Gagal mengubah draft pengeluaran',
        postError: 'Gagal memposting dokumen, stok mungkin tidak mencukupi.',
        cancelError: 'Gagal membatalkan dokumen',
    },
});