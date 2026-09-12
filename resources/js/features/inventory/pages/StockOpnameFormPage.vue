<template>
  <div class="space-y-2.5">
    <!-- Top Compact Header Bar -->
    <div class="bg-white rounded-xl border border-gray-200 px-3.5 py-2.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <!-- Left: Title & Status -->
      <div class="flex items-center gap-2.5">
        <router-link
          :to="isEdit ? `/inventory/opnames/${route.params.id}` : '/inventory/opnames'"
          class="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors cursor-pointer"
          title="Kembali ke daftar opname"
        >
          <svg
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
        </router-link>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-base font-bold text-gray-900 leading-tight">
              {{ isEdit ? 'Edit Draft Stock Opname' : 'Inisiasi Sesi Stock Opname' }}
            </h1>
            <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase tracking-wide">
              {{ isEdit ? 'Edit Draft' : 'Sesi Baru' }}
            </span>
          </div>
          <p class="text-[11px] text-gray-500 hidden sm:block">
            Tentukan lokasi gudang dan tanggal untuk memulai sesi penghitungan fisik stok.
          </p>
        </div>
      </div>

      <!-- Right: Main Actions -->
      <div class="flex items-center gap-2 self-end sm:self-auto">
        <router-link
          :to="isEdit ? `/inventory/opnames/${route.params.id}` : '/inventory/opnames'"
          class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 border border-gray-300 shadow-2xs hover:bg-gray-50 transition-colors cursor-pointer"
        >
          Batal
        </router-link>
        <button
          id="btn-save-opname-draft"
          type="button"
          :disabled="store.loadingAction?.save"
          class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50 transition-colors cursor-pointer"
          title="Simpan Dokumen Sesi Opname (Shortcut: F9)"
          @click="submitForm"
        >
          <svg
            class="w-3.5 h-3.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"
            />
          </svg>
          <span>{{ store.loadingAction?.save ? 'Menyimpan...' : 'Simpan Draft' }}</span>
          <kbd class="hidden sm:inline-block font-mono text-[9px] bg-indigo-700/90 text-indigo-100 px-1 py-0.2 rounded border border-indigo-400 font-bold">F9</kbd>
        </button>
      </div>
    </div>

    <!-- Error Alert -->
    <div
      v-if="store.error"
      class="rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-xs text-rose-800 flex items-center justify-between gap-2"
    >
      <div class="flex items-center gap-2">
        <svg
          class="w-4 h-4 text-rose-500 shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>{{ store.error }}</span>
      </div>
      <button
        type="button"
        class="text-rose-400 hover:text-rose-600 cursor-pointer"
        @click="store.error = null"
      >
        &times;
      </button>
    </div>

    <!-- Form Section -->
    <form
      class="space-y-2.5"
      @submit.prevent="submitForm"
    >
      <!-- Opname Metadata Horizontal Card -->
      <div class="bg-white rounded-xl border border-gray-200 p-3 sm:p-4 shadow-2xs">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- Location -->
          <div>
            <label
              for="location_id"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Lokasi Penghitungan *
            </label>
            <BaseCombobox
              id="location_id"
              v-model="form.location_id"
              :options="locations"
              size="xs"
              placeholder="Pilih lokasi gudang..."
              :disabled="isEdit"
              required
            />
            <p
              v-if="store.validationErrors?.location_id"
              class="mt-1 text-[10px] text-rose-600"
            >
              {{ store.validationErrors.location_id[0] }}
            </p>
          </div>

          <!-- Opname Date -->
          <div>
            <label
              for="opname_date"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Tanggal Opname *
            </label>
            <input
              id="opname_date"
              v-model="form.opname_date"
              type="date"
              :max="today"
              class="block w-full rounded-lg border-gray-300 py-1.5 px-2.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
              required
            >
            <p
              v-if="store.validationErrors?.opname_date"
              class="mt-1 text-[10px] text-rose-600"
            >
              {{ store.validationErrors.opname_date[0] }}
            </p>
          </div>

          <!-- Notes -->
          <div>
            <label
              for="notes"
              class="block text-[11px] font-semibold text-gray-600 mb-1"
            >
              Catatan / Keterangan
            </label>
            <input
              id="notes"
              v-model="form.notes"
              type="text"
              placeholder="Opsional — catatan tambahan sesi opname..."
              class="block w-full rounded-lg border-gray-300 py-1.5 px-2.5 text-xs focus:border-indigo-500 focus:ring-indigo-500"
            >
          </div>
        </div>
      </div>

      <!-- Compact Information Banner -->
      <div class="rounded-xl bg-blue-50/80 border border-blue-200/70 p-3 text-xs text-blue-800 flex items-start gap-2.5 shadow-2xs">
        <svg
          class="w-4 h-4 text-blue-600 shrink-0 mt-0.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <div class="leading-relaxed">
          <span class="font-bold">Informasi Sesi:</span> Daftar produk yang dihitung akan otomatis diambil dari saldo persediaan di lokasi yang dipilih ketika sesi dimulai (<em>Start</em>). Produk dengan saldo nol juga disertakan, dan produk baru/tidak terduga dapat ditambahkan secara langsung saat penghitungan berlangsung.
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useStockOpnameStore } from '../stores/useStockOpnameStore';
import apiClient from '@/shared/api/api_client';
import BaseCombobox from '@/shared/components/BaseCombobox.vue';

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
        let res = await apiClient.get('/locations', { params: { is_active: 1, assigned_only: 1, per_page: 1000 } });
        let loaded = res.data.data.data ?? res.data.data ?? [];
        if (loaded.length === 0) {
            res = await apiClient.get('/locations', { params: { is_active: 1, per_page: 1000 } });
            loaded = res.data.data.data ?? res.data.data ?? [];
        }
        locations.value = loaded;

        // Default to ADM location if not in edit mode
        if (!isEdit && locations.value.length > 0) {
            if (route.query.location_id) {
                const queryLoc = locations.value.find((l) => String(l.id) === String(route.query.location_id));
                if (queryLoc) {
                    form.value.location_id = queryLoc.id;
                    return;
                }
            }
            if (!form.value.location_id) {
                const admLoc = locations.value.find((l) =>
                    (l.code && l.code.toUpperCase() === 'ADM') ||
                    (l.name && l.name.toUpperCase().includes('ADM'))
                );
                form.value.location_id = admLoc ? admLoc.id : locations.value[0].id;
            }
        }
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

const handleGlobalKeyDown = (e) => {
    if (e.key === 'F9') {
        e.preventDefault();
        submitForm();
    }
};

onMounted(async () => {
    window.addEventListener('keydown', handleGlobalKeyDown);
    await loadLocations();
    if (isEdit) {
        await loadOpname();
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeyDown);
});
</script>
