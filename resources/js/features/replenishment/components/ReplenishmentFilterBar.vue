<template>
  <div class="bg-white rounded-xl p-4 shadow-xs border border-gray-200 space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <!-- Target Location (Required) -->
      <div>
        <label
          for="filter-location"
          class="block text-xs font-semibold text-gray-700 mb-1"
        >
          Lokasi Target <span class="text-rose-500">*</span>
        </label>
        <select
          id="filter-location"
          :value="filters.location_id"
          class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900 shadow-xs"
          @change="onFilterChange('location_id', $event.target.value)"
        >
          <option
            value=""
            disabled
          >
            -- Pilih Lokasi Target --
          </option>
          <option
            v-for="loc in filterOptions.locations"
            :key="loc.id"
            :value="loc.id"
          >
            {{ loc.code }} - {{ loc.name }}
          </option>
        </select>
      </div>

      <!-- Search Input -->
      <div>
        <label
          for="filter-search"
          class="block text-xs font-semibold text-gray-700 mb-1"
        >
          Cari Produk / SKU / Barcode
        </label>
        <input
          id="filter-search"
          :value="filters.search"
          type="text"
          placeholder="Ketik kata kunci..."
          class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900"
          @input="onFilterChange('search', $event.target.value)"
          @keyup.enter="$emit('search')"
        >
      </div>

      <!-- Category Filter -->
      <div>
        <label
          for="filter-category"
          class="block text-xs font-semibold text-gray-700 mb-1"
        >
          Kategori
        </label>
        <select
          id="filter-category"
          :value="filters.category_id"
          class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900 shadow-xs"
          @change="onFilterChange('category_id', $event.target.value)"
        >
          <option value="">
            Semua Kategori
          </option>
          <option
            v-for="cat in filterOptions.categories"
            :key="cat.id"
            :value="cat.id"
          >
            {{ cat.name }}
          </option>
        </select>
      </div>

      <!-- Unit Filter -->
      <div>
        <label
          for="filter-unit"
          class="block text-xs font-semibold text-gray-700 mb-1"
        >
          Satuan
        </label>
        <select
          id="filter-unit"
          :value="filters.unit_id"
          class="block w-full rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900 shadow-xs"
          @change="onFilterChange('unit_id', $event.target.value)"
        >
          <option value="">
            Semua Satuan
          </option>
          <option
            v-for="u in filterOptions.units"
            :key="u.id"
            :value="u.id"
          >
            {{ u.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Secondary Filters & Actions Row -->
    <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-gray-100">
      <div class="flex flex-wrap items-center gap-3">
        <!-- Recommendation Type Filter -->
        <div class="w-52">
          <select
            id="filter-recommendation-type"
            :value="filters.recommendation_type"
            class="block w-full rounded-md border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900 shadow-xs"
            @change="onFilterChange('recommendation_type', $event.target.value)"
          >
            <option value="">
              Semua Tipe Rekomendasi
            </option>
            <option
              v-for="type in filterOptions.recommendation_types"
              :key="type.value"
              :value="type.value"
            >
              {{ type.label }}
            </option>
          </select>
        </div>

        <!-- Priority Filter -->
        <div class="w-44">
          <select
            id="filter-priority"
            :value="filters.priority"
            class="block w-full rounded-md border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900 shadow-xs"
            @change="onFilterChange('priority', $event.target.value)"
          >
            <option value="">
              Semua Prioritas
            </option>
            <option
              v-for="p in filterOptions.priorities"
              :key="p.value"
              :value="p.value"
            >
              {{ p.label }}
            </option>
          </select>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2 ml-auto">
        <button
          type="button"
          class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-xs hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors cursor-pointer"
          @click="$emit('reset')"
        >
          Reset Filter
        </button>

        <button
          type="button"
          :disabled="loading"
          class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-600 cursor-pointer"
          @click="$emit('refresh')"
        >
          <svg
            v-if="loading"
            class="animate-spin -ml-0.5 mr-1 h-3.5 w-3.5 text-white"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8v8H4z"
            />
          </svg>
          Segarkan
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  filters: {
    type: Object,
    required: true,
  },
  filterOptions: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  generatedAt: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:filter', 'search', 'reset', 'refresh']);

const onFilterChange = (key, value) => {
  emit('update:filter', { key, value });
};
</script>
