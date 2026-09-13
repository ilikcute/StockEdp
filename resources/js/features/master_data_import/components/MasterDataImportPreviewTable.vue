<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between">
      <h5 class="text-xs font-bold text-gray-700 uppercase tracking-wider">
        Preview Data ({{ previewRows.length }} Baris Pertama):
      </h5>
      <span class="text-[11px] text-gray-400">Menampilkan maksimal 20 baris</span>
    </div>
    <div class="max-h-60 overflow-x-auto overflow-y-auto border border-gray-200 rounded-xl shadow-2xs custom-scrollbar">
      <table class="w-full text-left text-xs border-collapse min-w-[500px]">
        <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10">
          <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
            <th class="py-1.5 px-1.5 w-10 text-center whitespace-nowrap">
              No.
            </th>
            <th class="py-1.5 px-2 w-16 text-center whitespace-nowrap">
              Status
            </th>
            <th
              v-for="col in columns"
              :key="col"
              class="py-1.5 px-2 whitespace-nowrap capitalize"
              :class="col === 'unit_price' ? 'text-right' : ''"
            >
              {{ formatColumnName(col) }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr
            v-for="row in previewRows"
            :key="row.row_number"
            :class="row.is_valid ? 'hover:bg-gray-50/80 transition-colors' : 'bg-red-50/30 hover:bg-red-50/60 transition-colors'"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ row.row_number }}
            </td>
            <td class="py-1.5 px-2 text-center whitespace-nowrap">
              <span
                class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider inline-flex items-center"
                :class="row.is_valid ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20'"
              >
                {{ row.is_valid ? 'VALID' : 'ERROR' }}
              </span>
            </td>
            <td
              v-for="col in columns"
              :key="col"
              class="py-1.5 px-2 text-gray-800 truncate max-w-xs font-mono text-[11px] whitespace-nowrap"
              :class="col === 'unit_price' ? 'text-right font-semibold text-emerald-700' : ''"
            >
              {{ formatCellValue(row, col) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
  previewRows: {
    type: Array,
    required: true,
  },
  columns: {
    type: Array,
    required: true,
  },
});

const columnLabels = {
  sku: 'SKU',
  barcode: 'Barcode',
  name: 'Nama',
  description: 'Deskripsi',
  category_code: 'Kode Kategori',
  unit_code: 'Kode Satuan',
  minimum_stock: 'Min. Stok',
  unit_price: 'Harga Satuan (Rp)',
  code: 'Kode',
  symbol: 'Simbol',
  address: 'Alamat',
  phone: 'Telepon',
};

function formatColumnName(col) {
  return columnLabels[col] || col.replace('_', ' ');
}

function formatCellValue(row, col) {
  const val = row[col];
  if (val === null || val === undefined || val === '') return '-';
  if (col === 'unit_price') {
    const num = Number(val);
    return isNaN(num) ? val : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(num);
  }
  return val;
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
