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
            No. Adjustment
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
            class="py-1.5 px-2 text-center whitespace-nowrap"
          >
            Direction
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Alasan
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
            Total Nilai (Rp)
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Diposting Oleh
          </th>
          <th
            scope="col"
            class="py-1.5 px-2 whitespace-nowrap"
          >
            Catatan
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
            {{ item.adjustment_number }}
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
            {{ item.document_date }}
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-500 font-mono whitespace-nowrap">
            {{ formatTimestamp(item.posted_at) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] text-gray-700 whitespace-nowrap">
            {{ item.location?.name || '-' }}
          </td>
          <td class="py-1.5 px-2 text-center whitespace-nowrap">
            <span
              class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider inline-flex items-center"
              :class="item.direction === 'INCREASE' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20'"
            >
              {{ getDirectionLabel(item.direction) }}
            </span>
          </td>
          <td class="py-1.5 px-2 whitespace-nowrap">
            <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold uppercase tracking-wider inline-flex items-center bg-gray-100 text-gray-700">
              {{ getReasonCodeLabel(item.reason_code) }}
            </span>
          </td>
          <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
            <div
              class="font-medium text-gray-900 truncate max-w-[220px]"
              :title="item.product?.name"
            >
              {{ item.product?.name }}
            </div>
            <div class="text-[10px] text-gray-400 font-mono flex items-center gap-1">
              <span>{{ item.product?.sku }}</span>
              <span
                v-if="item.product?.category_name"
                class="text-gray-300"
              >•</span>
              <span
                v-if="item.product?.category_name"
                class="text-gray-400 truncate max-w-[120px]"
              >{{ item.product?.category_name }}</span>
            </div>
          </td>
          <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-600 whitespace-nowrap">
            {{ formatRupiah(item.product?.unit_price) }}
          </td>
          <td class="py-1.5 px-2 text-[11px] font-mono text-right font-semibold text-gray-900 whitespace-nowrap">
            <span>{{ formatQuantity(item.quantity) }}</span>
            <span
              v-if="item.product?.unit_name"
              class="text-[10px] text-gray-400 font-normal ml-1"
            >
              {{ item.product.unit_name }}
            </span>
          </td>
          <td
            class="py-1.5 px-2 text-[11px] font-mono text-right font-semibold whitespace-nowrap"
            :class="item.direction === 'INCREASE' ? 'text-emerald-700' : 'text-rose-700'"
          >
            {{ item.direction === 'DECREASE' ? '-' : '+' }}{{ formatRupiah(item.total_amount) }}
          </td>
          <td
            class="py-1.5 px-2 text-[11px] text-gray-500 max-w-[110px] truncate whitespace-nowrap"
            :title="item.posted_by?.name || '-'"
          >
            {{ item.posted_by?.name || '-' }}
          </td>
          <td
            class="py-1.5 px-2 text-[11px] text-gray-500 max-w-[140px] truncate"
            :title="item.notes || '-'"
          >
            {{ item.notes || '-' }}
          </td>
        </tr>
      </tbody>
      <tfoot
        v-if="items.length > 0"
        class="border-t border-gray-200 bg-gray-50/90 text-[11px] font-medium"
      >
        <tr class="text-[11px]">
          <td
            colspan="9"
            class="py-1.5 px-2 text-right text-gray-600 uppercase tracking-wider text-[10px]"
          >
            Subtotal Halaman Ini:
          </td>
          <td class="py-1.5 px-2 text-right font-mono text-gray-900 font-semibold">
            {{ formatQuantity(pageTotalQuantity) }}
          </td>
          <td class="py-1.5 px-2 text-right font-mono text-indigo-700 font-semibold">
            {{ formatRupiah(pageTotalAmount) }}
          </td>
          <td
            colspan="2"
            class="py-1.5 px-2"
          />
        </tr>
      </tfoot>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { getDirectionLabel, getReasonCodeLabel } from '../../utils/reportHelpers';
import { formatRupiah, formatQuantity, rowNumber, formatTimestamp } from '@/shared/utils/formatters.js';

const props = defineProps({
  items: { type: Array, required: true },
  pagination: { type: Object, default: null },
});

const pageTotalQuantity = computed(() => {
  return props.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
});

const pageTotalAmount = computed(() => {
  return props.items.reduce((sum, item) => sum + (parseFloat(item.total_amount) || 0), 0);
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
