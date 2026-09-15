<template>
  <div class="space-y-3">
    <!-- Role Cards Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      <div
        v-for="role in roles"
        :key="role.id"
        class="rounded-xl p-3.5 border border-gray-200 bg-white shadow-2xs flex flex-col justify-between"
      >
        <div>
          <div class="flex items-start justify-between gap-2">
            <div>
              <div class="flex items-center gap-1.5">
                <h3 class="text-xs font-bold text-gray-900">
                  {{ role.name }}
                </h3>
                <span
                  :class="[
                    'inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-mono font-semibold ring-1 ring-inset',
                    role.code === 'ADMIN'
                      ? 'bg-purple-50 text-purple-700 ring-purple-600/20'
                      : role.code === 'INVENTORY_SUPERVISOR'
                        ? 'bg-blue-50 text-blue-700 ring-blue-600/20'
                        : 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                  ]"
                >
                  {{ role.code }}
                </span>
              </div>
              <p class="text-[11px] text-gray-500 mt-0.5">
                {{ role.description || 'Peran otorisasi sistem persediaan.' }}
              </p>
            </div>
          </div>

          <div class="mt-2.5 pt-2 border-t border-gray-100 flex items-center justify-between text-[11px]">
            <span class="text-gray-500">
              Total Pengguna: <strong class="text-gray-800">{{ role.users_count ?? 0 }}</strong>
            </span>
            <span class="text-gray-500">
              Hak Akses: <strong class="text-indigo-600">{{ role.code === 'ADMIN' ? 'Semua (Penuh)' : (role.permissions?.length ?? 0) }}</strong>
            </span>
          </div>
        </div>

        <div class="mt-2.5 pt-2 border-t border-gray-100 flex items-center justify-end">
          <button
            v-if="role.code !== 'ADMIN'"
            type="button"
            class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 shadow-2xs hover:bg-gray-50 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors cursor-pointer"
            @click="$emit('edit-role-permissions', role)"
          >
            <svg
              class="w-3.5 h-3.5 text-gray-500"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
            Edit Hak Akses
          </button>
          <span
            v-else
            class="text-[10px] font-medium text-purple-700 bg-purple-50 px-2 py-0.5 rounded ring-1 ring-inset ring-purple-600/20"
          >
            Akses Penuh Permanen
          </span>
        </div>
      </div>
    </div>

    <!-- Permissions Breakdown Matrix by Group -->
    <div class="bg-white rounded-xl shadow-2xs border border-gray-200 overflow-hidden">
      <div class="px-3.5 py-2.5 border-b border-gray-200 bg-gray-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
        <div>
          <h3 class="text-xs font-bold text-gray-900">
            Matriks Hak Akses Berdasarkan Menu Sistem
          </h3>
          <p class="text-[11px] text-gray-500 mt-0.5">
            Daftar izin operasional dikelompokkan sesuai navigasi menu sidebar. Fitur atau modul baru otomatis muncul.
          </p>
        </div>

        <!-- Search in Matrix -->
        <div class="w-full sm:w-64 relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5">
            <svg
              class="h-3.5 w-3.5 text-gray-400"
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
            placeholder="Cari izin atau modul..."
            class="block w-full rounded-lg border border-gray-300 bg-white pl-8 pr-2.5 py-1 text-xs shadow-2xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 text-gray-900"
          >
        </div>
      </div>

      <div
        v-if="loading"
        class="py-12 text-center text-gray-500"
      >
        <div class="inline-flex items-center gap-2">
          <svg
            class="animate-spin h-5 w-5 text-indigo-600"
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
          <span class="text-xs font-medium">Memuat rincian hak akses...</span>
        </div>
      </div>

      <div
        v-else-if="groupedSections.length > 0"
        class="divide-y divide-gray-200"
      >
        <!-- Sections: Menu Utama, Master Data, Transaksi Persediaan, Laporan, Pengaturan, dll -->
        <div
          v-for="section in groupedSections"
          :key="section.id"
          class="p-4 space-y-3"
        >
          <!-- Major Section Header -->
          <div class="flex items-center justify-between border-b border-gray-100 pb-2">
            <div class="flex items-center gap-2">
              <span class="w-5 h-5 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                <svg
                  v-if="section.icon === 'dashboard'"
                  class="w-3 h-3"
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
                  class="w-3 h-3"
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
                  class="w-3 h-3"
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
                  class="w-3 h-3"
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
                  class="w-3 h-3"
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
                  class="w-3 h-3"
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

              <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                {{ section.title }}
              </h4>
              <span class="text-[11px] text-gray-400">
                ({{ section.subgroups.reduce((acc, sg) => acc + sg.permissions.length, 0) }} izin)
              </span>
            </div>
          </div>

          <!-- Subgroups inside Section -->
          <div class="space-y-3.5 pl-2">
            <div
              v-for="subgroup in section.subgroups"
              :key="subgroup.key"
              class="space-y-1.5"
            >
              <div class="flex items-center gap-1.5">
                <span class="h-1.5 w-1.5 rounded-full bg-indigo-500" />
                <h5 class="text-[11px] font-bold text-gray-700">
                  {{ subgroup.label }}
                </h5>
                <span class="text-[10px] text-gray-400 font-mono">({{ subgroup.permissions.length }})</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                <div
                  v-for="perm in subgroup.permissions"
                  :key="perm.id"
                  class="p-2 rounded-lg border border-gray-100 bg-gray-50/60 text-xs space-y-1 shadow-2xs hover:bg-gray-50 transition-colors"
                >
                  <div class="font-semibold text-gray-900 leading-tight">
                    {{ perm.name }}
                  </div>
                  <div class="text-[10px] font-mono text-gray-400 truncate" :title="perm.code">
                    {{ perm.code }}
                  </div>

                  <!-- Roles that have this permission -->
                  <div class="flex flex-wrap gap-1 pt-1 border-t border-gray-100">
                    <span
                      v-for="role in rolesWithPermission(perm.code)"
                      :key="role.id"
                      :class="[
                        'inline-flex items-center px-1.5 py-0.2 rounded text-[9px] font-semibold',
                        role.code === 'ADMIN'
                          ? 'bg-purple-100 text-purple-800'
                          : role.code === 'INVENTORY_SUPERVISOR'
                            ? 'bg-blue-100 text-blue-800'
                            : 'bg-emerald-100 text-emerald-800'
                      ]"
                    >
                      {{ role.name }}
                    </span>
                    <span
                      v-if="rolesWithPermission(perm.code).length === 0"
                      class="text-[10px] text-gray-400 italic"
                    >
                      Tidak ada peran
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty Search Result -->
      <div
        v-else
        class="py-12 text-center text-gray-400 text-xs"
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
        <div class="font-medium text-gray-600">Tidak ada izin yang sesuai dengan pencarian.</div>
        <div class="text-[11px] text-gray-400 mt-0.5">Coba kata kunci lain atau bersihkan kotak pencarian.</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { buildGroupedMenuSections } from '../utils/permission_grouping.js';

const props = defineProps({
  roles: {
    type: Array,
    default: () => [],
  },
  allPermissions: {
    type: Object,
    default: () => ({}),
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['edit-role-permissions']);

const searchQuery = ref('');

const groupedSections = computed(() => {
  return buildGroupedMenuSections(props.allPermissions, searchQuery.value);
});

const rolesWithPermission = (permCode) => {
  return props.roles.filter((role) => {
    if (role.code === 'ADMIN') return true; // Admin has all permissions
    return role.permissions?.some((p) => p.code === permCode);
  });
};
</script>
