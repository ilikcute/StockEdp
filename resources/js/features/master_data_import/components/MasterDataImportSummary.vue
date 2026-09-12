<template>
  <div class="space-y-4">
    <!-- Summary Cards -->
    <div class="grid grid-cols-3 gap-3">
      <div class="bg-gray-50 border border-gray-100 p-3 rounded-xl">
        <span class="text-xs text-gray-500 block">Total Baris</span>
        <span class="text-lg font-bold text-gray-900">{{ summary.total_rows }}</span>
      </div>
      <div class="bg-green-50 border border-green-100 p-3 rounded-xl">
        <span class="text-xs text-green-700 block">Baris Valid</span>
        <span class="text-lg font-bold text-green-700">{{ summary.valid_rows }}</span>
      </div>
      <div
        class="p-3 rounded-xl border"
        :class="summary.invalid_rows > 0 ? 'bg-red-50 border-red-100 text-red-700' : 'bg-gray-50 border-gray-100 text-gray-500'"
      >
        <span class="text-xs block">Baris Error</span>
        <span
          class="text-lg font-bold"
          :class="summary.invalid_rows > 0 ? 'text-red-700' : 'text-gray-900'"
        >
          {{ summary.invalid_rows }}
        </span>
      </div>
    </div>

    <!-- Status Banner -->
    <div
      v-if="summary.invalid_rows === 0 && summary.total_rows > 0"
      class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 flex items-center gap-2"
    >
      <span>✓</span>
      <span class="font-medium">Seluruh {{ summary.total_rows }} baris data valid dan siap diimport ke database.</span>
    </div>

    <div
      v-else-if="summary.invalid_rows > 0"
      class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-800 space-y-1"
    >
      <div class="flex items-center gap-2 font-semibold">
        <span>⚠</span>
        <span>Ditemukan {{ summary.invalid_rows }} baris data bermasalah.</span>
      </div>
      <p class="text-[11px] text-red-700">
        Sesuai aturan <em>All-or-Nothing</em>, proses import tidak dapat dilanjutkan sebelum file CSV diperbaiki.
      </p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  summary: {
    type: Object,
    required: true,
  },
});
</script>
