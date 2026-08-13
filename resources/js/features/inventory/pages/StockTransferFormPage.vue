<template>
  <div class="px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="sm:flex sm:items-center justify-between">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          {{ isEdit ? 'Edit Draft Transfer Stok' : 'Buat Draft Transfer Stok' }}
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Isi form di bawah atau gunakan Barcode Scanner untuk membuat/mengubah draft pemindahan stok.
        </p>
      </div>
      <div class="mt-4 sm:mt-0 flex gap-2">
        <router-link
          :to="isEdit ? `/inventory/transfers/${route.params.id}` : '/inventory/transfers'"
          class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 cursor-pointer"
        >
          Batal
        </router-link>
        <button
          id="btn-save-transfer-draft"
          type="button"
          :disabled="store.loadingAction"
          class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 cursor-pointer"
          @click="submitForm"
        >
          {{ store.loadingAction ? 'Menyimpan...' : 'Simpan Draft' }}
        </button>
      </div>
    </div>

    <!-- Error Alert -->
    <div
      v-if="store.error || locationError"
      class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800"
    >
      {{ store.error || locationError }}
    </div>

    <!-- Barcode Scanner Section -->
    <div class="bg-white p-4 sm:p-6 shadow-xs rounded-xl border border-gray-200 space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
            <span class="w-6 h-6 rounded bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">📷</span>
            Barcode Scanner Transfer
          </h2>
          <p class="text-xs text-gray-500 mt-0.5">
            Pilih Lokasi Asal & Tujuan terlebih dahulu sebelum melakukan scan barcode item transfer.
          </p>
        </div>
      </div>

      <BarcodeScannerPanel
        :location-selected="Boolean(form.origin_location_id && form.destination_location_id && !locationError)"
        placeholder="Scan barcode produk yang ditransfer (cth: 000123456789)..."
        @scan-success="handleProductScanned"
        @scan-error="(msg) => { locationError = msg; }"
      />
    </div>

    <!-- Header & Items Form -->
    <form
      class="space-y-6"
      @submit.prevent="submitForm"
    >
      <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6 bg-white shadow-xs rounded-xl border border-gray-200 p-4 sm:p-6">
        <div class="sm:col-span-2">
          <label
            for="transfer_date"
            class="block text-sm font-medium text-gray-700 mb-1"
          >Tanggal Transfer *</label>
          <input
            id="transfer_date"
            v-model="form.transfer_date"
            type="date"
            class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            required
          >
          <p
            v-if="store.validationErrors.transfer_date"
            class="mt-1 text-xs text-rose-600"
          >
            {{ store.validationErrors.transfer_date[0] }}
          </p>
        </div>

        <div class="sm:col-span-2">
          <label
            for="origin_location"
            class="block text-sm font-medium text-gray-700 mb-1"
          >Lokasi Asal (Origin) *</label>
          <select
            id="origin_location"
            v-model="form.origin_location_id"
            class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
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
            v-if="store.validationErrors.origin_location_id"
            class="mt-1 text-xs text-rose-600"
          >
            {{ store.validationErrors.origin_location_id[0] }}
          </p>
        </div>

        <div class="sm:col-span-2">
          <label
            for="destination_location"
            class="block text-sm font-medium text-gray-700 mb-1"
          >Lokasi Tujuan (Destination) *</label>
          <select
            id="destination_location"
            v-model="form.destination_location_id"
            class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
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
            v-if="store.validationErrors.destination_location_id"
            class="mt-1 text-xs text-rose-600"
          >
            {{ store.validationErrors.destination_location_id[0] }}
          </p>
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

      <!-- Items Section -->
      <div class="bg-white shadow-xs rounded-xl border border-gray-200 p-4 sm:p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-semibold text-gray-900">
            Daftar Item Produk Transfer
          </h3>
          <button
            id="btn-add-transfer-item"
            type="button"
            class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-indigo-600 shadow-xs ring-1 ring-inset ring-indigo-300 hover:bg-indigo-50 cursor-pointer"
            @click="addItemRow"
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
                      :disabled="isProductSelectedInOtherRow(p.id, index)"
                    >
                      {{ p.sku }} — {{ p.name }} {{ p.barcode ? `(${p.barcode})` : '' }}
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
                    @blur="item.quantity = normalizeDecimal4String(item.quantity)"
                  >
                </td>
                <td class="py-2.5 px-3 text-center">
                  <button
                    type="button"
                    class="text-rose-600 hover:text-rose-900 font-semibold cursor-pointer"
                    @click="removeItemRow(index)"
                  >
                    Hapus
                  </button>
                </td>
              </tr>
              <tr v-if="form.items.length === 0">
                <td
                  colspan="4"
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
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStockTransferStore } from '../stores/useStockTransferStore';
import { locationApi } from '@features/location/api/location_api.js';
import { productApi } from '@features/product/api/product_api.js';
import BarcodeScannerPanel from '../scanner/components/BarcodeScannerPanel.vue';
import { addDecimal4Strings, normalizeDecimal4String } from '../scanner/utils/decimal_string.js';

const route = useRoute();
const router = useRouter();
const store = useStockTransferStore();

const isEdit = computed(() => !!route.params.id);

const form = reactive({
  transfer_date: new Date().toISOString().substring(0, 10),
  origin_location_id: '',
  destination_location_id: '',
  notes: '',
  items: [
    { product_id: '', quantity: '1.0000' }
  ],
});

const userLocations = ref([]);
const allLocations = ref([]);
const products = ref([]);
const locationError = ref('');

const availableDestinations = computed(() => {
  return allLocations.value.filter((loc) => loc.id !== form.origin_location_id);
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
    form.items[existingIndex].quantity = addDecimal4Strings(currentQty, '1.0000');
  } else {
    // If first item in form is empty placeholder, replace it; otherwise push
    if (form.items.length === 1 && !form.items[0].product_id) {
      form.items[0] = { product_id: scannedProduct.id, quantity: '1.0000' };
    } else {
      form.items.push({ product_id: scannedProduct.id, quantity: '1.0000' });
    }
  }

  locationError.value = '';
};

const addItemRow = () => {
  form.items.push({ product_id: '', quantity: '1.0000' });
};

const removeItemRow = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1);
  } else {
    form.items[0] = { product_id: '', quantity: '1.0000' };
  }
};

const loadMasterData = async () => {
  try {
    const [locRes, prodRes] = await Promise.all([
      locationApi.getAll({ is_active: true, per_page: 100 }),
      productApi.getAll({ is_active: true, per_page: 100 }),
    ]);
    allLocations.value = locRes.data?.data?.data || locRes.data?.data || [];
    userLocations.value = allLocations.value;
    products.value = prodRes.data?.data?.data || prodRes.data?.data || [];
  } catch {
    store.error = 'Gagal memuat data master lokasi atau produk.';
  }
};

const submitForm = async () => {
  validateLocations();
  if (locationError.value) return;

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

onMounted(async () => {
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
        quantity: normalizeDecimal4String(i.quantity),
      }));
    } catch {
      // Handled by store
    }
  }
});
</script>
