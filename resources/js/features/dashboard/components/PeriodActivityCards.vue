<template>
  <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-5">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
          <svg
            class="w-5 h-5 text-blue-600"
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
        <p class="text-xs text-gray-500 mt-0.5">
          Jumlah dokumen yang selesai diposting/diterima dan total pergerakan stok selama periode terpilih.
        </p>
      </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <!-- Posted Receipts -->
      <div class="bg-emerald-50/50 border border-emerald-200 rounded-lg p-3.5">
        <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider block">Penerimaan Diposting</span>
        <span
          id="period-posted-receipt-count"
          class="text-2xl font-bold text-gray-900 mt-1 block"
        >{{ data.posted_receipt_count || 0 }}</span>
      </div>

      <!-- Posted Issues -->
      <div class="bg-amber-50/50 border border-amber-200 rounded-lg p-3.5">
        <span class="text-xs font-semibold text-amber-700 uppercase tracking-wider block">Pengeluaran Diposting</span>
        <span
          id="period-posted-issue-count"
          class="text-2xl font-bold text-gray-900 mt-1 block"
        >{{ data.posted_issue_count || 0 }}</span>
      </div>

      <!-- Received Transfers -->
      <div class="bg-blue-50/50 border border-blue-200 rounded-lg p-3.5">
        <span class="text-xs font-semibold text-blue-700 uppercase tracking-wider block">Transfer Diterima</span>
        <span
          id="period-received-transfer-count"
          class="text-2xl font-bold text-gray-900 mt-1 block"
        >{{ data.received_transfer_count || 0 }}</span>
      </div>

      <!-- Stock Movements -->
      <div class="bg-purple-50/50 border border-purple-200 rounded-lg p-3.5">
        <span class="text-xs font-semibold text-purple-700 uppercase tracking-wider block">Total Movement Stok</span>
        <span
          id="period-movement-count"
          class="text-2xl font-bold text-gray-900 mt-1 block"
        >{{ data.movement_count || 0 }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  data: {
    type: Object,
    default: () => ({
      posted_receipt_count: 0,
      posted_issue_count: 0,
      received_transfer_count: 0,
      movement_count: 0,
    }),
  },
});
</script>
