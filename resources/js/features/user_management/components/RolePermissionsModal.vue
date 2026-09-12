<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-xs flex items-center justify-center p-4"
    @click.self="$emit('close')"
  >
    <div class="relative w-full max-w-4xl max-h-[90vh] flex flex-col rounded-xl bg-white shadow-xl border border-gray-200 overflow-hidden">
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-gray-100 flex items-start justify-between bg-white shrink-0">
        <div>
          <div class="flex items-center gap-2.5">
            <h2 class="text-lg font-bold text-gray-900">
              Kelola Hak Akses: {{ role?.name }}
            </h2>
            <span
              :class="[
                'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-mono font-semibold ring-1 ring-inset',
                role?.code === 'INVENTORY_SUPERVISOR'
                  ? 'bg-blue-50 text-blue-700 ring-blue-600/20'
                  : 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
              ]"
            >
              {{ role?.code }}
            </span>
          </div>
          <p class="text-xs text-gray-500 mt-1">
            Centang atau batalkan centang izin operasional untuk menentukan akses halaman dan fitur bagi peran ini.
          </p>
        </div>

        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-lg hover:bg-gray-100 cursor-pointer"
          @click="$emit('close')"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Quick Search & Stats Bar -->
      <div class="px-6 py-3 bg-gray-50/80 border-b border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
        <div class="w-full sm:max-w-xs relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg
              class="h-4 w-4 text-gray-400"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                clip-rule="evenodd"
              />
            </svg>
          </div>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nama atau kode izin..."
            class="block w-full rounded-md border border-gray-300 bg-white pl-9 pr-3 py-1.5 text-xs shadow-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900"
          >
        </div>

        <div class="flex items-center gap-3 text-xs text-gray-600">
          <div>
            Total Terpilih: <strong class="text-indigo-600 font-bold">{{ selectedPermissionIds.length }}</strong> dari {{ totalPermissionsCount }} izin
          </div>
          <div class="h-4 w-px bg-gray-300" />
          <button
            type="button"
            class="text-indigo-600 hover:text-indigo-800 font-semibold cursor-pointer"
            @click="selectAllGlobal"
          >
            Pilih Semua
          </button>
          <button
            type="button"
            class="text-gray-500 hover:text-gray-700 cursor-pointer"
            @click="deselectAllGlobal"
          >
            Kosongkan
          </button>
        </div>
      </div>

      <!-- Error Alert -->
      <div
        v-if="error"
        class="mx-6 mt-4 p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 text-xs flex items-center gap-2 shrink-0"
      >
        <svg
          class="w-4 h-4 text-rose-600 shrink-0"
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
        <span>{{ error }}</span>
      </div>

      <!-- Permissions List Scrollable Body -->
      <div class="flex-1 overflow-y-auto p-6 space-y-6">
        <div
          v-for="(perms, groupKey) in filteredPermissions"
          :key="groupKey"
          class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-2xs"
        >
          <!-- Group Header -->
          <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="h-2 w-2 rounded-full bg-indigo-600" />
              <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                {{ formatGroupName(groupKey) }}
              </h3>
              <span class="text-xs text-gray-400">({{ perms.length }} izin)</span>
            </div>

            <div class="flex items-center gap-2 text-[11px]">
              <button
                type="button"
                class="text-indigo-600 hover:underline font-medium cursor-pointer"
                @click="selectGroup(perms)"
              >
                Pilih Semua
              </button>
              <span class="text-gray-300">|</span>
              <button
                type="button"
                class="text-gray-500 hover:underline cursor-pointer"
                @click="deselectGroup(perms)"
              >
                Batal
              </button>
            </div>
          </div>

          <!-- Group Permission Items Grid -->
          <div class="p-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
            <label
              v-for="perm in perms"
              :key="perm.id"
              :class="[
                'flex items-start p-2.5 rounded-lg border text-xs cursor-pointer transition-all',
                selectedPermissionIds.includes(perm.id)
                  ? 'border-indigo-500 bg-indigo-50/40 ring-1 ring-indigo-500'
                  : 'border-gray-200 bg-white hover:bg-gray-50'
              ]"
            >
              <input
                v-model="selectedPermissionIds"
                type="checkbox"
                :value="perm.id"
                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-0.5 shrink-0"
              >
              <div class="ml-2.5">
                <div class="font-semibold text-gray-900 leading-tight">
                  {{ perm.name }}
                </div>
                <div class="text-[11px] font-mono text-gray-400 mt-0.5">
                  {{ perm.code }}
                </div>
              </div>
            </label>
          </div>
        </div>

        <div
          v-if="Object.keys(filteredPermissions).length === 0"
          class="py-8 text-center text-gray-400 text-xs"
        >
          Tidak ditemukan izin yang sesuai dengan kata kunci pencarian "{{ searchQuery }}".
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex items-center justify-between shrink-0">
        <div class="text-xs text-gray-500">
          Perubahan akan langsung berlaku bagi seluruh pengguna dengan peran ini.
        </div>

        <div class="flex items-center gap-3">
          <button
            type="button"
            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors cursor-pointer"
            @click="$emit('close')"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="saving"
            class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 disabled:opacity-50 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-600 cursor-pointer"
            @click="handleSave"
          >
            <svg
              v-if="saving"
              class="animate-spin -ml-0.5 mr-1 h-4 w-4 text-white"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v8H4z"
              />
            </svg>
            <span>{{ saving ? 'Menyimpan...' : 'Simpan Hak Akses' }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  role: {
    type: Object,
    default: null,
  },
  allPermissions: {
    type: Object,
    default: () => ({}),
  },
  saving: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['close', 'save']);

const selectedPermissionIds = ref([]);
const searchQuery = ref('');

const groupLabels = {
  products: 'Master Data Produk',
  categories: 'Master Data Kategori',
  units: 'Master Data Satuan',
  suppliers: 'Master Data Supplier',
  locations: 'Master Data Lokasi Gudang',
  inventory: 'Persediaan & Saldo Stok',
  stock_receipts: 'Penerimaan Stok (Inbound)',
  stock_issues: 'Pengeluaran Stok (Outbound)',
  stock_transfers: 'Transfer Antar Gudang',
  stock_adjustments: 'Penyesuaian Stok (Stock Adjustment)',
  stock_opnames: 'Stock Opname Fisik',
  dashboard: 'Dashboard Operasional',
  replenishment: 'Rekomendasi Reorder',
  reports: 'Laporan & Ekspor Persediaan',
  users: 'Pengelolaan Pengguna & Hak Akses',
};

const formatGroupName = (group) => {
  return groupLabels[group] || group.replace(/_/g, ' ');
};

const totalPermissionsCount = computed(() => {
  let count = 0;
  Object.values(props.allPermissions).forEach((perms) => {
    count += perms.length;
  });
  return count;
});

const allFlatPermissions = computed(() => {
  const list = [];
  Object.values(props.allPermissions).forEach((perms) => {
    perms.forEach((p) => list.push(p));
  });
  return list;
});

const filteredPermissions = computed(() => {
  if (!searchQuery.value.trim()) {
    return props.allPermissions;
  }

  const query = searchQuery.value.toLowerCase().trim();
  const result = {};

  Object.entries(props.allPermissions).forEach(([group, perms]) => {
    const matched = perms.filter(
      (p) => p.name.toLowerCase().includes(query) || p.code.toLowerCase().includes(query)
    );
    if (matched.length > 0) {
      result[group] = matched;
    }
  });

  return result;
});

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      searchQuery.value = '';
      if (props.role?.permissions) {
        selectedPermissionIds.value = props.role.permissions.map((p) => p.id);
      } else if (Array.isArray(props.role?.permission_ids)) {
        selectedPermissionIds.value = [...props.role.permission_ids];
      } else {
        selectedPermissionIds.value = [];
      }
    }
  },
  { immediate: true }
);

const selectGroup = (perms) => {
  const idsToAdd = perms.map((p) => p.id);
  const currentSet = new Set(selectedPermissionIds.value);
  idsToAdd.forEach((id) => currentSet.add(id));
  selectedPermissionIds.value = Array.from(currentSet);
};

const deselectGroup = (perms) => {
  const idsToRemove = new Set(perms.map((p) => p.id));
  selectedPermissionIds.value = selectedPermissionIds.value.filter((id) => !idsToRemove.has(id));
};

const selectAllGlobal = () => {
  selectedPermissionIds.value = allFlatPermissions.value.map((p) => p.id);
};

const deselectAllGlobal = () => {
  selectedPermissionIds.value = [];
};

const handleSave = () => {
  emit('save', {
    roleId: props.role.id,
    permissionIds: selectedPermissionIds.value,
  });
};
</script>
