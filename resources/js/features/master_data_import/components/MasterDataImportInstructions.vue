<template>
  <div class="bg-blue-50/70 border border-blue-100 rounded-xl p-4 space-y-3">
    <div class="flex items-start justify-between gap-4">
      <div class="space-y-1">
        <h4 class="text-xs font-semibold text-blue-900 uppercase tracking-wider">
          Petunjuk Pengisian File CSV:
        </h4>
        <ul class="text-xs text-blue-800 list-disc list-inside space-y-0.5">
          <li>Gunakan template resmi berformat CSV UTF-8.</li>
          <li>Jangan mengubah atau menghapus nama kolom header.</li>
          <li>Maksimum 5.000 baris data per proses import.</li>
          <li><strong>CREATE ONLY</strong>: Data yang sudah ada di database atau duplikat di file dianggap error.</li>
          <li><strong>All-or-Nothing</strong>: Seluruh baris harus valid agar data dapat diimport.</li>
        </ul>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-700 bg-white hover:bg-blue-50 border border-blue-200 rounded-lg transition-colors shadow-xs shrink-0 cursor-pointer"
        :disabled="isDownloading"
        @click="$emit('download')"
      >
        <span v-if="isDownloading">Mengunduh...</span>
        <span v-else>⬇ Unduh Template CSV</span>
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  isDownloading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['download']);
</script>
