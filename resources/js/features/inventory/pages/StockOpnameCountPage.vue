<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center">
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
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex gap-2">
        <router-link
          :to="`/inventory/opnames/${route.params.id}`"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
        >
          ← Kembali ke Detail
        </router-link>
      </div>
    </div>

    <!-- Guard: only IN_PROGRESS -->
    <div
      v-if="!store.loadingDetail && opname && opname.status !== 'IN_PROGRESS'"
      class="mt-8 rounded-md bg-yellow-50 p-6 text-center"
    >
      <p class="text-sm font-medium text-yellow-800">
        Sesi opname tidak dalam status <strong>Sedang Dihitung</strong>.
        Ruang hitung hanya tersedia selama opname berlangsung.
      </p>
      <router-link
        :to="`/inventory/opnames/${route.params.id}`"
        class="mt-4 inline-block rounded-md bg-yellow-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-yellow-500"
      >
        Lihat Detail Opname
      </router-link>
    </div>

    <template v-else-if="opname && opname.status === 'IN_PROGRESS'">
      <!-- Error Alert -->
      <div
        v-if="store.error"
        class="mt-4 rounded-md bg-red-50 p-4"
      >
        <p class="text-sm font-medium text-red-800">
          {{ store.error }}
        </p>
      </div>

      <!-- Blind Count Notice -->
      <div class="mt-6 rounded-md bg-blue-50 border border-blue-200 p-4">
        <p class="text-sm text-blue-800">
          <strong>Mode Blind Count:</strong> Stok sistem dan selisih tidak ditampilkan selama
          penghitungan berlangsung untuk menghindari bias. Angka akan terlihat setelah sesi
          diselesaikan (<em>Complete</em>).
        </p>
      </div>

      <!-- Progress Bar -->
      <div class="mt-4">
        <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
          <span>
            Progress: <strong class="text-indigo-700">{{ countedCount }}</strong> / {{ items.length }} item dihitung
          </span>
          <span>{{ progressPercent }}%</span>
        </div>
        <div class="h-2 w-full rounded-full bg-gray-200 overflow-hidden">
          <div
            class="h-full bg-indigo-500 rounded-full transition-all duration-300"
            :style="{ width: progressPercent + '%' }"
          />
        </div>
      </div>

      <!-- Search / Filter for items -->
      <div class="mt-4 flex gap-3 items-center">
        <input
          v-model="searchItem"
          type="text"
          placeholder="Cari produk..."
          class="block w-64 rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
          <input
            v-model="showUncountedOnly"
            type="checkbox"
            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
          >
          Tampilkan belum dihitung saja
        </label>

        <!-- Add Unexpected Product -->
        <button
          v-if="abilities.can_add_item"
          type="button"
          class="ml-auto inline-flex items-center rounded-md bg-orange-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-orange-500"
          @click="showAddUnexpected = true"
        >
          + Produk Tak Terduga
        </button>
      </div>

      <!-- Items counting table -->
      <div class="mt-4 bg-white shadow sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-300">
          <thead class="bg-gray-50">
            <tr>
              <th
                scope="col"
                class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"
              >
                Produk
              </th>
              <th
                scope="col"
                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
              >
                SKU
              </th>
              <th
                scope="col"
                class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
              >
                Satuan
              </th>
              <th
                scope="col"
                class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900"
              >
                Qty Hitung Fisik
              </th>
              <th
                scope="col"
                class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900"
              >
                Status
              </th>
              <th
                scope="col"
                class="relative py-3.5 pl-3 pr-4 sm:pr-6"
              >
                <span class="sr-only">Simpan</span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-if="filteredItems.length === 0">
              <td
                colspan="6"
                class="py-8 text-center text-sm text-gray-500"
              >
                Tidak ada item sesuai pencarian.
              </td>
            </tr>
            <tr
              v-for="item in filteredItems"
              :key="item.id"
              :class="{ 'bg-green-50': item.is_counted && !store.countConflicts[item.id] }"
            >
              <!-- Product name -->
              <td class="py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                {{ item.product?.name || item.product_name || '-' }}
                <span
                  v-if="item.is_unexpected"
                  class="ml-1 inline-flex items-center rounded-full bg-orange-100 px-1.5 py-0.5 text-xs font-medium text-orange-700"
                >
                  Tak Terduga
                </span>
              </td>
              <!-- SKU -->
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 font-mono">
                {{ item.product?.sku || '-' }}
              </td>
              <!-- Unit -->
              <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                {{ item.product?.unit?.symbol || item.product?.unit?.name || '-' }}
              </td>
              <!-- Count input -->
              <td class="whitespace-nowrap px-3 py-4 text-sm text-right">
                <div class="flex items-center justify-end gap-2">
                  <!-- Conflict warning -->
                  <span
                    v-if="store.countConflicts[item.id]"
                    class="text-xs text-orange-600"
                    title="Data diperbarui server. Masukkan ulang jumlah."
                  >
                    ⚠ Konflik
                  </span>
                  <input
                    :id="`count-${item.id}`"
                    v-model="countInputs[item.id]"
                    type="number"
                    step="0.0001"
                    min="0"
                    placeholder="0"
                    class="w-28 rounded-md border border-gray-300 px-2 py-1 text-sm font-mono text-right focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    :class="{
                      'border-green-400 bg-green-50': item.is_counted && !store.countConflicts[item.id],
                      'border-orange-400': store.countConflicts[item.id],
                    }"
                    :aria-label="`Jumlah hitung fisik untuk ${item.product?.name || '-'}`"
                  >
                </div>
              </td>
              <!-- Status badge -->
              <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                <span
                  v-if="store.countConflicts[item.id]"
                  class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-800"
                >
                  Konflik
                </span>
                <span
                  v-else-if="item.is_counted"
                  class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800"
                >
                  ✓ Sudah
                </span>
                <span
                  v-else
                  class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600"
                >
                  Belum
                </span>
              </td>
              <!-- Save button -->
              <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm sm:pr-6">
                <button
                  type="button"
                  :disabled="store.loadingItemCount[item.id] || !countInputs[item.id]"
                  class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                  @click="saveCount(item)"
                >
                  {{ store.loadingItemCount[item.id] ? '...' : 'Simpan' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Complete button (bottom shortcut) -->
      <div class="mt-6 flex justify-end">
        <button
          v-if="abilities.can_complete"
          :disabled="isAnyActionLoading"
          class="rounded-md bg-yellow-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-yellow-400 disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-500"
          @click="showCompleteConfirm = true"
        >
          Selesai Hitung →
        </button>
      </div>
    </template>

    <!-- Loading -->
    <div
      v-else-if="store.loadingDetail"
      class="mt-8 text-center text-gray-500"
    >
      Memuat data...
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
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        aria-hidden="true"
        @click="showAddUnexpected = false"
      />
      <div class="flex min-h-screen items-center justify-center px-4 py-8">
        <div class="relative w-full max-w-md rounded-lg bg-white shadow-xl">
          <div class="px-6 pt-6 pb-4">
            <h3
              id="add-unexpected-title"
              class="text-base font-semibold leading-6 text-gray-900"
            >
              Tambah Produk Tak Terduga
            </h3>
            <p class="mt-1 text-sm text-gray-500">
              Produk yang ditemukan saat penghitungan fisik namun tidak ada dalam daftar opname
              (saldo sistem = 0 atau tidak terdaftar di lokasi ini).
            </p>
            <div class="mt-4 space-y-4">
              <div>
                <label
                  for="unexpected-product"
                  class="block text-sm font-medium text-gray-700"
                >
                  Produk
                  <span
                    class="text-red-500"
                    aria-hidden="true"
                  >*</span>
                </label>
                <select
                  id="unexpected-product"
                  v-model="unexpectedForm.product_id"
                  class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  :class="{ 'border-red-500': store.validationErrors.product_id }"
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
                    {{ prod.sku }} — {{ prod.name }}
                  </option>
                </select>
                <p
                  v-if="store.validationErrors.product_id"
                  class="mt-1 text-sm text-red-600"
                >
                  {{ store.validationErrors.product_id[0] }}
                </p>
              </div>
              <div>
                <label
                  for="unexpected-qty"
                  class="block text-sm font-medium text-gray-700"
                >
                  Qty Hitung Fisik
                  <span
                    class="text-red-500"
                    aria-hidden="true"
                  >*</span>
                </label>
                <input
                  id="unexpected-qty"
                  v-model="unexpectedForm.counted_quantity"
                  type="number"
                  step="0.0001"
                  min="0"
                  placeholder="0"
                  class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-mono text-right focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  :class="{ 'border-red-500': store.validationErrors.counted_quantity }"
                >
                <p
                  v-if="store.validationErrors.counted_quantity"
                  class="mt-1 text-sm text-red-600"
                >
                  {{ store.validationErrors.counted_quantity[0] }}
                </p>
              </div>
              <div
                v-if="store.error"
                class="rounded-md bg-red-50 p-3"
              >
                <p class="text-sm text-red-700">
                  {{ store.error }}
                </p>
              </div>
            </div>
          </div>
          <div class="flex flex-row-reverse gap-2 rounded-b-lg bg-gray-50 px-6 py-4">
            <button
              type="button"
              :disabled="store.loadingAction.save"
              class="inline-flex justify-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-500 disabled:opacity-50"
              @click="submitUnexpected"
            >
              {{ store.loadingAction.save ? 'Menyimpan...' : 'Tambah' }}
            </button>
            <button
              type="button"
              class="inline-flex justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
              @click="showAddUnexpected = false"
            >
              Batal
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Complete Confirm Dialog (bottom shortcut) -->
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
          class="block mt-2 text-orange-600 font-medium"
        >
          ⚠ Masih ada {{ uncountedCount }} item belum dihitung.
        </span>
      </p>
    </ConfirmDialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useStockOpnameStore } from '../stores/useStockOpnameStore';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import apiClient from '@/shared/api/api_client';

const route = useRoute();
const store = useStockOpnameStore();

const opname = computed(() => store.currentOpname);
const abilities = computed(() => store.abilities);
const items = computed(() => store.currentItems);

const searchItem = ref('');
const showUncountedOnly = ref(false);
const showAddUnexpected = ref(false);
const showCompleteConfirm = ref(false);

// keyed by item.id, string value (not parsed)
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
                (i.product?.name || i.product_name || '').toLowerCase().includes(q) ||
                (i.product?.sku || '').toLowerCase().includes(q),
        );
    }
    return result;
});

const countedCount = computed(() => items.value.filter((i) => i.is_counted).length);
const uncountedCount = computed(() => items.value.length - countedCount.value);
const progressPercent = computed(() =>
    items.value.length === 0 ? 0 : Math.round((countedCount.value / items.value.length) * 100),
);

const isAnyActionLoading = computed(() => Object.values(store.loadingAction).some(Boolean));

// Sync countInputs when items update (after saveItemCount returns)
watch(
    items,
    (newItems) => {
        newItems.forEach((item) => {
            // Only pre-fill if the item has already been counted (to show existing value)
            if (item.is_counted && item.counted_quantity !== null && item.counted_quantity !== undefined) {
                if (!(item.id in countInputs)) {
                    countInputs[item.id] = String(item.counted_quantity);
                }
            } else if (!(item.id in countInputs)) {
                countInputs[item.id] = '';
            }
        });
    },
    { deep: true },
);

const unexpectedForm = reactive({
    product_id: '',
    counted_quantity: '',
});

async function saveCount(item) {
    const raw = countInputs[item.id];
    if (raw === '' || raw === null || raw === undefined) return;

    // Quantities are sent as strings — do not parseFloat
    await store.saveItemCount(route.params.id, item.id, {
        counted_quantity: String(raw),
        expected_version: item.count_version,
    });
}

async function submitUnexpected() {
    store.resetErrors();
    if (!unexpectedForm.product_id || unexpectedForm.counted_quantity === '') return;

    await store.addUnexpectedProduct(route.params.id, {
        product_id: unexpectedForm.product_id,
        counted_quantity: String(unexpectedForm.counted_quantity),
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
        const res = await apiClient.get('/products', { params: { is_active: 1, per_page: 2000 } });
        products.value = res.data.data.data ?? res.data.data;
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
