<template>
  <div class="space-y-2.5">
    <!-- Top Compact Header Bar & KPI Summary -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
      <!-- Left: Title & Status -->
      <div class="flex items-center gap-2.5">
        <router-link
          :to="isEdit ? `/inventory/issues/${route.params.id}` : '/inventory/issues'"
          class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer"
          title="Kembali ke daftar pengeluaran"
        >
          <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
        </router-link>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-base font-bold text-gray-900 leading-tight">
              {{ isEdit ? 'Edit Draft Pengeluaran' : 'Pengeluaran Stok (Goods Issue)' }}
            </h1>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-50 text-amber-700 border border-amber-200 uppercase tracking-wide">
              {{ isEdit ? 'Edit Draft' : 'Draft Baru' }}
            </span>
          </div>
          <p class="text-[11px] text-gray-500 hidden sm:block">
            Input cepat barcode & pencatatan mutasi barang keluar.
          </p>
        </div>
      </div>

      <!-- Middle: KPI Summary Chips -->
      <div class="flex items-center gap-2 flex-wrap">
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs">
          <span class="text-gray-500 font-medium">Item:</span>
          <span class="font-bold text-gray-800 font-mono">{{ form.items.length }}</span>
        </div>
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 border border-amber-200 rounded-lg text-xs">
          <span class="text-amber-700 font-medium">Total Qty:</span>
          <span class="font-black text-amber-800 font-mono">{{ formatQuantity(totalItemsQty) }}</span>
        </div>
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-50 border border-indigo-200 rounded-lg text-xs">
          <span class="text-indigo-700 font-medium">Nilai Keluar:</span>
          <span class="font-black text-indigo-900 font-mono">{{ formatRupiah(grandTotalAmount) }}</span>
        </div>
      </div>

      <!-- Right: Main Actions -->
      <div class="flex items-center gap-2 self-end md:self-auto">
        <router-link
          :to="isEdit ? `/inventory/issues/${route.params.id}` : '/inventory/issues'"
          class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 border border-gray-300 shadow-2xs hover:bg-gray-50 transition-colors cursor-pointer"
        >
          Batal
        </router-link>
        <button
          id="btn-save-issue-draft"
          type="button"
          :disabled="isSubmitting"
          class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50 transition-colors cursor-pointer"
          title="Simpan Dokumen Draft (Shortcut: F9)"
          @click="submitForm"
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
              d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"
            />
          </svg>
          <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Draft' }}</span>
          <kbd class="hidden sm:inline-block font-mono text-[9px] bg-indigo-700/90 text-indigo-100 px-1 py-0.2 rounded border border-indigo-400 font-bold">F9</kbd>
        </button>
      </div>
    </div>

    <!-- Error Alert -->
    <div
      v-if="errorMsg"
      class="rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-xs text-rose-800 flex items-center justify-between gap-2"
    >
      <div class="flex items-center gap-2">
        <svg
          class="w-4 h-4 text-rose-500 shrink-0"
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
        <span>{{ errorMsg }}</span>
      </div>
      <button
        type="button"
        class="text-rose-400 hover:text-rose-600 cursor-pointer"
        @click="errorMsg = ''"
      >
        &times;
      </button>
    </div>

    <!-- Form Section -->
    <form
      class="space-y-2.5"
      @submit.prevent="submitForm"
    >
      <!-- Document Metadata Strip (Single Horizontal Grid) -->
      <div class="bg-white rounded-xl border border-gray-200 p-2.5 sm:p-3 shadow-2xs">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
          <!-- Tanggal Pengeluaran -->
          <div>
            <label
              for="date"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Tanggal Pengeluaran *
            </label>
            <input
              id="date"
              v-model="form.date"
              type="date"
              class="block w-full rounded-lg border-gray-300 py-1.5 px-2.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
              required
            >
          </div>

          <!-- Tujuan / Alasan -->
          <div>
            <label
              for="purpose"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Tujuan / Alasan *
            </label>
            <input
              id="purpose"
              v-model="form.purpose"
              type="text"
              placeholder="Contoh: Produksi SPK-001, Pemakaian Internal..."
              class="block w-full rounded-lg border-gray-300 py-1.5 px-2.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
              required
            >
          </div>

          <!-- Catatan -->
          <div class="sm:col-span-2">
            <label
              for="notes"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Catatan
            </label>
            <input
              id="notes"
              v-model="form.notes"
              type="text"
              placeholder="Keterangan tambahan pengeluaran stok..."
              class="block w-full rounded-lg border-gray-300 py-1.5 px-2.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
            >
          </div>
        </div>
      </div>

      <!-- Integrated Scanner & Quick Entry Strip -->
      <div class="bg-amber-50/50 border border-amber-100 rounded-xl p-2 sm:p-2.5 shadow-2xs flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
        <!-- Lokasi Scan Asal -->
        <div class="w-full sm:w-60 lg:w-72 shrink-0">
          <div class="flex items-center gap-1.5 mb-1">
            <span class="w-2 h-2 rounded-full bg-amber-500" />
            <label
              for="issue-scan-location"
              class="text-[11px] font-bold text-amber-900 uppercase tracking-wider"
            >
              Lokasi Scan Asal *
            </label>
          </div>
          <BaseCombobox
            id="issue-scan-location"
            v-model="scanLocationId"
            :options="locations"
            size="xs"
            placeholder="Pilih lokasi asal scan..."
          />
        </div>

        <!-- Scanner Barcode Panel -->
        <div class="flex-1 min-w-[200px]">
          <BarcodeScannerPanel
            ref="scannerPanelRef"
            :compact="true"
            :location-selected="Boolean(scanLocationId)"
            label="Scan Barcode / SKU"
            placeholder="Scan barcode / ketik SKU pengeluaran [F2] lalu Enter..."
            @scan-success="handleProductScanned"
            @scan-error="(msg) => { errorMsg = msg; }"
          />
        </div>

        <!-- Tombol Tambah Baris Manual -->
        <div class="shrink-0 flex items-end">
          <button
            id="btn-add-issue-item"
            type="button"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-lg bg-white border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-800 shadow-2xs hover:bg-amber-50 hover:border-amber-300 transition-colors min-h-[36px] whitespace-nowrap cursor-pointer"
            title="Tambah Baris Item Kosong"
            @click="addItem"
          >
            <svg
              class="w-3.5 h-3.5 text-amber-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            <span>+ Baris Manual</span>
          </button>
        </div>
      </div>

      <!-- High-Density Items Table Card -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-2xs overflow-hidden flex flex-col">
        <!-- Table Header Context Bar -->
        <div class="px-3.5 py-2 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
          <div class="flex items-center gap-2">
            <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">
              Daftar Item Produk Keluar
            </h3>
            <span class="px-1.5 py-0.2 text-[10px] font-bold rounded-md bg-gray-100 text-gray-600 font-mono">
              {{ form.items.length }} baris
            </span>
          </div>
          <span class="text-[11px] text-gray-400 font-mono hidden md:inline-block">
            Tekan <kbd class="px-1 py-0.5 rounded bg-gray-100 border border-gray-200 text-gray-600 font-bold">F2</kbd> ke Scanner &bull; <kbd class="px-1 py-0.5 rounded bg-gray-100 border border-gray-200 text-gray-600 font-bold">F9</kbd> Simpan
          </span>
        </div>

        <!-- Scrollable Table Container -->
        <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-320px)] min-h-[200px]">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs text-gray-600 font-semibold border-b border-gray-200 z-10">
              <tr class="text-[11px] text-gray-500">
                <th class="py-2 px-2.5 w-10 text-center">
                  #
                </th>
                <th class="py-2 px-2.5 w-32">
                  SKU / Barcode
                </th>
                <th class="py-2 px-2.5 min-w-[200px]">
                  Produk *
                </th>
                <th class="py-2 px-2.5 min-w-[180px]">
                  Lokasi Asal *
                </th>
                <th class="py-2 px-2.5 text-right w-28">
                  Stok Tersedia
                </th>
                <th class="py-2 px-2.5 text-right w-28">
                  Harga Satuan
                </th>
                <th class="py-2 px-2.5 text-right w-24">
                  Jumlah (Qty) *
                </th>
                <th class="py-2 px-2.5 text-right w-32">
                  Subtotal
                </th>
                <th class="py-2 px-2 w-14 text-center">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="(item, index) in form.items"
                :key="index"
                class="hover:bg-amber-50/20 transition-colors"
              >
                <!-- Nomor Urut -->
                <td class="py-1.5 px-2.5 text-center text-gray-400 font-mono text-[11px]">
                  {{ index + 1 }}
                </td>

                <!-- SKU Input -->
                <td class="py-1.5 px-2.5">
                  <input
                    type="text"
                    :value="getProductSku(item.product_id)"
                    placeholder="Ketik SKU..."
                    class="block w-full rounded-md border-gray-300 font-mono text-[11px] py-1 px-2 uppercase focus:border-amber-500 focus:ring-amber-500"
                    @change="onSkuEntered($event.target.value, index)"
                    @keydown.enter.prevent="onSkuEntered($event.target.value, index)"
                  >
                </td>

                <!-- Produk Combobox -->
                <td class="py-1.5 px-2.5">
                  <BaseCombobox
                    v-model="item.product_id"
                    :options="products"
                    size="xs"
                    placeholder="Pilih / cari produk..."
                    @change="fetchStock(index)"
                  />
                </td>

                <!-- Lokasi Asal Combobox -->
                <td class="py-1.5 px-2.5">
                  <BaseCombobox
                    v-model="item.location_id"
                    :options="locations"
                    size="xs"
                    placeholder="Pilih / cari lokasi asal..."
                    @change="fetchStock(index)"
                  />
                </td>

                <!-- Stok Tersedia -->
                <td class="py-1.5 px-2.5 text-right font-mono text-[11px]">
                  <span
                    :class="[
                      'inline-flex items-center gap-1 font-bold',
                      isQuantityExceeding(item) ? 'text-rose-600' : 'text-gray-700'
                    ]"
                  >
                    {{ item.available_stock !== null ? formatQuantity(item.available_stock) : '-' }}
                    <span
                      v-if="isQuantityExceeding(item)"
                      class="text-[9px] bg-rose-100 text-rose-800 px-1 py-0.2 rounded font-sans"
                    >
                      Kurang
                    </span>
                  </span>
                </td>

                <!-- Harga Satuan -->
                <td class="py-1.5 px-2.5 text-right font-mono text-[11px] text-gray-600">
                  {{ formatRupiah(getProductPrice(item.product_id)) }}
                </td>

                <!-- Kuantitas -->
                <td class="py-1.5 px-2.5">
                  <input
                    v-model="item.quantity"
                    type="text"
                    inputmode="decimal"
                    placeholder="1"
                    class="block w-full text-right font-mono rounded-md border-gray-300 text-xs py-1 px-2 font-bold text-gray-900 focus:border-amber-500 focus:ring-amber-500"
                    required
                    @blur="handleQtyBlur(item)"
                  >
                </td>

                <!-- Subtotal -->
                <td class="py-1.5 px-2.5 text-right font-mono text-[11px] font-bold text-gray-900">
                  {{ formatRupiah(getItemSubtotal(item)) }}
                </td>

                <!-- Hapus Baris -->
                <td class="py-1.5 px-2 text-center">
                  <button
                    type="button"
                    class="p-1 rounded text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer"
                    title="Hapus baris item"
                    @click="removeItem(index)"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                      />
                    </svg>
                  </button>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="form.items.length === 0">
                <td
                  colspan="9"
                  class="py-8 text-center"
                >
                  <div class="flex flex-col items-center justify-center text-gray-400 text-xs">
                    <svg
                      class="w-7 h-7 text-gray-300 mb-1.5"
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
                    <span class="font-medium text-gray-500">Belum ada item produk pengeluaran.</span>
                    <span class="text-[11px] text-gray-400 mt-0.5">Pilih Lokasi Scan Asal di atas & arahkan scanner, atau tekan "+ Baris Manual".</span>
                  </div>
                </td>
              </tr>
            </tbody>

            <!-- Sticky Summary Footer -->
            <tfoot
              v-if="form.items.length > 0"
              class="sticky bottom-0 bg-gray-50/95 backdrop-blur-xs border-t-2 border-gray-200 text-xs font-semibold z-10"
            >
              <tr>
                <td
                  colspan="6"
                  class="py-2 px-2.5 text-right text-gray-600 uppercase text-[11px] tracking-wider"
                >
                  Grand Total Pengeluaran:
                </td>
                <td class="py-2 px-2.5 text-right font-mono font-black text-amber-800">
                  {{ formatQuantity(totalItemsQty) }}
                </td>
                <td class="py-2 px-2.5 text-right font-mono font-black text-indigo-700">
                  {{ formatRupiah(grandTotalAmount) }}
                </td>
                <td />
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router';
import { useStockIssueStore } from '../stores/useStockIssueStore';
import { useDocumentForm } from '../composables/use_document_form';
import BarcodeScannerPanel from '../scanner/components/BarcodeScannerPanel.vue';
import BaseCombobox from '@/shared/components/BaseCombobox.vue';
import { formatRupiah, formatQuantity } from '@/shared/utils/formatters.js';

const route = useRoute();
const store = useStockIssueStore();

const {
    isEdit,
    isSubmitting,
    errorMsg,
    scanLocationId,
    scannerPanelRef,
    form,
    products,
    locations,
    getProductPrice,
    getItemSubtotal,
    totalItemsQty,
    grandTotalAmount,
    fetchStock,
    isQuantityExceeding,
    handleQtyBlur,
    handleProductScanned,
    getProductSku,
    onSkuEntered,
    addItem,
    removeItem,
    submitForm,
} = useDocumentForm({
    store,
    isEdit: route.name === 'stockIssuesEdit',
    basePath: '/inventory/issues',
    headerKey: 'purpose',
    locationNoun: 'lokasi asal',
    hasStockColumn: true,
});
</script>