<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-300">
      <thead class="bg-gray-50">
        <tr>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            No. Transfer
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
            Status
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            Asal → Tujuan
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
            Pengiriman
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            Penerimaan
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            Durasi Transit
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
            {{ item.transfer_number }}
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
            {{ item.document_date }}
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm">
            <span
              class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
              :class="item.status === 'RECEIVED' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'"
            >
              {{ getTransferStatusLabel(item.status) }}
            </span>
            <span
              v-if="item.is_in_transit"
              class="ml-1 inline-flex rounded bg-blue-100 px-1.5 py-0.5 text-xs font-medium text-blue-800"
            >
              In-Transit
            </span>
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
            <div>{{ item.origin_location?.name }}</div>
            <div class="text-xs text-gray-500">
              → {{ item.destination_location?.name }}
            </div>
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
            <div>{{ item.sent_at || '-' }}</div>
            <div class="text-xs">
              {{ item.sent_by?.name || '-' }}
            </div>
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
            <div>{{ item.received_at || '-' }}</div>
            <div class="text-xs">
              {{ item.received_by?.name || '-' }}
            </div>
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
            {{ formatTransitDuration(item.transit_duration_seconds) }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { formatTransitDuration, getTransferStatusLabel } from '../../utils/reportHelpers';

defineProps({
  items: { type: Array, required: true },
});
</script>
