<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Detail Stock Opname
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Rincian sesi penghitungan stok fisik.
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex flex-wrap gap-2">
        <router-link
          to="/inventory/opnames"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
        >
          Kembali
        </router-link>

        <!-- Edit Draft -->
        <router-link
          v-if="abilities.can_update"
          :to="`/inventory/opnames/${opname.id}/edit`"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
        >
          Edit Draft
        </router-link>

        <!-- Start -->
        <button
          v-if="abilities.can_start"
          :disabled="isAnyActionLoading"
          class="block rounded-md bg-blue-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50"
          @click="openConfirm('start')"
        >
          Mulai Opname
        </button>

        <!-- Go to Counting Workspace -->
        <router-link
          v-if="opname?.status === 'IN_PROGRESS'"
          :to="`/inventory/opnames/${opname.id}/count`"
          class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
        >
          Ruang Hitung →
        </router-link>

        <!-- Complete -->
        <button
          v-if="abilities.can_complete"
          :disabled="isAnyActionLoading"
          class="block rounded-md bg-yellow-500 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-yellow-400 disabled:opacity-50"
          @click="openConfirm('complete')"
        >
          Selesai Hitung
        </button>

        <!-- Reopen -->
        <button
          v-if="abilities.can_reopen"
          :disabled="isAnyActionLoading"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-yellow-700 shadow-sm ring-1 ring-inset ring-yellow-400 hover:bg-yellow-50 disabled:opacity-50"
          @click="showReopenDialog = true"
        >
          Buka Kembali
        </button>

        <!-- Post -->
        <button
          v-if="abilities.can_post"
          :disabled="isAnyActionLoading"
          class="block rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50"
          @click="openConfirm('post')"
        >
          Posting
        </button>

        <!-- Cancel -->
        <button
          v-if="abilities.can_cancel"
          :disabled="isAnyActionLoading"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-red-600 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50 disabled:opacity-50"
          @click="showCancelDialog = true"
        >
          Batalkan
        </button>
      </div>
    </div>

    <!-- Loading skeleton -->
    <div
      v-if="store.loadingDetail && !opname"
      class="mt-8 text-center text-gray-500"
    >
      Memuat data opname...
    </div>

    <template v-else-if="opname">
      <!-- Error alert -->
      <div
        v-if="store.error"
        class="mt-4 rounded-md bg-red-50 p-4"
      >
        <p class="text-sm font-medium text-red-800">
          {{ store.error }}
        </p>
      </div>

      <!-- Document Info -->
      <div class="mt-8 overflow-hidden bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 flex items-center justify-between">
          <h3 class="text-base font-semibold leading-6 text-gray-900">
            Informasi Dokumen
          </h3>
          <StockOpnameStatusBadge :status="opname.status" />
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
          <dl class="sm:divide-y sm:divide-gray-200">
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Nomor Opname
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-mono font-medium">
                {{ opname.opname_number }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Lokasi
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ opname.location_name || '-' }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Tanggal Opname
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ opname.opname_date }}
              </dd>
            </div>
            <div
              v-if="opname.notes"
              class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
            >
              <dt class="text-sm font-medium text-gray-500">
                Catatan
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 whitespace-pre-line">
                {{ opname.notes }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Dibuat Oleh / Pada
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ opname.created_by || '-' }}
                <span
                  v-if="opname.created_at"
                  class="text-gray-500"
                >
                  ({{ opname.created_at }})
                </span>
              </dd>
            </div>
            <div
              v-if="opname.posted_by"
              class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
            >
              <dt class="text-sm font-medium text-gray-500">
                Diposting Oleh / Pada
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ opname.posted_by }} ({{ opname.posted_at }})
              </dd>
            </div>
            <div
              v-if="opname.canceled_at || opname.cancelled_at"
              class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6"
            >
              <dt class="text-sm font-medium text-gray-500">
                Dibatalkan Pada
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ opname.canceled_at || opname.cancelled_at }}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- COUNTED: Summary Variance -->
      <div
        v-if="opname.status === 'COUNTED' || opname.status === 'POSTED'"
        class="mt-6 rounded-md bg-yellow-50 border border-yellow-200 p-4"
      >
        <p class="text-sm font-medium text-yellow-800">
          <span v-if="opname.status === 'COUNTED'">
            ✓ Penghitungan selesai. Review selisih di bawah, lalu klik <strong>Posting</strong> untuk
            membukukan penyesuaian stok, atau <strong>Buka Kembali</strong> untuk menghitung ulang.
          </span>
          <span v-else>
            ✓ Opname telah diposting. Selisih stok sudah tercatat sebagai movement OPNAME_IN / OPNAME_OUT.
          </span>
        </p>
      </div>

      <!-- Items Table -->
      <div class="mt-6 bg-white shadow sm:rounded-lg overflow-hidden">
        <div class="px-4 py-5 sm:px-6 flex items-center justify-between">
          <h3 class="text-base font-semibold leading-6 text-gray-900">
            Daftar Item
            <span class="ml-2 text-sm font-normal text-gray-500">
              ({{ opname.items?.length ?? 0 }} produk)
            </span>
          </h3>
          <!-- Show counted progress when IN_PROGRESS -->
          <span
            v-if="opname.status === 'IN_PROGRESS'"
            class="text-sm text-gray-600"
          >
            Sudah dihitung:
            <span class="font-semibold text-indigo-700">{{ countedCount }}</span>
            /
            {{ opname.items?.length ?? 0 }}
          </span>
        </div>
        <div class="border-t border-gray-200 overflow-x-auto">
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
                  Stok Sistem
                </th>
                <!-- Only show counted/variance if COUNTED or POSTED -->
                <template v-if="opname.status === 'COUNTED' || opname.status === 'POSTED'">
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900"
                  >
                    Hitung Fisik
                  </th>
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900"
                  >
                    Selisih
                  </th>
                </template>
                <!-- Only show is_counted when IN_PROGRESS -->
                <template v-else-if="opname.status === 'IN_PROGRESS'">
                  <th
                    scope="col"
                    class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900"
                  >
                    Status Hitung
                  </th>
                </template>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-if="!opname.items || opname.items.length === 0">
                <td
                  :colspan="opname.status === 'IN_PROGRESS' ? 4 : 5"
                  class="py-8 text-center text-sm text-gray-500"
                >
                  Belum ada item.
                </td>
              </tr>
              <tr
                v-for="item in opname.items"
                :key="item.id"
              >
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                  {{ item.product?.name || item.product_name || '-' }}
                  <span
                    v-if="item.is_unexpected"
                    class="ml-1 inline-flex items-center rounded-full bg-orange-100 px-1.5 py-0.5 text-xs font-medium text-orange-800"
                  >
                    Tak Terduga
                  </span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 font-mono">
                  {{ item.product?.sku || '-' }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm font-mono text-right text-gray-900">
                  {{ item.snapshot_quantity ?? '-' }}
                </td>
                <!-- COUNTED / POSTED columns -->
                <template v-if="opname.status === 'COUNTED' || opname.status === 'POSTED'">
                  <td class="whitespace-nowrap px-3 py-4 text-sm font-mono text-right text-gray-900">
                    {{ item.counted_quantity ?? '-' }}
                  </td>
                  <td
                    class="whitespace-nowrap px-3 py-4 text-sm font-mono text-right font-semibold"
                    :class="{
                      'text-green-700': Number(item.variance_quantity) > 0,
                      'text-red-700': Number(item.variance_quantity) < 0,
                      'text-gray-500': Number(item.variance_quantity) === 0,
                    }"
                  >
                    {{ item.variance_quantity !== null && item.variance_quantity !== undefined
                      ? (Number(item.variance_quantity) > 0 ? '+' : '') + item.variance_quantity
                      : '-' }}
                  </td>
                </template>
                <!-- IN_PROGRESS: show counted badge -->
                <template v-else-if="opname.status === 'IN_PROGRESS'">
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-center">
                    <span
                      v-if="item.is_counted"
                      class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800"
                    >
                      ✓ Sudah Dihitung
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600"
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
    <ConfirmDialog
      v-if="confirmType === 'start'"
      title="Mulai Sesi Opname"
      confirm-text="Ya, Mulai Opname"
      variant="primary"
      :loading="store.loadingAction.start"
      @confirm="executeAction"
      @cancel="confirmType = null"
    >
      <p>
        Sesi opname untuk lokasi <strong>{{ opname?.location_name }}</strong> akan dimulai.
        Lokasi tersebut akan <strong>dibekukan</strong> — transaksi stok masuk/keluar
        pada lokasi ini tidak dapat dilakukan selama opname berlangsung.
      </p>
    </ConfirmDialog>

    <ConfirmDialog
      v-if="confirmType === 'complete'"
      title="Selesaikan Penghitungan"
      confirm-text="Ya, Selesaikan Hitung"
      variant="warning"
      :loading="store.loadingAction.complete"
      @confirm="executeAction"
      @cancel="confirmType = null"
    >
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
    </ConfirmDialog>

    <ConfirmDialog
      v-if="confirmType === 'post'"
      title="Posting Stock Opname"
      confirm-text="Ya, Posting"
      variant="primary"
      :loading="store.loadingAction.post"
      @confirm="executeAction"
      @cancel="confirmType = null"
    >
      <p>
        Selisih stok (variance) akan dibukukan sebagai movement
        <strong>OPNAME_IN</strong> atau <strong>OPNAME_OUT</strong>.
        Tindakan ini tidak dapat diurungkan dan freeze pada lokasi akan dilepaskan.
      </p>
    </ConfirmDialog>

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
import ConfirmDialog from '../components/ConfirmDialog.vue';
import ReopenOpnameDialog from '../components/ReopenOpnameDialog.vue';
import CancelOpnameDialog from '../components/CancelOpnameDialog.vue';

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
