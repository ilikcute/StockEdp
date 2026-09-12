<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 text-xs">
      <thead class="bg-gray-50">
        <tr>
          <th
            scope="col"
            class="py-2 pl-3 pr-2 text-center text-xs font-semibold text-gray-700 border-b border-gray-200 w-12"
          >
            No.
          </th>
          <th
            scope="col"
            class="px-2.5 py-2 text-left text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap"
          >
            No. Adjustment
          </th>
          <th
            scope="col"
            class="px-2.5 py-2 text-left text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap"
          >
            Tgl Dokumen
          </th>
          <th
            scope="col"
            class="px-2.5 py-2 text-left text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap"
          >
            Waktu Posting
          </th>
          <th
            scope="col"
            class="px-2.5 py-2 text-left text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap"
          >
            Lokasi
          </th>
          <th
            scope="col"
            class="px-2 py-2 text-center text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap"
          >
            Direction
          </th>
          <th
            scope="col"
            class="px-2.5 py-2 text-left text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap"
          >
            Alasan
          </th>
          <th
            scope="col"
            class="px-2.5 py-2 text-left text-xs font-semibold text-gray-700 border-b border-gray-200 min-w-[180px]"
          >
            Produk
          </th>
          <th
            scope="col"
            class="px-2.5 py-2 text-right text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap"
          >
            Harga Satuan
          </th>
          <th
            scope="col"
            class="px-2.5 py-2 text-right text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap"
          >
            Quantity
          </th>
          <th
            scope="col"
            class="px-2.5 py-2 text-right text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap"
          >
            Total Nilai (Rp)
          </th>
          <th
            scope="col"
            class="px-2 py-2 text-left text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap"
          >
            Diposting Oleh
          </th>
          <th
            scope="col"
            class="px-2 py-2 text-left text-xs font-semibold text-gray-700 border-b border-gray-200 min-w-[120px]"
          >
            Catatan
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 bg-white">
        <tr
          v-for="(item, index) in items"
          :key="item.item_id"
          class="hover:bg-slate-50 transition-colors"
        >
          <td class="whitespace-nowrap py-1.5 pl-3 pr-2 text-xs font-medium text-center text-gray-400">
            {{ rowNumber(pagination, index) }}
          </td>
          <td class="whitespace-nowrap px-2.5 py-1.5 text-xs font-medium text-indigo-600 font-mono">
            {{ item.adjustment_number }}
          </td>
          <td class="whitespace-nowrap px-2.5 py-1.5 text-xs text-gray-600">
            {{ item.document_date }}
          </td>
          <td class="whitespace-nowrap px-2.5 py-1.5 text-xs text-gray-500 font-mono">
            {{ formatTimestamp(item.posted_at) }}
          </td>
          <td class="whitespace-nowrap px-2.5 py-1.5 text-xs text-gray-700">
            {{ item.location?.name || '-' }}
          </td>
          <td class="whitespace-nowrap px-2 py-1.5 text-center text-xs">
            <span
              class="inline-flex items-center rounded px-1.5 py-0.5 text-[11px] font-medium"
              :class="item.direction === 'INCREASE' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20'"
            >
              {{ getDirectionLabel(item.direction) }}
            </span>
          </td>
          <td class="whitespace-nowrap px-2.5 py-1.5 text-xs text-gray-700">
            <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-[11px] text-gray-700">
              {{ getReasonCodeLabel(item.reason_code) }}
            </span>
          </td>
          <td class="px-2.5 py-1.5 text-xs text-gray-900 max-w-[220px]">
            <div
              class="font-medium text-gray-900 truncate"
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
                class="text-gray-400 truncate"
              >{{ item.product?.category_name }}</span>
            </div>
          </td>
          <td class="whitespace-nowrap px-2.5 py-1.5 text-xs font-mono text-right text-gray-600">
            {{ formatRupiah(item.product?.unit_price) }}
          </td>
          <td class="whitespace-nowrap px-2.5 py-1.5 text-xs font-mono text-right font-medium text-gray-900">
            <span>{{ formatQuantity(item.quantity) }}</span>
            <span
              v-if="item.product?.unit_name"
              class="text-[10px] text-gray-400 font-normal ml-1"
            >
              {{ item.product.unit_name }}
            </span>
          </td>
          <td
            class="whitespace-nowrap px-2.5 py-1.5 text-xs font-mono text-right font-semibold"
            :class="item.direction === 'INCREASE' ? 'text-emerald-700' : 'text-rose-700'"
          >
            {{ item.direction === 'DECREASE' ? '-' : '+' }}{{ formatRupiah(item.total_amount) }}
          </td>
          <td
            class="whitespace-nowrap px-2 py-1.5 text-xs text-gray-500 max-w-[110px] truncate"
            :title="item.posted_by?.name || '-'"
          >
            {{ item.posted_by?.name || '-' }}
          </td>
          <td
            class="px-2 py-1.5 text-xs text-gray-500 max-w-[140px] truncate"
            :title="item.notes || '-'"
          >
            {{ item.notes || '-' }}
          </td>
        </tr>
      </tbody>
      <tfoot
        v-if="items.length > 0"
        class="bg-gray-50 font-semibold border-t-2 border-gray-200 text-xs text-gray-800"
      >
        <tr>
          <td
            colspan="9"
            class="py-2 px-3 text-right text-gray-600 uppercase tracking-wider text-[11px]"
          >
            Subtotal Halaman Ini:
          </td>
          <td class="py-2 px-2.5 text-right font-mono text-gray-900">
            {{ formatQuantity(pageTotalQuantity) }}
          </td>
          <td class="py-2 px-2.5 text-right font-mono text-indigo-700">
            {{ formatRupiah(pageTotalAmount) }}
          </td>
          <td
            colspan="2"
            class="py-2 px-2"
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
