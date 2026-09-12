<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Detail Transfer Stok
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Rincian dokumen perpindahan barang antar lokasi.
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex gap-2">
        <router-link
          to="/inventory/transfers"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
        >
          Kembali
        </router-link>
        
        <router-link
          v-if="transfer?.status === 'DRAFT' && hasPermission('stock_transfers.update')"
          :to="`/inventory/transfers/${transfer.id}/edit`"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
        >
          Edit Draft
        </router-link>

        <button
          v-if="transfer?.status === 'DRAFT' && hasPermission('stock_transfers.cancel')"
          :disabled="store.loadingAction"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-red-600 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50 disabled:opacity-50"
          @click="openConfirmModal('cancel')"
        >
          Batalkan Draft
        </button>

        <button
          v-if="transfer?.status === 'DRAFT' && hasPermission('stock_transfers.send')"
          :disabled="store.loadingAction"
          class="block rounded-md bg-blue-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
          @click="openConfirmModal('send')"
        >
          Kirim Barang (Send)
        </button>

        <button
          v-if="transfer?.status === 'IN_TRANSIT' && hasPermission('stock_transfers.receive')"
          :disabled="store.loadingAction"
          class="block rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
          @click="openConfirmModal('receive')"
        >
          Terima Barang (Receive)
        </button>
      </div>
    </div>

    <div
      v-if="store.loadingDetail && !transfer"
      class="mt-8 text-center text-gray-500"
    >
      Memuat data transfer...
    </div>

    <div
      v-else-if="transfer"
      class="mt-8"
    >
      <div
        v-if="store.error"
        class="mb-4 rounded-md bg-red-50 p-4"
      >
        <p class="text-sm font-medium text-red-800">
          {{ store.error }}
        </p>
      </div>

      <div class="overflow-hidden bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-base font-semibold leading-6 text-gray-900">
            Informasi Dokumen Transfer
          </h3>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
          <dl class="sm:divide-y sm:divide-gray-200">
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Nomor Transfer
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-medium">
                {{ transfer.transfer_number }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Status
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                <span 
                  class="px-2 py-1 text-xs font-semibold rounded-full"
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
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Jenis Transfer
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ transfer.transfer_type === 'RETURN' ? 'Retur ke Gudang' : 'Transfer Stok' }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Lokasi Asal (Origin)
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ transfer.origin_location_name || '-' }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Lokasi Tujuan (Destination)
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ transfer.destination_location_name || '-' }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Tanggal Transfer
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ transfer.transfer_date }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Catatan
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 whitespace-pre-line">
                {{ transfer.notes || '-' }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Dibuat Oleh / Pada
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ transfer.created_by || '-' }} ({{ transfer.created_at }})
              </dd>
            </div>
            <div
              v-if="transfer.sent_at"
              class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
            >
              <dt class="text-sm font-medium text-gray-500">
                Waktu Pengiriman (Dikirim)
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ transfer.sent_at }}
              </dd>
            </div>
            <div
              v-if="transfer.received_at"
              class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
            >
              <dt class="text-sm font-medium text-gray-500">
                Waktu Penerimaan (Received)
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ transfer.received_at }}
              </dd>
            </div>
            <div
              v-if="transfer.canceled_at"
              class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
            >
              <dt class="text-sm font-medium text-gray-500">
                Waktu Pembatalan (Canceled)
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ transfer.canceled_at }}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Items Section -->
      <div class="mt-6 bg-white shadow-sm border border-gray-300 sm:rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-base font-semibold leading-6 text-gray-900">
            Daftar Barang Ditransfer
          </h3>
        </div>
        <div class="border-t border-gray-300 overflow-x-auto touch-scroll">
          <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
              <tr>
                <th
                  scope="col"
                  class="py-3.5 pl-4 pr-3 text-center text-sm font-semibold text-gray-900 sm:pl-6 border-b border-gray-300 w-16"
                >
                  No.
                </th>
                <th
                  scope="col"
                  class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
                >
                  Produk
                </th>
                <th
                  scope="col"
                  class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
                >
                  SKU
                </th>
                <th
                  scope="col"
                  class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
                >
                  Satuan (Unit)
                </th>
                <th
                  scope="col"
                  class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 border-b border-gray-300"
                >
                  Harga Satuan
                </th>
                <th
                  scope="col"
                  class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 border-b border-gray-300"
                >
                  Kondisi
                </th>
                <th
                  scope="col"
                  class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 border-b border-gray-300"
                >
                  Jumlah (Quantity)
                </th>
                <th
                  scope="col"
                  class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 border-b border-gray-300"
                >
                  Diterima
                </th>
                <th
                  scope="col"
                  class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 sm:pr-6 border-b border-gray-300"
                >
                  Nilai Transfer
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr
                v-for="(item, index) in transfer.items"
                :key="item.id"
              >
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-center text-gray-500 sm:pl-6">
                  {{ index + 1 }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
                  {{ item.product?.name || item.product_name || '-' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  {{ item.product?.sku || item.product_sku || '-' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  {{ item.product?.unit?.symbol || item.product?.unit?.name || '-' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-right text-gray-700">
                  {{ formatRupiah(item.unit_price ?? item.product?.unit_price ?? item.product_unit_price ?? 0) }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-center text-gray-600">
                  <span
                    class="px-2 py-1 text-xs rounded-full"
                    :class="(item.condition || 'GOOD') === 'DEFECTIVE'
                      ? 'bg-red-100 text-red-800'
                      : 'bg-emerald-100 text-emerald-800'"
                  >
                    {{ (item.condition || 'GOOD') === 'DEFECTIVE' ? 'RUSAK' : 'BAGUS' }}
                  </span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-mono text-right text-gray-900">
                  {{ formatQuantity(item.quantity) }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-mono text-right text-gray-900">
                  {{ item.received_quantity != null ? formatQuantity(item.received_quantity) : '-' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-mono text-right font-medium text-gray-900 sm:pr-6">
                  {{ formatRupiah(item.subtotal ?? (Number(item.quantity || 0) * Number(item.unit_price ?? item.product?.unit_price ?? item.product_unit_price ?? 0))) }}
                </td>
              </tr>
            </tbody>
            <tfoot
              v-if="transfer?.items?.length"
              class="bg-gray-50 font-semibold text-gray-900 border-t border-gray-300"
            >
              <tr>
                <td
                  colspan="8"
                  class="px-4 py-3 text-right text-sm"
                >
                  Grand Total Nilai Transfer:
                </td>
                <td class="px-3 py-3 text-right text-sm font-mono text-indigo-700 sm:pr-6">
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

const route = useRoute();
const store = useStockTransferStore();
const authStore = useAuthStore();

const transfer = computed(() => store.currentTransfer);

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
