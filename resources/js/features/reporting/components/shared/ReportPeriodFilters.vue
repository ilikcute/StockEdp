<template>
  <div class="contents">
    <div>
      <label class="block text-xs font-medium text-gray-700">Tanggal Mulai</label>
      <input
        :value="startDate"
        type="date"
        class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        @input="emit('update:startDate', $event.target.value)"
      >
    </div>

    <div>
      <label class="block text-xs font-medium text-gray-700">Tanggal Akhir</label>
      <input
        :value="endDate"
        type="date"
        class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
