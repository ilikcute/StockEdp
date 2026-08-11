<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-300">
      <thead class="bg-gray-50">
        <tr>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            No. Adjustment
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            Tgl Dokumen
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            Waktu Posting
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            Lokasi
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            Direction
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            Alasan
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            Produk
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-right text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            Quantity
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            Posted By
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            Notes
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white">
        <tr
          v-for="item in items"
          :key="item.item_id"
          class="hover:bg-gray-50"
        >
          <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
            {{ item.adjustment_number }}
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
            {{ item.document_date }}
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
            {{ item.posted_at }}
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
            {{ item.location?.name || '-' }}
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm">
            <span
              class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
              :class="item.direction === 'INCREASE' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
            >
              {{ getDirectionLabel(item.direction) }}
            </span>
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">
            <span class="inline-flex rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800">
              {{ getReasonCodeLabel(item.reason_code) }}
            </span>
          </td>
          <td class="px-3 py-4 text-sm text-gray-900">
            <div class="font-medium">
              {{ item.product?.name }}
            </div>
            <div class="text-xs text-gray-500 font-mono">
              SKU: {{ item.product?.sku }}
            </div>
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm font-mono text-right text-gray-900">
            {{ item.quantity }}
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
            {{ item.posted_by?.name || '-' }}
          </td>
          <td class="px-3 py-4 text-sm text-gray-500 max-w-xs truncate">
            {{ item.notes || '-' }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { getDirectionLabel, getReasonCodeLabel } from '../../utils/reportHelpers';

defineProps({
  items: { type: Array, required: true },
});
</script>
