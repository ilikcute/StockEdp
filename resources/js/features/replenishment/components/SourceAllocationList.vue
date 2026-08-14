<template>
  <div class="space-y-2">
    <div
      v-for="alloc in allocations"
      :key="alloc.source_location_id"
      class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 text-xs"
    >
      <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
        <span class="font-semibold text-slate-800 dark:text-slate-200">
          {{ alloc.source_location_code }} — {{ alloc.source_location_name }}
        </span>
        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
          Transfer: {{ alloc.suggested_transfer_quantity }}
        </span>
      </div>

      <div class="grid grid-cols-3 gap-2 text-slate-500 dark:text-slate-400 text-[11px] mb-2 font-mono">
        <div>Stok: {{ alloc.source_on_hand_quantity }}</div>
        <div>Min: {{ alloc.source_minimum_stock }}</div>
        <div>Surplus: {{ alloc.available_surplus_quantity }}</div>
      </div>

      <div class="flex items-center justify-end pt-1 border-t border-slate-200/60 dark:border-slate-700/40">
        <router-link
          v-if="canCreateTransfer && actionable"
          :to="{
            path: '/inventory/transfers/create',
            query: {
              origin_location_id: alloc.source_location_id,
              destination_location_id: targetLocationId,
              product_id: productId,
              quantity: alloc.suggested_transfer_quantity,
              source: 'replenishment',
            },
          }"
          class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-1"
        >
          Siapkan Transfer
        </router-link>
        <span
          v-else-if="!actionable"
          class="text-[11px] text-amber-600 dark:text-amber-400 italic"
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

const authStore = useAuthStore();
const canCreateTransfer = computed(() => authStore.hasPermission('stock_transfers.create'));
</script>
