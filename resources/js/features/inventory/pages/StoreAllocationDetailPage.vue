<template>
  <div class="px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="sm:flex sm:items-center justify-between">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Detail Alokasi Unit Toko
        </h1>
        <p class="mt-1 text-sm text-gray-600">
          Rincian penggantian unit terpasang dan penarikan unit rusak di toko.
        </p>
      </div>
      <div class="mt-4 sm:mt-0 flex gap-2">
        <router-link
          to="/inventory/store-allocations"
          class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 cursor-pointer"
        >
          Kembali
        </router-link>
        <button
          type="button"
          class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 cursor-pointer"
          @click="windowPrint"
        >
          Cetak Dokumen
        </button>
      </div>
    </div>

    <div
      v-if="store.loading && !doc"
      class="py-12 text-center text-gray-500"
    >
      Memuat detail alokasi...
    </div>

    <div
      v-else-if="store.error"
      class="rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm text-rose-800"
    >
      {{ store.error }}
    </div>

    <div
      v-else-if="doc"
      class="space-y-6"
    >
      <!-- Header Info Card -->
      <div class="overflow-hidden bg-white shadow-xs rounded-xl border border-gray-200">
        <div class="bg-gray-50/70 px-4 py-4 sm:px-6 border-b border-gray-200 flex justify-between items-center">
          <div class="font-semibold text-gray-900 flex items-center gap-2">
            <span>Dokumen Alokasi Toko</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
              SELESAI (COMPLETED)
            </span>
          </div>
          <div class="font-mono text-xs text-gray-500">
            Dibuat: {{ doc.created_at }}
          </div>
        </div>
        <div class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-sm">
          <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">
              Nomor Alokasi
            </dt>
            <dd class="mt-1 font-mono font-bold text-gray-900 text-base">
              {{ doc.allocation_number }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">
              Tanggal Alokasi
            </dt>
            <dd class="mt-1 text-gray-900 font-medium">
              {{ doc.allocated_at }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">
              Toko Tujuan
            </dt>
            <dd class="mt-1 text-gray-900 font-medium">
              {{ doc.store_name }}
              <span
                v-if="doc.store_code"
                class="text-xs text-gray-500 font-mono"
              >({{ doc.store_code }})</span>
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">
              Teknisi Pelaksana
            </dt>
            <dd class="mt-1 text-gray-900 font-medium">
              {{ doc.technician_name }}
              <div class="text-xs text-gray-500">
                Lokasi: {{ doc.technician_location_name }}
              </div>
            </dd>
          </div>
          <div class="sm:col-span-2 lg:col-span-4">
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">
              Catatan
            </dt>
            <dd class="mt-1 text-gray-700">
              {{ doc.notes || '-' }}
            </dd>
          </div>
        </div>
      </div>

      <!-- Items Table -->
      <div class="bg-white shadow-xs rounded-xl border border-gray-200 p-4 sm:p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-900">
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
                <th class="py-1.5 px-2 text-right w-16 whitespace-nowrap">
                  Qty Pasang
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
                <td class="py-1.5 px-2 text-right font-mono font-bold text-emerald-700 text-[11px] whitespace-nowrap">
                  {{ item.quantity }}
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
                  {{ item.pulled_quantity || '-' }}
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
