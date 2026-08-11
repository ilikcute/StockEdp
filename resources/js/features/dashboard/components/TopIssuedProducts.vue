<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
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
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
          Ringkasan total kuantitas barang keluar dalam periode terpilih.
        </p>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-if="!products || products.length === 0"
      class="text-center py-6 text-gray-400 dark:text-gray-500 text-xs"
    >
      Tidak ada data pengeluaran barang pada periode ini.
    </div>

    <!-- Table -->
    <div
      v-else
      class="overflow-x-auto"
    >
      <table class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 font-semibold border-b border-gray-200 dark:border-gray-700">
            <th class="py-2 px-2.5 w-10 text-center">
              No.
            </th>
            <th class="py-2 px-2.5">
              SKU & Nama Produk
            </th>
            <th class="py-2 px-2.5 text-right">
              Total Keluar
            </th>
            <th class="py-2 px-2.5 text-center">
              Trx
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          <tr
            v-for="(item, index) in products"
            :key="item.product_id || index"
            class="hover:bg-gray-50/80 dark:hover:bg-gray-700/40 transition-colors"
          >
            <td class="py-2 px-2.5 text-center text-gray-400 font-mono">
              {{ index + 1 }}
            </td>
            <td class="py-2 px-2.5">
              <div class="font-semibold text-gray-900 dark:text-white">
                {{ item.name }}
              </div>
              <div class="text-[10px] text-gray-400 font-mono">
                {{ item.sku }}
              </div>
            </td>
            <td class="py-2 px-2.5 text-right font-mono font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap">
              {{ formatQuantity(item.total_quantity) }} {{ item.unit_symbol }}
            </td>
            <td class="py-2 px-2.5 text-center text-gray-500 dark:text-gray-400 font-mono text-[11px]">
              {{ item.movement_count }}x
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
  products: {
    type: Array,
    default: () => [],
  },
});

const formatQuantity = (qty) => {
  if (qty === null || qty === undefined) return '0.0000';
  const num = parseFloat(qty);
  if (isNaN(num)) return String(qty);
  return num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 4 });
};
</script>
