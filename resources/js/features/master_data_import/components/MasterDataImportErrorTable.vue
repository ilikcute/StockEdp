<template>
  <div class="space-y-2">
    <h5 class="text-xs font-bold text-rose-900 uppercase tracking-wider">
      Daftar Error ({{ errors.length }} Masalah Ditemukan):
    </h5>
    <div class="max-h-60 overflow-y-auto overflow-x-auto border border-rose-200 rounded-xl shadow-2xs custom-scrollbar">
      <table class="w-full text-left text-xs border-collapse">
        <thead class="bg-rose-50/95 backdrop-blur-xs text-rose-900 font-semibold sticky top-0 border-b border-rose-200 text-[11px]">
          <tr>
            <th class="py-1.5 px-1.5 w-12 text-center whitespace-nowrap">
              Baris
            </th>
            <th class="py-1.5 px-2 w-28 whitespace-nowrap">
              Kolom
            </th>
            <th class="py-1.5 px-2 w-36 whitespace-nowrap">
              Kode Error
            </th>
            <th class="py-1.5 px-2 whitespace-nowrap">
              Keterangan
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-rose-100 bg-white">
          <tr
            v-for="(err, idx) in errors"
            :key="idx"
            class="hover:bg-rose-50/40 transition-colors"
          >
            <td class="py-1.5 px-1.5 text-center font-mono font-semibold text-rose-700 text-[11px] whitespace-nowrap">
              {{ err.row }}
            </td>
            <td class="py-1.5 px-2 font-mono text-gray-700 text-[11px] whitespace-nowrap">
              {{ err.field }}
            </td>
            <td class="py-1.5 px-2 font-mono text-[11px] text-rose-600 whitespace-nowrap">
              {{ err.code }}
            </td>
            <td class="py-1.5 px-2 text-gray-800 text-[11px]">
              {{ err.message }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
  errors: {
    type: Array,
    required: true,
  },
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
