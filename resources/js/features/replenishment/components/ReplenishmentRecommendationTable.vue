<template>
  <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 uppercase tracking-wider">
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
            <th class="py-3 px-3 text-right font-mono text-indigo-600">
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
        <tbody class="divide-y divide-gray-200 text-sm">
          <tr
            v-if="loading"
            class="text-center"
          >
            <td
              colspan="8"
              class="py-12 text-gray-500"
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
                <span class="text-sm font-medium">Menghitung rekomendasi reorder live...</span>
              </div>
            </td>
          </tr>

          <tr
            v-else-if="items.length === 0"
            class="text-center"
          >
            <td
              colspan="8"
              class="py-12 text-gray-500"
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
                <span class="font-medium text-gray-700">Tidak ada produk kekurangan stok</span>
                <span class="text-xs text-gray-400">Semua produk berada di atas batas minimum stok atau filter tidak cocok.</span>
              </div>
            </td>
          </tr>

          <tr
            v-for="item in items"
            :key="item.product_id"
            class="hover:bg-gray-50/80 transition-colors align-top"
          >
            <!-- 1. Produk -->
            <td class="py-3 px-4">
              <div class="font-semibold text-gray-900">
                {{ item.product_name }}
              </div>
              <div class="flex flex-wrap items-center gap-1.5 mt-0.5 text-xs text-gray-500 font-mono">
                <span>SKU: {{ item.sku }}</span>
                <span v-if="item.barcode">• Barcode: {{ item.barcode }}</span>
              </div>
              <div class="mt-1 flex items-center gap-1 text-[11px] text-gray-400">
                <span>{{ item.category_name }}</span>
                <span>•</span>
                <span>{{ item.unit_name }}</span>
              </div>
            </td>

            <!-- 2. Stok Saat Ini -->
            <td class="py-3 px-3 text-right font-mono font-medium">
              <span :class="item.priority === 'CRITICAL' ? 'text-rose-600 font-bold' : 'text-gray-900'">
                {{ item.on_hand_quantity }}
              </span>
            </td>

            <!-- 3. Stok Minimum -->
            <td class="py-3 px-3 text-right font-mono text-gray-600">
              {{ item.minimum_stock }}
            </td>

            <!-- 4. Kekurangan Kotor (Gross Shortage) -->
            <td class="py-3 px-3 text-right font-mono font-medium text-amber-600">
              {{ item.gross_shortage_quantity }}
            </td>

            <!-- 5. Inbound SENT -->
            <td class="py-3 px-3 text-right font-mono text-blue-600">
              {{ item.pending_inbound_quantity }}
            </td>

            <!-- 6. Net Kebutuhan -->
            <td class="py-3 px-3 text-right font-mono font-bold text-indigo-600">
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
                class="p-2 rounded-lg bg-blue-50 text-blue-800 text-xs border border-blue-200 shadow-xs"
              >
                Sudah ditutup transfer in-transit ({{ item.pending_inbound_quantity }} {{ item.unit_name }}). Tidak perlu tindakan tambahan.
              </div>

              <!-- External Reorder Only -->
              <div
                v-else-if="item.recommendation_type === 'EXTERNAL_REORDER'"
                class="p-2.5 rounded-lg bg-rose-50 text-rose-800 text-xs border border-rose-200 space-y-1 shadow-xs"
              >
                <div class="font-semibold flex items-center gap-1">
                  <span>Perlu Reorder Eksternal</span>
                </div>
                <div class="font-mono text-rose-700 font-medium">
                  Qty: {{ item.external_reorder_quantity }} {{ item.unit_name }}
                </div>
                <div class="text-[11px] text-gray-500">
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
                  class="p-2 rounded-lg bg-amber-50 border border-amber-200 text-[11px] text-amber-800 font-mono shadow-xs"
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
