<template>
  <div class="space-y-2.5">
    <!-- Top Compact Header Bar & KPI Summary -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
      <!-- Left: Title & Status -->
      <div class="flex items-center gap-2.5">
        <router-link
          :to="isEdit ? `/inventory/transfers/${route.params.id}` : '/inventory/transfers'"
          class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer"
          title="Kembali ke daftar transfer"
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
              {{ isEdit ? 'Edit Draft Transfer Stok' : 'Transfer Stok Antar Lokasi' }}
            </h1>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-200 uppercase tracking-wide">
              {{ isEdit ? 'Edit Draft' : 'Draft Baru' }}
            </span>
          </div>
          <p class="text-[11px] text-gray-500 hidden sm:block">
            Pencatatan perpindahan fisik stok barang antar gudang / lokasi.
          </p>
        </div>
      </div>

      <!-- Middle: KPI Summary Chips -->
      <div class="flex items-center gap-2 flex-wrap">
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs">
          <span class="text-gray-500 font-medium">Item:</span>
          <span class="font-bold text-gray-800 font-mono">{{ form.items.length }}</span>
        </div>
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 border border-blue-200 rounded-lg text-xs">
          <span class="text-blue-700 font-medium">Total Qty:</span>
          <span class="font-black text-blue-800 font-mono">{{ formatQuantity(totalTransferQty) }}</span>
        </div>
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-50 border border-indigo-200 rounded-lg text-xs">
          <span class="text-indigo-700 font-medium">Estimasi Nilai:</span>
          <span class="font-black text-indigo-900 font-mono">{{ formatRupiah(grandTotal) }}</span>
        </div>
      </div>

      <!-- Right: Main Actions -->
      <div class="flex items-center gap-2 self-end md:self-auto">
        <router-link
          :to="isEdit ? `/inventory/transfers/${route.params.id}` : '/inventory/transfers'"
          class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 border border-gray-300 shadow-2xs hover:bg-gray-50 transition-colors cursor-pointer"
        >
          Batal
        </router-link>
        <button
          id="btn-save-transfer-draft"
          type="button"
          :disabled="store.loadingAction"
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
          <span>{{ store.loadingAction ? 'Menyimpan...' : 'Simpan Draft' }}</span>
          <kbd class="hidden sm:inline-block font-mono text-[9px] bg-indigo-700/90 text-indigo-100 px-1 py-0.2 rounded border border-indigo-400 font-bold">F9</kbd>
        </button>
      </div>
    </div>

    <!-- Error Alert -->
    <div
      v-if="store.error || locationError"
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
        <span>{{ store.error || locationError }}</span>
      </div>
      <button
        type="button"
        class="text-rose-400 hover:text-rose-600 cursor-pointer"
        @click="store.error = null; locationError = '';"
      >
        &times;
      </button>
    </div>

    <!-- Warning Alert -->
    <div
      v-if="warningMessage"
      class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800 flex items-center justify-between gap-2"
    >
      <div class="flex items-center gap-2">
        <svg
          class="w-4 h-4 text-amber-500 shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        <span>{{ warningMessage }}</span>
      </div>
      <button
        type="button"
        class="text-amber-500 hover:text-amber-700 cursor-pointer"
        @click="warningMessage = ''"
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
          <!-- Tanggal Transfer -->
          <div>
            <label
              for="transfer_date"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Tanggal Transfer *
            </label>
            <input
              id="transfer_date"
              v-model="form.transfer_date"
              type="date"
              class="block w-full rounded-lg border-gray-300 py-1.5 px-2.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
              required
            >
            <p
              v-if="store.validationErrors?.transfer_date"
              class="mt-1 text-[10px] text-rose-600"
            >
              {{ store.validationErrors.transfer_date[0] }}
            </p>
          </div>

          <!-- Lokasi Asal (Origin) -->
          <div>
            <label
              for="origin_location"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Lokasi Asal (Origin) *
            </label>
            <select
              id="origin_location"
              v-model="form.origin_location_id"
              class="block w-full rounded-lg border-gray-300 py-1.5 px-2 text-xs focus:border-indigo-500 focus:ring-indigo-500"
              required
              @change="validateLocations"
            >
              <option
                value=""
                disabled
              >
                Pilih Lokasi Asal
              </option>
              <option
                v-for="loc in userLocations"
                :key="loc.id"
                :value="loc.id"
              >
                {{ loc.name }} ({{ loc.code }})
              </option>
            </select>
            <p
              v-if="store.validationErrors?.origin_location_id"
              class="mt-1 text-[10px] text-rose-600"
            >
              {{ store.validationErrors.origin_location_id[0] }}
            </p>
          </div>

          <!-- Lokasi Tujuan (Destination) -->
          <div>
            <label
              for="destination_location"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Lokasi Tujuan (Destination) *
            </label>
            <select
              id="destination_location"
              v-model="form.destination_location_id"
              class="block w-full rounded-lg border-gray-300 py-1.5 px-2 text-xs focus:border-indigo-500 focus:ring-indigo-500"
              required
              @change="validateLocations"
            >
              <option
                value=""
                disabled
              >
                Pilih Lokasi Tujuan
              </option>
              <option
                v-for="loc in availableDestinations"
                :key="loc.id"
                :value="loc.id"
              >
                {{ loc.name }} ({{ loc.code }})
              </option>
            </select>
            <p
              v-if="store.validationErrors?.destination_location_id"
              class="mt-1 text-[10px] text-rose-600"
            >
              {{ store.validationErrors.destination_location_id[0] }}
            </p>
          </div>

          <!-- Catatan -->
          <div>
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
              placeholder="Keterangan transfer stok..."
              class="block w-full rounded-lg border-gray-300 py-1.5 px-2.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
            >
          </div>
        </div>
      </div>

      <!-- Integrated Scanner & Quick Entry Strip -->
      <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-2 sm:p-2.5 shadow-2xs flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
        <!-- Status Route Transfer -->
        <div class="w-full sm:w-64 shrink-0 flex items-center gap-2 px-2.5 py-1.5 bg-white rounded-lg border border-blue-200">
          <span
            class="w-2.5 h-2.5 rounded-full shrink-0"
            :class="form.origin_location_id && form.destination_location_id && !locationError ? 'bg-emerald-500' : 'bg-amber-400 animate-pulse'"
          />
          <div class="text-[11px] truncate">
            <span
              v-if="form.origin_location_id && form.destination_location_id && !locationError"
              class="font-bold text-emerald-800"
            >
              Rute Transfer Siap
            </span>
            <span
              v-else
              class="font-medium text-amber-700"
            >
              Pilih Asal & Tujuan
            </span>
          </div>
        </div>

        <!-- Scanner Barcode Panel -->
        <div class="flex-1 min-w-[200px]">
          <BarcodeScannerPanel
            ref="scannerPanelRef"
            :compact="true"
            :location-selected="Boolean(form.origin_location_id && form.destination_location_id && !locationError)"
            label="Scan Barcode Transfer"
            placeholder="Scan barcode produk ditransfer [F2] lalu Enter..."
            @scan-success="handleProductScanned"
            @scan-error="(msg) => { locationError = msg; }"
          />
        </div>

        <!-- Tombol Tambah Baris Manual -->
        <div class="shrink-0 flex items-end">
          <button
            id="btn-add-transfer-item"
            type="button"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-lg bg-white border border-blue-200 px-3 py-1.5 text-xs font-semibold text-blue-700 shadow-2xs hover:bg-blue-50 hover:border-blue-300 transition-colors min-h-[36px] whitespace-nowrap cursor-pointer"
            title="Tambah Baris Item Kosong"
            @click="addItemRow"
          >
            <svg
              class="w-3.5 h-3.5 text-blue-600"
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
              Daftar Item Produk Transfer
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
                <th class="py-2 px-2.5 min-w-[280px]">
                  Produk *
                </th>
                <th class="py-2 px-2.5 text-right w-32">
                  Harga Satuan
                </th>
                <th class="py-2 px-2.5 text-right w-28">
                  Kuantitas (Qty) *
                </th>
                <th class="py-2 px-2.5 text-right w-36">
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
                class="hover:bg-blue-50/20 transition-colors"
              >
                <!-- Nomor Urut -->
                <td class="py-1.5 px-2.5 text-center text-gray-400 font-mono text-[11px]">
                  {{ index + 1 }}
                </td>

                <!-- Produk Select -->
                <td class="py-1.5 px-2.5">
                  <select
                    v-model="item.product_id"
                    class="block w-full rounded-md border-gray-300 text-xs py-1 px-2 focus:border-indigo-500 focus:ring-indigo-500"
                    required
                  >
                    <option
                      value=""
                      disabled
                    >
                      Pilih Produk...
                    </option>
                    <option
                      v-for="p in products"
                      :key="p.id"
                      :value="p.id"
                      :disabled="isProductSelectedInOtherRow(p.id, index)"
                    >
                      {{ p.sku }} — {{ p.name }} {{ p.barcode ? `(${p.barcode})` : '' }} [• {{ formatRupiah(p.unit_price) }}]
                    </option>
                  </select>
                </td>

                <!-- Harga Satuan -->
                <td class="py-1.5 px-2.5 text-right font-mono text-[11px] text-gray-600">
                  {{ formatRupiah(getItemUnitPrice(item.product_id)) }}
                </td>

                <!-- Kuantitas -->
                <td class="py-1.5 px-2.5">
                  <input
                    v-model="item.quantity"
                    type="text"
                    inputmode="decimal"
                    placeholder="1"
                    class="block w-full text-right font-mono rounded-md border-gray-300 text-xs py-1 px-2 font-bold text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"
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
                    @click="removeItemRow(index)"
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
                  colspan="6"
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
                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                      />
                    </svg>
                    <span class="font-medium text-gray-500">Belum ada item transfer.</span>
                    <span class="text-[11px] text-gray-400 mt-0.5">Pilih Lokasi Asal & Tujuan di atas & arahkan scanner, atau tekan "+ Baris Manual".</span>
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
                  colspan="3"
                  class="py-2 px-2.5 text-right text-gray-600 uppercase text-[11px] tracking-wider"
                >
                  Grand Total Estimasi Nilai Transfer:
                </td>
                <td class="py-2 px-2.5 text-right font-mono font-black text-blue-800">
                  {{ formatQuantity(totalTransferQty) }}
                </td>
                <td class="py-2 px-2.5 text-right font-mono font-black text-indigo-700">
                  {{ formatRupiah(grandTotal) }}
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
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStockTransferStore } from '../stores/useStockTransferStore';
import { locationApi } from '@features/location/api/location_api.js';
import { productApi } from '@features/product/api/product_api.js';
import BarcodeScannerPanel from '../scanner/components/BarcodeScannerPanel.vue';
import { formatRupiah, formatQuantity } from '@/shared/utils/formatters';
import {
  addDecimal4Strings,
  normalizeDecimal4String,
  tryNormalizeDecimal4String,
  isValidDecimal4String,
  compareDecimal4Strings,
} from '../scanner/utils/decimal_string.js';

const route = useRoute();
const router = useRouter();
const store = useStockTransferStore();

const isEdit = computed(() => !!route.params.id);
const scannerPanelRef = ref(null);

const form = reactive({
  transfer_date: new Date().toISOString().substring(0, 10),
  origin_location_id: '',
  destination_location_id: '',
  notes: '',
  items: [
    { product_id: '', quantity: '1' }
  ],
});

const userLocations = ref([]);
const allLocations = ref([]);
const products = ref([]);
const locationError = ref('');
const warningMessage = ref('');

const availableDestinations = computed(() => {
  return allLocations.value.filter((loc) => loc.id !== form.origin_location_id);
});

const totalTransferQty = computed(() => {
  return form.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
});

const validateLocations = () => {
  if (form.origin_location_id && form.destination_location_id && form.origin_location_id === form.destination_location_id) {
    locationError.value = 'Lokasi tujuan tidak boleh sama dengan lokasi asal.';
  } else {
    locationError.value = '';
  }
};

const isProductSelectedInOtherRow = (productId, currentRowIndex) => {
  return form.items.some((item, index) => index !== currentRowIndex && item.product_id === productId);
};

const getItemUnitPrice = (productId) => {
  if (!productId) return 0;
  const p = products.value.find((prod) => prod.id === productId);
  return p ? Number(p.unit_price) || 0 : 0;
};

const getItemSubtotal = (item) => {
  const price = getItemUnitPrice(item.product_id);
  const qty = Number(item.quantity) || 0;
  return qty * price;
};

const grandTotal = computed(() => {
  return form.items.reduce((sum, item) => sum + getItemSubtotal(item), 0);
});

const handleQtyBlur = (item) => {
  const norm = tryNormalizeDecimal4String(item.quantity);
  if (norm !== null) {
    item.quantity = norm.replace(/\.0+$/, '').replace(/(\.[0-9]*[1-9])0+$/, '$1');
  }
};

const handleProductScanned = (scannedProduct) => {
  validateLocations();
  if (locationError.value || !form.origin_location_id || !form.destination_location_id) {
    locationError.value = 'Pilih lokasi asal dan lokasi tujuan yang sah terlebih dahulu.';
    return;
  }

  // Ensure product is in products list
  const existsInProducts = products.value.some((p) => p.id === scannedProduct.id);
  if (!existsInProducts) {
    products.value.push(scannedProduct);
  }

  // Check if product already exists in transfer items (duplicate key = product_id)
  const existingIndex = form.items.findIndex((item) => item.product_id === scannedProduct.id);

  if (existingIndex !== -1) {
    const currentQty = form.items[existingIndex].quantity;
    if (!isValidDecimal4String(currentQty)) {
      store.error = `Kuantitas saat ini pada baris produk "${scannedProduct.name}" (${currentQty}) tidak valid. Harap perbaiki kuantitas sebelum melakukan scan ulang.`;
      return;
    }
    const summed = addDecimal4Strings(currentQty, '1');
    form.items[existingIndex].quantity = summed.replace(/\.0+$/, '').replace(/(\.[0-9]*[1-9])0+$/, '$1');
  } else {
    // If first item in form is empty placeholder, replace it; otherwise push
    if (form.items.length === 1 && !form.items[0].product_id) {
      form.items[0] = { product_id: scannedProduct.id, quantity: '1' };
    } else {
      form.items.push({ product_id: scannedProduct.id, quantity: '1' });
    }
  }

  locationError.value = '';
};

const addItemRow = () => {
  form.items.push({ product_id: '', quantity: '1' });
};

const removeItemRow = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1);
  } else {
    form.items[0] = { product_id: '', quantity: '1' };
  }
};

const loadMasterData = async () => {
  try {
    const [userLocRes, allLocRes, prodRes] = await Promise.all([
      locationApi.getAll({ is_active: true, assigned_only: 1, per_page: 1000 }),
      locationApi.getAll({ is_active: true, per_page: 1000 }),
      productApi.getAll({ is_active: true, per_page: 1000 }),
    ]);
    userLocations.value = userLocRes.data?.data?.data || userLocRes.data?.data || [];
    allLocations.value = allLocRes.data?.data?.data || allLocRes.data?.data || [];
    products.value = prodRes.data?.data?.data || prodRes.data?.data || [];
  } catch {
    store.error = 'Gagal memuat data master lokasi atau produk.';
  }
};

const submitForm = async () => {
  validateLocations();
  if (locationError.value) return;

  for (const item of form.items) {
    if (!item.product_id) {
      store.error = 'Semua baris item harus memilih produk.';
      return;
    }
    if (!isValidDecimal4String(item.quantity) || compareDecimal4Strings(item.quantity, '0.0000') <= 0) {
      store.error = 'Kuantitas item harus berupa angka positif valid dengan maksimal 4 desimal.';
      return;
    }
  }

  try {
    const payload = {
      transfer_date: form.transfer_date,
      origin_location_id: form.origin_location_id,
      destination_location_id: form.destination_location_id,
      notes: form.notes,
      items: form.items.map((i) => ({
        product_id: i.product_id,
        quantity: normalizeDecimal4String(i.quantity),
      })),
    };

    if (isEdit.value) {
      await store.updateTransfer(route.params.id, payload);
      router.push(`/inventory/transfers/${route.params.id}`);
    } else {
      const res = await store.createTransfer(payload);
      router.push(`/inventory/transfers/${res.data.id}`);
    }
  } catch {
    // Handled by Pinia store
  }
};

const handleGlobalKeyDown = (e) => {
  if (e.key === 'F2') {
    e.preventDefault();
    scannerPanelRef.value?.focusInput();
  } else if (e.key === 'F9') {
    e.preventDefault();
    submitForm();
  }
};

onMounted(async () => {
  window.addEventListener('keydown', handleGlobalKeyDown);
  await loadMasterData();

  if (isEdit.value) {
    try {
      const data = await store.fetchTransferById(route.params.id);
      if (data.status !== 'DRAFT') {
        store.error = 'Hanya dokumen berstatus DRAFT yang dapat diedit.';
        router.push(`/inventory/transfers/${data.id}`);
        return;
      }
      form.transfer_date = data.transfer_date;
      form.origin_location_id = data.origin_location_id;
      form.destination_location_id = data.destination_location_id;
      form.notes = data.notes || '';
      form.items = data.items.map((i) => ({
        product_id: i.product_id,
        quantity: String(i.quantity).replace(/\.0+$/, '').replace(/(\.[0-9]*[1-9])0+$/, '$1'),
      }));
    } catch {
      // Handled by store
    }
  } else if (route.query.source === 'replenishment') {
    const originId = parseInt(route.query.origin_location_id, 10);
    const destId = parseInt(route.query.destination_location_id, 10);
    const prodId = parseInt(route.query.product_id, 10);
    const rawQty = route.query.quantity;

    if (route.query.origin_location_id) {
      if (originId && userLocations.value.some((l) => l.id === originId)) {
        form.origin_location_id = originId;
      } else {
        warningMessage.value = 'Lokasi asal rekomendasi tidak valid atau di luar hak akses Anda.';
      }
    }

    if (route.query.destination_location_id) {
      if (destId && allLocations.value.some((l) => l.id === destId && l.id !== originId)) {
        form.destination_location_id = destId;
      } else {
        warningMessage.value = 'Lokasi tujuan rekomendasi tidak valid atau sama dengan lokasi asal.';
      }
    }

    let prefillItems = [];
    if (window.history.state && Array.isArray(window.history.state.replenishment_items)) {
      prefillItems = window.history.state.replenishment_items;
    }

    if (prefillItems.length > 0) {
      const parsedItems = [];
      prefillItems.forEach((it) => {
        const pid = parseInt(it.product_id, 10);
        const qty = it.quantity || it.requested_quantity;
        if (pid && products.value.some((p) => p.id === pid)) {
          if (typeof qty === 'string' && isValidDecimal4String(qty) && compareDecimal4Strings(qty, '0.0000') > 0) {
            parsedItems.push({ product_id: pid, quantity: normalizeDecimal4String(qty) });
          } else {
            parsedItems.push({ product_id: pid, quantity: '' });
          }
        }
      });
      if (parsedItems.length > 0) {
        form.items = parsedItems;
      }
    } else if (route.query.product_id) {
      if (prodId && products.value.some((p) => p.id === prodId)) {
        if (typeof rawQty === 'string' && isValidDecimal4String(rawQty) && compareDecimal4Strings(rawQty, '0.0000') > 0) {
          form.items = [{ product_id: prodId, quantity: normalizeDecimal4String(rawQty) }];
        } else {
          form.items = [{ product_id: prodId, quantity: '' }];
          warningMessage.value = 'Rekomendasi quantity tidak valid. Silakan isi quantity secara manual.';
        }
      } else {
        warningMessage.value = 'Produk rekomendasi tidak valid atau tidak aktif.';
      }
    }
    validateLocations();
  }
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleGlobalKeyDown);
});
</script>
