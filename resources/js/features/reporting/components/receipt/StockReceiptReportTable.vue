<template>
  <div class="overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar">
    <table class="w-full text-left text-xs border-collapse">
      <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10">
        <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
          <th
            scope="col"
            class="py-1.5 px-1.5 w-8 text-center whitespace-nowrap"
          >
            No.
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            No. Penerimaan
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Tgl Dokumen
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Waktu Posting
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Supplier
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Lokasi
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Produk
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Kategori / Unit
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 text-right whitespace-nowrap"
          >
            Harga Satuan
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 text-right whitespace-nowrap"
          >
            Quantity
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 text-right whitespace-nowrap"
          >
            Total Nilai
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Posted By
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Notes
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 bg-white">
        <tr
          v-for="(item, index) in items"
          :key="item.item_id"
          class="hover:bg-gray-50/80 transition-colors"
        >
          <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
            {{ rowNumber(pagination, index) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] font-mono font-medium text-indigo-600 whitespace-nowrap">
            {{ item.receipt_number }}
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
            {{ item.document_date }}
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-500 font-mono whitespace-nowrap">
            {{ formatTimestamp(item.posted_at) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-900 whitespace-nowrap">
            {{ item.supplier?.name || '-' }}
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
            {{ item.location?.name || '-' }}
          </td>
          <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
            <div class="font-medium text-gray-900">
              {{ item.product?.name }}
            </div>
            <div class="text-[10px] text-gray-400 font-mono">
              SKU: {{ item.product?.sku }}
            </div>
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
            <div>{{ item.product?.category_name }}</div>
            <div class="text-[10px] text-gray-400">
              {{ item.product?.unit_name }}
            </div>
          </td>
          <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-700 whitespace-nowrap">
            {{ formatRupiah(item.product?.unit_price) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-900 font-semibold whitespace-nowrap">
            {{ formatQuantity(item.quantity) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] font-mono text-right text-emerald-700 font-semibold whitespace-nowrap">
            {{ formatRupiah(item.total_amount) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
            {{ item.posted_by?.name || '-' }}
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-500 max-w-xs truncate">
            {{ item.notes || '-' }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { formatRupiah, formatQuantity, rowNumber, formatTimestamp } from '@/shared/utils/formatters.js';

defineProps({
  items: { type: Array, required: true },
  pagination: { type: Object, default: null },
});
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
