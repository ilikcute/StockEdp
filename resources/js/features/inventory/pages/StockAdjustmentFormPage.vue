<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          {{ isEdit ? 'Edit Draft Penyesuaian Stok' : 'Buat Draft Penyesuaian Stok' }}
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Form koreksi kuantitas stok barang karena selisih fisik, kerusakan, kedaluwarsa, atau kesalahan pencatatan.
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex gap-2">
        <router-link
          :to="isEdit ? `/inventory/adjustments/${route.params.id}` : '/inventory/adjustments'"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
        >
          Batal
        </router-link>
        <button
          type="button"
          :disabled="store.loadingAction"
          class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          @click="submitForm"
        >
          Simpan Draft
        </button>
      </div>
    </div>

    <!-- Error Global Alert -->
    <div
      v-if="store.error"
      class="mt-4 rounded-md bg-red-50 p-4"
    >
      <p class="text-sm font-medium text-red-800">
        {{ store.error }}
      </p>
    </div>

    <form
      class="mt-8 space-y-8"
      @submit.prevent="submitForm"
    >
      <!-- Header Section -->
      <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6 bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
        <div class="sm:col-span-2">
          <label
            for="adjustment_date"
            class="block text-sm font-medium text-gray-700"
          >Tanggal Adjustment *</label>
          <div class="mt-1">
            <input
              id="adjustment_date"
              v-model="form.adjustment_date"
              type="date"
              :max="todayDate"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              required
            >
          </div>
          <p
            v-if="store.validationErrors.adjustment_date"
            class="mt-1 text-xs text-red-600"
          >
            {{ store.validationErrors.adjustment_date[0] }}
          </p>
        </div>

        <div class="sm:col-span-2">
          <label
            for="location"
            class="block text-sm font-medium text-gray-700 mb-1"
          >Lokasi Gudang *</label>
          <BaseCombobox
            id="location"
            v-model="form.location_id"
            :options="locations"
            size="md"
            placeholder="Pilih / cari kode atau nama gudang..."
            required
          />
          <p
            v-if="store.validationErrors.location_id"
            class="mt-1 text-xs text-red-600"
          >
            {{ store.validationErrors.location_id[0] }}
          </p>
        </div>

        <div class="sm:col-span-2">
          <label
            for="direction"
            class="block text-sm font-medium text-gray-700"
          >Arah Penyesuaian (Direction) *</label>
          <div class="mt-1">
            <select
              id="direction"
              v-model="form.direction"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              required
              @change="onDirectionChange"
            >
              <option value="INCREASE">
                Penambahan Stok (INCREASE)
              </option>
              <option value="DECREASE">
                Pengurangan Stok (DECREASE)
              </option>
            </select>
          </div>
          <p class="mt-1 text-xs text-gray-500">
            {{ form.direction === 'INCREASE' ? 'Seluruh item akan menambah saldo stok.' : 'Seluruh item akan mengurangi saldo stok.' }}
          </p>
          <p
            v-if="store.validationErrors.direction"
            class="mt-1 text-xs text-red-600"
          >
            {{ store.validationErrors.direction[0] }}
          </p>
        </div>

        <div class="sm:col-span-3">
          <label
            for="reason_code"
            class="block text-sm font-medium text-gray-700"
          >Alasan (Reason Code) *</label>
          <div class="mt-1">
            <select
              id="reason_code"
              v-model="form.reason_code"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              required
              @change="onReasonChange"
            >
              <option
                value=""
                disabled
              >
                Pilih Alasan Penyesuaian
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
          <p
            v-if="store.validationErrors.reason_code"
            class="mt-1 text-xs text-red-600"
          >
            {{ store.validationErrors.reason_code[0] }}
          </p>
        </div>

        <div class="sm:col-span-6">
          <label
            for="notes"
            class="block text-sm font-medium text-gray-700"
          >
            Catatan Dokumen
            <span
              v-if="form.reason_code === 'OTHER'"
              class="text-red-600 font-bold"
            >* (Wajib diisi untuk alasan Lain-lain)</span>
          </label>
          <div class="mt-1">
            <textarea
              id="notes"
              v-model="form.notes"
              rows="2"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :placeholder="form.reason_code === 'OTHER' ? 'Jelaskan alasan penyesuaian secara rinci (Wajib)...' : 'Catatan opsional penyesuaian stok...'"
              :required="form.reason_code === 'OTHER'"
            />
          </div>
          <p
            v-if="notesError"
            class="mt-1 text-xs text-red-600 font-medium"
          >
            {{ notesError }}
          </p>
          <p
            v-if="store.validationErrors.notes"
            class="mt-1 text-xs text-red-600"
          >
            {{ store.validationErrors.notes[0] }}
          </p>
        </div>
      </div>

      <!-- Items Section -->
      <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium leading-6 text-gray-900">
            Daftar Barang Adjustment
          </h3>
          <button
            type="button"
            class="rounded bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-600 shadow-sm hover:bg-indigo-100"
            @click="addItemRow"
          >
            + Tambah Barang
          </button>
        </div>

        <p
          v-if="store.validationErrors.items"
          class="mb-3 text-xs text-red-600 font-medium"
        >
          {{ store.validationErrors.items[0] }}
        </p>

        <div class="overflow-x-auto shadow-sm border border-gray-300 rounded-lg">
          <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
              <tr>
                <th
                  scope="col"
                  class="text-center text-xs font-medium text-gray-700 uppercase tracking-wider py-3.5 px-3 border-b border-gray-300 w-16"
                >
                  No.
                </th>
                <th
                  scope="col"
                  class="text-left text-xs font-medium text-gray-700 uppercase tracking-wider py-3.5 px-3 border-b border-gray-300 min-w-[320px]"
                >
                  Produk *
                </th>
                <th
                  scope="col"
                  class="text-right text-xs font-medium text-gray-700 uppercase tracking-wider py-3.5 px-3 border-b border-gray-300 w-36"
                >
                  Harga Satuan
                </th>
                <th
                  scope="col"
                  class="text-right text-xs font-medium text-gray-700 uppercase tracking-wider py-3.5 px-3 border-b border-gray-300 w-32"
                >
                  Jumlah (Quantity) *
                </th>
                <th
                  scope="col"
                  class="text-right text-xs font-medium text-gray-700 uppercase tracking-wider py-3.5 px-3 border-b border-gray-300 w-36"
                >
                  Estimasi Nilai
                </th>
                <th
                  scope="col"
                  class="text-left text-xs font-medium text-gray-700 uppercase tracking-wider py-3.5 px-3 border-b border-gray-300 w-48"
                >
                  Catatan Item
                </th>
                <th
                  scope="col"
                  class="w-16 py-3.5 border-b border-gray-300"
                />
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr
                v-for="(item, index) in form.items"
                :key="index"
              >
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-center text-gray-500">
                  {{ index + 1 }}
                </td>
                <td class="py-3 px-3 min-w-[320px]">
                  <BaseCombobox
                    v-model="item.product_id"
                    :options="products"
                    size="sm"
                    placeholder="Pilih / cari produk (SKU, Barcode, Nama)..."
                    required
                  />
                  <p
                    v-if="store.validationErrors[`items.${index}.product_id`]"
                    class="mt-1 text-xs text-red-600"
                  >
                    {{ store.validationErrors[`items.${index}.product_id`][0] }}
                  </p>
                </td>
                <td class="py-3 px-3 text-right font-medium text-sm text-gray-700">
                  {{ formatRupiah(getItemUnitPrice(item.product_id)) }}
                </td>
                <td class="py-3 px-3">
                  <input
                    v-model="item.quantity"
                    type="text"
                    aria-label="Jumlah Quantity"
                    class="block w-full text-right font-mono rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="1"
                    required
                    @blur="handleQtyBlur(item)"
                  >
                  <p
                    v-if="store.validationErrors[`items.${index}.quantity`]"
                    class="mt-1 text-xs text-red-600"
                  >
                    {{ store.validationErrors[`items.${index}.quantity`][0] }}
                  </p>
                </td>
                <td class="py-3 px-3 text-right font-mono font-medium text-sm text-gray-900">
                  {{ formatRupiah(getItemSubtotal(item)) }}
                </td>
                <td class="py-3 pr-4">
                  <input
                    v-model="item.item_notes"
                    type="text"
                    aria-label="Catatan Item"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="Contoh: Kondisi bungkus penyok"
                  >
                </td>
                <td class="py-3 text-right">
                  <button
                    type="button"
                    class="text-red-600 hover:text-red-900 text-sm font-medium disabled:opacity-30"
                    :disabled="form.items.length <= 1"
                    @click="removeItemRow(index)"
                  >
                    Hapus
                  </button>
                </td>
              </tr>
            </tbody>
            <tfoot
              v-if="form.items.length > 0"
              class="bg-gray-50 font-semibold text-gray-900 border-t border-gray-300"
            >
              <tr>
                <td
                  colspan="4"
                  class="py-3 px-4 text-right text-sm"
                >
                  Grand Total Estimasi Nilai Penyesuaian:
                </td>
                <td class="py-3 px-3 text-right font-mono text-sm text-indigo-700">
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
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStockAdjustmentStore } from '../stores/useStockAdjustmentStore';
import { locationApi } from '@features/location/api/location_api.js';
import { productApi } from '@features/product/api/product_api.js';
import { formatRupiah } from '@/shared/utils/formatters';
import BaseCombobox from '@/shared/components/BaseCombobox.vue';

const route = useRoute();
const router = useRouter();
const store = useStockAdjustmentStore();

const isEdit = computed(() => !!route.params.id);
const todayDate = new Date().toISOString().substring(0, 10);

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
    window.scrollTo({ top: 0, behavior: 'smooth' });
    return;
  }

  if (form.reason_code === 'OTHER' && (!form.notes || form.notes.trim() === '')) {
    notesError.value = 'Catatan wajib diisi jika alasan penyesuaian adalah Lain-lain.';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    return;
  }

  for (const item of form.items) {
    if (!item.product_id) {
      store.error = 'Semua baris item harus memilih produk.';
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }
  }

  const productIds = form.items.map((i) => i.product_id).filter(Boolean);
  const duplicates = productIds.filter((id, idx) => productIds.indexOf(id) !== idx);
  if (duplicates.length > 0) {
    store.error = 'Terdapat produk duplikat pada baris item penyesuaian. Setiap produk hanya boleh dipilih satu kali.';
    window.scrollTo({ top: 0, behavior: 'smooth' });
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
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

onMounted(async () => {
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
</script>
