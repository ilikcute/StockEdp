<template>
  <div class="space-y-3">
    <!-- TOP Header & Action Strip Compact -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3">
        <router-link
          to="/inventory/adjustments"
          class="inline-flex items-center gap-1 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-gray-50 hover:bg-gray-100 px-2.5 py-1.5 rounded-lg border border-gray-200 transition-colors"
        >
          &larr; Kembali
        </router-link>

        <div class="h-4 w-px bg-gray-200" />

        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-base font-bold text-gray-900 tracking-tight font-mono">
              {{ adjustment?.adjustment_number || 'Detail Penyesuaian Stok' }}
            </h1>
            <span
              v-if="adjustment"
              class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full"
              :class="{
                'bg-yellow-100 text-yellow-800': adjustment.status === 'DRAFT',
                'bg-green-100 text-green-800': adjustment.status === 'POSTED',
                'bg-gray-100 text-gray-800': adjustment.status === 'CANCELED'
              }"
            >
              {{ adjustment.status_label || adjustment.status }}
            </span>
          </div>
          <p class="text-[11px] text-gray-500">
            Rincian dokumen koreksi pergerakan saldo stok.
          </p>
        </div>
      </div>

      <div
        v-if="adjustment"
        class="flex items-center gap-2 flex-wrap"
      >
        <router-link
          v-if="adjustment.status === 'DRAFT' && adjustment.abilities?.can_update"
          :to="`/inventory/adjustments/${adjustment.id}/edit`"
          class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs hover:bg-gray-50"
        >
          Edit Draft
        </router-link>

        <button
          v-if="adjustment.status === 'DRAFT' && adjustment.abilities?.can_cancel"
          :disabled="store.loadingAction"
          class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 shadow-2xs hover:bg-rose-100 disabled:opacity-50 cursor-pointer"
          @click="openConfirmModal('cancel')"
        >
          Batalkan Draft
        </button>

        <button
          v-if="adjustment.status === 'DRAFT' && adjustment.abilities?.can_post"
          :disabled="store.loadingAction"
          class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-emerald-700 disabled:opacity-50 cursor-pointer"
          @click="openConfirmModal('post')"
        >
          Posting Adjustment
        </button>
      </div>
    </div>

    <!-- Alert Error Global -->
    <div
      v-if="store.error"
      class="rounded-xl bg-rose-50 p-3 border border-rose-200"
    >
      <p class="text-xs font-medium text-rose-800">
        {{ store.error }}
      </p>
    </div>

    <!-- Alert Success Feedback -->
    <div
      v-if="actionSuccessMessage"
      class="rounded-xl bg-emerald-50 p-3 border border-emerald-200 flex items-center justify-between"
    >
      <div class="flex items-center gap-2">
        <span class="text-emerald-600 font-bold text-sm">✓</span>
        <p class="text-xs font-medium text-emerald-800">
          {{ actionSuccessMessage }}
        </p>
      </div>
      <button
        type="button"
        class="text-emerald-600 hover:text-emerald-800 text-xs font-bold cursor-pointer"
        @click="actionSuccessMessage = ''"
      >
        ✕
      </button>
    </div>

    <!-- Banner Maker-Checker untuk Pembuat Draft -->
    <div
      v-if="adjustment?.status === 'DRAFT' && !adjustment?.abilities?.can_post"
      class="rounded-xl bg-blue-50 p-3 border border-blue-200"
    >
      <div class="flex items-center gap-2">
        <span class="text-blue-500 font-bold text-xs">ℹ</span>
        <p class="text-xs font-medium text-blue-800">
          Adjustment ini harus diposting oleh pengguna lain yang memiliki izin (Maker-Checker Rule).
        </p>
      </div>
    </div>

    <div
      v-if="store.loadingDetail && !adjustment"
      class="p-8 text-center text-xs text-gray-500"
    >
      Memuat data adjustment...
    </div>

    <div
      v-else-if="adjustment"
      class="space-y-3"
    >
      <!-- Compact Metadata Card -->
      <div class="bg-white rounded-xl border border-gray-200 p-3 shadow-2xs">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Lokasi Gudang</span>
            <span class="font-semibold text-gray-800">{{ adjustment.location_name || '-' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Arah (Direction)</span>
            <span
              class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold"
              :class="adjustment.direction === 'INCREASE' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200'"
            >
              {{ adjustment.direction === 'INCREASE' ? '↑ Tambah (INCREASE)' : '↓ Kurang (DECREASE)' }}
            </span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Alasan (Reason)</span>
            <span class="font-semibold text-gray-800">{{ adjustment.reason_label || adjustment.reason_code }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Tanggal Adjustment</span>
            <span class="font-semibold text-gray-800">{{ adjustment.adjustment_date }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Dibuat Oleh</span>
            <span class="font-semibold text-gray-800">{{ adjustment.created_by || '-' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Catatan</span>
            <span class="text-gray-700 truncate block">{{ adjustment.notes || '-' }}</span>
          </div>
        </div>
      </div>

      <!-- Informational Notice -->
      <div class="p-2.5 rounded-lg bg-amber-50 border border-amber-200">
        <p class="text-[11px] text-amber-900">
          <strong>Perhatian:</strong> Kuantitas pada item adalah delta perubahan stok, bukan saldo akhir persediaan.
          <span v-if="adjustment.status === 'POSTED'">
            Dokumen yang sudah diposting bersifat <strong>immutable</strong> dan pergerakan stok telah dicatat secara permanen di ledger.
          </span>
        </p>
      </div>

      <!-- Items Section -->
      <div class="mt-4 bg-white shadow-2xs border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-4 py-2.5 sm:px-4 border-b border-gray-100">
          <h3 class="text-xs font-semibold leading-5 text-gray-900">
            Daftar Barang Adjustment
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
                  class="py-1.5 px-2 text-right whitespace-nowrap"
                >
                  Harga Satuan
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 text-right whitespace-nowrap"
                >
                  Delta Kuantitas
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 text-right whitespace-nowrap"
                >
                  Nilai Penyesuaian
                </th>
                <th
                  scope="col"
                  class="py-1.5 px-2 whitespace-nowrap"
                >
                  Catatan Item
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <tr
                v-for="(item, index) in adjustment.items"
                :key="item.id"
                class="hover:bg-gray-50/80 transition-colors"
              >
                <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
                  {{ index + 1 }}
                </td>
                <td class="py-1.5 px-2 text-[11px] font-medium text-gray-900 whitespace-nowrap">
                  {{ item.product_name || '-' }}
                </td>
                <td class="py-1.5 px-2 text-[10px] text-gray-400 font-mono whitespace-nowrap">
                  {{ item.product_sku || '-' }}
                </td>
                <td class="py-1.5 px-2 text-[11px] text-right font-mono text-gray-900 whitespace-nowrap">
                  {{ formatRupiah(item.product_unit_price || item.product?.unit_price || 0) }}
                </td>
                <td class="py-1.5 px-2 text-[11px] font-mono text-right font-medium whitespace-nowrap">
                  <span :class="adjustment.direction === 'INCREASE' ? 'text-emerald-700' : 'text-orange-700'">
                    {{ adjustment.direction === 'INCREASE' ? '+' : '-' }}{{ formatQuantity(item.quantity, false) }}
                  </span>
                </td>
                <td class="py-1.5 px-2 text-[11px] font-mono text-right font-medium text-gray-900 whitespace-nowrap">
                  {{ formatRupiah((item.product_unit_price || item.product?.unit_price || 0) * Number(item.quantity || 0)) }}
                </td>
                <td class="py-1.5 px-2 text-[11px] text-gray-500 whitespace-nowrap">
                  {{ item.item_notes || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Fallback jika data adjustment gagal dimuat / null -->
    <div
      v-else-if="!adjustment && !store.loadingDetail"
      class="mt-8 text-center bg-white p-8 rounded-lg shadow-sm border border-gray-200"
    >
      <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 mb-4">
        <span class="text-red-600 text-xl font-bold">!</span>
      </div>
      <h3 class="text-base font-semibold text-gray-900">
        Dokumen Adjustment Tidak Ditemukan
      </h3>
      <p class="mt-2 text-sm text-gray-500">
        {{ store.error || 'Data penyesuaian stok tidak dapat dimuat atau Anda tidak memiliki akses ke dokumen ini.' }}
      </p>
      <div class="mt-6">
        <router-link
          to="/inventory/adjustments"
          class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 cursor-pointer"
        >
          Kembali ke Daftar Penyesuaian
        </router-link>
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
              <p class="text-sm text-gray-600">
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
              confirmActionType === 'post' ? 'bg-green-600 hover:bg-green-500 focus-visible:outline-green-600' : '',
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
import { useStockAdjustmentStore } from '../stores/useStockAdjustmentStore';
import { formatRupiah, formatQuantity } from '@/shared/utils/formatters.js';

const route = useRoute();
const store = useStockAdjustmentStore();

const adjustment = computed(() => store.currentAdjustment);

const confirmActionType = ref(null);
const actionSuccessMessage = ref('');

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
  actionSuccessMessage.value = '';

  try {
    if (actionType === 'post') {
      await store.postAdjustment(id);
      actionSuccessMessage.value = 'Dokumen penyesuaian stok berhasil diposting. Pergerakan stok telah dicatat ke ledger persediaan.';
    } else if (actionType === 'cancel') {
      await store.cancelAdjustment(id);
      actionSuccessMessage.value = 'Draft penyesuaian stok berhasil dibatalkan.';
    }
  } catch (err) {
    console.error('Adjustment action error:', err);
  }
};

onMounted(() => {
  store.fetchAdjustmentById(route.params.id);
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
