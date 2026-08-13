<template>
  <div class="px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="sm:flex sm:items-center justify-between">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          {{ isEdit ? 'Edit Draft Penerimaan' : 'Buat Draft Penerimaan Stok' }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Isi form di bawah atau gunakan Barcode Scanner untuk mencatat mutasi masuk barang.
        </p>
      </div>
      <div class="mt-4 sm:mt-0 flex gap-2">
        <router-link
          :to="isEdit ? `/inventory/receipts/${route.params.id}` : '/inventory/receipts'"
          class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 cursor-pointer"
        >
          Batal
        </router-link>
        <button
          id="btn-save-receipt-draft"
          type="button"
          :disabled="isSubmitting"
          class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 cursor-pointer"
          @click="submitForm"
        >
          {{ isSubmitting ? 'Menyimpan...' : 'Simpan Draft' }}
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
            <span class="w-6 h-6 rounded bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">📷</span>
            Barcode Scanner Entry
          </h2>
          <p class="text-xs text-gray-500 mt-0.5">
            Pilih Lokasi Scan lalu arahkan scanner barcode (HID/Keyboard Wedge).
          </p>
        </div>

        <div class="w-full sm:w-64">
          <label
            for="receipt-scan-location"
            class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1"
          >
            Lokasi Scan Aktif *
          </label>
          <select
            id="receipt-scan-location"
            v-model="scanLocationId"
            class="block w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
          >
            <option
              value=""
              disabled
            >
              Pilih Lokasi Scan
            </option>
            <option
              v-for="loc in locations"
              :key="loc.id"
              :value="loc.id"
            >
              {{ loc.code }} — {{ loc.name }}
            </option>
          </select>
        </div>
      </div>

      <BarcodeScannerPanel
        :location-selected="Boolean(scanLocationId)"
        placeholder="Scan barcode produk penerimaan (cth: 000123456789)..."
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
          >Tanggal Penerimaan *</label>
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
            for="supplier"
            class="block text-sm font-medium text-gray-700 mb-1"
          >Supplier *</label>
          <select
            id="supplier"
            v-model="form.supplier_id"
            class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            required
          >
            <option
              value=""
              disabled
            >
              Pilih Supplier
            </option>
            <option
              v-for="sup in suppliers"
              :key="sup.id"
              :value="sup.id"
            >
              {{ sup.name }} ({{ sup.code }})
            </option>
          </select>
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
            Daftar Item Produk Masuk
          </h3>
          <button
            id="btn-add-receipt-item"
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
                <th class="py-2.5 px-3">
                  Produk *
                </th>
                <th class="py-2.5 px-3">
                  Lokasi Tujuan *
                </th>
                <th class="py-2.5 px-3 text-right w-36">
                  Jumlah (Qty) *
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
                  <select
                    v-model="item.product_id"
                    class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                    required
                  >
                    <option
                      value=""
                      disabled
                    >
                      Pilih Produk
                    </option>
                    <option
                      v-for="p in products"
                      :key="p.id"
                      :value="p.id"
                    >
                      {{ p.sku }} — {{ p.name }} {{ p.barcode ? `(${p.barcode})` : '' }}
                    </option>
                  </select>
                </td>
                <td class="py-2.5 px-3">
                  <select
                    v-model="item.location_id"
                    class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                    required
                  >
                    <option
                      value=""
                      disabled
                    >
                      Pilih Lokasi
                    </option>
                    <option
                      v-for="loc in locations"
                      :key="loc.id"
                      :value="loc.id"
                    >
                      {{ loc.code }} — {{ loc.name }}
                    </option>
                  </select>
                </td>
                <td class="py-2.5 px-3">
                  <input
                    v-model="item.quantity"
                    type="text"
                    inputmode="decimal"
                    class="block w-full text-right font-mono rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                    required
                    @blur="handleQtyBlur(item)"
                  >
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
                  colspan="5"
                  class="py-6 text-center text-gray-400"
                >
                  Belum ada item. Gunakan scanner di atas atau tombol Tambah Baris Manual.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStockReceiptStore } from '../stores/useStockReceiptStore';
import { supplierApi } from '@/features/supplier/api/supplier_api.js';
import { productApi } from '@/features/product/api/product_api.js';
import { locationApi } from '@/features/location/api/location_api.js';
import BarcodeScannerPanel from '../scanner/components/BarcodeScannerPanel.vue';
import {
  addDecimal4Strings,
  normalizeDecimal4String,
  tryNormalizeDecimal4String,
  isValidDecimal4String,
  compareDecimal4Strings,
} from '../scanner/utils/decimal_string.js';

const route = useRoute();
const router = useRouter();
const store = useStockReceiptStore();

const isEdit = route.name === 'stockReceiptsEdit';
const isSubmitting = ref(false);
const errorMsg = ref('');
const scanLocationId = ref('');

const form = ref({
  supplier_id: '',
  date: new Date().toISOString().slice(0, 10),
  notes: '',
  items: [],
});

const suppliers = ref([]);
const products = ref([]);
const locations = ref([]);

const fetchDependencies = async () => {
  try {
    const [supRes, prodRes, locRes] = await Promise.all([
      supplierApi.getAll({ is_active: 1, per_page: 100 }),
      productApi.getAll({ is_active: 1, per_page: 1000 }),
      locationApi.getAll({ is_active: 1, assigned_only: 1, per_page: 200 }),
    ]);

    suppliers.value = supRes.data?.data?.data || supRes.data?.data || [];
    products.value = prodRes.data?.data?.data || prodRes.data?.data || [];
    locations.value = locRes.data?.data?.data || locRes.data?.data || [];

    if (locations.value.length > 0) {
      scanLocationId.value = locations.value[0].id;
    }
  } catch {
    errorMsg.value = 'Gagal memuat master data produk, lokasi, atau supplier.';
  }
};

const loadReceipt = async () => {
  try {
    const data = await store.fetchReceiptById(route.params.id);
    if (data.status !== 'DRAFT') {
      alert('Hanya DRAFT yang dapat diedit.');
      router.push(`/inventory/receipts/${route.params.id}`);
      return;
    }
    form.value = {
      supplier_id: data.supplier_id,
      date: data.date,
      notes: data.notes || '',
      items: data.items.map((i) => ({
        product_id: i.product_id,
        location_id: i.location_id,
        quantity: normalizeDecimal4String(i.quantity),
      })),
    };
  } catch {
    errorMsg.value = store.error || 'Gagal memuat data dokumen.';
  }
};

const handleQtyBlur = (item) => {
  const norm = tryNormalizeDecimal4String(item.quantity);
  if (norm !== null) {
    item.quantity = norm;
  }
};

const handleProductScanned = (scannedProduct) => {
  if (!scanLocationId.value) {
    errorMsg.value = 'Pilih lokasi scan terlebih dahulu.';
    return;
  }

  // Ensure product is present in dropdown products array
  const existsInProducts = products.value.some((p) => p.id === scannedProduct.id);
  if (!existsInProducts) {
    products.value.push(scannedProduct);
  }

  // Canonical replacement: if form currently has exactly 1 unselected placeholder item, replace it!
  if (form.value.items.length === 1 && !form.value.items[0].product_id) {
    form.value.items[0] = {
      product_id: scannedProduct.id,
      location_id: scanLocationId.value,
      quantity: '1.0000',
    };
    errorMsg.value = '';
    return;
  }

  // Check if item combination (product_id + location_id) already exists in form.items
  const existingItemIndex = form.value.items.findIndex(
    (item) => item.product_id === scannedProduct.id && String(item.location_id) === String(scanLocationId.value)
  );

  if (existingItemIndex !== -1) {
    const currentQty = form.value.items[existingItemIndex].quantity;
    if (!isValidDecimal4String(currentQty)) {
      errorMsg.value = `Kuantitas saat ini pada baris produk "${scannedProduct.name}" (${currentQty}) tidak valid. Harap perbaiki kuantitas sebelum melakukan scan ulang.`;
      return;
    }
    // Increment existing quantity by exact "1.0000" using string arithmetic
    form.value.items[existingItemIndex].quantity = addDecimal4Strings(currentQty, '1.0000');
  } else {
    // Add new item row with initial quantity "1.0000"
    form.value.items.push({
      product_id: scannedProduct.id,
      location_id: scanLocationId.value,
      quantity: '1.0000',
    });
  }

  errorMsg.value = '';
};

const addItem = () => {
  form.value.items.push({
    product_id: '',
    location_id: scanLocationId.value || (locations.value[0]?.id || ''),
    quantity: '1.0000',
  });
};

const removeItem = (index) => {
  form.value.items.splice(index, 1);
};

const submitForm = async () => {
  if (form.value.items.length === 0) {
    errorMsg.value = 'Minimal harus ada 1 baris item produk.';
    return;
  }

  // Duplicate validation and strict decimal validation
  const combos = new Set();
  for (const item of form.value.items) {
    if (!item.product_id || !item.location_id) {
      errorMsg.value = 'Semua baris item harus memilih produk dan lokasi.';
      return;
    }
    if (!isValidDecimal4String(item.quantity) || compareDecimal4Strings(item.quantity, '0.0000') <= 0) {
      errorMsg.value = 'Kuantitas item harus berupa angka positif valid dengan maksimal 4 desimal.';
      return;
    }
    const key = `${item.product_id}-${item.location_id}`;
    if (combos.has(key)) {
      errorMsg.value = 'Tidak boleh ada produk dan lokasi yang sama dalam satu dokumen.';
      return;
    }
    combos.add(key);
  }

  isSubmitting.value = true;
  errorMsg.value = '';

  try {
    if (isEdit) {
      await store.updateReceipt(route.params.id, form.value);
      router.push(`/inventory/receipts/${route.params.id}`);
    } else {
      const data = await store.createReceipt(form.value);
      router.push(`/inventory/receipts/${data.data.id}`);
    }
  } catch (e) {
    errorMsg.value = store.error || e.response?.data?.message || 'Gagal menyimpan dokumen.';
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(async () => {
  await fetchDependencies();
  if (isEdit) {
    await loadReceipt();
  } else if (form.value.items.length === 0) {
    addItem();
  }
});
</script>
