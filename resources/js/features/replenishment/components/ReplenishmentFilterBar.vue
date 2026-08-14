<template>
  <div class="bg-white dark:bg-slate-800 rounded-xl p-4 shadow-sm border border-slate-200 dark:border-slate-700 space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <!-- Target Location (Required) -->
      <div>
        <label
          for="filter-location"
          class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
        >
          Lokasi Target <span class="text-rose-500">*</span>
        </label>
        <select
          id="filter-location"
          :value="filters.location_id"
          class="w-full text-sm rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
          class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
        >
          Cari Produk / SKU / Barcode
        </label>
        <input
          id="filter-search"
          :value="filters.search"
          type="text"
          placeholder="Ketik kata kunci..."
          class="w-full text-sm rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          @input="onFilterChange('search', $event.target.value)"
          @keyup.enter="$emit('search')"
        >
      </div>

      <!-- Category Filter -->
      <div>
        <label
          for="filter-category"
          class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
        >
          Kategori
        </label>
        <select
          id="filter-category"
          :value="filters.category_id"
          class="w-full text-sm rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
          class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
        >
          Satuan
        </label>
        <select
          id="filter-unit"
          :value="filters.unit_id"
          class="w-full text-sm rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
    <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100 dark:border-slate-700/60">
      <div class="flex flex-wrap items-center gap-3">
        <!-- Recommendation Type Filter -->
        <div class="w-48">
          <select
            id="filter-recommendation-type"
            :value="filters.recommendation_type"
            class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
            class="w-full text-xs rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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

      <!-- Action Buttons & Timestamp -->
      <div class="flex items-center gap-2 ml-auto">
        <span
          v-if="generatedAt"
          class="text-xs text-slate-400 dark:text-slate-500 hidden sm:inline"
        >
          Terakhir diperbarui: {{ formatTime(generatedAt) }}
        </span>

        <button
          type="button"
          class="px-3 py-1.5 text-xs font-medium rounded-lg text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-400"
          @click="$emit('reset')"
        >
          Reset
        </button>

        <button
          type="button"
          :disabled="loading"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
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

const formatTime = (isoString) => {
  if (!isoString) return '-';
  try {
    const d = new Date(isoString);
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  } catch {
    return isoString;
  }
};
</script>
