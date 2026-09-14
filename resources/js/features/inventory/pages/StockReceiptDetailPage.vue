<template>
  <div class="space-y-3">
    <!-- TOP Header & Action Strip Compact -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3">
        <router-link
          to="/inventory/receipts"
          class="inline-flex items-center gap-1 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-gray-50 hover:bg-gray-100 px-2.5 py-1.5 rounded-lg border border-gray-200 transition-colors"
        >
          &larr; Kembali
        </router-link>

        <div class="h-4 w-px bg-gray-200" />

        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-base font-bold text-gray-900 tracking-tight font-mono">
              {{ doc?.receipt_number || 'Detail Penerimaan Stok' }}
            </h1>
            <DocumentStatusBadge
              v-if="doc"
              :status="doc.status"
            />
          </div>
          <p class="text-[11px] text-gray-500">
            Dokumen mutasi barang masuk gudang.
          </p>
        </div>
      </div>

      <!-- Action Buttons -->
      <div
        v-if="doc"
        class="flex items-center gap-2 flex-wrap"
      >
        <BasePrintButton
          @click="handlePrint"
        />

        <router-link
          v-if="doc.status === 'DRAFT' && hasPermission('stock_receipts.update')"
          :to="`/inventory/receipts/${doc.id}/edit`"
          class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs hover:bg-gray-50"
        >
          Edit Draft
        </router-link>

        <button
          v-if="doc.status === 'DRAFT' && hasPermission('stock_receipts.cancel')"
          :disabled="isProcessing"
          type="button"
          class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 shadow-2xs hover:bg-rose-100 disabled:opacity-50 cursor-pointer"
          @click="openCancelConfirm"
        >
          Batalkan Draft
        </button>

        <button
          v-if="doc.status === 'DRAFT' && hasPermission('stock_receipts.post')"
          :disabled="isProcessing"
          type="button"
          class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-indigo-700 disabled:opacity-50 cursor-pointer"
          @click="openPostConfirm"
        >
          Post Dokumen
        </button>
      </div>
    </div>

    <!-- Feedback & Loading -->
    <div
      v-if="store.loading && !doc"
      class="p-8 text-center text-xs text-gray-500"
    >
      Memuat data...
    </div>

    <div
      v-else-if="doc"
      class="space-y-3"
    >
      <BaseAlert
        v-if="store.error"
        :message="store.error"
      />

      <!-- Compact Metadata Card -->
      <div class="bg-white rounded-xl border border-gray-200 p-3 shadow-2xs">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Tanggal Dokumen</span>
            <span class="font-semibold text-gray-800">{{ doc.date }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">No. SPB / Memo GA</span>
            <span class="font-semibold text-gray-800 font-mono">{{ doc.memo_number || '-' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Sumber Barang</span>
            <span
              class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium"
              :class="doc.source_type === 'GA_SERVICED' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-blue-50 text-blue-800 border border-blue-200'"
            >
              {{ doc.source_type === 'GA_SERVICED' ? 'Hasil Servis GA' : 'Pengadaan Baru GA' }}
            </span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Supplier</span>
            <span class="font-semibold text-gray-800">{{ doc.supplier?.name ? `${doc.supplier.name} (${doc.supplier.code || ''})` : 'Tanpa Supplier / Internal GA' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Dibuat Oleh</span>
            <span class="font-semibold text-gray-800">{{ doc.creator?.name || '-' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Catatan</span>
            <span class="text-gray-700 truncate block">{{ doc.notes || '-' }}</span>
          </div>
        </div>
      </div>

      <!-- Items Table -->
      <DocumentItemsTable
        :items="doc.items"
        heading="Item Penerimaan"
        grand-total-label="Grand Total Penerimaan:"
        :total-quantity="totalQuantity"
        :total-amount="totalAmount"
      />
    </div>

    <!-- Confirm Dialogs -->
    <BaseConfirmation
      v-if="showPostConfirm"
      :model-value="true"
      title="Post Dokumen"
      confirm-label="Ya, Post"
      variant="primary"
      :loading="isProcessing"
      @confirm="confirmPost"
      @cancel="showPostConfirm = false"
    >
      <template #description>
        <p>
          Apakah Anda yakin ingin memposting dokumen ini? Saldo stok akan berubah
          dan dokumen tidak dapat diubah lagi.
        </p>
      </template>
    </BaseConfirmation>

    <BaseConfirmation
      v-if="showCancelConfirm"
      :model-value="true"
      title="Batalkan Draft"
      confirm-label="Ya, Batalkan"
      danger
      :loading="isProcessing"
      @confirm="confirmCancel"
      @cancel="showCancelConfirm = false"
    >
      <template #description>
        <p>Apakah Anda yakin ingin membatalkan draft ini?</p>
      </template>
    </BaseConfirmation>
  </div>
</template>

<script setup>
import { useRoute } from 'vue-router';
import { useStockReceiptStore } from '../stores/useStockReceiptStore';
import { useDocumentDetail } from '../composables/use_document_detail';
import BaseAlert from '@/shared/components/BaseAlert.vue';
import BaseConfirmation from '@/shared/components/BaseConfirmation.vue';
import BasePrintButton from '@/shared/components/BasePrintButton.vue';
import DocumentStatusBadge from '../components/DocumentStatusBadge.vue';
import DocumentItemsTable from '../components/DocumentItemsTable.vue';
import { useAuthStore } from '@features/auth/stores/use_auth_store';
import { printStockReceipt } from '@/shared/utils/printDocument';

const store = useStockReceiptStore();
const authStore = useAuthStore();

const {
    doc,
    isProcessing,
    showPostConfirm,
    showCancelConfirm,
    totalQuantity,
    totalAmount,
    hasPermission,
    openPostConfirm,
    openCancelConfirm,
    confirmPost,
    confirmCancel,
} = useDocumentDetail({
    store,
    currentKey: 'currentReceipt',
    id: useRoute().params.id,
    toastTitle: 'Penerimaan Stok',
});

const handlePrint = () => {
    if (!doc.value) return;
    printStockReceipt(doc.value, {
        printedBy: authStore.user?.name || 'Sistem StockEdp',
    });
};
</script>