<template>
  <div class="space-y-3">
    <!-- Top Header Toolbar (Compact) -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
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
              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
            />
          </svg>
          Daftar Penerimaan Stok (Goods Receipts)
        </h1>
        <p class="text-[11px] text-gray-500 mt-0.5">
          Pengelolaan arsip dokumen pencatatan barang masuk dari pengadaan atau retur servis.
        </p>
      </div>

      <div class="flex items-center gap-2">
        <router-link
          v-if="hasPermission('stock_receipts.create')"
          to="/inventory/receipts/create"
          class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 transition-colors cursor-pointer"
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
              d="M12 4v16m8-8H4"
            />
          </svg>
          <span>Buat Draft Baru</span>
        </router-link>
      </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="flex flex-col sm:flex-row justify-between gap-2.5">
      <div class="w-full sm:w-64">
        <BaseSearchInput
          id="search"
          v-model="searchQuery"
          placeholder="Cari Nomor Referensi / SPB..."
          size="sm"
          @search="onSearch"
        />
      </div>
      <div class="flex gap-2 flex-wrap sm:flex-nowrap">
        <select
          v-model="statusFilter"
          class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option value="">
            Semua Status
          </option>
          <option value="DRAFT">
            Draft
          </option>
          <option value="POSTED">
            Posted
          </option>
          <option value="CANCELED">
            Canceled
          </option>
        </select>
      </div>
    </div>

    <BaseAlert
      v-if="store.error"
      :message="store.error"
    />

    <!-- High-Density Table Container -->
    <div class="overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar">
      <table class="w-full text-left text-xs border-collapse">
        <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10">
          <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
            <th
              scope="col"
              class="py-1.5 px-1.5 w-8 text-center"
            >
              No.
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap min-w-[160px]"
            >
              Nomor Dokumen
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Tanggal
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 min-w-[180px]"
            >
              Sumber / Supplier
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap w-24"
            >
              Status
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-center whitespace-nowrap w-16"
            >
              Aksi
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr v-if="store.loading && (!store.receipts?.data || store.receipts.data.length === 0)">
            <td
              colspan="6"
              class="py-8 text-center text-xs text-gray-500"
            >
              <div class="flex items-center justify-center gap-2">
                <svg
                  class="animate-spin h-4 w-4 text-indigo-600"
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
                <span>Memuat data penerimaan...</span>
              </div>
            </td>
          </tr>
          <tr v-else-if="!store.receipts?.data || store.receipts.data.length === 0">
            <td
              colspan="6"
              class="py-8 text-center text-xs text-gray-400"
            >
              Tidak ada data penerimaan yang cocok.
            </td>
          </tr>
          <tr
            v-for="(item, index) in (store.receipts?.data || [])"
            :key="item.id"
            class="hover:bg-gray-50/80 transition-colors"
          >
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(store.receipts?.meta, index) }}
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap font-mono">
              <div class="font-bold text-gray-900 text-[11px]">
                {{ item.receipt_number }}
              </div>
              <div
                v-if="item.memo_number"
                class="text-[10px] text-indigo-600 font-sans"
              >
                SPB: {{ item.memo_number }}
              </div>
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap text-gray-600 text-[11px]">
              {{ item.date }}
            </td>
            <td class="py-1.5 px-2 text-gray-800 text-[11px]">
              {{ item.supplier?.name || (item.source_type === 'GA_SERVICED' ? 'Hasil Servis GA' : 'Internal GA') }}
            </td>
            <td class="py-1.5 px-2 whitespace-nowrap">
              <DocumentStatusBadge :status="item.status" />
            </td>
            <td class="py-1.5 px-2 text-center whitespace-nowrap">
              <router-link
                v-if="hasPermission('stock_receipts.view')"
                :to="`/inventory/receipts/${item.id}`"
                class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-900 hover:underline cursor-pointer"
              >
                Detail
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <BasePagination
      :pagination="store.receipts?.meta"
      :loading="store.loading"
      @change="changePage"
    />
  </div>
</template>

<script setup>
import { useStockReceiptStore } from '../stores/useStockReceiptStore';
import { useDocumentList } from '../composables/use_document_list';
import { rowNumber } from '@/shared/utils/formatters';
import BasePagination from '@/shared/components/BasePagination.vue';
import BaseSearchInput from '@/shared/components/BaseSearchInput.vue';
import BaseAlert from '@/shared/components/BaseAlert.vue';
import DocumentStatusBadge from '../components/DocumentStatusBadge.vue';

const store = useStockReceiptStore();

const { searchQuery, statusFilter, onSearch, changePage, hasPermission } = useDocumentList({
    store,
    fetch: (params) => store.fetchList(params),
    collection: 'receipts',
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