<template>
  <div class="space-y-3">
    <!-- Top Header & Integrated Filter Toolbar (Compact & Konsisten dengan StoreAllocationFormPage/ListPage) -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <!-- Left: Title & Subtitle -->
      <div>
        <h1 class="text-base font-bold text-gray-900 leading-tight flex items-center gap-1.5">
          <svg
            class="w-4 h-4 text-indigo-600 shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
            />
          </svg>
          <span>Pelacakan Serial Number</span>
        </h1>
        <p class="text-[11px] text-gray-500 mt-0.5">
          Pelacakan siklus hidup unit fisik, lokasi penyimpanan/toko, kondisi operasional, dan riwayat mutasi per-SN.
        </p>
      </div>

      <!-- Right: Search Input & Filters (Unified in Header) -->
      <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
        <!-- Live Search Input with Debounce 300ms -->
        <div class="relative w-full sm:w-64">
          <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
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
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
          </div>
          <input
            id="search-serials"
            v-model="searchQuery"
            type="text"
            placeholder="Cari / Scan No. Seri, SKU, Produk..."
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 pl-8 pr-7 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 font-mono"
            @input="handleSearch"
            @keydown.enter.prevent="handleDirectLookup"
          >
          <button
            v-if="searchQuery"
            type="button"
            class="absolute inset-y-0 right-0 pr-2 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer text-sm"
            title="Hapus pencarian"
            @click="clearSearch"
          >
            &times;
          </button>
        </div>

        <!-- Quick Status Filter -->
        <select
          v-model="selectedStatus"
          class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer text-gray-700"
          @change="loadData(1)"
        >
          <option value="">
            Semua Status
          </option>
          <option value="IN_STOCK">
            Tersedia di Gudang (IN_STOCK)
          </option>
          <option value="INSTALLED">
            Terpasang di Toko (INSTALLED)
          </option>
          <option value="DEFECTIVE">
            Rusak / Afkir (DEFECTIVE)
          </option>
          <option value="RETURNED_TO_VENDOR">
            Servis Vendor (RMA)
          </option>
          <option value="DISPOSED">
            Musnah / Dihapus (DISPOSED)
          </option>
        </select>

        <!-- Quick Condition Filter -->
        <select
          v-model="selectedCondition"
          class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer text-gray-700"
          @change="loadData(1)"
        >
          <option value="">
            Semua Kondisi
          </option>
          <option value="GOOD">
            Bagus (GOOD)
          </option>
          <option value="DEFECTIVE">
            Rusak (DEFECTIVE)
          </option>
        </select>

        <!-- Reset Button -->
        <button
          v-if="hasActiveFilters"
          type="button"
          class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-50 shadow-2xs cursor-pointer whitespace-nowrap"
          title="Reset Filter"
          @click="resetFilters"
        >
          Reset
        </button>
      </div>
    </div>

    <!-- Error / Lookup Banner -->
    <div
      v-if="store.error || store.lookupError"
      class="rounded-lg bg-rose-50 border border-rose-200 p-3 text-xs text-rose-800 flex items-center justify-between shadow-2xs"
    >
      <div class="flex items-center gap-1.5">
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
        <span>{{ store.error || store.lookupError }}</span>
      </div>
      <button
        type="button"
        class="text-rose-500 hover:text-rose-700 text-xs font-semibold cursor-pointer"
        @click="clearErrors"
      >
        Tutup
      </button>
    </div>

    <!-- Main Data Table -->
    <div class="overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar relative">
      <div
        v-if="store.loading"
        class="absolute inset-0 bg-white/60 backdrop-blur-2xs z-20 flex items-center justify-center"
      >
        <div class="inline-flex items-center gap-2 text-indigo-600 font-semibold bg-white px-3.5 py-2 rounded-lg shadow-sm text-xs border border-gray-100">
          <svg
            class="w-4 h-4 animate-spin"
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
          <span>Memuat data serial number...</span>
        </div>
      </div>

      <table class="w-full text-left text-xs border-collapse">
        <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10 text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
          <tr>
            <th
              scope="col"
              class="py-2 px-2 w-8 text-center whitespace-nowrap"
            >
              No.
            </th>
            <th
              scope="col"
              class="py-2 px-3 whitespace-nowrap"
            >
              Serial Number
            </th>
            <th
              scope="col"
              class="py-2 px-3 whitespace-nowrap"
            >
              Produk & SKU
            </th>
            <th
              scope="col"
              class="py-2 px-2.5 text-center whitespace-nowrap"
            >
              Status Unit
            </th>
            <th
              scope="col"
              class="py-2 px-2.5 text-center whitespace-nowrap"
            >
              Kondisi
            </th>
            <th
              scope="col"
              class="py-2 px-3 whitespace-nowrap"
            >
              Posisi Saat Ini
            </th>
            <th
              scope="col"
              class="py-2 px-3 whitespace-nowrap"
            >
              Terakhir Diperbarui
            </th>
            <th
              scope="col"
              class="py-2 px-2.5 text-center whitespace-nowrap w-20"
            >
              Aksi
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <BaseTableEmpty
            v-if="!store.loading && (!store.serials?.data || store.serials.data.length === 0)"
            :colspan="8"
            message="Tidak ada data serial number yang cocok."
            :hint="hasActiveFilters ? 'Silakan sesuaikan filter pencarian atau scan serial number lain.' : ''"
          />

          <tr
            v-for="(item, index) in store.serials?.data"
            :key="item.id"
            class="hover:bg-gray-50/80 transition-colors cursor-pointer group"
            @click="openDetail(item)"
          >
            <!-- 1. Nomor Baris -->
            <td class="py-2 px-2 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(store.serials?.meta, index) }}
            </td>

            <!-- 2. Serial Number -->
            <td class="py-2 px-3 text-[11px] whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <span class="font-mono font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">
                  {{ item.serial_number }}
                </span>
                <button
                  type="button"
                  title="Salin Serial Number"
                  class="opacity-0 group-hover:opacity-100 text-gray-400 hover:text-gray-700 p-0.5 rounded transition-opacity cursor-pointer"
                  @click.stop="copyText(item.serial_number)"
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
                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                    />
                  </svg>
                </button>
              </div>
            </td>

            <!-- 3. Produk & SKU -->
            <td class="py-2 px-3 text-[11px] whitespace-nowrap">
              <div class="font-medium text-gray-900">
                {{ item.product?.name || '—' }}
              </div>
              <div class="text-[10px] text-gray-400 font-mono">
                {{ item.product?.sku }}
              </div>
            </td>

            <!-- 4. Status Unit -->
            <td class="py-2 px-2.5 text-[11px] text-center whitespace-nowrap">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold"
                :class="statusBadgeClass(item.status)"
              >
                {{ item.status_label || item.status }}
              </span>
            </td>

            <!-- 5. Kondisi -->
            <td class="py-2 px-2.5 text-[11px] text-center whitespace-nowrap">
              <span
                class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider inline-flex items-center"
                :class="item.current_condition === 'DEFECTIVE' ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20' : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20'"
              >
                {{ item.current_condition === 'DEFECTIVE' ? 'RUSAK' : 'BAGUS' }}
              </span>
            </td>

            <!-- 6. Posisi Saat Ini -->
            <td class="py-2 px-3 text-[11px] whitespace-nowrap">
              <template v-if="item.current_store">
                <div class="font-medium text-blue-700 flex items-center gap-1">
                  <svg
                    class="w-3.5 h-3.5 text-blue-500 shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                    />
                  </svg>
                  <span>Toko: {{ item.current_store.name }}</span>
                </div>
              </template>
              <template v-else-if="item.current_location">
                <div class="font-medium text-gray-700 flex items-center gap-1">
                  <svg
                    class="w-3.5 h-3.5 text-gray-400 shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                    />
                  </svg>
                  <span>{{ item.current_location.name }}</span>
                </div>
              </template>
              <template v-else>
                <span class="text-gray-400 italic">Di luar lokasi internal</span>
              </template>
            </td>

            <!-- 7. Terakhir Diperbarui -->
            <td class="py-2 px-3 text-gray-500 text-[11px] whitespace-nowrap">
              {{ formatDateTime(item.updated_at) }}
            </td>

            <!-- 8. Aksi -->
            <td
              class="py-2 px-2.5 text-center whitespace-nowrap"
              @click.stop
            >
              <button
                type="button"
                class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-1 text-[10px] font-semibold text-indigo-700 hover:bg-indigo-100 hover:text-indigo-800 transition-colors shadow-2xs border border-indigo-100/80 cursor-pointer"
                title="Buka riwayat pergerakan unit"
                @click="openDetail(item)"
              >
                <svg
                  class="w-3 h-3 text-indigo-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                <span>Timeline</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination (Standard BasePagination Component) -->
    <BasePagination
      :pagination="store.serials?.meta"
      :loading="store.loading"
      @change="changePage"
    />

    <!-- Timeline Detail Modal -->
    <ProductSerialDetailModal
      :is-open="isModalOpen"
      :serial="store.selectedSerial"
      @close="closeModal"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useProductSerialStore } from '../stores/useProductSerialStore';
import ProductSerialDetailModal from '../components/ProductSerialDetailModal.vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import BaseTableEmpty from '@/shared/components/BaseTableEmpty.vue';
import { rowNumber } from '@/shared/utils/formatters.js';

const store = useProductSerialStore();

const searchQuery = ref('');
const selectedStatus = ref('');
const selectedCondition = ref('');
const isModalOpen = ref(false);

let searchTimer = null;

const hasActiveFilters = computed(() => {
    return Boolean(searchQuery.value || selectedStatus.value || selectedCondition.value);
});

onMounted(() => {
    loadData(1);
});

async function loadData(page = 1) {
    const params = {
        page,
        per_page: 15,
    };
    if (searchQuery.value) params.search = searchQuery.value.trim();
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (selectedCondition.value) params.condition = selectedCondition.value;

    await store.fetchSerials(params);
}

// Live search with standard 300ms debounce (consistent with StoreAllocationListPage and others)
function handleSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        loadData(1);
    }, 300);
}

// Direct scan / barcode / Enter trigger
async function handleDirectLookup() {
    const query = searchQuery.value.trim();
    if (!query) return;

    try {
        await store.lookupSerial(query);
        if (store.selectedSerial) {
            isModalOpen.value = true;
            return;
        }
    } catch {
        // Fallback to table search
    }
    clearTimeout(searchTimer);
    loadData(1);
}

function clearSearch() {
    searchQuery.value = '';
    clearTimeout(searchTimer);
    loadData(1);
}

function resetFilters() {
    searchQuery.value = '';
    selectedStatus.value = '';
    selectedCondition.value = '';
    clearTimeout(searchTimer);
    loadData(1);
}

function changePage(page) {
    loadData(page);
}

function clearErrors() {
    store.error = null;
    store.lookupError = null;
}

async function openDetail(serialItem) {
    try {
        await store.fetchSerialDetail(serialItem.id);
        isModalOpen.value = true;
    } catch (e) {
        console.error(e);
    }
}

function closeModal() {
    isModalOpen.value = false;
    store.clearSelectedSerial();
}

function copyText(text) {
    if (!text) return;
    navigator.clipboard.writeText(text);
}

function formatDateTime(dateStr) {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
}

function statusBadgeClass(status) {
    switch (status) {
        case 'IN_STOCK':
            return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
        case 'INSTALLED':
            return 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20';
        case 'DEFECTIVE':
            return 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20';
        case 'RETURNED_TO_VENDOR':
            return 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20';
        case 'DISPOSED':
            return 'bg-gray-100 text-gray-700 ring-1 ring-gray-600/20';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}
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
