<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Detail Penerimaan Stok
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Rincian dokumen mutasi barang masuk.
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex gap-2">
        <router-link
          to="/inventory/receipts"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
        >
          Kembali
        </router-link>

        <router-link
          v-if="doc?.status === 'DRAFT' && hasPermission('stock_receipts.update')"
          :to="`/inventory/receipts/${doc.id}/edit`"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
        >
          Edit Draft
        </router-link>

        <button
          v-if="doc?.status === 'DRAFT' && hasPermission('stock_receipts.cancel')"
          :disabled="isProcessing"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-red-600 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50 disabled:opacity-50"
          @click="openCancelConfirm"
        >
          Batalkan Draft
        </button>

        <button
          v-if="doc?.status === 'DRAFT' && hasPermission('stock_receipts.post')"
          :disabled="isProcessing"
          class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          @click="openPostConfirm"
        >
          Post Dokumen
        </button>
      </div>
    </div>

    <div
      v-if="store.loading && !doc"
      class="mt-8 text-center text-gray-500"
    >
      Memuat data...
    </div>

    <div
      v-else-if="doc"
      class="mt-8"
    >
      <BaseAlert
        v-if="store.error"
        :message="store.error"
      />

      <div class="overflow-hidden bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-base font-semibold leading-6 text-gray-900">
            Informasi Dokumen
          </h3>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
          <dl class="sm:divide-y sm:divide-gray-200">
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Nomor Dokumen
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-medium">
                {{ doc.receipt_number }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Status
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                <DocumentStatusBadge :status="doc.status" />
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Tanggal Penerimaan
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ doc.date }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Supplier
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ doc.supplier?.name }} ({{ doc.supplier?.code }})
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Catatan
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ doc.notes || '-' }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Dibuat Oleh
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ doc.creator?.name }}
              </dd>
            </div>
          </dl>
        </div>
      </div>

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
import DocumentStatusBadge from '../components/DocumentStatusBadge.vue';
import DocumentItemsTable from '../components/DocumentItemsTable.vue';

const store = useStockReceiptStore();

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
</script>