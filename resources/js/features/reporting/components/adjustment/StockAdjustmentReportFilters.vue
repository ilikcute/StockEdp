<template>
  <div class="rounded-lg bg-white p-3 border border-gray-200 shadow-2xs">
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2 sm:gap-2.5">
      <div>
        <label class="block text-xs font-medium text-gray-700">Direction</label>
        <select
          :value="filters.direction"
          class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          @change="emit('update:filter', 'direction', $event.target.value)"
        >
          <option value="">
            Semua Direction
          </option>
          <option value="INCREASE">
            INCREASE (Penambahan)
          </option>
          <option value="DECREASE">
            DECREASE (Pengurangan)
          </option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700">Alasan / Reason</label>
        <select
          :value="filters.reason_code"
          class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          @change="emit('update:filter', 'reason_code', $event.target.value)"
        >
          <option value="">
            Semua Alasan
          </option>
          <option value="FOUND">
            FOUND (Ditemukan)
          </option>
          <option value="DAMAGED">
            DAMAGED (Rusak)
          </option>
          <option value="EXPIRED">
            EXPIRED (Kedaluwarsa)
          </option>
          <option value="RECORDING_ERROR">
            RECORDING_ERROR (Salah Catat)
          </option>
          <option value="ADMINISTRATIVE">
            ADMINISTRATIVE (Koreksi Adm)
          </option>
          <option value="LOST">
            LOST (Hilang)
          </option>
          <option value="OTHER">
            OTHER (Lain-lain)
          </option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700">Pencarian Teks</label>
        <input
          :value="filters.search"
          type="text"
          placeholder="Cari nomor, notes..."
          class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          @input="emit('update:filter', 'search', $event.target.value)"
        >
      </div>
      <ReportMasterSelect
        label="Lokasi"
        placeholder="Semua Lokasi"
        :model-value="filters.location_id"
        :options="masterStore.locations"
        @update:model-value="val => emit('update:filter', 'location_id', val)"
      />
      <ReportMasterSelect
        label="Kategori"
        placeholder="Semua Kategori"
        :model-value="filters.category_id"
        :options="masterStore.categories"
        @update:model-value="val => emit('update:filter', 'category_id', val)"
      />
      <ReportMasterSelect
        label="Satuan"
        placeholder="Semua Satuan"
        :model-value="filters.unit_id"
        :options="masterStore.units"
        @update:model-value="val => emit('update:filter', 'unit_id', val)"
      />
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
      <ReportPeriodFilters
        :start-date="filters.start_date"
        :end-date="filters.end_date"
        @update:start-date="val => emit('update:filter', 'start_date', val)"
        @update:end-date="val => emit('update:filter', 'end_date', val)"
      />
      <ReportSortControls
        :sort-by="filters.sort_by"
        :sort-order="filters.sort_order"
        :per-page="filters.per_page"
        :sort-options="adjustmentSortOptions"
        @update:sort-by="val => emit('update:filter', 'sort_by', val)"
        @update:sort-order="val => emit('update:filter', 'sort_order', val)"
        @update:per-page="val => emit('update:filter', 'per_page', val)"
      />
      <div class="flex items-end">
        <button
          type="button"
          class="w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-2xs hover:bg-gray-50 cursor-pointer focus:outline-none focus:ring-1 focus:ring-indigo-500 h-[34px] flex items-center justify-center gap-1"
          @click="emit('reset')"
        >
          <svg
            class="w-3.5 h-3.5 text-gray-500"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="2"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"
            />
          </svg>
          <span>Reset</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import ReportMasterSelect from '../shared/ReportMasterSelect.vue';
import ReportPeriodFilters from '../shared/ReportPeriodFilters.vue';
import ReportSortControls from '../shared/ReportSortControls.vue';
import ReportProductSearch from '../shared/ReportProductSearch.vue';

defineProps({
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

const adjustmentSortOptions = [
    { value: 'posted_at', label: 'Waktu Posting' },
    { value: 'adjustment_date', label: 'Tanggal Dokumen' },
    { value: 'adjustment_number', label: 'Nomor Adjustment' },
    { value: 'id', label: 'ID Item' },
];
</script>
