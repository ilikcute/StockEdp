<template>
  <div
    v-if="hasAnyAction"
    class="bg-white rounded-xl border border-gray-200 px-3 py-2 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-2.5 transition-all"
  >
    <!-- Left: Quick Action Transaction Shortcuts -->
    <div class="flex flex-wrap items-center gap-1.5">
      <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider mr-1 hidden sm:inline-flex items-center gap-1 select-none">
        <svg
          class="w-3.5 h-3.5 text-blue-600"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M13 10V3L4 14h7v7l9-11h-7z"
          />
        </svg>
        Aksi Cepat:
      </span>

      <!-- Penerimaan Baru -->
      <router-link
        v-if="canCreateReceipt"
        to="/inventory/receipts/create"
        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-colors shadow-2xs"
        title="Buat Dokumen Penerimaan Barang Baru"
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
            d="M19 14l-7 7m0 0l-7-7m7 7V3"
          />
        </svg>
        <span>+ Penerimaan</span>
      </router-link>

      <!-- Pengeluaran Baru -->
      <router-link
        v-if="canCreateIssue"
        to="/inventory/issues/create"
        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition-colors shadow-2xs"
        title="Buat Dokumen Pengeluaran Barang Baru"
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
            d="M5 10l7-7m0 0l7 7m-7-7v18"
          />
        </svg>
        <span>+ Pengeluaran</span>
      </router-link>

      <!-- Transfer Baru -->
      <router-link
        v-if="canCreateTransfer"
        to="/inventory/transfers/create"
        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 transition-colors shadow-2xs"
        title="Buat Dokumen Transfer Antar Lokasi"
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
            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
          />
        </svg>
        <span>+ Transfer</span>
      </router-link>

      <!-- Opname Baru -->
      <router-link
        v-if="canCreateOpname"
        to="/inventory/opnames/create"
        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 transition-colors shadow-2xs"
        title="Buat Sesi Perhitungan Stock Opname Baru"
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
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
          />
        </svg>
        <span>+ Opname</span>
      </router-link>

      <!-- Alokasi Toko Baru -->
      <router-link
        v-if="canCreateAllocation"
        to="/inventory/store-allocations/create"
        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-lg bg-teal-50 text-teal-700 hover:bg-teal-100 border border-teal-200 transition-colors shadow-2xs"
        title="Buat Alokasi Toko & Teknisi"
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
            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
          />
        </svg>
        <span>+ Alokasi</span>
      </router-link>
    </div>

    <!-- Right: Replenishment Action Center -->
    <div
      v-if="canViewReplenishment"
      class="flex items-center justify-between sm:justify-end gap-2 shrink-0 pt-1.5 md:pt-0 border-t md:border-t-0 border-gray-100"
    >
      <div class="flex items-center gap-1.5">
        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800">
          Live Engine
        </span>
        <span class="text-xs font-semibold text-gray-700 hidden lg:inline">
          Rekomendasi Replenishment
        </span>
      </div>

      <router-link
        :to="{
          path: '/inventory/replenishment',
          query: locationId ? { location_id: locationId } : {}
        }"
        class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-500 shadow-2xs transition-colors cursor-pointer"
      >
        <span>Action Center</span>
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
            d="M14 5l7 7m0 0l-7 7m7-7H3"
          />
        </svg>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAuthStore } from '@features/auth/stores/use_auth_store.js';

defineProps({
  locationId: {
    type: [Number, String],
    default: null,
  },
});

const authStore = useAuthStore();

const canCreateReceipt = computed(() => authStore.hasPermission('stock_receipts.create'));
const canCreateIssue = computed(() => authStore.hasPermission('stock_issues.create'));
const canCreateTransfer = computed(() => authStore.hasPermission('stock_transfers.create'));
const canCreateOpname = computed(() => authStore.hasPermission('stock_opnames.create'));
const canCreateAllocation = computed(() => authStore.hasPermission('store_allocations.create_own|store_allocations.create_for_others'));
const canViewReplenishment = computed(() => authStore.hasPermission('replenishment.view'));

const hasAnyAction = computed(() => {
  return canCreateReceipt.value
    || canCreateIssue.value
    || canCreateTransfer.value
    || canCreateOpname.value
    || canCreateAllocation.value
    || canViewReplenishment.value;
});
</script>
