<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 backdrop-blur-xs flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    @click.self="$emit('close')"
  >
    <div class="relative w-full max-w-5xl max-h-[92vh] flex flex-col rounded-xl bg-white shadow-2xl border border-gray-200 overflow-hidden">
      <!-- Modal Header -->
      <div class="px-6 py-3.5 border-b border-gray-100 flex items-start justify-between bg-white shrink-0">
        <div>
          <div class="flex items-center gap-2.5">
            <h2 class="text-base font-bold text-gray-900">
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
          <p class="text-xs text-gray-500 mt-0.5">
            Hak akses dikelompokkan hierarkis sesuai menu navigasi sistem. Fitur atau modul baru otomatis muncul pada daftar ini.
          </p>
        </div>

        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 transition-colors p-1.5 rounded-lg hover:bg-gray-100 cursor-pointer"
          title="Tutup Modal"
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

      <!-- Quick Search, Stats & Global Controls Bar -->
      <div class="px-6 py-2.5 bg-gray-50 border-b border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
        <!-- Search Input -->
        <div class="w-full sm:max-w-xs relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg
              class="h-4 w-4 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
          </div>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari izin, nama modul, kode..."
            class="block w-full rounded-lg border border-gray-300 bg-white pl-9 pr-3 py-1.5 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900"
          >
        </div>

        <!-- Global Select/Deselect & Counter -->
        <div class="flex items-center gap-3 text-xs text-gray-600 flex-wrap">
          <div>
            Total Terpilih: <strong class="text-indigo-600 font-bold font-mono">{{ selectedPermissionIds.length }}</strong> dari {{ totalPermissionsCount }} izin
          </div>
          <div class="h-4 w-px bg-gray-300 hidden sm:block" />
          <button
            type="button"
            class="text-indigo-600 hover:text-indigo-800 font-semibold cursor-pointer transition-colors"
            @click="selectAllGlobal"
          >
            Pilih Semua Global
          </button>
          <button
            type="button"
            class="text-gray-500 hover:text-gray-700 cursor-pointer transition-colors"
            @click="deselectAllGlobal"
          >
            Kosongkan
          </button>
        </div>
      </div>

      <!-- Section Navigation Filter Tabs (Pills) -->
      <div class="px-6 py-2 bg-white border-b border-gray-100 flex items-center gap-1.5 overflow-x-auto shrink-0 custom-scrollbar">
        <button
          type="button"
          :class="[
            'px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors cursor-pointer',
            selectedSectionFilter === 'ALL'
              ? 'bg-indigo-600 text-white shadow-2xs'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80 hover:text-gray-900'
          ]"
          @click="selectedSectionFilter = 'ALL'"
        >
          Semua Menu ({{ totalPermissionsCount }})
        </button>

        <button
          v-for="section in groupedSections"
          :key="section.id"
          type="button"
          :class="[
            'px-2.5 py-1 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors cursor-pointer flex items-center gap-1.5',
            selectedSectionFilter === section.id
              ? 'bg-indigo-600 text-white shadow-2xs'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80 hover:text-gray-900'
          ]"
          @click="selectedSectionFilter = section.id"
        >
          <span>{{ section.title }}</span>
          <span
            :class="[
              'px-1.5 py-0.2 rounded-full text-[10px] font-mono',
              selectedSectionFilter === section.id
                ? 'bg-indigo-700 text-white'
                : 'bg-white text-gray-600 border border-gray-200'
            ]"
          >
            {{ getSectionSelectedCount(section) }}/{{ getSectionTotalCount(section) }}
          </span>
        </button>
      </div>

      <!-- Error Alert -->
      <div
        v-if="error"
        class="mx-6 mt-3 p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 text-xs flex items-center gap-2 shrink-0"
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
      <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-slate-50/50">
        <!-- Loop Sections (Menu Utama, Master Data, Transaksi Persediaan, Laporan, Pengaturan, dll) -->
        <div
          v-for="section in displayedSections"
          :key="section.id"
          class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-2xs"
        >
          <!-- Section Major Header -->
          <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-center gap-2.5">
              <!-- Section Icon -->
              <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                <svg
                  v-if="section.icon === 'dashboard'"
                  class="w-3.5 h-3.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                  />
                </svg>
                <svg
                  v-else-if="section.icon === 'database'"
                  class="w-3.5 h-3.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"
                  />
                </svg>
                <svg
                  v-else-if="section.icon === 'transfer'"
                  class="w-3.5 h-3.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                  />
                </svg>
                <svg
                  v-else-if="section.icon === 'chart'"
                  class="w-3.5 h-3.5"
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
                <svg
                  v-else-if="section.icon === 'users'"
                  class="w-3.5 h-3.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                  />
                </svg>
                <svg
                  v-else
                  class="w-3.5 h-3.5"
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
              </span>

              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                    {{ section.title }}
                  </h3>
                  <span class="text-[11px] font-mono text-indigo-700 bg-indigo-50 px-1.5 py-0.2 rounded border border-indigo-200 font-semibold">
                    {{ getSectionSelectedCount(section) }} / {{ getSectionTotalCount(section) }} aktif
                  </span>
                </div>
                <p class="text-[11px] text-gray-500">
                  {{ section.description }}
                </p>
              </div>
            </div>

            <!-- Section Quick Actions -->
            <div class="flex items-center gap-2 text-xs self-end sm:self-auto">
              <button
                type="button"
                class="text-indigo-600 hover:text-indigo-800 font-semibold cursor-pointer"
                @click="selectAllInSection(section)"
              >
                Pilih Semua {{ section.title }}
              </button>
              <span class="text-gray-300">|</span>
              <button
                type="button"
                class="text-gray-500 hover:text-gray-700 cursor-pointer"
                @click="deselectAllInSection(section)"
              >
                Batal
              </button>
            </div>
          </div>

          <!-- Subgroups inside Section (Cards) -->
          <div class="p-4 space-y-4 divide-y divide-gray-100">
            <div
              v-for="subgroup in section.subgroups"
              :key="subgroup.key"
              class="pt-3 first:pt-0"
            >
              <!-- Subgroup Header -->
              <div class="flex items-center justify-between mb-2.5">
                <div class="flex items-center gap-2">
                  <span class="w-1.5 h-1.5 rounded-full bg-indigo-500" />
                  <h4 class="text-xs font-bold text-gray-800">
                    {{ subgroup.label }}
                  </h4>
                  <span class="text-[11px] text-gray-400 font-mono">({{ getSubgroupSelectedCount(subgroup) }}/{{ subgroup.permissions.length }})</span>
                </div>

                <div class="flex items-center gap-2 text-[11px]">
                  <button
                    type="button"
                    class="text-indigo-600 hover:underline font-medium cursor-pointer"
                    @click="selectSubgroup(subgroup)"
                  >
                    Pilih
                  </button>
                  <span class="text-gray-300">|</span>
                  <button
                    type="button"
                    class="text-gray-500 hover:underline cursor-pointer"
                    @click="deselectSubgroup(subgroup)"
                  >
                    Batal
                  </button>
                </div>
              </div>

              <!-- Permission Items Grid (Clean Responsive Card Checkboxes) -->
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                <label
                  v-for="perm in subgroup.permissions"
                  :key="perm.id"
                  :class="[
                    'flex items-start p-2 rounded-lg border text-xs cursor-pointer transition-all',
                    selectedPermissionIds.includes(perm.id)
                      ? 'border-indigo-400 bg-indigo-50/50 shadow-2xs ring-1 ring-indigo-500/30'
                      : 'border-gray-200 bg-white hover:bg-gray-50/80'
                  ]"
                >
                  <input
                    v-model="selectedPermissionIds"
                    type="checkbox"
                    :value="perm.id"
                    class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-0.5 shrink-0"
                  >
                  <div class="ml-2 min-w-0">
                    <div class="font-semibold text-gray-900 leading-tight">
                      {{ perm.name }}
                    </div>
                    <div
                      class="text-[10px] font-mono text-gray-400 mt-0.5 truncate"
                      :title="perm.code"
                    >
                      {{ perm.code }}
                    </div>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty Search Result -->
        <div
          v-if="displayedSections.length === 0"
          class="py-12 text-center text-gray-400 text-xs bg-white rounded-xl border border-gray-200"
        >
          <svg
            class="w-8 h-8 text-gray-300 mx-auto mb-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <div class="font-medium text-gray-600">
            Tidak ada izin yang sesuai.
          </div>
          <div class="text-[11px] text-gray-400 mt-0.5">
            Coba gunakan kata kunci pencarian yang berbeda.
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-3 border-t border-gray-100 bg-gray-50 flex items-center justify-between shrink-0">
        <div class="text-xs text-gray-500 hidden sm:block">
          Perubahan langsung berlaku bagi seluruh pengguna dengan peran ini.
        </div>

        <div class="flex items-center gap-2.5 ml-auto">
          <button
            type="button"
            class="rounded-lg border border-gray-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-gray-700 shadow-2xs hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors cursor-pointer"
            @click="$emit('close')"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="saving"
            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 disabled:opacity-50 transition-colors focus:outline-none focus:ring-1 focus:ring-indigo-600 cursor-pointer"
            @click="handleSave"
          >
            <svg
              v-if="saving"
              class="animate-spin -ml-0.5 mr-1 h-3.5 w-3.5 text-white"
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
import { buildGroupedMenuSections } from '../utils/permission_grouping.js';

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
const selectedSectionFilter = ref('ALL');

// Membangun daftar section terstruktur sesuai menu sidebar
const groupedSections = computed(() => {
  return buildGroupedMenuSections(props.allPermissions, searchQuery.value);
});

// Filter section berdasarkan pill tab yang dipilih
const displayedSections = computed(() => {
  if (selectedSectionFilter.value === 'ALL') {
    return groupedSections.value;
  }
  return groupedSections.value.filter((s) => s.id === selectedSectionFilter.value);
});

// Menghitung seluruh permissions dalam bentuk flat list
const allFlatPermissions = computed(() => {
  const list = [];
  Object.values(props.allPermissions).forEach((perms) => {
    if (Array.isArray(perms)) {
      perms.forEach((p) => list.push(p));
    }
  });
  return list;
});

const totalPermissionsCount = computed(() => allFlatPermissions.value.length);

const getSubgroupSelectedCount = (subgroup) => {
  const ids = new Set(subgroup.permissions.map((p) => p.id));
  return selectedPermissionIds.value.filter((id) => ids.has(id)).length;
};

const getSectionSelectedCount = (section) => {
  const ids = new Set();
  section.subgroups.forEach((sg) => {
    sg.permissions.forEach((p) => ids.add(p.id));
  });
  return selectedPermissionIds.value.filter((id) => ids.has(id)).length;
};

const getSectionTotalCount = (section) => {
  let count = 0;
  section.subgroups.forEach((sg) => {
    count += sg.permissions.length;
  });
  return count;
};

watch(
  [() => props.isOpen, () => props.role],
  ([open, roleVal]) => {
    if (open) {
      searchQuery.value = '';
      selectedSectionFilter.value = 'ALL';
      if (roleVal?.permissions) {
        selectedPermissionIds.value = roleVal.permissions.map((p) => p.id);
      } else if (Array.isArray(roleVal?.permission_ids)) {
        selectedPermissionIds.value = [...roleVal.permission_ids];
      } else {
        selectedPermissionIds.value = [];
      }
    }
  },
  { immediate: true, deep: true }
);

const selectSubgroup = (subgroup) => {
  const idsToAdd = subgroup.permissions.map((p) => p.id);
  const currentSet = new Set(selectedPermissionIds.value);
  idsToAdd.forEach((id) => currentSet.add(id));
  selectedPermissionIds.value = Array.from(currentSet);
};

const deselectSubgroup = (subgroup) => {
  const idsToRemove = new Set(subgroup.permissions.map((p) => p.id));
  selectedPermissionIds.value = selectedPermissionIds.value.filter((id) => !idsToRemove.has(id));
};

const selectAllInSection = (section) => {
  const idsToAdd = [];
  section.subgroups.forEach((sg) => {
    sg.permissions.forEach((p) => idsToAdd.push(p.id));
  });
  const currentSet = new Set(selectedPermissionIds.value);
  idsToAdd.forEach((id) => currentSet.add(id));
  selectedPermissionIds.value = Array.from(currentSet);
};

const deselectAllInSection = (section) => {
  const idsToRemove = new Set();
  section.subgroups.forEach((sg) => {
    sg.permissions.forEach((p) => idsToRemove.add(p.id));
  });
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
