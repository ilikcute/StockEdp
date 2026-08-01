<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          {{ isEdit ? 'Edit Draft Pengeluaran' : 'Buat Draft Pengeluaran Stok' }}
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Isi form di bawah untuk mencatat mutasi keluar barang.
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex gap-2">
        <router-link
          :to="isEdit ? `/inventory/issues/${route.params.id}` : '/inventory/issues'"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
        >
          Batal
        </router-link>
        <button
          :disabled="isSubmitting"
          class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          @click="submitForm"
        >
          Simpan Draft
        </button>
      </div>
    </div>

    <div
      v-if="errorMsg"
      class="mt-4 rounded-md bg-red-50 p-4"
    >
      <p class="text-sm font-medium text-red-800">
        {{ errorMsg }}
      </p>
    </div>

    <form
      class="mt-8 space-y-8"
      @submit.prevent="submitForm"
    >
      <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6 bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
        <!-- Header Section -->
        <div class="sm:col-span-3">
          <label
            for="date"
            class="block text-sm font-medium text-gray-700"
          >Tanggal Pengeluaran *</label>
          <div class="mt-1">
            <input
              id="date"
              v-model="form.date"
              type="date"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              required
            >
          </div>
        </div>

        <div class="sm:col-span-3">
          <label
            for="purpose"
            class="block text-sm font-medium text-gray-700"
          >Tujuan / Alasan *</label>
          <div class="mt-1">
            <input
              id="purpose"
              v-model="form.purpose"
              type="text"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Contoh: Produksi SPK-001"
              required
            >
          </div>
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
              rows="3"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
        </div>
      </div>

      <!-- Items Section -->
      <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium leading-6 text-gray-900">
            Item Produk
          </h3>
          <button
            type="button"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            @click="addItem"
          >
            Tambah Baris
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
                  scope="col"
                  class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Produk *
                </th>
                <th
                  scope="col"
                  class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Lokasi *
                </th>
                <th
                  scope="col"
                  class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Stok Tersedia
                </th>
                <th
                  scope="col"
                  class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                >
                  Kuantitas *
                </th>
                <th
                  scope="col"
                  class="relative px-3 py-3"
                >
                  <span class="sr-only">Hapus</span>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="form.items.length === 0">
                <td
                  colspan="5"
                  class="px-3 py-4 text-center text-sm text-gray-500"
                >
                  Belum ada item ditambahkan.
                </td>
              </tr>
              <tr
                v-for="(item, index) in form.items"
                :key="index"
              >
                <td class="px-3 py-4 whitespace-nowrap">
                  <select
                    v-model="item.product_id"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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
                      v-for="prod in products"
                      :key="prod.id"
                      :value="prod.id"
                    >
                      {{ prod.sku }} - {{ prod.name }}
                    </option>
                  </select>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <select
                    v-model="item.location_id"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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
                      {{ loc.code }} - {{ loc.name }}
                    </option>
                  </select>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <span
                    class="text-sm font-mono"
                    :class="item.available_stock < item.quantity ? 'text-red-600' : 'text-gray-900'"
                  >
                    {{ item.available_stock !== null ? Number(item.available_stock).toFixed(4) : '-' }}
                  </span>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <input
                    v-model="item.quantity"
                    type="number"
                    step="0.0001"
                    min="0.0001"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                    required
                  >
                </td>
                <td class="px-3 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button
                    type="button"
                    class="text-red-600 hover:text-red-900"
                    @click="removeItem(index)"
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
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStockIssueStore } from '../stores/useStockIssueStore';
import apiClient from '@/shared/api/api_client';

const route = useRoute();
const router = useRouter();
const store = useStockIssueStore();

const isEdit = route.name === 'stockIssuesEdit';
const isSubmitting = ref(false);
const errorMsg = ref('');

const form = ref({
    purpose: '',
    date: new Date().toISOString().slice(0, 10),
    notes: '',
    items: []
});

const products = ref([]);
const locations = ref([]);

const fetchDependencies = async () => {
    try {
        const [prodRes, locRes] = await Promise.all([
            apiClient.get('/products', { params: { is_active: 1, per_page: 1000 } }),
            apiClient.get('/locations', { params: { is_active: 1, per_page: 100 } })
        ]);
        products.value = prodRes.data.data.data || prodRes.data.data;
        locations.value = locRes.data.data.data || locRes.data.data;
    } catch {
        errorMsg.value = 'Gagal memuat master data produk atau lokasi.';
    }
};

const fetchStock = async (index) => {
    const item = form.value.items[index];
    if (item.product_id && item.location_id) {
        try {
            const res = await apiClient.get('/inventory/balances', {
                params: {
                    product_id: item.product_id,
                    location_id: item.location_id
                }
            });
            const data = res.data.data.data || res.data.data;
            if (data.length > 0) {
                item.available_stock = data[0].quantity;
            } else {
                item.available_stock = 0;
            }
        } catch {
            item.available_stock = 0;
        }
    }
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
            items: data.items.map(i => ({
                product_id: i.product_id,
                location_id: i.location_id,
                quantity: i.quantity,
                available_stock: null
            }))
        };
        // Fetch stock for existing items
        form.value.items.forEach((_, idx) => fetchStock(idx));
    } catch {
        errorMsg.value = store.error || 'Gagal memuat data dokumen.';
    }
};

const addItem = () => {
    form.value.items.push({
        product_id: '',
        location_id: '',
        quantity: 1,
        available_stock: null
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
    
    // Validasi duplikat
    const combos = new Set();
    for (const item of form.value.items) {
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
    } else {
        addItem();
    }
});
</script>
