<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between">
      <h5 class="text-xs font-bold text-gray-700 uppercase tracking-wider">
        Preview Data ({{ previewRows.length }} Baris Pertama):
      </h5>
      <span class="text-[11px] text-gray-400">Menampilkan maksimal 20 baris</span>
    </div>
    <div class="max-h-60 overflow-x-auto overflow-y-auto border border-gray-200 rounded-xl">
      <table class="w-full text-left text-xs border-collapse min-w-[500px]">
        <thead class="bg-gray-50 text-gray-700 font-semibold sticky top-0 border-b border-gray-200">
          <tr>
            <th class="p-2.5 w-14 text-center">
              #
            </th>
            <th class="p-2.5 w-16 text-center">
              Status
            </th>
            <th
              v-for="col in columns"
              :key="col"
              class="p-2.5 capitalize"
            >
              {{ formatColumnName(col) }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr
            v-for="row in previewRows"
            :key="row.row_number"
            :class="row.is_valid ? 'hover:bg-gray-50' : 'bg-red-50/30 hover:bg-red-50/60'"
          >
            <td class="p-2 text-center text-gray-500 font-mono">
              {{ row.row_number }}
            </td>
            <td class="p-2 text-center">
              <span
                class="px-1.5 py-0.5 rounded-full text-[10px] font-bold"
                :class="row.is_valid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
              >
                {{ row.is_valid ? 'VALID' : 'ERROR' }}
              </span>
            </td>
            <td
              v-for="col in columns"
              :key="col"
              class="p-2 text-gray-800 truncate max-w-xs font-mono text-[11px]"
            >
              {{ row[col] ?? '-' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
  previewRows: {
    type: Array,
    required: true,
  },
  columns: {
    type: Array,
    required: true,
  },
});

function formatColumnName(col) {
  return col.replace('_', ' ');
}
</script>
