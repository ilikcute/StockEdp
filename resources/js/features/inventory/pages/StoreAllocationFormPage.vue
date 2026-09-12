<template>
  <div class="px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="sm:flex sm:items-center justify-between">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Catat Alokasi Penggantian Unit Toko
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Penggantian unit operasional toko: catat unit bagus yang dipasang dan penarikan unit rusak oleh teknisi.
        </p>
      </div>
      <div class="mt-4 sm:mt-0 flex gap-2">
        <router-link
          to="/inventory/store-allocations"
          class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 cursor-pointer"
        >
          Batal
        </router-link>
        <button
          type="button"
          :disabled="isSubmitting"
          class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 cursor-pointer"
          @click="submitAllocation"
        >
          <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Alokasi Toko' }}</span>
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

    <!-- Form Container -->
    <form
      class="space-y-6"
      @submit.prevent="submitAllocation"
    >
      <!-- Header Information -->
      <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6 bg-white shadow-xs rounded-xl border border-gray-200 p-4 sm:p-6">
        <!-- Toko Tujuan -->
        <div class="sm:col-span-3">
          <label
            for="store_id"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Toko Tujuan *
          </label>
          <BaseCombobox
            id="store_id"
            v-model="form.store_id"
            :options="storeOptions"
            :format-label="formatStoreLabel"
            placeholder="Ketik kode atau nama toko (cth: T-001)..."
            required
          />
        </div>

        <!-- Lokasi Teknisi (Field Personnel) -->
        <div class="sm:col-span-3">
          <label
            for="technician_location_id"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Teknisi / Lokasi Lapangan *
          </label>
          <BaseCombobox
            id="technician_location_id"
            v-model="form.technician_location_id"
            :options="locationOptions"
            placeholder="Pilih atau cari lokasi teknisi..."
            required
            @change="onLocationChanged"
          />
        </div>

        <!-- Tanggal Alokasi -->
        <div class="sm:col-span-2">
          <label
            for="allocated_at"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Tanggal Pemasangan *
          </label>
          <input
            id="allocated_at"
            v-model="form.allocated_at"
            type="date"
            class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            required
          >
        </div>

        <!-- Catatan -->
        <div class="sm:col-span-4">
          <label
            for="notes"
            class="block text-sm font-medium text-gray-700 mb-1"
          >
            Catatan Tambahan
          </label>
          <input
            id="notes"
            v-model="form.notes"
            type="text"
            placeholder="Keterangan alokasi/kondisi lapangan..."
            class="block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
        </div>
      </div>

      <!-- Barcode Scanner Section -->
      <div class="bg-white p-4 sm:p-6 shadow-xs rounded-xl border border-gray-200 space-y-3">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
          <div>
            <h2 class="text-sm font-bold text-gray-900 flex items-center gap-2">
              <span class="w-6 h-6 rounded bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">📷</span>
              Barcode Scanner (Input Cepat Unit Pasang)
            </h2>
            <p class="text-xs text-gray-500">
              Arahkan scanner barcode fisik atau ketik SKU / Barcode produk lalu tekan Enter [F2].
            </p>
          </div>
          <div
            v-if="isLoadingBalances"
            class="flex items-center gap-1.5 text-xs text-indigo-600 font-medium"
          >
            <svg
              class="animate-spin h-3.5 w-3.5 text-indigo-600"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v8H4z"
              />
            </svg>
            <span>Memperbarui saldo teknisi...</span>
          </div>
        </div>

        <BarcodeScannerPanel
          ref="scannerPanelRef"
          :location-selected="Boolean(form.technician_location_id)"
          label="Scan Barcode / SKU Unit Pasang"
          placeholder="Scan barcode atau ketik SKU / Barcode produk dipasang... [F2]"
          @scan-success="handleProductScanned"
          @scan-error="(msg) => { errorMsg = msg; }"
        />
      </div>

      <!-- Items Section -->
      <div class="bg-white shadow-xs rounded-xl border border-gray-200 p-4 sm:p-6 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-base font-semibold text-gray-900">
              Daftar Unit Dipasang & Ditarik
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">
              Setiap unit baru (GOOD) akan memotong saldo teknisi. Jika ada unit rusak (DEFECTIVE) ditarik, saldo rusak teknisi akan bertambah otomatis.
            </p>
          </div>
          <button
            type="button"
            class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-indigo-600 shadow-xs ring-1 ring-inset ring-indigo-300 hover:bg-indigo-50 cursor-pointer"
            @click="addRow"
          >
            + Tambah Baris Unit
          </button>
        </div>

        <div class="space-y-4">
          <div
            v-for="(row, idx) in form.items"
            :key="idx"
            class="p-4 rounded-lg border border-gray-200 bg-gray-50/50 space-y-4 relative"
          >
            <!-- Header Row -->
            <div class="flex items-center justify-between border-b border-gray-200 pb-2">
              <span class="font-bold text-xs text-gray-700 flex items-center gap-1.5">
                <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs">
                  {{ idx + 1 }}
                </span>
                Baris Alokasi #{{ idx + 1 }}
              </span>
              <button
                v-if="form.items.length > 1"
                type="button"
                class="text-xs text-rose-600 hover:text-rose-800 font-semibold cursor-pointer"
                @click="removeRow(idx)"
              >
                Hapus Baris
              </button>
            </div>

            <!-- Install Unit Section -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-start">
              <div class="sm:col-span-5">
                <label class="block text-xs font-semibold text-emerald-800 uppercase tracking-wider mb-1">
                  ✓ Unit Bagus Dipasang (GOOD) *
                </label>
                <BaseCombobox
                  v-model="row.product_id"
                  :options="installProductOptions"
                  size="xs"
                  placeholder="Pilih / cari nama atau SKU produk..."
                  required
                />
                <!-- Stock Indicator below Combobox -->
                <div
                  v-if="row.product_id"
                  class="mt-1.5 flex items-center justify-between text-[11px] bg-white px-2 py-1 rounded border border-gray-200"
                >
                  <span class="text-gray-500 font-medium">Sisa Stok Teknisi (GOOD):</span>
                  <span
                    class="font-mono font-bold px-1.5 py-0.2 rounded text-[10px]"
                    :class="getTechnicianStockNumber(row.product_id) > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                  >
                    {{ getTechnicianStock(row.product_id) }}
                  </span>
                </div>
                <!-- Inline Warning if Quantity exceeds Stock -->
                <p
                  v-if="row.product_id && row.quantity > getTechnicianStockNumber(row.product_id)"
                  class="mt-1 text-[11px] font-semibold text-rose-600 flex items-center gap-1"
                >
                  ⚠ Kuantitas pasang ({{ row.quantity }}) melebihi sisa stok ({{ getTechnicianStock(row.product_id) }}).
                </p>
              </div>

              <div class="sm:col-span-3">
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                  Qty Pasang *
                </label>
                <input
                  v-model.number="row.quantity"
                  type="number"
                  min="1"
                  step="1"
                  class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 min-h-[36px]"
                  required
                >
              </div>

              <div class="sm:col-span-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1">
                  Serial Number Unit Baru
                </label>
                <input
                  v-model="row.serial_number"
                  type="text"
                  placeholder="Contoh: SN-2026-001"
                  class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 min-h-[36px]"
                >
              </div>
            </div>

            <!-- Pull Defective Toggle -->
            <div class="pt-2 border-t border-dashed border-gray-200">
              <label class="inline-flex items-center gap-2 cursor-pointer">
                <input
                  v-model="row.has_pull"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4"
                  @change="onTogglePull(row)"
                >
                <span class="text-xs font-semibold text-amber-900">
                  Ada unit lama ditarik dari toko (rusak)?
                </span>
              </label>

              <!-- Pulled Details -->
              <div
                v-if="row.has_pull"
                class="mt-3 p-3 bg-amber-50/60 rounded-md border border-amber-200 grid grid-cols-1 sm:grid-cols-12 gap-3 items-end"
              >
                <div class="sm:col-span-4">
                  <label class="block text-xs font-semibold text-amber-800 uppercase tracking-wider mb-1">
                    ⚠ Unit Rusak Ditarik (DEFECTIVE) *
                  </label>
                  <BaseCombobox
                    v-model="row.pulled_product_id"
                    :options="products"
                    size="xs"
                    placeholder="Pilih / cari produk ditarik..."
                    required
                  />
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-amber-800 mb-1">
                    Qty Tarik *
                  </label>
                  <input
                    v-model.number="row.pulled_quantity"
                    type="number"
                    min="1"
                    step="1"
                    class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 min-h-[36px]"
                    required
                  >
                </div>

                <div class="sm:col-span-3">
                  <label class="block text-xs font-semibold text-amber-800 mb-1">
                    Serial Number Rusak
                  </label>
                  <input
                    v-model="row.pulled_serial_number"
                    type="text"
                    placeholder="S/N unit lama..."
                    class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 min-h-[36px]"
                  >
                </div>

                <div class="sm:col-span-3">
                  <label class="block text-xs font-semibold text-amber-800 mb-1">
                    Alasan Kerusakan *
                  </label>
                  <input
                    v-model="row.defective_reason"
                    type="text"
                    placeholder="Contoh: Mati total / Layar blank"
                    class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 min-h-[36px]"
                    required
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { storeAllocationApi } from '../api/store_allocation_api';
import { storeApi } from '@/features/store/api/store_api';
import { locationApi } from '@/features/location/api/location_api';
import { productApi } from '@/features/product/api/product_api';
import { inventoryApi } from '@/features/inventory/api/inventoryApi';
import { useAuthStore } from '@/features/auth/stores/use_auth_store';
import { showToast } from '@/shared/utils/use_toast';
import BaseCombobox from '@/shared/components/BaseCombobox.vue';
import BarcodeScannerPanel from '../scanner/components/BarcodeScannerPanel.vue';

const router = useRouter();
const authStore = useAuthStore();

const isSubmitting = ref(false);
const isLoadingBalances = ref(false);
const errorMsg = ref('');
const scannerPanelRef = ref(null);

const stores = ref([]);
const fieldLocations = ref([]);
const products = ref([]);
const balancesMap = ref({});

const form = ref({
    store_id: '',
    technician_user_id: '',
    technician_location_id: '',
    allocated_at: new Date().toISOString().slice(0, 10),
    notes: '',
    items: [
        {
            product_id: '',
            quantity: 1,
            serial_number: '',
            has_pull: false,
            pulled_product_id: '',
            pulled_quantity: 1,
            pulled_serial_number: '',
            defective_reason: '',
        },
    ],
});

const storeOptions = computed(() => {
    return stores.value.map((st) => ({
        id: st.id,
        code: st.code,
        name: st.name,
        city: st.city,
    }));
});

const formatStoreLabel = (st) => {
    if (!st) return '';
    return `[${st.code}] ${st.name}${st.city ? ' - ' + st.city : ''}`;
};

const locationOptions = computed(() => {
    return fieldLocations.value.map((loc) => ({
        id: loc.id,
        code: loc.code,
        name: `${loc.name} (${loc.user?.name || 'Teknisi'})`,
        type: loc.type,
    }));
});

const installProductOptions = computed(() => {
    return products.value.map((p) => {
        const stock = balancesMap.value[p.id] || '0.0000';
        return {
            ...p,
            stockText: stock,
            hasStock: (parseFloat(stock) || 0) > 0,
        };
    });
});

const getTechnicianStock = (productId) => {
    if (!productId) return '0.0000';
    return balancesMap.value[productId] || '0.0000';
};

const getTechnicianStockNumber = (productId) => {
    const s = getTechnicianStock(productId);
    return parseFloat(s) || 0;
};

const fetchTechnicianBalances = async (locationId) => {
    if (!locationId) {
        balancesMap.value = {};
        return;
    }
    isLoadingBalances.value = true;
    try {
        const res = await inventoryApi.getBalances({
            location_id: locationId,
            condition: 'GOOD',
            per_page: 500,
        });
        const list = res.data?.data?.data || res.data?.data || [];
        const map = {};
        list.forEach((item) => {
            map[item.product_id] = String(item.quantity);
        });
        balancesMap.value = map;
    } catch {
        balancesMap.value = {};
    } finally {
        isLoadingBalances.value = false;
    }
};

const loadDependencies = async () => {
    try {
        const [storeRes, locRes, prodRes] = await Promise.all([
            storeApi.getAll({ is_active: 1, per_page: 1000 }),
            locationApi.getAll({ is_active: 1, per_page: 500 }),
            productApi.getAll({ is_active: 1, per_page: 1000 }),
        ]);

        stores.value = storeRes.data?.data?.data || storeRes.data?.data || [];
        const allLocs = locRes.data?.data?.data || locRes.data?.data || [];
        fieldLocations.value = allLocs.filter((l) => l.type === 'FIELD_PERSONNEL');
        if (fieldLocations.value.length === 0) {
            fieldLocations.value = allLocs;
        }

        products.value = prodRes.data?.data?.data || prodRes.data?.data || [];

        // Auto-select if current user has an assigned field location
        if (fieldLocations.value.length > 0) {
            const userLoc = fieldLocations.value.find((l) => l.user_id === authStore.user?.id);
            if (userLoc) {
                form.value.technician_location_id = userLoc.id;
                form.value.technician_user_id = userLoc.user_id;
            } else {
                form.value.technician_location_id = fieldLocations.value[0].id;
                form.value.technician_user_id = fieldLocations.value[0].user_id || authStore.user?.id;
            }
        }

        if (form.value.technician_location_id) {
            await fetchTechnicianBalances(form.value.technician_location_id);
        }
    } catch {
        errorMsg.value = 'Gagal memuat master data toko, lokasi, atau produk.';
    }
};

const onLocationChanged = async () => {
    const loc = fieldLocations.value.find((l) => l.id === form.value.technician_location_id);
    if (loc && loc.user_id) {
        form.value.technician_user_id = loc.user_id;
    } else {
        form.value.technician_user_id = authStore.user?.id;
    }
    await fetchTechnicianBalances(form.value.technician_location_id);
};

const handleProductScanned = (scannedProduct) => {
    errorMsg.value = '';
    if (!scannedProduct || !scannedProduct.id) return;

    // Check if this product is already in the list without serial number
    const existingRow = form.value.items.find(
        (i) => String(i.product_id) === String(scannedProduct.id) && !i.serial_number
    );

    if (existingRow) {
        existingRow.quantity = (existingRow.quantity || 0) + 1;
        showToast(`Kuantitas "${scannedProduct.name}" ditambah menjadi ${existingRow.quantity}.`, { type: 'info' });
    } else {
        // If the first row is empty, fill it
        const firstEmpty = form.value.items.find((i) => !i.product_id);
        if (firstEmpty) {
            firstEmpty.product_id = scannedProduct.id;
            firstEmpty.quantity = 1;
        } else {
            form.value.items.push({
                product_id: scannedProduct.id,
                quantity: 1,
                serial_number: '',
                has_pull: false,
                pulled_product_id: '',
                pulled_quantity: 1,
                pulled_serial_number: '',
                defective_reason: '',
            });
        }
        showToast(`Produk "${scannedProduct.name}" berhasil ditambahkan.`, { type: 'success' });
    }
};

const addRow = () => {
    form.value.items.push({
        product_id: '',
        quantity: 1,
        serial_number: '',
        has_pull: false,
        pulled_product_id: '',
        pulled_quantity: 1,
        pulled_serial_number: '',
        defective_reason: '',
    });
};

const removeRow = (idx) => {
    form.value.items.splice(idx, 1);
};

const onTogglePull = (row) => {
    if (row.has_pull && !row.pulled_product_id) {
        row.pulled_product_id = row.product_id;
    }
};

const handleKeyDown = (e) => {
    if (e.key === 'F2') {
        e.preventDefault();
        scannerPanelRef.value?.focusInput();
    }
};

const submitAllocation = async () => {
    errorMsg.value = '';

    if (!form.value.store_id) {
        errorMsg.value = 'Harap pilih toko tujuan.';
        return;
    }

    if (!form.value.technician_location_id) {
        errorMsg.value = 'Harap pilih lokasi teknisi.';
        return;
    }

    if (form.value.items.length === 0) {
        errorMsg.value = 'Minimal harus ada 1 unit yang dialokasikan.';
        return;
    }

    for (const [idx, item] of form.value.items.entries()) {
        if (!item.product_id) {
            errorMsg.value = `Baris #${idx + 1}: Harap pilih unit bagus yang dipasang.`;
            return;
        }
        if (!item.quantity || item.quantity <= 0) {
            errorMsg.value = `Baris #${idx + 1}: Kuantitas pasang harus lebih dari 0.`;
            return;
        }
        if (item.has_pull) {
            if (!item.pulled_product_id) {
                errorMsg.value = `Baris #${idx + 1}: Harap pilih unit rusak yang ditarik.`;
                return;
            }
            if (!item.pulled_quantity || item.pulled_quantity <= 0) {
                errorMsg.value = `Baris #${idx + 1}: Kuantitas ditarik harus lebih dari 0.`;
                return;
            }
        }
    }

    const payload = {
        store_id: form.value.store_id,
        technician_user_id: form.value.technician_user_id || authStore.user?.id,
        technician_location_id: form.value.technician_location_id,
        allocated_at: form.value.allocated_at,
        notes: form.value.notes || null,
        items: form.value.items.map((i) => ({
            product_id: i.product_id,
            quantity: i.quantity,
            serial_number: i.serial_number || null,
            pulled_product_id: i.has_pull ? i.pulled_product_id : null,
            pulled_quantity: i.has_pull ? i.pulled_quantity : null,
            pulled_serial_number: i.has_pull ? (i.pulled_serial_number || null) : null,
            defective_reason: i.has_pull ? (i.defective_reason || null) : null,
        })),
    };

    isSubmitting.value = true;
    try {
        const res = await storeAllocationApi.create(payload);
        showToast('Alokasi toko berhasil disimpan.', { type: 'success' });
        const newId = res.data?.data?.id || res.data?.id;
        if (newId) {
            router.push(`/inventory/store-allocations/${newId}`);
        } else {
            router.push('/inventory/store-allocations');
        }
    } catch (err) {
        errorMsg.value = err.response?.data?.message || 'Gagal menyimpan alokasi toko.';
    } finally {
        isSubmitting.value = false;
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
    loadDependencies();
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>
