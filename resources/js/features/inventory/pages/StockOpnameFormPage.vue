<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <h1 class="text-xl font-semibold text-gray-900">
          {{ isEdit ? 'Edit Draft Stock Opname' : 'Buat Sesi Stock Opname' }}
        </h1>
        <p class="mt-2 text-sm text-gray-700">
          {{ isEdit
            ? 'Ubah header dokumen stock opname (hanya status DRAFT).'
            : 'Tentukan lokasi dan tanggal sesi penghitungan stok.' }}
        </p>
      </div>
      <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex gap-2">
        <router-link
          :to="isEdit ? `/inventory/opnames/${route.params.id}` : '/inventory/opnames'"
          class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
        >
          Batal
        </router-link>
        <button
          :disabled="store.loadingAction.save"
          class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          @click="submitForm"
        >
          {{ store.loadingAction.save ? 'Menyimpan...' : 'Simpan Draft' }}
        </button>
      </div>
    </div>

    <!-- Error -->
    <div
      v-if="store.error"
      class="mt-4 rounded-md bg-red-50 p-4"
    >
      <p class="text-sm font-medium text-red-800">
        {{ store.error }}
      </p>
    </div>

    <form
      class="mt-8 space-y-6"
      @submit.prevent="submitForm"
    >
      <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6 bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
        <!-- Location -->
        <div class="sm:col-span-3">
          <label
            for="location_id"
            class="block text-sm font-medium text-gray-700"
          >
            Lokasi Penghitungan
            <span
              class="text-red-500"
              aria-hidden="true"
            >*</span>
          </label>
          <div class="mt-1">
            <select
              id="location_id"
              v-model="form.location_id"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :class="{ 'border-red-500': store.validationErrors.location_id }"
              :disabled="isEdit"
              required
            >
              <option
                value=""
                disabled
              >
                Pilih Lokasi
              </option>
              <option
                v-for="loc in locations"
                :key="loc.id"
                :value="loc.id"
              >
                {{ loc.code }} — {{ loc.name }}
              </option>
            </select>
            <p
              v-if="store.validationErrors.location_id"
              class="mt-1 text-sm text-red-600"
            >
              {{ store.validationErrors.location_id[0] }}
            </p>
          </div>
        </div>

        <!-- Opname Date -->
        <div class="sm:col-span-3">
          <label
            for="opname_date"
            class="block text-sm font-medium text-gray-700"
          >
            Tanggal Opname
            <span
              class="text-red-500"
              aria-hidden="true"
            >*</span>
          </label>
          <div class="mt-1">
            <input
              id="opname_date"
              v-model="form.opname_date"
              type="date"
              :max="today"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :class="{ 'border-red-500': store.validationErrors.opname_date }"
              required
            >
            <p
              v-if="store.validationErrors.opname_date"
              class="mt-1 text-sm text-red-600"
            >
              {{ store.validationErrors.opname_date[0] }}
            </p>
          </div>
        </div>

        <!-- Notes -->
        <div class="sm:col-span-6">
          <label
            for="notes"
            class="block text-sm font-medium text-gray-700"
          >
            Catatan / Keterangan
          </label>
          <div class="mt-1">
            <textarea
              id="notes"
              v-model="form.notes"
              rows="3"
              placeholder="Opsional — catatan tambahan terkait sesi opname..."
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
        </div>
      </div>

      <!-- Informational note about items -->
      <div class="rounded-md bg-blue-50 p-4">
        <div class="flex">
          <div class="ml-3 flex-1 text-sm text-blue-700">
            <p>
              <strong>Catatan:</strong> Daftar produk yang dihitung akan otomatis diisi dari saldo persediaan
              di lokasi yang dipilih ketika sesi dimulai (<em>Start</em>). Produk dengan saldo nol juga
              disertakan. Produk tidak terduga dapat ditambahkan secara manual saat sesi berlangsung.
            </p>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStockOpnameStore } from '../stores/useStockOpnameStore';
import apiClient from '@/shared/api/api_client';

const route = useRoute();
const router = useRouter();
const store = useStockOpnameStore();

const isEdit = route.name === 'stockOpnamesEdit';
const today = new Date().toISOString().slice(0, 10);

const form = ref({
    location_id: '',
    opname_date: today,
    notes: '',
});

const locations = ref([]);

async function loadLocations() {
    try {
        const res = await apiClient.get('/locations', { params: { is_active: 1, per_page: 200 } });
        locations.value = res.data.data.data ?? res.data.data;
    } catch {
        store.error = 'Gagal memuat daftar lokasi.';
    }
}

async function loadOpname() {
    try {
        const data = await store.fetchOpname(route.params.id);
        if (data.status !== 'DRAFT') {
            router.push(`/inventory/opnames/${route.params.id}`);
            return;
        }
        form.value = {
            location_id: data.location_id,
            opname_date: data.opname_date,
            notes: data.notes ?? '',
        };
    } catch {
        store.error = store.error || 'Gagal memuat dokumen.';
    }
}

async function submitForm() {
    store.resetErrors();

    try {
        if (isEdit) {
            await store.updateOpname(route.params.id, {
                opname_date: form.value.opname_date,
                notes: form.value.notes,
            });
            router.push(`/inventory/opnames/${route.params.id}`);
        } else {
            const res = await store.createOpname(form.value);
            router.push(`/inventory/opnames/${res.data.id}`);
        }
    } catch {
        // store already holds normalized error
    }
}

onMounted(async () => {
    await loadLocations();
    if (isEdit) {
        await loadOpname();
    }
});
</script>
