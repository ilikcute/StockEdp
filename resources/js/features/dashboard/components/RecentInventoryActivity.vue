<template>
  <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-3.5">
    <div class="flex items-center justify-between mb-2">
      <div>
        <h3 class="text-xs font-bold text-gray-900 flex items-center gap-1.5">
          <svg
            class="w-4 h-4 text-indigo-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          Aktivitas Persediaan Terkini (Maks. 10 Total)
        </h3>
        <p class="text-[11px] text-gray-500 mt-0.5">
          Pergerakan stok fisik terbaru tercatat dalam sistem.
        </p>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-if="!activities || activities.length === 0"
      class="text-center py-6 text-gray-400 text-xs"
    >
      Belum ada aktivitas pergerakan stok pada lokasi terjangkau.
    </div>

    <!-- Table with No. Sequence Column -->
    <div
      v-else
      class="overflow-x-auto max-h-[300px] overflow-y-auto custom-scrollbar"
    >
      <table class="w-full text-left text-xs border-collapse">
        <thead class="sticky top-0 bg-gray-50 z-10">
          <tr class="text-gray-600 font-semibold border-b border-gray-200">
            <th class="py-1 px-1.5 w-8 text-center">
              No.
            </th>
            <th class="py-1 px-2">
              Waktu
            </th>
            <th class="py-1 px-2">
              Tipe
            </th>
            <th class="py-1 px-2">
              No. Dokumen
            </th>
            <th class="py-1 px-2">
              SKU & Produk
            </th>
            <th class="py-1 px-2">
              Lokasi
            </th>
            <th class="py-1 px-2 text-right">
              Harga
            </th>
            <th class="py-1 px-2 text-right">
              Jumlah
            </th>
            <th class="py-1 px-2 text-right">
              Total Gross
            </th>
            <th class="py-1 px-2">
              Petugas
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr
            v-for="(item, index) in activities"
            :key="item.id || index"
            class="hover:bg-gray-50/80 transition-colors"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px]">
              {{ index + 1 }}
            </td>
            <td class="py-1.5 px-2 text-gray-600 whitespace-nowrap text-[11px]">
              {{ formatTimestamp(item.occurred_at) }}
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap">
              <span :class="['px-1.5 py-0.5 rounded text-[9px] font-bold uppercase', typeBadgeClass(item.type)]">
                {{ formatTypeLabel(item.type) }}
              </span>
            </td>
            <td class="py-1.5 px-2 font-mono text-gray-800 whitespace-nowrap text-[11px]">
              {{ item.reference_number || '-' }}
            </td>
            <td class="py-1.5 px-2">
              <div class="font-medium text-gray-900 leading-tight">
                {{ item.product_name }}
              </div>
              <div class="text-[10px] text-gray-400 font-mono">
                {{ item.product_sku }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-gray-600 whitespace-nowrap text-[11px]">
              <span class="font-semibold">{{ item.location_code }}</span> — {{ item.location_name }}
            </td>
            <td class="py-1.5 px-2 text-right font-mono text-gray-600 whitespace-nowrap text-[11px]">
              {{ formatRupiah(item.unit_price) }}
            </td>
            <td class="py-1.5 px-2 text-right font-mono font-bold text-gray-900 whitespace-nowrap text-[11px]">
              {{ formatQuantity(item.quantity, true) }} {{ item.unit_symbol }}
            </td>
            <td class="py-1.5 px-2 text-right font-mono font-bold text-gray-900 whitespace-nowrap text-[11px]">
              {{ formatRupiah(calculateGross(item)) }}
            </td>
            <td class="py-1.5 px-2 text-gray-500 whitespace-nowrap text-[11px]">
              {{ item.performed_by || 'System' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { formatTimestamp, formatQuantity, formatRupiah } from '@/shared/utils/formatters.js';

defineProps({
  activities: {
    type: Array,
    default: () => [],
  },
});

const calculateGross = (item) => {
  if (item.total_gross !== undefined && item.total_gross !== null) {
    return item.total_gross;
  }
  const qty = parseFloat(item.quantity) || 0;
  const price = parseFloat(item.unit_price) || 0;
  return qty * price;
};

const formatTypeLabel = (type) => {
  const map = {
    RECEIPT: 'Penerimaan',
    RECEIPT_GA: 'Penerimaan GA',
    ISSUE: 'Pengeluaran',
    TRANSFER_IN: 'Transfer Masuk',
    TRANSFER_OUT: 'Transfer Keluar',
    STORE_ALLOCATION: 'Alokasi Toko',
    REPLACEMENT_PULL: 'Tarik Unit Bekas',
    RETURN_TO_WAREHOUSE: 'Retur ke Gudang',
    ADJUSTMENT_IN: 'Penyesuaian (+)',
    ADJUSTMENT_OUT: 'Penyesuaian (-)',
    OPNAME_IN: 'Opname (+)',
    OPNAME_OUT: 'Opname (-)',
    REVERSAL: 'Pembatalan',
  };
  return map[type] || type;
};

const typeBadgeClass = (type) => {
  if (['RECEIPT', 'RECEIPT_GA', 'TRANSFER_IN', 'REPLACEMENT_PULL', 'RETURN_TO_WAREHOUSE', 'ADJUSTMENT_IN', 'OPNAME_IN'].includes(type)) {
    return 'bg-emerald-100 text-emerald-800';
  }
  if (['ISSUE', 'TRANSFER_OUT', 'STORE_ALLOCATION', 'ADJUSTMENT_OUT', 'OPNAME_OUT'].includes(type)) {
    return 'bg-amber-100 text-amber-800';
  }
  if (type === 'REVERSAL') {
    return 'bg-rose-100 text-rose-800';
  }
  return 'bg-gray-100 text-gray-800';
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 4px;
}
</style>
