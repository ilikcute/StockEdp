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
        const html = generatePrintDocumentHtml(options);

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

/**
 * Cetak Dokumen Bukti Alokasi & Penggantian Unit Toko.
 *
 * @param {Object} doc StoreAllocation document
 * @param {Object} [extraOptions={}]
 */
export function printStoreAllocation(doc, extraOptions = {}) {
    if (!doc) return;

    const items = doc.items || [];
    let totalInstalledQty = 0;
    let totalInstalledAmount = 0;
    let totalPulledQty = 0;

    const tableHeaders = [
        { label: 'No.', width: '28px', align: 'center' },
        { label: 'Unit Baru Dipasang', align: 'left' },
        { label: 'S/N Baru', width: '90px', align: 'center', mono: true },
        { label: 'Qty', width: '40px', align: 'right' },
        { label: 'Harga (Rp)', width: '80px', align: 'right', mono: true },
        { label: 'Total (Rp)', width: '85px', align: 'right', mono: true },
        { label: 'Unit Lama Ditarik (RUSAK)', align: 'left' },
        { label: 'S/N Tarik', width: '90px', align: 'center', mono: true },
        { label: 'Qty', width: '40px', align: 'right' },
        { label: 'Alasan Kerusakan', align: 'left' },
    ];

    const tableRows = items.map((item, idx) => {
        const instQty = Number(item.installed_quantity || 0);
        const instPrice = Number(item.unit_price || 0);
        const instSubtotal = Number(item.installed_subtotal ?? (instQty * instPrice));
        const pullQty = Number(item.pulled_quantity || 0);

        totalInstalledQty += instQty;
        totalInstalledAmount += instSubtotal;
        totalPulledQty += pullQty;

        return [
            String(idx + 1),
            `<div style="font-weight:600;">${escapeHtml(item.product_name || '-')}</div><div style="font-size:8.5px;color:#64748b;font-family:monospace;">${escapeHtml(item.product_sku || '')}</div>`,
            escapeHtml(item.installed_serial_number || '-'),
            formatQuantity(instQty),
            formatRupiah(instPrice, false),
            formatRupiah(instSubtotal, false),
            item.pulled_product_name ? `<div style="font-weight:600;color:#991b1b;">${escapeHtml(item.pulled_product_name)}</div>` : '<span style="color:#94a3b8;">-</span>',
            escapeHtml(item.pulled_serial_number || '-'),
            pullQty > 0 ? formatQuantity(pullQty) : '<span style="color:#94a3b8;">-</span>',
            escapeHtml(item.pulled_reason || '-'),
        ];
    });

    const totals = [
        {
            label: `Total Pasang: ${formatQuantity(totalInstalledQty)} unit | Total Tarik: ${formatQuantity(totalPulledQty)} unit | Grand Total Nilai Pasang:`,
            value: formatRupiah(totalInstalledAmount),
            labelSpan: 5,
            valSpan: 5,
            align: 'right',
        },
    ];

    const storeInfo = doc.store_name ? `${doc.store_name} ${doc.store_code ? `(${doc.store_code})` : ''}` : '-';
    const techInfo = doc.technician_name ? `${doc.technician_name} ${doc.technician_location_name ? `(${doc.technician_location_name})` : ''}` : '-';

    return printDocument({
        title: 'BUKTI ALOKASI & PENGGANTIAN UNIT TOKO',
        subtitle: 'Berita Acara Pemasangan Perangkat EDP Baru dan Penarikan Perangkat Rusak di Toko',
        docNumber: doc.allocation_number || '-',
        docDate: doc.allocated_at || doc.created_at || '-',
        status: 'SELESAI',
        statusLabel: 'COMPLETED',
        meta: [
            { label: 'Nomor Alokasi', value: doc.allocation_number },
            { label: 'Tanggal Alokasi', value: doc.allocated_at },
            { label: 'Toko Tujuan', value: storeInfo },
            { label: 'Teknisi Pelaksana', value: techInfo },
            { label: 'Waktu Input', value: doc.created_at || '-' },
            { label: 'Status Dokumen', value: 'SELESAI (TERPOSTING)' },
        ],
        tableHeaders,
        tableRows,
        totals,
        notes: doc.notes,
        signatures: [
            { role: 'Yang Menyerahkan (Teknisi)', name: doc.technician_name || '............................................', title: doc.technician_location_name || 'Teknisi EDP' },
            { role: 'Yang Menerima (Toko)', name: '............................................', title: `Kepala Toko / PIC ${doc.store_name || ''}` },
            { role: 'Mengetahui / Disetujui', name: '............................................', title: 'Supervisor EDP / Logistik' },
        ],
        ...extraOptions,
    });
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
            { label: 'Tujuan / Alasan', value: doc.purpose || '-' },
            { label: 'Penerima Barang', value: doc.recipient || '-' },
            { label: 'Dibuat Oleh', value: doc.creator?.name || '-' },
            { label: 'Status Dokumen', value: doc.status || 'POSTED' },
        ],
        tableHeaders,
        tableRows,
        totals,
        notes: doc.notes,
        signatures: [
            { role: 'Yang Menyerahkan', name: doc.creator?.name || '............................................', title: 'Petugas Gudang / EDP' },
            { role: 'Yang Menerima', name: doc.recipient || '............................................', title: 'Penerima / Pemohon' },
            { role: 'Mengetahui / Disetujui', name: '............................................', title: 'Supervisor / Kepala Bagian' },
        ],
        ...extraOptions,
    });
}

/**
 * Cetak Surat Jalan Transfer Barang Antar Lokasi.
 *
 * @param {Object} transfer StockTransfer document
 * @param {Object} [extraOptions={}]
 */
export function printStockTransfer(transfer, extraOptions = {}) {
    if (!transfer) return;

    const items = transfer.items || [];
    let totalSent = 0;
    let totalReceived = 0;

    const isReturn = transfer.transfer_type === 'RETURN';

    const tableHeaders = [
        { label: 'No.', width: '30px', align: 'center' },
        { label: 'Nama Produk / Barang', align: 'left' },
        { label: 'SKU / Kode', width: '90px', align: 'left', mono: true },
        { label: 'Satuan', width: '55px', align: 'center' },
        { label: 'Qty Kirim', width: '65px', align: 'right', mono: true },
        { label: 'Qty Terima', width: '65px', align: 'right', mono: true },
        { label: 'Selisih', width: '55px', align: 'right', mono: true },
        { label: 'Catatan Item', align: 'left' },
    ];

    const tableRows = items.map((item, idx) => {
        const sent = parseFloat(item.sent_quantity ?? item.quantity) || 0;
        const rec = parseFloat(item.received_quantity) || 0;
        const diff = item.difference !== undefined ? item.difference : (rec - sent);

        totalSent += sent;
        totalReceived += rec;

        const diffDisplay = diff === 0
            ? '<span style="color:#059669;">0</span>'
            : (diff > 0 ? `<span style="color:#2563eb;font-weight:700;">+${diff}</span>` : `<span style="color:#dc2626;font-weight:700;">${diff}</span>`);

        return [
            String(idx + 1),
            `<div style="font-weight:600;">${escapeHtml(item.product_name || item.product?.name || '-')}</div>`,
            escapeHtml(item.product_sku || item.product?.sku || '-'),
            escapeHtml(item.unit_symbol || item.product?.unit?.symbol || '-'),
            formatQuantity(sent),
            transfer.status === 'DRAFT' ? '<span style="color:#94a3b8;">-</span>' : formatQuantity(rec),
            transfer.status === 'DRAFT' ? '<span style="color:#94a3b8;">-</span>' : diffDisplay,
            escapeHtml(item.notes || '-'),
        ];
    });

    const totals = [
        {
            label: `Total Barang Dikirim: ${formatQuantity(totalSent)} unit | Total Diterima: ${transfer.status === 'DRAFT' ? '-' : formatQuantity(totalReceived)} unit`,
            value: '',
            labelSpan: 4,
            valSpan: 4,
            align: 'right',
        },
    ];

    const statusMap = {
        DRAFT: 'Draft',
        IN_TRANSIT: 'Dalam Pengiriman (In-Transit)',
        RECEIVED: 'Selesai Diterima',
        DISCREPANCY: 'Ada Selisih (Discrepancy)',
        CANCELED: 'Dibatalkan',
    };

    return printDocument({
        title: isReturn ? 'SURAT JALAN RETUR KE GUDANG PUSAT' : 'SURAT JALAN TRANSFER BARANG',
        subtitle: 'Dokumen Bukti Mutasi & Pengiriman Fisik Barang Antar Lokasi Inventaris',
        docNumber: transfer.transfer_number || '-',
        docDate: transfer.transfer_date || '-',
        status: transfer.status || 'DRAFT',
        statusLabel: statusMap[transfer.status] || transfer.status,
        meta: [
            { label: 'Nomor Transfer', value: transfer.transfer_number },
            { label: 'Tanggal Transfer', value: transfer.transfer_date },
            { label: 'Jenis Transfer', value: isReturn ? 'Retur ke Gudang' : 'Transfer Antar Lokasi' },
            { label: 'Lokasi Asal', value: transfer.origin_location_name || '-' },
            { label: 'Lokasi Tujuan', value: transfer.destination_location_name || '-' },
            { label: 'Dibuat Oleh', value: transfer.created_by || '-' },
            { label: 'Waktu Kirim', value: transfer.shipped_at || '-' },
            { label: 'Waktu Diterima', value: transfer.received_at || '-' },
            { label: 'Status Pengiriman', value: statusMap[transfer.status] || transfer.status },
        ],
        tableHeaders,
        tableRows,
        totals,
        notes: transfer.notes,
        signatures: [
            { role: 'Yang Menyerahkan (Pengirim)', name: transfer.created_by || '............................................', title: transfer.origin_location_name || 'Petugas Pengirim' },
            { role: 'Petugas Pengantar / Ekspedisi', name: '............................................', title: 'Driver / Kurir' },
            { role: 'Yang Menerima', name: '............................................', title: transfer.destination_location_name || 'Petugas Penerima' },
        ],
        ...extraOptions,
    });
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
