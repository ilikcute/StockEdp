<template>
  <div class="space-y-2.5">
    <!-- Top Compact Header Bar & KPI Summary -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
      <!-- Left: Title & Status -->
      <div class="flex items-center gap-2.5">
        <router-link
          :to="isEdit ? `/inventory/adjustments/${route.params.id}` : '/inventory/adjustments'"
          class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer"
          title="Kembali ke daftar penyesuaian stok"
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
              {{ isEdit ? 'Edit Draft Penyesuaian Stok' : 'Penyesuaian Stok (Adjustment)' }}
            </h1>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-purple-50 text-purple-700 border border-purple-200 uppercase tracking-wide">
              {{ isEdit ? 'Edit Draft' : 'Draft Baru' }}
            </span>
            <span
              class="px-2 py-0.5 text-[10px] font-bold rounded-full border uppercase tracking-wide"
              :class="form.direction === 'INCREASE' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
            >
              {{ form.direction === 'INCREASE' ? '+ Penambahan' : '- Pengurangan' }}
            </span>
          </div>
          <p class="text-[11px] text-gray-500 hidden sm:block">
            Koreksi saldo stok fisik, kerusakan, kedaluwarsa, atau penyesuaian administratif.
          </p>
        </div>
      </div>

      <!-- Middle: KPI Summary Chips -->
      <div class="flex items-center gap-2 flex-wrap">
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs">
          <span class="text-gray-500 font-medium">Item:</span>
          <span class="font-bold text-gray-800 font-mono">{{ form.items.length }}</span>
        </div>
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-purple-50 border border-purple-200 rounded-lg text-xs">
          <span class="text-purple-700 font-medium">Total Qty:</span>
          <span class="font-black text-purple-800 font-mono">{{ formatQuantity(totalAdjustmentQty) }}</span>
        </div>
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-50 border border-indigo-200 rounded-lg text-xs">
          <span class="text-indigo-700 font-medium">Nilai Est:</span>
          <span class="font-black text-indigo-900 font-mono">{{ formatRupiah(grandTotal) }}</span>
        </div>
      </div>

      <!-- Right: Main Actions -->
      <div class="flex items-center gap-2 self-end md:self-auto">
        <router-link
          :to="isEdit ? `/inventory/adjustments/${route.params.id}` : '/inventory/adjustments'"
          class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 border border-gray-300 shadow-2xs hover:bg-gray-50 transition-colors cursor-pointer"
        >
          Batal
        </router-link>
        <button
          id="btn-save-adjustment-draft"
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
      v-if="store.error || notesError"
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
        <span>{{ store.error || notesError }}</span>
      </div>
      <button
        type="button"
        class="text-rose-400 hover:text-rose-600 cursor-pointer"
        @click="store.error = null; notesError = '';"
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5">
          <!-- Tanggal Adjustment -->
          <div>
            <label
              for="adjustment_date"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Tanggal Penyesuaian *
            </label>
            <input
              id="adjustment_date"
              v-model="form.adjustment_date"
              type="date"
              :max="todayDate"
              class="block w-full rounded-lg border-gray-300 py-1.5 px-2.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
              required
            >
            <p
              v-if="store.validationErrors?.adjustment_date"
              class="mt-1 text-[10px] text-rose-600"
            >
              {{ store.validationErrors.adjustment_date[0] }}
            </p>
          </div>

          <!-- Lokasi Gudang -->
          <div>
            <label
              for="location"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Lokasi Gudang *
            </label>
            <BaseCombobox
              id="location"
              v-model="form.location_id"
              :options="locations"
              size="xs"
              placeholder="Pilih lokasi gudang..."
              required
            />
            <p
              v-if="store.validationErrors?.location_id"
              class="mt-1 text-[10px] text-rose-600"
            >
              {{ store.validationErrors.location_id[0] }}
            </p>
          </div>

          <!-- Arah Penyesuaian (Direction) -->
          <div>
            <label
              for="direction"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Arah Penyesuaian *
            </label>
            <select
              id="direction"
              v-model="form.direction"
              class="block w-full rounded-lg border-gray-300 py-1.5 px-2 text-xs focus:border-indigo-500 focus:ring-indigo-500"
              required
              @change="onDirectionChange"
            >
              <option value="INCREASE">
                + Penambahan (INCREASE)
              </option>
              <option value="DECREASE">
                - Pengurangan (DECREASE)
              </option>
            </select>
          </div>

          <!-- Alasan (Reason Code) -->
          <div>
            <label
              for="reason_code"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Alasan (Reason Code) *
            </label>
            <select
              id="reason_code"
              v-model="form.reason_code"
              class="block w-full rounded-lg border-gray-300 py-1.5 px-2 text-xs focus:border-indigo-500 focus:ring-indigo-500"
              required
              @change="onReasonChange"
            >
              <option
                value=""
                disabled
              >
                Pilih Alasan...
              </option>
              <option
                v-for="r in compatibleReasons"
                :key="r.value"
                :value="r.value"
              >
                {{ r.label }}
              </option>
            </select>
          </div>

          <!-- Catatan Dokumen -->
          <div>
            <label
              for="notes"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Catatan Dokumen {{ form.reason_code === 'OTHER' ? '*' : '' }}
            </label>
            <input
              id="notes"
              v-model="form.notes"
              type="text"
              :placeholder="form.reason_code === 'OTHER' ? 'Wajib: alasan rinci...' : 'Keterangan tambahan...'"
              class="block w-full rounded-lg border-gray-300 py-1.5 px-2.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
              :required="form.reason_code === 'OTHER'"
            >
          </div>
        </div>
      </div>

      <!-- Integrated Scanner & Quick Entry Strip -->
      <div class="bg-purple-50/50 border border-purple-100 rounded-xl p-2 sm:p-2.5 shadow-2xs flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
        <!-- Status Lokasi Gudang -->
        <div class="w-full sm:w-60 shrink-0 flex items-center gap-2 px-2.5 py-1.5 bg-white rounded-lg border border-purple-200">
          <span
            class="w-2.5 h-2.5 rounded-full shrink-0"
            :class="form.location_id ? 'bg-purple-600' : 'bg-amber-400 animate-pulse'"
          />
          <div class="text-[11px] truncate">
            <span
              v-if="form.location_id"
              class="font-bold text-purple-900"
            >
              Lokasi Aktif Terpilih
            </span>
            <span
              v-else
              class="font-medium text-amber-700"
            >
              Pilih Lokasi Gudang
            </span>
          </div>
        </div>

        <!-- Scanner Barcode Panel -->
        <div class="flex-1 min-w-[200px]">
          <BarcodeScannerPanel
            ref="scannerPanelRef"
            :compact="true"
            :location-selected="Boolean(form.location_id)"
            label="Scan Barcode / SKU"
            placeholder="Scan barcode / ketik SKU produk penyesuaian [F2] lalu Enter..."
            @scan-success="handleProductScanned"
            @scan-error="(msg) => { store.error = msg; }"
          />
        </div>

        <!-- Tombol Tambah Baris Manual -->
        <div class="shrink-0 flex items-end">
          <button
            id="btn-add-adjustment-item"
            type="button"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-lg bg-white border border-purple-200 px-3 py-1.5 text-xs font-semibold text-purple-700 shadow-2xs hover:bg-purple-50 hover:border-purple-300 transition-colors min-h-[36px] whitespace-nowrap cursor-pointer"
            title="Tambah Baris Item Kosong"
            @click="addItemRow"
          >
            <svg
              class="w-3.5 h-3.5 text-purple-600"
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
              Daftar Barang Penyesuaian
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
                <th class="py-2 px-2.5 min-w-[260px]">
                  Produk *
                </th>
                <th class="py-2 px-2.5 text-right w-28">
                  Harga Satuan
                </th>
                <th class="py-2 px-2.5 text-right w-24">
                  Kuantitas *
                </th>
                <th class="py-2 px-2.5 text-right w-32">
                  Estimasi Nilai
                </th>
                <th class="py-2 px-2.5 min-w-[180px]">
                  Catatan Item
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
                class="hover:bg-purple-50/20 transition-colors"
              >
                <!-- Nomor Urut -->
                <td class="py-1.5 px-2.5 text-center text-gray-400 font-mono text-[11px]">
                  {{ index + 1 }}
                </td>

                <!-- Produk Combobox -->
                <td class="py-1.5 px-2.5">
                  <BaseCombobox
                    v-model="item.product_id"
                    :options="products"
                    size="xs"
                    placeholder="Pilih / cari produk (SKU, Nama)..."
                    required
                  />
                  <p
                    v-if="store.validationErrors?.[`items.${index}.product_id`]"
                    class="mt-1 text-[10px] text-rose-600"
                  >
                    {{ store.validationErrors[`items.${index}.product_id`][0] }}
                  </p>
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
                    class="block w-full text-right font-mono rounded-md border-gray-300 text-xs py-1 px-2 font-bold text-gray-900 focus:border-purple-500 focus:ring-purple-500"
                    required
                    @blur="handleQtyBlur(item)"
                  >
                  <p
                    v-if="store.validationErrors?.[`items.${index}.quantity`]"
                    class="mt-1 text-[10px] text-rose-600"
                  >
                    {{ store.validationErrors[`items.${index}.quantity`][0] }}
                  </p>
                </td>

                <!-- Subtotal / Estimasi Nilai -->
                <td class="py-1.5 px-2.5 text-right font-mono text-[11px] font-bold text-gray-900">
                  {{ formatRupiah(getItemSubtotal(item)) }}
                </td>

                <!-- Catatan Item -->
                <td class="py-1.5 px-2.5">
                  <input
                    v-model="item.item_notes"
                    type="text"
                    placeholder="Alasan/kondisi item..."
                    class="block w-full rounded-md border-gray-300 text-xs py-1 px-2 focus:border-purple-500 focus:ring-purple-500"
                  >
                </td>

                <!-- Hapus Baris -->
                <td class="py-1.5 px-2 text-center">
                  <button
                    type="button"
                    class="p-1 rounded text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer disabled:opacity-30"
                    :disabled="form.items.length <= 1"
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
                  colspan="7"
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
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                      />
                    </svg>
                    <span class="font-medium text-gray-500">Belum ada item penyesuaian stok.</span>
                    <span class="text-[11px] text-gray-400 mt-0.5">Gunakan scanner barcode di atas atau tekan "+ Baris Manual".</span>
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
                  Grand Total Estimasi Nilai:
                </td>
                <td class="py-2 px-2.5 text-right font-mono font-black text-purple-800">
                  {{ formatQuantity(totalAdjustmentQty) }}
                </td>
                <td class="py-2 px-2.5 text-right font-mono font-black text-indigo-700">
                  {{ formatRupiah(grandTotal) }}
                </td>
                <td colspan="2" />
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
import { useStockAdjustmentStore } from '../stores/useStockAdjustmentStore';
import { locationApi } from '@features/location/api/location_api.js';
import { productApi } from '@features/product/api/product_api.js';
import { formatRupiah, formatQuantity } from '@/shared/utils/formatters';
import BaseCombobox from '@/shared/components/BaseCombobox.vue';
import BarcodeScannerPanel from '../scanner/components/BarcodeScannerPanel.vue';

const route = useRoute();
const router = useRouter();
const store = useStockAdjustmentStore();

const isEdit = computed(() => !!route.params.id);
const todayDate = new Date().toISOString().substring(0, 10);
const scannerPanelRef = ref(null);

const form = reactive({
  adjustment_date: todayDate,
  location_id: '',
  direction: 'INCREASE',
  reason_code: 'FOUND',
  notes: '',
  items: [
    { product_id: '', quantity: '1', item_notes: '' }
  ]
});

const locations = ref([]);
const products = ref([]);
const notesError = ref('');

const allReasons = [
  { value: 'FOUND', label: 'Barang ditemukan (Hanya INCREASE)', allowed: ['INCREASE'] },
  { value: 'DAMAGED', label: 'Barang rusak (Hanya DECREASE)', allowed: ['DECREASE'] },
  { value: 'EXPIRED', label: 'Barang kedaluwarsa (Hanya DECREASE)', allowed: ['DECREASE'] },
  { value: 'LOST', label: 'Kehilangan barang (Hanya DECREASE)', allowed: ['DECREASE'] },
  { value: 'RECORDING_ERROR', label: 'Kesalahan pencatatan', allowed: ['INCREASE', 'DECREASE'] },
  { value: 'ADMINISTRATIVE', label: 'Koreksi administratif', allowed: ['INCREASE', 'DECREASE'] },
  { value: 'OTHER', label: 'Lain-lain (Notes Wajib)', allowed: ['INCREASE', 'DECREASE'] },
];

const compatibleReasons = computed(() => {
  return allReasons.filter(r => r.allowed.includes(form.direction));
});

const totalAdjustmentQty = computed(() => {
  return form.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
});

const onDirectionChange = () => {
  const isStillCompatible = compatibleReasons.value.some(r => r.value === form.reason_code);
  if (!isStillCompatible) {
    form.reason_code = '';
  }
};

const onReasonChange = () => {
  if (form.reason_code !== 'OTHER') {
    notesError.value = '';
  }
};

const getItemUnitPrice = (productId) => {
  if (!productId) return 0;
  const p = products.value.find(prod => prod.id === productId);
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
  if (item.quantity) {
    item.quantity = String(item.quantity).trim().replace(/\.0+$/, '').replace(/(\.[0-9]*[1-9])0+$/, '$1');
  }
};

const handleProductScanned = (scannedProduct) => {
  if (!scannedProduct || !scannedProduct.id) return;

  const existsInProducts = products.value.some(p => p.id === scannedProduct.id);
  if (!existsInProducts) {
    products.value.push(scannedProduct);
  }

  const existingIndex = form.items.findIndex(item => item.product_id === scannedProduct.id);
  if (existingIndex !== -1) {
    const cur = parseFloat(form.items[existingIndex].quantity) || 0;
    form.items[existingIndex].quantity = String(cur + 1);
  } else {
    if (form.items.length === 1 && !form.items[0].product_id) {
      form.items[0] = { product_id: scannedProduct.id, quantity: '1', item_notes: '' };
    } else {
      form.items.push({ product_id: scannedProduct.id, quantity: '1', item_notes: '' });
    }
  }
};

const addItemRow = () => {
  form.items.push({ product_id: '', quantity: '1', item_notes: '' });
};

const removeItemRow = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1);
  }
};

const loadMasterData = async () => {
  try {
    const [locRes, prodRes] = await Promise.all([
      locationApi.getAll({ is_active: true, assigned_only: 1, per_page: 1000 }),
      productApi.getAll({ is_active: true, per_page: 1000 })
    ]);
    let loadedLocations = locRes.data?.data?.data || locRes.data?.data || [];
    if (loadedLocations.length === 0) {
      const allLocRes = await locationApi.getAll({ is_active: true, per_page: 1000 });
      loadedLocations = allLocRes.data?.data?.data || allLocRes.data?.data || [];
    }
    locations.value = loadedLocations;
    products.value = prodRes.data?.data?.data || prodRes.data?.data || [];

    // Default to ADM location if creating new adjustment
    if (!isEdit.value && locations.value.length > 0) {
      if (route.query.location_id) {
        const queryLoc = locations.value.find((l) => String(l.id) === String(route.query.location_id));
        if (queryLoc) {
          form.location_id = queryLoc.id;
          return;
        }
      }
      if (!form.location_id) {
        const admLoc = locations.value.find((l) =>
          (l.code && l.code.toUpperCase() === 'ADM') ||
          (l.name && l.name.toUpperCase().includes('ADM'))
        );
        form.location_id = admLoc ? admLoc.id : locations.value[0].id;
      }
    }
  } catch {
    store.error = 'Gagal memuat data master lokasi atau produk.';
  }
};

const submitForm = async () => {
  store.error = null;
  notesError.value = '';

  if (!form.location_id) {
    store.error = 'Lokasi Gudang wajib dipilih.';
    return;
  }

  if (form.reason_code === 'OTHER' && (!form.notes || form.notes.trim() === '')) {
    notesError.value = 'Catatan wajib diisi jika alasan penyesuaian adalah Lain-lain.';
    return;
  }

  for (const item of form.items) {
    if (!item.product_id) {
      store.error = 'Semua baris item harus memilih produk.';
      return;
    }
  }

  const productIds = form.items.map((i) => i.product_id).filter(Boolean);
  const duplicates = productIds.filter((id, idx) => productIds.indexOf(id) !== idx);
  if (duplicates.length > 0) {
    store.error = 'Terdapat produk duplikat pada baris item penyesuaian. Setiap produk hanya boleh dipilih satu kali.';
    return;
  }

  try {
    const payload = {
      adjustment_date: form.adjustment_date,
      location_id: form.location_id,
      direction: form.direction,
      reason_code: form.reason_code,
      notes: form.notes ? form.notes.trim() : '',
      items: form.items.map(i => ({
        product_id: i.product_id,
        quantity: i.quantity,
        item_notes: i.item_notes ? i.item_notes.trim() : ''
      }))
    };

    if (isEdit.value) {
      await store.updateAdjustment(route.params.id, payload);
      router.push(`/inventory/adjustments/${route.params.id}`);
    } else {
      const res = await store.createAdjustment(payload);
      router.push(`/inventory/adjustments/${res.data.id}`);
    }
  } catch {
    // Handled by store
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
  store.resetFormErrors();
  await loadMasterData();

  if (isEdit.value) {
    try {
      const data = await store.fetchAdjustmentById(route.params.id);
      if (data.status !== 'DRAFT') {
        store.error = 'Hanya dokumen berstatus DRAFT yang dapat diedit.';
        router.push(`/inventory/adjustments/${data.id}`);
        return;
      }
      if (data.abilities && !data.abilities.can_update) {
        store.error = 'Anda tidak memiliki hak akses untuk mengedit draft adjustment ini.';
        router.push(`/inventory/adjustments/${data.id}`);
        return;
      }
      form.adjustment_date = data.adjustment_date;
      form.location_id = data.location_id;
      form.direction = data.direction;
      form.reason_code = data.reason_code;
      form.notes = data.notes || '';
      form.items = data.items.map(i => ({
        product_id: i.product_id,
        quantity: String(i.quantity).replace(/\.0+$/, '').replace(/(\.[0-9]*[1-9])0+$/, '$1'),
        item_notes: i.item_notes || ''
      }));
    } catch {
      // Handled by store
    }
  }
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleGlobalKeyDown);
});
</script>
