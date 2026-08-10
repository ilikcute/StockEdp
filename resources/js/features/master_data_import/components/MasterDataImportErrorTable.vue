<template>
  <div class="space-y-2">
    <h5 class="text-xs font-bold text-red-900 uppercase tracking-wider">
      Daftar Error ({{ errors.length }} Masalah Ditemukan):
    </h5>
    <div class="max-h-60 overflow-y-auto border border-red-200 rounded-xl">
      <table class="w-full text-left text-xs border-collapse">
        <thead class="bg-red-50 text-red-900 font-semibold sticky top-0 border-b border-red-200">
          <tr>
            <th class="p-2.5 w-16 text-center">
              Baris
            </th>
            <th class="p-2.5 w-28">
              Kolom
            </th>
            <th class="p-2.5 w-36">
              Kode Error
            </th>
            <th class="p-2.5">
              Keterangan
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-red-100 bg-white">
          <tr
            v-for="(err, idx) in errors"
            :key="idx"
            class="hover:bg-red-50/40"
          >
            <td class="p-2 text-center font-semibold text-red-700">
              {{ err.row }}
            </td>
            <td class="p-2 font-mono text-gray-700">
              {{ err.field }}
            </td>
            <td class="p-2 font-mono text-[11px] text-red-600">
              {{ err.code }}
            </td>
            <td class="p-2 text-gray-800">
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
