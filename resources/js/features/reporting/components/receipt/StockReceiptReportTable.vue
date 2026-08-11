<template>
  <div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-300">
      <thead class="bg-gray-50">
        <tr>
          <th
            scope="col"
            class="py-3.5 pl-4 pr-3 text-center text-xs font-semibold text-gray-900 sm:pl-6 border-b border-gray-300 w-16"
          >
            No.
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            No. Penerimaan
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
            Supplier
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
            Produk
          </th>
          <th
            scope="col"
            class="px-3 py-3.5 text-left text-xs font-semibold text-gray-900 border-b border-gray-300"
          >
            Kategori / Unit
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
          v-for="(item, index) in items"
          :key="item.item_id"
          class="hover:bg-gray-50"
        >
          <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-center text-gray-500 sm:pl-6">
            {{ (pagination?.from ? pagination.from + index : index + 1) }}
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
            {{ item.receipt_number }}
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
            {{ item.document_date }}
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
            {{ item.posted_at }}
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
            {{ item.supplier?.name || '-' }}
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
            {{ item.location?.name || '-' }}
          </td>
          <td class="px-3 py-4 text-sm text-gray-900">
            <div class="font-medium">
              {{ item.product?.name }}
            </div>
            <div class="text-xs text-gray-500 font-mono">
              SKU: {{ item.product?.sku }}
            </div>
          </td>
          <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
            <div>{{ item.product?.category_name }}</div>
            <div class="text-xs text-gray-400">
              {{ item.product?.unit_name }}
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
defineProps({
  items: { type: Array, required: true },
  pagination: { type: Object, default: null },
});
</script>
