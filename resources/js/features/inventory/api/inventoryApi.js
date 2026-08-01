import apiClient from '@/shared/api/api_client';

export const inventoryApi = {
    getBalances(params) {
        return apiClient.get('/inventory/balances', { params });
    },
    
    getMovements(params) {
        return apiClient.get('/inventory/movements', { params });
    },
    
    getMovementById(id) {
        return apiClient.get(`/inventory/movements/${id}`);
    },

    getReceipts(params) {
        return apiClient.get('/stock-receipts', { params });
    },

    getReceiptById(id) {
        return apiClient.get(`/stock-receipts/${id}`);
    },

    createReceipt(data) {
        return apiClient.post('/stock-receipts', data);
    },

    updateReceipt(id, data) {
        return apiClient.patch(`/stock-receipts/${id}`, data);
    },

    postReceipt(id) {
        return apiClient.post(`/stock-receipts/${id}/post`);
    },

    cancelReceipt(id) {
        return apiClient.post(`/stock-receipts/${id}/cancel`);
    },

    getIssues(params) {
        return apiClient.get('/stock-issues', { params });
    },

    getIssueById(id) {
        return apiClient.get(`/stock-issues/${id}`);
    },

    createIssue(data) {
        return apiClient.post('/stock-issues', data);
    },

    updateIssue(id, data) {
        return apiClient.patch(`/stock-issues/${id}`, data);
    },

    postIssue(id) {
        return apiClient.post(`/stock-issues/${id}/post`);
    },

    cancelIssue(id) {
        return apiClient.post(`/stock-issues/${id}/cancel`);
    },

    getTransfers(params) {
        return apiClient.get('/stock-transfers', { params });
    },

    getTransferById(id) {
        return apiClient.get(`/stock-transfers/${id}`);
    },

    createTransfer(data) {
        return apiClient.post('/stock-transfers', data);
    },

    updateTransfer(id, data) {
        return apiClient.patch(`/stock-transfers/${id}`, data);
    },

    sendTransfer(id) {
        return apiClient.post(`/stock-transfers/${id}/send`);
    },

    receiveTransfer(id) {
        return apiClient.post(`/stock-transfers/${id}/receive`);
    },

    cancelTransfer(id) {
        return apiClient.post(`/stock-transfers/${id}/cancel`);
    }
};
