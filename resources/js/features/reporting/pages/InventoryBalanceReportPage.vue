<template>
  <div class="space-y-3">
    <!-- Top Header & Search Bar (Clean & Focused - Tanpa Filter di Halaman Depan) -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <!-- Title & Subtitle -->
        <div>
          <h1 class="text-base font-bold text-gray-900 leading-tight flex items-center gap-1.5">
            <svg
              class="w-4 h-4 text-emerald-600 shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
              />
            </svg>
            <span>Laporan Saldo Stok Global</span>
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Akumulasi total stok dan nilai persediaan per SKU, Kategori, dan Kondisi. Klik baris produk untuk melihat rincian lokasi penyimpanan.
          </p>
        </div>

        <!-- Controls: Search Input, Per Page & Export -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          <!-- Search Input -->
          <div class="relative w-full sm:w-64">
            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
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
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
              </svg>
            </div>
            <input
              id="search"
              v-model="filters.search"
              type="text"
              class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-8 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              placeholder="Cari SKU, Nama Produk, Kategori..."
            >
            <button
              v-if="filters.search"
              type="button"
              class="absolute inset-y-0 right-0 pr-2 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer"
              title="Hapus pencarian"
              @click="filters.search = ''"
            >
              &times;
            </button>
          </div>

          <!-- Per Page Select -->
          <select
            v-model="filters.per_page"
            class="rounded-lg border border-gray-300 bg-white py-1.5 pl-2 pr-6 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer text-gray-700"
          >
            <option value="15">
              15 / hal
            </option>
            <option value="25">
              25 / hal
            </option>
            <option value="50">
              50 / hal
            </option>
            <option value="100">
              100 / hal
            </option>
          </select>

          <!-- Refresh Button -->
          <button
            type="button"
            class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs hover:bg-gray-50 transition-colors cursor-pointer"
            :disabled="store.loading"
            title="Muat Ulang Data"
            @click="fetchData(store.meta?.current_page || 1)"
          >
            <svg
              class="w-3.5 h-3.5 text-gray-500"
              :class="{ 'animate-spin': store.loading }"
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
            <span class="hidden sm:inline">Refresh</span>
          </button>

          <!-- Export Control -->
          <ReportExportControl
            size="sm"
            :loading="exportStore.isExporting(reportKey)"
            :disabled="false"
            :error="exportStore.errorFor(reportKey)"
            :status="exportStore.statusFor(reportKey)"
            :validation-errors="exportStore.validationErrorsFor(reportKey)"
            :success-message="exportStore.successFor(reportKey)"
            @export="exportCsv"
            @dismiss="exportStore.clearFeedback(reportKey)"
          />
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div
      v-if="store.error"
      class="rounded-lg bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800 flex items-center justify-between shadow-2xs"
    >
      <div>
        <span class="font-semibold">Error memuat data: </span>
        <span>{{ store.error }}</span>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="font-semibold text-rose-700 hover:text-rose-900 bg-rose-100 px-2.5 py-1 rounded text-xs cursor-pointer"
          @click="fetchData(1)"
        >
          Coba Lagi
        </button>
        <button
          type="button"
          class="text-rose-500 hover:text-rose-700 text-xs font-semibold cursor-pointer"
          @click="store.error = null"
        >
          Tutup
        </button>
      </div>
    </div>

    <!-- Summary Metrics Cards (Rekap Kategori & Item, Total Qty, Total Rupiah) -->
    <div
      v-if="store.summary"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5"
    >
      <!-- 1. Card Rekap Kategori & Item -->
      <div class="bg-gradient-to-br from-slate-50 to-indigo-50/40 p-3 rounded-xl border border-indigo-100 shadow-2xs relative overflow-hidden flex flex-col justify-between group">
        <div>
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-bold text-indigo-900 uppercase tracking-wider flex items-center gap-1.5">
              <svg
                class="w-3.5 h-3.5 text-indigo-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                />
              </svg>
              <span>Kategori & Produk</span>
            </span>
            <button
              v-if="store.summary?.by_category?.length > 0"
              type="button"
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-600 hover:text-white transition-all shadow-2xs cursor-pointer"
              title="Buka rincian lengkap per kategori"
              @click="showCategoryModal = true"
            >
              <span>Rincian</span>
              <svg
                class="w-3 h-3"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </button>
          </div>
          <div class="mt-1.5 flex items-baseline gap-2">
            <span class="text-xl font-bold font-mono text-gray-900 tracking-tight">
              {{ store.summary?.total_categories ?? 0 }}
            </span>
            <span class="text-xs font-semibold text-gray-500">Kategori Aktif</span>
          </div>
        </div>
        <div class="mt-2 pt-2 border-t border-indigo-100/60 flex items-center justify-between text-[11px]">
          <span class="text-gray-600 flex items-center gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500" />
            Total Terdaftar:
          </span>
          <span class="font-bold font-mono text-gray-900 bg-white px-2 py-0.5 rounded border border-gray-200 shadow-2xs">
            {{ store.summary?.total_products ?? 0 }} SKU
          </span>
        </div>
      </div>

      <!-- 2. Card Total Kuantitas (Total Qty) -->
      <div class="bg-gradient-to-br from-emerald-50/60 to-teal-50/30 p-3 rounded-xl border border-emerald-200 shadow-2xs flex flex-col justify-between">
        <div>
          <div class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider flex items-center gap-1.5">
            <svg
              class="w-3.5 h-3.5 text-emerald-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
              />
            </svg>
            <span>Total Kuantitas Fisik</span>
          </div>
          <div class="mt-1.5 flex items-baseline gap-1.5">
            <span class="text-xl font-bold font-mono text-emerald-950 tracking-tight">
              {{ formatQuantity(store.summary?.total_quantity ?? 0) }}
            </span>
            <span class="text-xs font-semibold text-emerald-700">Total Unit</span>
          </div>
        </div>
        <div class="mt-2 pt-2 border-t border-emerald-100 flex items-center justify-between gap-1 text-[11px]">
          <div class="flex items-center gap-1">
            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-semibold font-mono text-[10px]">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-600" />
              {{ formatQuantity(store.summary?.good_quantity ?? 0) }} BAGUS
            </span>
          </div>
          <div class="flex items-center gap-1">
            <span
              class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded font-semibold font-mono text-[10px]"
              :class="Number(store.summary?.defective_quantity) > 0 ? 'bg-amber-100 text-amber-900 ring-1 ring-amber-400' : 'bg-gray-100 text-gray-500'"
            >
              <span
                class="w-1.5 h-1.5 rounded-full"
                :class="Number(store.summary?.defective_quantity) > 0 ? 'bg-amber-600' : 'bg-gray-400'"
              />
              {{ formatQuantity(store.summary?.defective_quantity ?? 0) }} RUSAK
            </span>
          </div>
        </div>
      </div>

      <!-- 3. Card Total Rupiah (Total Nilai Persediaan) -->
      <div class="bg-gradient-to-br from-indigo-50/70 to-blue-50/40 p-3 rounded-xl border border-indigo-200 shadow-2xs flex flex-col justify-between sm:col-span-2 lg:col-span-1">
        <div>
          <div class="text-[10px] font-bold text-indigo-800 uppercase tracking-wider flex items-center gap-1.5">
            <svg
              class="w-3.5 h-3.5 text-indigo-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <span>Total Nilai Persediaan</span>
          </div>
          <div class="mt-1.5 text-xl font-bold font-mono text-indigo-950 tracking-tight">
            {{ formatRupiah(store.summary?.total_value ?? 0) }}
          </div>
        </div>
        <div class="mt-2 pt-2 border-t border-indigo-100 flex items-center justify-between text-[11px] text-gray-500">
          <span>Nilai Saldo Fisik Aktif</span>
          <span class="text-[10px] font-mono text-indigo-700 bg-white px-2 py-0.5 rounded border border-indigo-100">
            Per Satuan Real-Time
          </span>
        </div>
      </div>
    </div>

    <!-- Main Data Table: Global Total per SKU, Kategori, Kondisi -->
    <div class="overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar relative">
      <div
        v-if="store.loading"
        class="absolute inset-0 bg-white/60 backdrop-blur-2xs z-20 flex items-center justify-center"
      >
        <div class="inline-flex items-center gap-2 text-indigo-600 font-semibold bg-white px-3.5 py-2 rounded-lg shadow-sm text-xs border border-gray-100">
          <svg
            class="w-4 h-4 animate-spin"
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
          <span>Memuat saldo stok global...</span>
        </div>
      </div>

      <table class="w-full text-left text-xs border-collapse">
        <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10">
          <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
            <th
              scope="col"
              class="py-2 px-2 w-8 text-center whitespace-nowrap"
            >
              No.
            </th>
            <th
              scope="col"
              class="py-2 px-3 whitespace-nowrap"
            >
              SKU / Produk
            </th>
            <th
              scope="col"
              class="py-2 px-3 whitespace-nowrap"
            >
              Kategori & Satuan
            </th>
            <th
              scope="col"
              class="py-2 px-2.5 text-center whitespace-nowrap"
            >
              Kondisi
            </th>
            <th
              scope="col"
              class="py-2 px-3 text-right whitespace-nowrap"
            >
              Harga Satuan
            </th>
            <th
              scope="col"
              class="py-2 px-3 text-right whitespace-nowrap"
            >
              Total Stok Global
            </th>
            <th
              scope="col"
              class="py-2 px-3 text-right whitespace-nowrap"
            >
              Total Nilai Global
            </th>
            <th
              scope="col"
              class="py-2 px-3 text-center whitespace-nowrap"
            >
              Sebaran Lokasi
            </th>
            <th
              scope="col"
              class="py-2 px-2.5 text-center whitespace-nowrap w-24"
            >
              Aksi
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <BaseTableEmpty
            v-if="!store.loading && store.data.length === 0"
            :colspan="9"
            message="Tidak ada data saldo stok ditemukan."
            :hint="filters.search ? `Coba ubah kata kunci pencarian &quot;${filters.search}&quot;.` : 'Belum ada data saldo stok pada filter yang dipilih.'"
          />

          <tr
            v-for="(item, index) in store.data"
            :key="item.product_id + '_' + item.condition"
            class="hover:bg-indigo-50/40 transition-colors cursor-pointer group"
            @click="openLocationDetail(item)"
          >
            <!-- 1. Nomor Baris -->
            <td class="py-2.5 px-2 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(store.meta, index) }}
            </td>

            <!-- 2. SKU / Produk -->
            <td class="py-2.5 px-3 text-[11px] whitespace-nowrap">
              <div class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors flex items-center gap-1.5">
                <span>{{ item.product_name }}</span>
                <svg
                  class="w-3 h-3 text-indigo-500 opacity-0 group-hover:opacity-100 transition-opacity shrink-0"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                  />
                </svg>
              </div>
              <div class="text-[10px] text-gray-500 font-mono flex items-center gap-1.5 mt-0.5">
                <span class="bg-gray-100 text-gray-700 px-1.5 py-0.2 rounded">{{ item.product_sku }}</span>
                <span
                  v-if="item.product_barcode"
                  class="text-gray-400"
                >&bull; {{ item.product_barcode }}</span>
              </div>
            </td>

            <!-- 3. Kategori & Satuan -->
            <td class="py-2.5 px-3 text-[11px] text-gray-700 whitespace-nowrap">
              <div class="font-medium text-gray-800">
                {{ item.category_name || '-' }}
              </div>
              <div class="text-[10px] text-gray-400 font-mono">
                Satuan: {{ item.unit_name || '-' }}
              </div>
            </td>

            <!-- 4. Kondisi -->
            <td class="py-2.5 px-2.5 text-[11px] text-center whitespace-nowrap">
              <span
                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider inline-flex items-center gap-1"
                :class="item.condition === 'DEFECTIVE' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20'"
              >
                <span
                  class="w-1.5 h-1.5 rounded-full"
                  :class="item.condition === 'DEFECTIVE' ? 'bg-amber-500' : 'bg-emerald-500'"
                />
                {{ item.condition === 'DEFECTIVE' ? 'RUSAK' : 'BAGUS' }}
              </span>
            </td>

            <!-- 5. Harga Satuan -->
            <td class="py-2.5 px-3 text-[11px] font-mono text-right text-gray-600 whitespace-nowrap">
              {{ formatRupiah(item.unit_price || 0) }}
            </td>

            <!-- 6. Total Stok Global -->
            <td class="py-2.5 px-3 text-[11px] font-mono text-right whitespace-nowrap">
              <span class="font-bold text-gray-900 text-xs">
                {{ formatQuantity(item.total_quantity || item.on_hand_quantity) }}
              </span>
              <span class="text-[10px] text-gray-500 ml-1">{{ item.unit_name }}</span>
            </td>

            <!-- 7. Total Nilai Global -->
            <td class="py-2.5 px-3 text-[11px] font-mono text-right font-bold text-indigo-700 whitespace-nowrap">
              {{ formatRupiah(item.total_value || 0) }}
            </td>

            <!-- 8. Sebaran Lokasi (Clickable Badge) -->
            <td class="py-2.5 px-3 text-center whitespace-nowrap">
              <span
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold transition-all"
                :class="(item.locations_count || item.locations?.length || 0) > 0 ? 'bg-indigo-50 text-indigo-700 border border-indigo-200/80 group-hover:bg-indigo-100' : 'bg-gray-100 text-gray-500'"
              >
                <svg
                  class="w-3.5 h-3.5 text-indigo-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                </svg>
                <span>{{ item.locations_count || item.locations?.length || 0 }} Lokasi</span>
              </span>
            </td>

            <!-- 9. Aksi Column -->
            <td
              class="py-2.5 px-2.5 text-center whitespace-nowrap"
              @click.stop
            >
              <button
                type="button"
                class="inline-flex items-center gap-1 rounded-md bg-white border border-gray-200 px-2 py-1 text-[10px] font-semibold text-gray-700 hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-700 transition-colors shadow-2xs cursor-pointer"
                title="Lihat rincian lokasi penyimpanan"
                @click="openLocationDetail(item)"
              >
                <svg
                  class="w-3 h-3 text-indigo-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
                <span>Rincian</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <BasePagination
      :pagination="store.meta"
      :loading="store.loading"
      @change="changePage"
    />

    <!-- MODAL DETAIL LOKASI PENYIMPANAN / PEMBAWA BARANG -->
    <div
      v-if="showDetailModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-gray-900/60 backdrop-blur-xs"
      @click.self="showDetailModal = false"
    >
      <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 max-w-3xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-150">
        <!-- Modal Header -->
        <div class="px-5 py-3.5 border-b border-gray-200 flex items-start justify-between bg-gray-50">
          <div>
            <div class="text-xs font-semibold text-indigo-600 uppercase tracking-wider flex items-center gap-1.5">
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
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
              <span>Rincian Lokasi Penyimpanan & Pemegang Unit</span>
            </div>
            <h2 class="text-base font-bold text-gray-900 leading-tight mt-0.5">
              {{ selectedItem?.product_name }}
            </h2>
            <div class="flex items-center gap-2 mt-1 text-[11px] text-gray-500 font-mono">
              <span class="bg-gray-200/80 text-gray-700 px-1.5 py-0.2 rounded font-semibold">{{ selectedItem?.product_sku }}</span>
              <span>&bull; {{ selectedItem?.category_name }}</span>
              <span>&bull;</span>
              <span
                class="px-1.5 py-0.2 rounded text-[10px] font-bold"
                :class="selectedItem?.condition === 'DEFECTIVE' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'"
              >
                {{ selectedItem?.condition === 'DEFECTIVE' ? 'KONDISI RUSAK' : 'KONDISI BAGUS' }}
              </span>
            </div>
          </div>

          <button
            type="button"
            class="text-gray-400 hover:text-gray-600 text-lg font-bold p-1 rounded-lg hover:bg-gray-200/50 cursor-pointer"
            @click="showDetailModal = false"
          >
            &times;
          </button>
        </div>

        <!-- Modal KPI Cards -->
        <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3 bg-gray-50/50 border-b border-gray-100 text-xs">
          <div class="bg-white p-3 rounded-xl border border-gray-200/80 shadow-2xs">
            <span class="text-[10px] text-gray-500 font-medium">Total Stok Tersebar</span>
            <div class="text-base font-bold text-gray-900 mt-0.5">
              {{ formatQuantity(selectedItem?.total_quantity) }}
              <span class="text-xs font-normal text-gray-500">{{ selectedItem?.unit_name }}</span>
            </div>
          </div>
          <div class="bg-white p-3 rounded-xl border border-gray-200/80 shadow-2xs">
            <span class="text-[10px] text-gray-500 font-medium">Total Nilai Persediaan</span>
            <div class="text-base font-bold text-indigo-700 mt-0.5">
              {{ formatRupiah(selectedItem?.total_value || 0) }}
            </div>
          </div>
          <div class="bg-white p-3 rounded-xl border border-gray-200/80 shadow-2xs">
            <span class="text-[10px] text-gray-500 font-medium">Jumlah Lokasi Aktif</span>
            <div class="text-base font-bold text-emerald-700 mt-0.5">
              {{ selectedItem?.locations?.length || 0 }} Lokasi
            </div>
          </div>
        </div>

        <!-- Modal Table Content -->
        <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
          <table class="min-w-full divide-y divide-gray-200 text-xs">
            <thead class="bg-gray-50 text-gray-600 font-semibold sticky top-0 shadow-2xs text-[11px]">
              <tr>
                <th
                  scope="col"
                  class="py-2 px-2.5 text-center w-8"
                >
                  No.
                </th>
                <th
                  scope="col"
                  class="py-2 px-3 text-left"
                >
                  Lokasi / Fasilitas
                </th>
                <th
                  scope="col"
                  class="py-2 px-3 text-left"
                >
                  Tipe Lokasi
                </th>
                <th
                  scope="col"
                  class="py-2 px-3 text-left"
                >
                  Dibawa Oleh / Personel
                </th>
                <th
                  scope="col"
                  class="py-2 px-3 text-right"
                >
                  Stok di Lokasi
                </th>
                <th
                  scope="col"
                  class="py-2 px-3 text-right"
                >
                  Subtotal Nilai
                </th>
                <th
                  scope="col"
                  class="py-2 px-2 text-center w-20"
                >
                  Kartu Stok
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <BaseTableEmpty
                v-if="!selectedItem?.locations || selectedItem.locations.length === 0"
                :colspan="7"
                message="Item ini saat ini belum tercatat memiliki saldo fisik di lokasi mana pun."
              />
              <tr
                v-for="(loc, lIdx) in selectedItem.locations"
                v-else
                :key="loc.location_id"
                class="hover:bg-gray-50 transition-colors"
              >
                <!-- No -->
                <td class="py-2 px-2.5 text-center text-gray-400 font-mono text-[10px]">
                  {{ lIdx + 1 }}
                </td>

                <!-- Lokasi -->
                <td class="py-2 px-3">
                  <div class="font-bold text-gray-900 flex items-center gap-1.5">
                    <svg
                      v-if="loc.is_field_personnel"
                      class="w-3.5 h-3.5 text-amber-600 shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                      />
                    </svg>
                    <svg
                      v-else
                      class="w-3.5 h-3.5 text-indigo-600 shrink-0"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                      />
                    </svg>
                    <span>{{ loc.location_name }}</span>
                  </div>
                  <div class="text-[10px] text-gray-400 font-mono">
                    {{ loc.location_code }}
                  </div>
                </td>

                <!-- Tipe Lokasi -->
                <td class="py-2 px-3">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold"
                    :class="loc.is_field_personnel ? 'bg-amber-50 text-amber-800 border border-amber-200/80' : 'bg-gray-100 text-gray-700'"
                  >
                    {{ loc.location_type_label || loc.location_type }}
                  </span>
                </td>

                <!-- Dibawa Oleh / Personel -->
                <td class="py-2 px-3">
                  <div
                    v-if="loc.personnel_name"
                    class="flex items-center gap-1.5"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0" />
                    <div>
                      <div class="font-semibold text-gray-900 text-[11px]">
                        {{ loc.personnel_name }}
                      </div>
                      <div class="text-[10px] text-gray-400 font-mono">
                        @{{ loc.personnel_username }}
                      </div>
                    </div>
                  </div>
                  <span
                    v-else
                    class="text-gray-400 text-[11px]"
                  >-</span>
                </td>

                <!-- Stok di Lokasi -->
                <td class="py-2 px-3 text-right font-mono">
                  <div class="font-bold text-gray-900 text-xs">
                    {{ formatQuantity(loc.quantity) }}
                    <span class="text-[10px] font-normal text-gray-500">{{ selectedItem?.unit_name }}</span>
                  </div>
                  <div
                    v-if="Number(selectedItem?.total_quantity) > 0"
                    class="text-[9px] text-gray-400"
                  >
                    {{ ((Number(loc.quantity) / Number(selectedItem.total_quantity)) * 100).toFixed(1) }}% dari total
                  </div>
                </td>

                <!-- Subtotal Nilai -->
                <td class="py-2 px-3 text-right font-mono font-semibold text-gray-700">
                  {{ formatRupiah(loc.total_value || 0) }}
                </td>

                <!-- Kartu Stok Action -->
                <td class="py-2 px-2 text-center whitespace-nowrap">
                  <router-link
                    :to="{
                      path: '/reports/stock-card',
                      query: {
                        product_id: selectedItem?.product_id,
                        location_id: loc.location_id,
                        sku: selectedItem?.product_sku,
                        name: selectedItem?.product_name
                      }
                    }"
                    class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-1 text-[10px] font-semibold text-indigo-700 hover:bg-indigo-100 hover:text-indigo-800 transition-colors shadow-2xs border border-indigo-100/80 cursor-pointer"
                    title="Buka Kartu Stok lokasi ini"
                  >
                    <svg
                      class="w-3 h-3 text-indigo-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                      />
                    </svg>
                    <span>Kartu</span>
                  </router-link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Modal Footer -->
        <div class="px-5 py-3 border-t border-gray-200 flex items-center justify-between bg-gray-50">
          <span class="text-[11px] text-gray-500">
            Klik tombol <strong>Kartu</strong> pada baris lokasi untuk melihat mutasi riwayat fisik.
          </span>
          <button
            type="button"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition-colors cursor-pointer"
            @click="showDetailModal = false"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL REKAPITULASI RINCIAN PER KATEGORI -->
    <div
      v-if="showCategoryModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-gray-900/60 backdrop-blur-xs"
      @click.self="showCategoryModal = false"
    >
      <div class="bg-white rounded-2xl shadow-2xl border border-gray-200 max-w-3xl w-full max-h-[85vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-150">
        <!-- Modal Header -->
        <div class="px-5 py-3.5 border-b border-gray-200 flex items-start justify-between bg-gradient-to-r from-gray-50 to-indigo-50/40">
          <div>
            <div class="text-xs font-semibold text-indigo-600 uppercase tracking-wider flex items-center gap-1.5">
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
                  d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                />
              </svg>
              <span>Rekapitulasi Persediaan per Kategori</span>
            </div>
            <p class="text-xs text-gray-600 mt-0.5">
              Rincian kuantitas fisik, jumlah SKU, dan nilai persediaan untuk setiap kategori barang.
            </p>
          </div>
          <button
            type="button"
            class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
            title="Tutup"
            @click="showCategoryModal = false"
          >
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <!-- Modal Body (Table) -->
        <div class="overflow-y-auto p-4 custom-scrollbar">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px] bg-gray-50/80">
                <th class="py-2 px-2.5 text-center w-10">
                  No.
                </th>
                <th class="py-2 px-3">
                  Nama Kategori
                </th>
                <th class="py-2 px-3 text-center">
                  Jumlah Item (SKU)
                </th>
                <th class="py-2 px-3 text-right">
                  Total Kuantitas
                </th>
                <th class="py-2 px-3 text-right">
                  Total Nilai (Rp)
                </th>
                <th class="py-2 px-3 text-center w-24">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="(cat, idx) in store.summary?.by_category || []"
                :key="cat.category_id || idx"
                class="hover:bg-indigo-50/30 transition-colors"
              >
                <td class="py-2.5 px-2.5 text-center font-mono text-gray-400 text-[11px]">
                  {{ idx + 1 }}
                </td>
                <td class="py-2.5 px-3 font-semibold text-gray-900">
                  <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-indigo-500" />
                    <span>{{ cat.category_name }}</span>
                  </div>
                </td>
                <td class="py-2.5 px-3 text-center font-mono font-bold text-gray-800">
                  {{ cat.item_count }} SKU
                </td>
                <td class="py-2.5 px-3 text-right font-mono">
                  <div class="font-bold text-gray-900">
                    {{ formatQuantity(cat.total_quantity) }}
                  </div>
                  <div class="text-[10px] text-gray-400">
                    {{ formatQuantity(cat.good_quantity) }} Bagus &bull; {{ formatQuantity(cat.defective_quantity) }} Rusak
                  </div>
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-indigo-700">
                  {{ formatRupiah(cat.total_value) }}
                  <div
                    v-if="store.summary?.total_value > 0"
                    class="text-[10px] font-normal text-gray-400"
                  >
                    {{ ((cat.total_value / store.summary.total_value) * 100).toFixed(1) }}% aset
                  </div>
                </td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-indigo-50 text-indigo-700 hover:bg-indigo-600 hover:text-white font-semibold text-[10px] transition-colors shadow-2xs border border-indigo-200/80 cursor-pointer"
                    title="Filter tabel berdasarkan kategori ini"
                    @click="filterByCategory(cat.category_name)"
                  >
                    <svg
                      class="w-3 h-3"
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
                  </button>
                </td>
              </tr>
            </tbody>
            <tfoot class="border-t-2 border-gray-200 bg-gray-50/90 font-semibold text-gray-900 text-xs">
              <tr>
                <td
                  colspan="2"
                  class="py-2.5 px-3 text-right"
                >
                  Total Keseluruhan:
                </td>
                <td class="py-2.5 px-3 text-center font-mono font-bold">
                  {{ store.summary?.total_products ?? 0 }} SKU
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-800">
                  {{ formatQuantity(store.summary?.total_quantity ?? 0) }}
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-indigo-900">
                  {{ formatRupiah(store.summary?.total_value ?? 0) }}
                </td>
                <td />
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Modal Footer -->
        <div class="px-5 py-3 border-t border-gray-200 flex items-center justify-between bg-gray-50">
          <span class="text-[11px] text-gray-500">
            Klik tombol <strong>Filter</strong> pada baris kategori untuk menampilkan item dari kategori tersebut.
          </span>
          <button
            type="button"
            class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition-colors cursor-pointer"
            @click="showCategoryModal = false"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, watch, reactive, ref } from 'vue';
import { useInventoryBalanceReportStore } from '../stores/useInventoryBalanceReportStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import ReportExportControl from '../components/ReportExportControl.vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import BaseTableEmpty from '@/shared/components/BaseTableEmpty.vue';
import { formatRupiah, formatQuantity, rowNumber } from '@/shared/utils/formatters.js';

const store = useInventoryBalanceReportStore();
const exportStore = useReportCsvExportStore();
const reportKey = 'inventory-balances';

// Modal Detail State
const showDetailModal = ref(false);
const selectedItem = ref(null);

function openLocationDetail(item) {
    selectedItem.value = item;
    showDetailModal.value = true;
}

// Modal Category Summary State
const showCategoryModal = ref(false);

function filterByCategory(categoryName) {
    if (!categoryName || categoryName === 'Tanpa Kategori') return;
    filters.search = categoryName;
    showCategoryModal.value = false;
}

// Filter State: Clean, focused on Search & Per Page (Tanpa filter di halaman depan)
const filters = reactive({
    search: '',
    per_page: '15',
});

let debounceTimer = null;
const debouncedFetch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchData(1), 300);
};

watch(() => filters.search, debouncedFetch);
watch(() => filters.per_page, () => fetchData(1));

const fetchData = (page = 1) => {
    store.fetchBalances({
        page,
        view_mode: 'grouped',
        search: filters.search ? filters.search.trim() : '',
        per_page: filters.per_page,
    });
};

const exportCsv = async (format = 'xlsx') => {
    const exportFormat = typeof format === 'string' && (format.toLowerCase() === 'csv' || format.toLowerCase() === 'xlsx')
        ? format.toLowerCase()
        : 'xlsx';
    await exportStore.exportReport(reportKey, {
        search: filters.search ? filters.search.trim() : '',
        format: exportFormat,
    });
};

const changePage = (page) => {
    if (!store.meta || page < 1 || page > store.meta.last_page) {
        return;
    }
    fetchData(page);
};

onMounted(() => {
    fetchData(1);
});
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
