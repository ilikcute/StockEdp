<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 mb-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
          <svg
            class="w-5 h-5 text-blue-600 dark:text-blue-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
            />
          </svg>
          Antrean Operasional Membutuhkan Tindakan
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
          Daftar transaksi dan proses persediaan yang membutuhkan penyelesaian atau verifikasi.
        </p>
      </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
      <!-- Receipt Drafts -->
      <div
        class="bg-gray-50 dark:bg-gray-900/50 hover:bg-blue-50/50 dark:hover:bg-blue-900/20 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700 rounded-lg p-3 transition-all cursor-pointer group"
        @click="navigateTo('/stock-receipts')"
      >
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 block truncate">Draft Penerimaan</span>
        <div class="mt-2 flex items-baseline justify-between">
          <span
            id="queue-receipt-draft-count"
            class="text-xl font-bold text-gray-900 dark:text-white"
          >{{ data.receipt_draft_count || 0 }}</span>
          <span class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold group-hover:translate-x-0.5 transition-transform">&rarr;</span>
        </div>
      </div>

      <!-- Issue Drafts -->
      <div
        class="bg-gray-50 dark:bg-gray-900/50 hover:bg-blue-50/50 dark:hover:bg-blue-900/20 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700 rounded-lg p-3 transition-all cursor-pointer group"
        @click="navigateTo('/stock-issues')"
      >
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 block truncate">Draft Pengeluaran</span>
        <div class="mt-2 flex items-baseline justify-between">
          <span
            id="queue-issue-draft-count"
            class="text-xl font-bold text-gray-900 dark:text-white"
          >{{ data.issue_draft_count || 0 }}</span>
          <span class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold group-hover:translate-x-0.5 transition-transform">&rarr;</span>
        </div>
      </div>

      <!-- Transfer Awaiting Receipt -->
      <div
        class="bg-gray-50 dark:bg-gray-900/50 hover:bg-amber-50/50 dark:hover:bg-amber-900/20 border border-gray-200 dark:border-gray-700 hover:border-amber-300 dark:hover:border-amber-700 rounded-lg p-3 transition-all cursor-pointer group"
        @click="navigateTo('/stock-transfers')"
      >
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 block truncate">Transfer Transit</span>
        <div class="mt-2 flex items-baseline justify-between">
          <span
            id="queue-transfer-awaiting-receipt-count"
            class="text-xl font-bold text-gray-900 dark:text-white"
          >{{ data.transfer_awaiting_receipt_count || 0 }}</span>
          <span class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold group-hover:translate-x-0.5 transition-transform">&rarr;</span>
        </div>
      </div>

      <!-- Adjustment Pending -->
      <div
        class="bg-gray-50 dark:bg-gray-900/50 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/20 border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700 rounded-lg p-3 transition-all cursor-pointer group"
        @click="navigateTo('/stock-adjustments')"
      >
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 block truncate">Draft Penyesuaian</span>
        <div class="mt-2 flex items-baseline justify-between">
          <span
            id="queue-adjustment-pending-count"
            class="text-xl font-bold text-gray-900 dark:text-white"
          >{{ data.adjustment_pending_count || 0 }}</span>
          <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold group-hover:translate-x-0.5 transition-transform">&rarr;</span>
        </div>
      </div>

      <!-- Opname In Progress -->
      <div
        class="bg-gray-50 dark:bg-gray-900/50 hover:bg-purple-50/50 dark:hover:bg-purple-900/20 border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-700 rounded-lg p-3 transition-all cursor-pointer group"
        @click="navigateTo('/stock-opnames')"
      >
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 block truncate">Opname Berlangsung</span>
        <div class="mt-2 flex items-baseline justify-between">
          <span
            id="queue-opname-in-progress-count"
            class="text-xl font-bold text-gray-900 dark:text-white"
          >{{ data.opname_in_progress_count || 0 }}</span>
          <span class="text-[10px] text-purple-600 dark:text-purple-400 font-semibold group-hover:translate-x-0.5 transition-transform">&rarr;</span>
        </div>
      </div>

      <!-- Opname Awaiting Post -->
      <div
        class="bg-gray-50 dark:bg-gray-900/50 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 border border-gray-200 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-700 rounded-lg p-3 transition-all cursor-pointer group"
        @click="navigateTo('/stock-opnames')"
      >
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 block truncate">Opname Tuntas Dihitung</span>
        <div class="mt-2 flex items-baseline justify-between">
          <span
            id="queue-opname-awaiting-post-count"
            class="text-xl font-bold text-gray-900 dark:text-white"
          >{{ data.opname_awaiting_post_count || 0 }}</span>
          <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold group-hover:translate-x-0.5 transition-transform">&rarr;</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';

defineProps({
  data: {
    type: Object,
    default: () => ({
      receipt_draft_count: 0,
      issue_draft_count: 0,
      transfer_awaiting_receipt_count: 0,
      adjustment_pending_count: 0,
      opname_in_progress_count: 0,
      opname_awaiting_post_count: 0,
    }),
  },
});

const router = useRouter();

const navigateTo = (path) => {
  if (router) {
    router.push(path);
  }
};
</script>
