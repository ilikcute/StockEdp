<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-300">
      <thead class="bg-gray-50">
        <tr>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900"
          >
            No. Opname
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900"
          >
            Tgl Dokumen
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900"
          >
            Waktu Posting
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900"
          >
            Lokasi
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900"
          >
            Produk
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-right text-xs font-semibold text-gray-900"
          >
            Snapshot
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-right text-xs font-semibold text-gray-900"
          >
            Fisik (Counted)
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-right text-xs font-semibold text-gray-900"
          >
            Signed Variance
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900"
          >
            Movement / Status
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900"
          >
            Hitung / Post By
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
            {{ item.opname_number }}
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
          <td class="px-3 py-4 text-sm text-gray-900">
            <div class="font-medium flex items-center gap-1.5">
              <span>{{ item.product?.name }}</span>
              <span
                v-if="item.is_unexpected"
                class="inline-flex rounded bg-amber-100 px-1.5 py-0.5 text-xs font-semibold text-amber-800"
              >
                Unexpected
              </span>
            </div>
            <div class="text-xs text-gray-500 font-mono">
              SKU: {{ item.product?.sku }}
            </div>
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm font-mono text-right text-gray-600">
            {{ item.snapshot_quantity }}
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm font-mono text-right text-gray-900 font-medium">
            {{ item.counted_quantity }}
          </td>
          <td
            class="whitespace-nowrap px-3 py-4 text-sm font-mono text-right font-semibold"
            :class="item.signed_variance.startsWith('-') ? 'text-red-700' : (item.movement_direction === 'NONE' ? 'text-gray-600' : 'text-green-700')"
          >
            {{ item.signed_variance }}
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm">
            <span
              class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
              :class="{
                'bg-green-100 text-green-800': item.movement_direction === 'OPNAME_IN',
                'bg-red-100 text-red-800': item.movement_direction === 'OPNAME_OUT',
                'bg-gray-100 text-gray-700': item.movement_direction === 'NONE'
              }"
            >
              {{ getMovementDirectionLabel(item.movement_direction) }}
            </span>
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
            <div>{{ item.last_counted_by?.name || '-' }}</div>
            <div class="text-xs text-gray-400">
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

defineProps({
  items: { type: Array, required: true },
});
</script>
