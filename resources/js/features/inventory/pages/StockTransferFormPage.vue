<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          {{ isEdit ? 'Edit Draft Transfer Stok' : 'Buat Draft Transfer Stok' }}
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Isi form di bawah untuk membuat atau mengubah draft pemindahan stok barang antar lokasi.
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex gap-2">
        <router-link
          :to="isEdit ? `/inventory/transfers/${route.params.id}` : '/inventory/transfers'"
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
      <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6 bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
        <!-- Header Section -->
        <div class="sm:col-span-2">
          <label
            for="transfer_date"
            class="block text-sm font-medium text-gray-700"
          >Tanggal Transfer *</label>
          <div class="mt-1">
            <input
              id="transfer_date"
              v-model="form.transfer_date"
              type="date"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              required
            >
          </div>
          <p
            v-if="store.validationErrors.transfer_date"
            class="mt-1 text-xs text-red-600"
          >
            {{ store.validationErrors.transfer_date[0] }}
          </p>
        </div>

        <div class="sm:col-span-2">
          <label
            for="origin_location"
            class="block text-sm font-medium text-gray-700"
          >Lokasi Asal (Origin) *</label>
          <div class="mt-1">
            <select
              id="origin_location"
              v-model="form.origin_location_id"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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
          </div>
          <p
            v-if="store.validationErrors.origin_location_id"
            class="mt-1 text-xs text-red-600"
          >
            {{ store.validationErrors.origin_location_id[0] }}
          </p>
        </div>

        <div class="sm:col-span-2">
          <label
            for="destination_location"
            class="block text-sm font-medium text-gray-700"
          >Lokasi Tujuan (Destination) *</label>
          <div class="mt-1">
            <select
              id="destination_location"
              v-model="form.destination_location_id"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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
          </div>
          <p
            v-if="locationError"
            class="mt-1 text-xs text-red-600"
          >
            {{ locationError }}
          </p>
          <p
            v-if="store.validationErrors.destination_location_id"
            class="mt-1 text-xs text-red-600"
          >
            {{ store.validationErrors.destination_location_id[0] }}
          </p>
        </div>

        <div class="sm:col-span-6">
          <label
            for="notes"
            class="block text-sm font-medium text-gray-700"
          >Catatan</label>
          <div class="mt-1">
            <textarea
              id="notes"
              v-model="form.notes"
              rows="2"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Catatan opsional mengenai dokumen transfer ini..."
            />
          </div>
        </div>
      </div>

      <!-- Items Section -->
      <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium leading-6 text-gray-900">
            Daftar Barang Transfer
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
                  class="text-left text-xs font-medium text-gray-700 uppercase tracking-wider py-3.5 px-3 border-b border-gray-300"
                >
                  Produk *
                </th>
                <th
                  scope="col"
                  class="text-left text-xs font-medium text-gray-700 uppercase tracking-wider py-3.5 px-3 border-b border-gray-300 w-48"
                >
                  Jumlah (Quantity) *
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
                <td class="py-3 px-3">
                  <select
                    v-model="item.product_id"
                    aria-label="Pilih Produk"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    required
                  >
                    <option
                      value=""
                      disabled
                    >
                      Pilih Produk Active
                    </option>
                    <option
                      v-for="prod in products"
                      :key="prod.id"
                      :value="prod.id"
                      :disabled="isProductSelectedInOtherRow(prod.id, index)"
                    >
                      {{ prod.name }} (SKU: {{ prod.sku }})
                    </option>
                  </select>
                  <p
                    v-if="store.validationErrors[`items.${index}.product_id`]"
                    class="mt-1 text-xs text-red-600"
                  >
                    {{ store.validationErrors[`items.${index}.product_id`][0] }}
                  </p>
                </td>
                <td class="py-3 pr-4">
                  <input
                    v-model="item.quantity"
                    type="text"
                    aria-label="Jumlah Quantity"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="0.0000"
                    required
                  >
                  <p
                    v-if="store.validationErrors[`items.${index}.quantity`]"
                    class="mt-1 text-xs text-red-600"
                  >
                    {{ store.validationErrors[`items.${index}.quantity`][0] }}
                  </p>
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
  ]
});

const userLocations = ref([]);
const allLocations = ref([]);
const products = ref([]);
const locationError = ref('');

const availableDestinations = computed(() => {
  return allLocations.value.filter(loc => loc.id !== form.origin_location_id);
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

const addItemRow = () => {
  form.items.push({ product_id: '', quantity: '1.0000' });
};

const removeItemRow = (index) => {
  if (form.items.length > 1) {
    form.items.splice(index, 1);
  }
};

const loadMasterData = async () => {
  try {
    const [locRes, prodRes] = await Promise.all([
      locationApi.getAll({ is_active: true, per_page: 100 }),
      productApi.getAll({ is_active: true, per_page: 100 })
    ]);
    allLocations.value = locRes.data.data.data || locRes.data.data || [];
    userLocations.value = allLocations.value; // Filtered by active locations
    products.value = prodRes.data.data.data || prodRes.data.data || [];
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
      items: form.items.map(i => ({
        product_id: i.product_id,
        quantity: i.quantity
      }))
    };

    if (isEdit.value) {
      await store.updateTransfer(route.params.id, payload);
      router.push(`/inventory/transfers/${route.params.id}`);
    } else {
      const res = await store.createTransfer(payload);
      router.push(`/inventory/transfers/${res.data.id}`);
    }
  } catch {
    // Handled by Pinia store validationErrors & error
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
      form.items = data.items.map(i => ({
        product_id: i.product_id,
        quantity: String(i.quantity)
      }));
    } catch {
      // Handled by store
    }
  }
});
</script>
