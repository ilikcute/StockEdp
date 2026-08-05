import { defineStore } from 'pinia';
import { reportingApi } from '../api/reportingApi';
import { extractCsvFilename, downloadCsvBlob, normalizeCsvExportError } from '../utils/reportCsvDownload';

const fallbackFilenames = {
    'inventory-balances': 'inventory-balances.csv',
    'low-stock': 'low-stock.csv',
    'stock-card': 'stock-card.csv',
    'stock-receipts': 'stock-receipts.csv',
    'stock-issues': 'stock-issues.csv',
    'stock-transfers': 'stock-transfers.csv',
    'stock-adjustments': 'stock-adjustments.csv',
    'stock-opnames': 'stock-opnames.csv',
};

const exportHandlers = {
    'inventory-balances': (params) => reportingApi.exportInventoryBalances(params),
    'low-stock': (params) => reportingApi.exportLowStock(params),
    'stock-card': (params) => reportingApi.exportStockCard(params),
    'stock-receipts': (params) => reportingApi.exportStockReceipts(params),
    'stock-issues': (params) => reportingApi.exportStockIssues(params),
    'stock-transfers': (params) => reportingApi.exportStockTransfers(params),
    'stock-adjustments': (params) => reportingApi.exportStockAdjustments(params),
    'stock-opnames': (params) => reportingApi.exportStockOpnames(params),
};

export const useReportCsvExportStore = defineStore('reportCsvExport', {
    state: () => ({
        exporting: {},
        status: {},
        errors: {},
        validationErrors: {},
        successMessages: {},
        filenames: {},
    }),

    getters: {
        isExporting: (state) => (reportKey) => Boolean(state.exporting[reportKey]),
        errorFor: (state) => (reportKey) => state.errors[reportKey] ?? null,
        statusFor: (state) => (reportKey) => state.status[reportKey] ?? null,
        validationErrorsFor: (state) => (reportKey) => state.validationErrors[reportKey] ?? {},
        successFor: (state) => (reportKey) => state.successMessages[reportKey] ?? null,
    },

    actions: {
        async exportReport(reportKey, params = {}) {
            if (this.exporting[reportKey]) {
                return false;
            }

            const handler = exportHandlers[reportKey];
            if (!handler) {
                this.errors[reportKey] = 'Jenis laporan tidak dikenali.';
                return false;
            }

            this.clearFeedback(reportKey);
            this.exporting[reportKey] = true;

            try {
                const response = await handler({ ...params });

                if (!response?.data || !(response.data instanceof Blob)) {
                    this.errors[reportKey] = 'Response export tidak valid.';
                    return false;
                }

                const disposition = response.headers?.['content-disposition'] || response.headers?.['Content-Disposition'];
                const fallback = fallbackFilenames[reportKey] || `${reportKey}.csv`;
                const filename = extractCsvFilename(disposition, fallback);

                downloadCsvBlob(response.data, filename);

                this.successMessages[reportKey] = `File ${filename} berhasil diunduh.`;
                this.filenames[reportKey] = filename;
                this.status[reportKey] = response.status;
                return true;
            } catch (error) {
                const normalized = await normalizeCsvExportError(error);
                this.status[reportKey] = normalized.status || 500;

                if (normalized.status === 403) {
                    this.errors[reportKey] = 'Anda tidak memiliki izin untuk mengekspor laporan ini.';
                    this.validationErrors[reportKey] = {};
                } else if (normalized.status === 422) {
                    this.validationErrors[reportKey] = normalized.errors || {};
                    this.errors[reportKey] = null;
                } else if (normalized.status === 500) {
                    this.errors[reportKey] = 'Gagal mengekspor laporan CSV.';
                    this.validationErrors[reportKey] = {};
                } else {
                    this.errors[reportKey] = normalized.message || 'Gagal mengekspor laporan CSV.';
                    this.validationErrors[reportKey] = normalized.errors || {};
                }

                return false;
            } finally {
                this.exporting[reportKey] = false;
            }
        },

        clearFeedback(reportKey) {
            delete this.errors[reportKey];
            delete this.validationErrors[reportKey];
            delete this.successMessages[reportKey];
            delete this.status[reportKey];
        },
    },
});
