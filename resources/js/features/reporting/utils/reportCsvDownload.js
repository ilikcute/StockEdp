import { normalizeApiError } from '@/shared/api/api_client';

export function validateCsvExportResponse(response) {
    const blob = response?.data;
    const contentType = String(
        response?.headers?.['content-type'] ?? response?.headers?.['Content-Type'] ?? blob?.type ?? ''
    ).toLowerCase();

    if (!(blob instanceof Blob)) {
        return {
            valid: false,
            message: 'Response export tidak valid.',
        };
    }

    const isValidContentType = contentType.includes('text/csv')
        || contentType.includes('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        || contentType.includes('spreadsheetml')
        || contentType.includes('application/octet-stream')
        || contentType.includes('excel');

    if (!isValidContentType) {
        return {
            valid: false,
            message: 'Response export tidak valid.',
        };
    }

    if (blob.size === 0) {
        return {
            valid: false,
            message: 'Response export tidak valid.',
        };
    }

    return { valid: true };
}

export function extractCsvFilename(contentDisposition, fallbackFilename = 'report.xlsx') {
    if (!contentDisposition || typeof contentDisposition !== 'string') {
        return sanitizeFilename(fallbackFilename);
    }

    let filename = '';

    // 1. Check filename*=UTF-8''...
    const utf8Match = contentDisposition.match(/filename\*=UTF-8''([^;\r\n]+)/i);
    if (utf8Match && utf8Match[1]) {
        try {
            filename = decodeURIComponent(utf8Match[1]);
        } catch {
            filename = utf8Match[1];
        }
    }

    // 2. Check quoted filename="..."
    if (!filename) {
        const quotedMatch = contentDisposition.match(/filename="([^"\r\n]+)"/i);
        if (quotedMatch && quotedMatch[1]) {
            filename = quotedMatch[1];
        }
    }

    // 3. Check unquoted filename=...
    if (!filename) {
        const unquotedMatch = contentDisposition.match(/filename=([^;\r\n]+)/i);
        if (unquotedMatch && unquotedMatch[1]) {
            filename = unquotedMatch[1].trim();
        }
    }

    if (!filename) {
        filename = fallbackFilename;
    }

    const defaultExt = fallbackFilename.toLowerCase().endsWith('.csv') ? 'csv' : 'xlsx';
    return sanitizeFilename(filename, defaultExt);
}

export function sanitizeFilename(rawFilename, defaultExt = 'xlsx') {
    let cleaned = (rawFilename || '')
        .replace(/[/\\]/g, '')
        // eslint-disable-next-line no-control-regex
        .replace(/[\x00-\x1F\x7F]/g, '')
        .trim();

    if (!cleaned) {
        cleaned = `report.${defaultExt}`;
    }

    const hasExt = cleaned.toLowerCase().endsWith('.csv') || cleaned.toLowerCase().endsWith('.xlsx');
    if (!hasExt) {
        cleaned += `.${defaultExt}`;
    }

    return cleaned;
}

export function downloadCsvBlob(blob, filename) {
    const objectUrl = URL.createObjectURL(blob);
    const anchor = document.createElement('a');

    anchor.href = objectUrl;
    anchor.download = filename;
    anchor.style.display = 'none';

    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();

    window.setTimeout(() => {
        URL.revokeObjectURL(objectUrl);
    }, 0);
}

export async function normalizeCsvExportError(error) {
    if (error?.code === 'ECONNABORTED' || (typeof error?.message === 'string' && error.message.toLowerCase().includes('timeout'))) {
        return {
            status: 408,
            message: 'Proses ekspor melewati batas waktu.',
            errors: {},
        };
    }

    if (!error?.response) {
        return {
            status: 0,
            message: 'Tidak dapat terhubung ke server. Periksa koneksi jaringan.',
            errors: {},
        };
    }

    if (error.response.data && error.response.data instanceof Blob) {
        try {
            const text = await error.response.data.text();
            if (text && text.trim().startsWith('{')) {
                const parsed = JSON.parse(text);
                const status = error.response.status || 500;
                let message = parsed.message;

                if (!message) {
                    if (status === 403) {
                        message = 'Anda tidak memiliki izin untuk mengekspor laporan ini.';
                    } else if (status === 422) {
                        message = 'Data yang diberikan tidak valid.';
                    } else {
                        message = 'Gagal mengekspor laporan CSV.';
                    }
                }

                return {
                    status,
                    message,
                    errors: parsed.errors || {},
                };
            }
        } catch {
            // Fallthrough to normalizer
        }
    }

    return normalizeApiError(error);
}
