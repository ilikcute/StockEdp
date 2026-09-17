<template>
  <div class="space-y-3">
    <!-- Top Header & Filter Toolbar -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-3 shadow-2xs space-y-3">
      <!-- Title Row -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
        <div>
          <h1 class="text-base font-bold text-gray-900 leading-tight flex items-center gap-2">
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
                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
              />
            </svg>
            Log Aktivitas Sistem (Audit Trail)
          </h1>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Rekam jejak seluruh mutasi data sensitif, perubahan status, dan autentikasi pengguna.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-xs font-semibold text-gray-700 shadow-2xs transition-colors cursor-pointer"
            :disabled="store.loading"
            @click="refreshData"
          >
            <svg
              class="w-3.5 h-3.5 text-gray-500"
              :class="{ 'animate-spin': store.loading }"
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
            <span>Muat Ulang</span>
          </button>
        </div>
      </div>

      <!-- Filters Row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 pt-1 border-t border-gray-100 text-xs">
        <!-- Search -->
        <div>
          <label
            for="audit-search"
            class="text-[10px] uppercase font-semibold text-gray-500 block mb-1"
          >Pencarian</label>
          <div class="relative">
            <input
              id="audit-search"
              v-model="filters.search"
              type="text"
              placeholder="Cari deskripsi, modul, aksi..."
              class="w-full pl-7 pr-3 py-1.5 border border-gray-200 rounded-lg text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
              @input="onSearchInput"
            >
            <svg
              class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5 pointer-events-none"
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
        </div>

        <!-- Modul -->
        <div>
          <label
            for="audit-module"
            class="text-[10px] uppercase font-semibold text-gray-500 block mb-1"
          >Modul</label>
          <select
            id="audit-module"
            v-model="filters.module"
            class="w-full px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white cursor-pointer"
            @change="applyFilters"
          >
            <option value="">
              Semua Modul
            </option>
            <option
              v-for="mod in store.modules"
              :key="mod.value"
              :value="mod.value"
            >
              {{ mod.label }} ({{ mod.value }})
            </option>
          </select>
        </div>

        <!-- Date Range: From -->
        <div>
          <label
            for="audit-date-from"
            class="text-[10px] uppercase font-semibold text-gray-500 block mb-1"
          >Dari Tanggal</label>
          <input
            id="audit-date-from"
            v-model="filters.date_from"
            type="date"
            class="w-full px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white cursor-pointer"
            @change="applyFilters"
          >
        </div>

        <!-- Date Range: To & Reset -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label
              for="audit-date-to"
              class="text-[10px] uppercase font-semibold text-gray-500"
            >Sampai Tanggal</label>
            <button
              v-if="isAnyFilterActive"
              type="button"
              class="text-[10px] text-indigo-600 hover:text-indigo-800 font-semibold cursor-pointer"
              @click="resetFilters"
            >
              Reset Filter
            </button>
          </div>
          <input
            id="audit-date-to"
            v-model="filters.date_to"
            type="date"
            class="w-full px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white cursor-pointer"
            @change="applyFilters"
          >
        </div>
      </div>
    </div>

    <!-- Error Alert Banner -->
    <div
      v-if="store.error"
      class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 flex items-center justify-between"
    >
      <div class="flex items-center gap-2">
        <svg
          class="w-4 h-4 text-red-500 shrink-0"
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
        class="font-semibold underline hover:no-underline cursor-pointer"
        @click="fetchData(1)"
      >
        Coba Lagi
      </button>
    </div>

    <!-- Main Table Card -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-2xs overflow-hidden">
      <div class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-600 font-semibold text-[11px] uppercase tracking-wider">
              <th
                scope="col"
                class="py-2.5 px-3 text-center w-12"
              >
                No
              </th>
              <th
                scope="col"
                class="py-2.5 px-3 min-w-[130px]"
              >
                Waktu
              </th>
              <th
                scope="col"
                class="py-2.5 px-3 min-w-[150px]"
              >
                Aktor / User
              </th>
              <th
                scope="col"
                class="py-2.5 px-3 min-w-[160px]"
              >
                Modul & Aksi
              </th>
              <th
                scope="col"
                class="py-2.5 px-3 min-w-[280px]"
              >
                Deskripsi
              </th>
              <th
                scope="col"
                class="py-2.5 px-3 min-w-[120px]"
              >
                Alamat IP
              </th>
              <th
                scope="col"
                class="py-2.5 px-3 text-center w-16"
              >
                Detail
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100">
            <!-- Loading State -->
            <tr v-if="store.loading">
              <td
                colspan="7"
                class="py-12 text-center text-gray-500"
              >
                <div class="inline-flex items-center gap-2 text-xs font-semibold">
                  <svg
                    class="w-4 h-4 animate-spin text-indigo-600"
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
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    />
                  </svg>
                  <span>Memuat log aktivitas...</span>
                </div>
              </td>
            </tr>

            <!-- Empty State -->
            <BaseTableEmpty
              v-else-if="store.logs.length === 0"
              :colspan="7"
              message="Tidak ada log aktivitas ditemukan"
              :hint="isAnyFilterActive ? 'Coba ubah kata kunci pencarian atau sesuaikan filter rentang tanggal.' : 'Belum ada rekaman audit log aktivitas dalam sistem.'"
            />

            <!-- Data Rows -->
            <tr
              v-for="(item, idx) in store.logs"
              v-else
              :key="item.id"
              class="hover:bg-gray-50/70 transition-colors"
            >
              <td class="py-2 px-3 text-center font-mono text-[11px] text-gray-400">
                {{ calculateRowNumber(idx) }}
              </td>

              <td class="py-2 px-3 whitespace-nowrap">
                <div class="font-medium text-gray-900 text-[11px]">
                  {{ formatShortDate(item.created_at) }}
                </div>
                <div class="text-[10px] text-gray-400 font-mono">
                  {{ formatTime(item.created_at) }}
                </div>
              </td>

              <td class="py-2 px-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-[10px] shrink-0">
                    {{ getUserInitial(item.user?.name || item.user?.username || 'S') }}
                  </div>
                  <div class="min-w-0">
                    <div
                      class="font-semibold text-gray-900 truncate text-[11px]"
                      :title="item.user?.name"
                    >
                      {{ item.user ? item.user.name : (item.user_id ? `User #${item.user_id}` : 'Sistem') }}
                    </div>
                    <div
                      v-if="item.user?.username"
                      class="text-[10px] text-gray-400 font-mono truncate"
                    >
                      @{{ item.user.username }}
                    </div>
                  </div>
                </div>
              </td>

              <td class="py-2 px-3 whitespace-nowrap">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span
                    class="px-1.5 py-0.5 rounded text-[10px] font-semibold border"
                    :class="getModuleBadgeClass(item.module)"
                  >
                    {{ item.module }}
                  </span>
                  <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-700 border border-gray-200">
                    {{ item.action }}
                  </span>
                </div>
              </td>

              <td class="py-2 px-3 text-gray-800 leading-snug">
                {{ item.description || '-' }}
              </td>

              <td class="py-2 px-3 font-mono text-[11px] text-gray-600 whitespace-nowrap">
                {{ item.ip_address || '-' }}
              </td>

              <td class="py-2 px-3 text-center whitespace-nowrap">
                <button
                  type="button"
                  class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors cursor-pointer"
                  title="Lihat Detail Log"
                  @click="store.setSelectedLog(item)"
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
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                  </svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Footer -->
      <div
        v-if="store.meta && store.meta.total > 0"
        class="px-3.5 py-2.5 border-t border-gray-100"
      >
        <BasePagination
          :pagination="store.meta"
          :loading="store.loading"
          @change="changePage"
        />
      </div>
    </div>

    <!-- Detail Modal -->
    <AuditLogDetailModal
      v-if="store.selectedLog"
      :log="store.selectedLog"
      @close="store.clearSelectedLog"
    />
  </div>
</template>

<script setup>
import { reactive, onMounted, computed } from 'vue';
import { useAuditLogStore } from '../stores/useAuditLogStore';
import AuditLogDetailModal from '../components/AuditLogDetailModal.vue';
import BasePagination from '@/shared/components/BasePagination.vue';
import BaseTableEmpty from '@/shared/components/BaseTableEmpty.vue';

const store = useAuditLogStore();

const filters = reactive({
    search: '',
    module: '',
    date_from: '',
    date_to: '',
    page: 1,
    per_page: 25,
});

const isAnyFilterActive = computed(() => {
    return Boolean(filters.search || filters.module || filters.date_from || filters.date_to);
});

let debounceTimer = null;
const onSearchInput = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        filters.page = 1;
        fetchData(1);
    }, 300);
};

const applyFilters = () => {
    filters.page = 1;
    fetchData(1);
};

const resetFilters = () => {
    filters.search = '';
    filters.module = '';
    filters.date_from = '';
    filters.date_to = '';
    filters.page = 1;
    fetchData(1);
};

const refreshData = () => {
    fetchData(filters.page);
};

const changePage = (newPage) => {
    fetchData(newPage);
};

const fetchData = (page = 1) => {
    filters.page = page;
    const params = {
        page: filters.page,
        per_page: filters.per_page,
    };
    if (filters.search) params.search = filters.search.trim();
    if (filters.module) params.module = filters.module;
    if (filters.date_from) params.date_from = filters.date_from;
    if (filters.date_to) params.date_to = filters.date_to;

    store.fetchLogs(params);
};

const calculateRowNumber = (idx) => {
    const from = store.meta?.from ?? 1;
    return from + idx;
};

const getUserInitial = (name) => {
    return (name || 'U').charAt(0).toUpperCase();
};

const formatShortDate = (val) => {
    if (!val) return '-';
    try {
        const d = new Date(val);
        return d.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return val;
    }
};

const formatTime = (val) => {
    if (!val) return '';
    try {
        const d = new Date(val);
        return d.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    } catch {
        return '';
    }
};

const getModuleBadgeClass = (module) => {
    switch (module) {
        case 'auth':
            return 'bg-sky-50 text-sky-700 border-sky-200';
        case 'month_end':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        case 'store_allocations':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'users':
            return 'bg-amber-50 text-amber-800 border-amber-200';
        case 'products':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        default:
            return 'bg-indigo-50 text-indigo-700 border-indigo-200';
    }
};

onMounted(() => {
    store.fetchModules();
    fetchData(1);
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
