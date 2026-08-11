<template>
  <div class="contents">
    <div>
      <label class="block text-xs font-medium text-gray-700">Urutkan Berdasarkan</label>
      <select
        :value="sortBy"
        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        @change="emit('update:sortBy', $event.target.value)"
      >
        <option
          v-for="opt in sortOptions"
          :key="opt.value"
          :value="opt.value"
        >
          {{ opt.label }}
        </option>
      </select>
    </div>

    <div>
      <label class="block text-xs font-medium text-gray-700">Arah Urutan</label>
      <select
        :value="sortOrder"
        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        @change="emit('update:sortOrder', $event.target.value)"
      >
        <option value="desc">
          Terbaru / Descending
        </option>
        <option value="asc">
          Terlama / Ascending
        </option>
      </select>
    </div>

    <div>
      <label class="block text-xs font-medium text-gray-700">Per Halaman</label>
      <select
        :value="perPage"
        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        @change="emit('update:perPage', $event.target.value)"
      >
        <option value="15">
          15 Baris
        </option>
        <option value="25">
          25 Baris
        </option>
        <option value="50">
          50 Baris
        </option>
      </select>
    </div>
  </div>
</template>

<script setup>
defineProps({
    sortBy: { type: String, required: true },
    sortOrder: { type: String, default: 'desc' },
    perPage: { type: String, default: '15' },
    sortOptions: { type: Array, required: true },
});

const emit = defineEmits(['update:sortBy', 'update:sortOrder', 'update:perPage']);
</script>
