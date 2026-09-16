<template>
  <div class="rounded-xl bg-white p-2.5 border border-gray-200 shadow-2xs space-y-2.5">
    <!-- Primary Filter Bar (Compact Single Row) -->
    <div class="flex flex-wrap items-center justify-between gap-2">
      <!-- Left side: Core Search & Quick Filters -->
      <div class="flex flex-wrap items-center gap-2 flex-1 min-w-0">
        <!-- Text Search Input -->
        <div class="relative w-full sm:w-56 md:w-60">
          <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
            <svg
              class="w-3.5 h-3.5 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
          </div>
          <input
            :value="filters.search"
            type="text"
            placeholder="Cari nomor transfer, catatan..."
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-8 pr-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            @input="emit('update:filter', 'search', $event.target.value)"
          >
        </div>

        <!-- Origin Location -->
        <div class="w-full sm:w-36">
          <select
            :value="filters.origin_location_id"
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            title="Lokasi Asal"
            @change="emit('update:filter', 'origin_location_id', $event.target.value)"
          >
            <option value="">
              Semua Asal
            </option>
            <option
              v-for="loc in masterStore.locations"
              :key="loc.id"
              :value="String(loc.id)"
            >
              {{ loc.code ? `${loc.code} — ` : '' }}{{ loc.name }}
            </option>
          </select>
        </div>

        <!-- Destination Location -->
        <div class="w-full sm:w-36">
          <select
            :value="filters.destination_location_id"
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            title="Lokasi Tujuan"
            @change="emit('update:filter', 'destination_location_id', $event.target.value)"
          >
            <option value="">
              Semua Tujuan
            </option>
            <option
              v-for="loc in masterStore.locations"
              :key="loc.id"
              :value="String(loc.id)"
            >
              {{ loc.code ? `${loc.code} — ` : '' }}{{ loc.name }}
            </option>
          </select>
        </div>

        <!-- Transfer Status -->
        <div class="w-full sm:w-32">
          <select
            :value="filters.status"
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            title="Status Transfer"
            @change="emit('update:filter', 'status', $event.target.value)"
          >
            <option value="">
              Semua Status
            </option>
            <option
              value="IN_TRANSIT"
              :disabled="filters.date_basis === 'RECEIVED_AT'"
            >
              IN_TRANSIT
            </option>
            <option value="RECEIVED">
              RECEIVED
            </option>
          </select>
        </div>

        <!-- Period (Date Range) Compact with Date Basis -->
        <div class="flex items-center gap-1.5 bg-gray-50 border border-gray-200/80 rounded-lg px-2 py-1 shadow-2xs">
          <select
            :value="filters.date_basis"
            title="Dasar Tanggal"
            class="bg-transparent border-0 p-0 text-xs font-semibold text-indigo-700 focus:ring-0 focus:outline-none cursor-pointer"
            @change="emit('update:filter', 'date_basis', $event.target.value)"
          >
            <option value="SENT_AT">
              Kirim:
            </option>
            <option value="RECEIVED_AT">
              Terima:
            </option>
          </select>
          <input
            :value="filters.start_date"
            type="date"
            title="Tanggal Mulai"
            class="bg-transparent border-0 p-0 text-xs text-gray-700 focus:ring-0 focus:outline-none"
            @input="emit('update:filter', 'start_date', $event.target.value)"
          >
          <span class="text-gray-400 text-[11px]">s/d</span>
          <input
            :value="filters.end_date"
            type="date"
            title="Tanggal Akhir"
            class="bg-transparent border-0 p-0 text-xs text-gray-700 focus:ring-0 focus:outline-none"
            @input="emit('update:filter', 'end_date', $event.target.value)"
          >
        </div>
      </div>

      <!-- Right side: Toggle Advanced Filters & Reset -->
      <div class="flex items-center gap-2 shrink-0">
        <button
          type="button"
          :class="[
            'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium shadow-2xs transition-colors cursor-pointer whitespace-nowrap',
            showAdvanced || activeAdvancedCount > 0
              ? 'bg-indigo-50 border-indigo-200 text-indigo-700 hover:bg-indigo-100'
              : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
          ]"
          @click="showAdvanced = !showAdvanced"
        >
          <svg
            class="w-3.5 h-3.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
            />
          </svg>
          <span>Filter Lanjutan</span>
          <span
            v-if="activeAdvancedCount > 0"
            class="inline-flex items-center justify-center px-1.5 py-0.2 text-[10px] font-bold text-white bg-indigo-600 rounded-full"
          >
            {{ activeAdvancedCount }}
          </span>
        </button>

        <button
          v-if="isAnyFilterActive"
          type="button"
          class="rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 shadow-2xs cursor-pointer whitespace-nowrap"
          title="Reset semua filter"
          @click="emit('reset')"
        >
          Reset
        </button>
      </div>
    </div>

    <!-- Secondary Filter Drawer (Only visible when toggled) -->
    <div
      v-if="showAdvanced"
      class="pt-2.5 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 bg-gray-50/60 p-2.5 rounded-lg"
    >
      <!-- Category -->
      <ReportMasterSelect
        label="Kategori"
        placeholder="Semua Kategori"
        :model-value="filters.category_id"
        :options="masterStore.categories"
        @update:model-value="val => emit('update:filter', 'category_id', val)"
      />

      <!-- Unit -->
      <ReportMasterSelect
        label="Satuan"
        placeholder="Semua Satuan"
        :model-value="filters.unit_id"
        :options="masterStore.units"
        @update:model-value="val => emit('update:filter', 'unit_id', val)"
      />

      <!-- Product Search -->
      <ReportProductSearch
        :model-value="productSearch"
        :selected-product-id="filters.product_id"
        :products="masterStore.products"
        :loading="masterStore.loadingProducts"
        @update:model-value="val => emit('update:productSearch', val)"
        @search="q => emit('product-search', q)"
        @select-product="p => emit('select-product', p)"
        @clear-product="emit('clear-product')"
      />

      <!-- Sort Controls -->
      <ReportSortControls
        :sort-by="filters.sort_by"
        :sort-order="filters.sort_order"
        :per-page="filters.per_page"
        :sort-options="transferSortOptions"
        @update:sort-by="val => emit('update:filter', 'sort_by', val)"
        @update:sort-order="val => emit('update:filter', 'sort_order', val)"
        @update:per-page="val => emit('update:filter', 'per_page', val)"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import ReportMasterSelect from '../shared/ReportMasterSelect.vue';
import ReportSortControls from '../shared/ReportSortControls.vue';
import ReportProductSearch from '../shared/ReportProductSearch.vue';

const props = defineProps({
    filters: { type: Object, required: true },
    masterStore: { type: Object, required: true },
    productSearch: { type: String, default: '' },
});

const emit = defineEmits([
    'update:filter',
    'update:productSearch',
    'product-search',
    'select-product',
    'clear-product',
    'reset',
]);

const showAdvanced = ref(false);

const activeAdvancedCount = computed(() => {
    let c = 0;
    if (props.filters.category_id) c++;
    if (props.filters.unit_id) c++;
    if (props.filters.product_id) c++;
    if (props.filters.sort_by && !['sent_at', 'received_at'].includes(props.filters.sort_by)) c++;
    return c;
});

const isAnyFilterActive = computed(() => {
    return Boolean(
        props.filters.search ||
        props.filters.origin_location_id ||
        props.filters.destination_location_id ||
        props.filters.status ||
        props.filters.start_date ||
        props.filters.end_date ||
        props.filters.category_id ||
        props.filters.unit_id ||
        props.filters.product_id
    );
});

const transferSortOptions = [
    { value: 'sent_at', label: 'Waktu Pengiriman (sent_at)' },
    { value: 'received_at', label: 'Waktu Penerimaan (received_at)' },
    { value: 'transfer_number', label: 'Nomor Transfer' },
    { value: 'id', label: 'ID Item' },
];
</script>
