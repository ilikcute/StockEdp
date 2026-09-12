<template>
  <div class="mt-8">
    <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4">
      {{ heading }}
    </h3>
    <div class="overflow-x-auto touch-scroll shadow-sm border border-gray-300 md:rounded-lg">
      <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
          <tr>
            <th
              scope="col"
              class="py-3.5 pl-4 pr-3 text-center text-sm font-semibold text-gray-900 sm:pl-6 border-b border-gray-300 w-16"
            >
              No.
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Produk
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Lokasi Gudang
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Harga Satuan
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Kuantitas
            </th>
            <th
              scope="col"
              class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 border-b border-gray-300"
            >
              Subtotal
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr
            v-for="(item, index) in items"
            :key="item.id"
          >
            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-center text-gray-500 sm:pl-6">
              {{ index + 1 }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm">
              <div class="font-medium text-gray-900">
                {{ item.product?.name }}
              </div>
              <div class="text-gray-500 font-mono text-xs">
                {{ item.product?.sku }}
              </div>
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
              {{ item.location?.name }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right font-mono">
              {{ formatRupiah(item.unit_price ?? item.product?.unit_price) }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right font-mono font-medium">
              {{ formatQuantity(item.quantity) }} {{ item.product?.unit?.symbol || item.product?.unit?.name }}
            </td>
            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right font-mono font-medium">
              {{ formatRupiah(item.subtotal ?? (Number(item.quantity) * Number(item.unit_price ?? item.product?.unit_price ?? 0))) }}
            </td>
          </tr>
        </tbody>
        <tfoot
          v-if="items?.length > 0"
          class="border-t-2 border-gray-200 bg-gray-50 text-sm font-medium"
        >
          <tr>
            <td
              colspan="4"
              class="py-3 px-3 text-right text-gray-700 font-semibold"
            >
              {{ grandTotalLabel }}
            </td>
            <td class="py-3 px-3 text-right font-mono font-bold text-gray-900">
              {{ formatQuantity(totalQuantity) }}
            </td>
            <td class="py-3 px-3 text-right font-mono font-bold text-indigo-700">
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