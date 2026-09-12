<template>
  <div class="px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="sm:flex sm:items-center justify-between">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          {{ isEdit ? 'Edit Draft Pengeluaran' : 'Buat Draft Pengeluaran Stok' }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Isi form di bawah atau gunakan Barcode Scanner untuk mencatat mutasi keluar barang.
        </p>
      </div>
      <div class="mt-4 sm:mt-0 flex gap-2">
        <router-link
          :to="isEdit ? `/inventory/issues/${route.params.id}` : '/inventory/issues'"
          class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 cursor-pointer"
        >
          Batal
        </router-link>
        <button
          id="btn-save-issue-draft"
          type="button"
          :disabled="isSubmitting"
          class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 cursor-pointer"
          title="Simpan Dokumen Draft (Shortcut: F9)"
          @click="submitForm"
        >
          <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Draft' }}</span>
          <kbd class="hidden sm:inline-block font-mono text-[10px] bg-indigo-700/80 text-indigo-100 px-1.5 py-0.5 rounded border border-indigo-500 font-bold">F9</kbd>
        </button>
      </div>
    </div>

    <!-- Error Alert -->
    <div
      v-if="errorMsg"
      class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800"
    >
      {{ errorMsg }}
    </div>

    <!-- Barcode Scanner Section -->
    <div class="bg-white p-4 sm:p-6 shadow-xs rounded-xl border border-gray-200 space-y-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
            <span class="w-6 h-6 rounded bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">📷</span>
            Barcode Scanner Entry
          </h2>
          <p class="text-xs text-gray-500 mt-0.5">
            Pilih Lokasi Scan lalu arahkan scanner barcode untuk menambahkan item pengeluaran.
          </p>
        </div>

        <div class="w-full sm:w-72">
          <label
            for="issue-scan-location"
            class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1"
          >
            Lokasi Scan Asal *
          </label>
          <BaseCombobox
            id="issue-scan-location"
            v-model="scanLocationId"
            :options="locations"
            placeholder="Pilih / cari lokasi asal..."
          />
        </div>
      </div>

      <BarcodeScannerPanel
        ref="scannerPanelRef"
        :location-selected="Boolean(scanLocationId)"
        label="Scan Barcode / Masukkan SKU Produk"
        placeholder="Scan barcode atau ketik SKU / Barcode produk pengeluaran (cth: 1001 / 000123)... [F2]"
        @scan-success="handleProductScanned"
        @scan-error="(msg) => { errorMsg = msg; }"
      />
    </div>

    <!-- Header & Items Form -->
    <form
      class="space-y-6"
      @submit.prevent="submitForm"
    >
      <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6 bg-white shadow-xs rounded-xl border border-gray-200 p-4 sm:p-6">
        <div class="sm:col-span-3">
          <label
            for="date"
            class="block text-sm font-medium text-gray-700 mb-1"
          >Tanggal Pengeluaran *</label>
          <input
            id="date"
            v-model="form.date"
            type="date"
            class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            required
          >
        </div>

        <div class="sm:col-span-3">
          <label
            for="purpose"
            class="block text-sm font-medium text-gray-700 mb-1"
          >Tujuan / Alasan *</label>
          <input
            id="purpose"
            v-model="form.purpose"
            type="text"
            class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Contoh: Produksi SPK-001"
            required
          >
        </div>

        <div class="sm:col-span-6">
          <label
            for="notes"
            class="block text-sm font-medium text-gray-700 mb-1"
          >Catatan</label>
          <textarea
            id="notes"
            v-model="form.notes"
            rows="2"
            class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
        </div>
      </div>

      <!-- Items Table -->
      <div class="bg-white shadow-xs rounded-xl border border-gray-200 p-4 sm:p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-semibold text-gray-900">
            Daftar Item Produk Keluar
          </h3>
          <button
            id="btn-add-issue-item"
            type="button"
            class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-indigo-600 shadow-xs ring-1 ring-inset ring-indigo-300 hover:bg-indigo-50 cursor-pointer"
            @click="addItem"
          >
            + Tambah Baris Manual
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-200">
                <th class="py-2.5 px-3 w-10 text-center">
                  No.
                </th>
                <th class="py-2.5 px-3 w-36">
                  SKU / Barcode
                </th>
                <th class="py-2.5 px-3">
                  Produk *
                </th>
                <th class="py-2.5 px-3">
                  Lokasi Asal *
                </th>
                <th class="py-2.5 px-3 text-right">
                  Stok Tersedia
                </th>
                <th class="py-2.5 px-3 text-right w-32">
                  Harga Satuan
                </th>
                <th class="py-2.5 px-3 text-right w-28">
                  Kuantitas (Qty) *
                </th>
                <th class="py-2.5 px-3 text-right w-36">
                  Subtotal
                </th>
                <th class="py-2.5 px-3 text-center w-20">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr
                v-for="(item, index) in form.items"
                :key="index"
              >
                <td class="py-2.5 px-3 text-center text-gray-400 font-mono">
                  {{ index + 1 }}
                </td>
                <td class="py-2.5 px-3">
                  <input
                    type="text"
                    :value="getProductSku(item.product_id)"
                    placeholder="Ketik SKU..."
                    class="block w-full rounded-md border-gray-300 font-mono text-xs uppercase focus:border-indigo-500 focus:ring-indigo-500"
                    @change="onSkuEntered($event.target.value, index)"
                    @keydown.enter.prevent="onSkuEntered($event.target.value, index)"
                  >
                </td>
                <td class="py-2.5 px-3 min-w-[220px]">
                  <BaseCombobox
                    v-model="item.product_id"
                    :options="products"
                    size="xs"
                    placeholder="Pilih / cari produk..."
                    @change="fetchStock(index)"
                  />
                </td>
                <td class="py-2.5 px-3 min-w-[200px]">
                  <BaseCombobox
                    v-model="item.location_id"
                    :options="locations"
                    size="xs"
                    placeholder="Pilih / cari lokasi..."
                    @change="fetchStock(index)"
                  />
                </td>
                <td class="py-2.5 px-3 text-right font-mono">
                  <span
                    :class="[
                      'inline-flex items-center gap-1 font-bold',
                      isQuantityExceeding(item) ? 'text-rose-600' : 'text-gray-900'
                    ]"
                  >
                    {{ item.available_stock !== null ? formatQuantity(item.available_stock) : '-' }}
                    <span
                      v-if="isQuantityExceeding(item)"
                      class="text-[10px] bg-rose-100 text-rose-800 px-1.5 py-0.5 rounded font-sans"
                    >
                      Stok Kurang
                    </span>
                  </span>
                </td>
                <td class="py-2.5 px-3 text-right font-mono text-xs text-gray-700">
                  {{ formatRupiah(getProductPrice(item.product_id)) }}
                </td>
                <td class="py-2.5 px-3">
                  <input
                    v-model="item.quantity"
                    type="text"
                    inputmode="decimal"
                    placeholder="1"
                    class="block w-full text-right font-mono rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                    required
                    @blur="handleQtyBlur(item)"
                  >
                </td>
                <td class="py-2.5 px-3 text-right font-mono text-xs font-semibold text-gray-900">
                  {{ formatRupiah(getItemSubtotal(item)) }}
                </td>
                <td class="py-2.5 px-3 text-center">
                  <button
                    type="button"
                    class="text-rose-600 hover:text-rose-900 font-semibold cursor-pointer"
                    @click="removeItem(index)"
                  >
                    Hapus
                  </button>
                </td>
              </tr>
              <tr v-if="form.items.length === 0">
                <td
                  colspan="9"
                  class="py-6 text-center text-gray-400"
                >
                  Belum ada item. Gunakan scanner di atas atau tombol Tambah Baris Manual.
                </td>
              </tr>
            </tbody>
            <tfoot
              v-if="form.items.length > 0"
              class="border-t-2 border-gray-200 bg-gray-50 text-xs font-medium"
            >
              <tr>
                <td
                  colspan="6"
                  class="py-2.5 px-3 text-right text-gray-700 font-semibold"
                >
                  Grand Total Pengeluaran:
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-gray-900">
                  {{ formatQuantity(totalItemsQty) }}
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-indigo-700">
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