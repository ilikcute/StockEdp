<template>
  <div class="contents">
    <div>
      <label class="block text-xs font-medium text-gray-700">Tanggal Mulai</label>
      <input
        :value="startDate"
        type="date"
        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        @input="emit('update:startDate', $event.target.value)"
      >
    </div>

    <div>
      <label class="block text-xs font-medium text-gray-700">Tanggal Akhir</label>
      <input
        :value="endDate"
        type="date"
        class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        @input="emit('update:endDate', $event.target.value)"
      >
    </div>
  </div>
</template>

<script setup>
defineProps({
    startDate: { type: String, default: '' },
    endDate: { type: String, default: '' },
});

const emit = defineEmits(['update:startDate', 'update:endDate']);
</script>
