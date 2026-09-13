<template>
  <div class="mt-4">
    <h3 class="text-sm font-semibold text-gray-900 mb-2">
      {{ heading }}
    </h3>
    <div class="overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar">
      <table class="w-full text-left text-xs border-collapse">
        <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10">
          <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
            <th
              scope="col"
              class="py-1.5 px-1.5 w-8 text-center"
            >
              No.
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
              Lokasi Gudang
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
              Kuantitas
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap"
            >
              Subtotal
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr
            v-for="(item, index) in items"
            :key="item.id"
            class="hover:bg-gray-50/80 transition-colors"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ index + 1 }}
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap text-[11px]">
              <div class="font-medium text-gray-900 leading-tight">
                {{ item.product?.name }}
              </div>
              <div class="text-gray-400 font-mono text-[10px]">
                {{ item.product?.sku }}
              </div>
            </td>
            <td class="py-1.5 px-2 text-[11px] text-gray-500 whitespace-nowrap">
              {{ item.location?.name }}
            </td>
            <td class="py-1.5 px-2 text-[11px] text-gray-900 text-right font-mono whitespace-nowrap">
              {{ formatRupiah(item.unit_price ?? item.product?.unit_price) }}
            </td>
            <td class="py-1.5 px-2 text-[11px] text-gray-900 text-right font-mono font-medium whitespace-nowrap">
              {{ formatQuantity(item.quantity) }} {{ item.product?.unit?.symbol || item.product?.unit?.name }}
            </td>
            <td class="py-1.5 px-2 text-[11px] text-gray-900 text-right font-mono font-medium whitespace-nowrap">
              {{ formatRupiah(item.subtotal ?? (Number(item.quantity) * Number(item.unit_price ?? item.product?.unit_price ?? 0))) }}
            </td>
          </tr>
        </tbody>
        <tfoot
          v-if="items?.length > 0"
          class="border-t border-gray-200 bg-gray-50/90 text-xs font-medium"
        >
          <tr class="text-[11px]">
            <td
              colspan="4"
              class="py-1.5 px-2 text-right text-gray-700 font-semibold"
            >
              {{ grandTotalLabel }}
            </td>
            <td class="py-1.5 px-2 text-right font-mono font-bold text-gray-900 whitespace-nowrap">
              {{ formatQuantity(totalQuantity) }}
            </td>
            <td class="py-1.5 px-2 text-right font-mono font-bold text-indigo-700 whitespace-nowrap">
              {{ formatRupiah(totalAmount) }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>

<script setup>
import { formatRupiah, formatQuantity } from '@/shared/utils/formatters.js';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    heading: {
        type: String,
        required: true,
    },
    grandTotalLabel: {
        type: String,
        required: true,
    },
    totalQuantity: {
        type: Number,
        default: 0,
    },
    totalAmount: {
        type: Number,
        default: 0,
    },
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