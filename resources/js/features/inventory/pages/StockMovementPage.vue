<template>
  <div class="space-y-3">
    <!-- Top Header & Filter Toolbar (Compact) -->
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
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            />
          </svg>
          Riwayat Pergerakan Stok (Movement Ledger)
        </h1>
        <p class="text-[11px] text-gray-500 mt-0.5">
          Log histori transaksi dan mutasi persediaan barang secara komprehensif.
        </p>
      </div>

      <!-- Filters -->
      <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
        <!-- Search Input -->
        <div class="w-full sm:w-60">
          <input
            id="search"
            v-model="searchQuery"
            type="text"
            class="block w-full rounded-lg border border-gray-300 bg-white py-1.5 px-2.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            placeholder="Cari SKU atau No. Ref..."
          >
        </div>

        <!-- Movement Type Filter -->
        <select
          v-model="movementTypeFilter"
          class="block rounded-lg border border-gray-300 bg-white py-1.5 pl-2.5 pr-8 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option value="">
            Semua Jenis Mutasi
          </option>
          <option value="RECEIPT">
            Penerimaan (Semua)
          </option>
          <option value="RECEIPT_GA">
            Penerimaan GA
          </option>
          <option value="ISSUE">
            Pengeluaran
          </option>
          <option value="TRANSFER_IN">
            Transfer Masuk
          </option>
          <option value="TRANSFER_OUT">
            Transfer Keluar
          </option>
          <option value="STORE_ALLOCATION">
            Alokasi Toko
          </option>
          <option value="REPLACEMENT_PULL">
            Tarik Unit Bekas
          </option>
          <option value="RETURN_TO_WAREHOUSE">
            Retur ke Gudang
          </option>
          <option value="ADJUSTMENT_IN">
            Penyesuaian (+)
          </option>
          <option value="ADJUSTMENT_OUT">
            Penyesuaian (-)
          </option>
          <option value="OPNAME_IN">
            Opname (+)
          </option>
          <option value="OPNAME_OUT">
            Opname (-)
          </option>
          <option value="REVERSAL">
            Pembatalan
          </option>
        </select>
      </div>
    </div>

    <!-- Error Alert -->
    <div
      v-if="inventoryStore.error"
      class="rounded-lg bg-rose-50 p-3 border border-rose-200 text-xs text-rose-800 flex items-center justify-between"
    >
      <span>{{ inventoryStore.error }}</span>
      <button
        type="button"
        class="text-rose-500 hover:text-rose-700 text-xs font-semibold"
        @click="inventoryStore.error = null"
      >
        Tutup
      </button>
    </div>

    <!-- High-Density Table (Styled like RecentInventoryActivity.vue) -->
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
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Waktu
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Jenis
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              No. Dokumen / Ref
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 min-w-[200px]"
            >
              SKU & Produk
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Lokasi
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap"
            >
              Harga Satuan
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap"
            >
              Mutasi (Qty)
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 text-right whitespace-nowrap"
            >
              Saldo Akhir
            </th>
            <th
              scope="col"
              class="py-1.5 px-2 whitespace-nowrap"
            >
              Petugas
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
          <!-- Loading State -->
          <tr v-if="inventoryStore.loading && inventoryStore.movements.data.length === 0">
            <td
              colspan="11"
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
                <span>Memuat data riwayat mutasi...</span>
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-else-if="inventoryStore.movements.data.length === 0">
            <td
              colspan="11"
              class="py-8 text-center text-xs text-gray-400"
            >
              Tidak ada data pergerakan stok yang cocok dengan kriteria pencarian.
            </td>
          </tr>

          <!-- Data Rows -->
          <tr
            v-for="(item, index) in inventoryStore.movements.data"
            :key="item.id"
            class="hover:bg-gray-50/80 transition-colors"
          >
            <!-- No. -->
            <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
              {{ rowNumber(inventoryStore.movements?.meta, index) }}
            </td>

            <!-- Waktu -->
            <td class="py-1.5 px-2 text-gray-600 whitespace-nowrap text-[11px]">
              {{ formatTimestamp(item.occurred_at) }}
            </td>

            <!-- Jenis / Tipe Mutasi -->
            <td class="py-1.5 px-2 whitespace-nowrap">
              <span :class="['px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider', getBadgeClass(item.movement_type)]">
                {{ formatMovementType(item.movement_type) }}
              </span>
            </td>

            <!-- No. Referensi -->
            <td class="py-1.5 px-2 font-mono text-gray-800 whitespace-nowrap text-[11px]">
              {{ item.reference_number || '-' }}
            </td>

            <!-- SKU & Produk -->
            <td class="py-1.5 px-2">
              <div class="font-medium text-gray-900 leading-tight text-[11px]">
                {{ item.product?.name }}
              </div>
              <div class="text-[10px] text-gray-400 font-mono">
                {{ item.product?.sku }}
              </div>
            </td>

            <!-- Lokasi -->
            <td class="py-1.5 px-2 text-gray-600 whitespace-nowrap text-[11px]">
              <span class="font-semibold text-gray-700">{{ item.location?.code }}</span> — {{ item.location?.name }}
            </td>

            <!-- Harga Satuan -->
            <td class="py-1.5 px-2 text-right font-mono text-gray-600 whitespace-nowrap text-[11px]">
              {{ item.product?.unit_price ? formatRupiah(item.product.unit_price) : '-' }}
            </td>

            <!-- Jumlah Mutasi -->
            <td class="py-1.5 px-2 text-right font-mono font-bold whitespace-nowrap text-[11px]">
              <span :class="isPositiveMovement(item.movement_type) ? 'text-emerald-700' : 'text-amber-800'">
                {{ isPositiveMovement(item.movement_type) ? '+' : '-' }}{{ formatQuantity(item.quantity) }}
              </span>
            </td>

            <!-- Saldo Akhir -->
            <td class="py-1.5 px-2 text-right font-mono text-gray-600 whitespace-nowrap text-[11px]">
              {{ formatQuantity(item.quantity_after) }}
            </td>

            <!-- Petugas -->
            <td class="py-1.5 px-2 text-gray-500 whitespace-nowrap text-[11px]">
              {{ item.creator?.name || 'System' }}
            </td>

            <!-- Aksi Detail -->
            <td class="py-1.5 px-2 text-center whitespace-nowrap">
              <button
                type="button"
                class="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 hover:text-indigo-900 hover:underline cursor-pointer"
                @click="openDetail(item)"
              >
                Detail
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <BasePagination
      :pagination="inventoryStore.movements.meta"
      :loading="inventoryStore.isLoading"
      @change="changePage"
    />

    <!-- Stock Movement Detail Modal -->
    <StockMovementDetailModal
      v-model="showDetailModal"
      :movement="inventoryStore.selectedMovement"
      :loading="inventoryStore.movementDetailLoading"
    />
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from "vue";
import { useInventoryStore } from "../stores/useInventoryStore";
import BasePagination from "@/shared/components/BasePagination.vue";
import StockMovementDetailModal from "../components/StockMovementDetailModal.vue";
import {
    formatRupiah,
    formatQuantity,
    rowNumber,
    formatTimestamp,
} from "@/shared/utils/formatters.js";

const inventoryStore = useInventoryStore();

const searchQuery = ref("");
const movementTypeFilter = ref("");
const showDetailModal = ref(false);

const openDetail = async (item) => {
    showDetailModal.value = true;
    const movementId = item?.id;
    if (movementId) {
        await inventoryStore.fetchMovementById(movementId);
    }
};

let debounceTimer = null;
const debouncedSearch = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => fetchData(1), 400);
};

watch(searchQuery, debouncedSearch);
watch([movementTypeFilter], () => fetchData(1));

const fetchData = (page = 1) => {
    inventoryStore.fetchMovements({
        page,
        search: searchQuery.value,
        movement_type: movementTypeFilter.value,
    });
};

const changePage = (page) => {
    if (page >= 1 && page <= inventoryStore.movements.meta.last_page) {
        fetchData(page);
    }
};

const isPositiveMovement = (type) => {
    return ['RECEIPT', 'RECEIPT_GA', 'TRANSFER_IN', 'REPLACEMENT_PULL', 'RETURN_TO_WAREHOUSE', 'ADJUSTMENT_IN', 'OPNAME_IN'].includes(type);
};

const formatMovementType = (type) => {
    const map = {
        RECEIPT: "Penerimaan",
        RECEIPT_GA: "Penerimaan GA",
        ISSUE: "Pengeluaran",
        TRANSFER_IN: "Transfer Masuk",
        TRANSFER_OUT: "Transfer Keluar",
        STORE_ALLOCATION: "Alokasi Toko",
        REPLACEMENT_PULL: "Tarik Unit Bekas",
        RETURN_TO_WAREHOUSE: "Retur ke Gudang",
        ADJUSTMENT_IN: "Penyesuaian (+)",
        ADJUSTMENT_OUT: "Penyesuaian (-)",
        OPNAME_IN: "Opname (+)",
        OPNAME_OUT: "Opname (-)",
        REVERSAL: "Pembatalan",
    };
    return map[type] || type;
};

const getBadgeClass = (type) => {
    if (['RECEIPT', 'RECEIPT_GA', 'TRANSFER_IN', 'REPLACEMENT_PULL', 'RETURN_TO_WAREHOUSE', 'ADJUSTMENT_IN', 'OPNAME_IN'].includes(type)) {
        return 'bg-emerald-100 text-emerald-800';
    }
    if (['ISSUE', 'TRANSFER_OUT', 'STORE_ALLOCATION', 'ADJUSTMENT_OUT', 'OPNAME_OUT'].includes(type)) {
        return 'bg-amber-100 text-amber-800';
    }
    if (type === 'REVERSAL') {
        return 'bg-rose-100 text-rose-800';
    }
    return 'bg-gray-100 text-gray-800';
};

onMounted(() => {
    fetchData();
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
