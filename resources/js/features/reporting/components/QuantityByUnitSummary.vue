<template>
  <div
    v-if="summary"
    class="bg-white rounded-xl border border-gray-200 shadow-2xs p-3 space-y-2.5"
  >
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
      <div
        v-if="summary.total_rows !== undefined"
        class="bg-slate-50/70 rounded-lg p-2 border border-slate-200/80"
      >
        <span class="block text-[11px] text-gray-500 font-medium">Total Item</span>
        <span class="text-sm sm:text-base font-bold text-gray-900 font-mono">{{ summary.total_rows }}</span>
      </div>
      <div
        v-if="summary.total_documents !== undefined"
        class="bg-slate-50/70 rounded-lg p-2 border border-slate-200/80"
      >
        <span class="block text-[11px] text-gray-500 font-medium">Total Dokumen</span>
        <span class="text-sm sm:text-base font-bold text-gray-900 font-mono">{{ summary.total_documents }}</span>
      </div>
      <slot name="extra" />
    </div>

    <div
      v-if="summary.quantity_by_unit && summary.quantity_by_unit.length > 0"
      class="pt-2 border-t border-gray-100"
    >
      <span class="block text-[11px] font-medium text-gray-500 mb-1.5">Total Kuantitas per Satuan:</span>
      <div class="flex flex-wrap gap-1.5">
        <div
          v-for="unit in summary.quantity_by_unit"
          :key="unit.unit_id"
          class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-indigo-50/70 border border-indigo-100 text-xs"
        >
          <span class="text-gray-600 font-medium">{{ unit.unit_name || unit.unit_code }}:</span>
          <span class="font-mono font-bold text-indigo-700">{{ formatQuantity(unit.total_quantity, false) }}</span>
        </div>
      </div>
    </div>

    <div
      v-if="dateBasisDescription"
      class="text-[11px] text-gray-400 italic"
    >
      {{ dateBasisDescription }}
      <span v-if="dateBasis">({{ dateBasis }})</span>
    </div>
  </div>
</template>

<script setup>
import { formatQuantity } from '@/shared/utils/formatters.js';

defineProps({
    summary: {
        type: Object,
        default: null,
    },
    dateBasis: {
        type: String,
        default: null,
    },
    dateBasisDescription: {
        type: String,
        default: null,
    },
});
</script>
