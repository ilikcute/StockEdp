<template>
  <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-3.5 flex flex-col justify-between h-full">
    <div>
      <div class="flex items-center justify-between mb-2.5">
        <div>
          <h3 class="text-xs font-bold text-gray-900 flex items-center gap-1.5">
            <svg
              class="w-4 h-4 text-blue-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
              />
            </svg>
            Ringkasan Aktivitas Periode
          </h3>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Dokumen terposting, item terpengaruh, volume fisik, dan estimasi nilai mutasi.
          </p>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2">
        <!-- Posted Receipts -->
        <div class="bg-emerald-50/60 border border-emerald-200/80 rounded-lg p-2.5 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-semibold text-emerald-800 uppercase tracking-wider">Penerimaan Posting</span>
              <span class="text-[10px] px-1.5 py-0.5 rounded font-medium bg-emerald-100/80 text-emerald-800">
                {{ data.receipt_item_count || 0 }} Item
              </span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
              <span
                id="period-posted-receipt-count"
                class="text-base font-bold text-gray-900"
              >{{ data.posted_receipt_count || 0 }}</span>
              <span class="text-[11px] font-medium text-gray-500">Dokumen</span>
            </div>
          </div>

          <div class="mt-2 pt-1.5 border-t border-emerald-200/70 grid grid-cols-2 gap-1 text-[11px]">
            <div>
              <span class="text-gray-500 block text-[10px]">Total Qty:</span>
              <span class="font-semibold text-gray-900">{{ formatQuantity(data.receipt_total_quantity) }}</span>
            </div>
            <div class="text-right">
              <span class="text-gray-500 block text-[10px]">Total Nilai:</span>
              <span
                class="font-semibold text-emerald-700 truncate block"
                :title="formatRupiah(data.receipt_total_amount)"
              >{{ formatRupiah(data.receipt_total_amount) }}</span>
            </div>
          </div>
        </div>

        <!-- Posted Issues -->
        <div class="bg-amber-50/60 border border-amber-200/80 rounded-lg p-2.5 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-semibold text-amber-800 uppercase tracking-wider">Pengeluaran Posting</span>
              <span class="text-[10px] px-1.5 py-0.5 rounded font-medium bg-amber-100/80 text-amber-800">
                {{ data.issue_item_count || 0 }} Item
              </span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
              <span
                id="period-posted-issue-count"
                class="text-base font-bold text-gray-900"
              >{{ data.posted_issue_count || 0 }}</span>
              <span class="text-[11px] font-medium text-gray-500">Dokumen</span>
            </div>
          </div>

          <div class="mt-2 pt-1.5 border-t border-amber-200/70 grid grid-cols-2 gap-1 text-[11px]">
            <div>
              <span class="text-gray-500 block text-[10px]">Total Qty:</span>
              <span class="font-semibold text-gray-900">{{ formatQuantity(data.issue_total_quantity) }}</span>
            </div>
            <div class="text-right">
              <span class="text-gray-500 block text-[10px]">Total Nilai:</span>
              <span
                class="font-semibold text-amber-700 truncate block"
                :title="formatRupiah(data.issue_total_amount)"
              >{{ formatRupiah(data.issue_total_amount) }}</span>
            </div>
          </div>
        </div>

        <!-- Received Transfers -->
        <div class="bg-blue-50/60 border border-blue-200/80 rounded-lg p-2.5 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-semibold text-blue-800 uppercase tracking-wider">Transfer Selesai</span>
              <span class="text-[10px] px-1.5 py-0.5 rounded font-medium bg-blue-100/80 text-blue-800">
                {{ data.transfer_item_count || 0 }} Item
              </span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
              <span
                id="period-received-transfer-count"
                class="text-base font-bold text-gray-900"
              >{{ data.received_transfer_count || 0 }}</span>
              <span class="text-[11px] font-medium text-gray-500">Dokumen</span>
            </div>
          </div>

          <div class="mt-2 pt-1.5 border-t border-blue-200/70 grid grid-cols-2 gap-1 text-[11px]">
            <div>
              <span class="text-gray-500 block text-[10px]">Total Qty:</span>
              <span class="font-semibold text-gray-900">{{ formatQuantity(data.transfer_total_quantity) }}</span>
            </div>
            <div class="text-right">
              <span class="text-gray-500 block text-[10px]">Total Nilai:</span>
              <span
                class="font-semibold text-blue-700 truncate block"
                :title="formatRupiah(data.transfer_total_amount)"
              >{{ formatRupiah(data.transfer_total_amount) }}</span>
            </div>
          </div>
        </div>

        <!-- Stock Movements -->
        <div class="bg-purple-50/60 border border-purple-200/80 rounded-lg p-2.5 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-semibold text-purple-800 uppercase tracking-wider">Total Movement</span>
              <span class="text-[10px] px-1.5 py-0.5 rounded font-medium bg-purple-100/80 text-purple-800">
                {{ data.movement_item_count || 0 }} Item
              </span>
            </div>
            <div class="flex items-baseline gap-1 mt-1">
              <span
                id="period-movement-count"
                class="text-base font-bold text-gray-900"
              >{{ data.movement_count || 0 }}</span>
              <span class="text-[11px] font-medium text-gray-500">Mutasi</span>
            </div>
          </div>

          <div class="mt-2 pt-1.5 border-t border-purple-200/70 grid grid-cols-2 gap-1 text-[11px]">
            <div>
              <span class="text-gray-500 block text-[10px]">Total Qty:</span>
              <span class="font-semibold text-gray-900">{{ formatQuantity(data.movement_total_quantity) }}</span>
            </div>
            <div class="text-right">
              <span class="text-gray-500 block text-[10px]">Total Nilai:</span>
              <span
                class="font-semibold text-purple-700 truncate block"
                :title="formatRupiah(data.movement_total_amount)"
              >{{ formatRupiah(data.movement_total_amount) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { formatRupiah, formatQuantity } from '@/shared/utils/formatters.js';

defineProps({
  data: {
    type: Object,
    default: () => ({
      posted_receipt_count: 0,
      receipt_item_count: 0,
      receipt_total_quantity: '0',
      receipt_total_amount: 0,

      posted_issue_count: 0,
      issue_item_count: 0,
      issue_total_quantity: '0',
      issue_total_amount: 0,

      received_transfer_count: 0,
      transfer_item_count: 0,
      transfer_total_quantity: '0',
      transfer_total_amount: 0,

      movement_count: 0,
      movement_item_count: 0,
      movement_total_quantity: '0',
      movement_total_amount: 0,
    }),
  },
});
</script>
