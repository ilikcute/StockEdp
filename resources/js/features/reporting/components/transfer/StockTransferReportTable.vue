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
            No. Transfer
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
            Status
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Asal → Tujuan
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Produk
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
            Nilai Transfer
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Pengiriman
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Penerimaan
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Durasi Transit
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
            {{ item.transfer_number }}
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
            {{ item.document_date }}
          </td>
          <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
            <span
              class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider inline-flex items-center"
              :class="item.status === 'RECEIVED' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20'"
            >
              {{ getTransferStatusLabel(item.status) }}
            </span>
            <span
              v-if="item.is_in_transit"
              class="ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider inline-flex items-center bg-blue-50 text-blue-700 ring-1 ring-blue-600/20"
            >
              In-Transit
            </span>
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-900 whitespace-nowrap">
            <div>{{ item.origin_location?.name }}</div>
            <div class="text-[10px] text-gray-400">
              → {{ item.destination_location?.name }}
            </div>
          </td>
          <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
            <div class="font-medium text-gray-900">
              {{ item.product?.name }}
            </div>
            <div class="text-[10px] text-gray-400 font-mono">
              SKU: {{ item.product?.sku }}
            </div>
          </td>
          <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-700 whitespace-nowrap">
            {{ formatRupiah(item.product?.unit_price) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-900 font-semibold whitespace-nowrap">
            {{ formatQuantity(item.quantity, false) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] font-mono text-right text-indigo-700 font-semibold whitespace-nowrap">
            {{ formatRupiah(item.total_amount) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
            <div>{{ item.sent_at || '-' }}</div>
            <div class="text-[10px] text-gray-400">
              {{ item.sent_by?.name || '-' }}
            </div>
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
            <div>{{ item.received_at || '-' }}</div>
            <div class="text-[10px] text-gray-400">
              {{ item.received_by?.name || '-' }}
            </div>
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-500 whitespace-nowrap">
            {{ formatTransitDuration(item.transit_duration_seconds) }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { formatTransitDuration, getTransferStatusLabel } from '../../utils/reportHelpers';
import { formatRupiah, formatQuantity, rowNumber } from '@/shared/utils/formatters.js';

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
