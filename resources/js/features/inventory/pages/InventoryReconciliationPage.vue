<template>
  <div class="space-y-4">
    <!-- Top Header & Action Toolbar -->
    <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <h1 class="text-base font-bold text-gray-900 leading-tight flex items-center gap-1.5">
            <svg
              class="w-5 h-5 text-indigo-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
              />
            </svg>
            Rekonsiliasi Saldo Stok (Balance Recalculator)
          </h1>
          <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-[10px] font-bold text-purple-700 ring-1 ring-inset ring-purple-600/20">
            Khusus Admin
          </span>
        </div>
        <p class="text-[11px] text-gray-500 mt-1">
          Alat audit dan sinkronisasi otomatis saldo persediaan (<code class="text-gray-700 font-mono">inventory_balances</code>) berdasarkan perhitungan ulang seluruh detail transaksi mutasi fisik (<code class="text-gray-700 font-mono">stock_movements</code>).
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2 flex-wrap">
        <button
          type="button"
          :disabled="store.loadingScan || store.loadingApply"
          class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-indigo-500 disabled:opacity-50 cursor-pointer transition-colors"
          @click="handleScan"
        >
          <svg
            class="w-3.5 h-3.5 text-gray-500"
            :class="{ 'animate-spin': store.loadingScan }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          <span>{{ store.loadingScan ? 'Memindai...' : 'Pindai Ulang Selisih' }}</span>
        </button>

        <button
          v-if="store.hasScanned && store.scanResults.summary.discrepant_pairs > 0"
          type="button"
          :disabled="store.loadingScan || store.loadingApply"
          class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 cursor-pointer transition-colors"
          @click="openBulkConfirmModal"
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
              d="M5 13l4 4L19 7"
            />
          </svg>
          <span>
            {{ selectedItems.length > 0 ? `Sinkronkan (${selectedItems.length}) Pilihan` : 'Sinkronkan Semua Selisih' }}
          </span>
        </button>
      </div>
    </div>

    <!-- KPI Summary Cards -->
    <div
      v-if="store.hasScanned"
      class="grid grid-cols-2 lg:grid-cols-4 gap-3"
    >
      <div class="bg-white rounded-xl border border-gray-200 p-3.5 shadow-2xs">
        <span class="text-[11px] font-medium text-gray-500 block">Total Pasangan Stok Diperiksa</span>
        <div class="mt-1 flex items-baseline justify-between">
          <span class="text-xl font-bold text-gray-900">{{ formatQuantity(store.scanResults.summary.total_scanned_pairs) }}</span>
          <span class="text-[10px] text-gray-400">SKU & Lokasi</span>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-3.5 shadow-2xs">
        <span class="text-[11px] font-medium text-emerald-600 block">Saldo Sinkron (Sesuai)</span>
        <div class="mt-1 flex items-baseline justify-between">
          <span class="text-xl font-bold text-emerald-700">{{ formatQuantity(store.scanResults.summary.matching_pairs) }}</span>
          <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700">
            OK
          </span>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-3.5 shadow-2xs">
        <span class="text-[11px] font-medium text-rose-600 block">Selisih Ditemukan</span>
        <div class="mt-1 flex items-baseline justify-between">
          <span class="text-xl font-bold" :class="store.scanResults.summary.discrepant_pairs > 0 ? 'text-rose-600' : 'text-gray-900'">
            {{ formatQuantity(store.scanResults.summary.discrepant_pairs) }}
          </span>
          <span
            v-if="store.scanResults.summary.discrepant_pairs > 0"
            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 animate-pulse"
          >
            Perlu Sinkronisasi
          </span>
          <span
            v-else
            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-600"
          >
            Nol Selisih
          </span>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-3.5 shadow-2xs">
        <span class="text-[11px] font-medium text-gray-500 block">Waktu Terakhir Pemindaian</span>
        <div class="mt-1">
          <span class="text-xs font-semibold text-gray-800">{{ formatTimestamp(store.scanResults.summary.scanned_at) }}</span>
        </div>
      </div>
    </div>

    <!-- Alert / Notifications -->
    <div
      v-if="store.error"
      class="rounded-xl bg-rose-50 p-3 border border-rose-200 text-xs text-rose-800 flex items-center justify-between"
    >
      <div class="flex items-center gap-2">
        <svg
          class="w-4 h-4 text-rose-600 shrink-0"
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
        <span>{{ store.error }}</span>
      </div>
      <button
        type="button"
        class="text-rose-500 hover:text-rose-700 text-xs font-semibold cursor-pointer"
        @click="store.error = null"
      >
        Tutup
      </button>
    </div>

    <div
      v-if="store.lastApplyResult"
      class="rounded-xl bg-emerald-50 p-3.5 border border-emerald-200 text-xs text-emerald-800 flex items-start justify-between"
    >
      <div class="flex items-start gap-2.5">
        <svg
          class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <div>
          <span class="font-bold block">Sinkronisasi Berhasil Diterapkan!</span>
          <p class="mt-0.5 text-emerald-700">
            Total saldo yang disinkronkan: <strong>{{ store.lastApplyResult.reconciled_count }}</strong> baris persediaan. Saldo tabel <code class="font-mono bg-emerald-100/70 px-1 rounded text-emerald-900">inventory_balances</code> kini telah sesuai dengan mutasi fisik.
          </p>
        </div>
      </div>
      <button
        type="button"
        class="text-emerald-600 hover:text-emerald-800 text-xs font-semibold cursor-pointer"
        @click="store.lastApplyResult = null"
      >
        Tutup
      </button>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
        <!-- Location Filter -->
        <div class="w-full sm:w-56">
          <select
            v-model="store.filters.location_id"
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            @change="handleScan"
          >
            <option value="">
              Semua Lokasi / Gudang
            </option>
            <option
              v-for="loc in locations"
              :key="loc.id"
              :value="loc.id"
            >
              {{ loc.name }} ({{ loc.type }})
            </option>
          </select>
        </div>

        <!-- Search Input -->
        <div class="w-full sm:w-64">
          <input
            v-model="store.filters.search"
            type="text"
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 px-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            placeholder="Filter hasil: SKU, Nama Produk, Lokasi..."
          >
        </div>

        <button
          v-if="store.filters.location_id || store.filters.search"
          type="button"
          class="text-xs text-gray-500 hover:text-gray-700 underline font-medium cursor-pointer"
          @click="resetFilters"
        >
          Reset Filter
        </button>
      </div>

      <div
        v-if="store.hasScanned && store.scanResults.discrepancy_count > 0"
        class="text-[11px] text-gray-500 whitespace-nowrap"
      >
        Menampilkan <strong>{{ store.filteredDiscrepancies.length }}</strong> dari <strong>{{ store.scanResults.discrepancy_count }}</strong> selisih
      </div>
    </div>

    <!-- Content Area: Initial / Loading / Clean / Discrepancies Table -->
    <!-- 1. Initial State before Scan -->
    <div
      v-if="!store.hasScanned && !store.loadingScan"
      class="bg-white rounded-xl border border-gray-200 p-8 text-center shadow-2xs max-w-2xl mx-auto my-6"
    >
      <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-600 mx-auto flex items-center justify-center mb-3">
        <svg
          class="w-6 h-6"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
          />
        </svg>
      </div>
      <h3 class="text-sm font-bold text-gray-900">
        Siap Memindai Rekonsiliasi Saldo Stok
      </h3>
      <p class="text-xs text-gray-500 mt-1.5 max-w-lg mx-auto">
        Fitur ini akan mengkalkulasi saldo murni dari seluruh tabel riwayat mutasi fisik (<code class="font-mono text-gray-700">stock_movements</code>) dan membandingkannya dengan saldo yang tercatat di tabel <code class="font-mono text-gray-700">inventory_balances</code>.
      </p>
      <div class="mt-4">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 cursor-pointer transition-colors"
          @click="handleScan"
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
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
          <span>Mulai Pindai Selisih Stok</span>
        </button>
      </div>
    </div>

    <!-- 2. Loading State -->
    <div
      v-else-if="store.loadingScan"
      class="bg-white rounded-xl border border-gray-200 p-12 text-center shadow-2xs"
    >
      <div class="inline-block animate-spin text-indigo-600 mb-3">
        <svg
          class="w-8 h-8"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
          />
        </svg>
      </div>
      <h3 class="text-sm font-semibold text-gray-900">
        Sedang Memindai dan Menghitung Ulang Saldo...
      </h3>
      <p class="text-xs text-gray-500 mt-1">
        Memeriksa konsistensi seluruh mutasi masuk dan keluar dari ledger terhadap tabel saldo stok.
      </p>
    </div>

    <!-- 3. Clean State (0 Discrepancy) -->
    <div
      v-else-if="store.hasScanned && store.scanResults.discrepancy_count === 0"
      class="bg-white rounded-xl border border-emerald-200 p-8 text-center shadow-2xs"
    >
      <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center mb-3">
        <svg
          class="w-6 h-6"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M5 13l4 4L19 7"
          />
        </svg>
      </div>
      <h3 class="text-sm font-bold text-gray-900">
        Semua Saldo Stok Sudah Sempurna & Sinkron!
      </h3>
      <p class="text-xs text-gray-500 mt-1 max-w-md mx-auto">
        Tidak ada perbedaan antara saldo fisik tercatat pada <code class="font-mono text-gray-700">inventory_balances</code> dengan histori buku besar <code class="font-mono text-gray-700">stock_movements</code>.
      </p>
      <div class="mt-4">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs hover:bg-gray-50 cursor-pointer"
          @click="handleScan"
        >
          <span>Pindai Ulang</span>
        </button>
      </div>
    </div>

    <!-- 4. Discrepancies Table -->
    <div
      v-else-if="store.hasScanned && store.scanResults.discrepancy_count > 0"
      class="space-y-2"
    >
      <div class="flex items-center justify-between px-1">
        <div class="flex items-center gap-2">
          <input
            id="selectAll"
            type="checkbox"
            :checked="isAllSelected"
            :indeterminate="isIndeterminate"
            class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
            @change="toggleSelectAll"
          >
          <label for="selectAll" class="text-xs font-medium text-gray-700 cursor-pointer">
            Pilih Semua Baris ({{ selectedItems.length }} dipilih)
          </label>
        </div>

        <div class="flex items-center gap-2">
          <button
            v-if="selectedItems.length > 0"
            type="button"
            class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold cursor-pointer"
            @click="openBulkConfirmModal"
          >
            Sinkronkan {{ selectedItems.length }} Baris Terpilih
          </button>
        </div>
      </div>

      <div class="overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar">
        <table class="w-full text-left text-xs border-collapse">
          <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10">
            <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
              <th scope="col" class="py-2 px-2 w-8 text-center">
                #
              </th>
              <th scope="col" class="py-2 px-3 whitespace-nowrap">
                Lokasi / Gudang
              </th>
              <th scope="col" class="py-2 px-3 whitespace-nowrap">
                SKU
              </th>
              <th scope="col" class="py-2 px-3">
                Nama Produk
              </th>
              <th scope="col" class="py-2 px-2.5 text-center whitespace-nowrap">
                Kondisi
              </th>
              <th scope="col" class="py-2 px-3 text-right whitespace-nowrap bg-rose-50/40 text-rose-900">
                Saldo Tercatat (Current)
              </th>
              <th scope="col" class="py-2 px-3 text-right whitespace-nowrap bg-emerald-50/40 text-emerald-900">
                Saldo Harusnya (Ledger)
              </th>
              <th scope="col" class="py-2 px-3 text-center whitespace-nowrap">
                Selisih (Delta)
              </th>
              <th scope="col" class="py-2 px-3 text-center whitespace-nowrap w-24">
                Aksi
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr
              v-for="(item, idx) in store.filteredDiscrepancies"
              :key="item.key"
              class="hover:bg-indigo-50/30 transition-colors"
            >
              <!-- Checkbox -->
              <td class="py-2 px-2 text-center">
                <input
                  type="checkbox"
                  :checked="isItemSelected(item)"
                  class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                  @change="toggleItemSelection(item)"
                >
              </td>

              <!-- Location -->
              <td class="py-2 px-3 font-medium text-gray-900 whitespace-nowrap">
                {{ item.location_name }}
              </td>

              <!-- SKU -->
              <td class="py-2 px-3 font-mono font-semibold text-gray-800 whitespace-nowrap">
                {{ item.product_sku }}
              </td>

              <!-- Product Name -->
              <td class="py-2 px-3 text-gray-900">
                <div class="font-medium leading-snug">{{ item.product_name }}</div>
                <div class="text-[10px] text-gray-400 mt-0.5">{{ item.unit_name || '-' }}</div>
              </td>

              <!-- Condition -->
              <td class="py-2 px-2.5 text-center whitespace-nowrap">
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-700">
                  {{ item.condition }}
                </span>
              </td>

              <!-- Current Balance Qty -->
              <td class="py-2 px-3 text-right font-mono font-semibold text-rose-600 bg-rose-50/20 whitespace-nowrap">
                {{ formatQuantity(item.current_quantity) }}
              </td>

              <!-- Expected Balance Qty -->
              <td class="py-2 px-3 text-right font-mono font-semibold text-emerald-700 bg-emerald-50/20 whitespace-nowrap">
                {{ formatQuantity(item.expected_quantity) }}
              </td>

              <!-- Delta Badge -->
              <td class="py-2 px-3 text-center whitespace-nowrap">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold font-mono"
                  :class="Number(item.difference) > 0 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800'"
                >
                  {{ Number(item.difference) > 0 ? `+${formatQuantity(item.difference)}` : formatQuantity(item.difference) }}
                </span>
              </td>

              <!-- Action Button -->
              <td class="py-2 px-3 text-center whitespace-nowrap">
                <button
                  type="button"
                  :disabled="store.loadingApply"
                  class="inline-flex items-center gap-1 rounded bg-indigo-50 px-2 py-1 text-[11px] font-semibold text-indigo-700 hover:bg-indigo-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer transition-colors disabled:opacity-50"
                  @click="openSingleConfirmModal(item)"
                >
                  <svg
                    class="w-3 h-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                  </svg>
                  <span>Perbaiki</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <BaseConfirmation
      v-model="showConfirmModal"
      :title="confirmModalTitle"
      :description="confirmModalDescription"
      confirm-label="Ya, Hitung Ulang & Sinkronkan"
      cancel-label="Batal"
      variant="warning"
      :loading="store.loadingApply"
      @confirm="executeReconciliation"
      @cancel="showConfirmModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useInventoryReconciliationStore } from '../stores/useInventoryReconciliationStore';
import { locationApi } from '@features/location/api/location_api.js';
import { formatQuantity, formatTimestamp } from '@/shared/utils/formatters';
import BaseConfirmation from '@/shared/components/BaseConfirmation.vue';

const store = useInventoryReconciliationStore();

const locations = ref([]);
const selectedItems = ref([]);
const showConfirmModal = ref(false);
const itemToReconcileSingle = ref(null);

const confirmModalTitle = computed(() => {
  if (itemToReconcileSingle.value) {
    return `Sinkronkan SKU ${itemToReconcileSingle.value.product_sku}?`;
  }
  if (selectedItems.value.length > 0) {
    return `Sinkronkan ${selectedItems.value.length} Item Terpilih?`;
  }
  return `Sinkronkan Semua (${store.scanResults.summary.discrepant_pairs}) Selisih Stok?`;
});

const confirmModalDescription = computed(() => {
  if (itemToReconcileSingle.value) {
    const item = itemToReconcileSingle.value;
    return `Saldo stok produk "${item.product_name}" di lokasi "${item.location_name}" akan diperbarui dari ${formatQuantity(item.current_quantity)} menjadi ${formatQuantity(item.expected_quantity)} sesuai total histori mutasi barang.`;
  }
  if (selectedItems.value.length > 0) {
    return `Sebanyak ${selectedItems.value.length} baris saldo stok terpilih akan dihitung ulang dan diselaraskan langsung dengan seluruh mutasi transaksi fisik.`;
  }
  return `Semua (${store.scanResults.summary.discrepant_pairs}) saldo persediaan yang selisih akan disinkronkan kembali agar persis sama dengan total buku besar mutasi transaksi barang. Tindakan ini aman dan menjaga integritas data.`;
});

const isItemSelected = (item) => {
  return selectedItems.value.some(it => it.key === item.key);
};

const toggleItemSelection = (item) => {
  const index = selectedItems.value.findIndex(it => it.key === item.key);
  if (index >= 0) {
    selectedItems.value.splice(index, 1);
  } else {
    selectedItems.value.push(item);
  }
};

const isAllSelected = computed(() => {
  const list = store.filteredDiscrepancies;
  if (list.length === 0) return false;
  return list.every(item => isItemSelected(item));
});

const isIndeterminate = computed(() => {
  const list = store.filteredDiscrepancies;
  if (list.length === 0) return false;
  const count = list.filter(item => isItemSelected(item)).length;
  return count > 0 && count < list.length;
});

const toggleSelectAll = () => {
  const list = store.filteredDiscrepancies;
  if (isAllSelected.value) {
    selectedItems.value = selectedItems.value.filter(sel => !list.some(it => it.key === sel.key));
  } else {
    list.forEach(it => {
      if (!isItemSelected(it)) {
        selectedItems.value.push(it);
      }
    });
  }
};

const openSingleConfirmModal = (item) => {
  itemToReconcileSingle.value = item;
  showConfirmModal.value = true;
};

const openBulkConfirmModal = () => {
  itemToReconcileSingle.value = null;
  showConfirmModal.value = true;
};

const executeReconciliation = async () => {
  try {
    if (itemToReconcileSingle.value) {
      await store.applyReconciliation([itemToReconcileSingle.value.key]);
    } else if (selectedItems.value.length > 0) {
      await store.applyReconciliation(selectedItems.value.map(it => it.key));
      selectedItems.value = [];
    } else {
      await store.applyReconciliation([]);
      selectedItems.value = [];
    }
    showConfirmModal.value = false;
    itemToReconcileSingle.value = null;
  } catch {
    // Error is stored in store.error
  }
};

const handleScan = async () => {
  selectedItems.value = [];
  try {
    await store.runScan();
  } catch {
    // Error handled in store
  }
};

const resetFilters = () => {
  store.resetFilters();
  handleScan();
};

onMounted(async () => {
  try {
    const locRes = await locationApi.getAll({ is_active: true, per_page: 100 });
    locations.value = locRes.data.data?.data || locRes.data.data || [];
  } catch {
    // Ignore error loading location master for filter
  }

  // Auto scan on open
  handleScan();
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
