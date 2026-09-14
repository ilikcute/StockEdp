<template>
  <div class="space-y-3">
    <!-- TOP Header & Action Strip Compact -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3">
        <router-link
          to="/inventory/transfers"
          class="inline-flex items-center gap-1 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-gray-50 hover:bg-gray-100 px-2.5 py-1.5 rounded-lg border border-gray-200 transition-colors"
        >
          &larr; Kembali
        </router-link>

        <div class="h-4 w-px bg-gray-200" />

        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-base font-bold text-gray-900 tracking-tight font-mono">
              {{ transfer?.transfer_number || 'Detail Transfer Stok' }}
            </h1>
            <span
              v-if="transfer"
              class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full"
              :class="{
                'bg-yellow-100 text-yellow-800': transfer.status === 'DRAFT',
                'bg-blue-100 text-blue-800': transfer.status === 'IN_TRANSIT',
                'bg-green-100 text-green-800': transfer.status === 'RECEIVED',
                'bg-orange-100 text-orange-800': transfer.status === 'DISCREPANCY',
                'bg-gray-100 text-gray-800': transfer.status === 'CANCELED'
              }"
            >
              {{ ({ DRAFT: 'Draft', 'IN_TRANSIT': 'Dikirim (In-Transit)', RECEIVED: 'Diterima', DISCREPANCY: 'Selisih (Discrepancy)', CANCELED: 'Dibatalkan' })[transfer.status] || transfer.status }}
            </span>
          </div>
          <p class="text-[11px] text-gray-500">
            Rincian dokumen perpindahan barang antar lokasi.
          </p>
        </div>
      </div>

      <div
        v-if="transfer"
        class="flex items-center gap-2 flex-wrap"
      >
        <BasePrintButton
          label="Cetak Surat Jalan"
          @click="handlePrint"
        />
        <router-link
          v-if="transfer.status === 'DRAFT' && hasPermission('stock_transfers.update')"
          :to="`/inventory/transfers/${transfer.id}/edit`"
          class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs hover:bg-gray-50"
        >
          Edit Draft
        </router-link>

        <button
          v-if="transfer.status === 'DRAFT' && hasPermission('stock_transfers.cancel')"
          :disabled="store.loadingAction"
          class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 shadow-2xs hover:bg-rose-100 disabled:opacity-50 cursor-pointer"
          @click="openConfirmModal('cancel')"
        >
          Batalkan Draft
        </button>

        <button
          v-if="transfer.status === 'DRAFT' && hasPermission('stock_transfers.send')"
          :disabled="store.loadingAction"
          class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-blue-700 disabled:opacity-50 cursor-pointer"
          @click="openConfirmModal('send')"
        >
          Kirim Barang (Send)
        </button>

        <button
          v-if="transfer.status === 'IN_TRANSIT' && hasPermission('stock_transfers.receive')"
          :disabled="store.loadingAction"
          class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-emerald-700 disabled:opacity-50 cursor-pointer"
          @click="openConfirmModal('receive')"
        >
          Terima Barang (Receive)
        </button>
      </div>
    </div>

    <div
      v-if="store.loadingDetail && !transfer"
      class="p-8 text-center text-xs text-gray-500"
    >
      Memuat data transfer...
    </div>

    <div
      v-else-if="transfer"
      class="space-y-3"
    >
      <div
        v-if="store.error"
        class="rounded-xl border border-rose-200 bg-rose-50 p-3"
      >
        <p class="text-xs font-medium text-rose-800">
          {{ store.error }}
        </p>
      </div>

      <!-- Compact Metadata Card -->
      <div class="bg-white rounded-xl border border-gray-200 p-3 shadow-2xs">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Jenis Transfer</span>
            <span class="font-semibold text-gray-800">{{ transfer.transfer_type === 'RETURN' ? 'Retur ke Gudang' : 'Transfer Stok' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Lokasi Asal</span>
            <span class="font-semibold text-gray-800">{{ transfer.origin_location_name || '-' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Lokasi Tujuan</span>
            <span class="font-semibold text-gray-800">{{ transfer.destination_location_name || '-' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Tanggal Transfer</span>
            <span class="font-semibold text-gray-800">{{ transfer.transfer_date }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Dibuat Oleh</span>
            <span class="font-semibold text-gray-800">{{ transfer.created_by || '-' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Catatan</span>
            <span class="text-gray-700 truncate block">{{ transfer.notes || '-' }}</span>
          </div>
        </div>
      </div>

      <!-- Items Section -->
      <div class="bg-white shadow-2xs border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-4 py-2.5 sm:px-4 border-b border-gray-100">
          <h3 class="text-xs font-semibold leading-5 text-gray-900">
            Daftar Barang Ditransfer
          </h3>
        </div>
        <div class="overflow-x-auto custom-scrollbar">
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
                  Produk
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 whitespace-nowrap"
                >
                  SKU
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 whitespace-nowrap"
                >
                  Satuan (Unit)
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 text-right whitespace-nowrap"
                >
                  Harga Satuan
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 text-center whitespace-nowrap"
                >
                  Kondisi
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 text-right whitespace-nowrap"
                >
                  Jumlah (Quantity)
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 text-right whitespace-nowrap"
                >
                  Diterima
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 text-right whitespace-nowrap"
                >
                  Nilai Transfer
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <tr
                v-for="(item, index) in transfer.items"
                :key="item.id"
                class="hover:bg-gray-50/80 transition-colors"
              >
                <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
                  {{ index + 1 }}
                </td>
                <td class="py-1.5 px-2 text-[11px] font-medium text-gray-900 whitespace-nowrap">
                  {{ item.product?.name || item.product_name || '-' }}
                </td>
                <td class="py-1.5 px-2 text-[10px] text-gray-400 font-mono whitespace-nowrap">
                  {{ item.product?.sku || item.product_sku || '-' }}
                </td>
                <td class="py-1.5 px-2 text-[11px] text-gray-500 whitespace-nowrap">
                  {{ item.product?.unit?.symbol || item.product?.unit?.name || '-' }}
                </td>
                <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-700 whitespace-nowrap">
                  {{ formatRupiah(item.unit_price ?? item.product?.unit_price ?? item.product_unit_price ?? 0) }}
                </td>
                <td class="py-1.5 px-2 text-center whitespace-nowrap">
                  <span
                    class="px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded inline-flex items-center"
                    :class="(item.condition || 'GOOD') === 'DEFECTIVE'
                      ? 'bg-rose-50 text-rose-700 border border-rose-200'
                      : 'bg-emerald-50 text-emerald-700 border border-emerald-200'"
                  >
                    {{ (item.condition || 'GOOD') === 'DEFECTIVE' ? 'RUSAK' : 'BAGUS' }}
                  </span>
                </td>
                <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-900 whitespace-nowrap">
                  {{ formatQuantity(item.quantity, false) }}
                </td>
                <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-900 whitespace-nowrap">
                  {{ item.received_quantity != null ? formatQuantity(item.received_quantity, false) : '-' }}
                </td>
                <td class="py-1.5 px-2 text-[11px] font-mono text-right font-medium text-gray-900 whitespace-nowrap">
                  {{ formatRupiah(item.subtotal ?? (Number(item.quantity || 0) * Number(item.unit_price ?? item.product?.unit_price ?? item.product_unit_price ?? 0))) }}
                </td>
              </tr>
            </tbody>
            <tfoot
              v-if="transfer?.items?.length"
              class="border-t border-gray-200 bg-gray-50/90 text-xs font-medium"
            >
              <tr class="text-[11px]">
                <td
                  colspan="8"
                  class="py-1.5 px-2 text-right text-gray-700 font-semibold"
                >
                  Grand Total Nilai Transfer:
                </td>
                <td class="py-1.5 px-2 text-right font-mono font-bold text-indigo-700 whitespace-nowrap">
                  {{ formatRupiah(grandTotal) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Dialog Confirmation -->
    <div
      v-if="confirmActionType"
      class="fixed inset-0 z-50 overflow-y-auto flex min-h-full items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <div
        class="fixed inset-0"
        aria-hidden="true"
        @click="closeConfirmModal"
      />
      
      <div class="relative w-full max-w-lg transform overflow-hidden rounded-xl bg-white text-left shadow-2xl border border-gray-100 transition-all z-10 p-6">
        <div class="sm:flex sm:items-start">
          <div
            class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10"
            :class="{
              'bg-blue-100': confirmActionType === 'send',
              'bg-green-100': confirmActionType === 'receive',
              'bg-red-100': confirmActionType === 'cancel'
            }"
          >
            <span
              class="text-lg font-bold"
              :class="{
                'text-blue-600': confirmActionType === 'send',
                'text-green-600': confirmActionType === 'receive',
                'text-red-600': confirmActionType === 'cancel'
              }"
            >!</span>
          </div>
          <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
            <h3
              id="modal-title"
              class="text-base font-semibold leading-6 text-gray-900"
            >
              Konfirmasi {{ confirmTitle }}
            </h3>
            <div class="mt-2">
              <p class="text-sm text-gray-600">
                {{ confirmDescription }}
              </p>
            </div>

            <div
              v-if="confirmActionType === 'receive' && transfer?.items?.length"
              class="mt-4 divide-y divide-gray-200 border border-gray-200 rounded-lg"
            >
              <div class="px-4 py-3 bg-gray-50 text-left">
                <p class="text-sm font-semibold text-gray-700">
                  Cocokkan / Hitung Jumlah Diterima
                </p>
                <p class="text-xs text-gray-500">
                  Isi jumlah yang benar-benar diterima. Selisih dengan jumlah dikirim akan menandai transaksi sebagai Discrepancy.
                </p>
              </div>
              <div
                v-for="item in transfer.items"
                :key="item.id"
                class="px-4 py-3 flex items-center justify-between gap-4"
              >
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium text-gray-900">
                    {{ item.product?.name || item.product_name || '-' }}
                  </p>
                  <p class="text-xs text-gray-500">
                    SKU: {{ item.product?.sku || item.product_sku || '-' }} &middot; Dikirim: {{ formatQuantity(item.quantity) }}
                  </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <label
                    :for="`received-${item.id}`"
                    class="text-xs text-gray-500"
                  >Diterima</label>
                  <input
                    :id="`received-${item.id}`"
                    v-model.number="receivedQuantities[item.id]"
                    type="number"
                    min="0"
                    step="0.0001"
                    class="block w-28 rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
          <button
            type="button"
            class="inline-flex w-full justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:w-auto cursor-pointer"
            @click="closeConfirmModal"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="store.loadingAction"
            :class="[
              confirmActionType === 'send' ? 'bg-blue-600 hover:bg-blue-500 focus-visible:outline-blue-600' : '',
              confirmActionType === 'receive' ? 'bg-green-600 hover:bg-green-500 focus-visible:outline-green-600' : '',
              confirmActionType === 'cancel' ? 'bg-red-600 hover:bg-red-500 focus-visible:outline-red-600' : '',
              'inline-flex w-full justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-xs disabled:opacity-50 sm:w-auto cursor-pointer'
            ]"
            @click="executeAction"
          >
            <span
              v-if="store.loadingAction"
              class="inline-block animate-spin mr-2"
            >⟳</span>
            {{ confirmButtonText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useStockTransferStore } from '../stores/useStockTransferStore';
import { useAuthStore } from '@features/auth/stores/use_auth_store';
import { formatQuantity, formatRupiah } from '@/shared/utils/formatters';
import { printStockTransfer } from '@/shared/utils/printDocument';
import BasePrintButton from '@/shared/components/BasePrintButton.vue';

const route = useRoute();
const store = useStockTransferStore();
const authStore = useAuthStore();

const transfer = computed(() => store.currentTransfer);

const handlePrint = () => {
  if (!transfer.value) return;
  printStockTransfer(transfer.value, {
    printedBy: authStore.user?.name || 'Sistem StockEdp',
  });
};

const grandTotal = computed(() => {
  if (!transfer.value?.items) return 0;
  return transfer.value.items.reduce((sum, item) => {
    const qty = Number(item.quantity) || 0;
    const price = Number(item.unit_price ?? item.product?.unit_price ?? item.product_unit_price ?? 0);
    return sum + (qty * price);
  }, 0);
});

const confirmActionType = ref(null);
const receivedQuantities = ref({});

const resetReceivedQuantities = () => {
  receivedQuantities.value = {};
  if (transfer.value?.items) {
    transfer.value.items.forEach((item) => {
      receivedQuantities.value[item.id] = Number(item.quantity) || 0;
    });
  }
};

const confirmTitle = computed(() => {
  if (confirmActionType.value === 'send') return 'Pengiriman Stok';
  if (confirmActionType.value === 'receive') return 'Penerimaan Stok';
  if (confirmActionType.value === 'cancel') return 'Pembatalan Draft';
  return '';
});

const confirmDescription = computed(() => {
  if (confirmActionType.value === 'send') {
    return `Apakah Anda yakin ingin mengirim dokumen transfer #${transfer.value?.transfer_number}? Stok di lokasi asal (${transfer.value?.origin_location_name}) akan berkurang, dan barang akan berstatus In-Transit.`;
  }
  if (confirmActionType.value === 'receive') {
    return `Apakah Anda yakin ingin menerima dokumen transfer #${transfer.value?.transfer_number}? Stok di lokasi tujuan (${transfer.value?.destination_location_name}) akan bertambah.`;
  }
  if (confirmActionType.value === 'cancel') {
    return `Apakah Anda yakin ingin membatalkan draft transfer #${transfer.value?.transfer_number}? Pembatalan draft tidak memengaruhi saldo stok.`;
  }
  return '';
});

const confirmButtonText = computed(() => {
  if (confirmActionType.value === 'send') return 'Ya, Kirim Barang';
  if (confirmActionType.value === 'receive') return 'Ya, Terima Barang';
  if (confirmActionType.value === 'cancel') return 'Ya, Batalkan Draft';
  return 'Proses';
});

const openConfirmModal = (type) => {
  confirmActionType.value = type;
  if (type === 'receive') {
    resetReceivedQuantities();
  }
};

const closeConfirmModal = () => {
  confirmActionType.value = null;
};

const executeAction = async () => {
  if (!confirmActionType.value || !transfer.value) return;

  const id = transfer.value.id;
  const actionType = confirmActionType.value;
  closeConfirmModal();

  try {
    if (actionType === 'send') {
      await store.sendTransfer(id);
    } else if (actionType === 'receive') {
      const items = Object.entries(receivedQuantities.value).map(([itemId, qty]) => ({
        item_id: Number(itemId),
        received_quantity: Number(qty),
      }));
      await store.receiveTransfer(id, items);
    } else if (actionType === 'cancel') {
      await store.cancelTransfer(id);
    }
  } catch {
    // Handled by Pinia store
  }
};

const hasPermission = (permission) => {
  return authStore.hasPermission(permission);
};

onMounted(() => {
  store.fetchTransferById(route.params.id);
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
