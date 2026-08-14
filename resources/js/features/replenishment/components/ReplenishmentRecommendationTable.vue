<template>
  <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-[11px] font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
            <th class="py-3 px-4">
              Produk
            </th>
            <th class="py-3 px-3 text-right font-mono">
              Stok Saat Ini
            </th>
            <th class="py-3 px-3 text-right font-mono">
              Stok Min
            </th>
            <th class="py-3 px-3 text-right font-mono">
              Kekurangan
            </th>
            <th class="py-3 px-3 text-right font-mono">
              Inbound SENT
            </th>
            <th class="py-3 px-3 text-right font-mono text-indigo-600 dark:text-indigo-400">
              Net Kebutuhan
            </th>
            <th class="py-3 px-4 text-center">
              Rekomendasi
            </th>
            <th class="py-3 px-4">
              Alokasi Sumber & Tindakan
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60 text-sm">
          <tr
            v-if="loading"
            class="text-center"
          >
            <td
              colspan="8"
              class="py-12 text-slate-500 dark:text-slate-400"
            >
              <div class="inline-flex items-center gap-2">
                <svg
                  class="animate-spin h-5 w-5 text-indigo-600"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  />
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8v8H4z"
                  />
                </svg>
                <span>Menghitung rekomendasi reorder live...</span>
              </div>
            </td>
          </tr>

          <tr
            v-else-if="items.length === 0"
            class="text-center"
          >
            <td
              colspan="8"
              class="py-12 text-slate-500 dark:text-slate-400"
            >
              <div class="flex flex-col items-center justify-center space-y-1">
                <svg
                  class="w-8 h-8 text-emerald-500 mb-1"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                <span class="font-medium text-slate-700 dark:text-slate-300">Tidak ada produk kekurangan stok</span>
                <span class="text-xs text-slate-400">Semua produk berada di atas batas minimum stok atau filter tidak cocok.</span>
              </div>
            </td>
          </tr>

          <tr
            v-for="item in items"
            :key="item.product_id"
            class="hover:bg-slate-50/70 dark:hover:bg-slate-750 transition-colors align-top"
          >
            <!-- 1. Produk -->
            <td class="py-3 px-4">
              <div class="font-semibold text-slate-900 dark:text-slate-100">
                {{ item.product_name }}
              </div>
              <div class="flex flex-wrap items-center gap-1.5 mt-0.5 text-xs text-slate-500 dark:text-slate-400 font-mono">
                <span>SKU: {{ item.sku }}</span>
                <span v-if="item.barcode">• Barcode: {{ item.barcode }}</span>
              </div>
              <div class="mt-1 flex items-center gap-1 text-[11px] text-slate-400 dark:text-slate-500">
                <span>{{ item.category_name }}</span>
                <span>•</span>
                <span>{{ item.unit_name }}</span>
              </div>
            </td>

            <!-- 2. Stok Saat Ini -->
            <td class="py-3 px-3 text-right font-mono font-medium">
              <span :class="item.priority === 'CRITICAL' ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-800 dark:text-slate-200'">
                {{ item.on_hand_quantity }}
              </span>
            </td>

            <!-- 3. Stok Minimum -->
            <td class="py-3 px-3 text-right font-mono text-slate-600 dark:text-slate-400">
              {{ item.minimum_stock }}
            </td>

            <!-- 4. Kekurangan Kotor (Gross Shortage) -->
            <td class="py-3 px-3 text-right font-mono font-medium text-amber-600 dark:text-amber-400">
              {{ item.gross_shortage_quantity }}
            </td>

            <!-- 5. Inbound SENT -->
            <td class="py-3 px-3 text-right font-mono text-blue-600 dark:text-blue-400">
              {{ item.pending_inbound_quantity }}
            </td>

            <!-- 6. Net Kebutuhan -->
            <td class="py-3 px-3 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400">
              {{ item.net_replenishment_need }}
            </td>

            <!-- 7. Rekomendasi & Prioritas -->
            <td class="py-3 px-4 text-center">
              <div class="flex flex-col items-center gap-1">
                <ReplenishmentStatusBadge :type="item.recommendation_type" />
                <ReplenishmentStatusBadge :priority="item.priority" />
              </div>
            </td>

            <!-- 8. Alokasi Sumber & Tindakan -->
            <td class="py-3 px-4 max-w-xs sm:max-w-sm">
              <!-- Inbound Covered -->
              <div
                v-if="item.recommendation_type === 'INBOUND_COVERED'"
                class="p-2 rounded-lg bg-blue-50/70 dark:bg-blue-950/30 text-blue-800 dark:text-blue-300 text-xs border border-blue-200 dark:border-blue-800"
              >
                Sudah ditutup transfer in-transit ({{ item.pending_inbound_quantity }} {{ item.unit_name }}). Tidak perlu tindakan tambahan.
              </div>

              <!-- External Reorder Only -->
              <div
                v-else-if="item.recommendation_type === 'EXTERNAL_REORDER'"
                class="p-2.5 rounded-lg bg-rose-50/70 dark:bg-rose-950/30 text-rose-800 dark:text-rose-300 text-xs border border-rose-200 dark:border-rose-800 space-y-1"
              >
                <div class="font-semibold flex items-center gap-1">
                  <span>Perlu Reorder Eksternal</span>
                </div>
                <div class="font-mono text-rose-700 dark:text-rose-400">
                  Qty: {{ item.external_reorder_quantity }} {{ item.unit_name }}
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400">
                  Tidak ada surplus aman di gudang lain.
                </div>
              </div>

              <!-- Internal Transfer or Mixed -->
              <div
                v-else
                class="space-y-2"
              >
                <SourceAllocationList
                  :allocations="item.source_allocations"
                  :product-id="item.product_id"
                  :target-location-id="item.target_location?.id || targetLocationId"
                  :actionable="item.actionable"
                />

                <div
                  v-if="item.recommendation_type === 'MIXED'"
                  class="p-2 rounded bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-[11px] text-amber-800 dark:text-amber-300 font-mono"
                >
                  Sisa Reorder Eksternal: {{ item.external_reorder_quantity }} {{ item.unit_name }}
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import ReplenishmentStatusBadge from './ReplenishmentStatusBadge.vue';
import SourceAllocationList from './SourceAllocationList.vue';

defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  targetLocationId: {
    type: [Number, String],
    default: '',
  },
  loading: {
    type: Boolean,
    default: false,
  },
});
</script>
