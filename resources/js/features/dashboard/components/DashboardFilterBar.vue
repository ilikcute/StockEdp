<template>
  <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-3 transition-all duration-200">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-2.5">
      <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
        <!-- Location Dropdown -->
        <div class="w-full sm:w-60">
          <label
            for="dashboard-location-filter"
            class="block text-[11px] font-semibold text-gray-500 mb-0.5 uppercase tracking-wider"
          >
            Lokasi Persediaan
          </label>
          <select
            id="dashboard-location-filter"
            :value="locationId"
            class="w-full rounded-lg border border-gray-300 bg-white text-gray-900 text-sm px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            @change="handleLocationChange"
          >
            <option value="">
              Semua Lokasi Terjangkau
            </option>
            <option
              v-for="loc in locations"
              :key="loc.id"
              :value="loc.id"
            >
              {{ formatLocationOption(loc) }}
            </option>
          </select>
        </div>

        <!-- Period Preset Selector -->
        <div>
          <label class="block text-[11px] font-semibold text-gray-500 mb-0.5 uppercase tracking-wider">
            Periode
          </label>
          <div class="inline-flex rounded-lg border border-gray-300 p-0.5 bg-gray-50">
            <button
              v-for="p in periods"
              :id="'period-preset-' + p.value"
              :key="p.value"
              type="button"
              :class="[
                'px-2.5 py-1 text-xs font-medium rounded-md transition-all duration-150 cursor-pointer',
                period === p.value
                  ? 'bg-blue-600 text-white shadow-xs'
                  : 'text-gray-600 hover:text-gray-900'
              ]"
              @click="$emit('update:period', p.value)"
            >
              {{ p.label }}
            </button>
          </div>
        </div>
      </div>

      <!-- Actions & Generated Info -->
      <div class="flex items-center gap-2.5 w-full md:w-auto justify-between md:justify-end border-t md:border-t-0 pt-2 md:pt-0 border-gray-100">
        <div
          v-if="dateRangeText"
          class="text-xs text-gray-500 text-right"
        >
          <span class="font-medium text-gray-700">{{ dateRangeText }}</span>
          <span class="block text-[10px] text-gray-400">WIB (Asia/Jakarta)</span>
        </div>

        <button
          id="refresh-dashboard-btn"
          type="button"
          :disabled="loading"
          class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition-colors disabled:opacity-50 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
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
import { ref, computed, watch } from 'vue';

const props = defineProps({
  locationId: { type: [String, Number], default: '' },
  period: { type: String, default: '7d' },
  locations: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  dateFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
});

const emit = defineEmits(['update:locationId', 'update:period', 'refresh']);

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

const hasUserSelected = ref(false);

const findMainWarehouse = (locs) => {
  if (!locs || locs.length === 0) return null;
  // 1. Check by type MAIN_WAREHOUSE
  const byType = locs.find(l => l.type === 'MAIN_WAREHOUSE');
  if (byType) return byType;
  // 2. Check by code 'ADM' or contains 'MAIN' or 'GDG'
  const byCode = locs.find(l => ['ADM', 'MAIN', 'GDG', 'GUDANG'].includes(String(l.code || '').toUpperCase()));
  if (byCode) return byCode;
  // 3. Check by name containing 'adm' or 'gudang' or 'main'
  const byName = locs.find(l => /gudang|main|adm/i.test(l.name || ''));
  if (byName) return byName;
  // 4. Default to first location
  return locs[0];
};

const handleLocationChange = (event) => {
  hasUserSelected.value = true;
  emit('update:locationId', event.target.value);
  emit('update:location-id', event.target.value);
};

// Format location option label with type indicator
const LOCATION_TYPE_LABELS = {
  MAIN_WAREHOUSE: '🏛 Gudang Induk',
  FIELD_PERSONNEL: '🔧 Teknisi',
  DAMAGED_STORAGE: '⚠ Gudang Afkir',
};

const formatLocationOption = (loc) => {
  const typeLabel = LOCATION_TYPE_LABELS[loc.type] ? ` (${LOCATION_TYPE_LABELS[loc.type]})` : '';
  return `${loc.code} — ${loc.name}${typeLabel}`;
};

// Set default location to main gudang when locations are loaded and no location has been selected yet
watch(
  () => props.locations,
  (newLocations) => {
    if (!hasUserSelected.value && (!props.locationId || props.locationId === '') && newLocations && newLocations.length > 0) {
      const mainWarehouse = findMainWarehouse(newLocations);
      if (mainWarehouse) {
        emit('update:locationId', mainWarehouse.id);
        emit('update:location-id', mainWarehouse.id);
      }
    }
  },
  { immediate: true }
);
</script>
