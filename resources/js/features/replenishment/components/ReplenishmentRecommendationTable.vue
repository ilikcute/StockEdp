<template>
  <div class="space-y-3">
    <!-- Batch Action Bar (if items selected) -->
    <div
      v-if="selectedKeys.length > 0"
      class="p-3.5 bg-indigo-50 border border-indigo-200 rounded-xl flex items-center justify-between shadow-xs transition-all animate-fadeIn"
    >
      <div class="flex items-center gap-2 text-xs text-indigo-900">
        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-indigo-600 text-white font-bold text-[10px]">
          {{ selectedKeys.length }}
        </span>
        <span class="font-semibold">Rekomendasi transfer persediaan dipilih</span>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="px-3 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 shadow-2xs transition-colors cursor-pointer"
          @click="clearSelection"
        >
          Batalkan Pilihan
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-indigo-600 rounded-md hover:bg-indigo-500 shadow-xs transition-colors cursor-pointer"
          @click="handleBatchReview"
        >
          <svg
            class="w-3.5 h-3.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            />
          </svg>
          Review & Siapkan Transfer Terpilih ({{ selectedKeys.length }})
        </button>
      </div>
    </div>

    <!-- Main Table Card -->
    <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 uppercase tracking-wider">
              <!-- Select All Checkbox -->
              <th
                scope="col"
                class="py-3 pl-4 pr-2 w-10 text-center"
              >
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  :disabled="actionableItems.length === 0"
                  class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer disabled:opacity-40"
                  @change="toggleSelectAll"
                >
              </th>
              <th class="py-3 px-3">
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
                colspan="9"
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
                colspan="9"
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
              :class="[
                'transition-colors align-top',
                isItemSelected(item) ? 'bg-indigo-50/30' : 'hover:bg-gray-50/80'
              ]"
            >
              <!-- Checkbox Item -->
              <td class="py-3 pl-4 pr-2 text-center">
                <input
                  v-if="hasActionableAllocation(item)"
                  type="checkbox"
                  :checked="isItemSelected(item)"
                  class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                  @change="toggleItemSelection(item)"
                >
                <span
                  v-else
                  class="text-gray-300"
                >-</span>
              </td>

              <!-- 1. Produk -->
              <td class="py-3 px-3">
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
                    @review-transfer="onSingleReview(item, $event)"
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import ReplenishmentStatusBadge from './ReplenishmentStatusBadge.vue';
import SourceAllocationList from './SourceAllocationList.vue';

const props = defineProps({
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

const emit = defineEmits(['review-transfer-items']);

const selectedKeys = ref([]);

const actionableItems = computed(() => {
  return props.items.filter((i) => hasActionableAllocation(i));
});

const hasActionableAllocation = (item) => {
  return (
    item.actionable !== false &&
    Array.isArray(item.source_allocations) &&
    item.source_allocations.length > 0
  );
};

const isItemSelected = (item) => {
  return selectedKeys.value.includes(item.product_id);
};

const isAllSelected = computed(() => {
  if (actionableItems.value.length === 0) return false;
  return actionableItems.value.every((i) => selectedKeys.value.includes(i.product_id));
});

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedKeys.value = [];
  } else {
    selectedKeys.value = actionableItems.value.map((i) => i.product_id);
  }
};

const toggleItemSelection = (item) => {
  const id = item.product_id;
  const idx = selectedKeys.value.indexOf(id);
  if (idx > -1) {
    selectedKeys.value.splice(idx, 1);
  } else {
    selectedKeys.value.push(id);
  }
};

const clearSelection = () => {
  selectedKeys.value = [];
};

const onSingleReview = (item, alloc) => {
  const payload = [
    {
      product_id: item.product_id,
      product_name: item.product_name,
      sku: item.sku,
      barcode: item.barcode,
      source_location_id: alloc.source_location_id,
      source_location_name: alloc.source_location_name,
      target_location_name: item.target_location?.name || 'Gudang Tujuan',
      target_net_need: item.net_replenishment_need,
      source_available_surplus: alloc.available_surplus_quantity,
      suggested_transfer_quantity: alloc.suggested_transfer_quantity,
    },
  ];

  emit('review-transfer-items', payload);
};

const handleBatchReview = () => {
  const selectedRows = props.items.filter((i) => selectedKeys.value.includes(i.product_id));
  const reviewPayload = [];

  selectedRows.forEach((item) => {
    if (Array.isArray(item.source_allocations)) {
      item.source_allocations.forEach((alloc) => {
        reviewPayload.push({
          product_id: item.product_id,
          product_name: item.product_name,
          sku: item.sku,
          barcode: item.barcode,
          source_location_id: alloc.source_location_id,
          source_location_name: alloc.source_location_name,
          target_location_name: item.target_location?.name || 'Gudang Tujuan',
          target_net_need: item.net_replenishment_need,
          source_available_surplus: alloc.available_surplus_quantity,
          suggested_transfer_quantity: alloc.suggested_transfer_quantity,
        });
      });
    }
  });

  emit('review-transfer-items', reviewPayload);
};
</script>
