<template>
  <div class="rounded-lg bg-white p-4 shadow mb-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
      <div>
        <label class="block text-xs font-medium text-gray-700">Arah Selisih</label>
        <select
          :value="filters.variance_direction"
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @change="emit('update:filter', 'variance_direction', $event.target.value)"
        >
          <option value="">
            Semua Arah Selisih
          </option>
          <option value="POSITIVE">
            POSITIVE (Selisih Positif)
          </option>
          <option value="NEGATIVE">
            NEGATIVE (Selisih Negatif)
          </option>
          <option value="ZERO">
            ZERO (Nol / Sesuai)
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700">Filter Product Unexpected</label>
        <select
          :value="filters.is_unexpected"
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @change="emit('update:filter', 'is_unexpected', $event.target.value)"
        >
          <option value="">
            Semua Produk
          </option>
          <option value="1">
            Hanya Produk Tak Terduga (Unexpected = 1)
          </option>
          <option value="0">
            Hanya Produk Terdaftar (Unexpected = 0)
          </option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700">Pencarian Teks</label>
        <input
          :value="filters.search"
          type="text"
          placeholder="Cari nomor opname..."
          class="mt-1 block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
        :products="masterStore.products"
        @product-search="q => emit('product-search', q)"
        @select-product="p => emit('select-product', p)"
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
        :sort-options="opnameSortOptions"
        @update:sort-by="val => emit('update:filter', 'sort_by', val)"
        @update:sort-order="val => emit('update:filter', 'sort_order', val)"
        @update:per-page="val => emit('update:filter', 'per_page', val)"
      />

      <div class="flex items-end">
        <button
          type="button"
          class="w-full rounded-md bg-gray-100 px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-200"
          @click="emit('reset')"
        >
          Reset Filter
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
});

const emit = defineEmits(['update:filter', 'product-search', 'select-product', 'reset']);

const opnameSortOptions = [
    { value: 'posted_at', label: 'Waktu Posting' },
    { value: 'opname_date', label: 'Tanggal Dokumen' },
    { value: 'opname_number', label: 'Nomor Opname' },
    { value: 'id', label: 'ID Item' },
];
</script>
