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
      <component
        :is="canNavigate('stock_receipts.view') ? 'button' : 'div'"
        type="button"
        :class="[
          'bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-left transition-all',
          canNavigate('stock_receipts.view') ? 'hover:bg-blue-50/50 dark:hover:bg-blue-900/20 hover:border-blue-300 dark:hover:border-blue-700 cursor-pointer group focus:outline-none focus:ring-2 focus:ring-blue-500' : ''
        ]"
        @click="canNavigate('stock_receipts.view') && navigateTo('inventory.receipts')"
      >
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 block truncate">Draft Penerimaan</span>
        <div class="mt-2 flex items-baseline justify-between">
          <span
            id="queue-receipt-draft-count"
            class="text-xl font-bold text-gray-900 dark:text-white"
          >{{ data.receipt_draft_count || 0 }}</span>
          <span
            v-if="canNavigate('stock_receipts.view')"
            class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold group-hover:translate-x-0.5 transition-transform"
          >&rarr;</span>
        </div>
      </component>

      <!-- Issue Drafts -->
      <component
        :is="canNavigate('stock_issues.view') ? 'button' : 'div'"
        type="button"
        :class="[
          'bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-left transition-all',
          canNavigate('stock_issues.view') ? 'hover:bg-blue-50/50 dark:hover:bg-blue-900/20 hover:border-blue-300 dark:hover:border-blue-700 cursor-pointer group focus:outline-none focus:ring-2 focus:ring-blue-500' : ''
        ]"
        @click="canNavigate('stock_issues.view') && navigateTo('inventory.issues')"
      >
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 block truncate">Draft Pengeluaran</span>
        <div class="mt-2 flex items-baseline justify-between">
          <span
            id="queue-issue-draft-count"
            class="text-xl font-bold text-gray-900 dark:text-white"
          >{{ data.issue_draft_count || 0 }}</span>
          <span
            v-if="canNavigate('stock_issues.view')"
            class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold group-hover:translate-x-0.5 transition-transform"
          >&rarr;</span>
        </div>
      </component>

      <!-- Transfer Awaiting Receipt -->
      <component
        :is="canNavigate('stock_transfers.view') ? 'button' : 'div'"
        type="button"
        :class="[
          'bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-left transition-all',
          canNavigate('stock_transfers.view') ? 'hover:bg-amber-50/50 dark:hover:bg-amber-900/20 hover:border-amber-300 dark:hover:border-amber-700 cursor-pointer group focus:outline-none focus:ring-2 focus:ring-amber-500' : ''
        ]"
        @click="canNavigate('stock_transfers.view') && navigateTo('inventory.transfers')"
      >
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 block truncate">Transfer Transit</span>
        <div class="mt-2 flex items-baseline justify-between">
          <span
            id="queue-transfer-awaiting-receipt-count"
            class="text-xl font-bold text-gray-900 dark:text-white"
          >{{ data.transfer_awaiting_receipt_count || 0 }}</span>
          <span
            v-if="canNavigate('stock_transfers.view')"
            class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold group-hover:translate-x-0.5 transition-transform"
          >&rarr;</span>
        </div>
      </component>

      <!-- Adjustment Pending -->
      <component
        :is="canNavigate('stock_adjustments.view') ? 'button' : 'div'"
        type="button"
        :class="[
          'bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-left transition-all',
          canNavigate('stock_adjustments.view') ? 'hover:bg-indigo-50/50 dark:hover:bg-indigo-900/20 hover:border-indigo-300 dark:hover:border-indigo-700 cursor-pointer group focus:outline-none focus:ring-2 focus:ring-indigo-500' : ''
        ]"
        @click="canNavigate('stock_adjustments.view') && navigateTo('inventory.adjustments')"
      >
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 block truncate">Draft Penyesuaian</span>
        <div class="mt-2 flex items-baseline justify-between">
          <span
            id="queue-adjustment-pending-count"
            class="text-xl font-bold text-gray-900 dark:text-white"
          >{{ data.adjustment_pending_count || 0 }}</span>
          <span
            v-if="canNavigate('stock_adjustments.view')"
            class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold group-hover:translate-x-0.5 transition-transform"
          >&rarr;</span>
        </div>
      </component>

      <!-- Opname In Progress -->
      <component
        :is="canNavigate('stock_opnames.view') ? 'button' : 'div'"
        type="button"
        :class="[
          'bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-left transition-all',
          canNavigate('stock_opnames.view') ? 'hover:bg-purple-50/50 dark:hover:bg-purple-900/20 hover:border-purple-300 dark:hover:border-purple-700 cursor-pointer group focus:outline-none focus:ring-2 focus:ring-purple-500' : ''
        ]"
        @click="canNavigate('stock_opnames.view') && navigateTo('stockOpnames')"
      >
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 block truncate">Opname Berlangsung</span>
        <div class="mt-2 flex items-baseline justify-between">
          <span
            id="queue-opname-in-progress-count"
            class="text-xl font-bold text-gray-900 dark:text-white"
          >{{ data.opname_in_progress_count || 0 }}</span>
          <span
            v-if="canNavigate('stock_opnames.view')"
            class="text-[10px] text-purple-600 dark:text-purple-400 font-semibold group-hover:translate-x-0.5 transition-transform"
          >&rarr;</span>
        </div>
      </component>

      <!-- Opname Awaiting Post -->
      <component
        :is="canNavigate('stock_opnames.view') ? 'button' : 'div'"
        type="button"
        :class="[
          'bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg p-3 text-left transition-all',
          canNavigate('stock_opnames.view') ? 'hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 hover:border-emerald-300 dark:hover:border-emerald-700 cursor-pointer group focus:outline-none focus:ring-2 focus:ring-emerald-500' : ''
        ]"
        @click="canNavigate('stock_opnames.view') && navigateTo('stockOpnames')"
      >
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 block truncate">Opname Menunggu Post</span>
        <div class="mt-2 flex items-baseline justify-between">
          <span
            id="queue-opname-awaiting-post-count"
            class="text-xl font-bold text-gray-900 dark:text-white"
          >{{ data.opname_awaiting_post_count || 0 }}</span>
          <span
            v-if="canNavigate('stock_opnames.view')"
            class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold group-hover:translate-x-0.5 transition-transform"
          >&rarr;</span>
        </div>
      </component>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../auth/stores/use_auth_store';

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
const authStore = useAuthStore();

const canNavigate = (permission) => {
  return authStore && authStore.hasPermission ? authStore.hasPermission(permission) : false;
};

const navigateTo = (routeName) => {
  if (router && routeName) {
    router.push({ name: routeName });
  }
};
</script>
