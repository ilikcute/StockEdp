<template>
  <div class="space-y-2">
    <div
      v-for="alloc in allocations"
      :key="alloc.source_location_id"
      class="p-2.5 rounded-lg bg-gray-50 border border-gray-200 text-xs shadow-2xs"
    >
      <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
        <span class="font-semibold text-gray-900">
          {{ alloc.source_location_code }} — {{ alloc.source_location_name }}
        </span>
        <span class="font-mono font-bold text-emerald-600">
          Transfer: {{ alloc.suggested_transfer_quantity }}
        </span>
      </div>

      <div class="grid grid-cols-3 gap-2 text-gray-500 text-[11px] mb-2 font-mono">
        <div>Stok: {{ alloc.source_on_hand_quantity }}</div>
        <div>Min: {{ alloc.source_minimum_stock }}</div>
        <div>Surplus: {{ alloc.available_surplus_quantity }}</div>
      </div>

      <div class="flex items-center justify-end pt-1.5 border-t border-gray-200">
        <button
          v-if="canCreateTransfer && actionable"
          type="button"
          class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-md bg-emerald-600 text-white hover:bg-emerald-500 transition-colors shadow-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1 cursor-pointer"
          @click="$emit('review-transfer', alloc)"
        >
          Review & Siapkan Transfer
        </button>
        <span
          v-else-if="!actionable"
          class="text-[11px] text-amber-600 italic font-medium"
        >
          Lokasi target dibekukan
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@features/auth/stores/use_auth_store.js';

defineProps({
  allocations: {
    type: Array,
    default: () => [],
  },
  productId: {
    type: [Number, String],
    required: true,
  },
  targetLocationId: {
    type: [Number, String],
    required: true,
  },
  actionable: {
    type: Boolean,
    default: true,
  },
});

defineEmits(['review-transfer']);

const authStore = useAuthStore();
const canCreateTransfer = computed(() => authStore.hasPermission('stock_transfers.create'));
</script>
