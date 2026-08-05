export function toLocalDateInputValue(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

export function parseDateOnly(dateStr) {
    if (!dateStr || !dateStr.includes('-')) return null;
    const [y, m, d] = dateStr.split('-').map(Number);
    return new Date(y, m - 1, d);
}

export function validatePeriod(startDate, endDate) {
    if (!startDate && !endDate) {
        return { valid: true };
    }

    if ((startDate && !endDate) || (!startDate && endDate)) {
        return { valid: false, message: 'Tanggal mulai dan tanggal akhir harus diisi bersamaan.' };
    }

    const start = parseDateOnly(startDate);
    const end = parseDateOnly(endDate);

    if (!start || !end) {
        return { valid: false, message: 'Format tanggal tidak valid.' };
    }

    if (start > end) {
        return { valid: false, message: 'Tanggal mulai tidak boleh melewati tanggal akhir.' };
    }

    const diffTime = end.getTime() - start.getTime();
    const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays > 366) {
        return { valid: false, message: 'Rentang waktu maksimal adalah 366 hari.' };
    }

    return { valid: true };
}

export function cleanReportFilters(filters) {
    const result = {};

    for (const [key, value] of Object.entries(filters)) {
        if (value === '' || value === null || value === undefined) continue;
        if (value === false) continue;
        result[key] = value;
    }

    return result;
}

export function formatTransitDuration(seconds) {
    if (seconds === null || seconds === undefined) {
        return '-';
    }

    let remaining = seconds;
    if (remaining < 0) {
        remaining = 0;
    }

    const days = Math.floor(remaining / 86400);
    remaining %= 86400;
    const hours = Math.floor(remaining / 3600);
    remaining %= 3600;
    const minutes = Math.floor(remaining / 60);

    const parts = [];
    if (days > 0) parts.push(`${days} hr`);
    if (hours > 0) parts.push(`${hours} jam`);
    if (minutes > 0 || parts.length === 0) parts.push(`${minutes} mnt`);

    return parts.join(' ');
}

export function getReasonCodeLabel(code) {
    const labels = {
        FOUND: 'Barang ditemukan',
        DAMAGED: 'Barang rusak',
        EXPIRED: 'Barang kedaluwarsa',
        RECORDING_ERROR: 'Kesalahan pencatatan',
        ADMINISTRATIVE: 'Koreksi administratif',
        LOST: 'Kehilangan barang',
        OTHER: 'Lain-lain',
    };

    return labels[code] || code;
}


export function getDirectionLabel(direction) {
    if (direction === 'INCREASE') return 'Penambahan';
    if (direction === 'DECREASE') return 'Pengurangan';
    return direction;
}

export function getMovementDirectionLabel(direction) {
    if (direction === 'OPNAME_IN') return 'Selisih Masuk';
    if (direction === 'OPNAME_OUT') return 'Selisih Keluar';
    if (direction === 'NONE') return 'Tidak Ada Selisih';
    return direction;
}

export function getDateBasisDescription(dateBasis) {
    switch (dateBasis) {
    case 'MOVEMENT_POSTED_AT':
        return 'Periode laporan menggunakan waktu posting movement.';
    case 'POSTED_AT':
        return 'Periode laporan menggunakan waktu posting dokumen.';
    case 'SENT_AT':
        return 'Periode laporan menggunakan waktu pengiriman transfer.';
    case 'RECEIVED_AT':
        return 'Periode laporan menggunakan waktu penerimaan transfer.';
    default:
        return null;
    }
}

export function getTransferStatusLabel(status) {
    if (status === 'SENT') return 'Dikirim';
    if (status === 'RECEIVED') return 'Diterima';
    return status;
}

export function getVarianceDirectionLabel(direction) {
    if (direction === 'POSITIVE') return 'Positif';
    if (direction === 'NEGATIVE') return 'Negatif';
    if (direction === 'ZERO') return 'Nol';
    return direction;
}
