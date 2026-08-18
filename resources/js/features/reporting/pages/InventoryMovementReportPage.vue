<template>
  <div class="px-4 sm:px-6 lg:px-8 space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-xl font-bold text-gray-900 flex items-center gap-2.5">
          <span class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-sm font-black shadow-sm">
            MOV
          </span>
          Laporan Pergerakan Persediaan (Slow & Fast Moving)
        </h1>
        <p class="mt-1 text-xs text-gray-500">
          Analisis kecerdasan pergerakan stok untuk mengidentifikasi produk tanpa perputaran (Slow Moving) dan produk dengan permintaan tinggi (Fast Moving).
        </p>
      </div>

      <div class="mt-4 sm:mt-0 flex items-center gap-3">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 shadow-xs cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="isExporting || loading"
          @click="onExportCsv"
        >
          <svg
            class="w-4 h-4 text-gray-500"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
            />
          </svg>
          <span>{{ isExporting ? 'Mengekspor...' : 'Ekspor CSV' }}</span>
        </button>
      </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-xs space-y-4">
      <!-- Row 1: Period, Type, Location, Category, Unit -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
        <!-- Periode -->
        <div>
          <label
            for="filter-period"
            class="block text-xs font-semibold text-gray-700 mb-1"
          >Periode Analisis</label>
          <select
            id="filter-period"
            v-model="filters.period"
            class="w-full text-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            @change="applyFilters"
          >
            <option
              v-for="p in periodOptions"
              :key="p.value"
              :value="p.value"
            >
              {{ p.label }}
            </option>
          </select>
        </div>

        <!-- Tipe Analisis -->
        <div>
          <label
            for="filter-type"
            class="block text-xs font-semibold text-gray-700 mb-1"
          >Tipe Pergerakan</label>
          <select
            id="filter-type"
            v-model="filters.type"
            class="w-full text-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            @change="applyFilters"
          >
            <option value="slow-moving">
              Slow Moving (Dorman)
            </option>
            <option value="fast-moving">
              Fast Moving (Cepat)
            </option>
          </select>
        </div>

        <!-- Lokasi -->
        <div>
          <label
            for="filter-location"
            class="block text-xs font-semibold text-gray-700 mb-1"
          >Lokasi Gudang</label>
          <select
            id="filter-location"
            v-model="filters.location_id"
            class="w-full text-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            @change="applyFilters"
          >
            <option value="">
              Semua Lokasi Terotorisasi
            </option>
            <option
              v-for="loc in baseOptions.locations"
              :key="loc.id"
              :value="loc.id"
            >
              {{ loc.code }} - {{ loc.name }}
            </option>
          </select>
        </div>

        <!-- Kategori -->
        <div>
          <label
            for="filter-category"
            class="block text-xs font-semibold text-gray-700 mb-1"
          >Kategori Produk</label>
          <select
            id="filter-category"
            v-model="filters.category_id"
            class="w-full text-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            @change="applyFilters"
          >
            <option value="">
              Semua Kategori
            </option>
            <option
              v-for="cat in baseOptions.categories"
              :key="cat.id"
              :value="cat.id"
            >
              {{ cat.name }}
            </option>
          </select>
        </div>

        <!-- Satuan -->
        <div>
          <label
            for="filter-unit"
            class="block text-xs font-semibold text-gray-700 mb-1"
          >Satuan</label>
          <select
            id="filter-unit"
            v-model="filters.unit_id"
            class="w-full text-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            @change="applyFilters"
          >
            <option value="">
              Semua Satuan
            </option>
            <option
              v-for="u in baseOptions.units"
              :key="u.id"
              :value="u.id"
            >
              {{ u.code }} ({{ u.name }})
            </option>
          </select>
        </div>
      </div>

      <!-- Row 2: Search and Actions -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2 border-t border-gray-100">
        <div class="flex-1 max-w-md relative">
          <input
            id="filter-search"
            v-model="filters.search"
            type="text"
            placeholder="Cari SKU, Barcode, atau Nama Produk..."
            class="w-full text-xs rounded-lg border border-gray-300 bg-white pl-9 pr-3 py-2 text-gray-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            @keyup.enter="applyFilters"
          >
          <svg
            class="w-4 h-4 text-gray-400 absolute left-3 top-2.5"
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

        <div class="flex items-center gap-2">
          <button
            type="button"
            class="px-3.5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-xs cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500"
            @click="applyFilters"
          >
            Terapkan Filter
          </button>
          <button
            type="button"
            class="px-3 py-2 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-lg cursor-pointer"
            @click="resetFilters"
          >
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Movement Type Tabs & Active Summary -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-200 pb-3">
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="px-4 py-2 text-xs font-bold rounded-lg transition-colors cursor-pointer flex items-center gap-2"
          :class="filters.type === 'slow-moving' ? 'bg-slate-800 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          @click="switchType('slow-moving')"
        >
          <span>Slow Moving (Dorman)</span>
          <span
            class="px-1.5 py-0.5 rounded-full text-[10px]"
            :class="filters.type === 'slow-moving' ? 'bg-slate-700 text-slate-200' : 'bg-gray-200 text-gray-700'"
          >
            {{ meta?.summary?.slow_moving_count ?? 0 }}
          </span>
        </button>

        <button
          type="button"
          class="px-4 py-2 text-xs font-bold rounded-lg transition-colors cursor-pointer flex items-center gap-2"
          :class="filters.type === 'fast-moving' ? 'bg-emerald-700 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          @click="switchType('fast-moving')"
        >
          <span>Fast Moving (Cepat)</span>
          <span
            class="px-1.5 py-0.5 rounded-full text-[10px]"
            :class="filters.type === 'fast-moving' ? 'bg-emerald-800 text-emerald-100' : 'bg-gray-200 text-gray-700'"
          >
            {{ meta?.summary?.fast_moving_count ?? 0 }}
          </span>
        </button>
      </div>

      <div
        v-if="meta?.date_from && meta?.date_to"
        class="text-xs text-gray-500"
      >
        Rentang Tanggal Analisis: <span class="font-semibold text-gray-700">{{ meta.date_from }} s/d {{ meta.date_to }}</span> ({{ filters.period }} Hari)
      </div>
    </div>

    <!-- Error State -->
    <div
      v-if="error"
      class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 flex items-center justify-between"
    >
      <span>{{ error }}</span>
      <button
        type="button"
        class="underline font-semibold hover:text-rose-900 cursor-pointer"
        @click="fetchReport"
      >
        Coba Lagi
      </button>
    </div>

    <!-- Table Container -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
      <!-- Loading Overlay -->
      <div
        v-if="loading"
        class="p-8 text-center"
      >
        <div class="inline-block animate-spin w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full mb-2" />
        <p class="text-xs text-gray-500">
          Memuat data pergerakan persediaan...
        </p>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="!items.length"
        class="p-12 text-center"
      >
        <svg
          class="w-12 h-12 text-gray-300 mx-auto mb-3"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
          />
        </svg>
        <h3 class="text-sm font-semibold text-gray-900">
          Tidak ada data pergerakan ditemukan
        </h3>
        <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
          {{ filters.type === 'slow-moving'
            ? 'Seluruh produk memiliki pergerakan transaksi dalam periode yang dipilih.'
            : 'Tidak ada produk dengan transaksi pengeluaran (Issue) pada periode yang dipilih.'
          }}
        </p>
      </div>

      <!-- Data Table -->
      <div
        v-else
        class="overflow-x-auto"
      >
        <table class="min-w-full divide-y divide-gray-200 text-xs">
          <!-- 1. Slow Moving Columns -->
          <thead
            v-if="filters.type === 'slow-moving'"
            class="bg-gray-50"
          >
            <tr>
              <th
                scope="col"
                class="px-4 py-3 text-left font-semibold text-gray-700 cursor-pointer hover:bg-gray-100"
                @click="toggleSort('sku')"
              >
                SKU / Barcode {{ getSortIcon('sku') }}
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-left font-semibold text-gray-700 cursor-pointer hover:bg-gray-100"
                @click="toggleSort('product_name')"
              >
                Nama Produk {{ getSortIcon('product_name') }}
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-left font-semibold text-gray-700"
              >
                Kategori
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-left font-semibold text-gray-700"
              >
                Lokasi
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-right font-semibold text-gray-700 cursor-pointer hover:bg-gray-100"
                @click="toggleSort('current_stock')"
              >
                Stok Saat Ini {{ getSortIcon('current_stock') }}
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-left font-semibold text-gray-700 cursor-pointer hover:bg-gray-100"
                @click="toggleSort('last_movement_at')"
              >
                Mutasi Terakhir {{ getSortIcon('last_movement_at') }}
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-right font-semibold text-gray-700 cursor-pointer hover:bg-gray-100"
                @click="toggleSort('days_since_last_movement')"
              >
                Hari Tidak Bergerak {{ getSortIcon('days_since_last_movement') }}
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-center font-semibold text-gray-700"
              >
                Status
              </th>
            </tr>
          </thead>

          <!-- 2. Fast Moving Columns -->
          <thead
            v-else
            class="bg-gray-50"
          >
            <tr>
              <th
                scope="col"
                class="px-4 py-3 text-left font-semibold text-gray-700 cursor-pointer hover:bg-gray-100"
                @click="toggleSort('sku')"
              >
                SKU / Barcode {{ getSortIcon('sku') }}
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-left font-semibold text-gray-700 cursor-pointer hover:bg-gray-100"
                @click="toggleSort('product_name')"
              >
                Nama Produk {{ getSortIcon('product_name') }}
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-left font-semibold text-gray-700"
              >
                Kategori
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-left font-semibold text-gray-700"
              >
                Lokasi
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-right font-semibold text-gray-700 cursor-pointer hover:bg-gray-100"
                @click="toggleSort('current_stock')"
              >
                Stok Saat Ini {{ getSortIcon('current_stock') }}
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-right font-semibold text-gray-700 cursor-pointer hover:bg-gray-100"
                @click="toggleSort('total_outbound_quantity')"
              >
                Total Keluar {{ getSortIcon('total_outbound_quantity') }}
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-right font-semibold text-gray-700 cursor-pointer hover:bg-gray-100"
                @click="toggleSort('outbound_movement_count')"
              >
                Jml Transaksi {{ getSortIcon('outbound_movement_count') }}
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-right font-semibold text-gray-700 cursor-pointer hover:bg-gray-100"
                @click="toggleSort('velocity_score')"
              >
                Rata-rata Keluar / Hari {{ getSortIcon('velocity_score') }}
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-right font-semibold text-gray-700 cursor-pointer hover:bg-gray-100"
                @click="toggleSort('movement_days')"
              >
                Hari Aktif {{ getSortIcon('movement_days') }}
              </th>
              <th
                scope="col"
                class="px-4 py-3 text-center font-semibold text-gray-700"
              >
                Velocity
              </th>
            </tr>
          </thead>

          <!-- Table Body -->
          <tbody class="divide-y divide-gray-200 bg-white">
            <template v-if="filters.type === 'slow-moving'">
              <tr
                v-for="row in items"
                :key="`${row.product_id}-${row.location_id}`"
                class="hover:bg-gray-50/80"
              >
                <td class="px-4 py-3 font-mono text-gray-900 whitespace-nowrap">
                  <div class="font-semibold">
                    {{ row.sku }}
                  </div>
                  <div
                    v-if="row.barcode"
                    class="text-[10px] text-gray-400"
                  >
                    {{ row.barcode }}
                  </div>
                </td>
                <td class="px-4 py-3 font-medium text-gray-900">
                  {{ row.product_name }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ row.category_name || '-' }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-800">
                    {{ row.location_code }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right font-mono font-medium text-gray-900 whitespace-nowrap">
                  {{ row.current_stock }} {{ row.unit_symbol }}
                </td>
                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">
                  {{ formatDateTime(row.last_movement_at) }}
                </td>
                <td class="px-4 py-3 text-right font-semibold whitespace-nowrap">
                  <span
                    v-if="row.days_since_last_movement !== null"
                    class="text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200"
                  >
                    {{ row.days_since_last_movement }} Hari
                  </span>
                  <span
                    v-else
                    class="text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200"
                  >
                    Tidak pernah
                  </span>
                </td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-300">
                    DORMAN
                  </span>
                </td>
              </tr>
            </template>

            <template v-else>
              <tr
                v-for="row in items"
                :key="`${row.product_id}-${row.location_id}`"
                class="hover:bg-gray-50/80"
              >
                <td class="px-4 py-3 font-mono text-gray-900 whitespace-nowrap">
                  <div class="font-semibold">
                    {{ row.sku }}
                  </div>
                  <div
                    v-if="row.barcode"
                    class="text-[10px] text-gray-400"
                  >
                    {{ row.barcode }}
                  </div>
                </td>
                <td class="px-4 py-3 font-medium text-gray-900">
                  {{ row.product_name }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  {{ row.category_name || '-' }}
                </td>
                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-800">
                    {{ row.location_code }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right font-mono text-gray-700 whitespace-nowrap">
                  {{ row.current_stock }} {{ row.unit_symbol }}
                </td>
                <td class="px-4 py-3 text-right font-mono font-bold text-emerald-800 whitespace-nowrap">
                  {{ row.total_outbound_quantity }} {{ row.unit_symbol }}
                </td>
                <td class="px-4 py-3 text-right font-semibold text-gray-900 whitespace-nowrap">
                  {{ row.outbound_movement_count }}x
                </td>
                <td class="px-4 py-3 text-right font-mono font-semibold text-emerald-700 whitespace-nowrap">
                  {{ row.average_daily_outbound }} / hari
                </td>
                <td class="px-4 py-3 text-right text-gray-600 whitespace-nowrap">
                  {{ row.movement_days }} hari
                </td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    FAST
                  </span>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination Controls -->
      <div
        v-if="meta?.pagination?.total"
        class="bg-gray-50 px-4 py-3 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-gray-600"
      >
        <div>
          Menampilkan <span class="font-semibold text-gray-900">{{ meta.pagination.from ?? 0 }}</span> s/d
          <span class="font-semibold text-gray-900">{{ meta.pagination.to ?? 0 }}</span> dari
          <span class="font-semibold text-gray-900">{{ meta.pagination.total }}</span> produk
        </div>

        <div class="flex items-center gap-1.5 self-end sm:self-auto">
          <button
            type="button"
            class="px-2.5 py-1 rounded border border-gray-300 bg-white font-medium hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
            :disabled="meta.pagination.current_page <= 1 || loading"
            @click="changePage(meta.pagination.current_page - 1)"
          >
            &larr; Sebelumnya
          </button>

          <span class="px-2 font-semibold text-gray-800">
            Halaman {{ meta.pagination.current_page }} dari {{ meta.pagination.last_page }}
          </span>

          <button
            type="button"
            class="px-2.5 py-1 rounded border border-gray-300 bg-white font-medium hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
            :disabled="meta.pagination.current_page >= meta.pagination.last_page || loading"
            @click="changePage(meta.pagination.current_page + 1)"
          >
            Berikutnya &rarr;
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { reportingApi } from '../api/reportingApi';

const route = useRoute();
const router = useRouter();

const periodOptions = [
    { value: 30, label: '30 Hari Terakhir' },
    { value: 60, label: '60 Hari Terakhir' },
    { value: 90, label: '90 Hari Terakhir' },
    { value: 120, label: '120 Hari Terakhir' },
    { value: 180, label: '180 Hari Terakhir' },
    { value: 365, label: '365 Hari (1 Tahun)' },
];

const filters = reactive({
    type: 'slow-moving',
    period: 90,
    location_id: '',
    category_id: '',
    unit_id: '',
    search: '',
    sort_by: '',
    sort_order: 'desc',
    page: 1,
    per_page: 15,
});

const baseOptions = reactive({
    locations: [],
    categories: [],
    units: [],
});

const items = ref([]);
const meta = ref(null);
const loading = ref(false);
const error = ref(null);
const isExporting = ref(false);

onMounted(async () => {
    // 1. Sync query params from route
    if (route.query.type && ['slow-moving', 'fast-moving'].includes(route.query.type)) {
        filters.type = route.query.type;
    }
    if (route.query.period) {
        filters.period = Number(route.query.period);
    }
    if (route.query.location_id) {
        filters.location_id = route.query.location_id;
    }
    if (route.query.category_id) {
        filters.category_id = route.query.category_id;
    }
    if (route.query.search) {
        filters.search = route.query.search;
    }

    // Default sort based on type
    filters.sort_by = filters.type === 'fast-moving' ? 'velocity_score' : 'days_since_last_movement';

    await loadBaseOptions();
    await fetchReport();
});

watch(
    () => route.query,
    (newQuery) => {
        if (newQuery.type && newQuery.type !== filters.type) {
            filters.type = newQuery.type;
            filters.sort_by = filters.type === 'fast-moving' ? 'velocity_score' : 'days_since_last_movement';
            fetchReport();
        }
    }
);

async function loadBaseOptions() {
    try {
        const res = await reportingApi.getFilterBaseOptions();
        if (res?.data?.data) {
            baseOptions.locations = res.data.data.locations || [];
            baseOptions.categories = res.data.data.categories || [];
            baseOptions.units = res.data.data.units || [];
        }
    } catch (err) {
        console.error('Failed to load filter options:', err);
    }
}

async function fetchReport() {
    loading.value = true;
    error.value = null;

    try {
        const params = {
            type: filters.type,
            period: filters.period,
            page: filters.page,
            per_page: filters.per_page,
        };

        if (filters.location_id) params.location_id = filters.location_id;
        if (filters.category_id) params.category_id = filters.category_id;
        if (filters.unit_id) params.unit_id = filters.unit_id;
        if (filters.search) params.search = filters.search;
        if (filters.sort_by) params.sort_by = filters.sort_by;
        if (filters.sort_order) params.sort_order = filters.sort_order;

        const res = await reportingApi.getInventoryMovement(params);
        if (res?.data?.data) {
            items.value = res.data.data;
            meta.value = res.data.meta;
        }
    } catch (err) {
        console.error('Failed to load inventory movement report:', err);
        error.value = err.response?.data?.message || 'Gagal memuat laporan pergerakan persediaan.';
    } finally {
        loading.value = false;
    }
}

function applyFilters() {
    filters.page = 1;
    // Update router query cleanly
    router.replace({
        query: {
            ...route.query,
            type: filters.type,
            period: filters.period,
            location_id: filters.location_id || undefined,
            category_id: filters.category_id || undefined,
            search: filters.search || undefined,
        },
    });
    fetchReport();
}

function resetFilters() {
    filters.period = 90;
    filters.location_id = '';
    filters.category_id = '';
    filters.unit_id = '';
    filters.search = '';
    filters.page = 1;
    filters.sort_by = filters.type === 'fast-moving' ? 'velocity_score' : 'days_since_last_movement';
    filters.sort_order = 'desc';
    applyFilters();
}

function switchType(type) {
    if (filters.type === type) return;
    filters.type = type;
    filters.page = 1;
    filters.sort_by = type === 'fast-moving' ? 'velocity_score' : 'days_since_last_movement';
    filters.sort_order = 'desc';
    applyFilters();
}

function toggleSort(field) {
    if (filters.sort_by === field) {
        filters.sort_order = filters.sort_order === 'asc' ? 'desc' : 'asc';
    } else {
        filters.sort_by = field;
        filters.sort_order = 'desc';
    }
    filters.page = 1;
    fetchReport();
}

function getSortIcon(field) {
    if (filters.sort_by !== field) return '';
    return filters.sort_order === 'asc' ? '▲' : '▼';
}

function changePage(newPage) {
    filters.page = newPage;
    fetchReport();
}

async function onExportCsv() {
    isExporting.value = true;
    try {
        const params = {
            type: filters.type,
            period: filters.period,
        };
        if (filters.location_id) params.location_id = filters.location_id;
        if (filters.category_id) params.category_id = filters.category_id;
        if (filters.unit_id) params.unit_id = filters.unit_id;
        if (filters.search) params.search = filters.search;
        if (filters.sort_by) params.sort_by = filters.sort_by;
        if (filters.sort_order) params.sort_order = filters.sort_order;

        const res = await reportingApi.exportInventoryMovement(params);
        const blob = new Blob([res.data], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `${filters.type}-${filters.period}d-${new Date().toISOString().slice(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (err) {
        console.error('Failed to export inventory movement CSV:', err);
        alert('Gagal mengekspor laporan CSV.');
    } finally {
        isExporting.value = false;
    }
}

function formatDateTime(isoString) {
    if (!isoString) return 'Belum pernah';
    try {
        const d = new Date(isoString);
        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return isoString;
    }
}
</script>
