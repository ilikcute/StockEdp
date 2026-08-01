<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          Detail Pengeluaran Stok
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          Rincian dokumen mutasi barang keluar.
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex gap-2">
        <router-link
          to="/inventory/issues"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
        >
          Kembali
        </router-link>
        
        <router-link
          v-if="store.currentIssue?.status === 'DRAFT' && hasPermission('stock_issues.update')"
          :to="`/inventory/issues/${store.currentIssue.id}/edit`"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
        >
          Edit Draft
        </router-link>

        <button
          v-if="store.currentIssue?.status === 'DRAFT' && hasPermission('stock_issues.cancel')"
          :disabled="isProcessing"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-red-600 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50 disabled:opacity-50"
          @click="handleCancel"
        >
          Batalkan Draft
        </button>

        <button
          v-if="store.currentIssue?.status === 'DRAFT' && hasPermission('stock_issues.post')"
          :disabled="isProcessing"
          class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          @click="handlePost"
        >
          Post Dokumen
        </button>
      </div>
    </div>

    <div
      v-if="store.loading && !store.currentIssue"
      class="mt-8 text-center text-gray-500"
    >
      Memuat data...
    </div>

    <div
      v-else-if="store.currentIssue"
      class="mt-8"
    >
      <div
        v-if="store.error"
        class="mb-4 rounded-md bg-red-50 p-4"
      >
        <p class="text-sm font-medium text-red-800">
          {{ store.error }}
        </p>
      </div>

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
                {{ store.currentIssue.issue_number }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Status
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                <span 
                  class="px-2 py-1 text-xs font-semibold rounded-full"
                  :class="{
                    'bg-yellow-100 text-yellow-800': store.currentIssue.status === 'DRAFT',
                    'bg-green-100 text-green-800': store.currentIssue.status === 'POSTED',
                    'bg-gray-100 text-gray-800': store.currentIssue.status === 'CANCELED'
                  }"
                >
                  {{ store.currentIssue.status }}
                </span>
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Tanggal Pengeluaran
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ store.currentIssue.date }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Tujuan / Alasan
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ store.currentIssue.purpose }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Catatan
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ store.currentIssue.notes || '-' }}
              </dd>
            </div>
            <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500">
                Dibuat Oleh
              </dt>
              <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                {{ store.currentIssue.creator?.name }}
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <div class="mt-8">
        <h3 class="text-base font-semibold leading-6 text-gray-900 mb-4">
          Item Pengeluaran
        </h3>
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
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
                  Lokasi Gudang
                </th>
                <th
                  scope="col"
                  class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900"
                >
                  Kuantitas
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr
                v-for="item in store.currentIssue.items"
                :key="item.id"
              >
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                  <div class="font-medium text-gray-900">
                    {{ item.product?.name }}
                  </div>
                  <div class="text-gray-500 font-mono text-xs">
                    {{ item.product?.sku }}
                  </div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                  {{ item.location?.name }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 text-right font-mono font-medium">
                  {{ Number(item.quantity).toFixed(4) }} {{ item.product?.unit?.symbol }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useStockIssueStore } from '../stores/useStockIssueStore';
import { useAuthStore } from '@features/auth/stores/use_auth_store';

const route = useRoute();
const store = useStockIssueStore();
const authStore = useAuthStore();
const isProcessing = ref(false);

const id = route.params.id;

const hasPermission = (permission) => {
    return authStore.hasPermission(permission);
};

const handlePost = async () => {
    if (!confirm('Apakah Anda yakin ingin memposting dokumen ini? Saldo stok akan berkurang dan dokumen tidak dapat diubah lagi. Validasi stok akhir dilakukan di server.')) {
        return;
    }
    
    isProcessing.value = true;
    try {
        await store.postIssue(id);
        alert('Dokumen berhasil di-posting.');
    } catch {
        // Error handled in store
    } finally {
        isProcessing.value = false;
    }
};

const handleCancel = async () => {
    if (!confirm('Apakah Anda yakin ingin membatalkan draft ini?')) {
        return;
    }
    
    isProcessing.value = true;
    try {
        await store.cancelIssue(id);
        alert('Draft berhasil dibatalkan.');
    } catch {
        // Error handled in store
    } finally {
        isProcessing.value = false;
    }
};

onMounted(() => {
    store.fetchIssueById(id);
});
</script>
