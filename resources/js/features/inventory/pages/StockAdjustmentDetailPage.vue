<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Detail Penyesuaian Stok
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Rincian dokumen koreksi pergerakan saldo stok.
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex gap-2">
        <router-link
          to="/inventory/adjustments"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
        >
          Kembali
        </router-link>

        <router-link
          v-if="adjustment?.status === 'DRAFT' && adjustment?.abilities?.can_update"
          :to="`/inventory/adjustments/${adjustment.id}/edit`"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
        >
          Edit Draft
        </router-link>

        <button
          v-if="adjustment?.status === 'DRAFT' && adjustment?.abilities?.can_cancel"
          :disabled="store.loadingAction"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-red-600 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50 disabled:opacity-50"
          @click="openConfirmModal('cancel')"
        >
          Batalkan Draft
        </button>

        <button
          v-if="adjustment?.status === 'DRAFT' && adjustment?.abilities?.can_post"
          :disabled="store.loadingAction"
          class="block rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600"
          @click="openConfirmModal('post')"
        >
          Posting Adjustment
        </button>
      </div>
    </div>

    <!-- Banner Maker-Checker untuk Pembuat Draft -->
    <div
      v-if="adjustment?.status === 'DRAFT' && !adjustment?.abilities?.can_post"
      class="mt-4 rounded-md bg-blue-50 p-4 border border-blue-200"
    >
      <div class="flex">
        <div class="flex-shrink-0">
          <span class="text-blue-500 font-bold">ℹ</span>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-blue-800">
            Adjustment ini harus diposting oleh pengguna lain yang memiliki izin (Maker-Checker Rule).
          </p>
        </div>
      </div>
    </div>

    <div
      v-if="store.loadingDetail && !adjustment"
      class="mt-8 text-center text-gray-500"
    >
      Memuat data adjustment...
    </div>

    <div
      v-else-if="adjustment"
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
            Informasi Dokumen Adjustment
          </h3>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
          <dl class="sm:divide-y sm:divide-gray-200">
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Nomor Adjustment
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-medium">
                {{ adjustment.adjustment_number }}
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
                    'bg-yellow-100 text-yellow-800': adjustment.status === 'DRAFT',
                    'bg-green-100 text-green-800': adjustment.status === 'POSTED',
                    'bg-gray-100 text-gray-800': adjustment.status === 'CANCELED'
                  }"
                >
                  {{ adjustment.status_label || adjustment.status }}
                </span>
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Lokasi Gudang
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ adjustment.location_name || '-' }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Arah Penyesuaian (Direction)
              </dt>
              <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium"
                  :class="adjustment.direction === 'INCREASE' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'"
                >
                  {{ adjustment.direction === 'INCREASE' ? '↑ Penambahan Stok (INCREASE)' : '↓ Pengurangan Stok (DECREASE)' }}
                </span>
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Alasan (Reason Code)
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ adjustment.reason_label || adjustment.reason_code }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Tanggal Adjustment
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ adjustment.adjustment_date }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Catatan Dokumen
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 whitespace-pre-line">
                {{ adjustment.notes || '-' }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Dibuat Oleh / Pada
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ adjustment.created_by || '-' }} ({{ adjustment.created_at }})
              </dd>
            </div>
            <div
              v-if="adjustment.updated_by"
              class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
            >
              <dt class="text-sm font-medium text-gray-500">
                Diperbarui Oleh / Pada
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ adjustment.updated_by }} ({{ adjustment.updated_at }})
              </dd>
            </div>
            <div
              v-if="adjustment.posted_at"
              class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
            >
              <dt class="text-sm font-medium text-gray-500">
                Diposting Oleh / Pada
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-medium text-green-700">
                {{ adjustment.posted_by || '-' }} ({{ adjustment.posted_at }})
              </dd>
            </div>
            <div
              v-if="adjustment.canceled_at"
              class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
            >
              <dt class="text-sm font-medium text-gray-500">
                Dibatalkan Oleh / Pada
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 text-red-700">
                {{ adjustment.canceled_by || '-' }} ({{ adjustment.canceled_at }})
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Informational Notice -->
      <div class="mt-4 p-4 rounded-md bg-yellow-50 border border-yellow-200">
        <p class="text-xs text-yellow-800">
          <strong>Perhatian:</strong> Kuantitas pada item adalah delta perubahan stok, bukan saldo akhir persediaan.
          <span v-if="adjustment.status === 'POSTED'">
            Dokumen yang sudah diposting bersifat <strong>immutable</strong> dan pergerakan stoknya telah dicatat secara permanen di ledger pergerakan stok.
          </span>
        </p>
      </div>

      <!-- Items Section -->
      <div class="mt-6 bg-white shadow sm:rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-base font-semibold leading-6 text-gray-900">
            Daftar Barang Adjustment
          </h3>
        </div>
        <div class="border-t border-gray-200">
          <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
              <tr>
                <th
                  scope="col"
                  class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"
                >
                  Produk
                </th>
                <th
                  scope="col"
                  class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
                >
                  SKU
                </th>
                <th
                  scope="col"
                  class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900"
                >
                  Delta Kuantitas (Quantity)
                </th>
                <th
                  scope="col"
                  class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 sm:pr-6"
                >
                  Catatan Item
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr
                v-for="item in adjustment.items"
                :key="item.id"
              >
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                  {{ item.product_name || '-' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  {{ item.product_sku || '-' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-mono text-right font-medium sm:pr-6">
                  <span :class="adjustment.direction === 'INCREASE' ? 'text-green-700' : 'text-orange-700'">
                    {{ adjustment.direction === 'INCREASE' ? '+' : '-' }}{{ item.quantity }}
                  </span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  {{ item.item_notes || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal Dialog Confirmation -->
    <div
      v-if="confirmActionType"
      class="fixed inset-0 z-50 overflow-y-auto"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          aria-hidden="true"
          @click="closeConfirmModal"
        />
        <span
          class="hidden sm:inline-block sm:h-screen sm:align-middle"
          aria-hidden="true"
        >&#8203;</span>

        <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div
                class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full sm:mx-0 sm:h-10 sm:w-10"
                :class="{
                  'bg-green-100': confirmActionType === 'post',
                  'bg-red-100': confirmActionType === 'cancel'
                }"
              >
                <span
                  class="text-lg font-bold"
                  :class="{
                    'text-green-600': confirmActionType === 'post',
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
                  <p class="text-sm text-gray-500">
                    {{ confirmDescription }}
                  </p>
                  <p
                    v-if="confirmActionType === 'post'"
                    class="mt-2 text-xs font-semibold text-red-600"
                  >
                    Perhatian: Setelah diposting, dokumen ini menjadi IMMUTABLE dan tidak dapat diubah atau dibatalkan.
                  </p>
                </div>
              </div>
            </div>
          </div>
          <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
            <button
              type="button"
              :disabled="store.loadingAction"
              :class="[
                confirmActionType === 'post' ? 'bg-green-600 hover:bg-green-500 focus-visible:outline-green-600' : '',
                confirmActionType === 'cancel' ? 'bg-red-600 hover:bg-red-500 focus-visible:outline-red-600' : '',
                'inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm disabled:opacity-50 sm:ml-3 sm:w-auto'
              ]"
              @click="executeAction"
            >
              {{ confirmButtonText }}
            </button>
            <button
              type="button"
              class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
              @click="closeConfirmModal"
            >
              Batal
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useStockAdjustmentStore } from '../stores/useStockAdjustmentStore';

const route = useRoute();
const store = useStockAdjustmentStore();

const adjustment = computed(() => store.currentAdjustment);

const confirmActionType = ref(null);

const confirmTitle = computed(() => {
  if (confirmActionType.value === 'post') return 'Posting Stock Adjustment';
  if (confirmActionType.value === 'cancel') return 'Pembatalan Draft Adjustment';
  return '';
});

const confirmDescription = computed(() => {
  if (confirmActionType.value === 'post') {
    const dirText = adjustment.value?.direction === 'INCREASE'
      ? 'MENAMBAH stok fisik'
      : 'MENGURANGI stok fisik';
    return `Apakah Anda yakin ingin mem-posting adjustment #${adjustment.value?.adjustment_number}? Transaksi ini akan ${dirText} untuk ${adjustment.value?.items?.length || 0} item di lokasi ${adjustment.value?.location_name} dengan alasan ${adjustment.value?.reason_label}.`;
  }
  if (confirmActionType.value === 'cancel') {
    return `Apakah Anda yakin ingin membatalkan draft adjustment #${adjustment.value?.adjustment_number}? Pembatalan draft tidak memengaruhi saldo stok.`;
  }
  return '';
});

const confirmButtonText = computed(() => {
  if (confirmActionType.value === 'post') return 'Ya, Posting Sekarang';
  if (confirmActionType.value === 'cancel') return 'Ya, Batalkan Draft';
  return 'Proses';
});

const openConfirmModal = (type) => {
  confirmActionType.value = type;
};

const closeConfirmModal = () => {
  confirmActionType.value = null;
};

const executeAction = async () => {
  if (!confirmActionType.value || !adjustment.value) return;

  const id = adjustment.value.id;
  const actionType = confirmActionType.value;
  closeConfirmModal();

  try {
    if (actionType === 'post') {
      await store.postAdjustment(id);
    } else if (actionType === 'cancel') {
      await store.cancelAdjustment(id);
    }
  } catch {
    // Handled by Pinia store
  }
};

onMounted(() => {
  store.fetchAdjustmentById(route.params.id);
});
</script>
