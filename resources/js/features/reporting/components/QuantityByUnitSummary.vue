<template>
  <div
    v-if="summary"
    class="bg-gray-50 p-4 rounded-lg border border-gray-200 shadow-sm"
  >
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
      <div v-if="summary.total_rows !== undefined">
        <span class="block text-gray-500">Total Item</span>
        <span class="font-medium text-gray-900">{{ summary.total_rows }}</span>
      </div>
      <div v-if="summary.total_documents !== undefined">
        <span class="block text-gray-500">Total Dokumen</span>
        <span class="font-medium text-gray-900">{{ summary.total_documents }}</span>
      </div>
      <slot name="extra" />
    </div>
    <div
      v-if="summary.quantity_by_unit && summary.quantity_by_unit.length > 0"
      class="mt-3 pt-3 border-t border-gray-200"
    >
      <span class="block text-xs text-gray-500 mb-2">Total Kuantitas per Satuan</span>
      <div class="flex flex-wrap gap-3">
        <div
          v-for="unit in summary.quantity_by_unit"
          :key="unit.unit_id"
          class="text-sm"
        >
          <span class="text-gray-600">{{ unit.unit_name || unit.unit_code }}:</span>
          <span class="font-mono font-medium text-gray-900 ml-1">{{ unit.total_quantity }}</span>
        </div>
      </div>
    </div>
    <div
      v-if="dateBasisDescription"
      class="mt-2 text-xs text-gray-500"
    >
      {{ dateBasisDescription }}
      <span v-if="dateBasis"> ({{ dateBasis }})</span>
    </div>
  </div>
</template>

<script setup>
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
