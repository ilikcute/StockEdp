<template>
  <div class="space-y-3">
    <!-- TOP Header & Action Strip Compact -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3">
        <router-link
          to="/inventory/opnames"
          class="inline-flex items-center gap-1 text-xs font-semibold text-gray-600 hover:text-gray-900 bg-gray-50 hover:bg-gray-100 px-2.5 py-1.5 rounded-lg border border-gray-200 transition-colors"
        >
          &larr; Kembali
        </router-link>

        <div class="h-4 w-px bg-gray-200" />

        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-base font-bold text-gray-900 tracking-tight font-mono">
              {{ opname?.opname_number || 'Detail Stock Opname' }}
            </h1>
            <StockOpnameStatusBadge
              v-if="opname"
              :status="opname.status"
            />
          </div>
          <p class="text-[11px] text-gray-500">
            Rincian sesi penghitungan stok fisik.
          </p>
        </div>
      </div>

      <div
        v-if="opname"
        class="flex items-center gap-2 flex-wrap"
      >
        <!-- Edit Draft -->
        <router-link
          v-if="abilities.can_update"
          :to="`/inventory/opnames/${opname.id}/edit`"
          class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs hover:bg-gray-50"
        >
          Edit Draft
        </router-link>

        <!-- Start -->
        <button
          v-if="abilities.can_start"
          :disabled="isAnyActionLoading"
          class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-blue-700 disabled:opacity-50 cursor-pointer"
          @click="openConfirm('start')"
        >
          Mulai Opname
        </button>

        <!-- Go to Counting Workspace -->
        <router-link
          v-if="opname?.status === 'IN_PROGRESS'"
          :to="`/inventory/opnames/${opname.id}/count`"
          class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-indigo-700"
        >
          Ruang Hitung →
        </router-link>

        <!-- Complete -->
        <button
          v-if="abilities.can_complete"
          :disabled="isAnyActionLoading"
          class="inline-flex items-center gap-1 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-amber-700 disabled:opacity-50 cursor-pointer"
          @click="openConfirm('complete')"
        >
          Selesai Hitung
        </button>

        <!-- Reopen -->
        <button
          v-if="abilities.can_reopen"
          :disabled="isAnyActionLoading"
          class="inline-flex items-center gap-1 rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-700 shadow-2xs hover:bg-amber-50 disabled:opacity-50 cursor-pointer"
          @click="showReopenDialog = true"
        >
          Buka Kembali
        </button>

        <!-- Post -->
        <button
          v-if="abilities.can_post"
          :disabled="isAnyActionLoading"
          class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-emerald-700 disabled:opacity-50 cursor-pointer"
          @click="openConfirm('post')"
        >
          Posting
        </button>

        <!-- Cancel -->
        <button
          v-if="abilities.can_cancel"
          :disabled="isAnyActionLoading"
          class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 shadow-2xs hover:bg-rose-100 disabled:opacity-50 cursor-pointer"
          @click="showCancelDialog = true"
        >
          Batalkan
        </button>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div
      v-if="store.loadingDetail && !opname"
      class="p-8 text-center text-xs text-gray-500"
    >
      Memuat data opname...
    </div>

    <template v-else-if="opname">
      <!-- Error alert -->
      <div
        v-if="store.error"
        class="rounded-xl border border-rose-200 bg-rose-50 p-3"
      >
        <p class="text-xs font-medium text-rose-800">
          {{ store.error }}
        </p>
      </div>

      <!-- Compact Metadata Card -->
      <div class="bg-white rounded-xl border border-gray-200 p-3 shadow-2xs">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Lokasi</span>
            <span class="font-semibold text-gray-800">{{ opname.location_name || '-' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Tanggal Opname</span>
            <span class="font-semibold text-gray-800">{{ opname.opname_date }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Dibuat Oleh</span>
            <span class="font-semibold text-gray-800">{{ opname.created_by || '-' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Diposting Oleh</span>
            <span class="font-semibold text-gray-800">{{ opname.posted_by ? `${opname.posted_by} (${opname.posted_at || ''})` : '-' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Dibatalkan Pada</span>
            <span class="font-semibold text-gray-800">{{ opname.canceled_at || opname.cancelled_at || '-' }}</span>
          </div>

          <div>
            <span class="block text-[11px] text-gray-400 font-medium">Catatan</span>
            <span class="text-gray-700 truncate block">{{ opname.notes || '-' }}</span>
          </div>
        </div>
      </div>

      <!-- COUNTED: Summary Variance -->
      <div
        v-if="opname.status === 'COUNTED' || opname.status === 'POSTED'"
        class="rounded-lg bg-amber-50 border border-amber-200 p-2.5"
      >
        <p class="text-[11px] font-medium text-amber-900">
          <span v-if="opname.status === 'COUNTED'">
            ✓ Penghitungan selesai. Review selisih di bawah, lalu klik <strong>Posting</strong> untuk membukukan penyesuaian stok, atau <strong>Buka Kembali</strong> untuk menghitung ulang.
          </span>
          <span v-else>
            ✓ Opname telah diposting. Selisih stok sudah tercatat sebagai movement OPNAME_IN / OPNAME_OUT.
          </span>
        </p>
      </div>

      <!-- Items Table -->
      <div class="mt-4 bg-white shadow-2xs border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-4 py-2.5 sm:px-4 border-b border-gray-100 flex items-center justify-between">
          <h3 class="text-xs font-semibold leading-5 text-gray-900">
            Daftar Item
            <span class="ml-2 text-[11px] font-normal text-gray-500">
              ({{ opname.items?.length ?? 0 }} produk)
            </span>
          </h3>
          <!-- Show counted progress when IN_PROGRESS -->
          <span
            v-if="opname.status === 'IN_PROGRESS'"
            class="text-xs text-gray-600"
          >
            Sudah dihitung:
            <span class="font-semibold text-indigo-700">{{ countedCount }}</span>
            /
            {{ opname.items?.length ?? 0 }}
          </span>
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
                  Stok Sistem
                </th>
                <!-- Only show counted/variance if COUNTED or POSTED -->
                <template v-if="opname.status === 'COUNTED' || opname.status === 'POSTED'">
                  <th
                    scope="col"
                    class="py-1.5 px-2 text-right whitespace-nowrap"
                  >
                    Hitung Fisik
                  </th>
                  <th
                    scope="col"
                    class="py-1.5 px-2 text-right whitespace-nowrap"
                  >
                    Selisih
                  </th>
                  <th
                    scope="col"
                    class="py-1.5 px-2 text-right whitespace-nowrap"
                  >
                    Nilai Selisih
                  </th>
                </template>
                <!-- Only show is_counted when IN_PROGRESS -->
                <template v-else-if="opname.status === 'IN_PROGRESS'">
                  <th
                    scope="col"
                    class="py-1.5 px-2 text-center whitespace-nowrap"
                  >
                    Status Hitung
                  </th>
                </template>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <tr v-if="!opname.items || opname.items.length === 0">
                <td
                  :colspan="opname.status === 'IN_PROGRESS' ? 6 : 8"
                  class="py-8 text-center text-xs text-gray-500"
                >
                  Belum ada item.
                </td>
              </tr>
              <tr
                v-for="(item, index) in opname.items"
                :key="item.id"
                class="hover:bg-gray-50/80 transition-colors"
              >
                <td class="py-1.5 px-1.5 text-center text-gray-400 font-mono text-[11px] whitespace-nowrap">
                  {{ index + 1 }}
                </td>
                <td class="py-1.5 px-2 whitespace-nowrap text-[11px]">
                  <span class="font-medium text-gray-900 leading-tight">{{ item.product?.name || item.product_name || '-' }}</span>
                  <span
                    v-if="item.is_unexpected"
                    class="ml-1 inline-flex items-center rounded px-1 py-0.2 text-[9px] font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200"
                  >
                    Tak Terduga
                  </span>
                </td>
                <td class="py-1.5 px-2 text-[10px] text-gray-400 font-mono whitespace-nowrap">
                  {{ item.product?.sku || '-' }}
                </td>
                <td class="py-1.5 px-2 text-right font-mono text-gray-900 text-[11px] whitespace-nowrap">
                  {{ formatRupiah(item.product_unit_price || item.product?.unit_price || 0) }}
                </td>
                <td class="py-1.5 px-2 font-mono text-right text-gray-900 text-[11px] whitespace-nowrap">
                  {{ formatQuantity(item.snapshot_quantity, false) }}
                </td>
                <!-- COUNTED / POSTED columns -->
                <template v-if="opname.status === 'COUNTED' || opname.status === 'POSTED'">
                  <td class="py-1.5 px-2 font-mono text-right text-gray-900 text-[11px] whitespace-nowrap">
                    {{ item.counted_quantity !== null && item.counted_quantity !== undefined ? formatQuantity(item.counted_quantity, false) : '-' }}
                  </td>
                  <td
                    class="py-1.5 px-2 text-[11px] font-mono text-right font-semibold whitespace-nowrap"
                    :class="{
                      'text-emerald-700': item.variance_quantity && !item.variance_quantity.startsWith('-') && item.variance_quantity !== '0.0000' && item.variance_quantity !== '0',
                      'text-rose-700': item.variance_quantity && item.variance_quantity.startsWith('-'),
                      'text-gray-500': !item.variance_quantity || item.variance_quantity === '0.0000' || item.variance_quantity === '0',
                    }"
                  >
                    {{ item.variance_quantity !== null && item.variance_quantity !== undefined
                      ? ((item.variance_quantity && !item.variance_quantity.startsWith('-') && item.variance_quantity !== '0.0000' && item.variance_quantity !== '0' ? '+' : '') + formatQuantity(item.variance_quantity, false))
                      : '-' }}
                  </td>
                  <td class="py-1.5 px-2 text-[11px] font-mono text-right text-gray-900 font-medium whitespace-nowrap">
                    {{ formatRupiah(Math.abs(Number(item.variance_quantity || 0)) * (item.product_unit_price || item.product?.unit_price || 0)) }}
                  </td>
                </template>
                <!-- IN_PROGRESS: show counted badge -->
                <template v-else-if="opname.status === 'IN_PROGRESS'">
                  <td class="py-1.5 px-2 text-[11px] text-center whitespace-nowrap">
                    <span
                      v-if="item.is_counted"
                      class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200"
                    >
                      ✓ Sudah Dihitung
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-gray-100 text-gray-600 border border-gray-200"
                    >
                      Belum
                    </span>
                  </td>
                </template>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- Confirm Dialogs -->
    <BaseConfirmation
      v-if="confirmType === 'start'"
      :model-value="true"
      title="Mulai Sesi Opname"
      confirm-label="Ya, Mulai Opname"
      variant="primary"
      :loading="store.loadingAction.start"
      @confirm="executeAction"
      @cancel="confirmType = null"
    >
      <template #description>
        <p>
          Sesi opname untuk lokasi <strong>{{ opname?.location_name }}</strong> akan dimulai.
          Lokasi tersebut akan <strong>dibekukan</strong> — transaksi stok masuk/keluar
          pada lokasi ini tidak dapat dilakukan selama opname berlangsung.
        </p>
      </template>
    </BaseConfirmation>

    <BaseConfirmation
      v-if="confirmType === 'complete'"
      :model-value="true"
      title="Selesaikan Penghitungan"
      confirm-label="Ya, Selesaikan Hitung"
      variant="warning"
      :loading="store.loadingAction.complete"
      @confirm="executeAction"
      @cancel="confirmType = null"
    >
      <template #description>
        <p>
          Penghitungan fisik akan diselesaikan. Sistem akan menghitung selisih (variance)
          antara stok sistem dan hasil hitung fisik. Anda masih dapat membuka kembali
          sebelum melakukan Posting.
        </p>
        <p
          v-if="!allItemsCounted"
          class="mt-2 text-orange-600 font-medium"
        >
          ⚠ Masih ada <strong>{{ uncountedCount }}</strong> item yang belum dihitung.
          Item tersebut akan dianggap variance = 0 (counted = snapshot).
        </p>
      </template>
    </BaseConfirmation>

    <BaseConfirmation
      v-if="confirmType === 'post'"
      :model-value="true"
      title="Posting Stock Opname"
      confirm-label="Ya, Posting"
      variant="primary"
      :loading="store.loadingAction.post"
      @confirm="executeAction"
      @cancel="confirmType = null"
    >
      <template #description>
        <p>
          Selisih stok (variance) akan dibukukan sebagai movement
          <strong>OPNAME_IN</strong> atau <strong>OPNAME_OUT</strong>.
          Tindakan ini tidak dapat diurungkan dan freeze pada lokasi akan dilepaskan.
        </p>
      </template>
    </BaseConfirmation>

    <!-- Reopen Dialog -->
    <ReopenOpnameDialog
      v-if="showReopenDialog"
      :loading="store.loadingAction.reopen"
      @confirm="executeReopen"
      @cancel="showReopenDialog = false"
    />

    <!-- Cancel Dialog -->
    <CancelOpnameDialog
      v-if="showCancelDialog"
      :loading="store.loadingAction.cancel"
      @confirm="executeCancel"
      @cancel="showCancelDialog = false"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useStockOpnameStore } from '../stores/useStockOpnameStore';
import StockOpnameStatusBadge from '../components/StockOpnameStatusBadge.vue';
import BaseConfirmation from '@/shared/components/BaseConfirmation.vue';
import ReopenOpnameDialog from '../components/ReopenOpnameDialog.vue';
import CancelOpnameDialog from '../components/CancelOpnameDialog.vue';
import { formatRupiah, formatQuantity } from '@/shared/utils/formatters.js';

const route = useRoute();
const store = useStockOpnameStore();

const opname = computed(() => store.currentOpname);
const abilities = computed(() => store.abilities);

const confirmType = ref(null);
const showReopenDialog = ref(false);
const showCancelDialog = ref(false);

const countedCount = computed(() =>
    opname.value?.items?.filter((i) => i.is_counted).length ?? 0,
);

const uncountedCount = computed(() =>
    (opname.value?.items?.length ?? 0) - countedCount.value,
);

const allItemsCounted = computed(() => uncountedCount.value === 0);

const isAnyActionLoading = computed(() =>
    Object.values(store.loadingAction).some(Boolean),
);

function openConfirm(type) {
    store.resetErrors();
    confirmType.value = type;
}

async function executeAction() {
    const id = opname.value?.id;
    if (!id || !confirmType.value) return;
    const type = confirmType.value;
    confirmType.value = null;

    try {
        if (type === 'start') await store.startOpname(id);
        else if (type === 'complete') await store.completeOpname(id);
        else if (type === 'post') await store.postOpname(id);
    } catch {
        // store holds normalized error
    }
}

async function executeReopen(payload) {
    const id = opname.value?.id;
    if (!id) return;
    try {
        await store.reopenOpname(id, payload);
        showReopenDialog.value = false;
    } catch {
        // dialog shows error via store
    }
}

async function executeCancel(payload) {
    const id = opname.value?.id;
    if (!id) return;
    try {
        await store.cancelOpname(id, payload);
        showCancelDialog.value = false;
    } catch {
        // store holds error
    }
}

onMounted(() => {
    store.resetActiveOpname();
    store.fetchOpname(route.params.id);
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
