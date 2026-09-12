<template>
  <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-3.5 flex flex-col justify-between h-full">
    <div>
      <div class="flex items-center justify-between mb-2">
        <div>
          <h3 class="text-xs font-bold text-gray-900 flex items-center gap-1.5">
            <svg
              class="w-4 h-4 text-amber-500"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
              />
            </svg>
            Top 10 Produk Paling Banyak Dikeluarkan
          </h3>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Total barang keluar dalam periode terpilih.
          </p>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-if="!products || products.length === 0"
        class="text-center py-4 text-gray-400 text-xs"
      >
        Tidak ada data pengeluaran barang pada periode ini.
      </div>

      <!-- Table -->
      <div
        v-else
        class="overflow-x-auto max-h-[250px] overflow-y-auto custom-scrollbar"
      >
        <table class="w-full text-left text-xs border-collapse">
          <thead class="sticky top-0 bg-gray-50 z-10">
            <tr class="text-gray-600 font-semibold border-b border-gray-200">
              <th class="py-1 px-1.5 w-8 text-center">
                No.
              </th>
              <th class="py-1 px-2">
                SKU & Produk
              </th>
              <th class="py-1 px-2 text-right">
                Harga
              </th>
              <th class="py-1 px-2 text-right">
                Total Keluar
              </th>
              <th class="py-1 px-2 text-right">
                Total Gross
              </th>
              <th class="py-1 px-1.5 text-center">
                Trx
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="(item, index) in products"
              :key="item.product_id || index"
              class="hover:bg-gray-50/80 transition-colors"
            >
              <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px]">
                {{ index + 1 }}
              </td>
              <td class="py-1.5 px-2">
                <div class="font-medium text-gray-900 leading-tight">
                  {{ item.name }}
                </div>
                <div class="text-[10px] text-gray-400 font-mono">
                  {{ item.sku }}
                </div>
              </td>
              <td class="py-1.5 px-2 text-right font-mono text-gray-600 whitespace-nowrap text-[11px]">
                {{ formatRupiah(item.unit_price) }}
              </td>
              <td class="py-1.5 px-2 text-right font-mono font-bold text-amber-600 whitespace-nowrap">
                {{ formatQuantity(item.total_quantity, true) }} {{ item.unit_symbol }}
              </td>
              <td class="py-1.5 px-2 text-right font-mono font-bold text-gray-900 whitespace-nowrap text-[11px]">
                {{ formatRupiah(calculateGross(item)) }}
              </td>
              <td class="py-1.5 px-1.5 text-center text-gray-500 font-mono text-[10px]">
                {{ item.movement_count }}x
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { formatQuantity, formatRupiah } from '@/shared/utils/formatters.js';

defineProps({
  products: {
    type: Array,
    default: () => [],
  },
});

const calculateGross = (item) => {
  if (item.total_gross !== undefined && item.total_gross !== null) {
    return item.total_gross;
  }
  const qty = parseFloat(item.total_quantity) || 0;
  const price = parseFloat(item.unit_price) || 0;
  return qty * price;
};
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #e2e8f0;
  border-radius: 4px;
}
</style>
