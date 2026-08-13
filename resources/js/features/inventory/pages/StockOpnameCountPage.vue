<template>
  <div class="px-4 sm:px-6 lg:px-8 space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center justify-between">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Ruang Hitung — Stock Opname
        </h1>
        <p
          v-if="opname"
          class="mt-1 text-sm text-gray-600"
        >
          {{ opname.opname_number }} · Lokasi: <strong>{{ opname.location_name }}</strong>
        </p>
      </div>
      <div class="mt-4 sm:mt-0 flex gap-2">
        <router-link
          :to="`/inventory/opnames/${route.params.id}`"
          class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 cursor-pointer"
        >
          ← Kembali ke Detail
        </router-link>
      </div>
    </div>

    <!-- Guard: only IN_PROGRESS -->
    <div
      v-if="!store.loadingDetail && opname && opname.status !== 'IN_PROGRESS'"
      class="rounded-xl bg-amber-50 border border-amber-200 p-6 text-center"
    >
      <p class="text-sm font-medium text-amber-800">
        Sesi opname tidak dalam status <strong>Sedang Dihitung</strong>.
        Ruang hitung hanya tersedia selama opname berlangsung.
      </p>
      <router-link
        :to="`/inventory/opnames/${route.params.id}`"
        class="mt-4 inline-block rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-amber-500 cursor-pointer"
      >
        Lihat Detail Opname
      </router-link>
    </div>

    <template v-else-if="opname && opname.status === 'IN_PROGRESS'">
      <!-- Error Alert -->
      <div
        v-if="store.error"
        class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800"
      >
        {{ store.error }}
      </div>

      <!-- Blind Count Notice -->
      <div class="rounded-xl bg-blue-50 border border-blue-200 p-4">
        <p class="text-sm text-blue-900">
          <strong>Mode Blind Count Active:</strong> Stok sistem dan selisih tidak ditampilkan selama
          penghitungan berlangsung untuk menghindari bias. Angka akan terlihat setelah sesi
          diselesaikan (<em>Complete</em>).
        </p>
      </div>

      <!-- Barcode Scanner Section -->
      <div class="bg-white p-4 sm:p-6 shadow-xs rounded-xl border border-gray-200 space-y-3">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-bold">🔍</span>
              Barcode Scanner Locator
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">
              Scan barcode produk untuk langsung menyoroti item dan memfokuskan field jumlah hitung.
            </p>
          </div>
        </div>

        <BarcodeScannerPanel
          :location-selected="true"
          placeholder="Scan barcode produk opname untuk pencarian cepat..."
          @scan-success="handleProductScanned"
          @scan-error="(msg) => { store.error = msg; }"
        />
      </div>

      <!-- Progress Bar -->
      <div class="bg-white p-4 shadow-xs rounded-xl border border-gray-200 space-y-2">
        <div class="flex items-center justify-between text-sm text-gray-600">
          <span>
            Progress Penghitungan: <strong class="text-indigo-700 font-mono">{{ countedCount }}</strong> / {{ items.length }} item dihitung
          </span>
          <span class="font-bold text-gray-900 font-mono">{{ progressPercent }}%</span>
        </div>
        <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden">
          <div
            class="h-full bg-indigo-600 rounded-full transition-all duration-300"
            :style="{ width: progressPercent + '%' }"
          />
        </div>
      </div>

      <!-- Search / Filter for items -->
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white p-4 shadow-xs rounded-xl border border-gray-200">
        <div class="flex flex-wrap items-center gap-3">
          <input
            v-model="searchItem"
            type="text"
            placeholder="Cari nama atau SKU produk..."
            class="block w-full sm:w-64 rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"
          >
          <label class="flex items-center gap-2 text-xs font-medium text-gray-700 cursor-pointer">
            <input
              v-model="showUncountedOnly"
              type="checkbox"
              class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
            >
            Tampilkan belum dihitung saja
          </label>
        </div>

        <!-- Add Unexpected Product -->
        <button
          v-if="abilities.can_add_item"
          id="btn-add-unexpected-product"
          type="button"
          class="inline-flex items-center justify-center rounded-md bg-amber-600 px-3 py-2 text-xs font-semibold text-white shadow-xs hover:bg-amber-500 cursor-pointer"
          @click="showAddUnexpected = true"
        >
          + Produk Tak Terduga
        </button>
      </div>

      <!-- Items counting table -->
      <div class="bg-white shadow-xs rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-200">
                <th class="py-2.5 px-3 w-10 text-center">
                  No.
                </th>
                <th class="py-2.5 px-3">
                  Produk
                </th>
                <th class="py-2.5 px-3 font-mono">
                  SKU
                </th>
                <th class="py-2.5 px-3">
                  Satuan
                </th>
                <th class="py-2.5 px-3 text-right">
                  Qty Hitung Fisik *
                </th>
                <th class="py-2.5 px-3 text-center">
                  Status
                </th>
                <th class="py-2.5 px-3 text-center w-20">
                  Aksi
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <tr v-if="filteredItems.length === 0">
                <td
                  colspan="7"
                  class="py-8 text-center text-gray-400"
                >
                  Tidak ada item sesuai pencarian atau filter.
                </td>
              </tr>
              <tr
                v-for="(item, index) in filteredItems"
                :key="item.id"
                :class="[
                  'transition-colors',
                  item.is_counted && !store.countConflicts[item.id] ? 'bg-emerald-50/50' : 'hover:bg-gray-50/80'
                ]"
              >
                <td class="py-2.5 px-3 text-center text-gray-400 font-mono">
                  {{ index + 1 }}
                </td>
                <td class="py-2.5 px-3 font-medium text-gray-900">
                  {{ item.product?.name || item.product_name || '-' }}
                  <span
                    v-if="item.is_unexpected"
                    class="ml-1.5 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 uppercase"
                  >
                    Tak Terduga
                  </span>
                </td>
                <td class="py-2.5 px-3 font-mono text-gray-600">
                  {{ item.product?.sku || '-' }}
                </td>
                <td class="py-2.5 px-3 text-gray-600">
                  {{ item.product?.unit?.symbol || item.product?.unit?.name || '-' }}
                </td>
                <td class="py-2.5 px-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <span
                      v-if="store.countConflicts[item.id]"
                      class="text-xs text-amber-600 font-semibold"
                      title="Data diperbarui server. Masukkan ulang jumlah."
                    >
                      ⚠ Konflik
                    </span>
                    <input
                      :id="`count-${item.id}`"
                      v-model="countInputs[item.id]"
                      type="text"
                      inputmode="decimal"
                      placeholder="0.0000"
                      class="w-32 rounded-md border border-gray-300 px-2.5 py-1.5 text-xs font-mono text-right focus:border-indigo-500 focus:ring-indigo-500"
                      :class="{
                        'border-emerald-400 bg-emerald-50/50': item.is_counted && !store.countConflicts[item.id],
                        'border-amber-400': store.countConflicts[item.id],
                      }"
                      :aria-label="`Jumlah hitung fisik untuk ${item.product?.name || '-'}`"
                      @blur="handleCountBlur(item.id)"
                    >
                  </div>
                </td>
                <td class="py-2.5 px-3 text-center whitespace-nowrap">
                  <span
                    v-if="store.countConflicts[item.id]"
                    class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800"
                  >
                    Konflik
                  </span>
                  <span
                    v-else-if="item.is_counted"
                    class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800"
                  >
                    ✓ Sudah
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600"
                  >
                    Belum
                  </span>
                </td>
                <td class="py-2.5 px-3 text-center">
                  <button
                    type="button"
                    :disabled="store.loadingItemCount[item.id] || !countInputs[item.id]"
                    class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 cursor-pointer"
                    @click="saveCount(item)"
                  >
                    {{ store.loadingItemCount[item.id] ? '...' : 'Simpan' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Complete button (bottom shortcut) -->
      <div class="flex justify-end pt-2">
        <button
          v-if="abilities.can_complete"
          type="button"
          :disabled="isAnyActionLoading"
          class="inline-flex items-center rounded-md bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-amber-500 disabled:opacity-50 cursor-pointer"
          @click="showCompleteConfirm = true"
        >
          Selesai Hitung →
        </button>
      </div>
    </template>

    <!-- Loading -->
    <div
      v-else-if="store.loadingDetail"
      class="py-12 text-center text-gray-500 text-sm"
    >
      Memuat data opname...
    </div>

    <!-- Add Unexpected Product Modal -->
    <div
      v-if="showAddUnexpected"
      class="fixed inset-0 z-50 overflow-y-auto"
      role="dialog"
      aria-modal="true"
      aria-labelledby="add-unexpected-title"
    >
      <div
        class="fixed inset-0 bg-gray-500/75 transition-opacity"
        aria-hidden="true"
        @click="showAddUnexpected = false"
      />
      <div class="flex min-h-screen items-center justify-center px-4 py-8">
        <div class="relative w-full max-w-md rounded-xl bg-white shadow-xl p-6 space-y-4">
          <h3
            id="add-unexpected-title"
            class="text-base font-bold text-gray-900"
          >
            Tambah Produk Tak Terduga
          </h3>
          <p class="text-xs text-gray-500">
            Produk yang ditemukan saat penghitungan fisik namun tidak ada dalam daftar opname.
          </p>

          <div class="space-y-4">
            <div>
              <label
                for="unexpected-product"
                class="block text-xs font-semibold text-gray-700 mb-1"
              >
                Produk *
              </label>
              <select
                id="unexpected-product"
                v-model="unexpectedForm.product_id"
                class="block w-full rounded-md border border-gray-300 px-3 py-2 text-xs text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"
                :class="{ 'border-rose-500': store.validationErrors.product_id }"
              >
                <option
                  value=""
                  disabled
                >
                  Pilih Produk
                </option>
                <option
                  v-for="prod in products"
                  :key="prod.id"
                  :value="prod.id"
                >
                  {{ prod.sku }} — {{ prod.name }} {{ prod.barcode ? `(${prod.barcode})` : '' }}
                </option>
              </select>
              <p
                v-if="store.validationErrors.product_id"
                class="mt-1 text-xs text-rose-600"
              >
                {{ store.validationErrors.product_id[0] }}
              </p>
            </div>

            <div>
              <label
                for="unexpected-qty"
                class="block text-xs font-semibold text-gray-700 mb-1"
              >
                Qty Hitung Fisik *
              </label>
              <input
                id="unexpected-qty"
                v-model="unexpectedForm.counted_quantity"
                type="text"
                inputmode="decimal"
                placeholder="1.0000"
                class="block w-full text-right font-mono rounded-md border border-gray-300 px-3 py-2 text-xs text-gray-900 focus:border-indigo-500 focus:ring-indigo-500"
                :class="{ 'border-rose-500': store.validationErrors.counted_quantity }"
                @blur="handleUnexpectedBlur"
              >
              <p
                v-if="store.validationErrors.counted_quantity"
                class="mt-1 text-xs text-rose-600"
              >
                {{ store.validationErrors.counted_quantity[0] }}
              </p>
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
            <button
              type="button"
              class="rounded-md bg-white px-3 py-2 text-xs font-semibold text-gray-700 border border-gray-300 hover:bg-gray-50 cursor-pointer"
              @click="showAddUnexpected = false"
            >
              Batal
            </button>
            <button
              type="button"
              :disabled="store.loadingAction.addUnexpected"
              class="rounded-md bg-amber-600 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-500 disabled:opacity-50 cursor-pointer"
              @click="submitUnexpected"
            >
              {{ store.loadingAction.addUnexpected ? 'Menambahkan...' : 'Tambah & Simpan' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Complete Confirm Dialog -->
    <ConfirmDialog
      v-if="showCompleteConfirm"
      title="Selesaikan Penghitungan"
      confirm-text="Ya, Selesaikan Hitung"
      variant="warning"
      :loading="store.loadingAction.complete"
      @confirm="doComplete"
      @cancel="showCompleteConfirm = false"
    >
      <p>
        Penghitungan fisik akan diselesaikan. Sistem akan menghitung selisih (variance).
        <span
          v-if="uncountedCount > 0"
          class="block mt-2 text-amber-600 font-semibold"
        >
          ⚠ Masih ada {{ uncountedCount }} item belum dihitung.
        </span>
      </p>
    </ConfirmDialog>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useStockOpnameStore } from '../stores/useStockOpnameStore';
import { productApi } from '@/features/product/api/product_api.js';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import BarcodeScannerPanel from '../scanner/components/BarcodeScannerPanel.vue';
import {
  normalizeDecimal4String,
  tryNormalizeDecimal4String,
  isValidDecimal4String,
  compareDecimal4Strings,
} from '../scanner/utils/decimal_string.js';

const route = useRoute();
const store = useStockOpnameStore();

const opname = computed(() => store.currentOpname);
const abilities = computed(() => store.abilities);
const items = computed(() => store.currentItems);

const searchItem = ref('');
const showUncountedOnly = ref(false);
const showAddUnexpected = ref(false);
const showCompleteConfirm = ref(false);

const countInputs = reactive({});
const products = ref([]);

const filteredItems = computed(() => {
  let result = items.value;
  if (showUncountedOnly.value) {
    result = result.filter((i) => !i.is_counted);
  }
  if (searchItem.value.trim()) {
    const q = searchItem.value.trim().toLowerCase();
    result = result.filter(
      (i) =>
        i.product?.name?.toLowerCase().includes(q) ||
        i.product?.sku?.toLowerCase().includes(q) ||
        i.product?.barcode?.toLowerCase().includes(q)
    );
  }
  return result;
});

const countedCount = computed(() => items.value.filter((i) => i.is_counted).length);
const uncountedCount = computed(() => items.value.length - countedCount.value);
const progressPercent = computed(() =>
  items.value.length === 0 ? 0 : Math.round((countedCount.value / items.value.length) * 100)
);

const isAnyActionLoading = computed(() => Object.values(store.loadingAction).some(Boolean));

watch(
  items,
  (newItems) => {
    newItems.forEach((item) => {
      if (countInputs[item.id] === undefined) {
        countInputs[item.id] =
          item.counted_quantity !== null
            ? normalizeDecimal4String(item.counted_quantity)
            : '';
      }
    });
  },
  { immediate: true }
);

const unexpectedForm = reactive({
  product_id: '',
  counted_quantity: '',
});

const handleProductScanned = (scannedProduct) => {
  store.resetErrors();

  const existingItem = items.value.find(
    (i) => i.product_id === scannedProduct.id || i.product?.id === scannedProduct.id
  );

  if (existingItem) {
    searchItem.value = '';
    showUncountedOnly.value = false;

    nextTick(() => {
      const inputEl = document.getElementById(`count-${existingItem.id}`);
      if (inputEl) {
        inputEl.focus();
        inputEl.select();
        inputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  } else {
    if (abilities.value?.can_add_item) {
      showAddUnexpected.value = true;
      unexpectedForm.product_id = scannedProduct.id;
      unexpectedForm.counted_quantity = '1.0000';
    } else {
      store.error = `Produk ${scannedProduct.name} (${scannedProduct.sku}) ditemukan tetapi tidak termasuk dalam sesi opname ini. Anda tidak memiliki izin menambahkan produk tak terduga.`;
    }
  }
};

const handleCountBlur = (itemId) => {
  if (countInputs[itemId]) {
    const norm = tryNormalizeDecimal4String(countInputs[itemId]);
    if (norm !== null) {
      countInputs[itemId] = norm;
    }
  }
};

const handleUnexpectedBlur = () => {
  if (unexpectedForm.counted_quantity) {
    const norm = tryNormalizeDecimal4String(unexpectedForm.counted_quantity);
    if (norm !== null) {
      unexpectedForm.counted_quantity = norm;
    }
  }
};

async function saveCount(item) {
  const raw = countInputs[item.id];
  if (raw === '' || raw === null || raw === undefined) return;

  if (!isValidDecimal4String(raw) || compareDecimal4Strings(raw, '0.0000') < 0) {
    store.error = `Kuantitas fisik untuk "${item.product?.name || 'produk'}" tidak valid (${raw}). Masukkan angka non-negatif valid dengan maksimal 4 desimal.`;
    return;
  }

  const normalized = normalizeDecimal4String(raw);
  await store.saveItemCount(route.params.id, item.id, {
    counted_quantity: normalized,
    expected_version: item.count_version,
  });
}

async function submitUnexpected() {
  store.resetErrors();
  if (!unexpectedForm.product_id || unexpectedForm.counted_quantity === '') return;

  if (!isValidDecimal4String(unexpectedForm.counted_quantity) || compareDecimal4Strings(unexpectedForm.counted_quantity, '0.0000') <= 0) {
    store.error = `Kuantitas fisik produk tak terduga tidak valid (${unexpectedForm.counted_quantity}). Masukkan angka positif valid dengan maksimal 4 desimal.`;
    return;
  }

  const normalized = normalizeDecimal4String(unexpectedForm.counted_quantity);
  await store.addUnexpectedProduct(route.params.id, {
    product_id: unexpectedForm.product_id,
    counted_quantity: normalized,
  });

  if (!store.error) {
    showAddUnexpected.value = false;
    unexpectedForm.product_id = '';
    unexpectedForm.counted_quantity = '';
  }
}

async function doComplete() {
  showCompleteConfirm.value = false;
  await store.completeOpname(route.params.id);
}

async function loadProducts() {
  try {
    const res = await productApi.getAll({ is_active: 1, per_page: 2000 });
    products.value = res.data?.data?.data ?? res.data?.data ?? [];
  } catch {
    // non-critical
  }
}

onMounted(async () => {
  store.resetActiveOpname();
  await store.fetchOpname(route.params.id);
  await loadProducts();
});
</script>
