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
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
              />
            </svg>
            Antrean Tindakan Operasional
          </h3>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Transaksi yang membutuhkan verifikasi atau penyelesaian.
          </p>
        </div>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
        <!-- Receipt Drafts -->
        <component
          :is="canNavigate('stock_receipts.view') ? 'button' : 'div'"
          type="button"
          :class="[
            'bg-gray-50/80 border border-gray-200 rounded-lg p-2 text-left transition-all',
            canNavigate('stock_receipts.view') ? 'hover:bg-blue-50/50 hover:border-blue-300 cursor-pointer group focus:outline-none focus:ring-2 focus:ring-blue-500' : ''
          ]"
          @click="canNavigate('stock_receipts.view') && navigateTo('inventory.receipts')"
        >
          <span class="text-[10px] font-medium text-gray-500 block truncate">Draft Penerimaan</span>
          <div class="mt-1 flex items-baseline justify-between">
            <span
              id="queue-receipt-draft-count"
              class="text-base font-bold text-gray-900"
            >{{ data.receipt_draft_count || 0 }}</span>
            <span
              v-if="canNavigate('stock_receipts.view')"
              class="text-[10px] text-blue-600 font-semibold group-hover:translate-x-0.5 transition-transform"
            >&rarr;</span>
          </div>
        </component>

        <!-- Issue Drafts -->
        <component
          :is="canNavigate('stock_issues.view') ? 'button' : 'div'"
          type="button"
          :class="[
            'bg-gray-50/80 border border-gray-200 rounded-lg p-2 text-left transition-all',
            canNavigate('stock_issues.view') ? 'hover:bg-blue-50/50 hover:border-blue-300 cursor-pointer group focus:outline-none focus:ring-2 focus:ring-blue-500' : ''
          ]"
          @click="canNavigate('stock_issues.view') && navigateTo('inventory.issues')"
        >
          <span class="text-[10px] font-medium text-gray-500 block truncate">Draft Pengeluaran</span>
          <div class="mt-1 flex items-baseline justify-between">
            <span
              id="queue-issue-draft-count"
              class="text-base font-bold text-gray-900"
            >{{ data.issue_draft_count || 0 }}</span>
            <span
              v-if="canNavigate('stock_issues.view')"
              class="text-[10px] text-blue-600 font-semibold group-hover:translate-x-0.5 transition-transform"
            >&rarr;</span>
          </div>
        </component>

        <!-- Transfers Awaiting Receipt -->
        <component
          :is="canNavigate('stock_transfers.view') ? 'button' : 'div'"
          type="button"
          :class="[
            'bg-gray-50/80 border border-gray-200 rounded-lg p-2 text-left transition-all',
            canNavigate('stock_transfers.view') ? 'hover:bg-blue-50/50 hover:border-blue-300 cursor-pointer group focus:outline-none focus:ring-2 focus:ring-blue-500' : ''
          ]"
          @click="canNavigate('stock_transfers.view') && navigateTo('inventory.transfers')"
        >
          <span class="text-[10px] font-medium text-gray-500 block truncate">Transfer Dikirim</span>
          <div class="mt-1 flex items-baseline justify-between">
            <span
              id="queue-transfer-awaiting-receipt-count"
              class="text-base font-bold text-gray-900"
            >{{ data.transfer_awaiting_receipt_count || 0 }}</span>
            <span
              v-if="canNavigate('stock_transfers.view')"
              class="text-[10px] text-blue-600 font-semibold group-hover:translate-x-0.5 transition-transform"
            >&rarr;</span>
          </div>
        </component>

        <!-- Adjustment Pending -->
        <component
          :is="canNavigate('stock_adjustments.view') ? 'button' : 'div'"
          type="button"
          :class="[
            'bg-gray-50/80 border border-gray-200 rounded-lg p-2 text-left transition-all',
            canNavigate('stock_adjustments.view') ? 'hover:bg-blue-50/50 hover:border-blue-300 cursor-pointer group focus:outline-none focus:ring-2 focus:ring-blue-500' : ''
          ]"
          @click="canNavigate('stock_adjustments.view') && navigateTo('inventory.adjustments')"
        >
          <span class="text-[10px] font-medium text-gray-500 block truncate">Draft Penyesuaian</span>
          <div class="mt-1 flex items-baseline justify-between">
            <span
              id="queue-adjustment-pending-count"
              class="text-base font-bold text-gray-900"
            >{{ data.adjustment_pending_count || 0 }}</span>
            <span
              v-if="canNavigate('stock_adjustments.view')"
              class="text-[10px] text-blue-600 font-semibold group-hover:translate-x-0.5 transition-transform"
            >&rarr;</span>
          </div>
        </component>

        <!-- Opname In Progress -->
        <component
          :is="canNavigate('stock_opnames.view') ? 'button' : 'div'"
          type="button"
          :class="[
            'bg-gray-50/80 border border-gray-200 rounded-lg p-2 text-left transition-all',
            canNavigate('stock_opnames.view') ? 'hover:bg-blue-50/50 hover:border-blue-300 cursor-pointer group focus:outline-none focus:ring-2 focus:ring-blue-500' : ''
          ]"
          @click="canNavigate('stock_opnames.view') && navigateTo('stockOpnames')"
        >
          <span class="text-[10px] font-medium text-gray-500 block truncate">Opname Dihitung</span>
          <div class="mt-1 flex items-baseline justify-between">
            <span
              id="queue-opname-in-progress-count"
              class="text-base font-bold text-gray-900"
            >{{ data.opname_in_progress_count || 0 }}</span>
            <span
              v-if="canNavigate('stock_opnames.view')"
              class="text-[10px] text-blue-600 font-semibold group-hover:translate-x-0.5 transition-transform"
            >&rarr;</span>
          </div>
        </component>

        <!-- Opname Awaiting Post -->
        <component
          :is="canNavigate('stock_opnames.view') ? 'button' : 'div'"
          type="button"
          :class="[
            'bg-gray-50/80 border border-gray-200 rounded-lg p-2 text-left transition-all',
            canNavigate('stock_opnames.view') ? 'hover:bg-blue-50/50 hover:border-blue-300 cursor-pointer group focus:outline-none focus:ring-2 focus:ring-blue-500' : ''
          ]"
          @click="canNavigate('stock_opnames.view') && navigateTo('stockOpnames')"
        >
          <span class="text-[10px] font-medium text-gray-500 block truncate">Opname Menunggu Post</span>
          <div class="mt-1 flex items-baseline justify-between">
            <span
              id="queue-opname-awaiting-post-count"
              class="text-base font-bold text-gray-900"
            >{{ data.opname_awaiting_post_count || 0 }}</span>
            <span
              v-if="canNavigate('stock_opnames.view')"
              class="text-[10px] text-blue-600 font-semibold group-hover:translate-x-0.5 transition-transform"
            >&rarr;</span>
          </div>
        </component>
      </div>
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
