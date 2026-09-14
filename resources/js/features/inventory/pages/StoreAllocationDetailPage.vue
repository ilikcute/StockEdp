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
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
            />
          </svg>
          Detail Alokasi Unit Toko
        </h1>
        <p class="text-[11px] text-gray-500 mt-0.5">
          Rincian penggantian unit terpasang dan penarikan unit rusak di toko.
        </p>
      </div>
      <div class="flex items-center gap-2">
        <router-link
          to="/inventory/store-allocations"
          class="inline-flex items-center rounded-lg bg-white border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs hover:bg-gray-50 cursor-pointer"
        >
          Kembali
        </router-link>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 cursor-pointer"
          @click="windowPrint"
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
              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
            />
          </svg>
          <span>Cetak Dokumen</span>
        </button>
      </div>
    </div>

    <div
      v-if="store.loading && !doc"
      class="py-12 text-center text-xs text-gray-500"
    >
      Memuat detail alokasi...
    </div>

    <div
      v-else-if="store.error"
      class="rounded-lg bg-rose-50 border border-rose-200 p-2.5 text-xs text-rose-800 flex items-center justify-between shadow-2xs"
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

    <div
      v-else-if="doc"
      class="space-y-3"
    >
      <!-- Header Info Card (Compact) -->
      <div class="bg-white shadow-2xs rounded-xl border border-gray-200 overflow-hidden">
        <div class="bg-gray-50/70 px-3.5 py-2 border-b border-gray-200 flex justify-between items-center text-xs">
          <div class="font-semibold text-gray-900 flex items-center gap-2">
            <span>Dokumen Alokasi Toko</span>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
              SELESAI (COMPLETED)
            </span>
          </div>
          <div class="font-mono text-[10px] text-gray-500">
            Dibuat: {{ doc.created_at }}
          </div>
        </div>
        <div class="p-3.5 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
          <div>
            <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-400">
              Nomor Alokasi
            </dt>
            <dd class="mt-0.5 font-mono font-bold text-gray-900 text-sm">
              {{ doc.allocation_number }}
            </dd>
          </div>
          <div>
            <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-400">
              Tanggal Alokasi
            </dt>
            <dd class="mt-0.5 text-gray-900 font-medium text-xs">
              {{ doc.allocated_at }}
            </dd>
          </div>
          <div>
            <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-400">
              Toko Tujuan
            </dt>
            <dd class="mt-0.5 text-gray-900 font-medium text-xs truncate">
              {{ doc.store_name }}
              <span
                v-if="doc.store_code"
                class="text-[10px] text-gray-400 font-mono"
              >({{ doc.store_code }})</span>
            </dd>
          </div>
          <div>
            <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-400">
              Teknisi Pelaksana
            </dt>
            <dd class="mt-0.5 text-gray-900 font-medium text-xs truncate">
              {{ doc.technician_name }}
              <span class="text-[10px] text-gray-400">({{ doc.technician_location_name }})</span>
            </dd>
          </div>
          <div
            v-if="doc.notes"
            class="col-span-2 sm:col-span-4 border-t border-gray-100 pt-2"
          >
            <dt class="text-[10px] font-bold uppercase tracking-wider text-gray-400">
              Catatan
            </dt>
            <dd class="mt-0.5 text-gray-700 text-xs">
              {{ doc.notes }}
            </dd>
          </div>
        </div>
      </div>

      <!-- Items Table -->
      <div class="bg-white shadow-2xs rounded-xl border border-gray-200 p-3.5 space-y-2.5">
        <h2 class="text-xs font-bold uppercase tracking-wider text-gray-700">
          Rincian Unit Dipasang & Ditarik
        </h2>

        <div class="overflow-x-auto shadow-2xs border border-gray-200 rounded-xl bg-white custom-scrollbar">
          <table class="w-full text-left text-xs border-collapse">
            <thead class="sticky top-0 bg-gray-50/95 backdrop-blur-xs z-10">
              <tr class="text-gray-600 font-semibold border-b border-gray-200 text-[11px]">
                <th class="py-1.5 px-1.5 w-8 text-center whitespace-nowrap">
                  No.
                </th>
                <th class="py-1.5 px-2 whitespace-nowrap">
                  Unit Baru Dipasang (Kondisi: BAGUS)
                </th>
                <th class="py-1.5 px-2 text-right whitespace-nowrap">
                  Harga Satuan
                </th>
                <th class="py-1.5 px-2 text-right w-16 whitespace-nowrap">
                  Qty Pasang
                </th>
                <th class="py-1.5 px-2 text-right whitespace-nowrap">
                  Total Nilai (Rp)
                </th>
                <th class="py-1.5 px-2 w-32 whitespace-nowrap">
                  S/N Unit Baru
                </th>
                <th class="py-1.5 px-2 whitespace-nowrap">
                  Unit Lama Ditarik (Kondisi: RUSAK)
                </th>
                <th class="py-1.5 px-2 text-right w-16 whitespace-nowrap">
                  Qty Tarik
                </th>
                <th class="py-1.5 px-2 w-32 whitespace-nowrap">
                  S/N Unit Rusak
                </th>
                <th class="py-1.5 px-2 whitespace-nowrap">
                  Alasan Kerusakan
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <tr
                v-for="(item, index) in (doc.items || [])"
                :key="item.id || index"
                class="hover:bg-gray-50/80 transition-colors"
              >
                <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
                  {{ index + 1 }}
                </td>
                <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
                  <div class="font-medium text-gray-900">
                    {{ item.product_name }}
                  </div>
                  <div class="text-[10px] font-mono text-gray-400">
                    SKU: {{ item.product_sku }}
                  </div>
                </td>
                <td class="py-1.5 px-2 text-right font-mono text-gray-700 text-[11px] whitespace-nowrap">
                  {{ formatRupiah(item.unit_price) }}
                </td>
                <td class="py-1.5 px-2 text-right font-mono font-bold text-emerald-700 text-[11px] whitespace-nowrap">
                  {{ formatQuantity(item.quantity) }}
                </td>
                <td class="py-1.5 px-2 text-right font-mono font-bold text-indigo-700 text-[11px] whitespace-nowrap">
                  {{ formatRupiah(item.total_value) }}
                </td>
                <td class="py-1.5 px-2 font-mono text-gray-700 text-[11px] whitespace-nowrap">
                  {{ item.serial_number || '-' }}
                </td>
                <td class="py-1.5 px-2 text-[11px] whitespace-nowrap">
                  <div
                    v-if="item.pulled_product_id"
                    class="font-medium text-gray-900"
                  >
                    {{ item.pulled_product_name }}
                    <div class="text-[10px] font-mono text-gray-400">
                      SKU: {{ item.pulled_product_sku }}
                    </div>
                  </div>
                  <span
                    v-else
                    class="text-gray-400 italic text-[11px]"
                  >Tidak ada penarikan</span>
                </td>
                <td class="py-1.5 px-2 text-right font-mono font-bold text-amber-700 text-[11px] whitespace-nowrap">
                  {{ item.pulled_quantity ? formatQuantity(item.pulled_quantity) : '-' }}
                </td>
                <td class="py-1.5 px-2 font-mono text-gray-700 text-[11px] whitespace-nowrap">
                  {{ item.pulled_serial_number || '-' }}
                </td>
                <td class="py-1.5 px-2 text-gray-600 text-[11px]">
                  {{ item.defective_reason || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useStoreAllocationStore } from '../stores/useStoreAllocationStore';
import { formatQuantity, formatRupiah } from '@/shared/utils/formatters';

const route = useRoute();
const store = useStoreAllocationStore();

const doc = computed(() => store.currentAllocation);

const windowPrint = () => {
    window.print();
};

onMounted(() => {
    store.fetchAllocationById(route.params.id);
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
