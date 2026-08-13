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
            <span class="w-6 h-6 rounded bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">📷</span>
            Barcode Scanner Entry
          </h2>
          <p class="text-xs text-gray-500 mt-0.5">
            Pilih Lokasi Scan lalu arahkan scanner barcode untuk menambahkan item pengeluaran.
          </p>
        </div>

        <div class="w-full sm:w-64">
          <label
            for="issue-scan-location"
            class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1"
          >
            Lokasi Scan Asal *
          </label>
          <select
            id="issue-scan-location"
            v-model="scanLocationId"
            class="block w-full rounded-md border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500"
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
        placeholder="Scan barcode produk pengeluaran (cth: 000123456789)..."
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
                <th class="py-2.5 px-3">
                  Produk *
                </th>
                <th class="py-2.5 px-3">
                  Lokasi Asal *
                </th>
                <th class="py-2.5 px-3 text-right">
                  Stok Tersedia
                </th>
                <th class="py-2.5 px-3 text-right w-36">
                  Kuantitas (Qty) *
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
                    @change="fetchStock(index)"
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
                    @change="fetchStock(index)"
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
                <td class="py-2.5 px-3 text-right font-mono">
                  <span
                    :class="[
                      'inline-flex items-center gap-1 font-bold',
                      isQuantityExceeding(item) ? 'text-rose-600' : 'text-gray-900'
                    ]"
                  >
                    {{ item.available_stock !== null ? item.available_stock : '-' }}
                    <span
                      v-if="isQuantityExceeding(item)"
                      class="text-[10px] bg-rose-100 text-rose-800 px-1.5 py-0.5 rounded font-sans"
                    >
                      Stok Kurang
                    </span>
                  </span>
                </td>
                <td class="py-2.5 px-3">
                  <input
                    v-model="item.quantity"
                    type="text"
                    inputmode="decimal"
                    class="block w-full text-right font-mono rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500"
                    required
                    @blur="item.quantity = normalizeDecimal4String(item.quantity)"
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
                  colspan="6"
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
import { useStockIssueStore } from '../stores/useStockIssueStore';
import { productApi } from '@/features/product/api/product_api.js';
import { locationApi } from '@/features/location/api/location_api.js';
import { inventoryApi } from '../api/inventoryApi.js';
import BarcodeScannerPanel from '../scanner/components/BarcodeScannerPanel.vue';
import { addDecimal4Strings, compareDecimal4Strings, normalizeDecimal4String } from '../scanner/utils/decimal_string.js';

const route = useRoute();
const router = useRouter();
const store = useStockIssueStore();

const isEdit = route.name === 'stockIssuesEdit';
const isSubmitting = ref(false);
const errorMsg = ref('');
const scanLocationId = ref('');

const form = ref({
  purpose: '',
  date: new Date().toISOString().slice(0, 10),
  notes: '',
  items: [],
});

const products = ref([]);
const locations = ref([]);

const fetchDependencies = async () => {
  try {
    const [prodRes, locRes] = await Promise.all([
      productApi.getAll({ is_active: 1, per_page: 1000 }),
      locationApi.getAll({ is_active: 1, per_page: 100 }),
    ]);

    products.value = prodRes.data?.data?.data || prodRes.data?.data || [];
    locations.value = locRes.data?.data?.data || locRes.data?.data || [];

    if (locations.value.length > 0) {
      scanLocationId.value = locations.value[0].id;
    }
  } catch {
    errorMsg.value = 'Gagal memuat master data produk atau lokasi.';
  }
};

const fetchStock = async (index) => {
  const item = form.value.items[index];
  if (item && item.product_id && item.location_id) {
    try {
      const res = await inventoryApi.getBalances({
        product_id: item.product_id,
        location_id: item.location_id,
      });
      const data = res.data?.data?.data || res.data?.data || [];
      if (data.length > 0) {
        item.available_stock = normalizeDecimal4String(data[0].quantity);
      } else {
        item.available_stock = '0.0000';
      }
    } catch {
      item.available_stock = '0.0000';
    }
  }
};

const isQuantityExceeding = (item) => {
  if (!item.available_stock || !item.quantity) return false;
  return compareDecimal4Strings(item.quantity, item.available_stock) > 0;
};

const loadIssue = async () => {
  try {
    const data = await store.fetchIssueById(route.params.id);
    if (data.status !== 'DRAFT') {
      alert('Hanya DRAFT yang dapat diedit.');
      router.push(`/inventory/issues/${route.params.id}`);
      return;
    }
    form.value = {
      purpose: data.purpose,
      date: data.date,
      notes: data.notes || '',
      items: data.items.map((i) => ({
        product_id: i.product_id,
        location_id: i.location_id,
        quantity: normalizeDecimal4String(i.quantity),
        available_stock: '0.0000',
      })),
    };

    // Load available stock for existing items
    form.value.items.forEach((_, idx) => fetchStock(idx));
  } catch {
    errorMsg.value = store.error || 'Gagal memuat data dokumen.';
  }
};

const handleProductScanned = async (scannedProduct) => {
  if (!scanLocationId.value) {
    errorMsg.value = 'Pilih lokasi scan terlebih dahulu.';
    return;
  }

  // Ensure product is in products dropdown array
  const existsInProducts = products.value.some((p) => p.id === scannedProduct.id);
  if (!existsInProducts) {
    products.value.push(scannedProduct);
  }

  // Check if item combination (product_id + location_id) already exists in form.items
  const existingItemIndex = form.value.items.findIndex(
    (item) => item.product_id === scannedProduct.id && String(item.location_id) === String(scanLocationId.value)
  );

  if (existingItemIndex !== -1) {
    // Increment existing quantity by exact "1.0000" using string arithmetic
    const currentQty = form.value.items[existingItemIndex].quantity;
    form.value.items[existingItemIndex].quantity = addDecimal4Strings(currentQty, '1.0000');
    await fetchStock(existingItemIndex);
  } else {
    // Add new item row with initial quantity "1.0000"
    const newIdx = form.value.items.length;
    form.value.items.push({
      product_id: scannedProduct.id,
      location_id: scanLocationId.value,
      quantity: '1.0000',
      available_stock: '0.0000',
    });
    await fetchStock(newIdx);
  }

  errorMsg.value = '';
};

const addItem = () => {
  const newIdx = form.value.items.length;
  form.value.items.push({
    product_id: '',
    location_id: scanLocationId.value || (locations.value[0]?.id || ''),
    quantity: '1.0000',
    available_stock: '0.0000',
  });
  if (form.value.items[newIdx].product_id && form.value.items[newIdx].location_id) {
    fetchStock(newIdx);
  }
};

const removeItem = (index) => {
  form.value.items.splice(index, 1);
};

const submitForm = async () => {
  if (form.value.items.length === 0) {
    errorMsg.value = 'Minimal harus ada 1 baris item produk.';
    return;
  }

  // Duplicate validation
  const combos = new Set();
  for (const item of form.value.items) {
    if (!item.product_id || !item.location_id) {
      errorMsg.value = 'Semua baris item harus memilih produk dan lokasi asal.';
      return;
    }
    const key = `${item.product_id}-${item.location_id}`;
    if (combos.has(key)) {
      errorMsg.value = 'Tidak boleh ada produk dan lokasi asal yang sama dalam satu dokumen.';
      return;
    }
    combos.add(key);
  }

  isSubmitting.value = true;
  errorMsg.value = '';

  try {
    if (isEdit) {
      await store.updateIssue(route.params.id, form.value);
      router.push(`/inventory/issues/${route.params.id}`);
    } else {
      const data = await store.createIssue(form.value);
      router.push(`/inventory/issues/${data.data.id}`);
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
    await loadIssue();
  } else if (form.value.items.length === 0) {
    addItem();
  }
});
</script>
