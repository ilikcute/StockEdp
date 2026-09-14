<template>
  <div class="space-y-3">
    <!-- Top Header & Filter Toolbar (Compact) -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs space-y-2.5">
      <!-- Primary Header Row -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <!-- Title & Subtitle -->
        <div>
          <h1 class="text-base font-bold text-gray-900 leading-tight flex items-center gap-1.5">
            <svg
              class="w-4 h-4 text-indigo-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            Kartu Stok (Stock Card)
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Riwayat pergerakan stok suatu produk di lokasi tertentu dalam periode waktu tertentu.
          </p>
        </div>

        <!-- Controls (Product Search, Location, Date toggle, Submit, Export) -->
        <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
          <!-- Product Selector Compact -->
          <div class="w-full sm:w-56 relative">
            <input
              v-model="productSearch"
              type="text"
              class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
              placeholder="Pilih Produk *..."
              @input="onProductSearch"
              @focus="showProductDropdown = true"
            >
            <div
              v-if="selectedProduct"
              class="absolute inset-y-0 right-0 pr-2 flex items-center"
            >
              <button
                type="button"
                class="text-gray-400 hover:text-gray-600 cursor-pointer text-xs"
                @click="clearProduct"
              >
                ✕
              </button>
            </div>
            <!-- Dropdown Results -->
            <div
              v-if="showProductDropdown && masterStore.products.length > 0"
              class="absolute z-20 mt-1 w-full bg-white shadow-lg max-h-52 rounded-lg py-1 text-xs border border-gray-200 overflow-auto custom-scrollbar"
            >
              <div
                v-for="prod in masterStore.products"
                :key="prod.id"
                class="cursor-pointer select-none py-1.5 px-2.5 hover:bg-indigo-50 transition-colors"
                @click="selectProduct(prod)"
              >
                <div class="font-medium text-gray-900 truncate">
                  {{ prod.name }}
                </div>
                <div class="text-[10px] text-gray-500 font-mono">
                  {{ prod.sku }}
                </div>
              </div>
            </div>
          </div>

          <!-- Location Selector -->
          <div class="w-full sm:w-44">
            <select
              v-model="filters.location_id"
              class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
              <option value="">
                -- Pilih Lokasi * --
              </option>
              <option
                v-for="loc in masterStore.locations"
                :key="loc.id"
                :value="loc.id"
              >
                {{ loc.code ? `${loc.code} — ` : '' }}{{ loc.name }}
              </option>
            </select>
          </div>

          <!-- Toggle Date / Period -->
          <button
            type="button"
            :class="[
              'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium shadow-2xs transition-colors cursor-pointer whitespace-nowrap',
              showDateRow
                ? 'bg-indigo-50 border-indigo-200 text-indigo-700'
                : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
            ]"
            @click="showDateRow = !showDateRow"
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
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
            <span>Periode</span>
          </button>

          <!-- Tampilkan Button -->
          <button
            type="button"
            :disabled="!canFetch || store.loading"
            class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer whitespace-nowrap"
            @click="fetchData(1)"
          >
            <span v-if="store.loading">Memuat...</span>
            <span v-else>Tampilkan</span>
          </button>

          <!-- Export CSV Control -->
          <ReportCsvExportControl
            size="sm"
            :loading="exportStore.isExporting(reportKey)"
            :disabled="!canFetch"
            disabled-reason="Pilih produk, lokasi, dan periode terlebih dahulu."
            :error="exportStore.errorFor(reportKey)"
            :status="exportStore.statusFor(reportKey)"
            :validation-errors="exportStore.validationErrorsFor(reportKey)"
            :success-message="exportStore.successFor(reportKey)"
            @export="exportCsv"
            @dismiss="exportStore.clearFeedback(reportKey)"
          />
        </div>
      </div>

      <!-- Collapsible Date & Settings Row -->
      <div
        v-if="showDateRow"
        class="border-t border-gray-100 pt-2.5 flex flex-wrap items-center justify-between gap-3 text-xs"
      >
        <div class="flex items-center gap-3 flex-wrap">
          <div class="flex items-center gap-2">
            <span class="text-[11px] font-semibold text-gray-600">Mulai *:</span>
            <input
              v-model="filters.start_date"
              type="date"
              class="rounded-lg border border-gray-300 bg-white py-1 px-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
          </div>
          <div class="flex items-center gap-2">
            <span class="text-[11px] font-semibold text-gray-600">Sampai *:</span>
            <input
              v-model="filters.end_date"
              type="date"
              class="rounded-lg border border-gray-300 bg-white py-1 px-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            >
          </div>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-[11px] text-gray-500 font-medium">Per Halaman:</span>
          <select
            v-model="filters.per_page"
            class="rounded-lg border border-gray-300 bg-white py-1 pl-2 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            @change="fetchData(1)"
          >
            <option value="15">
              15 Baris
            </option>
            <option value="50">
              50 Baris
            </option>
            <option value="100">
              100 Baris
            </option>
          </select>
        </div>
      </div>

      <!-- Local Validation Error -->
      <div
        v-if="localValidationError"
        class="text-xs text-rose-600 font-medium border-t border-rose-100 pt-1.5"
      >
        {{ localValidationError }}
      </div>
    </div>

    <!-- Error State -->
    <div
      v-if="store.error"
      class="rounded-lg bg-rose-50 p-2.5 border border-rose-200 text-xs text-rose-800 flex items-center justify-between shadow-2xs"
    >
      <span>{{ store.error }}</span>
      <button
        type="button"
        class="text-rose-500 hover:text-rose-700 text-xs font-semibold cursor-pointer"
        @click="store.error = null"
      >
        Tutup
      </button>
    </div>

    <!-- Validation Errors -->
    <div
      v-if="Object.keys(store.validationErrors).length > 0"
      class="rounded-lg bg-amber-50 p-2.5 border border-amber-200 text-xs text-amber-800 shadow-2xs"
    >
      <ul class="list-disc pl-5 space-y-0.5">
        <li
          v-for="(errors, field) in store.validationErrors"
          :key="field"
        >
          {{ errors.join(', ') }}
        </li>
      </ul>
    </div>

    <!-- Prompt / Loading -->
    <div
      v-if="!hasFetchedData"
      class="rounded-xl bg-indigo-50/40 p-6 border border-indigo-100 text-center shadow-2xs"
    >
      <p class="text-xs font-semibold text-indigo-900">
        Pilih produk, lokasi, dan periode untuk melihat riwayat kartu stok.
      </p>
    </div>

    <div
      v-else-if="store.loading"
      class="rounded-xl bg-white p-8 border border-gray-200 text-center shadow-2xs"
    >
      <p class="text-xs text-gray-500 font-medium">
        Memuat kartu stok...
      </p>
    </div>

    <!-- Stock Card Data -->
    <div
      v-else
      class="space-y-3"
    >
      <!-- Stock Card Summary Badges (Compact) -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
        <div class="bg-white px-3 py-2 rounded-xl shadow-2xs border border-gray-200">
          <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">
            Harga Satuan
          </div>
          <div class="text-base font-bold font-mono text-indigo-700 mt-0.5 leading-tight">
            {{ formatRupiah(store.meta?.product?.unit_price) }}
          </div>
        </div>
        <div class="bg-white px-3 py-2 rounded-xl shadow-2xs border border-gray-200">
          <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400">
            Saldo Awal
          </div>
          <div class="text-base font-bold font-mono text-gray-900 mt-0.5 leading-tight">
            {{ formatQuantity(store.summary?.opening_balance) }}
          </div>
        </div>
        <div class="bg-emerald-50/50 px-3 py-2 rounded-xl shadow-2xs border border-emerald-200">
          <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">
            Total Masuk
          </div>
          <div class="text-base font-bold font-mono text-emerald-700 mt-0.5 leading-tight">
            +{{ formatQuantity(store.summary?.total_quantity_in) }}
          </div>
        </div>
        <div class="bg-rose-50/50 px-3 py-2 rounded-xl shadow-2xs border border-rose-200">
          <div class="text-[10px] font-bold uppercase tracking-wider text-rose-800">
            Total Keluar
          </div>
          <div class="text-base font-bold font-mono text-rose-700 mt-0.5 leading-tight">
            -{{ formatQuantity(store.summary?.total_quantity_out) }}
          </div>
        </div>
        <div class="bg-indigo-50/50 px-3 py-2 rounded-xl shadow-2xs border border-indigo-200">
          <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-800">
            Saldo Akhir
          </div>
          <div class="text-base font-bold font-mono text-indigo-900 mt-0.5 leading-tight">
            {{ formatQuantity(store.summary?.closing_balance) }}
          </div>
        </div>
      </div>

      <!-- High-Density Data Table -->
      <div class="overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar">
        <table class="w-full text-left text-xs border-collapse">
          <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10">
            <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
              <th
                scope="col"
                class="py-1.5 px-1.5 w-8 text-center whitespace-nowrap"
              >
                No.
              </th>
              <th
                scope="col"
                class="py-1.5 px-2 whitespace-nowrap"
              >
                Tanggal Dokumen
              </th>
              <th
                scope="col"
                class="py-1.5 px-2 whitespace-nowrap"
              >
                Tanggal Posting
              </th>
              <th
                scope="col"
                class="py-1.5 px-2 whitespace-nowrap"
              >
                Referensi / Tipe
              </th>
              <th
                scope="col"
                class="py-1.5 px-2 text-right whitespace-nowrap"
              >
                Saldo Sebelum
              </th>
              <th
                scope="col"
                class="py-1.5 px-2 text-right whitespace-nowrap text-emerald-700"
              >
                Masuk
              </th>
              <th
                scope="col"
                class="py-1.5 px-2 text-right whitespace-nowrap text-rose-700"
              >
                Keluar
              </th>
              <th
                scope="col"
                class="py-1.5 px-2 text-right whitespace-nowrap"
              >
                Saldo Sesudah
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr v-if="store.data.length === 0">
              <td
                colspan="8"
                class="py-8 text-center text-xs text-gray-400"
              >
                Tidak ada pergerakan stok pada periode ini.
              </td>
            </tr>
            <tr
              v-for="(item, index) in store.data"
              :key="item.id"
              class="hover:bg-gray-50/80 transition-colors"
            >
              <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
                {{ rowNumber(store.pagination, index) }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-gray-600 font-mono whitespace-nowrap">
                {{ item.document_date || '-' }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-gray-500 font-mono whitespace-nowrap">
                {{ item.movement_posted_at || item.occurred_at || '-' }}
              </td>
              <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
                <div class="font-medium text-gray-900 font-mono">
                  {{ item.reference_number || item.movement_id }}
                </div>
                <div class="text-[10px] text-gray-400">
                  {{ item.movement_type }}
                </div>
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right font-mono text-gray-500 whitespace-nowrap">
                {{ formatQuantity(item.quantity_before) }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right font-mono font-semibold text-emerald-600 whitespace-nowrap">
                {{ item.quantity_in && item.quantity_in !== '0.0000' && item.quantity_in !== '0' ? '+' + formatQuantity(item.quantity_in) : '-' }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right font-mono font-semibold text-rose-600 whitespace-nowrap">
                {{ item.quantity_out && item.quantity_out !== '0.0000' && item.quantity_out !== '0' ? '-' + formatQuantity(item.quantity_out) : '-' }}
              </td>
              <td class="py-1.5 px-2 text-[11px] text-right font-mono font-semibold text-gray-900 whitespace-nowrap">
                {{ formatQuantity(item.quantity_after) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="store.meta?.total > 0"
        class="flex items-center justify-between border-t border-gray-200 bg-white px-3 py-2 rounded-xl shadow-2xs text-xs"
      >
        <div class="text-[11px] text-gray-600">
          Menampilkan <span class="font-semibold text-gray-900">{{ store.meta.from }}</span> sampai <span class="font-semibold text-gray-900">{{ store.meta.to }}</span> dari <span class="font-semibold text-gray-900">{{ store.meta.total }}</span> hasil
        </div>
        <div class="flex gap-1.5">
          <button
            :disabled="store.meta.current_page <= 1"
            class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs shadow-2xs hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            @click="changePage(store.meta.current_page - 1)"
          >
            Sebelumnya
          </button>
          <button
            :disabled="store.meta.current_page >= store.meta.last_page"
            class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs shadow-2xs hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            @click="changePage(store.meta.current_page + 1)"
          >
            Selanjutnya
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useStockCardReportStore } from '../stores/useStockCardReportStore';
import { useReportFilterOptionsStore } from '../stores/useReportFilterOptionsStore';
import { useReportCsvExportStore } from '../stores/useReportCsvExportStore';
import { toLocalDateInputValue, cleanReportExportFilters } from '../utils/reportHelpers';
import ReportCsvExportControl from '../components/ReportCsvExportControl.vue';
import { formatRupiah, formatQuantity, rowNumber } from '@/shared/utils/formatters.js';

const store = useStockCardReportStore();
const masterStore = useReportFilterOptionsStore();
const exportStore = useReportCsvExportStore();
const reportKey = 'stock-card';

const productSearch = ref('');
const showProductDropdown = ref(false);
const selectedProduct = ref(null);
const localValidationError = ref('');
const hasFetchedData = ref(false);
const showDateRow = ref(false);

const filters = reactive({
    product_id: '',
    location_id: '',
    start_date: '',
    end_date: '',
    per_page: '15',
});

const parseDateOnly = (dateStr) => {
    if (!dateStr || !dateStr.includes('-')) return null;
    const [y, m, d] = dateStr.split('-').map(Number);
    return new Date(y, m - 1, d);
};

const today = new Date();
const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
filters.end_date = toLocalDateInputValue(today);
filters.start_date = toLocalDateInputValue(firstDayOfMonth);

let productSearchTimer = null;
const onProductSearch = () => {
    clearTimeout(productSearchTimer);
    productSearchTimer = setTimeout(() => {
        if (productSearch.value.trim().length >= 2) {
            masterStore.searchProducts(productSearch.value);
        }
    }, 300);
};

const selectProduct = (prod) => {
    selectedProduct.value = prod;
    filters.product_id = prod.id;
    productSearch.value = prod.name;
    showProductDropdown.value = false;
};

const clearProduct = () => {
    selectedProduct.value = null;
    filters.product_id = '';
    productSearch.value = '';
};

watch(productSearch, (val) => {
    if (!val) {
        clearProduct();
    }
});

const canFetch = computed(() => {
    return Boolean(filters.product_id && filters.location_id && filters.start_date && filters.end_date);
});

const validateFilters = () => {
    localValidationError.value = '';

    if (!filters.product_id || !filters.location_id || !filters.start_date || !filters.end_date) {
        localValidationError.value = 'Mohon lengkapi produk, lokasi, tanggal mulai, dan tanggal akhir.';
        return false;
    }

    const start = parseDateOnly(filters.start_date);
    const end = parseDateOnly(filters.end_date);

    if (!start || !end) {
        localValidationError.value = 'Format tanggal tidak valid.';
        return false;
    }

    if (start > end) {
        localValidationError.value = 'Tanggal mulai tidak boleh melewati tanggal akhir.';
        return false;
    }

    const diffTime = end.getTime() - start.getTime();
    const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays > 366) {
        localValidationError.value = 'Rentang waktu maksimal adalah 366 hari.';
        return false;
    }

    return true;
};

const fetchData = async (page = 1) => {
    if (!validateFilters()) return;

    hasFetchedData.value = true;

    await store.fetchStockCard({
        page,
        ...filters,
    });
};

const exportCsv = async () => {
    if (!validateFilters()) return;
    const params = cleanReportExportFilters({ ...filters });
    await exportStore.exportReport(reportKey, params);
};

const changePage = (page) => {
    if (!store.meta || page < 1 || page > store.meta.last_page) {
        return;
    }
    fetchData(page);
};

onMounted(async () => {
    await masterStore.fetchOptions();
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
