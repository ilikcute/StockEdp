<template>
  <div class="flex flex-wrap gap-2.5 items-end">
    <!-- Search -->
    <div>
      <label
        for="opname-search"
        class="block text-[11px] font-medium text-gray-700 mb-0.5"
      >
        Nomor Opname
      </label>
      <input
        id="opname-search"
        v-model="localFilters.search"
        type="text"
        placeholder="Cari nomor..."
        class="block w-40 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 placeholder-gray-400 shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        @input="emitFilters"
      >
    </div>

    <!-- Status filter -->
    <div>
      <label
        for="opname-status"
        class="block text-[11px] font-medium text-gray-700 mb-0.5"
      >
        Status
      </label>
      <select
        id="opname-status"
        v-model="localFilters.status"
        class="block rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        @change="emitFilters"
      >
        <option value="">
          Semua Status
        </option>
        <option value="DRAFT">
          Draft
        </option>
        <option value="IN_PROGRESS">
          Sedang Dihitung
        </option>
        <option value="COUNTED">
          Menunggu Rekonsiliasi
        </option>
        <option value="POSTED">
          Diposting
        </option>
        <option value="CANCELED">
          Dibatalkan
        </option>
      </select>
    </div>

    <!-- Location filter -->
    <div>
      <label
        for="opname-location"
        class="block text-[11px] font-medium text-gray-700 mb-0.5"
      >
        Lokasi
      </label>
      <select
        id="opname-location"
        v-model="localFilters.location_id"
        class="block rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        @change="emitFilters"
      >
        <option value="">
          Semua Lokasi
        </option>
        <option
          v-for="loc in locations"
          :key="loc.id"
          :value="loc.id"
        >
          {{ loc.name }}
        </option>
      </select>
    </div>

    <!-- Date range: start -->
    <div>
      <label
        for="opname-start-date"
        class="block text-[11px] font-medium text-gray-700 mb-0.5"
      >
        Tanggal Dari
      </label>
      <input
        id="opname-start-date"
        v-model="localFilters.start_date"
        type="date"
        class="block rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        @change="emitFilters"
      >
    </div>

    <!-- Date range: end -->
    <div>
      <label
        for="opname-end-date"
        class="block text-[11px] font-medium text-gray-700 mb-0.5"
      >
        Tanggal Hingga
      </label>
      <input
        id="opname-end-date"
        v-model="localFilters.end_date"
        type="date"
        class="block rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs text-gray-900 shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        @change="emitFilters"
      >
    </div>

    <!-- Reset -->
    <button
      type="button"
      class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-xs hover:bg-gray-50 cursor-pointer focus:outline-none focus:ring-1 focus:ring-indigo-500"
      @click="resetFilters"
    >
      Reset
    </button>
  </div>
</template>

<script setup>
import { reactive } from 'vue';

defineProps({
    locations: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['filter']);

const localFilters = reactive({
    search: '',
    status: '',
    location_id: '',
    start_date: '',
    end_date: '',
});

function emitFilters() {
    const params = {};
    if (localFilters.search) params.search = localFilters.search;
    if (localFilters.status) params.status = localFilters.status;
    if (localFilters.location_id) params.location_id = localFilters.location_id;
    if (localFilters.start_date) params.start_date = localFilters.start_date;
    if (localFilters.end_date) params.end_date = localFilters.end_date;
    emit('filter', params);
}

function resetFilters() {
    localFilters.search = '';
    localFilters.status = '';
    localFilters.location_id = '';
    localFilters.start_date = '';
    localFilters.end_date = '';
    emit('filter', {});
}
</script>
