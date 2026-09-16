<template>
  <div class="space-y-2.5">
    <!-- Top Compact Header Bar & KPI Summary -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-3">
      <!-- Left: Title & Status -->
      <div class="flex items-center gap-2.5">
        <router-link
          to="/inventory/store-allocations"
          class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer"
          title="Kembali ke daftar alokasi unit"
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
              Alokasi Penggantian Unit Toko
            </h1>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-teal-50 text-teal-700 border border-teal-200 uppercase tracking-wide">
              Unit Toko
            </span>
          </div>
          <p class="text-[11px] text-gray-500 hidden sm:block">
            Pencatatan unit operasional toko (pemasangan unit bagus & penarikan unit rusak).
          </p>
        </div>
      </div>

      <!-- Middle: KPI Summary Chips -->
      <div class="flex items-center gap-2 flex-wrap">
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs">
          <span class="text-gray-500 font-medium">Baris:</span>
          <span class="font-bold text-gray-800 font-mono">{{ form.items.length }}</span>
        </div>
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 border border-emerald-200 rounded-lg text-xs">
          <span class="text-emerald-700 font-medium">Pasang (GOOD):</span>
          <span class="font-black text-emerald-800 font-mono">{{ totalInstallQty }}</span>
        </div>
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 border border-amber-200 rounded-lg text-xs">
          <span class="text-amber-700 font-medium">Tarik (DEFECTIVE):</span>
          <span class="font-black text-amber-800 font-mono">{{ totalPullQty }}</span>
        </div>
      </div>

      <!-- Right: Main Actions -->
      <div class="flex items-center gap-2 self-end md:self-auto">
        <router-link
          to="/inventory/store-allocations"
          class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 border border-gray-300 shadow-2xs hover:bg-gray-50 transition-colors cursor-pointer"
        >
          Batal
        </router-link>
        <button
          id="btn-save-allocation"
          type="button"
          :disabled="isSubmitting"
          class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50 transition-colors cursor-pointer"
          title="Simpan Alokasi Toko (Shortcut: F9)"
          @click="submitAllocation"
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
          <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Alokasi Toko' }}</span>
          <kbd class="hidden sm:inline-block font-mono text-[9px] bg-indigo-700/90 text-indigo-100 px-1 py-0.2 rounded border border-indigo-400 font-bold">F9</kbd>
        </button>
      </div>
    </div>

    <!-- Error Alert -->
    <div
      v-if="errorMsg"
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
        <span>{{ errorMsg }}</span>
      </div>
      <button
        type="button"
        class="text-rose-400 hover:text-rose-600 cursor-pointer"
        @click="errorMsg = ''"
      >
        &times;
      </button>
    </div>

    <!-- Form Section -->
    <form
      class="space-y-2.5"
      @submit.prevent="submitAllocation"
    >
      <!-- Document Metadata Strip (Single Horizontal Grid) -->
      <div class="bg-white rounded-xl border border-gray-200 p-2.5 sm:p-3 shadow-2xs">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
          <!-- Toko Tujuan -->
          <div>
            <label
              for="store_id"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Toko Tujuan *
            </label>
            <BaseCombobox
              id="store_id"
              v-model="form.store_id"
              :options="storeOptions"
              :format-label="formatStoreLabel"
              size="xs"
              placeholder="Cari kode/nama toko..."
              required
            />
          </div>

          <!-- Lokasi Asal (Gudang Induk / Teknisi) -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label
                for="technician_location_id"
                class="block text-[11px] font-semibold text-gray-600"
              >
                Lokasi Asal (Gudang Induk / Teknisi) *
              </label>
              <div
                v-if="isLoadingBalances"
                class="flex items-center gap-1 text-[10px] text-indigo-600"
              >
                <svg
                  class="animate-spin h-3 w-3 text-indigo-600"
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
                <span>Cek saldo...</span>
              </div>
            </div>
            <BaseCombobox
              id="technician_location_id"
              v-model="form.technician_location_id"
              :options="locationOptions"
              size="xs"
              placeholder="Pilih gudang induk atau lokasi teknisi..."
              required
              @change="onLocationChanged"
            />
            <!-- Badge Hak Akses / Otorisasi Alokasi -->
            <div
              v-if="!canCreateForOthers"
              class="mt-1 flex items-center gap-1.5 text-[11px] text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-200"
            >
              <svg
                class="w-3.5 h-3.5 text-teal-600 shrink-0"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                  clip-rule="evenodd"
                />
              </svg>
              <span>Alokasi mandiri untuk akun: <strong>{{ authStore.user?.name }}</strong></span>
            </div>
            <div
              v-else
              class="mt-1 flex items-center gap-1.5 text-[11px] text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200"
            >
              <svg
                class="w-3.5 h-3.5 text-indigo-600 shrink-0"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  fill-rule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z"
                  clip-rule="evenodd"
                />
              </svg>
              <span>Mode Delegasi / SPV: Mengalokasikan atas nama teknisi pemilik lokasi.</span>
            </div>
          </div>

          <!-- Catatan -->
          <div>
            <label
              for="notes"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Catatan Lapangan
            </label>
            <input
              id="notes"
              v-model="form.notes"
              type="text"
              placeholder="Keterangan alokasi/kondisi..."
              class="block w-full rounded-lg border-gray-300 py-1.5 px-2.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
            >
          </div>
        </div>
      </div>

      <!-- Integrated Scanner & Quick Entry Strip -->
      <div class="bg-teal-50/50 border border-teal-100 rounded-xl p-2 sm:p-2.5 shadow-2xs flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
        <!-- Status Teknisi -->
        <div class="w-full sm:w-60 shrink-0 flex items-center gap-2 px-2.5 py-1.5 bg-white rounded-lg border border-teal-200">
          <span
            class="w-2.5 h-2.5 rounded-full shrink-0"
            :class="form.technician_location_id ? 'bg-teal-600' : 'bg-amber-400 animate-pulse'"
          />
          <div class="text-[11px] truncate">
            <span
              v-if="form.technician_location_id"
              class="font-bold text-teal-900"
            >
              Teknisi Terpilih
            </span>
            <span
              v-else
              class="font-medium text-amber-700"
            >
              Pilih Teknisi Lapangan
            </span>
          </div>
        </div>

        <!-- Scanner Barcode & Live Search Panel -->
        <div class="flex-1 min-w-[200px]">
          <BarcodeScannerPanel
            ref="scannerPanelRef"
            :compact="true"
            :location-selected="Boolean(form.technician_location_id)"
            :enable-live-search="true"
            :products="installProductOptions"
            :debounce-ms="250"
            label="Scan Barcode / Cari Produk Unit Pasang"
            placeholder="Scan barcode / ketik nama produk / SKU [F2]..."
            @scan-success="handleProductScanned"
            @scan-error="(msg) => { errorMsg = msg; }"
          />
        </div>

        <!-- Tombol Tambah Baris Manual -->
        <div class="shrink-0 flex items-end">
          <button
            id="btn-add-allocation-row"
            type="button"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-lg bg-white border border-teal-200 px-3 py-1.5 text-xs font-semibold text-teal-800 shadow-2xs hover:bg-teal-50 hover:border-teal-300 transition-colors min-h-[36px] whitespace-nowrap cursor-pointer"
            title="Tambah Baris Unit Kosong"
            @click="addRow"
          >
            <svg
              class="w-3.5 h-3.5 text-teal-600"
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
            <span>+ Tambah Baris Unit</span>
          </button>
        </div>
      </div>

      <!-- High-Density Items Card Container -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-2xs overflow-hidden flex flex-col">
        <!-- Context Header Bar -->
        <div class="px-3.5 py-2 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
          <div class="flex items-center gap-2">
            <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">
              Daftar Unit Dipasang & Ditarik
            </h3>
            <span class="px-1.5 py-0.2 text-[10px] font-bold rounded-md bg-gray-100 text-gray-600 font-mono">
              {{ form.items.length }} baris unit
            </span>
          </div>
          <span class="text-[11px] text-gray-400 font-mono hidden md:inline-block">
            Tekan <kbd class="px-1 py-0.5 rounded bg-gray-100 border border-gray-200 text-gray-600 font-bold">F2</kbd> ke Scanner / Cari Produk &bull; <kbd class="px-1 py-0.5 rounded bg-gray-100 border border-gray-200 text-gray-600 font-bold">F9</kbd> Simpan
          </span>
        </div>

        <!-- Scrollable High-Density List Container -->
        <div class="overflow-y-auto max-h-[calc(100vh-310px)] min-h-[200px] p-2.5 space-y-2">
          <div
            v-for="(row, idx) in form.items"
            :key="idx"
            class="rounded-lg border border-gray-200 bg-gray-50/40 p-2.5 space-y-2 transition-colors hover:border-teal-200"
          >
            <!-- Top Strip: Row Number, Install Info & Delete -->
            <div class="flex items-center justify-between gap-2 border-b border-gray-100 pb-1.5">
              <div class="flex items-center gap-2">
                <span class="w-5 h-5 rounded-md bg-teal-100 text-teal-800 flex items-center justify-center text-[10px] font-bold font-mono">
                  #{{ idx + 1 }}
                </span>
                <span class="text-[11px] font-bold text-gray-700">
                  Unit Pasang & Tarik
                </span>
                <span
                  v-if="row.has_pull"
                  class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200"
                >
                  + Tarik Rusak
                </span>
              </div>
              <button
                v-if="form.items.length > 1"
                type="button"
                class="text-[11px] text-rose-500 hover:text-rose-700 font-semibold cursor-pointer px-1.5 py-0.5 rounded hover:bg-rose-50 transition-colors"
                @click="removeRow(idx)"
              >
                Hapus Baris
              </button>
            </div>

            <!-- Install Unit Strip (GOOD) -->
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
              <!-- Produk Dipasang -->
              <div class="sm:col-span-6">
                <div class="flex items-center justify-between mb-0.5">
                  <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">
                    ✓ Unit Bagus Dipasang (GOOD) *
                  </span>
                  <!-- Inline Stock Badge -->
                  <div
                    v-if="row.product_id"
                    class="flex items-center gap-1 text-[10px]"
                  >
                    <span class="text-gray-400">Saldo Teknisi:</span>
                    <span
                      class="font-mono font-bold px-1 rounded text-[9px]"
                      :class="getTechnicianStockNumber(row.product_id) > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                    >
                      {{ getTechnicianStock(row.product_id) }}
                    </span>
                  </div>
                </div>
                <BaseCombobox
                  v-model="row.product_id"
                  :options="installProductOptions"
                  size="xs"
                  placeholder="Pilih / cari nama atau SKU produk..."
                  required
                />
                <!-- Warning Exceed Stock -->
                <p
                  v-if="row.product_id && row.quantity > getTechnicianStockNumber(row.product_id)"
                  class="mt-0.5 text-[10px] font-semibold text-rose-600 flex items-center gap-1"
                >
                  ⚠ Melebihi sisa stok ({{ getTechnicianStock(row.product_id) }}).
                </p>
              </div>

              <!-- Qty Pasang -->
              <div class="sm:col-span-2">
                <label class="block text-[10px] font-semibold text-gray-600 mb-0.5">
                  Qty Pasang *
                </label>
                <input
                  v-model.number="row.quantity"
                  type="number"
                  min="1"
                  step="1"
                  class="block w-full rounded-md border-gray-300 text-xs py-1 px-2 font-mono font-bold text-gray-900 focus:border-teal-500 focus:ring-teal-500"
                  required
                >
              </div>

              <!-- Serial Number Unit Baru -->
              <div class="sm:col-span-4">
                <label class="block text-[10px] font-semibold text-gray-600 mb-0.5">
                  Serial Number Baru (Opsional)
                </label>
                <input
                  v-model="row.serial_number"
                  type="text"
                  placeholder="Contoh: SN-2026-001"
                  class="block w-full rounded-md border-gray-300 text-xs py-1 px-2 focus:border-teal-500 focus:ring-teal-500"
                >
              </div>
            </div>

            <!-- Pulled Unit Strip (DEFECTIVE) -->
            <div class="pt-1.5 border-t border-dashed border-gray-200">
              <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-1.5 cursor-pointer select-none">
                  <input
                    v-model="row.has_pull"
                    type="checkbox"
                    class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 h-3.5 w-3.5"
                    @change="onTogglePull(row)"
                  >
                  <span class="text-[11px] font-semibold text-amber-900">
                    Ada penarikan unit lama/rusak dari toko?
                  </span>
                </label>
              </div>

              <!-- Pulled Details Inline -->
              <div
                v-if="row.has_pull"
                class="mt-1.5 p-2 bg-amber-50/70 rounded-md border border-amber-200 grid grid-cols-1 sm:grid-cols-12 gap-2 items-center"
              >
                <!-- Produk Tarik -->
                <div class="sm:col-span-5">
                  <label class="block text-[10px] font-bold text-amber-800 uppercase tracking-wider mb-0.5">
                    ⚠ Unit Rusak Ditarik *
                  </label>
                  <BaseCombobox
                    v-model="row.pulled_product_id"
                    :options="products"
                    size="xs"
                    placeholder="Pilih produk ditarik..."
                    required
                  />
                </div>

                <!-- Qty Tarik -->
                <div class="sm:col-span-2">
                  <label class="block text-[10px] font-semibold text-amber-800 mb-0.5">
                    Qty Tarik *
                  </label>
                  <input
                    v-model.number="row.pulled_quantity"
                    type="number"
                    min="1"
                    step="1"
                    class="block w-full rounded-md border-amber-300 text-xs py-1 px-2 font-mono font-bold text-gray-900 focus:border-amber-500 focus:ring-amber-500"
                    required
                  >
                </div>

                <!-- Serial Number Rusak -->
                <div class="sm:col-span-2">
                  <label class="block text-[10px] font-semibold text-amber-800 mb-0.5">
                    S/N Rusak
                  </label>
                  <input
                    v-model="row.pulled_serial_number"
                    type="text"
                    placeholder="S/N unit lama..."
                    class="block w-full rounded-md border-amber-300 text-xs py-1 px-2 focus:border-amber-500 focus:ring-amber-500"
                  >
                </div>

                <!-- Alasan Kerusakan -->
                <div class="sm:col-span-3">
                  <label class="block text-[10px] font-semibold text-amber-800 mb-0.5">
                    Alasan Kerusakan *
                  </label>
                  <input
                    v-model="row.defective_reason"
                    type="text"
                    placeholder="Mati total / layar blank..."
                    class="block w-full rounded-md border-amber-300 text-xs py-1 px-2 focus:border-amber-500 focus:ring-amber-500"
                    required
                  >
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State -->
          <div
            v-if="form.items.length === 0"
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
                  d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                />
              </svg>
              <span class="font-medium text-gray-500">Belum ada baris unit yang dialokasikan.</span>
              <span class="text-[11px] text-gray-400 mt-0.5">Scan barcode produk dipasang di atas atau klik tombol "+ Tambah Baris Unit".</span>
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

const canCreateForOthers = computed(() => {
    if (authStore.isAdmin) return true;
    if (authStore.user?.roles?.includes('INVENTORY_SUPERVISOR')) return true;
    return authStore.hasPermission('store_allocations.create_for_others');
});

const stores = ref([]);
const fieldLocations = ref([]);
const products = ref([]);
const balancesMap = ref({});

const form = ref({
    store_id: '',
    technician_user_id: '',
    technician_location_id: '',
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
        name: loc.type === 'MAIN_WAREHOUSE'
            ? `${loc.name} [Gudang Induk]`
            : `${loc.name} (${loc.user?.name || 'Teknisi Lapangan'})`,
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

const totalInstallQty = computed(() => {
    return form.value.items.reduce((sum, i) => sum + (Number(i.quantity) || 0), 0);
});

const totalPullQty = computed(() => {
    return form.value.items.reduce((sum, i) => sum + (i.has_pull ? (Number(i.pulled_quantity) || 0) : 0), 0);
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

        if (!canCreateForOthers.value) {
            // Field technician can only select their own personal location or main warehouse
            fieldLocations.value = allLocs.filter(
                (l) => (l.type === 'FIELD_PERSONNEL' && l.user_id === authStore.user?.id) || l.type === 'MAIN_WAREHOUSE'
            );
        } else {
            fieldLocations.value = allLocs.filter((l) => l.type === 'FIELD_PERSONNEL' || l.type === 'MAIN_WAREHOUSE');
        }

        if (fieldLocations.value.length === 0) {
            fieldLocations.value = allLocs;
        }

        products.value = prodRes.data?.data?.data || prodRes.data?.data || [];

        // Auto-select if current user has an assigned field location
        if (fieldLocations.value.length > 0) {
            const userLoc = fieldLocations.value.find((l) => l.user_id === authStore.user?.id);
            if (userLoc) {
                form.value.technician_location_id = userLoc.id;
                form.value.technician_user_id = canCreateForOthers.value ? userLoc.user_id : authStore.user?.id;
            } else {
                form.value.technician_location_id = fieldLocations.value[0].id;
                form.value.technician_user_id = canCreateForOthers.value
                    ? (fieldLocations.value[0].user_id || authStore.user?.id)
                    : authStore.user?.id;
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
    if (canCreateForOthers.value) {
        if (loc && loc.user_id) {
            form.value.technician_user_id = loc.user_id;
        } else {
            form.value.technician_user_id = authStore.user?.id;
        }
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

const submitAllocation = async () => {
    errorMsg.value = '';

    if (!form.value.store_id) {
        errorMsg.value = 'Harap pilih toko tujuan.';
        return;
    }

    if (!form.value.technician_location_id) {
        errorMsg.value = 'Harap pilih lokasi asal (gudang induk atau teknisi).';
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
        technician_user_id: canCreateForOthers.value
            ? (form.value.technician_user_id || authStore.user?.id)
            : authStore.user?.id,
        technician_location_id: form.value.technician_location_id,
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

const handleKeyDown = (e) => {
    if (e.key === 'F2') {
        e.preventDefault();
        scannerPanelRef.value?.focusInput();
    } else if (e.key === 'F9') {
        e.preventDefault();
        submitAllocation();
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
