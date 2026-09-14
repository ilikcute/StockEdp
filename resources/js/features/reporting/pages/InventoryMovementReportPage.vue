<template>
  <div class="space-y-3">
    <!-- Top Header & Filter Toolbar (Compact) -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs space-y-2.5">
      <!-- Primary Header Row: Title & Primary Controls -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <!-- Title & Subtitle -->
        <div class="shrink-0">
          <h1 class="text-base font-bold text-gray-900 leading-tight flex items-center gap-1.5 whitespace-nowrap">
            <svg
              class="w-4 h-4 text-indigo-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
              />
            </svg>
            Laporan Pergerakan Persediaan (Slow & Fast Moving)
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Analisis perputaran stok: Slow Moving (dorman) vs Fast Moving (permintaan tinggi).
          </p>
        </div>

        <!-- Primary Controls (Tabs, Search, Period, Filter Toggle, Reset, Export) -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          <!-- Movement Type Switcher (Pill Tabs) -->
          <div class="inline-flex rounded-lg bg-gray-100 p-0.5 text-xs font-semibold shrink-0">
            <button
              type="button"
              class="px-2.5 py-1 rounded-md transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
              :class="filters.type === 'slow-moving' ? 'bg-slate-800 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900'"
              @click="switchType('slow-moving')"
            >
              <span>Slow Moving</span>
              <span
                class="px-1.5 py-0.2 rounded-full text-[10px]"
                :class="filters.type === 'slow-moving' ? 'bg-slate-700 text-slate-200' : 'bg-gray-200 text-gray-700'"
              >
                {{ meta?.summary?.slow_moving_count ?? 0 }}
              </span>
            </button>
            <button
              type="button"
              class="px-2.5 py-1 rounded-md transition-all flex items-center gap-1.5 cursor-pointer whitespace-nowrap"
              :class="filters.type === 'fast-moving' ? 'bg-emerald-700 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900'"
              @click="switchType('fast-moving')"
            >
              <span>Fast Moving</span>
              <span
                class="px-1.5 py-0.2 rounded-full text-[10px]"
                :class="filters.type === 'fast-moving' ? 'bg-emerald-800 text-emerald-100' : 'bg-gray-200 text-gray-700'"
              >
                {{ meta?.summary?.fast_moving_count ?? 0 }}
              </span>
            </button>
          </div>

          <!-- Search Input -->
          <div class="w-full sm:w-44">
            <input
              id="filter-search"
              v-model="filters.search"
              type="text"
              placeholder="Cari SKU, Barcode, nama..."
              class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 px-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              @input="debouncedSearch"
              @keyup.enter="applyFilters"
            >
          </div>

          <!-- Quick Period Select -->
          <select
            id="filter-period"
            v-model="filters.period"
            class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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

          <!-- Toggle Advanced Filters -->
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium shadow-2xs transition-colors cursor-pointer whitespace-nowrap',
              showAdvancedFilters || activeExtraFiltersCount > 0
                ? 'bg-indigo-50 border-indigo-200 text-indigo-700 hover:bg-indigo-100'
                : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
            ]"
            @click="showAdvancedFilters = !showAdvancedFilters"
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
            <span>Filter</span>
            <span
              v-if="activeExtraFiltersCount > 0"
              class="inline-flex items-center justify-center px-1.5 py-0.2 text-[10px] font-bold text-white bg-indigo-600 rounded-full"
            >
              {{ activeExtraFiltersCount }}
            </span>
          </button>

          <!-- Reset Filter -->
          <button
            v-if="isAnyFilterActive"
            type="button"
            class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 shadow-2xs cursor-pointer whitespace-nowrap"
            title="Reset Filter"
            @click="resetFilters"
          >
            Reset
          </button>

          <!-- Ekspor CSV Button -->
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-700 hover:bg-emerald-800 shadow-2xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
            :disabled="isExporting || loading"
            @click="onExportCsv"
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
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
              />
            </svg>
            <span>{{ isExporting ? 'Mengekspor...' : 'Ekspor CSV' }}</span>
          </button>
        </div>
      </div>

      <!-- Secondary Row: Advanced Filters (Location, Category, Unit, Sorting, Paging) -->
      <div
        v-show="showAdvancedFilters"
        class="border-t border-gray-100 pt-2 flex flex-wrap items-center justify-between gap-2.5 text-xs"
      >
        <div class="flex items-center gap-2 flex-wrap">
          <!-- Lokasi -->
          <div class="flex items-center gap-1">
            <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Lokasi:</span>
            <select
              id="filter-location"
              v-model="filters.location_id"
              class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 max-w-[180px]"
              @change="applyFilters"
            >
              <option value="">
                Semua Lokasi
              </option>
              <option
                v-for="loc in baseOptions.locations"
                :key="loc.id"
                :value="loc.id"
              >
                {{ loc.code ? loc.code + ' — ' : '' }}{{ loc.name }}
              </option>
            </select>
          </div>

          <!-- Kategori -->
          <div class="flex items-center gap-1">
            <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Kategori:</span>
            <select
              id="filter-category"
              v-model="filters.category_id"
              class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 max-w-[180px]"
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
          <div class="flex items-center gap-1">
            <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Satuan:</span>
            <select
              id="filter-unit"
              v-model="filters.unit_id"
              class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
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

        <!-- Sorting & Paging -->
        <div class="flex items-center gap-1.5 flex-wrap ml-auto">
          <span class="text-[11px] text-gray-500 font-medium whitespace-nowrap">Urutkan:</span>
          <select
            v-model="filters.sort_by"
            class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            @change="applyFilters"
          >
            <template v-if="filters.type === 'slow-moving'">
              <option value="days_since_last_movement">
                Hari Tidak Bergerak
              </option>
              <option value="current_stock">
                Stok Saat Ini
              </option>
              <option value="unit_price">
                Harga Satuan
              </option>
              <option value="sku">
                SKU
              </option>
              <option value="product_name">
                Nama Produk
              </option>
            </template>
            <template v-else>
              <option value="velocity_score">
                Rata-rata Keluar / Hari
              </option>
              <option value="total_outbound_quantity">
                Total Keluar
              </option>
              <option value="outbound_movement_count">
                Jumlah Transaksi
              </option>
              <option value="movement_days">
                Hari Aktif
              </option>
              <option value="current_stock">
                Stok Saat Ini
              </option>
              <option value="unit_price">
                Harga Satuan
              </option>
              <option value="sku">
                SKU
              </option>
              <option value="product_name">
                Nama Produk
              </option>
            </template>
          </select>
          <select
            v-model="filters.sort_order"
            class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            @change="applyFilters"
          >
            <option value="desc">
              Desc
            </option>
            <option value="asc">
              Asc
            </option>
          </select>
          <select
            v-model="filters.per_page"
            class="block rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            @change="applyFilters"
          >
            <option :value="15">
              15 / hal
            </option>
            <option :value="50">
              50 / hal
            </option>
            <option :value="100">
              100 / hal
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Active Summary Info Bar -->
    <div
      v-if="meta?.date_from && meta?.date_to"
      class="flex items-center justify-between text-xs text-gray-500 bg-white px-3.5 py-1.5 rounded-xl border border-gray-200 shadow-2xs"
    >
      <div class="flex items-center gap-2 flex-wrap">
        <span class="text-[11px] text-gray-400">Mode:</span>
        <span
          class="font-bold text-[11px] uppercase tracking-wider"
          :class="filters.type === 'slow-moving' ? 'text-slate-800' : 'text-emerald-700'"
        >
          {{ filters.type === 'slow-moving' ? 'Slow Moving (Dorman)' : 'Fast Moving (Cepat)' }}
        </span>
        <span
          class="px-1.5 py-0.2 rounded-full text-[10px] font-bold font-mono"
          :class="filters.type === 'slow-moving' ? 'bg-slate-100 text-slate-800 border border-slate-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200'"
        >
          {{ (filters.type === 'slow-moving' ? meta?.summary?.slow_moving_count : meta?.summary?.fast_moving_count) ?? 0 }} Produk
        </span>
        <span
          v-if="selectedLocationName"
          class="inline-flex items-center gap-1 text-[11px] text-gray-600 font-medium"
        >
          &bull; Lokasi: <strong class="text-gray-900">{{ selectedLocationName }}</strong>
        </span>
      </div>
      <div class="text-[11px]">
        Rentang Analisis: <span class="font-semibold text-gray-800 font-mono">{{ meta.date_from }} s/d {{ meta.date_to }}</span> ({{ filters.period }} Hari)
      </div>
    </div>

    <!-- Error Alert -->
    <div
      v-if="error"
      class="rounded-lg bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800 flex items-center justify-between shadow-2xs"
    >
      <div class="flex items-center gap-2">
        <svg
          class="w-4 h-4 text-rose-600 flex-shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>{{ error }}</span>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="font-semibold text-rose-700 hover:text-rose-900 bg-rose-100 px-2.5 py-1 rounded text-xs cursor-pointer"
          @click="fetchReport"
        >
          Coba Lagi
        </button>
        <button
          type="button"
          class="text-rose-500 hover:text-rose-700 text-xs font-semibold cursor-pointer"
          @click="error = null"
        >
          Tutup
        </button>
      </div>
    </div>

    <!-- Data Table -->
    <div class="overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar relative">
      <div
        v-if="loading && items.length > 0"
        class="absolute inset-0 bg-white/50 z-20 flex items-center justify-center"
      >
        <span class="text-indigo-600 font-medium bg-white px-3 py-1.5 rounded-lg shadow-sm text-xs">Memuat data...</span>
      </div>

      <table class="w-full text-left text-xs border-collapse">
        <!-- 1. Slow Moving Columns -->
        <thead
          v-if="filters.type === 'slow-moving'"
          class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10"
        >
          <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
            <th
              scope="col"
              class="py-1.5 px-2 text-center whitespace-nowrap w-10 text-gray-500 font-semibold"
            >
              No.
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-left whitespace-nowrap cursor-pointer hover:bg-gray-100"
              @click="toggleSort('sku')"
            >
              SKU / Barcode {{ getSortIcon('sku') }}
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-left whitespace-nowrap cursor-pointer hover:bg-gray-100"
              @click="toggleSort('product_name')"
            >
              Nama Produk {{ getSortIcon('product_name') }}
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-left whitespace-nowrap"
            >
              Kategori
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-left whitespace-nowrap"
            >
              Lokasi
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap cursor-pointer hover:bg-gray-100 text-gray-600 font-semibold"
              @click="toggleSort('unit_price')"
            >
              Harga Satuan {{ getSortIcon('unit_price') }}
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap cursor-pointer hover:bg-gray-100"
              @click="toggleSort('current_stock')"
            >
              Stok Saat Ini {{ getSortIcon('current_stock') }}
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap text-gray-600 font-semibold"
            >
              Total Nilai (Rp)
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-left whitespace-nowrap cursor-pointer hover:bg-gray-100"
              @click="toggleSort('last_movement_at')"
            >
              Mutasi Terakhir {{ getSortIcon('last_movement_at') }}
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap cursor-pointer hover:bg-gray-100"
              @click="toggleSort('days_since_last_movement')"
            >
              Hari Tidak Bergerak {{ getSortIcon('days_since_last_movement') }}
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-center whitespace-nowrap"
            >
              Status
            </th>
          </tr>
        </thead>

        <!-- 2. Fast Moving Columns -->
        <thead
          v-else
          class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10"
        >
          <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
            <th
              scope="col"
              class="py-1.5 px-2 text-center whitespace-nowrap w-10 text-gray-500 font-semibold"
            >
              No.
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-left whitespace-nowrap cursor-pointer hover:bg-gray-100"
              @click="toggleSort('sku')"
            >
              SKU / Barcode {{ getSortIcon('sku') }}
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-left whitespace-nowrap cursor-pointer hover:bg-gray-100"
              @click="toggleSort('product_name')"
            >
              Nama Produk {{ getSortIcon('product_name') }}
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-left whitespace-nowrap"
            >
              Kategori
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-left whitespace-nowrap"
            >
              Lokasi
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap cursor-pointer hover:bg-gray-100 text-gray-600 font-semibold"
              @click="toggleSort('unit_price')"
            >
              Harga Satuan {{ getSortIcon('unit_price') }}
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap cursor-pointer hover:bg-gray-100"
              @click="toggleSort('current_stock')"
            >
              Stok Saat Ini {{ getSortIcon('current_stock') }}
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap text-gray-600 font-semibold"
            >
              Total Nilai (Rp)
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap cursor-pointer hover:bg-gray-100"
              @click="toggleSort('total_outbound_quantity')"
            >
              Total Keluar {{ getSortIcon('total_outbound_quantity') }}
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap cursor-pointer hover:bg-gray-100"
              @click="toggleSort('outbound_movement_count')"
            >
              Jml Transaksi {{ getSortIcon('outbound_movement_count') }}
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap cursor-pointer hover:bg-gray-100"
              @click="toggleSort('velocity_score')"
            >
              Rata-rata Keluar / Hari {{ getSortIcon('velocity_score') }}
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap cursor-pointer hover:bg-gray-100"
              @click="toggleSort('movement_days')"
            >
              Hari Aktif {{ getSortIcon('movement_days') }}
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-center whitespace-nowrap"
            >
              Velocity
            </th>
          </tr>
        </thead>

        <!-- Table Body -->
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="loading && items.length === 0">
            <td
              :colspan="filters.type === 'slow-moving' ? 11 : 13"
              class="py-8 text-center text-xs text-gray-400"
            >
              Memuat data pergerakan persediaan...
            </td>
          </tr>
          <tr v-else-if="!loading && items.length === 0">
            <td
              :colspan="filters.type === 'slow-moving' ? 11 : 13"
              class="py-12 text-center text-xs text-gray-400"
            >
              <svg
                class="w-10 h-10 text-gray-300 mx-auto mb-2"
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
              <div class="text-xs font-semibold text-gray-700">
                Tidak ada data pergerakan ditemukan
              </div>
              <p class="text-[11px] text-gray-500 mt-0.5 max-w-sm mx-auto">
                {{ filters.type === 'slow-moving'
                  ? 'Seluruh produk memiliki pergerakan transaksi dalam periode yang dipilih.'
                  : 'Tidak ada produk dengan transaksi pengeluaran (Issue) pada periode yang dipilih.'
                }}
              </p>
            </td>
          </tr>
          <template v-else-if="filters.type === 'slow-moving'">
            <tr
              v-for="(row, idx) in items"
              :key="`${row.product_id}-${row.location_id}`"
              class="hover:bg-gray-50/80 transition-colors"
            >
              <td class="py-1.5 px-2 text-center font-mono text-[11px] text-gray-400 whitespace-nowrap">
                {{ getRowNumber(idx) }}
              </td>
              <td class="py-1.5 px-2 font-mono text-[11px] text-gray-900 whitespace-nowrap">
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
              <td class="py-1.5 px-2 text-[11px] font-medium text-gray-900 whitespace-nowrap">
                {{ row.product_name }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
                {{ row.category_name || '-' }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-800">
                  {{ row.location_code }}
                </span>
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right font-mono text-gray-700 whitespace-nowrap">
                {{ formatRupiah(row.unit_price) }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right font-mono font-medium text-gray-900 whitespace-nowrap">
                {{ formatQuantity(row.current_stock) }} {{ row.unit_symbol }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right font-mono font-bold text-indigo-700 whitespace-nowrap">
                {{ formatRupiah(row.total_value ?? (Number(row.current_stock) * Number(row.unit_price || 0))) }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-gray-500 whitespace-nowrap font-mono">
                {{ formatTimestamp(row.last_movement_at) }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right font-semibold whitespace-nowrap">
                <span
                  v-if="row.days_since_last_movement !== null"
                  class="text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200 text-[10px]"
                >
                  {{ row.days_since_last_movement }} Hari
                </span>
                <span
                  v-else
                  class="text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200 text-[10px]"
                >
                  Tidak pernah
                </span>
              </td>
              <td class="py-1.5 px-2 text-center whitespace-nowrap">
                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider inline-flex items-center bg-slate-100 text-slate-700 border border-slate-300">
                  DORMAN
                </span>
              </td>
            </tr>
          </template>

          <template v-else>
            <tr
              v-for="(row, idx) in items"
              :key="`${row.product_id}-${row.location_id}`"
              class="hover:bg-gray-50/80 transition-colors"
            >
              <td class="py-1.5 px-2 text-center font-mono text-[11px] text-gray-400 whitespace-nowrap">
                {{ getRowNumber(idx) }}
              </td>
              <td class="py-1.5 px-2 font-mono text-[11px] text-gray-900 whitespace-nowrap">
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
              <td class="py-1.5 px-2 text-[11px] font-medium text-gray-900 whitespace-nowrap">
                {{ row.product_name }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
                {{ row.category_name || '-' }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-gray-600 whitespace-nowrap">
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-800">
                  {{ row.location_code }}
                </span>
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right font-mono text-gray-700 whitespace-nowrap">
                {{ formatRupiah(row.unit_price) }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right font-mono text-gray-700 whitespace-nowrap">
                {{ formatQuantity(row.current_stock) }} {{ row.unit_symbol }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right font-mono font-bold text-indigo-700 whitespace-nowrap">
                {{ formatRupiah(row.total_value ?? (Number(row.current_stock) * Number(row.unit_price || 0))) }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right font-mono font-bold text-emerald-800 whitespace-nowrap">
                {{ formatQuantity(row.total_outbound_quantity) }} {{ row.unit_symbol }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right font-semibold text-gray-900 whitespace-nowrap">
                {{ row.outbound_movement_count }}x
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right font-mono font-semibold text-emerald-700 whitespace-nowrap">
                {{ Number(row.average_daily_outbound).toLocaleString('id-ID', { maximumFractionDigits: 2 }) }} / hari
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right text-gray-600 whitespace-nowrap">
                {{ row.movement_days }} hari
              </td>
              <td class="py-1.5 px-2 text-center whitespace-nowrap">
                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider inline-flex items-center bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20">
                  FAST
                </span>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <BasePagination
      :pagination="meta?.pagination"
      :loading="loading"
      @change="changePage"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { reportingApi } from '../api/reportingApi';
import BasePagination from '@/shared/components/BasePagination.vue';
import { showToast } from '@/shared/utils/use_toast.js';
import { formatTimestamp, formatRupiah, formatQuantity, rowNumber } from '@/shared/utils/formatters.js';

const route = useRoute();
const router = useRouter();

const showAdvancedFilters = ref(false);

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

const selectedLocationName = computed(() => {
    if (!filters.location_id) return null;
    const loc = baseOptions.locations.find((l) => String(l.id) === String(filters.location_id));
    return loc ? (loc.code ? `${loc.code} — ${loc.name}` : loc.name) : null;
});

const activeExtraFiltersCount = computed(() => {
    let count = 0;
    if (filters.location_id) count++;
    if (filters.category_id) count++;
    if (filters.unit_id) count++;
    if (filters.per_page !== 15) count++;
    return count;
});

const isAnyFilterActive = computed(() => {
    return !!(
        filters.search ||
        filters.location_id ||
        filters.category_id ||
        filters.unit_id ||
        filters.period !== 90 ||
        filters.per_page !== 15
    );
});

let searchDebounceTimer = null;
function debouncedSearch() {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
        applyFilters();
    }, 300);
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
            unit_id: filters.unit_id || undefined,
            search: filters.search || undefined,
        },
    });
    fetchReport();
}

function resetFilters() {
    clearTimeout(searchDebounceTimer);
    filters.period = 90;
    filters.location_id = '';
    filters.category_id = '';
    filters.unit_id = '';
    filters.search = '';
    filters.page = 1;
    filters.per_page = 15;
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

function getRowNumber(index) {
    return rowNumber(meta.value?.pagination, index);
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
        showToast('Gagal mengekspor laporan CSV.', { type: 'error', title: 'Ekspor CSV' });
    } finally {
        isExporting.value = false;
    }
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
