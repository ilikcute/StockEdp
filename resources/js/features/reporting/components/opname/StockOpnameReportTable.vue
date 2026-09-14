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
            No. Opname
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
            class="py-1.5 px-2 text-right whitespace-nowrap"
          >
            Harga Satuan
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 text-right whitespace-nowrap"
          >
            Snapshot
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 text-right whitespace-nowrap"
          >
            Fisik (Counted)
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 text-right whitespace-nowrap"
          >
            Signed Variance
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 text-right whitespace-nowrap"
          >
            Nilai Selisih
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Movement / Status
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Hitung / Post By
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
            {{ item.opname_number }}
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
            {{ item.document_date }}
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-500 font-mono whitespace-nowrap">
            {{ formatTimestamp(item.posted_at) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
            {{ item.location?.name || '-' }}
          </td>
          <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
            <div class="font-medium text-gray-900 flex items-center gap-1.5">
              <span>{{ item.product?.name }}</span>
              <span
                v-if="item.is_unexpected"
                class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider inline-flex items-center bg-amber-50 text-amber-700 ring-1 ring-amber-600/20"
              >
                Unexpected
              </span>
            </div>
            <div class="text-[10px] text-gray-400 font-mono">
              SKU: {{ item.product?.sku }}
            </div>
          </td>
          <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-700 whitespace-nowrap">
            {{ formatRupiah(item.product?.unit_price) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-600 whitespace-nowrap">
            {{ formatQuantity(item.snapshot_quantity, false) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] font-mono text-right font-semibold text-gray-900 whitespace-nowrap">
            {{ formatQuantity(item.counted_quantity, false) }}
          </td>
          <td
            class="py-1.5 px-2 text-[11px] font-mono text-right font-semibold whitespace-nowrap"
            :class="item.signed_variance?.startsWith('-') ? 'text-rose-700' : (item.movement_direction === 'NONE' ? 'text-gray-600' : 'text-emerald-700')"
          >
            {{ (item.signed_variance && !item.signed_variance.startsWith('-') && item.signed_variance !== '0.0000' && item.signed_variance !== '0' ? '+' : '') + formatQuantity(item.signed_variance, false) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] font-mono text-right font-semibold text-gray-900 whitespace-nowrap">
            {{ formatRupiah(item.variance_amount) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
            <span
              class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider inline-flex items-center"
              :class="{
                'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20': item.movement_direction === 'OPNAME_IN',
                'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20': item.movement_direction === 'OPNAME_OUT',
                'bg-gray-100 text-gray-700': item.movement_direction === 'NONE'
              }"
            >
              {{ getMovementDirectionLabel(item.movement_direction) }}
            </span>
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
            <div>{{ item.last_counted_by?.name || '-' }}</div>
            <div class="text-[10px] text-gray-400">
              Post: {{ item.posted_by?.name || '-' }}
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { getMovementDirectionLabel } from '../../utils/reportHelpers';
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
