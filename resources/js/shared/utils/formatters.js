/**
 * Format a number or numeric string to Indonesian Rupiah currency format.
 * Example: 15000 -> "Rp 15.000", 15000.5 -> "Rp 15.000,50"
 *
 * @param {number|string|null|undefined} amount
 * @param {boolean} withPrefix
 * @returns {string}
 */
export function formatRupiah(amount, withPrefix = true) {
  if (amount === null || amount === undefined || amount === '') {
    return withPrefix ? 'Rp 0' : '0';
  }

  const numericValue = typeof amount === 'number' ? amount : parseFloat(amount);
  if (isNaN(numericValue)) {
    return withPrefix ? 'Rp 0' : '0';
  }

  const formatted = new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(numericValue);

  return withPrefix ? `Rp ${formatted}` : formatted;
}

/**
 * Format quantity / stock numbers without decimal places in data tables.
 * Prevents ambiguity where "10.0000" or "10.00" looks like "10.000" (sepuluh ribu).
 * Example:
 * 10.0000 -> "10"
 * 10.00 -> "10"
 * 1500 -> "1.500"
 *
 * @param {number|string|null|undefined} val
 * @param {boolean} allowDecimals If true, shows up to 2 decimals only when fraction exists. Default false.
 * @returns {string}
 */
export function formatQuantity(val, allowDecimals = false) {
  if (val === null || val === undefined || val === '') return '0';
  const num = typeof val === 'number' ? val : parseFloat(val);
  if (isNaN(num)) return '0';

  if (!allowDecimals || num % 1 === 0) {
    return new Intl.NumberFormat('id-ID', {
      maximumFractionDigits: 0,
      minimumFractionDigits: 0,
    }).format(Math.round(num));
  }

  return new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 2,
    minimumFractionDigits: 0,
  }).format(num);
}

/**
 * Hitung nomor urut baris di tabel ber-pagination.
 * Konsisten dengan `from`/`current_page`/`per_page` dari Laravel paginator.
 *
 * @param {{ from: number, current_page: number, per_page: number }|null} pagination
 * @param {number} index Indeks item di dalam array halaman saat ini (0-based).
 * @returns {number}
 */
export function rowNumber(pagination, index) {
  if (pagination?.from) {
    return pagination.from + index;
  }

  const page = pagination?.current_page ?? 1;
  const perPage = pagination?.per_page ?? 15;

  return (page - 1) * perPage + index + 1;
}

/**
 * Format nilai timestamp ISO menjadi format tanggal/waktu Indonesia.
 * Contoh: 2026-08-19T07:30:00.000000Z -> "19 Agu 2026, 14.30".
 *
 * @param {string|null|undefined} isoString
 * @returns {string}
 */
export function formatTimestamp(isoString) {
  if (!isoString) return 'Belum pernah';
  const d = new Date(isoString);
  if (Number.isNaN(d.getTime())) return '-';

  const datePart = d.toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });
  const timePart = d.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  });

  return `${datePart}, ${timePart}`;
}
