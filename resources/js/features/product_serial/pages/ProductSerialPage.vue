<template>
  <div class="space-y-4">
    <!-- Top Barcode / Quick Lookup Box -->
    <div class="bg-linear-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-4 sm:p-5 text-white shadow-md border border-slate-800">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="p-1.5 rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-400/30">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
              </svg>
            </span>
            <h1 class="text-lg font-bold tracking-tight text-white">
              Pelacakan Siklus Hidup Serial Number
            </h1>
          </div>
          <p class="text-xs text-slate-300 mt-1 max-w-xl">
            Lacak riwayat lengkap unit fisik, keberadaan unit (gudang/teknisi/toko), kondisi operasional, dan mutasi dari awal registrasi hingga servis vendor.
          </p>
        </div>

        <!-- Quick Scan / Search Input -->
        <form class="flex items-center gap-2 w-full lg:w-auto" @submit.prevent="handleQuickLookup">
          <div class="relative flex-1 sm:w-80">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
              </svg>
            </div>
            <input
              v-model="quickSnInput"
              type="text"
              placeholder="Scan Barcode / Ketik No. Seri..."
              class="w-full pl-9 pr-3 py-2 bg-slate-800/90 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent font-mono"
            >
          </div>
          <button
            type="submit"
            :disabled="!quickSnInput.trim() || store.detailLoading"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors flex items-center gap-1.5 whitespace-nowrap cursor-pointer"
          >
            <span v-if="store.detailLoading" class="inline-block animate-spin h-3.5 w-3.5 border-2 border-white border-t-transparent rounded-full" />
            <span v-else>Cari Unit</span>
          </button>
        </form>
      </div>

      <!-- Quick lookup feedback error -->
      <div v-if="store.lookupError" class="mt-3 p-2.5 rounded-lg bg-rose-500/20 border border-rose-500/40 text-rose-200 text-xs flex items-center justify-between">
        <span>{{ store.lookupError }}</span>
        <button type="button" class="text-rose-300 hover:text-white text-xs underline cursor-pointer" @click="store.lookupError = null">
          Tutup
        </button>
      </div>
    </div>

    <!-- Filter Toolbar & Statistics -->
    <div class="bg-white rounded-xl border border-slate-200 p-3.5 shadow-2xs space-y-3">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
        <!-- Search Query -->
        <div class="relative flex-1 max-w-sm">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Cari No. Seri / SKU / Nama Produk..."
            class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
            @input="debouncedSearch"
          >
          <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        <!-- Filter Selects -->
        <div class="flex items-center gap-2 flex-wrap">
          <!-- Status Filter -->
          <select
            v-model="filters.status"
            class="text-xs rounded-lg border border-slate-300 py-1.5 px-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
            @change="loadData(1)"
          >
            <option value="">Semua Status</option>
            <option value="IN_STOCK">Tersedia di Gudang (IN_STOCK)</option>
            <option value="INSTALLED">Terpasang di Toko (INSTALLED)</option>
            <option value="DEFECTIVE">Rusak / Afkir (DEFECTIVE)</option>
            <option value="RETURNED_TO_VENDOR">Klaim Servis Vendor (RMA)</option>
            <option value="DISPOSED">Musnah / Dihapus (DISPOSED)</option>
          </select>

          <!-- Condition Filter -->
          <select
            v-model="filters.condition"
            class="text-xs rounded-lg border border-slate-300 py-1.5 px-2.5 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
            @change="loadData(1)"
          >
            <option value="">Semua Kondisi</option>
            <option value="GOOD">Bagus (GOOD)</option>
            <option value="DEFECTIVE">Rusak (DEFECTIVE)</option>
          </select>

          <!-- Reset Filter Button -->
          <button
            v-if="hasActiveFilters"
            type="button"
            class="px-2.5 py-1.5 text-xs text-slate-600 hover:text-slate-900 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors"
            @click="resetFilters"
          >
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Main Table -->
    <div class="overflow-x-auto shadow-2xs border border-slate-200 rounded-xl bg-white custom-scrollbar">
      <table class="w-full text-left text-xs border-collapse">
        <thead class="bg-slate-50/80 text-slate-700 font-semibold border-b border-slate-200 uppercase tracking-wider text-[10px]">
          <tr>
            <th class="py-3 px-4">Serial Number</th>
            <th class="py-3 px-4">Produk</th>
            <th class="py-3 px-4 text-center">Status Unit</th>
            <th class="py-3 px-4 text-center">Kondisi</th>
            <th class="py-3 px-4">Posisi Saat Ini</th>
            <th class="py-3 px-4">Terakhir Diperbarui</th>
            <th class="py-3 px-4 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="store.loading">
            <td colspan="7" class="py-12 text-center text-slate-400">
              <div class="flex items-center justify-center gap-2">
                <span class="inline-block animate-spin h-4 w-4 border-2 border-indigo-600 border-t-transparent rounded-full" />
                <span>Memuat data serial number...</span>
              </div>
            </td>
          </tr>

          <tr v-else-if="!store.serials?.data || store.serials.data.length === 0">
            <td colspan="7" class="py-12 text-center text-slate-400">
              <p class="font-medium text-slate-600">Tidak ada data serial number yang cocok.</p>
              <p class="text-[11px] text-slate-400 mt-0.5">Silakan sesuaikan filter pencarian Anda atau scan serial number lain.</p>
            </td>
          </tr>

          <tr
            v-for="item in store.serials.data"
            v-else
            :key="item.id"
            class="hover:bg-indigo-50/30 transition-colors group cursor-pointer"
            @click="openDetail(item)"
          >
            <!-- Serial Number -->
            <td class="py-3 px-4">
              <div class="flex items-center gap-1.5">
                <span class="font-mono font-bold text-slate-900 group-hover:text-indigo-600">
                  {{ item.serial_number }}
                </span>
                <button
                  type="button"
                  title="Salin Serial Number"
                  class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-slate-700 p-0.5 rounded transition-opacity"
                  @click.stop="copyText(item.serial_number)"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                </button>
              </div>
            </td>

            <!-- Product -->
            <td class="py-3 px-4">
              <div class="font-medium text-slate-900">{{ item.product?.name || '—' }}</div>
              <div class="text-[10px] text-slate-400 font-mono">{{ item.product?.sku }}</div>
            </td>

            <!-- Status -->
            <td class="py-3 px-4 text-center">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold"
                :class="statusBadgeClass(item.status)"
              >
                {{ item.status_label || item.status }}
              </span>
            </td>

            <!-- Condition -->
            <td class="py-3 px-4 text-center">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium"
                :class="item.current_condition === 'GOOD' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
              >
                {{ item.current_condition === 'GOOD' ? 'Bagus' : 'Rusak' }}
              </span>
            </td>

            <!-- Position -->
            <td class="py-3 px-4">
              <template v-if="item.current_store">
                <div class="font-medium text-blue-700 flex items-center gap-1">
                  <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  <span>{{ item.current_store.name }}</span>
                </div>
              </template>
              <template v-else-if="item.current_location">
                <div class="font-medium text-slate-700 flex items-center gap-1">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                  </svg>
                  <span>{{ item.current_location.name }}</span>
                </div>
              </template>
              <template v-else>
                <span class="text-slate-400 italic">Di luar lokasi internal</span>
              </template>
            </td>

            <!-- Updated At -->
            <td class="py-3 px-4 text-slate-500 text-[11px] whitespace-nowrap">
              {{ formatDateTime(item.updated_at) }}
            </td>

            <!-- Action -->
            <td class="py-3 px-4 text-right">
              <button
                type="button"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-[11px] transition-colors cursor-pointer"
                @click.stop="openDetail(item)"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Timeline</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Table Footer / Pagination -->
      <div
        v-if="store.serials?.meta && store.serials.meta.total > 0"
        class="bg-slate-50/80 px-4 py-2.5 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500"
      >
        <div>
          Menampilkan <span class="font-semibold text-slate-700">{{ store.serials.meta.from || 1 }}</span>
          sampai <span class="font-semibold text-slate-700">{{ store.serials.meta.to || store.serials.data.length }}</span>
          dari <span class="font-semibold text-slate-700">{{ store.serials.meta.total }}</span> unit serial number
        </div>

        <div class="flex items-center gap-1.5">
          <button
            type="button"
            :disabled="store.serials.meta.current_page <= 1"
            class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
            @click="loadData(store.serials.meta.current_page - 1)"
          >
            Sebelumnya
          </button>
          <span class="px-2 py-1 font-semibold text-slate-700">
            Hal {{ store.serials.meta.current_page }} dari {{ store.serials.meta.last_page }}
          </span>
          <button
            type="button"
            :disabled="store.serials.meta.current_page >= store.serials.meta.last_page"
            class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"
            @click="loadData(store.serials.meta.current_page + 1)"
          >
            Selanjutnya
          </button>
        </div>
      </div>
    </div>

    <!-- Timeline Detail Modal -->
    <ProductSerialDetailModal
      :is-open="isModalOpen"
      :serial="store.selectedSerial"
      @close="closeModal"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useProductSerialStore } from '../stores/useProductSerialStore';
import ProductSerialDetailModal from '../components/ProductSerialDetailModal.vue';

const store = useProductSerialStore();

const quickSnInput = ref('');
const isModalOpen = ref(false);

const filters = reactive({
    search: '',
    status: '',
    condition: '',
});

let searchDebounceTimeout = null;

const hasActiveFilters = computed(() => {
    return Boolean(filters.search || filters.status || filters.condition);
});

onMounted(() => {
    loadData(1);
});

async function loadData(page = 1) {
    const params = {
        page,
        per_page: 15,
    };
    if (filters.search) params.search = filters.search;
    if (filters.status) params.status = filters.status;
    if (filters.condition) params.condition = filters.condition;

    await store.fetchSerials(params);
}

function debouncedSearch() {
    clearTimeout(searchDebounceTimeout);
    searchDebounceTimeout = setTimeout(() => {
        loadData(1);
    }, 350);
}

function resetFilters() {
    filters.search = '';
    filters.status = '';
    filters.condition = '';
    loadData(1);
}

async function handleQuickLookup() {
    const sn = quickSnInput.value.trim();
    if (!sn) return;

    try {
        await store.lookupSerial(sn);
        if (store.selectedSerial) {
            isModalOpen.value = true;
        }
    } catch {
        // Error handled in store.lookupError
    }
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
            return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
        case 'INSTALLED':
            return 'bg-blue-100 text-blue-800 border border-blue-200';
        case 'DEFECTIVE':
            return 'bg-rose-100 text-rose-800 border border-rose-200';
        case 'RETURNED_TO_VENDOR':
            return 'bg-purple-100 text-purple-800 border border-purple-200';
        case 'DISPOSED':
            return 'bg-slate-200 text-slate-800 border border-slate-300';
        default:
            return 'bg-slate-100 text-slate-700';
    }
}
</script>
