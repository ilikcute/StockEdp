<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6 transition-all duration-200">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
        <!-- Location Dropdown -->
        <div class="w-full sm:w-64">
          <label
            for="dashboard-location-filter"
            class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider"
          >
            Lokasi Persediaan
          </label>
          <select
            id="dashboard-location-filter"
            :value="locationId"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            @change="$emit('update:locationId', $event.target.value)"
          >
            <option value="">
              Semua Lokasi Terjangkau
            </option>
            <option
              v-for="loc in locations"
              :key="loc.id"
              :value="loc.id"
            >
              {{ loc.code }} — {{ loc.name }}
            </option>
          </select>
        </div>

        <!-- Period Preset Selector -->
        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">
            Periode
          </label>
          <div class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 p-0.5 bg-gray-50 dark:bg-gray-900">
            <button
              v-for="p in periods"
              :id="'period-preset-' + p.value"
              :key="p.value"
              type="button"
              :class="[
                'px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-150',
                period === p.value
                  ? 'bg-blue-600 text-white shadow-sm'
                  : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
              ]"
              @click="$emit('update:period', p.value)"
            >
              {{ p.label }}
            </button>
          </div>
        </div>
      </div>

      <!-- Actions & Generated Info -->
      <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end border-t md:border-t-0 pt-3 md:pt-0 border-gray-100 dark:border-gray-700">
        <div
          v-if="dateRangeText"
          class="text-xs text-gray-500 dark:text-gray-400 text-right"
        >
          <span class="font-medium text-gray-700 dark:text-gray-300">{{ dateRangeText }}</span>
          <span class="block text-[10px] text-gray-400">WIB (Asia/Jakarta)</span>
        </div>

        <button
          id="refresh-dashboard-btn"
          type="button"
          :disabled="loading"
          class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-medium rounded-lg transition-colors disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
          @click="$emit('refresh')"
        >
          <svg
            :class="['w-3.5 h-3.5', loading ? 'animate-spin' : '']"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          Refresh
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  locationId: { type: [String, Number], default: '' },
  period: { type: String, default: '7d' },
  locations: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  dateFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
});

defineEmits(['update:locationId', 'update:period', 'refresh']);

const periods = [
  { value: 'today', label: 'Hari Ini' },
  { value: '7d', label: '7 Hari' },
  { value: '30d', label: '30 Hari' },
];

const dateRangeText = computed(() => {
  if (!props.dateFrom || !props.dateTo) return '';
  if (props.dateFrom === props.dateTo) return props.dateFrom;
  return `${props.dateFrom} s/d ${props.dateTo}`;
});
</script>
