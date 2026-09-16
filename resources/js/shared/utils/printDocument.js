import { formatQuantity, formatRupiah } from './formatters.js';

/**
 * Escapes HTML characters to prevent XSS in print templates.
 *
 * @param {any} val
 * @returns {string}
 */
function escapeHtml(val) {
    if (val === null || val === undefined) return '';
    return String(val)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Formats current date and time to Indonesian standard format: DD/MM/YYYY HH:mm:ss.
 *
 * @param {Date} [date=new Date()]
 * @returns {string}
 */
function formatCurrentDateTime(date = new Date()) {
    const pad = (n) => String(n).padStart(2, '0');
    const d = pad(date.getDate());
    const m = pad(date.getMonth() + 1);
    const y = date.getFullYear();
    const h = pad(date.getHours());
    const min = pad(date.getMinutes());
    const s = pad(date.getSeconds());
    return `${d}/${m}/${y} ${h}:${min}:${s}`;
}

/**
 * Returns color classes/hex for document status badge.
 *
 * @param {string} status
 * @returns {{ bg: string, color: string, border: string }}
 */
function getStatusBadgeStyle(status) {
    const s = String(status || '').toUpperCase();
    if (['POSTED', 'SELESAI', 'COMPLETED', 'RECEIVED'].includes(s)) {
        return { bg: '#ecfdf5', color: '#065f46', border: '#a7f3d0' };
    }
    if (['DRAFT'].includes(s)) {
        return { bg: '#fefce8', color: '#854d0e', border: '#fef08a' };
    }
    if (['IN_TRANSIT', 'IN_PROGRESS', 'COUNTED'].includes(s)) {
        return { bg: '#eff6ff', color: '#1e40af', border: '#bfdbfe' };
    }
    if (['CANCELED', 'CANCELLED', 'DISCREPANCY'].includes(s)) {
        return { bg: '#fef2f2', color: '#991b1b', border: '#fecaca' };
    }
    return { bg: '#f3f4f6', color: '#374151', border: '#e5e7eb' };
}

/**
 * Generates the complete HTML string for the printable document.
 *
 * @param {Object} options
 * @returns {string}
 */
export function generatePrintDocumentHtml(options = {}) {
    const {
        title = 'DOKUMEN INVENTARIS',
        subtitle = 'Sistem Informasi Pengelolaan Persediaan & Aset EDP',
        docNumber = '-',
        docDate = '-',
        status = '',
        statusLabel = '',
        isReprint = true,
        companyName = 'StockEdp',
        companyTagline = 'INVENTORY & FIELD ASSETS MANAGEMENT SYSTEM',
        companyAddress = 'Departemen EDP & IT — Logistik & Pengelolaan Perangkat Toko',
        meta = [], // [{ label, value, colSpan }]
        customHeaderHtml = '',
        tableHeaders = [], // [{ label, align, width }]
        tableRows = [], // array of row items or raw array of cell values
        totals = [], // [{ label, value, align }]
        customBodyHtml = '',
        signatures = [], // [{ role, name, title }]
        notes = '',
        printedBy = 'Sistem StockEdp',
        printedAt = formatCurrentDateTime(),
    } = options;

    const displayStatus = statusLabel || status;
    const badgeStyle = getStatusBadgeStyle(status);

    // Build Metadata HTML
    let metaHtml = '';
    if (meta.length > 0) {
        metaHtml = `
      <div class="meta-container">
        <table class="meta-table">
          <tbody>
            ${(() => {
                let rows = '';
                const itemsPerRow = 3;
                for (let i = 0; i < meta.length; i += itemsPerRow) {
                    const rowItems = meta.slice(i, i + itemsPerRow);
                    rows += '<tr>';
                    for (let j = 0; j < itemsPerRow; j++) {
                        const item = rowItems[j];
                        if (item) {
                            rows += `
                <td class="meta-cell">
                  <div class="meta-label">${escapeHtml(item.label)}</div>
                  <div class="meta-value">${escapeHtml(item.value || '-')}</div>
                </td>
              `;
                        } else {
                            rows += '<td class="meta-cell meta-cell-empty"></td>';
                        }
                    }
                    rows += '</tr>';
                }
                return rows;
            })()}
          </tbody>
        </table>
      </div>
    `;
    }

    // Build Table HTML
    let tableHtml = '';
    if (tableHeaders.length > 0 && tableRows.length > 0) {
        const theadHtml = tableHeaders
            .map((th) => {
                const align = th.align || 'left';
                const width = th.width ? `style="width:${th.width};text-align:${align};"` : `style="text-align:${align};"`;
                return `<th ${width}>${escapeHtml(th.label)}</th>`;
            })
            .join('');

        const tbodyHtml = tableRows
            .map((row, rIdx) => {
                const trClass = rIdx % 2 === 1 ? 'class="tr-alt"' : '';
                const cellsHtml = row
                    .map((cell, cIdx) => {
                        const th = tableHeaders[cIdx] || {};
                        const align = th.align || 'left';
                        const isMono = th.mono || false;
                        const style = `text-align:${align};${isMono ? 'font-family:ui-monospace,SFMono-Regular,Consolas,monospace;' : ''}`;
                        return `<td style="${style}">${cell}</td>`;
                    })
                    .join('');
                return `<tr ${trClass}>${cellsHtml}</tr>`;
            })
            .join('');

        let tfootHtml = '';
        if (totals.length > 0) {
            tfootHtml = `
        <tfoot>
          ${totals
              .map((tot) => {
                  const labelSpan = tot.labelSpan || (tableHeaders.length > 2 ? tableHeaders.length - 2 : 1);
                  const valSpan = tot.valSpan || (tableHeaders.length - labelSpan);
                  return `
              <tr class="tfoot-row">
                <td colspan="${labelSpan}" class="tfoot-label">${escapeHtml(tot.label)}</td>
                <td colspan="${valSpan}" class="tfoot-value" style="text-align:${tot.align || 'right'};">${tot.value}</td>
              </tr>
            `;
              })
              .join('')}
        </tfoot>
      `;
        }

        tableHtml = `
      <table class="data-table">
        <thead>
          <tr>${theadHtml}</tr>
        </thead>
        <tbody>
          ${tbodyHtml}
        </tbody>
        ${tfootHtml}
      </table>
    `;
    }

    // Build Signatures HTML
    const defaultSignatures = [
        { role: 'Yang Menyerahkan', name: '............................................', title: 'Petugas / Vendor' },
        { role: 'Yang Menerima', name: '............................................', title: 'Teknisi / PIC Toko' },
        { role: 'Mengetahui / Disetujui', name: '............................................', title: 'Supervisor IT / Logistik' },
    ];
    const activeSignatures = signatures.length > 0 ? signatures : defaultSignatures;

    const signaturesHtml = `
    <div class="signatures-wrap">
      <table class="signatures-table">
        <tr>
          ${activeSignatures
              .map(
                  (sig) => `
            <td class="sig-column">
              <div class="sig-role">${escapeHtml(sig.role)}</div>
              <div class="sig-date">Tgl: &nbsp; &nbsp; &nbsp; &nbsp; / &nbsp; &nbsp; &nbsp; &nbsp; / 20 &nbsp; &nbsp;</div>
              <div class="sig-space"></div>
              <div class="sig-name">( ${escapeHtml(sig.name)} )</div>
              <div class="sig-title">${escapeHtml(sig.title || '')}</div>
            </td>
          `,
              )
              .join('')}
        </tr>
      </table>
    </div>
  `;

    // Build Notes HTML
    const notesHtml = notes
        ? `
    <div class="notes-box">
      <span class="notes-title">Catatan:</span>
      <span class="notes-body">${escapeHtml(notes)}</span>
    </div>
  `
        : '';

    return `<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>${escapeHtml(title)} - ${escapeHtml(docNumber)}</title>
  <style>
    @page {
      size: A4 portrait;
      margin: 10mm 12mm 12mm 12mm;
    }

    * {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      color: #0f172a;
      background: #ffffff;
      font-size: 10.5px;
      line-height: 1.35;
    }

    .doc-container {
      width: 100%;
      max-width: 210mm;
      margin: 0 auto;
    }

    /* KOP SURAT */
    .kop-wrapper {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      padding-bottom: 8px;
    }

    .kop-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .kop-logo-box {
      width: 38px;
      height: 38px;
      background: #4f46e5;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #ffffff;
      font-weight: 900;
      font-size: 20px;
      font-family: monospace;
      letter-spacing: -1px;
    }

    .kop-company {
      font-size: 15px;
      font-weight: 800;
      color: #1e1b4b;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .kop-tagline {
      font-size: 9px;
      font-weight: 700;
      color: #4f46e5;
      letter-spacing: 0.7px;
      text-transform: uppercase;
    }

    .kop-address {
      font-size: 9.5px;
      color: #475569;
      margin-top: 1px;
    }

    .kop-right {
      text-align: right;
    }

    .doc-badge-status {
      display: inline-block;
      font-size: 9px;
      font-weight: 800;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      padding: 2px 8px;
      border-radius: 4px;
      background: ${badgeStyle.bg};
      color: ${badgeStyle.color};
      border: 1px solid ${badgeStyle.border};
      margin-bottom: 2px;
    }

    .doc-reprint-tag {
      display: inline-block;
      font-size: 8px;
      font-weight: 700;
      color: #64748b;
      background: #f1f5f9;
      border: 1px solid #cbd5e1;
      padding: 1px 5px;
      border-radius: 3px;
      margin-left: 4px;
    }

    .kop-divider {
      border-top: 2px solid #1e1b4b;
      border-bottom: 1px solid #cbd5e1;
      height: 3px;
      margin-bottom: 10px;
    }

    /* TITLE BANNER */
    .title-banner {
      text-align: center;
      margin-bottom: 10px;
      padding: 4px 0;
    }

    .title-banner h1 {
      margin: 0;
      font-size: 13.5px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.7px;
      color: #0f172a;
    }

    .title-banner p {
      margin: 2px 0 0 0;
      font-size: 9.5px;
      color: #64748b;
    }

    /* METADATA GRID */
    .meta-container {
      margin-bottom: 10px;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      overflow: hidden;
      background: #ffffff;
    }

    .meta-table {
      width: 100%;
      border-collapse: collapse;
    }

    .meta-cell {
      padding: 5px 8px;
      border-bottom: 1px solid #e2e8f0;
      border-right: 1px solid #e2e8f0;
      vertical-align: top;
      width: 33.333%;
    }

    .meta-cell:last-child {
      border-right: none;
    }

    .meta-table tr:last-child .meta-cell {
      border-bottom: none;
    }

    .meta-cell-empty {
      background: #fafafa;
    }

    .meta-label {
      font-size: 8.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      color: #64748b;
      margin-bottom: 1px;
    }

    .meta-value {
      font-size: 10.5px;
      font-weight: 600;
      color: #1e293b;
      word-break: break-word;
    }

    /* DATA TABLE */
    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
      border: 1px solid #cbd5e1;
      font-size: 10px;
    }

    .data-table th {
      background: #f1f5f9;
      color: #334155;
      font-weight: 700;
      padding: 5px 6px;
      border: 1px solid #cbd5e1;
      text-transform: uppercase;
      font-size: 9px;
      letter-spacing: 0.3px;
    }

    .data-table td {
      padding: 4.5px 6px;
      border: 1px solid #e2e8f0;
      color: #1e293b;
      vertical-align: middle;
    }

    .data-table tr.tr-alt td {
      background: #f8fafc;
    }

    .data-table tfoot .tfoot-row td {
      background: #f1f5f9;
      border-top: 2px solid #cbd5e1;
      padding: 5px 6px;
      font-weight: 700;
    }

    .tfoot-label {
      text-align: right;
      color: #334155;
      font-size: 9.5px;
      text-transform: uppercase;
    }

    .tfoot-value {
      font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
      font-size: 10.5px;
      color: #0f172a;
    }

    /* NOTES BOX */
    .notes-box {
      border: 1px dashed #cbd5e1;
      background: #f8fafc;
      border-radius: 5px;
      padding: 5px 8px;
      margin-bottom: 10px;
      font-size: 9.5px;
    }

    .notes-title {
      font-weight: 700;
      color: #475569;
      margin-right: 4px;
    }

    .notes-body {
      color: #334155;
    }

    /* SIGNATURES BLOCK */
    .signatures-wrap {
      margin-top: 14px;
      page-break-inside: avoid;
    }

    .signatures-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .sig-column {
      text-align: center;
      vertical-align: top;
      padding: 0 10px;
    }

    .sig-role {
      font-size: 9.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      color: #1e293b;
    }

    .sig-date {
      font-size: 8.5px;
      color: #64748b;
      margin-top: 2px;
    }

    .sig-space {
      height: 52px;
    }

    .sig-name {
      font-size: 9.5px;
      font-weight: 600;
      color: #0f172a;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .sig-title {
      font-size: 8.5px;
      color: #64748b;
      margin-top: 1px;
    }

    /* FOOTER */
    .doc-footer {
      margin-top: 14px;
      padding-top: 6px;
      border-top: 1px solid #e2e8f0;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 8.5px;
      color: #94a3b8;
    }

    .footer-left {
      font-style: italic;
    }

    .footer-right {
      font-weight: 600;
      letter-spacing: 0.3px;
    }
  </style>
</head>
<body>
  <div class="doc-container">
    <!-- KOP SURAT RESMI -->
    <div class="kop-wrapper">
      <div class="kop-left">
        <div class="kop-logo-box">SE</div>
        <div>
          <div class="kop-company">${escapeHtml(companyName)}</div>
          <div class="kop-tagline">${escapeHtml(companyTagline)}</div>
          <div class="kop-address">${escapeHtml(companyAddress)}</div>
        </div>
      </div>
      <div class="kop-right">
        <div>
          <span class="doc-badge-status">${escapeHtml(displayStatus || 'DOKUMEN RESMI')}</span>
          ${isReprint ? '<span class="doc-reprint-tag">SALINAN REPRINT</span>' : ''}
        </div>
        <div style="font-size: 11px; font-weight: 800; font-family: monospace; color: #1e293b; margin-top: 3px;">
          ${escapeHtml(docNumber)}
        </div>
        <div style="font-size: 9px; color: #64748b;">
          Tgl: ${escapeHtml(docDate)}
        </div>
      </div>
    </div>
    <div class="kop-divider"></div>

    <!-- JUDUL DOKUMEN -->
    <div class="title-banner">
      <h1>${escapeHtml(title)}</h1>
      ${subtitle ? `<p>${escapeHtml(subtitle)}</p>` : ''}
    </div>

    ${customHeaderHtml}

    <!-- METADATA -->
    ${metaHtml}

    <!-- TABEL UTAMA -->
    ${tableHtml}

    ${customBodyHtml}

    <!-- CATATAN -->
    ${notesHtml}

    <!-- KOLOM TANDA TANGAN -->
    ${signaturesHtml}

    <!-- FOOTER INFORMASI SISTEM -->
    <div class="doc-footer">
      <div class="footer-left">
        Dicetak dari Sistem StockEdp pada: ${escapeHtml(printedAt)} &bull; User: ${escapeHtml(printedBy)}
      </div>
      <div class="footer-right">
        StockEdp Integrated System &bull; Dokumen Sah Komputerisasi
      </div>
    </div>
  </div>
</body>
</html>`;
}

/**
 * Executes browser printing through an isolated hidden iframe.
 *
 * @param {Object} options Options passed to generatePrintDocumentHtml.
 * @returns {Promise<boolean>}
 */
export function printDocument(options = {}) {
    return new Promise((resolve) => {
        const html = typeof options === 'string'
            ? options
            : (options.rawHtml || generatePrintDocumentHtml(options));

        // Remove any pre-existing print iframe
        const existingFrame = document.getElementById('stockedp-print-frame');
        if (existingFrame) {
            existingFrame.remove();
        }

        const iframe = document.createElement('iframe');
        iframe.id = 'stockedp-print-frame';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        iframe.style.visibility = 'hidden';

        document.body.appendChild(iframe);

        const frameDoc = iframe.contentDocument || iframe.contentWindow.document;
        frameDoc.open();
        frameDoc.write(html);
        frameDoc.close();

        // Allow layout to render before triggering print
        setTimeout(() => {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
                resolve(true);
            } catch (err) {
                console.error('[printDocument] Gagal melakukan print:', err);
                resolve(false);
            } finally {
                // Clean up iframe after 60s
                setTimeout(() => {
                    if (iframe && iframe.parentNode) {
                        iframe.remove();
                    }
                }, 60000);
            }
        }, 300);
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// ADAPTERS KHUSUS DOKUMEN INVENTARIS STOCK EDP
// ─────────────────────────────────────────────────────────────────────────────

// Code 128 Set B pattern table (widths of 6 elements: 3 bars, 3 spaces; stop has 7 elements)
const CODE128_PATTERNS = [
    '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
    '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
    '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
    '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
    '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
    '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
    '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
    '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
    '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
    '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
    '114131', '311141', '411131', '211412', '211214', '211232', '2331112'
];

/**
 * Menghasilkan string SVG barcode Code 128 vektor tajam tanpa ketergantungan library eksternal.
 *
 * @param {string} text
 * @param {Object} [options={}]
 * @returns {string}
 */
export function generateBarcodeSvg(text, options = {}) {
    const raw = String(text || '').trim();
    if (!raw) return '';

    const height = options.height || 42;
    const moduleWidth = options.moduleWidth || 1.35;
    const quietZone = options.quietZone !== undefined ? options.quietZone : 12;

    const codes = [104]; // START B
    let checksum = 104;

    for (let i = 0; i < raw.length; i++) {
        const charCode = raw.charCodeAt(i);
        const code = (charCode >= 32 && charCode <= 126) ? (charCode - 32) : 0;
        codes.push(code);
        checksum += (i + 1) * code;
    }

    codes.push(checksum % 103);
    codes.push(106); // STOP

    let currentX = quietZone;
    const rects = [];

    for (let s = 0; s < codes.length; s++) {
        const pattern = CODE128_PATTERNS[codes[s]];
        if (!pattern) continue;

        for (let p = 0; p < pattern.length; p++) {
            const barUnits = parseInt(pattern[p], 10);
            const w = barUnits * moduleWidth;
            const isBar = p % 2 === 0;
            if (isBar) {
                rects.push(`<rect x="${currentX.toFixed(2)}" y="0" width="${w.toFixed(2)}" height="${height}" fill="#000000" />`);
            }
            currentX += w;
        }
    }

    currentX += quietZone;
    const totalWidth = Math.ceil(currentX);

    return `<svg width="${totalWidth}" height="${height}" viewBox="0 0 ${totalWidth} ${height}" xmlns="http://www.w3.org/2000/svg" style="display:block;margin:0 auto;">${rects.join('')}</svg>`;
}

/**
 * Format tanggal Indonesia resmi: DD MMMM YYYY atau DD MMM YYYY.
 *
 * @param {string|Date} val
 * @param {boolean} [shortMonth=false]
 * @returns {string}
 */
export function formatIndoDate(val, shortMonth = false) {
    if (!val) return '-';
    let d, m, y;

    if (typeof val === 'string' && val.includes('-')) {
        const parts = val.split('T')[0].split('-');
        if (parts.length === 3) {
            y = parseInt(parts[0], 10);
            m = parseInt(parts[1], 10) - 1;
            d = parseInt(parts[2], 10);
        }
    }

    if (d === undefined || isNaN(d)) {
        const dateObj = new Date(val);
        if (isNaN(dateObj.getTime())) return String(val);
        d = dateObj.getDate();
        m = dateObj.getMonth();
        y = dateObj.getFullYear();
    }

    const monthsShort = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Des'];
    const monthsLong = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    const monthName = shortMonth ? (monthsShort[m] || '') : (monthsLong[m] || '');
    return `${d} ${monthName} ${y}`;
}

/**
 * Format jam resmi: HH:mm:ss.
 *
 * @param {Date} [date=new Date()]
 * @returns {string}
 */
export function formatIndoTime(date = new Date()) {
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
}

/**
 * Menghasilkan dokumen HTML lengkap untuk Surat Jalan Alokasi Unit Toko
 * persis sesuai format standar PT. INDOMARCO PRISMATAMA - IDM YOGYAKARTA.
 *
 * @param {Object} doc StoreAllocation document
 * @param {Object} [extraOptions={}]
 * @returns {string}
 */
export function generateStoreAllocationSuratJalanHtml(doc, extraOptions = {}) {
    if (!doc) return '';

    const companyName = extraOptions.companyName || 'PT. INDOMARCO PRISMATAMA';
    const branchName = extraOptions.branchName || 'IDM YOGYAKARTA';
    const branchAddress = extraOptions.branchAddress || 'JL.ARTERI (LINGKAR LUAR BARAT)<br>DESA TRIHANGGO KEC GAMPING<br>KAB SLEMAN YOGYAKARTA';

    const printedBy = extraOptions.printedBy || doc.creator_name || 'EDP_YOG';
    const createdBy = doc.creator_name || doc.technician_name || printedBy;
    const senderUser = extraOptions.senderUser || doc.creator_name || doc.technician_name || createdBy || '';
    const currentDateIndo = formatIndoDate(new Date(), false);
    const currentTimeIndo = formatIndoTime(new Date());

    const senderCompanySubtitle = senderUser
        ? ((branchName === 'IDM YOGYAKARTA' && companyName === 'PT. INDOMARCO PRISMATAMA')
            ? `PT. INDOMARCO PRISMATAMA - IDM<br>YOGYAKARTA - ${escapeHtml(senderUser)}`
            : `${escapeHtml(companyName)} - ${escapeHtml(branchName)} - ${escapeHtml(senderUser)}`)
        : ((branchName === 'IDM YOGYAKARTA' && companyName === 'PT. INDOMARCO PRISMATAMA')
            ? 'PT. INDOMARCO PRISMATAMA - IDM<br>YOGYAKARTA'
            : `${escapeHtml(companyName)} - ${escapeHtml(branchName)}`);

    const docNumber = doc.allocation_number || '-';
    const docDateIndo = formatIndoDate(doc.allocated_at || doc.created_at, true);

    const storeCode = doc.store_code || '';
    const storeName = doc.store_name || '';
    const storeAddress = doc.store_address || '';
    const storeDisplay = storeAddress
        ? `${storeCode ? storeCode + ' - ' : ''}${storeAddress}`
        : `${storeCode ? storeCode + ' - ' : ''}${storeName}`;

    const barcodeSvg = generateBarcodeSvg(docNumber, { height: 42, moduleWidth: 1.35 });

    const items = doc.items || [];
    const rows = [];
    let rowNumber = 1;

    items.forEach((item) => {
        // Unit terpasang / dikirim (BA Perbaikan / Unit Pasang)
        const instQty = Number(item.quantity ?? item.installed_quantity ?? 1);
        const instPrice = Number(item.unit_price ?? item.product?.unit_price ?? 0);
        const instSubtotal = Number(item.total_value ?? (instQty * instPrice));
        const instPriceStr = instPrice > 0 ? formatRupiah(instPrice, false) : '-';
        const instSubtotalStr = instSubtotal > 0 ? formatRupiah(instSubtotal, false) : (instPrice > 0 ? '0' : '-');
        const instSerial = item.serial_number || item.installed_serial_number || '-';
        const instSku = item.product_sku || item.product?.sku || '-';
        const instName = item.product_name || item.product?.name || '-';
        const instType = item.item_type || 'BA Perbaikan';
        const instNotes = item.notes || '';
        const refBkb = doc.allocation_number || '';
        const refPb = doc.pb_number || doc.spb_number || doc.memo_number || '';

        rows.push(`
          <tr>
            <td style="text-align: center;">${rowNumber++}</td>
            <td style="text-align: left;">${escapeHtml(instSku)}</td>
            <td style="text-align: left;">${escapeHtml(instName)}</td>
            <td style="text-align: left;">${escapeHtml(instType)}</td>
            <td style="text-align: right;">${formatQuantity(instQty)}</td>
            <td style="text-align: right;">${instPriceStr}</td>
            <td style="text-align: right;">${instSubtotalStr}</td>
            <td style="text-align: left;">${escapeHtml(instSerial)}</td>
            <td style="text-align: left;">${escapeHtml(instNotes)}</td>
            <td style="text-align: left;">${escapeHtml(refBkb)}</td>
            <td style="text-align: left;">${escapeHtml(refPb)}</td>
          </tr>
        `);

        // Unit lama ditarik dari toko (jika ada)
        const pullQty = Number(item.pulled_quantity || 0);
        const hasPulled = pullQty > 0 || Boolean(item.pulled_product_name || item.pulledProduct?.name);
        if (hasPulled) {
            const pullSku = item.pulled_product_sku || item.pulledProduct?.sku || instSku;
            const pullName = item.pulled_product_name || item.pulledProduct?.name || instName;
            const pullSerial = item.pulled_serial_number || '-';
            const pullNotes = item.defective_reason || item.pulled_reason || 'Tarik unit rusak';

            rows.push(`
              <tr>
                <td style="text-align: center;">${rowNumber++}</td>
                <td style="text-align: left;">${escapeHtml(pullSku)}</td>
                <td style="text-align: left;">${escapeHtml(pullName)}</td>
                <td style="text-align: left;">Tarik Unit Rusak</td>
                <td style="text-align: right;">${formatQuantity(pullQty)}</td>
                <td style="text-align: right;">-</td>
                <td style="text-align: right;">-</td>
                <td style="text-align: left;">${escapeHtml(pullSerial)}</td>
                <td style="text-align: left;">${escapeHtml(pullNotes)}</td>
                <td style="text-align: left;">${escapeHtml(refBkb)}</td>
                <td style="text-align: left;">${escapeHtml(refPb)}</td>
              </tr>
            `);
        }
    });

    if (rows.length === 0) {
        rows.push(`
          <tr>
            <td style="text-align: center;">1</td>
            <td style="text-align: left;">-</td>
            <td style="text-align: left;">-</td>
            <td style="text-align: left;">BA Perbaikan</td>
            <td style="text-align: right;">1</td>
            <td style="text-align: right;">-</td>
            <td style="text-align: right;">-</td>
            <td style="text-align: left;">-</td>
            <td style="text-align: left;">-</td>
            <td style="text-align: left;">${escapeHtml(docNumber)}</td>
            <td style="text-align: left;">-</td>
          </tr>
        `);
    }

    return `<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>SURAT JALAN - ${escapeHtml(docNumber)}</title>
  <style>
    @page {
      size: A4 portrait;
      margin: 15mm 15mm 15mm 15mm;
    }
    *, *::before, *::after {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, Helvetica, sans-serif;
      color: #000000;
      background: #ffffff;
      font-size: 11px;
      line-height: 1.35;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .sj-container {
      width: 100%;
      max-width: 820px;
      margin: 0 auto;
      padding: 10px;
    }
    @media print {
      @page {
        size: A4 portrait;
        margin: 12mm 12mm 12mm 12mm;
      }
      body {
        margin: 0;
        padding: 0;
      }
      .sj-container {
        max-width: 100%;
        padding: 0;
      }
    }
    .sj-top-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 22px;
    }
    .sj-company-info {
      font-style: italic;
      font-weight: bold;
      font-size: 11px;
      line-height: 1.35;
    }
    .sj-print-meta {
      font-size: 11px;
    }
    .sj-meta-table {
      border-collapse: collapse;
    }
    .sj-meta-table td {
      padding: 1px 0;
      vertical-align: top;
      font-size: 11px;
    }
    .sj-meta-label {
      width: 95px;
    }
    .sj-meta-colon {
      width: 14px;
      text-align: center;
    }
    .sj-title-section {
      text-align: center;
      margin-bottom: 25px;
    }
    .sj-title {
      font-size: 18px;
      font-weight: bold;
      letter-spacing: 0.5px;
      margin-bottom: 6px;
    }
    .sj-barcode-wrap {
      display: flex;
      justify-content: center;
      margin-bottom: 8px;
    }
    .sj-doc-meta-block {
      display: inline-block;
      text-align: left;
      font-size: 11px;
    }
    .sj-recipient-section {
      margin-bottom: 18px;
      font-size: 11px;
      line-height: 1.4;
    }
    .sj-recipient-title {
      margin-bottom: 2px;
    }
    .sj-recipient-company {
      margin-bottom: 10px;
    }
    .sj-recipient-store {
      margin-bottom: 10px;
      text-transform: uppercase;
    }
    .sj-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
      font-size: 10.5px;
    }
    .sj-table th, .sj-table td {
      border: 1px solid #000000;
      padding: 4px 5px;
      vertical-align: middle;
    }
    .sj-table th {
      font-weight: bold;
      background: #ffffff;
    }
    .sj-signatures {
      display: flex;
      justify-content: space-between;
      margin-top: 15px;
      page-break-inside: avoid;
    }
    .sj-sig-box {
      width: 48%;
    }
    .sj-sig-title {
      font-size: 11px;
      margin-bottom: 2px;
    }
    .sj-sig-subtitle {
      font-size: 11px;
      font-weight: normal;
      margin-bottom: 2px;
    }
    .sj-sig-space {
      height: 60px;
    }
    .sj-sig-note {
      font-style: italic;
      font-size: 10.5px;
      margin-bottom: 2px;
    }
    .sj-sig-line {
      font-size: 11px;
    }
  </style>
</head>
<body>
  <div class="sj-container">
    <!-- Header Atas -->
    <div class="sj-top-header">
      <div class="sj-company-info">
        ${escapeHtml(companyName)}<br>
        ${escapeHtml(branchName)}<br>
        ${branchAddress}
      </div>
      <div class="sj-print-meta">
        <table class="sj-meta-table">
          <tr>
            <td class="sj-meta-label">Dicetak Oleh</td>
            <td class="sj-meta-colon">:</td>
            <td>${escapeHtml(printedBy)}</td>
          </tr>
          <tr>
            <td class="sj-meta-label">Dibuat Oleh</td>
            <td class="sj-meta-colon">:</td>
            <td>${escapeHtml(createdBy)}</td>
          </tr>
          <tr>
            <td class="sj-meta-label">Tanggal Cetak</td>
            <td class="sj-meta-colon">:</td>
            <td>${escapeHtml(currentDateIndo)}</td>
          </tr>
          <tr>
            <td class="sj-meta-label">Jam Cetak</td>
            <td class="sj-meta-colon">:</td>
            <td>${escapeHtml(currentTimeIndo)}</td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Judul & Barcode Tengah -->
    <div class="sj-title-section">
      <div class="sj-title">SURAT JALAN</div>
      <div class="sj-barcode-wrap">
        ${barcodeSvg}
      </div>
      <div class="sj-doc-meta-block">
        <table class="sj-meta-table">
          <tr>
            <td style="width:55px;">Nomor</td>
            <td class="sj-meta-colon">:</td>
            <td>${escapeHtml(docNumber)}</td>
          </tr>
          <tr>
            <td>Tanggal</td>
            <td class="sj-meta-colon">:</td>
            <td>${escapeHtml(docDateIndo)}</td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Tujuan / Kepada Yth. -->
    <div class="sj-recipient-section">
      <div class="sj-recipient-title">Kepada Yth.</div>
      <div class="sj-recipient-company">${escapeHtml(companyName)}</div>

      <div>Ditujukan ke : &nbsp;${escapeHtml(storeDisplay ? `${storeDisplay} ${branchName}` : (branchName || '-'))}</div>
    </div>

    <!-- Tabel Rincian Barang -->
    <table class="sj-table">
      <thead>
        <tr>
          <th style="width: 30px; text-align: center;">No</th>
          <th style="width: 55px; text-align: left;">PLU</th>
          <th style="text-align: left;">Nama dan Spesifikasi</th>
          <th style="width: 85px; text-align: left;">Tipe Barang</th>
          <th style="width: 48px; text-align: right;">Kuantitas</th>
          <th style="width: 78px; text-align: right;">Harga Satuan</th>
          <th style="width: 82px; text-align: right;">Total Nilai</th>
          <th style="width: 110px; text-align: left;">Nomor Serial</th>
          <th style="width: 75px; text-align: left;">Keterangan</th>
          <th style="width: 85px; text-align: left;"><i>Ref. Kode BKB</i></th>
          <th style="width: 85px; text-align: left;"><i>Ref. Kode PB</i></th>
        </tr>
      </thead>
      <tbody>
        ${rows.join('')}
      </tbody>
    </table>

    <!-- Tanda Tangan -->
    <div class="sj-signatures">
      <div class="sj-sig-box">
        <div class="sj-sig-title">Diterima Oleh :</div>
        <div class="sj-sig-space"></div>
        <div class="sj-sig-note">(tandatangan dan cap Perusahaan)</div>
        <div class="sj-sig-line">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
      </div>
      <div class="sj-sig-box" style="margin-left: 50px;">
        <div class="sj-sig-title">Dikirim Oleh :</div>
        <div class="sj-sig-subtitle">${senderCompanySubtitle}</div>
        <div class="sj-sig-space" style="height: 44px;"></div>
        <div class="sj-sig-note">(tandatangan dan cap Perusahaan)</div>
        <div class="sj-sig-line">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
      </div>
    </div>
  </div>
</body>
</html>`;
}

/**
 * Cetak Dokumen Surat Jalan Alokasi Unit Toko (Format Resmi PT. Indomarco Prismatama).
 *
 * @param {Object} doc StoreAllocation document
 * @param {Object} [extraOptions={}]
 */
export function printStoreAllocation(doc, extraOptions = {}) {
    if (!doc) return;
    const html = generateStoreAllocationSuratJalanHtml(doc, extraOptions);
    return printDocument({ rawHtml: html, ...extraOptions });
}

/**
 * Cetak Dokumen Penerimaan Barang (Goods Receipt).
 *
 * @param {Object} doc StockReceipt document
 * @param {Object} [extraOptions={}]
 */
export function printStockReceipt(doc, extraOptions = {}) {
    if (!doc) return;

    const items = doc.items || [];
    let totalQty = 0;
    let totalAmount = 0;

    const tableHeaders = [
        { label: 'No.', width: '30px', align: 'center' },
        { label: 'Nama Produk / Barang', align: 'left' },
        { label: 'SKU / Kode', width: '100px', align: 'left', mono: true },
        { label: 'Lokasi Gudang', width: '110px', align: 'left' },
        { label: 'Harga Satuan (Rp)', width: '100px', align: 'right', mono: true },
        { label: 'Qty Masuk', width: '70px', align: 'right', mono: true },
        { label: 'Subtotal (Rp)', width: '105px', align: 'right', mono: true },
    ];

    const tableRows = items.map((item, idx) => {
        const qty = parseFloat(item.quantity) || 0;
        const price = Number(item.unit_price ?? item.product?.unit_price ?? 0);
        const sub = item.subtotal ?? (qty * price);

        totalQty += qty;
        totalAmount += Number(sub);

        const unit = item.product?.unit?.symbol || item.product?.unit?.name || '';

        return [
            String(idx + 1),
            `<div style="font-weight:600;">${escapeHtml(item.product?.name || '-')}</div>`,
            escapeHtml(item.product?.sku || '-'),
            escapeHtml(item.location?.name || '-'),
            formatRupiah(price, false),
            `${formatQuantity(qty)} ${escapeHtml(unit)}`,
            formatRupiah(sub, false),
        ];
    });

    const totals = [
        {
            label: `Total Kuantitas: ${formatQuantity(totalQty)} item | Grand Total Penerimaan:`,
            value: formatRupiah(totalAmount),
            labelSpan: 5,
            valSpan: 2,
            align: 'right',
        },
    ];

    const supplierText = doc.supplier?.name
        ? `${doc.supplier.name} ${doc.supplier.code ? `(${doc.supplier.code})` : ''}`
        : 'Tanpa Supplier / Internal GA';

    const sourceText = doc.source_type === 'GA_SERVICED' ? 'Hasil Servis GA' : 'Pengadaan Baru GA';

    return printDocument({
        title: 'BUKTI PENERIMAAN BARANG (GOODS RECEIPT)',
        subtitle: 'Surat Bukti Penerimaan Barang Masuk ke Gudang / Inventaris EDP',
        docNumber: doc.receipt_number || '-',
        docDate: doc.date || '-',
        status: doc.status || 'POSTED',
        meta: [
            { label: 'Nomor Penerimaan', value: doc.receipt_number },
            { label: 'Tanggal Penerimaan', value: doc.date },
            { label: 'No. SPB / Memo GA', value: doc.memo_number || '-' },
            { label: 'Sumber Barang', value: sourceText },
            { label: 'Supplier / Vendor', value: supplierText },
            { label: 'Dibuat Oleh', value: doc.creator?.name || '-' },
        ],
        tableHeaders,
        tableRows,
        totals,
        notes: doc.notes,
        signatures: [
            { role: 'Yang Menyerahkan', name: doc.supplier?.name || '............................................', title: 'Supplier / Petugas Pengirim' },
            { role: 'Yang Menerima', name: doc.creator?.name || '............................................', title: 'Petugas Gudang / EDP' },
            { role: 'Mengetahui / Disetujui', name: '............................................', title: 'Supervisor Gudang & Logistik' },
        ],
        ...extraOptions,
    });
}

/**
 * Cetak Dokumen Pengeluaran Barang (Goods Issue).
 *
 * @param {Object} doc StockIssue document
 * @param {Object} [extraOptions={}]
 */
export function printStockIssue(doc, extraOptions = {}) {
    if (!doc) return;

    const items = doc.items || [];
    let totalQty = 0;
    let totalAmount = 0;

    const tableHeaders = [
        { label: 'No.', width: '30px', align: 'center' },
        { label: 'Nama Produk / Barang', align: 'left' },
        { label: 'SKU / Kode', width: '100px', align: 'left', mono: true },
        { label: 'Lokasi Gudang', width: '110px', align: 'left' },
        { label: 'Harga Satuan (Rp)', width: '100px', align: 'right', mono: true },
        { label: 'Qty Keluar', width: '70px', align: 'right', mono: true },
        { label: 'Subtotal (Rp)', width: '105px', align: 'right', mono: true },
    ];

    const tableRows = items.map((item, idx) => {
        const qty = parseFloat(item.quantity) || 0;
        const price = Number(item.unit_price ?? item.product?.unit_price ?? 0);
        const sub = item.subtotal ?? (qty * price);

        totalQty += qty;
        totalAmount += Number(sub);

        const unit = item.product?.unit?.symbol || item.product?.unit?.name || '';

        return [
            String(idx + 1),
            `<div style="font-weight:600;">${escapeHtml(item.product?.name || '-')}</div>`,
            escapeHtml(item.product?.sku || '-'),
            escapeHtml(item.location?.name || '-'),
            formatRupiah(price, false),
            `${formatQuantity(qty)} ${escapeHtml(unit)}`,
            formatRupiah(sub, false),
        ];
    });

    const totals = [
        {
            label: `Total Kuantitas: ${formatQuantity(totalQty)} item | Grand Total Pengeluaran:`,
            value: formatRupiah(totalAmount),
            labelSpan: 5,
            valSpan: 2,
            align: 'right',
        },
    ];

    return printDocument({
        title: 'BUKTI PENGELUARAN BARANG (GOODS ISSUE)',
        subtitle: 'Surat Bukti Pengeluaran / Pemakaian Persediaan dari Gudang EDP',
        docNumber: doc.issue_number || '-',
        docDate: doc.date || '-',
        status: doc.status || 'POSTED',
        meta: [
            { label: 'Nomor Pengeluaran', value: doc.issue_number },
            { label: 'Tanggal Pengeluaran', value: doc.date },
            { label: 'Departemen Tujuan', value: doc.department ? `${doc.department.code} - ${doc.department.name}` : '-' },
            { label: 'Tujuan / Keperluan', value: doc.purpose || '-' },
            { label: 'Dibuat Oleh', value: doc.creator?.name || '-' },
            { label: 'Status Dokumen', value: doc.status || 'POSTED' },
        ],
        tableHeaders,
        tableRows,
        totals,
        notes: doc.notes,
        signatures: [
            { role: 'Yang Menyerahkan', name: doc.creator?.name || '............................................', title: 'Petugas Gudang / EDP' },
            { role: 'Yang Menerima', name: doc.recipient || (doc.department ? `Dept. ${doc.department.code}` : '............................................'), title: 'Penerima / Pemohon' },
            { role: 'Mengetahui / Disetujui', name: '............................................', title: 'Supervisor / Kepala Bagian' },
        ],
        ...extraOptions,
    });
}

/**
 * Menghasilkan dokumen HTML lengkap untuk Surat Jalan Transfer Barang Antar Lokasi
 * persis sesuai format standar Surat Jalan Alokasi Unit Toko (PT. INDOMARCO PRISMATAMA).
 *
 * @param {Object} transfer StockTransfer document
 * @param {Object} [extraOptions={}]
 * @returns {string}
 */
export function generateStockTransferSuratJalanHtml(transfer, extraOptions = {}) {
    if (!transfer) return '';

    const companyName = extraOptions.companyName || 'PT. INDOMARCO PRISMATAMA';
    const branchName = extraOptions.branchName || 'IDM YOGYAKARTA';
    const branchAddress = extraOptions.branchAddress || 'JL.ARTERI (LINGKAR LUAR BARAT)<br>DESA TRIHANGGO KEC GAMPING<br>KAB SLEMAN YOGYAKARTA';

    const printedBy = extraOptions.printedBy || transfer.created_by || 'EDP_YOG';
    const createdBy = transfer.created_by || printedBy;
    const senderUser = extraOptions.senderUser || transfer.created_by || createdBy || '';
    const currentDateIndo = formatIndoDate(new Date(), false);
    const currentTimeIndo = formatIndoTime(new Date());

    const senderCompanySubtitle = senderUser
        ? ((branchName === 'IDM YOGYAKARTA' && companyName === 'PT. INDOMARCO PRISMATAMA')
            ? `PT. INDOMARCO PRISMATAMA - IDM<br>YOGYAKARTA - ${escapeHtml(senderUser)}`
            : `${escapeHtml(companyName)} - ${escapeHtml(branchName)} - ${escapeHtml(senderUser)}`)
        : ((branchName === 'IDM YOGYAKARTA' && companyName === 'PT. INDOMARCO PRISMATAMA')
            ? 'PT. INDOMARCO PRISMATAMA - IDM<br>YOGYAKARTA'
            : `${escapeHtml(companyName)} - ${escapeHtml(branchName)}`);

    const docNumber = transfer.transfer_number || '-';
    const docDateIndo = formatIndoDate(transfer.transfer_date || transfer.created_at, true);

    const isReturn = transfer.transfer_type === 'RETURN';
    const defaultTransferType = isReturn ? 'Retur Barang' : 'Transfer Stock';

    const originDisplay = transfer.origin_location_name || transfer.originLocation?.name || '-';
    const destDisplay = transfer.destination_location_name || transfer.destinationLocation?.name || '-';

    const barcodeSvg = generateBarcodeSvg(docNumber, { height: 42, moduleWidth: 1.35 });

    const items = transfer.items || [];
    const rows = [];
    let rowNumber = 1;

    items.forEach((item) => {
        const qty = Number(item.sent_quantity ?? item.quantity ?? 1);
        const price = Number(item.unit_price ?? item.product?.unit_price ?? item.product_unit_price ?? 0);
        const subtotal = Number(item.subtotal ?? (qty * price));
        const priceStr = price > 0 ? formatRupiah(price, false) : '-';
        const subtotalStr = subtotal > 0 ? formatRupiah(subtotal, false) : (price > 0 ? '0' : '-');
        const serial = item.serial_number || '-';
        const sku = item.product_sku || item.product?.sku || '-';
        const name = item.product_name || item.product?.name || '-';
        const itemType = item.item_type || defaultTransferType;
        const itemNotes = item.notes || (item.condition === 'DEFECTIVE' ? 'Kondisi Rusak' : (transfer.notes || '-'));
        const refBkb = docNumber;
        const refPb = transfer.reference_number || transfer.memo_number || '-';

        rows.push(`
          <tr>
            <td style="text-align: center;">${rowNumber++}</td>
            <td style="text-align: left;">${escapeHtml(sku)}</td>
            <td style="text-align: left;">${escapeHtml(name)}</td>
            <td style="text-align: left;">${escapeHtml(itemType)}</td>
            <td style="text-align: right;">${formatQuantity(qty)}</td>
            <td style="text-align: right;">${priceStr}</td>
            <td style="text-align: right;">${subtotalStr}</td>
            <td style="text-align: left;">${escapeHtml(serial)}</td>
            <td style="text-align: left;">${escapeHtml(itemNotes)}</td>
            <td style="text-align: left;">${escapeHtml(refBkb)}</td>
            <td style="text-align: left;">${escapeHtml(refPb)}</td>
          </tr>
        `);
    });

    if (rows.length === 0) {
        rows.push(`
          <tr>
            <td style="text-align: center;">1</td>
            <td style="text-align: left;">-</td>
            <td style="text-align: left;">-</td>
            <td style="text-align: left;">${escapeHtml(defaultTransferType)}</td>
            <td style="text-align: right;">1</td>
            <td style="text-align: right;">-</td>
            <td style="text-align: right;">-</td>
            <td style="text-align: left;">-</td>
            <td style="text-align: left;">-</td>
            <td style="text-align: left;">${escapeHtml(docNumber)}</td>
            <td style="text-align: left;">-</td>
          </tr>
        `);
    }

    return `<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>SURAT JALAN - ${escapeHtml(docNumber)}</title>
  <style>
    @page {
      size: A4 portrait;
      margin: 15mm 15mm 15mm 15mm;
    }
    *, *::before, *::after {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, Helvetica, sans-serif;
      color: #000000;
      background: #ffffff;
      font-size: 11px;
      line-height: 1.35;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .sj-container {
      width: 100%;
      max-width: 820px;
      margin: 0 auto;
      padding: 10px;
    }
    @media print {
      @page {
        size: A4 portrait;
        margin: 12mm 12mm 12mm 12mm;
      }
      body {
        margin: 0;
        padding: 0;
      }
      .sj-container {
        max-width: 100%;
        padding: 0;
      }
    }
    .sj-top-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 22px;
    }
    .sj-company-info {
      font-style: italic;
      font-weight: bold;
      font-size: 11px;
      line-height: 1.35;
    }
    .sj-print-meta {
      font-size: 11px;
    }
    .sj-meta-table {
      border-collapse: collapse;
    }
    .sj-meta-table td {
      padding: 1px 0;
      vertical-align: top;
      font-size: 11px;
    }
    .sj-meta-label {
      width: 95px;
    }
    .sj-meta-colon {
      width: 14px;
      text-align: center;
    }
    .sj-title-section {
      text-align: center;
      margin-bottom: 25px;
    }
    .sj-title {
      font-size: 18px;
      font-weight: bold;
      letter-spacing: 0.5px;
      margin-bottom: 6px;
    }
    .sj-barcode-wrap {
      display: flex;
      justify-content: center;
      margin-bottom: 8px;
    }
    .sj-doc-meta-block {
      display: inline-block;
      text-align: left;
      font-size: 11px;
    }
    .sj-recipient-section {
      margin-bottom: 18px;
      font-size: 11px;
      line-height: 1.4;
    }
    .sj-recipient-title {
      margin-bottom: 2px;
    }
    .sj-recipient-company {
      margin-bottom: 10px;
    }
    .sj-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
      font-size: 10.5px;
    }
    .sj-table th, .sj-table td {
      border: 1px solid #000000;
      padding: 4px 5px;
      vertical-align: middle;
    }
    .sj-table th {
      font-weight: bold;
      background: #ffffff;
    }
    .sj-signatures {
      display: flex;
      justify-content: space-between;
      margin-top: 15px;
      page-break-inside: avoid;
    }
    .sj-sig-box {
      width: 48%;
    }
    .sj-sig-title {
      font-size: 11px;
      margin-bottom: 2px;
    }
    .sj-sig-subtitle {
      font-size: 11px;
      font-weight: normal;
      margin-bottom: 2px;
    }
    .sj-sig-space {
      height: 60px;
    }
    .sj-sig-note {
      font-style: italic;
      font-size: 10.5px;
      margin-bottom: 2px;
    }
    .sj-sig-line {
      font-size: 11px;
    }
  </style>
</head>
<body>
  <div class="sj-container">
    <!-- Header Atas -->
    <div class="sj-top-header">
      <div class="sj-company-info">
        ${escapeHtml(companyName)}<br>
        ${escapeHtml(branchName)}<br>
        ${branchAddress}
      </div>
      <div class="sj-print-meta">
        <table class="sj-meta-table">
          <tr>
            <td class="sj-meta-label">Dicetak Oleh</td>
            <td class="sj-meta-colon">:</td>
            <td>${escapeHtml(printedBy)}</td>
          </tr>
          <tr>
            <td class="sj-meta-label">Dibuat Oleh</td>
            <td class="sj-meta-colon">:</td>
            <td>${escapeHtml(createdBy)}</td>
          </tr>
          <tr>
            <td class="sj-meta-label">Tanggal Cetak</td>
            <td class="sj-meta-colon">:</td>
            <td>${escapeHtml(currentDateIndo)}</td>
          </tr>
          <tr>
            <td class="sj-meta-label">Jam Cetak</td>
            <td class="sj-meta-colon">:</td>
            <td>${escapeHtml(currentTimeIndo)}</td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Judul & Barcode Tengah -->
    <div class="sj-title-section">
      <div class="sj-title">SURAT JALAN</div>
      <div class="sj-barcode-wrap">
        ${barcodeSvg}
      </div>
      <div class="sj-doc-meta-block">
        <table class="sj-meta-table">
          <tr>
            <td style="width:55px;">Nomor</td>
            <td class="sj-meta-colon">:</td>
            <td>${escapeHtml(docNumber)}</td>
          </tr>
          <tr>
            <td>Tanggal</td>
            <td class="sj-meta-colon">:</td>
            <td>${escapeHtml(docDateIndo)}</td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Tujuan / Kepada Yth. -->
    <div class="sj-recipient-section">
      <div class="sj-recipient-title">Kepada Yth.</div>
      <div class="sj-recipient-company">${escapeHtml(companyName)}</div>

      <div>Ditujukan ke : &nbsp;${escapeHtml(destDisplay ? `${destDisplay} ${branchName}` : (branchName || '-'))}</div>
      <div>Dari Lokasi : &nbsp;${escapeHtml(originDisplay)}</div>
    </div>

    <!-- Tabel Rincian Barang -->
    <table class="sj-table">
      <thead>
        <tr>
          <th style="width: 30px; text-align: center;">No</th>
          <th style="width: 55px; text-align: left;">PLU</th>
          <th style="text-align: left;">Nama dan Spesifikasi</th>
          <th style="width: 85px; text-align: left;">Tipe Barang</th>
          <th style="width: 48px; text-align: right;">Kuantitas</th>
          <th style="width: 78px; text-align: right;">Harga Satuan</th>
          <th style="width: 82px; text-align: right;">Total Nilai</th>
          <th style="width: 110px; text-align: left;">Nomor Serial</th>
          <th style="width: 75px; text-align: left;">Keterangan</th>
          <th style="width: 85px; text-align: left;"><i>Ref. Kode BKB</i></th>
          <th style="width: 85px; text-align: left;"><i>Ref. Kode PB</i></th>
        </tr>
      </thead>
      <tbody>
        ${rows.join('')}
      </tbody>
    </table>

    <!-- Tanda Tangan -->
    <div class="sj-signatures">
      <div class="sj-sig-box">
        <div class="sj-sig-title">Diterima Oleh :</div>
        <div class="sj-sig-space"></div>
        <div class="sj-sig-note">(tandatangan dan cap Perusahaan)</div>
        <div class="sj-sig-line">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
      </div>
      <div class="sj-sig-box" style="margin-left: 50px;">
        <div class="sj-sig-title">Dikirim Oleh :</div>
        <div class="sj-sig-subtitle">${senderCompanySubtitle}</div>
        <div class="sj-sig-space" style="height: 44px;"></div>
        <div class="sj-sig-note">(tandatangan dan cap Perusahaan)</div>
        <div class="sj-sig-line">( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )</div>
      </div>
    </div>
  </div>
</body>
</html>`;
}

/**
 * Cetak Surat Jalan Transfer Barang Antar Lokasi (Format Resmi Standar PT. Indomarco Prismatama).
 *
 * @param {Object} transfer StockTransfer document
 * @param {Object} [extraOptions={}]
 */
export function printStockTransfer(transfer, extraOptions = {}) {
    if (!transfer) return;
    const html = generateStockTransferSuratJalanHtml(transfer, extraOptions);
    return printDocument({ rawHtml: html, ...extraOptions });
}

/**
 * Cetak Berita Acara Penyesuaian Stok (Stock Adjustment).
 *
 * @param {Object} adjustment StockAdjustment document
 * @param {Object} [extraOptions={}]
 */
export function printStockAdjustment(adjustment, extraOptions = {}) {
    if (!adjustment) return;

    const items = adjustment.items || [];
    let totalQty = 0;
    let totalAmount = 0;

    const isIncrease = adjustment.direction === 'INCREASE';

    const tableHeaders = [
        { label: 'No.', width: '30px', align: 'center' },
        { label: 'Nama Produk / Barang', align: 'left' },
        { label: 'SKU / Kode', width: '100px', align: 'left', mono: true },
        { label: 'Satuan', width: '55px', align: 'center' },
        { label: 'Delta Qty', width: '80px', align: 'right', mono: true },
        { label: 'Harga Satuan (Rp)', width: '95px', align: 'right', mono: true },
        { label: 'Total Nilai (Rp)', width: '105px', align: 'right', mono: true },
        { label: 'Keterangan Item', align: 'left' },
    ];

    const tableRows = items.map((item, idx) => {
        const qty = parseFloat(item.quantity) || 0;
        const price = Number(item.unit_price || 0);
        const sub = item.subtotal ?? (qty * price);

        totalQty += qty;
        totalAmount += Number(sub);

        const deltaPrefix = isIncrease ? '+' : '-';
        const color = isIncrease ? '#059669' : '#dc2626';

        return [
            String(idx + 1),
            `<div style="font-weight:600;">${escapeHtml(item.product_name || '-')}</div>`,
            escapeHtml(item.product_sku || '-'),
            escapeHtml(item.unit_symbol || '-'),
            `<span style="color:${color};font-weight:700;">${deltaPrefix}${formatQuantity(qty)}</span>`,
            formatRupiah(price, false),
            formatRupiah(sub, false),
            escapeHtml(item.notes || '-'),
        ];
    });

    const totals = [
        {
            label: `Total Penyesuaian: ${formatQuantity(totalQty)} unit | Grand Total Nilai Penyesuaian:`,
            value: formatRupiah(totalAmount),
            labelSpan: 4,
            valSpan: 4,
            align: 'right',
        },
    ];

    return printDocument({
        title: 'BERITA ACARA PENYESUAIAN STOK (STOCK ADJUSTMENT)',
        subtitle: 'Dokumen Koreksi Administratif & Penyesuaian Saldo Fisik Persediaan Gudang',
        docNumber: adjustment.adjustment_number || '-',
        docDate: adjustment.adjustment_date || '-',
        status: adjustment.status || 'DRAFT',
        meta: [
            { label: 'Nomor Dokumen', value: adjustment.adjustment_number },
            { label: 'Tanggal Adjustment', value: adjustment.adjustment_date },
            { label: 'Lokasi Gudang', value: adjustment.location_name || '-' },
            { label: 'Arah Penyesuaian', value: isIncrease ? 'TAMBAH STOK (+)' : 'KURANG STOK (-)' },
            { label: 'Alasan Penyesuaian', value: adjustment.reason_label || adjustment.reason_code || '-' },
            { label: 'Dibuat Oleh', value: adjustment.created_by || '-' },
        ],
        tableHeaders,
        tableRows,
        totals,
        notes: adjustment.notes,
        signatures: [
            { role: 'Petugas Pemeriksa', name: adjustment.created_by || '............................................', title: 'Inventory Staff' },
            { role: 'Verifikator Stok', name: '............................................', title: 'Supervisor Gudang' },
            { role: 'Menyetujui', name: '............................................', title: 'Manager Operasional / EDP' },
        ],
        ...extraOptions,
    });
}

/**
 * Cetak Berita Acara Hasil Stock Opname.
 *
 * @param {Object} opname StockOpname document
 * @param {Object} [extraOptions={}]
 */
export function printStockOpname(opname, extraOptions = {}) {
    if (!opname) return;

    const items = opname.items || [];
    let totalSys = 0;
    let totalPhys = 0;
    let totalDiff = 0;

    const tableHeaders = [
        { label: 'No.', width: '28px', align: 'center' },
        { label: 'Nama Produk / Barang', align: 'left' },
        { label: 'SKU / Kode', width: '90px', align: 'left', mono: true },
        { label: 'Satuan', width: '50px', align: 'center' },
        { label: 'Qty Sistem', width: '65px', align: 'right', mono: true },
        { label: 'Qty Fisik', width: '65px', align: 'right', mono: true },
        { label: 'Selisih', width: '60px', align: 'right', mono: true },
        { label: 'Status Selisih', width: '90px', align: 'center' },
        { label: 'Keterangan', align: 'left' },
    ];

    const tableRows = items.map((item, idx) => {
        const sys = parseFloat(item.system_quantity) || 0;
        const phys = parseFloat(item.physical_quantity) || 0;
        const diff = item.difference !== undefined ? parseFloat(item.difference) : (phys - sys);

        totalSys += sys;
        totalPhys += phys;
        totalDiff += diff;

        let statusText = '<span style="color:#059669;font-weight:600;">Cocok</span>';
        if (diff > 0) {
            statusText = '<span style="color:#2563eb;font-weight:600;">Lebih (+)</span>';
        } else if (diff < 0) {
            statusText = '<span style="color:#dc2626;font-weight:600;">Kurang (-)</span>';
        }

        const diffDisplay = diff === 0
            ? '0'
            : (diff > 0 ? `<span style="color:#2563eb;font-weight:700;">+${formatQuantity(diff)}</span>` : `<span style="color:#dc2626;font-weight:700;">${formatQuantity(diff)}</span>`);

        return [
            String(idx + 1),
            `<div style="font-weight:600;">${escapeHtml(item.product_name || '-')}</div>`,
            escapeHtml(item.product_sku || '-'),
            escapeHtml(item.unit_symbol || '-'),
            formatQuantity(sys),
            formatQuantity(phys),
            diffDisplay,
            statusText,
            escapeHtml(item.notes || '-'),
        ];
    });

    const diffTotalDisplay = totalDiff === 0
        ? '0'
        : (totalDiff > 0 ? `+${formatQuantity(totalDiff)}` : `${formatQuantity(totalDiff)}`);

    const totals = [
        {
            label: `Total Qty Sistem: ${formatQuantity(totalSys)} | Total Qty Fisik: ${formatQuantity(totalPhys)} | Total Selisih: ${diffTotalDisplay}`,
            value: '',
            labelSpan: 4,
            valSpan: 5,
            align: 'right',
        },
    ];

    return printDocument({
        title: 'BERITA ACARA HASIL STOCK OPNAME',
        subtitle: 'Hasil Rekonsiliasi & Pencocokan Fisik Persediaan Gudang vs Saldo Sistem',
        docNumber: opname.opname_number || '-',
        docDate: opname.opname_date || '-',
        status: opname.status || 'DRAFT',
        meta: [
            { label: 'Nomor Opname', value: opname.opname_number },
            { label: 'Tanggal Opname', value: opname.opname_date },
            { label: 'Lokasi Gudang', value: opname.location_name || '-' },
            { label: 'Status Sesi', value: opname.status || '-' },
            { label: 'Dibuat Oleh', value: opname.created_by || '-' },
            { label: 'Diposting Oleh', value: opname.posted_by ? `${opname.posted_by} (${opname.posted_at || ''})` : '-' },
        ],
        tableHeaders,
        tableRows,
        totals,
        notes: opname.notes,
        signatures: [
            { role: 'Petugas Penghitung Fisik', name: opname.created_by || '............................................', title: 'Tim Opname' },
            { role: 'Saksi / Pemeriksa', name: '............................................', title: 'Internal Audit / Checker' },
            { role: 'Penanggung Jawab Gudang', name: opname.posted_by || '............................................', title: 'Supervisor Gudang & EDP' },
        ],
        ...extraOptions,
    });
}
